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

if (isActionAccessible($guid, $connection2, '/modules/Data Updater/data_medical.php') == false) {
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
        echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Update Medical Data').'</div>';
        echo '</div>';

        if ($highestAction == 'Update Medical Data_any') {
            echo '<p>';
            echo __($guid, 'This page allows a user to request selected medical data updates for any student.');
            echo '</p>';
        } else {
            echo '<p>';
            echo __($guid, 'This page allows any adult with data access permission to request medical data updates for any member of their family.');
            echo '</p>';
        }

        $customResponces = array();

        $success0 = __($guid, 'Your request was completed successfully. An administrator will process your request as soon as possible. You will not see the updated data in the system until it has been processed and approved.');
        if ($_SESSION[$guid]['organisationDBAEmail'] != '' and $_SESSION[$guid]['organisationDBAName'] != '') {
            $success0 .= ' '.sprintf(__($guid, 'Please contact %1$s if you have any questions.'), "<a href='mailto:".$_SESSION[$guid]['organisationDBAEmail']."'>".$_SESSION[$guid]['organisationDBAName'].'</a>');
        }
        $customResponces['success0'] = $success0;

        if (isset($_GET['return'])) {
            returnProcess($guid, $_GET['return'], null, $customResponces);
        }

        echo '<h2>';
        echo 'Choose User';
        echo '</h2>';

        $gibbonPersonID = null;
        if (isset($_GET['gibbonPersonID'])) {
            $gibbonPersonID = $_GET['gibbonPersonID'];
        }
        ?>

		<form method="get" action="<?php echo $_SESSION[$guid]['absoluteURL']?>/index.php">
			<table class='smallIntBorder fullWidth' cellspacing='0'>
				<tr>
					<td style='width: 275px'>
						<b><?php echo __($guid, 'Person') ?> *</b><br/>
					</td>
					<td class="right">
						<select class="standardWidth" name="gibbonPersonID">
							<?php
                            if ($highestAction == 'Update Medical Data_any') {
                                try {
                                    $dataSelect = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                                    $sqlSelect = "SELECT username, surname, preferredName, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND status='Full' ORDER BY surname, preferredName";
                                    $resultSelect = $connection2->prepare($sqlSelect);
                                    $resultSelect->execute($dataSelect);
                                } catch (PDOException $e) {
                                }
                                echo "<option value=''></option>";
                                while ($rowSelect = $resultSelect->fetch()) {
                                    if ($gibbonPersonID == $rowSelect['gibbonPersonID']) {
                                        echo "<option selected value='".$rowSelect['gibbonPersonID']."'>".formatName('', htmlPrep($rowSelect['preferredName']), htmlPrep($rowSelect['surname']), 'Student', true).' ('.$rowSelect['username'].')</option>';
                                    } else {
                                        echo "<option value='".$rowSelect['gibbonPersonID']."'>".formatName('', htmlPrep($rowSelect['preferredName']), htmlPrep($rowSelect['surname']), 'Student', true).' ('.$rowSelect['username'].')</option>';
                                    }
                                }
                            } else {
                                try {
                                    $dataSelect = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                                    $sqlSelect = "SELECT gibbonFamilyAdult.gibbonFamilyID, name FROM gibbonFamilyAdult JOIN gibbonFamily ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) WHERE gibbonPersonID=:gibbonPersonID AND childDataAccess='Y' ORDER BY name";
                                    $resultSelect = $connection2->prepare($sqlSelect);
                                    $resultSelect->execute($dataSelect);
                                } catch (PDOException $e) {
                                }
                                echo "<option value=''></option>";
                                while ($rowSelect = $resultSelect->fetch()) {
                                    try {
                                        $dataSelect2 = array('gibbonFamilyID' => $rowSelect['gibbonFamilyID']);
                                        $sqlSelect2 = "SELECT surname, preferredName, gibbonPerson.gibbonPersonID, gibbonFamilyID FROM gibbonFamilyChild JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Full' AND gibbonFamilyID=:gibbonFamilyID";
                                        $resultSelect2 = $connection2->prepare($sqlSelect2);
                                        $resultSelect2->execute($dataSelect2);
                                    } catch (PDOException $e) {
                                    }
                                    while ($rowSelect2 = $resultSelect2->fetch()) {
                                        if ($gibbonPersonID == $rowSelect2['gibbonPersonID']) {
                                            echo "<option selected value='".$rowSelect2['gibbonPersonID']."'>".formatName('', htmlPrep($rowSelect2['preferredName']), htmlPrep($rowSelect2['surname']), 'Student', true).'</option>';
                                        } else {
                                            echo "<option value='".$rowSelect2['gibbonPersonID']."'>".formatName('', htmlPrep($rowSelect2['preferredName']), htmlPrep($rowSelect2['surname']), 'Student', true).'</option>';
                                        }
                                    }
                                }
                            }
        					?>
						</select>
					</td>
				</tr>
				<tr>
					<td colspan=2 class="right">
						<input type="hidden" name="q" value="/modules/<?php echo $_SESSION[$guid]['module'] ?>/data_medical.php">
						<input type="submit" value="<?php echo __($guid, 'Submit'); ?>">
					</td>
				</tr>
			</table>
		</form>
		<?php

        if ($gibbonPersonID != '') {
            echo '<h2>';
            echo __($guid, 'Update Data');
            echo '</h2>';

            //Check access to person
            $checkCount = 0;
            if ($highestAction == 'Update Medical Data_any') {
                try {
                    $dataSelect = array();
                    $sqlSelect = "SELECT surname, preferredName, gibbonPerson.gibbonPersonID FROM gibbonPerson WHERE status='Full' ORDER BY surname, preferredName";
                    $resultSelect = $connection2->prepare($sqlSelect);
                    $resultSelect->execute($dataSelect);
                } catch (PDOException $e) {
                }
                $checkCount = $resultSelect->rowCount();
            } else {
                try {
                    $dataCheck = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                    $sqlCheck = "SELECT gibbonFamilyAdult.gibbonFamilyID, name FROM gibbonFamilyAdult JOIN gibbonFamily ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) WHERE gibbonPersonID=:gibbonPersonID AND childDataAccess='Y' ORDER BY name";
                    $resultCheck = $connection2->prepare($sqlCheck);
                    $resultCheck->execute($dataCheck);
                } catch (PDOException $e) {
                }
                while ($rowCheck = $resultCheck->fetch()) {
                    try {
                        $dataCheck2 = array('gibbonFamilyID' => $rowCheck['gibbonFamilyID'], 'gibbonFamilyID2' => $rowCheck['gibbonFamilyID']);
                        $sqlCheck2 = '(SELECT surname, preferredName, gibbonPerson.gibbonPersonID, gibbonFamilyID FROM gibbonFamilyChild JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonFamilyID=:gibbonFamilyID) UNION (SELECT surname, preferredName, gibbonPerson.gibbonPersonID, gibbonFamilyID FROM gibbonFamilyAdult JOIN gibbonPerson ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonFamilyID=:gibbonFamilyID2)';
                        $resultCheck2 = $connection2->prepare($sqlCheck2);
                        $resultCheck2->execute($dataCheck2);
                    } catch (PDOException $e) {
                    }
                    while ($rowCheck2 = $resultCheck2->fetch()) {
                        if ($gibbonPersonID == $rowCheck2['gibbonPersonID']) {
                            ++$checkCount;
                        }
                    }
                }
            }
            if ($checkCount < 1) {
                echo "<div class='error'>";
                echo __($guid, 'The selected record does not exist, or you do not have access to it.');
                echo '</div>';
            } else {
                //Get user's data
                try {
                    $data = array('gibbonPersonID' => $gibbonPersonID);
                    $sql = 'SELECT * FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID';
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
                    //Check if there is already a pending form for this user
                    $existing = false;
                    $proceed = false;
                    try {
                        $dataForm = array('gibbonPersonID' => $gibbonPersonID, 'gibbonPersonID2' => $_SESSION[$guid]['gibbonPersonID']);
                        $sqlForm = "SELECT * FROM gibbonPersonMedicalUpdate WHERE gibbonPersonID=:gibbonPersonID AND gibbonPersonIDUpdater=:gibbonPersonID2 AND status='Pending'";
                        $resultForm = $connection2->prepare($sqlForm);
                        $resultForm->execute($dataForm);
                    } catch (PDOException $e) {
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }
                    if ($result->rowCount() > 1) {
                        echo "<div class='error'>";
                        echo __($guid, 'Your request failed due to a database error.');
                        echo '</div>';
                    } elseif ($result->rowCount() == 1) {
                        $existing = true;
                        echo "<div class='warning'>";
                        echo __($guid, 'You have already submitted a form, which is pending approval by an administrator. If you wish to make changes, please edited the data below, but remember your data will not appear in the system until it has been approved.');
                        echo '</div>';
                        $proceed = true;
                    } else {
                        //Get user's data
                        try {
                            $dataForm = array('gibbonPersonID' => $gibbonPersonID);
                            $sqlForm = 'SELECT * FROM gibbonPersonMedical WHERE gibbonPersonID=:gibbonPersonID';
                            $resultForm = $connection2->prepare($sqlForm);
                            $resultForm->execute($dataForm);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }

                        if ($result->rowCount() == 1) {
                            $proceed = true;
                        }
                    }

                    if ($proceed == true) {
                        $rowForm = $resultForm->fetch();
                        ?>
						<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/data_medicalProcess.php?gibbonPersonID='.$gibbonPersonID ?>">
							<table class='smallIntBorder fullWidth' cellspacing='0'>
								<tr>
									<td style='width: 275px'>
										<b><?php echo __($guid, 'Blood Type') ?></b><br/>
										<span class="emphasis small"></span>
									</td>
									<td class="right">
										<select class="standardWidth" name="bloodType">
											<option <?php if ($rowForm['bloodType'] == '') { echo 'selected '; } ?>value=""></option>
											<option <?php if ($rowForm['bloodType'] == 'O+') { echo 'selected '; } ?>value="O+">O+</option>
											<option <?php if ($rowForm['bloodType'] == 'A+') { echo 'selected '; } ?>value="A+">A+</option>
											<option <?php if ($rowForm['bloodType'] == 'B+') { echo 'selected '; } ?>value="B+">B+</option>
											<option <?php if ($rowForm['bloodType'] == 'AB+') { echo 'selected '; } ?>value="AB+">AB+</option>
											<option <?php if ($rowForm['bloodType'] == 'O-') { echo 'selected '; } ?>value="O-">O-</option>
											<option <?php if ($rowForm['bloodType'] == 'A-') { echo 'selected '; } ?>value="A-">A-</option>
											<option <?php if ($rowForm['bloodType'] == 'B-') { echo 'selected '; } ?>value="B-">B-</option>
											<option <?php if ($rowForm['bloodType'] == 'AB-') { echo 'selected '; } ?>value="AB-">AB-</option>
										</select>
									</td>
								</tr>
								<tr>
									<td>
										<b><?php echo __($guid, 'Long-Term Medication?') ?></b><br/>
										<span class="emphasis small"></span>
									</td>
									<td class="right">
										<select class="standardWidth" name="longTermMedication">
											<option <?php if ($rowForm['longTermMedication'] == '') { echo 'selected '; } ?>value=""></option>
											<option <?php if ($rowForm['longTermMedication'] == 'Y') { echo 'selected '; } ?>value="Y"><?php echo __($guid, 'Yes') ?></option>
											<option <?php if ($rowForm['longTermMedication'] == 'N') { echo 'selected '; } ?>value="N"><?php echo __($guid, 'No') ?></option>
										</select>
									</td>
								</tr>
								<tr>
									<td>
										<b><?php echo __($guid, 'Medication Details') ?></b><br/>
									</td>
									<td class="right">
										<textarea name="longTermMedicationDetails" id="longTermMedicationDetails" rowForms=8 class="standardWidth"><?php echo $rowForm['longTermMedicationDetails'] ?></textarea>
									</td>
								</tr>
								<tr>
									<td>
										<b><?php echo __($guid, 'Tetanus Within Last 10 Years?') ?></b><br/>
										<span class="emphasis small"></span>
									</td>
									<td class="right">
										<select class="standardWidth" name="tetanusWithin10Years">
											<option <?php if ($rowForm['tetanusWithin10Years'] == '') { echo 'selected '; } ?>value=""></option>
											<option <?php if ($rowForm['tetanusWithin10Years'] == 'Y') { echo 'selected '; } ?>value="Y"><?php echo __($guid, 'Yes') ?></option>
											<option <?php if ($rowForm['tetanusWithin10Years'] == 'N') { echo 'selected '; } ?>value="N"><?php echo __($guid, 'No') ?></option>
										</select>
									</td>
								</tr>

								<input name="gibbonPersonMedicalID" id="gibbonPersonMedicalID" value="<?php echo htmlPrep($rowForm['gibbonPersonMedicalID']) ?>" type="hidden">

								<?php
                                $count = 0;
                        if ($rowForm['gibbonPersonMedicalID'] != '' or $existing == true) {
                            try {
                                if ($existing == true) {
                                    $dataCond = array('gibbonPersonMedicalUpdateID' => $rowForm['gibbonPersonMedicalUpdateID']);
                                    $sqlCond = 'SELECT * FROM gibbonPersonMedicalConditionUpdate WHERE gibbonPersonMedicalUpdateID=:gibbonPersonMedicalUpdateID ORDER BY name';
                                } else {
                                    $dataCond = array('gibbonPersonMedicalID' => $rowForm['gibbonPersonMedicalID']);
                                    $sqlCond = 'SELECT * FROM gibbonPersonMedicalCondition WHERE gibbonPersonMedicalID=:gibbonPersonMedicalID ORDER BY name';
                                }
                                $resultCond = $connection2->prepare($sqlCond);
                                $resultCond->execute($dataCond);
                            } catch (PDOException $e) {
                                echo "<div class='error'>".$e->getMessage().'</div>';
                            }

                            while ($rowCond = $resultCond->fetch()) {
                                ?>
								<tr class='break'>
									<td colspan=2>
										<h3><?php echo __($guid, 'Medical Condition') ?> <?php echo $count + 1 ?></h3>
									</td>
								</tr>
								<tr>
									<td>
										<b><?php echo __($guid, 'Condition Name') ?> *</b><br/>
									</td>
									<td class="right">
										<select class="standardWidth" name="name<?php echo $count ?>" id="name<?php echo $count ?>">
											<?php
											try {
												$dataSelect = array();
												$sqlSelect = 'SELECT * FROM gibbonMedicalCondition ORDER BY name';
												$resultSelect = $connection2->prepare($sqlSelect);
												$resultSelect->execute($dataSelect);
											} catch (PDOException $e) {
											}
											echo "<option value='Please select...'>".__($guid, 'Please select...').'</option>';
											while ($rowSelect = $resultSelect->fetch()) {
												if ($rowCond['name'] == $rowSelect['name']) {
													echo "<option selected value='".htmlPrep($rowSelect['name'])."'>".htmlPrep(__($guid, $rowSelect['name'])).'</option>';
												} else {
													echo "<option value='".htmlPrep($rowSelect['name'])."'>".htmlPrep(__($guid, $rowSelect['name'])).'</option>';
												}
											}
											?>
												</select>
												<script type="text/javascript">
													var name<?php echo $count ?>=new LiveValidation('name<?php echo $count ?>');
													name<?php echo $count ?>.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
												</script>
											</td>
										</tr>
										<tr>
											<td>
												<b><?php echo __($guid, 'Risk') ?> *</b><br/>
											</td>
											<td class="right">
												<select name="gibbonAlertLevelID<?php echo $count ?>" id="gibbonAlertLevelID<?php echo $count ?>" class="standardWidth">
													<option value='Please select...'><?php echo __($guid, 'Please select...') ?></option>
													<?php
                                                    try {
                                                        $dataSelect = array();
                                                        $sqlSelect = 'SELECT * FROM gibbonAlertLevel ORDER BY sequenceNumber';
                                                        $resultSelect = $connection2->prepare($sqlSelect);
                                                        $resultSelect->execute($dataSelect);
                                                    } catch (PDOException $e) {
                                                    }

													while ($rowSelect = $resultSelect->fetch()) {
														$selected = '';
														if ($rowCond['gibbonAlertLevelID'] == $rowSelect['gibbonAlertLevelID']) {
															$selected = 'selected';
														}
														echo "<option $selected value='".$rowSelect['gibbonAlertLevelID']."'>".__($guid, $rowSelect['name']).'</option>';
													}
													?>
												</select>
												<script type="text/javascript">
													var gibbonAlertLevelID<?php echo $count ?>=new LiveValidation('gibbonAlertLevelID<?php echo $count ?>');
													gibbonAlertLevelID<?php echo $count ?>.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
												</script>
											</td>
										</tr>
										<tr>
											<td>
												<b><?php echo __($guid, 'Triggers') ?></b><br/>
											</td>
											<td class="right">
												<input name="triggers<?php echo $count ?>" id="triggers<?php echo $count ?>" maxlength=255 value="<?php echo htmlPrep($rowCond['triggers']) ?>" type="text" class="standardWidth">
											</td>
										</tr>
										<tr>
											<td>
												<b><?php echo __($guid, 'Reaction') ?></b><br/>
											</td>
											<td class="right">
												<input name="reaction<?php echo $count ?>" id="reaction<?php echo $count ?>" maxlength=255 value="<?php echo htmlPrep($rowCond['reaction']) ?>" type="text" class="standardWidth">
											</td>
										</tr>
										<tr>
											<td>
												<b><?php echo __($guid, 'Response') ?></b><br/>
											</td>
											<td class="right">
												<input name="response<?php echo $count ?>" id="response<?php echo $count ?>" maxlength=255 value="<?php echo htmlPrep($rowCond['response']) ?>" type="text" class="standardWidth">
											</td>
										</tr>
										<tr>
											<td>
												<b><?php echo __($guid, 'Medication') ?></b><br/>
											</td>
											<td class="right">
												<input name="medication<?php echo $count ?>" id="medication<?php echo $count ?>" maxlength=255 value="<?php echo htmlPrep($rowCond['medication']) ?>" type="text" class="standardWidth">
											</td>
										</tr>
										<tr>
											<td>
												<b><?php echo __($guid, 'Last Episode Date') ?></b><br/>
												<span class="emphasis small"><?php echo __($guid, 'Format:').' '.$_SESSION[$guid]['i18n']['dateFormat']  ?></span>
											</td>
											<td class="right">
												<input name="lastEpisode<?php echo $count ?>" id="lastEpisode<?php echo $count ?>" maxlength=10 value="<?php echo dateConvertBack($guid, $rowCond['lastEpisode']) ?>" type="text" class="standardWidth">
												<script type="text/javascript">
													var lastEpisode<?php echo $count ?>=new LiveValidation('lastEpisode<?php echo $count ?>');
													lastEpisode<?php echo $count ?>.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]['i18n']['dateFormatRegEx'] == '') {
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
														$( "#lastEpisode<?php echo $count ?>" ).datepicker();
													});
												</script>
											</td>
										</tr>
										<tr>
											<td>
												<b><?php echo __($guid, 'Last Episode Treatment') ?></b><br/>
											</td>
											<td class="right">
												<input name="lastEpisodeTreatment<?php echo $count ?>" id="lastEpisodeTreatment<?php echo $count ?>" maxlength=255 value="<?php echo htmlPrep($rowCond['lastEpisodeTreatment']) ?>" type="text" class="standardWidth">
											</td>
										</tr>
										<tr>
											<td>
												<b><?php echo __($guid, 'Comment') ?></b><br/>
											</td>
											<td class="right">
												<textarea name="comment<?php echo $count ?>" id="comment<?php echo $count ?>" rows=8 class="standardWidth"><?php echo $rowCond['comment'] ?></textarea>
											</td>
										</tr>
										<input name="gibbonPersonMedicalConditionID<?php echo $count ?>" id="gibbonPersonMedicalConditionID<?php echo $count ?>" value="<?php echo htmlPrep($rowCond['gibbonPersonMedicalConditionID']) ?>" type="hidden">
										<input name="gibbonPersonMedicalConditionUpdateID<?php echo $count ?>" id="gibbonPersonMedicalConditionUpdateID<?php echo $count ?>" value="<?php echo htmlPrep($rowCond['gibbonPersonMedicalConditionUpdateID']) ?>" type="hidden">
										<?php
                                        ++$count;
										}

										echo "<input name='count' id='count' value='$count' type='hidden'>";
									}
									?>
								<tr class='break'>
									<td colspan=2>
										<h3><?php echo __($guid, 'Add Medical Condition') ?></h3>
									</td>
								</tr>
								<tr>
									<td class='right' colspan=2>
										<script type="text/javascript">
											/* Advanced Options Control */
											$(document).ready(function(){
                                                namex.disable();
                                                gibbonAlertLevelIDx.disable();
                                                $("#addCondition").click(function(){
													if ($('input[name=addCondition]:checked').val()=="Yes" ) {
														$(".addConditionRow").slideDown("fast", $(".addConditionRow").css("display","table-row"));
														namex.enable();
														gibbonAlertLevelIDx.enable();
													}
													else {
														$(".addConditionRow").slideUp("fast");
														namex.disable();
														gibbonAlertLevelIDx.disable();
													}
												 });
											});
										</script>
										<span style='font-weight: bold; font-style: italic'><?php echo __($guid, 'Check the box to add a new medical condition') ?> <input id='addCondition' name='addCondition' type='checkbox' value='Yes'/></span>
									</td>
								</tr>
								<tr style='display: none' class='addConditionRow'>
									<td>
										<b><?php echo __($guid, 'Condition Name') ?> *</b><br/>
									</td>
									<td class="right">
										<select class="standardWidth" name="name" id="namex">
											<?php
                                            try {
                                                $dataSelect = array();
                                                $sqlSelect = 'SELECT * FROM gibbonMedicalCondition ORDER BY name';
                                                $resultSelect = $connection2->prepare($sqlSelect);
                                                $resultSelect->execute($dataSelect);
                                            } catch (PDOException $e) {
                                            }
											echo "<option value='Please select...'>".__($guid, 'Please select...').'</option>';
											while ($rowSelect = $resultSelect->fetch()) {
												if ($rowCond['name'] == $rowSelect['name']) {
													echo "<option selected value='".htmlPrep($rowSelect['name'])."'>".htmlPrep(__($guid, $rowSelect['name'])).'</option>';
												} else {
													echo "<option value='".htmlPrep($rowSelect['name'])."'>".htmlPrep(__($guid, $rowSelect['name'])).'</option>';
												}
											}
											?>
										</select>
										<script type="text/javascript">
											var namex=new LiveValidation('namex');
											namex.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
										</script>
									</td>
								</tr>
								<tr style='display: none' class='addConditionRow'>
									<td>
										<b><?php echo __($guid, 'Risk') ?> *</b><br/>
									</td>
									<td class="right">
										<select name="gibbonAlertLevelID" id="gibbonAlertLevelIDx" class="standardWidth">
											<option value='Please select...'><?php echo __($guid, 'Please select...') ?></option>
											<?php
                                            try {
                                                $dataSelect = array();
                                                $sqlSelect = 'SELECT * FROM gibbonAlertLevel ORDER BY sequenceNumber';
                                                $resultSelect = $connection2->prepare($sqlSelect);
                                                $resultSelect->execute($dataSelect);
                                            } catch (PDOException $e) {
                                            }

											while ($rowSelect = $resultSelect->fetch()) {
												echo "<option value='".$rowSelect['gibbonAlertLevelID']."'>".__($guid, $rowSelect['name']).'</option>';
											}
											?>
										</select>
										<script type="text/javascript">
											var gibbonAlertLevelIDx=new LiveValidation('gibbonAlertLevelIDx');
											gibbonAlertLevelIDx.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
										</script>
									</td>
								</tr>
								<tr style='display: none' class='addConditionRow'>
									<td>
										<b><?php echo __($guid, 'Triggers') ?></b><br/>
									</td>
									<td class="right">
										<input name="triggers" id="triggers" maxlength=255 value="" type="text" class="standardWidth">
									</td>
								</tr>
								<tr style='display: none' class='addConditionRow'>
									<td>
										<b><?php echo __($guid, 'Reaction') ?></b><br/>
									</td>
									<td class="right">
										<input name="reaction" id="reaction" maxlength=255 value="" type="text" class="standardWidth">
									</td>
								</tr>
								<tr style='display: none' class='addConditionRow'>
									<td>
										<b><?php echo __($guid, 'Response') ?></b><br/>
									</td>
									<td class="right">
										<input name="response" id="response" maxlength=255 value="" type="text" class="standardWidth">
									</td>
								</tr>
								<tr style='display: none' class='addConditionRow'>
									<td>
										<b><?php echo __($guid, 'Medication') ?></b><br/>
									</td>
									<td class="right">
										<input name="medication" id="medication" maxlength=255 value="" type="text" class="standardWidth">
									</td>
								</tr>
								<tr style='display: none' class='addConditionRow'>
									<td>
										<b><?php echo __($guid, 'Last Episode Date') ?></b><br/>
										<span class="emphasis small"><?php echo __($guid, 'Format:').' '.$_SESSION[$guid]['i18n']['dateFormat']  ?></span>
									</td>
									<td class="right">
										<input name="lastEpisode" id="lastEpisode" maxlength=10 value="" type="text" class="standardWidth">
										<script type="text/javascript">
											var lastEpisode=new LiveValidation('lastEpisode');
											lastEpisode.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]['i18n']['dateFormatRegEx'] == '') {
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
												$( "#lastEpisode" ).datepicker();
											});
										</script>
									</td>
								</tr>
								<tr style='display: none' class='addConditionRow'>
									<td>
										<b><?php echo __($guid, 'Last Episode Treatment') ?></b><br/>
									</td>
									<td class="right">
										<input name="lastEpisodeTreatment" id="lastEpisodeTreatment" maxlength=255 value="" type="text" class="standardWidth">
									</td>
								</tr>
								<tr style='display: none' class='addConditionRow'>
									<td>
										<b><?php echo __($guid, 'Comment') ?></b><br/>
									</td>
									<td class="right">
										<textarea name="comment" id="comment" rows=8 class="standardWidth"></textarea>
									</td>
								</tr>
								<tr>
									<td>
										<span class="emphasis small">* <?php echo __($guid, 'denotes a required field'); ?></span>
									</td>
									<td class="right">
										<?php
                                        if ($existing) {
                                            echo "<input type='hidden' name='existing' value='".$rowForm['gibbonPersonMedicalUpdateID']."'>";
                                        } else {
                                            echo "<input type='hidden' name='existing' value='N'>";
                                        }
                        			?>										<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
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
}
?>
