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

use Gibbon\Domain\Timetable\CourseGateway;

include '../../gibbon.php';

$gibbonSchoolYearID = $_POST['gibbonSchoolYearID'];
$gibbonCourseID = $_POST['gibbonCourseID'];
$gibbonUnitID = $_POST['gibbonUnitID'];
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/units_delete.php&gibbonUnitID=$gibbonUnitID&gibbonCourseID=$gibbonCourseID&gibbonSchoolYearID=$gibbonSchoolYearID";
$URLDelete = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/units.php&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonCourseID=$gibbonCourseID";

if (isActionAccessible($guid, $connection2, '/modules/Planner/units_delete.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    $highestAction = getHighestGroupedAction($guid, $_POST['address'], $connection2);
    if ($highestAction == false) {
        $URL .= "&return=error0$params";
        header("Location: {$URL}");
    } else {
        //Proceed!
        if ($gibbonSchoolYearID == '' or $gibbonCourseID == '' or $gibbonUnitID == '') {
            $URL .= '&return=warning2';
            header("Location: {$URL}");
        } else {
            $courseGateway = $container->get(CourseGateway::class);

            // Check access to specified course
            if ($highestAction == 'Unit Planner_all') {
                $result = $courseGateway->selectCourseDetailsByCourse($gibbonCourseID);
            } elseif ($highestAction == 'Unit Planner_learningAreas') {
                $result = $courseGateway->selectCourseDetailsByCourseAndPerson($gibbonCourseID, $gibbon->session->get('gibbonPersonID'));
            }

            if ($result->rowCount() != 1) {
                $URL .= '&deleteReturn=error4';
                header("Location: {$URL}");
            } else {
                //Check existence of specified unit
                
                    $data = array('gibbonUnitID' => $gibbonUnitID, 'gibbonCourseID' => $gibbonCourseID);
                    $sql = 'SELECT * FROM gibbonUnit WHERE gibbonUnitID=:gibbonUnitID AND gibbonCourseID=:gibbonCourseID';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);

                if ($result->rowCount() != 1) {
                    $URL .= '&deleteReturn=error4';
                    header("Location: {$URL}");
                } else {
                    //Write to database
                    try {
                        $data = array('gibbonUnitID' => $gibbonUnitID);
                        $sql = 'DELETE FROM gibbonUnitClass WHERE gibbonUnitID=:gibbonUnitID';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $URL .= '&return=error2';
                        header("Location: {$URL}");
                        exit();
                    }

                    try {
                        $data = array('gibbonUnitID' => $gibbonUnitID);
                        $sql = 'DELETE FROM gibbonUnitBlock WHERE gibbonUnitID=:gibbonUnitID';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $URL .= '&return=error2';
                        header("Location: {$URL}");
                        exit();
                    }

                    try {
                        $data = array('gibbonUnitID' => $gibbonUnitID);
                        $sql = 'DELETE FROM gibbonUnitOutcome WHERE gibbonUnitID=:gibbonUnitID';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $URL .= '&return=error2';
                        header("Location: {$URL}");
                        exit();
                    }

                    try {
                        $data = array('gibbonUnitID' => $gibbonUnitID);
                        $sql = 'DELETE FROM gibbonUnit WHERE gibbonUnitID=:gibbonUnitID';
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
