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

@session_start() ;

if (isActionAccessible($guid, $connection2, "/modules/User Admin/user_manage_add.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/User Admin/user_manage.php'>" . __($guid, 'Manage Users') . "</a> > </div><div class='trailEnd'>" . __($guid, 'Add User') . "</div>" ;
	print "</div>" ;
	
	if (isset($_GET["addReturn"])) { $addReturn=$_GET["addReturn"] ; } else { $addReturn="" ; }
	$addReturnMessage="" ;
	$class="error" ;
	if (!($addReturn=="")) {
		if ($addReturn=="fail0") {
			$addReturnMessage=__($guid, "Your request failed because you do not have access to this action.") ;	
		}
		else if ($addReturn=="fail2") {
			$addReturnMessage=__($guid, "Your request failed due to a database error.") ;	
		}
		else if ($addReturn=="fail3") {
			$addReturnMessage=__($guid, "Your request failed because your inputs were invalid.") ;	
		}
		else if ($addReturn=="fail4") {
			$addReturnMessage=__($guid, "Your request failed because some inputs did not meet a requirement for uniqueness.") ;	
		}
		else if ($addReturn=="fail5") {
			$addReturnMessage=__($guid, "Your request failed because your passwords did not match.") ;	
		}
		else if ($addReturn=="fail6") {
			$addReturnMessage=__($guid, "Your request failed due to an attachment error.") ;	
		}
		else if ($addReturn=="fail7") {
			$addReturnMessage=__($guid, "Your request failed because your password to not meet the minimum requirements for strength.") ;	
		}
		else if ($addReturn=="success0") {
			$addReturnMessage=__($guid, "Your request was completed successfully. You can now add another record if you wish.") ;	
			$class="success" ;
		}
		else if ($addReturn=="success1") {
			$addReturnMessage=__($guid, "Your request was completed successfully, but one or more images were the wrong size and so were not saved. You can now add another record if you wish.") ;	
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $addReturnMessage;
		print "</div>" ;
	} 
	
	if ($_GET["search"]!="") {
		print "<div class='linkTop'>" ;
			print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/User Admin/user_manage.php&search=" . $_GET["search"] . "'>" . __($guid, 'Back to Search Results') . "</a>" ;
		print "</div>" ;
	}
	?>
	<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/user_manage_addProcess.php?search=" . $_GET["search"] ?>" enctype="multipart/form-data">
		<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
			<tr class='break'>
				<td colspan=2> 
					<h3><?php print __($guid, 'Basic Information') ?></h3>
				</td>
			</tr>
			<tr>
				<td style='width: 275px'> 
					<b><?php print __($guid, 'Title') ?></b><br/>
				</td>
				<td class="right">
					<select style="width: 302px" name="title">
						<option value=""></option>
						<option value="Ms."><?php print __($guid, 'Ms.') ?></option>
						<option value="Miss"><?php print __($guid, 'Miss') ?></option>
						<option value="Mr."><?php print __($guid, 'Mr.') ?></option>
						<option value="Mrs."><?php print __($guid, 'Mrs.') ?></option>
						<option value="Dr."><?php print __($guid, 'Dr.') ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print __($guid, 'Surname') ?> *</b><br/>
					<span style="font-size: 90%"><i><?php print __($guid, 'Family name as shown in ID documents.') ?></i></span>
				</td>
				<td class="right">
					<input name="surname" id="surname" maxlength=30 value="" type="text" style="width: 300px">
					<script type="text/javascript">
						var surname=new LiveValidation('surname');
						surname.add(Validate.Presence);
					</script>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print __($guid, 'First Name') ?>*</b><br/>
					<span style="font-size: 90%"><i><?php print __($guid, 'First name as shown in ID documents.') ?></i></span>
				</td>
				<td class="right">
					<input name="firstName" id="firstName" maxlength=30 value="" type="text" style="width: 300px">
					<script type="text/javascript">
						var firstName=new LiveValidation('firstName');
						firstName.add(Validate.Presence);
					</script>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print __($guid, 'Preferred Name') ?> *</b><br/>
					<span style="font-size: 90%"><i><?php print __($guid, 'Most common name, alias, nickname, etc.') ?></i></span>
				</td>
				<td class="right">
					<input name="preferredName" id="preferredName" maxlength=30 value="" type="text" style="width: 300px">
					<script type="text/javascript">
						var preferredName=new LiveValidation('preferredName');
						preferredName.add(Validate.Presence);
					</script>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print __($guid, 'Official Name') ?> *</b><br/>
					<span style="font-size: 90%"><i><?php print __($guid, 'Full name as shown in ID documents.') ?></i></span>
				</td>
				<td class="right">
					<input name="officialName" id="officialName" maxlength=150 value="" type="text" style="width: 300px">
					<script type="text/javascript">
						var officialName=new LiveValidation('officialName');
						officialName.add(Validate.Presence);
					</script>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print __($guid, 'Name In Characters') ?></b><br/>
					<span style="font-size: 90%"><i><?php print __($guid, 'Chinese or other character-based name.') ?></i></span>
				</td>
				<td class="right">
					<input name="nameInCharacters" id="nameInCharacters" maxlength=20 value="" type="text" style="width: 300px">
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print __($guid, 'Gender') ?> *</b><br/>
				</td>
				<td class="right">
					<select name="gender" id="gender" style="width: 302px">
						<option value="Please select..."><?php print __($guid, 'Please select...') ?></option>
						<option value="F"><?php print __($guid, 'Female') ?></option>
						<option value="M"><?php print __($guid, 'Male') ?></option>
						<option value="F"><?php print __($guid, 'Other') ?></option>
						<option value="M"><?php print __($guid, 'Unspecified') ?></option>
					</select>
					<script type="text/javascript">
						var gender=new LiveValidation('gender');
						gender.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print __($guid, 'Select something!') ?>"});
					</script>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print __($guid, 'Date of Birth') ?></b><br/>
					<span style="font-size: 90%"><i><?php print $_SESSION[$guid]["i18n"]["dateFormat"]  ?></i></span>
				</td>
				<td class="right">
					<input name="dob" id="dob" maxlength=10 value="" type="text" style="width: 300px">
					<script type="text/javascript">
						var dob=new LiveValidation('dob');
						dob.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
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
					<b><?php print __($guid, 'User Photo') ?></b><br/>
					<span style="font-size: 90%"><i><?php print __($guid, 'Displayed at 240px by 320px.') . "<br/>" . __($guid, 'Accepts images up to 360px by 480px.') . "<br/>" . __($guid, 'Accepts aspect ratio between 1:1.2 and 1:1.4.') ?></i></span>
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
					<h3><?php print __($guid, 'System Access') ?></h3>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print __($guid, 'Primary Role') ?> *</b><br/>
					<span style="font-size: 90%"><i><?php print __($guid, 'Controls what a user can do and see.') ?></i></span>
				</td>
				<td class="right">
					<select name="gibbonRoleIDPrimary" id="gibbonRoleIDPrimary" style="width: 302px">
						<?php
						print "<option value='Please select...'>" . __($guid, 'Please select...') . "</option>" ;
						try {
							$dataSelect=array(); 
							$sqlSelect="SELECT * FROM gibbonRole ORDER BY name" ;
							$resultSelect=$connection2->prepare($sqlSelect);
							$resultSelect->execute($dataSelect);
						}
						catch(PDOException $e) { }
						while ($rowSelect=$resultSelect->fetch()) {
							print "<option value='" . $rowSelect["gibbonRoleID"] . "'>" . htmlPrep(__($guid, $rowSelect["name"])) . "</option>" ;
						}
						?>				
					</select>
					<script type="text/javascript">
						var gibbonRoleIDPrimary=new LiveValidation('gibbonRoleIDPrimary');
						gibbonRoleIDPrimary.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print __($guid, 'Select something!') ?>"});
					</script>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print __($guid, 'Username') ?> *</b><br/>
					<span style="font-size: 90%"><i><?php print __($guid, 'Must be unique. System login name. Cannot be changed.') ?></i></span>
				</td>
				<td class="right">
					<input name="username" id="username" maxlength=20 value="" type="text" style="width: 300px">
					<?php
					$idList="" ;
					try {
						$dataSelect=array(); 
						$sqlSelect="SELECT username FROM gibbonPerson ORDER BY username" ;
						$resultSelect=$connection2->prepare($sqlSelect);
						$resultSelect->execute($dataSelect);
					}
					catch(PDOException $e) { }
					while ($rowSelect=$resultSelect->fetch()) {
						$idList.="'" . $rowSelect["username"]  . "'," ;
					}
					?>
					<script type="text/javascript">
						var username=new LiveValidation('username');
						username.add( Validate.Exclusion, { within: [<?php print $idList ;?>], failureMessage: "<?php print __($guid, 'Value already in use!') ?>", partialMatch: false, caseSensitive: false } );
						username.add(Validate.Presence);
					</script>
				</td>
			</tr>
			<tr>
				<td colspan=2>
					<?php
					$policy=getPasswordPolicy($connection2) ;
					if ($policy!=FALSE) {
						print "<div class='warning'>" ;
							print $policy ;
						print "</div>" ;
					}
					?>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print __($guid, 'Password') ?> *</b><br/>
					<span style="font-size: 90%"><i></i></span>
				</td>
				<td class="right">
					<input name="password" id="password" maxlength=30 value="" type="password" style="width: 300px">
					<script type="text/javascript">
						var password=new LiveValidation('password');
						password.add(Validate.Presence);
						<?php
						$alpha=getSettingByScope( $connection2, "System", "passwordPolicyAlpha" ) ;
						if ($alpha=="Y") {
							print "password.add( Validate.Format, { pattern: /.*(?=.*[a-z])(?=.*[A-Z]).*/, failureMessage: \"" . __($guid, 'Does not meet password policy.') . "\" } );" ;
						}
						$numeric=getSettingByScope( $connection2, "System", "passwordPolicyNumeric" ) ;
						if ($numeric=="Y") {
							print "password.add( Validate.Format, { pattern: /.*[0-9]/, failureMessage: \"" . __($guid, 'Does not meet password policy.') . "\" } );" ;
						}
						$punctuation=getSettingByScope( $connection2, "System", "passwordPolicyNonAlphaNumeric" ) ;
						if ($punctuation=="Y") {
							print "password.add( Validate.Format, { pattern: /[^a-zA-Z0-9]/, failureMessage: \"" . __($guid, 'Does not meet password policy.') . "\" } );" ;
						}
						$minLength=getSettingByScope( $connection2, "System", "passwordPolicyMinLength" ) ;
						if (is_numeric($minLength)) {
							print "password.add( Validate.Length, { minimum: " . $minLength . "} );" ;
						}
						?>
					</script>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print __($guid, 'Confirm Password') ?> *</b><br/>
					<span style="font-size: 90%"><i></i></span>
				</td>
				<td class="right">
					<input name="passwordConfirm" id="passwordConfirm" maxlength=20 value="" type="password" style="width: 300px">
					<script type="text/javascript">
						var passwordConfirm=new LiveValidation('passwordConfirm');
						passwordConfirm.add(Validate.Presence);
						passwordConfirm.add(Validate.Confirmation, { match: 'password' } );
					</script>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print __($guid, 'Status') ?> *</b><br/>
					<span style="font-size: 90%"><i><?php print __($guid, 'This determines visibility within the system.') ?></i></span>
				</td>
				<td class="right">
					<select style="width: 302px" name="status">
						<option value="Full"><?php print __($guid, 'Full') ?></option>
						<option value="Expected"><?php print __($guid, 'Expected') ?></option>
						<option value="Left"><?php print __($guid, 'Left') ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print __($guid, 'Can Login?') ?> *</b><br/>
					<span style="font-size: 90%"><i></i></span>
				</td>
				<td class="right">
					<select style="width: 302px" name="canLogin">
						<option value="Y"><?php print __($guid, 'Yes') ?></option>
						<option value="N"><?php print __($guid, 'No') ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print __($guid, 'Force Reset Password?') ?> *</b><br/>
					<span style="font-size: 90%"><i><?php print __($guid, 'User will be prompted on next login.') ?></i></span>
				</td>
				<td class="right">
					<select style="width: 302px" name="passwordForceReset">
						<option value="Y"><?php print __($guid, 'Yes') ?></option>
						<option value="N"><?php print __($guid, 'No') ?></option>
					</select>
				</td>
			</tr>
			
			<tr class='break'>
				<td colspan=2> 
					<h3><?php print __($guid, 'Contact Information') ?></h3>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print __($guid, 'Email') ?></b><br/>
				</td>
				<td class="right">
					<input name="email" id="email" maxlength=50 value="" type="text" style="width: 300px">
					<script type="text/javascript">
						var email=new LiveValidation('email');
						email.add(Validate.Email);
					</script>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print __($guid, 'Alternate Email') ?></b><br/>
					<span style="font-size: 90%"><i></i></span>
				</td>
				<td class="right">
					<input name="emailAlternate" id="emailAlternate" maxlength=50 value="" type="text" style="width: 300px">
					<script type="text/javascript">
						var emailAlternate=new LiveValidation('emailAlternate');
						emailAlternate.add(Validate.Email);
					</script>
				</td>
			</tr>
			<tr>
				<td colspan=2> 
					<div class='warning'>
						<?php print __($guid, 'Address information for an individual only needs to be set under the following conditions:') ?>
						<ol>
							<li><?php print __($guid, 'If the user is not in a family.') ?></li>
							<li><?php print __($guid, 'If the user\'s family does not have a home address set.') ?></li>
							<li><?php print __($guid, 'If the user needs an address in addition to their family\'s home address.') ?></li>
						</ol>
					</div>
				</td>
			</tr>
			<?php
			//Controls to hide address fields unless they are present, or box is checked
			?>
			<tr>
				<td> 
					<b><?php print __($guid, 'Enter Personal Address?') ?></b><br/>
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
					<b><?php print __($guid, 'Address 1') ?></b><br/>
					<span style="font-size: 90%"><i><?php print __($guid, 'Unit, Building, Street') ?></i></span>
				</td>
				<td class="right">
					<input name="address1" id="address1" maxlength=255 value="" type="text" style="width: 300px">
				</td>
			</tr>
			<tr class='address'>
				<td> 
					<b><?php print __($guid, 'Address 1 District') ?></b><br/>
					<span style="font-size: 90%"><i><?php print __($guid, 'County, State, District') ?></i></span>
				</td>
				<td class="right">
					<input name="address1District" id="address1District" maxlength=30 value="" type="text" style="width: 300px">
				</td>
				<script type="text/javascript">
					$(function() {
						var availableTags=[
							<?php
							try {
								$dataAuto=array(); 
								$sqlAuto="SELECT DISTINCT name FROM gibbonDistrict ORDER BY name" ;
								$resultAuto=$connection2->prepare($sqlAuto);
								$resultAuto->execute($dataAuto);
							}
							catch(PDOException $e) { }
							while ($rowAuto=$resultAuto->fetch()) {
								print "\"" . $rowAuto["name"] . "\", " ;
							}
							?>
						];
						$( "#address1District" ).autocomplete({source: availableTags});
					});
				</script>
			</tr>
			<tr class='address'>
				<td> 
					<b><?php print __($guid, 'Address 1 Country') ?></b><br/>
				</td>
				<td class="right">
					<select name="address1Country" id="address1Country" style="width: 302px">
						<?php
						print "<option value=''></option>" ;
						try {
							$dataSelect=array(); 
							$sqlSelect="SELECT printable_name FROM gibbonCountry ORDER BY printable_name" ;
							$resultSelect=$connection2->prepare($sqlSelect);
							$resultSelect->execute($dataSelect);
						}
						catch(PDOException $e) { }
						while ($rowSelect=$resultSelect->fetch()) {
							print "<option value='" . $rowSelect["printable_name"] . "'>" . htmlPrep(__($guid, $rowSelect["printable_name"])) . "</option>" ;
						}
						?>				
					</select>
				</td>
			</tr>
			<tr class='address'>
				<td> 
					<b><?php print __($guid, 'Address 2') ?></b><br/>
					<span style="font-size: 90%"><i><?php print __($guid, 'Unit, Building, Street') ?></i></span>
				</td>
				<td class="right">
					<input name="address2" id="address2" maxlength=255 value="" type="text" style="width: 300px">
				</td>
			</tr>
			<tr class='address'>
				<td> 
					<b><?php print __($guid, 'Address 2 District') ?></b><br/>
					<span style="font-size: 90%"><i><?php print __($guid, 'County, State, District') ?></i></span>
				</td>
				<td class="right">
					<input name="address2District" id="address2District" maxlength=30 value="" type="text" style="width: 300px">
				</td>
				<script type="text/javascript">
					$(function() {
						var availableTags=[
							<?php
							try {
								$dataAuto=array(); 
								$sqlAuto="SELECT DISTINCT name FROM gibbonDistrict ORDER BY name" ;
								$resultAuto=$connection2->prepare($sqlAuto);
								$resultAuto->execute($dataAuto);
							}
							catch(PDOException $e) { }
							while ($rowAuto=$resultAuto->fetch()) {
								print "\"" . $rowAuto["name"] . "\", " ;
							}
							?>
						];
						$( "#address2District" ).autocomplete({source: availableTags});
					});
				</script>
			</tr>
			<tr class='address'>
				<td> 
					<b><?php print __($guid, 'Address 2 Country') ?></b><br/>
				</td>
				<td class="right">
					<select name="address2Country" id="address2Country" style="width: 302px">
						<?php
						print "<option value=''></option>" ;
						try {
							$dataSelect=array(); 
							$sqlSelect="SELECT printable_name FROM gibbonCountry ORDER BY printable_name" ;
							$resultSelect=$connection2->prepare($sqlSelect);
							$resultSelect->execute($dataSelect);
						}
						catch(PDOException $e) { }
						while ($rowSelect=$resultSelect->fetch()) {
							print "<option value='" . $rowSelect["printable_name"] . "'>" . htmlPrep(__($guid, $rowSelect["printable_name"])) . "</option>" ;
						}
						?>				
					</select>
				</td>
			</tr>
			<?php
			for ($i=1; $i<5; $i++) {
				?>
				<tr>
					<td> 
						<b><?php print __($guid, 'Phone') ?> <?php print $i ?></b><br/>
						<span style="font-size: 90%"><i><?php print __($guid, 'Type, country code, number.') ?></i></span>
					</td>
					<td class="right">
						<input name="phone<?php print $i ?>" id="phone<?php print $i ?>" maxlength=20 value="" type="text" style="width: 160px">
						<select name="phone<?php print $i ?>CountryCode" id="phone<?php print $i ?>CountryCode" style="width: 60px">
							<?php
							print "<option value=''></option>" ;
							try {
								$dataSelect=array(); 
								$sqlSelect="SELECT * FROM gibbonCountry ORDER BY printable_name" ;
								$resultSelect=$connection2->prepare($sqlSelect);
								$resultSelect->execute($dataSelect);
							}
							catch(PDOException $e) { }
							while ($rowSelect=$resultSelect->fetch()) {
								print "<option value='" . $rowSelect["iddCountryCode"] . "'>" . htmlPrep($rowSelect["iddCountryCode"]) . " - " .  htmlPrep(__($guid, $rowSelect["printable_name"])) . "</option>" ;
							}
							?>				
						</select>
						<select style="width: 70px" name="phone<?php print $i ?>Type">
							<option value=""></option>
							<option value="Mobile"><?php print __($guid, 'Mobile') ?></option>
							<option value="Home"><?php print __($guid, 'Home') ?></option>
							<option value="Work"><?php print __($guid, 'Work') ?></option>
							<option value="Fax"><?php print __($guid, 'Fax') ?></option>
							<option value="Pager"><?php print __($guid, 'Pager') ?></option>
							<option value="Other"><?php print __($guid, 'Other') ?></option>
						</select>
						
					</td>
				</tr>
				<?php
			}
			?>
			<tr>
				<td> 
					<b><?php print __($guid, 'Website') ?></b><br/>
					<span style="font-size: 90%"><i><?php print __($guid, 'Include http://') ?></i></span>
				</td>
				<td class="right">
					<input name="website" id="website" maxlength=255 value="" type="text" style="width: 300px">
					<script type="text/javascript">
						var text=new LiveValidation('text');
						text.add( Validate.Format, { pattern: /(http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/, failureMessage: "Must start with http:// or https://" } );
					</script>	
				</td>
			</tr>
			
			
			<tr class='break'>
				<td colspan=2> 
					<h3><?php print __($guid, 'School Information') ?></h3>
				</td>
			</tr>
			<?php
				$dayTypeOptions=getSettingByScope($connection2, 'User Admin', 'dayTypeOptions') ;
				if ($dayTypeOptions!="") {
					?>
					<tr>
						<td> 
							<b><?php print __($guid, 'Day Type') ?></b><br/>
							<span style="font-size: 90%"><i><?php print getSettingByScope($connection2, 'User Admin', 'dayTypeText') ; ?></i></span>
						</td>
						<td class="right">
							<select name="dayType" id="dayType" style="width: 302px">
								<option value=''></option>
								<?php
								$dayTypes=explode(",", $dayTypeOptions) ;
								foreach ($dayTypes as $dayType) {
									print "<option value='" . trim($dayType) . "'>" . trim($dayType) . "</option>" ;
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
					<b><?php print __($guid, 'Last School') ?></b><br/>
				</td>
				<td class="right">
					<input name="lastSchool" id="lastSchool" maxlength=30 value="" type="text" style="width: 300px">
				</td>
				<script type="text/javascript">
					$(function() {
						var availableTags=[
							<?php
							try {
								$dataAuto=array(); 
								$sqlAuto="SELECT DISTINCT lastSchool FROM gibbonPerson ORDER BY lastSchool" ;
								$resultAuto=$connection2->prepare($sqlAuto);
								$resultAuto->execute($dataAuto);
							}
							catch(PDOException $e) { }
							while ($rowAuto=$resultAuto->fetch()) {
								print "\"" . $rowAuto["lastSchool"] . "\", " ;
							}
							?>
						];
						$( "#lastSchool" ).autocomplete({source: availableTags});
					});
				</script>
			</tr>
			<tr>
				<td> 
					<b><?php print __($guid, 'Start Date') ?></b><br/>
					<span style="font-size: 90%"><i><?php print __($guid, 'Users\'s first day at school.') ?><br/> Format <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?></i></span>
				</td>
				<td class="right">
					<input name="dateStart" id="dateStart" maxlength=10 value="" type="text" style="width: 300px">
					<script type="text/javascript">
						var dateStart=new LiveValidation('dateStart');
						dateStart.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
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
					<b><?php print __($guid, 'Class Of') ?></b><br/>
					<span style="font-size: 90%"><i><?php print __($guid, 'When is the student expected to graduate?') ?></i></span>
				</td>
				<td class="right">
					<select name="gibbonSchoolYearIDClassOf" id="gibbonSchoolYearIDClassOf" style="width: 302px">
						<?php
						print "<option value=''></option>" ;
						try {
							$dataSelect=array(); 
							$sqlSelect="SELECT * FROM gibbonSchoolYear ORDER BY sequenceNumber" ;
							$resultSelect=$connection2->prepare($sqlSelect);
							$resultSelect->execute($dataSelect);
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						while ($rowSelect=$resultSelect->fetch()) {
							print "<option value='" . $rowSelect["gibbonSchoolYearID"] . "'>" . htmlPrep($rowSelect["name"]) . "</option>" ;
						}
						?>				
					</select>
				</td>
			</tr>
			
			<tr class='break'>
				<td colspan=2> 
					<h3><?php print __($guid, 'Background Information') ?></h3>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print __($guid, 'First Language') ?></b><br/>
				</td>
				<td class="right">
					<select name="languageFirst" id="languageFirst" style="width: 302px">
						<?php
						print "<option value=''></option>" ;
						try {
							$dataSelect=array(); 
							$sqlSelect="SELECT name FROM gibbonLanguage ORDER BY name" ;
							$resultSelect=$connection2->prepare($sqlSelect);
							$resultSelect->execute($dataSelect);
						}
						catch(PDOException $e) { }
						while ($rowSelect=$resultSelect->fetch()) {
							print "<option value='" . $rowSelect["name"] . "'>" . htmlPrep(__($guid, $rowSelect["name"])) . "</option>" ;
						}
						?>				
					</select>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print __($guid, 'Second Language') ?></b><br/>
				</td>
				<td class="right">
					<select name="languageSecond" id="languageSecond" style="width: 302px">
						<?php
						print "<option value=''></option>" ;
						try {
							$dataSelect=array(); 
							$sqlSelect="SELECT name FROM gibbonLanguage ORDER BY name" ;
							$resultSelect=$connection2->prepare($sqlSelect);
							$resultSelect->execute($dataSelect);
						}
						catch(PDOException $e) { }
						while ($rowSelect=$resultSelect->fetch()) {
							print "<option value='" . $rowSelect["name"] . "'>" . htmlPrep(__($guid, $rowSelect["name"])) . "</option>" ;
						}
						?>				
					</select>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print __($guid, 'Third Language') ?></b><br/>
				</td>
				<td class="right">
					<select name="languageThird" id="languageThird" style="width: 302px">
						<?php
						print "<option value=''></option>" ;
						try {
							$dataSelect=array(); 
							$sqlSelect="SELECT name FROM gibbonLanguage ORDER BY name" ;
							$resultSelect=$connection2->prepare($sqlSelect);
							$resultSelect->execute($dataSelect);
						}
						catch(PDOException $e) { }
						while ($rowSelect=$resultSelect->fetch()) {
							print "<option value='" . $rowSelect["name"] . "'>" . htmlPrep(__($guid, $rowSelect["name"])) . "</option>" ;
						}
						?>				
					</select>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print __($guid, 'Country of Birth') ?></b><br/>
				</td>
				<td class="right">
					<select name="countryOfBirth" id="countryOfBirth" style="width: 302px">
						<?php
						print "<option value=''></option>" ;
						try {
							$dataSelect=array(); 
							$sqlSelect="SELECT printable_name FROM gibbonCountry ORDER BY printable_name" ;
							$resultSelect=$connection2->prepare($sqlSelect);
							$resultSelect->execute($dataSelect);
						}
						catch(PDOException $e) { }
						while ($rowSelect=$resultSelect->fetch()) {
							print "<option value='" . $rowSelect["printable_name"] . "'>" . htmlPrep(__($guid, $rowSelect["printable_name"])) . "</option>" ;
						}
						?>				
					</select>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print __($guid, 'Ethnicity') ?></b><br/>
				</td>
				<td class="right">
					<select name="ethnicity" id="ethnicity" style="width: 302px">
						<option value=""></option>
						<?php
						$ethnicities=explode(",", getSettingByScope($connection2, "User Admin", "ethnicity")) ;
						foreach ($ethnicities as $ethnicity) {
							print "<option value='" . trim($ethnicity) . "'>" . trim($ethnicity) . "</option>" ;
						}
						?>
					</select>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print __($guid, 'Religion') ?></b><br/>
				</td>
				<td class="right">
					<select name="religion" id="religion" style="width: 302px">
						<option value=""></option>
						<option value="Nonreligious/Agnostic/Atheist"><?php print __($guid, 'Nonreligious/Agnostic/Atheist') ?></option>
						<option value="Buddhism"><?php print __($guid, 'Buddhism') ?></option>
						<option value="Christianity"><?php print __($guid, 'Christianity') ?></option>
						<option value="Hinduism"><?php print __($guid, 'Hinduism') ?></option>
						<option value="Islam"><?php print __($guid, 'Islam') ?></option>
						<option value="Judaism"><?php print __($guid, 'Judaism') ?></option>
						<option value="Other"><?php print __($guid, 'Other') ?></option>	
					</select>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print __($guid, 'Citizenship 1') ?></b><br/>
				</td>
				<td class="right">
					<select name="citizenship1" id="countryOfBirth" style="width: 302px">
						<?php
						print "<option value=''></option>" ;
						$nationalityList=getSettingByScope($connection2, "User Admin", "nationality") ;
						if ($nationalityList=="") {
							try {
								$dataSelect=array(); 
								$sqlSelect="SELECT printable_name FROM gibbonCountry ORDER BY printable_name" ;
								$resultSelect=$connection2->prepare($sqlSelect);
								$resultSelect->execute($dataSelect);
							}
							catch(PDOException $e) { }
							while ($rowSelect=$resultSelect->fetch()) {
								print "<option value='" . $rowSelect["printable_name"] . "'>" . htmlPrep(__($guid, $rowSelect["printable_name"])) . "</option>" ;
							}
						}
						else {
							$nationalities=explode(",", $nationalityList) ;
							foreach ($nationalities as $nationality) {
								print "<option value='" . trim($nationality) . "'>" . trim($nationality) . "</option>" ;
							}
						}
						?>				
					</select>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print __($guid, 'Citizenship 1 Passport Number') ?></b><br/>
				</td>
				<td class="right">
					<input name="citizenship1Passport" id="citizenship1Passport" maxlength=30 value="" type="text" style="width: 300px">
				</td>
			</tr>
			
			<tr>
				<td> 
					<b><?php print __($guid, 'Citizenship 1 Passport Scan') ?></b><br/>
					<span style="font-size: 90%"><i><?php print __($guid, 'Less than 1440px by 900px') ?></i></span>
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
					<b><?php print __($guid, 'Citizenship 2') ?></b><br/>
				</td>
				<td class="right">
					<select name="citizenship2" id="countryOfBirth" style="width: 302px">
						<?php
						print "<option value=''></option>" ;
						$nationalityList=getSettingByScope($connection2, "User Admin", "nationality") ;
						if ($nationalityList=="") {
							try {
								$dataSelect=array(); 
								$sqlSelect="SELECT printable_name FROM gibbonCountry ORDER BY printable_name" ;
								$resultSelect=$connection2->prepare($sqlSelect);
								$resultSelect->execute($dataSelect);
							}
							catch(PDOException $e) { }
							while ($rowSelect=$resultSelect->fetch()) {
								print "<option value='" . $rowSelect["printable_name"] . "'>" . htmlPrep(__($guid, $rowSelect["printable_name"])) . "</option>" ;
							}
						}
						else {
							$nationalities=explode(",", $nationalityList) ;
							foreach ($nationalities as $nationality) {
								print "<option $selected value='" . trim($nationality) . "'>" . trim($nationality) . "</option>" ;
							}
						}
						?>					
					</select>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print __($guid, 'Citizenship 2 Passport Number') ?></b><br/>
				</td>
				<td class="right">
					<input name="citizenship2Passport" id="citizenship2Passport" maxlength=30 value="" type="text" style="width: 300px">
				</td>
			</tr>
			<tr>
				<td> 
					<?php
					if ($_SESSION[$guid]["country"]=="") {
						print "<b>" . __($guid, 'National ID Card Number') . "</b><br/>" ;
					}
					else {
						print "<b>" . $_SESSION[$guid]["country"] . " " . __($guid, 'ID Card Number') . "</b><br/>" ;
					}
					?>
				</td>
				<td class="right">
					<input name="nationalIDCardNumber" id="nationalIDCardNumber" maxlength=30 value="" type="text" style="width: 300px">
				</td>
			</tr>
			<tr>
				<td> 
					<?php
					if ($_SESSION[$guid]["country"]=="") {
						print "<b>" . __($guid, 'National ID Card Scan') . "</b><br/>" ;
					}
					else {
						print "<b>" . $_SESSION[$guid]["country"] . " " . __($guid, 'ID Card Scan') . "</b><br/>" ;
					}
					?>
					<span style="font-size: 90%"><i><?php print __($guid, 'Less than 1440px by 900px') ?></i></span>
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
					if ($_SESSION[$guid]["country"]=="") {
						print "<b>" . __($guid, 'Residency/Visa Type') . "</b><br/>" ;
					}
					else {
						print "<b>" . $_SESSION[$guid]["country"] . " " . __($guid, 'Residency/Visa Type') . "</b><br/>" ;
					}
					?>
				</td>
				<td class="right">
					<?php
					$residencyStatusList=getSettingByScope($connection2, "User Admin", "residencyStatus") ;
					if ($residencyStatusList=="") {
						print "<input name='residencyStatus' id='residencyStatus' maxlength=30 value='' type='text' style='width: 300px'>" ;
					}
					else {
						print "<select name='residencyStatus' id='residencyStatus' style='width: 302px'>" ;
							print "<option value=''></option>" ;
							$residencyStatuses=explode(",", $residencyStatusList) ;
							foreach ($residencyStatuses as $residencyStatus) {
								print "<option value='" . trim($residencyStatus) . "'>" . trim($residencyStatus) . "</option>" ;
							}
						print "</select>" ;
					}
					?>
				</td>
			</tr>
			<tr>
				<td> 
					<?php
					if ($_SESSION[$guid]["country"]=="") {
						print "<b>" . __($guid, 'Visa Expiry Date') . "</b><br/>" ;
					}
					else {
						print "<b>" . $_SESSION[$guid]["country"] . " " . __($guid, 'Visa Expiry Date') . "</b><br/>" ;
					}
					print "<span style='font-size: 90%'><i>Format " ; if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; } print ". " . __($guid, 'If relevant.') . "</i></span>" ;
					?>
				</td>
				<td class="right">
					<input name="visaExpiryDate" id="visaExpiryDate" maxlength=10 value="" type="text" style="width: 300px">
					<script type="text/javascript">
						var visaExpiryDate=new LiveValidation('visaExpiryDate');
						visaExpiryDate.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
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
					<h3><?php print __($guid, 'Employment') ?></h3>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print __($guid, 'Profession') ?></b><br/>
				</td>
				<td class="right">
					<input name="profession" id="profession" maxlength=30 value="" type="text" style="width: 300px">
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print __($guid, 'Employer') ?></b><br/>
				</td>
				<td class="right">
					<input name="employer" id="employer" maxlength=30 value="" type="text" style="width: 300px">
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print __($guid, 'Job Title') ?></b><br/>
				</td>
				<td class="right">
					<input name="jobTitle" id="jobTitle" maxlength=30 value="" type="text" style="width: 300px">
				</td>
			</tr>
			
			
			<tr class='break'>
				<td colspan=2> 
					<h3><?php print __($guid, 'Emergency Contacts') ?></h3>
				</td>
			</tr>
			<tr>
				<td colspan=2> 
					<?php print __($guid, 'These details are used when immediate family members (e.g. parent, spouse) cannot be reached first. Please try to avoid listing immediate family members.') ?> 
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print __($guid, 'Contact 1 Name') ?></b><br/>
				</td>
				<td class="right">
					<input name="emergency1Name" id="emergency1Name" maxlength=30 value="" type="text" style="width: 300px">
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print __($guid, 'Contact 1 Relationship') ?></b><br/>
				</td>
				<td class="right">
					<select name="emergency1Relationship" id="emergency1Relationship" style="width: 302px">
						<option></option>
						<option value="Parent"><?php print __($guid, 'Parent') ?></option>
						<option value="Spouse"><?php print __($guid, 'Spouse') ?></option>
						<option value="Offspring"><?php print __($guid, 'Offspring') ?></option>
						<option value="Friend"><?php print __($guid, 'Friend') ?></option>
						<option value="Doctor"><?php print __($guid, 'Doctor') ?></option>
						<option value="Other"><?php print __($guid, 'Other') ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print __($guid, 'Contact 1 Number 1') ?></b><br/>
				</td>
				<td class="right">
					<input name="emergency1Number1" id="emergency1Number1" maxlength=30 value="" type="text" style="width: 300px">
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print __($guid, 'Contact 1 Number 2') ?></b><br/>
				</td>
				<td class="right">
					<input name="emergency1Number2" id="emergency1Number2" maxlength=30 value="" type="text" style="width: 300px">
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print __($guid, 'Contact 2 Name') ?></b><br/>
				</td>
				<td class="right">
					<input name="emergency2Name" id="emergency2Name" maxlength=30 value="" type="text" style="width: 300px">
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print __($guid, 'Contact 2 Relationship') ?></b><br/>
				</td>
				<td class="right">
					<select name="emergency2Relationship" id="emergency2Relationship" style="width: 302px">
						<option></option>
						<option value="Parent"><?php print __($guid, 'Parent') ?></option>
						<option value="Spouse"><?php print __($guid, 'Spouse') ?></option>
						<option value="Offspring"><?php print __($guid, 'Offspring') ?></option>
						<option value="Friend"><?php print __($guid, 'Friend') ?></option>
						<option value="Doctor"><?php print __($guid, 'Doctor') ?></option>
						<option value="Other"><?php print __($guid, 'Other') ?></option>
					</select>	
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print __($guid, 'Contact 2 Number 1') ?></b><br/>
				</td>
				<td class="right">
					<input name="emergency2Number1" id="emergency2Number1" maxlength=30 value="" type="text" style="width: 300px">
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print __($guid, 'Contact 2 Number 2') ?></b><br/>
				</td>
				<td class="right">
					<input name="emergency2Number2" id="emergency2Number2" maxlength=30 value="" type="text" style="width: 300px">
				</td>
			</tr>
			
			<tr class='break'>
				<td colspan=2> 
					<h3><?php print __($guid, 'Miscellaneous') ?></h3>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print __($guid, 'House') ?></b><br/>
				</td>
				<td class="right">
					<select name="gibbonHouseID" id="gibbonHouseID" style="width: 302px">
						<?php
						print "<option value=''></option>" ;
						try {
							$dataSelect=array(); 
							$sqlSelect="SELECT gibbonHouseID, name FROM gibbonHouse ORDER BY name" ;
							$resultSelect=$connection2->prepare($sqlSelect);
							$resultSelect->execute($dataSelect);
						}
						catch(PDOException $e) { }
						while ($rowSelect=$resultSelect->fetch()) {
							print "<option value='" . $rowSelect["gibbonHouseID"] . "'>" . htmlPrep($rowSelect["name"]) . "</option>" ;
						}
						?>				
					</select>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print __($guid, 'Student ID') ?></b><br/>
					<span style="font-size: 90%"><i><?php print __($guid, 'Must be unique if set.') ?></i></span>
				</td>
				<td class="right">
					<input name="studentID" id="studentID" maxlength=10 value="" type="text" style="width: 300px">
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print __($guid, 'Transport') ?></b><br/>
					<span style="font-size: 90%"><i></i></span>
				</td>
				<td class="right">
					<input name="transport" id="transport" maxlength=255 value="" type="text" style="width: 300px">
				</td>
			</tr>
			<script type="text/javascript">
				$(function() {
					var availableTags=[
						<?php
						try {
							$dataAuto=array(); 
							$sqlAuto="SELECT DISTINCT transport FROM gibbonPerson ORDER BY transport" ;
							$resultAuto=$connection2->prepare($sqlAuto);
							$resultAuto->execute($dataAuto);
						}
						catch(PDOException $e) { }
						while ($rowAuto=$resultAuto->fetch()) {
							print "\"" . $rowAuto["transport"] . "\", " ;
						}
						?>
					];
					$( "#transport" ).autocomplete({source: availableTags});
				});
			</script>
			<tr>
				<td> 
					<b><?php print __($guid, 'Transport Notes') ?></b><br/>
					<span style="font-size: 90%"><i></i></span>
				</td>
				<td class="right">
					<textarea name="transportNotes" id="transportNotes" rows=4 value="" style="width: 300px"></textarea>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print __($guid, 'Locker Number') ?></b><br/>
					<span style="font-size: 90%"></span>
				</td>
				<td class="right">
					<input name="lockerNumber" id="lockerNumber" maxlength=20 value="" type="text" style="width: 300px">
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print __($guid, 'Vehicle Registration') ?></b><br/>
					<span style="font-size: 90%"></span>
				</td>
				<td class="right">
					<input name="vehicleRegistration" id="vehicleRegistration" maxlength=20 value="" type="text" style="width: 300px">
				</td>
			</tr>
			<?php
			$privacySetting=getSettingByScope( $connection2, "User Admin", "privacy" ) ;
			$privacyBlurb=getSettingByScope( $connection2, "User Admin", "privacyBlurb" ) ;
			$privacyOptions=getSettingByScope( $connection2, "User Admin", "privacyOptions" ) ;
			if ($privacySetting=="Y" AND $privacyBlurb!="" AND $privacyOptions!="") {
				?>
				<tr>
					<td> 
						<b><?php print __($guid, 'Privacy') ?> *</b><br/>
						<span style="font-size: 90%"><i><?php print htmlPrep($privacyBlurb) ?><br/>
						</i></span>
					</td>
					<td class="right">
						<?php
						$options=explode(",",$privacyOptions) ;
						foreach ($options AS $option) {
							print $option . " <input type='checkbox' name='privacyOptions[]' value='" . htmlPrep(trim($option)) . "'/><br/>" ;
						}
						?>
						
					</td>
				</tr>
				<?php
			}
			else {
				print "<input type=\"hidden\" name=\"privacy\" value=\"\">" ;
			}
			
			$studentAgreementOptions=getSettingByScope($connection2, "School Admin", "studentAgreementOptions") ;
			if ($studentAgreementOptions!="") {
				?>
				<tr>
					<td> 
						<b><?php print __($guid, 'Student Agreements') ?></b><br/>
						<span style="font-size: 90%"><i><?php print __($guid, 'Check to indicate that student has signed the relevant agreement.') ?><br/>
						</i></span>
					</td>
					<td class="right">
						<?php
						$agreements=explode(",",$studentAgreementOptions) ;
						foreach ($agreements AS $agreement) {
							print $agreement . " <input type='checkbox' name='studentAgreements[]' value='" . htmlPrep(trim($agreement)) . "'/><br/>" ;
						}
						?>
		
					</td>
				</tr>
				<?php
			}
			?>
			
			<tr>
				<td>
					<span style="font-size: 90%"><i>* <?php print __($guid, "denotes a required field") ; ?></i><br/>
					<?php
					print getMaxUpload(TRUE) ;				
					?>
					</span>
				</td>
				<td class="right">
					<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
					<input type="submit" value="<?php print __($guid, "Submit") ; ?>">
				</td>
			</tr>
		</table>
	</form>
	<?php
}
?>