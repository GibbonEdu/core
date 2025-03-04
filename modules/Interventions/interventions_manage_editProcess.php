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

    // Validate Inputs
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $formTutorDecision = $_POST['formTutorDecision'] ?? '';
    $formTutorNotes = $_POST['formTutorNotes'] ?? '';
    $newStatus = $_POST['status'] ?? '';
    
    // Set a default status if it's empty
    if (empty($newStatus)) {
        $newStatus = 'Referral'; // Default status
        error_log('Setting default status to: ' . $newStatus);
    }
    
    error_log('Name: ' . $name);
    error_log('Description: ' . $description);
    error_log('Form Tutor Decision: ' . $formTutorDecision);
    error_log('Form Tutor Notes: ' . $formTutorNotes);
    error_log('New Status: ' . $newStatus);
    
    // Determine user roles
    $isFormTutor = ($intervention['gibbonPersonIDFormTutor'] == $session->get('gibbonPersonID'));
    $isCreator = ($intervention['gibbonPersonIDCreator'] == $session->get('gibbonPersonID'));
    $isAdmin = ($highestAction == 'Manage Interventions');

    if (empty($name) || empty($description)) {
        error_log('Validation failed: Name empty: ' . empty($name) . ', Description empty: ' . empty($description));
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }
    
    // Check if form tutor decision was submitted
    $formTutorDecisionSubmitted = $_POST['formTutorDecisionSubmitted'] ?? '';
    error_log('Form tutor decision submitted: ' . $formTutorDecisionSubmitted);
    error_log('Form tutor decision: ' . $formTutorDecision);
    error_log('POST data: ' . json_encode($_POST));

    // Only process form tutor decision if it was actually submitted and a decision was made
    if ($formTutorDecisionSubmitted == 'Y' && !empty($formTutorDecision)) {
        error_log('Processing form tutor decision');
        // If form tutor made a decision, update the status accordingly
        if ($isFormTutor || $isAdmin) {
            error_log('User is form tutor or admin');
            if ($formTutorDecision != 'Pending') {
                error_log('Decision is not pending: ' . $formTutorDecision);
                // If resolved, update status
                if ($formTutorDecision == 'Resolved') {
                    error_log('Decision is Resolved');
                    $newStatus = 'Resolved';
                }
                
                // If eligibility assessment, update status
                if ($formTutorDecision == 'Eligibility Assessment') {
                    error_log('Decision is Eligibility Assessment');
                    $newStatus = 'Eligibility Assessment';
                    
                    // Create an eligibility assessment record if one doesn't exist
                    $eligibilityAssessmentGateway = $container->get(INInterventionEligibilityAssessmentGateway::class);
                    $existingAssessment = $eligibilityAssessmentGateway->getByInterventionID($gibbonINInterventionID);
                    
                    if (empty($existingAssessment)) {
                        error_log('No existing assessment found, creating new one');
                        // Create a new assessment
                        $data = [
                            'gibbonINInterventionID' => $gibbonINInterventionID,
                            'gibbonPersonIDStudent' => $intervention['gibbonPersonIDStudent'] ?? null,
                            'gibbonPersonIDCreator' => $session->get('gibbonPersonID'),
                            'status' => 'In Progress',
                            'timestampCreated' => date('Y-m-d H:i:s')
                        ];
                        
                        error_log('Assessment data: ' . json_encode($data));
                        $gibbonINInterventionEligibilityAssessmentID = $eligibilityAssessmentGateway->insert($data);
                        error_log('New assessment ID: ' . $gibbonINInterventionEligibilityAssessmentID);
                        
                        // Redirect to the new intervention eligibility edit page
                        if (!empty($gibbonINInterventionEligibilityAssessmentID)) {
                            $redirectURL = $session->get('absoluteURL').'/index.php?q=/modules/Interventions/intervention_eligibility_edit.php&gibbonINInterventionID='.$gibbonINInterventionID.'&gibbonINInterventionEligibilityAssessmentID='.$gibbonINInterventionEligibilityAssessmentID.'&gibbonFormGroupID='.$gibbonFormGroupID.'&gibbonYearGroupID='.$gibbonYearGroupID.'&status='.$status;
                            error_log('Redirecting to: ' . $redirectURL);
                            header("Location: {$redirectURL}");
                            exit;
                        } else {
                            error_log('Failed to create assessment');
                        }
                    } else {
                        error_log('Existing assessment found: ' . json_encode($existingAssessment));
                        // Redirect to edit the existing assessment
                        $redirectURL = $session->get('absoluteURL').'/index.php?q=/modules/Interventions/intervention_eligibility_edit.php&gibbonINInterventionID='.$gibbonINInterventionID.'&gibbonINInterventionEligibilityAssessmentID='.$existingAssessment['gibbonINInterventionEligibilityAssessmentID'].'&gibbonFormGroupID='.$gibbonFormGroupID.'&gibbonYearGroupID='.$gibbonYearGroupID.'&status='.$status;
                        error_log('Redirecting to existing assessment: ' . $redirectURL);
                        header("Location: {$redirectURL}");
                        exit;
                    }
                }
                
                // If pending, update status
                if ($formTutorDecision == 'Pending' && $intervention['status'] == 'Referral') {
                    error_log('Decision is Pending and status is Referral');
                    // If decision is pending but referral is being reviewed, set status to Form Tutor Review
                    $newStatus = 'Form Tutor Review';
                }
            } else {
                error_log('User is not form tutor or admin');
            }
        }
    }
    
    // If user is not form tutor or admin, they can only edit basic details
    if (!$isFormTutor && !$isAdmin) {
        // Preserve existing values for fields they shouldn't change
        $formTutorDecision = $intervention['formTutorDecision'];
        $formTutorNotes = $intervention['formTutorNotes'];
        $newStatus = $intervention['status'];
    }

    // Update the intervention
    $data = [
        'name' => $name,
        'description' => $description,
        'formTutorDecision' => $formTutorDecision,
        'formTutorNotes' => $formTutorNotes,
        'status' => $newStatus
    ];

    error_log('Updating intervention with data: ' . json_encode($data));
    $updated = $interventionGateway->update($gibbonINInterventionID, $data);

    if (!$updated) {
        error_log('Failed to update intervention');
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    error_log('Intervention updated successfully');

    // Send notification if status has changed
    if ($newStatus != $intervention['status']) {
        error_log('Status changed from ' . $intervention['status'] . ' to ' . $newStatus);
        $notificationGateway = $container->get(NotificationGateway::class);

        $studentName = Format::name('', $intervention['preferredName'], $intervention['surname'], 'Student', false, true);
        $notificationString = __('The intervention "{name}" for {student} has been updated to {status}.', [
            'name' => $name,
            'student' => $studentName,
            'status' => $newStatus
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
