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
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Planner/planner_view_full_submit_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        echo "<div class='error'>";
        echo __('The highest grouped action cannot be determined.');
        echo '</div>';
    } else {
        $viewBy = $_GET['viewBy'];
		$subView = $_GET['subView'];
		$class = null;
		$date = null;
		$gibbonCourseClassID = null;
        if ($viewBy != 'date' and $viewBy != 'class') {
            $viewBy = 'date';
        }
        if ($viewBy == 'date') {
            $date = $_GET['date'];
            if (!empty($_GET['dateHuman'])) {
                $date = dateConvert($guid, $_GET['dateHuman']);
            }
            if ($date == '') {
                $date = date('Y-m-d');
            }
            list($dateYear, $dateMonth, $dateDay) = explode('-', $date);
            $dateStamp = mktime(0, 0, 0, $dateMonth, $dateDay, $dateYear);
        } elseif ($viewBy == 'class') {
            if (isset($_GET['class'])) {
                $class = $_GET['class'];
            }
            $gibbonCourseClassID = $_GET['gibbonCourseClassID'];
        }

        //Get class variable
        $gibbonPlannerEntryID = $_GET['gibbonPlannerEntryID'];

        if ($gibbonPlannerEntryID == '') {
            echo "<div class='warning'>";
            echo __('The selected record does not exist, or you do not have access to it.');
            echo '</div>';
        }
        //Check existence of and access to this class.
        else {
            try {
                if ($highestAction == 'Lesson Planner_viewAllEditMyClasses') {
                    $data = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonPlannerEntryID' => $gibbonPlannerEntryID, 'date' => $date, 'gibbonPersonID2' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonPlannerEntryID2' => $gibbonPlannerEntryID);
                    $sql = "(SELECT gibbonPlannerEntry.gibbonPlannerEntryID, gibbonCourseClass.gibbonCourseClassID, gibbonUnitID, gibbonHookID, gibbonPlannerEntry.gibbonCourseClassID, gibbonPlannerEntry.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, date, timeStart, timeEnd, summary, gibbonPlannerEntry.description, teachersNotes, homework, homeworkDueDateTime, homeworkDetails, viewableStudents, viewableParents, role, homeworkSubmission, homeworkSubmissionDateOpen, homeworkSubmissionDrafts, homeworkSubmissionType FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left' AND gibbonPlannerEntry.gibbonPlannerEntryID=:gibbonPlannerEntryID) UNION (SELECT gibbonPlannerEntry.gibbonPlannerEntryID, gibbonCourseClass.gibbonCourseClassID, gibbonUnitID, gibbonHookID, gibbonPlannerEntry.gibbonCourseClassID, gibbonPlannerEntry.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, date, timeStart, timeEnd, summary, gibbonPlannerEntry.description, teachersNotes, homework, homeworkDueDateTime, homeworkDetails, viewableStudents, viewableParents, role, homeworkSubmission, homeworkSubmissionDateOpen, homeworkSubmissionDrafts, homeworkSubmissionType FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonPlannerEntryGuest ON (gibbonPlannerEntryGuest.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE date=:date AND gibbonPlannerEntryGuest.gibbonPersonID=:gibbonPersonID AND gibbonPlannerEntry.gibbonPlannerEntryID=:gibbonPlannerEntryID2) ORDER BY date, timeStart";
                } elseif ($highestAction == 'Lesson Planner_viewEditAllClasses') {
                    $data = array('gibbonPlannerEntryID' => $gibbonPlannerEntryID);
                    $sql = "SELECT gibbonPlannerEntry.gibbonPlannerEntryID, gibbonCourseClass.gibbonCourseClassID, gibbonUnitID, gibbonHookID, gibbonPlannerEntry.gibbonCourseClassID, gibbonPlannerEntry.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, date, timeStart, timeEnd, summary, gibbonPlannerEntry.description, teachersNotes, homework, homeworkDueDateTime, homeworkDetails, viewableStudents, viewableParents, 'Teacher' AS role, homeworkSubmission, homeworkSubmissionDateOpen, homeworkSubmissionDrafts, homeworkSubmissionType FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonPlannerEntry.gibbonPlannerEntryID=:gibbonPlannerEntryID ORDER BY date, timeStart";
                }
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            if ($result->rowCount() != 1) {
                echo "<div class='warning'>";
                echo __('The selected record does not exist, or you do not have access to it.');
                echo '</div>';
            } else {
                $row = $result->fetch();

                // target of the planner
                $target = ($viewBy === 'class') ? $row['course'].'.'.$row['class'] : dateConvertBack($guid, $date);

                // planner's parameters
                $params = [];
                if ($date != '') {
                    $params['date'] = $_GET['date'];
                }
                if ($viewBy != '') {
                    $params['viewBy'] = $_GET['viewBy'] ?? '';
                }
                if ($gibbonCourseClassID != '') {
                    $params['gibbonCourseClassID'] = $gibbonCourseClassID;
                }
                $params['subView'] = $subView;
                $paramsVar = '&' . http_build_query($params); // for backward compatibile uses below (should be get rid of)

                $page->breadcrumbs
                    ->add(__('Planner for {classDesc}', [
                        'classDesc' => $target,
                    ]), 'planner.php', $params)
                    ->add(__('View Lesson Plan'), 'planner_view_full.php', $params + ['gibbonPlannerEntryID' => $gibbonPlannerEntryID])
                    ->add(__('Add Comment'));

                if (isset($_GET['return'])) {
                    returnProcess($guid, $_GET['return'], null, null);
                }

                if ($_GET['submission'] != 'true' and $_GET['submission'] != 'false') {
                    echo "<div class='warning'>";
                    echo __('You have not specified one or more required parameters.');
                    echo '</div>';
                } else {
                    if ($_GET['submission'] == 'true') {
                        $submission = true;
                        $gibbonPlannerEntryHomeworkID = $_GET['gibbonPlannerEntryHomeworkID'];
                    } else {
                        $submission = false;
                        $gibbonPersonID = $_GET['gibbonPersonID'];
                    }

                    if (($submission == true and $gibbonPlannerEntryHomeworkID == '') or ($submission == false and $gibbonPersonID == '')) {
                        echo "<div class='warning'>";
                        echo __('You have not specified one or more required parameters.');
                        echo '</div>';
                    } else {
                        if ($submission == true) {
                            echo '<h2>';
                            echo __('Update Submission');
                            echo '</h2>';

                            try {
                                $dataSubmission = array('gibbonPlannerEntryHomeworkID' => $gibbonPlannerEntryHomeworkID);
                                $sqlSubmission = 'SELECT gibbonPlannerEntryHomework.*, surname, preferredName FROM gibbonPlannerEntryHomework JOIN gibbonPerson ON (gibbonPlannerEntryHomework.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPlannerEntryHomeworkID=:gibbonPlannerEntryHomeworkID';
                                $resultSubmission = $connection2->prepare($sqlSubmission);
                                $resultSubmission->execute($dataSubmission);
                            } catch (PDOException $e) {
                                echo "<div class='error'>".$e->getMessage().'</div>';
                            }

                            if ($resultSubmission->rowCount() != 1) {
                                echo "<div class='warning'>";
                                echo __('The selected record does not exist, or you do not have access to it.');
                                echo '</div>';
                            } else {
                                $rowSubmission = $resultSubmission->fetch()
                                ?>
								<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/planner_view_full_submit_editProcess.php' ?>">
									<table class='smallIntBorder fullWidth' cellspacing='0'>	
										<tr>
											<td style='width: 275px'> 
												<b><?php echo __('Student') ?> *</b><br/>
												<span class="emphasis small"><?php echo __('This value cannot be changed.') ?></span>
											</td>
											<td class="right">
												<input readonly name="courseName" id="courseName" maxlength=20 value="<?php echo formatName('', htmlPrep($rowSubmission['preferredName']), htmlPrep($rowSubmission['surname']), 'Student') ?>" type="text" class="standardWidth">
											</td>
										</tr>
										<tr>
											<td> 
												<b><?php echo __('Status') ?> *</b><br/>
											</td>
											<td class="right">
												<select class="standardWidth" name="status">
													<option <?php if ($rowSubmission['status'] == 'On Time') { echo 'selected '; } ?>value="On Time"><?php echo __('On Time') ?></option>
													<option <?php if ($rowSubmission['status'] == 'Late') { echo 'selected '; } ?>value="Late"><?php echo __('Late') ?></option>
												</select>
											</td>
										</tr>
										<tr>
											<td class="right" colspan=2>
												<?php
                                                echo "<input type='hidden' name='search' value='".$_GET['search']."'>";
												echo "<input type='hidden' name='params' value='$paramsVar'>";
												echo "<input type='hidden' name='gibbonPlannerEntryID' value='$gibbonPlannerEntryID'>";
												echo "<input type='hidden' name='submission' value='true'>";
												echo "<input type='hidden' name='gibbonPlannerEntryHomeworkID' value='$gibbonPlannerEntryHomeworkID'>";
												echo "<input type='hidden' name='address' value='".$_SESSION[$guid]['address']."'>";
												?>
												
												<input type="submit" value="<?php echo __('Submit'); ?>">
											</td>
										</tr>
									</table>
								</form>
							<?php

                            }
                        } else {
                            echo '<h2>';
                            echo __('Add Submission');
                            echo '</h2>';

                            try {
                                $dataSubmission = array('gibbonPersonID' => $gibbonPersonID);
                                $sqlSubmission = 'SELECT surname, preferredName FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID';
                                $resultSubmission = $connection2->prepare($sqlSubmission);
                                $resultSubmission->execute($dataSubmission);
                            } catch (PDOException $e) {
                                echo "<div class='error'>".$e->getMessage().'</div>';
                            }

                            if ($resultSubmission->rowCount() != 1) {
                                echo "<div class='warning'>";
                                echo 'There are no records to display.';
                                echo '</div>';
                            } else {
                                $rowSubmission = $resultSubmission->fetch()

                                ?>
								<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/planner_view_full_submit_editProcess.php' ?>" enctype="multipart/form-data">
									<table class='smallIntBorder fullWidth' cellspacing='0'>	
										<tr>
											<td style='width: 275px'> 
												<b><?php echo __('Student') ?> *</b><br/>
												<span class="emphasis small"><?php echo __('This value cannot be changed.') ?></span>
											</td>
											<td class="right">
												<input readonly name="courseName" id="courseName" maxlength=20 value="<?php echo formatName('', htmlPrep($rowSubmission['preferredName']), htmlPrep($rowSubmission['surname']), 'Student') ?>" type="text" class="standardWidth">
											</td>
										</tr>
										<tr>
											<td> 
												<b><?php echo __('Type') ?> *</b><br/>
											</td>
											<td class="right">
												<?php
                                                if ($row['homeworkSubmissionType'] == 'Link') {
                                                    ?>
													<input checked type="radio" id="type" name="type" class="type" value="Link" /> <?php echo __('Link') ?>
													<input type="radio" id="type" name="type" class="type" value="None" /> <?php echo __('None') ?>
													<?php

                                                } elseif ($row['homeworkSubmissionType'] == 'File') {
                                                    ?>
													<input checked type="radio" id="type" name="type" class="type" value="File" /> <?php echo __('File') ?>
													<input type="radio" id="type" name="type" class="type" value="None" /> <?php echo __('None') ?>
													<?php

                                                } else {
                                                    ?>
													<input type="radio" id="type" name="type" class="type" value="Link" /> <?php echo __('Link') ?>
													<input type="radio" id="type" name="type" class="type" value="File" /> <?php echo __('File') ?>
													<input checked type="radio" id="type" name="type" class="type" value="None" /> <?php echo __('None') ?>
													<?php

                                                }
                               		 		?>
											</td>
										</tr>
										<tr>
											<td> 
												<b><?php echo __('Version') ?> *</b><br/>
											</td>
											<td class="right">
												<?php
                                                echo "<select style='float: none; width: 302px' name='version'>";
												if ($row['homeworkSubmissionDrafts'] > 0 and $status != 'Late' and $resultVersion->rowCount() < $row['homeworkSubmissionDrafts']) {
													echo "<option value='Draft'>".__('Draft').'</option>';
												}
												echo "<option value='Final'>".__('Final').'</option>';
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
													$("#linkRow").css("display","none");
													<?php

                                                }
                               		 			?>
											
												$(".type").click(function(){
													if ($('input[name=type]:checked').val()=="Link" ) {
														$("#fileRow").css("display","none");
														$("#linkRow").slideDown("fast", $("#linkRow").css("display","table-row")); 
													} else if ($('input[name=type]:checked').val()=="File" ) {
														$("#linkRow").css("display","none");
														$("#fileRow").slideDown("fast", $("#fileRow").css("display","table-row")); 
													} else {
														$("#fileRow").css("display","none");
														$("#linkRow").css("display","none");
													}
												 });
											});
										</script>
									
										<tr id="fileRow">
											<td> 
												<b><?php echo __('Submit File') ?> *</b><br/>
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
													file.add( Validate.Inclusion, { within: [<?php echo $ext; ?>], failureMessage: "Illegal file type!", partialMatch: true, caseSensitive: false } );
												</script>
											</td>
										</tr>
										<tr id="linkRow">
											<td> 
												<b><?php echo __('Submit Link') ?> *</b><br/>
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
											<td> 
												<b><?php echo __('Status') ?> *</b><br/>
											</td>
											<td class="right">
												<select class="standardWidth" name="status">
													<option value="On Time"><?php echo __('On Time') ?></option>
													<option value="Late"><?php echo __('Late') ?></option>
													<option value="Exemption"><?php echo __('Exemption') ?></option>
												</select>
											</td>
										</tr>
									
										<tr>
											<td class="right" colspan=2>
                                                <?php

												$count = 0;
												try {
													$dataVersion = array('gibbonPersonID' => $gibbonPersonID, 'gibbonPlannerEntryID' => $gibbonPlannerEntryID);
													$sqlVersion = 'SELECT * FROM gibbonPlannerEntryHomework WHERE gibbonPersonID=:gibbonPersonID AND gibbonPlannerEntryID=:gibbonPlannerEntryID';
													$resultVersion = $connection2->prepare($sqlVersion);
													$resultVersion->execute($dataVersion);
												} catch (PDOException $e) {
													echo "<div class='error'>".$e->getMessage().'</div>';
												}

												if ($resultVersion->rowCount() < 1) {
													$count = $resultVersion->rowCount();
												}

												echo "<input type='hidden' name='count' value='$count'>";
												echo "<input type='hidden' name='lesson' value='".$row['name']."'>";
												echo "<input type='hidden' name='search' value='".$_GET['search']."'>";
												echo "<input type='hidden' name='params' value='$paramsVar'>";
												echo "<input type='hidden' name='gibbonPlannerEntryID' value='$gibbonPlannerEntryID'>";
												echo "<input type='hidden' name='submission' value='false'>";
												echo "<input type='hidden' name='gibbonPersonID' value='$gibbonPersonID'>";
												echo "<input type='hidden' name='address' value='".$_SESSION[$guid]['address']."'>";
												?>
											
												<input type="submit" value="<?php echo __('Submit'); ?>">
											</td>
										</tr>
									</table>
								</form>
								<?php

                            }
                        }
                    }
                }
            }
        }
    }
}
