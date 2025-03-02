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
use Gibbon\Domain\Interventions\INReferralGateway;
use Gibbon\Domain\Interventions\INEligibilityAssessmentGateway;
use Gibbon\Services\Format;
use Gibbon\Data\Validator;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$gibbonINReferralID = $_POST['gibbonINReferralID'] ?? '';
$gibbonINEligibilityAssessmentID = $_POST['gibbonINEligibilityAssessmentID'] ?? '';
$gibbonPersonID = $_POST['gibbonPersonID'] ?? '';
$gibbonFormGroupID = $_POST['gibbonFormGroupID'] ?? '';
$gibbonYearGroupID = $_POST['gibbonYearGroupID'] ?? '';

$URL = $session->get('absoluteURL')."/index.php?q=/modules/Intervention/eligibility_edit.php&gibbonINReferralID=$gibbonINReferralID&gibbonPersonID=$gibbonPersonID&gibbonFormGroupID=$gibbonFormGroupID&gibbonYearGroupID=$gibbonYearGroupID";

if (isActionAccessible($guid, $connection2, '/modules/Intervention/eligibility_contributor_add.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $gibbonPersonIDContributor = $_POST['gibbonPersonIDContributor'] ?? '';
    $contributorNotes = $_POST['contributorNotes'] ?? '';

    // Validate the required values
    if (empty($gibbonINReferralID) || empty($gibbonINEligibilityAssessmentID) || empty($gibbonPersonIDContributor)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Get referral
    $referralGateway = $container->get(INReferralGateway::class);
    $referral = $referralGateway->getByID($gibbonINReferralID);

    if (empty($referral)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    // Get the eligibility assessment
    $eligibilityAssessmentGateway = $container->get(INEligibilityAssessmentGateway::class);
    $assessment = $eligibilityAssessmentGateway->getByID($gibbonINEligibilityAssessmentID);

    if (empty($assessment)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    // Check if the assessment already has an assessor
    if (!empty($assessment['gibbonPersonIDAssessor'])) {
        $URL .= '&return=error3';
        header("Location: {$URL}");
        exit;
    }

    // Update the assessment with the contributor
    $data = [
        'gibbonPersonIDAssessor' => $gibbonPersonIDContributor,
        'notes' => $contributorNotes,
    ];

    $updated = $eligibilityAssessmentGateway->update($gibbonINEligibilityAssessmentID, $data);

    if (!$updated) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    // Send notification to the contributor
    $notificationGateway = $container->get(NotificationGateway::class);
    $notificationSender = new NotificationSender($notificationGateway, $session);

    // Get assessment type name
    $sql = "SELECT name FROM gibbonINEligibilityAssessmentType WHERE gibbonINEligibilityAssessmentTypeID=:gibbonINEligibilityAssessmentTypeID";
    $result = $pdo->select($sql, ['gibbonINEligibilityAssessmentTypeID' => $assessment['gibbonINEligibilityAssessmentTypeID']]);
    $assessmentTypeName = ($result->rowCount() > 0) ? $result->fetchColumn(0) : __('Unknown');

    // Get student name for notifications
    $studentName = Format::name('', $referral['preferredName'], $referral['surname'], 'Student', true);

    $notificationString = __('You have been assigned to complete a {assessmentType} for {student}.', [
        'assessmentType' => $assessmentTypeName,
        'student' => $studentName
    ]);

    $notificationSender->addNotification(
        $gibbonPersonIDContributor,
        $notificationString,
        'Intervention',
        '/index.php?q=/modules/Intervention/eligibility_assessment_edit.php&gibbonINEligibilityAssessmentID='.$gibbonINEligibilityAssessmentID
    );

    $notificationSender->sendNotifications();

    // Set success message and redirect
    $URL .= '&return=success0';
    header("Location: {$URL}");
    exit;
}
