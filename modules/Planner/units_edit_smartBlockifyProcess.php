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

$gibbonSchoolYearID = $_POST['gibbonSchoolYearID'];
$gibbonCourseClassID = $_POST['gibbonCourseClassID'];
$gibbonCourseID = $_POST['gibbonCourseID'];
$gibbonUnitID = $_POST['gibbonUnitID'];
$gibbonUnitClassID = $_POST['gibbonUnitClassID'];
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/units_edit_smartBlockify.php&gibbonUnitID=$gibbonUnitID&gibbonCourseID=$gibbonCourseID&gibbonCourseClassID=$gibbonCourseClassID&gibbonSchoolYearID=$gibbonSchoolYearID";
$URLCopy = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/units_edit.php&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonCourseID=$gibbonCourseID&gibbonUnitID=$gibbonUnitID";

if (isActionAccessible($guid, $connection2, '/modules/Planner/units_edit_smartBlockify.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    $highestAction = getHighestGroupedAction($guid, $_POST['address'], $connection2);
    if ($highestAction == false) {
        $URL .= "&return=error0$params";
        header("Location: {$URL}");
    } else {
        //Proceed!
        if ($gibbonSchoolYearID == '' or $gibbonCourseID == '' or $gibbonCourseClassID == '' or $gibbonUnitID == '' or $gibbonUnitClassID == '') {
            $URL .= '&return=error1';
            header("Location: {$URL}");
        } else {
            //Check access to specified course
            try {
                if ($highestAction == 'Unit Planner_all') {
                    $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonCourseID' => $gibbonCourseID);
                    $sql = 'SELECT * FROM gibbonCourse WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseID=:gibbonCourseID';
                } elseif ($highestAction == 'Unit Planner_learningAreas') {
                    $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonCourseID' => $gibbonCourseID, 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                    $sql = "SELECT gibbonCourseID, gibbonCourse.name, gibbonCourse.nameShort FROM gibbonCourse JOIN gibbonDepartment ON (gibbonCourse.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE gibbonDepartmentStaff.gibbonPersonID=:gibbonPersonID AND (role='Coordinator' OR role='Assistant Coordinator' OR role='Teacher (Curriculum)') AND gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseID=:gibbonCourseID ORDER BY gibbonCourse.nameShort";
                }
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
                exit();
            }
            if ($result->rowCount() != 1) {
                $URL .= '&return=error3';
                header("Location: {$URL}");
            } else {
                //Check existence of specified unit
                try {
                    $data = array('gibbonUnitID' => $gibbonUnitID, 'gibbonCourseID' => $gibbonCourseID);
                    $sql = 'SELECT * FROM gibbonUnit WHERE gibbonUnitID=:gibbonUnitID AND gibbonCourseID=:gibbonCourseID';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                    exit();
                }
                if ($result->rowCount() != 1) {
                    $URL .= '&return=error3';
                    header("Location: {$URL}");
                } else {
                    //Ready to let loose with the real logic
                    //GET ALL LESSONS IN UNIT, IN ORDER
                    try {
                        $data = array('gibbonUnitID' => $gibbonUnitID, 'gibbonCourseClassID' => $gibbonCourseClassID);
                        $sql = 'SELECT gibbonPlannerEntryID, name, description, teachersNotes, timeStart, timeEnd, date FROM gibbonPlannerEntry WHERE gibbonUnitID=:gibbonUnitID AND gibbonCourseClassID=:gibbonCourseClassID ORDER BY date';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $URL .= '&return=error2';
                        header("Location: {$URL}");
                        exit();
                    }

                    $sequenceNumber = 9999;
                    $partialFail = false;
                    while ($row = $result->fetch()) {
                        $blockFail = false;
                        ++$sequenceNumber;
                        $length = (strtotime($row['date'].' '.$row['timeEnd']) - strtotime($row['date'].' '.$row['timeStart'])) / 60;

                        //MAKE NEW BLOCK
                        try {
                            $dataBlock = array('gibbonUnitID' => $gibbonUnitID, 'title' => $row['name'], 'type' => '', 'length' => $length, 'contents' => $row['description'], 'teachersNotes' => $row['teachersNotes'], 'sequenceNumber' => $sequenceNumber, 'gibbonOutcomeIDList' => '');
                            $sqlBlock = 'INSERT INTO gibbonUnitBlock SET gibbonUnitID=:gibbonUnitID, title=:title, type=:type, length=:length, contents=:contents, teachersNotes=:teachersNotes, sequenceNumber=:sequenceNumber, gibbonOutcomeIDList=:gibbonOutcomeIDList';
                            $resultBlock = $connection2->prepare($sqlBlock);
                            $resultBlock->execute($dataBlock);
                        } catch (PDOException $e) {
                            $partialFail = true;
                            $blockFail = true;
                        }

                        if ($blockFail == false) {
                            //TURN MASTER BLOCK INTO A WORKING BLOCK, ATTACHING IT TO LESSON
                            $gibbonUnitBlockID = $connection2->lastInsertID();
                            $blockFail2 = false;
                            try {
                                $dataBlock2 = array('gibbonUnitClassID' => $gibbonUnitClassID, 'gibbonPlannerEntryID' => $row['gibbonPlannerEntryID'], 'gibbonUnitBlockID' => $gibbonUnitBlockID, 'title' => $row['name'], 'type' => '', 'length' => $length, 'contents' => $row['description'], 'teachersNotes' => $row['teachersNotes'], 'sequenceNumber' => 1, 'gibbonOutcomeIDList' => '');
                                $sqlBlock2 = 'INSERT INTO gibbonUnitClassBlock SET gibbonUnitClassID=:gibbonUnitClassID, gibbonPlannerEntryID=:gibbonPlannerEntryID, gibbonUnitBlockID=:gibbonUnitBlockID, title=:title, type=:type, length=:length, contents=:contents, teachersNotes=:teachersNotes, sequenceNumber=:sequenceNumber, gibbonOutcomeIDList=:gibbonOutcomeIDList';
                                $resultBlock2 = $connection2->prepare($sqlBlock2);
                                $resultBlock2->execute($dataBlock2);
                            } catch (PDOException $e) {
                                $partialFail = true;
                                $blockFail2 = true;
                            }

                            if ($blockFail2 == false) {
                                //REWRITE LESSON TO REMOVE description AND teachersNotes
                                try {
                                    $dataRewrite = array('gibbonPlannerEntryID' => $row['gibbonPlannerEntryID']);
                                    $sqlRewrite = "UPDATE gibbonPlannerEntry SET description='', teachersNotes='' WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID";
                                    $resultRewrite = $connection2->prepare($sqlRewrite);
                                    $resultRewrite->execute($dataRewrite);
                                } catch (PDOException $e) {
                                    $partialFail = true;
                                }
                            }
                        }
                    }

                    if ($partialFail == true) {
                        $URL .= '&copyReturn=error6';
                        header("Location: {$URL}");
                    } else {
                        $URLCopy = $URLCopy.'&copyReturn=success1';
                        header("Location: {$URLCopy}");
                    }
                }
            }
        }
    }
}
