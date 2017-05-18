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

if (isActionAccessible($guid, $connection2, '/modules/User Admin/user_manage_add.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/User Admin/user_manage.php'>".__($guid, 'Manage Users')."</a> > </div><div class='trailEnd'>".__($guid, 'Add User').'</div>';
    echo '</div>';

    $returns = array();
    $returns['error5'] = __($guid, 'Your request failed because your passwords did not match.');
    $returns['error6'] = __($guid, 'Your request failed due to an attachment error.');
    $returns['error7'] = __($guid, 'Your request failed because your password to not meet the minimum requirements for strength.');
    $returns['warning1'] = __($guid, 'Your request was completed successfully, but one or more images were the wrong size and so were not saved.');
    $editLink = '';
    if (isset($_GET['editID'])) {
        $editLink = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/User Admin/user_manage_edit.php&gibbonPersonID='.$_GET['editID'].'&search='.$_GET['search'];
    }
    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], $editLink, $returns);
    }

    if ($_GET['search'] != '') {
        echo "<div class='linkTop'>";
        echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/User Admin/user_manage.php&search='.$_GET['search']."'>".__($guid, 'Back to Search Results').'</a>';
        echo '</div>';
    }
    ?>
	<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/user_manage_addProcess.php?search='.$_GET['search'] ?>" enctype="multipart/form-data">
		<table class='smallIntBorder fullWidth' cellspacing='0'>
			<tr class='break'>
				<td colspan=2>
					<h3><?php echo __($guid, 'Basic Information') ?></h3>
				</td>
			</tr>
			<tr>
				<td style='width: 275px'>
					<b><?php echo __($guid, 'Title') ?></b><br/>
				</td>
				<td class="right">
					<select class="standardWidth" name="title">
						<option value=""></option>
						<option value="Ms."><?php echo __($guid, 'Ms.') ?></option>
						<option value="Miss"><?php echo __($guid, 'Miss') ?></option>
						<option value="Mr."><?php echo __($guid, 'Mr.') ?></option>
						<option value="Mrs."><?php echo __($guid, 'Mrs.') ?></option>
						<option value="Dr."><?php echo __($guid, 'Dr.') ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<td>
					<b><?php echo __($guid, 'Surname') ?> *</b><br/>
					<span class="emphasis small"><?php echo __($guid, 'Family name as shown in ID documents.') ?></span>
				</td>
				<td class="right">
					<input name="surname" id="surname" maxlength=60 value="" type="text" class="standardWidth">
					<script type="text/javascript">
						var surname=new LiveValidation('surname');
						surname.add(Validate.Presence);
					</script>
				</td>
			</tr>
			<tr>
				<td>
					<b><?php echo __($guid, 'First Name') ?>*</b><br/>
					<span class="emphasis small"><?php echo __($guid, 'First name as shown in ID documents.') ?></span>
				</td>
				<td class="right">
					<input name="firstName" id="firstName" maxlength=60 value="" type="text" class="standardWidth">
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
					<input name="preferredName" id="preferredName" maxlength=60 value="" type="text" class="standardWidth">
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
					<input name="officialName" id="officialName" maxlength=150 value="" type="text" class="standardWidth">
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
						<option value="Other"><?php echo __($guid, 'Other') ?></option>
						<option value="Unspecified"><?php echo __($guid, 'Unspecified') ?></option>
					</select>
					<script type="text/javascript">
						var gender=new LiveValidation('gender');
						gender.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
					</script>
				</td>
			</tr>
			<tr>
				<td>
					<b><?php echo __($guid, 'Date of Birth') ?></b><br/>
					<span class="emphasis small"><?php echo $_SESSION[$guid]['i18n']['dateFormat']  ?></span>
				</td>
				<td class="right">
					<input name="dob" id="dob" maxlength=10 value="" type="text" class="standardWidth">
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
					</script>
					 <script type="text/javascript">
						$(function() {
							$( "#dob" ).datepicker();
						});
					</script>
				</td>
			</tr>
			<tr>
				<td>
					<b><?php echo __($guid, 'User Photo') ?></b><br/>
					<span class="emphasis small"><?php echo __($guid, 'Displayed at 240px by 320px.').'<br/>'.__($guid, 'Accepts images up to 360px by 480px.').'<br/>'.__($guid, 'Accepts aspect ratio between 1:1.2 and 1:1.4.') ?></span>
				</td>
				<td class="right">
					<input type="file" name="file1" id="file1"><br/><br/>
					<script type="text/javascript">
						var file1=new LiveValidation('file1');
						file1.add( Validate.Inclusion, { within: ['gif','jpg','jpeg','png'], failureMessage: "Illegal file type!", partialMatch: true, caseSensitive: false } );
					</script>
				</td>
			</tr>


			<tr class='break'>
				<td colspan=2>
					<h3><?php echo __($guid, 'System Access') ?></h3>
				</td>
			</tr>
			<tr>
				<td>
					<b><?php echo __($guid, 'Primary Role') ?> *</b><br/>
					<span class="emphasis small"><?php echo __($guid, 'Controls what a user can do and see.') ?></span>
				</td>
				<td class="right">
					<select name="gibbonRoleIDPrimary" id="gibbonRoleIDPrimary" class="standardWidth">
						<?php
                        echo "<option value='Please select...'>".__($guid, 'Please select...').'</option>';
						try {
							$dataSelect = array();
							$sqlSelect = 'SELECT * FROM gibbonRole ORDER BY name';
							$resultSelect = $connection2->prepare($sqlSelect);
							$resultSelect->execute($dataSelect);
						} catch (PDOException $e) {
						}

						// Put together an array of this user's current roles
						$currentUserRoles = (is_array($_SESSION[$guid]['gibbonRoleIDAll'])) ? array_column($_SESSION[$guid]['gibbonRoleIDAll'], 0) : array();
						$currentUserRoles[] = $_SESSION[$guid]['gibbonRoleIDPrimary'];

						while ($rowSelect = $resultSelect->fetch()) {
							// Check for and remove restricted roles from this list
							if ($rowSelect['restriction'] == 'Admin Only') {
								if (!in_array('001', $currentUserRoles, true)) continue;
							} else if ($rowSelect['restriction'] == 'Same Role') {
								if (!in_array($rowSelect['gibbonRoleID'], $currentUserRoles, true) && !in_array('001', $currentUserRoles, true)) continue;
							}

							echo "<option value='".$rowSelect['gibbonRoleID']."'>".htmlPrep(__($guid, $rowSelect['name'])).'</option>';
						}
						?>
					</select>
					<script type="text/javascript">
						var gibbonRoleIDPrimary=new LiveValidation('gibbonRoleIDPrimary');
						gibbonRoleIDPrimary.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
					</script>
				</td>
			</tr>
			<tr>
				<td>
					<b><?php echo __($guid, 'Username') ?> *</b><br/>
					<span class="emphasis small"><?php echo __($guid, 'Must be unique. System login name. Cannot be changed.') ?></span>
				</td>
				<td class="right">
					<input name="username" id="username" maxlength=20 value="" type="text" class="standardWidth">
					<?php
                    $idList = '';
					try {
						$dataSelect = array();
						$sqlSelect = 'SELECT username FROM gibbonPerson ORDER BY username';
						$resultSelect = $connection2->prepare($sqlSelect);
						$resultSelect->execute($dataSelect);
					} catch (PDOException $e) {
					}
					while ($rowSelect = $resultSelect->fetch()) {
						$idList .= "'".addslashes($rowSelect['username'])."',";
					}
					$idList = substr($idList, 0, -1); ?>
					<script type="text/javascript">
						var username=new LiveValidation('username');
						username.add( Validate.Exclusion, { within: [<?php echo $idList; ?>], failureMessage: "<?php echo __($guid, 'Value already in use!') ?>", partialMatch: false, caseSensitive: false } );
						username.add(Validate.Presence);
					</script>
				</td>
			</tr>
			<tr>
				<td colspan=2>
					<?php
                    $policy = getPasswordPolicy($guid, $connection2);
					if ($policy != false) {
						echo "<div class='warning'>";
						echo $policy;
						echo '</div>';
					}
					?>
				</td>
			</tr>
			<tr>
				<td>
					<b><?php echo __($guid, 'Password') ?> *</b><br/>
					<span class="emphasis small"></span>
				</td>
				<td class="right">
					<input type='button' class="generatePassword" value="<?php echo __($guid, 'Generate Password') ?>"/>
					<input name="passwordNew" id="passwordNew" maxlength=30 value="" type="password" class="standardWidth"><br/>

					<script type="text/javascript">
						var passwordNew=new LiveValidation('passwordNew');
						passwordNew.add(Validate.Presence);
						<?php
                        $alpha = getSettingByScope($connection2, 'System', 'passwordPolicyAlpha');
						$numeric = getSettingByScope($connection2, 'System', 'passwordPolicyNumeric');
						$punctuation = getSettingByScope($connection2, 'System', 'passwordPolicyNonAlphaNumeric');
						$minLength = getSettingByScope($connection2, 'System', 'passwordPolicyMinLength');
						if ($alpha == 'Y') {
							echo 'passwordNew.add( Validate.Format, { pattern: /.*(?=.*[a-z])(?=.*[A-Z]).*/, failureMessage: "'.__($guid, 'Does not meet password policy.').'" } );';
						}
						if ($numeric == 'Y') {
							echo 'passwordNew.add( Validate.Format, { pattern: /.*[0-9]/, failureMessage: "'.__($guid, 'Does not meet password policy.').'" } );';
						}
						if ($punctuation == 'Y') {
							echo 'passwordNew.add( Validate.Format, { pattern: /[^a-zA-Z0-9]/, failureMessage: "'.__($guid, 'Does not meet password policy.').'" } );';
						}
						if (is_numeric($minLength)) {
							echo 'passwordNew.add( Validate.Length, { minimum: '.$minLength.'} );';
						}
						?>

						$(".generatePassword").click(function(){
							var chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789![]{}()%&*$#^<>~@|';
							var text = '';
							for(var i=0; i < <?php echo $minLength + 4 ?>; i++) {
								for(var i=0; i < <?php echo $minLength + 4 ?>; i++) {
									if (i==0) { text += chars.charAt(Math.floor(Math.random() * 26)); }
									else if (i==1) { text += chars.charAt(Math.floor(Math.random() * 26)+26); }
									else if (i==2) { text += chars.charAt(Math.floor(Math.random() * 10)+52); }
									else if (i==3) { text += chars.charAt(Math.floor(Math.random() * 19)+62); }
									else { text += chars.charAt(Math.floor(Math.random() * chars.length)); }
								}
							}
							$('input[name="passwordNew"]').val(text);
							$('input[name="passwordConfirm"]').val(text);
							alert('<?php echo __($guid, 'Copy this password if required:') ?>' + '\r\n\r\n' + text) ;
						});
					</script>
				</td>
			</tr>
			<tr>
				<td>
					<b><?php echo __($guid, 'Confirm Password') ?> *</b><br/>
					<span class="emphasis small"></span>
				</td>
				<td class="right">
					<input name="passwordConfirm" id="passwordConfirm" maxlength=30 value="" type="password" class="standardWidth">
					<script type="text/javascript">
						var passwordConfirm=new LiveValidation('passwordConfirm');
						passwordConfirm.add(Validate.Presence);
						passwordConfirm.add(Validate.Confirmation, { match: 'passwordNew' } );
					</script>
				</td>
			</tr>
			<tr>
				<td>
					<b><?php echo __($guid, 'Status') ?> *</b><br/>
					<span class="emphasis small"><?php echo __($guid, 'This determines visibility within the system.') ?></span>
				</td>
				<td class="right">
					<select class="standardWidth" name="status">
						<option value="Full"><?php echo __($guid, 'Full') ?></option>
						<option value="Expected"><?php echo __($guid, 'Expected') ?></option>
						<option value="Left"><?php echo __($guid, 'Left') ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<td>
					<b><?php echo __($guid, 'Can Login?') ?> *</b><br/>
					<span class="emphasis small"></span>
				</td>
				<td class="right">
					<select class="standardWidth" name="canLogin">
						<option value="Y"><?php echo __($guid, 'Yes') ?></option>
						<option value="N"><?php echo __($guid, 'No') ?></option>
						<option value="A"><?php echo __($guid, 'Activation Required') ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<td>
					<b><?php echo __($guid, 'Force Reset Password?') ?> *</b><br/>
					<span class="emphasis small"><?php echo __($guid, 'User will be prompted on next login.') ?></span>
				</td>
				<td class="right">
					<select class="standardWidth" name="passwordForceReset">
						<option value="Y"><?php echo __($guid, 'Yes') ?></option>
						<option value="N"><?php echo __($guid, 'No') ?></option>
					</select>
				</td>
			</tr>

			<tr class='break'>
				<td colspan=2>
					<h3><?php echo __($guid, 'Contact Information') ?></h3>
				</td>
			</tr>
			<tr>
				<td>
					<b><?php echo __($guid, 'Email') ?></b><br/>
				</td>
				<td class="right">
					<input name="email" id="email" maxlength=50 value="" type="text" class="standardWidth">
					<script type="text/javascript">
						var email=new LiveValidation('email');
						email.add(Validate.Email);
					</script>
				</td>
			</tr>
			<tr>
				<td>
					<b><?php echo __($guid, 'Alternate Email') ?></b><br/>
					<span class="emphasis small"></span>
				</td>
				<td class="right">
					<input name="emailAlternate" id="emailAlternate" maxlength=50 value="" type="text" class="standardWidth">
					<script type="text/javascript">
						var emailAlternate=new LiveValidation('emailAlternate');
						emailAlternate.add(Validate.Email);
					</script>
				</td>
			</tr>
			<tr>
				<td colspan=2>
					<div class='warning'>
						<?php echo __($guid, 'Address information for an individual only needs to be set under the following conditions:') ?>
						<ol>
							<li><?php echo __($guid, 'If the user is not in a family.') ?></li>
							<li><?php echo __($guid, 'If the user\'s family does not have a home address set.') ?></li>
							<li><?php echo __($guid, 'If the user needs an address in addition to their family\'s home address.') ?></li>
						</ol>
					</div>
				</td>
			</tr>
			<?php
            //Controls to hide address fields unless they are present, or box is checked
            ?>
			<tr>
				<td>
					<b><?php echo __($guid, 'Enter Personal Address?') ?></b><br/>
				</td>
				<td class='right' colspan=2>
					<script type="text/javascript">
						/* Advanced Options Control */
						$(document).ready(function(){
							$(".address").slideUp("fast");
							$("#showAddresses").click(function(){
								if ($('input[name=showAddresses]:checked').val()=="Yes" ) {
									$(".address").slideDown("fast", $(".address").css("display","table-row"));
								}
								else {
									$(".address").slideUp("fast");
									$("#address1").val("");
									$("#address1District").val("");
									$("#address1Country").val("");
									$("#address2").val("");
									$("#address2District").val("");
									$("#address2Country").val("");

								}
							 });
						});
					</script>
					<input id='showAddresses' name='showAddresses' type='checkbox' value='Yes'/>
				</td>
			</tr>
			<tr class='address'>
				<td>
					<b><?php echo __($guid, 'Address 1') ?></b><br/>
					<span class="emphasis small"><?php echo __($guid, 'Unit, Building, Street') ?></span>
				</td>
				<td class="right">
					<input name="address1" id="address1" maxlength=255 value="" type="text" class="standardWidth">
				</td>
			</tr>
			<tr class='address'>
				<td>
					<b><?php echo __($guid, 'Address 1 District') ?></b><br/>
					<span class="emphasis small"><?php echo __($guid, 'County, State, District') ?></span>
				</td>
				<td class="right">
					<input name="address1District" id="address1District" maxlength=30 value="" type="text" class="standardWidth">
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
						$( "#address1District" ).autocomplete({source: availableTags});
					});
				</script>
			</tr>
			<tr class='address'>
				<td>
					<b><?php echo __($guid, 'Address 1 Country') ?></b><br/>
				</td>
				<td class="right">
					<select name="address1Country" id="address1Country" class="standardWidth">
						<?php
                        echo "<option value=''></option>";
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
						?>
					</select>
				</td>
			</tr>
			<tr class='address'>
				<td>
					<b><?php echo __($guid, 'Address 2') ?></b><br/>
					<span class="emphasis small"><?php echo __($guid, 'Unit, Building, Street') ?></span>
				</td>
				<td class="right">
					<input name="address2" id="address2" maxlength=255 value="" type="text" class="standardWidth">
				</td>
			</tr>
			<tr class='address'>
				<td>
					<b><?php echo __($guid, 'Address 2 District') ?></b><br/>
					<span class="emphasis small"><?php echo __($guid, 'County, State, District') ?></span>
				</td>
				<td class="right">
					<input name="address2District" id="address2District" maxlength=30 value="" type="text" class="standardWidth">
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
						$( "#address2District" ).autocomplete({source: availableTags});
					});
				</script>
			</tr>
			<tr class='address'>
				<td>
					<b><?php echo __($guid, 'Address 2 Country') ?></b><br/>
				</td>
				<td class="right">
					<select name="address2Country" id="address2Country" class="standardWidth">
						<?php
                        echo "<option value=''></option>";
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
						?>
					</select>
				</td>
			</tr>
			<?php
            for ($i = 1; $i < 5; ++$i) {
                ?>
				<tr>
					<td>
						<b><?php echo __($guid, 'Phone') ?> <?php echo $i ?></b><br/>
						<span class="emphasis small"><?php echo __($guid, 'Type, country code, number.') ?></span>
					</td>
					<td class="right">
						<input name="phone<?php echo $i ?>" id="phone<?php echo $i ?>" maxlength=20 value="" type="text" style="width: 160px">
						<select name="phone<?php echo $i ?>CountryCode" id="phone<?php echo $i ?>CountryCode" style="width: 60px">
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
						<select style="width: 70px" name="phone<?php echo $i ?>Type">
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
				<?php

            }
   		 	?>
			<tr>
				<td>
					<b><?php echo __($guid, 'Website') ?></b><br/>
					<span class="emphasis small"><?php echo __($guid, 'Include http://') ?></span>
				</td>
				<td class="right">
					<input name="website" id="website" maxlength=255 value="" type="text" class="standardWidth">
					<script type="text/javascript">
						var website=new LiveValidation('website');
						website.add( Validate.Format, { pattern: /(http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/, failureMessage: "Must start with http:// or https://" } );
					</script>
				</td>
			</tr>


			<tr class='break'>
				<td colspan=2>
					<h3><?php echo __($guid, 'School Information') ?></h3>
				</td>
			</tr>
			<?php
                $dayTypeOptions = getSettingByScope($connection2, 'User Admin', 'dayTypeOptions');
				if ($dayTypeOptions != '') {
					?>
					<tr>
						<td>
							<b><?php echo __($guid, 'Day Type') ?></b><br/>
							<span class="emphasis small"><?php echo getSettingByScope($connection2, 'User Admin', 'dayTypeText'); ?></span>
						</td>
						<td class="right">
							<select name="dayType" id="dayType" class="standardWidth">
								<option value=''></option>
								<?php
                                $dayTypes = explode(',', $dayTypeOptions);
								foreach ($dayTypes as $dayType) {
									echo "<option value='".trim($dayType)."'>".trim($dayType).'</option>';
								}
								?>
							</select>
						</td>
					</tr>
					<?php

				}
				?>
			<tr>
				<td>
					<b><?php echo __($guid, 'Last School') ?></b><br/>
				</td>
				<td class="right">
					<input name="lastSchool" id="lastSchool" maxlength=30 value="" type="text" class="standardWidth">
				</td>
				<script type="text/javascript">
					$(function() {
						var availableTags=[
							<?php
                            try {
                                $dataAuto = array();
                                $sqlAuto = 'SELECT DISTINCT lastSchool FROM gibbonPerson ORDER BY lastSchool';
                                $resultAuto = $connection2->prepare($sqlAuto);
                                $resultAuto->execute($dataAuto);
                            } catch (PDOException $e) {
                            }
							while ($rowAuto = $resultAuto->fetch()) {
								echo '"'.$rowAuto['lastSchool'].'", ';
							}
							?>
						];
						$( "#lastSchool" ).autocomplete({source: availableTags});
					});
				</script>
			</tr>
			<tr>
				<td>
					<b><?php echo __($guid, 'Start Date') ?></b><br/>
					<span class="emphasis small"><?php echo __($guid, 'Users\'s first day at school.') ?><br/> <?php echo __($guid, 'Format:').' ';
					if ($_SESSION[$guid]['i18n']['dateFormat'] == '') {
						echo 'dd/mm/yyyy';
					} else {
						echo $_SESSION[$guid]['i18n']['dateFormat'];
					}
					?></span>
				</td>
				<td class="right">
					<input name="dateStart" id="dateStart" maxlength=10 value="" type="text" class="standardWidth">
					<script type="text/javascript">
						var dateStart=new LiveValidation('dateStart');
						dateStart.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]['i18n']['dateFormatRegEx'] == '') {
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
							$( "#dateStart" ).datepicker();
						});
					</script>
				</td>
			</tr>
			<tr>
				<td>
					<b><?php echo __($guid, 'Class Of') ?></b><br/>
					<span class="emphasis small"><?php echo __($guid, 'When is the student expected to graduate?') ?></span>
				</td>
				<td class="right">
					<select name="gibbonSchoolYearIDClassOf" id="gibbonSchoolYearIDClassOf" class="standardWidth">
						<?php
                        echo "<option value=''></option>";
						try {
							$dataSelect = array();
							$sqlSelect = 'SELECT * FROM gibbonSchoolYear ORDER BY sequenceNumber';
							$resultSelect = $connection2->prepare($sqlSelect);
							$resultSelect->execute($dataSelect);
						} catch (PDOException $e) {
							echo "<div class='error'>".$e->getMessage().'</div>';
						}
						while ($rowSelect = $resultSelect->fetch()) {
							echo "<option value='".$rowSelect['gibbonSchoolYearID']."'>".htmlPrep($rowSelect['name']).'</option>';
						}
						?>
					</select>
				</td>
			</tr>

			<tr class='break'>
				<td colspan=2>
					<h3><?php echo __($guid, 'Background Information') ?></h3>
				</td>
			</tr>
			<tr>
				<td>
					<b><?php echo __($guid, 'First Language') ?></b><br/>
				</td>
				<td class="right">
					<select name="languageFirst" id="languageFirst" class="standardWidth">
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
                        echo "<option value=''></option>";
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
						?>
					</select>
				</td>
			</tr>
            <tr>
                <td>
                    <b><?php echo __($guid, 'Birth Certificate Scan') ?></b><br/>
                    <span class="emphasis small"><?php echo __($guid, 'Less than 1440px by 900px') ?></span>
                </td>
                <td class="right">
                    <input type="file" name="birthCertificateScan" id="birthCertificateScan"><br/><br/>
                    <script type="text/javascript">
                        var birthCertificateScan=new LiveValidation('birthCertificateScan');
                        birthCertificateScan.add( Validate.Inclusion, { within: ['gif','jpg','jpeg','png'], failureMessage: "Illegal file type!", partialMatch: true, caseSensitive: false } );
                    </script>
                </td>
            </tr>
			<tr>
				<td>
					<b><?php echo __($guid, 'Ethnicity') ?></b><br/>
				</td>
				<td class="right">
					<select name="ethnicity" id="ethnicity" class="standardWidth">
						<option value=""></option>
						<?php
                        $ethnicities = explode(',', getSettingByScope($connection2, 'User Admin', 'ethnicity'));
						foreach ($ethnicities as $ethnicity) {
							echo "<option value='".trim($ethnicity)."'>".trim($ethnicity).'</option>';
						}
						?>
					</select>
				</td>
			</tr>
			<tr>
				<td>
					<b><?php echo __($guid, 'Religion') ?></b><br/>
				</td>
				<td class="right">
					<select name="religion" id="religion" class="standardWidth">
						<option value=""></option>
						<?php
                        $religions = explode(',', getSettingByScope($connection2, 'User Admin', 'religions'));
						foreach ($religions as $religion) {
							echo "<option value='".trim($religion)."'>".trim($religion).'</option>';
						}
						?>
					</select>
				</td>
			</tr>
			<tr>
				<td>
					<b><?php echo __($guid, 'Citizenship 1') ?></b><br/>
				</td>
				<td class="right">
					<select name="citizenship1" id="countryOfBirth" class="standardWidth">
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
					<b><?php echo __($guid, 'Citizenship 1 Passport Number') ?></b><br/>
				</td>
				<td class="right">
					<input name="citizenship1Passport" id="citizenship1Passport" maxlength=30 value="" type="text" class="standardWidth">
				</td>
			</tr>
			<tr>
				<td>
					<b><?php echo __($guid, 'Citizenship 1 Passport Scan') ?></b><br/>
					<span class="emphasis small"><?php echo __($guid, 'Less than 1440px by 900px') ?></span>
				</td>
				<td class="right">
					<input type="file" name="citizenship1PassportScan" id="citizenship1PassportScan"><br/><br/>
					<script type="text/javascript">
						var citizenship1PassportScan=new LiveValidation('citizenship1PassportScan');
						citizenship1PassportScan.add( Validate.Inclusion, { within: ['gif','jpg','jpeg','png'], failureMessage: "Illegal file type!", partialMatch: true, caseSensitive: false } );
					</script>
				</td>
			</tr>
			<tr>
				<td>
					<b><?php echo __($guid, 'Citizenship 2') ?></b><br/>
				</td>
				<td class="right">
					<select name="citizenship2" id="countryOfBirth" class="standardWidth">
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
								echo "<option $selected value='".trim($nationality)."'>".trim($nationality).'</option>';
							}
						}
						?>
					</select>
				</td>
			</tr>
			<tr>
				<td>
					<b><?php echo __($guid, 'Citizenship 2 Passport Number') ?></b><br/>
				</td>
				<td class="right">
					<input name="citizenship2Passport" id="citizenship2Passport" maxlength=30 value="" type="text" class="standardWidth">
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
                        echo '<b>'.__($guid, 'National ID Card Scan').'</b><br/>';
                    } else {
                        echo '<b>'.$_SESSION[$guid]['country'].' '.__($guid, 'ID Card Scan').'</b><br/>';
                    }
   				 	?>
					<span class="emphasis small"><?php echo __($guid, 'Less than 1440px by 900px') ?></span>
				</td>
				<td class="right">
					<input type="file" name="nationalIDCardScan" id="nationalIDCardScan"><br/><br/>
					<script type="text/javascript">
						var nationalIDCardScan=new LiveValidation('nationalIDCardScan');
						nationalIDCardScan.add( Validate.Inclusion, { within: ['gif','jpg','jpeg','png'], failureMessage: "Illegal file type!", partialMatch: true, caseSensitive: false } );
					</script>
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
					echo "<span style='font-size: 90%'><i>Format ";
					if ($_SESSION[$guid]['i18n']['dateFormat'] == '') {
						echo 'dd/mm/yyyy';
					} else {
						echo $_SESSION[$guid]['i18n']['dateFormat'];
					}
					echo '. '.__($guid, 'If relevant.').'</span>';?>
				</td>
				<td class="right">
					<input name="visaExpiryDate" id="visaExpiryDate" maxlength=10 value="" type="text" class="standardWidth">
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
					<h3><?php echo __($guid, 'Employment') ?></h3>
				</td>
			</tr>
			<tr>
				<td>
					<b><?php echo __($guid, 'Profession') ?></b><br/>
				</td>
				<td class="right">
					<input name="profession" id="profession" maxlength=150 value="" type="text" class="standardWidth">
				</td>
			</tr>
			<tr>
				<td>
					<b><?php echo __($guid, 'Employer') ?></b><br/>
				</td>
				<td class="right">
					<input name="employer" id="employer" maxlength=150 value="" type="text" class="standardWidth">
				</td>
			</tr>
			<tr>
				<td>
					<b><?php echo __($guid, 'Job Title') ?></b><br/>
				</td>
				<td class="right">
					<input name="jobTitle" id="jobTitle" maxlength=150 value="" type="text" class="standardWidth">
				</td>
			</tr>


			<tr class='break'>
				<td colspan=2>
					<h3><?php echo __($guid, 'Emergency Contacts') ?></h3>
				</td>
			</tr>
			<tr>
				<td colspan=2>
					<?php echo __($guid, 'These details are used when immediate family members (e.g. parent, spouse) cannot be reached first. Please try to avoid listing immediate family members.') ?>
				</td>
			</tr>
			<tr>
				<td>
					<b><?php echo __($guid, 'Contact 1 Name') ?></b><br/>
				</td>
				<td class="right">
					<input name="emergency1Name" id="emergency1Name" maxlength=90 value="" type="text" class="standardWidth">
				</td>
			</tr>
			<tr>
				<td>
					<b><?php echo __($guid, 'Contact 1 Relationship') ?></b><br/>
				</td>
				<td class="right">
					<select name="emergency1Relationship" id="emergency1Relationship" class="standardWidth">
						<option></option>
						<option value="Parent"><?php echo __($guid, 'Parent') ?></option>
						<option value="Spouse"><?php echo __($guid, 'Spouse') ?></option>
						<option value="Offspring"><?php echo __($guid, 'Offspring') ?></option>
						<option value="Friend"><?php echo __($guid, 'Friend') ?></option>
						<option value="Doctor"><?php echo __($guid, 'Doctor') ?></option>
						<option value="Other"><?php echo __($guid, 'Other') ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<td>
					<b><?php echo __($guid, 'Contact 1 Number 1') ?></b><br/>
				</td>
				<td class="right">
					<input name="emergency1Number1" id="emergency1Number1" maxlength=30 value="" type="text" class="standardWidth">
				</td>
			</tr>
			<tr>
				<td>
					<b><?php echo __($guid, 'Contact 1 Number 2') ?></b><br/>
				</td>
				<td class="right">
					<input name="emergency1Number2" id="emergency1Number2" maxlength=30 value="" type="text" class="standardWidth">
				</td>
			</tr>
			<tr>
				<td>
					<b><?php echo __($guid, 'Contact 2 Name') ?></b><br/>
				</td>
				<td class="right">
					<input name="emergency2Name" id="emergency2Name" maxlength=90 value="" type="text" class="standardWidth">
				</td>
			</tr>
			<tr>
				<td>
					<b><?php echo __($guid, 'Contact 2 Relationship') ?></b><br/>
				</td>
				<td class="right">
					<select name="emergency2Relationship" id="emergency2Relationship" class="standardWidth">
						<option></option>
						<option value="Parent"><?php echo __($guid, 'Parent') ?></option>
						<option value="Spouse"><?php echo __($guid, 'Spouse') ?></option>
						<option value="Offspring"><?php echo __($guid, 'Offspring') ?></option>
						<option value="Friend"><?php echo __($guid, 'Friend') ?></option>
						<option value="Doctor"><?php echo __($guid, 'Doctor') ?></option>
						<option value="Other"><?php echo __($guid, 'Other') ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<td>
					<b><?php echo __($guid, 'Contact 2 Number 1') ?></b><br/>
				</td>
				<td class="right">
					<input name="emergency2Number1" id="emergency2Number1" maxlength=30 value="" type="text" class="standardWidth">
				</td>
			</tr>
			<tr>
				<td>
					<b><?php echo __($guid, 'Contact 2 Number 2') ?></b><br/>
				</td>
				<td class="right">
					<input name="emergency2Number2" id="emergency2Number2" maxlength=30 value="" type="text" class="standardWidth">
				</td>
			</tr>

			<tr class='break'>
				<td colspan=2>
					<h3><?php echo __($guid, 'Miscellaneous') ?></h3>
				</td>
			</tr>
			<tr>
				<td>
					<b><?php echo __($guid, 'House') ?></b><br/>
				</td>
				<td class="right">
					<select name="gibbonHouseID" id="gibbonHouseID" class="standardWidth">
						<?php
                        echo "<option value=''></option>";
						try {
							$dataSelect = array();
							$sqlSelect = 'SELECT gibbonHouseID, name FROM gibbonHouse ORDER BY name';
							$resultSelect = $connection2->prepare($sqlSelect);
							$resultSelect->execute($dataSelect);
						} catch (PDOException $e) {
						}
						while ($rowSelect = $resultSelect->fetch()) {
							echo "<option value='".$rowSelect['gibbonHouseID']."'>".htmlPrep($rowSelect['name']).'</option>';
						}
						?>
					</select>
				</td>
			</tr>
			<tr>
				<td>
					<b><?php echo __($guid, 'Student ID') ?></b><br/>
					<span class="emphasis small"><?php echo __($guid, 'Must be unique if set.') ?></span>
				</td>
				<td class="right">
					<input name="studentID" id="studentID" maxlength=10 value="" type="text" class="standardWidth">
				</td>
			</tr>
			<tr>
				<td>
					<b><?php echo __($guid, 'Transport') ?></b><br/>
					<span class="emphasis small"></span>
				</td>
				<td class="right">
					<input name="transport" id="transport" maxlength=255 value="" type="text" class="standardWidth">
				</td>
			</tr>
			<script type="text/javascript">
				$(function() {
					var availableTags=[
						<?php
                        try {
                            $dataAuto = array();
                            $sqlAuto = 'SELECT DISTINCT transport FROM gibbonPerson ORDER BY transport';
                            $resultAuto = $connection2->prepare($sqlAuto);
                            $resultAuto->execute($dataAuto);
                        } catch (PDOException $e) {
                        }
						while ($rowAuto = $resultAuto->fetch()) {
							echo '"'.$rowAuto['transport'].'", ';
						}
						?>
					];
					$( "#transport" ).autocomplete({source: availableTags});
				});
			</script>
			<tr>
				<td>
					<b><?php echo __($guid, 'Transport Notes') ?></b><br/>
					<span class="emphasis small"></span>
				</td>
				<td class="right">
					<textarea name="transportNotes" id="transportNotes" rows=4 value="" class="standardWidth"></textarea>
				</td>
			</tr>
			<tr>
				<td>
					<b><?php echo __($guid, 'Locker Number') ?></b><br/>
					<span style="font-size: 90%"></span>
				</td>
				<td class="right">
					<input name="lockerNumber" id="lockerNumber" maxlength=20 value="" type="text" class="standardWidth">
				</td>
			</tr>
			<tr>
				<td>
					<b><?php echo __($guid, 'Vehicle Registration') ?></b><br/>
					<span style="font-size: 90%"></span>
				</td>
				<td class="right">
					<input name="vehicleRegistration" id="vehicleRegistration" maxlength=20 value="" type="text" class="standardWidth">
				</td>
			</tr>
			<?php
            $privacySetting = getSettingByScope($connection2, 'User Admin', 'privacy');
			$privacyBlurb = getSettingByScope($connection2, 'User Admin', 'privacyBlurb');
			$privacyOptions = getSettingByScope($connection2, 'User Admin', 'privacyOptions');
			if ($privacySetting == 'Y' and $privacyBlurb != '' and $privacyOptions != '') {
				?>
				<tr>
					<td>
						<b><?php echo __($guid, 'Privacy') ?> *</b><br/>
						<span class="emphasis small"><?php echo htmlPrep($privacyBlurb) ?><br/>
						</span>
					</td>
					<td class="right">
						<?php
                        $options = explode(',', $privacyOptions);
						foreach ($options as $option) {
							echo $option." <input type='checkbox' name='privacyOptions[]' value='".htmlPrep(trim($option))."'/><br/>";
						}
						?>

					</td>
				</tr>
				<?php

    } else {
        echo '<input type="hidden" name="privacy" value="">';
    }

    $studentAgreementOptions = getSettingByScope($connection2, 'School Admin', 'studentAgreementOptions');
    if ($studentAgreementOptions != '') {
        ?>
				<tr>
					<td>
						<b><?php echo __($guid, 'Student Agreements') ?></b><br/>
						<span class="emphasis small"><?php echo __($guid, 'Check to indicate that student has signed the relevant agreement.') ?><br/>
						</span>
					</td>
					<td class="right">
						<?php
                        $agreements = explode(',', $studentAgreementOptions);
						foreach ($agreements as $agreement) {
							echo $agreement." <input type='checkbox' name='studentAgreements[]' value='".htmlPrep(trim($agreement))."'/><br/>";
						}
						?>

					</td>
				</tr>
				<?php

			}
			?>

			<tr>
				<td>
					<span class="emphasis small">* <?php echo __($guid, 'denotes a required field'); ?></i><br/>
					<?php
                    echo getMaxUpload($guid, true); ?>
					</span>
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
?>
