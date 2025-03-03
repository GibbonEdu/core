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

use Gibbon\Domain\Interventions\INInterventionGateway;
use Gibbon\Domain\Interventions\INInterventionEligibilityAssessmentGateway;
use Gibbon\Domain\System\NotificationGateway;
use Gibbon\Services\Format;
use Gibbon\Data\Validator;

require_once '../../gibbon.php';
require_once __DIR__ . '/moduleFunctions.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$gibbonINInterventionID = $_POST['gibbonINInterventionID'] ?? '';
$gibbonPersonIDStudent = $_POST['gibbonPersonIDStudent'] ?? '';
$gibbonFormGroupID = $_POST['gibbonFormGroupID'] ?? '';
$gibbonYearGroupID = $_POST['gibbonYearGroupID'] ?? '';
$status = $_POST['status'] ?? '';

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
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

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
    
    // Determine user roles
    $isFormTutor = ($intervention['gibbonPersonIDFormTutor'] == $session->get('gibbonPersonID'));
    $isCreator = ($intervention['gibbonPersonIDCreator'] == $session->get('gibbonPersonID'));
    $isAdmin = ($highestAction == 'Manage Interventions');

    if (empty($name) || empty($description) || empty($newStatus)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }
    
    // Check if form tutor decision was submitted
    $formTutorDecisionSubmitted = $_POST['formTutorDecisionSubmitted'] ?? '';

    // Only process form tutor decision if it was actually submitted
    if ($formTutorDecisionSubmitted == 'Y') {
        // If form tutor made a decision, update the status accordingly
        if ($isFormTutor || $isAdmin) {
            if (!empty($formTutorDecision) && $formTutorDecision != 'Pending') {
                // If resolved, update status
                if ($formTutorDecision == 'Resolved') {
                    $newStatus = 'Resolved';
                }
                
                // If eligibility assessment, update status
                if ($formTutorDecision == 'Eligibility Assessment') {
                    $newStatus = 'Eligibility Assessment';
                    
                    // Create an eligibility assessment record if one doesn't exist
                    $eligibilityAssessmentGateway = $container->get(INInterventionEligibilityAssessmentGateway::class);
                    $existingAssessment = $eligibilityAssessmentGateway->getByInterventionID($gibbonINInterventionID);
                    
                    if (empty($existingAssessment)) {
                        // Create a new assessment
                        $data = [
                            'gibbonINInterventionID' => $gibbonINInterventionID,
                            'gibbonPersonIDStudent' => $intervention['gibbonPersonIDStudent'] ?? null,
                            'gibbonPersonIDCreator' => $session->get('gibbonPersonID'),
                            'status' => 'In Progress',
                            'timestampCreated' => date('Y-m-d H:i:s')
                        ];
                        
                        $gibbonINInterventionEligibilityAssessmentID = $eligibilityAssessmentGateway->insert($data);
                        
                        // Redirect to the new intervention eligibility edit page
                        if (!empty($gibbonINInterventionEligibilityAssessmentID)) {
                            $redirectURL = $session->get('absoluteURL').'/index.php?q=/modules/Interventions/intervention_eligibility_edit.php&gibbonINInterventionID='.$gibbonINInterventionID.'&gibbonINInterventionEligibilityAssessmentID='.$gibbonINInterventionEligibilityAssessmentID.'&gibbonPersonIDStudent='.$intervention['gibbonPersonIDStudent'].'&gibbonFormGroupID='.$gibbonFormGroupID.'&gibbonYearGroupID='.$gibbonYearGroupID.'&status='.$status;
                            header("Location: {$redirectURL}");
                            exit;
                        }
                    } else {
                        // Redirect to edit the existing assessment
                        $redirectURL = $session->get('absoluteURL').'/index.php?q=/modules/Interventions/intervention_eligibility_edit.php&gibbonINInterventionID='.$gibbonINInterventionID.'&gibbonINInterventionEligibilityAssessmentID='.$existingAssessment['gibbonINInterventionEligibilityAssessmentID'].'&gibbonPersonIDStudent='.$intervention['gibbonPersonIDStudent'].'&gibbonFormGroupID='.$gibbonFormGroupID.'&gibbonYearGroupID='.$gibbonYearGroupID.'&status='.$status;
                        header("Location: {$redirectURL}");
                        exit;
                    }
                }
            } else if ($formTutorDecision == 'Pending' && $intervention['status'] == 'Referral') {
                // If decision is pending but referral is being reviewed, set status to Form Tutor Review
                $newStatus = 'Form Tutor Review';
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

    $updated = $interventionGateway->update($gibbonINInterventionID, $data);

    if (!$updated) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    // Send notification if status has changed
    if ($newStatus != $intervention['status']) {
        $notificationGateway = $container->get(NotificationGateway::class);

        $studentName = Format::name('', $intervention['preferredName'], $intervention['surname'], 'Student', false, true);
        $notificationString = __('The intervention "{name}" for {student} has been updated to {status}.', [
            'name' => $name,
            'student' => $studentName,
            'status' => $newStatus
        ]);
        
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
