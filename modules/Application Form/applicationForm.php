<?
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

session_start() ;

$proceed=FALSE ;
$public=FALSE ;

if ($_SESSION[$guid]["username"]=="") {
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


if ($proceed==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "You do not have access to this action." ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	if ($_SESSION[$guid]["username"]!="") {
		print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>Home</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > </div><div class='trailEnd'>" . $_SESSION[$guid]["organisationNameShort"] . " Application Form</div>" ;
	}
	else {
		print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>Home</a> > </div><div class='trailEnd'>" . $_SESSION[$guid]["organisationNameShort"] . " Application Form</div>" ;
	}
	print "</div>" ;
	
	//Get intro
	$intro=getSettingByScope($connection2, 'Application Form', 'introduction') ;
	if ($intro!="") {
		print "<p>" ;
			print $intro ;
			if ($_SESSION[$guid]["username"]=="") {
				print "<br/><br/>" ;
				print "<span style='font-weight: bold; text-decoration: none; font-size: 115%; color: #c00'><i><u>If you have an " . $_SESSION[$guid]["organisationNameShort"] . " " . $_SESSION[$guid]["systemName"] . " account, please log in now to prevent creation of duplicate data about you!</u></i> Once logged in, you can find the form under People > Data in the main menu.<br/>" ;
				print "<br/>" ;
				print "If you do not have an " . $_SESSION[$guid]["organisationNameShort"] . " " . $_SESSION[$guid]["systemName"] . " account, please use the form below.</span>" ;
			}
		print "</p>" ;
	}
	
	$addReturn = $_GET["addReturn"] ;
	$addReturnMessage ="" ;
	$class="error" ;
	if (!($addReturn=="")) {
		if ($addReturn=="fail0") {
			$addReturnMessage ="Add failed because you do not have access to this action." ;	
		}
		else if ($addReturn=="fail2") {
			$addReturnMessage ="Add failed due to a database error." ;	
		}
		else if ($addReturn=="fail3") {
			$addReturnMessage ="Add failed because your inputs were invalid." ;	
		}
		else if ($addReturn=="fail4") {
			$addReturnMessage ="Add failed because some values need to be unique but were not." ;	
		}
		else if ($addReturn=="fail5") {
			$addReturnMessage ="Add failed because the passwords did not match." ;	
		}
		else if ($addReturn=="success0" OR $addReturn=="success1" OR $addReturn=="success2" ) {
			if ($addReturn=="success0") {
				$addReturnMessage ="Your application was successfully submitted. Our admissions team will review your application and be in touch in due course." ;
			}
			else if ($addReturn=="success1") {
				$addReturnMessage ="Your application was successfully submitted and <font style='font-weight: bold; text-decoration: underline'>payment has been made to your credit card</font>. Our admissions team will review your application and be in touch in due course." ;
			}
			else if ($addReturn=="success2") {
				$addReturnMessage ="Your application was successfully submitted, but <font style='font-weight: bold; text-decoration: underline'>payment could not be made to your credit card</font>. Our admissions team will review your application and be in touch in due course." ;
			}
			else if ($addReturn=="success3") {
				$addReturnMessage ="Your application was successfully submitted, <font style='font-weight: bold; text-decoration: underline; color: #ff0000'>payment has been made to your credit card, but there has been an error recording your payment. Please print this screen and contact the school ASAP.</font> Our admissions team will review your application and be in touch in due course." ;
			}
			if ($_GET["id"]!="") {
				$addReturnMessage=$addReturnMessage . "<br/><br/>If you need to contact the school in reference to this application, please quote the following number: <b><u>" . $_GET["id"] . "</b></u>." ;
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
			print "Please note that there is an application fee of <b><u>" . $currency . $applicationFee . "</u></b>." ;
			if ($enablePayments=="Y" AND $paypalAPIUsername!="" AND $paypalAPIPassword!="" AND $paypalAPISignature!="") {
				print " Payment must be made by credit card, using our secure PayPal payment gateway. When you press Submit at the end of this form, you will be directed to PayPal in order to make payment. During this process we do not see or store your credit card details." ;
			}
		print "</div>" ;
	}
	
	?>
	
	<form method="post" action="<? print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/applicationFormProcess.php" ?>" enctype="multipart/form-data">
		<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
			<tr class='break'>
				<td colspan=2> 
					<h3>Student</h3>
				</td>
			</tr>
			
			<tr>
				<td colspan=2> 
					<h4>Student Personal Data</h4>
				</td>
			</tr>
			<tr>
				<td> 
					<b>Surname *</b><br/>
					<span style="font-size: 90%"><i>Family name as shown in ID documents.</i></span>
				</td>
				<td class="right">
					<input name="surname" id="surname" maxlength=30 value="<? print $row["surname"] ?>" type="text" style="width: 300px">
					<script type="text/javascript">
						var surname = new LiveValidation('surname');
						surname.add(Validate.Presence);
					 </script>
				</td>
			</tr>
			<tr>
				<td> 
					<b>First Name *</b><br/>
					<span style="font-size: 90%"><i>First name as shown in ID documents.</i></span>
				</td>
				<td class="right">
					<input name="firstName" id="firstName" maxlength=30 value="<? print $row["firstName"] ?>" type="text" style="width: 300px">
					<script type="text/javascript">
						var firstName = new LiveValidation('firstName');
						firstName.add(Validate.Presence);
					 </script>
				</td>
			</tr>
			<tr>
				<td> 
					<b>Other Names</b><br/>
					<span style="font-size: 90%"><i>Any other names shown in ID documents.</i></span>
				</td>
				<td class="right">
					<input maxlength=30 value="<? print $row["otherNames"] ?>" type="text" style="width: 300px" name="otherNames">
				</td>
			</tr>
			<tr>
				<td> 
					<b>Preferred Name *</b><br/>
					<span style="font-size: 90%"><i>Most common name, alias, nickname, etc.</i></span>
				</td>
				<td class="right">
					<input name="preferredName" id="preferredName" maxlength=30 value="<? print $row["preferredName"] ?>" type="text" style="width: 300px">
					<script type="text/javascript">
						var preferredName = new LiveValidation('preferredName');
						preferredName.add(Validate.Presence);
					 </script>
				</td>
			</tr>
			<tr>
				<td> 
					<b>Official Name *</b><br/>
					<span style="font-size: 90%"><i>Full name as shown in ID documents.</i></span>
				</td>
				<td class="right">
					<input title='Please enter full name as shown in ID documents' name="officialName" id="officialName" maxlength=150 value="<? print $row["officialName"] ?>" type="text" style="width: 300px">
					<script type="text/javascript">
						var officialName = new LiveValidation('officialName');
						officialName.add(Validate.Presence);
					 </script>
				</td>
			</tr>
			<tr>
				<td> 
					<b>Name In Characters</b><br/>
					<span style="font-size: 90%"><i>Chinese or other character-based name.</i></span>
				</td>
				<td class="right">
					<input name="nameInCharacters" id="nameInCharacters" maxlength=20 value="<? print $row["nameInCharacters"] ?>" type="text" style="width: 300px">
				</td>
			</tr>
			<tr>
				<td> 
					<b>Gender *</b><br/>
				</td>
				<td class="right">
					<select name="gender" id="gender" style="width: 302px">
						<option value="Please select...">Please select...</option>
						<option value="F">F</option>
						<option value="M">M</option>
					</select>
					<script type="text/javascript">
						var gender = new LiveValidation('gender');
						gender.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "Select something!"});
					 </script>
				</td>
			</tr>
			<tr>
				<td> 
					<b>Date of Birth *</b><br/>
					<span style="font-size: 90%"><i>dd/mm/yyyy</i></span>
				</td>
				<td class="right">
					<input name="dob" id="dob" maxlength=10 value="<? print $row["dob"] ?>" type="text" style="width: 300px">
					<script type="text/javascript">
						var dob = new LiveValidation('dob');
						dob.add( Validate.Format, {pattern: /^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i, failureMessage: "Use dd/mm/yyyy." } ); 
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
					<h4>Student Background</h4>
				</td>
			</tr>
			<tr>
				<td> 
					<b>Home Language *</b><br/>
					<span style="font-size: 90%"><i>The primary language used in the student's home.</i></span>
				</td>
				<td class="right">
					<input name="languageHome" id="languageHome" maxlength=30 value="<? print $row["languageHome"] ?>" type="text" style="width: 300px">
				</td>
				<script type="text/javascript">
					$(function() {
						var availableTags = [
							<?
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
					var languageHome = new LiveValidation('languageHome');
					languageHome.add(Validate.Presence);
				</script>
			</tr>
			<tr>
				<td> 
					<b>First Language *</b><br/>
					<span style="font-size: 90%"><i>Student's native/first/mother language. </i></span>
				</td>
				<td class="right">
					<input name="languageFirst" id="languageFirst" maxlength=30 value="<? print $row["languageFirst"] ?>" type="text" style="width: 300px">
				</td>
				<script type="text/javascript">
					$(function() {
						var availableTags = [
							<?
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
					var languageFirst = new LiveValidation('languageFirst');
					languageFirst.add(Validate.Presence);
				</script>
			</tr>
			<tr>
				<td> 
					<b>Second Language</b><br/>
				</td>
				<td class="right">
					<input name="languageSecond" id="languageSecond" maxlength=30 value="<? print $row["languageSecond"] ?>" type="text" style="width: 300px">
				</td>
				<script type="text/javascript">
					$(function() {
						var availableTags = [
							<?
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
					<b>Third Language</b><br/>
				</td>
				<td class="right">
					<input name="languageThird" id="languageThird" maxlength=30 value="<? print $row["languageThird"] ?>" type="text" style="width: 300px">
				</td>
				<script type="text/javascript">
					$(function() {
						var availableTags = [
							<?
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
					<b>Country of Birth</b><br/>
				</td>
				<td class="right">
					<select name="countryOfBirth" id="countryOfBirth" style="width: 302px">
						<?
						try {
							$dataSelect=array(); 
							$sqlSelect="SELECT printable_name FROM gibbonCountry ORDER BY printable_name" ;
							$resultSelect=$connection2->prepare($sqlSelect);
							$resultSelect->execute($dataSelect);
						}
						catch(PDOException $e) { }
						print "<option value=''></option>" ;
						while ($rowSelect=$resultSelect->fetch()) {
							print "<option value='" . $rowSelect["printable_name"] . "'>" . htmlPrep($rowSelect["printable_name"]) . "</option>" ;
						}
						?>				
					</select>
				</td>
			</tr>
			<tr>
				<td> 
					<b>Citizenship</b><br/>
				</td>
				<td class="right">
					<select name="citizenship1" id="citizenship1" style="width: 302px">
						<?
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
								print "<option value='" . $rowSelect["printable_name"] . "'>" . htmlPrep($rowSelect["printable_name"]) . "</option>" ;
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
					<b>Citizenship Passport Number</b><br/>
				</td>
				<td class="right">
					<input name="citizenship1Passport" id="citizenship1Passport" maxlength=30 value="<? print $row["citizenship1Passport"] ?>" type="text" style="width: 300px">
				</td>
			</tr>
			<tr>
				<td> 
					<?
					if ($_SESSION[$guid]["country"]=="") {
						print "<b>National ID Card Number</b><br/>" ;
					}
					else {
						print "<b>" . $_SESSION[$guid]["country"] . " ID Card Number</b><br/>" ;
					}
					?>
				</td>
				<td class="right">
					<input name="nationalIDCardNumber" id="nationalIDCardNumber" maxlength=30 value="<? print $row["nationalIDCardNumber"] ?>" type="text" style="width: 300px">
				</td>
			</tr>
			<tr>
				<td> 
					<?
					if ($_SESSION[$guid]["country"]=="") {
						print "<b>Residency/Visa Type</b><br/>" ;
					}
					else {
						print "<b>" . $_SESSION[$guid]["country"] . " Residency/Visa Type</b><br/>" ;
					}
					?>
				</td>
				<td class="right">
					<?
					$residencyStatusList=getSettingByScope($connection2, "User Admin", "residencyStatus") ;
					if ($residencyStatusList=="") {
						print "<input name='residencyStatus' id='residencyStatus' maxlength=30 value='" . $row["residencyStatus"] . "' type='text' style='width: 300px'>" ;
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
					<?
					if ($_SESSION[$guid]["country"]=="") {
						print "<b>Visa Expiry Date</b><br/>" ;
					}
					else {
						print "<b>" . $_SESSION[$guid]["country"] . " Visa Expiry Date</b><br/>" ;
					}
					print "<span style='font-size: 90%'><i>dd/mm/yyyy. If relevant.</i></span>" ;
					?>
				</td>
				<td class="right">
					<input name="visaExpiryDate" id="visaExpiryDate" maxlength=10 value="<? print $row["visaExpiryDate"] ?>" type="text" style="width: 300px">
					<script type="text/javascript">
						var visaExpiryDate = new LiveValidation('visaExpiryDate');
						visaExpiryDate.add( Validate.Format, {pattern: /^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i, failureMessage: "Use dd/mm/yyyy." } ); 
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
					<h4>Student Contact</h4>
				</td>
			</tr>
			<tr>
				<td> 
					<b>Email</b><br/>
				</td>
				<td class="right">
					<input name="email" id="email" maxlength=50 value="<? print $row["email"] ?>" type="text" style="width: 300px">
					<script type="text/javascript">
						var email = new LiveValidation('email');
						email.add(Validate.Email);
					 </script>
				</td>
			</tr>
			<?
			for ($i=1; $i<3; $i++) {
				?>
				<tr>
					<td> 
						<b>Phone <? print $i ?></b><br/>
						<span style="font-size: 90%"><i>Type, country code, number</i></span>
					</td>
					<td class="right">
						<input name="phone<? print $i ?>" id="phone<? print $i ?>" maxlength=20 value="<? print $row["phone" . $i] ?>" type="text" style="width: 160px">
						<select name="phone<? print $i ?>CountryCode" id="phone<? print $i ?>CountryCode" style="width: 60px">
							<?
							print "<option value=''></option>" ;
							try {
								$dataSelect=array(); 
								$sqlSelect="SELECT * FROM gibbonCountry ORDER BY printable_name" ;
								$resultSelect=$connection2->prepare($sqlSelect);
								$resultSelect->execute($dataSelect);
							}
							catch(PDOException $e) { }
							while ($rowSelect=$resultSelect->fetch()) {
								print "<option value='" . $rowSelect["iddCountryCode"] . "'>" . htmlPrep($rowSelect["iddCountryCode"]) . " - " .  htmlPrep($rowSelect["printable_name"]) . "</option>" ;
							}
							?>				
						</select>
						<select style="width: 70px" name="phone<? print $i ?>Type">
							<option value=""></option>
							<option value="Mobile">Mobile</option>
							<option value="Home">Home</option>
							<option value="Work">Work</option>
							<option value="Fax">Fax</option>
							<option value="Pager">Pager</option>
							<option value="Other">Other</option>
						</select>
					</td>
				</tr>
				<?
			}
			?>
			
			
			<tr>
				<td colspan=2> 
					<h4>Student Medical & Development</h4>
				</td>
			</tr>
			<tr>
				<td colspan=2 style='padding-top: 15px'> 
					<b>Medical Information</b><br/>
					<span style="font-size: 90%"><i>Please indicate any medical conditions.</i></span><br/>
					<textarea name="medicalInformation" id="medicalInformation" rows=5 style="width:738px; margin: 5px 0px 0px 0px"></textarea>
				</td>
			</tr>
			<tr>
				<td colspan=2 style='padding-top: 15px'> 
					<b>Development Information</b><br/>
					<span style="font-size: 90%"><i>Provide any comments or information concerning your child’s development that may be relevant to your child’s performance in the classroom or elsewhere? (Incorrect or withheld information may affect continued enrolment).</i></span><br/> 					
					<textarea name="developmentInformation" id="developmentInformation" rows=5 style="width:738px; margin: 5px 0px 0px 0px"></textarea>
				</td>
			</tr>
			
			
			
			<tr>
				<td colspan=2> 
					<h4>Student Education</h4>
				</td>
			</tr>
			<tr>
				<td> 
					<b>Anticipated Year of Entry *</b><br/>
					<span style="font-size: 90%"><i>What school year will the student join in?</i></span>
				</td>
				<td class="right">
					<select name="gibbonSchoolYearIDEntry" id="gibbonSchoolYearIDEntry" style="width: 302px">
						<?
						print "<option value='Please select...'>Please select...</option>" ;
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
						var gibbonSchoolYearIDEntry = new LiveValidation('gibbonSchoolYearIDEntry');
						gibbonSchoolYearIDEntry.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "Select something!"});
					 </script>
				</td>
			</tr>
			<tr>
				<td> 
					<b>Intended Start Date *</b><br/>
					<span style="font-size: 90%"><i>Student's intended first day at school.<br/>dd/mm/yyyy</i></span>
				</td>
				<td class="right">
					<input name="dateStart" id="dateStart" maxlength=10 value="<? print dateConvertBack($row["dateStart"]) ?>" type="text" style="width: 300px">
					<script type="text/javascript">
						var dateStart = new LiveValidation('dateStart');
						dateStart.add( Validate.Format, {pattern: /^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i, failureMessage: "Use dd/mm/yyyy." } ); 
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
					<b>Year Group at Entry *</b><br/>
					<span style="font-size: 90%"><i>Which year level will student enter.</i></span>
				</td>
				<td class="right">
					<select name="gibbonYearGroupIDEntry" id="gibbonYearGroupIDEntry" style="width: 302px">
						<?
						print "<option value='Please select...'>Please select...</option>" ;
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
						var gibbonYearGroupIDEntry = new LiveValidation('gibbonYearGroupIDEntry');
						gibbonYearGroupIDEntry.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "Select something!"});
					 </script>
				</td>
			</tr>
			
			<?
			$dayTypeOptions=getSettingByScope($connection2, 'User Admin', 'dayTypeOptions') ;
			if ($dayTypeOptions!="") {
				?>
				<tr>
					<td> 
						<b>Day Type</b><br/>
						<span style="font-size: 90%"><i><? print getSettingByScope($connection2, 'User Admin', 'dayTypeText') ; ?></i></span>
					</td>
					<td class="right">
						<select name="dayType" id="dayType" style="width: 302px">
							<?
							$dayTypes=explode(",", $dayTypeOptions) ;
							foreach ($dayTypes as $dayType) {
								print "<option value='" . trim($dayType) . "'>" . trim($dayType) . "</option>" ;
							}
							?>				
						</select>
					</td>
				</tr>
				<?
			}		
			?>
			
			
			
			<tr>
				<td colspan=2 style='padding-top: 15px'> 
					<b>Previous Schools *</b><br/>
					<span style="font-size: 90%"><i>Please give information on the last two schools attended by the applicant.</i></span>
				</td>
			</tr>
			<tr>
				<td colspan=2> 
					<?
					print "<table cellspacing='0' style='width: 100%'>" ;
						print "<tr class='head'>" ;
							print "<th>" ;
								print "School Name" ;
							print "</th>" ;
							print "<th>" ;
								print "Address" ;
							print "</th>" ;
							print "<th>" ;
								print "Grades<br/>Attended" ;
							print "</th>" ;
							print "<th>" ;
								print "Language of<br/>Instruction" ;
							print "</th>" ;
							print "<th>" ;
								print "Joining Date<br/><span style='font-size: 80%'>dd/mm/yyyy</span>" ;
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
											var availableTags = [
												<?
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
											$( "#schoolLanguage<? print $i ?>" ).autocomplete({source: availableTags});
										});
									</script>
									<?
								print "</td>" ;
								print "<td>" ;
									?>
									<input name="<? print "schoolDate$i" ?>" id="<? print "schoolDate$i" ?>" maxlength=10 value="" type="text" style="width:90px; float: left">
									<script type="text/javascript">
										$(function() {
											$( "#<? print "schoolDate$i" ?>" ).datepicker();
										});
									</script>
									<?
								print "</td>" ;
							print "</tr>" ;
						}
					print "</table>" ;
					?>
				</td>
			</tr>
			
			
			
			<?
			//FAMILY
			try {
				$dataSelect=array("gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
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
							Home Address
						</h3>
						<p>
							This address will be used for all members of the family. If an individual within the family needs a different address, this can be set through Data Updater after admission. 
						</p>
					</td>
				</tr>
				<tr>
					<td> 
						<b>Home Address *</b><br/>
						<span style="font-size: 90%"><i>Unit, Building, Street</i></span>
					</td>
					<td class="right">
						<input name="homeAddress" id="homeAddress" maxlength=255 value="<? print $row["homeAddress"] ?>" type="text" style="width: 300px">
						<script type="text/javascript">
							var homeAddress = new LiveValidation('homeAddress');
							homeAddress.add(Validate.Presence);
						 </script>
					</td>
				</tr>
				<tr>
					<td> 
						<b>Home Address (District) *</b><br/>
						<span style="font-size: 90%"><i>County, State, District</i></span>
					</td>
					<td class="right">
						<input name="homeAddressDistrict" id="homeAddressDistrict" maxlength=30 value="<? print $row["homeAddressDistrict"] ?>" type="text" style="width: 300px">
					</td>
					<script type="text/javascript">
						$(function() {
							var availableTags = [
								<?
								try {
									$dataAuto=array(); 
									$sqlAuto="SELECT DISTINCT homeAddressDistrict FROM gibbonApplicationForm ORDER BY homeAddressDistrict" ;
									$resultAuto=$connection2->prepare($sqlAuto);
									$resultAuto->execute($dataAuto);
								}
								catch(PDOException $e) { }
								while ($rowAuto=$resultAuto->fetch()) {
									print "\"" . $rowAuto["homeAddressDistrict"] . "\", " ;
								}
								?>
							];
							$( "#homeAddressDistrict" ).autocomplete({source: availableTags});
						});
					</script>
					<script type="text/javascript">
						var homeAddressDistrict = new LiveValidation('homeAddressDistrict');
						homeAddressDistrict.add(Validate.Presence);
					 </script>
				</tr>
				<tr>
					<td> 
						<b>Address (Country) *</b><br/>
					</td>
					<td class="right">
						<select name="homeAddressCountry" id="homeAddressCountry" style="width: 302px">
							<?
							try {
								$dataSelect=array(); 
								$sqlSelect="SELECT printable_name FROM gibbonCountry ORDER BY printable_name" ;
								$resultSelect=$connection2->prepare($sqlSelect);
								$resultSelect->execute($dataSelect);
							}
							catch(PDOException $e) { }
							print "<option value='Please select...'>Please select...</option>" ;
							while ($rowSelect=$resultSelect->fetch()) {
								print "<option value='" . $rowSelect["printable_name"] . "'>" . htmlPrep($rowSelect["printable_name"]) . "</option>" ;
							}
							?>				
						</select>
						<script type="text/javascript">
							var homeAddressCountry = new LiveValidation('homeAddressCountry');
							homeAddressCountry.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "Select something!"});
						 </script>
					</td>
				</tr>
				<?
				
				if ($_SESSION[$guid]["username"]!="") {
					$start=2 ;
					?>
					<tr class='break'>
						<td colspan=2> 
							<h3>
								Parent/Guardian 1
								<?
								if ($i==1) {
									print "<span style='font-size: 75%'></span>" ;
								}
								?>
							</h3>
						</td>
					</tr>
					<tr>
						<td> 
							<b>Username</b><br/>
							<span style="font-size: 90%"><i>System login ID.</i></span>
						</td>
						<td class="right">
							<input readonly name='parent1username' maxlength=30 value="<? print $_SESSION[$guid]["username"] ?>" type="text" style="width: 300px">
						</td>
					</tr>
					
					<tr>
						<td> 
							<b>Surname</b><br/>
							<span style="font-size: 90%"><i>Family name as shown in ID documents.</i></span>
						</td>
						<td class="right">
							<input readonly name='parent1surname' maxlength=30 value="<? print $_SESSION[$guid]["surname"] ?>" type="text" style="width: 300px">
						</td>
					</tr>
					<tr>
						<td> 
							<b>Preferred Name</b><br/>
							<span style="font-size: 90%"><i>Most common name, alias, nickname, etc.</i></span>
						</td>
						<td class="right">
							<input readonly name='parent1preferredName' maxlength=30 value="<? print $_SESSION[$guid]["preferredName"] ?>" type="text" style="width: 300px">
						</td>
					</tr>
					<tr>
						<td> 
							<b>Relationship *</b><br/>
						</td>
						<td class="right">
							<select name="parent1relationship" id="parent1relationship" style="width: 302px">
								<option value="Please select...">Please select...</option>
								<option value="Mother">Mother</option>
								<option value="Father">Father</option>
								<option value="Step-Mother">Step-Mother</option>
								<option value="Step-Father">Step-Father</option>
								<option value="Adoptive Parent">Adoptive Parent</option>
								<option value="Guardian">Guardian</option>
								<option value="Grandmother">Grandmother</option>
								<option value="Grandfather">Grandfather</option>
								<option value="Aunt">Aunt</option>
								<option value="Uncle">Uncle</option>
								<option value="Nanny/Helper">Nanny/Helper</option>
								<option value="Other">Other</option>
							</select>
							<script type="text/javascript">
								var parent1relationship = new LiveValidation('parent1relationship');
								parent1relationship.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "Select something!"});
							 </script>
						</td>
					</tr>
					<input name='parent1gibbonPersonID' value="<? print $_SESSION[$guid]["gibbonPersonID"] ?>" type="hidden">
					<?
				}
				else {
					$start=1 ;
				}
				for ($i=$start;$i<3;$i++) {
					?>
					<tr class='break'>
						<td colspan=2> 
							<h3>
								Parent/Guardian <? print $i ?>
								<?
								if ($i==1) {
									print "<span style='font-size: 75%'> (e.g. mother)</span>" ;
								}
								else if ($i==2 AND $_SESSION[$guid]["gibbonPersonID"]=="") {
									print "<span style='font-size: 75%'> (e.g. father)</span>" ;
								}
								?>
							</h3>
						</td>
					</tr>
					<?
					if ($i==2) {
						?>
						<tr>
							<td class='right' colspan=2> 
								<script type="text/javascript">
									/* Advanced Options Control */
									$(document).ready(function(){
										$("#secondParent").click(function(){
											if ($('input[name=secondParent]:checked').val() == "No" ) {
												$(".secondParent").slideUp("fast"); 	
												$("#parent2title").attr("disabled", "disabled");
												$("#parent2surname").attr("disabled", "disabled");
												$("#parent2firstName").attr("disabled", "disabled");
												$("#parent2otherNames").attr("disabled", "disabled");
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
												$("#parent2otherNames").removeAttr("disabled");
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
						<?
					}
					?>
					<tr <? if ($i==2) { print "class='secondParent'" ; }?>>
						<td colspan=2> 
							<h4>Parent/Guardian <? print $i ?> Personal Data</h4>
						</td>
					</tr>
					<tr <? if ($i==2) { print "class='secondParent'" ; }?>>
						<td> 
							<b>Title *</b><br/>
							<span style="font-size: 90%"><i></i></span>
						</td>
						<td class="right">
							<select style="width: 302px" id="<? print "parent$i" ?>title" name="<? print "parent$i" ?>title">
								<option value="Please select...">Please select...</option>
								<option value="Ms. ">Ms.</option>
								<option value="Miss ">Miss</option>
								<option value="Mr. ">Mr.</option>
								<option value="Mrs. ">Mrs.</option>
								<option value="Dr. ">Dr.</option>
							</select>
							<script type="text/javascript">
								var <? print "parent$i" ?>title = new LiveValidation('<? print "parent$i" ?>title');
								<? print "parent$i" ?>title.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "Select something!"});
							 </script>
						</td>
					</tr>
					<tr <? if ($i==2) { print "class='secondParent'" ; }?>>
						<td> 
							<b>Surname *</b><br/>
							<span style="font-size: 90%"><i>Family name as shown in ID documents.</i></span>
						</td>
						<td class="right">
							<input name="<? print "parent$i" ?>surname" id="<? print "parent$i" ?>surname" maxlength=30 value="<? if ($i==1) { print $_SESSION[$guid]["surname"] ;}?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var <? print "parent$i" ?>surname = new LiveValidation('<? print "parent$i" ?>surname');
								<? print "parent$i" ?>surname.add(Validate.Presence);
							 </script>
						</td>
					</tr>
					<tr <? if ($i==2) { print "class='secondParent'" ; }?>>
						<td> 
							<b>First Name *</b><br/>
							<span style="font-size: 90%"><i>First name as shown in ID documents.</i></span>
						</td>
						<td class="right">
							<input name="<? print "parent$i" ?>firstName" id="<? print "parent$i" ?>firstName" maxlength=30 value="<? if ($i==1) { print $_SESSION[$guid]["firstName"] ;}?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var <? print "parent$i" ?>firstName = new LiveValidation('<? print "parent$i" ?>firstName');
								<? print "parent$i" ?>firstName.add(Validate.Presence);
							 </script>
						</td>
					</tr>
					<tr <? if ($i==2) { print "class='secondParent'" ; }?>>
						<td> 
							<b>Other Names</b><br/>
							<span style="font-size: 90%"><i>Any other names shown in ID documents.</i></span>
						</td>
						<td class="right">
							<input maxlength=30 value="<? if ($i==1) { print $_SESSION[$guid]["otherNames"] ;}?>" type="text" style="width: 300px" name="<? print "parent$i" ?>otherNames">
						</td>
					</tr>
					<tr <? if ($i==2) { print "class='secondParent'" ; }?>>
						<td> 
							<b>Preferred Name *</b><br/>
							<span style="font-size: 90%"><i>Most common name, alias, nickname, etc.</i></span>
						</td>
						<td class="right">
							<input name="<? print "parent$i" ?>preferredName" id="<? print "parent$i" ?>preferredName" maxlength=30 value="<? if ($i==1) { print $_SESSION[$guid]["preferredName"] ;}?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var <? print "parent$i" ?>preferredName = new LiveValidation('<? print "parent$i" ?>preferredName');
								<? print "parent$i" ?>preferredName.add(Validate.Presence);
							 </script>
						</td>
					</tr>
					<tr <? if ($i==2) { print "class='secondParent'" ; }?>>
						<td> 
							<b>Official Name *</b><br/>
							<span style="font-size: 90%"><i>Full name as shown in ID documents.</i></span>
						</td>
						<td class="right">
							<input title='Please enter full name as shown in ID documents' name="<? print "parent$i" ?>officialName" id="<? print "parent$i" ?>officialName" maxlength=150 value="<? if ($i==1) { print $_SESSION[$guid]["officialName"] ;}?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var <? print "parent$i" ?>officialName = new LiveValidation('<? print "parent$i" ?>officialName');
								<? print "parent$i" ?>officialName.add(Validate.Presence);
							 </script>
						</td>
					</tr>
					<tr <? if ($i==2) { print "class='secondParent'" ; }?>>
						<td> 
							<b>Name In Characters</b><br/>
							<span style="font-size: 90%"><i>Chinese or other character-based name.</i></span>
						</td>
						<td class="right">
							<input name="<? print "parent$i" ?>nameInCharacters" id="<? print "parent$i" ?>nameInCharacters" maxlength=20 value="<? print $row["nameInCharacters"] ?>" type="text" style="width: 300px">
						</td>
					</tr>
					<tr <? if ($i==2) { print "class='secondParent'" ; }?>>
						<td> 
							<b>Gender *</b><br/>
						</td>
						<td class="right">
							<select name="<? print "parent$i" ?>gender" id="<? print "parent$i" ?>gender" style="width: 302px">
								<option value="Please select...">Please select...</option>
								<option value="F">F</option>
								<option value="M">M</option>
							</select>
							<script type="text/javascript">
								var <? print "parent$i" ?>gender = new LiveValidation('<? print "parent$i" ?>gender');
								<? print "parent$i" ?>gender.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "Select something!"});
							 </script>
						</td>
					</tr>
					<tr <? if ($i==2) { print "class='secondParent'" ; }?>>
						<td> 
							<b>Relationship *</b><br/>
						</td>
						<td class="right">
							<select name="<? print "parent$i" ?>relationship" id="<? print "parent$i" ?>relationship" style="width: 302px">
								<option value="Please select...">Please select...</option>
								<option value="Mother">Mother</option>
								<option value="Father">Father</option>
								<option value="Step-Mother">Step-Mother</option>
								<option value="Step-Father">Step-Father</option>
								<option value="Adoptive Parent">Adoptive Parent</option>
								<option value="Guardian">Guardian</option>
								<option value="Grandmother">Grandmother</option>
								<option value="Grandfather">Grandfather</option>
								<option value="Aunt">Aunt</option>
								<option value="Uncle">Uncle</option>
								<option value="Nanny/Helper">Nanny/Helper</option>
								<option value="Other">Other</option>
							</select>
							<script type="text/javascript">
								var <? print "parent$i" ?>relationship = new LiveValidation('<? print "parent$i" ?>relationship');
								<? print "parent$i" ?>relationship.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "Select something!"});
							 </script>
						</td>
					</tr>
					
					<tr <? if ($i==2) { print "class='secondParent'" ; }?>>
						<td colspan=2> 
							<h4>Parent/Guardian <? print $i ?> Background</h4>
						</td>
					</tr>
					<tr <? if ($i==2) { print "class='secondParent'" ; }?>>
						<td> 
							<b>First Language </b><br/>
						</td>
						<td class="right">
							<input name="<? print "parent$i" ?>languageFirst" id="<? print "parent$i" ?>languageFirst" maxlength=30 value="<? print $row["languageFirst"] ?>" type="text" style="width: 300px">
						</td>
						<script type="text/javascript">
							$(function() {
								var availableTags = [
									<?
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
								$( "#<? print 'parent' . $i ?>languageFirst" ).autocomplete({source: availableTags});
							});
						</script>
					</tr>
					<tr <? if ($i==2) { print "class='secondParent'" ; }?>>
						<td> 
							<b>Second Language</b><br/>
						</td>
						<td class="right">
							<input name="<? print "parent$i" ?>languageSecond" id="<? print "parent$i" ?>languageSecond" maxlength=30 value="<? print $row["languageSecond"] ?>" type="text" style="width: 300px">
						</td>
						<script type="text/javascript">
							$(function() {
								var availableTags = [
									<?
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
								$( "#<? print 'parent' . $i ?>languageSecond" ).autocomplete({source: availableTags});
							});
						</script>
					</tr>
					<tr <? if ($i==2) { print "class='secondParent'" ; }?>>
						<td> 
							<b>Citizenship</b><br/>
						</td>
						<td class="right">
							<select name="<? print "parent$i" ?>citizenship1" id="<? print "parent$i" ?>citizenship1" style="width: 302px">
								<?
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
										print "<option value='" . $rowSelect["printable_name"] . "'>" . htmlPrep($rowSelect["printable_name"]) . "</option>" ;
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
					<tr <? if ($i==2) { print "class='secondParent'" ; }?>>
						<td> 
							<?
							if ($_SESSION[$guid]["country"]=="") {
								print "<b>National ID Card Number</b><br/>" ;
							}
							else {
								print "<b>" . $_SESSION[$guid]["country"] . " ID Card Number</b><br/>" ;
							}
							?>
						</td>
						<td class="right">
							<input name="<? print "parent$i" ?>nationalIDCardNumber" id="<? print "parent$i" ?>nationalIDCardNumber" maxlength=30 value="<? print $row["nationalIDCardNumber"] ?>" type="text" style="width: 300px">
						</td>
					</tr>
					<tr <? if ($i==2) { print "class='secondParent'" ; }?>>
						<td> 
							<?
							if ($_SESSION[$guid]["country"]=="") {
								print "<b>Residency/Visa Type</b><br/>" ;
							}
							else {
								print "<b>" . $_SESSION[$guid]["country"] . " Residency/Visa Type</b><br/>" ;
							}
							?>
						</td>
						<td class="right">
							<?
							$residencyStatusList=getSettingByScope($connection2, "User Admin", "residencyStatus") ;
							if ($residencyStatusList=="") {
								print "<input name='parent" . $i . "residencyStatus' id='parent" . $i . "residencyStatus' maxlength=30 value='" . $row["residencyStatus"] . "' type='text' style='width: 300px'>" ;
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
					<tr <? if ($i==2) { print "class='secondParent'" ; }?>>
						<td> 
							<?
							if ($_SESSION[$guid]["country"]=="") {
								print "<b>Visa Expiry Date</b><br/>" ;
							}
							else {
								print "<b>" . $_SESSION[$guid]["country"] . " Visa Expiry Date</b><br/>" ;
							}
							print "<span style='font-size: 90%'><i>dd/mm/yyyy. If relevant.</i></span>" ;
							?>
						</td>
						<td class="right">
							<input name="<? print "parent$i" ?>visaExpiryDate" id="<? print "parent$i" ?>visaExpiryDate" maxlength=10 value="<? print $row["visaExpiryDate"] ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var <? print "parent$i" ?>visaExpiryDate = new LiveValidation('<? print "parent$i" ?>visaExpiryDate');
								<? print "parent$i" ?>visaExpiryDate.add( Validate.Format, {pattern: /^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i, failureMessage: "Use dd/mm/yyyy." } ); 
							 </script>
							 <script type="text/javascript">
								$(function() {
									$( "#<? print "parent$i" ?>visaExpiryDate" ).datepicker();
								});
							</script>
						</td>
					</tr>
					
					
					<tr <? if ($i==2) { print "class='secondParent'" ; }?>>
						<td colspan=2> 
							<h4>Parent/Guardian <? print $i ?> Contact</h4>
						</td>
					</tr>
					<tr <? if ($i==2) { print "class='secondParent'" ; }?>>
						<td> 
							<b>Email *</b><br/>
						</td>
						<td class="right">
							<input name="<? print "parent$i" ?>email" id="<? print "parent$i" ?>email" maxlength=50 value="<? if ($i==1) { print $_SESSION[$guid]["email"] ;}?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var <? print "parent$i" ?>email = new LiveValidation('<? print "parent$i" ?>email');
								<? 
								print "parent$i" . "email.add(Validate.Email);";
								print "parent$i" . "email.add(Validate.Presence);" ;
								?>
							 </script>
						</td>
					</tr>
					
					<?
					for ($y=1; $y<3; $y++) {
						?>
						<tr <? if ($i==2) { print "class='secondParent'" ; }?>>
							<td> 
								<b>Phone <? print $y ; if ($y==1) { print " *" ;}?></b><br/>
								<span style="font-size: 90%"><i>Type, country code, number</i></span>
							</td>
							<td class="right">
								<input name="<? print "parent$i" ?>phone<? print $y ?>" id="<? print "parent$i" ?>phone<? print $y ?>" maxlength=20 value="<? print $row["phone" . $y] ?>" type="text" style="width: 160px">
								<?
								if ($y==1) {
									?>
									<script type="text/javascript">
										var <? print "parent$i" ?>phone<? print $y ?> = new LiveValidation('<? print "parent$i" ?>phone<? print $y ?>');
										<? print "parent$i" ?>phone<? print $y ?>.add(Validate.Presence);
									 </script>
									<?
								}
								?>
								<select name="<? print "parent$i" ?>phone<? print $y ?>CountryCode" id="<? print "parent$i" ?>phone<? print $y ?>CountryCode" style="width: 60px">
									<?
									print "<option value=''></option>" ;
									try {
										$dataSelect=array(); 
										$sqlSelect="SELECT * FROM gibbonCountry ORDER BY printable_name" ;
										$resultSelect=$connection2->prepare($sqlSelect);
										$resultSelect->execute($dataSelect);
									}
									catch(PDOException $e) { }
									while ($rowSelect=$resultSelect->fetch()) {
										print "<option value='" . $rowSelect["iddCountryCode"] . "'>" . htmlPrep($rowSelect["iddCountryCode"]) . " - " .  htmlPrep($rowSelect["printable_name"]) . "</option>" ;
									}
									?>				
								</select>
								<select style="width: 70px" name="<? print "parent$i" ?>phone<? print $y ?>Type">
									<option value=""></option>
									<option value="Mobile">Mobile</option>
									<option value="Home">Home</option>
									<option value="Work">Work</option>
									<option value="Fax">Fax</option>
									<option value="Pager">Pager</option>
									<option value="Other">Other</option>
								</select>
							</td>
						</tr>
						<?
					}
					?>
					
					<tr <? if ($i==2) { print "class='secondParent'" ; }?>>
						<td colspan=2> 
							<h4>Employment</h4>
						</td>
					</tr>
					<tr <? if ($i==2) { print "class='secondParent'" ; }?>>
						<td> 
							<b>Profession *</b><br/>
						</td>
						<td class="right">
							<input name="<? print "parent$i" ?>profession" id="<? print "parent$i" ?>profession" maxlength=30 value="<? print $row["profession"] ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var <? print "parent$i" ?>profession = new LiveValidation('<? print "parent$i" ?>profession');
								<? print "parent$i" ?>profession.add(Validate.Presence);
							 </script>
						</td>
					</tr>
					<tr <? if ($i==2) { print "class='secondParent'" ; }?>>
						<td> 
							<b>Employer</b><br/>
						</td>
						<td class="right">
							<input name="<? print "parent$i" ?>employer" id="<? print "parent$i" ?>employer" maxlength=30 value="<? print $row["employer"] ?>" type="text" style="width: 300px">
						</td>
					</tr>
					<?
				}
			}
			else {
				?>
				<input type="hidden" name="gibbonFamily" value="TRUE">
				<tr class='break'>
					<td colspan=2> 
						<h3>Family</h3>
						<p>Choose the family you wish to associate this application with.</p>
						<?
						print "<table cellspacing='0' style='width: 100%'>" ;
							print "<tr class='head'>" ;
								print "<th>" ;
									print "Family Name" ;
								print "</th>" ;
								print "<th>" ;
									print "Selected" ;
								print "</th>" ;
								print "<th>" ;
									print "Relationships" ;
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
												<select name="<? print $rowSelect["gibbonFamilyID"] ?>-relationships[]" id="relationships[]" style="width: 200px">
													<option <? if ($rowRelationships["gender"]=="F") { print "selected" ; } ?> value="Mother">Mother</option>
													<option <? if ($rowRelationships["gender"]=="M") { print "selected" ; } ?> value="Father">Father</option>
													<option value="Step-Mother">Step-Mother</option>
													<option value="Step-Father">Step-Father</option>
													<option value="Adoptive Parent">Adoptive Parent</option>
													<option value="Guardian">Guardian</option>
													<option value="Grandmother">Grandmother</option>
													<option value="Grandfather">Grandfather</option>
													<option value="Aunt">Aunt</option>
													<option value="Uncle">Uncle</option>
													<option value="Nanny/Helper">Nanny/Helper</option>
													<option value="Other">Other</option>
												</select>
												<input type="hidden" name="<? print $rowSelect["gibbonFamilyID"] ?>-relationshipsGibbonPersonID[]" value="<? print $rowRelationships["gibbonPersonID"] ?>">
												<?
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
				<?
			}
			?>
			<tr class='break'>
				<td colspan=2> 
					<h3>Siblings</h3>
				</td>
			</tr>
			<tr>
				<td colspan=2 style='padding-top: 0px'> 
					<p>Please give information on the applicants' siblings.</p>
				</td>
			</tr>
			<tr>
				<td colspan=2> 
					<?
					print "<table cellspacing='0' style='width: 100%'>" ;
						print "<tr class='head'>" ;
							print "<th>" ;
								print "Sibling Name" ;
							print "</th>" ;
							print "<th>" ;
								print "Date of Birth<br/><span style='font-size: 80%'>dd/mm/yyyy</span>" ;
							print "</th>" ;
							print "<th>" ;
								print "School Attending" ;
							print "</th>" ;
							print "<th>" ;
								print "Joining Date<br/><span style='font-size: 80%'>dd/mm/yyyy</span>" ;
							print "</th>" ;
						print "</tr>" ;
						
						$rowCount=1 ;
						
						//List siblings who have been to or are at the school
						if ($gibbonFamilyID!="") {
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
										<input name="<? print "siblingDOB$rowCount" ?>" id="<? print "siblingDOB$rowCount" ?>" maxlength=10 value="<? print dateConvertBack($rowSibling["dob"]) ?>" type="text" style="width:90px; float: left"><br/>
										<script type="text/javascript">
											$(function() {
												$( "#<? print "siblingDOB$rowCount" ?>" ).datepicker();
											});
										</script>
										<?
									print "</td>" ;
									print "<td>" ;
										print "<input name='siblingSchool$rowCount' id='siblingSchool$rowCount' maxlength=50 value='" . $_SESSION[$guid]["organisationName"] . "' type='text' style='width:200px; float: left'>" ;
									print "</td>" ;
									print "<td>" ;
										?>
										<input name="<? print "siblingSchoolJoiningDate$rowCount" ?>" id="<? print "siblingSchoolJoiningDate$rowCount" ?>" maxlength=10 value="<? print dateConvertBack($rowSibling["dateStart"]) ?>" type="text" style="width:90px; float: left">
										<script type="text/javascript">
											$(function() {
												$( "#<? print "siblingSchoolJoiningDate$rowCount" ?>" ).datepicker();
											});
										</script>
										<?
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
									<input name="<? print "siblingDOB$i" ?>" id="<? print "siblingDOB$i" ?>" maxlength=10 value="" type="text" style="width:90px; float: left"><br/>
									<script type="text/javascript">
										$(function() {
											$( "#<? print "siblingDOB$i" ?>" ).datepicker();
										});
									</script>
									<?
								print "</td>" ;
								print "<td>" ;
									print "<input name='siblingSchool$i' id='siblingSchool$i' maxlength=50 value='' type='text' style='width:200px; float: left'>" ;
								print "</td>" ;
								print "<td>" ;
									?>
									<input name="<? print "siblingSchoolJoiningDate$i" ?>" id="<? print "siblingSchoolJoiningDate$i" ?>" maxlength=10 value="" type="text" style="width:120px; float: left">
									<script type="text/javascript">
										$(function() {
											$( "#<? print "siblingSchoolJoiningDate$i" ?>" ).datepicker();
										});
									</script>
									<?
								print "</td>" ;
							print "</tr>" ;
						}
					print "</table>" ;
					?>
				</td>
			</tr>
			
			<?
			$languageOptionsActive=getSettingByScope($connection2, 'Application Form', 'languageOptionsActive') ;
			if ($languageOptionsActive=="On") {
				?>
				<tr class='break'>
					<td colspan=2> 
						<h3>Language Selection</h3>
						<?
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
						<b>Language Choice *</b><br/>
						<span style="font-size: 90%"><i>Please choose preferred additional language to study.</i></span>
					</td>
					<td class="right">
						<select name="languageChoice" id="languageChoice" style="width: 302px">
							<?
							print "<option value='Please select...'>Please select...</option>" ;
							$languageOptionsLanguageList=getSettingByScope($connection2, "Application Form", "languageOptionsLanguageList") ;
							$languages=explode(",", $languageOptionsLanguageList) ;
							foreach ($languages as $language) {
								print "<option value='" . trim($language) . "'>" . trim($language) . "</option>" ;
							}
							?>				
						</select>
						<script type="text/javascript">
							var languageChoice = new LiveValidation('languageChoice');
							languageChoice.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "Select something!"});
						 </script>
					</td>
				</tr>
				<tr>
					<td colspan=2 style='padding-top: 15px'> 
						<b>Language Choice Experience *</b><br/>
						<span style="font-size: 90%"><i>Has the applicant studied the selected language before? If so, please describe the level and type of experience.</i></span><br/> 					
						<textarea name="languageChoiceExperience" id="languageChoiceExperience" rows=5 style="width:738px; margin: 5px 0px 0px 0px"></textarea>
						<script type="text/javascript">
							var languageChoiceExperience = new LiveValidation('languageChoiceExperience');
							languageChoiceExperience.add(Validate.Presence);
						 </script>
					</td>
				</tr>
				<?
			}		
			?>
			


			<tr class='break'>
				<td colspan=2> 
					<h3>Scholarships</h3>
					<?
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
					<b>Interest</b><br/>
					<span style="font-size: 90%"><i>Indicate if you are interested in a scholarship.</i></span><br/>
				</td>
				<td class="right">
					<input type="radio" id="scholarshipInterest" name="scholarshipInterest" class="type" value="Y" /> Yes
					<input checked type="radio" id="scholarshipInterest" name="scholarshipInterest" class="type" value="N" /> No
				</td>
			</tr>
			<tr>
				<td> 
					<b>Required?</b><br/>
					<span style="font-size: 90%"><i>Is a scholarship <b>required</b> for you to take up a place at <? print $_SESSION[$guid]["organisationNameShort"] ?>?</i></span><br/>
				</td>
				<td class="right">
					<input type="radio" id="scholarshipRequired" name="scholarshipRequired" class="type" value="Y" /> Yes
					<input checked type="radio" id="scholarshipRequired" name="scholarshipRequired" class="type" value="N" /> No
				</td>
			</tr>
			
			
			<tr class='break'>
				<td colspan=2> 
					<h3>Payment</h3>
				</td>
			</tr>
			<script type="text/javascript">
				/* Resource 1 Option Control */
				$(document).ready(function(){
					$("#companyNameRow").css("display","none");
					$("#companyContactRow").css("display","none");
					$("#companyAddressRow").css("display","none");
					$("#companyEmailRow").css("display","none");
					$("#companyPhoneRow").css("display","none");
					$("#companyAllRow").css("display","none");
					$("#companyCategoriesRow").css("display","none");
					
					$(".payment").click(function(){
						if ($('input[name=payment]:checked').val() == "Family" ) {
							$("#companyNameRow").css("display","none");
							$("#companyContactRow").css("display","none");
							$("#companyAddressRow").css("display","none");
							$("#companyEmailRow").css("display","none");
							$("#companyPhoneRow").css("display","none");
							$("#companyAllRow").css("display","none");
							$("#companyCategoriesRow").css("display","none");
						} else {
							$("#companyNameRow").slideDown("fast", $("#companyNameRow").css("display","table-row")); 
							$("#companyContactRow").slideDown("fast", $("#companyContactRow").css("display","table-row")); 
							$("#companyAddressRow").slideDown("fast", $("#companyAddressRow").css("display","table-row")); 
							$("#companyEmailRow").slideDown("fast", $("#companyEmailRow").css("display","table-row")); 
							$("#companyPhoneRow").slideDown("fast", $("#companyPhoneRow").css("display","table-row")); 
							$("#companyAllRow").slideDown("fast", $("#companyAllRow").css("display","table-row")); 
							if ($('input[name=companyAll]:checked').val() == "N" ) {
								$("#companyCategoriesRow").slideDown("fast", $("#companyCategoriesRow").css("display","table-row")); 
							}
						}
					 });
					 
					 $(".companyAll").click(function(){
						if ($('input[name=companyAll]:checked').val() == "Y" ) {
							$("#companyCategoriesRow").css("display","none");
						} else {
							$("#companyCategoriesRow").slideDown("fast", $("#companyCategoriesRow").css("display","table-row")); 
						}
					 });
				});
			</script>
			<tr id="familyRow">
				<td colspan=2>
					<p>If you choose family, future invoices will be sent according to your family's contact preferences, which can be changed at a later date by contacting the school. For example you may wish both parents to receive the invoice, or only one. Alternatively, if you choose Company, you can choose for all or only some fees to be covered by the specified company.</p>
				</td>
			</tr>
			<tr>
				<td> 
					<b>Send Future Invoices To</b><br/>
				</td>
				<td class="right">
					<input type="radio" name="payment" value="Family" class="payment" checked /> Family
					<input type="radio" name="payment" value="Company" class="payment" /> Company
				</td>
			</tr>
			<tr id="companyNameRow">
				<td> 
					<b>Company Name</b><br/>
				</td>
				<td class="right">
					<input name="companyName" id="companyName" maxlength=100 value="" type="text" style="width: 300px">
				</td>
			</tr>
			<tr id="companyContactRow">
				<td> 
					<b>Company Contact Person</b><br/>
				</td>
				<td class="right">
					<input name="companyContact" id="companyContact" maxlength=100 value="" type="text" style="width: 300px">
				</td>
			</tr>
			<tr id="companyAddressRow">
				<td> 
					<b>Company Address</b><br/>
				</td>
				<td class="right">
					<input name="companyAddress" id="companyAddress" maxlength=255 value="" type="text" style="width: 300px">
				</td>
			</tr>
			<tr id="companyEmailRow">
				<td> 
					<b>Company Email</b><br/>
				</td>
				<td class="right">
					<input name="companyEmail" id="companyEmail" maxlength=255 value="" type="text" style="width: 300px">
					<script type="text/javascript">
						var companyEmail = new LiveValidation('companyEmail');
						companyEmail.add(Validate.Email);
					 </script>
				</td>
			</tr>
			<tr id="companyPhoneRow">
				<td> 
					<b>Company Phone</b><br/>
				</td>
				<td class="right">
					<input name="companyPhone" id="companyPhone" maxlength=20 value="" type="text" style="width: 300px">
				</td>
			</tr>
			<?
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
						<b>Company All?</b><br/>
						<span style="font-size: 90%"><i>Should all items be billed to the specified company, or just some?</i></span>
					</td>
					<td class="right">
						<input type="radio" name="companyAll" value="Y" class="companyAll" checked /> All
						<input type="radio" name="companyAll" value="N" class="companyAll" /> Selected
					</td>
				</tr>
				<tr id="companyCategoriesRow">
					<td> 
						<b>Company Fee Categories</b><br/>
						<span style="font-size: 90%"><i>If the specified company is not paying all fees, which categories are they paying?</i></span>
					</td>
					<td class="right">
						<?
						while ($rowCat=$resultCat->fetch()) {
							print $rowCat["name"] . " <input type='checkbox' name='gibbonFinanceFeeCategoryIDList[]' value='" . $rowCat["gibbonFinanceFeeCategoryID"] . "'/><br/>" ;
						}
						print "Other <input type='checkbox' name='gibbonFinanceFeeCategoryIDList[]' value='0001'/><br/>" ;
						?>
					</td>
				</tr>
			<?
			}
			
			$requiredDocuments=getSettingByScope($connection2, "Application Form", "requiredDocuments") ;
			$requiredDocumentsText=getSettingByScope($connection2, "Application Form", "requiredDocumentsText") ;
			$requiredDocumentsCompulsory=getSettingByScope($connection2, "Application Form", "requiredDocumentsCompulsory") ;
			if ($requiredDocuments!="" AND $requiredDocuments!=FALSE) {
				?>
				<tr class='break'>
					<td colspan=2> 
						<h3>Supporting Documents</h3>
						<? 
						if ($requiredDocumentsText!="" OR $requiredDocumentsCompulsory!="") {
							print "<p>" ;
								print $requiredDocumentsText . " " ;
								if ($requiredDocumentsCompulsory=="Y") {
									print "These documents must all be included before the application can be submitted." ;
								}
								else {
									print "These documents are all required, but can be submitted separately to this form if preferred. Please note, however, that your application will be processed faster if the documents are included here." ;
								}
							print "</p>" ;
						}
						?>
					</td>
				</tr>
				<?
				
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
							<b><? print $document ; if ($requiredDocumentsCompulsory=="Y") { print " *" ; } ?></b><br/>
						</td>
						<td class="right">
							<?
							print "<input type='file' name='file$count' id='file$count'><br/>" ;
							print "<input type='hidden' name='fileName$count' id='filefileName$count' value='$document'>" ;
							if ($requiredDocumentsCompulsory=="Y") {
								print "<script type='text/javascript'>" ;
									print "var file$count = new LiveValidation('file$count');" ;
									print "file$count.add( Validate.Inclusion, { within: [" . $ext . "], failureMessage: 'Illegal file type!', partialMatch: true, caseSensitive: false } );" ;
									print "file$count.add(Validate.Presence);" ;
								print "</script>" ;
							}
							$count++ ;
							?>
						</td>
					</tr>
					<?
				}
				?>
				<tr>
					<td colspan=2> 
						<? print getMaxUpload() ; ?>
						<input type="hidden" name="fileCount" value="<? print $count ?>">
					</td>
				</tr>
				<?
			}
			?>
			
			<tr class='break'>
				<td colspan=2> 
					<h3>Miscellaneous</h3>
				</td>
			</tr>
			<tr>
				<td> 
					<b>How Did You Hear About Us? *</b><br/>
				</td>
				<td class="right">
					<?
					$howDidYouHearList=getSettingByScope($connection2, "Application Form", "howDidYouHear") ;
					if ($howDidYouHearList=="") {
						print "<input name='howDidYouHear' id='howDidYouHear' maxlength=30 value='" . $row["howDidYouHear"] . "' type='text' style='width: 300px'>" ;
					}
					else {
						print "<select name='howDidYouHear' id='howDidYouHear' style='width: 302px'>" ;
							print "<option value='Please select...'>Please select...</option>" ;
							$howDidYouHears=explode(",", $howDidYouHearList) ;
							foreach ($howDidYouHears as $howDidYouHear) {
								print "<option value='" . trim($howDidYouHear) . "'>" . trim($howDidYouHear) . "</option>" ;
							}
						print "</select>" ;
						?>
						<script type="text/javascript">
							var howDidYouHear = new LiveValidation('howDidYouHear');
							howDidYouHear.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "Select something!"});
						</script>
						<?
					}
					?>
				</td>
			</tr>
			<script type="text/javascript">
				$(document).ready(function(){
					$("#howDidYouHear").change(function(){
						if ($('#howDidYouHear option:selected').val() == "Please select..." ) {
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
					<b>Tell Us More </b><br/>
					<span style="font-size: 90%"><i>The name of a person or link to a website.</i></span>
				</td>
				<td class="right">
					<input name="howDidYouHearMore" id="howDidYouHearMore" maxlength=255 value="<? print $row["howDidYouHearMore"] ?>" type="text" style="width: 300px">
				</td>
			</tr>
			<?
			$privacySetting=getSettingByScope( $connection2, "User Admin", "privacy" ) ;
			$privacyBlurb=getSettingByScope( $connection2, "User Admin", "privacyBlurb" ) ;
			$privacyOptions=getSettingByScope( $connection2, "User Admin", "privacyOptions" ) ;
			if ($privacySetting=="Y" AND $privacyBlurb!="" AND $privacyOptions!="") {
				?>
				<tr>
					<td> 
						<b>Privacy *</b><br/>
						<span style="font-size: 90%"><i><? print htmlPrep($privacyBlurb) ?><br/>
						</i></span>
					</td>
					<td class="right">
						<?
						$options=explode(",",$privacyOptions) ;
						foreach ($options AS $option) {
							print $option . " <input type='checkbox' name='privacyOptions[]' value='" . htmlPrep($option) . "'/><br/>" ;
						}
						?>

					</td>
				</tr>
				<?
			}
			
			//Get agreement
			$agreement=getSettingByScope($connection2, 'Application Form', 'agreement') ;
			if ($agreement!="") {
				print "<tr>" ;
					print "<td colspan=2>" ; 
						print "<h3>" ; 
							print "Agreement" ;
						print "</h3>" ;
						print "<p>" ;
							print $agreement ;
						print "</p>" ;
					print "</td>" ;
				print "</tr>" ;
				print "<tr>" ;
					print "<td>" ; 
						print "<b>Do you agree to the above?</b><br/>" ;
					print "</td>" ;
					print "<td class='right'>" ;
						print "Yes <input type='checkbox' name='agreement' id='agreement'>" ;
						?>
						<script type="text/javascript">
							var agreement = new LiveValidation('agreement');
							agreement.add( Validate.Acceptance );
						 </script>
						 <?
					print "</td>" ;
				print "</tr>" ;
			}
			else {
				print "<input type='hidden' name='agreement' id='agreement' value='on'>" ;
			}	
			?>
	
		
			<tr>
				<td>
					<span style="font-size: 90%"><i>* denotes a required field</i></span>
				</td>
				<td class="right">
					<input type="hidden" name="address" value="<? print $_SESSION[$guid]["address"] ?>">
					<input type="reset" value="Reset"> <input type="submit" value="Submit">
				</td>
			</tr>
		</table>
	</form>	
	
	<?
	//Get postscrript
	$postscript=getSettingByScope($connection2, 'Application Form', 'postscript') ;
	if ($postscript!="") {
		print "<h2>" ; 
			print "Further Information" ;
		print "</h2>" ;
		print "<p style='padding-bottom: 15px'>" ;
			print $postscript ;
		print "</p>" ;
	}
}
?>