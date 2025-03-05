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

use Gibbon\Module\Interventions\Domain\INSupportPlanGateway;
use Gibbon\Services\Format;
use Gibbon\Domain\System\NotificationGateway;
use Gibbon\Domain\System\NotificationSender;

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
        'gibbonPersonID' => $gibbonPersonID
    ];
    $sql = "SELECT * FROM gibbonINSupportPlanContributor 
            WHERE gibbonINSupportPlanID=:gibbonINSupportPlanID 
            AND gibbonPersonID=:gibbonPersonID 
            AND canEdit='Y'";
    $stmt = $connection2->prepare($sql);
    $stmt->execute($data);
    $resultContributor = $stmt->fetch();
    $isContributor = ($resultContributor !== false);
    
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
    $stmt = $connection2->prepare($sql);
    $stmt->execute($data);
    $resultProgress = $stmt->fetch();
    
    if ($resultProgress === false) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }
    
    $progress = $resultProgress;
    
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
        // Store old values for history logging
        $oldValues = [
            'reportingCycle' => $progress['reportingCycle'],
            'progressSummary' => $progress['progressSummary'],
            'goalProgress' => $progress['goalProgress'],
            'nextSteps' => $progress['nextSteps'],
            'progressDate' => $progress['progressDate']
        ];
        
        // Update the progress report
        $data = [
            'gibbonINSupportPlanProgressID' => $gibbonINSupportPlanProgressID,
            'gibbonINSupportPlanID' => $gibbonINSupportPlanID,
            'reportingCycle' => $reportingCycle,
            'progressSummary' => $progressSummary,
            'goalProgress' => $goalProgress,
            'nextSteps' => $nextSteps,
            'progressDate' => $date
        ];
        
        $sql = "UPDATE gibbonINSupportPlanProgress 
                SET reportingCycle=:reportingCycle, 
                    progressSummary=:progressSummary, 
                    goalProgress=:goalProgress, 
                    nextSteps=:nextSteps, 
                    progressDate=:progressDate 
                WHERE gibbonINSupportPlanProgressID=:gibbonINSupportPlanProgressID 
                AND gibbonINSupportPlanID=:gibbonINSupportPlanID";
        
        $stmt = $connection2->prepare($sql);
        $stmt->execute($data);
        
        // Notify all contributors and the plan owner
        $notificationGateway = $container->get(\Gibbon\Domain\System\NotificationGateway::class);
        $notificationSender = $container->get(\Gibbon\Domain\System\NotificationSender::class);
        
        // Get all contributors
        $data = ['gibbonINSupportPlanID' => $gibbonINSupportPlanID];
        $sql = "SELECT gibbonPersonID FROM gibbonINSupportPlanContributor 
                WHERE gibbonINSupportPlanID=:gibbonINSupportPlanID";
        $stmt = $connection2->prepare($sql);
        $stmt->execute($data);
        $contributors = $stmt->fetchAll();
        
        $recipients = [];
        foreach ($contributors as $contributor) {
            if ($contributor['gibbonPersonID'] != $_SESSION[$guid]['gibbonPersonID']) {
                $recipients[] = $contributor['gibbonPersonID'];
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
