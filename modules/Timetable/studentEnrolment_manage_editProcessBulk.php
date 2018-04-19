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

@session_start();

$gibbonCourseClassID = $_POST['gibbonCourseClassID'];
$gibbonCourseID = $_POST['gibbonCourseID'];
$action = $_POST['action'];

if ($gibbonCourseClassID == '' or $gibbonCourseID == '' or $action == '') { echo 'Fatal error loading this page!';
} else {
    $URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/studentEnrolment_manage_edit.php&gibbonCourseID=$gibbonCourseID&gibbonCourseClassID=$gibbonCourseClassID";

    if (isActionAccessible($guid, $connection2, '/modules/Timetable/studentEnrolment_manage_edit.php') == false) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
    } else {
        //Check access to the course
        try {
            $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonCourseClassID' => $gibbonCourseClassID);
            $sql = "SELECT gibbonCourseClassID, gibbonCourseClass.name, gibbonCourseClass.nameShort, gibbonCourse.gibbonCourseID, gibbonCourse.name AS courseName, gibbonCourse.nameShort as courseNameShort, gibbonCourse.description AS courseDescription, gibbonCourse.gibbonSchoolYearID, gibbonSchoolYear.name as yearName, gibbonYearGroupIDList FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonDepartment ON (gibbonCourse.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) JOIN gibbonSchoolYear ON (gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE (role='Coordinator' OR role='Assistant Coordinator') AND gibbonPersonID=:gibbonPersonID AND gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClassID=:gibbonCourseClassID";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
        }
        if ($result->rowCount() != 1) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
        } else {
            $people = isset($_POST['gibbonPersonID']) ? $_POST['gibbonPersonID'] : array();

            //Proceed!
            //Check if person specified
            if (count($people) < 1) {
                $URL .= '&return=error3';
                header("Location: {$URL}");
            } else {
                $partialFail = false;
                if ($action == 'Mark as left') {
                    foreach ($people as $gibbonPersonID) {
                        try {
                            $data = array('gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonPersonID' => $gibbonPersonID);
                            $sql = "UPDATE gibbonCourseClassPerson SET role=CONCAT(role, ' - Left ') WHERE gibbonCourseClassID=:gibbonCourseClassID AND gibbonPersonID=:gibbonPersonID  AND (role = 'Student' OR role = 'Teacher')";
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
    }
}
