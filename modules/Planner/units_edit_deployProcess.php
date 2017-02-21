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

$gibbonSchoolYearID = $_GET['gibbonSchoolYearID'];
$gibbonCourseID = $_GET['gibbonCourseID'];
$gibbonCourseClassID = $_GET['gibbonCourseClassID'];
$gibbonUnitID = $_GET['gibbonUnitID'];
$gibbonUnitClassID = $_GET['gibbonUnitClassID'];
$orders = $_POST['order'];

//IF UNIT DOES NOT CONTAIN HYPHEN, IT IS A GIBBON UNIT
$gibbonUnitID = $_GET['gibbonUnitID'];
if (strpos($gibbonUnitID, '-') == false) {
    $hooked = false;
} else {
    $hooked = true;
    $gibbonHookIDToken = substr($gibbonUnitID, 11);
    $gibbonUnitIDToken = substr($gibbonUnitID, 0, 10);
}

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['address'])."/units_edit.php&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonCourseID=$gibbonCourseID&gibbonUnitID=$gibbonUnitID";

if (isActionAccessible($guid, $connection2, '/modules/Planner/units_edit_deploy.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    $highestAction = getHighestGroupedAction($guid, $_GET['address'], $connection2);
    if ($highestAction == false) {
        $URL .= "&return=error0$params";
        header("Location: {$URL}");
    } else {
        //Proceed!
        //Validate Inputs
        if ($gibbonSchoolYearID == '' or $gibbonCourseID == '' or $gibbonUnitID == '' or $gibbonUnitClassID == '' or $orders == '') {
            $URL .= '&return=error3';
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
                $URL .= '&return=error2a';
                header("Location: {$URL}");
                exit();
            }

            if ($result->rowCount() != 1) {
                $URL .= '&return=error4';
                header("Location: {$URL}");
            } else {
                //Check existence of specified unit
                if ($hooked == false) {
                    try {
                        $data = array('gibbonUnitID' => $gibbonUnitID, 'gibbonCourseID' => $gibbonCourseID);
                        $sql = 'SELECT gibbonCourse.nameShort AS courseName, gibbonUnit.* FROM gibbonUnit JOIN gibbonCourse ON (gibbonUnit.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonUnitID=:gibbonUnitID AND gibbonUnit.gibbonCourseID=:gibbonCourseID';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $URL .= '&return=error2b';
                        header("Location: {$URL}");
                        exit();
                    }
                } else {
                    try {
                        $dataHooks = array('gibbonHookID' => $gibbonHookIDToken);
                        $sqlHooks = "SELECT * FROM gibbonHook WHERE type='Unit' AND gibbonHookID=:gibbonHookID ORDER BY name";
                        $resultHooks = $connection2->prepare($sqlHooks);
                        $resultHooks->execute($dataHooks);
                    } catch (PDOException $e) {
                        $URL .= '&return=error2c';
                        header("Location: {$URL}");
                        exit();
                    }
                    if ($resultHooks->rowCount() == 1) {
                        $rowHooks = $resultHooks->fetch();
                        $hookOptions = unserialize($rowHooks['options']);
                        if ($hookOptions['unitTable'] != '' and $hookOptions['unitIDField'] != '' and $hookOptions['unitCourseIDField'] != '' and $hookOptions['unitNameField'] != '' and $hookOptions['unitDescriptionField'] != '' and $hookOptions['classLinkTable'] != '' and $hookOptions['classLinkJoinFieldUnit'] != '' and $hookOptions['classLinkJoinFieldClass'] != '' and $hookOptions['classLinkIDField'] != '') {
                            try {
                                $data = array('unitIDField' => $gibbonUnitIDToken);
                                $sql = 'SELECT '.$hookOptions['unitTable'].'.*, gibbonCourse.nameShort FROM '.$hookOptions['unitTable'].' JOIN gibbonCourse ON ('.$hookOptions['unitTable'].'.'.$hookOptions['unitCourseIDField'].'=gibbonCourse.gibbonCourseID) WHERE '.$hookOptions['unitIDField'].'=:unitIDField';
                                $result = $connection2->prepare($sql);
                                $result->execute($data);
                            } catch (PDOException $e) {
                                $URL .= '&return=error2d';
                                header("Location: {$URL}");
                                exit();
                            }
                        }
                    }
                }

                if ($result->rowCount() != 1) {
                    $URL .= '&return=error4';
                    header("Location: {$URL}");
                } else {
                    $row = $result->fetch();

                    $partialFail = false;

                    //CREATE LESSON PLANS
                    try {
                        if ($hooked == false) {
                            $sql = 'LOCK TABLES gibbonPlannerEntry WRITE, gibbonUnitClassBlock WRITE';
                        } else {
                            $sql = 'LOCK TABLES gibbonPlannerEntry WRITE, gibbonUnitClassBlock WRITE, '.$hookOptions['classSmartBlockTable'].' WRITE';
                        }
                        $result = $connection2->query($sql);
                    } catch (PDOException $e) {
                        $URL .= '&return=error2e';
                        header("Location: {$URL}");
                        exit();
                    }

                    //Get next autoincrement
                    try {
                        $sqlAI = "SHOW TABLE STATUS LIKE 'gibbonPlannerEntry'";
                        $resultAI = $connection2->query($sqlAI);
                    } catch (PDOException $e) {
                        $URL .= '&return=error2f';
                        header("Location: {$URL}");
                        exit();
                    }

                    $rowAI = $resultAI->fetch();
                    $AI = str_pad($rowAI['Auto_increment'], 14, '0', STR_PAD_LEFT);

                    $lessonCount = 0;
                    $sequenceNumber = 0;
                    $lessDescriptions = array();
                    foreach ($orders as $order) {
                        //It is a lesson, so add it
                        if (strpos($order, 'lessonHeader-') !== false) {
                            if ($lessonCount != 0) {
                                ++$AI;
                                $AI = str_pad($AI, 14, '0', STR_PAD_LEFT);
                            }
                            $summary = 'Part of the '.$row['name'].' unit.';
                            $lessonDescriptions[$AI][0] = $AI;
                            $lessonDescriptions[$AI][1] = '';
                            $teachersNotes = getSettingByScope($connection2, 'Planner', 'teachersNotesTemplate');
                            $viewableStudents = $_POST['viewableStudents'];
                            $viewableParents = $_POST['viewableParents'];

                            try {
                                if ($hooked == false) {
                                    $data = array('gibbonPlannerEntryID' => $AI, 'gibbonCourseClassID' => $gibbonCourseClassID, 'date' => $_POST["date$lessonCount"], 'timeStart' => $_POST["timeStart$lessonCount"], 'timeEnd' => $_POST["timeEnd$lessonCount"], 'gibbonUnitID' => $gibbonUnitID, 'name' => $row['name'].' '.($lessonCount + 1), 'summary' => $summary, 'teachersNotes' => $teachersNotes, 'viewableParents' => $viewableParents, 'viewableStudents' => $viewableStudents, 'gibbonPersonIDCreator' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonPersonIDLastEdit' => $_SESSION[$guid]['gibbonPersonID']);
                                    $sql = "INSERT INTO gibbonPlannerEntry SET gibbonPlannerEntryID=:gibbonPlannerEntryID, gibbonCourseClassID=:gibbonCourseClassID, date=:date, timeStart=:timeStart, timeEnd=:timeEnd, gibbonUnitID=:gibbonUnitID, name=:name, summary=:summary, description='', teachersNotes=:teachersNotes, homework='N', viewableParents=:viewableParents, viewableStudents=:viewableStudents, gibbonPersonIDCreator=:gibbonPersonIDCreator, gibbonPersonIDLastEdit=:gibbonPersonIDLastEdit";
                                } else {
                                    $data = array('gibbonPlannerEntryID' => $AI, 'gibbonCourseClassID' => $gibbonCourseClassID, 'date' => $_POST["date$lessonCount"], 'timeStart' => $_POST["timeStart$lessonCount"], 'timeEnd' => $_POST["timeEnd$lessonCount"], 'gibbonUnitID' => $gibbonUnitIDToken, 'gibbonHookID' => $gibbonHookIDToken, 'name' => $row['name'].' '.($lessonCount + 1), 'summary' => $summary, 'teachersNotes' => $teachersNotes, 'viewableParents' => $viewableParents, 'viewableStudents' => $viewableStudents, 'gibbonPersonIDCreator' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonPersonIDLastEdit' => $_SESSION[$guid]['gibbonPersonID']);
                                    $sql = "INSERT INTO gibbonPlannerEntry SET gibbonPlannerEntryID=:gibbonPlannerEntryID, gibbonCourseClassID=:gibbonCourseClassID, date=:date, timeStart=:timeStart, timeEnd=:timeEnd, gibbonUnitID=:gibbonUnitID, gibbonHookID=:gibbonHookID, name=:name, summary=:summary, description='', teachersNotes=:teachersNotes, homework='N', viewableParents=:viewableParents, viewableStudents=:viewableStudents, gibbonPersonIDCreator=:gibbonPersonIDCreator, gibbonPersonIDLastEdit=:gibbonPersonIDLastEdit";
                                }
                                $result = $connection2->prepare($sql);
                                $result->execute($data);
                            } catch (PDOException $e) {
                                $partialFail = true;
                            }
                            ++$lessonCount;
                        }
                        //It is a block, so add it to the last added lesson
                        else {
                            $titles = $_POST['title'.$order];
                            $lessonDescriptions[$AI][1] .= $titles.', ';
                            $types = $_POST['type'.$order];
                            $lengths = $_POST['length'.$order];
                            $contents = $_POST['contents'.$order];
                            $teachersNotes = $_POST['teachersNotes'.$order];
                            $gibbonUnitBlockID = $_POST['gibbonUnitBlockID'.$order];

                            //Deal with outcomes
                            $gibbonOutcomeIDList = '';
                            if (isset($_POST['outcomes'.$order])) {
                                if (is_array($_POST['outcomes'.$order])) {
                                    foreach ($_POST['outcomes'.$order] as $outcome) {
                                        $gibbonOutcomeIDList .= $outcome.',';
                                    }
                                }
                                $gibbonOutcomeIDList = substr($gibbonOutcomeIDList, 0, -1);
                            }

                            try {
                                if ($hooked == false) {
                                    $data = array('gibbonUnitClassID' => $gibbonUnitClassID, 'gibbonPlannerEntryID' => $AI, 'gibbonUnitBlockID' => $gibbonUnitBlockID, 'title' => $titles, 'type' => $types, 'length' => $lengths, 'contents' => $contents, 'teachersNotes' => $teachersNotes, 'sequenceNumber' => $sequenceNumber, 'gibbonOutcomeIDList' => $gibbonOutcomeIDList);
                                    $sql = "INSERT INTO gibbonUnitClassBlock SET gibbonUnitClassID=:gibbonUnitClassID, gibbonPlannerEntryID=:gibbonPlannerEntryID, gibbonUnitBlockID=:gibbonUnitBlockID, title=:title, type=:type, length=:length, contents=:contents, teachersNotes=:teachersNotes, sequenceNumber=:sequenceNumber, gibbonOutcomeIDList=:gibbonOutcomeIDList, complete='N'";
                                } else {
                                    $data = array('gibbonUnitClassID' => $gibbonUnitClassID, 'gibbonPlannerEntryID' => $AI, 'gibbonUnitBlockID' => $gibbonUnitBlockID, 'title' => $titles, 'type' => $types, 'length' => $lengths, 'contents' => $contents, 'teachersNotes' => $teachersNotes, 'sequenceNumber' => $sequenceNumber);
                                    $sql = 'INSERT INTO '.$hookOptions['classSmartBlockTable'].' SET '.$hookOptions['classSmartBlockJoinField'].'=:gibbonUnitClassID, '.$hookOptions['classSmartBlockPlannerJoin'].'=:gibbonPlannerEntryID, '.$hookOptions['classSmartBlockUnitBlockJoinField'].'=:gibbonUnitBlockID, '.$hookOptions['classSmartBlockTitleField'].'=:title, '.$hookOptions['classSmartBlockTypeField'].'=:type, '.$hookOptions['classSmartBlockLengthField'].'=:length, '.$hookOptions['classSmartBlockContentsField'].'=:contents, '.$hookOptions['classSmartBlockTeachersNotesField'].'=:teachersNotes, '.$hookOptions['classSmartBlockSequenceNumberField']."=:sequenceNumber, complete='N'";
                                }
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
                        $URL .= '&return=error6';
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
