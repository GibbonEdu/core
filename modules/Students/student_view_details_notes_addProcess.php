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

    if ($enableStudentNotes != 'Y') {
        $URL .= '&return=error0';
        header("Location: {$URL}");
    } else {
        if ($gibbonPersonID == '' or $subpage == '') {
            echo 'Fatal error loading this page!';
        } else {
            //Check for existence of student
            try {
                $data = array('gibbonPersonID' => $gibbonPersonID, 'gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'));
                $sql = 'SELECT gibbonPerson.surname, gibbonPerson.preferredName, gibbonPerson.status, gibbonStudentEnrolment.gibbonYearGroupID
                    FROM gibbonPerson
                    LEFT JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID)
                    WHERE gibbonPerson.gibbonPersonID=:gibbonPersonID';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
                exit();
            }

            if ($result->rowCount() != 1) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
                exit();
            } else {
                $row = $result->fetch();
                $status = $row['status'];

                //Proceed!
                //Validate Inputs
                $title = $_POST['title'] ?? '';
                $gibbonStudentNoteCategoryID = $_POST['gibbonStudentNoteCategoryID'] ?? '';
                if ($gibbonStudentNoteCategoryID == '') {
                    $gibbonStudentNoteCategoryID = null;
                }
                $note = $_POST['note'] ?? '';

                if ($note == '' or $title == '') {
                    $URL .= '&return=error1';
                    header("Location: {$URL}");
                } else {
                    //Write to database
                    try {
                        $data = array('gibbonStudentNoteCategoryID' => $gibbonStudentNoteCategoryID, 'title' => $title, 'note' => $note, 'gibbonPersonID' => $gibbonPersonID, 'gibbonPersonIDCreator' => $session->get('gibbonPersonID'), 'timestamp' => date('Y-m-d H:i:s', time()));
                        $sql = 'INSERT INTO gibbonStudentNote SET gibbonStudentNoteCategoryID=:gibbonStudentNoteCategoryID, title=:title, note=:note, gibbonPersonID=:gibbonPersonID, gibbonPersonIDCreator=:gibbonPersonIDCreator, timestamp=:timestamp';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $URL .= '&return=error2';
                        header("Location: {$URL}");
                        exit();
                    }

                    //Attempt to issue alerts form tutor(s) and teacher(s) according to settings
                    if ($status == 'Full') {

                        // Raise a new notification event
                        $event = new NotificationEvent('Students', 'New Student Note');

                        $staffName = Format::name('', $session->get('preferredName'), $session->get('surname'), 'Staff', false, true);
                        $studentName = Format::name('', $row['preferredName'], $row['surname'], 'Student', false);

                        $event->setNotificationText(sprintf(__('%1$s has added a student note ("%2$s") about %3$s.'), $staffName, $title, $studentName));
                        $event->setActionLink("/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=$gibbonPersonID&search=".$_GET['search']."&subpage=$subpage&category=".$_GET['category']);

                        $event->addScope('gibbonPersonIDStudent', $gibbonPersonID);
                        $event->addScope('gibbonYearGroupID', $row['gibbonYearGroupID']);

                        if ($noteCreationNotification == 'Tutors' or $noteCreationNotification == 'Tutors & Teachers') {
                            try {
                                $data = array('gibbonPersonID' => $gibbonPersonID, 'gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'));
                                $sql = "SELECT gibbonPerson.gibbonPersonID
    								FROM gibbonStudentEnrolment
    								JOIN gibbonFormGroup ON (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID)
    								LEFT JOIN gibbonPerson ON ((gibbonPerson.gibbonPersonID=gibbonFormGroup.gibbonPersonIDTutor AND gibbonPerson.status='Full') OR (gibbonPerson.gibbonPersonID=gibbonFormGroup.gibbonPersonIDTutor2 AND gibbonPerson.status='Full') OR (gibbonPerson.gibbonPersonID=gibbonFormGroup.gibbonPersonIDTutor3 AND gibbonPerson.status='Full'))
    								WHERE gibbonStudentEnrolment.gibbonPersonID=:gibbonPersonID AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID";
                                $result = $connection2->prepare($sql);
                                $result->execute($data);
                            } catch (PDOException $e) {}
                            while ($rowTutors = $result->fetch()) {
                                $event->addRecipient($rowTutors['gibbonPersonID']);
                            }

                            // Add the HOY if there is one
                            $yearGroup = $container->get(YearGroupGateway::class)->getByID($row['gibbonYearGroupID']);
                            $yearGroupHOY = $container->get(UserGateway::class)->getByID($yearGroup['gibbonPersonIDHOY'] ?? '');
                            if (!empty($yearGroupHOY)) {
                                $event->addRecipient($yearGroupHOY['gibbonPersonID']);
                            }

                        }
                        if ($noteCreationNotification == 'Tutors & Teachers') {

                                $data = array('gibbonPersonID' => $gibbonPersonID, 'gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'));
                                $sql = "SELECT DISTINCT teacher.gibbonPersonID FROM gibbonPerson AS teacher JOIN gibbonCourseClassPerson AS teacherClass ON (teacherClass.gibbonPersonID=teacher.gibbonPersonID)  JOIN gibbonCourseClassPerson AS studentClass ON (studentClass.gibbonCourseClassID=teacherClass.gibbonCourseClassID) JOIN gibbonPerson AS student ON (studentClass.gibbonPersonID=student.gibbonPersonID) JOIN gibbonCourseClass ON (studentClass.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE teacher.status='Full' AND teacherClass.role='Teacher' AND studentClass.role='Student' AND student.gibbonPersonID=:gibbonPersonID AND gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY teacher.preferredName, teacher.surname, teacher.email ;";
                                $result = $connection2->prepare($sql);
                                $result->execute($data);
                            while ($row = $result->fetch()) {
                                $event->addRecipient($row['gibbonPersonID']);
                            }
                        }

                        // Send notifications
                        $event->sendNotifications($pdo, $session);
                    }

                    $URL .= '&return=success0';
                    header("Location: {$URL}");
                }
            }
        }
    }
}
