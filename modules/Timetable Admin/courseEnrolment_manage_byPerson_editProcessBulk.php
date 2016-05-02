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

$type = $_POST['type'];
$gibbonPersonID = $_POST['gibbonPersonID'];
$gibbonSchoolYearID = $_POST['gibbonSchoolYearID'];
$action = $_POST['action'];
$allUsers = $_GET['allUsers'];
$search = '';
if (isset($_GET['search'])) {
    $search = $_GET['search'];
}

if ($gibbonPersonID == '' or $gibbonSchoolYearID == '' or $action == '') {
    echo 'Fatal error loading this page!';
} else {
    $URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/courseEnrolment_manage_byPerson_edit.php&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonPersonID=$gibbonPersonID&type=$type&allUsers=$allUsers&search=$search";

    if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/courseEnrolment_manage_byPerson_edit.php') == false) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
    } else {
        $classes = array();
        $count = 0;
        for ($i = 1; $i <= $_POST['count']; ++$i) {
            if (isset($_POST["check-$i"])) {
                if ($_POST["check-$i"] == 'on') {
                    $classes[$count][0] = $_POST["gibbonCourseClassID-$i"];
                    $classes[$count][1] = $_POST["role-$i"];
                    ++$count;
                }
            }
        }
        //Proceed!
        //Check if person specified
        if (count($classes) < 1) {
            $URL .= '&return=error3';
            header("Location: {$URL}");
        } else {
            $partialFail = false;
            if ($action == 'Delete') {
                for ($i = 0; $i < count($classes); ++$i) {
                    try {
                        $data = array('gibbonCourseClassID' => $classes[$i][0], 'gibbonPersonID' => $gibbonPersonID);
                        $sql = 'DELETE FROM gibbonCourseClassPerson WHERE gibbonCourseClassID=:gibbonCourseClassID AND gibbonPersonID=:gibbonPersonID';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $partialFail == true;
                    }
                }
            } else {
                for ($i = 0; $i < count($classes); ++$i) {
                    if ($classes[$i][1] == 'Student' or $classes[$i][1] == 'Teacher') {
                        try {
                            $data = array('role' => $classes[$i][1].' - Left', 'gibbonCourseClassID' => $classes[$i][0], 'gibbonPersonID' => $gibbonPersonID);
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
