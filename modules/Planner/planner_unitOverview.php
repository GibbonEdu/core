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

if (isActionAccessible($guid, $connection2, '/modules/Planner/planner_unitOverview.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'Your request failed because you do not have access to this action.');
    echo '</div>';
} else {
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
        $replyTo = null;
        if (isset($_GET['replyTo'])) {
            $replyTo = $_GET['replyTo'];
        }

        $gibbonPersonID = null;
        if (isset($_GET['search'])) {
            $gibbonPersonID = $_GET['search'];
        }

        //Get class variable
        $gibbonPlannerEntryID = null;
        if (isset($_GET['gibbonPlannerEntryID'])) {
            $gibbonPlannerEntryID = $_GET['gibbonPlannerEntryID'];
        }
        if ($gibbonPlannerEntryID == '') {
            echo "<div class='warning'>";
            echo __($guid, 'You have not specified one or more required parameters.');
            echo '</div>';
        }
        //Check existence of and access to this class.
        else {
            if ($highestAction == 'Lesson Planner_viewMyChildrensClasses') {
                if ($_GET['search'] == '') {
                    echo "<div class='warning'>";
                    echo __($guid, 'You have not specified one or more required parameters.');
                    echo '</div>';
                } else {
                    try {
                        $dataChild = array('gibbonPersonID1' => $gibbonPersonID, 'gibbonPersonID2' => $_SESSION[$guid]['gibbonPersonID']);
                        $sqlChild = "SELECT * FROM gibbonFamilyChild JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonFamilyChild.gibbonPersonID=:gibbonPersonID1 AND gibbonFamilyAdult.gibbonPersonID=:gibbonPersonID2 AND childDataAccess='Y'";
                        $resultChild = $connection2->prepare($sqlChild);
                        $resultChild->execute($dataChild);
                    } catch (PDOException $e) {
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }
                    if ($resultChild->rowCount() != 1) {
                        echo "<div class='error'>";
                        echo __($guid, 'The selected record does not exist, or you do not have access to it.');
                        echo '</div>';
                    } else {
                        $data = array('date' => $date);
                        $data['gibbonPlannerEntryID1'] = $gibbonPlannerEntryID;
                        $data['gibbonPlannerEntryID2'] = $gibbonPlannerEntryID;
                        $sql = "(SELECT gibbonPlannerEntry.gibbonPlannerEntryID, gibbonCourseClass.gibbonCourseClassID, gibbonUnitID, gibbonHookID, gibbonPlannerEntry.gibbonCourseClassID, gibbonPlannerEntry.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, date, timeStart, timeEnd, summary, gibbonPlannerEntry.description, teachersNotes, homework, homeworkDueDateTime, homeworkDetails, viewableStudents, viewableParents, role, homeworkSubmission, homeworkSubmissionDateOpen, homeworkSubmissionDrafts, homeworkSubmissionType FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourseClassPerson.gibbonPersonID=$gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left' AND gibbonPlannerEntry.gibbonPlannerEntryID=:gibbonPlannerEntryID1) UNION (SELECT gibbonPlannerEntry.gibbonPlannerEntryID, gibbonCourseClass.gibbonCourseClassID, gibbonUnitID, gibbonHookID, gibbonPlannerEntry.gibbonCourseClassID, gibbonPlannerEntry.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, date, timeStart, timeEnd, summary, gibbonPlannerEntry.description, teachersNotes, homework, homeworkDueDateTime, homeworkDetails, viewableStudents, viewableParents, role, homeworkSubmission, homeworkSubmissionDateOpen, homeworkSubmissionDrafts, homeworkSubmissionType FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonPlannerEntryGuest ON (gibbonPlannerEntryGuest.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE date=:date AND gibbonPlannerEntryGuest.gibbonPersonID=$gibbonPersonID AND gibbonPlannerEntry.gibbonPlannerEntryID=:gibbonPlannerEntryID2) ORDER BY date, timeStart";
                    }
                }
            } elseif ($highestAction == 'Lesson Planner_viewMyClasses') {
                $data = array('date' => $date, 'gibbonPlannerEntryID1' => $gibbonPlannerEntryID, 'gibbonPlannerEntryID2' => $gibbonPlannerEntryID);
                $sql = '(SELECT gibbonPlannerEntry.gibbonPlannerEntryID, gibbonCourseClass.gibbonCourseClassID, gibbonUnitID, gibbonHookID, gibbonPlannerEntry.gibbonCourseClassID, gibbonPlannerEntry.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, date, timeStart, timeEnd, summary, gibbonPlannerEntry.description, teachersNotes, homework, homeworkDueDateTime, homeworkDetails, viewableStudents, viewableParents, role, homeworkSubmission, homeworkSubmissionDateOpen, homeworkSubmissionDrafts, homeworkSubmissionType FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourseClassPerson.gibbonPersonID='.$_SESSION[$guid]['gibbonPersonID']." AND NOT role='Student - Left' AND NOT role='Teacher - Left' AND gibbonPlannerEntry.gibbonPlannerEntryID=:gibbonPlannerEntryID1) UNION (SELECT gibbonPlannerEntry.gibbonPlannerEntryID, gibbonCourseClass.gibbonCourseClassID, gibbonUnitID, gibbonHookID, gibbonPlannerEntry.gibbonCourseClassID, gibbonPlannerEntry.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, date, timeStart, timeEnd, summary, gibbonPlannerEntry.description, teachersNotes, homework, homeworkDueDateTime, homeworkDetails, viewableStudents, viewableParents, role, homeworkSubmission, homeworkSubmissionDateOpen, homeworkSubmissionDrafts, homeworkSubmissionType FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonPlannerEntryGuest ON (gibbonPlannerEntryGuest.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE date=:date AND gibbonPlannerEntryGuest.gibbonPersonID=".$_SESSION[$guid]['gibbonPersonID'].' AND gibbonPlannerEntry.gibbonPlannerEntryID=:gibbonPlannerEntryID2) ORDER BY date, timeStart';
            } elseif ($highestAction == 'Lesson Planner_viewAllEditMyClasses' or $highestAction == 'Lesson Planner_viewEditAllClasses') {
                $data = array('gibbonPlannerEntryID' => $gibbonPlannerEntryID);
                $sql = "SELECT gibbonPlannerEntry.gibbonPlannerEntryID, gibbonCourseClass.gibbonCourseClassID, gibbonUnitID, gibbonHookID, gibbonPlannerEntry.gibbonCourseClassID, gibbonPlannerEntry.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, date, timeStart, timeEnd, summary, gibbonPlannerEntry.description, teachersNotes, homework, homeworkDueDateTime, homeworkDetails, viewableStudents, viewableParents, 'Teacher' AS role, homeworkSubmission, homeworkSubmissionDateOpen, homeworkSubmissionDrafts, homeworkSubmissionType FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonPlannerEntry.gibbonPlannerEntryID=:gibbonPlannerEntryID ORDER BY date, timeStart";
            }
            try {
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

                $extra = '';
                if ($viewBy == 'class') {
                    $extra = $row['course'].'.'.$row['class'];
                } else {
                    $extra = dateConvertBack($guid, $date);
                }

                $params = '';
                if ($_GET['date'] != '') {
                    $params = $params.'&date='.$_GET['date'];
                }
                if ($_GET['viewBy'] != '') {
                    $params = $params.'&viewBy='.$_GET['viewBy'];
                }
                if ($_GET['gibbonCourseClassID'] != '') {
                    $params = $params.'&gibbonCourseClassID='.$_GET['gibbonCourseClassID'];
                }
                $params .= "&subView=$subView";

                echo "<div class='trail'>";
                echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/planner.php$params&search=$gibbonPersonID'>".__($guid, 'Planner')." $extra</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/planner_view_full.php$params&gibbonPlannerEntryID=$gibbonPlannerEntryID&search=$gibbonPersonID'>".__($guid, 'View Lesson Plan')."</a> > </div><div class='trailEnd'>".__($guid, 'Unit Overview').'</div>';
                echo '</div>';

                if ($row['gibbonUnitID'] == '') {
                    echo __($guid, 'The selected record does not exist, or you do not have access to it.');
                } else {
                    //Get unit contents
                    try {
                        $dataUnit = array('gibbonUnitID' => $row['gibbonUnitID']);
                        $sqlUnit = 'SELECT * FROM gibbonUnit WHERE gibbonUnitID=:gibbonUnitID';
                        $resultUnit = $connection2->prepare($sqlUnit);
                        $resultUnit->execute($dataUnit);
                    } catch (PDOException $e) {
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }

                    if ($resultUnit->rowCount() != 1) {
                        echo "<div class='error'>";
                        echo __($guid, 'The selected record does not exist, or you do not have access to it.');
                        echo '</div>';
                    } else {
                        $rowUnit = $resultUnit->fetch();

                        echo '<h2>';
                        echo $rowUnit['name'];
                        echo '</h2>';
                        echo '<p>';
                        echo __($guid, 'This page shows an overview of the unit that the current lesson belongs to, including all the outcomes, resources, lessons and chats for the classes you have access to.');
                        echo '</p>';

                        //Set up where and data array for getting items from accessible planners
                        if ($highestAction == 'Lesson Planner_viewEditAllClasses' or $highestAction == 'Lesson Planner_viewAllEditMyClasses') {
                            $dataPlanners = array('gibbonUnitID' => $row['gibbonUnitID'], 'gibbonCourseClassID' => $row['gibbonCourseClassID']);
                            $sqlPlanners = 'SELECT * FROM gibbonPlannerEntry WHERE gibbonUnitID=:gibbonUnitID AND gibbonCourseClassID=:gibbonCourseClassID';
                        } elseif ($highestAction == 'Lesson Planner_viewMyClasses') {
                            $dataPlanners = array('gibbonUnitID1' => $row['gibbonUnitID'], 'gibbonPersonID1' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonCourseClassID1' => $row['gibbonCourseClassID'], 'gibbonUnitID2' => $row['gibbonUnitID'], 'gibbonPersonID2' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonCourseClassID2' => $row['gibbonCourseClassID']);
                            $sqlPlanners = "(SELECT gibbonPlannerEntry.* FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonUnitID=:gibbonUnitID1 AND gibbonPersonID=:gibbonPersonID1 AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID1 AND role='Teacher')
							UNION
							(SELECT gibbonPlannerEntry.* FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonUnitID=:gibbonUnitID2 AND gibbonPersonID=:gibbonPersonID2 AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID2 AND role='Student' AND viewableStudents='Y')";
                        } elseif ($highestAction == 'Lesson Planner_viewMyChildrensClasses') {
                            $dataPlanners = array('gibbonUnitID' => $row['gibbonUnitID'], 'gibbonCourseClassID' => $row['gibbonCourseClassID']);
                            $sqlPlanners = "SELECT * FROM gibbonPlannerEntry WHERE gibbonUnitID=:gibbonUnitID AND gibbonCourseClassID=:gibbonCourseClassID AND viewableParents='Y'";
                        }
                        try {
                            $resultPlanners = $connection2->prepare($sqlPlanners);
                            $resultPlanners->execute($dataPlanners);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }

                        if ($resultPlanners->rowCount() < 1) {
                            echo "<div class='error'>";
                            echo __($guid, 'There are no records to display.');
                            echo '</div>';
                        } else {
                            $dataMulti = array();
                            $whereMulti = '(';
                            $multiCount = 0;
                            while ($rowPlanners = $resultPlanners->fetch()) {
                                $dataMulti['gibbonPlannerEntryID'.$multiCount] = $rowPlanners['gibbonPlannerEntryID'];
                                $whereMulti .= 'gibbonPlannerEntryID=:gibbonPlannerEntryID'.$multiCount.' OR ';
                                ++$multiCount;
                            }
                            $whereMulti = substr($whereMulti, 0, -4).')';
                            ?>
							<script type='text/javascript'>
								$(function() {
									$( "#tabs" ).tabs({
										ajaxOptions: {
											error: function( xhr, status, index, anchor ) {
												$( anchor.hash ).html(
													"Couldn't load this tab." );
											}
										}
									});
								});
							</script>
							<?php

                            echo "<div id='tabs' style='margin: 20px 0'>";
							//Tab links
							echo '<ul>';
                            echo "<li><a href='#tabs1'>".__($guid, 'Unit Overview').'</a></li>';
                            echo "<li><a href='#tabs2'>".__($guid, 'Smart Blocks').'</a></li>';
                            echo "<li><a href='#tabs3'>".__($guid, 'Outcomes').'</a></li>';
                            echo "<li><a href='#tabs4'>".__($guid, 'Lessons').'</a></li>';
                            echo "<li><a href='#tabs5'>".__($guid, 'Resources').'</a></li>';
                            echo '</ul>';

							//Tab content
							//UNIT OVERVIEW
							echo "<div id='tabs1'>";
                            $shareUnitOutline = getSettingByScope($connection2, 'Planner', 'shareUnitOutline');
                            echo '<h2>';
                            echo __($guid, 'Description');
                            echo '</h2>';
                            echo '<p>';
                            echo $rowUnit['description'];
                            echo '</p>';

                            if ($rowUnit['tags'] != '') {
                                echo '<h2>';
                                echo __($guid, 'Concepts & Keywords');
                                echo '</h2>';
                                echo '<p>';
                                echo $rowUnit['tags'];
                                echo '</p>';
                            }
                            if ($highestAction == 'Lesson Planner_viewEditAllClasses' or $highestAction == 'Lesson Planner_viewAllEditMyClasses' or $shareUnitOutline == 'Y') {
                                if ($rowUnit['details'] != '') {
                                    echo '<h2>';
                                    echo __($guid, 'Unit Outline');
                                    echo '</h2>';
                                    echo '<p>';
                                    echo $rowUnit['details'];
                                    echo '</p>';
                                }
                            }
                            echo '</div>';
                            //SMART BLOCKS
                            echo "<div id='tabs2'>";
                            try {
                                $dataBlocks = array('gibbonUnitID' => $row['gibbonUnitID']);
                                $sqlBlocks = 'SELECT * FROM gibbonUnitBlock WHERE gibbonUnitID=:gibbonUnitID ORDER BY sequenceNumber';
                                $resultBlocks = $connection2->prepare($sqlBlocks);
                                $resultBlocks->execute($dataBlocks);
                            } catch (PDOException $e) {
                                echo "<div class='error'>".$e->getMessage().'</div>';
                            }

                            while ($rowBlocks = $resultBlocks->fetch()) {
                                if ($rowBlocks['title'] != '' or $rowBlocks['type'] != '' or $rowBlocks['length'] != '') {
                                    echo "<div class='blockView' style='min-height: 35px'>";
                                    if ($rowBlocks['type'] != '' or $rowBlocks['length'] != '') {
                                        $width = '69%';
                                    } else {
                                        $width = '100%';
                                    }
                                    echo "<div style='padding-left: 3px; width: $width; float: left;'>";
                                    if ($rowBlocks['title'] != '') {
                                        echo "<h5 style='padding-bottom: 2px'>".$rowBlocks['title'].'</h5>';
                                    }
                                    echo '</div>';
                                    if ($rowBlocks['type'] != '' or $rowBlocks['length'] != '') {
                                        echo "<div style='float: right; width: 29%; padding-right: 3px; height: 55px'>";
                                        echo "<div style='text-align: right; font-size: 85%; font-style: italic; margin-top: 3px; border-bottom: 1px solid #ddd; height: 21px'>";
                                        if ($rowBlocks['type'] != '') {
                                            echo $rowBlocks['type'];
                                            if ($rowBlocks['length'] != '') {
                                                echo ' | ';
                                            }
                                        }
                                        if ($rowBlocks['length'] != '') {
                                            echo $rowBlocks['length'].' min';
                                        }
                                        echo '</div>';
                                        echo '</div>';
                                    }
                                    echo '</div>';
                                }
                                if ($rowBlocks['contents'] != '') {
                                    echo "<div style='padding: 15px 3px 10px 3px; width: 100%; text-align: justify; border-bottom: 1px solid #ddd'>".$rowBlocks['contents'].'</div>';
                                }
                            }
                            echo '</div>';
                            //OUTCOMES
							echo "<div id='tabs3'>";
                            try {
                                $dataOutcomes = $dataMulti;
                                $dataOutcomes['gibbonUnitID'] = $row['gibbonUnitID'];
                                $sqlOutcomes = "(SELECT gibbonOutcome.*, gibbonPlannerEntryOutcome.content FROM gibbonPlannerEntryOutcome JOIN gibbonOutcome ON (gibbonPlannerEntryOutcome.gibbonOutcomeID=gibbonOutcome.gibbonOutcomeID) WHERE $whereMulti AND active='Y')
									UNION
									(SELECT gibbonOutcome.*, gibbonUnitOutcome.content FROM gibbonUnitOutcome JOIN gibbonOutcome ON (gibbonUnitOutcome.gibbonOutcomeID=gibbonOutcome.gibbonOutcomeID) WHERE gibbonUnitID=:gibbonUnitID AND active='Y')
									ORDER BY scope DESC, name";
                                $resultOutcomes = $connection2->prepare($sqlOutcomes);
                                $resultOutcomes->execute($dataOutcomes);
                            } catch (PDOException $e) {
                                echo "<div class='error'>".$e->getMessage().'</div>';
                            }
                            if ($resultOutcomes->rowCount() < 1) {
                                echo "<div class='error'>";
                                echo __($guid, 'There are no records to display.');
                                echo '</div>';
                            } else {
                                echo "<table cellspacing='0' style='width: 100%'>";
                                echo "<tr class='head'>";
                                echo '<th>';
                                echo __($guid, 'Scope');
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
                                    if ($rowOutcomes['scope'] == 'Learning Area' and $rowOutcomes['gibbonDepartmentID'] != '') {
                                        try {
                                            $dataLearningArea = array('gibbonDepartmentID' => $rowOutcomes['gibbonDepartmentID']);
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
                            echo '</div>';
                            //LESSONS
                            echo "<div id='tabs4'>";
                            $resourceContents = '';
                            try {
                                $dataLessons = $dataMulti;
                                $sqlLessons = "SELECT * FROM gibbonPlannerEntry WHERE $whereMulti";
                                $resultLessons = $connection2->prepare($sqlLessons);
                                $resultLessons->execute($dataLessons);
                            } catch (PDOException $e) {
                                echo "<div class='error'>".$e->getMessage().'</div>';
                            }

                            if ($resultLessons->rowCount() < 1) {
                                echo "<div class='warning'>";
                                echo __($guid, 'There are no records to display.');
                                echo '</div>';
                            } else {
                                while ($rowLessons = $resultLessons->fetch()) {
                                    echo '<h3>'.$rowLessons['name'].'</h3>';
                                    echo $rowLessons['description'];
                                    $resourceContents .= $rowLessons['description'];
                                    if ($rowLessons['teachersNotes'] != '' and ($highestAction == 'Lesson Planner_viewAllEditMyClasses' or $highestAction == 'Lesson Planner_viewEditAllClasses')) {
                                        echo "<div style='background-color: #F6CECB; padding: 0px 3px 10px 3px; width: 98%; text-align: justify; border-bottom: 1px solid #ddd'><p style='margin-bottom: 0px'><b>".__($guid, "Teacher's Notes").':</b></p> '.$rowLessons['teachersNotes'].'</div>';
                                        $resourceContents .= $rowLessons['teachersNotes'];
                                    }

                                    try {
                                        $dataBlock = array('gibbonPlannerEntryID' => $rowLessons['gibbonPlannerEntryID']);
                                        $sqlBlock = 'SELECT * FROM gibbonUnitClassBlock WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID ORDER BY sequenceNumber';
                                        $resultBlock = $connection2->prepare($sqlBlock);
                                        $resultBlock->execute($dataBlock);
                                    } catch (PDOException $e) {
                                        echo "<div class='error'>".$e->getMessage().'</div>';
                                    }

                                    while ($rowBlock = $resultBlock->fetch()) {
                                        echo "<h5 style='font-size: 85%'>".$rowBlock['title'].'</h5>';
                                        echo '<p>';
                                        echo '<b>'.__($guid, 'Type').'</b>: '.$rowBlock['type'].'<br/>';
                                        echo '<b>'.__($guid, 'Length').'</b>: '.$rowBlock['length'].'<br/>';
                                        echo '<b>'.__($guid, 'Contents').'</b>: '.$rowBlock['contents'].'<br/>';
                                        $resourceContents .= $rowBlock['contents'];
                                        if ($rowBlock['teachersNotes'] != '' and ($highestAction == 'Lesson Planner_viewAllEditMyClasses' or $highestAction == 'Lesson Planner_viewEditAllClasses')) {
                                            echo "<div style='background-color: #F6CECB; padding: 0px 3px 10px 3px; width: 98%; text-align: justify; border-bottom: 1px solid #ddd'><p style='margin-bottom: 0px'><b>".__($guid, "Teacher's Notes").':</b></p> '.$rowBlock['teachersNotes'].'</div>';
                                            $resourceContents .= $rowBlock['teachersNotes'];
                                        }
                                        echo '</p>';
                                    }

									//Print chats
                                    try {
                                        $dataDiscuss = array('gibbonPlannerEntryID' => $rowLessons['gibbonPlannerEntryID']);
                                        $sqlDiscuss = 'SELECT gibbonPlannerEntryDiscuss.*, title, surname, preferredName, category FROM gibbonPlannerEntryDiscuss JOIN gibbonPerson ON (gibbonPlannerEntryDiscuss.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID ORDER BY timestamp';
                                        $resultDiscuss = $connection2->prepare($sqlDiscuss);
                                        $resultDiscuss->execute($dataDiscuss);
                                    } catch (PDOException $e) { print $e->getMessage();}

                                    if ($resultDiscuss->rowCount() > 0) {
                                        echo "<h5 style='font-size: 85%'>".__($guid, 'Chat').'</h5>';
                                        echo '<style type="text/css">';
                                        echo 'table.chatbox { width: 90%!important }';
                                        echo '</style>';
                                        echo getThread($guid, $connection2, $rowLessons['gibbonPlannerEntryID'], null, 0, null, null, null, null, null, $class[1], $_SESSION[$guid]['gibbonPersonID'], 'Teacher', false, true);
                                    }
                                }
                            }
                            echo '</div>';
                            //RESOURCES
                            echo "<div id='tabs5'>";
                            $noReosurces = true;

							//Links
							$links = '';
                            $linksArray = array();
                            $linksCount = 0;
                            $dom = new DOMDocument();
                            $dom->loadHTML($resourceContents);
                            foreach ($dom->getElementsByTagName('a') as $node) {
                                if ($node->nodeValue != '') {
                                    $linksArray[$linksCount] = "<li><a href='".$node->getAttribute('href')."'>".$node->nodeValue.'</a></li>';
                                    ++$linksCount;
                                }
                            }

                            $linksArray = array_unique($linksArray);
                            natcasesort($linksArray);

                            foreach ($linksArray as $link) {
                                $links .= $link;
                            }

                            if ($links != '') {
                                echo '<h2>';
                                echo 'Links';
                                echo '</h2>';
                                echo '<ul>';
                                echo $links;
                                echo '</ul>';
                                $noReosurces = false;
                            }

							//Images
							$images = '';
                            $imagesArray = array();
                            $imagesCount = 0;
                            $dom2 = new DOMDocument();
                            $dom2->loadHTML($resourceContents);
                            foreach ($dom2->getElementsByTagName('img') as $node) {
                                if ($node->getAttribute('src') != '') {
                                    $imagesArray[$imagesCount] = "<img class='resource' style='margin: 10px 0; max-width: 560px' src='".$node->getAttribute('src')."'/><br/>";
                                    ++$imagesCount;
                                }
                            }

                            $imagesArray = array_unique($imagesArray);
                            natcasesort($imagesArray);

                            foreach ($imagesArray as $image) {
                                $images .= $image;
                            }

                            if ($images != '') {
                                echo '<h2>';
                                echo 'Images';
                                echo '</h2>';
                                echo $images;
                                $noReosurces = false;
                            }

							//Embeds
							$embeds = '';
                            $embedsArray = array();
                            $embedsCount = 0;
                            $dom2 = new DOMDocument();
                            $dom2->loadHTML($resourceContents);
                            foreach ($dom2->getElementsByTagName('iframe') as $node) {
                                if ($node->getAttribute('src') != '') {
                                    $embedsArray[$embedsCount] = "<iframe style='max-width: 560px' width='".$node->getAttribute('width')."' height='".$node->getAttribute('height')."' src='".$node->getAttribute('src')."' frameborder='".$node->getAttribute('frameborder')."'></iframe>";
                                    ++$embedsCount;
                                }
                            }

                            $embedsArray = array_unique($embedsArray);
                            natcasesort($embedsArray);

                            foreach ($embedsArray as $embed) {
                                $embeds .= $embed.'<br/><br/>';
                            }

                            if ($embeds != '') {
                                echo '<h2>';
                                echo 'Embeds';
                                echo '</h2>';
                                echo $embeds;
                                $noReosurces = false;
                            }

							//No resources!
							if ($noReosurces) {
								echo "<div class='error'>";
								echo __($guid, 'There are no records to display.');
								echo '</div>';
							}
                            echo '</div>';
                            echo '</div>';
                        }
                    }
                }
            }
        }
    }
}
?>
