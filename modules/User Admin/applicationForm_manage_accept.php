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

//Module includes
include "./modules/" . $_SESSION[$guid]["module"] . "/moduleFunctions.php" ;


if (isActionAccessible($guid, $connection2, "/modules/User Admin/applicationForm_manage_accept.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/User Admin/applicationForm_manage.php&gibbonSchoolYearID=" . $_GET["gibbonSchoolYearID"] . "'>" . _('Manage Application Forms') . "</a> > </div><div class='trailEnd'>" . _('Accept Application') . "</div>" ;
	print "</div>" ;
	
	//Check if school year specified
	$gibbonApplicationFormID=$_GET["gibbonApplicationFormID"];
	$gibbonSchoolYearID=$_GET["gibbonSchoolYearID"] ;
	$search=$_GET["search"] ;
	if ($gibbonApplicationFormID=="" OR $gibbonSchoolYearID=="") {
		print "<div class='error'>" ;
			print _("You have not specified one or more required parameters.") ;
		print "</div>" ;
	}
	else {
		try {
			$data=array("gibbonApplicationFormID"=>$gibbonApplicationFormID); 
			$sql="SELECT * FROM gibbonApplicationForm WHERE gibbonApplicationFormID=:gibbonApplicationFormID AND (status='Pending' OR status='Waiting List')" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
		
		if ($result->rowCount()!=1) {
			print "<div class='error'>" ;
				print _("The selected application does not exist or has already been processed.") ;
			print "</div>" ;
		}
		else {
			if (isset($_GET["acceptReturn"])) { $acceptReturn=$_GET["acceptReturn"] ; } else { $acceptReturn="" ; }
			$acceptReturnMessage="" ;
			$class="error" ;
			if (!($acceptReturn=="")) {
				if ($acceptReturn=="fail0") {
					$acceptReturnMessage=_("Your request failed because you do not have access to this action.") ;	
				}
				else if ($acceptReturn=="fail1") {
					$acceptReturnMessage=_("Your request failed because your inputs were invalid.") ;	
				}
				else if ($acceptReturn=="fail2") {
					$acceptReturnMessage=_("Your request failed due to a database error.") ;	
				}
				else if ($acceptReturn=="fail3") {
					$acceptReturnMessage=_("Your request failed because your inputs were invalid.") ;	
				}
				else if ($acceptReturn=="success1") {
					$acceptReturnMessage=_("Your request was completed successfully., but status could not be updated.") ;	
				}
				print "<div class='$class'>" ;
					print $acceptReturnMessage;
				print "</div>" ;
			} 

			//Let's go!
			$row=$result->fetch() ;
			$step="" ;
			if (isset($_GET["step"])) {
				$step=$_GET["step"] ;
			}
			if ($step!=1 AND $step!=2) {
				$step=1 ;
			}
			
			//Step 1
			if ($step==1) {
				print "<h3>" ;
				print _("Step") . " $step" ;
				print "</h3>" ;
				
				print "<div class='linkTop'>" ;
					if ($search!="") {
						print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/User Admin/applicationForm_manage.php&gibbonSchoolYearID=$gibbonSchoolYearID&search=$search'>" . _('Back to Search Results') . "</a>" ;
					}
				print "</div>" ;
				?>
				<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/applicationForm_manage_accept.php&step=2&gibbonApplicationFormID=$gibbonApplicationFormID&gibbonSchoolYearID=$gibbonSchoolYearID&search=$search" ?>">
					<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
						<tr>
							<td> 
								<b><?php print sprintf(_('Are you sure you want to accept the application for %1$s?'), formatName("", $row["preferredName"], $row["surname"], "Student")) ?></b><br/>
								<br/>
								<?php
								$checkedStudent="" ;
								if (getSettingByScope( $connection2, "Application Form", "notificationStudentDefault")=="On") {
									$checkedStudent="checked" ;
								}
								?>
								<input <?php print $checkedStudent ?> type='checkbox' name='informStudent'/> <?php print _('Automatically inform <u>student</u> of their Gibbon login details by email?') ?><br/>
								<?php
								$checkedParents="" ;
								if (getSettingByScope( $connection2, "Application Form", "notificationParentsDefault")=="On") {
									$checkedParents="checked" ;
								}
								?>
								<input <?php print $checkedParents ?> type='checkbox' name='informParents'/> <?php print _('Automatically inform <u>parents</u> of their Gibbon login details by email?') ?><br/>
								
								<br/>
								<i><u><?php print _('The system will perform the following actions:') ?></u></i><br/>
								<ol>
									<li><?php print _('Create a Gibbon user account for the student.') ?></li>
									<?php
									if ($row["gibbonRollGroupID"]!="") {
										print "<li>" . _('Enrol the student in the selected school year (as the student has been assigned to a roll group).') . "</li>" ;
									}
									?>
									<li><?php print _('Save the student\'s payment preferences.') ?></li>
									<?php
									if ($row["gibbonFamilyID"]!="") {
										print "<li>" . _('Link the student to their family (who are already in Gibbon).') . "</li>" ;
									}
									else {
										print "<li>" . _('Create a new family.') . "</li>" ;
										print "<li>" . _('Create user accounts for the parents.') . "</li>" ;
										print "<li>" . _('Link student and parents to the family.') . "</li>" ;
									}
									?>
									<li><?php print _('Set the status of the application to "Accepted".') ?></li>
								</ol>
								<br/>
								<i><u><?php print _('But you may wish to manually do the following:') ?></u></i><br/>
								<ol>
									<?php
									if ($row["gibbonRollGroupID"]=="") {
										print "<li>" . _('Enrol the student in the relevant academic year (this will not be done automatically, as the student has not been assigned to a roll group).') . "</li>" ;
									}
									?>
									<li><?php print _('Create a medical record for the student.') ?></li>
									<li><?php print _('Create an individual needs record for the student.') ?></li>
									<li><?php print _('Create a note of the student\'s scholarship information outside of Gibbon.') ?></li>
									<li><?php print _('Create a timetable for the student.') ?></li>
								</ol>
							</td>
						</tr>
						<tr>
							<td class='right'> 
								<input name="gibbonSchoolYearID" id="gibbonSchoolYearID" value="<?php print $gibbonSchoolYearID ?>" type="hidden">
								<input name="gibbonApplicationFormID" id="gibbonApplicationFormID" value="<?php print $gibbonApplicationFormID ?>" type="hidden">
								<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
								<input type="submit" value="Accept">
							</td>
						</tr>
					</table>
				</form>				
				<?php
			}
			else if ($step==2) {
				print "<h3>" ;
				print _("Step") . " $step" ;
				print "</h3>" ;
				
				print "<div class='linkTop'>" ;
					if ($search!="") {
						print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/User Admin/applicationForm_manage.php&gibbonSchoolYearID=$gibbonSchoolYearID&search=$search'>" . _('Back to Search Results') . "</a>" ;
					}
				print "</div>" ;
				
				//Set up variables for automatic email to participants, if selected in Step 1.
				$informParents="N" ;
				if (isset($_POST["informParents"])) {
					if ($_POST["informParents"]=="on") {
						$informParents="Y" ;
						$informParentsArray=array() ;
					}
				}
				$informStudent="N" ;
				if (isset($_POST["informStudent"])) {
					if ($_POST["informStudent"]=="on") {
						$informStudent="Y" ;
						$informStudentArray=array() ;
					}
				}
				
				//CREATE STUDENT
				$failStudent=TRUE ;
				$lock=true ;
				try {
					$sql="LOCK TABLES gibbonPerson WRITE, gibbonSetting WRITE, gibbonSchoolYear WRITE, gibbonYearGroup WRITE, gibbonRollGroup WRITE" ;
					$result=$connection2->query($sql);   
				}
				catch(PDOException $e) { 
					$lock=false ;
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				if ($lock==true) {
					$gotAI=true ;
					try {
						$sqlAI="SHOW TABLE STATUS LIKE 'gibbonPerson'";
						$resultAI=$connection2->query($sqlAI);   
					}
					catch(PDOException $e) { 
						$gotAI=false ;
						print "<div class='error'>" . $e->getMessage() . "</div>" ; 
					}

					if ($gotAI==true) {
						$rowAI=$resultAI->fetch();
						$gibbonPersonID=str_pad($rowAI['Auto_increment'], 10, "0", STR_PAD_LEFT) ;
					
						//Set username & password
						$username=substr(str_replace(" ", "", preg_replace("/[^A-Za-z ]/", '', strtolower(substr($row["preferredName"],0,1) . $row["surname"]))), 0, 12);
						$usernameBase=$username ;
						$count=1 ;
						$continueLoop=TRUE ;
						while ($continueLoop==TRUE AND $count<10000) {
							$gotUsername=true ;
							try {
								$dataUsername=array("username"=>$username); 
								$sqlUsername="SELECT * FROM gibbonPerson WHERE username=:username" ;
								$resultUsername=$connection2->prepare($sqlUsername);
								$resultUsername->execute($dataUsername);
							}
							catch(PDOException $e) { 
								$gotUsername=false ;
								print "<div class='error'>" . $e->getMessage() . "</div>" ; 
							}
							
							if ($resultUsername->rowCount()==0 AND $gotUsername==true) {
								$continueLoop=FALSE ;
							}
							else {
								$username=$usernameBase . $count ;
							}
							$count++ ;
						}
						
						$password=randomPassword(8);
						$salt=getSalt() ;
						$passwordStrong=hash("sha256", $salt.$password) ;
						
						$lastSchool="" ;
						if ($row["schoolDate1"]>$row["schoolDate2"] ) {
							$lastSchool=$row["schoolName1"] ;
						}
						else if ($row["schoolDate2"]>$row["schoolDate1"] ) {
							$lastSchool=$row["schoolName2"] ;
						}
						
						//Set default email address for student
						$email=$row["email"] ;
						$emailAlternate="" ;
						$studentDefaultEmail=getSettingByScope( $connection2, "Application Form", "studentDefaultEmail") ;
						if ($studentDefaultEmail!="") {
							$emailAlternate=$email ;
							$email=str_replace("[username]", $username, $studentDefaultEmail) ; 
						}
						
						//Set default website address for student
						$website="" ;
						$studentDefaultWebsite=getSettingByScope( $connection2, "Application Form", "studentDefaultWebsite") ;
						if ($studentDefaultWebsite!="") {
							$website=str_replace("[username]", $username, $studentDefaultWebsite) ; 
						}
						
						//Email website and email address to admin for creation
						if ($studentDefaultEmail!="" OR $studentDefaultWebsite!="") {
							print "<h4>" ;
							print _("Student Email & Website") ;
							print "</h4>" ;
							$to=$_SESSION[$guid]["organisationAdministratorEmail"] ;
							$subject=sprintf(_('Create Student Email/Websites for %1$s at %2$s'), $_SESSION[$guid]["systemName"], $_SESSION[$guid]["organisationNameShort"]) ;
							$body=sprintf(_('Please create the following for new student %1$s.'), formatName("", $row["preferredName"], $row["surname"], "Student")) . "\n\n" ;
							if ($studentDefaultEmail!="") {
								$body.=_("Email") . ": " . $email . "\n" ;
							}
							if ($studentDefaultWebsite!="") {
								$body.=_("Website") . ": " . $website . "\n" ;
							}
							if ($row["gibbonSchoolYearIDEntry"]!="") {
								try {
									$dataYearGroup=array("gibbonSchoolYearID"=>$row["gibbonSchoolYearIDEntry"]); 
									$sqlYearGroup="SELECT * FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID" ;
									$resultYearGroup=$connection2->prepare($sqlYearGroup);
									$resultYearGroup->execute($dataYearGroup);
								}
								catch(PDOException $e) { }
								
								if ($resultYearGroup->rowCount()==1) {
									$rowYearGroup=$resultYearGroup->fetch() ;
									if ($rowYearGroup["name"]!="") {
											$body.=_("School Year") . ": " . $rowYearGroup["name"] . "\n" ;
									}
								}
							}
							if ($row["gibbonYearGroupIDEntry"]!="") {
								try {
									$dataYearGroup=array("gibbonYearGroupID"=>$row["gibbonYearGroupIDEntry"]); 
									$sqlYearGroup="SELECT * FROM gibbonYearGroup WHERE gibbonYearGroupID=:gibbonYearGroupID" ;
									$resultYearGroup=$connection2->prepare($sqlYearGroup);
									$resultYearGroup->execute($dataYearGroup);
								}
								catch(PDOException $e) { }
								
								if ($resultYearGroup->rowCount()==1) {
									$rowYearGroup=$resultYearGroup->fetch() ;
									if ($rowYearGroup["name"]!="") {
											$body.=_("Year Group") . ": " . $rowYearGroup["name"] . "\n" ;
									}
								}
							}
							if ($row["gibbonRollGroupID"]!="") {
								try {
									$dataYearGroup=array("gibbonRollGroupID"=>$row["gibbonRollGroupID"]); 
									$sqlYearGroup="SELECT * FROM gibbonRollGroup WHERE gibbonRollGroupID=:gibbonRollGroupID" ;
									$resultYearGroup=$connection2->prepare($sqlYearGroup);
									$resultYearGroup->execute($dataYearGroup);
								}
								catch(PDOException $e) { }
								
								if ($resultYearGroup->rowCount()==1) {
									$rowYearGroup=$resultYearGroup->fetch() ;
									if ($rowYearGroup["name"]!="") {
											$body.=_("Roll Group") . ": " . $rowYearGroup["name"] . "\n" ;
									}
								}
							}
							if ($row["dateStart"]!="") {
									$body.=_("Start Date") . ": " .  dateConvertBack($guid, $row["dateStart"]) . "\n" ;
							}
							
							$headers="From: " . $_SESSION[$guid]["organisationAdministratorEmail"] ;

							if (mail($to, $subject, $body, $headers)) {
								print "<div class='success'>" ;
									print sprintf(_('A request to create a student email address and/or website address was successfully sent to %1$s.'), $_SESSION[$guid]["organisationAdministratorName"]) ;
								print "</div>" ;
							}
							else {
								print "<div class='error'>" ;
									print sprintf(_('A request to create a student email address and/or website address failed. Please contact %1$s to request these manually.'), $_SESSION[$guid]["organisationAdministratorName"]) ;
								print "</div>" ;
							}
						}
						
						if ($continueLoop==FALSE) {
							$insertOK=true ;
							try {
								$data=array("username"=>$username, "passwordStrong"=>$passwordStrong, "passwordStrongSalt"=>$salt, "surname"=>$row["surname"], "firstName"=>$row["firstName"], "preferredName"=>$row["preferredName"], "officialName"=>$row["officialName"], "nameInCharacters"=>$row["nameInCharacters"], "gender"=>$row["gender"], "dob"=>$row["dob"], "languageFirst"=>$row["languageFirst"], "languageSecond"=>$row["languageSecond"], "languageThird"=>$row["languageThird"], "countryOfBirth"=>$row["countryOfBirth"], "citizenship1"=>$row["citizenship1"], "citizenship1Passport"=>$row["citizenship1Passport"], "nationalIDCardNumber"=>$row["nationalIDCardNumber"], "residencyStatus"=>$row["residencyStatus"], "visaExpiryDate"=>$row["visaExpiryDate"], "email"=>$email, "emailAlternate"=>$emailAlternate, "website"=>$website, "phone1Type"=>$row["phone1Type"],"phone1CountryCode"=>$row["phone1CountryCode"],"phone1"=>$row["phone1"],"phone2Type"=>$row["phone2Type"],"phone2CountryCode"=>$row["phone2CountryCode"],"phone2"=>$row["phone2"], "lastSchool"=>$lastSchool, "dateStart"=>$row["dateStart"], "privacy"=>$row["privacy"], "dayType"=>$row["dayType"], "fields"=>$row["fields"]); 
								$sql="INSERT INTO gibbonPerson SET username=:username, password='', passwordStrong=:passwordStrong, passwordStrongSalt=:passwordStrongSalt, gibbonRoleIDPrimary='003', gibbonRoleIDAll='003', status='Full', surname=:surname, firstName=:firstName, preferredName=:preferredName, officialName=:officialName, nameInCharacters=:nameInCharacters, gender=:gender, dob=:dob, languageFirst=:languageFirst, languageSecond=:languageSecond, languageThird=:languageThird, countryOfBirth=:countryOfBirth, citizenship1=:citizenship1, citizenship1Passport=:citizenship1Passport, nationalIDCardNumber=:nationalIDCardNumber, residencyStatus=:residencyStatus, visaExpiryDate=:visaExpiryDate, email=:email, emailAlternate=:emailAlternate, website=:website, phone1Type=:phone1Type, phone1CountryCode=:phone1CountryCode, phone1=:phone1, phone2Type=:phone2Type, phone2CountryCode=:phone2CountryCode, phone2=:phone2, lastSchool=:lastSchool, dateStart=:dateStart, privacy=:privacy, dayType=:dayType, fields=:fields" ;
								$result=$connection2->prepare($sql);
								$result->execute($data);
							}
							catch(PDOException $e) { 
								$insertOK=false ;
								print "<div class='error'>" . $e->getMessage() . "</div>" ; 
							}
							if ($insertOK==true) {
								$failStudent=FALSE ;
								
								//Populate informStudent array
								if ($informStudent=="Y") {
									$informStudentArray[0]["email"]=$row["email"] ;
									$informStudentArray[0]["surname"]=$row["surname"] ;
									$informStudentArray[0]["preferredName"]=$row["preferredName"] ;
									$informStudentArray[0]["username"]=$username ;
									$informStudentArray[0]["password"]=$password ;
								}
							}
						}
					}
				}
				try {
					$sql="UNLOCK TABLES" ;
					$result=$connection2->query($sql);   
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				
				if ($failStudent==TRUE) {
					print "<div class='error'>" ;
					print _("Student could not be created!") ;
					print "</div>" ;
				}
				else {
					print "<h4>" ;
					print _("Student Details") ;
					print "</h4>" ;
					print "<ul>" ;
						print "<li><b>gibbonPersonID</b>: $gibbonPersonID</li>" ;
						print "<li><b>" . _('Name')  ."</b>: " . formatName("", $row["preferredName"], $row["surname"], "Student") . "</li>" ;
						print "<li><b>" . _('Email') . "</b>: " . $email . "</li>" ;
						print "<li><b>" . _('Email Alternate') . "</b>: " . $emailAlternate . "</li>" ;
						print "<li><b>" . _('Username') . "</b>: $username</li>" ;
						print "<li><b>" . _('Password') ."</b>: $password</li>" ;
					print "</ul>" ;
					
					
					//Move documents to student notes
					try {
						$dataDoc=array("gibbonApplicationFormID"=>$gibbonApplicationFormID); 
						$sqlDoc="SELECT * FROM gibbonApplicationFormFile WHERE gibbonApplicationFormID=:gibbonApplicationFormID" ;
						$resultDoc=$connection2->prepare($sqlDoc);
						$resultDoc->execute($dataDoc);
					}
					catch(PDOException $e) { 
						print "<div class='error'>" . $e->getMessage() . "</div>" ; 
					}	
					if ($resultDoc->rowCount()>0) {
						$note="<p><b>" . _('Application Documents:') . " </b><br/>" ;
						while ($rowDoc=$resultDoc->fetch()) {
							$note.="<a href='" . $_SESSION[$guid]["absoluteURL"] . "/" . $rowDoc["path"] . "'>" . $rowDoc["name"] . "</a><br/>" ;
						}
						$note.="</p>" ;
						try {
							$data=array("gibbonPersonID"=>$gibbonPersonID, "note"=>$note, "gibbonPersonIDCreator"=>$_SESSION[$guid]["gibbonPersonID"], "timestamp"=>date('Y-m-d H:i:s')); 
							$sql="INSERT INTO gibbonStudentNote SET gibbonPersonID=:gibbonPersonID, gibbonStudentNoteCategoryID=NULL, note=:note, gibbonPersonIDCreator=:gibbonPersonIDCreator, timestamp=:timestamp" ;
							$result=$connection2->prepare($sql);
							$result->execute($data);
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}	
					}
					
						
					//Enrol student
					$enrolmentOK=true ;
					if ($row["gibbonRollGroupID"]!="") {
						if ($gibbonPersonID!="" AND $row["gibbonSchoolYearIDEntry"]!="" AND $row["gibbonYearGroupIDEntry"]!="") {
							try {
								$data=array("gibbonPersonID"=>$gibbonPersonID, "gibbonSchoolYearID"=>$row["gibbonSchoolYearIDEntry"], "gibbonYearGroupID"=>$row["gibbonYearGroupIDEntry"], "gibbonRollGroupID"=>$row["gibbonRollGroupID"]); 
								$sql="INSERT INTO gibbonStudentEnrolment SET gibbonPersonID=:gibbonPersonID, gibbonSchoolYearID=:gibbonSchoolYearID, gibbonYearGroupID=:gibbonYearGroupID, gibbonRollGroupID=:gibbonRollGroupID" ;
								$result=$connection2->prepare($sql);
								$result->execute($data);
							}
							catch(PDOException $e) { 
								$enrolmentOK=false ;
								print "<div class='error'>" . $e->getMessage() . "</div>" ; 
							}	
						}
						else {
							$enrolmentOK=false ;
						}
						
						//Report back
						if ($enrolmentOK==false) {
							print "<div class='warning'>" ;
							print _("Student could not be enroled, so this will have to be done manually at a later date.") ;
							print "</div>" ;
						}
						else {
							print "<h4>" ;
							print "Student Enrolment" ;
							print "</h4>" ;
							print "<ul>" ;
								print "<li>" . _('The student has successfully been enroled in the specified school year, year group and roll group.') . "</li>" ;
							print "</ul>" ;
						}
					}
					
					//SAVE PAYMENT PREFERENCES
					$failPayment=TRUE ;
					$invoiceTo=$row["payment"] ;
					if ($invoiceTo=="Company") {
						$companyName=$row["companyName"] ;
						$companyContact=$row["companyContact"] ;
						$companyAddress=$row["companyAddress"] ;
						$companyEmail=$row["companyEmail"] ;
						$companyPhone=$row["companyPhone"] ;
						$companyAll=$row["companyAll"] ;
						$gibbonFinanceFeeCategoryIDList=NULL ;
						if ($companyAll=="N") {
							$gibbonFinanceFeeCategoryIDList="" ;
							$gibbonFinanceFeeCategoryIDArray=explode(",",$row["gibbonFinanceFeeCategoryIDList"]) ;
							if (count($gibbonFinanceFeeCategoryIDArray)>0) {
								foreach ($gibbonFinanceFeeCategoryIDArray AS $gibbonFinanceFeeCategoryID) {
									$gibbonFinanceFeeCategoryIDList.=$gibbonFinanceFeeCategoryID . "," ;
								}
								$gibbonFinanceFeeCategoryIDList=substr($gibbonFinanceFeeCategoryIDList,0,-1) ;
							}
						}
					}
					else {
						$companyName=NULL ;
						$companyContact=NULL ;
						$companyAddress=NULL ;
						$companyEmail=NULL ;
						$companyPhone=NULL ;
						$companyAll=NULL ;
						$gibbonFinanceFeeCategoryIDList=NULL ;
					}
					$paymentOK=true ;
					try {
						$data=array("gibbonPersonID"=>$gibbonPersonID, "invoiceTo"=>$invoiceTo, "companyName"=>$companyName, "companyContact"=>$companyContact, "companyAddress"=>$companyAddress, "companyEmail"=>$companyEmail, "companyPhone"=>$companyPhone,"companyAll"=>$companyAll, "gibbonFinanceFeeCategoryIDList"=>$gibbonFinanceFeeCategoryIDList); 
						$sql="INSERT INTO gibbonFinanceInvoicee SET gibbonPersonID=:gibbonPersonID, invoiceTo=:invoiceTo, companyName=:companyName, companyContact=:companyContact, companyAddress=:companyAddress, companyEmail=:companyEmail, companyPhone=:companyPhone, companyAll=:companyAll, gibbonFinanceFeeCategoryIDList=:gibbonFinanceFeeCategoryIDList" ;
						$result=$connection2->prepare($sql);
						$result->execute($data);
					}
					catch(PDOException $e) { 
						$paymentOK=false ;
						print "<div class='error'>" . $e->getMessage() . "</div>" ; 
					}
					
					if ($paymentOK==false) {
						print "<div class='warning'>" ;
						print _("Student payment details could not be saved, but we will continue, as this is a minor issue.") ;
						print "</div>" ;
					}
					
					$failFamily=true ;
					if ($row["gibbonFamilyID"]!="") {
						//CONNECT STUDENT TO FAMILY
						try {
							$dataFamily=array("gibbonFamilyID"=>$row["gibbonFamilyID"]); 
							$sqlFamily="SELECT * FROM gibbonFamily WHERE gibbonFamilyID=:gibbonFamilyID" ;
							$resultFamily=$connection2->prepare($sqlFamily);
							$resultFamily->execute($dataFamily);
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						if ($resultFamily->rowCount()==1) {
							$rowFamily=$resultFamily->fetch() ;
							$familyName=$rowFamily["name"] ;
							if ($familyName!=""){
								$insertFail=false ;
								try {
									$data=array("gibbonPersonID"=>$gibbonPersonID, "gibbonFamilyID"=>$row["gibbonFamilyID"]); 
									$sql="INSERT INTO gibbonFamilyChild SET gibbonPersonID=:gibbonPersonID, gibbonFamilyID=:gibbonFamilyID" ;
									$result=$connection2->prepare($sql);
									$result->execute($data);
								}
								catch(PDOException $e) { 
									$insertFail==true ;
									print "<div class='error'>" . $e->getMessage() . "</div>" ; 
								}
								if ($insertFail==false) {
									$failFamily=false ;
								}
							}
						}
						
						try {
							$dataParents=array("gibbonFamilyID"=>$row["gibbonFamilyID"]); 
							$sqlParents="SELECT gibbonFamilyAdult.*, gibbonPerson.gibbonRoleIDAll FROM gibbonFamilyAdult JOIN gibbonPerson ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonFamilyID=:gibbonFamilyID" ; 
							$resultParents=$connection2->prepare($sqlParents);
							$resultParents->execute($dataParents);
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						while ($rowParents=$resultParents->fetch()) {
							//Update parent roles
							if (strpos($rowParents["gibbonRoleIDAll"], "004")===FALSE) {
								try {
									$dataRoleUpdate=array("gibbonPersonID"=>$rowParents["gibbonPersonID"]); 
									$sqlRoleUpdate="UPDATE gibbonPerson SET gibbonRoleIDAll=concat(gibbonRoleIDAll, ',004') WHERE gibbonPersonID=:gibbonPersonID" ;
									$resultRoleUpdate=$connection2->prepare($sqlRoleUpdate);
									$resultRoleUpdate->execute($dataRoleUpdate);
								}
								catch(PDOException $e) { 
									print "<div class='error'>" . $e->getMessage() . "</div>" ; 
								}
							}
							
							//Add relationship record for each parent
							try {
								$dataRelationship=array("gibbonApplicationFormID"=>$gibbonApplicationFormID, "gibbonPersonID"=>$rowParents["gibbonPersonID"]); 
								$sqlRelationship="SELECT * FROM gibbonApplicationFormRelationship WHERE gibbonApplicationFormID=:gibbonApplicationFormID AND gibbonPersonID=:gibbonPersonID" ;
								$resultRelationship=$connection2->prepare($sqlRelationship);
								$resultRelationship->execute($dataRelationship);
							}
							catch(PDOException $e) { 
								print "<div class='error'>" . $e->getMessage() . "</div>" ;
							}
							if ($resultRelationship->rowCount()==1) {
								$rowRelationship=$resultRelationship->fetch() ;
								$relationship=$rowRelationship["relationship"] ;
								try {
									$data=array("gibbonFamilyID"=>$row["gibbonFamilyID"], "gibbonPersonID1"=>$rowParents["gibbonPersonID"], "gibbonPersonID2"=>$gibbonPersonID); 
									$sql="SELECT * FROM gibbonFamilyRelationship WHERE gibbonFamilyID=:gibbonFamilyID AND gibbonPersonID1=:gibbonPersonID1 AND gibbonPersonID2=:gibbonPersonID2" ;
									$result=$connection2->prepare($sql);
									$result->execute($data);
								}
								catch(PDOException $e) { 
									print "<div class='error'>" . $e->getMessage() . "</div>" ;
								}
								if ($result->rowCount()==0) {
									try {
										$data=array("gibbonFamilyID"=>$row["gibbonFamilyID"], "gibbonPersonID1"=>$rowParents["gibbonPersonID"], "gibbonPersonID2"=>$gibbonPersonID, "relationship"=>$relationship); 
										$sql="INSERT INTO gibbonFamilyRelationship SET gibbonFamilyID=:gibbonFamilyID, gibbonPersonID1=:gibbonPersonID1, gibbonPersonID2=:gibbonPersonID2, relationship=:relationship" ;
										$result=$connection2->prepare($sql);
										$result->execute($data);
									}
									catch(PDOException $e) { 
										print "<div class='error'>" . $e->getMessage() . "</div>" ;
									}
								}
								else if ($result->rowCount()==1) {
									$row=$result->fetch() ;
				
									if ($row["relationship"]!=$relationship) {
										try {
											$data=array("relationship"=>$relationship, "gibbonFamilyRelationshipID"=>$row["gibbonFamilyRelationshipID"]); 
											$sql="UPDATE gibbonFamilyRelationship SET relationship=:relationship WHERE gibbonFamilyRelationshipID=:gibbonFamilyRelationshipID" ;
											$result=$connection2->prepare($sql);
											$result->execute($data);
										}
										catch(PDOException $e) { 
											print "<div class='error'>" . $e->getMessage() . "</div>" ;
										}
									}
								}
								else {
									print "<div class='error'>" . $e->getMessage() . "</div>" ;
								}
							}
						}
						
						if ($failFamily==TRUE) {
							print "<div class='warning'>" ;
							print _("Student could not be linked to family!") ;
							print "</div>" ;
						}
						else {
							print "<h4>" ;
							print _("Family") ;
							print "</h4>" ;
							print "<ul>" ;
								print "<li><b>gibbonFamilyID</b>: " . $row["gibbonFamilyID"] . "</li>" ;
								print "<li><b>" . _('Family Name') . "</b>: $familyName </li>" ;
								print "<li><b>" . _('Roles') . "</b>: " . _('System has tried to assign parents "Parent" role access if they did not already have it.') . "</li>" ;
							print "</ul>" ;
						}
					}
					else {
						//CREATE A NEW FAMILY
						$failFamily=TRUE ;
						$lock=true ;
						try {
							$sql="LOCK TABLES gibbonFamily WRITE" ;
							$result=$connection2->query($sql);   
						}
						catch(PDOException $e) { 
							$lock=false ;
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						if ($lock==true) {
							$gotAI=true ;
							try {
								$sqlAI="SHOW TABLE STATUS LIKE 'gibbonFamily'";
								$resultAI=$connection2->query($sqlAI);   
							}
							catch(PDOException $e) { 
								$gotAI=false ;
								print "<div class='error'>" . $e->getMessage() . "</div>" ; 
							}
							if ($gotAI==true) {
								$rowAI=$resultAI->fetch();
								$gibbonFamilyID=str_pad($rowAI['Auto_increment'], 7, "0", STR_PAD_LEFT) ;
							
								$familyName=$row["parent1preferredName"] . " " . $row["parent1surname"] ; 
								if ($row["parent2preferredName"]!="" AND $row["parent2surname"]!="") {
									$familyName.=" & " . $row["parent2preferredName"] . " " . $row["parent2surname"] ; 
								}
								$nameAddress="" ;
								//Parents share same surname and parent 2 has enough information to be added
								if ($row["parent1surname"]==$row["parent2surname"] AND $row["parent2preferredName"]!="" AND $row["parent2title"]!="") {
									$nameAddress=$row["parent1title"] . " & " . $row["parent2title"] . " " . $row["parent1surname"] ;
								}
								//Parents have different names, and parent2 is not blank and has enough information to be added
								else if ($row["parent1surname"]!=$row["parent2surname"] AND $row["parent2surname"]!="" AND $row["parent2preferredName"]!="" AND $row["parent2title"]!="") {
									$nameAddress=$row["parent1title"] . " " . $row["parent1surname"] . " & " . $row["parent2title"] . " " . $row["parent2surname"] ;
								}
								//Just use parent1's name 
								else {
									$nameAddress=$row["parent1title"] . " " . $row["parent1surname"] ;
								}
								$languageHome=$row["languageHome"] ; 
								
								$insertOK=true ;
								try {
									$data=array("familyName"=>$familyName, "nameAddress"=>$nameAddress, "languageHome"=>$languageHome, "homeAddress"=>$row["homeAddress"], "homeAddressDistrict"=>$row["homeAddressDistrict"], "homeAddressCountry"=>$row["homeAddressCountry"]); 
									$sql="INSERT INTO gibbonFamily SET name=:familyName, nameAddress=:nameAddress, languageHome=:languageHome, homeAddress=:homeAddress, homeAddressDistrict=:homeAddressDistrict, homeAddressCountry=:homeAddressCountry" ;
									$result=$connection2->prepare($sql);
									$result->execute($data);
								}
								catch(PDOException $e) { 
									$insertOK=false ;
									print "<div class='error'>" . $e->getMessage() . "</div>" ; 
								}
								if ($insertOK==true) {
									$failFamily=false ;
								}
							}
						}
						try {
							$sql="UNLOCK TABLES" ;
							$result=$connection2->query($sql);   
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}

						if ($failFamily==TRUE) {
							print "<div class='error'>" ;
							print _("Family could not be created!") ;
							print "</div>" ;
						}
						else {
							print "<h4>" ;
							print _("Family Details") ;
							print "</h4>" ;
							print "<ul>" ;
								print "<li><b>gibbonFamilyID</b>: $gibbonFamilyID</li>" ;
								print "<li><b>" . _('Family Name') . "</b>: $familyName</li>" ;
								print "<li><b>" . _('Address Name') . "</b>: $nameAddress</li>" ;
							print "</ul>" ;
							
							//LINK STUDENT INTO FAMILY
							$failFamily=TRUE ;
							if ($gibbonFamilyID!="") {
								try {
									$dataFamily=array("gibbonFamilyID"=>$gibbonFamilyID); 
									$sqlFamily="SELECT * FROM gibbonFamily WHERE gibbonFamilyID=:gibbonFamilyID" ;
									$resultFamily=$connection2->prepare($sqlFamily);
									$resultFamily->execute($dataFamily);
								}
								catch(PDOException $e) { 
									print "<div class='error'>" . $e->getMessage() . "</div>" ; 
								}
							
								if ($resultFamily->rowCount()==1) {
									$rowFamily=$resultFamily->fetch() ;
									$familyName=$rowFamily["name"] ;
									if ($familyName!=""){
										$insertOK=true ;
										try {
											$data=array("gibbonPersonID"=>$gibbonPersonID, "gibbonFamilyID"=>$gibbonFamilyID); 
											$sql="INSERT INTO gibbonFamilyChild SET gibbonPersonID=:gibbonPersonID, gibbonFamilyID=:gibbonFamilyID" ;
											$result=$connection2->prepare($sql);
											$result->execute($data);
										}
										catch(PDOException $e) { 
											$insertOK=false ;
											print "<div class='error'>" . $e->getMessage() . "</div>" ; 
										}
										if ($insertOK==true) {
											$failFamily=FALSE ;
										}
									}
								}
								
								if ($failFamily==TRUE) {
									print "<div class='warning'>" ;
									print _("Student could not be linked to family!") ;
									print "</div>" ;
								}
							}
							
							//CREATE PARENT 1
							$failParent1=TRUE ;
							if ($row["parent1gibbonPersonID"]!="") {
								$gibbonPersonIDParent1=$row["parent1gibbonPersonID"];
								print "<h4>" ;
								print "Parent 1" ;
								print "</h4>" ;
								print "<ul>" ;
									print "<li>" . _('Parent 1 already exists in Gibbon, and so does not need a new account.') . "</li>" ;
									print "<li><b>gibbonPersonID</b>: $gibbonPersonIDParent1</li>" ;
									print "<li><b>" . _('Name') . "</b>: " . formatName("", $row["parent1preferredName"], $row["parent1surname"], "Parent") . "</li>" ;
								print "</ul>" ;
								
								//LINK PARENT 1 INTO FAMILY
								$failFamily=TRUE ;
								if ($gibbonFamilyID!="") {
									try {
										$dataFamily=array("gibbonFamilyID"=>$gibbonFamilyID); 
										$sqlFamily="SELECT * FROM gibbonFamily WHERE gibbonFamilyID=:gibbonFamilyID" ;
										$resultFamily=$connection2->prepare($sqlFamily);
										$resultFamily->execute($dataFamily);
									}
									catch(PDOException $e) { 
										print "<div class='error'>" . $e->getMessage() . "</div>" ; 
									}
									if ($resultFamily->rowCount()==1) {
										$rowFamily=$resultFamily->fetch() ;
										$familyName=$rowFamily["name"] ;
										if ($familyName!=""){
											$insertOK=true ;
											try {
												$data=array("gibbonPersonID"=>$gibbonPersonIDParent1, "gibbonFamilyID"=>$gibbonFamilyID); 
												$sql="INSERT INTO gibbonFamilyAdult SET gibbonPersonID=:gibbonPersonID, gibbonFamilyID=:gibbonFamilyID, contactPriority=1, contactCall='Y', contactSMS='Y', contactEmail='Y', contactMail='Y'" ;
												$result=$connection2->prepare($sql);
												$result->execute($data);
											}
											catch(PDOException $e) { 
												$insertOK=false ;
												print "<div class='error'>" . $e->getMessage() . "</div>" ; 
											}
											if ($insertOK==true) {
												$failFamily=FALSE ;
											}
										}
									}
									
									if ($failFamily==TRUE) {
										print "<div class='warning'>" ;
										print _("Parent 1 could not be linked to family!") ;
										print "</div>" ;
									}
								}
								
								//Set parent relationship
								try {
									$data=array("gibbonFamilyID"=>$gibbonFamilyID, "gibbonPersonID1"=>$gibbonPersonIDParent1, "gibbonPersonID2"=>$gibbonPersonID, "relationship"=>$row["parent1relationship"]); 
									$sql="INSERT INTO gibbonFamilyRelationship SET gibbonFamilyID=:gibbonFamilyID, gibbonPersonID1=:gibbonPersonID1, gibbonPersonID2=:gibbonPersonID2, relationship=:relationship" ;
									$result=$connection2->prepare($sql);
									$result->execute($data);
								}
								catch(PDOException $e) { 
									print "<div class='error'>" . $e->getMessage() . "</div>" ;
								}
							}
							else {
								$lock=true ;
								try {
									$sql="LOCK TABLES gibbonPerson WRITE" ;
									$result=$connection2->query($sql);   
								}
								catch(PDOException $e) { 
									$lock=false ;
									print "<div class='error'>" . $e->getMessage() . "</div>" ; 
								}
								if ($lock==true) {
									$gotAI=true ;
									try {
										$sqlAI="SHOW TABLE STATUS LIKE 'gibbonPerson'";
										$resultAI=$connection2->query($sqlAI);   
									}
									catch(PDOException $e) { 
										$gotAI=false ;
										print "<div class='error'>" . $e->getMessage() . "</div>" ; 
									}
				
									if ($gotAI==true) {
										$rowAI=$resultAI->fetch();
										$gibbonPersonIDParent1=str_pad($rowAI['Auto_increment'], 10, "0", STR_PAD_LEFT) ;
									
										//Set username & password
										$username=substr(str_replace(" ", "", preg_replace("/[^A-Za-z ]/", '', strtolower(substr($row["parent1preferredName"],0,1) . $row["parent1surname"]))), 0, 12);
										$usernameBase=$username ;
										$count=1 ;
										$continueLoop=TRUE ;
										while ($continueLoop==TRUE AND $count<10000) {
											$gotUsername=true ;
											try {
												$dataUsername=array("username"=>$username); 
												$sqlUsername="SELECT * FROM gibbonPerson WHERE username=:username" ;
												$resultUsername=$connection2->prepare($sqlUsername);
												$resultUsername->execute($dataUsername);
											}
											catch(PDOException $e) { 
												$gotUsername=false ;
												print "<div class='error'>" . $e->getMessage() . "</div>" ; 
											}
											
											if ($resultUsername->rowCount()==0 AND $gotUsername==true) {
												$continueLoop=FALSE ;
											}
											else {
												$username=$usernameBase . $count ;
											}
											$count++ ;
										}
										
										$password=randomPassword(8);
										$salt=getSalt() ;
										$passwordStrong=hash("sha256", $salt.$password) ;
										
										if ($continueLoop==FALSE) {
											$insertOK=true ;
											try {
												$data=array("username"=>$username, "passwordStrong"=>$passwordStrong, "passwordStrongSalt"=>$salt, "title"=>$row["parent1title"], "surname"=>$row["parent1surname"], "firstName"=>$row["parent1firstName"], "preferredName"=>$row["parent1preferredName"], "officialName"=>$row["parent1officialName"], "nameInCharacters"=>$row["parent1nameInCharacters"], "gender"=>$row["parent1gender"], "parent1languageFirst"=>$row["parent1languageFirst"], "parent1languageSecond"=>$row["parent1languageSecond"], "citizenship1"=>$row["parent1citizenship1"], "nationalIDCardNumber"=>$row["parent1nationalIDCardNumber"], "residencyStatus"=>$row["parent1residencyStatus"], "visaExpiryDate"=>$row["parent1visaExpiryDate"], "email"=>$row["parent1email"], "phone1Type"=>$row["parent1phone1Type"],"phone1CountryCode"=>$row["parent1phone1CountryCode"],"phone1"=>$row["parent1phone1"],"phone2Type"=>$row["parent1phone2Type"],"phone2CountryCode"=>$row["parent1phone2CountryCode"],"phone2"=>$row["parent1phone2"], "profession"=>$row["parent1profession"], "employer"=>$row["parent1employer"], "parent1fields"=>$row["parent1fields"]); 
												$sql="INSERT INTO gibbonPerson SET username=:username, password='', passwordStrong=:passwordStrong, passwordStrongSalt=:passwordStrongSalt, gibbonRoleIDPrimary='004', gibbonRoleIDAll='004', status='Full', title=:title, surname=:surname, firstName=:firstName, preferredName=:preferredName, officialName=:officialName, nameInCharacters=:nameInCharacters, gender=:gender, languageFirst=:parent1languageFirst, languageSecond=:parent1languageSecond, citizenship1=:citizenship1, nationalIDCardNumber=:nationalIDCardNumber, residencyStatus=:residencyStatus, visaExpiryDate=:visaExpiryDate, email=:email, phone1Type=:phone1Type, phone1CountryCode=:phone1CountryCode, phone1=:phone1, phone2Type=:phone2Type, phone2CountryCode=:phone2CountryCode, phone2=:phone2, profession=:profession, employer=:employer, fields=:parent1fields" ;
												$result=$connection2->prepare($sql);
												$result->execute($data);
											}
											catch(PDOException $e) { 
												$insertOK=false ;
												print "<div class='error'>" . $e->getMessage() . "</div>" ; 
											}
											if ($insertOK==true) {
												$failParent1=FALSE ;
								
												//Populate parent1 in informParent array
												if ($informParents=="Y") {
													$informParentsArray[0]["email"]=$row["parent1email"] ;
													$informParentsArray[0]["surname"]=$row["parent1surname"] ;
													$informParentsArray[0]["preferredName"]=$row["parent1preferredName"] ;
													$informParentsArray[0]["username"]=$username ;
													$informParentsArray[0]["password"]=$password ;
												}
											}
										}
									}
								}
								try {
									$sql="UNLOCK TABLES" ;
									$result=$connection2->query($sql);   
								}
								catch(PDOException $e) { 
									print "<div class='error'>" . $e->getMessage() . "</div>" ; 
								}
								
								if ($failParent1==TRUE) {
									print "<div class='error'>" ;
									print _("Parent 1 could not be created!") ;
									print "</div>" ;
								}
								else {
									print "<h4>" ;
									print _("Parent 1") ;
									print "</h4>" ;
									print "<ul>" ;
										print "<li><b>gibbonPersonID</b>: $gibbonPersonIDParent1</li>" ;
										print "<li><b>" . _('Name') . "</b>: " . formatName("", $row["parent1preferredName"], $row["parent1surname"], "Parent") . "</li>" ;
										print "<li><b>" . _('Email') . "</b>: " . $row["parent1email"] . "</li>" ;
										print "<li><b>" . _('Username') . "</b>: $username</li>" ;
										print "<li><b>" . _('Password') . "</b>: $password</li>" ;
									print "</ul>" ;
									
									//LINK PARENT 1 INTO FAMILY
									$failFamily=TRUE ;
									if ($gibbonFamilyID!="") {
										try {
											$dataFamily=array("gibbonFamilyID"=>$gibbonFamilyID); 
											$sqlFamily="SELECT * FROM gibbonFamily WHERE gibbonFamilyID=:gibbonFamilyID" ;
											$resultFamily=$connection2->prepare($sqlFamily);
											$resultFamily->execute($dataFamily);
										}
										catch(PDOException $e) { 
											print "<div class='error'>" . $e->getMessage() . "</div>" ; 
										}
										if ($resultFamily->rowCount()==1) {
											$rowFamily=$resultFamily->fetch() ;
											$familyName=$rowFamily["name"] ;
											if ($familyName!=""){
												$insertOK=true ;
												try {
													$data=array("gibbonPersonID"=>$gibbonPersonIDParent1, "gibbonFamilyID"=>$gibbonFamilyID, "contactCall"=>"Y", "contactSMS"=>"Y", "contactEmail"=>"Y", "contactMail"=>"Y"); 
													$sql="INSERT INTO gibbonFamilyAdult SET gibbonPersonID=:gibbonPersonID, gibbonFamilyID=:gibbonFamilyID, contactPriority=1, contactCall=:contactCall, contactSMS=:contactSMS, contactEmail=:contactEmail, contactMail=:contactMail" ;	
													$result=$connection2->prepare($sql);
													$result->execute($data);
												}
												catch(PDOException $e) { 
													$insertOK=false ;
													print "<div class='error'>" . $e->getMessage() . "</div>" ; 
												}
												if ($insertOK==true) {
													$failFamily=FALSE ;
												}
											}
										}
										
										if ($failFamily==TRUE) {
											print "<div class='warning'>" ;
											print _("Parent 1 could not be linked to family!") ;
											print "</div>" ;
										}
										
										//Set parent relationship
										try {
											$data=array("gibbonFamilyID"=>$gibbonFamilyID, "gibbonPersonID1"=>$gibbonPersonIDParent1, "gibbonPersonID2"=>$gibbonPersonID, "relationship"=>$row["parent1relationship"]); 
											$sql="INSERT INTO gibbonFamilyRelationship SET gibbonFamilyID=:gibbonFamilyID, gibbonPersonID1=:gibbonPersonID1, gibbonPersonID2=:gibbonPersonID2, relationship=:relationship" ;
											$result=$connection2->prepare($sql);
											$result->execute($data);
										}
										catch(PDOException $e) { 
											print "<div class='error'>" . $e->getMessage() . "</div>" ;
										}
									}
								}
							}
							
							//CREATE PARENT 2
							if ($row["parent2preferredName"]!="" AND $row["parent2surname"]!="") {
								$failParent2=TRUE ;
								$lock=true ;
								try {
									$sql="LOCK TABLES gibbonPerson WRITE" ;
									$result=$connection2->query($sql);   
								}
								catch(PDOException $e) { 
									$lock=false ;
									print "<div class='error'>" . $e->getMessage() . "</div>" ; 
								}
								if ($lock==true) {
									$gotAI=true ;
									try {
										$sqlAI="SHOW TABLE STATUS LIKE 'gibbonPerson'";
										$resultAI=$connection2->query($sqlAI);   
									}
									catch(PDOException $e) { 
										$gotAI=false ;
										print "<div class='error'>" . $e->getMessage() . "</div>" ; 
									}
				
									if ($gotAI==true) {
										$rowAI=$resultAI->fetch();
										$gibbonPersonIDParent2=str_pad($rowAI['Auto_increment'], 10, "0", STR_PAD_LEFT) ;
									
										//Set username & password
										$username=substr(str_replace(" ", "", preg_replace("/[^A-Za-z ]/", '', strtolower(substr($row["parent2preferredName"],0,1) . $row["parent2surname"]))), 0, 12);
										$usernameBase=$username ;
										$count=1 ;
										$continueLoop=TRUE ;
										while ($continueLoop==TRUE AND $count<10000) {
											$gotUsername=true ;
											try {
												$dataUsername=array("username"=>$username); 
												$sqlUsername="SELECT * FROM gibbonPerson WHERE username=:username" ;
												$resultUsername=$connection2->prepare($sqlUsername);
												$resultUsername->execute($dataUsername);
											}
											catch(PDOException $e) { 
												$gotUsername=false ;
												print "<div class='error'>" . $e->getMessage() . "</div>" ; 
											}
											
											if ($resultUsername->rowCount()==0 AND $gotUsername==true) {
												$continueLoop=FALSE ;
											}
											else {
												$username=$usernameBase . $count ;
											}
											$count++ ;
										}
										
										$password=randomPassword(8);
										$salt=getSalt() ;
										$passwordStrong=hash("sha256", $salt.$password) ;
										
										if ($continueLoop==FALSE) {
											$insertOK=true ;
											try {
												$data=array("username"=>$username, "passwordStrong"=>$passwordStrong, "passwordStrongSalt"=>$salt, "title"=>$row["parent2title"], "surname"=>$row["parent2surname"], "firstName"=>$row["parent2firstName"], "preferredName"=>$row["parent2preferredName"], "officialName"=>$row["parent2officialName"], "nameInCharacters"=>$row["parent2nameInCharacters"], "gender"=>$row["parent2gender"], "parent2languageFirst"=>$row["parent2languageFirst"], "parent2languageSecond"=>$row["parent2languageSecond"], "citizenship1"=>$row["parent2citizenship1"], "nationalIDCardNumber"=>$row["parent2nationalIDCardNumber"], "residencyStatus"=>$row["parent2residencyStatus"], "visaExpiryDate"=>$row["parent2visaExpiryDate"], "email"=>$row["parent2email"], "phone1Type"=>$row["parent2phone1Type"],"phone1CountryCode"=>$row["parent2phone1CountryCode"],"phone1"=>$row["parent2phone1"],"phone2Type"=>$row["parent2phone2Type"],"phone2CountryCode"=>$row["parent2phone2CountryCode"],"phone2"=>$row["parent2phone2"], "profession"=>$row["parent2profession"], "employer"=>$row["parent2employer"], "parent2fields"=>$row["parent2fields"]); 
												$sql="INSERT INTO gibbonPerson SET username=:username, password='', passwordStrong=:passwordStrong, passwordStrongSalt=:passwordStrongSalt, gibbonRoleIDPrimary='004', gibbonRoleIDAll='004', status='Full', title=:title, surname=:surname, firstName=:firstName, preferredName=:preferredName, officialName=:officialName, nameInCharacters=:nameInCharacters, gender=:gender, languageFirst=:parent2languageFirst, languageSecond=:parent2languageSecond, citizenship1=:citizenship1, nationalIDCardNumber=:nationalIDCardNumber, residencyStatus=:residencyStatus, visaExpiryDate=:visaExpiryDate, email=:email, phone1Type=:phone1Type, phone1CountryCode=:phone1CountryCode, phone1=:phone1, phone2Type=:phone2Type, phone2CountryCode=:phone2CountryCode, phone2=:phone2, profession=:profession, employer=:employer, fields=:parent2fields" ;
												$result=$connection2->prepare($sql);
												$result->execute($data);
											}
											catch(PDOException $e) { 
												$insertOK=false ;
												print "<div class='error'>" . $e->getMessage() . "</div>" ; 
											}
											if ($insertOK==true) {
												$failParent2=FALSE ;
								
												//Populate parent2 in informParents array
												if ($informParents=="Y") {
													$informParentsArray[1]["email"]=$row["parent2email"] ;
													$informParentsArray[1]["surname"]=$row["parent2surname"] ;
													$informParentsArray[1]["preferredName"]=$row["parent2preferredName"] ;
													$informParentsArray[1]["username"]=$username ;
													$informParentsArray[1]["password"]=$password ;
												}
											}
										}
									}
								}
								try {
									$sql="UNLOCK TABLES" ;
									$result=$connection2->query($sql);   
								}
								catch(PDOException $e) { 
									print "<div class='error'>" . $e->getMessage() . "</div>" ; 
								}

								if ($failParent2==TRUE) {
									print "<div class='error'>" ;
									print _("Parent 2 could not be created!") ;
									print "</div>" ;
								}
								else {
									print "<h4>" ;
									print _("Parent 2") ;
									print "</h4>" ;
									print "<ul>" ;
										print "<li><b>gibbonPersonID</b>: $gibbonPersonIDParent2</li>" ;
										print "<li><b>" . _('Name') . "</b>: " . formatName("", $row["parent2preferredName"], $row["parent2surname"], "Parent") . "</li>" ;
										print "<li><b>" . _('Email') . "</b>: " . $row["parent2email"] . "</li>" ;
										print "<li><b>" . _('Username') . "</b>: $username</li>" ;
										print "<li><b>" . _('Password') . "</b>: $password</li>" ;
									print "</ul>" ;
									
									
									//LINK PARENT 2 INTO FAMILY
									$failFamily=TRUE ;
									if ($gibbonFamilyID!="") {
										try {
											$dataFamily=array("gibbonFamilyID"=>$gibbonFamilyID); 
											$sqlFamily="SELECT * FROM gibbonFamily WHERE gibbonFamilyID=:gibbonFamilyID" ;
											$resultFamily=$connection2->prepare($sqlFamily);
											$resultFamily->execute($dataFamily);
										}
										catch(PDOException $e) { 
											print "<div class='error'>" . $e->getMessage() . "</div>" ; 
										}
										if ($resultFamily->rowCount()==1) {
											$rowFamily=$resultFamily->fetch() ;
											$familyName=$rowFamily["name"] ;
											if ($familyName!=""){
												$insertOK=true ;
												try {
													$data=array("gibbonPersonID"=>$gibbonPersonIDParent2, "gibbonFamilyID"=>$gibbonFamilyID, "contactCall"=>"Y", "contactSMS"=>"Y", "contactEmail"=>"Y", "contactMail"=>"Y"); 
													$sql="INSERT INTO gibbonFamilyAdult SET gibbonPersonID=:gibbonPersonID, gibbonFamilyID=:gibbonFamilyID, contactPriority=2, contactCall=:contactCall, contactSMS=:contactSMS, contactEmail=:contactEmail, contactMail=:contactMail" ;
													$result=$connection2->prepare($sql);
													$result->execute($data);
												}
												catch(PDOException $e) { 
													$insertOK=false ;
													print "<div class='error'>" . $e->getMessage() . "</div>" ; 
												}
												if ($insertOK==true) {
													$failFamily=FALSE ;
												}
											}
										}
										
										if ($failFamily==TRUE) {
											print "<div class='warning'>" ;
											print _("Parent 2 could not be linked to family!") ;
											print "</div>" ;
										}
										
										//Set parent relationship
										try {
											$data=array("gibbonFamilyID"=>$gibbonFamilyID, "gibbonPersonID1"=>$gibbonPersonIDParent2, "gibbonPersonID2"=>$gibbonPersonID, "relationship"=>$row["parent2relationship"]); 
											$sql="INSERT INTO gibbonFamilyRelationship SET gibbonFamilyID=:gibbonFamilyID, gibbonPersonID1=:gibbonPersonID1, gibbonPersonID2=:gibbonPersonID2, relationship=:relationship" ;
											$result=$connection2->prepare($sql);
											$result->execute($data);
										}
										catch(PDOException $e) { 
											print "<div class='error'>" . $e->getMessage() . "</div>" ;
										}
									}
								}
							}
						}
					}
					
					//SEND INFORM STUDENT EMAIL
					if ($informStudent=="Y") {
						print "<h4>" ;
						print _("Student Welcome Email") ;
						print "</h4>" ;
						$notificationStudentMessage=getSettingByScope( $connection2, "Application Form", "notificationStudentMessage" ) ;
						foreach ($informStudentArray AS $informStudentEntry) {
							if ($informStudentEntry["email"]!="" AND $informStudentEntry["surname"]!="" AND $informStudentEntry["preferredName"]!="" AND $informStudentEntry["username"]!="" AND $informStudentEntry["password"]) {
								$to=$informStudentEntry["email"];
								$subject=sprintf(_('Welcome to %1$s at %2$s'), $_SESSION[$guid]["systemName"], $_SESSION[$guid]["organisationNameShort"]) ;
								if ($notificationStudentMessage!="" ) {
									$body=sprintf(_('Dear %1$s,\n\nWelcome to %2$s, %3$s\'s system for managing school information. You can access the system by going to %4$s and logging in with your new username (%5$s) and password (%6$s).\n\nIn order to maintain the security of your data, we highly recommend you change your password to something easy to remember but hard to guess. This can be done by using the Preferences page after logging in (top-right of the screen).\n\n'), formatName("", $informStudentEntry["preferredName"], $informStudentEntry["surname"], "Student"), $_SESSION[$guid]["systemName"], $_SESSION[$guid]["organisationNameShort"], $_SESSION[$guid]["absoluteURL"], $informStudentEntry["username"], $informStudentEntry["password"]) . $notificationParentsMessage . sprintf(_('\n\nPlease feel free to reply to this email should you have any questions.\n\n%1$s,\n\n%2$s Administrator'), $_SESSION[$guid]["organisationAdministratorName"], $_SESSION[$guid]["systemName"]);
								}
								else {
									$body="Dear " . formatName("", $informStudentEntry["preferredName"], $informStudentEntry["surname"], "Student") . ",\n\nWelcome to " . $_SESSION[$guid]["systemName"] . ", " . $_SESSION[$guid]["organisationNameShort"] . "'s system for managing school information. You can access the system by going to " . $_SESSION[$guid]["absoluteURL"] . " and logging in with your new username (" . $informStudentEntry["username"] . ") and password (" . $informStudentEntry["password"] . ").\n\nIn order to maintain the security of your data, we highly recommend you change your password to something easy to remember but hard to guess. This can be done by using the Preferences page after logging in (top-right of the screen).\n\nPlease feel free to reply to this email should you have any questions.\n\n" . $_SESSION[$guid]["organisationAdministratorName"] . ",\n\n" . $_SESSION[$guid]["systemName"] . " Administrator";
								}
								$headers="From: " . $_SESSION[$guid]["organisationAdministratorEmail"] ;

								if (mail($to, $subject, $body, $headers)) {
									print "<div class='success'>" ;
										print _("A welcome email was successfully sent to") . " " . formatName("", $informStudentEntry["preferredName"], $informStudentEntry["surname"], "Student") . "." ;
									print "</div>" ;
								}
								else {
									print "<div class='error'>" ;
										print _("A welcome email could not be sent to") . " " . formatName("", $informStudentEntry["preferredName"], $informStudentEntry["surname"], "Student") . "." ;
									print "</div>" ;
								}
							}
						}	
					}
					
					//SEND INFORM PARENTS EMAIL
					if ($informParents=="Y") {
						print "<h4>" ;
						print "Parent Welcome Email" ;
						print "</h4>" ;
						$notificationParentsMessage=getSettingByScope( $connection2, "Application Form", "notificationParentsMessage" ) ;
						foreach ($informParentsArray AS $informParentsEntry) {
							if ($informParentsEntry["email"]!="" AND $informParentsEntry["surname"]!="" AND $informParentsEntry["preferredName"]!="" AND $informParentsEntry["username"]!="" AND $informParentsEntry["password"]) {
								$to=$informParentsEntry["email"];
								$subject=sprintf(_('Welcome to %1$s at %2$s'), $_SESSION[$guid]["systemName"], $_SESSION[$guid]["organisationNameShort"]) ;
								if ($notificationParentsMessage!="" ) {
									$body=sprintf(_('Dear %1$s,\n\nWelcome to %2$s, %3$s\'s system for managing school information. You can access the system by going to %4$s and logging in with your new username (%5$s) and password (%6$s). You can learn more about using %7$s on the official support website (https://gibbonedu.org/support/parents).\n\nIn order to maintain the security of your data, we highly recommend you change your password to something easy to remember but hard to guess. This can be done by using the Preferences page after logging in (top-right of the screen).\n\n'), formatName("", $informParentsEntry["preferredName"], $informParentsEntry["surname"], "Student"), $_SESSION[$guid]["systemName"], $_SESSION[$guid]["organisationNameShort"], $_SESSION[$guid]["absoluteURL"], $informParentsEntry["username"], $informParentsEntry["password"], $_SESSION[$guid]["systemName"]) . $notificationParentsMessage . sprintf(_('\n\nPlease feel free to reply to this email should you have any questions.\n\n%1$s,\n\n%2$s Administrator'), $_SESSION[$guid]["organisationAdministratorName"], $_SESSION[$guid]["systemName"]);
								}
								else {
									$body=sprintf(_('Dear %1$s,\n\nWelcome to %2$s, %3$s\'s system for managing school information. You can access the system by going to %4$s and logging in with your new username (%5$s) and password (%6$s). You can learn more about using %7$s on the official support website (https://gibbonedu.org/support/parents).\n\nIn order to maintain the security of your data, we highly recommend you change your password to something easy to remember but hard to guess. This can be done by using the Preferences page after logging in (top-right of the screen).\n\n'), formatName("", $informParentsEntry["preferredName"], $informParentsEntry["surname"], "Student"), $_SESSION[$guid]["systemName"], $_SESSION[$guid]["organisationNameShort"], $_SESSION[$guid]["absoluteURL"], $informParentsEntry["username"], $informParentsEntry["password"], $_SESSION[$guid]["systemName"]) . sprintf(_('\n\nPlease feel free to reply to this email should you have any questions.\n\n%1$s,\n\n%2$s Administrator'), $_SESSION[$guid]["organisationAdministratorName"], $_SESSION[$guid]["systemName"]);
								}
								$headers="From: " . $_SESSION[$guid]["organisationAdministratorEmail"] ;

								if (mail($to, $subject, $body, $headers)) {
									print "<div class='success'>" ;
										print _("A welcome email was successfully sent to") . " " . formatName("", $informParentsEntry["preferredName"], $informParentsEntry["surname"], "Student") . "." ;
									print "</div>" ;
								}
								else {
									print "<div class='error'>" ;
										print _("A welcome email could not be sent to") . " " . formatName("", $informParentsEntry["preferredName"], $informParentsEntry["surname"], "Student") . "." ;
									print "</div>" ;
								}
							}
						}	
					}
					//SET STATUS TO ACCEPTED
					$failStatus=false ;
					try {
						$data=array("gibbonApplicationFormID"=>$gibbonApplicationFormID); 
						$sql="UPDATE gibbonApplicationForm SET status='Accepted' WHERE gibbonApplicationFormID=:gibbonApplicationFormID" ;
						$result=$connection2->prepare($sql);
						$result->execute($data);
					}
					catch(PDOException $e) { 
						$failStatus=true ;
						print "<div class='error'>" . $e->getMessage() . "</div>" ; 
					}

					
					if ($failStatus==true) {
						print "<div class='error'>" ;
						print _("Student status could not be updated: student is in the system, but acceptance has failed.") ;
						print "</div>" ;
					}
					else {
						print "<h4>" ;
						print _("Application Status") ;
						print "</h4>" ;
						print "<ul>" ;
							print "<li><b>" . _('Status') . "</b>: ".  _('Accepted') . "</li>" ;
						print "</ul>" ;
						
						print "<div class='success' style='margin-bottom: 20px'>" ;
						print _("Applicant has been successfully accepted into ICHK.") . " <i><u>" . _('You may wish to now do the following:') . "</u></i><br/>" ;
						print "<ol>" ;
							print "<li>" . _('Enrol the student in the relevant academic year.') . "</li>" ;
							print "<li>" . _('Create a medical record for the student.') . "</li>" ;
							print "<li>" . _('Create an individual needs record for the student.') . "</li>" ;
							print "<li>" . _('Create a note of the student\'s scholarship information outside of Gibbon.') . "</li>" ;
							print "<li>" . _('Create a timetable for the student.') . "</li>" ;
							print "<li>" . _('Inform the student and their parents of their Gibbon login details (if this was not done automatically).') . "</li>" ;
						print "</ol>" ;
						print "</div>" ;
					}
				}
			}
		}
	}
}
?>