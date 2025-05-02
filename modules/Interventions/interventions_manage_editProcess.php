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
use Gibbon\Module\Interventions\Domain\INInterventionEligibilityAssessmentGateway;
use Gibbon\Domain\System\NotificationGateway;
use Gibbon\Services\Format;
use Gibbon\Data\Validator;

require_once '../../gibbon.php';
require_once __DIR__ . '/moduleFunctions.php';

// Add error logging
error_log('Starting interventions_manage_editProcess.php');

$_POST = $container->get(Validator::class)->sanitize($_POST);

$gibbonINInterventionID = $_POST['gibbonINInterventionID'] ?? '';
$gibbonPersonIDStudent = $_POST['gibbonPersonIDStudent'] ?? '';
$gibbonFormGroupID = $_POST['gibbonFormGroupID'] ?? '';
$gibbonYearGroupID = $_POST['gibbonYearGroupID'] ?? '';
$status = $_POST['status'] ?? '';

error_log('Processing intervention ID: ' . $gibbonINInterventionID);
error_log('Student ID from POST: ' . $gibbonPersonIDStudent);

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Interventions/interventions_manage_edit.php&gibbonINInterventionID='.$gibbonINInterventionID.'&gibbonPersonIDStudent='.$gibbonPersonIDStudent.'&gibbonFormGroupID='.$gibbonFormGroupID.'&gibbonYearGroupID='.$gibbonYearGroupID.'&status='.$status;

if (isActionAccessible($guid, $connection2, '/modules/Interventions/interventions_manage_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Get action with highest precedence
    $highestAction = getHighestGroupedAction($guid, $_POST['address'], $connection2);
    if (empty($highestAction)) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
        exit;
    }

    // Proceed!
    if (empty($gibbonINInterventionID)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    $interventionGateway = $container->get(INInterventionGateway::class);
    $intervention = $interventionGateway->getInterventionByID($gibbonINInterventionID);

    if (empty($intervention)) {
        error_log('Intervention not found for ID: ' . $gibbonINInterventionID);
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }
    
    error_log('Retrieved intervention: ' . json_encode($intervention));
    error_log('Student ID from intervention: ' . ($intervention['gibbonPersonIDStudent'] ?? 'not set'));

    // Check access based on the highest action level
    if ($highestAction == 'Manage Interventions_my' && $intervention['gibbonPersonIDCreator'] != $session->get('gibbonPersonID')) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
        exit;
    }

    // Process form data
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $status = $_POST['status'] ?? '';
    $formTutorDecision = $_POST['formTutorDecision'] ?? '';
    $formTutorNotes = $_POST['formTutorNotes'] ?? '';
    $outcomeDecision = $_POST['outcomeDecision'] ?? '';
    $outcomeNotes = $_POST['outcomeNotes'] ?? '';
    $targetDate = $_POST['targetDate'] ?? '';
    $parentConsent = $_POST['parentConsent'] ?? 'N';
    $parentConsultNotes = $_POST['parentConsultNotes'] ?? '';
    $strategies = $_POST['strategies'] ?? '';
    $goals = $_POST['goals'] ?? '';
    $activateSupportPlan = $_POST['activateSupportPlan'] ?? '0';
    $phase = $_POST['phase'] ?? '';
    
    // Check for required fields
    if (empty($name) || empty($description)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }
    
    // Determine user roles
    $isFormTutor = ($intervention['gibbonPersonIDFormTutor'] == $session->get('gibbonPersonID'));
    $isCreator = ($intervention['gibbonPersonIDCreator'] == $session->get('gibbonPersonID'));
    $isAdmin = ($highestAction == 'Manage Interventions');
    
    // Prepare data for update
    $data = [
        'name' => $name,
        'description' => $description,
        'parentConsent' => $parentConsent,
        'parentConsultNotes' => $parentConsultNotes,
        'timestampModified' => date('Y-m-d H:i:s')
    ];
    
    // Process based on which phase was submitted
    if ($phase == 'phase1') {
        // Phase 1 submission - only update basic info and form tutor decision
        
        // If form tutor decision is provided
        if (!empty($formTutorDecision)) {
            // Check if user is form tutor or admin
            if ($isFormTutor || $isAdmin) {
                $data['formTutorDecision'] = $formTutorDecision;
                $data['formTutorNotes'] = $formTutorNotes;
                
                // If resolved, update status
                if ($formTutorDecision == 'Resolved') {
                    $data['status'] = 'Resolved';
                }
                
                // If eligibility assessment, update status
                if ($formTutorDecision == 'Eligibility Assessment') {
                    $data['status'] = 'Eligibility Assessment';
                    
                    // Create an eligibility assessment record if one doesn't exist
                    $eligibilityAssessmentGateway = $container->get(INInterventionEligibilityAssessmentGateway::class);
                    $existingAssessment = $eligibilityAssessmentGateway->selectBy(['gibbonINInterventionID' => $gibbonINInterventionID])->fetch();
                    
                    if (empty($existingAssessment)) {
                        // Create a new assessment
                        $dataAssessment = [
                            'gibbonINInterventionID' => $gibbonINInterventionID,
                            'gibbonPersonIDStudent' => $intervention['gibbonPersonIDStudent'] ?? null,
                            'gibbonPersonIDCreator' => $session->get('gibbonPersonID'),
                            'timestampCreated' => date('Y-m-d H:i:s')
                        ];
                        
                        $gibbonINInterventionEligibilityAssessmentID = $eligibilityAssessmentGateway->insert($dataAssessment);
                        
                        // Redirect to the new intervention eligibility edit page
                        if (!empty($gibbonINInterventionEligibilityAssessmentID)) {
                            $URL = $session->get('absoluteURL').'/index.php?q=/modules/Interventions/intervention_eligibility_edit.php&gibbonINInterventionEligibilityAssessmentID='.$gibbonINInterventionEligibilityAssessmentID.'&gibbonINInterventionID='.$gibbonINInterventionID;
                            header("Location: {$URL}");
                            exit;
                        }
                    } else {
                        // Redirect to the existing intervention eligibility edit page
                        $gibbonINInterventionEligibilityAssessmentID = $existingAssessment['gibbonINInterventionEligibilityAssessmentID'];
                        $URL = $session->get('absoluteURL').'/index.php?q=/modules/Interventions/intervention_eligibility_edit.php&gibbonINInterventionEligibilityAssessmentID='.$gibbonINInterventionEligibilityAssessmentID.'&gibbonINInterventionID='.$gibbonINInterventionID;
                        header("Location: {$URL}");
                        exit;
                    }
                }
                
                // If decision is pending but referral is being reviewed, set status to Form Tutor Review
                if ($formTutorDecision == 'Pending' && $intervention['status'] == 'Referral') {
                    $data['status'] = 'Form Tutor Review';
                }
            }
        }
    } else if ($phase == 'phase3') {
        // Phase 3 submission - update support plan details
        
        // Add target date only if it's not empty
        if (!empty($targetDate)) {
            $data['targetDate'] = $targetDate;
        }
        
        // Add strategies and goals
        $data['strategies'] = $strategies;
        $data['goals'] = $goals;
        
        // If activating the support plan, update status
        if ($activateSupportPlan == '1' && $intervention['status'] == 'Intervention Required') {
            $data['status'] = 'Support Plan Active';
        }
    } else if ($phase == 'phase5') {
        // Phase 5 submission - update outcome details
        
        // If outcome decision is being changed, update it
        if (!empty($outcomeDecision)) {
            $data['outcomeDecision'] = $outcomeDecision;
        }
        
        // If outcome notes are being changed, update them
        if (!empty($outcomeNotes)) {
            $data['outcomeNotes'] = $outcomeNotes;
        }
        
        // If resolving the intervention, update status
        if ($outcomeDecision == 'Resolved') {
            $data['status'] = 'Resolved';
        }
    } else {
        // Default submission - update all fields
        
        // Add target date only if it's not empty
        if (!empty($targetDate)) {
            $data['targetDate'] = $targetDate;
        }
        
        // Add strategies and goals
        $data['strategies'] = $strategies;
        $data['goals'] = $goals;
        
        // If status is being changed, update it
        if (!empty($status)) {
            $data['status'] = $status;
        }
        
        // If activating the support plan, update status
        if ($activateSupportPlan == '1' && $intervention['status'] == 'Intervention Required') {
            $data['status'] = 'Support Plan Active';
        }
        
        // If outcome decision is being changed, update it
        if (!empty($outcomeDecision)) {
            $data['outcomeDecision'] = $outcomeDecision;
        }
        
        // If outcome notes are being changed, update them
        if (!empty($outcomeNotes)) {
            $data['outcomeNotes'] = $outcomeNotes;
        }
        
        // If form tutor decision is provided
        if (!empty($formTutorDecision)) {
            // Check if user is form tutor or admin
            if ($isFormTutor || $isAdmin) {
                $data['formTutorDecision'] = $formTutorDecision;
                $data['formTutorNotes'] = $formTutorNotes;
                
                // If resolved, update status
                if ($formTutorDecision == 'Resolved') {
                    $data['status'] = 'Resolved';
                }
                
                // If eligibility assessment, update status
                if ($formTutorDecision == 'Eligibility Assessment') {
                    $data['status'] = 'Eligibility Assessment';
                    
                    // Create an eligibility assessment record if one doesn't exist
                    $eligibilityAssessmentGateway = $container->get(INInterventionEligibilityAssessmentGateway::class);
                    $existingAssessment = $eligibilityAssessmentGateway->selectBy(['gibbonINInterventionID' => $gibbonINInterventionID])->fetch();
                    
                    if (empty($existingAssessment)) {
                        // Create a new assessment
                        $dataAssessment = [
                            'gibbonINInterventionID' => $gibbonINInterventionID,
                            'gibbonPersonIDStudent' => $intervention['gibbonPersonIDStudent'] ?? null,
                            'gibbonPersonIDCreator' => $session->get('gibbonPersonID'),
                            'timestampCreated' => date('Y-m-d H:i:s')
                        ];
                        
                        $gibbonINInterventionEligibilityAssessmentID = $eligibilityAssessmentGateway->insert($dataAssessment);
                        
                        // Redirect to the new intervention eligibility edit page
                        if (!empty($gibbonINInterventionEligibilityAssessmentID)) {
                            $URL = $session->get('absoluteURL').'/index.php?q=/modules/Interventions/intervention_eligibility_edit.php&gibbonINInterventionEligibilityAssessmentID='.$gibbonINInterventionEligibilityAssessmentID.'&gibbonINInterventionID='.$gibbonINInterventionID;
                            header("Location: {$URL}");
                            exit;
                        }
                    } else {
                        // Redirect to the existing intervention eligibility edit page
                        $gibbonINInterventionEligibilityAssessmentID = $existingAssessment['gibbonINInterventionEligibilityAssessmentID'];
                        $URL = $session->get('absoluteURL').'/index.php?q=/modules/Interventions/intervention_eligibility_edit.php&gibbonINInterventionEligibilityAssessmentID='.$gibbonINInterventionEligibilityAssessmentID.'&gibbonINInterventionID='.$gibbonINInterventionID;
                        header("Location: {$URL}");
                        exit;
                    }
                }
                
                // If decision is pending but referral is being reviewed, set status to Form Tutor Review
                if ($formTutorDecision == 'Pending' && $intervention['status'] == 'Referral') {
                    $data['status'] = 'Form Tutor Review';
                }
            }
        }
    }
    
    // If user is not form tutor or admin, they can only edit basic details
    if (!$isFormTutor && !$isAdmin) {
        // Preserve existing values for fields they shouldn't change
        $formTutorDecision = $intervention['formTutorDecision'];
        $formTutorNotes = $intervention['formTutorNotes'];
        $data['status'] = $intervention['status'];
    }

    // Update the intervention
    $updated = $interventionGateway->update($gibbonINInterventionID, $data);

    if (!$updated) {
        error_log('Failed to update intervention');
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    error_log('Intervention updated successfully');

    // Send notification if status has changed
    if ($data['status'] != $intervention['status']) {
        error_log('Status changed from ' . $intervention['status'] . ' to ' . $data['status']);
        $notificationGateway = $container->get(NotificationGateway::class);

        $studentName = Format::name('', $intervention['preferredName'], $intervention['surname'], 'Student', false, true);
        $notificationString = __('The intervention "{name}" for {student} has been updated to {status}.', [
            'name' => $name,
            'student' => $studentName,
            'status' => $data['status']
        ]);
        
        error_log('Notification message: ' . $notificationString);
        // Notify the creator if not the current user
        if ($intervention['gibbonPersonIDCreator'] != $session->get('gibbonPersonID')) {
            $notificationGateway->addNotification([$intervention['gibbonPersonIDCreator']], 'Intervention', $notificationString, 'interventions_manage_edit.php', [
                'gibbonINInterventionID' => $gibbonINInterventionID
            ], 'Alert');
        }
        
        // Notify contributors
        $contributorSQL = "SELECT gibbonPersonID FROM gibbonINInterventionContributor WHERE gibbonINInterventionID=:gibbonINInterventionID";
        if ($session->exists('gibbonPersonID')) {
            $contributorSQL .= " AND gibbonPersonID<>:gibbonPersonID";
            $contributorParams = ['gibbonINInterventionID' => $gibbonINInterventionID, 'gibbonPersonID' => $session->get('gibbonPersonID')];
        } else {
            $contributorParams = ['gibbonINInterventionID' => $gibbonINInterventionID];
        }
        
        $contributorResult = $pdo->select($contributorSQL, $contributorParams);
        
        if ($contributorResult && $contributorResult->rowCount() > 0) {
            while ($contributor = $contributorResult->fetch()) {
                $notificationGateway->addNotification([$contributor['gibbonPersonID']], 'Intervention', $notificationString, 'interventions_manage_edit.php', [
                    'gibbonINInterventionID' => $gibbonINInterventionID
                ], 'Alert');
            }
        }
    }

    $URL .= '&return=success0';
    header("Location: {$URL}");
    exit;
}
