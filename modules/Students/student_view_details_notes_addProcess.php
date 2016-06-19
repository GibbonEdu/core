<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

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

include '../../functions.php';
include '../../config.php';

//New PDO DB connection
$pdo = new Gibbon\sqlConnection();
$connection2 = $pdo->getConnection();

@session_start();

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]['timezone']);

$gibbonPersonID = $_GET['gibbonPersonID'];
$subpage = $_GET['subpage'];
$allStudents = '';
if (isset($_GET['allStudents'])) {
    $allStudents = $_GET['allStudents'];
}
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/student_view_details_notes_add.php&gibbonPersonID=$gibbonPersonID&search=".$_GET['search']."&subpage=$subpage&category=".$_GET['category']."&allStudents=$allStudents";

if (isActionAccessible($guid, $connection2, '/modules/Students/student_view_details_notes_add.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    $enableStudentNotes = getSettingByScope($connection2, 'Students', 'enableStudentNotes');
    if ($enableStudentNotes != 'Y') {
        $URL .= '&return=error0';
        header("Location: {$URL}");
    } else {
        if ($gibbonPersonID == '' or $subpage == '') {
            echo 'Fatal error loading this page!';
        } else {
            //Check for existence of student
            try {
                $data = array('gibbonPersonID' => $gibbonPersonID);
                $sql = 'SELECT surname, preferredName, status FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID';
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
                $name = formatName('', $row['preferredName'], $row['surname'], 'Student', false);
                $status = $row['status'];

                //Proceed!
                //Validate Inputs
                $title = $_POST['title'];
                $gibbonStudentNoteCategoryID = $_POST['gibbonStudentNoteCategoryID'];
                if ($gibbonStudentNoteCategoryID == '') {
                    $gibbonStudentNoteCategoryID = null;
                }
                $note = $_POST['note'];

                if ($note == '' or $title == '') {
                    $URL .= '&return=error1';
                    header("Location: {$URL}");
                } else {
                    //Write to database
                    try {
                        $data = array('gibbonStudentNoteCategoryID' => $gibbonStudentNoteCategoryID, 'title' => $title, 'note' => $note, 'gibbonPersonID' => $gibbonPersonID, 'gibbonPersonIDCreator' => $_SESSION[$guid]['gibbonPersonID'], 'timestamp' => date('Y-m-d H:i:s', time()));
                        $sql = 'INSERT INTO gibbonStudentNote SET gibbonStudentNoteCategoryID=:gibbonStudentNoteCategoryID, title=:title, note=:note, gibbonPersonID=:gibbonPersonID, gibbonPersonIDCreator=:gibbonPersonIDCreator, timestamp=:timestamp';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $URL .= '&return=error2';
                        header("Location: {$URL}");
                        exit();
                    }

                    //Attempt to alert form tutor(s)
                    if ($status == 'Full') {
                        try {
                            $data = array('gibbonPersonID' => $gibbonPersonID, 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                            $sql = "SELECT tutor1.gibbonPersonID AS tutor1gibbonPersonID, tutor2.gibbonPersonID AS tutor2gibbonPersonID, tutor3.gibbonPersonID AS tutor3gibbonPersonID 
								FROM gibbonStudentEnrolment 
								JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) 
								LEFT JOIN gibbonPerson AS tutor1 ON (tutor1.gibbonPersonID=gibbonRollGroup.gibbonPersonIDTutor AND tutor1.status='Full') 
								LEFT JOIN gibbonPerson AS tutor2 ON (tutor2.gibbonPersonID=gibbonRollGroup.gibbonPersonIDTutor2 AND tutor2.status='Full') 
								LEFT JOIN gibbonPerson AS tutor3 ON (tutor3.gibbonPersonID=gibbonRollGroup.gibbonPersonIDTutor3 AND tutor3.status='Full') 
								WHERE gibbonStudentEnrolment.gibbonPersonID=:gibbonPersonID AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID";
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        } catch (PDOException $e) {
                        }
                        if ($result->rowCount() == 1) {
                            $row = $result->fetch();
                            $notificationText = sprintf(__($guid, 'Someone has added a note ("%1$s") about your tutee, %2$s.'), $title, $name);
                            if ($row['tutor1gibbonPersonID'] != '') {
                                if ($row['tutor1gibbonPersonID'] != $_SESSION[$guid]['gibbonPersonID']) {
                                    setNotification($connection2, $guid, $row['tutor1gibbonPersonID'], $notificationText, 'Students', "/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=$gibbonPersonID&search=".$_GET['search']."&subpage=$subpage&category=".$_GET['category']);
                                }
                            }
                            if ($row['tutor2gibbonPersonID'] != '') {
                                if ($row['tutor2gibbonPersonID'] != $_SESSION[$guid]['gibbonPersonID']) {
                                    setNotification($connection2, $guid, $row['tutor2gibbonPersonID'], $notificationText, 'Students', "/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=$gibbonPersonID&search=".$_GET['search']."&subpage=$subpage&category=".$_GET['category']);
                                }
                            }
                            if ($row['tutor3gibbonPersonID'] != '') {
                                if ($row['tutor3gibbonPersonID'] != $_SESSION[$guid]['gibbonPersonID']) {
                                    setNotification($connection2, $guid, $row['tutor3gibbonPersonID'], $notificationText, 'Students', "/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=$gibbonPersonID&search=".$_GET['search']."&subpage=$subpage&category=".$_GET['category']);
                                }
                            }
                        }
                    }

                    $URL .= '&return=success0';
                    header("Location: {$URL}");
                }
            }
        }
    }
}
