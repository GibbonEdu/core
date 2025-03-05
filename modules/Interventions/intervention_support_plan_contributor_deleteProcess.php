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
$gibbonINSupportPlanContributorID = $_POST['gibbonINSupportPlanContributorID'] ?? '';

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Interventions/intervention_support_plan_contributor_delete.php&gibbonINInterventionID='.$gibbonINInterventionID.'&gibbonINSupportPlanID='.$gibbonINSupportPlanID.'&gibbonINSupportPlanContributorID='.$gibbonINSupportPlanContributorID;
$URLSuccess = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Interventions/intervention_support_plan_view.php&gibbonINInterventionID='.$gibbonINInterventionID.'&gibbonINSupportPlanID='.$gibbonINSupportPlanID;

if (isActionAccessible($guid, $connection2, '/modules/Interventions/intervention_support_plan_contributor_delete.php') == false) {
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
    
    // Get contributor details before deletion
    $data = [
        'gibbonINSupportPlanContributorID' => $gibbonINSupportPlanContributorID,
        'gibbonINSupportPlanID' => $gibbonINSupportPlanID
    ];
    $sql = "SELECT gibbonPersonIDContributor FROM gibbonINSupportPlanContributor 
            WHERE gibbonINSupportPlanContributorID=:gibbonINSupportPlanContributorID 
            AND gibbonINSupportPlanID=:gibbonINSupportPlanID";
    $resultContributor = $pdo->executeQuery($data, $sql);
    
    if ($resultContributor->rowCount() != 1) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }
    
    $contributor = $resultContributor->fetch();
    
    try {
        // Delete the contributor
        $data = [
            'gibbonINSupportPlanContributorID' => $gibbonINSupportPlanContributorID,
            'gibbonINSupportPlanID' => $gibbonINSupportPlanID
        ];
        
        $sql = "DELETE FROM gibbonINSupportPlanContributor 
                WHERE gibbonINSupportPlanContributorID=:gibbonINSupportPlanContributorID 
                AND gibbonINSupportPlanID=:gibbonINSupportPlanID";
        
        $result = $connection2->prepare($sql);
        $result->execute($data);
        
        // Log the deletion in history
        $data = [
            'gibbonINSupportPlanID' => $gibbonINSupportPlanID,
            'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'],
            'action' => 'Delete',
            'fieldName' => 'contributor',
            'oldValue' => $contributor['gibbonPersonIDContributor']
        ];
        
        $sql = "INSERT INTO gibbonINSupportPlanHistory 
                (gibbonINSupportPlanID, gibbonPersonID, action, fieldName, oldValue) 
                VALUES 
                (:gibbonINSupportPlanID, :gibbonPersonID, :action, :fieldName, :oldValue)";
        
        $result = $connection2->prepare($sql);
        $result->execute($data);
        
        // Send notification to the contributor
        $notificationGateway = $container->get(\Gibbon\Domain\System\NotificationGateway::class);
        $notificationSender = $container->get(\Gibbon\Domain\System\NotificationSender::class);
        
        $personName = Format::name('', $_SESSION[$guid]['preferredName'], $_SESSION[$guid]['surname'], 'Staff', false, true);
        
        $notificationString = sprintf(__('You have been removed as a contributor from a support plan by %1$s.'), $personName);
        
        $notificationSender->addNotification(
            [$contributor['gibbonPersonIDContributor']], 
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
