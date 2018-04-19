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

$gibbonCourseID = $_GET['gibbonCourseID'];
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address']).'/course_manage_edit.php&gibbonCourseID='.$gibbonCourseID.'&gibbonSchoolYearID='.$_POST['gibbonSchoolYearID'];

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/course_manage_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    //Check if special day specified
    if ($gibbonCourseID == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        try {
            $data = array('gibbonCourseID' => $gibbonCourseID);
            $sql = 'SELECT * FROM gibbonCourse WHERE gibbonCourseID=:gibbonCourseID';
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
        } else {
            //Validate Inputs
            if ($_POST['gibbonDepartmentID'] != '') {
                $gibbonDepartmentID = $_POST['gibbonDepartmentID'];
            } else {
                $gibbonDepartmentID = null;
            }
            $name = $_POST['name'];
            $nameShort = $_POST['nameShort'];
            $orderBy = $_POST['orderBy'];
            $description = $_POST['description'];
            $map = $_POST['map'];
            $gibbonSchoolYearID = $_POST['gibbonSchoolYearID'];
            $gibbonYearGroupIDList = (isset($_POST['gibbonYearGroupIDList']))? implode(',', $_POST['gibbonYearGroupIDList']) : '';

            if ($name == '' or $nameShort == '' or $gibbonSchoolYearID == '' or $map == '') {
                $URL .= '&return=error3';
                header("Location: {$URL}");
            } else {
                //Check unique inputs for uniquness
                try {
                    $data = array('name' => $name, 'gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonCourseID' => $gibbonCourseID);
                    $sql = 'SELECT * FROM gibbonCourse WHERE (name=:name AND gibbonSchoolYearID=:gibbonSchoolYearID) AND NOT (gibbonCourseID=:gibbonCourseID)';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                    exit();
                }

                if ($result->rowCount() > 0) {
                    $URL .= '&return=error3';
                    header("Location: {$URL}");
                } else {
                    //Write to database
                    try {
                        $data = array('gibbonDepartmentID' => $gibbonDepartmentID, 'name' => $name, 'nameShort' => $nameShort, 'orderBy' => $orderBy, 'description' => $description, 'map' => $map, 'gibbonYearGroupIDList' => $gibbonYearGroupIDList, 'gibbonCourseID' => $gibbonCourseID);
                        $sql = 'UPDATE gibbonCourse SET gibbonDepartmentID=:gibbonDepartmentID, name=:name, nameShort=:nameShort, orderBy=:orderBy, description=:description, map=:map, gibbonYearGroupIDList=:gibbonYearGroupIDList WHERE gibbonCourseID=:gibbonCourseID';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $URL .= '&return=error2';
                        header("Location: {$URL}");
                        exit();
                    }

                    $URL .= '&return=success0';
                    header("Location: {$URL}");
                }
            }
        }
    }
}
