                        if (! $this->getSecurity()->isActionAccessible('/modules/Markbook/markbook_view.php')) {
                            $this->displayMessage('Your request failed because you do not have access to this action.');
                        } else {
                            $details->highestAction = getHighestGroupedAction($guid, '/modules/Markbook/markbook_view.php', $connection2);
                            if ($details->highestAction == false) {
                                $this->displayMessage('The highest grouped action cannot be determined.');
                            } else {
                                //Get alternative header names
                                $attainmentAlternativeName = getSettingByScope($connection2, 'Markbook', 'attainmentAlternativeName');
                                $attainmentAlternativeNameAbrev = getSettingByScope($connection2, 'Markbook', 'attainmentAlternativeNameAbrev');
                                $effortAlternativeName = getSettingByScope($connection2, 'Markbook', 'effortAlternativeName');
                                $effortAlternativeNameAbrev = getSettingByScope($connection2, 'Markbook', 'effortAlternativeNameAbrev');

                                $alert = getAlert($guid, $connection2, 002);
                                $role = getRoleCategory($_SESSION['gibbonRoleIDCurrent'], $connection2);
                                if ($role == 'Parent') {
                                    $showParentAttainmentWarning = getSettingByScope($connection2, 'Markbook', 'showParentAttainmentWarning');
                                    $showParentEffortWarning = getSettingByScope($connection2, 'Markbook', 'showParentEffortWarning');
                                } else {
                                    $showParentAttainmentWarning = 'Y';
                                    $showParentEffortWarning = 'Y';
                                }
                                $entryCount = 0;

                                $and = '';
                                $and2 = '';
                                $dataList = array();
                                $dataEntry = array();
                                $filter = null;
                                if (isset($_GET['filter'])) {
                                    $filter = $_GET['filter'];
                                } elseif (isset($_POST['filter'])) {
                                    $filter = $_POST['filter'];
                                }
                                if ($filter == '') {
                                    $filter = $_SESSION['gibbonSchoolYearID'];
                                }
                                if ($filter != '*') {
                                    $dataList['filter'] = $filter;
                                    $and .= ' AND gibbonSchoolYearID=:filter';
                                }

                                $filter2 = null;
                                if (isset($_GET['filter2'])) {
                                    $filter2 = $_GET['filter2'];
                                } elseif (isset($_POST['filter2'])) {
                                    $filter2 = $_POST['filter2'];
                                }
                                if ($filter2 != '') {
                                    $dataList['filter2'] = $filter2;
                                    $and .= ' AND gibbonDepartmentID=:filter2';
                                }

                                $filter3 = null;
                                if (isset($_GET['filter3'])) {
                                    $filter3 = $_GET['filter3'];
                                } elseif (isset($_POST['filter3'])) {
                                    $filter3 = $_POST['filter3'];
                                }
                                if ($filter3 != '') {
                                    $dataEntry['filter3'] = $filter3;
                                    $and2 .= ' AND type=:filter3';
                                }

                                echo '<p>';
                                echo trans::__('This page displays academic results for a student throughout their school career. Only subjects with published results are shown.');
                                echo '</p>';

                                echo "<form method='post' action='".$_SESSION['absoluteURL'].'/index.php?q='.$_GET['q']."&gibbonPersonID=$details->personID&search=$details->search&allStudents=$details->allStudents&subpage=Markbook'>";
                                echo"<table class='noIntBorder' cellspacing='0' style='width: 100%'>";
                                ?>
									<tr>
										<td>
											<b><?php echo trans::__('Learning Areas') ?></b><br/>
											<span class="emphasis small"></span>
										</td>
										<td class="right">
											<?php
											echo "<select name='filter2' id='filter2' style='width:302px'>";
											echo "<option value=''>".trans::__('All Learning Areas').'</option>';
											try {
												$dataSelect = array();
												$sqlSelect = "SELECT * FROM gibbonDepartment WHERE type='Learning Area' ORDER BY name";
												$resultSelect = $connection2->prepare($sqlSelect);
												$resultSelect->execute($dataSelect);
											} catch (PDOException $e) {
											}
											while ($rowSelect = $resultSelect->fetch()) {
												$selected = '';
												if ($rowSelect['gibbonDepartmentID'] == $filter2) {
													$selected = 'selected';
												}
												echo "<option $selected value='".$rowSelect['gibbonDepartmentID']."'>".$rowSelect['name'].'</option>';
											}
											echo '</select>';
											?>
										</td>
									</tr>
									<tr>
										<td>
											<b><?php echo trans::__('School Years') ?></b><br/>
											<span class="emphasis small"></span>
										</td>
										<td class="right">
											<?php
											echo "<select name='filter' id='filter' style='width:302px'>";
											echo "<option value='*'>".trans::__('All Years').'</option>';
											try {
												$dataSelect = array('gibbonPersonID' => $details->personID);
												$sqlSelect = 'SELECT gibbonSchoolYear.gibbonSchoolYearID, gibbonSchoolYear.name AS year, gibbonYearGroup.name AS yearGroup FROM gibbonStudentEnrolment JOIN gibbonSchoolYear ON (gibbonStudentEnrolment.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) WHERE gibbonPersonID=:gibbonPersonID ORDER BY gibbonSchoolYear.sequenceNumber';
												$resultSelect = $connection2->prepare($sqlSelect);
												$resultSelect->execute($dataSelect);
											} catch (PDOException $e) {
											}
											while ($rowSelect = $resultSelect->fetch()) {
												$selected = '';
												if ($rowSelect['gibbonSchoolYearID'] == $filter) {
													$selected = 'selected';
												}
												echo "<option $selected value='".$rowSelect['gibbonSchoolYearID']."'>".$rowSelect['year'].' ('.$rowSelect['yearGroup'].')</option>';
											}
											echo '</select>';
											?>
										</td>
									</tr>
									<?php
									$types = getSettingByScope($connection2, 'Markbook', 'markbookType');
									if ($types != false) {
										$types = explode(',', $types);
										?>
										<tr>
											<td>
												<b><?php echo trans::__('Type') ?></b><br/>
												<span class="emphasis small"></span>
											</td>
											<td class="right">
												<select name="filter3" id="filter3" class="standardWidth">
													<option value=""></option>
													<?php
													for ($i = 0; $i < count($types); ++$i) {
														$selected = '';
														if ($filter3 == $types[$i]) {
															$selected = 'selected';
														}
														?>
														<option <?php echo $selected ?> value="<?php echo trim($types[$i]) ?>"><?php echo trim($types[$i]) ?></option>
													<?php

													}
											?>
												</select>
											</td>
										</tr>
										<?php

										}
										echo '<tr>';
										echo "<td class='right' colspan=2>";
										echo "<input type='hidden' name='q' value='".$_GET['q']."'>";
										echo "<input checked type='checkbox' name='details' class='details' value='Yes' />";
										echo "<span style='font-size: 85%; font-weight: normal; font-style: italic'> ".trans::__('Show/Hide Details').'</span>';
										?>
										<script type="text/javascript">
											/* Show/Hide detail control */
											$(document).ready(function(){
												$(".details").click(function(){
													if ($('input[name=details]:checked').val()=="Yes" ) {
														$(".detailItem").slideDown("fast", $("#detailItem").css("{'display' : 'table-row'}"));
													}
													else {
														$(".detailItem").slideUp("fast");
													}
												 });
											});
										</script>
										<?php
										echo "<input type='submit' value='".trans::__('Go')."'>";
									echo '</td>';
									echo '</tr>';
								echo'</table>';
								echo '</form>';

                                //Get class list
                                try {
                                    $dataList['gibbonPersonID'] = $details->personID;
                                    $dataList['gibbonPersonID2'] = $details->personID;
                                    $sqlList = "SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourse.name, gibbonCourseClass.gibbonCourseClassID, gibbonScaleGrade.value AS target FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) LEFT JOIN gibbonMarkbookTarget ON (gibbonMarkbookTarget.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID AND gibbonMarkbookTarget.gibbonPersonIDStudent=:gibbonPersonID2) LEFT JOIN gibbonScaleGrade ON (gibbonMarkbookTarget.gibbonScaleGradeID=gibbonScaleGrade.gibbonScaleGradeID) WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID $and ORDER BY course, class";
                                    $resultList = $connection2->prepare($sqlList);
                                    $resultList->execute($dataList);
                                } catch (PDOException $e) {
                                    echo "<div class='error'>".$e->getMessage().'</div>';
                                }

                                if ($resultList->rowCount() > 0) {
                                    while ($rowList = $resultList->fetch()) {
                                        try {
                                            $dataEntry['gibbonPersonID'] = $details->personID;
                                            $dataEntry['gibbonCourseClassID'] = $rowList['gibbonCourseClassID'];
                                            if ($details->highestAction == 'View Markbook_viewMyChildrensClasses') {
                                                $sqlEntry = "SELECT *, gibbonMarkbookColumn.comment AS commentOn, gibbonMarkbookColumn.uploadedResponse AS uploadedResponseOn, gibbonMarkbookEntry.comment AS comment FROM gibbonMarkbookEntry JOIN gibbonMarkbookColumn ON (gibbonMarkbookEntry.gibbonMarkbookColumnID=gibbonMarkbookColumn.gibbonMarkbookColumnID) WHERE gibbonPersonIDStudent=:gibbonPersonID AND gibbonCourseClassID=:gibbonCourseClassID AND complete='Y' AND completeDate<='".date('Y-m-d')."' AND viewableParents='Y' $and2 ORDER BY completeDate";
                                            } else {
                                                $sqlEntry = "SELECT *, gibbonMarkbookColumn.comment AS commentOn, gibbonMarkbookColumn.uploadedResponse AS uploadedResponseOn, gibbonMarkbookEntry.comment AS comment FROM gibbonMarkbookEntry JOIN gibbonMarkbookColumn ON (gibbonMarkbookEntry.gibbonMarkbookColumnID=gibbonMarkbookColumn.gibbonMarkbookColumnID) WHERE gibbonPersonIDStudent=:gibbonPersonID AND gibbonCourseClassID=:gibbonCourseClassID AND complete='Y' AND completeDate<='".date('Y-m-d')."' $and2 ORDER BY completeDate";
                                            }
                                            $resultEntry = $connection2->prepare($sqlEntry);
                                            $resultEntry->execute($dataEntry);
                                        } catch (PDOException $e) {
                                            echo "<div class='error'>".$e->getMessage().'</div>';
                                        }

                                        if ($resultEntry->rowCount() > 0) {
                                            echo "<a name='".$rowList['gibbonCourseClassID']."'></a><h4>".$rowList['course'].'.'.$rowList['class']." <span style='font-size:85%; font-style: italic'>(".$rowList['name'].')</span></h4>';

                                            try {
                                                $dataTeachers = array('gibbonCourseClassID' => $rowList['gibbonCourseClassID']);
                                                $sqlTeachers = "SELECT title, surname, preferredName FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE role='Teacher' AND gibbonCourseClassID=:gibbonCourseClassID ORDER BY surname, preferredName";
                                                $resultTeachers = $connection2->prepare($sqlTeachers);
                                                $resultTeachers->execute($dataTeachers);
                                            } catch (PDOException $e) {
                                                echo "<div class='error'>".$e->getMessage().'</div>';
                                            }

                                            $teachers = '<p><b>'.trans::__('Taught by:').'</b> ';
                                            while ($rowTeachers = $resultTeachers->fetch()) {
                                                $teachers = $teachers.$rowTeachers['title'].' '.$rowTeachers['surname'].', ';
                                            }
                                            $teachers = substr($teachers, 0, -2);
                                            $teachers = $teachers.'</p>';
                                            echo $teachers;

                                            if ($rowList['target'] != '') {
                                                echo "<div style='font-weight: bold' class='linkTop'>";
                                                echo trans::__('Target').': '.$rowList['target'];
                                                echo '</div>';
                                            }

                                            echo "<table cellspacing='0' style='width: 100%'>";
                                            echo "<tr class='head'>";
                                            echo "<th style='width: 120px'>";
                                            echo trans::__('Assessment');
                                            echo '</th>';
                                            echo "<th style='width: 75px; text-align: center'>";
                                            if ($attainmentAlternativeName != '') {
                                                echo $attainmentAlternativeName;
                                            } else {
                                                echo trans::__('Attainment');
                                            }
                                            echo '</th>';
                                            echo "<th style='width: 75px; text-align: center'>";
                                            if ($effortAlternativeName != '') {
                                                echo $effortAlternativeName;
                                            } else {
                                                echo trans::__('Effort');
                                            }
                                            echo '</th>';
                                            echo '<th>';
                                            echo trans::__('Comment');
                                            echo '</th>';
                                            echo "<th style='width: 75px'>";
                                            echo trans::__('Submission');
                                            echo '</th>';
                                            echo '</tr>';

                                            $count = 0;
                                            while ($rowEntry = $resultEntry->fetch()) {
                                                if ($count % 2 == 0) {
                                                    $rowNum = 'even';
                                                } else {
                                                    $rowNum = 'odd';
                                                }
                                                ++$count;
                                                ++$entryCount;

                                                echo "<tr class=$rowNum>";
                                                echo '<td>';
                                                echo "<span title='".htmlPrep($rowEntry['description'])."'><b><u>".$rowEntry['name'].'</u></b></span><br/>';
                                                echo "<span style='font-size: 90%; font-style: italic; font-weight: normal'>";
                                                $unit = getUnit($connection2, $rowEntry['gibbonUnitID'], $rowEntry['gibbonHookID'], $rowEntry['gibbonCourseClassID']);
                                                if (isset($unit[0])) {
                                                    echo $unit[0].'<br/>';
                                                }
                                                if (isset($unit[1])) {
                                                    if ($unit[1] != '') {
                                                        echo $unit[1].' '.trans::__('Unit').'</em><br/>';
                                                    }
                                                }
                                                if ($rowEntry['completeDate'] != '') {
                                                    echo trans::__('Marked on').' '.dateConvertBack($guid, $rowEntry['completeDate']).'<br/>';
                                                } else {
                                                    echo trans::__('Unmarked').'<br/>';
                                                }
                                                echo $rowEntry['type'];
                                                if ($rowEntry['attachment'] != '' and file_exists($_SESSION['absolutePath'].'/'.$rowEntry['attachment'])) {
                                                    echo " | <a 'title='".trans::__('Download more information')."' href='".$_SESSION['absoluteURL'].'/'.$rowEntry['attachment']."'>".trans::__('More info').'</a>';
                                                }
                                                echo '</span><br/>';
                                                echo '</td>';
                                                if ($rowEntry['attainment'] == 'N' or ($rowEntry['gibbonScaleIDAttainment'] == '' and $rowEntry['gibbonRubricIDAttainment'] == '')) {
                                                    echo "<td class='dull' style='color: #bbb; text-align: center'>";
                                                    echo trans::__('N/A');
                                                    echo '</td>';
                                                } else {
                                                    echo "<td style='text-align: center'>";
                                                    $attainmentExtra = '';
                                                    try {
                                                        $dataAttainment = array('gibbonScaleIDAttainment' => $rowEntry['gibbonScaleIDAttainment']);
                                                        $sqlAttainment = 'SELECT * FROM gibbonScale WHERE gibbonScaleID=:gibbonScaleIDAttainment';
                                                        $resultAttainment = $connection2->prepare($sqlAttainment);
                                                        $resultAttainment->execute($dataAttainment);
                                                    } catch (PDOException $e) {
                                                        echo "<div class='error'>".$e->getMessage().'</div>';
                                                    }
                                                    if ($resultAttainment->rowCount() == 1) {
                                                        $rowAttainment = $resultAttainment->fetch();
                                                        $attainmentExtra = '<br/>'.trans::__($rowAttainment['usage']);
                                                    }
                                                    $styleAttainment = "style='font-weight: bold'";
                                                    if ($rowEntry['attainmentConcern'] == 'Y' and $showParentAttainmentWarning == 'Y') {
                                                        $styleAttainment = "style='color: #".$alert['color'].'; font-weight: bold; border: 2px solid #'.$alert['color'].'; padding: 2px 4px; background-color: #'.$alert['colorBG']."'";
                                                    } elseif ($rowEntry['attainmentConcern'] == 'P' and $showParentAttainmentWarning == 'Y') {
                                                        $styleAttainment = "style='color: #390; font-weight: bold; border: 2px solid #390; padding: 2px 4px; background-color: #D4F6DC'";
                                                    }
                                                    echo "<div $styleAttainment>".$rowEntry['attainmentValue'];
                                                    if ($rowEntry['gibbonRubricIDAttainment'] != '') {
                                                        echo "<a class='thickbox' href='".$_SESSION['absoluteURL'].'/fullscreen.php?q=/modules/Markbook/markbook_view_rubric.php&gibbonRubricID='.$rowEntry['gibbonRubricIDAttainment'].'&gibbonCourseClassID='.$rowList['gibbonCourseClassID'].'&gibbonMarkbookColumnID='.$rowEntry['gibbonMarkbookColumnID']."&gibbonPersonID=$details->personID&mark=FALSE&type=attainment&width=1100&height=550'><img style='margin-bottom: -3px; margin-left: 3px' title='View Rubric' src='./themes/".$_SESSION['gibbonThemeName']."/img/rubric.png'/></a>";
                                                    }
                                                    echo '</div>';
                                                    if ($rowEntry['attainmentValue'] != '') {
                                                        echo "<div class='detailItem' style='font-size: 75%; font-style: italic; margin-top: 2px'><b>".htmlPrep(trans::__($rowEntry['attainmentDescriptor'])).'</b>'.trans::__($attainmentExtra).'</div>';
                                                    }
                                                    echo '</td>';
                                                }
                                                if ($rowEntry['effort'] == 'N' or ($rowEntry['gibbonScaleIDEffort'] == '' and $rowEntry['gibbonRubricIDEffort'] == '')) {
                                                    echo "<td class='dull' style='color: #bbb; text-align: center'>";
                                                    echo trans::__('N/A');
                                                    echo '</td>';
                                                } else {
                                                    echo "<td style='text-align: center'>";
                                                    $effortExtra = '';
                                                    try {
                                                        $dataEffort = array('gibbonScaleIDEffort' => $rowEntry['gibbonScaleIDEffort']);
                                                        $sqlEffort = 'SELECT * FROM gibbonScale WHERE gibbonScaleID=:gibbonScaleIDEffort';
                                                        $resultEffort = $connection2->prepare($sqlEffort);
                                                        $resultEffort->execute($dataEffort);
                                                    } catch (PDOException $e) {
                                                        echo "<div class='error'>".$e->getMessage().'</div>';
                                                    }

                                                    if ($resultEffort->rowCount() == 1) {
                                                        $rowEffort = $resultEffort->fetch();
                                                        $effortExtra = '<br/>'.trans::__($rowEffort['usage']);
                                                    }
                                                    $styleEffort = "style='font-weight: bold'";
                                                    if ($rowEntry['effortConcern'] == 'Y' and $showParentEffortWarning == 'Y') {
                                                        $styleEffort = "style='color: #".$alert['color'].'; font-weight: bold; border: 2px solid #'.$alert['color'].'; padding: 2px 4px; background-color: #'.$alert['colorBG']."'";
                                                    }
                                                    echo "<div $styleEffort>".$rowEntry['effortValue'];
                                                    if ($rowEntry['gibbonRubricIDEffort'] != '') {
                                                        echo "<a class='thickbox' href='".$_SESSION['absoluteURL'].'/fullscreen.php?q=/modules/Markbook/markbook_view_rubric.php&gibbonRubricID='.$rowEntry['gibbonRubricIDEffort'].'&gibbonCourseClassID='.$rowList['gibbonCourseClassID'].'&gibbonMarkbookColumnID='.$rowEntry['gibbonMarkbookColumnID']."&gibbonPersonID=$details->personID&mark=FALSE&type=effort&width=1100&height=550'><img style='margin-bottom: -3px; margin-left: 3px' title='View Rubric' src='./themes/".$_SESSION['gibbonThemeName']."/img/rubric.png'/></a>";
                                                    }
                                                    echo '</div>';
                                                    if ($rowEntry['effortValue'] != '') {
                                                        echo "<div class='detailItem' style='font-size: 75%; font-style: italic; margin-top: 2px'><b>".htmlPrep(trans::__($rowEntry['effortDescriptor'])).'</b>'.trans::__($effortExtra).'</div>';
                                                    }
                                                    echo '</td>';
                                                }
                                                if ($rowEntry['commentOn'] == 'N' and $rowEntry['uploadedResponseOn'] == 'N') {
                                                    echo "<td class='dull' style='color: #bbb; text-align: center'>";
                                                    echo trans::__('N/A');
                                                    echo '</td>';
                                                } else {
                                                    echo '<td>';
                                                    if ($rowEntry['comment'] != '') {
                                                        if (strlen($rowEntry['comment']) > 50) {
                                                            echo "<script type='text/javascript'>";
                                                            echo '$(document).ready(function(){';
                                                            echo "\$(\".comment-$entryCount\").hide();";
                                                            echo "\$(\".show_hide-$entryCount\").fadeIn(1000);";
                                                            echo "\$(\".show_hide-$entryCount\").click(function(){";
                                                            echo "\$(\".comment-$entryCount\").fadeToggle(1000);";
                                                            echo '});';
                                                            echo '});';
                                                            echo '</script>';
                                                            echo '<span>'.substr($rowEntry['comment'], 0, 50).'...<br/>';
                                                            echo "<a title='".trans::__('View Description')."' class='show_hide-$entryCount' onclick='return false;' href='#'>".trans::__('Read more').'</a></span><br/>';
                                                        } else {
                                                            echo nl2br($rowEntry['comment']);
                                                        }
                                                        if ($rowEntry['response'] != '') {
                                                            echo "<a title='Uploaded Response' href='".$_SESSION['absoluteURL'].'/'.$rowEntry['response']."'>".trans::__('Uploaded Response').'</a><br/>';
                                                        }
                                                    }
                                                    echo '</td>';
                                                }
                                                if ($rowEntry['gibbonPlannerEntryID'] == 0) {
                                                    echo "<td class='dull' style='color: #bbb; text-align: center'>";
                                                    echo trans::__('N/A');
                                                    echo '</td>';
                                                } else {
                                                    try {
                                                        $dataSub = array('gibbonPlannerEntryID' => $rowEntry['gibbonPlannerEntryID']);
                                                        $sqlSub = "SELECT * FROM gibbonPlannerEntry WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND homeworkSubmission='Y'";
                                                        $resultSub = $connection2->prepare($sqlSub);
                                                        $resultSub->execute($dataSub);
                                                    } catch (PDOException $e) {
                                                        echo "<div class='error'>".$e->getMessage().'</div>';
                                                    }
                                                    if ($resultSub->rowCount() != 1) {
                                                        echo "<td class='dull' style='color: #bbb; text-align: center'>";
                                                        echo trans::__('N/A');
                                                        echo '</td>';
                                                    } else {
                                                        echo '<td>';
                                                        $rowSub = $resultSub->fetch();

                                                        try {
                                                            $dataWork = array('gibbonPlannerEntryID' => $rowEntry['gibbonPlannerEntryID'], 'gibbonPersonID' => $_GET['gibbonPersonID']);
                                                            $sqlWork = 'SELECT * FROM gibbonPlannerEntryHomework WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPersonID=:gibbonPersonID ORDER BY count DESC';
                                                            $resultWork = $connection2->prepare($sqlWork);
                                                            $resultWork->execute($dataWork);
                                                        } catch (PDOException $e) {
                                                            echo "<div class='error'>".$e->getMessage().'</div>';
                                                        }
                                                        if ($resultWork->rowCount() > 0) {
                                                            $rowWork = $resultWork->fetch();

                                                            if ($rowWork['status'] == 'Exemption') {
                                                                $linkText = trans::__('Exemption');
                                                            } elseif ($rowWork['version'] == 'Final') {
                                                                $linkText = trans::__('Final');
                                                            } else {
                                                                $linkText = trans::__('Draft').' '.$rowWork['count'];
                                                            }

                                                            $style = '';
                                                            $status = 'On Time';
                                                            if ($rowWork['status'] == 'Exemption') {
                                                                $status = trans::__('Exemption');
                                                            } elseif ($rowWork['status'] == 'Late') {
                                                                $style = "style='color: #ff0000; font-weight: bold; border: 2px solid #ff0000; padding: 2px 4px'";
                                                                $status = trans::__('Late');
                                                            }

                                                            if ($rowWork['type'] == 'File') {
                                                                echo "<span title='".$rowWork['version'].". $status. ".sprintf(trans::__('Submitted at %1$s on %2$s'), substr($rowWork['timestamp'], 11, 5), dateConvertBack($guid, substr($rowWork['timestamp'], 0, 10)))."' $style><a href='".$_SESSION['absoluteURL'].'/'.$rowWork['location']."'>$linkText</a></span>";
                                                            } elseif ($rowWork['type'] == 'Link') {
                                                                echo "<span title='".$rowWork['version'].". $status. ".sprintf(trans::__('Submitted at %1$s on %2$s'), substr($rowWork['timestamp'], 11, 5), dateConvertBack($guid, substr($rowWork['timestamp'], 0, 10)))."' $style><a target='_blank' href='".$rowWork['location']."'>$linkText</a></span>";
                                                            } else {
                                                                echo "<span title='$status. ".sprintf(trans::__('Recorded at %1$s on %2$s'), substr($rowWork['timestamp'], 11, 5), dateConvertBack($guid, substr($rowWork['timestamp'], 0, 10)))."' $style>$linkText</span>";
                                                            }
                                                        } else {
                                                            if (date('Y-m-d H:i:s') < $rowSub['homeworkDueDateTime']) {
                                                                echo "<span title='Pending'>".trans::__('Pending').'</span>';
                                                            } else {
                                                                if ($details->student->getField('dateStart') > $rowSub['date']) {
                                                                    echo "<span title='".trans::__('Student joined school after assessment was given.')."' style='color: #000; font-weight: normal; border: 2px none #ff0000; padding: 2px 4px'>".trans::__('NA').'</span>';
                                                                } else {
                                                                    if ($rowSub['homeworkSubmissionRequired'] == 'Compulsory') {
                                                                        echo "<div style='color: #ff0000; font-weight: bold; border: 2px solid #ff0000; padding: 2px 4px; margin: 2px 0px'>".trans::__('Incomplete').'</div>';
                                                                    } else {
                                                                        echo trans::__('Not submitted online');
                                                                    }
                                                                }
                                                            }
                                                        }
                                                        echo '</td>';
                                                    }
                                                }
                                                echo '</tr>';
                                                if (strlen($rowEntry['comment']) > 50) {
                                                    echo "<tr class='comment-$entryCount' id='comment-$entryCount'>";
                                                    echo '<td colspan=6>';
                                                    echo nl2br($rowEntry['comment']);
                                                    echo '</td>';
                                                    echo '</tr>';
                                                }
                                            }
                                            echo '</table>';
                                        }
                                    }
                                }
                                if ($entryCount < 1) {
                                    $this->displayMessage('There are no records to display.');
                                }
                            }
                        }
