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

include "../../functions.php" ;
include "../../config.php" ;

//New PDO DB connection
try {
  	$connection2=new PDO("mysql:host=$databaseServer;dbname=$databaseName;charset=utf8", $databaseUsername, $databasePassword);
	$connection2->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$connection2->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
}
catch(PDOException $e) {
  echo $e->getMessage();
}

@session_start() ;

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]["timezone"]);

$gibbonApplicationFormID=$_POST["gibbonApplicationFormID"] ;
$gibbonSchoolYearID=$_POST["gibbonSchoolYearID"] ;
$search=$_GET["search"] ;
$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/applicationForm_manage_edit.php&gibbonApplicationFormID=$gibbonApplicationFormID&gibbonSchoolYearID=$gibbonSchoolYearID&search=$search" ;

if (isActionAccessible($guid, $connection2, "/modules/User Admin/applicationForm_manage_edit.php")==FALSE) {
	//Fail 0
	$URL=$URL . "&updateReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	//Proceed!
	//Check if school year specified
	
	if ($gibbonApplicationFormID=="" OR $gibbonSchoolYearID=="") {
		//Fail1
		$URL=$URL . "&updateReturn=fail1" ;
		header("Location: {$URL}");
	}
	else {
		try {
			$data=array("gibbonApplicationFormID"=>$gibbonApplicationFormID); 
			$sql="SELECT * FROM gibbonApplicationForm WHERE gibbonApplicationFormID=:gibbonApplicationFormID" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			//Fail2
			$URL=$URL . "&updateReturn=fail2" ;
			header("Location: {$URL}");
			break ;
		}

		if ($result->rowCount()!=1) {
			//Fail 2
			$URL=$URL . "&updateReturn=fail2" ;
			header("Location: {$URL}");
		}
		else {
			//Proceed!
			//Get student fields
			$priority=$_POST["priority"] ;
			$status=$_POST["status"] ;
			$milestones="" ;
			$milestonesMaster=explode(",", getSettingByScope($connection2, "Application Form", "milestones")) ;
			foreach ($milestonesMaster as $milestoneMaster) {
				if (isset($_POST["milestone_" . preg_replace('/\s+/', '', $milestoneMaster)])) {
					if ($_POST["milestone_" . preg_replace('/\s+/', '', $milestoneMaster)]=="on") {
						$milestones.=trim($milestoneMaster) . "," ;
					}
				}
			}
			$milestones=substr($milestones,0,-1) ;
			$dateStart=NULL ;
			if ($_POST["dateStart"]!="") {
				$dateStart=dateConvert($_POST["dateStart"]) ;
			}
			$gibbonRollGroupID=NULL ;
			if ($_POST["gibbonRollGroupID"]!="") {
				$gibbonRollGroupID=$_POST["gibbonRollGroupID"] ;
			}
			$paymentMade="N" ;
			if (isset($_POST["paymentMade"])) {
				$paymentMade=$_POST["paymentMade"] ;
			}
			$notes=$_POST["notes"] ;
			$surname=$_POST["surname"] ;
			$firstName=$_POST["firstName"] ;
			$preferredName=$_POST["preferredName"] ;
			$officialName=$_POST["officialName"] ;
			$nameInCharacters=$_POST["nameInCharacters"] ;
			$gender=$_POST["gender"] ;
			$dob=$_POST["dob"] ;
			if ($dob=="") {
				$dob=NULL ;
			}
			else {
				$dob=dateConvert($dob) ;
			}
			$languageHome=$_POST["languageHome"] ;
			$languageFirst=$_POST["languageFirst"] ;
			$languageSecond=$_POST["languageSecond"] ;
			$languageThird=$_POST["languageThird"] ;
			$countryOfBirth=$_POST["countryOfBirth"] ;
			$citizenship1=$_POST["citizenship1"] ;
			$citizenship1Passport=$_POST["citizenship1Passport"] ;
			$nationalIDCardNumber=$_POST["nationalIDCardNumber"] ;
			$residencyStatus=$_POST["residencyStatus"] ;
			$visaExpiryDate=$_POST["visaExpiryDate"] ;
			if ($visaExpiryDate=="") {
				$visaExpiryDate=NULL ;
			}
			else {
				$visaExpiryDate=dateConvert($visaExpiryDate) ;
			}
			$email=$_POST["email"] ;
			$phone1Type=$_POST["phone1Type"] ; 
			$phone1CountryCode=$_POST["phone1CountryCode"] ; 
			$phone1=$_POST["phone1"] ; 
			$phone2Type=$_POST["phone2Type"] ; 
			$phone2CountryCode=$_POST["phone2CountryCode"] ; 
			$phone2=$_POST["phone2"] ; 
			$medicalInformation=$_POST["medicalInformation"] ;
			$developmentInformation=$_POST["developmentInformation"] ;
			$gibbonSchoolYearIDEntry=$_POST["gibbonSchoolYearIDEntry"] ;
			$gibbonYearGroupIDEntry=$_POST["gibbonYearGroupIDEntry"] ;
			$dayType=NULL ;
			if (isset($_POST["dayType"])) {
				$dayType=$_POST["dayType"] ;
			}
			$schoolName1=$_POST["schoolName1"] ;
			$schoolAddress1=$_POST["schoolAddress1"] ;
			$schoolGrades1=$_POST["schoolGrades1"] ;
			$schoolGrades1=$_POST["schoolGrades1"] ;
			$schoolDate1=$_POST["schoolDate1"] ;
			if ($schoolDate1=="") {
				$schoolDate1=NULL ;
			}
			else {
				$schoolDate1=dateConvert($schoolDate1);
			}
			$schoolName2=$_POST["schoolName2"] ;
			$schoolAddress2=$_POST["schoolAddress2"] ;
			$schoolGrades2=$_POST["schoolGrades2"] ;
			$schoolGrades2=$_POST["schoolGrades2"] ;
			$schoolDate2=$_POST["schoolDate2"] ;
			if ($schoolDate2=="") {
				$schoolDate2=NULL ;
			}
			else {
				$schoolDate2=dateConvert($schoolDate2) ;
			}
			
			//Get family information
			$gibbonFamily=$_POST["gibbonFamily"] ;
			if ($gibbonFamily=="TRUE") {
				$gibbonFamilyID=$_POST["gibbonFamilyID"] ;
				$homeAddress="" ; 	
				$homeAddressDistrict="" ; 	
				$homeAddressCountry="" ; 	
			}
			else {
				$gibbonFamilyID=NULL ;
				$homeAddress=$_POST["homeAddress"] ; 	
				$homeAddressDistrict=$_POST["homeAddressDistrict"] ; 	
				$homeAddressCountry=$_POST["homeAddressCountry"] ; 	
				$parent1gibbonPersonID=NULL ;
				if (isset($_POST["parent1gibbonPersonID"])) {
					$parent1gibbonPersonID=$_POST["parent1gibbonPersonID"] ;
				}
				if ($parent1gibbonPersonID=="") {
					$parent1gibbonPersonID=NULL ;
				}
				else {
					$parent1gibbonPersonID=$parent1gibbonPersonID;
				}
				$parent1title=$_POST["parent1title"] ;
				$parent1surname=$_POST["parent1surname"] ;
				$parent1firstName=$_POST["parent1firstName"] ;
				$parent1preferredName=$_POST["parent1preferredName"] ;
				$parent1officialName=$_POST["parent1officialName"] ;
				$parent1nameInCharacters=$_POST["parent1nameInCharacters"] ;
				$parent1gender=$_POST["parent1gender"] ;
				$parent1relationship=$_POST["parent1relationship"] ;
				$parent1languageFirst=$_POST["parent1languageFirst"] ;
				$parent1languageSecond=$_POST["parent1languageSecond"] ;
				$parent1citizenship1=$_POST["parent1citizenship1"] ;
				$parent1nationalIDCardNumber=$_POST["parent1nationalIDCardNumber"] ;
				$parent1residencyStatus=$_POST["parent1residencyStatus"] ;
				$parent1visaExpiryDate=$_POST["parent1visaExpiryDate"] ;
				if ($parent1visaExpiryDate=="") {
					$parent1visaExpiryDate=NULL ;
				}
				else {
					$parent1visaExpiryDate=dateConvert($parent1visaExpiryDate) ;
				}
				$parent1email=$_POST["parent1email"] ;
				$parent1phone1Type=$_POST["parent1phone1Type"] ; 
				$parent1phone1CountryCode=$_POST["parent1phone1CountryCode"] ; 
				$parent1phone1=$_POST["parent1phone1"] ; 
				$parent1phone2Type=$_POST["parent1phone2Type"] ; 
				$parent1phone2CountryCode=$_POST["parent1phone2CountryCode"] ; 
				$parent1phone2=$_POST["parent1phone2"] ; 
				$parent1profession=$_POST["parent1profession"] ;
				$parent1employer=$_POST["parent1employer"] ;
				$parent2title=$_POST["parent2title"] ;
				$parent2surname=$_POST["parent2surname"] ;
				$parent2firstName=$_POST["parent2firstName"] ;
				$parent2preferredName=$_POST["parent2preferredName"] ;
				$parent2officialName=$_POST["parent2officialName"] ;
				$parent2nameInCharacters=$_POST["parent2nameInCharacters"] ;
				$parent2gender=$_POST["parent2gender"] ;
				$parent2relationship=$_POST["parent2relationship"] ;
				$parent2languageFirst=$_POST["parent2languageFirst"] ;
				$parent2languageSecond=$_POST["parent2languageSecond"] ;
				$parent2citizenship1=$_POST["parent2citizenship1"] ;
				$parent2nationalIDCardNumber=$_POST["parent2nationalIDCardNumber"] ;
				$parent2residencyStatus=$_POST["parent2residencyStatus"] ;
				$parent2visaExpiryDate=$_POST["parent2visaExpiryDate"] ;
				if ($parent2visaExpiryDate=="") {
					$parent2visaExpiryDate=NULL ;
				}
				else {
					$parent2visaExpiryDate=dateConvert($parent2visaExpiryDate) ;
				}
				$parent2email=$_POST["parent2email"] ;
				$parent2phone1Type=$_POST["parent2phone1Type"] ; 
				$parent2phone1CountryCode=$_POST["parent2phone1CountryCode"] ; 
				$parent2phone1=$_POST["parent2phone1"] ; 
				$parent2phone2Type=$_POST["parent2phone2Type"] ; 
				$parent2phone2CountryCode=$_POST["parent2phone2CountryCode"] ; 
				$parent2phone2=$_POST["parent2phone2"] ; 
				$parent2profession=$_POST["parent2profession"] ;
				$parent2employer=$_POST["parent2employer"] ;
			}
			
			//Get sibling information
			$siblingName1=$_POST["siblingName1"] ;
			$siblingDOB1=$_POST["siblingDOB1"] ;
			if ($siblingDOB1=="") {
				$siblingDOB1=NULL ;
			}
			else {
				$siblingDOB1=dateConvert($siblingDOB1) ;
			}
			$siblingSchool1=$_POST["siblingSchool1"] ;
			$siblingSchoolJoiningDate1=$_POST["siblingSchoolJoiningDate1"] ;
			if ($siblingSchoolJoiningDate1=="") {
				$siblingSchoolJoiningDate1=NULL ;
			}
			else {
				$siblingSchoolJoiningDate1=dateConvert($siblingSchoolJoiningDate1) ;
			}
			$siblingName2=$_POST["siblingName2"] ;
			$siblingDOB2=$_POST["siblingDOB2"] ;
			if ($siblingDOB2=="") {
				$siblingDOB2=NULL ;
			}
			else {
				$siblingDOB2=dateConvert($siblingDOB2);
			}
			$siblingSchool2=$_POST["siblingSchool2"] ;
			$siblingSchoolJoiningDate2=$_POST["siblingSchoolJoiningDate2"] ;
			if ($siblingSchoolJoiningDate2=="") {
				$siblingSchoolJoiningDate2=NULL ;
			}
			else {
				$siblingSchoolJoiningDate2=dateConvert($siblingSchoolJoiningDate2) ;
			}
			$siblingName3=$_POST["siblingName3"] ;
			$siblingDOB3=$_POST["siblingDOB3"] ;
			if ($siblingDOB3=="") {
				$siblingDOB3=NULL ;
			}
			else {
				$siblingDOB3=dateConvert($siblingDOB3);
			}
			$siblingSchool3=$_POST["siblingSchool3"] ;
			$siblingSchoolJoiningDate3=$_POST["siblingSchoolJoiningDate3"] ;
			if ($siblingSchoolJoiningDate3=="") {
				$siblingSchoolJoiningDate3=NULL ;
			}
			else {
				$siblingSchoolJoiningDate3=dateConvert($siblingSchoolJoiningDate3);
			}
			
			//Get other fields
			$languageChoice="" ;
			if (isset($_POST["languageChoice"])) {
				$languageChoice=$_POST["languageChoice"] ;
			}
			$languageChoiceExperience="" ;
			if (isset($_POST["languageChoiceExperience"])) {
				$languageChoiceExperience=$_POST["languageChoiceExperience"] ;
			}
			$scholarshipInterest=$_POST["scholarshipInterest"] ;
			$scholarshipRequired=$_POST["scholarshipRequired"] ;
			$payment=$_POST["payment"] ;
			if ($payment=="Company") {
				$companyName=$_POST["companyName"] ;
				$companyContact=$_POST["companyContact"] ;
				$companyAddress=$_POST["companyAddress"] ;
				$companyEmail=$_POST["companyEmail"] ;
				$companyPhone=$_POST["companyPhone"] ;
				$companyAll=$_POST["companyAll"] ;
				if ($companyAll=="N") {
					$gibbonFinanceFeeCategoryIDList=="" ;
					$gibbonFinanceFeeCategoryIDArray=$_POST["gibbonFinanceFeeCategoryIDList"] ;
					if (count($gibbonFinanceFeeCategoryIDArray)>0) {
						foreach ($gibbonFinanceFeeCategoryIDArray AS $gibbonFinanceFeeCategoryID) {
							$gibbonFinanceFeeCategoryIDList.=$gibbonFinanceFeeCategoryID . "," ;
						}
						$gibbonFinanceFeeCategoryIDList=substr($gibbonFinanceFeeCategoryIDList,0,-1) ;
					}
				}
			}
			else {
				$companyName="" ;
				$companyContact="" ;
				$companyAddress="" ;
				$companyEmail="" ;
				$companyPhone="" ;
				$companyAll=NULL ;
				$gibbonFinanceFeeCategoryIDList=NULL ;
			}
			$howDidYouHear=$_POST["howDidYouHear"] ;
			$howDidYouHearMore=$_POST["howDidYouHearMore"] ;
			$agreement="N" ;
			if (isset($_POST["agreement"] )) {
				$agreement=$_POST["agreement"] ;
				if ($agreement=="on") {
					$agreement="Y" ;
				}
			}
			$privacy=NULL ;
			if (isset($_POST["privacyOptions"])) {
				$privacy="" ;			
				$privacyOptions=$_POST["privacyOptions"] ;
				foreach ($privacyOptions AS $privacyOption) {
					if ($privacyOption!="") {
						$privacy.=$privacyOption . ", " ;
					}
				}
				if ($privacy!="") {
					$privacy=substr($privacy,0,-2) ;
				}
				else {
					$privacy=NULL ;
				}
			}
			
			//Validate Inputs
			$familyFail=FALSE ;
			$gibbonFamily ;
			if ($gibbonFamily=="TRUE") {
				if ($gibbonFamilyID=="") {
					$familyFail=TRUE ;
				}
			}
			else {
				if ($homeAddress=="" OR $homeAddressDistrict=="" OR $homeAddressCountry=="") {
					$familyFail=TRUE ;
				}
				if ($parent1gibbonPersonID==NULL) {
					if ($parent1title=="" OR $parent1surname=="" OR $parent1firstName=="" OR $parent1preferredName=="" OR $parent1officialName=="" OR $parent1gender=="" OR $parent1relationship=="") {
						$familyFail=TRUE ;
					}
				}
			}
			if ($priority=="" OR $surname=="" OR $firstName=="" OR $preferredName=="" OR $officialName=="" OR $gender=="" OR $dob==NULL OR $gibbonSchoolYearIDEntry=="" OR $gibbonYearGroupIDEntry=="" OR $familyFail) {
				//Fail 3
				$URL=$URL . "&updateReturn=fail3" ;
				header("Location: {$URL}");
			}
			else {
				//Write to database
				try {
					$data=array("priority"=>$priority, "status"=>$status, "milestones"=>$milestones, "dateStart"=>$dateStart, "gibbonRollGroupID"=>$gibbonRollGroupID, "paymentMade"=>$paymentMade, "notes"=>$notes, "surname"=>$surname, "firstName"=>$firstName, "preferredName"=>$preferredName, "officialName"=>$officialName, "nameInCharacters"=>$nameInCharacters, "gender"=>$gender, "dob"=>$dob, "languageHome"=>$languageHome, "languageFirst"=>$languageFirst, "languageSecond"=>$languageSecond, "languageThird"=>$languageThird, "countryOfBirth"=>$countryOfBirth, "citizenship1"=>$citizenship1, "citizenship1Passport"=>$citizenship1Passport, "nationalIDCardNumber"=>$nationalIDCardNumber, "residencyStatus"=>$residencyStatus, "visaExpiryDate"=>$visaExpiryDate, "email"=>$email, "homeAddress"=>$homeAddress, "homeAddressDistrict"=>$homeAddressDistrict, "homeAddressCountry"=>$homeAddressCountry, "phone1Type"=>$phone1Type, "phone1CountryCode"=>$phone1CountryCode, "phone1"=>$phone1, "phone2Type"=>$phone2Type, "phone2CountryCode"=>$phone2CountryCode, "phone2"=>$phone2, "medicalInformation"=>$medicalInformation, "developmentInformation"=>$developmentInformation, "gibbonSchoolYearIDEntry"=>$gibbonSchoolYearIDEntry, "gibbonYearGroupIDEntry"=>$gibbonYearGroupIDEntry, "dayType"=>$dayType, "schoolName1"=>$schoolName1, "schoolAddress1"=>$schoolAddress1, "schoolGrades1"=>$schoolGrades1, "schoolDate1"=>$schoolDate1, "schoolName2"=>$schoolName2, "schoolAddress2"=>$schoolAddress2, "schoolGrades2"=>$schoolGrades2, "schoolDate2"=>$schoolDate2, "gibbonFamilyID"=>$gibbonFamilyID, "parent1gibbonPersonID"=>$parent1gibbonPersonID, "parent1title"=>$parent1title, "parent1surname"=>$parent1surname, "parent1firstName"=>$parent1firstName, "parent1preferredName"=>$parent1preferredName, "parent1officialName"=>$parent1officialName, "parent1nameInCharacters"=>$parent1nameInCharacters, "parent1gender"=>$parent1gender, "parent1relationship"=>$parent1relationship, "parent1languageFirst"=>$parent1languageFirst, "parent1languageSecond"=>$parent1languageSecond, "parent1citizenship1"=>$parent1citizenship1, "parent1nationalIDCardNumber"=>$parent1nationalIDCardNumber, "parent1residencyStatus"=>$parent1residencyStatus, "parent1visaExpiryDate"=>$parent1visaExpiryDate, "parent1email"=>$parent1email, "parent1phone1Type"=>$parent1phone1Type, "parent1phone1CountryCode"=>$parent1phone1CountryCode, "parent1phone1"=>$parent1phone1, "parent1phone2Type"=>$parent1phone2Type, "parent1phone2CountryCode"=>$parent1phone2CountryCode, "parent1phone2"=>$parent1phone2, "parent1profession"=>$parent1profession, "parent1employer"=>$parent1employer, "parent2title"=>$parent2title, "parent2surname"=>$parent2surname, "parent2firstName"=>$parent2firstName, "parent2preferredName"=>$parent2preferredName, "parent2officialName"=>$parent2officialName, "parent2nameInCharacters"=>$parent2nameInCharacters, "parent2gender"=>$parent2gender, "parent2relationship"=>$parent2relationship, "parent2languageFirst"=>$parent2languageFirst, "parent2languageSecond"=>$parent2languageSecond, "parent2citizenship1"=>$parent2citizenship1, "parent2nationalIDCardNumber"=>$parent2nationalIDCardNumber, "parent2residencyStatus"=>$parent2residencyStatus, "parent2visaExpiryDate"=>$parent2visaExpiryDate, "parent2email"=>$parent2email, "parent2phone1Type"=>$parent2phone1Type, "parent2phone1CountryCode"=>$parent2phone1CountryCode, "parent2phone1"=>$parent2phone1, "parent2phone2Type"=>$parent2phone2Type, "parent2phone2CountryCode"=>$parent2phone2CountryCode, "parent2phone2"=>$parent2phone2, "parent2profession"=>$parent2profession, "parent2employer"=>$parent2employer, "siblingName1"=>$siblingName1, "siblingDOB1"=>$siblingDOB1, "siblingSchool1"=>$siblingSchool1, "siblingSchoolJoiningDate1"=>$siblingSchoolJoiningDate1, "siblingName2"=>$siblingName2, "siblingDOB2"=>$siblingDOB2, "siblingSchool2"=>$siblingSchool2, "siblingSchoolJoiningDate2"=>$siblingSchoolJoiningDate2, "siblingName3"=>$siblingName3, "siblingDOB3"=>$siblingDOB3, "siblingSchool3"=>$siblingSchool3, "siblingSchoolJoiningDate3"=>$siblingSchoolJoiningDate3, "languageChoice"=>$languageChoice, "languageChoiceExperience"=>$languageChoiceExperience, "scholarshipInterest"=>$scholarshipInterest, "scholarshipRequired"=>$scholarshipRequired, "payment"=>$payment, "companyName"=>$companyName, "companyContact"=>$companyContact, "companyAddress"=>$companyAddress, "companyEmail"=>$companyEmail, "companyPhone"=>$companyPhone, "companyAll"=>$companyAll, "gibbonFinanceFeeCategoryIDList"=>$gibbonFinanceFeeCategoryIDList, "howDidYouHear"=>$howDidYouHear, "howDidYouHearMore"=>$howDidYouHearMore, "agreement"=>$agreement, "privacy"=>$privacy, "gibbonApplicationFormID"=>$gibbonApplicationFormID); 
					$sql="UPDATE gibbonApplicationForm SET priority=:priority, status=:status, milestones=:milestones, dateStart=:dateStart, gibbonRollGroupID=:gibbonRollGroupID, paymentMade=:paymentMade, notes=:notes, surname=:surname, firstName=:firstName, preferredName=:preferredName, officialName=:officialName, nameInCharacters=:nameInCharacters, gender=:gender, dob=:dob, languageHome=:languageHome, languageFirst=:languageFirst, languageSecond=:languageSecond, languageThird=:languageThird, countryOfBirth=:countryOfBirth, citizenship1=:citizenship1, citizenship1Passport=:citizenship1Passport, nationalIDCardNumber=:nationalIDCardNumber, residencyStatus=:residencyStatus, visaExpiryDate=:visaExpiryDate, email=:email, homeAddress=:homeAddress, homeAddressDistrict=:homeAddressDistrict, homeAddressCountry=:homeAddressCountry, phone1Type=:phone1Type, phone1CountryCode=:phone1CountryCode, phone1=:phone1, phone2Type=:phone2Type, phone2CountryCode=:phone2CountryCode, phone2=:phone2, medicalInformation=:medicalInformation, developmentInformation=:developmentInformation, gibbonSchoolYearIDEntry=:gibbonSchoolYearIDEntry, gibbonYearGroupIDEntry=:gibbonYearGroupIDEntry, dayType=:dayType, schoolName1=:schoolName1, schoolAddress1=:schoolAddress1, schoolGrades1=:schoolGrades1, schoolDate1=:schoolDate1, schoolName2=:schoolName2, schoolAddress2=:schoolAddress2, schoolGrades2=:schoolGrades2, schoolDate2=:schoolDate2, gibbonFamilyID=:gibbonFamilyID, parent1gibbonPersonID=:parent1gibbonPersonID, parent1title=:parent1title, parent1surname=:parent1surname, parent1firstName=:parent1firstName, parent1preferredName=:parent1preferredName, parent1officialName=:parent1officialName, parent1nameInCharacters=:parent1nameInCharacters, parent1gender=:parent1gender, parent1relationship=:parent1relationship, parent1languageFirst=:parent1languageFirst, parent1languageSecond=:parent1languageSecond, parent1citizenship1=:parent1citizenship1, parent1nationalIDCardNumber=:parent1nationalIDCardNumber, parent1residencyStatus=:parent1residencyStatus, parent1visaExpiryDate=:parent1visaExpiryDate, parent1email=:parent1email, parent1phone1Type=:parent1phone1Type, parent1phone1CountryCode=:parent1phone1CountryCode, parent1phone1=:parent1phone1, parent1phone2Type=:parent1phone2Type, parent1phone2CountryCode=:parent1phone2CountryCode, parent1phone2=:parent1phone2, parent1profession=:parent1profession, parent1employer=:parent1employer, parent2title=:parent2title, parent2surname=:parent2surname, parent2firstName=:parent2firstName, parent2preferredName=:parent2preferredName, parent2officialName=:parent2officialName, parent2nameInCharacters=:parent2nameInCharacters, parent2gender=:parent2gender, parent2relationship=:parent2relationship, parent2languageFirst=:parent2languageFirst, parent2languageSecond=:parent2languageSecond, parent2citizenship1=:parent2citizenship1, parent2nationalIDCardNumber=:parent2nationalIDCardNumber, parent2residencyStatus=:parent2residencyStatus, parent2visaExpiryDate=:parent2visaExpiryDate, parent2email=:parent2email, parent2phone1Type=:parent2phone1Type, parent2phone1CountryCode=:parent2phone1CountryCode, parent2phone1=:parent2phone1, parent2phone2Type=:parent2phone2Type, parent2phone2CountryCode=:parent2phone2CountryCode, parent2phone2=:parent2phone2, parent2profession=:parent2profession, parent2employer=:parent2employer, siblingName1=:siblingName1, siblingDOB1=:siblingDOB1, siblingSchool1=:siblingSchool1, siblingSchoolJoiningDate1=:siblingSchoolJoiningDate1, siblingName2=:siblingName2, siblingDOB2=:siblingDOB2, siblingSchool2=:siblingSchool2, siblingSchoolJoiningDate2=:siblingSchoolJoiningDate2, siblingName3=:siblingName3, siblingDOB3=:siblingDOB3, siblingSchool3=:siblingSchool3, siblingSchoolJoiningDate3=:siblingSchoolJoiningDate3, languageChoice=:languageChoice, languageChoiceExperience=:languageChoiceExperience, scholarshipInterest=:scholarshipInterest, scholarshipRequired=:scholarshipRequired, payment=:payment, companyName=:companyName, companyContact=:companyContact, companyAddress=:companyAddress, companyEmail=:companyEmail, companyPhone=:companyPhone, companyAll=:companyAll, gibbonFinanceFeeCategoryIDList=:gibbonFinanceFeeCategoryIDList, howDidYouHear=:howDidYouHear, howDidYouHearMore=:howDidYouHearMore, agreement=:agreement, privacy=:privacy WHERE gibbonApplicationFormID=:gibbonApplicationFormID" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					//Fail 2
					$URL=$URL . "&updateReturn=fail2" ;
					header("Location: {$URL}");
					break ;
				}
				
				//Success 0
				$URL=$URL . "&updateReturn=success0" ;
				header("Location: {$URL}");
			}
		}
	}
}
?>