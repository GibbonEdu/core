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
$gibbonUnitClassID = $_GET['gibbonUnitClassID'];
$orders = $_POST['order'];

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/units_edit_working.php&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonCourseID=$gibbonCourseID&gibbonUnitID=$gibbonUnitID&gibbonCourseClassID=$gibbonCourseClassID&gibbonUnitClassID=$gibbonUnitClassID";

if (isActionAccessible($guid, $connection2, '/modules/Planner/units_edit_working.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    $highestAction = getHighestGroupedAction($guid, $_POST['address'], $connection2);
    if ($highestAction == false) {
        $URL .= "&return=error0$params";
        header("Location: {$URL}");
    } else {
        //Proceed!
        //Validate Inputs
        if ($gibbonSchoolYearID == '' or $gibbonCourseID == '' or $gibbonUnitID == '' or $orders == '') {
            $URL .= '&return=error3';
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
                $URL .= '&return=error3';
                header("Location: {$URL}");
            } else {
                //Check existence of specified unit
                try {
                    $data = array('gibbonUnitID' => $gibbonUnitID, 'gibbonCourseID' => $gibbonCourseID);
                    $sql = 'SELECT gibbonCourse.nameShort AS courseName, gibbonUnit.* FROM gibbonUnit JOIN gibbonCourse ON (gibbonUnit.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonUnitID=:gibbonUnitID AND gibbonUnit.gibbonCourseID=:gibbonCourseID';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $URL .= '&deployReturn=error2';
                    header("Location: {$URL}");
                    exit();
                }

                if ($result->rowCount() != 1) {
                    $URL .= '&return=error3';
                    header("Location: {$URL}");
                } else {
                    $row = $result->fetch();

                    //Remove all blocks
                    try {
                        $data = array('gibbonUnitClassID' => $gibbonUnitClassID);
                        $sql = 'DELETE FROM gibbonUnitClassBlock WHERE gibbonUnitClassID=:gibbonUnitClassID';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $URL .= '&return=error2';
                        header("Location: {$URL}");
                        exit();
                    }

                    $partialFail = false;

                    $lessonCount = 0;
                    $lessonDescriptions = array();
                    $sequenceNumber = 0;
                    foreach ($orders as $order) {
                        //It is a lesson, get gibbonPlannerID
                        if (strpos($order, 'lessonHeader-') !== false) {
                            $AI = $_POST["gibbonPlannerEntryID$lessonCount"];
                            $lessonDescriptions[$_POST['gibbonPlannerEntryID'.$lessonCount]][0] = $_POST['gibbonPlannerEntryID'.$lessonCount];
                            $lessonDescriptions[$_POST['gibbonPlannerEntryID'.$lessonCount]][1] = '';
                            ++$lessonCount;
                        }
                        //It is a block, so add it to the last added lesson
                        else {
                            $titles = $_POST['title'.$order];
                            $lessonDescriptions[$_POST['gibbonPlannerEntryID'.($lessonCount - 1)]][1] .= $_POST['title'.$order].', ';
                            $types = $_POST['type'.$order];
                            $lengths = $_POST['length'.$order];
                            $completes = null;
                            if (isset($_POST['complete'.$order])) {
                                $completes = $_POST['complete'.$order];
                            }
                            if ($completes == 'on') {
                                $completes = 'Y';
                            } else {
                                $completes = 'N';
                            }
                            $contents = $_POST['contents'.$order];
                            $teachersNotes = $_POST['teachersNotes'.$order];
                            $gibbonUnitBlockID = $_POST['gibbonUnitBlockID'.$order];

                            try {
                                $data = array('gibbonUnitClassID' => $gibbonUnitClassID, 'gibbonPlannerEntryID' => $AI, 'gibbonUnitBlockID' => $gibbonUnitBlockID, 'title' => $titles, 'type' => $types, 'length' => $lengths, 'complete' => $completes, 'contents' => $contents, 'teachersNotes' => $teachersNotes, 'sequenceNumber' => $sequenceNumber);
                                $sql = 'INSERT INTO gibbonUnitClassBlock SET gibbonUnitClassID=:gibbonUnitClassID, gibbonPlannerEntryID=:gibbonPlannerEntryID, gibbonUnitBlockID=:gibbonUnitBlockID, title=:title, type=:type, length=:length, complete=:complete, contents=:contents, teachersNotes=:teachersNotes, sequenceNumber=:sequenceNumber';
                                $result = $connection2->prepare($sql);
                                $result->execute($data);
                            } catch (PDOException $e) {
                                $partialFail = true;
                            }
                            ++$sequenceNumber;
                        }
                    }

                    //Update lesson description
                    foreach ($lessonDescriptions as $lessonDescription) {
                        $lessonDescription[1] = substr($lessonDescription[1], 0, -2);
                        if (strlen($lessonDescription[1]) > 75) {
                            $lessonDescription[1] = substr($lessonDescription[1], 0, 72).'...';
                        }
                        try {
                            $data = array('summary' => $lessonDescription[1], 'gibbonPlannerEntryID' => $lessonDescription[0]);
                            $sql = 'UPDATE gibbonPlannerEntry SET summary=:summary WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID';
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        } catch (PDOException $e) {
                            $partialFail = true;
                        }
                    }

                    //RETURN
                    if ($partialFail == true) {
                        $URL .= '&updateReturn=error6';
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
