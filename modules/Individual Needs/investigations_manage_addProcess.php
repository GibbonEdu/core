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
use Gibbon\Domain\IndividualNeeds\INInvestigationGateway;
use Gibbon\Services\Format;
use Gibbon\Data\Validator;
use Gibbon\Comms\NotificationSender;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$gibbonPersonID = $_GET['gibbonPersonID'] ?? '';
$gibbonFormGroupID = $_GET['gibbonFormGroupID'] ?? '';
$gibbonYearGroupID = $_GET['gibbonYearGroupID'] ?? '';

$URL = $session->get('absoluteURL')."/index.php?q=/modules/Individual Needs/investigations_manage_add.php&gibbonPersonID=$gibbonPersonID&gibbonFormGroupID=$gibbonFormGroupID&gibbonYearGroupID=$gibbonYearGroupID";

if (isActionAccessible($guid, $connection2, '/modules/Individual Needs/investigations_manage_add.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $investigationGateway = $container->get(INInvestigationGateway::class);

    $data = [
        'gibbonSchoolYearID'    => $session->get('gibbonSchoolYearID'),
        'gibbonPersonIDCreator' => $session->get('gibbonPersonID'),
        'gibbonPersonIDStudent' => $_POST['gibbonPersonIDStudent'] ?? '',
        'status'                => 'Referral',
        'date'                  => Format::dateConvert($_POST['date']) ?? '',
        'reason'                => $_POST['reason'] ?? '',
        'strategiesTried'       => $_POST['strategiesTried'] ?? '',
        'parentsInformed'       => $_POST['parentsInformed'] ?? '',
        'parentsResponse'       => $_POST['parentsResponse'] ?? null
    ];

    // Validate the required values are present
    if (empty($data['gibbonPersonIDStudent']) || empty($data['date']) || empty($data['reason']) || empty($data['parentsInformed'])) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Create the record
    $gibbonINInvestigationID = $investigationGateway->insert($data);

    // Send notification to form tutors
    $notificationSender = $container->get(NotificationSender::class);
    
    // Get student information to use in notification
    $studentData = array('gibbonPersonID' => $data['gibbonPersonIDStudent']);
    $studentSQL = "SELECT gibbonPerson.preferredName, gibbonPerson.surname, gibbonFormGroup.gibbonFormGroupID, 
                         gibbonFormGroup.gibbonPersonIDTutor, gibbonFormGroup.gibbonPersonIDTutor2, gibbonFormGroup.gibbonPersonIDTutor3
                  FROM gibbonPerson 
                  LEFT JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID)
                  LEFT JOIN gibbonFormGroup ON (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID)
                  WHERE gibbonPerson.gibbonPersonID=:gibbonPersonID 
                  AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID";
    $studentData['gibbonSchoolYearID'] = $session->get('gibbonSchoolYearID');
    $studentResult = $pdo->select($studentSQL, $studentData);
    
    if ($studentResult && $studentResult->rowCount() > 0) {
        $student = $studentResult->fetch();
        $studentName = Format::name('', $student['preferredName'], $student['surname'], 'Student', false, true);
        $notificationString = __('An Individual Needs investigation for {student} has been initiated.', ['student' => $studentName]);
        $actionLink = "/index.php?q=/modules/Individual Needs/investigations_manage_edit.php&gibbonINInvestigationID=" . $gibbonINInvestigationID;
        
        // Get form tutors directly from the student record
        if (!empty($student['gibbonPersonIDTutor'])) {
            $notificationSender->addNotification($student['gibbonPersonIDTutor'], $notificationString, 'Individual Needs', $actionLink);
        }
        if (!empty($student['gibbonPersonIDTutor2'])) {
            $notificationSender->addNotification($student['gibbonPersonIDTutor2'], $notificationString, 'Individual Needs', $actionLink);
        }
        if (!empty($student['gibbonPersonIDTutor3'])) {
            $notificationSender->addNotification($student['gibbonPersonIDTutor3'], $notificationString, 'Individual Needs', $actionLink);
        }
        
        // Send all queued notifications
        $notificationSender->sendNotifications();
    }

    $URL .= !$gibbonINInvestigationID
        ? "&return=error2"
        : "&return=success0";

    header("Location: {$URL}&editID=$gibbonINInvestigationID");
}
