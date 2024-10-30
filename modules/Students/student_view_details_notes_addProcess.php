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

use Gibbon\Domain\System\SettingGateway;
use Gibbon\Services\Format;
use Gibbon\Comms\NotificationEvent;
use Gibbon\Domain\User\UserGateway;
use Gibbon\Domain\School\YearGroupGateway;
use Gibbon\Data\Validator;
use Gibbon\Domain\FormGroups\FormGroupGateway;
use Gibbon\Domain\Timetable\CourseEnrolmentGateway;
use Gibbon\Domain\Students\StudentGateway;
use Gibbon\Domain\Students\StudentNoteGateway;

include '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST, ['note' => 'HTML']);

$gibbonPersonID = $_GET['gibbonPersonID'] ?? '';
$subpage = $_GET['subpage'] ?? '';
$allStudents = $_GET['allStudents'] ?? '';
$URL = $session->get('absoluteURL')."/index.php?q=/modules/Students/student_view_details_notes_add.php&gibbonPersonID=$gibbonPersonID&search=".$_GET['search']."&subpage=$subpage&category=".$_GET['category']."&allStudents=$allStudents";

if (isActionAccessible($guid, $connection2, '/modules/Students/student_view_details_notes_add.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    $settingGateway = $container->get(SettingGateway::class);
    $enableStudentNotes = $settingGateway->getSettingByScope('Students', 'enableStudentNotes');
    $noteCreationNotification = $settingGateway->getSettingByScope('Students', 'noteCreationNotification');
    $noteGateway = $container->get(StudentNoteGateway::class);

    if ($enableStudentNotes != 'Y') {
        $URL .= '&return=error0';
        header("Location: {$URL}");
        exit;
    } 

    if ($gibbonPersonID == '' or $subpage == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    } 
    //Check for existence of student
    $student = $container->get(StudentGateway::class)->selectActiveStudentByPerson($session->get('gibbonSchoolYearID'), $gibbonPersonID, false)->fetch();

    if (empty($student)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    $title = $_POST['title'] ?? '';
    $gibbonStudentNoteCategoryID = $_POST['gibbonStudentNoteCategoryID'] ?? null;
    $note = $_POST['note'] ?? '';

    if (empty($title) || empty($note)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }
    
    //Write to database
    $inserted = $noteGateway->insert([
        'gibbonStudentNoteCategoryID' => $gibbonStudentNoteCategoryID,
        'title' => $title,
        'note' => $note,
        'gibbonPersonID' => $gibbonPersonID,
        'gibbonPersonIDCreator' => $session->get('gibbonPersonID'),
        'timestamp' => date('Y-m-d H:i:s', time()),
    ]);

    if (!$inserted) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    // Attempt to issue alerts form tutor(s) and teacher(s) according to settings
    if ($student['status'] == 'Full') {

        // Raise a new notification event
        $event = new NotificationEvent('Students', 'Student Notes');

        $staffName = Format::name('', $session->get('preferredName'), $session->get('surname'), 'Staff', false, true);
        $studentName = Format::name('', $student['preferredName'], $student['surname'], 'Student', false);

        $event->setNotificationText(sprintf(__('%1$s has added a student note ("%2$s") about %3$s.'), $staffName, $title, $studentName));
        $event->setActionLink("/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=$gibbonPersonID&search=".$_GET['search']."&subpage=$subpage&category=".$_GET['category']);

        $event->addScope('gibbonPersonIDStudent', $gibbonPersonID);
        $event->addScope('gibbonYearGroupID', $student['gibbonYearGroupID']);

        if ($noteCreationNotification == 'Tutors' || $noteCreationNotification == 'Tutors & Teachers') {
            // Add form group tutors
            $tutors = $container->get(FormGroupGateway::class)->selectTutorsByStudent($session->get('gibbonSchoolYearID'), $gibbonPersonID)->fetchAll();
            foreach ($tutors as $tutor) {
                $event->addRecipient($tutor['gibbonPersonID']);
            }

            // Add the HOY if there is one
            $yearGroup = $container->get(YearGroupGateway::class)->getByID($student['gibbonYearGroupID']);
            if (!empty($yearGroup['gibbonPersonIDHOY'])) {
                $event->addRecipient($yearGroup['gibbonPersonIDHOY']);
            }

        }
        if ($noteCreationNotification == 'Tutors & Teachers') {
            $teachers = $container->get(CourseEnrolmentGateway::class)->selectClassTeachersByStudent($session->get('gibbonSchoolYearID'), $gibbonPersonID)->fetchAll();
            foreach ($teachers as $teacher) {
                $event->addRecipient($teacher['gibbonPersonID']);
            }
        }

        // Send notifications
        $event->sendNotifications($pdo, $session);
    }

    $URL .= '&return=success0';
    header("Location: {$URL}");
}
