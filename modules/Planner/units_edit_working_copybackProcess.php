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

$gibbonSchoolYearID = $_GET['gibbonSchoolYearID'];
$gibbonCourseID = $_GET['gibbonCourseID'];
$gibbonCourseClassID = $_GET['gibbonCourseClassID'];
$gibbonUnitID = $_GET['gibbonUnitID'];
$gibbonUnitBlockID = $_GET['gibbonUnitBlockID'];
$gibbonUnitClassBlockID = $_GET['gibbonUnitClassBlockID'];
$gibbonUnitClassID = $_GET['gibbonUnitClassID'];

$URL = $_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Planner/units_edit_working_copyback.php&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonCourseID=$gibbonCourseID&gibbonCourseClassID=$gibbonCourseClassID&gibbonUnitID=$gibbonUnitID&gibbonUnitBlockID=$gibbonUnitBlockID&gibbonUnitClassBlockID=$gibbonUnitClassBlockID&gibbonUnitClassID=$gibbonUnitClassID";

if (isActionAccessible($guid, $connection2, '/modules/Planner/units_edit_working_copyback.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    $highestAction = getHighestGroupedAction($guid, '/modules/Planner/units_edit_working_copyback.php', $connection2);
    if ($highestAction == false) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
    } else {
        //Proceed!
        //Validate Inputs
        if ($gibbonSchoolYearID == '' or $gibbonCourseID == '' or $gibbonUnitID == '' or $gibbonCourseClassID == '' or $gibbonUnitClassID == '') {
            $URL .= '&copyReturn=error3';
            header("Location: {$URL}");
        } else {
            $courseGateway = $container->get(CourseGateway::class);

            // Check access to specified course
            if ($highestAction == 'Unit Planner_all') {
                $result = $courseGateway->selectCourseDetailsByClass($gibbonCourseClassID);
            } elseif ($highestAction == 'Unit Planner_learningAreas') {
                $result = $courseGateway->selectCourseDetailsByClassAndPerson($gibbonCourseClassID, $gibbon->session->get('gibbonPersonID'));
            }

            if ($result->rowCount() != 1) {
                $URL .= '&copyReturn=error4';
                header("Location: {$URL}");
            } else {
                //Check existence of specified unit/class
                try {
                    $data = array('gibbonUnitID' => $gibbonUnitID, 'gibbonCourseID' => $gibbonCourseID, 'gibbonUnitBlockID' => $gibbonUnitBlockID, 'gibbonUnitClassBlockID' => $gibbonUnitClassBlockID);
                    $sql = 'SELECT gibbonUnitClassBlock.* FROM gibbonUnit JOIN gibbonCourse ON (gibbonUnit.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonUnitBlock ON (gibbonUnitBlock.gibbonUnitID=gibbonUnit.gibbonUnitID) JOIN gibbonUnitClassBlock ON (gibbonUnitClassBlock.gibbonUnitBlockID=gibbonUnitBlock.gibbonUnitBlockID) WHERE gibbonUnitClassBlockID=:gibbonUnitClassBlockID AND gibbonUnitBlock.gibbonUnitBlockID=:gibbonUnitBlockID AND gibbonUnit.gibbonUnitID=:gibbonUnitID AND gibbonUnit.gibbonCourseID=:gibbonCourseID';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                    exit();
                }

                if ($result->rowCount() != 1) {
                    $URL .= '&copyReturn=error4';
                    header("Location: {$URL}");
                } else {
                    $row = $result->fetch();
                    $partialFail = false;

                    try {
                        $data = array('title' => $row['title'], 'type' => $row['type'], 'length' => $row['length'], 'contents' => $row['contents'], 'teachersNotes' => $row['teachersNotes'], 'gibbonUnitBlockID' => $gibbonUnitBlockID, 'gibbonUnitID' => $gibbonUnitID);
                        $sql = 'UPDATE gibbonUnitBlock SET title=:title, type=:type, length=:length, contents=:contents, teachersNotes=:teachersNotes WHERE gibbonUnitBlockID=:gibbonUnitBlockID AND gibbonUnitID=:gibbonUnitID';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $partialFail = true;
                    }

                    $working = $_POST['working'];
                    if ($working == 'Y') {
                        try {
                            $data = array('title' => $row['title'], 'type' => $row['type'], 'length' => $row['length'], 'contents' => $row['contents'], 'teachersNotes' => $row['teachersNotes'], 'gibbonUnitBlockID' => $gibbonUnitBlockID);
                            $sql = 'UPDATE gibbonUnitClassBlock SET title=:title, type=:type, length=:length, contents=:contents, teachersNotes=:teachersNotes WHERE gibbonUnitBlockID=:gibbonUnitBlockID';
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        } catch (PDOException $e) {
                            $partialFail = true;
                        }
                    }

                    //RETURN
                    if ($partialFail == true) {
                        $URL .= '&copyReturn=error6';
                        header("Location: {$URL}");
                    } else {
                        $URL .= '&return=success0';
                        header("Location: {$URL}");
                    }
                }
            }
        }
    }
}
