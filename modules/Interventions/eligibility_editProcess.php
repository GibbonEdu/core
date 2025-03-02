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

use Gibbon\Comms\NotificationSender;
use Gibbon\Domain\System\NotificationGateway;
use Gibbon\Domain\IndividualNeeds\INInvestigationGateway;
use Gibbon\Domain\IndividualNeeds\INEligibilityAssessmentGateway;
use Gibbon\Services\Format;
use Gibbon\Data\Validator;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$gibbonPersonID = $_GET['gibbonPersonID'] ?? '';
$gibbonFormGroupID = $_GET['gibbonFormGroupID'] ?? '';
$gibbonYearGroupID = $_GET['gibbonYearGroupID'] ?? '';
$gibbonINInvestigationID = $_POST['gibbonINInvestigationID'] ?? '';

$URL = $session->get('absoluteURL')."/index.php?q=/modules/Individual Needs/eligibility_edit.php&gibbonINInvestigationID=$gibbonINInvestigationID&gibbonPersonID=$gibbonPersonID&gibbonFormGroupID=$gibbonFormGroupID&gibbonYearGroupID=$gibbonYearGroupID";

if (isActionAccessible($guid, $connection2, '/modules/Individual Needs/eligibility_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    $highestAction = getHighestGroupedAction($guid, '/modules/Individual Needs/eligibility_manage.php', $connection2);
    if (empty($highestAction)) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
        exit;
    }

    // Get investigation
    $investigationGateway = $container->get(INInvestigationGateway::class);
    $investigation = $investigationGateway->getByID($gibbonINInvestigationID);

    if (empty($investigation)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Check access based on the highest action level
    if ($highestAction == 'Manage Eligibility Assessments_my' && $investigation['gibbonPersonIDCreator'] != $session->get('gibbonPersonID')) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
        exit;
    }

    // Get student name for notifications
    $studentName = Format::name('', $investigation['preferredName'], $investigation['surname'], 'Student', true);

    // Determine if this is a completion or update
    $isCompletion = ($investigation['status'] == 'Eligibility Assessment' && isset($_POST['eligibilityDecision']) && $_POST['eligibilityDecision'] != 'Pending');

    // Update the investigation
    $data = [
        'eligibilityDecision' => $_POST['eligibilityDecision'] ?? $investigation['eligibilityDecision'],
        'eligibilityNotes' => $_POST['eligibilityNotes'] ?? $investigation['eligibilityNotes'],
    ];

    // If completing the eligibility assessment, update the status
    if ($isCompletion) {
        $data['status'] = 'Eligibility Complete';
    }

    $updated = $investigationGateway->update($gibbonINInvestigationID, $data);

    if (!$updated) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    // Send notifications
    $notificationGateway = $container->get(NotificationGateway::class);
    $notificationSender = new NotificationSender($notificationGateway, $session);

    // Determine notification message based on action
    if ($isCompletion) {
        $eligibilityStatus = ($_POST['eligibilityDecision'] == 'Eligible') ? __('eligible for intervention') : __('not eligible for intervention');
        $notificationString = __('The eligibility assessment for {student} has been completed. The student is {status}.', [
            'student' => $studentName,
            'status' => $eligibilityStatus
        ]);
    } else {
        $notificationString = __('The eligibility assessment for {student} has been updated.', [
            'student' => $studentName
        ]);
    }

    // Notify the creator if not the current user
    if ($investigation['gibbonPersonIDCreator'] != $session->get('gibbonPersonID')) {
        $notificationSender->addNotification(
            $investigation['gibbonPersonIDCreator'],
            $notificationString,
            'Individual Needs',
            '/index.php?q=/modules/Individual Needs/eligibility_edit.php&gibbonINInvestigationID='.$gibbonINInvestigationID
        );
    }

    // Get all assessors to notify them
    $eligibilityAssessmentGateway = $container->get(INEligibilityAssessmentGateway::class);
    $criteria = $eligibilityAssessmentGateway->newQueryCriteria();
    $assessments = $eligibilityAssessmentGateway->queryAssessmentsByInvestigation($criteria, $gibbonINInvestigationID);

    foreach ($assessments as $assessment) {
        if (!empty($assessment['gibbonPersonIDAssessor']) && $assessment['gibbonPersonIDAssessor'] != $session->get('gibbonPersonID')) {
            $notificationSender->addNotification(
                $assessment['gibbonPersonIDAssessor'],
                $notificationString,
                'Individual Needs',
                '/index.php?q=/modules/Individual Needs/eligibility_edit.php&gibbonINInvestigationID='.$gibbonINInvestigationID
            );
        }
    }

    // Send all notifications
    $notificationSender->sendNotifications();

    // Set success message and redirect
    $URL .= '&return=success0';
    header("Location: {$URL}");
    exit;
}
