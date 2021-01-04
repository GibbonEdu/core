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

use Gibbon\Services\Format;
use Gibbon\Forms\Form;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Planner/planner.php') == false) {
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
        //Set variables
        $today = date('Y-m-d');
        $homeworkNameSingular = getSettingByScope($connection2, 'Planner', 'homeworkNameSingular');
        $homeworkNamePlural = getSettingByScope($connection2, 'Planner', 'homeworkNamePlural');

        //Proceed!
        //Get viewBy, date and class variables
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
            if (isset($_GET['date'])) {
                $date = $_GET['date'];
            }
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
            $gibbonCourseClassID = null;
            if (isset($_GET['gibbonCourseClassID'])) {
                $gibbonCourseClassID = $_GET['gibbonCourseClassID'];
            }
        }
        list($todayYear, $todayMonth, $todayDay) = explode('-', $today);
        $todayStamp = mktime(0, 0, 0, $todayMonth, $todayDay, $todayYear);
        $gibbonPersonIDArray = [];

        //My children's classes
        if ($highestAction == 'Lesson Planner_viewMyChildrensClasses') {
            $search = null;
            if (isset($_GET['search'])) {
                $search = $_GET['search'];
            }
            $page->breadcrumbs->add(__('My Children\'s Classes'));

            if (isset($_GET['return'])) {
                returnProcess($guid, $_GET['return'], null, null);
            }

            //Test data access field for permission
            
                $data = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                $sql = "SELECT * FROM gibbonFamilyAdult WHERE gibbonPersonID=:gibbonPersonID AND childDataAccess='Y'";
                $result = $connection2->prepare($sql);
                $result->execute($data);

            if ($result->rowCount() < 1) {
                echo "<div class='error'>";
                echo __('Access denied.');
                echo '</div>';
            } else {
                //Get child list
                $count = 0;
                $options = array();
                while ($row = $result->fetch()) {
                    
                        $dataChild = array('gibbonFamilyID' => $row['gibbonFamilyID'], 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                        $sqlChild = "SELECT * FROM gibbonFamilyChild JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonFamilyID=:gibbonFamilyID AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY surname, preferredName ";
                        $resultChild = $connection2->prepare($sqlChild);
                        $resultChild->execute($dataChild);
                    while ($rowChild = $resultChild->fetch()) {
                        $options[$rowChild['gibbonPersonID']] = Format::name('', $rowChild['preferredName'], $rowChild['surname'], 'Student');
                        $gibbonPersonIDArray[$count] = $rowChild['gibbonPersonID'];
                        ++$count;
                    }
                }

                if ($count == 0) {
                    echo "<div class='error'>";
                    echo __('Access denied.');
                    echo '</div>';
                } elseif ($count == 1) {
                    $search = $gibbonPersonIDArray[0];
                } else {
                    echo '<h2>';
                    echo __('Choose');
                    echo '</h2>';

                    $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');

                    $form->setClass('noIntBorder fullWidth');

                    $form->addHiddenValue('address', $_SESSION[$guid]['address']);
                    $form->addHiddenValue('q', '/modules/'.$_SESSION[$guid]['module'].'/planner.php');
                    if (isset($gibbonCourseClassID) && $gibbonCourseClassID != '') {
                        $form->addHiddenValue('gibbonCourseClassID', $gibbonCourseClassID);
                        $form->addHiddenValue('viewBy', 'class');
                    }
                    else {
                        $form->addHiddenValue('viewBy', 'date');
                    }

                    $row = $form->addRow();
                    $row->addLabel('search', __('Student'));
                    $row->addSelect('search')->fromArray($options)->selected($search)->placeholder();

                    $row = $form->addRow();
                        $row->addFooter();
                        $row->addSearchSubmit($gibbon->session);

                    echo $form->getOutput();
                }

                $gibbonPersonID = $search;

                if ($search != '' and $count > 0) {
                    //Confirm access to this student
                    
                        $dataChild = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonPersonID2' => $gibbonPersonID);
                        $sqlChild = "SELECT * FROM gibbonFamilyChild JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonFamilyChild.gibbonPersonID=:gibbonPersonID2 AND gibbonFamilyAdult.gibbonPersonID=:gibbonPersonID AND childDataAccess='Y'";
                        $resultChild = $connection2->prepare($sqlChild);
                        $resultChild->execute($dataChild);

                    if ($resultChild->rowCount() < 1) {
                        echo "<div class='error'>";
                        echo __('There are no records to display.');
                        echo '</div>';
                    } else {
                        $rowChild = $resultChild->fetch();

                        if ($count > 1) {
                            echo '<h2>';
                            echo __('Lessons');
                            echo '</h2>';
                        }

                        //Print planner
                        if ($viewBy == 'date') {
                            if (isSchoolOpen($guid, date('Y-m-d', $dateStamp), $connection2) == false) {
                                echo "<div class='warning'>";
                                echo __('School is closed on the specified day.');
                                echo '</div>';
                            } else {
                                
                                    $data = array('date1' => $date, 'gibbonPersonID1' => $gibbonPersonID, 'date2' => $date, 'gibbonPersonID2' => $gibbonPersonID);
                                    $sql = "
                                    (SELECT
                                        gibbonPlannerEntry.gibbonPlannerEntryID, gibbonUnitID, gibbonPlannerEntry.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, timeStart, timeEnd, viewableStudents, viewableParents, homework, role, homeworkSubmission, homeworkCrowdAssess, date, gibbonPlannerEntryStudentHomework.homeworkDueDateTime AS myHomeworkDueDateTime
                                    FROM gibbonPlannerEntry
                                        JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
                                        JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID)
                                        JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID)
                                        LEFT JOIN gibbonPlannerEntryStudentHomework ON (gibbonPlannerEntryStudentHomework.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID AND gibbonPlannerEntryStudentHomework.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID)
                                    WHERE date=:date1
                                        AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID1
                                        AND NOT role='Student - Left'
                                        AND NOT role='Teacher - Left')
                                    UNION
                                    (SELECT
                                        gibbonPlannerEntry.gibbonPlannerEntryID, gibbonUnitID, gibbonPlannerEntry.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, timeStart, timeEnd, viewableStudents, viewableParents, homework, role, homeworkSubmission, homeworkCrowdAssess, date, NULL AS myHomeworkDueDateTime
                                    FROM gibbonPlannerEntry
                                        JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
                                        JOIN gibbonPlannerEntryGuest ON (gibbonPlannerEntryGuest.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID)
                                        JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID)
                                    WHERE date=:date2
                                        AND gibbonPlannerEntryGuest.gibbonPersonID=:gibbonPersonID2)
                                    ORDER BY date, timeStart
                                    ";
                                    $result = $connection2->prepare($sql);
                                    $result->execute($data);

                                //Only show add if user has edit rights
                                if ($highestAction == 'Lesson Planner_viewAllEditMyClasses' or $highestAction == 'Lesson Planner_viewEditAllClasses') {
                                    echo "<div class='linkTop'>";
                                    echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Planner/planner_add.php&date=$date'>".__('Add')."<img style='margin-left: 5px' title='".__('Add')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new.png'/></a>";
                                    echo '</div>';
                                }

                                if ($result->rowCount() < 1) {
                                    echo "<div class='error'>";
                                    echo __('There are no records to display.');
                                    echo '</div>';
                                } else {
                                    echo "<table cellspacing='0' style='width: 100%'>";
                                    echo "<tr class='head'>";
                                    echo '<th>';
                                    echo __('Class');
                                    echo '</th>';
                                    echo '<th>';
                                    echo __('Lesson').'</br>';
                                    echo "<span style='font-size: 85%; font-style: italic'>".__('Unit').'</span>';
                                    echo '</th>';
                                    echo '<th>';
                                    echo __('Time');
                                    echo '</th>';
                                    echo '<th>';
                                    echo __($homeworkNameSingular);
                                    echo '</th>';
                                    echo '<th>';
                                    echo __('Access');
                                    echo '</th>';
                                    echo "<th style='min-width: 140px'>";
                                    echo __('Actions');
                                    echo '</th>';
                                    echo '</tr>';

                                    $count = 0;
                                    $rowNum = 'odd';
                                    while ($row = $result->fetch()) {
                                        if (!($row['role'] == 'Student' and $row['viewableParents'] == 'N')) {
                                            if ($count % 2 == 0) {
                                                $rowNum = 'even';
                                            } else {
                                                $rowNum = 'odd';
                                            }
                                            ++$count;

                      											//Highlight class in progress
                      											if ((date('H:i:s') > $row['timeStart']) and (date('H:i:s') < $row['timeEnd']) and ($date) == date('Y-m-d')) {
                      												$rowNum = 'current';
                      											}

                      											//COLOR ROW BY STATUS!
                      											echo "<tr class=$rowNum>";
                                            echo '<td>';
                                            echo $row['course'].'.'.$row['class'];
                                            echo '</td>';
                                            echo '<td>';
                                            echo '<b>'.$row['name'].'</b><br/>';
                                            echo "<span style='font-size: 85%; font-style: italic'>";
                                            $unit = getUnit($connection2, $row['gibbonUnitID'], $row['gibbonCourseClassID']);
                                            if (isset($unit[0])) {
                                                echo $unit[0];
                                                if ($unit[1] != '') {
                                                    echo '<br/><i>'.$unit[1].' '.__('Unit').'</i>';
                                                }
                                            }
                                            echo '</span>';
                                            echo '</td>';
                                            echo '<td>';
                                            echo substr($row['timeStart'], 0, 5).'-'.substr($row['timeEnd'], 0, 5);
                                            echo '</td>';
                                            echo '<td>';
                                            if ($row['homework'] == 'N' and $row['myHomeworkDueDateTime'] == '') {
                                                echo __('No');
                                            } else {
                                                if ($row['homework'] == 'Y') {
                                                    echo __('Yes').': '.__('Teacher Recorded').'<br/>';
                                                    if ($row['homeworkSubmission'] == 'Y') {
                                                        echo "<span style='font-size: 85%; font-style: italic'>+".__('Submission').'</span><br/>';
                                                        if ($row['homeworkCrowdAssess'] == 'Y') {
                                                            echo "<span style='font-size: 85%; font-style: italic'>+".__('Crowd Assessment').'</span><br/>';
                                                        }
                                                    }
                                                }
                                                if ($row['myHomeworkDueDateTime'] != '') {
                                                    echo __('Yes').': '.__('Student Recorded').'</br>';
                                                }
                                            }
                                            echo '</td>';
                                            echo '<td>';
                                            if ($row['viewableStudents'] == 'Y') {
                                                echo __('Students');
                                            }
                                            if ($row['viewableStudents'] == 'Y' and $row['viewableParents'] == 'Y') {
                                                echo ', ';
                                            }
                                            if ($row['viewableParents'] == 'Y') {
                                                echo __('Parents');
                                            }
                                            echo '</td>';
                                            echo '<td>';
                                            echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Planner/planner_view_full.php&search=$gibbonPersonID&gibbonPlannerEntryID=".$row['gibbonPlannerEntryID']."&viewBy=$viewBy&gibbonCourseClassID=$gibbonCourseClassID&date=$date&width=1000&height=550'><img title='".__('View')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/plus.png'/></a> ";
                                            echo '</td>';
                                            echo '</tr>';
                                        }
                                    }
                                    echo '</table>';
                                }
                            }
                        } elseif ($viewBy == 'class') {
                            if ($gibbonCourseClassID == '') {
                                echo "<div class='error'>";
                                echo __('You have not specified one or more required parameters.');
                                echo '</div>';
                            } else {
                                
                                    $data = array('gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonPersonID' => $gibbonPersonID);
                                    $sql = 'SELECT gibbonCourseClass.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourseClassPerson JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourse.gibbonSchoolYearID='.$_SESSION[$guid]['gibbonSchoolYearID'].' AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID AND gibbonPersonID=:gibbonPersonID';
                                    $result = $connection2->prepare($sql);
                                    $result->execute($data);

                                if ($result->rowCount() != 1) {
                                    echo "<div class='error'>";
                                    echo __('The selected record does not exist, or you do not have access to it.');
                                    echo '</div>';
                                } else {
                                    $row = $result->fetch();

                                    
                                        $data = array('gibbonCourseClassID1' => $gibbonCourseClassID, 'gibbonPersonID1' => $gibbonPersonID, 'gibbonCourseClassID2' => $gibbonCourseClassID, 'gibbonPersonID2' => $gibbonPersonID);
                                        $sql = "(SELECT gibbonPlannerEntry.gibbonPlannerEntryID, gibbonUnitID, gibbonPlannerEntry.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, timeStart, timeEnd, viewableStudents, viewableParents, homework, role, homeworkSubmission, homeworkCrowdAssess, date, gibbonPlannerEntryStudentHomework.homeworkDueDateTime AS myHomeworkDueDateTime FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) LEFT JOIN gibbonPlannerEntryStudentHomework ON (gibbonPlannerEntryStudentHomework.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID AND gibbonPlannerEntryStudentHomework.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID) WHERE gibbonPlannerEntry.gibbonCourseClassID=:gibbonCourseClassID1 AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID1 AND NOT role='Student - Left' AND NOT role='Teacher - Left') UNION (SELECT gibbonPlannerEntry.gibbonPlannerEntryID, gibbonUnitID, gibbonPlannerEntry.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, timeStart, timeEnd, viewableStudents, viewableParents, homework, role, homeworkSubmission, homeworkCrowdAssess, date, NULL AS myHomeworkDueDateTime FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonPlannerEntryGuest ON (gibbonPlannerEntryGuest.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonPlannerEntry.gibbonCourseClassID=:gibbonCourseClassID2 AND gibbonPlannerEntryGuest.gibbonPersonID=:gibbonPersonID2) ORDER BY date DESC, timeStart DESC";
                                        $result = $connection2->prepare($sql);
                                        $result->execute($data);

                                    //Only show add if user has edit rights
                                    if ($highestAction == 'Lesson Planner_viewAllEditMyClasses' or $highestAction == 'Lesson Planner_viewEditAllClasses') {
                                        echo "<div class='linkTop'>";
                                        echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Planner/planner_add.php&viewBy=$viewBy&gibbonCourseClassID=$gibbonCourseClassID'>".__('Add')."<img style='margin-left: 5px' title='".__('Add')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new.png'/></a>";
                                        echo '</div>';
                                    }

                                    if ($result->rowCount() < 1) {
                                        echo "<div class='error'>";
                                        echo __('There are no records to display.');
                                        echo '</div>';
                                    } else {
                                        echo "<table cellspacing='0' style='width: 100%'>";
                                        echo "<tr class='head'>";
                                        echo '<th>';
                                        echo __('Date');
                                        echo '</th>';
                                        echo '<th>';
                                        echo __('Lesson').'</br>';
                                        echo "<span style='font-size: 85%; font-style: italic'>".__('Unit').'</span>';
                                        echo '</th>';
                                        echo '<th>';
                                        echo __('Time');
                                        echo '</th>';
                                        echo '<th>';
                                        echo __($homeworkNameSingular);
                                        echo '</th>';
                                        echo '<th>';
                                        echo __('Access');
                                        echo '</th>';
                                        echo "<th style='min-width: 140px'>";
                                        echo __('Actions');
                                        echo '</th>';
                                        echo '</tr>';

                                        $count = 0;
                                        $rowNum = 'odd';
                                        while ($row = $result->fetch()) {
                                            if (!($row['role'] == 'Student' and $row['viewableParents'] == 'N')) {
                                                if ($count % 2 == 0) {
                                                    $rowNum = 'even';
                                                } else {
                                                    $rowNum = 'odd';
                                                }
                                                ++$count;

                                                //Highlight class in progress
                                                if ((date('Y-m-d') == $row['date']) and (date('H:i:s') > $row['timeStart']) and (date('H:i:s') < $row['timeEnd'])) {
                                                    $rowNum = 'current';
                                                }

                                                //COLOR ROW BY STATUS!
                                                echo "<tr class=$rowNum>";
                                                echo '<td>';
                                                if (!(is_null($row['date']))) {
                                                    echo '<b>'.dateConvertBack($guid, $row['date']).'</b><br/>';
                                                    echo Format::dateReadable($row['date'], '%A');
                                                }
                                                echo '</td>';
                                                echo '<td>';
                                                echo '<b>'.$row['name'].'</b><br/>';
                                                if ($row['gibbonUnitID'] != '') {
                                                    $unit = getUnit($connection2, $row['gibbonUnitID'], $row['gibbonCourseClassID']);
                                                    if (!empty($unit[0])) {
                                                        echo "<span style='font-size: 85%; font-style: italic'>";
                                                            echo $unit[0];
                                                            if ($unit[1] != '') {
                                                                echo '<br/><i>'.$unit[1].' '.__('Unit').'</i>';
                                                            }
                                                        echo '</span>';
                                                    }

                                                }
                                                echo '</td>';
                                                echo '<td>';
                                                if ($row['timeStart'] != '' and $row['timeEnd'] != '') {
                                                    echo substr($row['timeStart'], 0, 5).'-'.substr($row['timeEnd'], 0, 5);
                                                }
                                                echo '</td>';
                                                echo '<td>';
                                                if ($row['homework'] == 'N' and $row['myHomeworkDueDateTime'] == '') {
                                                    echo __('No');
                                                } else {
                                                    if ($row['homework'] == 'Y') {
                                                        echo __('Yes').': '.__('Teacher Recorded').'<br/>';
                                                        if ($row['homeworkSubmission'] == 'Y') {
                                                            echo "<span style='font-size: 85%; font-style: italic'>+".__('Submission').'</span><br/>';
                                                            if ($row['homeworkCrowdAssess'] == 'Y') {
                                                                echo "<span style='font-size: 85%; font-style: italic'>+".__('Crowd Assessment').'</span><br/>';
                                                            }
                                                        }
                                                    }
                                                    if ($row['myHomeworkDueDateTime'] != '') {
                                                        echo __('Yes').': '.__('Student Recorded').'</br>';
                                                    }
                                                }
                                                echo '</td>';
                                                echo '<td>';
                                                if ($row['viewableStudents'] == 'Y') {
                                                    echo __('Students');
                                                }
                                                if ($row['viewableStudents'] == 'Y' and $row['viewableParents'] == 'Y') {
                                                    echo ', ';
                                                }
                                                if ($row['viewableParents'] == 'Y') {
                                                    echo __('Parents');
                                                }
                                                echo '</td>';
                                                echo '<td>';
                                                echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Planner/planner_view_full.php&search=$gibbonPersonID&gibbonPlannerEntryID=".$row['gibbonPlannerEntryID']."&viewBy=class&gibbonCourseClassID=$gibbonCourseClassID&width=1000&height=550'><img title='".__('View')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/plus.png'/></a> ";
                                                echo '</td>';
                                                echo '</tr>';
                                            }
                                        }
                                        echo '</table>';
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        //My Classes
        elseif ($highestAction == 'Lesson Planner_viewMyClasses' or $highestAction == 'Lesson Planner_viewAllEditMyClasses' or $highestAction == 'Lesson Planner_viewEditAllClasses' or $highestAction == 'Lesson Planner_viewOnly') {
            $gibbonPersonID = $_SESSION[$guid]['gibbonPersonID'];
            if ($viewBy == 'date') {
                $page->breadcrumbs->add(__('Planner for {classDesc}', [
                    'classDesc' => dateConvertBack($guid, $date),
                ]));

                if (isset($_GET['return'])) {
                    returnProcess($guid, $_GET['return'], null, null);
                }

                if (isSchoolOpen($guid, date('Y-m-d', $dateStamp), $connection2) == false) {
                    echo "<div class='warning'>";
                    echo __('School is closed on the specified day.');
                    echo '</div>';
                } else {
                    //Set pagination variable
                    $page = 1;
                    if (isset($_GET['page'])) {
                        $page = $_GET['page'];
                    }
                    if ((!is_numeric($page)) or $page < 1) {
                        $page = 1;
                    }

                    try {
                        if ($highestAction == 'Lesson Planner_viewEditAllClasses' or $highestAction == 'Lesson Planner_viewOnly') {
                            $data = array('date' => $date);
                            $sql = "SELECT gibbonPlannerEntry.gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, timeStart, timeEnd, viewableStudents, viewableParents, homework, 'Teacher' AS role, homeworkSubmission, homeworkCrowdAssess, date, gibbonPlannerEntry.gibbonCourseClassID, NULL AS myHomeworkDueDateTime FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE date=:date ORDER BY date, timeStart";
                        } elseif ($highestAction == 'Lesson Planner_viewMyClasses' or $highestAction == 'Lesson Planner_viewAllEditMyClasses') {
                            $data = array('date1' => $date, 'gibbonPersonID1' => $gibbonPersonID, 'date2' => $date, 'gibbonPersonID2' => $gibbonPersonID);
                            $sql = "(SELECT gibbonPlannerEntry.gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, timeStart, timeEnd, viewableStudents, viewableParents, homework, role, homeworkSubmission, homeworkCrowdAssess, date, gibbonPlannerEntry.gibbonCourseClassID, gibbonPlannerEntryStudentHomework.homeworkDueDateTime AS myHomeworkDueDateTime FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) LEFT JOIN gibbonPlannerEntryStudentHomework ON (gibbonPlannerEntryStudentHomework.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID AND gibbonPlannerEntryStudentHomework.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID) WHERE date=:date1 AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID1 AND NOT role='Student - Left' AND NOT role='Teacher - Left') UNION (SELECT gibbonPlannerEntry.gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, timeStart, timeEnd, viewableStudents, viewableParents, homework, role, homeworkSubmission, homeworkCrowdAssess, date, gibbonPlannerEntry.gibbonCourseClassID, NULL AS myHomeworkDueDateTime FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonPlannerEntryGuest ON (gibbonPlannerEntryGuest.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE date=:date2 AND gibbonPlannerEntryGuest.gibbonPersonID=:gibbonPersonID2) ORDER BY date, timeStart";
                        }
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }

                    //Only show add if user has edit rights
                    if ($highestAction == 'Lesson Planner_viewEditMyClasses' or $highestAction == 'Lesson Planner_viewEditAllClasses' or $highestAction == 'Lesson Planner_viewAllEditMyClasses') {
                        echo "<div class='linkTop'>";
                        echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Planner/planner_add.php&date=$date'>".__('Add')."<img style='margin-left: 5px' title='".__('Add')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new.png'/></a>";
                        echo '</div>';
                    }

                    if ($result->rowCount() < 1) {
                        echo "<div class='error'>";
                        echo __('There are no records to display.');
                        echo '</div>';
                    } else {
                        echo "<table cellspacing='0' style='width: 100%'>";
                        echo "<tr class='head'>";
                        echo '<th>';
                        echo __('Class');
                        echo '</th>';
                        echo '<th>';
                        echo __('Lesson').'</br>';
                        echo "<span style='font-size: 85%; font-style: italic'>".__('Unit').'</span>';
                        echo '</th>';
                        echo '<th>';
                        echo __('Time');
                        echo '</th>';
                        echo '<th>';
                        echo __($homeworkNameSingular);
                        echo '</th>';
                        echo '<th>';
                        echo __('Access');
                        echo '</th>';
                        echo "<th style='min-width: 140px'>";
                        echo __('Actions');
                        echo '</th>';
                        echo '</tr>';

                        $count = 0;
                        $rowNum = 'odd';
                        while ($row = $result->fetch()) {
                            if ((!($row['role'] == 'Student' and $row['viewableStudents'] == 'N')) and (!($row['role'] == 'Guest Student' and $row['viewableStudents'] == 'N'))) {
                                if ($count % 2 == 0) {
                                    $rowNum = 'even';
                                } else {
                                    $rowNum = 'odd';
                                }
                                ++$count;

                                    //Highlight class in progress
                                    if ((date('H:i:s') > $row['timeStart']) and (date('H:i:s') < $row['timeEnd']) and ($date) == date('Y-m-d')) {
                                        $rowNum = 'current';
                                    }
                                    //Dull out past classes
                                    if ((($row['date']) == date('Y-m-d') and (date('H:i:s') > $row['timeEnd'])) or ($row['date']) < date('Y-m-d')) {
                                        $rowNum = 'past';
                                    }

                                    //COLOR ROW BY STATUS!
                                    echo "<tr class=$rowNum>";
                                echo '<td>';
                                echo $row['course'].'.'.$row['class'];
                                echo '</td>';
                                echo '<td>';
                                echo '<b>'.$row['name'].'</b><br/>';
                                echo "<span style='font-size: 85%; font-style: italic'>";
                                $unit = getUnit($connection2, $row['gibbonUnitID'], $row['gibbonCourseClassID']);
                                if (isset($unit[0])) {
                                    echo $unit[0];
                                    if ($unit[1] != '') {
                                        echo '<br/><i>'.$unit[1].' '.__('Unit').'</i>';
                                    }
                                }
                                echo '</span>';
                                echo '</td>';
                                echo '<td>';
                                echo substr($row['timeStart'], 0, 5).'-'.substr($row['timeEnd'], 0, 5);
                                echo '</td>';
                                echo '<td>';
                                if ($row['homework'] == 'N' and $row['myHomeworkDueDateTime'] == '') {
                                    echo __('No');
                                } else {
                                    if ($row['homework'] == 'Y') {
                                        echo __('Yes').': '.__('Teacher Recorded').'<br/>';
                                        if ($row['homeworkSubmission'] == 'Y') {
                                            echo "<span style='font-size: 85%; font-style: italic'>+".__('Submission').'</span><br/>';
                                            if ($row['homeworkCrowdAssess'] == 'Y') {
                                                echo "<span style='font-size: 85%; font-style: italic'>+".__('Crowd Assessment').'</span><br/>';
                                            }
                                        }
                                    }
                                    if ($row['myHomeworkDueDateTime'] != '') {
                                        echo __('Yes').': '.__('Student Recorded').'</br>';
                                    }
                                }
                                echo '<td>';
                                if ($row['viewableStudents'] == 'Y') {
                                    echo __('Students');
                                }
                                if ($row['viewableStudents'] == 'Y' and $row['viewableParents'] == 'Y') {
                                    echo ', ';
                                }
                                if ($row['viewableParents'] == 'Y') {
                                    echo __('Parents');
                                }
                                echo '</td>';
                                echo '<td>';
                                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Planner/planner_view_full.php&gibbonPlannerEntryID='.$row['gibbonPlannerEntryID']."&viewBy=$viewBy&gibbonCourseClassID=$gibbonCourseClassID&date=$date&width=1000&height=550'><img title='".__('View')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/plus.png'/></a> ";
                                if ($highestAction == 'Lesson Planner_viewAllEditMyClasses' or $highestAction == 'Lesson Planner_viewEditAllClasses') {
                                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Planner/planner_edit.php&gibbonPlannerEntryID='.$row['gibbonPlannerEntryID']."&viewBy=$viewBy&gibbonCourseClassID=$gibbonCourseClassID&date=$date'><img title='".__('Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                                    echo "<a class='thickbox' href='".$_SESSION[$guid]['absoluteURL'].'/fullscreen.php?q=/modules/Planner/planner_delete.php&gibbonPlannerEntryID='.$row['gibbonPlannerEntryID']."&viewBy=$viewBy&gibbonCourseClassID=$gibbonCourseClassID&date=$date&subView=$subView&width=650&height=135'><img title='".__('Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a>";
                                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Planner/planner_duplicate.php&gibbonPlannerEntryID='.$row['gibbonPlannerEntryID']."&viewBy=$viewBy&gibbonCourseClassID=$gibbonCourseClassID&date=$date'><img style='margin-left: 3px' title='".__('Duplicate')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/copy.png'/></a>";
                                }
                                echo '</td>';
                                echo '</tr>';
                            }
                        }
                        echo '</table>';
                    }
                }
            } elseif ($viewBy == 'class') {
                if ($gibbonCourseClassID == '') {
                    echo "<div class='error'>";
                    echo __('You have not specified one or more required parameters.');
                    echo '</div>';
                } else {
                    if ($highestAction == 'Lesson Planner_viewEditAllClasses' or $highestAction == 'Lesson Planner_viewAllEditMyClasses' or $highestAction == 'Lesson Planner_viewOnly') {
                        
                            $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonCourseClassID' => $gibbonCourseClassID);
                            $sql = 'SELECT gibbonCourseClass.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourseClass JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID';
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        $teacher = false;

                        
                            $dataTeacher = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                            $sqlTeacher = 'SELECT gibbonCourseClass.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourseClassPerson JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID AND gibbonPersonID=:gibbonPersonID';
                            $resultTeacher = $connection2->prepare($sqlTeacher);
                            $resultTeacher->execute($dataTeacher);
                        if ($resultTeacher->rowCount() > 0) {
                            $teacher = true;
                        }
                    } elseif ($highestAction == 'Lesson Planner_viewMyClasses') {
                        
                            $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                            $sql = 'SELECT gibbonCourseClass.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourseClassPerson JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID AND gibbonPersonID=:gibbonPersonID';
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                    }

                    if ($result->rowCount() != 1) {
                        echo "<div class='error'>";
                        echo __('The selected record does not exist, or you do not have access to it.');
                        echo '</div>';
                    } else {
                        $row = $result->fetch();

                        $page->breadcrumbs->add(__('Planner for {classDesc}', [
                            'classDesc' => $row['course'].'.'.$row['class'],
                        ]));

                        $returns = array();
                        $returns['success1'] = __('Bump was successful. It is possible that some lessons have not been moved (if there was no space for them), but a reasonable effort has been made.');
                        if (isset($_GET['return'])) {
                            returnProcess($guid, $_GET['return'], null, $returns);
                        }

                        try {
                            if ($highestAction == 'Lesson Planner_viewEditAllClasses' or $highestAction == 'Lesson Planner_viewAllEditMyClasses' or $highestAction == 'Lesson Planner_viewOnly') {
                                if ($subView == 'lesson' or $subView == '') {
                                    $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
                                    $sql = "SELECT gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, date, timeStart, timeEnd, viewableStudents, viewableParents, homework, 'Teacher' as role, homeworkSubmission, homeworkCrowdAssess, gibbonPlannerEntry.gibbonCourseClassID, NULL AS myHomeworkDueDateTime FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonPlannerEntry.gibbonCourseClassID=:gibbonCourseClassID ORDER BY date DESC, timeStart DESC";
                                } else {
                                    $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
                                    $sql = 'SELECT timeStart, timeEnd, date, gibbonTTColumnRow.name AS period, gibbonTTDayRowClassID, gibbonTTDayDateID, NULL AS myHomeworkDueDateTime FROM gibbonTTDayRowClass JOIN gibbonTTColumnRow ON (gibbonTTDayRowClass.gibbonTTColumnRowID=gibbonTTColumnRow.gibbonTTColumnRowID) JOIN gibbonTTColumn ON (gibbonTTColumnRow.gibbonTTColumnID=gibbonTTColumn.gibbonTTColumnID) JOIN gibbonTTDay ON (gibbonTTDayRowClass.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) JOIN gibbonTTDayDate ON (gibbonTTDayDate.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) WHERE gibbonCourseClassID=:gibbonCourseClassID ORDER BY date, timestart';
                                }
                            } elseif ($highestAction == 'Lesson Planner_viewMyClasses') {
                                $data = array('gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                                $sql = "SELECT gibbonPlannerEntry.gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, date, timeStart, timeEnd, viewableStudents, viewableParents, homework, role, homeworkSubmission, homeworkCrowdAssess, gibbonPlannerEntry.gibbonCourseClassID, gibbonPlannerEntryStudentHomework.homeworkDueDateTime AS myHomeworkDueDateTime FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) LEFT JOIN gibbonPlannerEntryStudentHomework ON (gibbonPlannerEntryStudentHomework.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID AND gibbonPlannerEntryStudentHomework.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID) WHERE gibbonPlannerEntry.gibbonCourseClassID=:gibbonCourseClassID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left' ORDER BY date DESC, timeStart DESC";
                            }
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }

                        //Only show add if user has edit rights
                        if ($highestAction == 'Lesson Planner_viewAllEditMyClasses' or $highestAction == 'Lesson Planner_viewEditAllClasses') {
                            echo "<div class='linkTop'>";
                            $style = '';
                            if ($subView == 'lesson' or $subView == '') {
                                $style = "style='font-weight: bold'";
                            }
                            echo "<a $style href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Planner/planner.php&viewBy=$viewBy&gibbonCourseClassID=$gibbonCourseClassID&subView=lesson'>".__('Lesson View').'</a> | ';
                            $style = '';
                            if ($subView == 'year') {
                                $style = "style='font-weight: bold'";
                            }
                            echo "<a $style href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Planner/planner.php&viewBy=$viewBy&gibbonCourseClassID=$gibbonCourseClassID&subView=year'>".__('Year Overview').'</a> | ';
                            echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Planner/planner_add.php&viewBy=$viewBy&gibbonCourseClassID=$gibbonCourseClassID&subView=$subView'>".__('Add')."<img style='margin-left: 5px' title='".__('Add')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new.png'/></a>";
                            echo '</div>';
                        }

                        if ($result->rowCount() < 1) {
                            echo "<div class='error'>";
                            echo __('There are no records to display.');
                            echo '</div>';
                        } else {
                            //PRINT LESSON VIEW
                            if ($subView == 'lesson' or $subView == '') {
                                echo "<table cellspacing='0' style='width: 100%'>";
                                echo "<tr class='head'>";
                                echo '<th>';
                                echo __('Date');
                                echo '</th>';
                                echo '<th>';
                                echo __('Lesson').'</br>';
                                echo "<span style='font-size: 85%; font-style: italic'>".__('Unit').'</span>';
                                echo '</th>';
                                echo '<th>';
                                echo __('Time');
                                echo '</th>';
                                echo '<th>';
                                echo __($homeworkNameSingular);
                                echo '</th>';
                                echo '<th>';
                                echo __('Access');
                                echo '</th>';
                                echo "<th style='min-width: 150px'>";
                                echo __('Actions');
                                echo '</th>';
                                echo '</tr>';

                                $count = 0;
                                $pastCount = 0;
                                $rowNum = 'odd';
                                while ($row = $result->fetch()) {
                                    if ((!($row['role'] == 'Student' and $row['viewableStudents'] == 'N')) and (!($row['role'] == 'Guest Student' and $row['viewableStudents'] == 'N'))) {
                                        if ($count % 2 == 0) {
                                            $rowNum = 'even';
                                        } else {
                                            $rowNum = 'odd';
                                        }
                                        ++$count;

                                        //Highlight class in progress
                                        if ((date('Y-m-d') == $row['date']) and (date('H:i:s') > $row['timeStart']) and (date('H:i:s') < $row['timeEnd'])) {
                                            $rowNum = 'current';
                                        }

                                        //Dull out past classes
                                        if ((($row['date']) == date('Y-m-d') and (date('H:i:s') > $row['timeEnd'])) or ($row['date']) < date('Y-m-d')) {
                                            $rowNum = 'past';
                                            if ($pastCount == 0) {
                                                echo "<tr style='padding: 0px; height: 2px; background-color: #000'>";
                                                echo "<td style='padding: 0px' colspan=8>";
                                                echo '</tr>';
                                            }
                                            ++$pastCount;
                                        }

                                        //COLOR ROW BY STATUS!
                                        echo "<tr class=$rowNum>";
                                        echo '<td>';
                                        if (!(is_null($row['date']))) {
                                            echo '<b>'.dateConvertBack($guid, $row['date']).'</b><br/>';
                                            echo Format::dateReadable($row['date'], '%A');
                                        }
                                        echo '</td>';
                                        echo '<td>';
                                        echo '<b>'.$row['name'].'</b><br/>';
                                        echo "<span style='font-size: 85%; font-style: italic'>";
                                        $unit = getUnit($connection2, $row['gibbonUnitID'], $row['gibbonCourseClassID']);
                                        if (isset($unit[0])) {
                                            echo $unit[0];
                                            if (isset($unit[1])) {
                                                if ($unit[1] != '') {
                                                    echo '<br/><i>'.$unit[1].' '.__('Unit').'</i>';
                                                }
                                            }
                                        }
                                        echo '</span>';
                                        echo '</td>';
                                        echo '<td>';
                                        if ($row['timeStart'] != '' and $row['timeEnd'] != '') {
                                            echo substr($row['timeStart'], 0, 5).'-'.substr($row['timeEnd'], 0, 5);
                                        }
                                        echo '</td>';
                                        echo '<td>';
                                        if ($row['homework'] == 'N' and $row['myHomeworkDueDateTime'] == '') {
                                            echo __('No');
                                        } else {
                                            if ($row['homework'] == 'Y') {
                                                echo __('Yes').': '.__('Teacher Recorded').'<br/>';
                                                if ($row['homeworkSubmission'] == 'Y') {
                                                    echo "<span style='font-size: 85%; font-style: italic'>+".__('Submission').'</span><br/>';
                                                    if ($row['homeworkCrowdAssess'] == 'Y') {
                                                        echo "<span style='font-size: 85%; font-style: italic'>+".__('Crowd Assessment').'</span><br/>';
                                                    }
                                                }
                                            }
                                            if ($row['myHomeworkDueDateTime'] != '') {
                                                echo __('Yes').': '.__('Student Recorded').'</br>';
                                            }
                                        }
                                        echo '</td>';
                                        echo '<td>';
                                        if ($row['viewableStudents'] == 'Y') {
                                            echo __('Students');
                                        }
                                        if ($row['viewableStudents'] == 'Y' and $row['viewableParents'] == 'Y') {
                                            echo ', ';
                                        }
                                        if ($row['viewableParents'] == 'Y') {
                                            echo __('Parents');
                                        }
                                        echo '</td>';
                                        echo '<td>';
                                        echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Planner/planner_view_full.php&gibbonPlannerEntryID='.$row['gibbonPlannerEntryID']."&viewBy=class&gibbonCourseClassID=$gibbonCourseClassID&width=1000&height=550'><img title='".__('View')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/plus.png'/></a> ";
                                        if ((($highestAction == 'Lesson Planner_viewAllEditMyClasses' and $teacher == true) or $highestAction == 'Lesson Planner_viewEditAllClasses')) {
                                            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Planner/planner_edit.php&gibbonPlannerEntryID='.$row['gibbonPlannerEntryID']."&viewBy=$viewBy&gibbonCourseClassID=$gibbonCourseClassID'><img title='".__('Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                                            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Planner/planner_bump.php&gibbonPlannerEntryID='.$row['gibbonPlannerEntryID']."&viewBy=$viewBy&gibbonCourseClassID=$gibbonCourseClassID'><img title='".__('Bump')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_right.png'/></a>";
                                            echo "<a class='thickbox' href='".$_SESSION[$guid]['absoluteURL'].'/fullscreen.php?q=/modules/Planner/planner_delete.php&gibbonPlannerEntryID='.$row['gibbonPlannerEntryID']."&viewBy=$viewBy&gibbonCourseClassID=$gibbonCourseClassID&date=$date&subView=$subView&width=650&height=135'><img title='".__('Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a>";
                                        }
                                        if ($highestAction == 'Lesson Planner_viewAllEditMyClasses' or $highestAction == 'Lesson Planner_viewEditAllClasses') {
                                            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Planner/planner_duplicate.php&gibbonPlannerEntryID='.$row['gibbonPlannerEntryID']."&viewBy=$viewBy&gibbonCourseClassID=$gibbonCourseClassID&date=$date'><img style='margin-left: 3px' title='".__('Duplicate')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/copy.png'/></a>";
                                        }
                                        echo '</td>';
                                        echo '</tr>';
                                    }
                                }
                                echo '</table>';
                            }
                            //PRINT YEAR OVERVIEW
                            else {
                                $count = 0;
                                $lessons = array();
                                while ($rowNext = $result->fetch()) {
                                    
                                        $dataPlanner = array('date' => $rowNext['date'], 'timeStart' => $rowNext['timeStart'], 'timeEnd' => $rowNext['timeEnd'], 'gibbonCourseClassID' => $gibbonCourseClassID);
                                        $sqlPlanner = 'SELECT * FROM gibbonPlannerEntry WHERE date=:date AND timeStart=:timeStart AND timeEnd=:timeEnd AND gibbonCourseClassID=:gibbonCourseClassID';
                                        $resultPlanner = $connection2->prepare($sqlPlanner);
                                        $resultPlanner->execute($dataPlanner);
                                    if ($resultPlanner->rowCount() == 0) {
                                        $lessons[$count][0] = 'Unplanned';
                                        $lessons[$count][1] = $rowNext['date'];
                                        $lessons[$count][2] = $rowNext['timeStart'];
                                        $lessons[$count][3] = $rowNext['timeEnd'];
                                        $lessons[$count][4] = $rowNext['period'];
                                        $lessons[$count][6] = $rowNext['gibbonTTDayRowClassID'];
                                        $lessons[$count][7] = $rowNext['gibbonTTDayDateID'];
                                        $lessons[$count][11] = null;
                                        $lessons[$count][12] = null;
                                        $lessons[$count][13] = null;
                                    } else {
                                        $rowPlanner = $resultPlanner->fetch();
                                        $lessons[$count][0] = 'Planned';
                                        $lessons[$count][1] = $rowNext['date'];
                                        $lessons[$count][2] = $rowNext['timeStart'];
                                        $lessons[$count][3] = $rowNext['timeEnd'];
                                        $lessons[$count][4] = $rowNext['period'];
                                        $lessons[$count][5] = $rowPlanner['name'];
                                        $lessons[$count][6] = false;
                                        $lessons[$count][7] = false;
                                        $lessons[$count][11] = $rowPlanner['gibbonUnitID'];
                                        $lessons[$count][12] = $rowPlanner['gibbonPlannerEntryID'];
                                        $lessons[$count][13] = $rowPlanner['gibbonCourseClassID'];
                                    }

                                    //Check for special days
                                    
                                        $dataSpecial = array('date' => $rowNext['date']);
                                        $sqlSpecial = 'SELECT * FROM gibbonSchoolYearSpecialDay WHERE date=:date';
                                        $resultSpecial = $connection2->prepare($sqlSpecial);
                                        $resultSpecial->execute($dataSpecial);

                                    if ($resultSpecial->rowCount() == 1) {
                                        $rowSpecial = $resultSpecial->fetch();
                                        $lessons[$count][8] = $rowSpecial['type'];
                                        $lessons[$count][9] = $rowSpecial['schoolStart'];
                                        $lessons[$count][10] = $rowSpecial['schoolEnd'];
                                    } else {
                                        $lessons[$count][8] = false;
                                        $lessons[$count][9] = false;
                                        $lessons[$count][10] = false;
                                    }

                                    ++$count;
                                }

                                if (count($lessons) < 1) {
                                    echo "<div class='error'>";
                                    echo __('There are no records to display.');
                                    echo '</div>';
                                } else {
                                    //Get term dates
                                    $terms = array();
                                    $termCount = 0;
                                    
                                        $dataTerms = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                                        $sqlTerms = 'SELECT * FROM gibbonSchoolYearTerm WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY sequenceNumber';
                                        $resultTerms = $connection2->prepare($sqlTerms);
                                        $resultTerms->execute($dataTerms);

                                    while ($rowTerms = $resultTerms->fetch()) {
                                        $terms[$termCount][0] = $rowTerms['firstDay'];
                                        $terms[$termCount][1] = __('Start of').' '.$rowTerms['nameShort'];
                                        ++$termCount;
                                        $terms[$termCount][0] = $rowTerms['lastDay'];
                                        $terms[$termCount][1] = __('End of').' '.$rowTerms['nameShort'];
                                        ++$termCount;
                                    }
                                    //Get school closure special days
                                    $specials = array();
                                    $specialCount = 0;
                                    
                                        $dataSpecial = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                                        $sqlSpecial = "SELECT gibbonSchoolYearSpecialDay.date, gibbonSchoolYearSpecialDay.name FROM gibbonSchoolYearSpecialDay JOIN gibbonSchoolYearTerm ON (gibbonSchoolYearSpecialDay.gibbonSchoolYearTermID=gibbonSchoolYearTerm.gibbonSchoolYearTermID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND type='School Closure' ORDER BY date";
                                        $resultSpecial = $connection2->prepare($sqlSpecial);
                                        $resultSpecial->execute($dataSpecial);

                                    $lastName = '';
                                    $currentName = '';
                                    $lastDate = '';
                                    $currentDate = '';
                                    $originalDate = '';
                                    while ($rowSpecial = $resultSpecial->fetch()) {
                                        $currentName = $rowSpecial['name'];
                                        $currentDate = $rowSpecial['date'];
                                        if ($currentName != $lastName) {
                                            $currentName = $rowSpecial['name'];
                                            $specials[$specialCount][0] = $rowSpecial['date'];
                                            $specials[$specialCount][1] = $rowSpecial['name'];
                                            $specials[$specialCount][2] = dateConvertBack($guid, $rowSpecial['date']);
                                            $originalDate = dateConvertBack($guid, $rowSpecial['date']);
                                            ++$specialCount;
                                        } else {
                                            if ((strtotime($currentDate) - strtotime($lastDate)) == 86400) {
                                                $specials[$specialCount - 1][2] = $originalDate.' - '.dateConvertBack($guid, $rowSpecial['date']);
                                            } else {
                                                $currentName = $rowSpecial['name'];
                                                $specials[$specialCount][0] = $rowSpecial['date'];
                                                $specials[$specialCount][1] = $rowSpecial['name'];
                                                $specials[$specialCount][2] = dateConvertBack($guid, $rowSpecial['date']);
                                                $originalDate = dateConvertBack($guid, $rowSpecial['date']);
                                                ++$specialCount;
                                            }
                                        }
                                        $lastName = $rowSpecial['name'];
                                        $lastDate = $rowSpecial['date'];
                                    }

                                    echo "<table cellspacing='0' style='width: 100%'>";
                                    echo "<tr class='head'>";
                                    echo '<th>';
                                    echo __('Lesson<br/>Number');
                                    echo '</th>';
                                    echo '<th>';
                                    echo __('Date');
                                    echo '</th>';
                                    echo '<th>';
                                    echo __('TT Period').'<br/>';
                                    echo "<span style='font-size: 85%; font-style: italic'>".__('Time')."</span>";
                                    echo '</th>';
                                    echo '<th>';
                                    echo __('Planned Lesson').'<br/>';
                                    echo "<span style='font-size: 85%; font-style: italic'>".__('Unit')."</span>";
                                    echo '</th>';
                                    echo '<th>';
                                    echo __('Actions');
                                    echo '</th>';
                                    echo '</tr>';

                                    $count = 0;
                                    $termCount = 0;
                                    $specialCount = 0;
                                    $classCount = 0;
                                    $rowNum = 'odd';
                                    $divide = false; //Have we passed gotten to today yet?

                                    foreach ($lessons as $lesson) {
                                        if ($count % 2 == 0) {
                                            $rowNum = 'even';
                                        } else {
                                            $rowNum = 'odd';
                                        }

                                        $style = '';
                                        if ($lesson[1] >= date('Y-m-d') and $divide == false) {
                                            $divide = true;
                                            $style = "style='border-top: 2px solid #333'";
                                        }

                                        if ($divide == false) {
                                            $rowNum = 'error';
                                        }
                                        ++$count;

                                        //Spit out row for start of term
                                        while ($termCount < (count($terms) - 1) && $lesson['1'] >= $terms[$termCount][0]) {
                                            if (substr($terms[$termCount][1], 0, 3) == 'End' and $lesson['1'] == $terms[$termCount][0]) {
                                                break;
                                            } else {
                                                echo "<tr class='dull'>";
                                                echo '<td>';
                                                echo '<b>'.$terms[$termCount][1].'</b>';
                                                echo '</td>';
                                                echo '<td colspan=6>';
                                                echo dateConvertBack($guid, $terms[$termCount][0]);
                                                echo '</td>';
                                                echo '</tr>';
                                                ++$termCount;
                                            }
                                        }

                                        //Spit out row for special day
                                        while ($lesson['1'] >= @$specials[$specialCount][0] and $specialCount < count($specials)) {
                                            echo "<tr class='dull'>";
                                            echo '<td>';
                                            echo '<b>'.$specials[$specialCount][1].'</b>';
                                            echo '</td>';
                                            echo '<td colspan=6>';
                                            echo $specials[$specialCount][2];
                                            echo '</td>';
                                            echo '</tr>';
                                            ++$specialCount;
                                        }

                                        //COLOR ROW BY STATUS!
                                        if ($lesson[8] != 'School Closure') {
                                            echo "<tr class=$rowNum>";
                                            echo "<td $style>";
                                            echo "<b>".__('Lesson')." ".($classCount + 1)."</b>";
                                            echo "</td>";
                                            echo "<td $style>";
                                            echo '<b>'.dateConvertBack($guid, $lesson['1']).'</b><br/>';
                                            echo Format::dateReadable($lesson['1'], '%A').'<br/>';
                                            echo Format::dateReadable($lesson['1'], '%B').'<br/>';
                                            if ($lesson[8] == 'Timing Change') {
                                                echo '<u>'.$lesson[8].'</u><br/><i>('.substr($lesson[9], 0, 5).'-'.substr($lesson[10], 0, 5).')</i>';
                                            }
                                            echo '</td>';
                                            echo "<td $style>";
                                            echo $lesson['4'].'<br/>';
                                            echo "<span style='font-size: 85%; font-style: italic'>".substr($lesson['2'], 0, 5).' - '.substr($lesson['3'], 0, 5).'</span>';
                                            echo '</td>';
                                            echo "<td $style>";
                                            if ($lesson['0'] == 'Planned') {
                                                echo '<b>'.$lesson['5'].'</b><br/>';
                                                $unit = getUnit($connection2, $lesson[11], $lesson[13]);
                                                if (isset($unit[0])) {
                                                    echo "<span style='font-size: 85%; font-style: italic'>";
                                                    echo $unit[0];
                                                    if (isset($unit[1])) {
                                                        if ($unit[1] != '') {
                                                            echo '<br/><i>'.$unit[1].' Unit</i>';
                                                        }
                                                    }
                                                    echo '</span>';
                                                }
                                            }
                                            echo '</td>';
                                            echo "<td $style>";
                                            if ($lesson['0'] == 'Unplanned') {
                                                echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Planner/planner_add.php&viewBy=$viewBy&gibbonCourseClassID=$gibbonCourseClassID&date=".$lesson[1].'&timeStart='.$lesson[2].'&timeEnd='.$lesson[3]."&subView=$subView'><img style='margin-bottom: -4px' title='".__('Add')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new.png'/></a>";
                                            } else {
                                                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Planner/planner_view_full.php&gibbonPlannerEntryID='.$lesson[12]."&viewBy=class&gibbonCourseClassID=$gibbonCourseClassID&width=1000&height=550&subView=$subView'><img title='".__('View')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/plus.png'/></a> ";
                                                if ((($highestAction == 'Lesson Planner_viewAllEditMyClasses' and $teacher == true) or $highestAction == 'Lesson Planner_viewEditAllClasses')) {
                                                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Planner/planner_edit.php&gibbonPlannerEntryID='.$lesson[12]."&viewBy=$viewBy&gibbonCourseClassID=$gibbonCourseClassID&subView=$subView'><img title='".__('Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                                                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Planner/planner_bump.php&gibbonPlannerEntryID='.$lesson[12]."&viewBy=$viewBy&gibbonCourseClassID=$gibbonCourseClassID&subView=$subView'><img title='".__('Bump')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_right.png'/></a>";
                                                    echo "<a class='thickbox' href='".$_SESSION[$guid]['absoluteURL'].'/fullscreen.php?q=/modules/Planner/planner_delete.php&gibbonPlannerEntryID='.$lesson[12]."&viewBy=$viewBy&gibbonCourseClassID=$gibbonCourseClassID&date=$date&subView=$subView&width=650&height=135'><img title='".__('Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a>";
                                                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Planner/planner_duplicate.php&gibbonPlannerEntryID='.$lesson[12]."&viewBy=$viewBy&gibbonCourseClassID=$gibbonCourseClassID&date=$date&subView=$subView'><img style='margin-left: 3px' title='".__('Duplicate')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/copy.png'/></a>";
                                                }
                                            }
                                            echo '</td>';
                                            echo '</tr>';
                                            ++$classCount;
                                        }

                                        //Spit out row for end of term/year
                                        while ($lesson['1'] >= @$terms[$termCount][0] and $termCount < count($terms) and substr($terms[$termCount][1], 0, 3) == 'End') {
                                            echo "<tr class='dull'>";
                                            echo '<td>';
                                            echo '<b>'.$terms[$termCount][1].'</b>';
                                            echo '</td>';
                                            echo '<td colspan=6>';
                                            echo dateConvertBack($guid, $terms[$termCount][0]);
                                            echo '</td>';
                                            echo '</tr>';
                                            ++$termCount;
                                        }
                                    }

                                    if (@$terms[$termCount][0] != '') {
                                        echo "<tr class='dull'>";
                                        echo '<td>';
                                        echo '<b><u>'.$terms[$termCount][1].'</u></b>';
                                        echo '</td>';
                                        echo '<td colspan=6>';
                                        echo dateConvertBack($guid, $terms[$termCount][0]);
                                        echo '</td>';
                                        echo '</tr>';
                                    }
                                    echo '</table>';
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    if ($gibbonPersonID != '') {
        //Print sidebar
        $_SESSION[$guid]['sidebarExtra'] = sidebarExtra($guid, $connection2, $todayStamp, $gibbonPersonID, $dateStamp, $gibbonCourseClassID);
    }
}
