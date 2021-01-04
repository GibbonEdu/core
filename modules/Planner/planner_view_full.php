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

use Gibbon\View\View;
use Gibbon\Forms\Form;
use Gibbon\FileUploader;
use Gibbon\Services\Format;
use Gibbon\Domain\Planner\PlannerEntryGateway;
use Gibbon\Domain\Timetable\CourseEnrolmentGateway;
use Gibbon\Domain\Attendance\AttendanceLogPersonGateway;
use Gibbon\Domain\Attendance\AttendanceLogCourseClassGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';
require_once __DIR__ . '/../Attendance/moduleFunctions.php';
require_once __DIR__ . '/../Attendance/src/AttendanceView.php';

if (isActionAccessible($guid, $connection2, '/modules/Planner/planner_view_full.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __('Your request failed because you do not have access to this action.');
    echo '</div>';
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        echo "<div class='error'>";
        echo __('The highest grouped action cannot be determined.');
        echo '</div>';
    } else {

        $homeworkNameSingular = getSettingByScope($connection2, 'Planner', 'homeworkNameSingular');
        $homeworkNamePlural = getSettingByScope($connection2, 'Planner', 'homeworkNamePlural');

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
            $date = $_GET['date'] ?? date('Y-m-d');
            if (isset($_GET['dateHuman'])) {
                $date = dateConvert($guid, $_GET['dateHuman']);
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
            echo __('The selected record does not exist, or you do not have access to it.');
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
                    echo __('Your request failed because some required values were not unique.');
                    echo '</div>';
                } else {
                    
                        $dataChild = array('gibbonPersonID' => $gibbonPersonID, 'gibbonPersonID2' => $_SESSION[$guid]['gibbonPersonID']);
                        $sqlChild = "SELECT * FROM gibbonFamilyChild JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonFamilyChild.gibbonPersonID=:gibbonPersonID AND gibbonFamilyAdult.gibbonPersonID=:gibbonPersonID2 AND childDataAccess='Y'";
                        $resultChild = $connection2->prepare($sqlChild);
                        $resultChild->execute($dataChild);

                    if ($resultChild->rowCount() < 1) {
                        echo "<div class='error'>";
                        echo __('The selected record does not exist, or you do not have access to it.');
                        echo '</div>';
                    } else {
                        $data = array('gibbonPersonID' => $gibbonPersonID, 'gibbonPlannerEntryID' => $gibbonPlannerEntryID, 'date' => $date, 'gibbonPersonID2' => $gibbonPersonID, 'gibbonPlannerEntryID2' => $gibbonPlannerEntryID);
                        $sql = "(SELECT gibbonPlannerEntry.gibbonPlannerEntryID, gibbonCourseClass.gibbonCourseClassID, gibbonUnitID, gibbonPlannerEntry.gibbonCourseClassID, gibbonPlannerEntry.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, date, timeStart, timeEnd, summary, gibbonPlannerEntry.description, teachersNotes, homework, homeworkDueDateTime, homeworkDetails, viewableStudents, viewableParents, role, homeworkTimeCap, homeworkSubmission, homeworkSubmissionDateOpen, homeworkSubmissionDrafts, homeworkSubmissionType, homeworkSubmissionRequired, gibbonCourseClass.attendance FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left' AND gibbonPlannerEntry.gibbonPlannerEntryID=:gibbonPlannerEntryID) UNION (SELECT gibbonPlannerEntry.gibbonPlannerEntryID, gibbonCourseClass.gibbonCourseClassID, gibbonUnitID, gibbonPlannerEntry.gibbonCourseClassID, gibbonPlannerEntry.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, date, timeStart, timeEnd, summary, gibbonPlannerEntry.description, teachersNotes, homework, homeworkDueDateTime, homeworkDetails, viewableStudents, viewableParents, role, homeworkTimeCap, homeworkSubmission, homeworkSubmissionDateOpen, homeworkSubmissionDrafts, homeworkSubmissionType, homeworkSubmissionRequired, gibbonCourseClass.attendance FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonPlannerEntryGuest ON (gibbonPlannerEntryGuest.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE date=:date AND gibbonPlannerEntryGuest.gibbonPersonID=:gibbonPersonID2 AND gibbonPlannerEntry.gibbonPlannerEntryID=:gibbonPlannerEntryID2) ORDER BY date, timeStart";
                    }
                }
            } elseif ($highestAction == 'Lesson Planner_viewMyClasses') {
                $data = array('date' => $date, 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonPlannerEntryID' => $gibbonPlannerEntryID);
                $sql = "(SELECT gibbonPlannerEntry.gibbonPlannerEntryID, gibbonCourseClass.gibbonCourseClassID, gibbonUnitID, gibbonPlannerEntry.gibbonCourseClassID, gibbonPlannerEntry.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, date, timeStart, timeEnd, summary, gibbonPlannerEntry.description, teachersNotes, homework, homeworkDueDateTime, homeworkDetails, viewableStudents, viewableParents, role, homeworkTimeCap, homeworkSubmission, homeworkSubmissionDateOpen, homeworkSubmissionDrafts, homeworkSubmissionType, homeworkSubmissionRequired, gibbonCourseClass.attendance FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left' AND gibbonPlannerEntry.gibbonPlannerEntryID=$gibbonPlannerEntryID) UNION (SELECT gibbonPlannerEntry.gibbonPlannerEntryID, gibbonCourseClass.gibbonCourseClassID, gibbonUnitID, gibbonPlannerEntry.gibbonCourseClassID, gibbonPlannerEntry.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, date, timeStart, timeEnd, summary, gibbonPlannerEntry.description, teachersNotes, homework, homeworkDueDateTime, homeworkDetails, viewableStudents, viewableParents, role, homeworkTimeCap, homeworkSubmission, homeworkSubmissionDateOpen, homeworkSubmissionDrafts, homeworkSubmissionType, homeworkSubmissionRequired, gibbonCourseClass.attendance FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonPlannerEntryGuest ON (gibbonPlannerEntryGuest.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE date=:date AND gibbonPlannerEntryGuest.gibbonPersonID=".$_SESSION[$guid]['gibbonPersonID'].' AND gibbonPlannerEntry.gibbonPlannerEntryID=:gibbonPlannerEntryID) ORDER BY date, timeStart';
            } elseif ($highestAction == 'Lesson Planner_viewOnly') {
                $sql = "SELECT gibbonCourse.gibbonCourseID, gibbonPlannerEntry.gibbonPlannerEntryID, gibbonCourseClass.gibbonCourseClassID, gibbonUnitID, gibbonPlannerEntry.gibbonCourseClassID, gibbonPlannerEntry.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, date, timeStart, timeEnd, summary, gibbonPlannerEntry.description, teachersNotes, homework, homeworkDueDateTime, homeworkDetails, viewableStudents, viewableParents, 'Other' AS role, homeworkTimeCap, homeworkSubmission, homeworkSubmissionDateOpen, homeworkSubmissionDrafts, homeworkSubmissionType, homeworkSubmissionRequired, gibbonDepartmentID, gibbonCourseClass.attendance FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonPlannerEntry.gibbonPlannerEntryID=$gibbonPlannerEntryID ORDER BY date, timeStart";
                $teacher = false;
            }
            elseif ($highestAction == 'Lesson Planner_viewEditAllClasses' or $highestAction == 'Lesson Planner_viewAllEditMyClasses'  or $highestAction == 'Lesson Planner_viewOnly') {
                $sql = "SELECT gibbonCourse.gibbonCourseID, gibbonPlannerEntry.gibbonPlannerEntryID, gibbonCourseClass.gibbonCourseClassID, gibbonUnitID, gibbonPlannerEntry.gibbonCourseClassID, gibbonPlannerEntry.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, date, timeStart, timeEnd, summary, gibbonPlannerEntry.description, teachersNotes, homework, homeworkDueDateTime, homeworkDetails, viewableStudents, viewableParents, 'Teacher' AS role, homeworkTimeCap, homeworkSubmission, homeworkSubmissionDateOpen, homeworkSubmissionDrafts, homeworkSubmissionType, homeworkSubmissionRequired, gibbonDepartmentID, gibbonCourseClass.attendance FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonPlannerEntry.gibbonPlannerEntryID=$gibbonPlannerEntryID ORDER BY date, timeStart";
                $teacher = false;
                
                    $dataTeacher = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonPlannerEntryID' => $gibbonPlannerEntryID, 'gibbonPersonID2' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonPlannerEntryID2' => $gibbonPlannerEntryID, 'date2' => $date);
                    $sqlTeacher = "(SELECT gibbonPlannerEntry.gibbonPlannerEntryID, gibbonCourseClass.gibbonCourseClassID, gibbonUnitID, gibbonPlannerEntry.gibbonCourseClassID, gibbonPlannerEntry.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, date, timeStart, timeEnd, summary, gibbonPlannerEntry.description, teachersNotes, homework, homeworkDueDateTime, homeworkDetails, viewableStudents, viewableParents, role, homeworkTimeCap, homeworkSubmission, homeworkSubmissionDateOpen, homeworkSubmissionDrafts, homeworkSubmissionType, homeworkSubmissionRequired
						FROM gibbonPlannerEntry
						JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
						JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID)
						JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID)
						WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID
							AND role='Teacher'
							AND gibbonPlannerEntry.gibbonPlannerEntryID=:gibbonPlannerEntryID)
						UNION
						(SELECT gibbonPlannerEntry.gibbonPlannerEntryID, gibbonCourseClass.gibbonCourseClassID, gibbonUnitID, gibbonPlannerEntry.gibbonCourseClassID, gibbonPlannerEntry.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, date, timeStart, timeEnd, summary, gibbonPlannerEntry.description, teachersNotes, homework, homeworkDueDateTime, homeworkDetails, viewableStudents, viewableParents, role, homeworkTimeCap, homeworkSubmission, homeworkSubmissionDateOpen, homeworkSubmissionDrafts, homeworkSubmissionType, homeworkSubmissionRequired
						FROM gibbonPlannerEntry
						JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
						JOIN gibbonPlannerEntryGuest ON (gibbonPlannerEntryGuest.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID)
						JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID)
						WHERE date=:date2 AND gibbonPlannerEntryGuest.gibbonPersonID=:gibbonPersonID2 AND gibbonPlannerEntry.gibbonPlannerEntryID=:gibbonPlannerEntryID2)
						ORDER BY date, timeStart";
                    $resultTeacher = $connection2->prepare($sqlTeacher);
                    $resultTeacher->execute($dataTeacher);
                if ($resultTeacher->rowCount() > 0) {
                    $teacher = true;
                }
            }

            if (isset($sql)) {
                
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                if ($result->rowCount() != 1) {
                    echo "<div class='warning'>";
                    echo __('The selected record does not exist, or you do not have access to it.');
                    echo '</div>';
                } else {
                    $values = $result->fetch();
                    $gibbonDepartmentID = null;
                    if (isset($values['gibbonDepartmentID'])) {
                        $gibbonDepartmentID = $values['gibbonDepartmentID'];
                    }

                    $gibbonUnitID = $values['gibbonUnitID'];

                    //Get gibbonUnitClassID
                    
                        $dataUnitClass = array('gibbonCourseClassID' => $values['gibbonCourseClassID'], 'gibbonUnitID' => $gibbonUnitID);
                        $sqlUnitClass = 'SELECT gibbonUnitClassID FROM gibbonUnitClass WHERE gibbonCourseClassID=:gibbonCourseClassID AND gibbonUnitID=:gibbonUnitID';
                        $resultUnitClass = $connection2->prepare($sqlUnitClass);
                        $resultUnitClass->execute($dataUnitClass);
                    if ($resultUnitClass->rowCount() == 1) {
                        $rowUnitClass = $resultUnitClass->fetch();
                        $gibbonUnitClassID = $rowUnitClass['gibbonUnitClassID'];
                    }

                    // target of the planner
                    $target = ($viewBy === 'class') ? $values['course'].'.'.$values['class'] : dateConvertBack($guid, $date);

                    // planner's parameters
                    $params = [];
                    $params['gibbonPlannerEntryID'] = $gibbonPlannerEntryID;
                    if ($date != '') {
                        $params['date'] = $_GET['date'] ?? '';
                    }
                    if ($viewBy != '') {
                        $params['viewBy'] = $_GET['viewBy'] ?? '';
                    }
                    if ($gibbonCourseClassID != '') {
                        $params['gibbonCourseClassID'] = $gibbonCourseClassID;
                    }
                    $params['subView'] = $subView;
                    $paramsVar = '&' . http_build_query($params); // for backward compatibile uses below (should be get rid of)

                    $roleCategory = getRoleCategory($_SESSION[$guid]['gibbonRoleIDCurrent'], $connection2);

                    $page->breadcrumbs
                        ->add(__('Planner for {classDesc}', [
                            'classDesc' => $target,
                        ]), 'planner.php', $params)
                        ->add(__('View Lesson Plan'));

                    $returns = array();
                    $returns['error6'] = __('An error occured with your submission, most likely because a submitted file was too large.');
                    $returns['error7'] = __('The specified date is in the future: it must be today or earlier.');
                    if (isset($_GET['return'])) {
                        returnProcess($guid, $_GET['return'], null, $returns);
                    }

                    if ($gibbonCourseClassID == '') {
                        $gibbonCourseClassID = $values['gibbonCourseClassID'];
                    }
                    if (($values['role'] == 'Student' and $values['viewableStudents'] == 'N') and ($highestAction == 'Lesson Planner_viewMyChildrensClasses' and $values['viewableParents'] == 'N')) {
                        echo "<div class='warning'>";
                        echo __('The selected record does not exist, or you do not have access to it.');
                        echo '</div>';
                    } else {
                        echo "<div style='height:50px'>";
                        echo '<h2>';
                        if (strlen($values['name']) <= 34) {
                            echo $values['name'].'<br/>';
                        } else {
                            echo substr($values['name'], 0, 34).'...<br/>';
                        }
                        $unit = getUnit($connection2, $values['gibbonUnitID'], $values['gibbonCourseClassID']);
                        if (isset($unit[0])) {
                            if ($unit[0] != '') {
                                if ($unit[1] != '') {
                                    echo "<div style='font-weight: normal; font-style: italic; font-size: 60%; margin-top: 0px'>$unit[1] ".__('Unit:').' '.$unit[0].'</div>';
                                    $unitType = $unit[1];
                                } else {
                                    echo "<div style='font-weight: normal; font-style: italic; font-size: 60%; margin-top: 0px'>".__('Unit:').' '.$unit[0].'</div>';
                                }
                            }
                        }
                        echo '</h2>';
                        echo "<div style='float: right; width: 35%; padding-right: 3px; margin-top: -52px'>";
                        if (strstr($values['role'], 'Guest') == false) {
                            //Links to previous and next lessons
                            echo "<p style='text-align: right; margin-top: 10px'>";
                            echo "<span style='font-size: 85%'>".__('For this class:').'</span><br/>';
                            try {
                                if ($values['role'] == 'Teacher' or $highestAction == 'Lesson Planner_viewOnly') {
                                    $dataPrevious = array('gibbonCourseClassID' => $values['gibbonCourseClassID'], 'date1' => $values['date'], 'date2' => $values['date'], 'timeStart' => $values['timeStart']);
                                    $sqlPrevious = "SELECT gibbonPlannerEntry.gibbonCourseClassID, gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, timeStart, timeEnd, viewableStudents, viewableParents, homework, 'Teacher' AS role FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonPlannerEntry.gibbonCourseClassID=:gibbonCourseClassID AND (date<:date1 OR (date=:date2 AND timeStart<:timeStart)) ORDER BY date DESC, timeStart DESC";
                                } else {
                                    if ($highestAction == 'Lesson Planner_viewMyChildrensClasses') {
                                        $dataPrevious = array('gibbonCourseClassID' => $values['gibbonCourseClassID'], 'gibbonPersonID' => $gibbonPersonID, 'date1' => $values['date'], 'date2' => $values['date'], 'timeStart' => $values['timeStart']);
                                        $sqlPrevious = "SELECT gibbonPlannerEntry.gibbonCourseClassID, gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, timeStart, timeEnd, viewableStudents, viewableParents, homework, role FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonPlannerEntry.gibbonCourseClassID=:gibbonCourseClassID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left' AND (date<:date1 OR (date=:date2 AND timeStart<:timeStart)) AND viewableParents='Y' ORDER BY date DESC, timeStart DESC";
                                    } else {
                                        $dataPrevious = array('gibbonCourseClassID' => $values['gibbonCourseClassID'], 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'], 'date1' => $values['date'], 'date2' => $values['date'], 'timeStart' => $values['timeStart']);
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
                                echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Planner/planner_view_full.php&search=$gibbonPersonID&gibbonPlannerEntryID=".$rowPrevious['gibbonPlannerEntryID']."&viewBy=$viewBy&subView=$subView&gibbonCourseClassID=".$rowPrevious['gibbonCourseClassID']."&date=$date'>".__('Previous Lesson').'</a>';
                            } else {
                                echo __('Previous Lesson');
                            }

                            echo ' | ';

                            try {
                                if ($values['role'] == 'Teacher' or $highestAction == 'Lesson Planner_viewOnly') {
                                    $dataNext = array('gibbonCourseClassID' => $values['gibbonCourseClassID'], 'date1' => $values['date'], 'date2' => $values['date'], 'timeStart' => $values['timeStart']);
                                    $sqlNext = "SELECT gibbonPlannerEntry.gibbonCourseClassID, gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, timeStart, timeEnd, viewableStudents, viewableParents, homework, 'Teacher' AS role FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonPlannerEntry.gibbonCourseClassID=:gibbonCourseClassID AND (date>:date1 OR (date=:date2 AND timeStart>:timeStart)) ORDER BY date, timeStart";
                                } else {
                                    if ($highestAction == 'Lesson Planner_viewMyChildrensClasses') {
                                        $dataNext = array('gibbonCourseClassID' => $values['gibbonCourseClassID'], 'gibbonPersonID' => $gibbonPersonID, 'date1' => $values['date'], 'date2' => $values['date'], 'timeStart' => $values['timeStart']);
                                        $sqlNext = "SELECT gibbonPlannerEntry.gibbonCourseClassID, gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, timeStart, timeEnd, viewableStudents, viewableParents, homework, role FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonPlannerEntry.gibbonCourseClassID=:gibbonCourseClassID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left' AND (date>:date1 OR (date=:date2 AND timeStart>:timeStart)) AND viewableParents='Y' ORDER BY date, timeStart";
                                    } else {
                                        $dataNext = array('gibbonCourseClassID' => $values['gibbonCourseClassID'], 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'], 'date1' => $values['date'], 'date2' => $values['date'], 'timeStart' => $values['timeStart']);
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
                                echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Planner/planner_view_full.php&search=$gibbonPersonID&gibbonPlannerEntryID=".$rowNext['gibbonPlannerEntryID']."&viewBy=$viewBy&subView=$subView&gibbonCourseClassID=".$rowNext['gibbonCourseClassID']."&date=$date'>".__('Next Lesson').'</a>';
                            } else {
                                echo __('Next Lesson');
                            }
                            echo '</p>';
                        }
                        echo '</div>';
                        echo '</div>';

                        if ($values['role'] == 'Teacher') {
                            echo "<div class='linkTop'>";
                            echo '<tr>';
                            echo '<td colspan=3>';
                            if ($values['gibbonUnitID'] != '') {
                                echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Planner/planner_unitOverview.php&viewBy=$viewBy&gibbonCourseClassID=$gibbonCourseClassID&gibbonPlannerEntryID=$gibbonPlannerEntryID&date=".$values['date']."&subView=$subView&gibbonUnitID=".$values['gibbonUnitID']."'>".__('Unit Overview').'</a> | ';
                            }
                            echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Planner/planner_edit.php&viewBy=$viewBy&gibbonCourseClassID=$gibbonCourseClassID&gibbonPlannerEntryID=$gibbonPlannerEntryID&date=".$values['date']."&subView=$subView'>".__('Edit')."<img style='margin: 0 0 -4px 3px' title='".__('Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> | ";
                            
                                $dataMarkbook = array('gibbonCourseClassID' => $values['gibbonCourseClassID'], 'gibbonPlannerEntryID' => $gibbonPlannerEntryID);
                                $sqlMarkbook = 'SELECT * FROM gibbonMarkbookColumn WHERE gibbonCourseClassID=:gibbonCourseClassID AND gibbonPlannerEntryID=:gibbonPlannerEntryID';
                                $resultMarkbook = $connection2->prepare($sqlMarkbook);
                                $resultMarkbook->execute($dataMarkbook);
                            if ($resultMarkbook->rowCount() == 1) {
                                $rowMarkbook = $resultMarkbook->fetch();
                                echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Markbook/markbook_edit_data.php&gibbonCourseClassID=$gibbonCourseClassID&gibbonMarkbookColumnID=".$rowMarkbook['gibbonMarkbookColumnID']."'>".__('Linked Markbook')."<img style='margin: 0 5px -4px 3px' title='".__('Linked Markbook')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/markbook.png'/></a> | ";
                            }
                            echo "<input type='checkbox' name='confidentialPlan' class='confidentialPlan' value='Yes' />";
                            echo "<span title='".__('Includes student data & teacher\'s notes')."' style='font-size: 85%; font-weight: normal; font-style: italic'> ".__('Show Confidential Data').'</span>';
                            echo '</td>';
                            echo '</tr>';
                            echo '</div>';
                        } else {
                            echo "<div class='linkTop'>";
                            if ($values['gibbonUnitID'] != '') {
                                echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Planner/planner_unitOverview.php&viewBy=$viewBy&gibbonCourseClassID=$gibbonCourseClassID&gibbonPlannerEntryID=$gibbonPlannerEntryID&date=".$values['date']."&subView=$subView&gibbonUnitID=".$values['gibbonUnitID']."&search=$gibbonPersonID'>".__('Unit Overview').'</a>';
                            }
                            echo '</div>';
                        }
                        echo "<table class='smallIntBorder' cellspacing='0' style='width: 100%;'>";
                        echo '<tr>';
                        echo "<td style='width: 33%; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__('Class').'</span><br/>';
                        echo $values['course'].'.'.$values['class'];
                        echo '</td>';
                        echo "<td style='width: 33%; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__('Date').'</span><br/>';
                        echo dateConvertBack($guid, $values['date']);
                        echo '</td>';
                        echo "<td style='width: 33%; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__('Time').'</span><br/>';
                        if ($values['timeStart'] != '' and $values['timeEnd'] != '') {
                            echo substr($values['timeStart'], 0, 5).'-'.substr($values['timeEnd'], 0, 5);
                        }
                        echo '</td>';
                        echo '</tr>';
                        if ($values['summary'] != '') {
                            echo '<tr>';
                            echo "<td style='padding-top: 5px; width: 33%; vertical-align: top' colspan=3>";
                            echo "<span style='font-size: 115%; font-weight: bold'>".__('Summary').'</span><br/>';
                            echo $values['summary'];
                            echo '</td>';
                            echo '</tr>';
                        }
                        echo '</table>';

                        //Lesson outcomes
                        
                            $dataOutcomes = array('gibbonPlannerEntryID' => $values['gibbonPlannerEntryID']);
                            $sqlOutcomes = "SELECT scope, name, nameShort, category, gibbonYearGroupIDList, sequenceNumber, content FROM gibbonPlannerEntryOutcome JOIN gibbonOutcome ON (gibbonPlannerEntryOutcome.gibbonOutcomeID=gibbonOutcome.gibbonOutcomeID) WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND active='Y' ORDER BY (sequenceNumber='') ASC, sequenceNumber, category, name";
                            $resultOutcomes = $connection2->prepare($sqlOutcomes);
                            $resultOutcomes->execute($dataOutcomes);

                        if ($resultOutcomes->rowCount() > 0) {
                            echo '<h2>'.__('Lesson Outcomes').'</h2>';
                            echo "<table cellspacing='0' style='width: 100%'>";
                            echo "<tr class='head'>";
                            echo '<th>';
                            echo __('Scope');
                            echo '</th>';
                            echo '<th>';
                            echo __('Category');
                            echo '</th>';
                            echo '<th>';
                            echo __('Name');
                            echo '</th>';
                            echo '<th>';
                            echo __('Year Groups');
                            echo '</th>';
                            echo '<th>';
                            echo __('Actions');
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
                                    
                                        $dataLearningArea = array('gibbonDepartmentID' => $gibbonDepartmentID);
                                        $sqlLearningArea = 'SELECT * FROM gibbonDepartment WHERE gibbonDepartmentID=:gibbonDepartmentID';
                                        $resultLearningArea = $connection2->prepare($sqlLearningArea);
                                        $resultLearningArea->execute($dataLearningArea);
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
                                    echo "<a title='".__('View Description')."' class='show_hide-$count' onclick='false' href='#'><img style='padding-left: 0px' src='".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/page_down.png' alt='".__('Show Comment')."' onclick='return false;' /></a>";
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

                        //Get Smart Blocks
                        $dataBlocks = array('gibbonPlannerEntryID' => $gibbonPlannerEntryID);
                        $sqlBlocks = "SELECT * FROM gibbonUnitClassBlock WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID ORDER BY sequenceNumber";
                        $blocks = $pdo->select($sqlBlocks, $dataBlocks)->fetchAll();

                        // LESSON CONTENTS
                        $form = Form::create('smartBlockCompletion', $gibbon->session->get('absoluteURL').'/modules/Planner/planner_view_full_smartProcess.php');
                        $form->setClass('blank');

                        $form->setTitle(__('Lesson Content'));
                        $description = '';
                        if (!empty($values['description'])) {
                            $description = '<div class="unit-block rounded p-8 mb-4 border bg-gray-100 text-gray-700">'.$values['description'].'</div>';
                        }

                        if (empty($blocks) and empty($values['description'])) {
                            $description = Format::alert(__('This lesson has not had any content assigned to it.'));
                        }

                        if (!empty($values['teachersNotes']) and ($highestAction == 'Lesson Planner_viewAllEditMyClasses' or $highestAction == 'Lesson Planner_viewEditAllClasses') and ($values['role'] == 'Teacher' or $values['role'] == 'Assistant' or $values['role'] == 'Technician')) {
                            $description .= '<div id="teachersNotes" class="unit-block rounded p-8 mb-4 border bg-red-200 text-gray-700"><h3 class="m-0">'.__('Teacher\'s Notes').'</h3>'.$values['teachersNotes'].'</div>';
                        }

                        $form->setDescription($description);

                        if (!empty($blocks)) {
                            $form->addHiddenValue('address', $gibbon->session->get('address'));
                            $form->addHiddenValue('gibbonPlannerEntryID', $gibbonPlannerEntryID);
                            $form->addHiddenValue('date', $values['date']);
                            $form->addHiddenValue('mode', 'view');
                            $form->addHiddenValues($params);

                            if ($values['role'] == 'Teacher' and $teacher == true) {
                                $form->addHeaderAction('blocks', __m('Edit Blocks'))
                                    ->setURL('/modules/Planner/planner_edit.php', '#SmartBlocks')
                                    ->addParam('viewBy', $viewBy)
                                    ->addParam('gibbonCourseClassID', $gibbonCourseClassID)
                                    ->addParam('gibbonPlannerEntryID', $gibbonPlannerEntryID)
                                    ->addParam('date', $values['date'])
                                    ->addParam('subView', $subView)
                                    ->displayLabel()
                                    ->prepend(__('Smart Blocks').': ')
                                    ->append(' | ');

                                $form->addHeaderAction('unit', __m('Edit Unit'))
                                    ->setURL('/modules/Planner/units_edit_working.php')
                                    ->addParam('viewBy', $viewBy)
                                    ->addParam('gibbonCourseClassID', $gibbonCourseClassID)
                                    ->addParam('gibbonCourseID', $values['gibbonCourseID'])
                                    ->addParam('gibbonUnitID', $values['gibbonUnitID'])
                                    ->addParam('gibbonUnitClassID', $gibbonUnitClassID)
                                    ->addParam('gibbonSchoolYearID', $gibbon->session->get('gibbonSchoolYearID'))
                                    ->addParam('subView', $subView)
                                    ->displayLabel();
                            }

                            $templateView = $container->get(View::class);
                            $blockCount = 0;

                            foreach ($blocks as $block) {
                                $blockContent = $templateView->fetchFromTemplate('ui/unitBlock.twig.html', $block + [
                                    'roleCategory' => $roleCategory, 'gibbonPersonID' => $_SESSION[$guid]['username'] ?? '', 'blockCount' => $blockCount, 'checked' => ($block['complete'] == 'Y' ? 'checked' : ''), 'role' => $values['role'], 'teacher' => $values['role'] == 'Teacher' and $teacher == true ?? ''
                                ]);

                                $form->addRow()->addContent($blockContent);
                                $blockCount++;
                            }

                            if ($values['role'] == 'Teacher' and $teacher == true) {
                                $row = $form->addRow()->addSubmit();
                            }
                        }

                        echo $form->getOutput();

                        echo "<h2 style='padding-top: 30px'>".__($homeworkNamePlural).'</h2>';

                        echo "<table class='smallIntBorder' cellspacing='0' style='width: 100%;'>";
                        if ($values['role'] == 'Student') {
                            echo "<tr class='break'>";
                            echo "<td style='padding-top: 5px; width: 33%; vertical-align: top' colspan=3>";
                            echo '<h3>'.__('Teacher Recorded {homeworkName}', ['homeworkName' => __($homeworkNameSingular)]).'</h3>';
                            echo '</td>';
                            echo '</tr>';
                        }
                        echo '<tr>';
                        echo "<td style='padding-top: 5px; width: 33%; vertical-align: top' colspan=3>";
                        if ($values['homework'] == 'Y') {

                            if ($values['role'] == 'Student' && !empty($values['homeworkTimeCap'])) {
                                echo Format::alert(__('Your teacher has indicated a <b><u>{timeCap} minute</u></b> time cap for this work. Aim to spend no more than {timeCap} minutes on this {homeworkName} and let your teacher know if you were unable to complete it within this time frame.', ['timeCap' => $values['homeworkTimeCap'], 'homeworkName' => mb_strtolower(__($homeworkNameSingular))]), 'message');
                            }

                            echo "<span style='font-weight: bold; color: #CC0000'>".sprintf(__('Due on %1$s at %2$s.'), dateConvertBack($guid, substr($values['homeworkDueDateTime'], 0, 10)), substr($values['homeworkDueDateTime'], 11, 5)).'</span><br/>';
                            echo $values['homeworkDetails'].'<br/>';
                            if ($values['homeworkSubmission'] == 'Y') {
                                if ($values['role'] == 'Student' and ($highestAction == 'Lesson Planner_viewMyClasses' or $highestAction == 'Lesson Planner_viewAllEditMyClasses')) {
                                    echo "<span style='font-size: 115%; font-weight: bold'>".__('Online Submission').'</span><br/>';
                                    echo '<i>'.__('Online submission is {required} for this {homeworkName}.', ['homeworkName' => mb_strtolower(__($homeworkNameSingular)), 'required' => '<b>'.strtolower($values['homeworkSubmissionRequired']).'</b>']).'</i><br/>';
                                    if (date('Y-m-d') < $values['homeworkSubmissionDateOpen']) {
                                        echo '<i>Submission opens on '.Format::date($values['homeworkSubmissionDateOpen']).'</i>';
                                    } else {
                                        //Check previous submissions!
										
											$dataVersion = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonPlannerEntryID' => $values['gibbonPlannerEntryID']);
											$sqlVersion = 'SELECT * FROM gibbonPlannerEntryHomework WHERE gibbonPersonID=:gibbonPersonID AND gibbonPlannerEntryID=:gibbonPlannerEntryID ORDER BY count';
											$resultVersion = $connection2->prepare($sqlVersion);
											$resultVersion->execute($dataVersion);

                                        $latestVersion = '';
                                        $count = 0;
                                        $rowNum = 'odd';
                                        if ($resultVersion->rowCount() > 0) {
                                            ?>
											<table cellspacing='0' style="width: 100%">
												<tr class='head'>
													<th>
														<?php echo __('Count') ?><br/>
													</th>
													<th>
														<?php echo __('Version') ?><br/>
													</th>
													<th>
														<?php echo __('Status') ?><br/>
													</th>
													<th>
														<?php echo __('Date/Time') ?><br/>
													</th>
													<th>
														<?php echo __('View') ?><br/>
													</td>
													<?php
													if (date('Y-m-d H:i:s') < $values['homeworkDueDateTime']) {
														echo '<th>';
														echo __('Actions').'<br/>';
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
														if (date('Y-m-d H:i:s') < $values['homeworkDueDateTime']) {
															echo '<td>';
															echo "<a onclick='return confirm(\"".__('Are you sure you wish to delete this record?')."\")' href='".$_SESSION[$guid]['absoluteURL']."/modules/Planner/planner_view_full_submit_studentDeleteProcess.php?gibbonPlannerEntryID=$gibbonPlannerEntryID&viewBy=$viewBy&subView=$subView&gibbonCourseClassID=$gibbonCourseClassID&date=$date&width=1000&height=550&search=$gibbonPersonID&gibbonPlannerEntryHomeworkID=".$rowVersion['gibbonPlannerEntryHomeworkID']."'><img title='".__('Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a><br/>";
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
                                            if (date('Y-m-d H:i:s') > $values['homeworkDueDateTime']) {
                                                echo "<span style='color: #C00; font-stlye: italic'>".__('The due date has passed. Your work will be marked as late.').'</span><br/>';
                                                $status = 'Late';
                                            }

                                            // SUBMIT HOMEWORK - Teacher Recorded
                                            $form = Form::create('homeworkTeacher', $gibbon->session->get('absoluteURL').'/modules/Planner/planner_view_full_submitProcess.php?address='.$_GET['q'].$paramsVar.'&gibbonPlannerEntryID='.$values['gibbonPlannerEntryID']);

                                            $form->addHiddenValue('address', $gibbon->session->get('address'));
                                            $form->addHiddenValue('lesson', $values['name']);
                                            $form->addHiddenValue('count', $count);
                                            $form->addHiddenValue('status', $status);
                                            $form->addHiddenValue('gibbonPlannerEntryID', $gibbonPlannerEntryID);
                                            $form->addHiddenValue('currentDate', $values['date']);

                                            $row = $form->addRow();
                                                $row->addLabel('type', __('Type'));

                                            if ($values['homeworkSubmissionType'] == 'Link') {
                                                $row->addTextField('type')->readonly()->required()->setValue('Link');
                                            } elseif ($values['homeworkSubmissionType'] == 'File') {
                                                $row->addTextField('type')->readonly()->required()->setValue('File');
                                            } else {
                                                $types = ['Link' => __('Link'), 'File' => __('File')];
                                                $row->addRadio('type')->fromArray($types)->inline()->required()->checked('Link');

                                                $form->toggleVisibilityByClass('submitFile')->onRadio('type')->when('File');
                                                $form->toggleVisibilityByClass('submitLink')->onRadio('type')->when('Link');
                                            }

                                            if ($values['homeworkSubmissionDrafts'] > 0 and $status != 'Late' and $resultVersion->rowCount() < $values['homeworkSubmissionDrafts']) {
                                                $versions = ['Draft' => __('Draft'), 'Final' => __('Final')];
                                            } else {
                                                $versions = ['Final' => __('Final')];
                                            }

                                            $row = $form->addRow();
                                                $row->addLabel('version', __('Version'));
                                                $row->addSelect('version')->fromArray($versions)->required();

                                            // File
                                            if ($values['homeworkSubmissionType'] != 'Link') {
                                                $fileUploader = $container->get(FileUploader::class);
                                                $row = $form->addRow()->addClass('submitFile');
                                                    $row->addLabel('file', __('Submit File'));
                                                    $row->addFileUpload('file')->accepts($fileUploader->getFileExtensions())->required();
                                            }

                                            // Link
                                            if ($values['homeworkSubmissionType'] != 'File') {
                                                $row = $form->addRow()->addClass('submitLink');
                                                    $row->addLabel('link', __('Submit Link'));
                                                    $row->addURL('link')->maxLength(255)->required();
                                            }


                                            $row = $form->addRow();
                                                $row->addFooter();
                                                $row->addSubmit();

                                            echo $form->getOutput();
                                        }
                                    }
                                } elseif ($values['role'] == 'Student' and $highestAction == 'Lesson Planner_viewMyChildrensClasses') {
                                    echo "<span style='font-size: 115%; font-weight: bold'>Online Submission</span><br/>";
                                    echo '<i>'.__('Online submission is {required} for this {homeworkName}.', ['homeworkName' => mb_strtolower(__($homeworkNameSingular)), 'required' => '<b>'.strtolower($values['homeworkSubmissionRequired']).'</b>']).'</i><br/>';
                                    if (date('Y-m-d') < $values['homeworkSubmissionDateOpen']) {
                                        echo '<i>Submission opens on '.dateConvertBack($guid, $values['homeworkSubmissionDateOpen']).'</i>';
                                    } else {
                                        //Check previous submissions!
										
											$dataVersion = array('gibbonPersonID' => $gibbonPersonID, 'gibbonPlannerEntryID' => $values['gibbonPlannerEntryID']);
											$sqlVersion = 'SELECT * FROM gibbonPlannerEntryHomework WHERE gibbonPersonID=:gibbonPersonID AND gibbonPlannerEntryID=:gibbonPlannerEntryID';
											$resultVersion = $connection2->prepare($sqlVersion);
											$resultVersion->execute($dataVersion);
                                        $latestVersion = '';
                                        $count = 0;
                                        $rowNum = 'odd';
                                        if ($resultVersion->rowCount() < 1) {
                                            if (date('Y-m-d H:i:s') > $values['homeworkDueDateTime']) {
                                                echo "<span style='color: #C00; font-stlye: italic'>".__('The due date has passed, and no work has been submitted.').'</span><br/>';
                                            }
                                        } else {
                                            ?>
											<table cellspacing='0' style="width: 100%">
												<tr class='head'>
													<th>
														<?php echo __('Count') ?><br/>
													</th>
													<th>
														<?php echo __('Version') ?><br/>
													</th>
													<th>
														<?php echo __('Status') ?><br/>
													</th>
													<th>
														<?php echo __('Date/Time') ?><br/>
													</th>
													<th>
														<?php echo __('View') ?><br/>
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
                                } elseif ($values['role'] == 'Teacher') {
                                    echo "<span style='font-size: 115%; font-weight: bold'>".__('Online Submission').'</span><br/>';
                                    echo '<i>'.__('Online submission is {required} for this {homeworkName}.', ['homeworkName' => mb_strtolower(__($homeworkNameSingular)), 'required' => '<b>'.strtolower($values['homeworkSubmissionRequired']).'</b>']).'</i><br/>';

                                    if ($teacher == true) {
                                        //List submissions
										
											$dataClass = array('gibbonCourseClassID' => $values['gibbonCourseClassID']);
											$sqlClass = "SELECT * FROM gibbonCourseClassPerson INNER JOIN gibbonPerson ON gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID WHERE gibbonCourseClassID=:gibbonCourseClassID AND status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND role='Student' ORDER BY role DESC, surname, preferredName";
											$resultClass = $connection2->prepare($sqlClass);
											$resultClass->execute($dataClass);
                                        $count = 0;
                                        $rowNum = 'odd';
                                        if ($resultClass->rowCount() > 0) {
                                            ?>
											<table cellspacing='0' style="width: 100%">
												<tr class='head'>
													<th>
														<?php echo __('Student') ?><br/>
													</th>
													<th>
														<?php echo __('Status') ?><br/>
													</th>
													<th>
														<?php echo __('Version') ?><br/>
													</th>
													<th>
														<?php echo __('Date/Time') ?><br/>
													</th>
													<th>
														<?php echo __('View') ?><br/>
													</th>
													<th>
														<?php echo __('Action') ?><br/>
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
															<?php echo "<a href='index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=".$rowClass['gibbonPersonID']."'>".Format::name('', $rowClass['preferredName'], $rowClass['surname'], 'Student', true).'</a>' ?><br/>
														</td>

														<?php

														
															$dataVersion = array('gibbonPlannerEntryID' => $values['gibbonPlannerEntryID'], 'gibbonPersonID' => $rowClass['gibbonPersonID']);
															$sqlVersion = 'SELECT * FROM gibbonPlannerEntryHomework WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPersonID=:gibbonPersonID ORDER BY count DESC';
															$resultVersion = $connection2->prepare($sqlVersion);
															$resultVersion->execute($dataVersion);
													if ($resultVersion->rowCount() < 1) {
														?>
															<td colspan=4>
																<?php
																//Before deadline
																if (date('Y-m-d H:i:s') < $values['homeworkDueDateTime']) {
																	echo 'Pending';
																}
																//After
																else {
																	if ($rowClass['dateStart'] > $values['date']) {
																		echo "<span title='".__('Student joined school after lesson was taught.')."' style='color: #000; font-weight: normal; border: 2px none #ff0000; padding: 2px 4px'>".__('NA').'</span>';
																	} else {
																		if ($values['homeworkSubmissionRequired'] == 'Required') {
																			echo "<span style='color: #ff0000; font-weight: bold; border: 2px solid #ff0000; padding: 2px 4px'>".__('Incomplete').'</span>';
																		} else {
																			echo __('Not submitted online');
																		}
																	}
																}
														?>
															</td>
															<td>
																<?php
																echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Planner/planner_view_full_submit_edit.php&gibbonPlannerEntryID=$gibbonPlannerEntryID&viewBy=$viewBy&subView=$subView&gibbonCourseClassID=$gibbonCourseClassID&date=$date&search=".$gibbonPersonID.'&gibbonPersonID='.$rowClass['gibbonPersonID']."&submission=false'><img title='".__('Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
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
																echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Planner/planner_view_full_submit_edit.php&gibbonPlannerEntryID=$gibbonPlannerEntryID&viewBy=$viewBy&subView=$subView&gibbonCourseClassID=$gibbonCourseClassID&date=$date&width=1000&height=550&search=".$gibbonPersonID.'&gibbonPlannerEntryHomeworkID='.$rowVersion['gibbonPlannerEntryHomeworkID']."&submission=true'><img title='".__('Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
														echo "<a onclick='return confirm(\"".__('Are you sure you wish to delete this record?')."\")' href='".$_SESSION[$guid]['absoluteURL']."/modules/Planner/planner_view_full_submit_deleteProcess.php?gibbonPlannerEntryID=$gibbonPlannerEntryID&viewBy=$viewBy&subView=$subView&gibbonCourseClassID=$gibbonCourseClassID&date=$date&width=1000&height=550&search=$gibbonPersonID&gibbonPlannerEntryHomeworkID=".$rowVersion['gibbonPlannerEntryHomeworkID']."'><img title='".__('Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a>";
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
                        } elseif ($values['homework'] == 'N') {
                            echo __('No').'<br/>';
                        }
                        echo '</td>';
                        echo '</tr>';

                        if ($values['role'] == 'Student') { //MY HOMEWORK
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
                            echo '<h3>'.__('Student Recorded {homeworkName}', ['homeworkName' => __($homeworkNameSingular)]).'</h3>';
                            if ($roleCategory == 'Student') {
                                echo '<p>'.__('You can use this section to record your own {homeworkName}.', ['homeworkName' => mb_strtolower(__($homeworkNamePlural))]).'</p>';
                            }
                            echo '</td>';
                            echo '</tr>';
                            if ($myHomeworkFail or $resultMyHomework->rowCount() > 1) {
                                echo "<div class='error'>";
                                echo __('Your request failed due to a database error.');
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
											<b><?php echo __('Add {homeworkName}?', ['homeworkName' => __($homeworkNameSingular)]) ?> *</b><br/>
										</td>
										<td>
											<?php
											if ($rowMyHomework['homework'] == 'Y') {
												echo __('Yes');
											} else {
												echo __('No');
											}
										?>
										</td>
									</tr>

									<?php
									if ($rowMyHomework['homework'] == 'Y') {
										?>
										<tr>
											<td>
												<b><?php echo __('{homeworkName} Due Date', ['homeworkName' => __($homeworkNameSingular)]) ?> *</b><br/>
											</td>
											<td>
												<?php if ($rowMyHomework['homework'] == 'Y') { echo dateConvertBack($guid, substr($rowMyHomework['homeworkDueDateTime'], 0, 10)); } ?>
											</td>
										</tr>
										<tr >
											<td>
												<b><?php echo __('{homeworkName} Due Date Time', ['homeworkName' => __($homeworkNameSingular)]) ?></b><br/>
												<span class="emphasis small"><?php echo __('Format: hh:mm (24hr)') ?><br/></span>
											</td>
											<td >
												<?php if ($rowMyHomework['homework'] == 'Y') { echo substr($rowMyHomework['homeworkDueDateTime'], 11, 5); } ?>
											</td>
										</tr>
										<tr>
											<td>
												<b><?php echo __('{homeworkName} Details', ['homeworkName' => __($homeworkNameSingular)]) ?></b><br/>
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
									if ($rowMyHomework['homework'] == 'N' and $values['date'] != '' and $values['timeStart'] != '' and $values['timeEnd'] != '') {
										//Get $_GET values
										$homeworkDueDate = '';
										$homeworkDueDateTime = '';

										
											$dataNext = array('gibbonCourseClassID' => $values['gibbonCourseClassID'], 'date' => $values['date']);
											$sqlNext = 'SELECT timeStart, timeEnd, date FROM gibbonTTDayRowClass JOIN gibbonTTColumnRow ON (gibbonTTDayRowClass.gibbonTTColumnRowID=gibbonTTColumnRow.gibbonTTColumnRowID) JOIN gibbonTTColumn ON (gibbonTTColumnRow.gibbonTTColumnID=gibbonTTColumn.gibbonTTColumnID) JOIN gibbonTTDay ON (gibbonTTDayRowClass.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) JOIN gibbonTTDayDate ON (gibbonTTDayDate.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) WHERE gibbonCourseClassID=:gibbonCourseClassID AND date>:date ORDER BY date, timeStart LIMIT 0, 10';
											$resultNext = $connection2->prepare($sqlNext);
											$resultNext->execute($dataNext);
										if ($resultNext->rowCount() > 0) {
											$rowNext = $resultNext->fetch();
											$homeworkDueDate = $rowNext['date'];
											$homeworkDueDateTime = $rowNext['timeStart'];
										}
									}

                                // SUBMIT HOMEWORK - Student Recorded
                                $form = Form::create('homeworkStudent', $gibbon->session->get('absoluteURL')."/modules/Planner/planner_view_full_myHomeworkProcess.php?gibbonPlannerEntryID=$gibbonPlannerEntryID&viewBy=$viewBy&subView=$subView&address=".$gibbon->session->get('address')."&gibbonCourseClassID=$gibbonCourseClassID&date=$date");

                                $form->addHiddenValue('address', $gibbon->session->get('address'));
                                $form->addHiddenValue('gibbonPlannerEntryID', $gibbonPlannerEntryID);

                                $row = $form->addRow();
                                    $row->addLabel('homework', __($homeworkNameSingular));
                                    $row->addYesNoRadio('homework')->checked($rowMyHomework['homework'] ?? 'N');

                                $form->toggleVisibilityByClass('showHomework')->onRadio('homework')->when('Y');

                                if (!empty($rowMyHomework['homeworkDueDateTime'])) {
                                    $homeworkDueDate = substr($rowMyHomework['homeworkDueDateTime'], 0, 10);
                                    $homeworkDueDateTime = substr($rowMyHomework['homeworkDueDateTime'], 11, 5);
                                }

                                $row = $form->addRow()->addClass('showHomework');
                                    $row->addLabel('homeworkDueDate', __('{homeworkName} Due Date', ['homeworkName' => __($homeworkNameSingular)]));
                                    $col = $row->addColumn('homeworkDueDate');
                                    $col->addDate('homeworkDueDate')
                                        ->addClass('mr-2')
                                        ->required()
                                        ->setValue(!empty($homeworkDueDate) ? Format::date($homeworkDueDate) : '');
                                    $col->addTime('homeworkDueDateTime')
                                        ->setValue(!empty($homeworkDueDateTime) ? substr($homeworkDueDateTime, 0, 5) : '');

                                $col = $form->addRow()->addClass('showHomework')->addColumn();
                                    $col->addLabel('homeworkDetails', __('{homeworkName} Details', ['homeworkName' => __($homeworkNameSingular)]));
                                    $col->addEditor('homeworkDetails', $guid)->setRows(15)->showMedia()->required()->setValue($rowMyHomework['homeworkDetails'] ?? '');

                                $row = $form->addRow();
                                    $row->addFooter();
                                    $row->addSubmit();

                                echo '<tr><td colspan="3">';
                                echo $form->getOutput();
                                echo '</td></tr>';

                                }
                            }
                        }
                        echo '</table>';

                        if ($highestAction != 'Lesson Planner_viewOnly') {

                          echo "<a name='chat'></a>";
                          echo "<h2 style='padding-top: 30px'>".__('Chat').'</h2>';
                          echo "<table class='smallIntBorder' cellspacing='0' style='width: 100%;'>";
                          echo '<tr>';
                          echo "<td style='text-align: justify; padding-top: 5px; width: 33%; vertical-align: top; max-width: 752px!important;' colspan=3>";

                              echo "<div style='margin: 0px' class='linkTop'>";
                              echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/planner_view_full.php$paramsVar#chat'>".__('Refresh')."<img style='margin-left: 5px' title='".__('Refresh')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/refresh.png'/></a> <a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Planner/planner_view_full_post.php&gibbonPlannerEntryID=$gibbonPlannerEntryID&viewBy=$viewBy&subView=$subView&gibbonCourseClassID=$gibbonCourseClassID&date=$date&search=".$gibbonPersonID."'>".__('Add')."<img style='margin-left: 5px' title='".__('Add')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new.png'/></a> ";
                              echo '</div>';

                              //Get discussion
                              echo getThread($guid, $connection2, $gibbonPlannerEntryID, null, 0, null, $viewBy, $subView, $date, @$class, $gibbonCourseClassID, $gibbonPersonID, $values['role']);

                          echo '</td>';
                          echo '</tr>';
                        }
                        echo '</table>';

                        //Participants & Attendance
                        $gibbonCourseClassID = $values['gibbonCourseClassID'];
                        $columns = 2;

                        $highestAction = getHighestGroupedAction($guid, '/modules/Students/student_view_details.php', $connection2);

                        $canAccessProfile = ($highestAction == 'View Student Profile_brief' || $highestAction == 'View Student Profile_full' || $highestAction == 'View Student Profile_fullNoNotes' || $highestAction == 'View Student Profile_fullEditAllNotes') ;

                        // Only show certain options if Class Attendance is Enabled school-wide, and for this particular class
                        $attendanceEnabled = $values['attendance'] == 'Y';
                        $canTakeAttendance = $attendanceEnabled && isActionAccessible($guid, $connection2, "/modules/Attendance/attendance_take_byCourseClass.php");

                        // Get attendance pre-fill and default settings
                        $defaultAttendanceType = getSettingByScope($connection2, 'Attendance', 'defaultClassAttendanceType');
                        $crossFillClasses = getSettingByScope($connection2, 'Attendance', 'crossFillClasses');

                        $attendance = new Gibbon\Module\Attendance\AttendanceView($gibbon, $pdo);
                        $attendanceGateway = $container->get(AttendanceLogPersonGateway::class);

                        $participants = $container->get(CourseEnrolmentGateway::class)->selectClassParticipantsByDate($gibbonCourseClassID, $values['date'])->fetchAll();
                        $defaults = ['type' => $defaultAttendanceType, 'reason' => '', 'comment' => '', 'context' => '', 'prefill' => 'Y'];

                        // Build attendance data
                        foreach ($participants as $key => $student) {
                            if ($student['role'] != 'Student') continue;

                            $result = $attendanceGateway->selectClassAttendanceLogsByPersonAndDate($gibbonCourseClassID, $student['gibbonPersonID'], $values['date']);

                            $log = ($result->rowCount() > 0) ? $result->fetch() : $defaults;
                            $log['prefilled'] = $result->rowCount() > 0 ? $log['context'] : '';

                            //Check for school prefill if attendance not taken in this class
                            if ($result->rowCount() == 0) {
                                $result = $attendanceGateway->selectAttendanceLogsByPersonAndDate($student['gibbonPersonID'], $values['date'], $crossFillClasses);

                                $log = ($result->rowCount() > 0) ? $result->fetch() : $log;
                                $log['prefilled'] = $result->rowCount() > 0 ? $log['context'] : '';

                                if ($log['prefill'] == 'N') {
                                    $log = $defaults;
                                }
                            }

                            $participants[$key]['cellHighlight'] = '';
                            if ($attendance->isTypeAbsent($log['type'])) {
                                $participants[$key]['cellHighlight'] = 'bg-red-200';
                            } elseif ($attendance->isTypeOffsite($log['type'])) {
                                $participants[$key]['cellHighlight'] = 'bg-blue-200';
                            } elseif ($attendance->isTypeLate($log['type'])) {
                                $participants[$key]['cellHighlight'] = 'bg-orange-200';
                            }

                            $participants[$key]['log'] = $log;
                        }

                        // ATTENDANCE FORM
                        $form = Form::create('attendanceByClass', $_SESSION[$guid]['absoluteURL'] . '/modules/Attendance/attendance_take_byCourseClassProcess.php');
                        $form->setClass('noIntBorder fullWidth');
                        $form->setAutocomplete('off');
                        $form->setTitle($attendanceEnabled ? __('Participants & Attendance') : __('Participants'));

                        // Display the dated this attendance was taken, if any
                        if ($canTakeAttendance) {
                            $classLogs = $container->get(AttendanceLogCourseClassGateway::class)->selectClassAttendanceLogsByDate($gibbonCourseClassID, $values['date'])->fetchAll();
                            if (empty($classLogs)) {
                                $form->setDescription(Format::alert(__('Attendance has not been taken. The entries below are a best-guess, not actual data.')));
                            } else {
                                $logText = '<ul class="ml-4">';
                                foreach ($classLogs as $log) {
                                    $linkText = Format::time($log['timestampTaken']).' '.Format::date($log['date']).' '.__('by').' '.Format::name('', $log['preferredName'], $log['surname'], 'Student', true);

                                    $logText .= '<li>'.Format::link('./index.php?q=/modules/Attendance/attendance_take_byCourseClass.php&gibbonCourseClassID='.$gibbonCourseClassID.'&currentDate='.Format::date($log['date']), $linkText, ['style' => 'color: inherit']).'</li>';

                                }
                                $logText .= '</ul>';
                                $form->setDescription(Format::alert(__('Attendance has been taken at the following times for this lesson:').$logText, 'success'));
                            }
                        }

                        $grid = $form->addRow()->addGrid('attendance')->setClass('-mx-3 -my-2')->setBreakpoints('w-1/2');

                        // Display attendance grid
                        $count = 0;

                        foreach ($participants as $person) {
                            $form->addHiddenValue($count . '-gibbonPersonID', $person['gibbonPersonID']);
                            $form->addHiddenValue($count . '-prefilled', $person['log']['prefilled'] ?? '');

                            $cell = $grid->addCell()
                                ->setClass('text-center py-4 px-1 -mr-px -mb-px flex flex-col justify-start')
                                ->addClass($person['cellHighlight'] ?? '');

                            // Display alerts and birthdays, teacher only
                            if ($person['role'] == 'Student' && $values['role'] == 'Teacher' && $teacher == true) {
                                $alert = getAlertBar($guid, $connection2, $person['gibbonPersonID'], $person['privacy'], "id='confidentialPlan$count'");
                            }

                            $canViewStudents = isActionAccessible($guid, $connection2, '/modules/Students/student_view_details.php', 'View Student Profile_brief')
                                || ($highestAction == 'View Student Profile_full' || $highestAction == 'View Student Profile_fullNoNotes' || $highestAction == 'View Student Profile_fullEditAllNotes');
                            if ($person['role'] == 'Student' && $canViewStudents) {
                                $icon = Format::userBirthdayIcon($person['dob'], $person['preferredName']);
                            }

                            // Display a photo per user
                            $cell->addContent(Format::userPhoto($person['image_240'], 75, ''))
                                ->setClass('relative')
                                ->prepend($alert ?? '')
                                ->append($icon ?? '');

                            if ($person['role'] == 'Student') {
                                // Add attendance fields, teacher only
                                if ($canTakeAttendance) {
                                    $form->toggleVisibilityByClass($count.'-attendance')->onSelect($count . '-type')->whenNot('Present');
                                    $cell->addSelect($count . '-type')
                                        ->fromArray(array_keys($attendance->getAttendanceTypes()))
                                        ->selected($person['log']['type'] ?? '')
                                        ->setClass('mx-auto float-none w-24 text-xs p-0 m-0 mb-px');
                                    $cell->addSelect($count . '-reason')
                                        ->fromArray($attendance->getAttendanceReasons())
                                        ->selected($person['log']['reason'] ?? '')
                                        ->setClass($count.'-attendance mx-auto float-none w-24 text-xs p-0 m-0 mb-px');
                                    $cell->addTextField($count . '-comment')
                                        ->maxLength(255)
                                        ->setValue($person['log']['comment'] ?? '')
                                        ->setClass($count.'-attendance mx-auto float-none w-24 text-xs p-0 m-0');
                                }

                                // Display a student profile link if this user has access
                                if (($values['role'] == 'Teacher' && $teacher == true) || $canAccessProfile) {
                                    $cell->addWebLink(Format::name('', $person['preferredName'], $person['surname'], 'Student', false))
                                        ->setURL('index.php?q=/modules/Students/student_view_details.php')
                                        ->addParam('gibbonPersonID', $person['gibbonPersonID'])
                                        ->setClass('font-bold underline mt-1');
                                } else {
                                    $cell->addContent(Format::name('', $person['preferredName'], $person['surname'], 'Student', false))->wrap('<b>', '</b>');
                                }

                                $count++;
                            } else {
                                $cell->addContent(Format::name('', $person['preferredName'], $person['surname'], 'Staff', false))->wrap('<b>', '</b>');
                            }

                            $cell->addContent($person['role']);
                        }

                        if ($canTakeAttendance) {
                            $form->addHiddenValue('address', $gibbon->session->get('address'));
                            $form->addHiddenValue('gibbonCourseClassID', $gibbonCourseClassID);
                            $form->addHiddenValue('gibbonPlannerEntryID', $gibbonPlannerEntryID);
                            $form->addHiddenValue('currentDate', $values['date']);
                            $form->addHiddenValue('count', $count);
                            $form->addHiddenValues($params);

                            $form->addRow()->addSubmit();
                        }

                        $page->addSidebarExtra($form->getOutput());


                        // GUESTS
                        $guests = $container->get(PlannerEntryGateway::class)->selectPlannerGuests($gibbonPlannerEntryID)->fetchAll();

                        if (!empty($guests)) {
                            $form = Form::create('plannerGuests', '');
                            $form->setClass('noIntBorder fullWidth');
                            $form->setTitle(__('Guests'));

                            $grid = $form->addRow()->addGrid('attendance')->setClass('-mx-3 -my-2')->setBreakpoints('w-1/2');

                            foreach ($guests as $guest) {
                                $cell = $grid->addCell()->setClass('text-center py-4 px-1 -mr-px -mb-px flex flex-col justify-start');

                                $cell->addContent(Format::userPhoto($guest['image_240'], 75, ''));
                                $cell->addContent(Format::name($guest['title'], $guest['preferredName'], $guest['surname'], 'Staff', false))->wrap('<b>', '</b>');
                                $cell->addContent($guest['role']);
                            }

                            $page->addSidebarExtra($form->getOutput());
                        }

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
