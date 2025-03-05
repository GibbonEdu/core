<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright 2010, Gibbon Foundation
Gibbon, Gibbon Education Ltd. (Hong Kong)

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

use Gibbon\Module\Interventions\Domain\INInterventionGateway;
use Gibbon\Module\Interventions\Domain\INSupportPlanGateway;

require_once '../../gibbon.php';

$gibbonINInterventionID = $_POST['gibbonINInterventionID'] ?? '';
$gibbonINSupportPlanID = $_POST['gibbonINSupportPlanID'] ?? '';
$status = $_POST['status'] ?? '';

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Interventions/intervention_support_plan_status.php&gibbonINInterventionID='.$gibbonINInterventionID.'&gibbonINSupportPlanID='.$gibbonINSupportPlanID.'&status='.$status;
$URLSuccess = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Interventions/intervention_support_plan_view.php&gibbonINInterventionID='.$gibbonINInterventionID.'&gibbonINSupportPlanID='.$gibbonINSupportPlanID;

if (isActionAccessible($guid, $connection2, '/modules/Interventions/intervention_support_plan_status.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $interventionGateway = $container->get(INInterventionGateway::class);
    $supportPlanGateway = $container->get(INSupportPlanGateway::class);
    
    // Get intervention and support plan
    $intervention = $interventionGateway->getByID($gibbonINInterventionID);
    $supportPlan = $supportPlanGateway->getByID($gibbonINSupportPlanID);
    
    if (empty($intervention) || empty($supportPlan) || $supportPlan['gibbonINInterventionID'] != $gibbonINInterventionID) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }
    
    // Check access to this intervention
    $gibbonPersonID = $_SESSION[$guid]['gibbonPersonID'];
    $isAdmin = isActionAccessible($guid, $connection2, '/modules/Interventions/interventions_manage.php', 'Manage Interventions_all');
    
    if (!$isAdmin && $supportPlan['gibbonPersonIDCreator'] != $gibbonPersonID) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
        exit;
    }
    
    // Validate status
    $validStatuses = ['Draft', 'Active', 'Completed', 'Cancelled'];
    if (!in_array($status, $validStatuses)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }
    
    // Get outcome fields if provided
    $outcome = '';
    $outcomeNotes = '';
    
    if ($status == 'Completed' || $status == 'Cancelled') {
        $outcome = $_POST['outcome'] ?? '';
        $outcomeNotes = $_POST['outcomeNotes'] ?? '';
        
        if (empty($outcome)) {
            $URL .= '&return=error1';
            header("Location: {$URL}");
            exit;
        }
    }
    
    try {
        $data = [
            'status' => $status,
            'timestampModified' => date('Y-m-d H:i:s')
        ];
        
        // If status is changing to Active, set the start date
        if ($status == 'Active' && $supportPlan['status'] != 'Active') {
            $data['dateStart'] = date('Y-m-d');
        }
        
        // If status is changing to Completed or Cancelled, set the end date and outcome
        if (($status == 'Completed' || $status == 'Cancelled') && 
            ($supportPlan['status'] != 'Completed' && $supportPlan['status'] != 'Cancelled')) {
            $data['dateEnd'] = date('Y-m-d');
            $data['outcome'] = $outcome;
            $data['outcomeNotes'] = $outcomeNotes;
        }
        
        // Begin transaction
        $connection2->beginTransaction();
        
        // If setting this plan to active, deactivate any other active plans
        if ($status == 'Active' && $supportPlan['status'] != 'Active') {
            $sql = "UPDATE gibbonINSupportPlan SET gibbonINSupportPlan.status='Completed' WHERE gibbonINInterventionID=:gibbonINInterventionID AND gibbonINSupportPlan.status='Active' AND gibbonINSupportPlanID!=:gibbonINSupportPlanID";
            $stmt = $connection2->prepare($sql);
            $stmt->execute([
                'gibbonINInterventionID' => $gibbonINInterventionID,
                'gibbonINSupportPlanID' => $gibbonINSupportPlanID
            ]);
            
            // Update intervention status if not already in implementation
            if ($intervention['status'] != 'Support Plan Active') {
                $interventionGateway->update($gibbonINInterventionID, [
                    'status' => 'Support Plan Active'
                ]);
            }
        }
        
        // Update the support plan
        $supportPlanGateway->update($gibbonINSupportPlanID, $data);
        
        // Commit transaction
        $connection2->commit();
        
        $URLSuccess .= '&return=success0';
        header("Location: {$URLSuccess}");
        exit;
    } catch (Exception $e) {
        $connection2->rollBack();
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }
}
