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

$gibbonCourseClassID = $_POST['gibbonCourseClassID'];
$gibbonCourseID = $_POST['gibbonCourseID'];
$gibbonSchoolYearID = $_POST['gibbonSchoolYearID'];
$action = $_POST['action'];

if ($gibbonCourseClassID == '' or $gibbonCourseID == '' or $gibbonSchoolYearID == '' or $action == '') { echo 'Fatal error loading this page!';
} else {
    $URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/courseEnrolment_manage_class_edit.php&gibbonCourseID=$gibbonCourseID&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonCourseClassID=$gibbonCourseClassID";

    if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/courseEnrolment_manage_class_edit.php') == false) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
    } else {
        $people = array();
        $peopleCount = 0;
        for ($i = 0; $i < $_POST['count']; $i++) {
            if (isset($_POST["check-$i"])) {
                if ($_POST["check-$i"] == 'on') {
                    $people[$peopleCount][0] = $_POST["gibbonPersonID-$i"];
                    $people[$peopleCount][1] = $_POST["role-$i"];
                    $peopleCount ++;
                }
            }
        }

        //Proceed!
        //Check if person specified
        if (count($people) < 1) {
            $URL .= '&return=error3';
            header("Location: {$URL}");
        } else {
            $partialFail = false;
            if ($action == 'Delete') {
                for ($i = 0; $i < count($people); ++$i) {
                    try {
                        $data = array('gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonPersonID' => $people[$i][0]);
                        $sql = 'DELETE FROM gibbonCourseClassPerson WHERE gibbonCourseClassID=:gibbonCourseClassID AND gibbonPersonID=:gibbonPersonID';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $partialFail == true;
                    }
                }
            }
            else if ($action == 'Copy to class') {
                $gibbonCourseClassIDCopyTo = (isset($_POST['gibbonCourseClassIDCopyTo']))? $_POST['gibbonCourseClassIDCopyTo'] : NULL;
                if (!empty($gibbonCourseClassIDCopyTo)) {

                    for ($i = 0; $i < count($people); ++$i) {

                        // Check for duplicates
                        try {
                            $dataCheck = array('gibbonCourseClassIDCopyTo' => $gibbonCourseClassIDCopyTo, 'gibbonPersonID' => $people[$i][0]);
                            $sqlCheck = 'SELECT gibbonPersonID FROM gibbonCourseClassPerson WHERE gibbonCourseClassID=:gibbonCourseClassIDCopyTo AND gibbonPersonID=:gibbonPersonID';
                            $resultCheck = $connection2->prepare($sqlCheck);
                            $resultCheck->execute($dataCheck);
                        } catch (PDOException $e) {
                            $partialFail == true;
                        }

                        // Insert new course participants
                        if ($resultCheck->rowCount() == 0) {
                            try {
                                $data = array('gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonPersonID' => $people[$i][0], 'gibbonCourseClassIDCopyTo' => $gibbonCourseClassIDCopyTo);
                                $sql = 'INSERT INTO gibbonCourseClassPerson (gibbonCourseClassID, gibbonPersonID, role, reportable) SELECT :gibbonCourseClassIDCopyTo, gibbonPersonID, role, reportable FROM gibbonCourseClassPerson WHERE gibbonCourseClassID=:gibbonCourseClassID AND gibbonPersonID=:gibbonPersonID';
                                $result = $connection2->prepare($sql);
                                $result->execute($data);
                            } catch (PDOException $e) {
                                $partialFail == true;
                            }
                        }


                    }
                } else {
                    $URL .= '&return=error3';
                    header("Location: {$URL}");
                }
            } else if ($action == 'Mark as left') {
                for ($i = 0; $i < count($people); ++$i) {
                    if ($people[$i][1] == 'Student' or $people[$i][1] == 'Teacher') {
                        try {
                            $data = array('role' => $people[$i][1].' - Left', 'gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonPersonID' => $people[$i][0]);
                            $sql = 'UPDATE gibbonCourseClassPerson SET role=:role WHERE gibbonCourseClassID=:gibbonCourseClassID AND gibbonPersonID=:gibbonPersonID';
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        } catch (PDOException $e) {
                            $partialFail == true;
                        }
                    } else {
                        $partialFail = true;
                    }
                }
            }

            if ($partialFail == true) {
                $URL .= '&return=warning1';
                header("Location: {$URL}");
            } else {
                $URL .= '&return=success0';
                header("Location: {$URL}");
            }
        }
    }
}
