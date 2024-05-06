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

use Gibbon\Services\Format;
use Gibbon\Domain\User\UserGateway;
use Gibbon\Domain\Students\StudentGateway;
use Gibbon\Domain\Students\StudentNoteGateway;
use Gibbon\Comms\NotificationEvent;
use Gibbon\Domain\School\YearGroupGateway;
use Gibbon\Domain\FormGroups\FormGroupGateway;
use Gibbon\Domain\Timetable\CourseEnrolmentGateway;
use Gibbon\Domain\IndividualNeeds\INAssistantGateway;
use Gibbon\Domain\User\UserStatusLogGateway;
use Gibbon\Data\Validator;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$gibbonPersonID = $_POST['gibbonPersonID'] ?? '';
$URL = $session->get('absoluteURL').'/index.php?q=/modules/Admissions/student_withdraw.php';

if (isActionAccessible($guid, $connection2, '/modules/Admissions/student_withdraw.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $userGateway = $container->get(UserGateway::class);
    $studentGateway = $container->get(StudentGateway::class);

    $data = [
        'status'          => $_POST['status'] ?? '',
        'dateEnd'         => isset($_POST['dateEnd']) ? Format::dateConvert($_POST['dateEnd']) : null,
        'departureReason' => $_POST['departureReason'] ?? '',
        'nextSchool'      => $_POST['nextSchool'] ?? '',
    ];

    // Validate the required values are present
    if (empty($gibbonPersonID) || empty($data['status']) || empty($data['dateEnd']) || empty($data['departureReason'])) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Validate the database relationships exist
    $person = $userGateway->getByID($gibbonPersonID);
    $student = $studentGateway->selectActiveStudentByPerson($session->get('gibbonSchoolYearID'), $gibbonPersonID)->fetch();

    if (empty($person) || empty($student)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    // Update the user data
    $updated = $userGateway->update($gibbonPersonID, $data);
    $partialFail = !$updated;

    if ($updated) {
        $withdrawNote = $_POST['withdrawNote'] ?? '';
        if (!empty($withdrawNote)) {
            $noteGateway = $container->get(StudentNoteGateway::class);
            $inserted = $noteGateway->insert([
                'title'                       => __('Student Withdrawn'),
                'note'                        => $withdrawNote,
                'gibbonPersonID'              => $gibbonPersonID,
                'gibbonPersonIDCreator'       => $session->get('gibbonPersonID'),
                'gibbonStudentNoteCategoryID' => $noteGateway->getNoteCategoryIDByName('Academic') ?? null,
                'timestamp'                   => date('Y-m-d H:i:s', time()),
            ]);

            $partialFail &= !$inserted;
        }

        if ($data['status'] != $person['status']) {
            $statusReason = !empty($data['departureReason']) 
                ? __('Student Withdrawn').': '.$data['departureReason'] 
                : __('Student Withdrawn');

            $userStatusLogGateway = $container->get(UserStatusLogGateway::class);
            $userStatusLogGateway->insert(['gibbonPersonID' => $gibbonPersonID, 'statusOld' => $person['status'], 'statusNew' => $data['status'], 'reason' => $statusReason]);
        }

        $notify = $_POST['notify'] ?? [];
        $notificationList = isset($_POST['notificationList'])? explode(',', $_POST['notificationList']) : [];

        if (!empty($notify) || !empty($notificationList)) {
            // Create the notification body
            $studentName = Format::name('', $student['preferredName'], $student['surname'], 'Student', false, true);
                
            $today = date("Y-m-d"); 
            if ($today > $data['dateEnd']) {
                $notificationString = __('{student} {formGroup} has withdrawn from {school} on {date}.', [
                    'student'   => $studentName,
                    'formGroup'   => $student['formGroup'],
                    'school'    => $session->get('organisationNameShort'),
                    'date'      => Format::date($data['dateEnd']),
                ]);
            } else {
                $notificationString = __('{student} {formGroup} will withdraw from {school}, effective from {date}.', [
                    'student'   => $studentName,
                    'formGroup'   => $student['formGroup'],
                    'school'    => $session->get('organisationNameShort'),
                    'date'      => Format::date($data['dateEnd']),
                ]);
            }
            
            if (!empty($withdrawNote)) {
                $notificationString .= '<br/><br/>'.__('Withdraw Note').': '.$withdrawNote;
            }

            // Raise a new notification event
            $event = new NotificationEvent('Admissions', 'Student Withdrawn');
            $event->addScope('gibbonPersonIDStudent', $gibbonPersonID);
            $event->addScope('gibbonYearGroupID', $student['gibbonYearGroupID']);
            $event->setNotificationText($notificationString);
            $event->setActionLink('/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID='.$gibbonPersonID.'&search=&sort=&allStudents=on');

            // Notify Additional People
            foreach ($notificationList as $gibbonPersonIDNotify) {
                $event->addRecipient($gibbonPersonIDNotify);
            }

            // Admissions Administrator
            if (in_array('admin', $notify)) {
                $event->addRecipient($session->get('organisationAdmissions'));
            }

            // Head of Year
            if (in_array('HOY', $notify)) {
                $yearGroup = $container->get(YearGroupGateway::class)->getByID($student['gibbonYearGroupID']);
                $event->addRecipient($yearGroup['gibbonPersonIDHOY']);
            }

            // Form Tutors
            if (in_array('tutors', $notify)) {
                $formGroup = $container->get(FormGroupGateway::class)->getByID($student['gibbonFormGroupID']);
                $event->addRecipient($formGroup['gibbonPersonIDTutor']);
                $event->addRecipient($formGroup['gibbonPersonIDTutor2']);
                $event->addRecipient($formGroup['gibbonPersonIDTutor3']);
            }

            // Class Teachers
            if (in_array('teachers', $notify)) {
                $teachers = $container->get(CourseEnrolmentGateway::class)->selectClassTeachersByStudent($session->get('gibbonSchoolYearID'), $gibbonPersonID);
                foreach ($teachers as $teacher) {
                    $event->addRecipient($teacher['gibbonPersonID']);
                }
            }

            // Educational Assistants
            if (in_array('EAs', $notify)) {
                $EAs = $container->get(INAssistantGateway::class)->selectINAssistantsByStudent($gibbonPersonID);
                foreach ($EAs as $EA) {
                    $event->addRecipient($EA['gibbonPersonID']);
                }
            }

            // Add event listeners to the notification sender
            $event->sendNotifications($pdo, $session);
        }
    }

    $URL .= $partialFail
        ? "&return=warning1"
        : "&return=success0";

    header("Location: {$URL}");
}
