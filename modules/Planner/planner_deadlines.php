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

$style = '';

$highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
if (isActionAccessible($guid, $connection2, '/modules/Planner/planner_deadlines.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Set variables
    $today = date('Y-m-d');

    //Proceed!
    //Get viewBy, date and class variables
    $params = '';
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
        $params = "&viewBy=date&date=$date";
    } elseif ($viewBy == 'class') {
        $class = null;
        if (isset($_GET['class'])) {
            $class = $_GET['class'];
        }
        $gibbonCourseClassID = $_GET['gibbonCourseClassID'];
        $params = "&viewBy=class&class=$class&gibbonCourseClassID=$gibbonCourseClassID";
    }
    list($todayYear, $todayMonth, $todayDay) = explode('-', $today);
    $todayStamp = mktime(0, 0, 0, $todayMonth, $todayDay, $todayYear);
    $show = null;
    if (isset($_GET['show'])) {
        $show = $_GET['show'];
    }
    $gibbonCourseClassIDFilter = null;
    if (isset($_GET['gibbonCourseClassIDFilter'])) {
        $gibbonCourseClassIDFilter = $_GET['gibbonCourseClassIDFilter'];
    }
    $gibbonPersonID = null;
    if (isset($_GET['search'])) {
        $gibbonPersonID = $_GET['search'];
    }

    //My children's classes
    if ($highestAction == 'Lesson Planner_viewMyChildrensClasses') {
        echo "<div class='trail'>";
        echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/planner.php'>".__($guid, 'My Children\'s Classes')."</a> > </div><div class='trailEnd'>".__($guid, 'Homework + Deadlines').'</div>';
        echo '</div>';

        //Test data access field for permission
        try {
            $data = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
            $sql = "SELECT * FROM gibbonFamilyAdult WHERE gibbonPersonID=:gibbonPersonID AND childDataAccess='Y'";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }
        if ($result->rowCount() < 1) {
            echo "<div class='error'>";
            echo __($guid, 'Access denied.');
            echo '</div>';
        } else {
            //Get child list
            $count = 0;
            $options = '';
            while ($row = $result->fetch()) {
                try {
                    $dataChild = array('gibbonFamilyID' => $row['gibbonFamilyID'], 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                    $sqlChild = "SELECT * FROM gibbonFamilyChild JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonFamilyID=:gibbonFamilyID AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY surname, preferredName ";
                    $resultChild = $connection2->prepare($sqlChild);
                    $resultChild->execute($dataChild);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }

                while ($rowChild = $resultChild->fetch()) {
                    $select = '';
                    if ($rowChild['gibbonPersonID'] == $gibbonPersonID) {
                        $select = 'selected';
                    }
                    $options = $options."<option $select value='".$rowChild['gibbonPersonID']."'>".$rowChild['surname'].', '.$rowChild['preferredName'].'</option>';
                    $gibbonPersonIDArray[$count] = $rowChild['gibbonPersonID'];
                    ++$count;
                }
            }

            if ($count == 0) {
                echo "<div class='error'>";
                echo __($guid, 'Access denied.');
                echo '</div>';
            } elseif ($count == 1) {
                $gibbonPersonID = $gibbonPersonIDArray[0];
            } else {
                echo '<h3>';
                echo __($guid, 'Choose');
                echo '</h3>';

                ?>
				<form method="get" action="<?php echo $_SESSION[$guid]['absoluteURL']?>/index.php">
					<table class='noIntBorder' cellspacing='0' style="width: 100%">	
						<tr><td style="width: 30%"></td><td></td></tr>
						<tr>
							<td> 
								<b><?php echo __($guid, 'Search For') ?></b><br/>
								<span class="emphasis small"><?php echo __($guid, 'Preferred, surname, username.') ?></span>
							</td>
							<td class="right">
								<select name="search" id="search" class="standardWidth">
									<option value=""></value>
									<?php echo $options;
                ?> 
								</select>
							</td>
						</tr>
						<tr>
							<td colspan=2 class="right">
								<input type="hidden" name="q" value="/modules/<?php echo $_SESSION[$guid]['module'] ?>/planner_deadlines.php">
								<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
								<?php
                                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/planner.php'>".__($guid, 'Clear Search').'</a>';
                ?>
								<input type="submit" value="<?php echo __($guid, 'Submit'); ?>">
							</td>
						</tr>
					</table>
				</form>
				<?php

            }

            if ($gibbonPersonID != '' and $count > 0) {
                //Confirm access to this student
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
                    $rowChild = $resultChild->fetch();

                    echo '<h3>';
                    echo __($guid, 'Upcoming Deadlines');
                    echo '</h3>';

                    $proceed = true;
                    if ($viewBy == 'class') {
                        if ($gibbonCourseClassID == '') {
                            $proceed = false;
                        } else {
                            try {
                                $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $gibbonPersonID, 'gibbonCourseClassID' => $gibbonCourseClassID);
                                $sql = "SELECT gibbonCourse.gibbonCourseID, gibbonCourseClass.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourseClassPerson JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID AND role='Teacher' ORDER BY course, class";
                                $result = $connection2->prepare($sql);
                                $result->execute($data);
                            } catch (PDOException $e) {
                                echo "<div class='error'>".$e->getMessage().'</div>';
                            }
                            if ($result->rowCount() != 1) {
                                $proceed = false;
                            }
                        }
                    }

                    if ($proceed == false) {
                        echo "<div class='error'>";
                        echo __($guid, 'Your request failed because you do not have access to this action.');
                        echo '</div>';
                    } else {
                        try {
                            $data = array('gibbonPersonID' => $gibbonPersonID, 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                            $sql = "
							(SELECT 'teacherRecorded' AS type, gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, date, timeStart, timeEnd, viewableStudents, viewableParents, homework, homeworkDueDateTime, role FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left' AND homework='Y' AND (role='Teacher' OR (role='Student' AND viewableStudents='Y')) AND homeworkDueDateTime>'".date('Y-m-d H:i:s')."' AND ((date<'".date('Y-m-d')."') OR (date='".date('Y-m-d')."' AND timeEnd<='".date('H:i:s')."')))
							UNION
							(SELECT 'studentRecorded' AS type, gibbonPlannerEntry.gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, date, timeStart, timeEnd, 'Y' AS viewableStudents, 'Y' AS viewableParents, 'Y' AS homework, gibbonPlannerEntryStudentHomework.homeworkDueDateTime, role FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) JOIN gibbonPlannerEntryStudentHomework ON (gibbonPlannerEntryStudentHomework.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID AND gibbonPlannerEntryStudentHomework.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left' AND (role='Teacher' OR (role='Student' AND viewableStudents='Y')) AND gibbonPlannerEntryStudentHomework.homeworkDueDateTime>'".date('Y-m-d H:i:s')."' AND ((date<'".date('Y-m-d')."') OR (date='".date('Y-m-d')."' AND timeEnd<='".date('H:i:s')."')))
							ORDER BY homeworkDueDateTime, type";
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }

                        if ($result->rowCount() < 1) {
                            echo "<div class='success'>";
                            echo __($guid, 'No upcoming deadlines!');
                            echo '</div>';
                        } else {
                            echo '<ol>';
                            while ($row = $result->fetch()) {
                                $diff = (strtotime(substr($row['homeworkDueDateTime'], 0, 10)) - strtotime(date('Y-m-d'))) / 86400;
                                $style = "style='padding-right: 3px;'";
                                if ($diff < 2) {
                                    $style = "style='padding-right: 3px; border-right: 10px solid #cc0000'";
                                } elseif ($diff < 4) {
                                    $style = "style='padding-right: 3px; border-right: 10px solid #D87718'";
                                }
                                echo "<li $style>";
                                if ($viewBy == 'class') {
                                    echo "<b><a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/planner_view_full.php&gibbonPlannerEntryID='.$row['gibbonPlannerEntryID']."&viewBy=class&gibbonCourseClassID=$gibbonCourseClassID&search=".$gibbonPersonID."'>".$row['course'].'.'.$row['class'].'</a> - '.$row['name'].'</b><br/>';
                                } else {
                                    echo "<b><a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/planner_view_full.php&gibbonPlannerEntryID='.$row['gibbonPlannerEntryID']."&viewBy=date&date=$date&search=".$gibbonPersonID."'>".$row['course'].'.'.$row['class'].'</a> - '.$row['name'].'</b><br/>';
                                }
                                echo "<span style='margin-left: 15px; font-style: italic'>Due at ".substr($row['homeworkDueDateTime'], 11, 5).' on '.dateConvertBack($guid, substr($row['homeworkDueDateTime'], 0, 10));
                                echo '</li>';
                            }
                            echo '</ol>';
                        }
                    }

                    $style = '';

                    echo '<h3>';
                    echo __($guid, 'All Homework');
                    echo '</h3>';

                    $filter = null;
                    $filter2 = null;
                    $data = array();
                    if ($gibbonCourseClassIDFilter != '') {
                        $data['gibbonCourseClassIDFilter'] = $gibbonCourseClassIDFilter;
                        $data['gibbonCourseClassIDFilter2'] = $gibbonCourseClassIDFilter;
                        $filter = ' AND gibbonPlannerEntry.gibbonCourseClassID=:gibbonCourseClassIDFilter';
                        $filte2 = ' AND gibbonPlannerEntry.gibbonCourseClassID=:gibbonCourseClassIDFilte2';
                    }

                    try {
                        $data['gibbonPersonID'] = $gibbonPersonID;
                        $data['gibbonSchoolYearID'] = $_SESSION[$guid]['gibbonSchoolYearID'];
                        $sql = "
						(SELECT 'teacherRecorded' AS type, gibbonPlannerEntryID, gibbonUnitID, gibbonHookID, gibbonPlannerEntry.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, date, timeStart, timeEnd, viewableStudents, viewableParents, homework, role, homeworkDueDateTime, homeworkDetails, homeworkSubmission, homeworkSubmissionRequired FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left' AND homework='Y' AND gibbonSchoolYearID=:gibbonSchoolYearID AND (date<'".date('Y-m-d')."' OR (date='".date('Y-m-d')."' AND timeEnd<='".date('H:i:s')."')) $filter)
						UNION
						(SELECT 'studentRecorded' AS type, gibbonPlannerEntry.gibbonPlannerEntryID, gibbonUnitID, gibbonHookID, gibbonPlannerEntry.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, date, timeStart, timeEnd, 'Y' AS viewableStudents, 'Y' AS viewableParents, 'Y' AS homework, role, gibbonPlannerEntryStudentHomework.homeworkDueDateTime AS homeworkDueDateTime, gibbonPlannerEntryStudentHomework.homeworkDetails AS homeworkDetails, 'N' AS homeworkSubmission, '' AS homeworkSubmissionRequired FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) JOIN gibbonPlannerEntryStudentHomework ON (gibbonPlannerEntryStudentHomework.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID AND gibbonPlannerEntryStudentHomework.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID) WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left' AND gibbonSchoolYearID=:gibbonSchoolYearID AND (date<'".date('Y-m-d')."' OR (date='".date('Y-m-d')."' AND timeEnd<='".date('H:i:s')."')) $filter)
						ORDER BY date DESC, timeStart DESC";
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }

                    //Only show add if user has edit rights
                    if ($result->rowCount() < 1) {
                        echo "<div class='error'>";
                        echo __($guid, 'There are no records to display.');
                        echo '</div>';
                    } else {
                        echo "<div class='linkTop'>";
                        echo "<form method='get' action='".$_SESSION[$guid]['absoluteURL']."/index.php'>";
                        echo "<table class='blank' cellspacing='0' style='float: right; width: 250px'>";
                        echo '<tr>';
                        echo "<td style='width: 190px'>";
                        echo "<select name='gibbonCourseClassIDFilter' id='gibbonCourseClassIDFilter' style='width:190px'>";
                        echo "<option value=''></option>";
                        try {
                            $dataSelect = array('gibbonPersonID' => $gibbonPersonID, 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'date' => date('Y-m-d'));
                            $sqlSelect = "SELECT DISTINCT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.gibbonCourseClassID FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left' AND homework='Y' AND gibbonSchoolYearID=:gibbonSchoolYearID AND date<=:date ORDER BY course, class";
                            $resultSelect = $connection2->prepare($sqlSelect);
                            $resultSelect->execute($dataSelect);
                        } catch (PDOException $e) {
                        }
                        while ($rowSelect = $resultSelect->fetch()) {
                            $selected = '';
                            if ($rowSelect['gibbonCourseClassID'] == $gibbonCourseClassIDFilter) {
                                $selected = 'selected';
                            }
                            echo "<option $selected value='".$rowSelect['gibbonCourseClassID']."'>".htmlPrep($rowSelect['course']).'.'.htmlPrep($rowSelect['class']).'</option>';
                        }
                        echo '</select>';
                        echo '</td>';
                        echo "<td class='right'>";
                        echo "<input type='submit' value='".__($guid, 'Go')."' style='margin-right: 0px'>";
                        echo "<input type='hidden' name='q' value='/modules/Planner/planner_deadlines.php'>";
                        echo "<input type='hidden' name='search' value='$gibbonPersonID'>";
                        echo '</td>';
                        echo '</tr>';
                        echo '</table>';
                        echo '</form>';
                        echo '</div>';
                        echo "<table cellspacing='0' style='width: 100%'>";
                        echo "<tr class='head'>";
                        echo '<th>';
                        echo __($guid, 'Class').'</br>';
                        echo "<span style='font-size: 85%; font-style: italic'>".__($guid, 'Date').'</span>';
                        echo '</th>';
                        echo '<th>';
                        echo __($guid, 'Lesson').'</br>';
                        echo "<span style='font-size: 85%; font-style: italic'>".__($guid, 'Unit').'</span>';
                        echo '</th>';
                        echo "<th style='min-width: 25%'>";
                        echo __($guid, 'Type').'<br/>';
                        echo "<span style='font-size: 85%; font-style: italic'>".__($guid, 'Details').'</span>';
                        echo '</th>';
                        echo '<th>';
                        echo __($guid, 'Deadline');
                        echo '</th>';
                        echo '<th>';
                        echo sprintf(__($guid, 'Online%1$sSubmission'), '<br/>');
                        echo '</th>';
                        echo '<th>';
                        echo __($guid, 'Actions');
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
                                echo '<b>'.$row['course'].'.'.$row['class'].'</b></br>';
                                echo "<span style='font-size: 85%; font-style: italic'>".dateConvertBack($guid, $row['date']).'</span>';
                                echo '</td>';
                                echo '<td>';
                                echo '<b>'.$row['name'].'</b><br/>';
                                echo "<span style='font-size: 85%; font-style: italic'>";
                                $unit = getUnit($connection2, $row['gibbonUnitID'], $row['gibbonHookID'], $row['gibbonCourseClassID']);
                                if (isset($unit[0])) {
                                    echo $unit[0];
                                    if ($unit[1] != '') {
                                        echo '<br/><i>'.$unit[1].' Unit</i>';
                                    }
                                }
                                echo '</span>';
                                echo '</td>';
                                echo '<td>';
                                if ($row['type'] == 'teacherRecorded') {
                                    echo 'Teacher Recorded';
                                } else {
                                    echo 'Student Recorded';
                                }
                                echo  '<br/>';
                                echo "<span style='font-size: 85%; font-style: italic'>";
                                if ($row['homeworkDetails'] != '') {
                                    if (strlen(strip_tags($row['homeworkDetails'])) < 21) {
                                        echo strip_tags($row['homeworkDetails']);
                                    } else {
                                        echo "<span $style title='".htmlPrep(strip_tags($row['homeworkDetails']))."'>".substr(strip_tags($row['homeworkDetails']), 0, 20).'...</span>';
                                    }
                                }
                                echo '</span>';
                                echo '</td>';
                                echo '<td>';
                                echo dateConvertBack($guid, substr($row['homeworkDueDateTime'], 0, 10));
                                echo '</td>';
                                echo '<td>';
                                if ($row['homeworkSubmission'] == 'Y') {
                                    echo '<b>'.$row['homeworkSubmissionRequired'].'<br/></b>';
                                    if ($row['role'] == 'Student') {
                                        try {
                                            $dataVersion = array('gibbonPlannerEntryID' => $row['gibbonPlannerEntryID'], 'gibbonPersonID' => $gibbonPersonID);
                                            $sqlVersion = 'SELECT * FROM gibbonPlannerEntryHomework WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPersonID=:gibbonPersonID ORDER BY count DESC';
                                            $resultVersion = $connection2->prepare($sqlVersion);
                                            $resultVersion->execute($dataVersion);
                                        } catch (PDOException $e) {
                                            echo "<div class='error'>".$e->getMessage().'</div>';
                                        }

                                        if ($resultVersion->rowCount() < 1) {
                                            //Before deadline
                                                        if (date('Y-m-d H:i:s') < $row['homeworkDueDateTime']) {
                                                            echo __($guid, 'Pending');
                                                        }
                                                        //After
                                                        else {
                                                            if ($row['homeworkSubmissionRequired'] == 'Compulsory') {
                                                                echo "<div style='color: #ff0000; font-weight: bold; border: 2px solid #ff0000; padding: 2px 4px; margin: 2px 0px'>".__($guid, 'Incomplete').'</div>';
                                                            } else {
                                                                echo  __($guid, 'Not submitted online');
                                                            }
                                                        }
                                        } else {
                                            $rowVersion = $resultVersion->fetch();
                                            if ($rowVersion['status'] == 'On Time' or $rowVersion['status'] == 'Exemption') {
                                                echo $rowVersion['status'];
                                            } else {
                                                if ($row['homeworkSubmissionRequired'] == 'Compulsory') {
                                                    echo "<div style='color: #ff0000; font-weight: bold; border: 2px solid #ff0000; padding: 2px 4px; margin: 2px 0px'>".$rowVersion['status'].'</div>';
                                                } else {
                                                    echo $rowVersion['status'];
                                                }
                                            }
                                        }
                                    }
                                }
                                echo '</td>';
                                echo '<td>';
                                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/planner_view_full.php&search=$gibbonPersonID&gibbonPlannerEntryID=".$row['gibbonPlannerEntryID'].'&viewBy=class&gibbonCourseClassID='.$row['gibbonCourseClassID']."'><img title='".__($guid, 'View')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/plus.png'/></a> ";
                                echo '</td>';
                                echo '</tr>';
                            }
                        }
                        echo '</table>';
                    }
                }
            }
        }
    } elseif ($highestAction == 'Lesson Planner_viewMyClasses' or $highestAction == 'Lesson Planner_viewAllEditMyClasses' or $highestAction == 'Lesson Planner_viewEditAllClasses') {
        //Get current role category
        $category = getRoleCategory($_SESSION[$guid]['gibbonRoleIDCurrent'], $connection2);

        echo "<div class='trail'>";
        echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/planner.php$params'>".__($guid, 'Planner')."</a> > </div><div class='trailEnd'>".__($guid, 'Homework + Deadlines').'</div>';
        echo '</div>';

        //Get Smart Workflow help message
        $category = getRoleCategory($_SESSION[$guid]['gibbonRoleIDCurrent'], $connection2);
        if ($category == 'Staff') {
            $smartWorkflowHelp = getSmartWorkflowHelp($connection2, $guid, 4);
            if ($smartWorkflowHelp != false) {
                echo $smartWorkflowHelp;
            }
        }

        //Proceed!
        if (isset($_GET['return'])) {
            returnProcess($guid, $_GET['return'], null, null);
        }

        echo '<h3>';
        echo __($guid, 'Upcoming Deadlines');
        echo '</h3>';

        $proceed = true;
        if ($viewBy == 'class') {
            if ($gibbonCourseClassID == '') {
                $proceed = false;
            } else {
                try {
                    if ($highestAction == 'Lesson Planner_viewEditAllClasses') {
                        $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonCourseClassID' => $gibbonCourseClassID);
                        $sql = 'SELECT gibbonCourse.gibbonCourseID, gibbonCourseClass.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourseClass JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID ORDER BY course, class';
                    } else {
                        $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                        $sql = "SELECT gibbonCourse.gibbonCourseID, gibbonCourseClass.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourseClassPerson JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID AND role='Teacher' ORDER BY course, class";
                    }
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }
                if ($result->rowCount() != 1) {
                    $proceed = false;
                }
            }
        }

        if ($proceed == false) {
            echo "<div class='error'>";
            echo __($guid, 'Your request failed because you do not have access to this action.');
            echo '</div>';
        } else {
            try {
                if ($highestAction == 'Lesson Planner_viewEditAllClasses' and $show == 'all') {
                    $data = array('homeworkDueDateTime' => date('Y-m-d H:i:s'), 'date1' => date('Y-m-d'), 'date2' => date('Y-m-d'), 'timeEnd' => date('H:i:s'));
                    $sql = "SELECT gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, date, timeStart, timeEnd, viewableStudents, viewableParents, homework, homeworkDueDateTime FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE homework='Y' AND homeworkDueDateTime>:homeworkDueDateTime AND ((date<:date1) OR (date=:date2 AND timeEnd<=:timeEnd)) ORDER BY homeworkDueDateTime";
                } else {
                    $data = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                    $sql = "
					(SELECT 'teacherRecorded' AS type, gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, date, timeStart, timeEnd, viewableStudents, viewableParents, homework, homeworkDueDateTime, role FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left' AND homework='Y' AND (role='Teacher' OR (role='Student' AND viewableStudents='Y')) AND homeworkDueDateTime>'".date('Y-m-d H:i:s')."' AND ((date<'".date('Y-m-d')."') OR (date='".date('Y-m-d')."' AND timeEnd<='".date('H:i:s')."')))
					UNION
					(SELECT 'studentRecorded' AS type, gibbonPlannerEntry.gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, date, timeStart, timeEnd, 'Y' AS viewableStudents, 'Y' AS viewableParents, 'Y' AS homework, gibbonPlannerEntryStudentHomework.homeworkDueDateTime, role FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) JOIN gibbonPlannerEntryStudentHomework ON (gibbonPlannerEntryStudentHomework.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID AND gibbonPlannerEntryStudentHomework.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left' AND (role='Teacher' OR (role='Student' AND viewableStudents='Y')) AND gibbonPlannerEntryStudentHomework.homeworkDueDateTime>'".date('Y-m-d H:i:s')."' AND ((date<'".date('Y-m-d')."') OR (date='".date('Y-m-d')."' AND timeEnd<='".date('H:i:s')."')))
					 ORDER BY homeworkDueDateTime, type";
                }
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            if ($result->rowCount() < 1) {
                echo "<div class='success'>";
                echo __($guid, 'No upcoming deadlines!');
                echo '</div>';
            } else {
                echo '<ol>';
                while ($row = $result->fetch()) {
                    $diff = (strtotime(substr($row['homeworkDueDateTime'], 0, 10)) - strtotime(date('Y-m-d'))) / 86400;
                    $style = 'padding-right: 3px;';
                    if ($category == 'Student') {
                        if ($row['type'] == 'teacherRecorded') {
                            //Calculate style for student-specified completion
                            try {
                                $dataCompletion = array('gibbonPlannerEntryID' => $row['gibbonPlannerEntryID'], 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                                $sqlCompletion = "SELECT gibbonPlannerEntryID FROM gibbonPlannerEntryStudentTracker WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPersonID=:gibbonPersonID AND homeworkComplete='Y'";
                                $resultCompletion = $connection2->prepare($sqlCompletion);
                                $resultCompletion->execute($dataCompletion);
                            } catch (PDOException $e) {
                                echo "<div class='error'>".$e->getMessage().'</div>';
                            }
                            if ($resultCompletion->rowCount() == 1) {
                                $style .= '; background-color: #B3EFC2';
                            }
                            //Calculate style for online submission completion
                            try {
                                $dataCompletion = array('gibbonPlannerEntryID' => $row['gibbonPlannerEntryID'], 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                                $sqlCompletion = "SELECT gibbonPlannerEntryID FROM gibbonPlannerEntryHomework WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPersonID=:gibbonPersonID AND version='Final'";
                                $resultCompletion = $connection2->prepare($sqlCompletion);
                                $resultCompletion->execute($dataCompletion);
                            } catch (PDOException $e) {
                                echo "<div class='error'>".$e->getMessage().'</div>';
                            }
                            if ($resultCompletion->rowCount() == 1) {
                                $style .= '; background-color: #B3EFC2';
                            }
                        } else {
                            //Calculate style for student-recorded homework
                            try {
                                $dataCompletion = array('gibbonPlannerEntryID' => $row['gibbonPlannerEntryID'], 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                                $sqlCompletion = "SELECT gibbonPlannerEntryID FROM gibbonPlannerEntryStudentHomework WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPersonID=:gibbonPersonID AND homeworkComplete='Y'";
                                $resultCompletion = $connection2->prepare($sqlCompletion);
                                $resultCompletion->execute($dataCompletion);
                            } catch (PDOException $e) {
                                echo "<div class='error'>".$e->getMessage().'</div>';
                            }
                            if ($resultCompletion->rowCount() == 1) {
                                $style .= '; background-color: #B3EFC2';
                            }
                        }
                    }

                    //Calculate style for deadline
                    if ($diff < 2) {
                        $style .= '; border-right: 10px solid #cc0000';
                    } elseif ($diff < 4) {
                        $style .= '; border-right: 10px solid #D87718';
                    }

                    echo "<li style='$style'>";
                    if ($viewBy == 'class') {
                        echo "<b><a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/planner_view_full.php&gibbonPlannerEntryID='.$row['gibbonPlannerEntryID']."&viewBy=class&gibbonCourseClassID=$gibbonCourseClassID'>".$row['course'].'.'.$row['class'].'</a> - '.$row['name'].'</b><br/>';
                    } else {
                        echo "<b><a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/planner_view_full.php&gibbonPlannerEntryID='.$row['gibbonPlannerEntryID']."&viewBy=date&date=$date'>".$row['course'].'.'.$row['class'].'</a> - '.$row['name'].'</b><br/>';
                    }
                    echo "<span style='margin-left: 15px; font-style: italic'>Due at ".substr($row['homeworkDueDateTime'], 11, 5).' on '.dateConvertBack($guid, substr($row['homeworkDueDateTime'], 0, 10));
                    echo '</li>';
                }
                echo '</ol>';
            }
        }

        echo '<h3>';
        echo __($guid, 'All Homework');
        echo '</h3>';

        $completionArray = array();
        if ($category == 'Student') {
            try {
                $dataCompletion = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonSchoolYearID2' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID2' => $_SESSION[$guid]['gibbonPersonID']);
                $sqlCompletion = "
				(SELECT 'teacherRecorded' AS type, gibbonPlannerEntryStudentTracker.gibbonPlannerEntryID FROM gibbonPlannerEntryStudentTracker JOIN gibbonPlannerEntry ON (gibbonPlannerEntryStudentTracker.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID) JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID AND homeworkComplete='Y')
				UNION
				(SELECT 'studentRecorded' AS type, gibbonPlannerEntry.gibbonPlannerEntryID FROM gibbonPlannerEntryStudentHomework JOIN gibbonPlannerEntry ON (gibbonPlannerEntryStudentHomework.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID) JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID2 AND gibbonPersonID=:gibbonPersonID2 AND homeworkComplete='Y')
				ORDER BY gibbonPlannerEntryID, type
				";
                $resultCompletion = $connection2->prepare($sqlCompletion);
                $resultCompletion->execute($dataCompletion);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }
            while ($rowCompletion = $resultCompletion->fetch()) {
                $completionArray[$rowCompletion['gibbonPlannerEntryID']][0] = 'checked';
                $completionArray[$rowCompletion['gibbonPlannerEntryID']][1] = $rowCompletion['type'];
            }
        }

        $filter = null;
        $filter2 = null;
        $data = array();
        if ($gibbonCourseClassIDFilter != '') {
            $data['gibbonCourseClassIDFilter'] = $gibbonCourseClassIDFilter;
            $data['gibbonCourseClassIDFilter2'] = $gibbonCourseClassIDFilter;
            $filter = ' AND gibbonPlannerEntry.gibbonCourseClassID=:gibbonCourseClassIDFilter';
            $filte2 = ' AND gibbonPlannerEntry.gibbonCourseClassID=:gibbonCourseClassIDFilte2';
        }

        try {
            if ($highestAction == 'Lesson Planner_viewEditAllClasses' and $show == 'all') {
                $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'date1' => date('Y-m-d'), 'date2' => date('Y-m-d'), 'timeEnd' => date('H:i:s'));
                $sql = "SELECT gibbonPlannerEntryID, gibbonUnitID, gibbonHookID, gibbonPlannerEntry.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.gibbonCourseClassID, gibbonPlannerEntry.name, date, timeStart, timeEnd, 'Y' AS viewableStudents, 'Y' AS viewableParents, homework, homeworkDueDateTime, homeworkDetails, homeworkSubmission, homeworkSubmissionRequired, homeworkCrowdAssess FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE homework='Y' AND gibbonSchoolYearID=:gibbonSchoolYearID AND (date<:date1 OR (date=:date2 AND timeEnd<=:timeEnd)) $filter ORDER BY date DESC, timeStart DESC";
            } else {
                $data['gibbonPersonID'] = $_SESSION[$guid]['gibbonPersonID'];
                $data['gibbonSchoolYearID'] = $_SESSION[$guid]['gibbonSchoolYearID'];
                $data['gibbonPersonID'] = $_SESSION[$guid]['gibbonPersonID'];
                $sql = "
				(SELECT 'teacherRecorded' AS type, gibbonPlannerEntryID, gibbonUnitID, gibbonHookID, gibbonPlannerEntry.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, date, timeStart, timeEnd, viewableStudents, viewableParents, homework, role, homeworkDueDateTime, homeworkDetails, homeworkSubmission, homeworkSubmissionRequired FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left' AND homework='Y' AND gibbonSchoolYearID=:gibbonSchoolYearID AND ((date<'".date('Y-m-d')."') OR (date='".date('Y-m-d')."' AND timeEnd<='".date('H:i:s')."')) $filter)
				UNION
				(SELECT 'studentRecorded' AS type, gibbonPlannerEntry.gibbonPlannerEntryID, gibbonUnitID, gibbonHookID, gibbonPlannerEntry.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, date, timeStart, timeEnd, 'Y' AS viewableStudents, 'Y' AS viewableParents, 'Y' AS homework, role, gibbonPlannerEntryStudentHomework.homeworkDueDateTime AS homeworkDueDateTime, gibbonPlannerEntryStudentHomework.homeworkDetails AS homeworkDetails, 'N' AS homeworkSubmission, '' AS homeworkSubmissionRequired FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) JOIN gibbonPlannerEntryStudentHomework ON (gibbonPlannerEntryStudentHomework.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID AND gibbonPlannerEntryStudentHomework.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID) WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left' AND gibbonSchoolYearID=:gibbonSchoolYearID AND ((date<'".date('Y-m-d')."') OR (date='".date('Y-m-d')."' AND timeEnd<='".date('H:i:s')."')) $filter)
				ORDER BY date DESC, timeStart DESC";
            }
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        //Only show add if user has edit rights
        if ($result->rowCount() < 1) {
            echo "<div class='error'>";
            echo __($guid, 'There are no records to display.');
            echo '</div>';
        } else {
            echo "<div class='linkTop'>";
            echo "<form method='get' action='".$_SESSION[$guid]['absoluteURL']."/index.php'>";
            echo "<table class='blank' cellspacing='0' style='float: right; width: 250px'>";
            echo '<tr>';
            echo "<td style='width: 190px'>";
            echo "<select name='gibbonCourseClassIDFilter' id='gibbonCourseClassIDFilter' style='width:190px'>";
            echo "<option value=''></option>";
            try {
                if ($highestAction == 'Lesson Planner_viewEditAllClasses' and $show == 'all') {
                    $dataSelect = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'date' => date('Y-m-d'));
                    $sqlSelect = "SELECT DISTINCT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.gibbonCourseClassID FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE homework='Y' AND gibbonSchoolYearID=:gibbonSchoolYearID AND date<=:date ORDER BY course, class";
                } else {
                    $dataSelect = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'date' => date('Y-m-d'), 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                    $sqlSelect = "SELECT DISTINCT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.gibbonCourseClassID FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left' AND homework='Y' AND gibbonSchoolYearID=:gibbonSchoolYearID AND date<=:date ORDER BY course, class";
                }
                $resultSelect = $connection2->prepare($sqlSelect);
                $resultSelect->execute($dataSelect);
            } catch (PDOException $e) {
            }
            while ($rowSelect = $resultSelect->fetch()) {
                $selected = '';
                if ($rowSelect['gibbonCourseClassID'] == $gibbonCourseClassIDFilter) {
                    $selected = 'selected';
                }
                echo "<option $selected value='".$rowSelect['gibbonCourseClassID']."'>".htmlPrep($rowSelect['course']).'.'.htmlPrep($rowSelect['class']).'</option>';
            }
            echo '</select>';
            echo '</td>';
            echo "<td class='right'>";
            echo "<input type='submit' value='".__($guid, 'Go')."' style='margin-right: 0px'>";
            echo "<input type='hidden' name='q' value='/modules/Planner/planner_deadlines.php'>";
            echo '</td>';
            echo '</tr>';
            echo '</table>';
            echo '</form>';
            echo '</div>';
            echo "<form method='post' action='".$_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/planner_deadlinesProcess.php?viewBy=$viewBy&subView=$subView&address=".$_SESSION[$guid]['address']."&gibbonCourseClassIDFilter=$gibbonCourseClassIDFilter'>";
            echo "<table cellspacing='0' style='width: 100%; margin-top: 60px'>";

            if ($category == 'Student') {
                ?>
						<tr>
							<td class="right" colspan=7>
								<input type="submit" value="<?php echo __($guid, 'Submit'); ?>">
							</td>
						</tr>
						<?php

            }
            echo "<tr class='head'>";
            echo '<th>';
            echo __($guid, 'Class').'</br>';
            echo "<span style='font-size: 85%; font-style: italic'>".__($guid, 'Date').'</span>';
            echo '</th>';
            echo '<th>';
            echo __($guid, 'Lesson').'</br>';
            echo "<span style='font-size: 85%; font-style: italic'>".__($guid, 'Unit').'</span>';
            echo '</th>';
            echo "<th style='min-width: 25%'>";
            echo __($guid, 'Type').'<br/>';
            echo "<span style='font-size: 85%; font-style: italic'>".__($guid, 'Details').'</span>';
            echo '</th>';
            echo '<th>';
            echo __($guid, 'Deadline');
            echo '</th>';

            if ($category == 'Student') {
                echo '<th colspan=2>';
                echo __($guid, 'Complete?');
                echo '</th>';
            } else {
                echo '<th>';
                echo sprintf(__($guid, 'Online%1$sSubmission'), '<br/>');
                echo '</th>';
            }
            echo '<th>';
            echo __($guid, 'Actions');
            echo '</th>';
            echo '</tr>';

            $count = 0;
            $rowNum = 'odd';
            while ($row = $result->fetch()) {
                if (!($row['role'] == 'Student' and $row['viewableStudents'] == 'N')) {
                    if ($count % 2 == 0) {
                        $rowNum = 'even';
                    } else {
                        $rowNum = 'odd';
                    }
                    ++$count;

                            //Deal with homework completion
                            if ($category == 'Student') {
                                $now = date('Y-m-d H:i:s');
                                if (isset($completionArray[$row['gibbonPlannerEntryID']][0]) and isset($completionArray[$row['gibbonPlannerEntryID']][1])) {
                                    if ($completionArray[$row['gibbonPlannerEntryID']][1] == $row['type']) {
                                        $rowNum = 'current';
                                    }
                                } else {
                                    if ($row['homeworkDueDateTime'] < $now) {
                                        $rowNum = 'error';
                                    }
                                }
                                $status = '';
                                $completion = '';
                                if ($row['homeworkSubmission'] == 'Y') {
                                    $status = '<b>OS: '.$row['homeworkSubmissionRequired'].'</b><br/>';
                                    try {
                                        $dataVersion = array('gibbonPlannerEntryID' => $row['gibbonPlannerEntryID'], 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                                        $sqlVersion = 'SELECT * FROM gibbonPlannerEntryHomework WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPersonID=:gibbonPersonID ORDER BY count DESC';
                                        $resultVersion = $connection2->prepare($sqlVersion);
                                        $resultVersion->execute($dataVersion);
                                    } catch (PDOException $e) {
                                        echo "<div class='error'>".$e->getMessage().'</div>';
                                    }
                                    if ($resultVersion->rowCount() < 1) {
                                        //Before deadline
                                        if (date('Y-m-d H:i:s') < $row['homeworkDueDateTime']) {
                                            if ($row['homeworkSubmissionRequired'] == 'Compulsory') {
                                                $status .= 'Pending';
                                                $completion = "<input disabled type='checkbox'>";
                                            } else {
                                                $status .= __($guid, 'Pending');
                                                $completion = '<input '.$completionArray[$row['gibbonPlannerEntryID']]." name='complete-$count' type='checkbox'>";
                                            }
                                        }
                                        //After
                                        else {
                                            if ($row['homeworkSubmissionRequired'] == 'Compulsory') {
                                                $status .= "<div style='color: #ff0000; font-weight: bold; border: 2px solid #ff0000; padding: 2px 4px; margin: 2px 0px'>".__($guid, 'Incomplete').'</div>';
                                                $completion = "<input disabled type='checkbox'>";
                                            } else {
                                                $status .= __($guid, 'Not submitted online');
                                                @$completion = '<input '.$completionArray[$row['gibbonPlannerEntryID']]." name='complete-$count' type='checkbox'>";
                                            }
                                        }
                                    } else {
                                        $rowVersion = $resultVersion->fetch();
                                        if ($rowVersion['status'] == 'On Time' or $rowVersion['status'] == 'Exemption') {
                                            $status .= $rowVersion['status'];
                                            $completion = "<input disabled checked type='checkbox'>";
                                            $rowNum = 'current';
                                        } else {
                                            if ($row['homeworkSubmissionRequired'] == 'Compulsory') {
                                                $status .= "<div style='color: #ff0000; font-weight: bold; border: 2px solid #ff0000; padding: 2px 4px; margin: 2px 0px'>".$rowVersion['status'].'</div>';
                                                $completion = "<input disabled checked type='checkbox'>";
                                            } else {
                                                $status .= $rowVersion['status'];
                                                $completion = "<input disabled checked type='checkbox'>";
                                            }
                                        }
                                    }
                                } else {
                                    $completion = '<input ';
                                    if (isset($completionArray[$row['gibbonPlannerEntryID']][0]) and isset($completionArray[$row['gibbonPlannerEntryID']][1])) {
                                        if ($completionArray[$row['gibbonPlannerEntryID']][1] == $row['type']) {
                                            $completion .= $completionArray[$row['gibbonPlannerEntryID']][0];
                                        }
                                    }
                                    $completion .= " name='complete-$count' type='checkbox'>";
                                    $completion .= "<input type='hidden' name='completeType-$count' value='".$row['type']."'/>";
                                }
                            }

                            //COLOR ROW BY STATUS!
                            echo "<tr class=$rowNum>";
                    echo '<td>';
                    echo '<b>'.$row['course'].'.'.$row['class'].'</b></br>';
                    echo "<span style='font-size: 85%; font-style: italic'>".dateConvertBack($guid, $row['date']).'</span>';
                    echo '</td>';
                    echo '<td>';
                    echo '<b>'.$row['name'].'</b><br/>';
                    echo "<span style='font-size: 85%; font-style: italic'>";
                    $unit = getUnit($connection2, $row['gibbonUnitID'], $row['gibbonHookID'], $row['gibbonCourseClassID']);
                    if (isset($unit[0])) {
                        echo $unit[0];
                        if ($unit[1] != '') {
                            echo '<br/><i>'.$unit[1].' Unit</i>';
                        }
                    }
                    echo '</span>';
                    echo '</td>';
                    echo '<td>';
                    if ($row['type'] == 'teacherRecorded') {
                        echo 'Teacher Recorded';
                    } else {
                        echo 'Student Recorded';
                    }
                    echo  '<br/>';
                    echo "<span style='font-size: 85%; font-style: italic'>";
                    if ($row['homeworkDetails'] != '') {
                        if (strlen(strip_tags($row['homeworkDetails'])) < 21) {
                            echo strip_tags($row['homeworkDetails']);
                        } else {
                            echo "<span $style title='".htmlPrep(strip_tags($row['homeworkDetails']))."'>".substr(strip_tags($row['homeworkDetails']), 0, 20).'...</span>';
                        }
                    }
                    echo '</span>';
                    echo '</td>';
                    echo '<td>';
                    echo dateConvertBack($guid, substr($row['homeworkDueDateTime'], 0, 10));
                    echo '</td>';
                    if ($category == 'Student') {
                        echo '<td>';
                        echo $status;
                        echo '</td>';
                        echo '<td>';
                        echo $completion;
                        echo "<input type='hidden' name='count[]' value='$count'>";
                        echo "<input type='hidden' name='gibbonPlannerEntryID-$count' value='".$row['gibbonPlannerEntryID']."'>";
                        echo '</td>';
                    } else {
                        echo '<td>';
                        if ($row['homeworkSubmission'] == 'Y') {
                            echo '<b>'.$row['homeworkSubmissionRequired'].'</b><br/>';
                            if ($row['role'] == 'Teacher') {
                                try {
                                    $dataVersion = array('gibbonPlannerEntryID' => $row['gibbonPlannerEntryID']);
                                    $sqlVersion = "SELECT DISTINCT gibbonPlannerEntryHomework.gibbonPersonID FROM gibbonPlannerEntryHomework JOIN gibbonPerson ON (gibbonPlannerEntryHomework.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND version='Final' AND gibbonPlannerEntryHomework.status='On Time'";
                                    $resultVersion = $connection2->prepare($sqlVersion);
                                    $resultVersion->execute($dataVersion);
                                } catch (PDOException $e) {
                                    echo "<div class='error'>".$e->getMessage().'</div>';
                                }
                                $onTime = $resultVersion->rowCount();
                                echo "<span style='font-size: 85%; font-style: italic'>On Time: $onTime</span><br/>";

                                try {
                                    $dataVersion = array('gibbonPlannerEntryID' => $row['gibbonPlannerEntryID']);
                                    $sqlVersion = "SELECT DISTINCT gibbonPlannerEntryHomework.gibbonPersonID FROM gibbonPlannerEntryHomework JOIN gibbonPerson ON (gibbonPlannerEntryHomework.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND version='Final' AND gibbonPlannerEntryHomework.status='Late'";
                                    $resultVersion = $connection2->prepare($sqlVersion);
                                    $resultVersion->execute($dataVersion);
                                } catch (PDOException $e) {
                                    echo "<div class='error'>".$e->getMessage().'</div>';
                                }
                                $late = $resultVersion->rowCount();
                                echo "<span style='font-size: 85%; font-style: italic'>Late: $late</span><br/>";

                                try {
                                    $dataVersion = array('gibbonCourseClassID' => $row['gibbonCourseClassID']);
                                    $sqlVersion = "SELECT gibbonCourseClassPerson.gibbonPersonID FROM gibbonCourseClassPerson JOIN gibbonPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonCourseClassID=:gibbonCourseClassID AND role='Student' AND status='Full'";
                                    $resultVersion = $connection2->prepare($sqlVersion);
                                    $resultVersion->execute($dataVersion);
                                } catch (PDOException $e) {
                                    echo "<div class='error'>".$e->getMessage().'</div>';
                                }
                                $class = $resultVersion->rowCount();
                                if (date('Y-m-d H:i:s') < $row['homeworkDueDateTime']) {
                                    echo "<span style='font-size: 85%; font-style: italic'>".__($guid, 'Pending').': '.($class - $late - $onTime).'</span><br/>';
                                } else {
                                    if ($row['homeworkSubmissionRequired'] == 'Compulsory') {
                                        echo "<span style='font-size: 85%; font-style: italic'>".__($guid, 'Incomplete').': '.($class - $late - $onTime).'</span><br/>';
                                    } else {
                                        echo "<span style='font-size: 85%; font-style: italic'>".__($guid, 'Not Submitted Online').': '.($class - $late - $onTime).'</span><br/>';
                                    }
                                }
                            }
                        }
                        echo '</td>';
                    }
                    echo '<td>';
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/planner_view_full.php&search=$gibbonPersonID&gibbonPlannerEntryID=".$row['gibbonPlannerEntryID'].'&viewBy=class&gibbonCourseClassID='.$row['gibbonCourseClassID']."'><img title='".__($guid, 'View')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/plus.png'/></a> ";
                    echo '</td>';
                    echo '</tr>';
                }
            }
            if ($category == 'Student') {
                ?>
						<tr>
							<td class="right" colspan=7>
								<input type="submit" value="<?php echo __($guid, 'Submit'); ?>">
							</td>
						</tr>
						<?php

            }
            echo '</table>';
            echo '</form>';
        }
    }

    //Print sidebar
    $_SESSION[$guid]['sidebarExtra'] = sidebarExtra($guid, $connection2, $todayStamp, $_SESSION[$guid]['gibbonPersonID'], $dateStamp, $gibbonCourseClassID);
}
?>