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

if (isActionAccessible($guid, $connection2, '/modules/Students/firstAidRecord_edit.php') == false) {
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
        //Proceed!
        echo "<div class='trail'>";
        echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Students/firstAidRecord.php'>".__($guid, 'Manage Behaviour Records')."</a> > </div><div class='trailEnd'>".__($guid, 'Edit').'</div>';
        echo '</div>';

        if (isset($_GET['return'])) {
            returnProcess($guid, $_GET['return'], null, null);
        }

        //Check if school year is specified
        $gibbonBehaviourID = $_GET['gibbonBehaviourID'];
        if ($gibbonBehaviourID == 'Y') {
            echo "<div class='error'>";
            echo __($guid, 'You have not specified one or more required parameters.');
            echo '</div>';
        } else {
            try {
                if ($highestAction == 'Manage Behaviour Records_all') {
                    $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonBehaviourID' => $gibbonBehaviourID);
                    $sql = 'SELECT gibbonBehaviour.*, student.surname AS surnameStudent, student.preferredName AS preferredNameStudent, creator.surname AS surnameCreator, creator.preferredName AS preferredNameCreator, creator.title FROM gibbonBehaviour JOIN gibbonPerson AS student ON (gibbonBehaviour.gibbonPersonID=student.gibbonPersonID) JOIN gibbonPerson AS creator ON (gibbonBehaviour.gibbonPersonIDCreator=creator.gibbonPersonID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonBehaviourID=:gibbonBehaviourID ORDER BY date DESC';
                } elseif ($highestAction == 'Manage Behaviour Records_my') {
                    $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonBehaviourID' => $gibbonBehaviourID, 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                    $sql = 'SELECT gibbonBehaviour.*, student.surname AS surnameStudent, student.preferredName AS preferredNameStudent, creator.surname AS surnameCreator, creator.preferredName AS preferredNameCreator, creator.title FROM gibbonBehaviour JOIN gibbonPerson AS student ON (gibbonBehaviour.gibbonPersonID=student.gibbonPersonID) JOIN gibbonPerson AS creator ON (gibbonBehaviour.gibbonPersonIDCreator=creator.gibbonPersonID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonBehaviourID=:gibbonBehaviourID AND gibbonPersonIDCreator=:gibbonPersonID ORDER BY date DESC';
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
                echo '</div>';

                //Let's go!
                $row = $result->fetch();
                ?>
				<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/firstAidRecord_editProcess.php?gibbonBehaviourID=$gibbonBehaviourID&gibbonPersonID=".$_GET['gibbonPersonID'].'&gibbonRollGroupID='.$_GET['gibbonRollGroupID'].'&gibbonYearGroupID='.$_GET['gibbonYearGroupID'].'&type='.$_GET['type'] ?>">
					<table class='smallIntBorder fullWidth' cellspacing='0'>
						<tr>
							<td style='width: 275px'>
								<b><?php echo __($guid, 'Student') ?> *</b><br/>
								<span class="emphasis small"><?php echo __($guid, 'This value cannot be changed.') ?></span>
							</td>
							<td class="right">
								<?php
                                try {
                                    $dataSelect = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $row['gibbonPersonID']);
                                    $sqlSelect = "SELECT * FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID AND status='Full' AND gibbonPerson.gibbonPersonID=:gibbonPersonID ORDER BY surname, preferredName";
                                    $resultSelect = $connection2->prepare($sqlSelect);
                                    $resultSelect->execute($dataSelect);
                                } catch (PDOException $e) {
                                }
								if ($resultSelect->rowCount() == 1) {
									$rowSelect = $resultSelect->fetch();
								}

								?>
								<input type="hidden" name="gibbonPersonID" value="<?php echo $row['gibbonPersonID'] ?>">
								<input readonly name="name" id="name" value="<?php echo formatName('', $rowSelect['preferredName'], $rowSelect['surname'], 'Student') ?>" type="text" class="standardWidth">
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
								<input readonly name="date" id="date" maxlength=10 value="<?php echo dateConvertBack($guid, $row['date']) ?>" type="text" class="standardWidth">
							</td>
						</tr>

						<tr>
							<td>
								<b><?php echo __($guid, 'Type') ?> *</b><br/>
								<span class="emphasis small"><?php echo __($guid, 'This value cannot be changed.') ?></span>
							</td>
							<td class="right">
								<input name="type" id="type" readonly="readonly" maxlength=20 value="<?php echo __($guid, $row['type']) ?>" type="text" class="standardWidth">
							</td>
						</tr>

						<?php
                        if ($enableDescriptors == 'Y') {
                            $options = array();
                            if ($row['type'] == 'Positive') { //Show positive descriptors
                                try {
                                    $sqlPositive = "SELECT * FROM gibbonSetting WHERE scope='Behaviour' AND name='positiveDescriptors'";
                                    $resultPositive = $connection2->query($sqlPositive);
                                } catch (PDOException $e) {
                                }

                                if ($resultPositive->rowCount() == 1) {
                                    $rowPositive = $resultPositive->fetch();
                                    $optionsPositive = $rowPositive['value'];
                                    if ($optionsPositive != '') {
                                        $options = explode(',', $optionsPositive);
                                    }
                                }
                            } elseif ($row['type'] == 'Negative') { //Show negative descriptors
                                try {
                                    $sqlNegative = "SELECT * FROM gibbonSetting WHERE scope='Behaviour' AND name='negativeDescriptors'";
                                    $resultNegative = $connection2->query($sqlNegative);
                                } catch (PDOException $e) {
                                }

                                if ($resultNegative->rowCount() == 1) {
                                    $rowNegative = $resultNegative->fetch();
                                    $optionsNegative = $rowNegative['value'];
                                    if ($optionsNegative != '') {
                                        $options = explode(',', $optionsNegative);
                                    }
                                }
                            }

                            if (count($options) > 0) {
                                ?>
								<tr>
									<td>
										<b><?php echo __($guid, 'Descriptor') ?> *</b><br/>
										<span class="emphasis small"></span>
									</td>
									<td class="right">
										<select name="descriptor" id="descriptor" class="standardWidth">
											<option value="Please select..."><?php echo __($guid, 'Please select...') ?></option>
											<?php
                                            for ($i = 0; $i < count($options); ++$i) {
                                                $selected = '';
                                                if ($row['descriptor'] == $options[$i]) {
                                                    $selected = 'selected';
                                                }
                                                ?>
												<option <?php echo $selected ?> <?php if ($row['descriptor'] == $options[$i]) { echo 'selected '; }
                                                ?>value="<?php echo trim($options[$i]) ?>"><?php echo trim($options[$i]) ?></option>
												<?php

                                            }
											?>
										</select>
										<script type="text/javascript">
											var descriptor=new LiveValidation('descriptor');
											descriptor.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
										</script>
									</td>
								</tr>
								<?php
                            }
                        }
               			 ?>

						<?php
                        if ($enableLevels == 'Y') {
                            try {
                                $dataLevels = array();
                                $sqlLevels = "SELECT * FROM gibbonSetting WHERE scope='Behaviour' AND name='Levels'";
                                $resultLevels = $connection2->prepare($sqlLevels);
                                $resultLevels->execute($dataLevels);
                            } catch (PDOException $e) {
                            }
                            if ($resultLevels->rowCount() == 1) {
                                $rowLevels = $resultLevels->fetch();
                                $optionsLevels = $rowLevels['value'];

                                if ($optionsLevels != '') {
                                    $optionsLevels = explode(',', $optionsLevels);
                                    ?>
									<tr>
										<td>
											<b><?php echo __($guid, 'Level') ?> *</b><br/>
											<span class="emphasis small"></span>
										</td>
										<td class="right">
											<select name="level" id="level" class="standardWidth">
												<option value="Please select..."><?php echo __($guid, 'Please select...') ?></option>
												<?php
                                                for ($i = 0; $i < count($optionsLevels); ++$i) {
                                                    $selected = '';
                                                    if ($row['level'] == $optionsLevels[$i]) {
                                                        $selected = 'selected';
                                                    }
                                                    ?>
													<option <?php echo $selected ?> value="<?php echo trim($optionsLevels[$i]) ?>"><?php echo trim($optionsLevels[$i]) ?></option>
												<?php

                                                }
                                    		?>
											</select>
											<script type="text/javascript">
												var level=new LiveValidation('level');
												level.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
											</script>
										</td>
									</tr>
									<?php

                                }
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
								<b><?php echo __($guid, 'Incident') ?></b><br/>
								<textarea name="comment" id="comment" rows=8 style="width: 100%"><?php echo htmlPrep($row['comment']) ?></textarea>
							</td>
						</tr>
						<tr>
							<td colspan=2>
								<b><?php echo __($guid, 'Follow Up') ?></b><br/>
								<textarea name="followup" id="followup" rows=8 style="width: 100%"><?php echo htmlPrep($row['followup']) ?></textarea>
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
                                    $minDate = date('Y-m-d', (strtotime($row['date']) - (24 * 60 * 60 * 30)));

									try {
										$dataSelect = array('date' => date('Y-m-d', strtotime($row['date'])), 'minDate' => $minDate, 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $row['gibbonPersonID']);
										$sqlSelect = "SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID, gibbonCourseClass.gibbonCourseClassID, gibbonPlannerEntry.name AS lesson, gibbonPlannerEntryID, date, homework, homeworkSubmission FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonPlannerEntry ON (gibbonCourseClass.gibbonCourseClassID=gibbonPlannerEntry.gibbonCourseClassID) WHERE (date<=:date AND date>=:minDate) AND gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND role='Student' ORDER BY course, class, date, timeStart";
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
											$selected = '';
											if ($rowSelect['gibbonPlannerEntryID'] == $row['gibbonPlannerEntryID']) {
												$selected = 'selected';
											}
											echo "<option $selected value='".$rowSelect['gibbonPlannerEntryID']."'>".htmlPrep($rowSelect['course']).'.'.htmlPrep($rowSelect['class']).' - '.htmlPrep($rowSelect['lesson'])."$submission</option>";
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
?>
