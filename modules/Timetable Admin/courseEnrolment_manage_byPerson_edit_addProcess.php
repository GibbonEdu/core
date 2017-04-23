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

$gibbonPersonID = $_GET['gibbonPersonID'];
$gibbonSchoolYearID = $_GET['gibbonSchoolYearID'];
$type = $_GET['type'];
$allUsers = $_GET['allUsers'];
$search = $_GET['search'];

if ($gibbonSchoolYearID == '' or $gibbonPersonID == '') { echo 'Fatal error loading this page!';
} else {
    $URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/courseEnrolment_manage_byPerson_edit.php&type=$type&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonPersonID=$gibbonPersonID&allUsers=$allUsers&search=$search";

    if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/courseEnrolment_manage_byPerson_edit.php') == false) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
    } else {
        //Proceed!
        //Run through each of the selected participants.
        $update = true;
        $choices = $_POST['Members'];
        $role = $_POST['role'];

        if (count($choices) < 1 or $role == '') {
            $URL .= '&return=error1';
            header("Location: {$URL}");
        } else {
            foreach ($choices as $t) {
                //Check to see if student is already registered in this class
                try {
                    $data = array('gibbonPersonID' => $gibbonPersonID, 'gibbonCourseClassID' => $t);
                    $sql = 'SELECT * FROM gibbonCourseClassPerson WHERE gibbonPersonID=:gibbonPersonID AND gibbonCourseClassID=:gibbonCourseClassID';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $update = false;
                }
                //If student not in course, add them
                if ($result->rowCount() == 0) {
                    try {
                        $data = array('gibbonPersonID' => $gibbonPersonID, 'gibbonCourseClassID' => $t, 'role' => $role);
                        $sql = 'INSERT INTO gibbonCourseClassPerson SET gibbonPersonID=:gibbonPersonID, gibbonCourseClassID=:gibbonCourseClassID, role=:role';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $update = false;
                    }
                } else {
                    try {
                        $data = array('gibbonPersonID' => $gibbonPersonID, 'gibbonCourseClassID' => $t, 'role' => $role);
                        $sql = 'UPDATE gibbonCourseClassPerson SET role=:role WHERE gibbonPersonID=:gibbonPersonID AND gibbonCourseClassID=:gibbonCourseClassID';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $update = false;
                    }
                }
            }
            //Write to database
            if ($update == false) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
            } else {
                $URL .= '&return=success0';
                header("Location: {$URL}");
            }
        }
    }
}
