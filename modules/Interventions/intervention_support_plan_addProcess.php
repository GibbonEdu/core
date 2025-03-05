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
$returnProcess = isset($_POST['returnProcess']) && $_POST['returnProcess'] == 'true';

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Interventions/intervention_support_plan_add.php&gibbonINInterventionID='.$gibbonINInterventionID;
$URLSuccess = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Interventions/intervention_process.php&gibbonINInterventionID='.$gibbonINInterventionID.'&step=3';

if ($returnProcess) {
    $URLSuccess = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Interventions/intervention_process_phase3.php&gibbonINInterventionID='.$gibbonINInterventionID;
}

if (isActionAccessible($guid, $connection2, '/modules/Interventions/intervention_support_plan_add.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $interventionGateway = $container->get(INInterventionGateway::class);
    $supportPlanGateway = $container->get(INSupportPlanGateway::class);
    
    // Get intervention
    $intervention = $interventionGateway->getByID($gibbonINInterventionID);
    if (empty($intervention)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }
    
    // Validate Inputs
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $goals = $_POST['goals'] ?? '';
    $strategies = $_POST['strategies'] ?? '';
    $resources = $_POST['resources'] ?? '';
    $targetDate = $_POST['targetDate'] ?? '';
    $gibbonPersonIDStaff = $_POST['gibbonPersonIDStaff'] ?? '';
    $status = $_POST['status'] ?? 'Draft';
    
    if (empty($name) || empty($goals) || empty($strategies) || empty($targetDate) || empty($gibbonPersonIDStaff)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }
    
    try {
        $data = [
            'gibbonINInterventionID' => $gibbonINInterventionID,
            'gibbonPersonIDCreator' => $_SESSION[$guid]['gibbonPersonID'],
            'name' => $name,
            'description' => $description,
            'goals' => $goals,
            'strategies' => $strategies,
            'resources' => $resources,
            'targetDate' => $targetDate,
            'gibbonPersonIDStaff' => $gibbonPersonIDStaff,
            'status' => $status,
            'dateStart' => ($status == 'Active') ? date('Y-m-d') : null,
            'timestampCreated' => date('Y-m-d H:i:s')
        ];
        
        // Begin transaction
        $connection2->beginTransaction();
        
        // If setting this plan to active, deactivate any other active plans
        if ($status == 'Active') {
            $sql = "UPDATE gibbonINSupportPlan SET gibbonINSupportPlan.status='Completed' WHERE gibbonINInterventionID=:gibbonINInterventionID AND gibbonINSupportPlan.status='Active'";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['gibbonINInterventionID' => $gibbonINInterventionID]);
            
            // Update intervention status if not already in implementation
            if ($intervention['status'] != 'Support Plan Active') {
                $interventionGateway->update($gibbonINInterventionID, [
                    'status' => 'Support Plan Active'
                ]);
            }
        }
        
        // Insert the support plan
        $gibbonINSupportPlanID = $supportPlanGateway->insert($data);
        
        // Commit transaction
        $connection2->commit();
        
        $URLSuccess .= '&return=success0&gibbonINSupportPlanID='.$gibbonINSupportPlanID;
        header("Location: {$URLSuccess}");
        exit;
    } catch (Exception $e) {
        $connection2->rollBack();
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }
}
