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

use Gibbon\Domain\Interventions\INInterventionEligibilityAssessmentGateway;
use Gibbon\Domain\Interventions\INInterventionGateway;
use Gibbon\FileUploader;
use Gibbon\Services\Format;

require_once '../../gibbon.php';

$gibbonINInterventionID = $_POST['gibbonINInterventionID'] ?? '';
$gibbonINInterventionEligibilityAssessmentID = $_POST['gibbonINInterventionEligibilityAssessmentID'] ?? '';
$gibbonPersonID = $_POST['gibbonPersonID'] ?? '';
$gibbonFormGroupID = $_POST['gibbonFormGroupID'] ?? '';
$gibbonYearGroupID = $_POST['gibbonYearGroupID'] ?? '';
$status = $_POST['status'] ?? '';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Interventions/intervention_eligibility_edit.php&gibbonINInterventionID='.$gibbonINInterventionID.'&gibbonINInterventionEligibilityAssessmentID='.$gibbonINInterventionEligibilityAssessmentID.'&gibbonPersonID='.$gibbonPersonID.'&gibbonFormGroupID='.$gibbonFormGroupID.'&gibbonYearGroupID='.$gibbonYearGroupID.'&status='.$status;

if (isActionAccessible($guid, $connection2, '/modules/Interventions/intervention_eligibility_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $eligibilityAssessmentGateway = $container->get(INInterventionEligibilityAssessmentGateway::class);
    $interventionGateway = $container->get(INInterventionGateway::class);
    
    // Validate the required values
    if (empty($gibbonINInterventionID)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }
    
    // Get intervention details
    $intervention = $interventionGateway->getInterventionByID($gibbonINInterventionID);
    if (empty($intervention)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }
    
    // Check access based on the highest action level
    $highestAction = getHighestGroupedAction($guid, '/modules/Interventions/intervention_eligibility_edit.php', $connection2);
    if ($highestAction == 'Manage Interventions_my' && $intervention['gibbonPersonIDCreator'] != $session->get('gibbonPersonID')) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
        exit;
    }
    
    // Get assessment details if editing
    $assessment = null;
    if (!empty($gibbonINInterventionEligibilityAssessmentID)) {
        $assessment = $eligibilityAssessmentGateway->getByID($gibbonINInterventionEligibilityAssessmentID);
        if (empty($assessment)) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
            exit;
        }
    }
    
    // Validate the database relationships
    if (!empty($gibbonINInterventionEligibilityAssessmentID) && $assessment['gibbonINInterventionID'] != $gibbonINInterventionID) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }
    
    // Validate Inputs
    $assessmentStatus = $_POST['status'] ?? '';
    $eligibilityDecision = $_POST['eligibilityDecision'] ?? '';
    $notes = $_POST['notes'] ?? '';
    
    if (empty($assessmentStatus) || empty($eligibilityDecision)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }
    
    // Handle file upload if present
    $documentPath = $assessment['documentPath'] ?? '';
    $fileUploader = new FileUploader($pdo, $session);
    
    if (!empty($_FILES['documentFile']['tmp_name'])) {
        $file = $_FILES['documentFile'] ?? null;
        
        // Upload the file, if fails, return the appropriate error
        $documentPath = $fileUploader->uploadFromPost($file, 'interventions_eligibility_assessment_'.date('Y-m-d-H-i-s'));
        
        if (empty($documentPath)) {
            $URL .= '&return=error4';
            header("Location: {$URL}");
            exit;
        }
    }
    
    // Prepare data for database
    $data = [
        'status' => $assessmentStatus,
        'eligibilityDecision' => $eligibilityDecision,
        'notes' => $notes,
        'documentPath' => $documentPath
    ];
    
    // Update or insert based on whether we have an ID
    $success = false;
    if (!empty($gibbonINInterventionEligibilityAssessmentID)) {
        $success = $eligibilityAssessmentGateway->update($gibbonINInterventionEligibilityAssessmentID, $data);
    } else {
        $data['gibbonINInterventionID'] = $gibbonINInterventionID;
        $data['gibbonPersonIDStudent'] = $intervention['gibbonPersonIDStudent'];
        $data['gibbonPersonIDCreator'] = $session->get('gibbonPersonID');
        $data['timestampCreated'] = date('Y-m-d H:i:s');
        
        $gibbonINInterventionEligibilityAssessmentID = $eligibilityAssessmentGateway->insert($data);
        $success = !empty($gibbonINInterventionEligibilityAssessmentID);
    }
    
    // Update intervention status if eligibility assessment is complete
    if ($success && $assessmentStatus == 'Complete') {
        // Update intervention status based on eligibility decision
        $interventionStatus = '';
        if ($eligibilityDecision == 'Eligible for IEP') {
            $interventionStatus = 'Eligible for IEP';
        } elseif ($eligibilityDecision == 'Needs Intervention') {
            $interventionStatus = 'Intervention Required';
        }
        
        if (!empty($interventionStatus)) {
            $interventionGateway->update($gibbonINInterventionID, [
                'status' => $interventionStatus
            ]);
        }
        
        // Notify relevant staff
        $notificationText = sprintf(__('An eligibility assessment for %s has been completed with the decision: %s'), Format::name('', $intervention['preferredName'], $intervention['surname'], 'Student'), __($eligibilityDecision));
        
        // Insert notification for the creator of the intervention
        if ($intervention['gibbonPersonIDCreator'] != $session->get('gibbonPersonID')) {
            $notificationGateway = $container->get(\Gibbon\Domain\System\NotificationGateway::class);
            $notificationSender = $container->get(\Gibbon\Domain\System\NotificationSender::class);
            
            $notificationGateway->insertNotification([
                'gibbonPersonID' => $intervention['gibbonPersonIDCreator'],
                'text' => $notificationText,
                'moduleName' => 'Interventions',
                'actionLink' => '/modules/Interventions/intervention_eligibility_edit.php?gibbonINInterventionID='.$gibbonINInterventionID.'&gibbonINInterventionEligibilityAssessmentID='.$gibbonINInterventionEligibilityAssessmentID
            ]);
            
            $notificationSender->sendNotifications($session->get('absoluteURL'));
        }
    }
    
    if ($success) {
        $URL .= '&return=success0';
    } else {
        $URL .= '&return=error2';
    }
    
    header("Location: {$URL}");
    exit;
}
