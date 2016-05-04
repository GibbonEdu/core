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

//Module includes from User Admin (for custom fields)
include './modules/User Admin/moduleFunctions.php';

$proceed = false;
$public = false;
if (isset($_SESSION[$guid]['username']) == false) {
    $public = true;
    $proceed = true;
} else {
    if (isActionAccessible($guid, $connection2, '/modules/Staff/applicationForm.php') != false) {
        $proceed = true;
    }
}

//Set gibbonPersonID of the person completing the application
$gibbonPersonID = null;
if (isset($_SESSION[$guid]['gibbonPersonID'])) {
    $gibbonPersonID = $_SESSION[$guid]['gibbonPersonID'];
}

if ($proceed == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    if (isset($_SESSION[$guid]['username'])) {
        echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Staff Application Form').'</div>';
    } else {
        echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > </div><div class='trailEnd'>".__($guid, 'Staff Application Form').'</div>';
    }
    echo '</div>';

    //Get intro
    $intro = getSettingByScope($connection2, 'Staff', 'staffApplicationFormIntroduction');
    if ($intro != '') {
        echo '<p>';
        echo $intro;
        echo '</p>';
    }

    if (isset($_SESSION[$guid]['username']) == false) {
        echo "<div class='warning' style='font-weight: bold'>".sprintf(__($guid, 'If you already have an account for %1$s %2$s, please log in now to prevent creation of duplicate data about you! Once logged in, you can find the form under People > Staff in the main menu.'), $_SESSION[$guid]['organisationNameShort'], $_SESSION[$guid]['systemName']).' '.sprintf(__($guid, 'If you do not have an account for %1$s %2$s, please use the form below.'), $_SESSION[$guid]['organisationNameShort'], $_SESSION[$guid]['systemName']).'</div>';
    }

    $returnExtra = '';
    if (isset($_GET['id'])) {
        if ($_GET['id'] != '') {
            $returnExtra .= '<br/><br/>'.__($guid, 'If you need to contact the school in reference to this application, please quote the following number(s):').' <b><u>'.$_GET['id'].'</b></u>.';
        }
    }
    if ($_SESSION[$guid]['organisationHRName'] != '' and $_SESSION[$guid]['organisationHREmail'] != '') {
        $returnExtra .= '<br/><br/>'.sprintf(__($guid, 'Please contact %1$s if you have any questions, comments or complaints.'), "<a href='mailto:".$_SESSION[$guid]['organisationHREmail']."'>".$_SESSION[$guid]['organisationHRName'].'</a>');
    }

    $returns = array();
    $returns['success0'] = __($guid, 'Your application was successfully submitted. Our Human Resources team will review your application and be in touch in due course.').$returnExtra;
    $returns['warning1'] = __($guid, 'Your application was submitted, but some errors occured. We recommend you contact our Human Resources team to review your application.').$returnExtra;
    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, $returns);
    }

    //Check for job openings
    try {
        $data = array('dateOpen' => date('Y-m-d'));
        $sql = "SELECT * FROM gibbonStaffJobOpening WHERE active='Y' AND dateOpen<=:dateOpen ORDER BY jobTitle";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        echo "<div class='error'>";
        echo __($guid, 'Your request failed due to a database error.');
        echo '</div>';
    }

    if ($result->rowCount() < 1) {
        echo "<div class='error'>";
        echo __($guid, 'There are no job openings at this time: please try again later.');
        echo '</div>';
    } else {
        $jobOpenings = $result->fetchAll();

        echo "<div class='linkTop'>";
        echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/applicationForm_jobOpenings_view.php'>".__($guid, 'View Current Job Openings')."<img style='margin-left: 5px' title='".__($guid, 'View Current Job Openings')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/plus.png'/></a>";
        echo '</div>'; ?>
	
		<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/applicationFormProcess.php' ?>" enctype="multipart/form-data">
			<table class='smallIntBorder fullWidth' cellspacing='0'>	
				<tr class='break'>
					<td colspan=2> 
						<h3><?php echo __($guid, 'Job Related Information') ?></h3>
					</td>
				</tr>
				<tr>
					<td style='width: 275px'> 
						<b><?php echo __($guid, 'Job Openings') ?> *</b><br/>
						<span class="emphasis small"><?php echo __($guid, 'Please select one or more jobs to apply for.') ?></span>
					</td>
					<td class="right">
						<?php
                        foreach ($jobOpenings as $jobOpening) {
                            echo $jobOpening['jobTitle']." <input type='checkbox' name='gibbonStaffJobOpeningID[]' value='".$jobOpening['gibbonStaffJobOpeningID']."'><br/>";
                        }
        				?>
					</td>
				</tr>
				<?php
                //Get agreement
                $staffApplicationFormQuestions = getSettingByScope($connection2, 'Staff', 'staffApplicationFormQuestions');
				if ($staffApplicationFormQuestions != '') {
					echo '<tr>';
					echo '<td colspan=2>';
					echo '<b>'.__($guid, 'Application Questions').'</b><br/>';
					echo '<span style="font-size: 90%"><i>'.__($guid, 'Please answer the following questions in relation to your application.').'</span><br/>';
					echo getEditor($guid,  true, 'questions', $staffApplicationFormQuestions, 10, false);
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
                if ($gibbonPersonID != null) {
                    ?>
					<input name="gibbonPersonID" id="gibbonPersonID" maxlength=10 value="<?php echo htmlPrep($_SESSION[$guid]['gibbonPersonID']) ?>" type="hidden" class="standardWidth">
					<tr>
						<td style='width: 275px'> 
							<b><?php echo __($guid, 'Surname') ?></b><br/>
							<span class="emphasis small"><?php echo __($guid, 'This value cannot be changed.') ?></span>
						</td>
						<td class="right">
							<input readonly name="surname" id="surname" maxlength=30 value="<?php echo htmlPrep($_SESSION[$guid]['surname']) ?>" type="text" class="standardWidth">
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Preferred Name') ?></b><br/>
							<span class="emphasis small"><?php echo __($guid, 'This value cannot be changed.') ?></span>
						</td>
						<td class="right">
							<input readonly name="preferredName" id="preferredName" maxlength=30 value="<?php echo htmlPrep($_SESSION[$guid]['preferredName']) ?>" type="text" class="standardWidth">
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
							<input name="surname" id="surname" maxlength=30 value="" type="text" class="standardWidth">
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
							<input name="firstName" id="firstName" maxlength=30 value="" type="text" class="standardWidth">
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
							<input name="preferredName" id="preferredName" maxlength=30 value="" type="text" class="standardWidth">
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
							<input title='Please enter full name as shown in ID documents' name="officialName" id="officialName" maxlength=150 value="" type="text" class="standardWidth">
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
							<input name="nameInCharacters" id="nameInCharacters" maxlength=20 value="" type="text" class="standardWidth">
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Gender') ?> *</b><br/>
						</td>
						<td class="right">
							<select name="gender" id="gender" class="standardWidth">
								<option value="Please select..."><?php echo __($guid, 'Please select...') ?></option>
								<option value="F"><?php echo __($guid, 'Female') ?></option>
								<option value="M"><?php echo __($guid, 'Male') ?></option>
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
							<input name="dob" id="dob" maxlength=10 value="" type="text" class="standardWidth">
							<script type="text/javascript">
								var dob=new LiveValidation('dob');
								dob.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]['i18n']['dateFormatRegEx'] == '') { echo "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i";
								} else {
									echo $_SESSION[$guid]['i18n']['dateFormatRegEx'];
								}
								?>, failureMessage: "Use <?php if ($_SESSION[$guid]['i18n']['dateFormat'] == '') { echo 'dd/mm/yyyy';
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
									echo "<option value='".$rowSelect['name']."'>".htmlPrep(__($guid, $rowSelect['name'])).'</option>';
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
									echo "<option value='".$rowSelect['name']."'>".htmlPrep(__($guid, $rowSelect['name'])).'</option>';
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
									echo "<option value='".$rowSelect['name']."'>".htmlPrep(__($guid, $rowSelect['name'])).'</option>';
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
									echo "<option value='".$rowSelect['printable_name']."'>".htmlPrep(__($guid, $rowSelect['printable_name'])).'</option>';
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
										echo "<option value='".$rowSelect['printable_name']."'>".htmlPrep(__($guid, $rowSelect['printable_name'])).'</option>';
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
							<input name="citizenship1Passport" id="citizenship1Passport" maxlength=30 value="" type="text" class="standardWidth">
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
							<input name="nationalIDCardNumber" id="nationalIDCardNumber" maxlength=30 value="" type="text" class="standardWidth">
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
								echo "<input name='residencyStatus' id='residencyStatus' maxlength=30 value='' type='text' style='width: 300px'>";
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
							echo '. '.__($guid, 'If relevant.').'</span>'; ?>
						</td>
						<td class="right">
							<input name="visaExpiryDate" id="visaExpiryDate" maxlength=10 value="" type="text" class="standardWidth">
							<script type="text/javascript">
								var visaExpiryDate=new LiveValidation('visaExpiryDate');
								visaExpiryDate.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]['i18n']['dateFormatRegEx'] == '') { echo "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i";
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
							<input name="email" id="email" maxlength=50 value="" type="text" class="standardWidth">
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
							<input name="phone1" id="phone1" maxlength=20 value="" type="text" style="width: 160px">
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
									echo "<option value='".$rowSelect['iddCountryCode']."'>".htmlPrep($rowSelect['iddCountryCode']).' - '.htmlPrep(__($guid, $rowSelect['printable_name'])).'</option>';
								}
								?>				
							</select>
							<select style="width: 70px" name="phone1Type">
								<option value=""></option>
								<option value="Mobile"><?php echo __($guid, 'Mobile') ?></option>
								<option value="Home"><?php echo __($guid, 'Home') ?></option>
								<option value="Work"><?php echo __($guid, 'Work') ?></option>
								<option value="Fax"><?php echo __($guid, 'Fax') ?></option>
								<option value="Pager"><?php echo __($guid, 'Pager') ?></option>
								<option value="Other"><?php echo __($guid, 'Other') ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Home Address') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Unit, Building, Street') ?></span>
						</td>
						<td class="right">
							<input name="homeAddress" id="homeAddress" maxlength=255 value="" type="text" class="standardWidth">
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
							<input name="homeAddressDistrict" id="homeAddressDistrict" maxlength=30 value="" type="text" class="standardWidth">
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
									echo "<option value='".$rowSelect['printable_name']."'>".htmlPrep(__($guid, $rowSelect['printable_name'])).'</option>';
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
							echo renderCustomFieldRow($connection2, $guid, $rowFields);
						}
					 }

					//SUPPORTING DOCUMENTS
					$staffApplicationFormRequiredDocuments = getSettingByScope($connection2, 'Staff', 'staffApplicationFormRequiredDocuments');
					$staffApplicationFormRequiredDocumentsText = getSettingByScope($connection2, 'Staff', 'staffApplicationFormRequiredDocumentsText');
					$staffApplicationFormRequiredDocumentsCompulsory = getSettingByScope($connection2, 'Staff', 'staffApplicationFormRequiredDocumentsCompulsory');
					if ($staffApplicationFormRequiredDocuments != '' and $staffApplicationFormRequiredDocuments != false) {
					?>
					<tr class='break'>
						<td colspan=2> 
							<h3><?php echo __($guid, 'Supporting Documents') ?></h3>
							<?php 
                            if ($staffApplicationFormRequiredDocumentsText != '' or $staffApplicationFormRequiredDocumentsCompulsory != '') {
                                echo '<p>';
                                echo $staffApplicationFormRequiredDocumentsText.' ';
                                if ($staffApplicationFormRequiredDocumentsCompulsory == 'Y') {
                                    echo __($guid, 'All documents must all be included before the application can be submitted.');
                                } else {
                                    echo __($guid, 'These documents are all required, but can be submitted separately to this form if preferred. Please note, however, that your application will be processed faster if the documents are included here.');
                                }
                                echo '</p>';
                            }
            				?>
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
					$count = 0;
					foreach ($staffApplicationFormRequiredDocumentsList as $document) {
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

					}
					?>
					<tr>
						<td colspan=2> 
							<?php echo getMaxUpload($guid); ?>
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
						echo '<p>';
						echo __($guid, 'Your nominated referees will be emailed a confidential form to complete on your behalf.');
						echo '</p>';
						echo '</td>';
						echo '</tr>'; ?>
						<tr>
							<td> 
								<b><?php echo __($guid, 'Referee 1') ?> *</b><br/>
								<span class="emphasis small"><?php echo __($guid, 'An email address for a referee at the applicant\'s current school.') ?></span>
							</td>
							<td class="right">
								<input name="referenceEmail1" id="referenceEmail1" maxlength=100 value="" type="text" class="standardWidth">
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
								<input name="referenceEmail2" id="referenceEmail2" maxlength=100 value="" type="text" class="standardWidth">
								<script type="text/javascript">
									var referenceEmail2=new LiveValidation('referenceEmail2');
									referenceEmail2.add(Validate.Presence);
									referenceEmail2.add(Validate.Email);
								</script>
							</td>
						</tr>
						<?php
					}

					//Get agreement
					$agreement = getSettingByScope($connection2, 'Staff', 'staffApplicationFormAgreement');
					if ($agreement != '') {
						echo "<tr class='break'>";
							echo '<td colspan=2>';
							echo '<h3>';
							echo __($guid, 'Agreement');
							echo '</h3>';
							echo '<p>';
							echo $agreement;
							echo '</p>';
							echo '</td>';
							echo '</tr>';
							echo '<tr>';
							echo '<td>';
							echo '<b>'.__($guid, 'Do you agree to the above?').'</b><br/>';
							echo '</td>';
							echo "<td class='right'>";
							echo "Yes <input type='checkbox' name='agreement' id='agreement'>"; ?>
							<script type="text/javascript">
								var agreement=new LiveValidation('agreement');
								agreement.add( Validate.Acceptance );
							</script>
							 <?php
						echo '</td>';
					echo '</tr>';
				}
				?>
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
        //Get postscrript
        $postscript = getSettingByScope($connection2, 'Staff', 'staffApplicationFormPostscript');
        if ($postscript != '') {
            echo '<h2>';
            echo __($guid, 'Further Information');
            echo '</h2>';
            echo "<p style='padding-bottom: 15px'>";
            echo $postscript;
            echo '</p>';
        }
    }
}
?>