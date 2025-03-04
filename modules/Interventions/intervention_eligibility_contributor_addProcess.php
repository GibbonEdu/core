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

use Gibbon\Comms\NotificationSender;
use Gibbon\Domain\System\NotificationGateway;
use Gibbon\Module\Interventions\Domain\INInterventionGateway;
use Gibbon\Module\Interventions\Domain\INInterventionEligibilityAssessmentGateway;
use Gibbon\Module\Interventions\Domain\INInterventionEligibilityContributorGateway;
use Gibbon\Services\Format;
use Gibbon\Data\Validator;

require_once '../../gibbon.php';
require_once __DIR__ . '/moduleFunctions.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$gibbonINInterventionID = $_POST['gibbonINInterventionID'] ?? '';
$gibbonINInterventionEligibilityAssessmentID = $_POST['gibbonINInterventionEligibilityAssessmentID'] ?? '';
$gibbonPersonIDStudent = $_POST['gibbonPersonIDStudent'] ?? '';
$gibbonFormGroupID = $_POST['gibbonFormGroupID'] ?? '';
$gibbonYearGroupID = $_POST['gibbonYearGroupID'] ?? '';
$status = $_POST['status'] ?? '';
$returnProcess = $_POST['returnProcess'] ?? false;

// Get the redirect URL
$URL = getInterventionRedirectURL($session, $gibbonINInterventionID, $gibbonINInterventionEligibilityAssessmentID, $gibbonPersonIDStudent, $gibbonFormGroupID, $gibbonYearGroupID, $status, $returnProcess);

if (isActionAccessible($guid, $connection2, '/modules/Interventions/intervention_eligibility_contributor_add.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $gibbonPersonIDContributor = $_POST['gibbonPersonIDContributor'] ?? '';
    $contributorNotes = $_POST['contributorNotes'] ?? '';
    
    // Assessment type is now optional at this stage and will be selected by the contributor

    // Validate the required values
    if (empty($gibbonINInterventionID) || empty($gibbonINInterventionEligibilityAssessmentID) || empty($gibbonPersonIDContributor)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Get intervention
    $interventionGateway = $container->get(INInterventionGateway::class);
    $intervention = $interventionGateway->getByID($gibbonINInterventionID);

    if (empty($intervention)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    // Get the eligibility assessment
    $eligibilityAssessmentGateway = $container->get(INInterventionEligibilityAssessmentGateway::class);
    $assessment = $eligibilityAssessmentGateway->getByID($gibbonINInterventionEligibilityAssessmentID);

    if (empty($assessment)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    // Check if the contributor already exists
    $sql = "SELECT COUNT(*) FROM gibbonINInterventionEligibilityContributor 
            WHERE gibbonINInterventionEligibilityAssessmentID=:gibbonINInterventionEligibilityAssessmentID 
            AND gibbonPersonIDContributor=:gibbonPersonIDContributor";
    $result = $pdo->select($sql, [
        'gibbonINInterventionEligibilityAssessmentID' => $gibbonINInterventionEligibilityAssessmentID,
        'gibbonPersonIDContributor' => $gibbonPersonIDContributor
    ]);
    
    if ($result->rowCount() > 0 && $result->fetchColumn(0) > 0) {
        $URL .= '&return=error3';
        header("Location: {$URL}");
        exit;
    }

    // Create a new contributor record
    $data = [
        'gibbonINInterventionEligibilityAssessmentID' => $gibbonINInterventionEligibilityAssessmentID,
        'gibbonPersonIDContributor' => $gibbonPersonIDContributor,
        'notes' => $contributorNotes,
        'status' => 'Pending',
        'timestampCreated' => date('Y-m-d H:i:s')
    ];

    // Insert the contributor
    $sql = "INSERT INTO gibbonINInterventionEligibilityContributor 
            (gibbonINInterventionEligibilityAssessmentID, gibbonPersonIDContributor, notes, status, timestampCreated) 
            VALUES 
            (:gibbonINInterventionEligibilityAssessmentID, :gibbonPersonIDContributor, :notes, :status, :timestampCreated)";
    
    $inserted = $pdo->insert($sql, $data);

    if (!$inserted) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    // Update intervention status if it's still in Referral status
    if ($intervention['status'] == 'Referral') {
        $interventionGateway->update($gibbonINInterventionID, [
            'status' => 'Eligibility Assessment'
        ]);
    }

    // Send notification to the contributor
    $notificationGateway = $container->get(NotificationGateway::class);
    $notificationSender = new NotificationSender($notificationGateway, $session);

    // Get student name for notifications
    $sql = "SELECT gibbonPerson.preferredName, gibbonPerson.surname 
            FROM gibbonPerson 
            WHERE gibbonPerson.gibbonPersonID=:gibbonPersonID";
    $result = $pdo->select($sql, ['gibbonPersonID' => $intervention['gibbonPersonIDStudent']]);
    $student = ($result->rowCount() > 0) ? $result->fetch() : [];
    $studentName = Format::name('', $student['preferredName'] ?? '', $student['surname'] ?? '', 'Student', true);

    $notificationString = __('You have been asked to contribute to an eligibility assessment for {student}. Please select the type of assessment you wish to perform when you complete your contribution.', [
        'student' => $studentName
    ]);

    $notificationSender->addNotification(
        $gibbonPersonIDContributor,
        $notificationString,
        'Intervention',
        '/index.php?q=/modules/Interventions/intervention_eligibility_edit.php&gibbonINInterventionID='.$gibbonINInterventionID.'&gibbonINInterventionEligibilityAssessmentID='.$gibbonINInterventionEligibilityAssessmentID
    );

    $notificationSender->sendNotifications();

    // Set success message and redirect
    $URL .= '&return=success0';
    header("Location: {$URL}");
    exit;
}
