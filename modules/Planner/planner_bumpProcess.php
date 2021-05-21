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

$gibbonPlannerEntryID = $_GET['gibbonPlannerEntryID'] ?? '';
$viewBy = $_POST['viewBy'] ?? '';
$subView = $_POST['subView'] ?? '';
if ($viewBy != 'date' and $viewBy != 'class') {
    $viewBy = 'date';
}
$gibbonCourseClassID = $_POST['gibbonCourseClassID'] ?? '';
$date = $_POST['date'] ?? '';
$direction = $_POST['direction'] ?? '';
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/planner_bump.php&gibbonPlannerEntryID=$gibbonPlannerEntryID";
$URLBump = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address']).'/planner.php';

//Params to pass back (viewBy + date or classID)
$params = "&viewBy=$viewBy&gibbonCourseClassID=$gibbonCourseClassID&subView=$subView";

if (isActionAccessible($guid, $connection2, '/modules/Planner/planner_bump.php') == false) {
    $URL .= "&return=error0$params";
    header("Location: {$URL}");
} else {
    $highestAction = getHighestGroupedAction($guid, $_POST['address'], $connection2);
    if ($highestAction == false) {
        $URL .= "&return=error0$params";
        header("Location: {$URL}");
    } else {
        //Proceed!
        if (($direction != 'forward' and $direction != 'backward') or $gibbonPlannerEntryID == '' or $viewBy == 'date' or ($viewBy == 'class' and $gibbonCourseClassID == 'Y')) {
            $URL .= "&return=error1$params";
            header("Location: {$URL}");
        } else {
            try {
                if ($highestAction == 'Lesson Planner_viewEditAllClasses') {
                    $data = array('gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonPlannerEntryID' => $gibbonPlannerEntryID);
                    $sql = 'SELECT gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, date, timeStart, timeEnd FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonPlannerEntry.gibbonCourseClassID=:gibbonCourseClassID AND gibbonPlannerEntryID=:gibbonPlannerEntryID';
                } else {
                    $data = array('gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonPlannerEntryID' => $gibbonPlannerEntryID, 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                    $sql = "SELECT gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, role, date, timeStart, timeEnd FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND role='Teacher' AND gibbonPlannerEntry.gibbonCourseClassID=:gibbonCourseClassID AND gibbonPlannerEntryID=:gibbonPlannerEntryID";
                }
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $URL .= "&return=error2$params";
                header("Location: {$URL}");
                exit();
            }

            if ($result->rowCount() != 1) {
                $URL .= "&return=error2$params";
                header("Location: {$URL}");
            } else {
                $row = $result->fetch();
                $partialFail = false;

                if ($direction == 'forward') { //BUMP FORWARD
                    try {
                        $dataList = array('gibbonCourseClassID' => $gibbonCourseClassID, 'date' => $row['date'], 'timeStart' => $row['timeStart'], 'timeEnd' => $row['timeEnd']);
                        $sqlList = 'SELECT * FROM gibbonPlannerEntry WHERE gibbonPlannerEntry.gibbonCourseClassID=:gibbonCourseClassID AND (date>=:date OR (date=:date AND timeStart>=:timeStart)) ORDER BY date DESC, timeStart DESC';
                        $resultList = $connection2->prepare($sqlList);
                        $resultList->execute($dataList);
                    } catch (PDOException $e) {
                        $URL .= "&return=error2$params";
                        header("Location: {$URL}");
                        exit();
                    }
                    while ($rowList = $resultList->fetch()) {
                        //Look for next available slot
                        try {
                            $dataNext = array('gibbonCourseClassID' => $gibbonCourseClassID, 'date' => $rowList['date']);
                            $sqlNext = 'SELECT timeStart, timeEnd, date FROM gibbonTTDayRowClass JOIN gibbonTTColumnRow ON (gibbonTTDayRowClass.gibbonTTColumnRowID=gibbonTTColumnRow.gibbonTTColumnRowID) JOIN gibbonTTColumn ON (gibbonTTColumnRow.gibbonTTColumnID=gibbonTTColumn.gibbonTTColumnID) JOIN gibbonTTDay ON (gibbonTTDayRowClass.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) JOIN gibbonTTDayDate ON (gibbonTTDayDate.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) WHERE gibbonCourseClassID=:gibbonCourseClassID AND date>=:date ORDER BY date, timestart LIMIT 0, 10';
                            $resultNext = $connection2->prepare($sqlNext);
                            $resultNext->execute($dataNext);
                        } catch (PDOException $e) {
                            $partialFail = true;
                        }
                        while ($rowNext = $resultNext->fetch()) {
                            if (isSchoolOpen($guid, $row['date'], $connection2)) {
                                try {
                                    $dataPlanner = array('date' => $rowNext['date'], 'timeStart' => $rowNext['timeStart'], 'timeEnd' => $rowNext['timeEnd'], 'gibbonCourseClassID' => $gibbonCourseClassID);
                                    $sqlPlanner = 'SELECT * FROM gibbonPlannerEntry WHERE date=:date AND timeStart=:timeStart AND timeEnd=:timeEnd AND gibbonCourseClassID=:gibbonCourseClassID';
                                    $resultPlanner = $connection2->prepare($sqlPlanner);
                                    $resultPlanner->execute($dataPlanner);
                                } catch (PDOException $e) {
                                    $partialFail = true;
                                }
                                if ($resultPlanner->rowCount() == 0) {
                                    try {
                                        $dataNext = array('gibbonPlannerEntryID' => $rowList['gibbonPlannerEntryID'], 'date' => $rowNext['date'], 'timeStart' => $rowNext['timeStart'], 'timeEnd' => $rowNext['timeEnd']);
                                        $sqlNext = 'UPDATE gibbonPlannerEntry  set date=:date, timeStart=:timeStart, timeEnd=:timeEnd WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID';
                                        $resultNext = $connection2->prepare($sqlNext);
                                        $resultNext->execute($dataNext);
                                    } catch (PDOException $e) {
                                        $partialFail = true;
                                    }
                                    break;
                                }
                            }
                        }
                    }
                } else { //BUMP BACKWARD
                    try {
                        $dataList = array('gibbonCourseClassID' => $gibbonCourseClassID, 'date' => $row['date'], 'timeStart' => $row['timeStart'], 'timeEnd' => $row['timeEnd']);
                        $sqlList = 'SELECT * FROM gibbonPlannerEntry WHERE gibbonPlannerEntry.gibbonCourseClassID=:gibbonCourseClassID AND (date<=:date OR (date=:date AND timeStart<=:timeStart)) ORDER BY date, timeStart';
                        $resultList = $connection2->prepare($sqlList);
                        $resultList->execute($dataList);
                    } catch (PDOException $e) {
                        $URL .= "&return=error2$params";
                        header("Location: {$URL}");
                        exit();
                    }
                    while ($rowList = $resultList->fetch()) {
                        //Look for last available slot
                        try {
                            $dataNext = array('gibbonCourseClassID' => $gibbonCourseClassID, 'date' => $rowList['date']);
                            $sqlNext = 'SELECT timeStart, timeEnd, date FROM gibbonTTDayRowClass JOIN gibbonTTColumnRow ON (gibbonTTDayRowClass.gibbonTTColumnRowID=gibbonTTColumnRow.gibbonTTColumnRowID) JOIN gibbonTTColumn ON (gibbonTTColumnRow.gibbonTTColumnID=gibbonTTColumn.gibbonTTColumnID) JOIN gibbonTTDay ON (gibbonTTDayRowClass.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) JOIN gibbonTTDayDate ON (gibbonTTDayDate.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) WHERE gibbonCourseClassID=:gibbonCourseClassID AND date<=:date ORDER BY date DESC, timestart DESC LIMIT 0, 10';
                            $resultNext = $connection2->prepare($sqlNext);
                            $resultNext->execute($dataNext);
                        } catch (PDOException $e) {
                            $partialFail = true;
                        }
                        while ($rowNext = $resultNext->fetch()) {
                            if (isSchoolOpen($guid, $row['date'], $connection2)) {
                                try {
                                    $dataPlanner = array('date' => $rowNext['date'], 'timeStart' => $rowNext['timeStart'], 'timeEnd' => $rowNext['timeEnd'], 'gibbonCourseClassID' => $gibbonCourseClassID);
                                    $sqlPlanner = 'SELECT * FROM gibbonPlannerEntry WHERE date=:date AND timeStart=:timeStart AND timeEnd=:timeEnd AND gibbonCourseClassID=:gibbonCourseClassID';
                                    $resultPlanner = $connection2->prepare($sqlPlanner);
                                    $resultPlanner->execute($dataPlanner);
                                } catch (PDOException $e) {
                                    $partialFail = true;
                                }
                                if ($resultPlanner->rowCount() == 0) {
                                    try {
                                        $dataNext = array('gibbonPlannerEntryID' => $rowList['gibbonPlannerEntryID'], 'date' => $rowNext['date'], 'timeStart' => $rowNext['timeStart'], 'timeEnd' => $rowNext['timeEnd']);
                                        $sqlNext = 'UPDATE gibbonPlannerEntry  set date=:date, timeStart=:timeStart, timeEnd=:timeEnd WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID';
                                        $resultNext = $connection2->prepare($sqlNext);
                                        $resultNext->execute($dataNext);
                                    } catch (PDOException $e) {
                                        $partialFail = true;
                                    }
                                    break;
                                }
                            }
                        }
                    }
                }

                //Write to database
                if ($partialFail == true) {
                    $URL .= "&return=error5$params";
                    header("Location: {$URL}");
                } else {
                    $URL = $URLBump."&return=success1$params";
                    header("Location: {$URL}");
                }
            }
        }
    }
}
