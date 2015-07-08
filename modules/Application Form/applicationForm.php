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


// START SECTION: function bi(...) & form for translation selection

function bi($string, $use_bi = null, $delimeter = null, $use_styling = null) {
	// Set argument default values, based on method suggested on:
    // http://stackoverflow.com/questions/9166914/using-default-arguments-in-a-function
	if (null === $use_bi) {
		$use_bi = False ;
	}
	if (null === $delimeter) {
		$delimeter = "<br />" ;
	}
	if (null === $use_styling) {
		$use_styling = True ;
	}

	// Get primary language string and then exit if bi not required
	$primary = gettext($string) ;

	// Return primary string if bi not required or if
	// the second language hasn't been set
	if (! $use_bi || $GLOBALS['bi_lang'] == "") {
		return $primary ;
	}

	// Get secondary language string
	$secondary = dgettext($GLOBALS['bi_lang'], $string) ;

	// Where secondary language string is the same, show alternative to duplicate text
	// Indicates missing translation or same language being used as bi_lang
	if ($secondary == $primary) {
		$secondary = $GLOBALS['bi_lang_missing_string'] ;
	}

	// Concetanate the complete string to be output
	$complete = $primary . $delimeter ;
	if ($use_styling) {
		$complete .= "<span" ;
		// $complete .= " dir='rtl'" ;
        $complete .= " style='color:#" . $GLOBALS['bi_lang_color'] . ";'" ;
		$complete .= ">" ;
	}
	$complete .= $secondary ;
	if ($use_styling) {
		$complete .= "</span>" ;
		// $complete .= "&lrm;" ;
		// $complete .= "&rlm;" ;
	}

	// Return the concatenated string
	return $complete ;
}


// Set the basic values related to bi_lang
$bi_lang = $_POST["biLang"] ;
$bi_lang_color = "22b" ;
$bi_lang_missing_string = "~~~" ;

if (bi_lang != "") {
    bindtextdomain($bi_lang, "./i18n");
    bind_textdomain_codeset($bi_lang, "UTF-8");
}


// Display the selection box to allow translation selection
print "<form method='post'>" ;
    print "<select name='biLang' id='biLang' style='width: 300px'>" ;
        try {
            $dataSelect=array(); 
            $sqlSelect="SELECT * FROM gibboni18n WHERE active='Y' ORDER BY name" ;
            $resultSelect=$connection2->prepare($sqlSelect);
            $resultSelect->execute($dataSelect);
        }
        catch(PDOException $e) { 
            print "<div class='error'>" . $e->getMessage() . "</div>" ; 
        }
        print "<option value=''>" . bi('No translation required...', True, ' | ', False) . "</option>" ;
        while ($rowSelect=$resultSelect->fetch()) {
            $selected="" ;
            if ($rowSelect["code"]==$bi_lang) {
                $selected="selected" ;
            }

            $code_baseLang = substr($rowSelect["code"], 0, strpos($rowSelect["code"], '_')) ;
            $session_baseLang = substr($_SESSION[$guid]["i18n"]["code"], 0, strpos($_SESSION[$guid]["i18n"]["code"], '_')) ;

            if ($code_baseLang!=$session_baseLang) {
                print "<option $selected value='" . $rowSelect["code"] . "'>" . htmlPrep($rowSelect["name"]) . "</option>" ;
            }
        }
    print "</select>" ;
    print "<input type='submit' value='" . bi('Submit', True, ' | ', false) . "'>" ;
print "</form>" ;

// END SECTION: function bi(...) & form for translation selection

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
		print bi("You do not have access to this action.", True) ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	if (isset($_SESSION[$guid]["username"])) {
		print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . bi("Home", True, ' | ') . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . bi(getModuleName($_GET["q"]), True, ' | ') . "</a> > </div><div class='trailEnd'>" . $_SESSION[$guid]["organisationNameShort"] . " " . bi('Application Form', True, ' | ') . "</div>" ;
	}
	else {
		print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . bi("Home", True, ' | ') . "</a> > </div><div class='trailEnd'>" . $_SESSION[$guid]["organisationNameShort"] . " " . bi('Application Form', True, ' | ') . "</div>" ;
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
		print "<p style='font-weight: bold; text-decoration: none; color: #c00'><i><u>" . sprintf(bi('If you have an %1$s %2$s account, please log in now to prevent creation of duplicate data about you! Once logged in, you can find the form under People > Data in the main menu.', True), $_SESSION[$guid]["organisationNameShort"], $_SESSION[$guid]["systemName"]) . "</u></i> " . sprintf(bi('If you do not have an %1$s %2$s account, please use the form below.', True), $_SESSION[$guid]["organisationNameShort"], $_SESSION[$guid]["systemName"]) . "</p>" ;
	}
	
	if (isset($_GET["addReturn"])) { $addReturn=$_GET["addReturn"] ; } else { $addReturn="" ; }
	$addReturnMessage="" ;
	$class="error" ;
	if (!($addReturn=="")) {
		if ($addReturn=="fail0") {
			$addReturnMessage=bi("Your request failed because you do not have access to this action.", True) ;	
		}
		else if ($addReturn=="fail2") {
			$addReturnMessage=bi("Your request failed due to a database error.", True) ;	
		}
		else if ($addReturn=="fail3") {
			$addReturnMessage=bi("Your request failed because your inputs were invalid.", True) ;	
		}
		else if ($addReturn=="fail4") {
			$addReturnMessage=bi("Your request failed because your inputs were invalid.", True) ;	
		}
		else if ($addReturn=="success0" OR $addReturn=="success1" OR $addReturn=="success2"  OR $addReturn=="success4") {
			if ($addReturn=="success0") {
				$addReturnMessage=bi("Your application was successfully submitted. Our admissions team will review your application and be in touch in due course.", True) ;
			}
			else if ($addReturn=="success1") {
				$addReturnMessage=bi("Your application was successfully submitted and payment has been made to your credit card. Our admissions team will review your application and be in touch in due course.", True) ;
			}
			else if ($addReturn=="success2") {
				$addReturnMessage=bi("Your application was successfully submitted, but payment could not be made to your credit card. Our admissions team will review your application and be in touch in due course.", True) ;
			}
			else if ($addReturn=="success3") {
				$addReturnMessage=bi("Your application was successfully submitted, payment has been made to your credit card, but there has been an error recording your payment. Please print this screen and contact the school ASAP. Our admissions team will review your application and be in touch in due course.", True) ;
			}
			else if ($addReturn=="success4") {
				$addReturnMessage=bi("Your application was successfully submitted, but payment could not be made as the payment gateway does not support the system's currency. Our admissions team will review your application and be in touch in due course.", True) ;
			}
			if (isset($_GET["id"])) {
				if ($_GET["id"]!="") {
					$addReturnMessage=$addReturnMessage . "<br/><br/>" . bi('If you need to contact the school in reference to this application, please quote the following number:', True) . " <b><u>" . $_GET["id"] . "</b></u>." ;
				}
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
			print bi("Please note that there is an application fee of:", True) . " <b><u>" . $currency . $applicationFee . "</u></b>." ;
			if ($enablePayments=="Y" AND $paypalAPIUsername!="" AND $paypalAPIPassword!="" AND $paypalAPISignature!="") {
				print " " . bi('Payment must be made by credit card, using our secure PayPal payment gateway. When you press Submit at the end of this form, you will be directed to PayPal in order to make payment. During this process we do not see or store your credit card details.', True) ;
			}
		print "</div>" ;
	}
	
	?>
	
	<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/applicationFormProcess.php" ?>" enctype="multipart/form-data">
		<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
			<tr class='break'>
				<td colspan=2> 
					<h3><?php print bi('Student', True) ?></h3>
				</td>
			</tr>
			
			<tr>
				<td colspan=2> 
					<h4><?php print bi('Student Personal Data', True) ?></h4>
				</td>
			</tr>
			<tr>
				<td style='width: 275px'> 
					<b><?php print bi('Surname', True) ?> *</b><br/>
					<span style="font-size: 90%"><i><?php print bi('Family name as shown in ID documents.', True) ?></i></span>
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
					<b><?php print bi('First Name', True) ?> *</b><br/>
					<span style="font-size: 90%"><i><?php print bi('First name as shown in ID documents.', True) ?></i></span>
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
					<b><?php print bi('Preferred Name', True) ?> *</b><br/>
					<span style="font-size: 90%"><i><?php print bi('Most common name, alias, nickname, etc.', True) ?></i></span>
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
					<b><?php print bi('Official Name', True) ?> *</b><br/>
					<span style="font-size: 90%"><i><?php print bi('Full name as shown in ID documents.', True) ?></i></span>
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
					<b><?php print bi('Name In Characters', True) ?></b><br/>
					<span style="font-size: 90%"><i><?php print bi('Chinese or other character-based name.', True) ?></i></span>
				</td>
				<td class="right">
					<input name="nameInCharacters" id="nameInCharacters" maxlength=20 value="" type="text" style="width: 300px">
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print bi('Gender', True) ?> *</b><br/>
				</td>
				<td class="right">
					<select name="gender" id="gender" style="width: 302px">
						<option value="Please select..."><?php print bi('Please select...', True, ' | ', False) ?></option>
						<option value="F"><?php print bi('Female', True, ' | ', False) ?></option>
						<option value="M"><?php print bi('Male', True, ' | ', False) ?></option>
					</select>
					<script type="text/javascript">
						var gender=new LiveValidation('gender');
						gender.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print bi('Select something!', True, ' | ', False) ?>"});
					 </script>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print bi('Date of Birth', True) ?> *</b><br/>
					<span style="font-size: 90%"><i><?php print bi('Format:', True) . " " . $_SESSION[$guid]["i18n"]["dateFormat"]  ?></i></span>
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
					<h4><?php print bi('Student Background', True) ?></h4>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print bi('Home Language', True) ?> *</b><br/>
					<span style="font-size: 90%"><i><?php print bi('The primary language used in the student\'s home.', True) ?></i></span>
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
					<b><?php print bi('First Language', True) ?> *</b><br/>
					<span style="font-size: 90%"><i><?php print bi('Student\'s native/first/mother language.', True) ?></i></span>
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
					<b><?php print bi('Second Language', True) ?></b><br/>
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
					<b><?php print bi('Third Language', True) ?></b><br/>
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
					<b><?php print bi('Country of Birth', True) ?></b><br/>
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
							print "<option value='" . $rowSelect["printable_name"] . "'>" . htmlPrep(bi($rowSelect["printable_name"], True, ' | ', False)) . "</option>" ;
						}
						?>				
					</select>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print bi('Citizenship', True) ?></b><br/>
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
								print "<option value='" . $rowSelect["printable_name"] . "'>" . htmlPrep(bi($rowSelect["printable_name"], True, ' | ', False)) . "</option>" ;
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
					<b><?php print bi('Citizenship Passport Number', True) ?></b><br/>
				</td>
				<td class="right">
					<input name="citizenship1Passport" id="citizenship1Passport" maxlength=30 value="" type="text" style="width: 300px">
				</td>
			</tr>
			<tr>
				<td> 
					<?php
					if ($_SESSION[$guid]["country"]=="") {
						print "<b>" . bi('National ID Card Number', True) . "</b><br/>" ;
					}
					else {
						print "<b>" . $_SESSION[$guid]["country"] . " " . bi('ID Card Number', True) . "</b><br/>" ;
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
						print "<b>" . bi('Residency/Visa Type', True) . "</b><br/>" ;
					}
					else {
						print "<b>" . $_SESSION[$guid]["country"] . " " . bi('Residency/Visa Type', True) . "</b><br/>" ;
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
						print "<b>" . bi('Visa Expiry Date', True) . "</b><br/>" ;
					}
					else {
						print "<b>" . $_SESSION[$guid]["country"] . " " . bi('Visa Expiry Date', True) . "</b><br/>" ;
					}
					print "<span style='font-size: 90%'><i>Format: " ; if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; } print ". " . bi('If relevant.', True) . "</i></span>" ;
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
					<h4><?php print bi('Student Contact', True) ?></h4>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print bi('Email', True) ?></b><br/>
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
						<b><?php print bi('Phone', True, ' | ') ?> <?php print $i ?></b><br/>
						<span style="font-size: 90%"><i><?php print bi('Type, country code, number.', True) ?></i></span>
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
								print "<option value='" . $rowSelect["iddCountryCode"] . "'>" . htmlPrep($rowSelect["iddCountryCode"]) . " - " .  htmlPrep(bi($rowSelect["printable_name"], True, ' | ', False)) . "</option>" ;
							}
							?>				
						</select>
						<select style="width: 70px" name="phone<?php print $i ?>Type">
							<option value=""></option>
							<option value="Mobile"><?php print bi('Mobile', True, ' | ', False) ?></option>
							<option value="Home"><?php print bi('Home', True, ' | ', False) ?></option>
							<option value="Work"><?php print bi('Work', True, ' | ', False) ?></option>
							<option value="Fax"><?php print bi('Fax', True, ' | ', False) ?></option>
							<option value="Pager"><?php print bi('Pager', True, ' | ', False) ?></option>
							<option value="Other"><?php print bi('Other', True, ' | ', False) ?></option>
						</select>
					</td>
				</tr>
				<?php
			}
			?>
			
			
			<tr>
				<td colspan=2> 
					<h4><?php print bi('Student Medical & Development', True) ?></h4>
				</td>
			</tr>
			<tr>
				<td colspan=2 style='padding-top: 15px'> 
					<b><?php print bi('Medical Information', True) ?></b><br/>
					<span style="font-size: 90%"><i><?php print bi('Please indicate any medical conditions.', True) ?></i></span><br/>
					<textarea name="medicalInformation" id="medicalInformation" rows=5 style="width:738px; margin: 5px 0px 0px 0px"></textarea>
				</td>
			</tr>
			<tr>
				<td colspan=2 style='padding-top: 15px'> 
					<b><?php print bi('Development Information', True) ?></b><br/>
					<span style="font-size: 90%"><i><?php print bi('Provide any comments or information concerning your child\'s development that may be relevant to your child\'s performance in the classroom or elsewhere? (Incorrect or withheld information may affect continued enrolment).', True) ?></i></span><br/> 					
					<textarea name="developmentInformation" id="developmentInformation" rows=5 style="width:738px; margin: 5px 0px 0px 0px"></textarea>
				</td>
			</tr>
			
			
			
			<tr>
				<td colspan=2> 
					<h4><?php print bi('Student Education', True) ?></h4>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print bi('Anticipated Year of Entry', True) ?> *</b><br/>
					<span style="font-size: 90%"><i><?php print bi('What school year will the student join in?', True) ?></i></span>
				</td>
				<td class="right">
					<select name="gibbonSchoolYearIDEntry" id="gibbonSchoolYearIDEntry" style="width: 302px">
						<?php
						print "<option value='Please select...'>" . bi('Please select...', True, ' | ', False) . "</option>" ;
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
						gibbonSchoolYearIDEntry.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print bi('Select something!', True, ' | ', False) ?>"});
					 </script>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print bi('Intended Start Date', True) ?> *</b><br/>
					<span style="font-size: 90%"><i><?php print bi('Student\'s intended first day at school.', True) ?><br/><?php print bi('Format:', True) ?> <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?></i></span>
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
					<b><?php print bi('Year Group at Entry', True) ?> *</b><br/>
					<span style="font-size: 90%"><i><?php print bi('Which year level will student enter.', True) ?></i></span>
				</td>
				<td class="right">
					<select name="gibbonYearGroupIDEntry" id="gibbonYearGroupIDEntry" style="width: 302px">
						<?php
						print "<option value='Please select...'>" . bi('Please select...', True, ' | ', False) . "</option>" ;
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
							print "<option value='" . $rowSelect["gibbonYearGroupID"] . "'>" . htmlPrep(bi($rowSelect["name"], True, ' | ', False)) . "</option>" ;
						}
						?>				
					</select>
					<script type="text/javascript">
						var gibbonYearGroupIDEntry=new LiveValidation('gibbonYearGroupIDEntry');
						gibbonYearGroupIDEntry.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print bi('Select something!', True, ' | ', False) ?>"});
					 </script>
				</td>
			</tr>
			
			<?php
			$dayTypeOptions=getSettingByScope($connection2, 'User Admin', 'dayTypeOptions') ;
			if ($dayTypeOptions!="") {
				?>
				<tr>
					<td> 
						<b><?php print bi('Day Type', True) ?></b><br/>
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
					<b><?php print bi('Previous Schools', True) ?> *</b><br/>
					<span style="font-size: 90%"><i><?php print bi('Please give information on the last two schools attended by the applicant.', True) ?></i></span>
				</td>
			</tr>
			<tr>
				<td colspan=2> 
					<?php
					print "<table cellspacing='0' style='width: 100%'>" ;
						print "<tr class='head'>" ;
							print "<th>" ;
								print bi("School Name", True) ;
							print "</th>" ;
							print "<th>" ;
								print bi("Address", True) ;
							print "</th>" ;
							print "<th>" ;
								print sprintf(bi('Grades%1$sAttended', True), "<br/>") ;
							print "</th>" ;
							print "<th>" ;
								print sprintf(bi('Language of%1$sInstruction', True), "<br/>") ;
							print "</th>" ;
							print "<th>" ;
								print bi("Joining Date", True) . "<br/><span style='font-size: 80%'>" ; if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; } print "</span>" ;
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
							<?php print bi('Home Address', True) ?>
						</h3>
						<p>
							<?php print bi('This address will be used for all members of the family. If an individual within the family needs a different address, this can be set through Data Updater after admission.', True) ?>
						</p>
					</td>
				</tr>
				<tr>
					<td> 
						<b><?php print bi('Home Address', True) ?> *</b><br/>
						<span style="font-size: 90%"><i><?php print bi('Unit, Building, Street', True) ?></i></span>
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
						<b><?php print bi('Home Address (District)', True) ?> *</b><br/>
						<span style="font-size: 90%"><i><?php print bi('County, State, District', True) ?></i></span>
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
						<b><?php print bi('Home Address (Country)', True) ?> *</b><br/>
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
							print "<option value='Please select...'>" . bi('Please select...', True, ' | ', False) . "</option>" ;
							while ($rowSelect=$resultSelect->fetch()) {
								print "<option value='" . $rowSelect["printable_name"] . "'>" . htmlPrep(bi($rowSelect["printable_name"], True, ' | ', False)) . "</option>" ;
							}
							?>				
						</select>
						<script type="text/javascript">
							var homeAddressCountry=new LiveValidation('homeAddressCountry');
							homeAddressCountry.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print bi('Select something!', True, ' | ', False) ?>"});
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
								<?php print bi('Parent/Guardian 1', True) ?>
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
							<b><?php print bi('Username', True) ?></b><br/>
							<span style="font-size: 90%"><i><?php print bi('System login ID.', True) ?></i></span>
						</td>
						<td class="right">
							<input readonly name='parent1username' maxlength=30 value="<?php print $_SESSION[$guid]["username"] ?>" type="text" style="width: 300px">
						</td>
					</tr>
					
					<tr>
						<td> 
							<b><?php print bi('Surname', True) ?></b><br/>
							<span style="font-size: 90%"><i><?php print bi('Family name as shown in ID documents.', True) ?></i></span>
						</td>
						<td class="right">
							<input readonly name='parent1surname' maxlength=30 value="<?php print $_SESSION[$guid]["surname"] ?>" type="text" style="width: 300px">
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print bi('Preferred Name', True) ?></b><br/>
							<span style="font-size: 90%"><i><?php print bi('Most common name, alias, nickname, etc.', True) ?></i></span>
						</td>
						<td class="right">
							<input readonly name='parent1preferredName' maxlength=30 value="<?php print $_SESSION[$guid]["preferredName"] ?>" type="text" style="width: 300px">
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print bi('Relationship', True) ?> *</b><br/>
						</td>
						<td class="right">
							<select name="parent1relationship" id="parent1relationship" style="width: 302px">
								<option value="Please select..."><?php print bi('Please select...', True, ' | ', False) ?></option>
								<option value="Mother"><?php print bi('Mother', True, ' | ', False) ?></option>
								<option value="Father"><?php print bi('Father', True, ' | ', False) ?></option>
								<option value="Step-Mother"><?php print bi('Step-Mother', True, ' | ', False) ?></option>
								<option value="Step-Father"><?php print bi('Step-Father', True, ' | ', False) ?></option>
								<option value="Adoptive Parent"><?php print bi('Adoptive Parent', True, ' | ', False) ?></option>
								<option value="Guardian"><?php print bi('Guardian', True, ' | ', False) ?></option>
								<option value="Grandmother"><?php print bi('Grandmother', True, ' | ', False) ?></option>
								<option value="Grandfather"><?php print bi('Grandfather', True, ' | ', False) ?></option>
								<option value="Aunt"><?php print bi('Aunt', True, ' | ', False) ?></option>
								<option value="Uncle"><?php print bi('Uncle', True, ' | ', False) ?></option>
								<option value="Nanny/Helper"><?php print bi('Nanny/Helper', True, ' | ', False) ?></option>
								<option value="Other"><?php print bi('Other', True, ' | ', False) ?></option>
							</select>
							<script type="text/javascript">
								var parent1relationship=new LiveValidation('parent1relationship');
								parent1relationship.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print bi('Select something!', True, ' | ', False) ?>"});
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
								<?php print bi('Parent/Guardian', True, ' | ') ?> <?php print $i ?>
								<?php
								if ($i==1) {
									print "<span style='font-size: 75%'> " . bi('(e.g. mother)', True, ' | ') . "</span>" ;
								}
								else if ($i==2 AND $gibbonPersonID=="") {
									print "<span style='font-size: 75%'> " . bi('(e.g. father)', True, ' | ') . "</span>" ;
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
								<span style='font-weight: bold; font-style: italic'><?php print bi('Do not include a second parent/guardian', True, ' | ') ?> <input id='secondParent' name='secondParent' type='checkbox' value='No'/></span>
							</td>
						</tr>
						<?php
					}
					?>
					<tr <?php if ($i==2) { print "class='secondParent'" ; }?>>
						<td colspan=2> 
							<h4><?php print bi('Parent/Guardian', True, ' | ') ?> <?php print $i ?> <?php print bi('Personal Data', True, ' | ') ?></h4>
						</td>
					</tr>
					<tr <?php if ($i==2) { print "class='secondParent'" ; }?>>
						<td> 
							<b><?php print bi('Title', True) ?> *</b><br/>
							<span style="font-size: 90%"><i></i></span>
						</td>
						<td class="right">
							<select style="width: 302px" id="<?php print "parent$i" ?>title" name="<?php print "parent$i" ?>title">
								<option value="Please select..."><?php print bi('Please select...', True, ' | ', False) ?></option>
								<option value="Ms."><?php print bi('Ms.', True, ' | ', False) ?></option>
								<option value="Miss"><?php print bi('Miss', True, ' | ', False) ?></option>
								<option value="Mr."><?php print bi('Mr.', True, ' | ', False) ?></option>
								<option value="Mrs."><?php print bi('Mrs.', True, ' | ', False) ?></option>
								<option value="Dr."><?php print bi('Dr.', True, ' | ', False) ?></option>
							</select>
							<script type="text/javascript">
								var <?php print "parent$i" ?>title=new LiveValidation('<?php print "parent$i" ?>title');
								<?php print "parent$i" ?>title.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print bi('Select something!', True, ' | ', False) ?>"});
							 </script>
						</td>
					</tr>
					<tr <?php if ($i==2) { print "class='secondParent'" ; }?>>
						<td> 
							<b><?php print bi('Surname', True) ?> *</b><br/>
							<span style="font-size: 90%"><i><?php print bi('Family name as shown in ID documents.', True) ?></i></span>
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
							<b><?php print bi('First Name', True) ?> *</b><br/>
							<span style="font-size: 90%"><i><?php print bi('First name as shown in ID documents.', True) ?></i></span>
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
							<b><?php print bi('Preferred Name', True) ?> *</b><br/>
							<span style="font-size: 90%"><i><?php print bi('Most common name, alias, nickname, etc.', True) ?></i></span>
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
							<b><?php print bi('Official Name', True) ?> *</b><br/>
							<span style="font-size: 90%"><i><?php print bi('Full name as shown in ID documents.', True) ?></i></span>
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
							<b><?php print bi('Name In Characters', True) ?></b><br/>
							<span style="font-size: 90%"><i><?php print bi('Chinese or other character-based name.', True) ?></i></span>
						</td>
						<td class="right">
							<input name="<?php print "parent$i" ?>nameInCharacters" id="<?php print "parent$i" ?>nameInCharacters" maxlength=20 value="" type="text" style="width: 300px">
						</td>
					</tr>
					<tr <?php if ($i==2) { print "class='secondParent'" ; }?>>
						<td> 
							<b><?php print bi('Gender', True) ?> *</b><br/>
						</td>
						<td class="right">
							<select name="<?php print "parent$i" ?>gender" id="<?php print "parent$i" ?>gender" style="width: 302px">
								<option value="Please select..."><?php print bi('Please select...', True, ' | ', False) ?></option>
                                <option value="F"><?php print bi('Female', True, ' | ', False) ?></option>
                                <option value="M"><?php print bi('Male', True, ' | ', False) ?></option>
							</select>
							<script type="text/javascript">
								var <?php print "parent$i" ?>gender=new LiveValidation('<?php print "parent$i" ?>gender');
								<?php print "parent$i" ?>gender.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print bi('Select something!', True, ' | ', False) ?>"});
							 </script>
						</td>
					</tr>
					<tr <?php if ($i==2) { print "class='secondParent'" ; }?>>
						<td> 
							<b><?php print bi('Relationship', True) ?> *</b><br/>
						</td>
						<td class="right">
							<select name="<?php print "parent$i" ?>relationship" id="<?php print "parent$i" ?>relationship" style="width: 302px">
								<option value="Please select..."><?php print bi('Please select...', True, ' | ', False) ?></option>
								<option value="Mother"><?php print bi('Mother', True, ' | ', False) ?></option>
								<option value="Father"><?php print bi('Father', True, ' | ', False) ?></option>
								<option value="Step-Mother"><?php print bi('Step-Mother', True, ' | ', False) ?></option>
								<option value="Step-Father"><?php print bi('Step-Father', True, ' | ', False) ?></option>
								<option value="Adoptive Parent"><?php print bi('Adoptive Parent', True, ' | ', False) ?></option>
								<option value="Guardian"><?php print bi('Guardian', True, ' | ', False) ?></option>
								<option value="Grandmother"><?php print bi('Grandmother', True, ' | ', False) ?></option>
								<option value="Grandfather"><?php print bi('Grandfather', True, ' | ', False) ?></option>
								<option value="Aunt"><?php print bi('Aunt', True, ' | ', False) ?></option>
								<option value="Uncle"><?php print bi('Uncle', True, ' | ', False) ?></option>
								<option value="Nanny/Helper"><?php print bi('Nanny/Helper', True, ' | ', False) ?></option>
								<option value="Other"><?php print bi('Other', True, ' | ', False) ?></option>
							</select>
							<script type="text/javascript">
								var <?php print "parent$i" ?>relationship=new LiveValidation('<?php print "parent$i" ?>relationship');
								<?php print "parent$i" ?>relationship.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print bi('Select something!', True, ' | ', False) ?>"});
							 </script>
						</td>
					</tr>
					
					<tr <?php if ($i==2) { print "class='secondParent'" ; }?>>
						<td colspan=2> 
							<h4><?php print bi('Parent/Guardian', True, ' | ') ?> <?php print $i ?> <?php print bi('Personal Background', True, ' | ') ?></h4>
						</td>
					</tr>
					<tr <?php if ($i==2) { print "class='secondParent'" ; }?>>
						<td> 
							<b><?php print bi('First Language', True) ?></b><br/>
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
							<b><?php print bi('Second Language', True) ?></b><br/>
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
							<b><?php print bi('Citizenship', True) ?></b><br/>
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
										print "<option value='" . $rowSelect["printable_name"] . "'>" . htmlPrep(bi($rowSelect["printable_name"], True, ' | ', False)) . "</option>" ;
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
								print "<b>" . bi('National ID Card Number', True) . "</b><br/>" ;
							}
							else {
								print "<b>" . $_SESSION[$guid]["country"] . " " . bi('ID Card Number', True) . "</b><br/>" ;
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
								print "<b>" . bi('Residency/Visa Type', True) . "</b><br/>" ;
							}
							else {
								print "<b>" . $_SESSION[$guid]["country"] . " " . bi('Residency/Visa Type', True) . "</b><br/>" ;
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
								print "<b>" . bi('Visa Expiry Date', True) . "</b><br/>" ;
							}
							else {
								print "<b>" . $_SESSION[$guid]["country"] . " " . bi('Visa Expiry Date', True) . "</b><br/>" ;
							}
							print "<span style='font-size: 90%'><i>" . bi('Format:', True) . " " ; if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; } print ". " . bi('If relevant.', True) . "</i></span>" ;
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
							<h4><?php print bi('Parent/Guardian', True, ' | ') ?> <?php print $i ?> <?php print bi('Contact', True, ' | ') ?></h4>
						</td>
					</tr>
					<tr <?php if ($i==2) { print "class='secondParent'" ; }?>>
						<td> 
							<b><?php print bi('Email', True) ?> *</b><br/>
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
								<b><?php print bi('Phone', True, ' | ') ?> <?php print $y ; if ($y==1) { print " *" ;}?></b><br/>
								<span style="font-size: 90%"><i><?php print bi('Type, country code, number.', True) ?></i></span>
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
										print "<option value='" . $rowSelect["iddCountryCode"] . "'>" . htmlPrep($rowSelect["iddCountryCode"]) . " - " .  htmlPrep(bi($rowSelect["printable_name"], True, ' | ', False)) . "</option>" ;
									}
									?>				
								</select>
								<select style="width: 70px" name="<?php print "parent$i" ?>phone<?php print $y ?>Type">
									<option value=""></option>
                                    <option value="Mobile"><?php print bi('Mobile', True, ' | ', False) ?></option>
                                    <option value="Home"><?php print bi('Home', True, ' | ', False) ?></option>
                                    <option value="Work"><?php print bi('Work', True, ' | ', False) ?></option>
                                    <option value="Fax"><?php print bi('Fax', True, ' | ', False) ?></option>
                                    <option value="Pager"><?php print bi('Pager', True, ' | ', False) ?></option>
                                    <option value="Other"><?php print bi('Other', True, ' | ', False) ?></option>
								</select>
							</td>
						</tr>
						<?php
					}
					?>
					
					<tr <?php if ($i==2) { print "class='secondParent'" ; }?>>
						<td colspan=2> 
							<h4><?php print bi('Parent/Guardian', True, ' | ') ?> <?php print $i ?> <?php print bi('Employment', True, ' | ') ?></h4>
						</td>
					</tr>
					<tr <?php if ($i==2) { print "class='secondParent'" ; }?>>
						<td> 
							<b><?php print bi('Profession', True) ?> *</b><br/>
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
							<b><?php print bi('Employer', True) ?></b><br/>
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
						<h3><?php print bi('Family', True) ?></h3>
						<p><?php print bi('Choose the family you wish to associate this application with.', True) ?></p>
						<?php
						print "<table cellspacing='0' style='width: 100%'>" ;
							print "<tr class='head'>" ;
								print "<th>" ;
									print bi("Family Name", True) ;
								print "</th>" ;
								print "<th>" ;
									print bi("Selected", True) ;
								print "</th>" ;
								print "<th>" ;
									print bi("Relationships", True) ;
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
													<option <?php if ($rowRelationships["gender"]=="F") { print "selected" ; } ?> value="Mother"><?php print bi('Mother', True) ?></option>
													<option <?php if ($rowRelationships["gender"]=="M") { print "selected" ; } ?> value="Father"><?php print bi('Father', True) ?></option>
													<option value="Step-Mother"><?php print bi('Step-Mother', True) ?></option>
													<option value="Step-Father"><?php print bi('Step-Father', True) ?></option>
													<option value="Adoptive Parent"><?php print bi('Adoptive Parent', True) ?></option>
													<option value="Guardian"><?php print bi('Guardian', True) ?></option>
													<option value="Grandmother"><?php print bi('Grandmother', True) ?></option>
													<option value="Grandfather"><?php print bi('Grandfather', True) ?></option>
													<option value="Aunt"><?php print bi('Aunt', True) ?></option>
													<option value="Uncle"><?php print bi('Uncle', True) ?></option>
													<option value="Nanny/Helper"><?php print bi('Nanny/Helper', True) ?></option>
													<option value="Other"><?php print bi('Other', True) ?></option>
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
					<h3><?php print bi('Siblings', True) ?></h3>
				</td>
			</tr>
			<tr>
				<td colspan=2 style='padding-top: 0px'> 
					<p><?php print bi('Please give information on the applicants\'s siblings.', True) ?></p>
				</td>
			</tr>
			<tr>
				<td colspan=2> 
					<?php
					print "<table cellspacing='0' style='width: 100%'>" ;
						print "<tr class='head'>" ;
							print "<th>" ;
								print bi("Sibling Name", True) ;
							print "</th>" ;
							print "<th>" ;
								print bi("Date of Birth", True) . "<br/><span style='font-size: 80%'>" . $_SESSION[$guid]["i18n"]["dateFormat"] . "</span>" ;
							print "</th>" ;
							print "<th>" ;
								print bi("School Attending", True) ;
							print "</th>" ;
							print "<th>" ;
								print bi("Joining Date", True) . "<br/><span style='font-size: 80%'>" . $_SESSION[$guid]["i18n"]["dateFormat"] . "</span>" ;
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
						<h3><?php print bi('Language Selection', True) ?></h3>
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
						<b><?php print bi('Language Choice', True) ?> *</b><br/>
						<span style="font-size: 90%"><i><?php  print bi('Please choose preferred additional language to study.', True) ?></i></span>
					</td>
					<td class="right">
						<select name="languageChoice" id="languageChoice" style="width: 302px">
							<?php
							print "<option value='Please select...'>" . bi('Please select...', True, ' | ', False) . "</option>" ;
							$languageOptionsLanguageList=getSettingByScope($connection2, "Application Form", "languageOptionsLanguageList") ;
							$languages=explode(",", $languageOptionsLanguageList) ;
							foreach ($languages as $language) {
								print "<option value='" . trim($language) . "'>" . trim($language) . "</option>" ;
							}
							?>				
						</select>
						<script type="text/javascript">
							var languageChoice=new LiveValidation('languageChoice');
							languageChoice.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print bi('Select something!', True, ' | ', False) ?>"});
						 </script>
					</td>
				</tr>
				<tr>
					<td colspan=2 style='padding-top: 15px'> 
						<b><?php print bi('Language Choice Experience', True) ?> *</b><br/>
						<span style="font-size: 90%"><i><?php print bi('Has the applicant studied the selected language before? If so, please describe the level and type of experience.', True) ?></i></span><br/> 					
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
					<h3><?php print bi('Scholarships', True) ?></h3>
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
					<b><?php print bi('Interest', True) ?></b><br/>
					<span style="font-size: 90%"><i><?php print bi('Indicate if you are interested in a scholarship.', True) ?></i></span><br/>
				</td>
				<td class="right">
					<input type="radio" id="scholarshipInterest" name="scholarshipInterest" class="type" value="Y" /> <?php print bi('Yes', True, ' | ') ?>
					<input checked type="radio" id="scholarshipInterest" name="scholarshipInterest" class="type" value="N" /> <?php print bi('No', True, ' | ') ?>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print bi('Required?', True) ?></b><br/>
					<span style="font-size: 90%"><i><?php print bi('Is a scholarship required for you to take up a place at the school?', True) ?></i></span><br/>
				</td>
				<td class="right">
					<input type="radio" id="scholarshipRequired" name="scholarshipRequired" class="type" value="Y" /> <?php print bi('Yes', True, ' | ') ?>
					<input checked type="radio" id="scholarshipRequired" name="scholarshipRequired" class="type" value="N" /> <?php print bi('No', True, ' | ') ?>
				</td>
			</tr>
			
			
			<tr class='break'>
				<td colspan=2> 
					<h3><?php print bi('Payment', True) ?></h3>
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
					<p><?php print bi('If you choose family, future invoices will be sent according to your family\'s contact preferences, which can be changed at a later date by contacting the school. For example you may wish both parents to receive the invoice, or only one. Alternatively, if you choose Company, you can choose for all or only some fees to be covered by the specified company.', True) ?></p>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print bi('Send Future Invoices To', True) ?></b><br/>
				</td>
				<td class="right">
					<input type="radio" name="payment" value="Family" class="payment" checked /> <?php print bi('Family', True, ' | ') ?>
					<input type="radio" name="payment" value="Company" class="payment" /> <?php print bi('Company', True, ' | ') ?>
				</td>
			</tr>
			<tr id="companyNameRow">
				<td> 
					<b><?php print bi('Company Name', True) ?></b><br/>
				</td>
				<td class="right">
					<input name="companyName" id="companyName" maxlength=100 value="" type="text" style="width: 300px">
				</td>
			</tr>
			<tr id="companyContactRow">
				<td> 
					<b><?php print bi('Company Contact Person', True) ?></b><br/>
				</td>
				<td class="right">
					<input name="companyContact" id="companyContact" maxlength=100 value="" type="text" style="width: 300px">
				</td>
			</tr>
			<tr id="companyAddressRow">
				<td> 
					<b><?php print bi('Company Address', True) ?></b><br/>
				</td>
				<td class="right">
					<input name="companyAddress" id="companyAddress" maxlength=255 value="" type="text" style="width: 300px">
				</td>
			</tr>
			<tr id="companyEmailRow">
				<td> 
					<b><?php print bi('Company Email', True) ?></b><br/>
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
					<b><?php print bi('CC Family?', True) ?></b><br/>
					<span style="font-size: 90%"><i><?php print bi('Should the family be sent a copy of billing emails?', True) ?></i></span>
				</td>
				<td class="right">
					<select name="companyCCFamily" id="companyCCFamily" style="width: 302px">
						<option value="N" /> <?php print bi('No', True, ' | ', False) ?>
						<option value="Y" /> <?php print bi('Yes', True, ' | ', False) ?>
					</select>
				</td>
			</tr>
			<tr id="companyPhoneRow">
				<td> 
					<b><?php print bi('Company Phone', True) ?></b><br/>
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
						<b><?php print bi('Company All?', True) ?></b><br/>
						<span style="font-size: 90%"><i><?php print bi('Should all items be billed to the specified company, or just some?', True) ?></i></span>
					</td>
					<td class="right">
						<input type="radio" name="companyAll" value="Y" class="companyAll" checked /> <?php print bi('All', True, ' | ') ?>
						<input type="radio" name="companyAll" value="N" class="companyAll" /> <?php print bi('Selected', True, ' | ') ?>
					</td>
				</tr>
				<tr id="companyCategoriesRow">
					<td> 
						<b><?php print bi('Company Fee Categories', True) ?></b><br/>
						<span style="font-size: 90%"><i><?php print bi('If the specified company is not paying all fees, which categories are they paying?', True) ?></i></span>
					</td>
					<td class="right">
						<?php
						while ($rowCat=$resultCat->fetch()) {
							print bi($rowCat["name"], True, ' | ') . " <input type='checkbox' name='gibbonFinanceFeeCategoryIDList[]' value='" . $rowCat["gibbonFinanceFeeCategoryID"] . "'/><br/>" ;
						}
						print bi("Other", True, ' | ') . " <input type='checkbox' name='gibbonFinanceFeeCategoryIDList[]' value='0001'/><br/>" ;
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
						<h3><?php print bi('Supporting Documents', True) ?></h3>
						<?php 
						if ($requiredDocumentsText!="" OR $requiredDocumentsCompulsory!="") {
							print "<p>" ;
								print $requiredDocumentsText . " " ;
								if ($requiredDocumentsCompulsory=="Y") {
									print bi("These documents must all be included before the application can be submitted.", True) ;
								}
								else {
									print bi("These documents are all required, but can be submitted separately to this form if preferred. Please note, however, that your application will be processed faster if the documents are included here.", True) ;
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
					<h3><?php print bi('Miscellaneous', True) ?></h3>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print bi('How Did You Hear About Us?', True) ?> *</b><br/>
				</td>
				<td class="right">
					<?php
					$howDidYouHearList=getSettingByScope($connection2, "Application Form", "howDidYouHear") ;
					if ($howDidYouHearList=="") {
						print "<input name='howDidYouHear' id='howDidYouHear' maxlength=30 value='" . $row["howDidYouHear"] . "' type='text' style='width: 300px'>" ;
					}
					else {
						print "<select name='howDidYouHear' id='howDidYouHear' style='width: 302px'>" ;
							print "<option value='Please select...'>" . bi('Please select...', True, ' | ', False) . "</option>" ;
							$howDidYouHears=explode(",", $howDidYouHearList) ;
							foreach ($howDidYouHears as $howDidYouHear) {
								print "<option value='" . trim($howDidYouHear) . "'>" . bi(trim($howDidYouHear), True, ' | ', False) . "</option>" ;
							}
						print "</select>" ;
						?>
						<script type="text/javascript">
							var howDidYouHear=new LiveValidation('howDidYouHear');
							howDidYouHear.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print bi('Select something!', True, ' | ', False) ?>"});
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
					<b><?php print bi('Tell Us More', True) ?> </b><br/>
					<span style="font-size: 90%"><i><?php print bi('The name of a person or link to a website, etc.', True) ?></i></span>
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
						<b><?php print bi('Privacy', True) ?> *</b><br/>
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
							print bi("Agreement", True) ;
						print "</h3>" ;
						print "<p>" ;
							print $agreement ;
						print "</p>" ;
					print "</td>" ;
				print "</tr>" ;
				print "<tr>" ;
					print "<td>" ; 
						print "<b>" . bi('Do you agree to the above?', True) . "</b><br/>" ;
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
					<span style="font-size: 90%"><i>* <?php print bi("denotes a required field", True) ; ?></i></span>
				</td>
				<td class="right">
					<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
					<input type="submit" value="<?php print bi("Submit", True, ' | ', false) ; ?>">
				</td>
			</tr>
		</table>
	</form>	
	
	<?php
	//Get postscrript
	$postscript=getSettingByScope($connection2, 'Application Form', 'postscript') ;
	if ($postscript!="") {
		print "<h2>" ; 
			print bi("Further Information", True) ;
		print "</h2>" ;
		print "<p style='padding-bottom: 15px'>" ;
			print $postscript ;
		print "</p>" ;
	}
}
?>