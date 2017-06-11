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

if (isActionAccessible($guid, $connection2, '/modules/Planner/units_dump.php') == false) {
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
        echo "<div class='trail'>";
        echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/units.php&gibbonSchoolYearID='.$_GET['gibbonSchoolYearID'].'&gibbonCourseID='.$_GET['gibbonCourseID']."'>".__($guid, 'Unit Planner')."</a> > </div><div class='trailEnd'>".__($guid, 'Dump Unit').'</div>';
        echo '</div>';

        //Check if courseschool year specified
        $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'];
        $gibbonCourseID = $_GET['gibbonCourseID'];
        $gibbonUnitID = $_GET['gibbonUnitID'];
        if ($gibbonCourseID == '' or $gibbonSchoolYearID == '') {
            echo "<div class='error'>";
            echo __($guid, 'You have not specified one or more required parameters.');
            echo '</div>';
        } else {
            try {
                if ($highestAction == 'Unit Planner_all') {
                    $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonCourseID' => $gibbonCourseID);
                    $sql = 'SELECT * FROM gibbonCourse WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseID=:gibbonCourseID';
                } elseif ($highestAction == 'Unit Planner_learningAreas') {
                    $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonCourseID' => $gibbonCourseID, 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                    $sql = "SELECT gibbonCourseID, gibbonCourse.name, gibbonCourse.nameShort, gibbonDepartment.gibbonDepartmentID FROM gibbonCourse JOIN gibbonDepartment ON (gibbonCourse.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE gibbonDepartmentStaff.gibbonPersonID=:gibbonPersonID AND (role='Coordinator' OR role='Assistant Coordinator' OR role='Teacher (Curriculum)') AND gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseID=:gibbonCourseID ORDER BY gibbonCourse.nameShort";
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
                $yearName = $row['name'];
                $gibbonDepartmentID = $row['gibbonDepartmentID'];

                //Check if unit specified
                if ($gibbonUnitID == '') {
                    echo "<div class='error'>";
                    echo __($guid, 'You have not specified one or more required parameters.');
                    echo '</div>';
                } else {
                    if ($gibbonUnitID == '') {
                        echo "<div class='error'>";
                        echo __($guid, 'You have not specified one or more required parameters.');
                        echo '</div>';
                    } else {
                        try {
                            $data = array('gibbonUnitID' => $gibbonUnitID, 'gibbonCourseID' => $gibbonCourseID);
                            $sql = 'SELECT gibbonCourse.nameShort AS courseName, gibbonSchoolYearID, gibbonUnit.* FROM gibbonUnit JOIN gibbonCourse ON (gibbonUnit.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonUnitID=:gibbonUnitID AND gibbonUnit.gibbonCourseID=:gibbonCourseID';
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }

                        if ($result->rowCount() != 1) {
                            echo "<div class='error'>";
                            echo __($guid, 'The specified record cannot be found.');
                            echo '</div>';
                        } else {
                            //Let's go!
                            $row = $result->fetch();

                            echo '<p>';
                            echo sprintf(__($guid, 'This page allows you to view all of the content of a selected unit (%1$s). If you wish to take this unit out of Gibbon, simply copy and paste the contents into a word processing application.'), '<b><u>'.$row['courseName'].' - '.$row['name'].'</u></b>');
                            echo '</p>';

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
                                //Prep classes in this unit
                                try {
                                    $dataClass = array('gibbonUnitID' => $gibbonUnitID);
                                    $sqlClass = 'SELECT gibbonUnitClass.gibbonCourseClassID, gibbonCourseClass.nameShort FROM gibbonUnitClass JOIN gibbonCourseClass ON (gibbonUnitClass.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonUnitID=:gibbonUnitID ORDER BY nameShort';
                                    $resultClass = $connection2->prepare($sqlClass);
                                    $resultClass->execute($dataClass);
                                } catch (PDOException $e) {
                                    echo "<div class='error'>".$e->getMessage().'</div>';
                                }

                                //Tab links
                                echo '<ul>';
                            echo "<li><a href='#tabs1'>".__($guid, 'Unit Overview').'</a></li>';
                            echo "<li><a href='#tabs2'>".__($guid, 'Smart Blocks').'</a></li>';
                            echo "<li><a href='#tabs3'>".__($guid, 'Resources').'</a></li>';
                            echo "<li><a href='#tabs4'>".__($guid, 'Outcomes').'</a></li>';
                            $classes = array();
                            $classCount = 0;
                            while ($rowClass = $resultClass->fetch()) {
                                echo "<li><a href='#tabs".($classCount + 5)."'>".$row['courseName'].'.'.$rowClass['nameShort'].'</a></li>';
                                $classes[$classCount][0] = $rowClass['nameShort'];
                                $classes[$classCount][1] = $rowClass['gibbonCourseClassID'];
                                ++$classCount;
                            }
                            echo '</ul>';

                            //Tabs
                            echo "<div id='tabs1'>";
                            if ($row['details'] == '') {
                                echo "<div class='error'>";
                                echo __($guid, 'There are no records to display.');
                                echo '</div>';
                            } else {
                                echo '<h2>';
                                echo __($guid, 'Description');
                                echo '</h2>';
                                echo '<p>';
                                echo $row['description'];
                                echo '</p>';

                                if ($row['tags'] != '') {
                                    echo '<h2>';
                                    echo __($guid, 'Concepts & Keywords');
                                    echo '</h2>';
                                    echo '<p>';
                                    echo $row['tags'];
                                    echo '</p>';
                                }
                                if ($row['details'] != '') {
                                    echo '<h2>';
                                    echo __($guid, 'Unit Outline');
                                    echo '</h2>';
                                    echo '<p>';
                                    echo $row['details'];
                                    echo '</p>';
                                }
                            }
                            echo '</div>';
                            echo "<div id='tabs2'>";
                            try {
                                $dataBlocks = array('gibbonUnitID' => $gibbonUnitID);
                                $sqlBlocks = 'SELECT * FROM gibbonUnitBlock WHERE gibbonUnitID=:gibbonUnitID ORDER BY sequenceNumber';
                                $resultBlocks = $connection2->prepare($sqlBlocks);
                                $resultBlocks->execute($dataBlocks);
                            } catch (PDOException $e) {
                                echo "<div class='error'>".$e->getMessage().'</div>';
                            }

                            $resourceContents = $row['details'];

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
                                        echo "<div style='text-align: right; font-size: 85%; font-style: italic; margin-top: 12px; border-bottom: 1px solid #ddd; height: 21px'>";
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
                                    $resourceContents .= $rowBlocks['contents'];
                                }
                                if ($rowBlocks['teachersNotes'] != '') {
                                    echo "<div style='background-color: #F6CECB; padding: 0px 3px 10px 3px; width: 98%; text-align: justify; border-bottom: 1px solid #ddd'><p style='margin-bottom: 0px'><b>".__($guid, "Teacher's Notes").':</b></p> '.$rowBlocks['teachersNotes'].'</div>';
                                    $resourceContents .= $rowBlocks['teachersNotes'];
                                }
                            }

                            echo '</div>';
                            echo "<div id='tabs3'>";
							//Resources
							$noReosurces = true;

							//Links
							$links = '';
                            $linksArray = array();
                            $linksCount = 0;
                            $dom = new DOMDocument();
                            @$dom->loadHTML($resourceContents);
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
                            @$dom2->loadHTML($resourceContents);
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
                            @$dom2->loadHTML($resourceContents);
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
                            echo "<div id='tabs4'>";
								//Spit out outcomes
								try {
									$dataBlocks = array('gibbonUnitID' => $gibbonUnitID);
									$sqlBlocks = "SELECT gibbonUnitOutcome.*, scope, name, nameShort, category, gibbonYearGroupIDList FROM gibbonUnitOutcome JOIN gibbonOutcome ON (gibbonUnitOutcome.gibbonOutcomeID=gibbonOutcome.gibbonOutcomeID) WHERE gibbonUnitID=:gibbonUnitID AND active='Y' ORDER BY sequenceNumber";
									$resultBlocks = $connection2->prepare($sqlBlocks);
									$resultBlocks->execute($dataBlocks);
								} catch (PDOException $e) {
									echo "<div class='error'>".$e->getMessage().'</div>';
								}
								if ($resultBlocks->rowCount() > 0) {
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
									while ($rowBlocks = $resultBlocks->fetch()) {
										if ($count % 2 == 0) {
											$rowNum = 'even';
										} else {
											$rowNum = 'odd';
										}

										//COLOR ROW BY STATUS!
										echo "<tr class=$rowNum>";
										echo '<td>';
										echo '<b>'.$rowBlocks['scope'].'</b><br/>';
										if ($rowBlocks['scope'] == 'Learning Area' and $gibbonDepartmentID != '') {
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
										echo '<b>'.$rowBlocks['category'].'</b><br/>';
										echo '</td>';
										echo '<td>';
										echo '<b>'.$rowBlocks['nameShort'].'</b><br/>';
										echo "<span style='font-size: 75%; font-style: italic'>".$rowBlocks['name'].'</span>';
										echo '</td>';
										echo '<td>';
										echo getYearGroupsFromIDList($guid, $connection2, $rowBlocks['gibbonYearGroupIDList']);
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
										if ($rowBlocks['content'] != '') {
											echo "<a title='".__($guid, 'View Description')."' class='show_hide-$count' onclick='false' href='#'><img style='padding-left: 0px' src='".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/page_down.png' alt='".__($guid, 'Show Comment')."' onclick='return false;' /></a>";
										}
										echo '</td>';
										echo '</tr>';
										if ($rowBlocks['content'] != '') {
											echo "<tr class='description-$count' id='description-$count'>";
											echo '<td colspan=6>';
											echo $rowBlocks['content'];
											echo '</td>';
											echo '</tr>';
										}
										echo '</tr>';

										++$count;
									}
									echo '</table>';
								}

								echo '</div>';
								$classCount = 0;
								foreach ($classes as $class) {
									echo "<div id='tabs".($classCount + 5)."'>";

									//Print Lessons
									echo '<h2>'.__($guid, 'Lessons').'</h2>';
									try {
										$dataLessons = array('gibbonCourseClassID' => $class[1], 'gibbonUnitID' => $gibbonUnitID);
										$sqlLessons = 'SELECT * FROM gibbonPlannerEntry WHERE gibbonCourseClassID=:gibbonCourseClassID AND gibbonUnitID=:gibbonUnitID ORDER BY date';
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
											echo $rowLessons['description'].'<br/>';
											if ($rowLessons['teachersNotes'] != '') {
												echo "<div style='background-color: #F6CECB; padding: 0px 3px 10px 3px; width: 98%; text-align: justify; border-bottom: 1px solid #ddd'><p style='margin-bottom: 0px'><b>".__($guid, "Teacher's Notes").':</b></p> '.$rowLessons['teachersNotes'].'</div>';
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
												if ($rowBlock['teachersNotes'] != '') {
													echo "<div style='background-color: #F6CECB; padding: 0px 3px 10px 3px; width: 98%; text-align: justify; border-bottom: 1px solid #ddd'><p style='margin-bottom: 0px'><b>".__($guid, "Teacher's Notes").':</b></p> '.$rowBlock['teachersNotes'].'</div>';
												}
												echo '</p>';
											}

											//Print chats
											echo "<h5 style='font-size: 85%'>".__($guid, 'Chat').'</h5>';
                                        echo getThread($guid, $connection2, $rowLessons['gibbonPlannerEntryID'], null, 0, null, null, null, null, null, $class[1], $_SESSION[$guid]['gibbonPersonID'], 'Teacher', false);
                                    }
                                }
                                echo '</div>';
                                ++$classCount;
                            }
                            echo '</div>';
                        }
                    }
                }
            }
        }
    }
}
?>
