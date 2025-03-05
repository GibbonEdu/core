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

use Gibbon\Domain\System\NotificationGateway;
use Gibbon\Module\Interventions\Domain\INInterventionGateway;
use Gibbon\Module\Interventions\Domain\INInterventionEligibilityAssessmentGateway;
use Gibbon\Data\Validator;
use Gibbon\Services\Format;

require_once '../../gibbon.php';

// Module includes
require_once __DIR__ . '/moduleFunctions.php';

$gibbonINInterventionID = $_POST['gibbonINInterventionID'] ?? '';

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Interventions/intervention_process.php&gibbonINInterventionID='.$gibbonINInterventionID;

if (empty($gibbonINInterventionID)) {
    $URL .= '&return=error1';
    header("Location: {$URL}");
    exit;
}

// Proceed!
$interventionGateway = $container->get(INInterventionGateway::class);
$eligibilityAssessmentGateway = $container->get(INInterventionEligibilityAssessmentGateway::class);
$notificationGateway = $container->get(NotificationGateway::class);
$validator = $container->get(Validator::class);

// Get the intervention
$intervention = $interventionGateway->getByID($gibbonINInterventionID);

if (empty($intervention)) {
    $URL .= '&return=error2';
    header("Location: {$URL}");
    exit;
}

// Check permissions
$gibbonPersonID = $_SESSION[$guid]['gibbonPersonID'];
$highestAction = getHighestGroupedAction($guid, '/modules/Interventions/intervention_process.php', $connection2);

$isFormTutor = ($intervention['gibbonPersonIDFormTutor'] == $gibbonPersonID);
$isCreator = ($intervention['gibbonPersonIDCreator'] == $gibbonPersonID);
$isAdmin = ($highestAction == 'Manage Interventions');

if (!$isFormTutor && !$isCreator && !$isAdmin) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
}

// Sanitize the input
$data = $validator->sanitize($_POST);

// Prepare the data to update - only include form tutor decision
$updateData = [];

// Handle form tutor decision if provided
if (($isFormTutor || $isAdmin) && isset($data['formTutorDecision'])) {
    $updateData['formTutorDecision'] = $data['formTutorDecision'];
    $updateData['formTutorNotes'] = $data['formTutorNotes'] ?? '';
    
    // Update the status based on the form tutor decision
    if ($data['formTutorDecision'] == 'Resolved') {
        $updateData['status'] = 'Resolved';
    } elseif ($data['formTutorDecision'] == 'Eligibility Assessment') {
        $updateData['status'] = 'Eligibility Assessment';
    }
}

// Only update if there's data to update
if (!empty($updateData)) {
    $updated = $interventionGateway->update($gibbonINInterventionID, $updateData);

    if (!$updated) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }
}

// If decision is to conduct eligibility assessment, create an assessment record
if (isset($data['formTutorDecision']) && $data['formTutorDecision'] == 'Eligibility Assessment') {
    // Check if an assessment already exists
    $existingAssessment = $eligibilityAssessmentGateway->selectBy(['gibbonINInterventionID' => $gibbonINInterventionID])->fetch();
    
    if (empty($existingAssessment)) {
        // Create a new assessment
        $assessmentData = [
            'gibbonINInterventionID' => $gibbonINInterventionID,
            'gibbonPersonIDStudent' => $intervention['gibbonPersonIDStudent'],
            'gibbonPersonIDCreator' => $gibbonPersonID,
            'status' => 'In Progress',
            'eligibilityDecision' => 'Pending',
            'timestampCreated' => date('Y-m-d H:i:s')
        ];
        
        $assessmentID = $eligibilityAssessmentGateway->insert($assessmentData);
        
        if (!$assessmentID) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
            exit;
        }
        
        // Redirect to the assessment page
        $URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Interventions/intervention_process.php&gibbonINInterventionID='.$gibbonINInterventionID.'&step=2';
        header("Location: {$URL}");
        exit;
    }
}

// Determine the next step based on the current status
$nextStep = 1; // Default to step 1

switch($updateData['status'] ?? $intervention['status']) {
    case 'Referral':
    case 'Form Tutor Review':
        $nextStep = 1;
        break;
    case 'Eligibility Assessment':
        $nextStep = 2;
        break;
    case 'Intervention Required':
        $nextStep = 3;
        break;
    case 'Support Plan Active':
        $nextStep = 4;
        break;
    case 'Resolved':
        $nextStep = 5;
        break;
}

// Send notification to relevant staff if status has changed
if (isset($updateData['status']) && $updateData['status'] != $intervention['status']) {
    // Get student name for notification
    $studentName = '';
    try {
        $dataStudent = array('gibbonPersonID' => $intervention['gibbonPersonIDStudent']);
        $sqlStudent = "SELECT surname, preferredName FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID";
        $resultStudent = $connection2->prepare($sqlStudent);
        $resultStudent->execute($dataStudent);
        
        if ($resultStudent->rowCount() == 1) {
            $rowStudent = $resultStudent->fetch();
            $studentName = Format::name('', $rowStudent['preferredName'], $rowStudent['surname'], 'Student', false);
        }
    } catch (PDOException $e) {
        // Silent fail - just won't include student name in notification
    }
    
    $notificationText = sprintf(__('Intervention status for %s has been updated to: %s'), 
        $studentName, 
        __($updateData['status'])
    );
    
    // Notify the creator if they're not the one making the update
    if ($intervention['gibbonPersonIDCreator'] != $gibbonPersonID) {
        $notificationGateway->addNotification(
            $intervention['gibbonPersonIDCreator'], 
            $notificationText, 
            'Interventions', 
            '/index.php?q=/modules/Interventions/intervention_process.php&gibbonINInterventionID='.$gibbonINInterventionID
        );
    }
    
    // Notify form tutor if they're not the one making the update
    if ($intervention['gibbonPersonIDFormTutor'] != $gibbonPersonID && !empty($intervention['gibbonPersonIDFormTutor'])) {
        $notificationGateway->addNotification(
            $intervention['gibbonPersonIDFormTutor'], 
            $notificationText, 
            'Interventions', 
            '/index.php?q=/modules/Interventions/intervention_process.php&gibbonINInterventionID='.$gibbonINInterventionID
        );
    }
}

// Redirect to the appropriate step
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Interventions/intervention_process.php&gibbonINInterventionID='.$gibbonINInterventionID.'&step='.$nextStep;
header("Location: {$URL}");
exit;
