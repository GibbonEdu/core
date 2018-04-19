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

include '../../gibbon.php';

$type = $_POST['type'];
$gibbonPersonID = $_POST['gibbonPersonID'];
$gibbonSchoolYearID = $_POST['gibbonSchoolYearID'];
$action = $_POST['action'];
$allUsers = $_GET['allUsers'];
$search = isset($_GET['search'])? $_GET['search'] : '';

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/courseEnrolment_manage_byPerson_edit.php&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonPersonID=$gibbonPersonID&type=$type&allUsers=$allUsers&search=$search";

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/courseEnrolment_manage_byPerson_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else if ($gibbonPersonID == '' or $gibbonSchoolYearID == '' or $action == '') {
    $URL .= '&return=error1';
    header("Location: {$URL}"); 
} else {
    $classes = isset($_POST['gibbonCourseClassID'])? $_POST['gibbonCourseClassID'] : array();

    //Proceed!
    //Check if person specified
    if (count($classes) <= 0) {
        $URL .= '&return=error3';
        header("Location: {$URL}");
    } else {
        $partialFail = false;
        if ($action == 'Delete') {
            foreach ($classes as $gibbonCourseClassID) {
                try {
                    $data = array('gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonPersonID' => $gibbonPersonID);
                    $sql = 'DELETE FROM gibbonCourseClassPerson WHERE gibbonCourseClassID=:gibbonCourseClassID AND gibbonPersonID=:gibbonPersonID';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $partialFail == true;
                }
            }
        } else {
            foreach ($classes as $gibbonCourseClassID) {
                try {
                    $data = array('gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonPersonID' => $gibbonPersonID);
                    $sql = "UPDATE gibbonCourseClassPerson SET role=CONCAT(role, ' - Left') WHERE gibbonCourseClassID=:gibbonCourseClassID AND gibbonPersonID=:gibbonPersonID AND (role = 'Student' OR role = 'Teacher')";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $partialFail == true;
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
