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

require_once '../../gibbon.php';

// Module includes
require_once __DIR__ . '/moduleFunctions.php';

$gibbonINInterventionID = $_POST['gibbonINInterventionID'] ?? '';
$gibbonINInterventionEligibilityAssessmentID = $_POST['gibbonINInterventionEligibilityAssessmentID'] ?? '';

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Interventions/intervention_process.php&gibbonINInterventionID='.$gibbonINInterventionID.'&step=2';

if (empty($gibbonINInterventionID) || empty($gibbonINInterventionEligibilityAssessmentID)) {
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

// Get the assessment
$assessment = $eligibilityAssessmentGateway->getByID($gibbonINInterventionEligibilityAssessmentID);

if (empty($assessment)) {
    $URL .= '&return=error2';
    header("Location: {$URL}");
    exit;
}

// Check permissions
$gibbonPersonID = $_SESSION[$guid]['gibbonPersonID'];
$highestAction = getHighestGroupedAction($guid, '/modules/Interventions/intervention_process.php', $connection2);

$isAdmin = ($highestAction == 'Manage Interventions');
$isAssessmentCreator = ($assessment['gibbonPersonIDCreator'] == $gibbonPersonID);

if (!$isAdmin && !$isAssessmentCreator) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
}

// Sanitize the input
$data = $validator->sanitize($_POST);

// Validate required fields
if (empty($data['eligibilityDecision'])) {
    $URL .= '&return=error1';
    header("Location: {$URL}");
    exit;
}

// Prepare the data to update the assessment
$updateData = [
    'eligibilityDecision' => $data['eligibilityDecision'],
    'notes' => $data['notes'] ?? '',
    'status' => 'Complete'
];

// Update the assessment
$updated = $eligibilityAssessmentGateway->update($gibbonINInterventionEligibilityAssessmentID, $updateData);

if (!$updated) {
    $URL .= '&return=error2';
    header("Location: {$URL}");
    exit;
}

// Update the intervention status based on the decision
$interventionStatus = '';
$nextStep = 2;

switch ($data['eligibilityDecision']) {
    case 'Intervention Required':
        $interventionStatus = 'Intervention Required';
        $nextStep = 3;
        break;
    case 'No Intervention Required':
        $interventionStatus = 'Resolved';
        $nextStep = 5;
        break;
    case 'Refer for IEP':
        $interventionStatus = 'Referred for IEP';
        $nextStep = 5;
        break;
    default:
        $interventionStatus = 'Eligibility Assessment';
}

// Update the intervention status
if (!empty($interventionStatus)) {
    $interventionGateway->update($gibbonINInterventionID, ['status' => $interventionStatus]);
}

// Send notifications
// Get the student name for the notification
$studentName = formatName('', $intervention['preferredName'], $intervention['surname'], 'Student');

// Notify the form tutor
if ($intervention['gibbonPersonIDFormTutor'] != $gibbonPersonID) {
    $notificationText = sprintf(__('The eligibility assessment for %1$s has been completed with decision: %2$s'), $studentName, $data['eligibilityDecision']);
    
    $notificationGateway->addNotification(
        $intervention['gibbonPersonIDFormTutor'],
        $notificationText,
        'Interventions',
        '/index.php?q=/modules/Interventions/intervention_process.php&gibbonINInterventionID='.$gibbonINInterventionID.'&step='.$nextStep
    );
}

// Notify the creator if different from form tutor and current user
if ($intervention['gibbonPersonIDCreator'] != $intervention['gibbonPersonIDFormTutor'] && $intervention['gibbonPersonIDCreator'] != $gibbonPersonID) {
    $notificationText = sprintf(__('The eligibility assessment for %1$s has been completed with decision: %2$s'), $studentName, $data['eligibilityDecision']);
    
    $notificationGateway->addNotification(
        $intervention['gibbonPersonIDCreator'],
        $notificationText,
        'Interventions',
        '/index.php?q=/modules/Interventions/intervention_process.php&gibbonINInterventionID='.$gibbonINInterventionID.'&step='.$nextStep
    );
}

// Redirect to the appropriate step
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Interventions/intervention_process.php&gibbonINInterventionID='.$gibbonINInterventionID.'&step='.$nextStep.'&return=success0';
header("Location: {$URL}");
exit;
