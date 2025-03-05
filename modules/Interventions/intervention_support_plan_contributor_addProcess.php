<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

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

use Gibbon\Module\Interventions\Domain\INSupportPlanGateway;

require_once '../../gibbon.php';

$gibbonINInterventionID = $_POST['gibbonINInterventionID'] ?? '';
$gibbonINSupportPlanID = $_POST['gibbonINSupportPlanID'] ?? '';

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Interventions/intervention_support_plan_contributor_add.php&gibbonINInterventionID='.$gibbonINInterventionID.'&gibbonINSupportPlanID='.$gibbonINSupportPlanID;
$URLSuccess = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Interventions/intervention_support_plan_view.php&gibbonINInterventionID='.$gibbonINInterventionID.'&gibbonINSupportPlanID='.$gibbonINSupportPlanID;

if (isActionAccessible($guid, $connection2, '/modules/Interventions/intervention_support_plan_contributor_add.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $supportPlanGateway = $container->get(INSupportPlanGateway::class);
    
    // Get support plan
    $supportPlan = $supportPlanGateway->getByID($gibbonINSupportPlanID);
    
    if (empty($supportPlan) || $supportPlan['gibbonINInterventionID'] != $gibbonINInterventionID) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }
    
    // Check access to this intervention
    $gibbonPersonID = $_SESSION[$guid]['gibbonPersonID'];
    $isAdmin = isActionAccessible($guid, $connection2, '/modules/Interventions/interventions_manage.php', 'Manage Interventions_all');
    $isCoordinator = isActionAccessible($guid, $connection2, '/modules/Interventions/interventions_manage.php', 'Manage Interventions_my');
    
    if (!$isAdmin && !$isCoordinator) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
        exit;
    }
    
    // Validate Inputs
    $gibbonPersonIDContributor = $_POST['gibbonPersonIDContributor'] ?? '';
    $role = $_POST['role'] ?? '';
    $canEdit = $_POST['canEdit'] ?? '';
    
    if (empty($gibbonPersonIDContributor) || empty($role) || empty($canEdit)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }
    
    // Check for existing contributor
    $data = [
        'gibbonINSupportPlanID' => $gibbonINSupportPlanID,
        'gibbonPersonIDContributor' => $gibbonPersonIDContributor
    ];
    $sql = "SELECT COUNT(*) FROM gibbonINSupportPlanContributor 
            WHERE gibbonINSupportPlanID=:gibbonINSupportPlanID 
            AND gibbonPersonIDContributor=:gibbonPersonIDContributor";
    $resultCheck = $connection2->prepare($sql);
    $resultCheck->execute($data);
    
    if ($resultCheck->fetchColumn() > 0) {
        $URL .= '&return=error7';
        header("Location: {$URL}");
        exit;
    }
    
    try {
        $data = [
            'gibbonINSupportPlanID' => $gibbonINSupportPlanID,
            'gibbonPersonIDContributor' => $gibbonPersonIDContributor,
            'role' => $role,
            'canEdit' => $canEdit
        ];
        
        $sql = "INSERT INTO gibbonINSupportPlanContributor 
                (gibbonINSupportPlanID, gibbonPersonIDContributor, role, canEdit) 
                VALUES 
                (:gibbonINSupportPlanID, :gibbonPersonIDContributor, :role, :canEdit)";
        
        $result = $connection2->prepare($sql);
        $result->execute($data);
        
        // Log the addition in history
        $data = [
            'gibbonINSupportPlanID' => $gibbonINSupportPlanID,
            'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'],
            'action' => 'Create',
            'fieldName' => 'contributor',
            'newValue' => $gibbonPersonIDContributor
        ];
        
        $sql = "INSERT INTO gibbonINSupportPlanHistory 
                (gibbonINSupportPlanID, gibbonPersonID, action, fieldName, newValue) 
                VALUES 
                (:gibbonINSupportPlanID, :gibbonPersonID, :action, :fieldName, :newValue)";
        
        $result = $connection2->prepare($sql);
        $result->execute($data);
        
        // Send notification to the contributor
        $notificationGateway = $container->get(\Gibbon\Domain\System\NotificationGateway::class);
        $notificationSender = $container->get(\Gibbon\Domain\System\NotificationSender::class);
        
        $personName = Format::name('', $_SESSION[$guid]['preferredName'], $_SESSION[$guid]['surname'], 'Staff', false, true);
        
        $notificationString = sprintf(__('You have been added as a contributor to a support plan by %1$s.'), $personName);
        
        $notificationSender->addNotification(
            [$gibbonPersonIDContributor], 
            'Support Plan Contributor', 
            $notificationString, 
            $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Interventions/interventions_contributor_dashboard.php'
        );
        
        $notificationSender->sendNotifications();
        
        $URLSuccess .= '&return=success0';
        header("Location: {$URLSuccess}");
        exit;
    } catch (Exception $e) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }
}
