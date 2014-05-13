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

$proceed=FALSE ;
$public=FALSE ;

if (isset($_SESSION[$guid]["username"])==FALSE) {
	$public=TRUE ;
	
	//Get public access
	$publicApplications=getSettingByScope($connection2, 'Application Form', 'publicApplications') ;
	if ($publicApplications=="Y") {
		$proceed=TRUE ;
	}
}
else {
	if (isActionAccessible($guid, $connection2, "/modules/Application Form/applicationForm.php")!=FALSE) {
		$proceed=TRUE ;
	}
}

//Set gibbonPersonID of the person completing the application
$gibbonPersonID=NULL ;
if (isset($_SESSION[$guid]["gibbonPersonID"])) {
	$gibbonPersonID=$_SESSION[$guid]["gibbonPersonID"] ;
}


if ($proceed==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	if (isset($_SESSION[$guid]["username"])) {
		print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > </div><div class='trailEnd'>" . $_SESSION[$guid]["organisationNameShort"] . " " . _('Application Form') . "</div>" ;
	}
	else {
		print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > </div><div class='trailEnd'>" . $_SESSION[$guid]["organisationNameShort"] . " " . _('Application Form') . "</div>" ;
	}
	print "</div>" ;
	
	//Get intro
	$intro=getSettingByScope($connection2, 'Application Form', 'introduction') ;
	if ($intro!="") {
		print "<p>" ;
			print $intro ;
		print "</p>" ;
	}
	
	if (isset($_SESSION[$guid]["username"])==false) {
		if ($intro!="") {
			print "<br/><br/>" ;
		}
		print "<p style='font-weight: bold; text-decoration: none; color: #c00'><i><u>" . sprintf(_('If you have an %1$s %2$s account, please log in now to prevent creation of duplicate data about you! Once logged in, you can find the form under People > Data in the main menu.'), $_SESSION[$guid]["organisationNameShort"], $_SESSION[$guid]["systemName"]) . "</u></i> " . sprintf(_('If you do not have an %1$s %2$s account, please use the form below.'), $_SESSION[$guid]["organisationNameShort"], $_SESSION[$guid]["systemName"]) . "</p>" ;
	}
	
	if (isset($_GET["addReturn"])) { $addReturn=$_GET["addReturn"] ; } else { $addReturn="" ; }
	$addReturnMessage="" ;
	$class="error" ;
	if (!($addReturn=="")) {
		if ($addReturn=="fail0") {
			$addReturnMessage=_("Your request failed because you do not have access to this action.") ;	
		}
		else if ($addReturn=="fail2") {
			$addReturnMessage=_("Your request failed due to a database error.") ;	
		}
		else if ($addReturn=="fail3") {
			$addReturnMessage=_("Your request failed because your inputs were invalid.") ;	
		}
		else if ($addReturn=="fail4") {
			$addReturnMessage=_("Your request failed because your inputs were invalid.") ;	
		}
		else if ($addReturn=="success0" OR $addReturn=="success1" OR $addReturn=="success2" ) {
			if ($addReturn=="success0") {
				$addReturnMessage=_("Your application was successfully submitted. Our admissions team will review your application and be in touch in due course.") ;
			}
			else if ($addReturn=="success1") {
				$addReturnMessage=_("Your application was successfully submitted and payment has been made to your credit card. Our admissions team will review your application and be in touch in due course.") ;
			}
			else if ($addReturn=="success2") {
				$addReturnMessage=_("Your application was successfully submitted, but payment could not be made to your credit card. Our admissions team will review your application and be in touch in due course.") ;
			}
			else if ($addReturn=="success3") {
				$addReturnMessage=_("Your application was successfully submitted, payment has been made to your credit card, but there has been an error recording your payment. Please print this screen and contact the school ASAP. Our admissions team will review your application and be in touch in due course.") ;
			}
			if ($_GET["id"]!="") {
				$addReturnMessage=$addReturnMessage . "<br/><br/>" . _('If you need to contact the school in reference to this application, please quote the following number:') . " <b><u>" . $_GET["id"] . "</b></u>." ;
			}
			if ($_SESSION[$guid]["organisationAdmissionsName"]!="" AND $_SESSION[$guid]["organisationAdmissionsEmail"]!="") {
				$addReturnMessage=$addReturnMessage . "<br/><br/>Please contact <a href='mailto:" . $_SESSION[$guid]["organisationAdmissionsEmail"] . "'>" . $_SESSION[$guid]["organisationAdmissionsName"] . "</a> if you have any questions, comments or complaints." ;	
			}
			
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $addReturnMessage;
		print "</div>" ;
	} 
	
	$currency=getSettingByScope($connection2, "System", "currency") ;
	$applicationFee=getSettingByScope($connection2, "Application Form", "applicationFee") ;
	$enablePayments=getSettingByScope($connection2, "System", "enablePayments") ;
	$paypalAPIUsername=getSettingByScope($connection2, "System", "paypalAPIUsername") ;
	$paypalAPIPassword=getSettingByScope($connection2, "System", "paypalAPIPassword") ;
	$paypalAPISignature=getSettingByScope($connection2, "System", "paypalAPISignature") ;
	
	if ($applicationFee>0 AND is_numeric($applicationFee)) {
		print "<div class='warning'>" ;
			print _("Please note that there is an application fee of:") . " <b><u>" . $currency . $applicationFee . "</u></b>." ;
			if ($enablePayments=="Y" AND $paypalAPIUsername!="" AND $paypalAPIPassword!="" AND $paypalAPISignature!="") {
				print " " . _('Payment must be made by credit card, using our secure PayPal payment gateway. When you press Submit at the end of this form, you will be directed to PayPal in order to make payment. During this process we do not see or store your credit card details.') ;
			}
		print "</div>" ;
	}
	
	?>
	
	<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/applicationFormProcess.php" ?>" enctype="multipart/form-data">
		<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
			<tr class='break'>
				<td colspan=2> 
					<h3><?php print _('Student') ?></h3>
				</td>
			</tr>
			
			<tr>
				<td colspan=2> 
					<h4><?php print _('Student Personal Data') ?></h4>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print _('Surname') ?> *</b><br/>
					<span style="font-size: 90%"><i><?php print _('Family name as shown in ID documents.') ?></i></span>
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
					<b><?php print _('First Name') ?> *</b><br/>
					<span style="font-size: 90%"><i><?php print _('First name as shown in ID documents.') ?></i></span>
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
					<b><?php print _('Preferred Name') ?> *</b><br/>
					<span style="font-size: 90%"><i><?php print _('Most common name, alias, nickname, etc.') ?></i></span>
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
					<b><?php print _('Official Name') ?> *</b><br/>
					<span style="font-size: 90%"><i><?php print _('Full name as shown in ID documents.') ?></i></span>
				</td>
				<td class="right">
					<input title='Please enter full name as shown in ID documents' name="officialName" id="officialName" maxlength=150 value="" type="text" style="width: 300px">
					<script type="text/javascript">
						var officialName=new LiveValidation('officialName');
						officialName.add(Validate.Presence);
					 </script>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print _('Name In Characters') ?></b><br/>
					<span style="font-size: 90%"><i><?php print _('Chinese or other character-based name.') ?></i></span>
				</td>
				<td class="right">
					<input name="nameInCharacters" id="nameInCharacters" maxlength=20 value="" type="text" style="width: 300px">
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print _('Gender') ?> *</b><br/>
				</td>
				<td class="right">
					<select name="gender" id="gender" style="width: 302px">
						<option value="Please select..."><?php print _('Please select...') ?></option>
						<option value="F"><?php print _('Female') ?></option>
						<option value="M"><?php print _('Male') ?></option>
					</select>
					<script type="text/javascript">
						var gender=new LiveValidation('gender');
						gender.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print _('Select something!') ?>"});
					 </script>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print _('Date of Birth') ?> *</b><br/>
					<span style="font-size: 90%"><i><?php print _('Format:') . " " . $_SESSION[$guid]["i18n"]["dateFormat"]  ?></i></span>
				</td>
				<td class="right">
					<input name="dob" id="dob" maxlength=10 value="" type="text" style="width: 300px">
					<script type="text/javascript">
						var dob=new LiveValidation('dob');
						dob.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
					 	dob.add(Validate.Presence);
					 </script>
					 <script type="text/javascript">
						$(function() {
							$( "#dob" ).datepicker();
						});
					</script>
				</td>
			</tr>
			
			
			<tr>
				<td colspan=2> 
					<h4><?php print _('Student Background') ?></h4>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print _('Home Language') ?> *</b><br/>
					<span style="font-size: 90%"><i><?php print _('The primary language used in the student\'s home.<') ?>/i></span>
				</td>
				<td class="right">
					<input name="languageHome" id="languageHome" maxlength=30 value="" type="text" style="width: 300px">
				</td>
				<script type="text/javascript">
					$(function() {
						var availableTags=[
							<?php
							try {
								$dataAuto=array(); 
								$sqlAuto="SELECT DISTINCT languageHome FROM gibbonApplicationForm ORDER BY languageHome" ;
								$resultAuto=$connection2->prepare($sqlAuto);
								$resultAuto->execute($dataAuto);
							}
							catch(PDOException $e) { }
							while ($rowAuto=$resultAuto->fetch()) {
								print "\"" . $rowAuto["languageHome"] . "\", " ;
							}
							?>
						];
						$( "#languageHome" ).autocomplete({source: availableTags});
					});
				</script>
				<script type="text/javascript">
					var languageHome=new LiveValidation('languageHome');
					languageHome.add(Validate.Presence);
				</script>
			</tr>
			<tr>
				<td> 
					<b><?php print _('First Language') ?> *</b><br/>
					<span style="font-size: 90%"><i><?php print _('Student\'s native/first/mother language.') ?></i></span>
				</td>
				<td class="right">
					<input name="languageFirst" id="languageFirst" maxlength=30 value="" type="text" style="width: 300px">
				</td>
				<script type="text/javascript">
					$(function() {
						var availableTags=[
							<?php
							try {
								$dataAuto=array(); 
								$sqlAuto="SELECT DISTINCT languageFirst FROM gibbonApplicationForm ORDER BY languageFirst" ;
								$resultAuto=$connection2->prepare($sqlAuto);
								$resultAuto->execute($dataAuto);
							}
							catch(PDOException $e) { }
							while ($rowAuto=$resultAuto->fetch()) {
								print "\"" . $rowAuto["languageFirst"] . "\", " ;
							}
							?>
						];
						$( "#languageFirst" ).autocomplete({source: availableTags});
					});
				</script>
				<script type="text/javascript">
					var languageFirst=new LiveValidation('languageFirst');
					languageFirst.add(Validate.Presence);
				</script>
			</tr>
			<tr>
				<td> 
					<b><?php print _('Second Language') ?></b><br/>
				</td>
				<td class="right">
					<input name="languageSecond" id="languageSecond" maxlength=30 value="" type="text" style="width: 300px">
				</td>
				<script type="text/javascript">
					$(function() {
						var availableTags=[
							<?php
							try {
								$dataAuto=array(); 
								$sqlAuto="SELECT DISTINCT languageSecond FROM gibbonApplicationForm ORDER BY languageSecond" ;
								$resultAuto=$connection2->prepare($sqlAuto);
								$resultAuto->execute($dataAuto);
							}
							catch(PDOException $e) { }
							while ($rowAuto=$resultAuto->fetch()) {
								print "\"" . $rowAuto["languageSecond"] . "\", " ;
							}
							?>
						];
						$( "#languageSecond" ).autocomplete({source: availableTags});
					});
				</script>
			</tr>
			<tr>
				<td> 
					<b><?php print _('Third Language') ?></b><br/>
				</td>
				<td class="right">
					<input name="languageThird" id="languageThird" maxlength=30 value="" type="text" style="width: 300px">
				</td>
				<script type="text/javascript">
					$(function() {
						var availableTags=[
							<?php
							try {
								$dataAuto=array(); 
								$sqlAuto="SELECT DISTINCT languageThird FROM gibbonApplicationForm ORDER BY languageThird" ;
								$resultAuto=$connection2->prepare($sqlAuto);
								$resultAuto->execute($dataAuto);
							}
							catch(PDOException $e) { }
							while ($rowAuto=$resultAuto->fetch()) {
								print "\"" . $rowAuto["languageThird"] . "\", " ;
							}
							?>
						];
						$( "#languageThird" ).autocomplete({source: availableTags});
					});
				</script>
			</tr>
			<tr>
				<td> 
					<b><?php print _('Country of Birth') ?></b><br/>
				</td>
				<td class="right">
					<select name="countryOfBirth" id="countryOfBirth" style="width: 302px">
						<?php
						try {
							$dataSelect=array(); 
							$sqlSelect="SELECT printable_name FROM gibbonCountry ORDER BY printable_name" ;
							$resultSelect=$connection2->prepare($sqlSelect);
							$resultSelect->execute($dataSelect);
						}
						catch(PDOException $e) { }
						print "<option value=''></option>" ;
						while ($rowSelect=$resultSelect->fetch()) {
							print "<option value='" . $rowSelect["printable_name"] . "'>" . htmlPrep(_($rowSelect["printable_name"])) . "</option>" ;
						}
						?>				
					</select>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print _('Citizenship') ?></b><br/>
				</td>
				<td class="right">
					<select name="citizenship1" id="citizenship1" style="width: 302px">
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
								print "<option value='" . $rowSelect["printable_name"] . "'>" . htmlPrep(_($rowSelect["printable_name"])) . "</option>" ;
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
					<b><?php print _('Citizenship Passport Number') ?></b><br/>
				</td>
				<td class="right">
					<input name="citizenship1Passport" id="citizenship1Passport" maxlength=30 value="" type="text" style="width: 300px">
				</td>
			</tr>
			<tr>
				<td> 
					<?php
					if ($_SESSION[$guid]["country"]=="") {
						print "<b>" . _('National ID Card Number') . "</b><br/>" ;
					}
					else {
						print "<b>" . $_SESSION[$guid]["country"] . " " . _('ID Card Number') . "</b><br/>" ;
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
						print "<b>" . _('Residency/Visa Type') . "</b><br/>" ;
					}
					else {
						print "<b>" . $_SESSION[$guid]["country"] . " " . _('Residency/Visa Type') . "</b><br/>" ;
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
								$selected="" ;
								if (trim($residencyStatus)==$row["residencyStatus"]) {
									$selected="selected" ;
								}
								print "<option $selected value='" . trim($residencyStatus) . "'>" . trim($residencyStatus) . "</option>" ;
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
						print "<b>" . _('Visa Expiry Date') . "</b><br/>" ;
					}
					else {
						print "<b>" . $_SESSION[$guid]["country"] . " " . _('Visa Expiry Date') . "</b><br/>" ;
					}
					print "<span style='font-size: 90%'><i>Format: " ; if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; } print ". " . _('If relevant.') . "</i></span>" ;
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
			
			
			<tr>
				<td colspan=2> 
					<h4><?php print _('Student Contact') ?></h4>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print _('Email') ?></b><br/>
				</td>
				<td class="right">
					<input name="email" id="email" maxlength=50 value="" type="text" style="width: 300px">
					<script type="text/javascript">
						var email=new LiveValidation('email');
						email.add(Validate.Email);
					 </script>
				</td>
			</tr>
			<?php
			for ($i=1; $i<3; $i++) {
				?>
				<tr>
					<td> 
						<b><?php print _('Phone') ?> <?php print $i ?></b><br/>
						<span style="font-size: 90%"><i><?php print _('Type, country code, number.') ?></i></span>
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
								print "<option value='" . $rowSelect["iddCountryCode"] . "'>" . htmlPrep($rowSelect["iddCountryCode"]) . " - " .  htmlPrep(_($rowSelect["printable_name"])) . "</option>" ;
							}
							?>				
						</select>
						<select style="width: 70px" name="phone<?php print $i ?>Type">
							<option value=""></option>
							<option value="Mobile"><?php print _('Mobile') ?></option>
							<option value="Home"><?php print _('Home') ?></option>
							<option value="Work"><?php print _('Work') ?></option>
							<option value="Fax"><?php print _('Fax') ?></option>
							<option value="Pager"><?php print _('Pager') ?></option>
							<option value="Other"><?php print _('Other') ?></option>
						</select>
					</td>
				</tr>
				<?php
			}
			?>
			
			
			<tr>
				<td colspan=2> 
					<h4><?php print _('Student Medical & Development') ?></h4>
				</td>
			</tr>
			<tr>
				<td colspan=2 style='padding-top: 15px'> 
					<b><?php print _('Medical Information') ?></b><br/>
					<span style="font-size: 90%"><i><?php print _('Please indicate any medical conditions.') ?></i></span><br/>
					<textarea name="medicalInformation" id="medicalInformation" rows=5 style="width:738px; margin: 5px 0px 0px 0px"></textarea>
				</td>
			</tr>
			<tr>
				<td colspan=2 style='padding-top: 15px'> 
					<b><?php print _('Development Information') ?></b><br/>
					<span style="font-size: 90%"><i><?php print _('Provide any comments or information concerning your child\’s development that may be relevant to your child\’s performance in the classroom or elsewhere? (Incorrect or withheld information may affect continued enrolment).') ?></i></span><br/> 					
					<textarea name="developmentInformation" id="developmentInformation" rows=5 style="width:738px; margin: 5px 0px 0px 0px"></textarea>
				</td>
			</tr>
			
			
			
			<tr>
				<td colspan=2> 
					<h4><?php print _('Student Education') ?></h4>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print _('Anticipated Year of Entry') ?> *</b><br/>
					<span style="font-size: 90%"><i><?php print _('What school year will the student join in?') ?></i></span>
				</td>
				<td class="right">
					<select name="gibbonSchoolYearIDEntry" id="gibbonSchoolYearIDEntry" style="width: 302px">
						<?php
						print "<option value='Please select...'>" . _('Please select...') . "</option>" ;
						try {
							$dataSelect=array(); 
							$sqlSelect="SELECT * FROM gibbonSchoolYear WHERE (status='Current' OR status='Upcoming') ORDER BY sequenceNumber" ;
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
					<script type="text/javascript">
						var gibbonSchoolYearIDEntry=new LiveValidation('gibbonSchoolYearIDEntry');
						gibbonSchoolYearIDEntry.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print _('Select something!') ?>"});
					 </script>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print _('Intended Start Date') ?> *</b><br/>
					<span style="font-size: 90%"><i><?php print _('Student\'s intended first day at school.') ?><br/><?php print _('Format:') ?> <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?></i></span>
				</td>
				<td class="right">
					<input name="dateStart" id="dateStart" maxlength=10 value="" type="text" style="width: 300px">
					<script type="text/javascript">
						var dateStart=new LiveValidation('dateStart');
						dateStart.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
					 	dateStart.add(Validate.Presence);
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
					<b><?php print _('Year Group at Entry') ?> *</b><br/>
					<span style="font-size: 90%"><i><?php print _('Which year level will student enter.') ?></i></span>
				</td>
				<td class="right">
					<select name="gibbonYearGroupIDEntry" id="gibbonYearGroupIDEntry" style="width: 302px">
						<?php
						print "<option value='Please select...'>" . _('Please select...') . "</option>" ;
						try {
							$dataSelect=array(); 
							$sqlSelect="SELECT * FROM gibbonYearGroup ORDER BY sequenceNumber" ;
							$resultSelect=$connection2->prepare($sqlSelect);
							$resultSelect->execute($dataSelect);
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						while ($rowSelect=$resultSelect->fetch()) {
							print "<option value='" . $rowSelect["gibbonYearGroupID"] . "'>" . htmlPrep($rowSelect["name"]) . "</option>" ;
						}
						?>				
					</select>
					<script type="text/javascript">
						var gibbonYearGroupIDEntry=new LiveValidation('gibbonYearGroupIDEntry');
						gibbonYearGroupIDEntry.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print _('Select something!') ?>"});
					 </script>
				</td>
			</tr>
			
			<?php
			$dayTypeOptions=getSettingByScope($connection2, 'User Admin', 'dayTypeOptions') ;
			if ($dayTypeOptions!="") {
				?>
				<tr>
					<td> 
						<b><?php print _('Day Type') ?></b><br/>
						<span style="font-size: 90%"><i><?php print getSettingByScope($connection2, 'User Admin', 'dayTypeText') ; ?></i></span>
					</td>
					<td class="right">
						<select name="dayType" id="dayType" style="width: 302px">
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
				<td colspan=2 style='padding-top: 15px'> 
					<b><?php print _('Previous Schools') ?> *</b><br/>
					<span style="font-size: 90%"><i><?php print _('Please give information on the last two schools attended by the applicant.') ?></i></span>
				</td>
			</tr>
			<tr>
				<td colspan=2> 
					<?php
					print "<table cellspacing='0' style='width: 100%'>" ;
						print "<tr class='head'>" ;
							print "<th>" ;
								print _("School Name") ;
							print "</th>" ;
							print "<th>" ;
								print _("Address") ;
							print "</th>" ;
							print "<th>" ;
								print sprintf(_('Grades%1$sAttended'), "<br/>") ;
							print "</th>" ;
							print "<th>" ;
								print sprintf(_('Language of%1$sInstruction'), "<br/>") ;
							print "</th>" ;
							print "<th>" ;
								print _("Joining Date") . "<br/><span style='font-size: 80%'>" ; if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; } print "</span>" ;
							print "</th>" ;
						print "</tr>" ;
						
						
						for ($i=1; $i<3; $i++) {
							if ((($i%2)-1)==0) {
								$rowNum="even" ;
							}
							else {
								$rowNum="odd" ;
							}
										
							print "<tr class=$rowNum>" ;
								print "<td>" ;
									print "<input name='schoolName$i' id='schoolName$i' maxlength=50 value='' type='text' style='width:120px; float: left'>" ;
								print "</td>" ;
								print "<td>" ;
									print "<input name='schoolAddress$i' id='schoolAddress$i' maxlength=255 value='' type='text' style='width:120px; float: left'>" ;
								print "</td>" ;
								print "<td>" ;
									print "<input name='schoolGrades$i' id='schoolGrades$i' maxlength=20 value='' type='text' style='width:70px; float: left'>" ;
								print "</td>" ;
								print "<td>" ;
									print "<input name='schoolLanguage$i' id='schoolLanguage$i' maxlength=50 value='' type='text' style='width:100px; float: left'>" ;
									?>
									<script type="text/javascript">
										$(function() {
											var availableTags=[
												<?php
												try {
													$dataAuto=array(); 
													$sqlAuto="SELECT DISTINCT schoolLanguage" . $i . " FROM gibbonApplicationForm ORDER BY schoolLanguage" . $i ;
													$resultAuto=$connection2->prepare($sqlAuto);
													$resultAuto->execute($dataAuto);
												}
												catch(PDOException $e) { }
												while ($rowAuto=$resultAuto->fetch()) {
													print "\"" . $rowAuto["schoolLanguage" . $i] . "\", " ;
												}
												?>
											];
											$( "#schoolLanguage<?php print $i ?>" ).autocomplete({source: availableTags});
										});
									</script>
									<?php
								print "</td>" ;
								print "<td>" ;
									?>
									<input name="<?php print "schoolDate$i" ?>" id="<?php print "schoolDate$i" ?>" maxlength=10 value="" type="text" style="width:90px; float: left">
									<script type="text/javascript">
										$(function() {
											$( "#<?php print "schoolDate$i" ?>" ).datepicker();
										});
									</script>
									<?php
								print "</td>" ;
							print "</tr>" ;
						}
					print "</table>" ;
					?>
				</td>
			</tr>
			
			
			
			<?php
			//FAMILY
			try {
				$dataSelect=array("gibbonPersonID"=>$gibbonPersonID); 
				$sqlSelect="SELECT * FROM gibbonFamily JOIN gibbonFamilyAdult ON (gibbonFamily.gibbonFamilyID=gibbonFamilyAdult.gibbonFamilyID) WHERE gibbonFamilyAdult.gibbonPersonID=:gibbonPersonID ORDER BY name" ;
				$resultSelect=$connection2->prepare($sqlSelect);
				$resultSelect->execute($dataSelect);
			}
			catch(PDOException $e) { }
						
			if ($public==TRUE OR $resultSelect->rowCount()<1) {
				?>
				<input type="hidden" name="gibbonFamily" value="FALSE">
				
				<tr class='break'>
					<td colspan=2> 
						<h3>
							<?php print _('Home Address') ?>
						</h3>
						<p>
							<?php print _('This address will be used for all members of the family. If an individual within the family needs a different address, this can be set through Data Updater after admission.') ?>
						</p>
					</td>
				</tr>
				<tr>
					<td> 
						<b><?php print _('Home Address') ?> *</b><br/>
						<span style="font-size: 90%"><i><?php print _('Unit, Building, Street') ?></i></span>
					</td>
					<td class="right">
						<input name="homeAddress" id="homeAddress" maxlength=255 value="" type="text" style="width: 300px">
						<script type="text/javascript">
							var homeAddress=new LiveValidation('homeAddress');
							homeAddress.add(Validate.Presence);
						 </script>
					</td>
				</tr>
				<tr>
					<td> 
						<b><?php print _('Home Address (District)') ?> *</b><br/>
						<span style="font-size: 90%"><i><?php print _('County, State, District') ?></i></span>
					</td>
					<td class="right">
						<input name="homeAddressDistrict" id="homeAddressDistrict" maxlength=30 value="" type="text" style="width: 300px">
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
						<b><?php print _('Home Address (Country)') ?> *</b><br/>
					</td>
					<td class="right">
						<select name="homeAddressCountry" id="homeAddressCountry" style="width: 302px">
							<?php
							try {
								$dataSelect=array(); 
								$sqlSelect="SELECT printable_name FROM gibbonCountry ORDER BY printable_name" ;
								$resultSelect=$connection2->prepare($sqlSelect);
								$resultSelect->execute($dataSelect);
							}
							catch(PDOException $e) { }
							print "<option value='Please select...'>" . _('Please select...') . "</option>" ;
							while ($rowSelect=$resultSelect->fetch()) {
								print "<option value='" . $rowSelect["printable_name"] . "'>" . htmlPrep(_($rowSelect["printable_name"])) . "</option>" ;
							}
							?>				
						</select>
						<script type="text/javascript">
							var homeAddressCountry=new LiveValidation('homeAddressCountry');
							homeAddressCountry.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print _('Select something!') ?>"});
						 </script>
					</td>
				</tr>
				<?php
				
				if (isset($_SESSION[$guid]["username"])) {
					$start=2 ;
					?>
					<tr class='break'>
						<td colspan=2> 
							<h3>
								<?php print _('Parent/Guardian 1') ?>
								<?php
								if ($i==1) {
									print "<span style='font-size: 75%'></span>" ;
								}
								?>
							</h3>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print _('Username') ?></b><br/>
							<span style="font-size: 90%"><i><?php print _('System login ID.') ?></i></span>
						</td>
						<td class="right">
							<input readonly name='parent1username' maxlength=30 value="<?php print $_SESSION[$guid]["username"] ?>" type="text" style="width: 300px">
						</td>
					</tr>
					
					<tr>
						<td> 
							<b><?php print _('Surname') ?></b><br/>
							<span style="font-size: 90%"><i><?php print _('Family name as shown in ID documents.') ?></i></span>
						</td>
						<td class="right">
							<input readonly name='parent1surname' maxlength=30 value="<?php print $_SESSION[$guid]["surname"] ?>" type="text" style="width: 300px">
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print _('Preferred Name') ?></b><br/>
							<span style="font-size: 90%"><i><?php print _('Most common name, alias, nickname, etc.') ?></i></span>
						</td>
						<td class="right">
							<input readonly name='parent1preferredName' maxlength=30 value="<?php print $_SESSION[$guid]["preferredName"] ?>" type="text" style="width: 300px">
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print _('Relationship') ?> *</b><br/>
						</td>
						<td class="right">
							<select name="parent1relationship" id="parent1relationship" style="width: 302px">
								<option value="Please select..."><?php print _('Please select...') ?></option>
								<option value="Mother"><?php print _('Mother') ?></option>
								<option value="Father"><?php print _('Father') ?></option>
								<option value="Step-Mother"><?php print _('Step-Mother') ?></option>
								<option value="Step-Father"><?php print _('Step-Father') ?></option>
								<option value="Adoptive Parent"><?php print _('Adoptive Parent') ?></option>
								<option value="Guardian"><?php print _('Guardian') ?></option>
								<option value="Grandmother"><?php print _('Grandmother') ?></option>
								<option value="Grandfather"><?php print _('Grandfather') ?></option>
								<option value="Aunt"><?php print _('Aunt') ?></option>
								<option value="Uncle"><?php print _('Uncle') ?></option>
								<option value="Nanny/Helper"><?php print _('Nanny/Helper') ?></option>
								<option value="Other"><?php print _('Other') ?></option>
							</select>
							<script type="text/javascript">
								var parent1relationship=new LiveValidation('parent1relationship');
								parent1relationship.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print _('Select something!') ?>"});
							 </script>
						</td>
					</tr>
					<input name='parent1gibbonPersonID' value="<?php print $gibbonPersonID ?>" type="hidden">
					<?php
				}
				else {
					$start=1 ;
				}
				for ($i=$start;$i<3;$i++) {
					?>
					<tr class='break'>
						<td colspan=2> 
							<h3>
								<?php print _('Parent/Guardian') ?> <?php print $i ?>
								<?php
								if ($i==1) {
									print "<span style='font-size: 75%'> (e.g. mother)</span>" ;
								}
								else if ($i==2 AND $gibbonPersonID=="") {
									print "<span style='font-size: 75%'> (e.g. father)</span>" ;
								}
								?>
							</h3>
						</td>
					</tr>
					<?php
					if ($i==2) {
						?>
						<tr>
							<td class='right' colspan=2> 
								<script type="text/javascript">
									/* Advanced Options Control */
									$(document).ready(function(){
										$("#secondParent").click(function(){
											if ($('input[name=secondParent]:checked').val()=="No" ) {
												$(".secondParent").slideUp("fast"); 	
												$("#parent2title").attr("disabled", "disabled");
												$("#parent2surname").attr("disabled", "disabled");
												$("#parent2firstName").attr("disabled", "disabled");
												$("#parent2preferredName").attr("disabled", "disabled");
												$("#parent2officialName").attr("disabled", "disabled");
												$("#parent2nameInCharacters").attr("disabled", "disabled");
												$("#parent2gender").attr("disabled", "disabled");
												$("#parent2relationship").attr("disabled", "disabled");
												$("#parent2languageFirst").attr("disabled", "disabled");
												$("#parent2languageSecond").attr("disabled", "disabled");
												$("#parent2citizenship1").attr("disabled", "disabled");
												$("#parent2nationalIDCardNumber").attr("disabled", "disabled");
												$("#parent2residencyStatus").attr("disabled", "disabled");
												$("#parent2visaExpiryDate").attr("disabled", "disabled");
												$("#parent2email").attr("disabled", "disabled");
												$("#parent2phone1Type").attr("disabled", "disabled");
												$("#parent2phone1CountryCode").attr("disabled", "disabled");
												$("#parent2phone1").attr("disabled", "disabled");
												$("#parent2phone2Type").attr("disabled", "disabled");
												$("#parent2phone2CountryCode").attr("disabled", "disabled");
												$("#parent2phone2").attr("disabled", "disabled");
												$("#parent2profession").attr("disabled", "disabled");
												$("#parent2employer").attr("disabled", "disabled");
											} 
											else {
												$(".secondParent").slideDown("fast", $(".secondParent").css("display","table-row")); 
												$("#parent2title").removeAttr("disabled");
												$("#parent2surname").removeAttr("disabled");
												$("#parent2firstName").removeAttr("disabled");
												$("#parent2preferredName").removeAttr("disabled");
												$("#parent2officialName").removeAttr("disabled");
												$("#parent2nameInCharacters").removeAttr("disabled");
												$("#parent2gender").removeAttr("disabled");
												$("#parent2relationship").removeAttr("disabled");
												$("#parent2languageFirst").removeAttr("disabled");
												$("#parent2languageSecond").removeAttr("disabled");
												$("#parent2citizenship1").removeAttr("disabled");
												$("#parent2nationalIDCardNumber").removeAttr("disabled");
												$("#parent2residencyStatus").removeAttr("disabled");
												$("#parent2visaExpiryDate").removeAttr("disabled");
												$("#parent2email").removeAttr("disabled");
												$("#parent2phone1Type").removeAttr("disabled");
												$("#parent2phone1CountryCode").removeAttr("disabled");
												$("#parent2phone1").removeAttr("disabled");
												$("#parent2phone2Type").removeAttr("disabled");
												$("#parent2phone2CountryCode").removeAttr("disabled");
												$("#parent2phone2").removeAttr("disabled");
												$("#parent2profession").removeAttr("disabled");
												$("#parent2employer").removeAttr("disabled");
											}
										 });
									});
								</script>
								<span style='font-weight: bold; font-style: italic'>Do not include a second parent/gaurdian <input id='secondParent' name='secondParent' type='checkbox' value='No'/></span>
							</td>
						</tr>
						<?php
					}
					?>
					<tr <?php if ($i==2) { print "class='secondParent'" ; }?>>
						<td colspan=2> 
							<h4><?php print _('Parent/Guardian') ?> <?php print $i ?> <?php print _('Personal Data') ?></h4>
						</td>
					</tr>
					<tr <?php if ($i==2) { print "class='secondParent'" ; }?>>
						<td> 
							<b><?php print _('Title') ?> *</b><br/>
							<span style="font-size: 90%"><i></i></span>
						</td>
						<td class="right">
							<select style="width: 302px" id="<?php print "parent$i" ?>title" name="<?php print "parent$i" ?>title">
								<option value="Please select..."><?php print _('Please select...') ?></option>
								<option value="Ms. ">Ms.</option>
								<option value="Miss ">Miss</option>
								<option value="Mr. ">Mr.</option>
								<option value="Mrs. ">Mrs.</option>
								<option value="Dr. ">Dr.</option>
							</select>
							<script type="text/javascript">
								var <?php print "parent$i" ?>title=new LiveValidation('<?php print "parent$i" ?>title');
								<?php print "parent$i" ?>title.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print _('Select something!') ?>"});
							 </script>
						</td>
					</tr>
					<tr <?php if ($i==2) { print "class='secondParent'" ; }?>>
						<td> 
							<b><?php print _('Surname') ?> *</b><br/>
							<span style="font-size: 90%"><i><?php print _('Family name as shown in ID documents.') ?></i></span>
						</td>
						<td class="right">
							<input name="<?php print "parent$i" ?>surname" id="<?php print "parent$i" ?>surname" maxlength=30 value="" type="text" style="width: 300px">
							<script type="text/javascript">
								var <?php print "parent$i" ?>surname=new LiveValidation('<?php print "parent$i" ?>surname');
								<?php print "parent$i" ?>surname.add(Validate.Presence);
							 </script>
						</td>
					</tr>
					<tr <?php if ($i==2) { print "class='secondParent'" ; }?>>
						<td> 
							<b><?php print _('First Name') ?> *</b><br/>
							<span style="font-size: 90%"><i><?php print _('First name as shown in ID documents.') ?></i></span>
						</td>
						<td class="right">
							<input name="<?php print "parent$i" ?>firstName" id="<?php print "parent$i" ?>firstName" maxlength=30 value="" type="text" style="width: 300px">
							<script type="text/javascript">
								var <?php print "parent$i" ?>firstName=new LiveValidation('<?php print "parent$i" ?>firstName');
								<?php print "parent$i" ?>firstName.add(Validate.Presence);
							 </script>
						</td>
					</tr>
					<tr <?php if ($i==2) { print "class='secondParent'" ; }?>>
						<td> 
							<b><?php print _('Preferred Name') ?> *</b><br/>
							<span style="font-size: 90%"><i><?php print _('Most common name, alias, nickname, etc.') ?></i></span>
						</td>
						<td class="right">
							<input name="<?php print "parent$i" ?>preferredName" id="<?php print "parent$i" ?>preferredName" maxlength=30 value="" type="text" style="width: 300px">
							<script type="text/javascript">
								var <?php print "parent$i" ?>preferredName=new LiveValidation('<?php print "parent$i" ?>preferredName');
								<?php print "parent$i" ?>preferredName.add(Validate.Presence);
							 </script>
						</td>
					</tr>
					<tr <?php if ($i==2) { print "class='secondParent'" ; }?>>
						<td> 
							<b><?php print _('Official Name') ?> *</b><br/>
							<span style="font-size: 90%"><i><?php print _('Full name as shown in ID documents.') ?></i></span>
						</td>
						<td class="right">
							<input title='Please enter full name as shown in ID documents' name="<?php print "parent$i" ?>officialName" id="<?php print "parent$i" ?>officialName" maxlength=150 value="" type="text" style="width: 300px">
							<script type="text/javascript">
								var <?php print "parent$i" ?>officialName=new LiveValidation('<?php print "parent$i" ?>officialName');
								<?php print "parent$i" ?>officialName.add(Validate.Presence);
							 </script>
						</td>
					</tr>
					<tr <?php if ($i==2) { print "class='secondParent'" ; }?>>
						<td> 
							<b><?php print _('Name In Characters') ?></b><br/>
							<span style="font-size: 90%"><i><?php print _('Chinese or other character-based name.') ?></i></span>
						</td>
						<td class="right">
							<input name="<?php print "parent$i" ?>nameInCharacters" id="<?php print "parent$i" ?>nameInCharacters" maxlength=20 value="" type="text" style="width: 300px">
						</td>
					</tr>
					<tr <?php if ($i==2) { print "class='secondParent'" ; }?>>
						<td> 
							<b><?php print _('Gender') ?> *</b><br/>
						</td>
						<td class="right">
							<select name="<?php print "parent$i" ?>gender" id="<?php print "parent$i" ?>gender" style="width: 302px">
								<option value="Please select..."><?php print _('Please select...') ?></option>
								<option value="F"><?php print _('Female') ?></option>
								<option value="M"><?php print _('Male') ?></option>
							</select>
							<script type="text/javascript">
								var <?php print "parent$i" ?>gender=new LiveValidation('<?php print "parent$i" ?>gender');
								<?php print "parent$i" ?>gender.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print _('Select something!') ?>"});
							 </script>
						</td>
					</tr>
					<tr <?php if ($i==2) { print "class='secondParent'" ; }?>>
						<td> 
							<b><?php print _('Relationship') ?> *</b><br/>
						</td>
						<td class="right">
							<select name="<?php print "parent$i" ?>relationship" id="<?php print "parent$i" ?>relationship" style="width: 302px">
								<option value="Please select..."><?php print _('Please select...') ?></option>
								<option value="Mother"><?php print _('Mother') ?></option>
								<option value="Father"><?php print _('Father') ?></option>
								<option value="Step-Mother"><?php print _('Step-Mother') ?></option>
								<option value="Step-Father"><?php print _('Step-Father') ?></option>
								<option value="Adoptive Parent"><?php print _('Adoptive Parent') ?></option>
								<option value="Guardian"><?php print _('Guardian') ?></option>
								<option value="Grandmother"><?php print _('Grandmother') ?></option>
								<option value="Grandfather"><?php print _('Grandfather') ?></option>
								<option value="Aunt"><?php print _('Aunt') ?></option>
								<option value="Uncle"><?php print _('Uncle') ?></option>
								<option value="Nanny/Helper"><?php print _('Nanny/Helper') ?></option>
								<option value="Other"><?php print _('Other') ?></option>
							</select>
							<script type="text/javascript">
								var <?php print "parent$i" ?>relationship=new LiveValidation('<?php print "parent$i" ?>relationship');
								<?php print "parent$i" ?>relationship.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print _('Select something!') ?>"});
							 </script>
						</td>
					</tr>
					
					<tr <?php if ($i==2) { print "class='secondParent'" ; }?>>
						<td colspan=2> 
							<h4><?php print _('Parent/Guardian') ?> <?php print $i ?> <?php print _('Personal Background') ?></h4>
						</td>
					</tr>
					<tr <?php if ($i==2) { print "class='secondParent'" ; }?>>
						<td> 
							<b><?php print _('First Language') ?></b><br/>
						</td>
						<td class="right">
							<input name="<?php print "parent$i" ?>languageFirst" id="<?php print "parent$i" ?>languageFirst" maxlength=30 value="" type="text" style="width: 300px">
						</td>
						<script type="text/javascript">
							$(function() {
								var availableTags=[
									<?php
									try {
										$dataAuto=array(); 
										$sqlAuto="SELECT DISTINCT languageFirst FROM gibbonApplicationForm ORDER BY languageFirst" ;
										$resultAuto=$connection2->prepare($sqlAuto);
										$resultAuto->execute($dataAuto);
									}
									catch(PDOException $e) { }
									while ($rowAuto=$resultAuto->fetch()) {
										print "\"" . $rowAuto["languageFirst"] . "\", " ;
									}
									?>
								];
								$( "#<?php print 'parent' . $i ?>languageFirst" ).autocomplete({source: availableTags});
							});
						</script>
					</tr>
					<tr <?php if ($i==2) { print "class='secondParent'" ; }?>>
						<td> 
							<b><?php print _('Second Language') ?></b><br/>
						</td>
						<td class="right">
							<input name="<?php print "parent$i" ?>languageSecond" id="<?php print "parent$i" ?>languageSecond" maxlength=30 value="" type="text" style="width: 300px">
						</td>
						<script type="text/javascript">
							$(function() {
								var availableTags=[
									<?php
									try {
										$dataAuto=array(); 
										$sqlAuto="SELECT DISTINCT languageSecond FROM gibbonApplicationForm ORDER BY languageSecond" ;
										$resultAuto=$connection2->prepare($sqlAuto);
										$resultAuto->execute($dataAuto);
									}
									catch(PDOException $e) { }
									while ($rowAuto=$resultAuto->fetch()) {
										print "\"" . $rowAuto["languageSecond"] . "\", " ;
									}
									?>
								];
								$( "#<?php print 'parent' . $i ?>languageSecond" ).autocomplete({source: availableTags});
							});
						</script>
					</tr>
					<tr <?php if ($i==2) { print "class='secondParent'" ; }?>>
						<td> 
							<b><?php print _('Citizenship') ?></b><br/>
						</td>
						<td class="right">
							<select name="<?php print "parent$i" ?>citizenship1" id="<?php print "parent$i" ?>citizenship1" style="width: 302px">
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
										print "<option value='" . $rowSelect["printable_name"] . "'>" . htmlPrep(_($rowSelect["printable_name"])) . "</option>" ;
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
					<tr <?php if ($i==2) { print "class='secondParent'" ; }?>>
						<td> 
							<?php
							if ($_SESSION[$guid]["country"]=="") {
								print "<b>" . _('National ID Card Number') . "</b><br/>" ;
							}
							else {
								print "<b>" . $_SESSION[$guid]["country"] . " " . _('ID Card Number') . "</b><br/>" ;
							}
							?>
						</td>
						<td class="right">
							<input name="<?php print "parent$i" ?>nationalIDCardNumber" id="<?php print "parent$i" ?>nationalIDCardNumber" maxlength=30 value="" type="text" style="width: 300px">
						</td>
					</tr>
					<tr <?php if ($i==2) { print "class='secondParent'" ; }?>>
						<td> 
							<?php
							if ($_SESSION[$guid]["country"]=="") {
								print "<b>" . _('Residency/Visa Type') . "</b><br/>" ;
							}
							else {
								print "<b>" . $_SESSION[$guid]["country"] . " " . _('Residency/Visa Type') . "</b><br/>" ;
							}
							?>
						</td>
						<td class="right">
							<?php
							$residencyStatusList=getSettingByScope($connection2, "User Admin", "residencyStatus") ;
							if ($residencyStatusList=="") {
								print "<input name='parent" . $i . "residencyStatus' id='parent" . $i . "residencyStatus' maxlength=30 type='text' style='width: 300px'>" ;
							}
							else {
								print "<select name='parent" . $i . "residencyStatus' id='parent" . $i . "residencyStatus' style='width: 302px'>" ;
									print "<option value=''></option>" ;
									$residencyStatuses=explode(",", $residencyStatusList) ;
									foreach ($residencyStatuses as $residencyStatus) {
										$selected="" ;
										if (trim($residencyStatus)==$row["parent" . $i . "residencyStatus"]) {
											$selected="selected" ;
										}
										print "<option $selected value='" . trim($residencyStatus) . "'>" . trim($residencyStatus) . "</option>" ;
									}
								print "</select>" ;
							}
							?>
						</td>
					</tr>
					<tr <?php if ($i==2) { print "class='secondParent'" ; }?>>
						<td> 
							<?php
							if ($_SESSION[$guid]["country"]=="") {
								print "<b>" . _('Visa Expiry Date') . "</b><br/>" ;
							}
							else {
								print "<b>" . $_SESSION[$guid]["country"] . " " . _('Visa Expiry Date') . "</b><br/>" ;
							}
							print "<span style='font-size: 90%'><i>" . _('Format:') . " " ; if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; } print ". " . _('If relevant.') . "</i></span>" ;
							?>
						</td>
						<td class="right">
							<input name="<?php print "parent$i" ?>visaExpiryDate" id="<?php print "parent$i" ?>visaExpiryDate" maxlength=10 value="" type="text" style="width: 300px">
							<script type="text/javascript">
								var <?php print "parent$i" ?>visaExpiryDate=new LiveValidation('<?php print "parent$i" ?>visaExpiryDate');
								<?php print "parent$i" ?>visaExpiryDate.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
							 </script>
							 <script type="text/javascript">
								$(function() {
									$( "#<?php print "parent$i" ?>visaExpiryDate" ).datepicker();
								});
							</script>
						</td>
					</tr>
					
					
					<tr <?php if ($i==2) { print "class='secondParent'" ; }?>>
						<td colspan=2> 
							<h4><?php print _('Parent/Guardian') ?> <?php print $i ?> <?php print _('Contact') ?></h4>
						</td>
					</tr>
					<tr <?php if ($i==2) { print "class='secondParent'" ; }?>>
						<td> 
							<b><?php print _('Email') ?> *</b><br/>
						</td>
						<td class="right">
							<input name="<?php print "parent$i" ?>email" id="<?php print "parent$i" ?>email" maxlength=50 value="" type="text" style="width: 300px">
							<script type="text/javascript">
								var <?php print "parent$i" ?>email=new LiveValidation('<?php print "parent$i" ?>email');
								<?php 
								print "parent$i" . "email.add(Validate.Email);";
								print "parent$i" . "email.add(Validate.Presence);" ;
								?>
							 </script>
						</td>
					</tr>
					
					<?php
					for ($y=1; $y<3; $y++) {
						?>
						<tr <?php if ($i==2) { print "class='secondParent'" ; }?>>
							<td> 
								<b><?php print _('Phone') ?> <?php print $y ; if ($y==1) { print " *" ;}?></b><br/>
								<span style="font-size: 90%"><i><?php print _('Type, country code, number.') ?></i></span>
							</td>
							<td class="right">
								<input name="<?php print "parent$i" ?>phone<?php print $y ?>" id="<?php print "parent$i" ?>phone<?php print $y ?>" maxlength=20 value="" type="text" style="width: 160px">
								<?php
								if ($y==1) {
									?>
									<script type="text/javascript">
										var <?php print "parent$i" ?>phone<?php print $y ?>=new LiveValidation('<?php print "parent$i" ?>phone<?php print $y ?>');
										<?php print "parent$i" ?>phone<?php print $y ?>.add(Validate.Presence);
									 </script>
									<?php
								}
								?>
								<select name="<?php print "parent$i" ?>phone<?php print $y ?>CountryCode" id="<?php print "parent$i" ?>phone<?php print $y ?>CountryCode" style="width: 60px">
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
										print "<option value='" . $rowSelect["iddCountryCode"] . "'>" . htmlPrep($rowSelect["iddCountryCode"]) . " - " .  htmlPrep(_($rowSelect["printable_name"])) . "</option>" ;
									}
									?>				
								</select>
								<select style="width: 70px" name="<?php print "parent$i" ?>phone<?php print $y ?>Type">
									<option value=""></option>
									<option value="Mobile"><?php print _('Mobile') ?></option>
									<option value="Home"><?php print _('Home') ?></option>
									<option value="Work"><?php print _('Work') ?></option>
									<option value="Fax"><?php print _('Fax') ?></option>
									<option value="Pager"><?php print _('Pager') ?></option>
									<option value="Other"><?php print _('Other') ?></option>
								</select>
							</td>
						</tr>
						<?php
					}
					?>
					
					<tr <?php if ($i==2) { print "class='secondParent'" ; }?>>
						<td colspan=2> 
							<h4><?php print _('Parent/Guardian') ?> <?php print $i ?> <?php print _('Employment') ?></h4>
						</td>
					</tr>
					<tr <?php if ($i==2) { print "class='secondParent'" ; }?>>
						<td> 
							<b><?php print _('Profession') ?> *</b><br/>
						</td>
						<td class="right">
							<input name="<?php print "parent$i" ?>profession" id="<?php print "parent$i" ?>profession" maxlength=30 value="" type="text" style="width: 300px">
							<script type="text/javascript">
								var <?php print "parent$i" ?>profession=new LiveValidation('<?php print "parent$i" ?>profession');
								<?php print "parent$i" ?>profession.add(Validate.Presence);
							 </script>
						</td>
					</tr>
					<tr <?php if ($i==2) { print "class='secondParent'" ; }?>>
						<td> 
							<b><?php print _('Employer') ?></b><br/>
						</td>
						<td class="right">
							<input name="<?php print "parent$i" ?>employer" id="<?php print "parent$i" ?>employer" maxlength=30 value="" type="text" style="width: 300px">
						</td>
					</tr>
					<?php
				}
			}
			else {
				?>
				<input type="hidden" name="gibbonFamily" value="TRUE">
				<tr class='break'>
					<td colspan=2> 
						<h3><?php print _('Family') ?></h3>
						<p><?php print _('Choose the family you wish to associate this application with.') ?></p>
						<?php
						print "<table cellspacing='0' style='width: 100%'>" ;
							print "<tr class='head'>" ;
								print "<th>" ;
									print _("Family Name") ;
								print "</th>" ;
								print "<th>" ;
									print _("Selected") ;
								print "</th>" ;
								print "<th>" ;
									print _("Relationships") ;
								print "</th>" ;
							print "</tr>" ;
							
							$rowCount=1 ;
							while ($rowSelect=$resultSelect->fetch()) {
								if (($rowCount%2)==0) {
									$rowNum="odd" ;
								}
								else {
									$rowNum="even" ;
								}
					
								print "<tr class=$rowNum>" ;
									print "<td>" ;
										print "<b>" . $rowSelect["name"] . "</b><br/>" ;
									print "</td>" ;
									print "<td>" ;
										$checked="" ;
										if ($rowCount==1) {
											$checked="checked" ;
										}
										print "<input $checked value='" . $rowSelect["gibbonFamilyID"] . "' name='gibbonFamilyID' type='radio'/>" ;
									print "</td>" ;
									print "<td>" ;
										try {
											$dataRelationships=array("gibbonFamilyID"=>$rowSelect["gibbonFamilyID"]); 
											$sqlRelationships="SELECT surname, preferredName, title, gender, gibbonFamilyAdult.gibbonPersonID FROM gibbonFamilyAdult JOIN gibbonPerson ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonFamilyID=:gibbonFamilyID" ; 
											$resultRelationships=$connection2->prepare($sqlRelationships);
											$resultRelationships->execute($dataRelationships);
										}
										catch(PDOException $e) { 
											print "<div class='error'>" . $e->getMessage() . "</div>" ; 
										}
										while ($rowRelationships=$resultRelationships->fetch()) {
											print "<div style='width: 100%'>" ;
												print formatName($rowRelationships["title"], $rowRelationships["preferredName"], $rowRelationships["surname"], "Parent") ;
												?>
												<select name="<?php print $rowSelect["gibbonFamilyID"] ?>-relationships[]" id="relationships[]" style="width: 200px">
													<option <?php if ($rowRelationships["gender"]=="F") { print "selected" ; } ?> value="Mother"><?php print _('Mother') ?></option>
													<option <?php if ($rowRelationships["gender"]=="M") { print "selected" ; } ?> value="Father"><?php print _('Father') ?></option>
													<option value="Step-Mother"><?php print _('Step-Mother') ?></option>
													<option value="Step-Father"><?php print _('Step-Father<') ?>/option>
													<option value="Adoptive Parent"><?php print _('Adoptive Parent') ?></option>
													<option value="Guardian"><?php print _('Guardian') ?></option>
													<option value="Grandmother"><?php print _('Grandmother') ?></option>
													<option value="Grandfather"><?php print _('Grandfather') ?></option>
													<option value="Aunt"><?php print _('Aunt') ?></option>
													<option value="Uncle"><?php print _('Uncle') ?></option>
													<option value="Nanny/Helper"><?php print _('Nanny/Helper') ?></option>
													<option value="Other"><?php print _('Other') ?></option>
												</select>
												<input type="hidden" name="<?php print $rowSelect["gibbonFamilyID"] ?>-relationshipsGibbonPersonID[]" value="<?php print $rowRelationships["gibbonPersonID"] ?>">
												<?php
											print "</div>" ;
											print "<br/>" ;
										}
									print "</td>" ;
								print "</tr>" ;
								$rowCount++ ;
							}
						print "</table>" ;	
						?>
					</td>
				</tr>
				<?php
			}
			?>
			<tr class='break'>
				<td colspan=2> 
					<h3><?php print _('Siblings') ?></h3>
				</td>
			</tr>
			<tr>
				<td colspan=2 style='padding-top: 0px'> 
					<p><?php print _('Please give information on the applicants\'s siblings.') ?></p>
				</td>
			</tr>
			<tr>
				<td colspan=2> 
					<?php
					print "<table cellspacing='0' style='width: 100%'>" ;
						print "<tr class='head'>" ;
							print "<th>" ;
								print _("Sibling Name") ;
							print "</th>" ;
							print "<th>" ;
								print _("Date of Birth") . "<br/><span style='font-size: 80%'>" . $_SESSION[$guid]["i18n"]["dateFormat"] . "</span>" ;
							print "</th>" ;
							print "<th>" ;
								print _("School Attending") ;
							print "</th>" ;
							print "<th>" ;
								print _("Joining Date") . "<br/><span style='font-size: 80%'>" . $_SESSION[$guid]["i18n"]["dateFormat"] . "</span>" ;
							print "</th>" ;
						print "</tr>" ;
						
						$rowCount=1 ;
						
						//List siblings who have been to or are at the school
						if (isset($gibbonFamilyID)) {
							try {
								$dataSibling=array("gibbonFamilyID"=>$gibbonFamilyID); 
								$sqlSibling="SELECT surname, preferredName, dob, dateStart FROM gibbonFamilyChild JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonFamilyID=:gibbonFamilyID ORDER BY dob ASC, surname, preferredName" ;
								$resultSibling=$connection2->prepare($sqlSibling);
								$resultSibling->execute($dataSibling);
							}
							catch(PDOException $e) { 
								print "<div class='error'>" . $e->getMessage() . "</div>" ; 
							}
							
							while ($rowSibling=$resultSibling->fetch()) {
								if (($rowCount%2)==0) {
									$rowNum="odd" ;
								}
								else {
									$rowNum="even" ;
								}
								
								print "<tr class=$rowNum>" ;
									print "<td>" ;
										print "<input name='siblingName$rowCount' id='siblingName$rowCount' maxlength=50 value='" . formatName("", $rowSibling["preferredName"], $rowSibling["surname"], "Student") . "' type='text' style='width:120px; float: left'>" ;
									print "</td>" ;
									print "<td>" ;
										?>
										<input name="<?php print "siblingDOB$rowCount" ?>" id="<?php print "siblingDOB$rowCount" ?>" maxlength=10 value="<?php print dateConvertBack($guid, $rowSibling["dob"]) ?>" type="text" style="width:90px; float: left"><br/>
										<script type="text/javascript">
											$(function() {
												$( "#<?php print "siblingDOB$rowCount" ?>" ).datepicker();
											});
										</script>
										<?php
									print "</td>" ;
									print "<td>" ;
										print "<input name='siblingSchool$rowCount' id='siblingSchool$rowCount' maxlength=50 value='" . $_SESSION[$guid]["organisationName"] . "' type='text' style='width:200px; float: left'>" ;
									print "</td>" ;
									print "<td>" ;
										?>
										<input name="<?php print "siblingSchoolJoiningDate$rowCount" ?>" id="<?php print "siblingSchoolJoiningDate$rowCount" ?>" maxlength=10 value="<?php print dateConvertBack($guid, $rowSibling["dateStart"]) ?>" type="text" style="width:90px; float: left">
										<script type="text/javascript">
											$(function() {
												$( "#<?php print "siblingSchoolJoiningDate$rowCount" ?>" ).datepicker();
											});
										</script>
										<?php
									print "</td>" ;
								print "</tr>" ;
								
								$rowCount++ ;								
							}
						}
						
						//Space for other siblings
						for ($i=$rowCount; $i<4; $i++) {
							if (($i%2)==0) {
								$rowNum="even" ;
							}
							else {
								$rowNum="odd" ;
							}
									
							print "<tr class=$rowNum>" ;
								print "<td>" ;
									print "<input name='siblingName$i' id='siblingName$i' maxlength=50 value='' type='text' style='width:120px; float: left'>" ;
								print "</td>" ;
								print "<td>" ;
									?>
									<input name="<?php print "siblingDOB$i" ?>" id="<?php print "siblingDOB$i" ?>" maxlength=10 value="" type="text" style="width:90px; float: left"><br/>
									<script type="text/javascript">
										$(function() {
											$( "#<?php print "siblingDOB$i" ?>" ).datepicker();
										});
									</script>
									<?php
								print "</td>" ;
								print "<td>" ;
									print "<input name='siblingSchool$i' id='siblingSchool$i' maxlength=50 value='' type='text' style='width:200px; float: left'>" ;
								print "</td>" ;
								print "<td>" ;
									?>
									<input name="<?php print "siblingSchoolJoiningDate$i" ?>" id="<?php print "siblingSchoolJoiningDate$i" ?>" maxlength=10 value="" type="text" style="width:120px; float: left">
									<script type="text/javascript">
										$(function() {
											$( "#<?php print "siblingSchoolJoiningDate$i" ?>" ).datepicker();
										});
									</script>
									<?php
								print "</td>" ;
							print "</tr>" ;
						}
					print "</table>" ;
					?>
				</td>
			</tr>
			
			<?php
			$languageOptionsActive=getSettingByScope($connection2, 'Application Form', 'languageOptionsActive') ;
			if ($languageOptionsActive=="On") {
				?>
				<tr class='break'>
					<td colspan=2> 
						<h3><? print _('Language Selection') ?></h3>
						<?php
						$languageOptionsBlurb=getSettingByScope($connection2, 'Application Form', 'languageOptionsBlurb') ;
						if ($languageOptionsBlurb!="") {
							print "<p>" ;
								print $languageOptionsBlurb ;
							print "</p>" ;
						}
						?>
					</td>
				</tr>
				<tr>
					<td> 
						<b><? print _('Language Choice') ?> *</b><br/>
						<span style="font-size: 90%"><i><? print _('Please choose preferred additional language to study.') ?></i></span>
					</td>
					<td class="right">
						<select name="languageChoice" id="languageChoice" style="width: 302px">
							<?php
							print "<option value='Please select...'>" . _('Please select...') . "</option>" ;
							$languageOptionsLanguageList=getSettingByScope($connection2, "Application Form", "languageOptionsLanguageList") ;
							$languages=explode(",", $languageOptionsLanguageList) ;
							foreach ($languages as $language) {
								print "<option value='" . trim($language) . "'>" . trim($language) . "</option>" ;
							}
							?>				
						</select>
						<script type="text/javascript">
							var languageChoice=new LiveValidation('languageChoice');
							languageChoice.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print _('Select something!') ?>"});
						 </script>
					</td>
				</tr>
				<tr>
					<td colspan=2 style='padding-top: 15px'> 
						<b><? print _('Language Choice Experience') ?> *</b><br/>
						<span style="font-size: 90%"><i><? print _('Has the applicant studied the selected language before? If so, please describe the level and type of experience.') ?></i></span><br/> 					
						<textarea name="languageChoiceExperience" id="languageChoiceExperience" rows=5 style="width:738px; margin: 5px 0px 0px 0px"></textarea>
						<script type="text/javascript">
							var languageChoiceExperience=new LiveValidation('languageChoiceExperience');
							languageChoiceExperience.add(Validate.Presence);
						 </script>
					</td>
				</tr>
				<?php
			}		
			?>
			


			<tr class='break'>
				<td colspan=2> 
					<h3><? print _('Scholarships') ?></h3>
					<?php
					//Get scholarships info
					$scholarship=getSettingByScope($connection2, 'Application Form', 'scholarships') ;
					if ($scholarship!="") {
						print "<p>" ;
							print $scholarship ;
						print "</p>" ;
					}
					?>
				</td>
			</tr>
			<tr>
				<td> 
					<b><? print _('Interest') ?></b><br/>
					<span style="font-size: 90%"><i><? print _('Indicate if you are interested in a scholarship.') ?></i></span><br/>
				</td>
				<td class="right">
					<input type="radio" id="scholarshipInterest" name="scholarshipInterest" class="type" value="Y" /> Yes
					<input checked type="radio" id="scholarshipInterest" name="scholarshipInterest" class="type" value="N" /> No
				</td>
			</tr>
			<tr>
				<td> 
					<b><? print _('Required?') ?></b><br/>
					<span style="font-size: 90%"><i><? print _('Is a scholarship required for you to take up a place at the school?') ?></i></span><br/>
				</td>
				<td class="right">
					<input type="radio" id="scholarshipRequired" name="scholarshipRequired" class="type" value="Y" /> Yes
					<input checked type="radio" id="scholarshipRequired" name="scholarshipRequired" class="type" value="N" /> No
				</td>
			</tr>
			
			
			<tr class='break'>
				<td colspan=2> 
					<h3><? print _('Payment') ?></h3>
				</td>
			</tr>
			<script type="text/javascript">
				/* Resource 1 Option Control */
				$(document).ready(function(){
					$("#companyNameRow").css("display","none");
					$("#companyContactRow").css("display","none");
					$("#companyAddressRow").css("display","none");
					$("#companyEmailRow").css("display","none");
					$("#companyCCFamilyRow").css("display","none");
					$("#companyPhoneRow").css("display","none");
					$("#companyAllRow").css("display","none");
					$("#companyCategoriesRow").css("display","none");
					
					
					$(".payment").click(function(){
						if ($('input[name=payment]:checked').val()=="Family" ) {
							$("#companyNameRow").css("display","none");
							$("#companyContactRow").css("display","none");
							$("#companyAddressRow").css("display","none");
							$("#companyEmailRow").css("display","none");
							$("#companyCCFamilyRow").css("display","none");
							$("#companyPhoneRow").css("display","none");
							$("#companyAllRow").css("display","none");
							$("#companyCategoriesRow").css("display","none");
						} else {
							$("#companyNameRow").slideDown("fast", $("#companyNameRow").css("display","table-row")); 
							$("#companyContactRow").slideDown("fast", $("#companyContactRow").css("display","table-row")); 
							$("#companyAddressRow").slideDown("fast", $("#companyAddressRow").css("display","table-row")); 
							$("#companyEmailRow").slideDown("fast", $("#companyEmailRow").css("display","table-row")); 
							$("#companyCCFamilyRow").slideDown("fast", $("#companyCCFamilyRow").css("display","table-row")); 
							$("#companyPhoneRow").slideDown("fast", $("#companyPhoneRow").css("display","table-row")); 
							$("#companyAllRow").slideDown("fast", $("#companyAllRow").css("display","table-row")); 
							if ($('input[name=companyAll]:checked').val()=="N" ) {
								$("#companyCategoriesRow").slideDown("fast", $("#companyCategoriesRow").css("display","table-row")); 
							}
						}
					 });
					 
					 $(".companyAll").click(function(){
						if ($('input[name=companyAll]:checked').val()=="Y" ) {
							$("#companyCategoriesRow").css("display","none");
						} else {
							$("#companyCategoriesRow").slideDown("fast", $("#companyCategoriesRow").css("display","table-row")); 
						}
					 });
				});
			</script>
			<tr id="familyRow">
				<td colspan=2>
					<p><? print _('If you choose family, future invoices will be sent according to your family\'s contact preferences, which can be changed at a later date by contacting the school. For example you may wish both parents to receive the invoice, or only one. Alternatively, if you choose Company, you can choose for all or only some fees to be covered by the specified company.') ?></p>
				</td>
			</tr>
			<tr>
				<td> 
					<b><? print _('Send Future Invoices To') ?></b><br/>
				</td>
				<td class="right">
					<input type="radio" name="payment" value="Family" class="payment" checked /> Family
					<input type="radio" name="payment" value="Company" class="payment" /> Company
				</td>
			</tr>
			<tr id="companyNameRow">
				<td> 
					<b><? print _('Company Name') ?></b><br/>
				</td>
				<td class="right">
					<input name="companyName" id="companyName" maxlength=100 value="" type="text" style="width: 300px">
				</td>
			</tr>
			<tr id="companyContactRow">
				<td> 
					<b><? print _('Company Contact Person') ?></b><br/>
				</td>
				<td class="right">
					<input name="companyContact" id="companyContact" maxlength=100 value="" type="text" style="width: 300px">
				</td>
			</tr>
			<tr id="companyAddressRow">
				<td> 
					<b><? print _('Company Address') ?></b><br/>
				</td>
				<td class="right">
					<input name="companyAddress" id="companyAddress" maxlength=255 value="" type="text" style="width: 300px">
				</td>
			</tr>
			<tr id="companyEmailRow">
				<td> 
					<b><? print _('Company Email') ?></b><br/>
				</td>
				<td class="right">
					<input name="companyEmail" id="companyEmail" maxlength=255 value="" type="text" style="width: 300px">
					<script type="text/javascript">
						var companyEmail=new LiveValidation('companyEmail');
						companyEmail.add(Validate.Email);
					 </script>
				</td>
			</tr>
			<tr id="companyCCFamilyRow">
				<td> 
					<b><? print _('CC Family?') ?></b><br/>
					<span style="font-size: 90%"><i><? print _('Should the family be sent a copy of billing emails?') ?></i></span>
				</td>
				<td class="right">
					<select name="companyCCFamily" id="companyCCFamily" style="width: 302px">
						<option value="N" /> <? print _('No') ?>
						<option value="Y" /> <? print _('Yes') ?>
					</select>
				</td>
			</tr>
			<tr id="companyPhoneRow">
				<td> 
					<b><? print _('Company Phone') ?></b><br/>
				</td>
				<td class="right">
					<input name="companyPhone" id="companyPhone" maxlength=20 value="" type="text" style="width: 300px">
				</td>
			</tr>
			<?php
			try {
				$dataCat=array(); 
				$sqlCat="SELECT * FROM gibbonFinanceFeeCategory WHERE active='Y' AND NOT gibbonFinanceFeeCategoryID=1 ORDER BY name" ;
				$resultCat=$connection2->prepare($sqlCat);
				$resultCat->execute($dataCat);
			}
			catch(PDOException $e) { }
			if ($resultCat->rowCount()<1) {
				print "<input type=\"hidden\" name=\"companyAll\" value=\"Y\" class=\"companyAll\"/>" ;
			}
			else {
				?>
				<tr id="companyAllRow">
					<td> 
						<b><? print _('Company All?') ?></b><br/>
						<span style="font-size: 90%"><i><? print _('Should all items be billed to the specified company, or just some?') ?></i></span>
					</td>
					<td class="right">
						<input type="radio" name="companyAll" value="Y" class="companyAll" checked /> <? print _('All') ?>
						<input type="radio" name="companyAll" value="N" class="companyAll" /> <? print _('Selected') ?>
					</td>
				</tr>
				<tr id="companyCategoriesRow">
					<td> 
						<b><? print _('Company Fee Categories') ?></b><br/>
						<span style="font-size: 90%"><i><? print _('If the specified company is not paying all fees, which categories are they paying?') ?></i></span>
					</td>
					<td class="right">
						<?php
						while ($rowCat=$resultCat->fetch()) {
							print $rowCat["name"] . " <input type='checkbox' name='gibbonFinanceFeeCategoryIDList[]' value='" . $rowCat["gibbonFinanceFeeCategoryID"] . "'/><br/>" ;
						}
						print _("Other") . " <input type='checkbox' name='gibbonFinanceFeeCategoryIDList[]' value='0001'/><br/>" ;
						?>
					</td>
				</tr>
			<?php
			}
			
			$requiredDocuments=getSettingByScope($connection2, "Application Form", "requiredDocuments") ;
			$requiredDocumentsText=getSettingByScope($connection2, "Application Form", "requiredDocumentsText") ;
			$requiredDocumentsCompulsory=getSettingByScope($connection2, "Application Form", "requiredDocumentsCompulsory") ;
			if ($requiredDocuments!="" AND $requiredDocuments!=FALSE) {
				?>
				<tr class='break'>
					<td colspan=2> 
						<h3><? print _('Supporting Documents') ?></h3>
						<?php 
						if ($requiredDocumentsText!="" OR $requiredDocumentsCompulsory!="") {
							print "<p>" ;
								print $requiredDocumentsText . " " ;
								if ($requiredDocumentsCompulsory=="Y") {
									print _("These documents must all be included before the application can be submitted.") ;
								}
								else {
									print _("These documents are all required, but can be submitted separately to this form if preferred. Please note, however, that your application will be processed faster if the documents are included here.") ;
								}
							print "</p>" ;
						}
						?>
					</td>
				</tr>
				<?php
				
				//Get list of acceptable file extensions
				try {
					$dataExt=array(); 
					$sqlExt="SELECT * FROM gibbonFileExtension" ;
					$resultExt=$connection2->prepare($sqlExt);
					$resultExt->execute($dataExt);
				}
				catch(PDOException $e) { }
				$ext="" ;
				while ($rowExt=$resultExt->fetch()) {
					$ext=$ext . "'." . $rowExt["extension"] . "'," ;
				}
							
				$requiredDocumentsList=explode(",", $requiredDocuments) ;
				$count=0 ;
				foreach ($requiredDocumentsList AS $document) {
					?>
					<tr>
						<td> 
							<b><?php print $document ; if ($requiredDocumentsCompulsory=="Y") { print " *" ; } ?></b><br/>
						</td>
						<td class="right">
							<?php
							print "<input type='file' name='file$count' id='file$count'><br/>" ;
							print "<input type='hidden' name='fileName$count' id='filefileName$count' value='$document'>" ;
							if ($requiredDocumentsCompulsory=="Y") {
								print "<script type='text/javascript'>" ;
									print "var file$count=new LiveValidation('file$count');" ;
									print "file$count.add( Validate.Inclusion, { within: [" . $ext . "], failureMessage: 'Illegal file type!', partialMatch: true, caseSensitive: false } );" ;
									print "file$count.add(Validate.Presence);" ;
								print "</script>" ;
							}
							$count++ ;
							?>
						</td>
					</tr>
					<?php
				}
				?>
				<tr>
					<td colspan=2> 
						<?php print getMaxUpload() ; ?>
						<input type="hidden" name="fileCount" value="<?php print $count ?>">
					</td>
				</tr>
				<?php
			}
			?>
			
			<tr class='break'>
				<td colspan=2> 
					<h3><? print _('Miscellaneous') ?></h3>
				</td>
			</tr>
			<tr>
				<td> 
					<b><? print _('How Did You Hear About Us?') ?> *</b><br/>
				</td>
				<td class="right">
					<?php
					$howDidYouHearList=getSettingByScope($connection2, "Application Form", "howDidYouHear") ;
					if ($howDidYouHearList=="") {
						print "<input name='howDidYouHear' id='howDidYouHear' maxlength=30 value='" . $row["howDidYouHear"] . "' type='text' style='width: 300px'>" ;
					}
					else {
						print "<select name='howDidYouHear' id='howDidYouHear' style='width: 302px'>" ;
							print "<option value='Please select...'>" . _('Please select...') . "</option>" ;
							$howDidYouHears=explode(",", $howDidYouHearList) ;
							foreach ($howDidYouHears as $howDidYouHear) {
								print "<option value='" . trim($howDidYouHear) . "'>" . trim($howDidYouHear) . "</option>" ;
							}
						print "</select>" ;
						?>
						<script type="text/javascript">
							var howDidYouHear=new LiveValidation('howDidYouHear');
							howDidYouHear.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print _('Select something!') ?>"});
						</script>
						<?php
					}
					?>
				</td>
			</tr>
			<script type="text/javascript">
				$(document).ready(function(){
					$("#howDidYouHear").change(function(){
						if ($('#howDidYouHear option:selected').val()=="Please select..." ) {
							$("#tellUsMoreRow").css("display","none");
						}
						else {
							$("#tellUsMoreRow").slideDown("fast", $("#tellUsMoreRow").css("display","table-row")); 
						}
					 });
				});
			</script>
			<tr id="tellUsMoreRow" style='display: none'>
				<td> 
					<b><? print _('Tell Us More') ?> </b><br/>
					<span style="font-size: 90%"><i><? print _('The name of a person or link to a website, etc.') ?></i></span>
				</td>
				<td class="right">
					<input name="howDidYouHearMore" id="howDidYouHearMore" maxlength=255 value="" type="text" style="width: 300px">
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
						<b><? print _('Privacy') ?> *</b><br/>
						<span style="font-size: 90%"><i><?php print htmlPrep($privacyBlurb) ?><br/>
						</i></span>
					</td>
					<td class="right">
						<?php
						$options=explode(",",$privacyOptions) ;
						foreach ($options AS $option) {
							print $option . " <input type='checkbox' name='privacyOptions[]' value='" . htmlPrep($option) . "'/><br/>" ;
						}
						?>

					</td>
				</tr>
				<?php
			}
			
			//Get agreement
			$agreement=getSettingByScope($connection2, 'Application Form', 'agreement') ;
			if ($agreement!="") {
				print "<tr class='break'>" ;
					print "<td colspan=2>" ; 
						print "<h3>" ; 
							print _("Agreement") ;
						print "</h3>" ;
						print "<p>" ;
							print $agreement ;
						print "</p>" ;
					print "</td>" ;
				print "</tr>" ;
				print "<tr>" ;
					print "<td>" ; 
						print "<b>" . _('Do you agree to the above?') . "</b><br/>" ;
					print "</td>" ;
					print "<td class='right'>" ;
						print "Yes <input type='checkbox' name='agreement' id='agreement'>" ;
						?>
						<script type="text/javascript">
							var agreement=new LiveValidation('agreement');
							agreement.add( Validate.Acceptance );
						 </script>
						 <?php
					print "</td>" ;
				print "</tr>" ;
			}
			?>
	
		
			<tr>
				<td>
					<span style="font-size: 90%"><i>* <?php print _("denotes a required field") ; ?></i></span>
				</td>
				<td class="right">
					<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
					<input type="submit" value="<?php print _("Submit") ; ?>">
				</td>
			</tr>
		</table>
	</form>	
	
	<?php
	//Get postscrript
	$postscript=getSettingByScope($connection2, 'Application Form', 'postscript') ;
	if ($postscript!="") {
		print "<h2>" ; 
			print _("Further Information") ;
		print "</h2>" ;
		print "<p style='padding-bottom: 15px'>" ;
			print $postscript ;
		print "</p>" ;
	}
}
?>