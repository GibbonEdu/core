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

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Planner/units_edit_working_add.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        echo "<div class='error'>";
        echo __($guid, 'The highest grouped action cannot be determined.');
        echo '</div>';
    } else {
        //IF UNIT DOES NOT CONTAIN HYPHEN, IT IS A GIBBON UNIT
        $gibbonUnitID = $_GET['gibbonUnitID'];
        if (strpos($gibbonUnitID, '-') == false) {
            $hooked = false;
        } else {
            $hooked = true;
            $gibbonHookIDToken = substr($gibbonUnitID, 11);
            $gibbonUnitIDToken = substr($gibbonUnitID, 0, 10);
        }

        //Proceed!
        echo "<div class='trail'>";
        echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/units.php&gibbonSchoolYearID='.$_GET['gibbonSchoolYearID'].'&gibbonCourseID='.$_GET['gibbonCourseID']."'>".__($guid, 'Unit Planner')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/units_edit.php&gibbonSchoolYearID='.$_GET['gibbonSchoolYearID'].'&gibbonCourseID='.$_GET['gibbonCourseID'].'&gibbonUnitID='.$_GET['gibbonUnitID']."'>".__($guid, 'Edit Unit')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/units_edit_working.php&gibbonSchoolYearID='.$_GET['gibbonSchoolYearID'].'&gibbonCourseID='.$_GET['gibbonCourseID'].'&gibbonUnitID='.$_GET['gibbonUnitID'].'&gibbonCourseClassID='.$_GET['gibbonCourseClassID']."'>".__($guid, 'Edit Working Copy')."</a> > </div><div class='trailEnd'>".__($guid, 'Add Lessons').'</div>';
        echo '</div>';

        if (isset($_GET['return'])) {
            returnProcess($guid, $_GET['return'], null, null);
        }

        //Check if courseschool year specified
        $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'];
        $gibbonCourseID = $_GET['gibbonCourseID'];
        $gibbonCourseClassID = $_GET['gibbonCourseClassID'];
        $gibbonUnitID = $_GET['gibbonUnitID'];
        $gibbonUnitClassID = $_GET['gibbonUnitClassID'];
        if ($gibbonCourseID == '' or $gibbonSchoolYearID == '' or $gibbonCourseClassID == '' or $gibbonUnitClassID == '') {
            echo "<div class='error'>";
            echo __($guid, 'You have not specified one or more required parameters.');
            echo '</div>';
        } else {
            try {
                if ($highestAction == 'Unit Planner_all') {
                    $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonCourseID' => $gibbonCourseID, 'gibbonCourseClassID' => $gibbonCourseClassID);
                    $sql = 'SELECT *, gibbonSchoolYear.name AS year, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) JOIN gibbonSchoolYear ON (gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourse.gibbonCourseID=:gibbonCourseID AND gibbonCourseClassID=:gibbonCourseClassID';
                } elseif ($highestAction == 'Unit Planner_learningAreas') {
                    $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonCourseID' => $gibbonCourseID, 'gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                    $sql = "SELECT gibbonCourse.gibbonCourseID, gibbonCourse.name, gibbonCourse.nameShort, gibbonSchoolYear.name AS year, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) JOIN gibbonSchoolYear ON (gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) JOIN gibbonDepartment ON (gibbonCourse.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE gibbonDepartmentStaff.gibbonPersonID=:gibbonPersonID AND (role='Coordinator' OR role='Assistant Coordinator' OR role='Teacher (Curriculum)') AND gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourse.gibbonCourseID=:gibbonCourseID AND gibbonCourseClassID=:gibbonCourseClassID ORDER BY gibbonCourse.nameShort";
                }
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            if ($result->rowCount() != 1) {
                echo "<div class='error'>";
                echo __($guid, 'The selected record does not exist, or you do not have access to it.');
                echo '</div>';
            } else {
                $row = $result->fetch();
                $year = $row['year'];
                $course = $row['course'];
                $class = $row['class'];

                //Check if unit specified
                if ($gibbonUnitID == '') {
                    echo "<div class='error'>";
                    echo __($guid, 'You have not specified one or more required parameters.');
                    echo '</div>';
                } else {
                    if ($hooked == false) {
                        try {
                            $data = array('gibbonUnitID' => $gibbonUnitID, 'gibbonCourseID' => $gibbonCourseID);
                            $sql = 'SELECT gibbonCourse.nameShort AS courseName, gibbonUnit.* FROM gibbonUnit JOIN gibbonCourse ON (gibbonUnit.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonUnitID=:gibbonUnitID AND gibbonUnit.gibbonCourseID=:gibbonCourseID';
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }
                    } else {
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
                    }

                    if ($result->rowCount() != 1) {
                        echo "<div class='error'>";
                        echo __($guid, 'The specified record cannot be found.');
                        echo '</div>';
                    } else {
                        //Let's go!
                        $row = $result->fetch();

                        echo "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>";
                        echo '<tr>';
                        echo "<td style='width: 34%; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'School Year').'</span><br/>';
                        echo '<i>'.$year.'</i>';
                        echo '</td>';
                        echo "<td style='width: 33%; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Class').'</span><br/>';
                        echo '<i>'.$course.'.'.$class.'</i>';
                        echo '</td>';
                        echo "<td style='width: 34%; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Unit').'</span><br/>';
                        echo '<i>'.$row['name'].'</i>';
                        echo '</td>';
                        echo '</tr>';
                        echo '</table>';

                        echo '<h3>';
                        echo __($guid, 'Choose Lessons');
                        echo '</h3>';
                        echo '<p>';
                        echo __($guid, 'Use the table below to select the lessons you wish to deploy this unit to. Only lessons without existing plans can be included in the deployment.');
                        echo '</p>';

                        //Find all unplanned slots for this class.
                        try {
                            $dataNext = array('gibbonCourseClassID' => $gibbonCourseClassID);
                            $sqlNext = 'SELECT timeStart, timeEnd, date, gibbonTTColumnRow.name AS period, gibbonTTDayRowClassID, gibbonTTDayDateID FROM gibbonTTDayRowClass JOIN gibbonTTColumnRow ON (gibbonTTDayRowClass.gibbonTTColumnRowID=gibbonTTColumnRow.gibbonTTColumnRowID) JOIN gibbonTTColumn ON (gibbonTTColumnRow.gibbonTTColumnID=gibbonTTColumn.gibbonTTColumnID) JOIN gibbonTTDay ON (gibbonTTDayRowClass.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) JOIN gibbonTTDayDate ON (gibbonTTDayDate.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) WHERE gibbonCourseClassID=:gibbonCourseClassID ORDER BY date, timestart';
                            $resultNext = $connection2->prepare($sqlNext);
                            $resultNext->execute($dataNext);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }

                        $count = 0;
                        $lessons = array();
                        while ($rowNext = $resultNext->fetch()) {
                            try {
                                $dataPlanner = array('date' => $rowNext['date'], 'timeStart' => $rowNext['timeStart'], 'timeEnd' => $rowNext['timeEnd'], 'gibbonCourseClassID' => $gibbonCourseClassID);
                                $sqlPlanner = 'SELECT * FROM gibbonPlannerEntry WHERE date=:date AND timeStart=:timeStart AND timeEnd=:timeEnd AND gibbonCourseClassID=:gibbonCourseClassID';
                                $resultPlanner = $connection2->prepare($sqlPlanner);
                                $resultPlanner->execute($dataPlanner);
                            } catch (PDOException $e) {
                                echo "<div class='error'>".$e->getMessage().'</div>';
                            }
                            if ($resultPlanner->rowCount() == 0) {
                                $lessons[$count][0] = 'Unplanned';
                                $lessons[$count][1] = $rowNext['date'];
                                $lessons[$count][2] = $rowNext['timeStart'];
                                $lessons[$count][3] = $rowNext['timeEnd'];
                                $lessons[$count][4] = $rowNext['period'];
                                $lessons[$count][6] = $rowNext['gibbonTTDayRowClassID'];
                                $lessons[$count][7] = $rowNext['gibbonTTDayDateID'];
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
                            }

                            //Check for special days
                            try {
                                $dataSpecial = array('date' => $rowNext['date']);
                                $sqlSpecial = 'SELECT * FROM gibbonSchoolYearSpecialDay WHERE date=:date';
                                $resultSpecial = $connection2->prepare($sqlSpecial);
                                $resultSpecial->execute($dataSpecial);
                            } catch (PDOException $e) {
                                echo "<div class='error'>".$e->getMessage().'</div>';
                            }

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
                            echo __($guid, 'There are no records to display.');
                            echo '</div>';
                        } else {
                            //Get term dates
                            $terms = array();
                            $termCount = 0;
                            try {
                                $dataTerms = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
                                $sqlTerms = 'SELECT * FROM gibbonSchoolYearTerm WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY sequenceNumber';
                                $resultTerms = $connection2->prepare($sqlTerms);
                                $resultTerms->execute($dataTerms);
                            } catch (PDOException $e) {
                                echo "<div class='error'>".$e->getMessage().'</div>';
                            }
                            while ($rowTerms = $resultTerms->fetch()) {
                                $terms[$termCount][0] = $rowTerms['firstDay'];
                                $terms[$termCount][1] = __($guid, 'Start of').' '.$rowTerms['nameShort'];
                                ++$termCount;
                                $terms[$termCount][0] = $rowTerms['lastDay'];
                                $terms[$termCount][1] = __($guid, 'End of').' '.$rowTerms['nameShort'];
                                ++$termCount;
                            }
                            //Get school closure special days
                            $specials = array();
                            $specialCount = 0;
                            try {
                                $dataSpecial = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
                                $sqlSpecial = "SELECT gibbonSchoolYearSpecialDay.date, gibbonSchoolYearSpecialDay.name FROM gibbonSchoolYearSpecialDay JOIN gibbonSchoolYearTerm ON (gibbonSchoolYearSpecialDay.gibbonSchoolYearTermID=gibbonSchoolYearTerm.gibbonSchoolYearTermID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND type='School Closure' ORDER BY date";
                                $resultSpecial = $connection2->prepare($sqlSpecial);
                                $resultSpecial->execute($dataSpecial);
                            } catch (PDOException $e) {
                                echo "<div class='error'>".$e->getMessage().'</div>';
                            }
                            $lastName = '';
                            $currentName = '';
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

                            echo "<form method='post' action='".$_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/units_edit_working_addProcess.php?gibbonUnitID=$gibbonUnitID&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonCourseID=$gibbonCourseID&gibbonCourseClassID=$gibbonCourseClassID&gibbonUnitClassID=$gibbonUnitClassID&address=".$_GET['q']."'>";
                            echo "<table cellspacing='0' style='width: 100%'>";
                            echo "<tr class='head'>";
                            echo '<th>';
                            echo sprintf(__($guid, 'Lesson%1$sNumber'), '<br/>');
                            echo '</th>';
                            echo '<th>';
                            echo __($guid, 'Date');
                            echo '</th>';
                            echo '<th>';
                            echo __($guid, 'Day');
                            echo '</th>';
                            echo '<th>';
                            echo __($guid, 'Month');
                            echo '</th>';
                            echo '<th>';
                            echo sprintf(__($guid, 'TT Period%1$sTime'), '<br/>');
                            echo '</th>';
                            echo '<th>';
                            echo sprintf(__($guid, 'Planned%1$sLesson'), '<br/>');
                            echo '</th>';
                            echo '<th>';
                            echo __($guid, 'Include?');
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
								while ($lesson['1'] >= $terms[$termCount][0] and $termCount < (count($terms) - 1)) {
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
									echo '<b>Lesson '.($classCount + 1).'</b>';
									echo '</td>';
									echo "<td $style>";
									echo dateConvertBack($guid, $lesson['1']).'<br/>';
									if ($lesson[8] == 'Timing Change') {
										echo '<u>'.$lesson[8].'</u><br/><i>('.substr($lesson[9], 0, 5).'-'.substr($lesson[10], 0, 5).')</i>';
									}
									echo '</td>';
									echo "<td $style>";
									echo date('D', dateConvertToTimestamp($lesson['1']));
									echo '</td>';
									echo "<td $style>";
									echo date('M', dateConvertToTimestamp($lesson['1']));
									echo '</td>';
									echo "<td $style>";
									echo $lesson['4'].'<br/>';
									echo substr($lesson['2'], 0, 5).' - '.substr($lesson['3'], 0, 5);
									echo '</td>';
									echo "<td $style>";
									if ($lesson['0'] == 'Planned') {
										echo $lesson['5'].'<br/>';
									}
									echo '</td>';
									echo "<td $style>";
									if ($lesson['0'] == 'Unplanned') {
										echo "<input name='deploy$count' type='checkbox'>";
										echo "<input name='date$count' type='hidden' value='".$lesson['1']."'>";
										echo "<input name='timeStart$count' type='hidden' value='".$lesson['2']."'>";
										echo "<input name='timeEnd$count' type='hidden' value='".$lesson['3']."'>";
										echo "<input name='period$count' type='hidden' value='".$lesson['4']."'>";
										echo "<input name='gibbonTTDayRowClassID$count' type='hidden' value='".$lesson['6']."'>";
										echo "<input name='gibbonTTDayDateID$count' type='hidden' value='".$lesson['7']."'>";
									}
									echo '</td>';
									echo '</tr>';
									++$classCount;
								}

								//Spit out row for end of term
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

                            echo '<tr>';
                            echo "<td class='right' colspan=7>";
                            echo "<input name='count' id='count' value='$count' type='hidden'>";
                            echo "<input id='submit' type='submit' value='Submit'>";
                            echo '</td>';
                            echo '</tr>';
                            echo '</table>';
                            echo '</form>';
                        }
                    }
                }
            }
        }
    }
    //Print sidebar
    $_SESSION[$guid]['sidebarExtra'] = sidebarExtraUnits($guid, $connection2, $gibbonCourseID, $gibbonSchoolYearID);
}
