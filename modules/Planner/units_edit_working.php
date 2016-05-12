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

if (isActionAccessible($guid, $connection2, '/modules/Planner/units_edit_working.php') == false) {
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
        echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/units.php&gibbonSchoolYearID='.$_GET['gibbonSchoolYearID'].'&gibbonCourseID='.$_GET['gibbonCourseID']."'>".__($guid, 'Unit Planner')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/units_edit.php&gibbonSchoolYearID='.$_GET['gibbonSchoolYearID'].'&gibbonCourseID='.$_GET['gibbonCourseID'].'&gibbonUnitID='.$_GET['gibbonUnitID']."'>".__($guid, 'Edit Unit')."</a> > </div><div class='trailEnd'>".__($guid, 'Edit Working Copy').'</div>';
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
                        echo __($guid, 'Lessons & Blocks');
                        echo '</h3>';
                        echo '<p>';
                        echo __($guid, 'You can now add your unit blocks using the dropdown menu in each lesson. Blocks can be dragged from one lesson to another.');
                        echo '</p>';

                        //Store UNIT BLOCKS in array
                        $blocks = array();
                        try {
                            if ($hooked == false) {
                                $dataBlocks = array('gibbonUnitID' => $gibbonUnitID);
                                $sqlBlocks = 'SELECT * FROM gibbonUnitBlock WHERE gibbonUnitID=:gibbonUnitID ORDER BY sequenceNumber';
                            } else {
                                $dataBlocks = array('classLinkJoinFieldUnit' => $gibbonUnitIDToken, 'classLinkJoinFieldClass' => $gibbonCourseClassID);
                                $sqlBlocks = 'SELECT '.$hookOptions['unitSmartBlockTable'].'.* FROM '.$hookOptions['unitSmartBlockTable'].' JOIN '.$hookOptions['classLinkTable'].' ON ('.$hookOptions['unitSmartBlockTable'].'.'.$hookOptions['unitSmartBlockJoinField'].'='.$hookOptions['classLinkTable'].'.'.$hookOptions['classLinkJoinFieldUnit'].') JOIN '.$hookOptions['unitTable'].' ON ('.$hookOptions['classLinkTable'].'.'.$hookOptions['classLinkJoinFieldUnit'].'='.$hookOptions['unitTable'].'.'.$hookOptions['unitIDField'].') WHERE '.$hookOptions['classLinkTable'].'.'.$hookOptions['classLinkJoinFieldUnit'].'=:classLinkJoinFieldUnit AND '.$hookOptions['classLinkTable'].'.'.$hookOptions['classLinkJoinFieldClass'].'=:classLinkJoinFieldClass ORDER BY sequenceNumber';
                            }
                            $resultBlocks = $connection2->prepare($sqlBlocks);
                            $resultBlocks->execute($dataBlocks);
                            $resultLessonBlocks = $connection2->prepare($sqlBlocks);
                            $resultLessonBlocks->execute($dataBlocks);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }
                        $blockCount = 0;
                        while ($rowBlocks = $resultBlocks->fetch()) {
                            if ($hooked == false) {
                                $blocks[$blockCount][0] = $rowBlocks['gibbonUnitBlockID'];
                                $blocks[$blockCount][1] = $rowBlocks['title'];
                                $blocks[$blockCount][2] = $rowBlocks['type'];
                                $blocks[$blockCount][3] = $rowBlocks['length'];
                                $blocks[$blockCount][4] = $rowBlocks['contents'];
                                $blocks[$blockCount][5] = $rowBlocks['teachersNotes'];
                                $blocks[$blockCount][5] = $rowBlocks['teachersNotes'];
                            } else {
                                $blocks[$blockCount][0] = $rowBlocks[$hookOptions['unitSmartBlockIDField']];
                                $blocks[$blockCount][1] = $rowBlocks[$hookOptions['unitSmartBlockTitleField']];
                                $blocks[$blockCount][2] = $rowBlocks[$hookOptions['unitSmartBlockTypeField']];
                                $blocks[$blockCount][3] = $rowBlocks[$hookOptions['unitSmartBlockLengthField']];
                                $blocks[$blockCount][4] = $rowBlocks[$hookOptions['unitSmartBlockContentsField']];
                                $blocks[$blockCount][5] = $rowBlocks[$hookOptions['unitSmartBlockTeachersNotesField']];
                            }
                            ++$blockCount;
                        }

                        //Store STAR BLOCKS in array
                        $blocks2 = array();
                        try {
                            $dataBlocks2 = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                            $sqlBlocks2 = 'SELECT * FROM gibbonUnitBlockStar JOIN gibbonUnitBlock ON (gibbonUnitBlockStar.gibbonUnitBlockID=gibbonUnitBlock.gibbonUnitBlockID) WHERE gibbonPersonID=:gibbonPersonID ORDER BY title';
                            $resultBlocks2 = $connection2->prepare($sqlBlocks2);
                            $resultBlocks2->execute($dataBlocks2);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }
                        $blockCount2 = 0;
                        while ($rowBlocks2 = $resultBlocks2->fetch()) {
                            $blocks2[$blockCount2][0] = $rowBlocks2['gibbonUnitBlockID'];
                            $blocks2[$blockCount2][1] = $rowBlocks2['title'];
                            $blocks2[$blockCount2][2] = $rowBlocks2['type'];
                            $blocks2[$blockCount2][3] = $rowBlocks2['length'];
                            $blocks2[$blockCount2][4] = $rowBlocks2['contents'];
                            $blocks2[$blockCount2][5] = $rowBlocks2['teachersNotes'];
                            $blocks2[$blockCount2][6] = $rowBlocks2['gibbonOutcomeIDList'];
                            ++$blockCount2;
                        }

                        echo "<form method='post' action='".$_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/units_edit_workingProcess.php?gibbonUnitID=$gibbonUnitID&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonCourseID=$gibbonCourseID&gibbonCourseClassID=$gibbonCourseClassID&address=".$_GET['q']."&gibbonUnitClassID=$gibbonUnitClassID'>";
                            //LESSONS (SORTABLES)
                            echo "<div class='linkTop'>";
                        echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/units_edit_working_add.php&gibbonUnitID=$gibbonUnitID&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonCourseID=$gibbonCourseID&gibbonCourseClassID=$gibbonCourseClassID&gibbonUnitClassID=$gibbonUnitClassID'>".__($guid, 'Add')."<img style='margin-left: 5px' title='".__($guid, 'Add')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new.png'/></a>";
                        echo '</div>';
                        echo "<div style='width: 100%; height: auto'>";
                        try {
                            $dataLessons = array('gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonUnitID' => $gibbonUnitID);
                            $sqlLessons = 'SELECT * FROM gibbonPlannerEntry WHERE gibbonCourseClassID=:gibbonCourseClassID AND gibbonUnitID=:gibbonUnitID ORDER BY date, timeStart';
                            $resultLessons = $connection2->prepare($sqlLessons);
                            $resultLessons->execute($dataLessons);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }

                        if ($resultLessons->rowCount() < 1) {
                            echo "<div class='error'>";
                            echo __($guid, 'There are no records to display.');
                            echo '</div>';
                        } else {
                            $i = 0;
                            $blockCount2 = $blockCount;
                            while ($rowLessons = $resultLessons->fetch()) {
                                echo "<div class='lessonInner' id='lessonInner$i' style='min-height: 60px; border: 1px solid #333; width: 100%; margin-bottom: 65px; float: left; padding: 2px; background-color: #F7F0E3'>";
                                echo "<div class='sortable' id='sortable$i' style='height: auto!important; min-height: 60px; font-size: 120%; font-style: italic'>";
                                echo "<div id='head$i' class='head' style='height: 54px; font-size: 85%; padding: 3px'>";

                                echo "<a onclick='return confirm(\"Are you sure you want to jump to this lesson? Any unsaved changes will be lost.\")' style='font-weight: bold; font-style: normal; color: #333' href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Planner/planner_view_full.php&viewBy=class&gibbonCourseClassID='.$rowLessons['gibbonCourseClassID'].'&gibbonPlannerEntryID='.$rowLessons['gibbonPlannerEntryID']."'>".($i + 1).'. '.$rowLessons['name']."</a> <a onclick='return confirm(\"".__($guid, 'Are you sure you want to delete this record? Any unsaved changes will be lost.')."\")' href='".$_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/units_edit_working_lessonDelete.php?gibbonUnitID=$gibbonUnitID&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonCourseID=$gibbonCourseID&gibbonCourseClassID=$gibbonCourseClassID&gibbonUnitClassID=$gibbonUnitClassID&address=".$_GET['q'].'&gibbonPlannerEntryID='.$rowLessons['gibbonPlannerEntryID']."'><img title='".__($guid, 'Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/ style='position: absolute; margin: -1px 0px 2px 10px'></a><br/>";

                                try {
                                    $dataTT = array('date' => $rowLessons['date'], 'timeStart' => $rowLessons['timeStart'], 'timeEnd' => $rowLessons['timeEnd'], 'gibbonCourseClassID' => $gibbonCourseClassID);
                                    $sqlTT = 'SELECT timeStart, timeEnd, date, gibbonTTColumnRow.name AS period, gibbonTTDayRowClassID, gibbonTTDayDateID FROM gibbonTTDayRowClass JOIN gibbonTTColumnRow ON (gibbonTTDayRowClass.gibbonTTColumnRowID=gibbonTTColumnRow.gibbonTTColumnRowID) JOIN gibbonTTColumn ON (gibbonTTColumnRow.gibbonTTColumnID=gibbonTTColumn.gibbonTTColumnID) JOIN gibbonTTDay ON (gibbonTTDayRowClass.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) JOIN gibbonTTDayDate ON (gibbonTTDayDate.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) WHERE date=:date AND timeStart=:timeStart AND timeEnd=:timeEnd AND gibbonCourseClassID=:gibbonCourseClassID ORDER BY date, timestart';
                                    $resultTT = $connection2->prepare($sqlTT);
                                    $resultTT->execute($dataTT);
                                } catch (PDOException $e) {
                                    echo "<div class='error'>".$e->getMessage().'</div>';
                                }

                                if ($resultTT->rowCount() == 1) {
                                    $rowTT = $resultTT->fetch();
                                    echo "<span style='font-size: 80%'><i>".date('D jS M, Y', dateConvertToTimestamp($rowLessons['date'])).'<br/>'.$rowTT['period'].' ('.substr($rowLessons['timeStart'], 0, 5).' - '.substr($rowLessons['timeEnd'], 0, 5).')</i></span>';
                                } else {
                                    echo "<span style='font-size: 80%'><i>";
                                    if ($rowLessons['date'] != '') {
                                        echo date('D jS M, Y', dateConvertToTimestamp($rowLessons['date'])).'<br/>';
                                        echo substr($rowLessons['timeStart'], 0, 5).' - '.substr($rowLessons['timeEnd'], 0, 5).'</i>';
                                    } else {
                                        echo 'Date not set<br/>';
                                    }
                                    echo '</i></span>';
                                }

                                echo "<input type='hidden' name='order[]' value='lessonHeader-$i' >";
                                echo "<input type='hidden' name='date$i' value='".$rowLessons['date']."' >";
                                echo "<input type='hidden' name='timeStart$i' value='".$rowLessons['timeStart']."' >";
                                echo "<input type='hidden' name='timeEnd$i' value='".$rowLessons['timeEnd']."' >";
                                echo "<input type='hidden' name='gibbonPlannerEntryID$i' value='".$rowLessons['gibbonPlannerEntryID']."' >";
                                echo "<div style='text-align: right; float: right; margin-top: -33px; margin-right: 3px'>";
                                echo "<span style='font-size: 80%'><i>".__($guid, 'Add Block:').'</i></span><br/>';
                                echo "<script type='text/javascript'>";
                                echo '$(document).ready(function(){';
                                echo "$(\"#blockAdd$i\").change(function(){";
                                echo "if ($(\"#blockAdd$i\").val()!='') {";
                                echo "$(\"#sortable$i\").append('<div id=\'blockOuter' + count + '\' class=\'blockOuter\'><div class=\'odd\' style=\'text-align: center; font-size: 75%; height: 60px; border: 1px solid #d8dcdf; margin: 0 0 5px\' id=\'block$i\' style=\'padding: 0px\'><img style=\'margin: 10px 0 5px 0\' src=\'".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/loading.gif\' alt=\'Loading\' onclick=\'return false;\' /><br/>Loading</div></div>');";
                                echo '$("#blockOuter" + count).load("'.$_SESSION[$guid]['absoluteURL']."/modules/Planner/units_add_blockAjax.php?mode=workingDeploy&gibbonUnitID=$gibbonUnitID&gibbonUnitBlockID=\" + $(\"#blockAdd$i\").val(),\"id=\" + count) ;";
                                echo 'count++ ;';
                                echo '}';
                                echo '}) ;';
                                echo '}) ;';
                                echo '</script>';
                                echo "<select name='blockAdd$i' id='blockAdd$i' style='width: 150px'>";
                                echo "<option value=''></option>";
                                echo "<optgroup label='--".__($guid, 'Unit Blocks')."--'>";
                                $blockSelectCount = 0;
                                foreach ($blocks as $block) {
                                    echo "<option value='".$block[0]."'>".($blockSelectCount + 1).') '.htmlPrep($block[1]).'</option>';
                                    ++$blockSelectCount;
                                }
                                echo '</optgroup>';
                                echo "<optgroup label='--".__($guid, 'Star Blocks')."--'>";
                                foreach ($blocks2 as $block2) {
                                    echo "<option value='".$block2[0]."'>".htmlPrep($block2[1]).'</option>';
                                }
                                echo '</optgroup>';
                                echo '</select>';
                                echo '</div>';
                                echo '</div>';

								//Get blocks
								try {
									if ($hooked == false) {
										$dataLessonBlocks = array('gibbonPlannerEntryID' => $rowLessons['gibbonPlannerEntryID'], 'gibbonCourseClassID' => $gibbonCourseClassID);
										$sqlLessonBlocks = 'SELECT * FROM gibbonUnitClassBlock JOIN gibbonUnitClass ON (gibbonUnitClassBlock.gibbonUnitClassID=gibbonUnitClass.gibbonUnitClassID) WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonCourseClassID=:gibbonCourseClassID ORDER BY sequenceNumber';
									} else {
										$dataLessonBlocks = array('gibbonPlannerEntryID' => $rowLessons['gibbonPlannerEntryID'], 'gibbonCourseClassID' => $gibbonCourseClassID);
										$sqlLessonBlocks = 'SELECT '.$hookOptions['classSmartBlockTable'].'.* FROM '.$hookOptions['classSmartBlockTable'].' JOIN '.$hookOptions['classLinkTable'].' ON ('.$hookOptions['classSmartBlockTable'].'.'.$hookOptions['classSmartBlockJoinField'].'='.$hookOptions['classLinkTable'].'.'.$hookOptions['classLinkIDField'].') WHERE '.$hookOptions['classSmartBlockTable'].'.'.$hookOptions['classSmartBlockPlannerJoin'].'=:gibbonPlannerEntryID AND '.$hookOptions['classLinkTable'].'.'.$hookOptions['classLinkJoinFieldClass'].'=:gibbonCourseClassID ORDER BY sequenceNumber';
									}
									$resultLessonBlocks = $connection2->prepare($sqlLessonBlocks);
									$resultLessonBlocks->execute($dataLessonBlocks);
								} catch (PDOException $e) {
									echo "<div class='error'>".$e->getMessage().'</div>';
								}

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

                                while ($rowLessonBlocks = $resultLessonBlocks->fetch()) {
                                    if ($hooked == false) {
                                        makeBlock($guid,  $connection2, $blockCount2, $mode = 'workingEdit', $rowLessonBlocks['title'], $rowLessonBlocks['type'], $rowLessonBlocks['length'], $rowLessonBlocks['contents'], $rowLessonBlocks['complete'], $rowLessonBlocks['gibbonUnitBlockID'], $rowLessonBlocks['gibbonUnitClassBlockID'], $rowLessonBlocks['teachersNotes'], true, $unitOutcomes, $rowLessonBlocks['gibbonOutcomeIDList']);
                                    } else {
                                        makeBlock($guid,  $connection2, $blockCount2, $mode = 'workingEdit', $rowLessonBlocks[$hookOptions['classSmartBlockTitleField']], $rowLessonBlocks[$hookOptions['classSmartBlockTypeField']], $rowLessonBlocks[$hookOptions['classSmartBlockLengthField']], $rowLessonBlocks[$hookOptions['classSmartBlockContentsField']], $rowLessonBlocks['complete'], $rowLessonBlocks['gibbonUnitBlockID'], $rowLessonBlocks['gibbonUnitClassBlockID'], $rowLessonBlocks[$hookOptions['classSmartBlockTeachersNotesField']], true);
                                    }
                                    ++$blockCount2;
                                }
                                echo '</div>';
                                echo '</div>';
                                ++$i;
                            }
                            $cells = $i;
                        }
                        ?>
						<div class='linkTop' style='margin-top: 0px!important'>
							<?php
							echo "<script type='text/javascript'>";
							echo "var count=$blockCount2 ;";
							echo '</script>';
							echo "<input type='submit' value='Submit'>";
							?>
						</div>
						<?php
                        echo '</div>';
                        echo '</form>';

                        //Add drag/drop controls
                        $sortableList = '';
                        ?>
						<style>
							.default { border: none; background-color: #ffffff }
							.drop { border: none; background-color: #eeeeee }
							.hover { border: none; background-color: #D4F6DC }
						</style>

						<script type="text/javascript">
							$(function() {
								var receiveCount=0 ;

								//Create list of lesson sortables
								<?php for ($i = 0; $i < $cells; ++$i) { ?>
									<?php $sortableList .= "#sortable$i, " ?>
								<?php } ?>

								//Create lesson sortables
								<?php for ($i = 0; $i < $cells; ++$i) { ?>
									$( "#sortable<?php echo $i ?>" ).sortable({
										revert: false,
										tolerance: 15,
										connectWith: "<?php echo substr($sortableList, 0, -2) ?>",
										items: "div.blockOuter",
										receive: function(event,ui) {
											var sortid=$(newItem).attr("id", 'receive'+receiveCount) ;
											var receiveid='receive'+receiveCount ;
											$('#' + receiveid + ' .delete').show() ;
											$('#' + receiveid + ' .delete').click(function() {
												$('#' + receiveid).fadeOut(600, function(){
													$('#' + receiveid).remove();
												});
											});
											$('#' + receiveid + ' .completeDiv').show() ;
											$('#' + receiveid + ' .complete').show() ;
											$('#' + receiveid + ' .complete').click(function() {
												if ($('#' + receiveid + ' .complete').is(':checked')==true) {
													$('#' + receiveid + ' .completeHide').val('on') ;
												} else {
													$('#' + receiveid + ' .completeHide').val('off') ;
												}
											});
											receiveCount++ ;
										},
										beforeStop: function (event, ui) {
										 newItem=ui.item;
										}
									});
									<?php for ($j = $blockCount; $j < $blockCount2; ++$j) { ?>
										$("#draggable<?php echo $j ?> .delete").show() ;
										$("#draggable<?php echo $j ?> .delete").click(function() {
											$("#draggable<?php echo $j ?>").fadeOut(600, function(){
												$("#draggable<?php echo $j ?>").remove();
											});
										});
										$("#draggable<?php echo $j ?> .completeDiv").show() ;
										$("#draggable<?php echo $j ?> .complete").show() ;
										$("#draggable<?php echo $j ?> .complete").click(function() {
												if ($("#draggable<?php echo $j ?> .complete").is(':checked')==true) {
													$("#draggable<?php echo $j ?> .completeHide").val('on') ;
												} else {
													$("#draggable<?php echo $j ?> .completeHide").val('off') ;
												}
											});
									<?php }
								} ?>

								//Draggables
								<?php for ($i = 0; $i < $blockCount; ++$i) { ?>
									$( "#draggable<?php echo $i ?>" ).draggable({
										connectToSortable: "<?php echo substr($sortableList, 0, -2) ?>",
										helper: "clone"
									});
								<?php } ?>
							});
						</script>
						<?php

                    }
                }
            }
        }
    }
    //Print sidebar
    $_SESSION[$guid]['sidebarExtra'] = sidebarExtraUnits($guid, $connection2, $gibbonCourseID, $gibbonSchoolYearID);
}
?>
