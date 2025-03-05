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
$gibbonINSupportPlanProgressID = $_POST['gibbonINSupportPlanProgressID'] ?? '';

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Interventions/intervention_support_plan_progress_edit.php&gibbonINInterventionID='.$gibbonINInterventionID.'&gibbonINSupportPlanID='.$gibbonINSupportPlanID.'&gibbonINSupportPlanProgressID='.$gibbonINSupportPlanProgressID;
$URLSuccess = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Interventions/intervention_support_plan_view.php&gibbonINInterventionID='.$gibbonINInterventionID.'&gibbonINSupportPlanID='.$gibbonINSupportPlanID;

if (isActionAccessible($guid, $connection2, '/modules/Interventions/intervention_support_plan_progress_edit.php') == false) {
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
    
    // Check if user is a contributor with edit rights
    $data = [
        'gibbonINSupportPlanID' => $gibbonINSupportPlanID,
        'gibbonPersonIDContributor' => $gibbonPersonID
    ];
    $sql = "SELECT * FROM gibbonINSupportPlanContributor 
            WHERE gibbonINSupportPlanID=:gibbonINSupportPlanID 
            AND gibbonPersonIDContributor=:gibbonPersonIDContributor 
            AND canEdit='Y'";
    $resultContributor = $pdo->executeQuery($data, $sql);
    $isContributor = ($resultContributor->rowCount() > 0);
    
    if (!$isAdmin && !$isCoordinator && !$isContributor) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
        exit;
    }
    
    // Get progress report details
    $data = [
        'gibbonINSupportPlanProgressID' => $gibbonINSupportPlanProgressID,
        'gibbonINSupportPlanID' => $gibbonINSupportPlanID
    ];
    $sql = "SELECT * FROM gibbonINSupportPlanProgress 
            WHERE gibbonINSupportPlanProgressID=:gibbonINSupportPlanProgressID 
            AND gibbonINSupportPlanID=:gibbonINSupportPlanID";
    $resultProgress = $pdo->executeQuery($data, $sql);
    
    if ($resultProgress->rowCount() != 1) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }
    
    $progress = $resultProgress->fetch();
    
    // Check if user is the creator of the progress report or has admin/coordinator access
    if (!$isAdmin && !$isCoordinator && $progress['gibbonPersonID'] != $gibbonPersonID) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
        exit;
    }
    
    // Validate Inputs
    $reportingCycle = $_POST['reportingCycle'] ?? '';
    $progressSummary = $_POST['progressSummary'] ?? '';
    $goalProgress = $_POST['goalProgress'] ?? '';
    $nextSteps = $_POST['nextSteps'] ?? '';
    $date = $_POST['date'] ?? '';
    
    if (empty($reportingCycle) || empty($progressSummary) || empty($goalProgress) || empty($nextSteps) || empty($date)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }
    
    try {
        // Store old values for history tracking
        $oldValues = [
            'reportingCycle' => $progress['reportingCycle'],
            'progressSummary' => $progress['progressSummary'],
            'goalProgress' => $progress['goalProgress'],
            'nextSteps' => $progress['nextSteps'],
            'date' => $progress['date']
        ];
        
        // Update the progress report
        $data = [
            'gibbonINSupportPlanProgressID' => $gibbonINSupportPlanProgressID,
            'gibbonINSupportPlanID' => $gibbonINSupportPlanID,
            'reportingCycle' => $reportingCycle,
            'progressSummary' => $progressSummary,
            'goalProgress' => $goalProgress,
            'nextSteps' => $nextSteps,
            'date' => $date
        ];
        
        $sql = "UPDATE gibbonINSupportPlanProgress 
                SET reportingCycle=:reportingCycle, 
                    progressSummary=:progressSummary, 
                    goalProgress=:goalProgress, 
                    nextSteps=:nextSteps, 
                    date=:date 
                WHERE gibbonINSupportPlanProgressID=:gibbonINSupportPlanProgressID 
                AND gibbonINSupportPlanID=:gibbonINSupportPlanID";
        
        $result = $connection2->prepare($sql);
        $result->execute($data);
        
        // Log the changes in history
        if ($oldValues['reportingCycle'] != $reportingCycle) {
            $data = [
                'gibbonINSupportPlanID' => $gibbonINSupportPlanID,
                'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'],
                'action' => 'Update',
                'fieldName' => 'progress_reportingCycle',
                'oldValue' => $oldValues['reportingCycle'],
                'newValue' => $reportingCycle
            ];
            
            $sql = "INSERT INTO gibbonINSupportPlanHistory 
                    (gibbonINSupportPlanID, gibbonPersonID, action, fieldName, oldValue, newValue) 
                    VALUES 
                    (:gibbonINSupportPlanID, :gibbonPersonID, :action, :fieldName, :oldValue, :newValue)";
            
            $result = $connection2->prepare($sql);
            $result->execute($data);
        }
        
        if ($oldValues['date'] != $date) {
            $data = [
                'gibbonINSupportPlanID' => $gibbonINSupportPlanID,
                'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'],
                'action' => 'Update',
                'fieldName' => 'progress_date',
                'oldValue' => $oldValues['date'],
                'newValue' => $date
            ];
            
            $sql = "INSERT INTO gibbonINSupportPlanHistory 
                    (gibbonINSupportPlanID, gibbonPersonID, action, fieldName, oldValue, newValue) 
                    VALUES 
                    (:gibbonINSupportPlanID, :gibbonPersonID, :action, :fieldName, :oldValue, :newValue)";
            
            $result = $connection2->prepare($sql);
            $result->execute($data);
        }
        
        // Notify all contributors and the plan owner
        $notificationGateway = $container->get(\Gibbon\Domain\System\NotificationGateway::class);
        $notificationSender = $container->get(\Gibbon\Domain\System\NotificationSender::class);
        
        // Get all contributors
        $data = ['gibbonINSupportPlanID' => $gibbonINSupportPlanID];
        $sql = "SELECT gibbonPersonIDContributor FROM gibbonINSupportPlanContributor 
                WHERE gibbonINSupportPlanID=:gibbonINSupportPlanID";
        $resultContributors = $pdo->executeQuery($data, $sql);
        
        $recipients = [];
        while ($contributor = $resultContributors->fetch()) {
            if ($contributor['gibbonPersonIDContributor'] != $_SESSION[$guid]['gibbonPersonID']) {
                $recipients[] = $contributor['gibbonPersonIDContributor'];
            }
        }
        
        // Add the plan creator if not already in the list
        if ($supportPlan['gibbonPersonIDCreator'] != $_SESSION[$guid]['gibbonPersonID'] && !in_array($supportPlan['gibbonPersonIDCreator'], $recipients)) {
            $recipients[] = $supportPlan['gibbonPersonIDCreator'];
        }
        
        // Add the plan staff member if not already in the list
        if ($supportPlan['gibbonPersonIDStaff'] != $_SESSION[$guid]['gibbonPersonID'] && !in_array($supportPlan['gibbonPersonIDStaff'], $recipients)) {
            $recipients[] = $supportPlan['gibbonPersonIDStaff'];
        }
        
        if (!empty($recipients)) {
            $personName = Format::name('', $_SESSION[$guid]['preferredName'], $_SESSION[$guid]['surname'], 'Staff', false, true);
            
            $notificationString = sprintf(__('A progress report has been updated for support plan "%1$s" by %2$s.'), $supportPlan['name'], $personName);
            
            $notificationSender->addNotification(
                $recipients, 
                'Support Plan Progress', 
                $notificationString, 
                $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Interventions/intervention_support_plan_view.php&gibbonINInterventionID='.$gibbonINInterventionID.'&gibbonINSupportPlanID='.$gibbonINSupportPlanID
            );
            
            $notificationSender->sendNotifications();
        }
        
        $URLSuccess .= '&return=success0';
        header("Location: {$URLSuccess}");
        exit;
    } catch (Exception $e) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }
}
