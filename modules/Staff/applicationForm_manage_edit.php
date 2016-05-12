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

//Module includes from User Admin (for custom fields)
include './modules/User Admin/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Staff/applicationForm_manage_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Staff/applicationForm_manage.php'>".__($guid, 'Manage Applications')."</a> > </div><div class='trailEnd'>".__($guid, 'Edit Form').'</div>';
    echo '</div>';

    //Check if school year specified
    $gibbonStaffApplicationFormID = $_GET['gibbonStaffApplicationFormID'];
    $search = $_GET['search'];
    if ($gibbonStaffApplicationFormID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonStaffApplicationFormID' => $gibbonStaffApplicationFormID);
            $sql = 'SELECT gibbonStaffApplicationForm.*, gibbonStaffJobOpening.jobTitle, gibbonStaffJobOpening.type FROM gibbonStaffApplicationForm JOIN gibbonStaffJobOpening ON (gibbonStaffApplicationForm.gibbonStaffJobOpeningID=gibbonStaffJobOpening.gibbonStaffJobOpeningID) LEFT JOIN gibbonPerson ON (gibbonStaffApplicationForm.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonStaffApplicationFormID=:gibbonStaffApplicationFormID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __($guid, 'The specified record does not exist.');
            echo '</div>';
        } else {
            if (isset($_GET['return'])) {
                returnProcess($guid, $_GET['return'], null, null);
            }

            //Let's go!
            $row = $result->fetch();
            $proceed = true;

            echo "<div class='linkTop'>";
            if ($search != '') {
                echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Staff/applicationForm_manage.php&search=$search'>".__($guid, 'Back to Search Results').'</a> | ';
            }
            echo "<a target='_blank' href='".$_SESSION[$guid]['absoluteURL'].'/report.php?q=/modules/'.$_SESSION[$guid]['module']."/applicationForm_manage_edit_print.php&gibbonStaffApplicationFormID=$gibbonStaffApplicationFormID'>".__($guid, 'Print')."<img style='margin-left: 5px' title='".__($guid, 'Print')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/print.png'/></a>";
            echo '</div>'; ?>
			<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/applicationForm_manage_editProcess.php?search=$search" ?>" enctype="multipart/form-data">
				<table class='smallIntBorder fullWidth' cellspacing='0'>
					<tr class='break'>
						<td colspan=2>
							<h3><?php echo __($guid, 'For Office Use') ?></h3>
						</td>
					</tr>
					<tr>
						<td style='width: 275px'>
							<b><?php echo __($guid, 'Application ID') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'This value cannot be changed.') ?></span>
						</td>
						<td class="right">
							<input readonly name="gibbonStaffApplicationFormID" id="gibbonStaffApplicationFormID" value="<?php echo htmlPrep($row['gibbonStaffApplicationFormID']) ?>" type="text" class="standardWidth">
						</td>
					</tr>
					<tr>
						<td>
							<b><?php echo __($guid, 'Priority') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Higher priority applicants appear first in list of applications.') ?></span>
						</td>
						<td class="right">
							<select name="priority" id="priority" class="standardWidth">
								<option <?php if ($row['priority'] == '9') { echo 'selected'; } ?> value="9">9</option>
								<option <?php if ($row['priority'] == '8') { echo 'selected'; } ?> value="8">8</option>
								<option <?php if ($row['priority'] == '7') { echo 'selected'; } ?> value="7">7</option>
								<option <?php if ($row['priority'] == '6') { echo 'selected'; } ?> value="6">6</option>
								<option <?php if ($row['priority'] == '5') { echo 'selected'; } ?> value="5">5</option>
								<option <?php if ($row['priority'] == '4') { echo 'selected'; } ?> value="4">4</option>
								<option <?php if ($row['priority'] == '3') { echo 'selected'; } ?> value="3">3</option>
								<option <?php if ($row['priority'] == '2') { echo 'selected'; } ?> value="2">2</option>
								<option <?php if ($row['priority'] == '1') { echo 'selected'; } ?> value="1">1</option>
								<option <?php if ($row['priority'] == '0') { echo 'selected'; } ?> value="0">0</option>
								<option <?php if ($row['priority'] == '-1') { echo 'selected'; } ?> value="-1">-1</option>
								<option <?php if ($row['priority'] == '-2') { echo 'selected'; } ?> value="-2">-2</option>
								<option <?php if ($row['priority'] == '-3') { echo 'selected'; } ?> value="-3">-3</option>
								<option <?php if ($row['priority'] == '-4') { echo 'selected'; } ?> value="-4">-4</option>
								<option <?php if ($row['priority'] == '-5') { echo 'selected'; } ?> value="-5">-5</option>
								<option <?php if ($row['priority'] == '-6') { echo 'selected'; } ?> value="-6">-6</option>
								<option <?php if ($row['priority'] == '-7') { echo 'selected'; } ?> value="-7">-7</option>
								<option <?php if ($row['priority'] == '-8') { echo 'selected'; } ?> value="-8">-8</option>
								<option <?php if ($row['priority'] == '-9') { echo 'selected'; } ?> value="-9">-9</option>
							</select>
						</td>
					</tr>
					<?php
                    if ($row['status'] != 'Accepted') {
                        ?>
						<tr>
							<td>
								<b><?php echo __($guid, 'Status') ?> *</b><br/>
								<span class="emphasis small"><?php echo __($guid, 'Manually set status. "Approved" not permitted.') ?></span>
							</td>
							<td class="right">
								<select name="status" id="status" class="standardWidth">
									<option <?php if ($row['status'] == 'Pending') { echo 'selected'; } ?> value="Pending"><?php echo __($guid, 'Pending') ?></option>
									<option <?php if ($row['status'] == 'Rejected') { echo 'selected'; } ?> value="Rejected"><?php echo __($guid, 'Rejected') ?></option>
									<option <?php if ($row['status'] == 'Withdrawn') { echo 'selected'; } ?> value="Withdrawn"><?php echo __($guid, 'Withdrawn') ?></option>
								</select>
							</td>
						</tr>
						<?php

                    } else {
                        ?>
						<tr>
							<td>
								<b><?php echo __($guid, 'Status') ?> *</b><br/>
								<span class="emphasis small"><?php echo __($guid, 'This value cannot be changed.') ?></span>
							</td>
							<td class="right">
								<input readonly name="status" id="status" maxlength=20 value="<?php echo htmlPrep($row['applicationStatus']) ?>" type="text" class="standardWidth">
							</td>
						</tr>
						<?php

                    }
					$milestonesMasterRaw = getSettingByScope($connection2, 'Staff', 'staffApplicationFormMilestones');
					if ($milestonesMasterRaw != '') {
                		?>
						<tr>
							<td>
								<b><?php echo __($guid, 'Milestones') ?></b><br/>
							</td>
							<td class="right">
								<?php
                                $milestones = explode(',', $row['milestones']);
								$milestonesMaster = explode(',', $milestonesMasterRaw);
								foreach ($milestonesMaster as $milestoneMaster) {
									$checked = '';
									foreach ($milestones as $milestone) {
										if (trim($milestoneMaster) == trim($milestone)) {
											$checked = 'checked';
										}
									}
									echo trim($milestoneMaster)." <input $checked type='checkbox' name='milestone_".preg_replace('/\s+/', '', $milestoneMaster)."'></br>";
								}
                			?>
							</td>
						</tr>
						<?php

					}
					?>
					<tr>
						<td>
							<b><?php echo __($guid, 'Start Date') ?></b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Intended first day at school.') ?><br/>Format <?php if ($_SESSION[$guid]['i18n']['dateFormat'] == '') { echo 'dd/mm/yyyy';
							} else {
								echo $_SESSION[$guid]['i18n']['dateFormat'];
							}
							?></span>
						</td>
						<td class="right">
							<input name="dateStart" id="dateStart" maxlength=10 value="<?php echo dateConvertBack($guid, $row['dateStart']) ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var dateStart=new LiveValidation('dateStart');
								dateStart.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]['i18n']['dateFormatRegEx'] == '') { echo "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i";
								} else {
									echo $_SESSION[$guid]['i18n']['dateFormatRegEx'];
								}
								?>, failureMessage: "Use <?php if ($_SESSION[$guid]['i18n']['dateFormat'] == '') { echo 'dd/mm/yyyy';
								} else {
									echo $_SESSION[$guid]['i18n']['dateFormat'];
								}
								?>." } );
							</script>
							 <script type="text/javascript">
								$(function() {
									$( "#dateStart" ).datepicker();
								});
							</script>
						</td>
					</tr>
					<tr>
						<td colspan=2 style='padding-top: 15px'>
							<b><?php echo __($guid, 'Notes') ?></b><br/>
							<textarea name="notes" id="notes" rows=5 style="width:738px; margin: 5px 0px 0px 0px"><?php echo htmlPrep($row['notes']) ?></textarea>
						</td>
					</tr>

					<tr class='break'>
						<td colspan=2> 
							<h3><?php echo __($guid, 'Job Related Information') ?></h3>
						</td>
					</tr>
					<tr>
						<td style='width: 275px'> 
							<b><?php echo __($guid, 'Job Type') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'This value cannot be changed.') ?></span>
						</td>
						<td class="right">
							<input readonly name="type" id="type" maxlength=30 value="<?php echo htmlPrep($row['type']) ?>" type="text" class="standardWidth">
						</td>
					</tr>
					<tr>
						<td style='width: 275px'> 
							<b><?php echo __($guid, 'Job Opening') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'This value cannot be changed.') ?></span>
						</td>
						<td class="right">
							<input readonly name="jobTitle" id="jobTitle" maxlength=30 value="<?php echo htmlPrep($row['jobTitle']) ?>" type="text" class="standardWidth">
							<input name="gibbonStaffJobOpeningID" id="gibbonStaffJobOpeningID" value="<?php echo htmlPrep($row['gibbonStaffJobOpeningID']) ?>" type="hidden" class="standardWidth">
						</td>
					</tr>
					<?php
                    //Get application question
                    $staffApplicationFormQuestions = getSettingByScope($connection2, 'Staff', 'staffApplicationFormQuestions');
					if ($staffApplicationFormQuestions != '') {
						echo '<tr>';
						echo '<td colspan=2>';
						echo '<b>'.__($guid, 'Application Questions').'</b><br/>';
						echo '<span style="font-size: 90%"><i>'.__($guid, 'This value cannot be changed.').'</span>';
						echo '<p>'.$row['questions'].'</p>';
						echo '</td>';
						echo '</tr>';
					}
					?>
				
					<tr class='break'>
						<td colspan=2> 
							<h3><?php echo __($guid, 'Personal Data') ?></h3>
						</td>
					</tr>
					<?php
                    if ($row['gibbonPersonID'] != null) {
                        ?>
						<input name="gibbonPersonID" id="gibbonPersonID" maxlength=10 value="<?php echo htmlPrep($_SESSION[$guid]['gibbonPersonID']) ?>" type="hidden" class="standardWidth">
						<tr>
							<td style='width: 275px'> 
								<b><?php echo __($guid, 'Surname') ?></b><br/>
								<span class="emphasis small"><?php echo __($guid, 'This value cannot be changed.') ?></span>
							</td>
							<td class="right">
								<input readonly name="surname" id="surname" maxlength=30 value="<?php echo htmlPrep($row['surname']) ?>" type="text" class="standardWidth">
							</td>
						</tr>
						<tr>
							<td> 
								<b><?php echo __($guid, 'Preferred Name') ?></b><br/>
								<span class="emphasis small"><?php echo __($guid, 'This value cannot be changed.') ?></span>
							</td>
							<td class="right">
								<input readonly name="preferredName" id="preferredName" maxlength=30 value="<?php echo htmlPrep($row['preferredName']) ?>" type="text" class="standardWidth">
							</td>
						</tr>
						<?php

                    } else {
                        ?>
						<tr>
							<td style='width: 275px'> 
								<b><?php echo __($guid, 'Surname') ?> *</b><br/>
								<span class="emphasis small"><?php echo __($guid, 'Family name as shown in ID documents.') ?></span>
							</td>
							<td class="right">
								<input name="surname" id="surname" maxlength=30 value="<?php echo htmlPrep($row['surname']) ?>" type="text" class="standardWidth">
								<script type="text/javascript">
									var surname=new LiveValidation('surname');
									surname.add(Validate.Presence);
								</script>
							</td>
						</tr>
						<tr>
							<td> 
								<b><?php echo __($guid, 'First Name') ?> *</b><br/>
								<span class="emphasis small"><?php echo __($guid, 'First name as shown in ID documents.') ?></span>
							</td>
							<td class="right">
								<input name="firstName" id="firstName" maxlength=30 value="<?php echo htmlPrep($row['firstName']) ?>" type="text" class="standardWidth">
								<script type="text/javascript">
									var firstName=new LiveValidation('firstName');
									firstName.add(Validate.Presence);
								</script>
							</td>
						</tr>
						<tr>
							<td> 
								<b><?php echo __($guid, 'Preferred Name') ?> *</b><br/>
								<span class="emphasis small"><?php echo __($guid, 'Most common name, alias, nickname, etc.') ?></span>
							</td>
							<td class="right">
								<input name="preferredName" id="preferredName" maxlength=30 value="<?php echo htmlPrep($row['preferredName']) ?>" type="text" class="standardWidth">
								<script type="text/javascript">
									var preferredName=new LiveValidation('preferredName');
									preferredName.add(Validate.Presence);
								</script>
							</td>
						</tr>
						<tr>
							<td> 
								<b><?php echo __($guid, 'Official Name') ?> *</b><br/>
								<span class="emphasis small"><?php echo __($guid, 'Full name as shown in ID documents.') ?></span>
							</td>
							<td class="right">
								<input title='Please enter full name as shown in ID documents' name="officialName" id="officialName" maxlength=150 value="<?php echo htmlPrep($row['officialName']) ?>" type="text" class="standardWidth">
								<script type="text/javascript">
									var officialName=new LiveValidation('officialName');
									officialName.add(Validate.Presence);
								</script>
							</td>
						</tr>
						<tr>
							<td> 
								<b><?php echo __($guid, 'Name In Characters') ?></b><br/>
								<span class="emphasis small"><?php echo __($guid, 'Chinese or other character-based name.') ?></span>
							</td>
							<td class="right">
								<input name="nameInCharacters" id="nameInCharacters" maxlength=20 value="<?php echo htmlPrep($row['nameInCharacters']) ?>" type="text" class="standardWidth">
							</td>
						</tr>
						<tr>
							<td> 
								<b><?php echo __($guid, 'Gender') ?> *</b><br/>
							</td>
							<td class="right">
								<select name="gender" id="gender" class="standardWidth">
									<option value="Please select..."><?php echo __($guid, 'Please select...') ?></option>
									<option <?php if ($row['gender'] == 'F') { echo 'selected'; } ?> value="F"><?php echo __($guid, 'Female') ?></option>
									<option <?php if ($row['gender'] == 'M') { echo 'selected'; } ?> value="M"><?php echo __($guid, 'Male') ?></option>
								</select>
								<script type="text/javascript">
									var gender=new LiveValidation('gender');
									gender.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
								</script>
							</td>
						</tr>
						<tr>
							<td> 
								<b><?php echo __($guid, 'Date of Birth') ?> *</b><br/>
								<span class="emphasis small"><?php echo __($guid, 'Format:').' '.$_SESSION[$guid]['i18n']['dateFormat']  ?></span>
							</td>
							<td class="right">
								<input name="dob" id="dob" maxlength=10 value="<?php if ($row['dob'] != '') { echo dateConvertBack($guid, $row['dob']); } ?>" type="text" class="standardWidth">
								<script type="text/javascript">
									var dob=new LiveValidation('dob');
									dob.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]['i18n']['dateFormatRegEx'] == '') {
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
									dob.add(Validate.Presence);
								</script>
								 <script type="text/javascript">
									$(function() {
										$( "#dob" ).datepicker();
									});
								</script>
							</td>
						</tr>
			
			
						<tr class='break'>
							<td colspan=2> 
								<h3><?php echo __($guid, 'Background Data') ?></h3>
							</td>
						</tr>
						<tr>
							<td> 
								<b><?php echo __($guid, 'First Language') ?> *</b><br/>
								<span class="emphasis small"><?php echo __($guid, 'Student\'s native/first/mother language.') ?></span>
							</td>
							<td class="right">
								<select name="languageFirst" id="languageFirst" class="standardWidth">
									<?php
                                    echo "<option value='Please select...'>Please select...</option>";
									try {
										$dataSelect = array();
										$sqlSelect = 'SELECT name FROM gibbonLanguage ORDER BY name';
										$resultSelect = $connection2->prepare($sqlSelect);
										$resultSelect->execute($dataSelect);
									} catch (PDOException $e) {
									}
									while ($rowSelect = $resultSelect->fetch()) {
										$selected = '';
										if ($row['languageFirst'] == $rowSelect['name']) {
											$selected = 'selected';
										}
										echo "<option $selected value='".$rowSelect['name']."'>".htmlPrep(__($guid, $rowSelect['name'])).'</option>';
									}
									?>				
								</select>
								<script type="text/javascript">
									var languageFirst=new LiveValidation('languageFirst');
									languageFirst.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
								</script>
							</td>
						</tr>
						<tr>
							<td> 
								<b><?php echo __($guid, 'Second Language') ?></b><br/>
							</td>
							<td class="right">
								<select name="languageSecond" id="languageSecond" class="standardWidth">
									<?php
                                    echo "<option value=''></option>";
									try {
										$dataSelect = array();
										$sqlSelect = 'SELECT name FROM gibbonLanguage ORDER BY name';
										$resultSelect = $connection2->prepare($sqlSelect);
										$resultSelect->execute($dataSelect);
									} catch (PDOException $e) {
									}
									while ($rowSelect = $resultSelect->fetch()) {
										$selected = '';
										if ($row['languageSecond'] == $rowSelect['name']) {
											$selected = 'selected';
										}
										echo "<option $selected value='".$rowSelect['name']."'>".htmlPrep(__($guid, $rowSelect['name'])).'</option>';
									}
									?>				
								</select>
							</td>
						</tr>
						<tr>
							<td> 
								<b><?php echo __($guid, 'Third Language') ?></b><br/>
							</td>
							<td class="right">
								<select name="languageThird" id="languageThird" class="standardWidth">
									<?php
                                    echo "<option value=''></option>";
									try {
										$dataSelect = array();
										$sqlSelect = 'SELECT name FROM gibbonLanguage ORDER BY name';
										$resultSelect = $connection2->prepare($sqlSelect);
										$resultSelect->execute($dataSelect);
									} catch (PDOException $e) {
									}
									while ($rowSelect = $resultSelect->fetch()) {
										$selected = '';
										if ($row['languageThird'] == $rowSelect['name']) {
											$selected = 'selected';
										}
										echo "<option $selected value='".$rowSelect['name']."'>".htmlPrep(__($guid, $rowSelect['name'])).'</option>';
									}
									?>				
								</select>
							</td>
						</tr>
						<tr>
							<td> 
								<b><?php echo __($guid, 'Country of Birth') ?></b><br/>
							</td>
							<td class="right">
								<select name="countryOfBirth" id="countryOfBirth" class="standardWidth">
									<?php
                                    try {
                                        $dataSelect = array();
                                        $sqlSelect = 'SELECT printable_name FROM gibbonCountry ORDER BY printable_name';
                                        $resultSelect = $connection2->prepare($sqlSelect);
                                        $resultSelect->execute($dataSelect);
                                    } catch (PDOException $e) {
                                    }
									echo "<option value=''></option>";
									while ($rowSelect = $resultSelect->fetch()) {
										$selected = '';
										if ($row['countryOfBirth'] == $rowSelect['printable_name']) {
											$selected = 'selected';
										}
										echo "<option $selected value='".$rowSelect['printable_name']."'>".htmlPrep(__($guid, $rowSelect['printable_name'])).'</option>';
									}
									?>				
								</select>
							</td>
						</tr>
						<tr>
							<td> 
								<b><?php echo __($guid, 'Citizenship') ?></b><br/>
							</td>
							<td class="right">
								<select name="citizenship1" id="citizenship1" class="standardWidth">
									<?php
                                    echo "<option value=''></option>";
									$nationalityList = getSettingByScope($connection2, 'User Admin', 'nationality');
									if ($nationalityList == '') {
										try {
											$dataSelect = array();
											$sqlSelect = 'SELECT printable_name FROM gibbonCountry ORDER BY printable_name';
											$resultSelect = $connection2->prepare($sqlSelect);
											$resultSelect->execute($dataSelect);
										} catch (PDOException $e) {
										}
										while ($rowSelect = $resultSelect->fetch()) {
											$selected = '';
											if ($row['citizenship1'] == $rowSelect['printable_name']) {
												$selected = 'selected';
											}
											echo "<option $selected value='".$rowSelect['printable_name']."'>".htmlPrep(__($guid, $rowSelect['printable_name'])).'</option>';
										}
									} else {
										$nationalities = explode(',', $nationalityList);
										foreach ($nationalities as $nationality) {
											echo "<option value='".trim($nationality)."'>".trim($nationality).'</option>';
										}
									}
									?>				
								</select>
							</td>
						</tr>
						<tr>
							<td> 
								<b><?php echo __($guid, 'Citizenship Passport Number') ?></b><br/>
							</td>
							<td class="right">
								<input name="citizenship1Passport" id="citizenship1Passport" maxlength=30 value="<?php echo htmlPrep($row['citizenship1Passport']) ?>" type="text" class="standardWidth">
							</td>
						</tr>
						<tr>
							<td> 
								<?php
                                if ($_SESSION[$guid]['country'] == '') {
                                    echo '<b>'.__($guid, 'National ID Card Number').'</b><br/>';
                                } else {
                                    echo '<b>'.$_SESSION[$guid]['country'].' '.__($guid, 'ID Card Number').'</b><br/>';
                                }
                        		?>
							</td>
							<td class="right">
								<input name="nationalIDCardNumber" id="nationalIDCardNumber" maxlength=30 value="<?php echo htmlPrep($row['nationalIDCardNumber']) ?>" type="text" class="standardWidth">
							</td>
						</tr>
						<tr>
							<td> 
								<?php
                                if ($_SESSION[$guid]['country'] == '') {
                                    echo '<b>'.__($guid, 'Residency/Visa Type').'</b><br/>';
                                } else {
                                    echo '<b>'.$_SESSION[$guid]['country'].' '.__($guid, 'Residency/Visa Type').'</b><br/>';
                                }
                        		?>
							</td>
							<td class="right">
								<?php
                                $residencyStatusList = getSettingByScope($connection2, 'User Admin', 'residencyStatus');
                        if ($residencyStatusList == '') {
                            echo "<input name='residencyStatus' id='residencyStatus' maxlength=30 value='".htmlPrep($row['residencyStatus'])."' type='text' style='width: 300px'>";
                        } else {
                            echo "<select name='residencyStatus' id='residencyStatus' style='width: 302px'>";
                            echo "<option value=''></option>";
                            $residencyStatuses = explode(',', $residencyStatusList);
                            foreach ($residencyStatuses as $residencyStatus) {
                                echo "<option value='".trim($residencyStatus)."'>".trim($residencyStatus).'</option>';
                            }
                            echo '</select>';
                        }
                        ?>
						</td>
					</tr>
					<tr>
						<td> 
							<?php
							if ($_SESSION[$guid]['country'] == '') {
								echo '<b>'.__($guid, 'Visa Expiry Date').'</b><br/>';
							} else {
								echo '<b>'.$_SESSION[$guid]['country'].' '.__($guid, 'Visa Expiry Date').'</b><br/>';
							}
							echo "<span style='font-size: 90%'><i>Format: ";
							if ($_SESSION[$guid]['i18n']['dateFormat'] == '') {
								echo 'dd/mm/yyyy';
							} else {
								echo $_SESSION[$guid]['i18n']['dateFormat'];
							}
							echo '. '.__($guid, 'If relevant.').'</span>';
							?>
						</td>
						<td class="right">
							<input name="visaExpiryDate" id="visaExpiryDate" maxlength=10 value="<?php if ($row['visaExpiryDate'] != '') { echo dateConvertBack($guid, $row['visaExpiryDate']); } ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var visaExpiryDate=new LiveValidation('visaExpiryDate');
								visaExpiryDate.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]['i18n']['dateFormatRegEx'] == '') {
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
									$( "#visaExpiryDate" ).datepicker();
								});
							</script>
						</td>
					</tr>
		
		
					<tr class='break'>
						<td colspan=2> 
							<h3><?php echo __($guid, 'Contacts') ?></h3>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Email') ?> *</b><br/>
						</td>
						<td class="right">
							<input name="email" id="email" maxlength=50 value="<?php echo htmlPrep($row['email']) ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var email=new LiveValidation('email');
								email.add(Validate.Email);
								email.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Phone') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Type, country code, number.') ?></span>
						</td>
						<td class="right">
							<input name="phone1" id="phone1" maxlength=20 value="<?php echo htmlPrep($row['phone1']) ?>" type="text" style="width: 160px">
							<script type="text/javascript">
								var phone1=new LiveValidation('phone1');
								phone1.add(Validate.Presence);
							</script>
							<select name="phone1CountryCode" id="phone1CountryCode" style="width: 60px">
								<?php
								echo "<option value=''></option>";
								try {
									$dataSelect = array();
									$sqlSelect = 'SELECT * FROM gibbonCountry ORDER BY printable_name';
									$resultSelect = $connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								} catch (PDOException $e) {
								}
								while ($rowSelect = $resultSelect->fetch()) {
									$selected = '';
									if ($row['phone1CountryCode'] == $rowSelect['iddCountryCode']) {
										$selected = 'selected';
									}
									echo "<option $selected value='".$rowSelect['iddCountryCode']."'>".htmlPrep($rowSelect['iddCountryCode']).' - '.htmlPrep(__($guid, $rowSelect['printable_name'])).'</option>';
								}
								?>				
							</select>
							<select style="width: 70px" name="phone1Type">
								<option value=""></option>
								<option <?php if ($row['phone1Type'] == 'Mobile') { echo 'selected'; } ?> value="Mobile"><?php echo __($guid, 'Mobile') ?></option>
								<option <?php if ($row['phone1Type'] == 'Home') { echo 'selected'; } ?> value="Home"><?php echo __($guid, 'Home') ?></option>
								<option <?php if ($row['phone1Type'] == 'Work') { echo 'selected'; } ?> value="Work"><?php echo __($guid, 'Work') ?></option>
								<option <?php if ($row['phone1Type'] == 'Fax') { echo 'selected'; } ?> value="Fax"><?php echo __($guid, 'Fax') ?></option>
								<option <?php if ($row['phone1Type'] == 'Pager') { echo 'selected'; } ?> value="Pager"><?php echo __($guid, 'Pager') ?></option>
								<option <?php if ($row['phone1Type'] == 'Other') { echo 'selected'; } ?> value="Other"><?php echo __($guid, 'Other') ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Home Address') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Unit, Building, Street') ?></span>
						</td>
						<td class="right">
							<input name="homeAddress" id="homeAddress" maxlength=255 value="<?php echo htmlPrep($row['homeAddress']) ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var homeAddress=new LiveValidation('homeAddress');
								homeAddress.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Home Address (District)') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'County, State, District') ?></span>
						</td>
						<td class="right">
							<input name="homeAddressDistrict" id="homeAddressDistrict" maxlength=30 value="<?php echo htmlPrep($row['homeAddressDistrict']) ?>" type="text" class="standardWidth">
						</td>
						<script type="text/javascript">
							$(function() {
								var availableTags=[
									<?php
									try {
										$dataAuto = array();
										$sqlAuto = 'SELECT DISTINCT name FROM gibbonDistrict ORDER BY name';
										$resultAuto = $connection2->prepare($sqlAuto);
										$resultAuto->execute($dataAuto);
									} catch (PDOException $e) {
									}
									while ($rowAuto = $resultAuto->fetch()) {
										echo '"'.$rowAuto['name'].'", ';
									}
									?>
								];
								$( "#homeAddressDistrict" ).autocomplete({source: availableTags});
							});
						</script>
						<script type="text/javascript">
							var homeAddressDistrict=new LiveValidation('homeAddressDistrict');
							homeAddressDistrict.add(Validate.Presence);
						</script>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Home Address (Country)') ?> *</b><br/>
						</td>
						<td class="right">
							<select name="homeAddressCountry" id="homeAddressCountry" class="standardWidth">
								<?php
								try {
									$dataSelect = array();
									$sqlSelect = 'SELECT printable_name FROM gibbonCountry ORDER BY printable_name';
									$resultSelect = $connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								} catch (PDOException $e) {
								}
					echo "<option value='Please select...'>".__($guid, 'Please select...').'</option>';
					while ($rowSelect = $resultSelect->fetch()) {
						$selected = '';
						if ($row['homeAddressCountry'] == $rowSelect['printable_name']) {
							$selected = 'selected';
						}
						echo "<option $selected value='".$rowSelect['printable_name']."'>".htmlPrep(__($guid, $rowSelect['printable_name'])).'</option>';
					}
					?>				
							</select>
							<script type="text/javascript">
								var homeAddressCountry=new LiveValidation('homeAddressCountry');
								homeAddressCountry.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
							</script>
						</td>
					</tr>
				<?php

				}

				//CUSTOM FIELDS FOR STAFF
				$fields = unserialize($row['fields']);
				$resultFields = getCustomFields($connection2, $guid, false, true, false, false, true, null);
				if ($resultFields->rowCount() > 0) {
					?>
					<tr class='break'>
						<td colspan=2> 
							<h3><?php echo __($guid, 'Other Information') ?></h3>
						</td>
					</tr>
					<?php
					while ($rowFields = $resultFields->fetch()) {
						echo renderCustomFieldRow($connection2, $guid, $rowFields, $fields[$rowFields['gibbonPersonFieldID']]);
					}
				}

				$staffApplicationFormRequiredDocuments = getSettingByScope($connection2, 'Staff', 'staffApplicationFormRequiredDocuments');
				$staffApplicationFormRequiredDocumentsCompulsory = getSettingByScope($connection2, 'Staff', 'staffApplicationFormRequiredDocumentsCompulsory');
				$count = 0;
				if ($staffApplicationFormRequiredDocuments != '' and $staffApplicationFormRequiredDocuments != false) {
					?>
					<tr class='break'>
						<td colspan=2>
							<h3><?php echo __($guid, 'Supporting Documents') ?></h3>
						</td>
					</tr>
					<?php

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

					$staffApplicationFormRequiredDocumentsList = explode(',', $staffApplicationFormRequiredDocuments);
					foreach ($staffApplicationFormRequiredDocumentsList as $document) {
						try {
							$dataFile = array('gibbonStaffApplicationFormID' => $gibbonStaffApplicationFormID, 'name' => $document);
							$sqlFile = 'SELECT * FROM gibbonStaffApplicationFormFile WHERE gibbonStaffApplicationFormID=:gibbonStaffApplicationFormID AND name=:name ORDER BY name';
							$resultFile = $connection2->prepare($sqlFile);
							$resultFile->execute($dataFile);
						} catch (PDOException $e) {
						}
						if ($resultFile->rowCount() == 0) {
							?>
							<tr>
								<td>
									<b><?php echo $document;
									if ($staffApplicationFormRequiredDocumentsCompulsory == 'Y') {
										echo ' *';
									}
									?></b><br/>
								</td>
								<td class="right">
									<?php
									echo "<input type='file' name='file$count' id='file$count'><br/>";
									echo "<input type='hidden' name='fileName$count' id='filefileName$count' value='$document'>";
									if ($staffApplicationFormRequiredDocumentsCompulsory == 'Y') {
										echo "<script type='text/javascript'>";
										echo "var file$count=new LiveValidation('file$count');";
										echo "file$count.add( Validate.Inclusion, { within: [".$ext."], failureMessage: 'Illegal file type!', partialMatch: true, caseSensitive: false } );";
										echo "file$count.add(Validate.Presence);";
										echo '</script>';
									}
									++$count;
									?>
								</td>
							</tr>
							<?php

							} elseif ($resultFile->rowCount() == 1) {
								$rowFile = $resultFile->fetch();
								?>
								<tr>
									<td>
										<?php echo '<b>'.$rowFile['name'].'</b><br/>' ?>
										<span class="emphasis small"><?php echo __($guid, 'This value cannot be changed.') ?></span>
									</td>
									<td class="right">
										<?php
										echo "<a target='_blank' href='".$_SESSION[$guid]['absoluteURL'].'/'.$rowFile['path']."'>Download</a>";
										?>
									</td>
								</tr>
								<?php

							} else {
								//Error
							}
						}
					}
					if ($count > 0) {
						?>
						<tr>
							<td colspan=2>
								<?php echo getMaxUpload($guid);?>
								<input type="hidden" name="fileCount" value="<?php echo $count ?>">
							</td>
						</tr>
						<?php
					}

					//REFERENCES
					$applicationFormRefereeLink = getSettingByScope($connection2, 'Staff', 'applicationFormRefereeLink');
					if ($applicationFormRefereeLink != '') {
						echo "<tr class='break'>";
						echo '<td colspan=2>';
						echo '<h3>';
						echo __($guid, 'References');
						echo '</h3>';
						echo '</td>';
						echo '</tr>'; ?>
						<tr>
							<td> 
								<b><?php echo __($guid, 'Referee 1') ?> *</b><br/>
								<span class="emphasis small"><?php echo __($guid, 'An email address for a referee at the applicant\'s current school.') ?></span>
							</td>
							<td class="right">
								<input name="referenceEmail1" id="referenceEmail1" maxlength=100 value="<?php echo htmlPrep($row['referenceEmail1']) ?>" type="text" class="standardWidth">
								<script type="text/javascript">
									var referenceEmail1=new LiveValidation('referenceEmail1');
									referenceEmail1.add(Validate.Presence);
									referenceEmail1.add(Validate.Email);
								</script>
							</td>
						</tr>
						<tr>
							<td> 
								<b><?php echo __($guid, 'Referee 2') ?> *</b><br/>
								<span class="emphasis small"><?php echo __($guid, 'An email address for a second referee.') ?></span>
							</td>
							<td class="right">
								<input name="referenceEmail2" id="referenceEmail2" maxlength=100 value="<?php echo htmlPrep($row['referenceEmail2']) ?>" type="text" class="standardWidth">
								<script type="text/javascript">
									var referenceEmail2=new LiveValidation('referenceEmail2');
									referenceEmail2.add(Validate.Presence);
									referenceEmail2.add(Validate.Email);
								</script>
							</td>
						</tr>
						<?php
					}

					if ($proceed == true) {
						?>
						<tr>
							<td>
								<span class="emphasis small">* <?php echo __($guid, 'denotes a required field'); ?></span>
							</td>
							<td class="right">
								<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
								<input name="gibbonSchoolYearID" id="gibbonSchoolYearID" value="<?php echo $gibbonSchoolYearID ?>" type="hidden">
								<input type="hidden" name="gibbonStaffApplicationFormID" value="<?php echo $row['gibbonStaffApplicationFormID'] ?>">
								<input type="submit" value="<?php echo __($guid, 'Submit'); ?>">
							</td>
						</tr>
						<?php
					}
					?>
				</table>
			</form>
			<?php
        }
    }
}
?>
