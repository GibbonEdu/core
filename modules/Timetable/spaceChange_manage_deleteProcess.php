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
$gibbonTTSpaceChangeID = $_GET['gibbonTTSpaceChangeID'];
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address']).'/spaceChange_manage_delete.php&gibbonTTSpaceChangeID='.$gibbonTTSpaceChangeID.'&gibbonCourseClassID='.$gibbonCourseClassID;
$URLDelete = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address']).'/spaceChange_manage.php';

if (isActionAccessible($guid, $connection2, '/modules/Timetable/spaceChange_manage_delete.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_POST['address'], $connection2);
    if ($highestAction == false) {
        $URL .= "&return=error0$params";
        header("Location: {$URL}");
    } else {
        //Proceed!
        //Check if school year specified
        if ($gibbonTTSpaceChangeID == '' OR $gibbonCourseClassID == '') {
            $URL .= '&return=error1';
            header("Location: {$URL}");
        } else {
            //Check for access
            try {
                if ($highestAction == 'Manage Facility Changes_allClasses') {
                    $dataSelect = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonCourseClassID' => $gibbonCourseClassID);
                    $sqlSelect = 'SELECT gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourseClass JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID ORDER BY course, class';
                } else if ($highestAction == 'Manage Facility Changes_myDepartment') {
                    $dataSelect = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonSchoolYearID2' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID2' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonCourseClassID2' => $gibbonCourseClassID);
                    $sqlSelect = '(SELECT gibbonCourseClass.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourseClass JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID)
                    UNION
                    (SELECT gibbonCourseClass.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourseClass JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonDepartment ON (gibbonCourse.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID2 AND (gibbonDepartmentStaff.gibbonPersonID=:gibbonPersonID2 AND role=\'Coordinator\') AND gibbonCourseClassID=:gibbonCourseClassID2)';
                } else {
                    $dataSelect = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonCourseClassID' => $gibbonCourseClassID);
                    $sqlSelect = 'SELECT gibbonCourseClass.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourseClass JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID ORDER BY course, class';
                }
                $resultSelect = $connection2->prepare($sqlSelect);
                $resultSelect->execute($dataSelect);
            } catch (PDOException $e) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
                exit();
            }

            if ($resultSelect->rowCount() != 1) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
                exit();
            }
            else {
                try {
                    if ($highestAction == 'Manage Facility Changes_allClasses' OR $highestAction == 'Manage Facility Changes_myDepartment') {
                        $data = array('gibbonTTSpaceChangeID' => $gibbonTTSpaceChangeID);
                        $sql = 'SELECT gibbonTTSpaceChangeID, gibbonTTSpaceChange.date, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, spaceOld.name AS spaceOld, spaceNew.name AS spaceNew FROM gibbonTTSpaceChange JOIN gibbonTTDayRowClass ON (gibbonTTSpaceChange.gibbonTTDayRowClassID=gibbonTTDayRowClass.gibbonTTDayRowClassID) JOIN gibbonCourseClass ON (gibbonTTDayRowClass.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) LEFT JOIN gibbonSpace AS spaceOld ON (gibbonTTDayRowClass.gibbonSpaceID=spaceOld.gibbonSpaceID) LEFT JOIN gibbonSpace AS spaceNew ON (gibbonTTSpaceChange.gibbonSpaceID=spaceNew.gibbonSpaceID) WHERE gibbonTTSpaceChangeID=:gibbonTTSpaceChangeID ORDER BY date, course, class';
                    } else {
                        $data = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonTTSpaceChangeID' => $gibbonTTSpaceChangeID);
                        $sql = 'SELECT gibbonTTSpaceChangeID, gibbonTTSpaceChange.date, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, spaceOld.name AS spaceOld, spaceNew.name AS spaceNew FROM gibbonTTSpaceChange JOIN gibbonTTDayRowClass ON (gibbonTTSpaceChange.gibbonTTDayRowClassID=gibbonTTDayRowClass.gibbonTTDayRowClassID)  JOIN gibbonCourseClass ON (gibbonTTDayRowClass.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) LEFT JOIN gibbonSpace AS spaceOld ON (gibbonTTDayRowClass.gibbonSpaceID=spaceOld.gibbonSpaceID) LEFT JOIN gibbonSpace AS spaceNew ON (gibbonTTSpaceChange.gibbonSpaceID=spaceNew.gibbonSpaceID) WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND gibbonTTSpaceChangeID=:gibbonTTSpaceChangeID ORDER BY date, course, class';
                    }
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $URL .= '&return=error2a';
                    header("Location: {$URL}");
                    exit();
                }

                if ($result->rowCount() != 1) {
                    $URL .= '&return=error2b';
                    header("Location: {$URL}");
                } else {
                    //Write to database
                    try {
                        $data = array('gibbonTTSpaceChangeID' => $gibbonTTSpaceChangeID);
                        $sql = 'DELETE FROM gibbonTTSpaceChange WHERE gibbonTTSpaceChangeID=:gibbonTTSpaceChangeID';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $URL .= '&return=error2';
                        header("Location: {$URL}");
                        exit();
                    }

                    $URLDelete = $URLDelete.'&return=success0';
                    header("Location: {$URLDelete}");
                }
            }
        }
    }
}
