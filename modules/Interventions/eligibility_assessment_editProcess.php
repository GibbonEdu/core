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
use Gibbon\FileUploader;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$gibbonINEligibilityAssessmentID = $_POST['gibbonINEligibilityAssessmentID'] ?? '';

$URL = $session->get('absoluteURL')."/index.php?q=/modules/Intervention/eligibility_assessment_edit.php&gibbonINEligibilityAssessmentID=$gibbonINEligibilityAssessmentID";

if (isActionAccessible($guid, $connection2, '/modules/Intervention/eligibility_assessment_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $result = $_POST['result'] ?? '';
    $date = $_POST['date'] ?? '';
    $notes = $_POST['notes'] ?? '';

    // Validate the required values
    if (empty($gibbonINEligibilityAssessmentID) || empty($result) || empty($date) || empty($notes)) {
        $URL .= '&return=error1';
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

    // Check access - must be the assigned assessor or have admin rights
    $highestAction = getHighestGroupedAction($guid, '/modules/Intervention/eligibility_manage.php', $connection2);
    if ($highestAction == 'Manage Eligibility Assessments_my' && $assessment['gibbonPersonIDAssessor'] != $session->get('gibbonPersonID')) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
        exit;
    }

    // Handle file upload if present
    $documentPath = $assessment['documentPath'];
    $partialFail = false;

    if (!empty($_FILES['documentFile']['tmp_name'])) {
        $fileUploader = new FileUploader($pdo, $session);
        $documentPath = $fileUploader->uploadFromPost($_FILES['documentFile'], 'EligibilityAssessment_'.$gibbonINEligibilityAssessmentID);

        if (empty($documentPath)) {
            $partialFail = true;
        }
    }

    // Update the assessment
    $data = [
        'result' => $result,
        'date' => $date,
        'notes' => $notes,
        'documentPath' => $documentPath,
    ];

    $updated = $eligibilityAssessmentGateway->update($gibbonINEligibilityAssessmentID, $data);

    if (!$updated) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    // Get referral
    $referralGateway = $container->get(INReferralGateway::class);
    $referral = $referralGateway->getByID($assessment['gibbonINReferralID']);

    // Send notification to the referral creator
    $notificationGateway = $container->get(NotificationGateway::class);
    $notificationSender = new NotificationSender($notificationGateway, $session);

    // Get assessment type name
    $sql = "SELECT name FROM gibbonINEligibilityAssessmentType WHERE gibbonINEligibilityAssessmentTypeID=:gibbonINEligibilityAssessmentTypeID";
    $resultType = $pdo->select($sql, ['gibbonINEligibilityAssessmentTypeID' => $assessment['gibbonINEligibilityAssessmentTypeID']]);
    $assessmentTypeName = ($resultType->rowCount() > 0) ? $resultType->fetchColumn(0) : __('Unknown');

    // Get student name for notifications
    $studentName = Format::name('', $referral['preferredName'], $referral['surname'], 'Student', true);

    $notificationString = __('The {assessmentType} for {student} has been completed with a result of {result}.', [
        'assessmentType' => $assessmentTypeName,
        'student' => $studentName,
        'result' => __($result)
    ]);

    // Notify the referral creator
    if ($referral['gibbonPersonIDCreator'] != $session->get('gibbonPersonID')) {
        $notificationSender->addNotification(
            $referral['gibbonPersonIDCreator'],
            $notificationString,
            'Intervention',
            '/index.php?q=/modules/Intervention/eligibility_edit.php&gibbonINReferralID='.$assessment['gibbonINReferralID']
        );
    }

    // Check if all assessments are complete and notify creator
    $criteria = $eligibilityAssessmentGateway->newQueryCriteria();
    $assessments = $eligibilityAssessmentGateway->queryAssessmentsByReferral($criteria, $assessment['gibbonINReferralID']);
    
    $allComplete = true;
    foreach ($assessments as $assessmentItem) {
        if ($assessmentItem['result'] == 'Inconclusive' || empty($assessmentItem['date'])) {
            $allComplete = false;
            break;
        }
    }

    if ($allComplete) {
        $notificationString = __('All eligibility assessments for {student} have been completed. Please review and make a final eligibility decision.', [
            'student' => $studentName
        ]);

        $notificationSender->addNotification(
            $referral['gibbonPersonIDCreator'],
            $notificationString,
            'Intervention',
            '/index.php?q=/modules/Intervention/eligibility_edit.php&gibbonINReferralID='.$assessment['gibbonINReferralID']
        );
    }

    $notificationSender->sendNotifications();

    // Set success message and redirect
    if ($partialFail) {
        $URL .= '&return=warning1';
    } else {
        $URL .= '&return=success0';
    }
    
    header("Location: {$URL}");
    exit;
}
