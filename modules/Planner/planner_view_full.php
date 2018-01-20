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

@session_start();

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';
include './modules/Attendance/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Planner/planner_view_full.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'Your request failed because you do not have access to this action.');
    echo '</div>';
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        echo "<div class='error'>";
        echo __($guid, 'The highest grouped action cannot be determined.');
        echo '</div>';
    } else {
        $viewBy = null;
        if (isset($_GET['viewBy'])) {
            $viewBy = $_GET['viewBy'];
        }
        $subView = null;
        if (isset($_GET['subView'])) {
            $subView = $_GET['subView'];
        }
        if ($viewBy != 'date' and $viewBy != 'class') {
            $viewBy = 'date';
        }
        $gibbonCourseClassID = null;
        $date = null;
        $dateStamp = null;
        if ($viewBy == 'date') {
            $date = $_GET['date'];
            if (isset($_GET['dateHuman'])) {
                $date = dateConvert($guid, $_GET['dateHuman']);
            }
            if ($date == '') {
                $date = date('Y-m-d');
            }
            list($dateYear, $dateMonth, $dateDay) = explode('-', $date);
            $dateStamp = mktime(0, 0, 0, $dateMonth, $dateDay, $dateYear);
        } elseif ($viewBy == 'class') {
            $class = null;
            if (isset($_GET['class'])) {
                $class = $_GET['class'];
            }
            $gibbonCourseClassID = $_GET['gibbonCourseClassID'];
        }
        $gibbonPersonID = null;

        //Proceed!
        //Get class variable
        $gibbonPlannerEntryID = $_GET['gibbonPlannerEntryID'];
        if ($gibbonPlannerEntryID == '') {
            echo "<div class='warning'>";
            echo __($guid, 'The selected record does not exist, or you do not have access to it.');
            echo '</div>';
        }
        //Check existence of and access to this class.
        else {
            $data = array();
            $gibbonPersonID = null;
            if (isset($_GET['search'])) {
                $gibbonPersonID = $_GET['search'];
            }
            if ($highestAction == 'Lesson Planner_viewMyChildrensClasses') {
                if ($gibbonPersonID == '') {
                    echo "<div class='warning'>";
                    echo __($guid, 'Your request failed because some required value were not unique.');
                    echo '</div>';
                } else {
                    try {
                        $dataChild = array('gibbonPersonID' => $gibbonPersonID, 'gibbonPersonID2' => $_SESSION[$guid]['gibbonPersonID']);
                        $sqlChild = "SELECT * FROM gibbonFamilyChild JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonFamilyChild.gibbonPersonID=:gibbonPersonID AND gibbonFamilyAdult.gibbonPersonID=:gibbonPersonID2 AND childDataAccess='Y'";
                        $resultChild = $connection2->prepare($sqlChild);
                        $resultChild->execute($dataChild);
                    } catch (PDOException $e) {
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }

                    if ($resultChild->rowCount() < 1) {
                        echo "<div class='error'>";
                        echo __($guid, 'The selected record does not exist, or you do not have access to it.');
                        echo '</div>';
                    } else {
                        $data = array('gibbonPersonID' => $gibbonPersonID, 'gibbonPlannerEntryID' => $gibbonPlannerEntryID, 'date' => $date, 'gibbonPersonID2' => $gibbonPersonID, 'gibbonPlannerEntryID2' => $gibbonPlannerEntryID);
                        $sql = "(SELECT gibbonPlannerEntry.gibbonPlannerEntryID, gibbonCourseClass.gibbonCourseClassID, gibbonUnitID, gibbonHookID, gibbonPlannerEntry.gibbonCourseClassID, gibbonPlannerEntry.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, date, timeStart, timeEnd, summary, gibbonPlannerEntry.description, teachersNotes, homework, homeworkDueDateTime, homeworkDetails, viewableStudents, viewableParents, role, homeworkSubmission, homeworkSubmissionDateOpen, homeworkSubmissionDrafts, homeworkSubmissionType, homeworkSubmissionRequired, gibbonCourseClass.attendance FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left' AND gibbonPlannerEntry.gibbonPlannerEntryID=:gibbonPlannerEntryID) UNION (SELECT gibbonPlannerEntry.gibbonPlannerEntryID, gibbonCourseClass.gibbonCourseClassID, gibbonUnitID, gibbonHookID, gibbonPlannerEntry.gibbonCourseClassID, gibbonPlannerEntry.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, date, timeStart, timeEnd, summary, gibbonPlannerEntry.description, teachersNotes, homework, homeworkDueDateTime, homeworkDetails, viewableStudents, viewableParents, role, homeworkSubmission, homeworkSubmissionDateOpen, homeworkSubmissionDrafts, homeworkSubmissionType, homeworkSubmissionRequired, gibbonCourseClass.attendance FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonPlannerEntryGuest ON (gibbonPlannerEntryGuest.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE date=:date AND gibbonPlannerEntryGuest.gibbonPersonID=:gibbonPersonID2 AND gibbonPlannerEntry.gibbonPlannerEntryID=:gibbonPlannerEntryID2) ORDER BY date, timeStart";
                    }
                }
            } elseif ($highestAction == 'Lesson Planner_viewMyClasses') {
                $data = array('date' => $date, 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonPlannerEntryID' => $gibbonPlannerEntryID);
                $sql = "(SELECT gibbonPlannerEntry.gibbonPlannerEntryID, gibbonCourseClass.gibbonCourseClassID, gibbonUnitID, gibbonHookID, gibbonPlannerEntry.gibbonCourseClassID, gibbonPlannerEntry.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, date, timeStart, timeEnd, summary, gibbonPlannerEntry.description, teachersNotes, homework, homeworkDueDateTime, homeworkDetails, viewableStudents, viewableParents, role, homeworkSubmission, homeworkSubmissionDateOpen, homeworkSubmissionDrafts, homeworkSubmissionType, homeworkSubmissionRequired, gibbonCourseClass.attendance FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left' AND gibbonPlannerEntry.gibbonPlannerEntryID=$gibbonPlannerEntryID) UNION (SELECT gibbonPlannerEntry.gibbonPlannerEntryID, gibbonCourseClass.gibbonCourseClassID, gibbonUnitID, gibbonHookID, gibbonPlannerEntry.gibbonCourseClassID, gibbonPlannerEntry.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, date, timeStart, timeEnd, summary, gibbonPlannerEntry.description, teachersNotes, homework, homeworkDueDateTime, homeworkDetails, viewableStudents, viewableParents, role, homeworkSubmission, homeworkSubmissionDateOpen, homeworkSubmissionDrafts, homeworkSubmissionType, homeworkSubmissionRequired, gibbonCourseClass.attendance FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonPlannerEntryGuest ON (gibbonPlannerEntryGuest.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE date=:date AND gibbonPlannerEntryGuest.gibbonPersonID=".$_SESSION[$guid]['gibbonPersonID'].' AND gibbonPlannerEntry.gibbonPlannerEntryID=:gibbonPlannerEntryID) ORDER BY date, timeStart';
            } elseif ($highestAction == 'Lesson Planner_viewEditAllClasses' or $highestAction == 'Lesson Planner_viewAllEditMyClasses') {
                $sql = "SELECT gibbonCourse.gibbonCourseID, gibbonPlannerEntry.gibbonPlannerEntryID, gibbonCourseClass.gibbonCourseClassID, gibbonUnitID, gibbonHookID, gibbonPlannerEntry.gibbonCourseClassID, gibbonPlannerEntry.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, date, timeStart, timeEnd, summary, gibbonPlannerEntry.description, teachersNotes, homework, homeworkDueDateTime, homeworkDetails, viewableStudents, viewableParents, 'Teacher' AS role, homeworkSubmission, homeworkSubmissionDateOpen, homeworkSubmissionDrafts, homeworkSubmissionType, homeworkSubmissionRequired, gibbonDepartmentID, gibbonCourseClass.attendance FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonPlannerEntry.gibbonPlannerEntryID=$gibbonPlannerEntryID ORDER BY date, timeStart";
                $teacher = false;
                try {
                    $dataTeacher = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonPlannerEntryID' => $gibbonPlannerEntryID, 'gibbonPersonID2' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonPlannerEntryID2' => $gibbonPlannerEntryID, 'date2' => $date);
                    $sqlTeacher = "(SELECT gibbonPlannerEntry.gibbonPlannerEntryID, gibbonCourseClass.gibbonCourseClassID, gibbonUnitID, gibbonHookID, gibbonPlannerEntry.gibbonCourseClassID, gibbonPlannerEntry.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, date, timeStart, timeEnd, summary, gibbonPlannerEntry.description, teachersNotes, homework, homeworkDueDateTime, homeworkDetails, viewableStudents, viewableParents, role, homeworkSubmission, homeworkSubmissionDateOpen, homeworkSubmissionDrafts, homeworkSubmissionType, homeworkSubmissionRequired
						FROM gibbonPlannerEntry
						JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
						JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID)
						JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID)
						WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID
							AND role='Teacher'
							AND gibbonPlannerEntry.gibbonPlannerEntryID=:gibbonPlannerEntryID)
						UNION
						(SELECT gibbonPlannerEntry.gibbonPlannerEntryID, gibbonCourseClass.gibbonCourseClassID, gibbonUnitID, gibbonHookID, gibbonPlannerEntry.gibbonCourseClassID, gibbonPlannerEntry.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, date, timeStart, timeEnd, summary, gibbonPlannerEntry.description, teachersNotes, homework, homeworkDueDateTime, homeworkDetails, viewableStudents, viewableParents, role, homeworkSubmission, homeworkSubmissionDateOpen, homeworkSubmissionDrafts, homeworkSubmissionType, homeworkSubmissionRequired
						FROM gibbonPlannerEntry
						JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
						JOIN gibbonPlannerEntryGuest ON (gibbonPlannerEntryGuest.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID)
						JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID)
						WHERE date=:date2 AND gibbonPlannerEntryGuest.gibbonPersonID=:gibbonPersonID2 AND gibbonPlannerEntry.gibbonPlannerEntryID=:gibbonPlannerEntryID2)
						ORDER BY date, timeStart";
                    $resultTeacher = $connection2->prepare($sqlTeacher);
                    $resultTeacher->execute($dataTeacher);
                } catch (PDOException $e) {
                }
                if ($resultTeacher->rowCount() > 0) {
                    $teacher = true;
                }
            }

            if (isset($sql)) {
                try {
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }
                if ($result->rowCount() != 1) {
                    echo "<div class='warning'>";
                    echo __($guid, 'The selected record does not exist, or you do not have access to it.');
                    echo '</div>';
                } else {
                    $row = $result->fetch();
                    $gibbonDepartmentID = null;
                    if (isset($row['gibbonDepartmentID'])) {
                        $gibbonDepartmentID = $row['gibbonDepartmentID'];
                    }

                    //CHECK IF UNIT IS GIBBON OR HOOKED
                    if ($row['gibbonHookID'] == null) {
                        $hooked = false;
                        $gibbonUnitID = $row['gibbonUnitID'];

                        //Get gibbonUnitClassID
                        try {
                            $dataUnitClass = array('gibbonCourseClassID' => $row['gibbonCourseClassID'], 'gibbonUnitID' => $gibbonUnitID);
                            $sqlUnitClass = 'SELECT gibbonUnitClassID FROM gibbonUnitClass WHERE gibbonCourseClassID=:gibbonCourseClassID AND gibbonUnitID=:gibbonUnitID';
                            $resultUnitClass = $connection2->prepare($sqlUnitClass);
                            $resultUnitClass->execute($dataUnitClass);
                        } catch (PDOException $e) {
                        }
                        if ($resultUnitClass->rowCount() == 1) {
                            $rowUnitClass = $resultUnitClass->fetch();
                            $gibbonUnitClassID = $rowUnitClass['gibbonUnitClassID'];
                        }
                    } else {
                        $hooked = true;
                        $gibbonUnitIDToken = $row['gibbonUnitID'];
                        $gibbonHookIDToken = $row['gibbonHookID'];

                        try {
                            $dataHooks = array('gibbonHookID' => $gibbonHookIDToken);
                            $sqlHooks = "SELECT * FROM gibbonHook WHERE type='Unit' AND gibbonHookID=:gibbonHookID ORDER BY name";
                            $resultHooks = $connection2->prepare($sqlHooks);
                            $resultHooks->execute($dataHooks);
                        } catch (PDOException $e) {
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
                                }
                            }
                        }

                        //Get gibbonUnitClassID
                        try {
                            $dataUnitClass = array('gibbonCourseClassID' => $row['gibbonCourseClassID'], 'gibbonUnitID' => $gibbonUnitIDToken);
                            $sqlUnitClass = 'SELECT '.$hookOptions['classLinkIDField'].' FROM '.$hookOptions['classLinkTable'].' WHERE '.$hookOptions['classLinkJoinFieldClass'].'=:gibbonCourseClassID AND '.$hookOptions['classLinkJoinFieldUnit'].'=:gibbonUnitID';
                            $resultUnitClass = $connection2->prepare($sqlUnitClass);
                            $resultUnitClass->execute($dataUnitClass);
                        } catch (PDOException $e) {
                            echo $e->getMessage();
                        }
                        if ($resultUnitClass->rowCount() == 1) {
                            $rowUnitClass = $resultUnitClass->fetch();
                            $gibbonUnitClassID = $rowUnitClass[$hookOptions['classLinkIDField']];
                        }
                    }

                    $extra = '';
                    if ($viewBy == 'class') {
                        $extra = $row['course'].'.'.$row['class'];
                    } else {
                        $extra = dateConvertBack($guid, $date);
                    }

                    $params = "&gibbonPlannerEntryID=$gibbonPlannerEntryID";
                    if ($date != '') {
                        $params = $params.'&date='.$_GET['date'];
                    }
                    if ($viewBy != '') {
                        $params = $params.'&viewBy='.@$_GET['viewBy'];
                    }
                    if ($gibbonCourseClassID != '') {
                        $params = $params.'&gibbonCourseClassID='.$_GET['gibbonCourseClassID'];
                    }
                    $params = $params."&subView=$subView";

                    echo "<div class='trail'>";
                    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/planner.php$params'>".__($guid, 'Planner')." $extra</a> > </div><div class='trailEnd'>".__($guid, 'View Lesson Plan').'</div>';
                    echo '</div>';

                    $returns = array();
                    $returns['error6'] = __($guid, 'An error occured with your submission, most likely because a submitted file was too large.');
                    if (isset($_GET['return'])) {
                        returnProcess($guid, $_GET['return'], null, $returns);
                    }

                    if ($gibbonCourseClassID == '') {
                        $gibbonCourseClassID = $row['gibbonCourseClassID'];
                    }
                    if (($row['role'] == 'Student' and $row['viewableStudents'] == 'N') and ($highestAction == 'Lesson Planner_viewMyChildrensClasses' and $row['viewableParents'] == 'N')) {
                        echo "<div class='warning'>";
                        echo __($guid, 'The selected record does not exist, or you do not have access to it.');
                        echo '</div>';
                    } else {
                        echo "<div style='height:50px'>";
                        echo '<h2>';
                        if (strlen($row['name']) <= 34) {
                            echo $row['name'].'<br/>';
                        } else {
                            echo substr($row['name'], 0, 34).'...<br/>';
                        }
                        $unit = getUnit($connection2, $row['gibbonUnitID'], $row['gibbonHookID'], $row['gibbonCourseClassID']);
                        if (isset($unit[0])) {
                            if ($unit[0] != '') {
                                if ($unit[1] != '') {
                                    echo "<div style='font-weight: normal; font-style: italic; font-size: 60%; margin-top: 0px'>$unit[1] ".__($guid, 'Unit:').' '.$unit[0].'</div>';
                                    $unitType = $unit[1];
                                } else {
                                    echo "<div style='font-weight: normal; font-style: italic; font-size: 60%; margin-top: 0px'>".__($guid, 'Unit:').' '.$unit[0].'</div>';
                                }
                            }
                        }
                        echo '</h2>';
                        echo "<div style='float: right; width: 35%; padding-right: 3px; margin-top: -52px'>";
                        if (strstr($row['role'], 'Guest') == false) {
                            //Links to previous and next lessons
                                    echo "<p style='text-align: right; margin-top: 10px'>";
                            echo "<span style='font-size: 85%'>".__($guid, 'For this class:').'</span><br/>';
                            try {
                                if ($row['role'] == 'Teacher') {
                                    $dataPrevious = array('gibbonCourseClassID' => $row['gibbonCourseClassID'], 'date1' => $row['date'], 'date2' => $row['date'], 'timeStart' => $row['timeStart']);
                                    $sqlPrevious = "SELECT gibbonPlannerEntry.gibbonCourseClassID, gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, timeStart, timeEnd, viewableStudents, viewableParents, homework, 'Teacher' AS role FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonPlannerEntry.gibbonCourseClassID=:gibbonCourseClassID AND (date<:date1 OR (date=:date2 AND timeStart<:timeStart)) ORDER BY date DESC, timeStart DESC";
                                } else {
                                    if ($highestAction == 'Lesson Planner_viewMyChildrensClasses') {
                                        $dataPrevious = array('gibbonCourseClassID' => $row['gibbonCourseClassID'], 'gibbonPersonID' => $gibbonPersonID, 'date1' => $row['date'], 'date2' => $row['date'], 'timeStart' => $row['timeStart']);
                                        $sqlPrevious = "SELECT gibbonPlannerEntry.gibbonCourseClassID, gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, timeStart, timeEnd, viewableStudents, viewableParents, homework, role FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonPlannerEntry.gibbonCourseClassID=:gibbonCourseClassID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left' AND (date<:date1 OR (date=:date2 AND timeStart<:timeStart)) AND viewableParents='Y' ORDER BY date DESC, timeStart DESC";
                                    } else {
                                        $dataPrevious = array('gibbonCourseClassID' => $row['gibbonCourseClassID'], 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'], 'date1' => $row['date'], 'date2' => $row['date'], 'timeStart' => $row['timeStart']);
                                        $sqlPrevious = "SELECT gibbonPlannerEntry.gibbonCourseClassID, gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, timeStart, timeEnd, viewableStudents, viewableParents, homework, role FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonPlannerEntry.gibbonCourseClassID=:gibbonCourseClassID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left' AND (date<:date1 OR (date=:date2 AND timeStart<:timeStart)) AND viewableStudents='Y' ORDER BY date DESC, timeStart DESC";
                                    }
                                }
                                $resultPrevious = $connection2->prepare($sqlPrevious);
                                $resultPrevious->execute($dataPrevious);
                            } catch (PDOException $e) {
                                echo "<div class='error'>".$e->getMessage().'</div>';
                            }
                            if ($resultPrevious->rowCount() > 0) {
                                $rowPrevious = $resultPrevious->fetch();
                                echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Planner/planner_view_full.php&search=$gibbonPersonID&gibbonPlannerEntryID=".$rowPrevious['gibbonPlannerEntryID']."&viewBy=$viewBy&subView=$subView&gibbonCourseClassID=".$rowPrevious['gibbonCourseClassID']."&date=$date'>".__($guid, 'Previous Lesson').'</a>';
                            } else {
                                echo __($guid, 'Previous Lesson');
                            }

                            echo ' | ';

                            try {
                                if ($row['role'] == 'Teacher') {
                                    $dataNext = array('gibbonCourseClassID' => $row['gibbonCourseClassID'], 'date1' => $row['date'], 'date2' => $row['date'], 'timeStart' => $row['timeStart']);
                                    $sqlNext = "SELECT gibbonPlannerEntry.gibbonCourseClassID, gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, timeStart, timeEnd, viewableStudents, viewableParents, homework, 'Teacher' AS role FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonPlannerEntry.gibbonCourseClassID=:gibbonCourseClassID AND (date>:date1 OR (date=:date2 AND timeStart>:timeStart)) ORDER BY date, timeStart";
                                } else {
                                    if ($highestAction == 'Lesson Planner_viewMyChildrensClasses') {
                                        $dataNext = array('gibbonCourseClassID' => $row['gibbonCourseClassID'], 'gibbonPersonID' => $gibbonPersonID, 'date1' => $row['date'], 'date2' => $row['date'], 'timeStart' => $row['timeStart']);
                                        $sqlNext = "SELECT gibbonPlannerEntry.gibbonCourseClassID, gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, timeStart, timeEnd, viewableStudents, viewableParents, homework, role FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonPlannerEntry.gibbonCourseClassID=:gibbonCourseClassID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left' AND (date>:date1 OR (date=:date2 AND timeStart>:timeStart)) AND viewableParents='Y' ORDER BY date, timeStart";
                                    } else {
                                        $dataNext = array('gibbonCourseClassID' => $row['gibbonCourseClassID'], 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'], 'date1' => $row['date'], 'date2' => $row['date'], 'timeStart' => $row['timeStart']);
                                        $sqlNext = "SELECT gibbonPlannerEntry.gibbonCourseClassID, gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, timeStart, timeEnd, viewableStudents, viewableParents, homework, role FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonPlannerEntry.gibbonCourseClassID=:gibbonCourseClassID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left' AND (date>:date1 OR (date=:date2 AND timeStart>:timeStart)) AND viewableStudents='Y' ORDER BY date, timeStart";
                                    }
                                }
                                $resultNext = $connection2->prepare($sqlNext);
                                $resultNext->execute($dataNext);
                            } catch (PDOException $e) {
                                echo "<div class='error'>".$e->getMessage().'</div>';
                            }
                            if ($resultNext->rowCount() > 0) {
                                $rowNext = $resultNext->fetch();
                                echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Planner/planner_view_full.php&search=$gibbonPersonID&gibbonPlannerEntryID=".$rowNext['gibbonPlannerEntryID']."&viewBy=$viewBy&subView=$subView&gibbonCourseClassID=".$rowNext['gibbonCourseClassID']."&date=$date'>".__($guid, 'Next Lesson').'</a>';
                            } else {
                                echo __($guid, 'Next Lesson');
                            }
                            echo '</p>';
                        }
                        echo '</div>';
                        echo '</div>';

                        if ($row['role'] == 'Teacher') {
                            echo "<div class='linkTop'>";
                            echo '<tr>';
                            echo '<td colspan=3>';
                            if ($row['gibbonUnitID'] != '') {
                                echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Planner/planner_unitOverview.php&viewBy=$viewBy&gibbonCourseClassID=$gibbonCourseClassID&gibbonPlannerEntryID=$gibbonPlannerEntryID&date=".$row['date']."&subView=$subView&gibbonUnitID=".$row['gibbonUnitID']."'>".__($guid, 'Unit Overview').'</a> | ';
                            }
                            echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Planner/planner_edit.php&viewBy=$viewBy&gibbonCourseClassID=$gibbonCourseClassID&gibbonPlannerEntryID=$gibbonPlannerEntryID&date=".$row['date']."&subView=$subView'>".__($guid, 'Edit')."<img style='margin: 0 0 -4px 3px' title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> | ";
                            try {
                                $dataMarkbook = array('gibbonCourseClassID' => $row['gibbonCourseClassID'], 'gibbonPlannerEntryID' => $gibbonPlannerEntryID);
                                $sqlMarkbook = 'SELECT * FROM gibbonMarkbookColumn WHERE gibbonCourseClassID=:gibbonCourseClassID AND gibbonPlannerEntryID=:gibbonPlannerEntryID';
                                $resultMarkbook = $connection2->prepare($sqlMarkbook);
                                $resultMarkbook->execute($dataMarkbook);
                            } catch (PDOException $e) {
                                echo "<div class='error'>".$e->getMessage().'</div>';
                            }
                            if ($resultMarkbook->rowCount() == 1) {
                                $rowMarkbook = $resultMarkbook->fetch();
                                echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Markbook/markbook_edit_data.php&gibbonCourseClassID=$gibbonCourseClassID&gibbonMarkbookColumnID=".$rowMarkbook['gibbonMarkbookColumnID']."'>".__($guid, 'Linked Markbook')."<img style='margin: 0 5px -4px 3px' title='".__($guid, 'Linked Markbook')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/markbook.png'/></a> | ";
                            }
                            echo "<input type='checkbox' name='confidentialPlan' class='confidentialPlan' value='Yes' />";
                            echo "<span title='".__($guid, 'Includes student data & teacher\'s notes')."' style='font-size: 85%; font-weight: normal; font-style: italic'> ".__($guid, 'Show Confidential Data').'</span>';
                            echo '</td>';
                            echo '</tr>';
                            echo '</div>';
                        } else {
                            echo "<div class='linkTop'>";
                            if ($row['gibbonUnitID'] != '') {
                                echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Planner/planner_unitOverview.php&viewBy=$viewBy&gibbonCourseClassID=$gibbonCourseClassID&gibbonPlannerEntryID=$gibbonPlannerEntryID&date=".$row['date']."&subView=$subView&gibbonUnitID=".$row['gibbonUnitID']."&search=$gibbonPersonID'>".__($guid, 'Unit Overview').'</a>';
                            }
                            echo '</div>';
                        }
                        echo "<table class='smallIntBorder' cellspacing='0' style='width: 100%;'>";
                        echo '<tr>';
                        echo "<td style='width: 33%; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Class').'</span><br/>';
                        echo $row['course'].'.'.$row['class'];
                        echo '</td>';
                        echo "<td style='width: 33%; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Date').'</span><br/>';
                        echo dateConvertBack($guid, $row['date']);
                        echo '</td>';
                        echo "<td style='width: 33%; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Time').'</span><br/>';
                        if ($row['timeStart'] != '' and $row['timeEnd'] != '') {
                            echo substr($row['timeStart'], 0, 5).'-'.substr($row['timeEnd'], 0, 5);
                        }
                        echo '</td>';
                        echo '</tr>';
                        if ($row['summary'] != '') {
                            echo '<tr>';
                            echo "<td style='padding-top: 5px; width: 33%; vertical-align: top' colspan=3>";
                            echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Summary').'</span><br/>';
                            echo $row['summary'];
                            echo '</td>';
                            echo '</tr>';
                        }
                        echo '</table>';

                        //Lesson outcomes
                        try {
                            $dataOutcomes = array('gibbonPlannerEntryID1' => $row['gibbonPlannerEntryID'], 'gibbonPlannerEntryID2' => $row['gibbonPlannerEntryID']);
                            $sqlOutcomes = "(SELECT scope, name, nameShort, category, gibbonYearGroupIDList, sequenceNumber, content FROM gibbonPlannerEntryOutcome JOIN gibbonOutcome ON (gibbonPlannerEntryOutcome.gibbonOutcomeID=gibbonOutcome.gibbonOutcomeID) WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID1 AND active='Y')
							UNION
							(SELECT scope, name, nameShort, category, gibbonYearGroupIDList, '' AS sequenceNumber, description AS content FROM gibbonUnitClassBlock JOIN gibbonOutcome ON (gibbonUnitClassBlock.gibbonOutcomeIDList LIKE concat( '%', gibbonOutcome.gibbonOutcomeID, '%' )) WHERE gibbonUnitClassBlock.gibbonPlannerEntryID=:gibbonPlannerEntryID2 AND active='Y')
							ORDER BY (sequenceNumber='') ASC, sequenceNumber, category, name";
                            $resultOutcomes = $connection2->prepare($sqlOutcomes);
                            $resultOutcomes->execute($dataOutcomes);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }

                        if ($resultOutcomes->rowCount() > 0) {
                            echo '<h2>'.__($guid, 'Lesson Outcomes').'</h2>';
                            echo "<table cellspacing='0' style='width: 100%'>";
                            echo "<tr class='head'>";
                            echo '<th>';
                            echo 'Scope';
                            echo '</th>';
                            echo '<th>';
                            echo __($guid, 'Category');
                            echo '</th>';
                            echo '<th>';
                            echo __($guid, 'Name');
                            echo '</th>';
                            echo '<th>';
                            echo __($guid, 'Year Groups');
                            echo '</th>';
                            echo '<th>';
                            echo __($guid, 'Actions');
                            echo '</th>';
                            echo '</tr>';

                            $count = 0;
                            $rowNum = 'odd';
                            while ($rowOutcomes = $resultOutcomes->fetch()) {
                                if ($count % 2 == 0) {
                                    $rowNum = 'even';
                                } else {
                                    $rowNum = 'odd';
                                }

                                    //COLOR ROW BY STATUS!
                                    echo "<tr class=$rowNum>";
                                echo '<td>';
                                echo '<b>'.$rowOutcomes['scope'].'</b><br/>';
                                if ($rowOutcomes['scope'] == 'Learning Area' and $gibbonDepartmentID != '') {
                                    try {
                                        $dataLearningArea = array('gibbonDepartmentID' => $gibbonDepartmentID);
                                        $sqlLearningArea = 'SELECT * FROM gibbonDepartment WHERE gibbonDepartmentID=:gibbonDepartmentID';
                                        $resultLearningArea = $connection2->prepare($sqlLearningArea);
                                        $resultLearningArea->execute($dataLearningArea);
                                    } catch (PDOException $e) {
                                        echo "<div class='error'>".$e->getMessage().'</div>';
                                    }
                                    if ($resultLearningArea->rowCount() == 1) {
                                        $rowLearningAreas = $resultLearningArea->fetch();
                                        echo "<span style='font-size: 75%; font-style: italic'>".$rowLearningAreas['name'].'</span>';
                                    }
                                }
                                echo '</td>';
                                echo '<td>';
                                echo '<b>'.$rowOutcomes['category'].'</b><br/>';
                                echo '</td>';
                                echo '<td>';
                                echo '<b>'.$rowOutcomes['nameShort'].'</b><br/>';
                                echo "<span style='font-size: 75%; font-style: italic'>".$rowOutcomes['name'].'</span>';
                                echo '</td>';
                                echo '<td>';
                                echo getYearGroupsFromIDList($guid, $connection2, $rowOutcomes['gibbonYearGroupIDList']);
                                echo '</td>';
                                echo '<td>';
                                echo "<script type='text/javascript'>";
                                echo '$(document).ready(function(){';
                                echo "\$(\".description-$count\").hide();";
                                echo "\$(\".show_hide-$count\").fadeIn(1000);";
                                echo "\$(\".show_hide-$count\").click(function(){";
                                echo "\$(\".description-$count\").fadeToggle(1000);";
                                echo '});';
                                echo '});';
                                echo '</script>';
                                if ($rowOutcomes['content'] != '') {
                                    echo "<a title='".__($guid, 'View Description')."' class='show_hide-$count' onclick='false' href='#'><img style='padding-left: 0px' src='".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/page_down.png' alt='".__($guid, 'Show Comment')."' onclick='return false;' /></a>";
                                }
                                echo '</td>';
                                echo '</tr>';
                                if ($rowOutcomes['content'] != '') {
                                    echo "<tr class='description-$count' id='description-$count'>";
                                    echo '<td colspan=6>';
                                    echo $rowOutcomes['content'];
                                    echo '</td>';
                                    echo '</tr>';
                                }
                                echo '</tr>';

                                ++$count;
                            }
                            echo '</table>';
                        }

                        echo "<h2 style='padding-top: 30px'>".__($guid, 'Lesson Content').'</h2>';
                        echo "<table class='smallIntBorder' cellspacing='0' style='width: 100%;'>";
                            //LESSON CONTENTS
                            //Get Smart Blocks
                            try {
                                if ($hooked == false) {
                                    $dataBlocks = array('gibbonPlannerEntryID' => $gibbonPlannerEntryID);
                                    $sqlBlocks = 'SELECT * FROM gibbonUnitClassBlock WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID ORDER BY sequenceNumber';
                                } else {
                                    $dataBlocks = array('gibbonPlannerEntryID' => $gibbonPlannerEntryID);
                                    $sqlBlocks = 'SELECT * FROM '.$hookOptions['classSmartBlockTable'].' WHERE '.$hookOptions['classSmartBlockPlannerJoin'].'=:gibbonPlannerEntryID ORDER BY sequenceNumber';
                                }
                                $resultBlocks = $connection2->prepare($sqlBlocks);
                                $resultBlocks->execute($dataBlocks);
                                $resultBlocksView = $connection2->prepare($sqlBlocks);
                                $resultBlocksView->execute($dataBlocks);
                            } catch (PDOException $e) {
                                echo "<div class='error'>".$e->getMessage().'</div>';
                            }
                        if ($row['description'] != '') {
                            echo '<tr>';
                            echo "<td style='text-align: justify; padding-top: 5px; width: 33%; vertical-align: top' colspan=3>";
                            echo $row['description'];
                            echo '</td>';
                            echo '</tr>';
                        }
                        if ($row['gibbonUnitID'] != '') {
                            echo '<tr>';
                            echo "<td style='text-align: justify; padding-top: 5px; width: 33%; vertical-align: top' colspan=3>";
                            if ($row['role'] == 'Teacher' and $teacher == true) {
                                echo "<div class='odd' style='padding: 5px; margin-top: 0px; text-align: right; border-bottom: 1px solid #666; border-top: 1px solid #666'>";
                                echo '<i>'.__($guid, 'Smart Blocks').'</i>: ';
                                if ($resultBlocks->rowCount() > 0) {
                                    echo "<a class='active' href='#' id='viewBlocks'>".__($guid, 'View')."</a> | <a href='#' id='editBlocks'>".__($guid, 'Edit Blocks').'</a> | ';
                                }
                                if ($hooked == false) {
                                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/units_edit_working.php&gibbonCourseClassID=$gibbonCourseClassID&gibbonCourseID=".$row['gibbonCourseID'].'&gibbonUnitID='.$row['gibbonUnitID'].'&gibbonSchoolYearID='.$_SESSION[$guid]['gibbonSchoolYearID']."&gibbonUnitClassID=$gibbonUnitClassID'>".__($guid, 'Edit Unit').'</a> ';
                                } else {
                                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/units_edit_working.php&gibbonCourseClassID=$gibbonCourseClassID&gibbonCourseID=".$row['gibbonCourseID'].'&gibbonUnitID='.$gibbonUnitIDToken.'-'.$gibbonHookIDToken.'&gibbonSchoolYearID='.$_SESSION[$guid]['gibbonSchoolYearID']."&gibbonUnitClassID=$gibbonUnitClassID'>".__($guid, 'Edit Unit').'</a> ';
                                }
                                echo '</div>';
                            }
                            if ($resultBlocks->rowCount() > 0) {
                                if ($row['role'] == 'Teacher' and $teacher == true) {
                                    ?>
									<script type="text/javascript">
										$(document).ready(function(){
											$("#smartEdit").hide() ;

											$('#viewBlocks').click(function() {
												$("#smartView").show() ;
												$("#viewBlocks").addClass("active") ;
												$("#smartEdit").hide() ;
												$("#editBlocks").removeClass("active") ;
											}) ;
											$('#editBlocks').click(function() {
												$("#smartView").hide() ;
												$("#viewBlocks").removeClass("active") ;
												$("#smartEdit").show() ;
												$("#editBlocks").addClass("active") ;
											}) ;
										}) ;
									</script>
									<?php
									echo "<div id='smartEdit'>";
                                    echo "<form method='post' action='".$_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/planner_view_full_smartProcess.php'>"; ?>
										<style>
											#sortable { list-style-type: none; margin: 0; padding: 0; width: 100%; }
											#sortable div.ui-state-default { margin: 0 0px 5px 0px; padding: 5px; font-size: 100%; min-height: 58px; }
											div.ui-state-default_dud { margin: 5px 0px 5px 0px; padding: 5px; font-size: 100%; min-height: 58px; }
											html>body #sortable li { min-height: 58px; line-height: 1.2em; }
											#sortable .ui-state-highlight { margin-bottom: 5px; min-height: 58px; line-height: 1.2em; width: 100%; }
										</style>
										<script type="text/javascript">
											$(function() {
												$( "#sortable" ).sortable({
													placeholder: "ui-state-highlight",
													axis: 'y'
												});
											});
										</script>

										<div class="sortable" id="sortable" style='width: 100%; padding: 5px 0px 0px 0px; border-top: 1px dotted #666; border-bottom: 1px dotted #666'>
											<?php
											//Get outcomes
											try {
												$dataOutcomes = array('gibbonUnitID' => $gibbonUnitID);
												$sqlOutcomes = "SELECT gibbonOutcome.gibbonOutcomeID, gibbonOutcome.name, gibbonOutcome.category, scope, gibbonDepartment.name AS department FROM gibbonUnitOutcome JOIN gibbonOutcome ON (gibbonUnitOutcome.gibbonOutcomeID=gibbonOutcome.gibbonOutcomeID) LEFT JOIN gibbonDepartment ON (gibbonOutcome.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE gibbonUnitID=:gibbonUnitID AND active='Y' ORDER BY sequenceNumber";
												$resultOutcomes = $connection2->prepare($sqlOutcomes);
												$resultOutcomes->execute($dataOutcomes);
											} catch (PDOException $e) {
												echo "<div class='error'>".$e->getMessage().'</div>';
											}
											$unitOutcomes = $resultOutcomes->fetchall();

											$i = 1;
											$minSeq = 0;
											while ($rowBlocks = $resultBlocks->fetch()) {
												if ($i == 1) {
													$minSeq = $rowBlocks['sequenceNumber'];
												}
												if ($hooked == false) {
													makeBlock($guid, $connection2, $i, 'plannerEdit', $rowBlocks['title'], $rowBlocks['type'], $rowBlocks['length'], $rowBlocks['contents'], $rowBlocks['complete'], '', $rowBlocks['gibbonUnitClassBlockID'], $rowBlocks['teachersNotes'], true, $unitOutcomes, $rowBlocks['gibbonOutcomeIDList']);
												} else {
													makeBlock($guid, $connection2, $i, 'plannerEdit', $rowBlocks[$hookOptions['classSmartBlockTitleField']], $rowBlocks[$hookOptions['classSmartBlockTypeField']], $rowBlocks[$hookOptions['classSmartBlockLengthField']], $rowBlocks[$hookOptions['classSmartBlockContentsField']], $rowBlocks[$hookOptions['classSmartBlockCompleteField']], '', $rowBlocks[$hookOptions['classSmartBlockIDField']], $rowBlocks[$hookOptions['classSmartBlockTeachersNotesField']]);
												}
												++$i;
											}
											?>
										</div>
										<?php
										echo "<div style='text-align: right; margin-top: 3px'>";
										echo "<input type='hidden' name='minSeq' value='$minSeq'>";
										echo "<input type='hidden' name='mode' value='edit'>";
										echo "<input type='hidden' name='params' value='$params'>";
										echo "<input type='hidden' name='gibbonPlannerEntryID' value='$gibbonPlannerEntryID'>";
										echo "<input type='hidden' name='address' value='".$_SESSION[$guid]['address']."'>";
										echo "<input type='submit' value='Submit'>";
										echo '</div>';
									echo '</form>';
									echo '</div>';
								}
                                echo "<div id='smartView' class='hiddenReveal'>";
                                echo "<form method='post' action='".$_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/planner_view_full_smartProcess.php'>";
                                $blockCount = 0;
                                while ($rowBlocksView = $resultBlocksView->fetch()) {
                                    if ($rowBlocksView['title'] != '' or $rowBlocksView['type'] != '' or $rowBlocksView['length'] != '') {
                                        echo "<div class='blockView' style='min-height: 35px'>";
                                        if ($rowBlocksView['type'] != '' or $rowBlocksView['length'] != '') {
                                            $width = '69%';
                                        } else {
                                            $width = '100%';
                                        }
                                        echo "<div style='padding-left: 3px; width: $width; float: left;'>";
                                        if ($rowBlocksView['title'] != '') {
                                            echo "<h5 style='padding-bottom: 2px'>".$rowBlocksView['title'].'</h5>';
                                        }
                                        echo '</div>';
                                        if ($rowBlocksView['type'] != '' or $rowBlocksView['length'] != '') {
                                            echo "<div style='float: right; width: 29%; padding-right: 3px; height: 35px'>";
                                            echo "<div style='text-align: right; font-size: 85%; font-style: italic; margin-top: 2px; border-bottom: 1px solid #ddd; height: 21px; padding-top: 4px'>";
                                            if ($rowBlocksView['type'] != '') {
                                                echo $rowBlocksView['type'];
                                                if ($rowBlocksView['length'] != '') {
                                                    echo ' | ';
                                                }
                                            }
                                            if ($rowBlocksView['length'] != '') {
                                                echo $rowBlocksView['length'].' min';
                                            }
                                            echo '</div>';
                                            echo '</div>';
                                        }
                                        echo '</div>';
                                    }
                                    if ($rowBlocksView['contents'] != '') {
                                        echo "<div style='padding: 15px 3px 10px 3px; width: 98%; text-align: justify; border-bottom: 1px solid #ddd'>".$rowBlocksView['contents'].'</div>';
                                    }
                                    if ($rowBlocksView['teachersNotes'] != '' and ($highestAction == 'Lesson Planner_viewAllEditMyClasses' or $highestAction == 'Lesson Planner_viewEditAllClasses') and ($row['role'] == 'Teacher' or $row['role'] == 'Assistant' or $row['role'] == 'Technician')) {
                                        echo "<div class='teachersNotes' style='background-color: #F6CECB; display: none; padding: 0px 3px 10px 3px; width: 98%; text-align: justify; border-bottom: 1px solid #ddd'><i>".__($guid, "Teacher's Notes").':</i> '.$rowBlocksView['teachersNotes'].'</div>';
                                    }
                                    $checked = '';
                                    if ($rowBlocksView['complete'] == 'Y') {
                                        $checked = 'checked';
                                    }
                                    if ($row['role'] == 'Teacher' and $teacher == true) {
                                        echo "<div style='text-align: right; font-weight: bold; margin-top: 20px'>".__($guid, 'Complete?')." <input name='complete$blockCount' style='margin-right: 2px' type='checkbox' $checked></div>";
                                    } else {
                                        echo "<div style='text-align: right; font-weight: bold; margin-top: 20px'>".__($guid, 'Complete?')." <input disabled name='complete$blockCount' style='margin-right: 2px' type='checkbox' $checked></div>";
                                    }
                                    if ($hooked == false) {
                                        echo "<input type='hidden' name='gibbonUnitClassBlockID[]' value='".$rowBlocksView['gibbonUnitClassBlockID']."'>";
                                    } else {
                                        echo "<input type='hidden' name='gibbonUnitClassBlockID[]' value='".$rowBlocksView['ibPYPUnitWorkingSmartBlockID']."'>";
                                    }

                                    echo "<div style='padding: 3px 3px 3px 0px ; width: 100%; text-align: justify; border-bottom: 1px solid #666' ></div>";
                                    ++$blockCount;
                                }
                                if ($row['role'] == 'Teacher' and $teacher == true) {
                                    echo "<div style='text-align: right; margin-top: 3px'>";
                                    echo "<input type='hidden' name='mode' value='view'>";
                                    echo "<input type='hidden' name='params' value='$params'>";
                                    echo "<input type='hidden' name='gibbonPlannerEntryID' value='$gibbonPlannerEntryID'>";
                                    echo "<input type='hidden' name='address' value='".$_SESSION[$guid]['address']."'>";
                                    echo "<input type='submit' value='Submit'>";
                                    echo '</div>';
                                }
                                echo '</form>';
                                echo '</div>';
                            }
                            echo '</td>';
                            echo '</tr>';
                        }

                        if ($resultBlocks->rowCount() < 1 and $row['description'] == '') {
                            echo '<tr>';
                            echo "<td style='text-align: justify; padding-top: 5px; width: 33%; vertical-align: top' colspan=3>";
                            echo "<div class='error'>";
                            echo __($guid, 'This lesson has not had any content assigned to it.');
                            echo '</div>';
                            echo '</td>';
                            echo '</tr>';
                        }

                        if ($row['teachersNotes'] != '' and ($highestAction == 'Lesson Planner_viewAllEditMyClasses' or $highestAction == 'Lesson Planner_viewEditAllClasses') and ($row['role'] == 'Teacher' or $row['role'] == 'Assistant' or $row['role'] == 'Technician')) {
                            echo "<tr class='break' id='teachersNotes'>";
                            echo "<td style='text-align: justify; padding-top: 5px; width: 33%; vertical-align: top' colspan=3>";
                            echo '<h3>'.__($guid, 'Teacher\'s Notes').'</h3>';
                            echo "<div style='background-color: #F6CECB; '>".$row['teachersNotes'].'</div>';
                            echo '</td>';
                            echo '</tr>';
                        }

                        echo '</table>';

                        echo "<h2 style='padding-top: 30px'>".__($guid, 'Homework').'</h2>';

                        echo "<table class='smallIntBorder' cellspacing='0' style='width: 100%;'>";
                        if ($row['role'] == 'Student') {
                            echo "<tr class='break'>";
                            echo "<td style='padding-top: 5px; width: 33%; vertical-align: top' colspan=3>";
                            echo '<h3>'.__($guid, 'Teacher Recorded Homework').'</h3>';
                            echo '</td>';
                            echo '</tr>';
                        }
                        echo '<tr>';
                        echo "<td style='padding-top: 5px; width: 33%; vertical-align: top' colspan=3>";
                        if ($row['homework'] == 'Y') {
                            echo "<span style='font-weight: bold; color: #CC0000'>".sprintf(__($guid, 'Due on %1$s at %2$s.'), dateConvertBack($guid, substr($row['homeworkDueDateTime'], 0, 10)), substr($row['homeworkDueDateTime'], 11, 5)).'</span><br/>';
                            echo $row['homeworkDetails'].'<br/>';
                            if ($row['homeworkSubmission'] == 'Y') {
                                if ($row['role'] == 'Student' and ($highestAction == 'Lesson Planner_viewMyClasses' or $highestAction == 'Lesson Planner_viewAllEditMyClasses')) {
                                    echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Online Submission').'</span><br/>';
                                    echo '<i>'.sprintf(__($guid, 'Online submission is %1$s for this homework.'), '<b>'.strtolower($row['homeworkSubmissionRequired']).'</b>').'</i><br/>';
                                    if (date('Y-m-d') < $row['homeworkSubmissionDateOpen']) {
                                        echo '<i>Submission opens on '.dateConvertBack($guid, $row['homeworkSubmissionDateOpen']).'</i>';
                                    } else {
                                        //Check previous submissions!
										try {
											$dataVersion = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonPlannerEntryID' => $row['gibbonPlannerEntryID']);
											$sqlVersion = 'SELECT * FROM gibbonPlannerEntryHomework WHERE gibbonPersonID=:gibbonPersonID AND gibbonPlannerEntryID=:gibbonPlannerEntryID ORDER BY count';
											$resultVersion = $connection2->prepare($sqlVersion);
											$resultVersion->execute($dataVersion);
										} catch (PDOException $e) {
											echo "<div class='error'>".$e->getMessage().'</div>';
										}

                                        $latestVersion = '';
                                        $count = 0;
                                        $rowNum = 'odd';
                                        if ($resultVersion->rowCount() > 0) {
                                            ?>
											<table cellspacing='0' style="width: 100%">
												<tr class='head'>
													<th>
														<?php echo __($guid, 'Count') ?><br/>
													</th>
													<th>
														<?php echo __($guid, 'Version') ?><br/>
													</th>
													<th>
														<?php echo __($guid, 'Status') ?><br/>
													</th>
													<th>
														<?php echo __($guid, 'Date/Time') ?><br/>
													</th>
													<th>
														<?php echo __($guid, 'View') ?><br/>
													</td>
													<?php
													if (date('Y-m-d H:i:s') < $row['homeworkDueDateTime']) {
														echo '<th>';
														echo __($guid, 'Actions').'<br/>';
														echo '</td>';
													}
												?>
												</tr>
												<?php
												while ($rowVersion = $resultVersion->fetch()) {
													if ($count % 2 == 0) {
														$rowNum = 'even';
													} else {
														$rowNum = 'odd';
													}
													++$count;

													echo "<tr class=$rowNum>";
													?>

														<td>
															<?php echo $rowVersion['count'] ?><br/>
														</td>
														<td>
															<?php echo $rowVersion['version'] ?><br/>
														</td>
														<td>
															<?php echo $rowVersion['status'] ?><br/>
														</td>
														<td>
															<?php echo substr($rowVersion['timestamp'], 11, 5).' '.dateConvertBack($guid, substr($rowVersion['timestamp'], 0, 10)) ?><br/>
														</td>
														<td style='max-width: 180px; word-wrap: break-word;'>
															<?php
															if ($rowVersion['type'] == 'File') {
																echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/'.$rowVersion['location']."'>".$rowVersion['location'].'</a>';
															} else {
                                                                if (strlen($rowVersion['location'])<=40) {
                                                                    echo "<a href='".$rowVersion['location']."'>".$rowVersion['location'].'</a>';
                                                                }
                                                                else {
                                                                    echo "<a href='".$rowVersion['location']."'>".substr($rowVersion['location'], 0, 50).'...'.'</a>';
                                                                }
															}
														?>
														</td>
														<?php
														if (date('Y-m-d H:i:s') < $row['homeworkDueDateTime']) {
															echo '<td>';
															echo "<a onclick='return confirm(\"".__($guid, 'Are you sure you wish to delete this record?')."\")' href='".$_SESSION[$guid]['absoluteURL']."/modules/Planner/planner_view_full_submit_studentDeleteProcess.php?gibbonPlannerEntryID=$gibbonPlannerEntryID&viewBy=$viewBy&subView=$subView&gibbonCourseClassID=$gibbonCourseClassID&date=$date&width=1000&height=550&search=$gibbonPersonID&gibbonPlannerEntryHomeworkID=".$rowVersion['gibbonPlannerEntryHomeworkID']."'><img title='".__($guid, 'Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a><br/>";
															echo '</td>';
														}
													?>
													</tr>
													<?php
													$latestVersion = $rowVersion['version'];
												}
											?>
											</table>
											<?php
                                        }

                                        if ($latestVersion != 'Final') {
                                            $status = 'On Time';
                                            if (date('Y-m-d H:i:s') > $row['homeworkDueDateTime']) {
                                                echo "<span style='color: #C00; font-stlye: italic'>".__($guid, 'The deadline has passed. Your work will be marked as late.').'</span><br/>';
                                                $status = 'Late';
                                            }

                                            ?>
											<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/planner_view_full_submitProcess.php?address='.$_GET['q'].$params.'&gibbonPlannerEntryID='.$row['gibbonPlannerEntryID'] ?>" enctype="multipart/form-data">
												<table class='smallIntBorder fullWidth' cellspacing='0'>
													<tr>
														<td>
															<b><?php echo __($guid, 'Type') ?> *</b><br/>
														</td>
														<td class="right">
															<?php
															if ($row['homeworkSubmissionType'] == 'Link') {
																echo "<input readonly id='type' name='type' type='text' value='Link' style='width: 302px'>";
															} elseif ($row['homeworkSubmissionType'] == 'File') {
																echo "<input readonly id='type' name='type' type='text' value='File' style='width: 302px'>";
															} else {
																?>
																<input checked type="radio" id="type" name="type" class="type" value="Link" /> Link
																<input type="radio" id="type" name="type" class="type" value="File" /> File
																<?php

															}
                                            				?>
														</td>
													</tr>
													<tr>
														<td>
															<b><?php echo __($guid, 'Version') ?> *</b><br/>
														</td>
														<td class="right">
															<?php
															echo "<select style='float: none; width: 302px' name='version'>";
															if ($row['homeworkSubmissionDrafts'] > 0 and $status != 'Late' and $resultVersion->rowCount() < $row['homeworkSubmissionDrafts']) {
																echo "<option value='Draft'>".__($guid, 'Draft').'</option>';
															}
															echo "<option value='Final'>".__($guid, 'Final').'</option>';
															echo '</select>';
															?>
														</td>
													</tr>

													<script type="text/javascript">
														/* Subbmission type control */
														$(document).ready(function(){
															<?php
															if ($row['homeworkSubmissionType'] == 'Link') {
																?>
																$("#fileRow").css("display","none");
																<?php

															} elseif ($row['homeworkSubmissionType'] == 'File') {
																?>
																$("#linkRow").css("display","none");
																<?php

															} else {
																?>
																$("#fileRow").css("display","none");
																$("#linkRow").slideDown("fast", $("#linkRow").css("display","table-row"));
																<?php

															}
                                            			?>

														$(".type").click(function(){
															if ($('input[name=type]:checked').val()=="Link" ) {
																$("#fileRow").css("display","none");
																$("#linkRow").slideDown("fast", $("#linkRow").css("display","table-row"));
															} else {
																$("#linkRow").css("display","none");
																$("#fileRow").slideDown("fast", $("#fileRow").css("display","table-row"));
															}
														 });
													});
												</script>

												<tr id="fileRow">
													<td>
														<b><?php echo __($guid, 'Submit File') ?> *</b><br/>
													</td>
													<td class="right">
														<input type="file" name="file" id="file"><br/><br/>
														<?php
														echo getMaxUpload($guid);

														//Get list of acceptable file extensions
														try {
															$dataExt = array();
															$sqlExt = 'SELECT * FROM gibbonFileExtension';
															$resultExt = $connection2->prepare($sqlExt);
															$resultExt->execute($dataExt);
														} catch (PDOException $e) {
														}
														$ext = '';
														while ($rowExt = $resultExt->fetch()) {
															$ext = $ext."'.".$rowExt['extension']."',";
														}
														?>

														<script type="text/javascript">
															var file=new LiveValidation('file');
															file.add( Validate.Inclusion, { within: [<?php echo $ext;?>], failureMessage: "Illegal file type!", partialMatch: true, caseSensitive: false } );
														</script>
													</td>
												</tr>
												<tr id="linkRow">
													<td>
														<b><?php echo __($guid, 'Submit Link') ?> *</b><br/>
													</td>
													<td class="right">
														<input name="link" id="link" maxlength=255 value="" type="text" class="standardWidth">
														<script type="text/javascript">
															var link=new LiveValidation('link');
															link.add( Validate.Inclusion, { within: ['http://', 'https://'], failureMessage: "Address must start with http:// or https://", partialMatch: true } );
														</script>
													</td>
												</tr>
												<tr>
													<td class="right" colspan=2>
														<?php
														echo "<input type='hidden' name='lesson' value='".$row['name']."'>";
														echo "<input type='hidden' name='count' value='$count'>";
														echo "<input type='hidden' name='status' value='$status'>";
														echo "<input type='hidden' name='gibbonPlannerEntryID' value='$gibbonPlannerEntryID'>";
														echo "<input type='hidden' name='currentDate' value='".$row['date']."'>";
														?>
														<input type="submit" value="<?php echo __($guid, 'Submit');
                                           				?>">
													</td>
												</tr>
											</table>
										</form>
										<?php

                                        }
                                    }
                                } elseif ($row['role'] == 'Student' and $highestAction == 'Lesson Planner_viewMyChildrensClasses') {
                                    echo "<span style='font-size: 115%; font-weight: bold'>Online Submission</span><br/>";
                                    echo '<i>Online submission is <b>'.strtolower($row['homeworkSubmissionRequired']).'</b> for this homework.</i><br/>';
                                    if (date('Y-m-d') < $row['homeworkSubmissionDateOpen']) {
                                        echo '<i>Submission opens on '.dateConvertBack($guid, $row['homeworkSubmissionDateOpen']).'</i>';
                                    } else {
                                        //Check previous submissions!
										try {
											$dataVersion = array('gibbonPersonID' => $gibbonPersonID, 'gibbonPlannerEntryID' => $row['gibbonPlannerEntryID']);
											$sqlVersion = 'SELECT * FROM gibbonPlannerEntryHomework WHERE gibbonPersonID=:gibbonPersonID AND gibbonPlannerEntryID=:gibbonPlannerEntryID';
											$resultVersion = $connection2->prepare($sqlVersion);
											$resultVersion->execute($dataVersion);
										} catch (PDOException $e) {
											echo "<div class='error'>".$e->getMessage().'</div>';
										}
                                        $latestVersion = '';
                                        $count = 0;
                                        $rowNum = 'odd';
                                        if ($resultVersion->rowCount() < 1) {
                                            if (date('Y-m-d H:i:s') > $row['homeworkDueDateTime']) {
                                                echo "<span style='color: #C00; font-stlye: italic'>".__($guid, 'The deadline has passed, and no work has been submitted.').'</span><br/>';
                                            }
                                        } else {
                                            ?>
											<table cellspacing='0' style="width: 100%">
												<tr class='head'>
													<th>
														<?php echo __($guid, 'Count') ?><br/>
													</th>
													<th>
														<?php echo __($guid, 'Version') ?><br/>
													</th>
													<th>
														<?php echo __($guid, 'Status') ?><br/>
													</th>
													<th>
														<?php echo __($guid, 'Date/Time') ?><br/>
													</th>
													<th>
														<?php echo __($guid, 'View') ?><br/>
													</td>
												</tr>
												<?php
												while ($rowVersion = $resultVersion->fetch()) {
													if ($count % 2 == 0) {
														$rowNum = 'even';
													} else {
														$rowNum = 'odd';
													}
													++$count;

													echo "<tr class=$rowNum>";
													?>
														<td>
															<?php echo $rowVersion['count'] ?><br/>
														</td>
														<td>
															<?php echo $rowVersion['version'] ?><br/>
														</td>
														<td>
															<?php echo $rowVersion['status'] ?><br/>
														</td>
														<td>
															<?php echo substr($rowVersion['timestamp'], 11, 5).' '.dateConvertBack($guid, substr($rowVersion['timestamp'], 0, 10)) ?><br/>
														</td>
														<td style='max-width: 180px; word-wrap: break-word;'>
															<?php
															if ($rowVersion['type'] == 'File') {
																echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/'.$rowVersion['location']."'>".$rowVersion['location'].'</a>';
															} else {
                                                                if (strlen($rowVersion['location'])<=40) {
                                                                    echo "<a href='".$rowVersion['location']."'>".$rowVersion['location'].'</a>';
                                                                }
                                                                else {
                                                                    echo "<a href='".$rowVersion['location']."'>".substr($rowVersion['location'], 0, 40).'...'.'</a>';
                                                                }
															}
                                                            ?>
														</td>
													</tr>
													<?php
													$latestVersion = $rowVersion['version'];
												}
												?>
											</table>
											<?php
                                        }
                                    }
                                } elseif ($row['role'] == 'Teacher') {
                                    echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Online Submission').'</span><br/>';
                                    echo '<i>'.sprintf(__($guid, 'Online submission is %1$s for this homework.'), '<b>'.strtolower($row['homeworkSubmissionRequired']).'</b>').'</i><br/>';

                                    if ($teacher == true) {
                                        //List submissions
										try {
											$dataClass = array('gibbonCourseClassID' => $row['gibbonCourseClassID']);
											$sqlClass = "SELECT * FROM gibbonCourseClassPerson INNER JOIN gibbonPerson ON gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID WHERE gibbonCourseClassID=:gibbonCourseClassID AND status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND role='Student' ORDER BY role DESC, surname, preferredName";
											$resultClass = $connection2->prepare($sqlClass);
											$resultClass->execute($dataClass);
										} catch (PDOException $e) {
											echo "<div class='error'>".$e->getMessage().'</div>';
										}
                                        $count = 0;
                                        $rowNum = 'odd';
                                        if ($resultClass->rowCount() > 0) {
                                            ?>
											<table cellspacing='0' style="width: 100%">
												<tr class='head'>
													<th>
														<?php echo __($guid, 'Student') ?><br/>
													</th>
													<th>
														<?php echo __($guid, 'Status') ?><br/>
													</th>
													<th>
														<?php echo __($guid, 'Version') ?><br/>
													</th>
													<th>
														<?php echo __($guid, 'Date/Time') ?><br/>
													</th>
													<th>
														<?php echo __($guid, 'View') ?><br/>
													</th>
													<th>
														<?php echo __($guid, 'Action') ?><br/>
													</th>
												</tr>
												<?php
												while ($rowClass = $resultClass->fetch()) {
													if ($count % 2 == 0) {
														$rowNum = 'even';
													} else {
														$rowNum = 'odd';
													}
													++$count;

													echo "<tr class=$rowNum>";
													?>

														<td>
															<?php echo "<a href='index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=".$rowClass['gibbonPersonID']."'>".formatName('', $rowClass['preferredName'], $rowClass['surname'], 'Student', true).'</a>' ?><br/>
														</td>

														<?php

														try {
															$dataVersion = array('gibbonPlannerEntryID' => $row['gibbonPlannerEntryID'], 'gibbonPersonID' => $rowClass['gibbonPersonID']);
															$sqlVersion = 'SELECT * FROM gibbonPlannerEntryHomework WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPersonID=:gibbonPersonID ORDER BY count DESC';
															$resultVersion = $connection2->prepare($sqlVersion);
															$resultVersion->execute($dataVersion);
														} catch (PDOException $e) {
															echo "<div class='error'>".$e->getMessage().'</div>';
														}
													if ($resultVersion->rowCount() < 1) {
														?>
															<td colspan=4>
																<?php
																//Before deadline
																if (date('Y-m-d H:i:s') < $row['homeworkDueDateTime']) {
																	echo 'Pending';
																}
																//After
																else {
																	if ($rowClass['dateStart'] > $row['date']) {
																		echo "<span title='".__($guid, 'Student joined school after lesson was taught.')."' style='color: #000; font-weight: normal; border: 2px none #ff0000; padding: 2px 4px'>".__($guid, 'NA').'</span>';
																	} else {
																		if ($row['homeworkSubmissionRequired'] == 'Compulsory') {
																			echo "<span style='color: #ff0000; font-weight: bold; border: 2px solid #ff0000; padding: 2px 4px'>".__($guid, 'Incomplete').'</span>';
																		} else {
																			echo __($guid, 'Not submitted online');
																		}
																	}
																}
														?>
															</td>
															<td>
																<?php
																echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Planner/planner_view_full_submit_edit.php&gibbonPlannerEntryID=$gibbonPlannerEntryID&viewBy=$viewBy&subView=$subView&gibbonCourseClassID=$gibbonCourseClassID&date=$date&search=".$gibbonPersonID.'&gibbonPersonID='.$rowClass['gibbonPersonID']."&submission=false'><img title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
														?>
															</td>
															<?php

													} else {
														$rowVersion = $resultVersion->fetch();
														?>
															<td>
																<?php
																if ($rowVersion['status'] == 'On Time' or $rowVersion['status'] == 'Exemption') {
																	echo $rowVersion['status'];
																} else {
																	echo "<span style='color: #ff0000; font-weight: bold; border: 2px solid #ff0000; padding: 2px 4px'>".$rowVersion['status'].'</span>';
																}
														?>
															</td>
															<td>
																<?php
																echo $rowVersion['version'];
														if ($rowVersion['version'] == 'Draft') {
															echo ' '.$rowVersion['count'];
														}
														?>
															</td>
															<td>
																<?php echo substr($rowVersion['timestamp'], 11, 5).' '.dateConvertBack($guid, substr($rowVersion['timestamp'], 0, 10)) ?><br/>
															</td>
															<td>
																<?php
																$locationPrint = $rowVersion['location'];
														if (strlen($locationPrint) > 15) {
															$locationPrint = substr($locationPrint, 0, 15).'...';
														}
														if ($rowVersion['type'] == 'File') {
															echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/'.$rowVersion['location']."'>".$locationPrint.'</a>';
														} else {
															echo "<a target='_blank' href='".$rowVersion['location']."'>".$locationPrint.'</a>';
														}
														?>
															</td>
															<td>
																<?php
																echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Planner/planner_view_full_submit_edit.php&gibbonPlannerEntryID=$gibbonPlannerEntryID&viewBy=$viewBy&subView=$subView&gibbonCourseClassID=$gibbonCourseClassID&date=$date&width=1000&height=550&search=".$gibbonPersonID.'&gibbonPlannerEntryHomeworkID='.$rowVersion['gibbonPlannerEntryHomeworkID']."&submission=true'><img title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
														echo "<a onclick='return confirm(\"".__($guid, 'Are you sure you wish to delete this record?')."\")' href='".$_SESSION[$guid]['absoluteURL']."/modules/Planner/planner_view_full_submit_deleteProcess.php?gibbonPlannerEntryID=$gibbonPlannerEntryID&viewBy=$viewBy&subView=$subView&gibbonCourseClassID=$gibbonCourseClassID&date=$date&width=1000&height=550&search=$gibbonPersonID&gibbonPlannerEntryHomeworkID=".$rowVersion['gibbonPlannerEntryHomeworkID']."'><img title='".__($guid, 'Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a>";
														?>
															</td>
															<?php

													}
													?>
													</tr>
													<?php

												}
                                            	?>
											</table>
											<?php
                                        }
                                    }
                                }
                            }
                        } elseif ($row['homework'] == 'N') {
                            echo __($guid, 'No').'<br/>';
                        }
                        echo '</td>';
                        echo '</tr>';

                        if ($row['role'] == 'Student') { //MY HOMEWORK
                            $roleCategory = getRoleCategory($_SESSION[$guid]['gibbonRoleIDCurrent'], $connection2);
                            $myHomeworkFail = false;
                            try {
                                if ($roleCategory != 'Student') { //Parent
                                    $dataMyHomework = array('gibbonPersonID' => $gibbonPersonID, 'gibbonPlannerEntryID' => $gibbonPlannerEntryID);
                                } else { //Student
                                    $dataMyHomework = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonPlannerEntryID' => $gibbonPlannerEntryID);
                                }
                                $sqlMyHomework = 'SELECT * FROM gibbonPlannerEntryStudentHomework WHERE gibbonPersonID=:gibbonPersonID AND gibbonPlannerEntryID=:gibbonPlannerEntryID';
                                $resultMyHomework = $connection2->prepare($sqlMyHomework);
                                $resultMyHomework->execute($dataMyHomework);
                            } catch (PDOException $e) {
                                $myHomeworkFail = true;
                            }

                            echo "<tr class='break'>";
                            echo "<td style='padding-top: 5px; width: 33%; vertical-align: top' colspan=3>";
                            echo '<h3>'.__($guid, 'Student Recorded Homework').'</h3>';
                            if ($roleCategory == 'Student') {
                                echo '<p>'.__($guid, 'If your teacher has not entered homework into Gibbon, or you want to record extra homework, you can enter the details here.').'</p>';
                            }
                            echo '</td>';
                            echo '</tr>';
                            if ($myHomeworkFail or $resultMyHomework->rowCount() > 1) {
                                echo "<div class='error'>";
                                echo __($guid, 'Your request failed due to a database error.');
                                echo '</div>';
                            } else {
                                if ($resultMyHomework->rowCount() == 1) {
                                    $rowMyHomework = $resultMyHomework->fetch();
                                    $rowMyHomework['homework'] = 'Y';
                                } else {
                                    $rowMyHomework = array();
                                    $rowMyHomework['homework'] = 'N';
                                    $rowMyHomework['homeworkDetails'] = '';
                                }

                                if ($roleCategory != 'Student') { //Parent, so show readonly
									?>
									<tr>
										<td>
											<b><?php echo __($guid, 'Homework?') ?> *</b><br/>
										</td>
										<td>
											<?php
											if ($rowMyHomework['homework'] == 'Y') {
												echo __($guid, 'Yes');
											} else {
												echo __($guid, 'No');
											}
										?>
										</td>
									</tr>

									<?php
									if ($rowMyHomework['homework'] == 'Y') {
										?>
										<tr>
											<td>
												<b><?php echo __($guid, 'Homework Due Date') ?> *</b><br/>
											</td>
											<td>
												<?php if ($rowMyHomework['homework'] == 'Y') { echo dateConvertBack($guid, substr($rowMyHomework['homeworkDueDateTime'], 0, 10)); } ?>
											</td>
										</tr>
										<tr >
											<td>
												<b><?php echo __($guid, 'Homework Due Date Time') ?></b><br/>
												<span class="emphasis small"><?php echo __($guid, 'Format: hh:mm (24hr)') ?><br/></span>
											</td>
											<td >
												<?php if ($rowMyHomework['homework'] == 'Y') { echo substr($rowMyHomework['homeworkDueDateTime'], 11, 5); } ?>
											</td>
										</tr>
										<tr>
											<td>
												<b><?php echo __($guid, 'Homework Details') ?></b><br/>
											</td>
											<td class="right">
												<?php echo $rowMyHomework['homeworkDetails'] ?>
											</td>
										</tr>
									<?php

									}
                                } else { //Student so show edit view
                                            $checkedYes = '';
                                    $checkedNo = '';
                                    if ($rowMyHomework['homework'] == 'Y') {
                                        $checkedYes = 'checked';
                                    } else {
                                        $checkedNo = 'checked';
                                    }
                                    ?>

									<script type="text/javascript">
										/* Homework Control */
										$(document).ready(function(){
											<?php
											if ($checkedNo == 'checked') {
												?>
												$("#homeworkDueDateRow").css("display","none");
												$("#homeworkDueDateTimeRow").css("display","none");
												$("#homeworkDetailsRow").css("display","none");
												<?php

											}
										?>

											//Response to clicking on homework control
											$(".homework").click(function(){
												if ($('input[name=homework]:checked').val()=="Yes" ) {
													homeworkDueDate.enable();
													homeworkDetails.enable();
													$("#homeworkDueDateRow").slideDown("fast", $("#homeworkDueDateRow").css("display","table-row"));
													$("#homeworkDueDateTimeRow").slideDown("fast", $("#homeworkDueDateTimeRow").css("display","table-row"));
													$("#homeworkDetailsRow").slideDown("fast", $("#homeworkDetailsRow").css("display","table-row"));
												} else {
													homeworkDueDate.disable();
													homeworkDetails.disable();
													$("#homeworkDueDateRow").css("display","none");
													$("#homeworkDueDateTimeRow").css("display","none");
													$("#homeworkDetailsRow").css("display","none");
												}
											 });
										});
									</script>

									<?php
									//Try and find the next slot for this class, to use as default HW deadline
									if ($rowMyHomework['homework'] == 'N' and $row['date'] != '' and $row['timeStart'] != '' and $row['timeEnd'] != '') {
										//Get $_GET values
										$homeworkDueDate = '';
										$homeworkDueDateTime = '';

										try {
											$dataNext = array('gibbonCourseClassID' => $row['gibbonCourseClassID'], 'date' => $row['date']);
											$sqlNext = 'SELECT timeStart, timeEnd, date FROM gibbonTTDayRowClass JOIN gibbonTTColumnRow ON (gibbonTTDayRowClass.gibbonTTColumnRowID=gibbonTTColumnRow.gibbonTTColumnRowID) JOIN gibbonTTColumn ON (gibbonTTColumnRow.gibbonTTColumnID=gibbonTTColumn.gibbonTTColumnID) JOIN gibbonTTDay ON (gibbonTTDayRowClass.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) JOIN gibbonTTDayDate ON (gibbonTTDayDate.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) WHERE gibbonCourseClassID=:gibbonCourseClassID AND date>:date ORDER BY date, timeStart LIMIT 0, 10';
											$resultNext = $connection2->prepare($sqlNext);
											$resultNext->execute($dataNext);
										} catch (PDOException $e) {
											echo "<div class='error'>".$e->getMessage().'</div>';
										}
										if ($resultNext->rowCount() > 0) {
											$rowNext = $resultNext->fetch();
											$homeworkDueDate = $rowNext['date'];
											$homeworkDueDateTime = $rowNext['timeStart'];
										}
									}
								?>

								<?php echo "<form method='post' action='".$_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/planner_view_full_myHomeworkProcess.php?gibbonPlannerEntryID=$gibbonPlannerEntryID&viewBy=$viewBy&subView=$subView&address=".$_SESSION[$guid]['address']."&gibbonCourseClassID=$gibbonCourseClassID&date=$date'>"; ?>
									<tr>
										<td>
											<b><?php echo __($guid, 'Homework?') ?> *</b><br/>
										</td>
										<td class="right">
											<input <?php echo $checkedYes ?> type="radio" name="homework" value="Yes" class="homework" /> <?php echo __($guid, 'Yes') ?>
											<input <?php echo $checkedNo ?> type="radio" name="homework" value="No" class="homework" /> <?php echo __($guid, 'No') ?>
										</td>
									</tr>
									<tr id="homeworkDueDateRow">
										<td>
											<b><?php echo __($guid, 'Homework Due Date') ?> *</b><br/>
											<span class="emphasis small"><?php echo __($guid, 'Format:') ?> <?php if ($_SESSION[$guid]['i18n']['dateFormat'] == '') { echo 'dd/mm/yyyy';
											} else {
												echo $_SESSION[$guid]['i18n']['dateFormat'];
											} ?><br/></span>
										</td>
										<td class="right">
											<input name="homeworkDueDate" id="homeworkDueDate" maxlength=10 value="<?php if ($rowMyHomework['homework'] == 'Y') { echo dateConvertBack($guid, substr($rowMyHomework['homeworkDueDateTime'], 0, 10));
											} else {
												echo dateConvertBack($guid, substr($homeworkDueDate, 0, 10));
											} ?>" type="text" class="standardWidth">
											<script type="text/javascript">
												var homeworkDueDate=new LiveValidation('homeworkDueDate');
												homeworkDueDate.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]['i18n']['dateFormatRegEx'] == '') { echo "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i";
												} else {
													echo $_SESSION[$guid]['i18n']['dateFormatRegEx'];
												}
                                   	 			?>, failureMessage: "Use <?php if ($_SESSION[$guid]['i18n']['dateFormat'] == '') { echo 'dd/mm/yyyy'; } else { echo $_SESSION[$guid]['i18n']['dateFormat']; } ?>." } );
												homeworkDueDate.add(Validate.Presence);
												<?php
												if ($rowMyHomework['homework'] != 'Y') {
													echo 'homeworkDueDate.disable();';
												}
                                    			?>
											</script>
											<script type="text/javascript">
												$(function() {
													$( "#homeworkDueDate" ).datepicker();
												});
											</script>
										</td>
									</tr>
									<tr id="homeworkDueDateTimeRow">
										<td>
											<b><?php echo __($guid, 'Homework Due Date Time') ?></b><br/>
											<span class="emphasis small"><?php echo __($guid, 'Format: hh:mm (24hr)') ?><br/></span>
										</td>
										<td class="right">
											<input name="homeworkDueDateTime" id="homeworkDueDateTime" maxlength=5 value="<?php if ($rowMyHomework['homework'] == 'Y') { echo substr($rowMyHomework['homeworkDueDateTime'], 11, 5);
											} else {
												echo substr($homeworkDueDateTime, 0, 5);
											} ?>" type="text" class="standardWidth">
											<script type="text/javascript">
												var homeworkDueDateTime=new LiveValidation('homeworkDueDateTime');
												homeworkDueDateTime.add( Validate.Format, {pattern: /^(0[0-9]|[1][0-9]|2[0-3])[:](0[0-9]|[1-5][0-9])/i, failureMessage: "Use hh:mm" } );
											</script>
											<script type="text/javascript">
												$(function() {
													var availableTags=[
														<?php
														try {
															$dataAuto = array();
															$sqlAuto = 'SELECT DISTINCT SUBSTRING(homeworkDueDateTime,12,5) AS homeworkDueTime FROM gibbonPlannerEntry ORDER BY homeworkDueDateTime';
															$resultAuto = $connection2->prepare($sqlAuto);
															$resultAuto->execute($dataAuto);
														} catch (PDOException $e) {
														}
														while ($rowAuto = $resultAuto->fetch()) {
															echo '"'.$rowAuto['homeworkDueTime'].'", ';
														}
														?>
													];
													$( "#homeworkDueDateTime" ).autocomplete({source: availableTags});
												});
											</script>
										</td>
									</tr>
									<tr id="homeworkDetailsRow">
										<td colspan=2>
											<b><?php echo __($guid, 'Homework Details') ?> *</b>
											<?php
											$initiallyHidden = true;
											if ($rowMyHomework['homework'] == 'Y') {
												$initiallyHidden = false;
											}
											echo getEditor($guid,  true, 'homeworkDetails', $rowMyHomework['homeworkDetails'], 25, true, true, $initiallyHidden)
											?>
										</td>
									</tr>
									<tr>
										<td>
											<span class="emphasis small">* <?php echo __($guid, 'denotes a required field');?></span>
										</td>
										<td class="right">
											<input type="submit" value="<?php echo __($guid, 'Submit');?>">
										</td>
									</tr>
								</form>
								<?php
                                }
                            }
                        }
                        echo '</table>';

                        echo "<a name='chat'></a>";
                        echo "<h2 style='padding-top: 30px'>".__($guid, 'Chat').'</h2>';
                        echo "<table class='smallIntBorder' cellspacing='0' style='width: 100%;'>";
                        echo '<tr>';
                        echo "<td style='text-align: justify; padding-top: 5px; width: 33%; vertical-align: top; max-width: 752px!important;' colspan=3>";

                        echo "<div style='margin: 0px' class='linkTop'>";
                        echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/planner_view_full.php$params#chat'>".__($guid, 'Refresh')."<img style='margin-left: 5px' title='".__($guid, 'Refresh')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/refresh.png'/></a> <a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Planner/planner_view_full_post.php&gibbonPlannerEntryID=$gibbonPlannerEntryID&viewBy=$viewBy&subView=$subView&gibbonCourseClassID=$gibbonCourseClassID&date=$date&search=".$gibbonPersonID."'>".__($guid, 'Add')."<img style='margin-left: 5px' title='".__($guid, 'Add')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new.png'/></a> ";
                        echo '</div>';

						//Get discussion
						echo getThread($guid, $connection2, $gibbonPlannerEntryID, null, 0, null, $viewBy, $subView, $date, @$class, $gibbonCourseClassID, $gibbonPersonID, $row['role']);

                        echo '</td>';
                        echo '</tr>';
                        echo '</table>';

                        //Participants & Attendance
                        $gibbonCourseClassID = $row['gibbonCourseClassID'];
                        $columns = 2;

                        // Only show certain options if Class Attendance is Enabled school-wide, and for this particular class
                        $attendanceEnabled = isActionAccessible($guid, $connection2, "/modules/Attendance/attendance_take_byCourseClass.php") && $row['attendance'] == 'Y';

                        // Get attendance pre-fill and default settings
                        $prefillAttendanceType = getSettingByScope($connection2, 'Attendance', 'prefillClass');
                        $defaultAttendanceType = getSettingByScope($connection2, 'Attendance', 'defaultClassAttendanceType');

                        require_once $_SESSION[$guid]['absolutePath'].'/modules/Attendance/src/attendanceView.php';

                        $attendance = new Module\Attendance\attendanceView($gibbon, $pdo);

                        try {
                            $dataClassGroup = array('gibbonCourseClassID' => $gibbonCourseClassID);
                            $sqlClassGroup = "SELECT * FROM gibbonCourseClassPerson INNER JOIN gibbonPerson ON gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID WHERE gibbonCourseClassID=:gibbonCourseClassID AND status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND (NOT role='Student - Left') AND (NOT role='Teacher - Left') ORDER BY FIELD(role, 'Teacher', 'Assistant', 'Technician', 'Student', 'Parent'), surname, preferredName";
                            $resultClassGroup = $connection2->prepare($sqlClassGroup);
                            $resultClassGroup->execute($dataClassGroup);
                        } catch (PDOException $e) {
                            $_SESSION[$guid]['sidebarExtra'] .= "<div class='error'>".$e->getMessage().'</div>';
                        }

                        $_SESSION[$guid]['sidebarExtra'] = "<div style='width:260px; float: right; font-size: 115%; font-weight: bold; margin-top: 8px; padding-left: 25px'>";

                        if ($attendanceEnabled) {
                             $_SESSION[$guid]['sidebarExtra'] .= __($guid, 'Participants') .' & '. __($guid, 'Attendance') . "<br/>";
                        } else {
                            $_SESSION[$guid]['sidebarExtra'] .= __($guid, 'Participants') . "<br/>";
                        }
                            //Show attendance log for the current day
                            if ( $attendanceEnabled && ( ($row['role'] == 'Teacher' and $teacher == true) )) {
                                try {
                                    $dataLog = array( 'date' => $row['date'], 'gibbonCourseClassID' => $gibbonCourseClassID);
                                    $sqlLog = 'SELECT * FROM gibbonAttendanceLogCourseClass, gibbonPerson WHERE gibbonAttendanceLogCourseClass.gibbonPersonIDTaker=gibbonPerson.gibbonPersonID AND date LIKE :date AND gibbonCourseClassID=:gibbonCourseClassID ORDER BY timestampTaken';
                                    $resultLog = $connection2->prepare($sqlLog);
                                    $resultLog->execute($dataLog);
                                } catch (PDOException $e) {
                                    $_SESSION[$guid]['sidebarExtra'] .= "<div class='error'>".$e->getMessage().'</div>';
                                }
                                if ($resultLog->rowCount() < 1) {
                                    $_SESSION[$guid]['sidebarExtra'] .= "<div class='error'>";
                                    $_SESSION[$guid]['sidebarExtra'] .= __($guid, 'Attendance has not been taken. The entries below are a best-guess, not actual data.');
                                    $_SESSION[$guid]['sidebarExtra'] .= '</div>';
                                } else {
                                    $_SESSION[$guid]['sidebarExtra'] .= "<div class='success'>";
                                    $_SESSION[$guid]['sidebarExtra'] .= __($guid, 'Attendance has been taken at the following times for this lesson:');
                                    $_SESSION[$guid]['sidebarExtra'] .= "<ul style='margin-left: 20px'>";
                                    while ($rowLog = $resultLog->fetch()) {
                                        $_SESSION[$guid]['sidebarExtra'] .= '<li><a style="color:inherit;" href="'.$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Attendance/attendance_take_byCourseClass.php&gibbonCourseClassID='.$gibbonCourseClassID.'&currentDate='.dateConvertBack($guid, $row['date'] ).'">'.substr($rowLog['timestampTaken'], 11, 5).' '.dateConvertBack($guid, substr($rowLog['timestampTaken'], 0, 10)).' '.__($guid, 'by').' '.formatName($rowLog['title'], $rowLog['preferredName'], $rowLog['surname'], 'Staff', false, true).'</a></li>';
                                    }
                                    $_SESSION[$guid]['sidebarExtra'] .= '</ul>';
                                    $_SESSION[$guid]['sidebarExtra'] .= '</div>';
                                }
                            }

                        if ($attendanceEnabled && $row['role'] == 'Teacher' and $teacher == true) {
                            $_SESSION[$guid]['sidebarExtra'] .= "<form autocomplete=\"off\" method='post' action='".$_SESSION[$guid]['absoluteURL']."/modules/Attendance/attendance_take_byCourseClassProcess.php'>";
                        }
                        $_SESSION[$guid]['sidebarExtra'] .= "<table class='noIntBorder' cellspacing='0' style='width:260px; float: right; margin-bottom: 30px'>";
                        $count = 0;
                        $countStudents = 0;
                        while ($rowClassGroup = $resultClassGroup->fetch()) {
                            if ($count % $columns == 0) {
                                $_SESSION[$guid]['sidebarExtra'] .= '<tr>';
                            }

							//Get attendance status for students
                            $rowLog = array('type' => $defaultAttendanceType, 'reason' => '', 'comment' => '');

                            if ($rowClassGroup['role'] == 'Student') {

                                //Get any student log data by context
                                try {
                                    $dataLog=array('gibbonPersonID' => $rowClassGroup['gibbonPersonID'], 'date' => $row['date'].'%', 'gibbonCourseClassID' => $gibbonCourseClassID);
                                    $sqlLog="SELECT * FROM gibbonAttendanceLogPerson WHERE context='Class' AND gibbonPersonID=:gibbonPersonID AND (gibbonCourseClassID=:gibbonCourseClassID) AND date LIKE :date ORDER BY timestampTaken DESC" ;
                                    $resultLog=$connection2->prepare($sqlLog);
                                    $resultLog->execute($dataLog);
                                }
                                catch(PDOException $e) {
                                    print "<div class='error'>" . $e->getMessage() . "</div>" ;
                                }

                                if ($resultLog && $resultLog->rowCount() > 0 ) {
                                    $rowLog = $resultLog->fetch();
                                }
                                elseif ($prefillAttendanceType == 'Y') {
                                    //Get any student log data
                                    try {
                                        $dataLog=array('gibbonPersonID' => $rowClassGroup['gibbonPersonID'], 'date' => $row['date'].'%');
                                        $sqlLog="SELECT * FROM gibbonAttendanceLogPerson WHERE gibbonPersonID=:gibbonPersonID AND date LIKE :date ORDER BY timestampTaken DESC" ;
                                        $resultLog=$connection2->prepare($sqlLog);
                                        $resultLog->execute($dataLog);
                                    }
                                    catch(PDOException $e) {
                                        print "<div class='error'>" . $e->getMessage() . "</div>" ;
                                    }

                                    if ($resultLog && $resultLog->rowCount() > 0 ) {
                                        $rowLog = $resultLog->fetch();
                                    }
                                }
                            }

                            //$rowLog['type'] == 'Absent' or $rowLog['type'] == 'Left - Early' or $rowLog['type'] == 'Left' or $rowLog['type'] == 'Present - Offsite'
                            if ( $attendance->isTypeAbsent($rowLog['type']) && $rowClassGroup['role'] == 'Student' ) {
                                $_SESSION[$guid]['sidebarExtra'] .= "<td style='border: 1px solid #CC0000; background-color: #F6CECB; width:20%; text-align: center; vertical-align: top'>";
                            } else {
                                $_SESSION[$guid]['sidebarExtra'] .= "<td style='border: 1px solid #rgba (1,1,1,0); width:20%; text-align: center; vertical-align: top'>";
                            }

							//Alerts, if permission allows
							if ($row['role'] == 'Teacher' and $teacher == true) {
								$_SESSION[$guid]['sidebarExtra'] .= getAlertBar($guid, $connection2, $rowClassGroup['gibbonPersonID'], $rowClassGroup['privacy'], "id='confidentialPlan$count'");
							}

							//Get photos
							$_SESSION[$guid]['sidebarExtra'] .= '<div>';
                            $_SESSION[$guid]['sidebarExtra'] .= getUserPhoto($guid, $rowClassGroup['image_240'], 75);

                            if ($row['role'] == 'Teacher' and $teacher == true) {
                                if ($rowClassGroup['role'] == 'Student') {
                                    try {
                                        $dataLike = array('gibbonPlannerEntryID' => $row['gibbonPlannerEntryID'], 'gibbonPersonID' => $rowClassGroup['gibbonPersonID']);
                                        $sqlLike = "SELECT * FROM gibbonBehaviour WHERE type='Positive' AND gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPersonID=:gibbonPersonID";
                                        $resultLike = $connection2->prepare($sqlLike);
                                        $resultLike->execute($dataLike);
                                    } catch (PDOException $e) {
                                    }

									//HEY SHORTY IT'S YOUR BIRTHDAY!
									$daysUntilNextBirthday = daysUntilNextBirthday($rowClassGroup['dob']);
                                    if ($daysUntilNextBirthday == 0) {
                                        $_SESSION[$guid]['sidebarExtra'] .= "<img title='".sprintf(__($guid, '%1$s  birthday today!'), $rowClassGroup['preferredName'].'&#39;s')."' style='margin: -24px 0 0 0; width: 25px; height: 25px' src='".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/gift_pink.png'/>";
                                    } elseif ($daysUntilNextBirthday > 0 and $daysUntilNextBirthday < 8) {
                                        $_SESSION[$guid]['sidebarExtra'] .= "<img title='$daysUntilNextBirthday day";
                                        if ($daysUntilNextBirthday != 1) {
                                            $_SESSION[$guid]['sidebarExtra'] .= 's';
                                        }
                                        $_SESSION[$guid]['sidebarExtra'] .= ' until '.$rowClassGroup['preferredName']."&#39;s birthday!' style='margin: -24px 0 0 0; width: 25px; height: 25px' src='".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/gift.png'/>";
                                    }

									//DEAL WITH LIKES
									$likesGiven = countLikesByContextAndGiver($connection2, 'Planner', 'gibbonPlannerEntryID', $gibbonPlannerEntryID, $_SESSION[$guid]['gibbonPersonID'], $rowClassGroup['gibbonPersonID']);
                                    $likeComment = addSlashes($row['course'].'.'.$row['class'].': '.$row['name']);
                                    $_SESSION[$guid]['sidebarExtra'] .= "<div id='star".$rowClassGroup['gibbonPersonID']."'>";
                                    $_SESSION[$guid]['sidebarExtra'] .= '<script type="text/javascript">';
                                    $_SESSION[$guid]['sidebarExtra'] .= '$(document).ready(function(){';
                                    $_SESSION[$guid]['sidebarExtra'] .= '$("#starAdd'.$rowClassGroup['gibbonPersonID'].'").click(function(){';
                                    $_SESSION[$guid]['sidebarExtra'] .= '$("#star'.$rowClassGroup['gibbonPersonID'].'").load("'.$_SESSION[$guid]['absoluteURL'].'/modules/Planner/planner_view_full_starAjax.php",{"gibbonPersonID": "'.$rowClassGroup['gibbonPersonID'].'", "gibbonPlannerEntryID": "'.$row['gibbonPlannerEntryID'].'", "mode": "add", "comment": "'.$likeComment.'"});';
                                    $_SESSION[$guid]['sidebarExtra'] .= '});';
                                    $_SESSION[$guid]['sidebarExtra'] .= '$("#starRemove'.$rowClassGroup['gibbonPersonID'].'").click(function(){';
                                    $_SESSION[$guid]['sidebarExtra'] .= '$("#star'.$rowClassGroup['gibbonPersonID'].'").load("'.$_SESSION[$guid]['absoluteURL'].'/modules/Planner/planner_view_full_starAjax.php",{"gibbonPersonID": "'.$rowClassGroup['gibbonPersonID'].'", "gibbonPlannerEntryID": "'.$row['gibbonPlannerEntryID'].'", "mode": "remove", "comment": "'.$likeComment.'"});';
                                    $_SESSION[$guid]['sidebarExtra'] .= '});';
                                    $_SESSION[$guid]['sidebarExtra'] .= '});';
                                    $_SESSION[$guid]['sidebarExtra'] .= '</script>';
                                    if ($likesGiven != 1) {
                                        $_SESSION[$guid]['sidebarExtra'] .= "<a id='starAdd".$rowClassGroup['gibbonPersonID']."' onclick='return false;' href='#'><img style='margin-top: -30px; margin-left: 60px' src='".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/like_off.png'></a>";
                                    } else {
                                        $_SESSION[$guid]['sidebarExtra'] .= "<a id='starRemove".$rowClassGroup['gibbonPersonID']."' onclick='return false;' href='#'><img style='margin-top: -30px; margin-left: 60px' src='".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/like_on.png'></a>";
                                    }

                                    $_SESSION[$guid]['sidebarExtra'] .= '</div>';
                                }
                            }
                            $_SESSION[$guid]['sidebarExtra'] .= '</div>';

                            if ($attendanceEnabled && $row['role'] == 'Teacher' and $teacher == true) {
                                if ($rowClassGroup['role'] == 'Student') {

                                    $_SESSION[$guid]['sidebarExtra'] .= "<input type='hidden' name='$countStudents-gibbonPersonID' value='".$rowClassGroup['gibbonPersonID']."' data-id='$countStudents'>";

                                    $_SESSION[$guid]['sidebarExtra'] .= $attendance->renderAttendanceTypeSelect( $rowLog['type'], "$countStudents-type", '86px;margin-left:1px;');

                                    // Only hide the reason and comment fields if Present is the default attendance type
                                    if ($defaultAttendanceType == 'Present' || $attendance->isTypePresent($rowLog['type'])) {
                                        $_SESSION[$guid]['sidebarExtra'] .= "<div id='$countStudents-hideReasons' style='display:none;'>";
                                    } else {
                                        $_SESSION[$guid]['sidebarExtra'] .= "<div>";
                                    }

                                    $_SESSION[$guid]['sidebarExtra'] .= $attendance->renderAttendanceReasonSelect( $rowLog['reason'], "$countStudents-reason", '84px');
                                    $_SESSION[$guid]['sidebarExtra'] .= "<input type='text' maxlength=255 name='$countStudents-comment' id='$countStudents-comment' style='float: none; width:82px; margin-bottom: 3px' value='".htmlPrep($rowLog['comment'])."'>";
                                    $_SESSION[$guid]['sidebarExtra'] .= "</div>";

                                }
                            }

                            if ($rowClassGroup['role'] == 'Student') {
                                $_SESSION[$guid]['sidebarExtra'] .= "<div style='padding-top: 5px'><b><a href='index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=".$rowClassGroup['gibbonPersonID']."'>".formatName('', $rowClassGroup['preferredName'], $rowClassGroup['surname'], 'Student').'</a></b><br/>';
                            } else {
                                $_SESSION[$guid]['sidebarExtra'] .= "<div style='padding-top: 5px'><b>".formatName($rowClassGroup['title'], $rowClassGroup['preferredName'], $rowClassGroup['surname'], 'Staff').'</b><br/>';
                            }

                            $_SESSION[$guid]['sidebarExtra'] .= '<i>'.$rowClassGroup['role'].'</i><br/><br/></div>';
                            $_SESSION[$guid]['sidebarExtra'] .= '</td>';

                            if ($count % $columns == ($columns - 1)) {
                                $_SESSION[$guid]['sidebarExtra'] .= '</tr>';
                            }

                            ++$count;
                            if ($rowClassGroup['role'] == 'Student') {
                                ++$countStudents;
                            }
                        }

                        for ($i = 0;$i < $columns - ($count % $columns);++$i) {
                            $_SESSION[$guid]['sidebarExtra'] .= "<td style='width:20%;'></td>";
                        }

                        if ($count % $columns != 0) {
                            $_SESSION[$guid]['sidebarExtra'] .= '</tr>';
                        }

                        if ($attendanceEnabled && $row['role'] == 'Teacher' and $teacher == true) {
                            $_SESSION[$guid]['sidebarExtra'] .= '<tr>';
                            $_SESSION[$guid]['sidebarExtra'] .= "<td class='right' colspan=5>";
                            $_SESSION[$guid]['sidebarExtra'] .= "<input type='hidden' name='params' value='$params'>";
                            $_SESSION[$guid]['sidebarExtra'] .= "<input type='hidden' name='gibbonCourseClassID' value='$gibbonCourseClassID'>";
                            $_SESSION[$guid]['sidebarExtra'] .= "<input type='hidden' name='gibbonPlannerEntryID' value='$gibbonPlannerEntryID'>";
                            $_SESSION[$guid]['sidebarExtra'] .= "<input type='hidden' name='currentDate' value='".$row['date']."'>";
                            $_SESSION[$guid]['sidebarExtra'] .= "<input type='hidden' name='count' value='$countStudents'>";
                            $_SESSION[$guid]['sidebarExtra'] .= "<input type='hidden' name='address' value='".$_SESSION[$guid]['address']."'>";
                            $_SESSION[$guid]['sidebarExtra'] .= "<input type='submit' value='Submit'>";
                            $_SESSION[$guid]['sidebarExtra'] .= '</td>';
                            $_SESSION[$guid]['sidebarExtra'] .= '</tr>';
                            $_SESSION[$guid]['sidebarExtra'] .= '</form>';
                        }

                        $_SESSION[$guid]['sidebarExtra'] .= '</table>';



                        //Guests
                        try {
                            $dataClassGroup = array('gibbonPlannerEntryID' => $gibbonPlannerEntryID);
                            $sqlClassGroup = "SELECT * FROM gibbonPlannerEntryGuest INNER JOIN gibbonPerson ON gibbonPlannerEntryGuest.gibbonPersonID=gibbonPerson.gibbonPersonID JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND status='Full' ORDER BY role DESC, surname, preferredName";
                            $resultClassGroup = $connection2->prepare($sqlClassGroup);
                            $resultClassGroup->execute($dataClassGroup);
                        } catch (PDOException $e) {
                            $_SESSION[$guid]['sidebarExtra'] .= "<div class='error'>".$e->getMessage().'</div>';
                        }
                        if ($resultClassGroup->rowCount() > 0) {
                            $_SESSION[$guid]['sidebarExtra'] .= "<span style='font-size: 115%; font-weight: bold; padding-top: 21px'>".__($guid, 'Guests').'<br/></span>';
                            $_SESSION[$guid]['sidebarExtra'] .= "<table class='noIntBorder' cellspacing='0' style='width:260px; float: right'>";
                            $count2 = 0;
                            $count2Students = 0;
                            while ($rowClassGroup = $resultClassGroup->fetch()) {
                                if ($count2 % $columns == 0) {
                                    $_SESSION[$guid]['sidebarExtra'] .= '<tr>';
                                }

                                $_SESSION[$guid]['sidebarExtra'] .= "<td style='border: 1px solid #ffffff; width:20%; text-align: center; vertical-align: top'>";

                                $_SESSION[$guid]['sidebarExtra'] .= getUserPhoto($guid, $rowClassGroup['image_240'], 75);

                                $_SESSION[$guid]['sidebarExtra'] .= "<div style='padding-top: 5px'><b>".formatName($rowClassGroup['title'], $rowClassGroup['preferredName'], $rowClassGroup['surname'], 'Staff').'</b><br/>';

                                $_SESSION[$guid]['sidebarExtra'] .= '<i>'.$rowClassGroup['role'].'</i><br/><br/></div>';
                                $_SESSION[$guid]['sidebarExtra'] .= '</td>';

                                if ($count2 % $columns == ($columns - 1)) {
                                    $_SESSION[$guid]['sidebarExtra'] .= '</tr>';
                                }

                                ++$count2;
                                if ($rowClassGroup['role'] == 'Student') {
                                    ++$count2Students;
                                }
                            }

                            for ($i = 0;$i < $columns - ($count2 % $columns);++$i) {
                                $_SESSION[$guid]['sidebarExtra'] .= "<td style='width:20%;'></td>";
                            }

                            if ($count2 % $columns != 0) {
                                $_SESSION[$guid]['sidebarExtra'] .= '</tr>';
                            }
                            $_SESSION[$guid]['sidebarExtra'] .= '</table>';
                        }
                        $_SESSION[$guid]['sidebarExtra'] .= '</div>';
                        ?>
						<script type="text/javascript">
							/* Confidential Control */
							$(document).ready(function(){
								$("#teachersNotes").slideUp("fast");
								$(".teachersNotes").slideUp("fast");
								<?php
                                for ($i = 0; $i < $count; ++$i) {
                                    ?>
									$("#confidentialPlan<?php echo $i ?>").css("display","none");
									<?php

                                }
                        		?>

								$(".confidentialPlan").click(function(){
									if ($('input[name=confidentialPlan]:checked').val()=="Yes" ) {
										$("#teachersNotes").slideDown("fast", $(".teachersNotes").css("{'display' : 'table-row', 'border' : 'right'}"));
										$(".teachersNotes").slideDown("fast", $("#teachersNotes").css("{'display' : 'table-row', 'border' : 'right'}"));
										<?php
                                        for ($i = 0; $i < $count; ++$i) {
                                            ?>
											$("#confidentialPlan<?php echo $i ?>").slideDown("fast", $("#confidentialPlan<?php echo $i ?>").css("{'display' : 'table-row', 'border' : 'right'}"));
											<?php
                                        }
                        			?>
                        			}
									else {
										$("#teachersNotes").slideUp("fast");
										$(".teachersNotes").slideUp("fast");
										<?php
                                        for ($i = 0; $i < $count; ++$i) {
                                            ?>
											$("#confidentialPlan<?php echo $i ?>").slideUp("fast");
											<?php

                                        }
                        			?>
                        			}
								 });
							});
						</script>
						<?php

                    }
                }
            }
        }
    }
}
?>
