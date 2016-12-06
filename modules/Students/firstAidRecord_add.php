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

$enableDescriptors = getSettingByScope($connection2, 'Behaviour', 'enableDescriptors');
$enableLevels = getSettingByScope($connection2, 'Behaviour', 'enableLevels');

if (isActionAccessible($guid, $connection2, '/modules/Students/firstAidRecord_add.php') == false) {
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
        echo "<div class='trail'>";
        echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Students/firstAidRecord.php'>".__($guid, 'Manage Behaviour Records')."</a> > </div><div class='trailEnd'>".__($guid, 'Add').'</div>';
        echo '</div>';

        $editLink = '';
        $editID = '';
        if (isset($_GET['editID'])) {
            $editLink = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Students/firstAidRecord_edit.php&gibbonBehaviourID='.$_GET['editID'].'&gibbonPersonID='.$_GET['gibbonPersonID'].'&gibbonRollGroupID='.$_GET['gibbonRollGroupID'].'&gibbonYearGroupID='.$_GET['gibbonYearGroupID'].'&type='.$_GET['type'];
            $editID = $_GET['editID'];
        }
        if (isset($_GET['return'])) {
            returnProcess($guid, $_GET['return'], $editLink, array('warning1' => 'Your request was successful, but some data was not properly saved.', 'success1' => 'Your request was completed successfully. You can now add extra information below if you wish.'));
        }

        $step = null;
        if (isset($_GET['step'])) {
            $step = $_GET['step'];
        }
        if ($step != 1 and $step != 2) {
            $step = 1;
        }
        $gibbonBehaviourID = null;
        if (isset($_GET['gibbonBehaviourID'])) {
            $gibbonBehaviourID = $_GET['gibbonBehaviourID'];
        }

        //Step 1
        if ($step == 1 or $gibbonBehaviourID == null) {
            echo "<div class='linkTop'>";
            $policyLink = getSettingByScope($connection2, 'Behaviour', 'policyLink');
            if ($policyLink != '') {
                echo "<a target='_blank' href='$policyLink'>".__($guid, 'View Behaviour Policy').'</a>';
            }
            if ($_GET['gibbonPersonID'] != '' or $_GET['gibbonRollGroupID'] != '' or $_GET['gibbonYearGroupID'] != '' or $_GET['type'] != '') {
                if ($policyLink != '') {
                    echo ' | ';
                }
                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Students/firstAidRecord.php&gibbonPersonID='.$_GET['gibbonPersonID'].'&gibbonRollGroupID='.$_GET['gibbonRollGroupID'].'&gibbonYearGroupID='.$_GET['gibbonYearGroupID'].'&type='.$_GET['type']."'>".__($guid, 'Back to Search Results').'</a>';
            }
            echo '</div>'; ?>

			<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/firstAidRecord_addProcess.php?step=1&gibbonPersonID='.$_GET['gibbonPersonID'].'&gibbonRollGroupID='.$_GET['gibbonRollGroupID'].'&gibbonYearGroupID='.$_GET['gibbonYearGroupID'].'&type='.$_GET['type'] ?>">
				<table class='smallIntBorder fullWidth' cellspacing='0'>
					<tr class='break'>
						<td colspan=2>
							<h3><?php echo __($guid, 'Step 1') ?></h3>
						</td>
					</tr>
					<tr>
						<td style='width: 275px'>
							<b><?php echo __($guid, 'Patient') ?> *</b><br/>
							<span class="emphasis small"></span>
						</td>
						<td class="right">
							<?php
                                $gibbonPersonID = null;
								if (isset($_GET['gibbonPersonID'])) {
									$gibbonPersonID = $_GET['gibbonPersonID'];
								}
								?>
							<select name="gibbonPersonID" id="gibbonPersonID2" class="standardWidth">
								<option value="Please select..."><?php echo __($guid, 'Please select...') ?></option>
								<?php
                                try {
                                    $dataSelect = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                                    $sqlSelect = "SELECT * FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID AND status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') ORDER BY surname, preferredName";
                                    $resultSelect = $connection2->prepare($sqlSelect);
                                    $resultSelect->execute($dataSelect);
                                } catch (PDOException $e) {
                                    echo "<div class='error'>".$e->getMessage().'</div>';
                                }
								while ($rowSelect = $resultSelect->fetch()) {
									if ($gibbonPersonID == $rowSelect['gibbonPersonID']) {
										echo "<option selected value='".$rowSelect['gibbonPersonID']."'>".formatName('', htmlPrep($rowSelect['preferredName']), htmlPrep($rowSelect['surname']), 'Student', true).' ('.htmlPrep($rowSelect['nameShort']).')</option>';
									} else {
										echo "<option value='".$rowSelect['gibbonPersonID']."'>".formatName('', htmlPrep($rowSelect['preferredName']), htmlPrep($rowSelect['surname']), 'Student', true).' ('.htmlPrep($rowSelect['nameShort']).')</option>';
									}
								}
								?>
							</select>
							<script type="text/javascript">
								var gibbonPersonID2=new LiveValidation('gibbonPersonID2');
								gibbonPersonID2.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
							</script>
						</td>
					</tr>
					<tr>
						<td>
							<b><?php echo __($guid, 'Date') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Format:') ?> <?php if ($_SESSION[$guid]['i18n']['dateFormat'] == '') {
								echo 'dd/mm/yyyy';
							} else {
								echo $_SESSION[$guid]['i18n']['dateFormat'];
							}
           					?></span>
						</td>
						<td class="right">
							<input name="date" id="date" maxlength=10 value="<?php echo date($_SESSION[$guid]['i18n']['dateFormatPHP']) ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var date=new LiveValidation('date');
								date.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]['i18n']['dateFormatRegEx'] == '') {
								echo "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i";
								} else {
									echo $_SESSION[$guid]['i18n']['dateFormatRegEx'];
								}
											?>, failureMessage: "Use <?php if ($_SESSION[$guid]['i18n']['dateFormat'] == '') {
									echo 'dd/mm/yyyy';
								} else {
									echo $_SESSION[$guid]['i18n']['dateFormat'];
								}
								?>." } );
							</script>
							 <script type="text/javascript">
								$(function() {
									$( "#date" ).datepicker();
								});
							</script>
						</td>
					</tr>
					  <?php
                  if ($enableDescriptors == 'Y') {
                      try {
                          $sqlPositive = "SELECT * FROM gibbonSetting WHERE scope='Behaviour' AND name='positiveDescriptors'";
                          $resultPositive = $connection2->query($sqlPositive);
                          $sqlNegative = "SELECT * FROM gibbonSetting WHERE scope='Behaviour' AND name='negativeDescriptors'";
                          $resultNegative = $connection2->query($sqlNegative);
                        } catch (PDOException $e) {
                        }

                        if ($resultPositive->rowCount() == 1 and $resultNegative->rowCount() == 1) {
                          $rowPositive = $resultPositive->fetch();
                          $rowNegative = $resultNegative->fetch();

                          $optionsPositive = $rowPositive['value'];
                          $optionsNegative = $rowNegative['value'];

                          if ($optionsPositive != '' and $optionsNegative != '') {
                              $optionsPositive = explode(',', $optionsPositive);
                              $optionsNegative = explode(',', $optionsNegative);
                              ?>
								<?php

                            }
                        }
                    }

            if ($enableLevels == 'Y') {
                $optionsLevels = getSettingByScope($connection2, 'Behaviour', 'levels');
                if ($optionsLevels != '') {
                    $optionsLevels = explode(',', $optionsLevels); ?>
                    
							<?php

							}
						}
						?>
					<script type='text/javascript'>
						$(document).ready(function(){
							autosize($('textarea'));
						});
					</script>

					<tr>
						<td colspan=2>
							<b><?php echo __($guid, 'Action Taken') ?></b><br/>
							<textarea name="comment" id="comment" rows=8 style="width: 100%"></textarea>
						</td>
					</tr>
					<tr>
						<td colspan=2>
							<b><?php echo __($guid, 'Follow Up') ?></b><br/>
							<textarea name="followup" id="followup" rows=8 style="width: 100%"></textarea>
						</td>
					</tr>
					<tr>
						<td>
							<span class="emphasis small">* <?php echo __($guid, 'denotes a required field'); ?></span>
						</td>
						<td class="right">
							<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
							<input type="submit" value="<?php echo __($guid, 'Submit') ?>">
						</td>
					</tr>
				</table>
			</form>
			<?php

        } elseif ($step == 2 and $gibbonBehaviourID != null) {
            if ($gibbonBehaviourID == '') {
                echo "<div class='error'>";
                echo __($guid, 'You have not specified one or more required parameters.');
                echo '</div>';
            } else {
                //Check for existence of behaviour record
                try {
                    $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonBehaviourID' => $gibbonBehaviourID);
                    $sql = "SELECT * FROM gibbonBehaviour JOIN gibbonPerson ON (gibbonBehaviour.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID AND status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonBehaviourID=:gibbonBehaviourID";
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
                    $row = $result->fetch();

                    ?>
					<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/firstAidRecord_addProcess.php?step=2&gibbonPersonID='.$_GET['gibbonPersonID'].'&gibbonRollGroupID='.$_GET['gibbonRollGroupID'].'&gibbonYearGroupID='.$_GET['gibbonYearGroupID'].'&type='.$_GET['type'].'&editID='.$editID ?>">
						<table class='smallIntBorder fullWidth' cellspacing='0'>
							<tr class='break'>
								<td colspan=2>
									<h3><?php echo __($guid, 'Step 2 (Optional)') ?></h3>
								</td>
							</tr>
							<tr>
								<td>
									<b><?php echo __($guid, 'Student') ?> *</b><br/>
									<span class="emphasis small"><?php echo __($guid, 'This value cannot be changed.') ?></span>
								</td>
								<td class="right">
									<input type="hidden" name="gibbonPersonID" value="<?php echo $row['gibbonPersonID'] ?>">
									<input readonly name="name" id="name" value="<?php echo formatName('', $row['preferredName'], $row['surname'], 'Student') ?>" type="text" class="standardWidth">
								</td>
							</tr>
							<tr>
								<td>
									<b><?php echo __($guid, 'Link To Lesson?') ?></b><br/>
									<span class="emphasis small"><?php echo __($guid, 'From last 30 days') ?></span>
								</td>
								<td class="right">
									<select name="gibbonPlannerEntryID" id="gibbonPlannerEntryID" class="standardWidth">
										<option value=""></option>
										<?php
                                        $minDate = date('Y-m-d', (time() - (24 * 60 * 60 * 30)));
										try {
											$dataSelect = array('date1' => date('Y-m-d', time()), 'date2' => $minDate, 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $row['gibbonPersonID']);
											$sqlSelect = "SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID, gibbonCourseClass.gibbonCourseClassID, gibbonPlannerEntry.name AS lesson, gibbonPlannerEntryID, date, homework, homeworkSubmission FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonPlannerEntry ON (gibbonCourseClass.gibbonCourseClassID=gibbonPlannerEntry.gibbonCourseClassID) WHERE (date<=:date1 AND date>=:date2) AND gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND role='Student' ORDER BY course, class, date, timeStart";
											$resultSelect = $connection2->prepare($sqlSelect);
											$resultSelect->execute($dataSelect);
										} catch (PDOException $e) {
										}
										while ($rowSelect = $resultSelect->fetch()) {
											$show = true;
											if ($highestAction == 'Manage Behaviour Records_my') {
												try {
													$dataShow = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonCourseClassID' => $rowSelect['gibbonCourseClassID']);
													$sqlShow = "SELECT * FROM gibbonCourseClassPerson WHERE gibbonPersonID=:gibbonPersonID AND gibbonCourseClassID=:gibbonCourseClassID AND role='Teacher'";
													$resultShow = $connection2->prepare($sqlShow);
													$resultShow->execute($dataShow);
												} catch (PDOException $e) {
													echo "<div class='error'>".$e->getMessage().'</div>';
												}
												if ($resultShow->rowCount() != 1) {
													$show = false;
												}
											}
											if ($show == true) {
												$submission = '';
												if ($rowSelect['homework'] == 'Y') {
													$submission = 'HW';
													if ($rowSelect['homeworkSubmission'] == 'Y') {
														$submission .= '+OS';
													}
												}
												if ($submission != '') {
													$submission = ' - '.$submission;
												}
												echo "<option value='".$rowSelect['gibbonPlannerEntryID']."'>".htmlPrep($rowSelect['course']).'.'.htmlPrep($rowSelect['class']).' '.htmlPrep($rowSelect['lesson']).' - '.substr(dateConvertBack($guid, $rowSelect['date']), 0, 5)."$submission</option>";
											}
										}
										?>
									</select>
								</td>
							</tr>

							<tr>
								<td>
									<span class="emphasis small">* <?php echo __($guid, 'denotes a required field'); ?></span>
								</td>
								<td class="right">
									<input type="hidden" name="gibbonBehaviourID" value="<?php echo $gibbonBehaviourID ?>">
									<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
									<input type="submit" value="<?php echo __($guid, 'Submit'); ?>">
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
?>
