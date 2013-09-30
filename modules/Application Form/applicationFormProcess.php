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

session_start() ;

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]["timezone"]);

$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Application Form/applicationForm.php" ;

$proceed=FALSE ;
$public=FALSE ;

if ($_SESSION[$guid]["username"]=="") {
	$public=TRUE ;
	//Get public access
	$access=getSettingByScope($connection2, 'Application Form', 'publicApplications') ;
	if ($access=="Y") {
		$proceed=TRUE ;
	}
}
else {
	if (isActionAccessible($guid, $connection2, "/modules/Application Form/applicationForm.php")!=FALSE) {
		$proceed=TRUE ;
	}
}


if ($proceed==FALSE) {
	//Fail 0
	$URL = $URL . "&addReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	$id=$_GET["id"] ;
	//IF ID IS NOT SET IT IS A NEW APPLICATION, SO PROCESS AND SAVE.
	if ($id=="") {
		//Proceed!
		//Get student fields
		$surname=$_POST["surname"] ;
		$firstName=$_POST["firstName"] ;
		$otherNames=$_POST["otherNames"] ;
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
		if ($_POST["phone1"]!="" AND $phone1Type=="") {
			$phone1Type="Other" ;
		}
		$phone1CountryCode=$_POST["phone1CountryCode"] ; 
		$phone1=$_POST["phone1"] ; 
		$phone2Type=$_POST["phone2Type"] ; 
		if ($_POST["phone2"]!="" AND $phone2Type=="") {
			$phone2Type="Other" ;
		} 
		$phone2CountryCode=$_POST["phone2CountryCode"] ; 
		$phone2=$_POST["phone2"] ; 
		$medicalInformation=$_POST["medicalInformation"] ;
		$developmentInformation=$_POST["developmentInformation"] ;
		$gibbonSchoolYearIDEntry=$_POST["gibbonSchoolYearIDEntry"] ;
		$dayType=$_POST["dayType"] ;
		$dateStart=dateConvert($_POST["dateStart"]) ;
		$gibbonYearGroupIDEntry=$_POST["gibbonYearGroupIDEntry"] ;
		$schoolName1=$_POST["schoolName1"] ;
		$schoolAddress1=$_POST["schoolAddress1"] ;
		$schoolGrades1=$_POST["schoolGrades1"] ;
		$schoolGrades1=$_POST["schoolGrades1"] ;
		$schoolDate1=$_POST["schoolDate1"] ;
		if ($schoolDate1=="") {
			$schoolDate1=NULL ;
		}
		else {
			$schoolDate1=dateConvert($schoolDate1) ;
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
		//APPLICATION MADE BY FAMILY, SO BLANK ALL PARENT FIELDS
		if ($gibbonFamily=="TRUE") {
			$gibbonFamilyID=$_POST["gibbonFamilyID"] ;
			$homeAddress="" ; 	
			$homeAddressDistrict="" ; 	
			$homeAddressCountry="" ; 	
			$parent1title="" ;
			$parent1surname="" ;
			$parent1firstName="" ;
			$parent1otherNames="" ;
			$parent1preferredName="" ;
			$parent1officialName="" ;
			$parent1nameInCharacters="" ;
			$parent1gender="" ;
			$parent1relationship="" ;
			$parent1languageFirst="" ;
			$parent1languageSecond="" ;
			$parent1citizenship1="" ;
			$parent1nationalIDCardNumber="" ;
			$parent1residencyStatus="" ;
			$parent1visaExpiryDate=NULL ;
			$parent1email="" ;
			$parent1address1="" ;
			$parent1address1District="" ;
			$parent1address1Country="" ;
			$parent1phone1Type="" ;
			$parent1phone1CountryCode="" ;
			$parent1phone1="" ;
			$parent1phone2Type="" ;
			$parent1phone2CountryCode="" ;
			$parent1phone2="" ;
			$parent1profession="" ;
			$parent1employer="" ;
			$parent2title="" ;
			$parent2surname="" ;
			$parent2firstName="" ;
			$parent2otherNames="" ;
			$parent2preferredName="" ;
			$parent2officialName="" ;
			$parent2nameInCharacters="" ;
			$parent2gender="" ;
			$parent2relationship="" ;
			$parent2languageFirst="" ;
			$parent2languageSecond="" ;
			$parent2citizenship1="" ;
			$parent2nationalIDCardNumber="" ;
			$parent2residencyStatus="" ;
			$parent2visaExpiryDate=NULL ;
			$parent2email="" ;
			$parent2address1="" ;
			$parent2address1District="" ;
			$parent2address1Country="" ;
			$parent2phone1Type="" ;
			$parent2phone1CountryCode="" ;
			$parent2phone1="" ;
			$parent2phone2Type="" ;
			$parent2phone2CountryCode="" ;
			$parent2phone2="" ;
			$parent2profession="" ;
			$parent2employer="" ;
		}
		else {
			//APPLICATION NOT MADE BY FAMILY, SO GET PARENT 1 FIELDS
			$gibbonFamilyID=NULL ;
			$homeAddress=$_POST["homeAddress"] ; 	
			$homeAddressDistrict=$_POST["homeAddressDistrict"] ; 	
			$homeAddressCountry=$_POST["homeAddressCountry"] ; 	
			//Is parent 1 alreayd in system? If so, set gibbonPerson, and null other fields.
			$parent1gibbonPersonID=$_POST["parent1gibbonPersonID"] ;
			if ($parent1gibbonPersonID=="") {
				$parent1gibbonPersonID=NULL ;
			}
			else {
				$parent1gibbonPersonID=$parent1gibbonPersonID;
			}
			$parent1title=$_POST["parent1title"] ;
			if (is_null($parent1title)) {
				$parent1title="" ;
			}
			$parent1surname=$_POST["parent1surname"] ;
			$parent1firstName=$_POST["parent1firstName"] ;
			if (is_null($parent1firstName)) {
				$parent1firstName="" ;
			}
			$parent1otherNames=$_POST["parent1otherNames"] ;
			if (is_null($parent1otherNames)) {
				$parent1otherNames="" ;
			}
			$parent1preferredName=$_POST["parent1preferredName"] ;
			$parent1officialName=$_POST["parent1officialName"] ;
			if (is_null($parent1officialName)) {
				$parent1officialName="" ;
			}
			$parent1nameInCharacters=$_POST["parent1nameInCharacters"] ;
			if (is_null($parent1nameInCharacters)) {
				$parent1nameInCharacters="" ;
			}
			$parent1gender=$_POST["parent1gender"] ;
			if (is_null($parent1gender)) {
				$parent1gender="" ;
			}
			$parent1relationship=$_POST["parent1relationship"] ;
			if (is_null($parent1relationship)) {
				$parent1relationship="" ;
			}
			$parent1languageFirst=$_POST["parent1languageFirst"] ;
			if (is_null($parent1languageFirst)) {
				$parent1languageFirst="" ;
			}
			$parent1languageSecond=$_POST["parent1languageSecond"] ;
			if (is_null($parent1languageSecond)) {
				$parent1languageSecond="" ;
			}
			$parent1citizenship1=$_POST["parent1citizenship1"] ;
			if (is_null($parent1citizenship1)) {
				$parent1citizenship1="" ;
			}
			$parent1nationalIDCardNumber=$_POST["parent1nationalIDCardNumber"] ;
			if (is_null($parent1nationalIDCardNumber)) {
				$parent1nationalIDCardNumber="" ;
			}
			$parent1residencyStatus=$_POST["parent1residencyStatus"] ;
			if (is_null($parent1residencyStatus)) {
				$parent1residencyStatus="" ;
			}
			$parent1visaExpiryDate=$_POST["parent1visaExpiryDate"] ;
			if ($parent1visaExpiryDate=="") {
				$parent1visaExpiryDate=NULL ;
			}
			else {
				$parent1visaExpiryDate=dateConvert($parent1visaExpiryDate) ;
			}
			$parent1email=$_POST["parent1email"] ;
			$parent1phone1Type=$_POST["parent1phone1Type"] ;
			if ($_POST["parent1phone1"]!="" AND $parent1phone1Type=="") {
				$parent1phone1Type="Other" ;
			} 
			if (is_null($parent1phone1Type)) {
				$parent1phone1Type="" ;
			}		
			$parent1phone1CountryCode=$_POST["parent1phone1CountryCode"] ; 
			if (is_null($parent1phone1CountryCode)) {
				$parent1phone1CountryCode="" ;
			}		
			$parent1phone1=$_POST["parent1phone1"] ; 
			if (is_null($parent1phone1)) {
				$parent1phone1="" ;
			}		
			$parent1phone2Type=$_POST["parent1phone2Type"] ;
			if ($_POST["parent1phone2"]!="" AND $parent1phone2Type=="") {
				$parent1phone2Type="Other" ;
			}  
			if (is_null($parent1phone2Type)) {
				$parent1phone2Type="" ;
			}		
			$parent1phone2CountryCode=$_POST["parent1phone2CountryCode"] ; 
			if (is_null($parent1phone2CountryCode)) {
				$parent1phone2CountryCode="" ;
			}		
			$parent1phone2=$_POST["parent1phone2"] ; 
			if (is_null($parent1phone2)) {
				$parent1phone2="" ;
			}		
			$parent1profession=$_POST["parent1profession"] ;
			$parent1employer=$_POST["parent1employer"] ;
			//PARENT 2 INCLUDED IN APPLICATION, SO GET FIELDS
			if ($_POST["secondParent"]!="No") {
				$parent2title=$_POST["parent2title"] ;
				$parent2surname=$_POST["parent2surname"] ;
				$parent2firstName=$_POST["parent2firstName"] ;
				$parent2otherNames=$_POST["parent2otherNames"] ;
				$parent2preferredName=$_POST["parent2preferredName"] ;
				$parent2officialName=$_POST["parent2officialName"] ;
				$parent2nameInCharacters=$_POST["parent2nameInCharacters"] ;
				$parent2gender=$_POST["parent2gender"] ;
				if (is_null($parent2gender)) {
					$parent2gender="" ;
				}
				$parent2relationship=$_POST["parent2relationship"] ;
				if (is_null($parent2relationship) OR $parent2relationship=="Please select...") {
					$parent2relationship="" ;
				}
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
				if ($_POST["parent2phone1"]!="" AND $parent2phone1Type=="") {
					$parent2phone1Type="Other" ;
				} 
				$parent2phone1CountryCode=$_POST["parent2phone1CountryCode"] ; 
				$parent2phone1=$_POST["parent2phone1"] ; 
				$parent2phone2Type=$_POST["parent2phone2Type"] ; 
				if ($_POST["parent2phone2"]!="" AND $parent2phone2Type=="") {
					$parent2phone2Type="Other" ;
				} 
				$parent2phone2CountryCode=$_POST["parent2phone2CountryCode"] ; 
				$parent2phone2=$_POST["parent2phone2"] ; 
				$parent2profession=$_POST["parent2profession"] ;
				$parent2employer=$_POST["parent2employer"] ;
			}
			//PARENT 2 NOT INCLUDED IN APPLICATION, SO BLANK FIELDS
			else {
				$parent2title="" ;
				$parent2surname="" ;
				$parent2firstName="" ;
				$parent2otherNames="" ;
				$parent2preferredName="" ;
				$parent2officialName="" ;
				$parent2nameInCharacters="" ;
				$parent2gender="" ;
				$parent2relationship="" ;
				$parent2languageFirst="" ;
				$parent2languageSecond="" ;
				$parent2citizenship1="" ;
				$parent2nationalIDCardNumber="" ;
				$parent2residencyStatus="" ;
				$parent2visaExpiryDate=NULL ;
				$parent2email="" ;
				$parent2phone1Type="" ;
				$parent2phone1CountryCode="" ;
				$parent2phone1="" ;
				$parent2phone2Type="" ;
				$parent2phone2CountryCode="" ;
				$parent2phone2="" ;
				$parent2profession="" ;
				$parent2employer="" ;
			}
		}
	
		//Get sibling information
		$siblingName1=$_POST["siblingName1"] ;
		$siblingDOB1=$_POST["siblingDOB1"] ;
		if ($siblingDOB1=="") {
			$siblingDOB1=NULL ;
		}
		else {
			$siblingDOB1=dateConvert($siblingDOB1);
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
			$siblingDOB3=dateConvert($siblingDOB3) ;
		}
		$siblingSchool3=$_POST["siblingSchool3"] ;
		$siblingSchoolJoiningDate3=$_POST["siblingSchoolJoiningDate3"] ;
		if ($siblingSchoolJoiningDate3=="") {
			$siblingSchoolJoiningDate3=NULL ;
		}
		else {
			$siblingSchoolJoiningDate3=dateConvert($siblingSchoolJoiningDate3) ;
		}
	
		//Get other fields
		$languageChoice=$_POST["languageChoice"] ;
		$languageChoiceExperience=$_POST["languageChoiceExperience"] ;
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
		}
		$howDidYouHear=$_POST["howDidYouHear"] ;
		$howDidYouHearMore=$_POST["howDidYouHearMore"] ;
		$agreement=$_POST["agreement"] ;
		if ($agreement=="on") {
			$agreement="Y" ;
		}
		$privacyOptions=$_POST["privacyOptions"] ;
		$privacy="" ;
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
	
		//Validate Inputs
		$familyFail=FALSE ;
		if ($gibbonFamily=="TRUE") {
			if ($gibbonFamilyID=="") {
				$familyFail=TRUE ;
			}
		}
		else {
			if ($homeAddress=="" OR $homeAddressDistrict=="" OR $homeAddressCountry=="") {
				$familyFail=TRUE ;
			}
			if ($parent1gibonPersonID=="parent1gibbonPersonID=NULL") {
				if ($parent1title=="" OR $parent1surname=="" OR $parent1firstName=="" OR $parent1preferredName=="" OR $parent1officialName=="" OR $parent1gender=="" OR $parent1relationship=="" OR $parent1phone1==""OR $parent1profession=="") {
					$familyFail=TRUE ;
				}
			}
			if ($_POST["secondParent"]!="No") {
				if ($parent2title=="" OR $parent2surname=="" OR $parent2firstName=="" OR $parent2preferredName=="" OR $parent2officialName=="" OR $parent2gender=="" OR $parent2relationship=="" OR $parent2phone1==""OR $parent2profession=="") {
					$familyFail=TRUE ;
				}
			}
		}
		if ($surname=="" OR $firstName=="" OR $preferredName=="" OR $officialName=="" OR $gender=="" OR $dob=="dob=NULL" OR $languageHome=="" OR $languageFirst=="" OR $gibbonSchoolYearIDEntry=="" OR $dateStart=="" OR $gibbonYearGroupIDEntry=="" OR $howDidYouHear=="" OR $agreement!="Y" OR $familyFail) {
			//Fail 3
			$URL = $URL . "&addReturn=fail3" ;
			header("Location: {$URL}");
		}
		else {
			//Write to database
			try {
				$data=array("surname"=>$surname, "firstName"=>$firstName, "otherNames"=>$otherNames, "preferredName"=>$preferredName, "officialName"=>$officialName, "nameInCharacters"=>$nameInCharacters, "gender"=>$gender, "dob"=>$dob, "languageHome"=>$languageHome, "languageFirst"=>$languageFirst, "languageSecond"=>$languageSecond, "languageThird"=>$languageThird, "countryOfBirth"=>$countryOfBirth, "citizenship1"=>$citizenship1, "citizenship1Passport"=>$citizenship1Passport, "nationalIDCardNumber"=>$nationalIDCardNumber, "residencyStatus"=>$residencyStatus, "visaExpiryDate"=>$visaExpiryDate, "email"=>$email, "homeAddress"=>$homeAddress, "homeAddressDistrict"=>$homeAddressDistrict, "homeAddressCountry"=>$homeAddressCountry, "phone1Type"=>$phone1Type, "phone1CountryCode"=>$phone1CountryCode, "phone1"=>$phone1, "phone2Type"=>$phone2Type, "phone2CountryCode"=>$phone2CountryCode, "phone2"=>$phone2, "medicalInformation"=>$medicalInformation, "developmentInformation"=>$developmentInformation, "gibbonSchoolYearIDEntry"=>$gibbonSchoolYearIDEntry, "dayType"=>$dayType, "dateStart"=>$dateStart, "gibbonYearGroupIDEntry"=>$gibbonYearGroupIDEntry, "schoolName1"=>$schoolName1, "schoolAddress1"=>$schoolAddress1, "schoolGrades1"=>$schoolGrades1, "schoolDate1"=>$schoolDate1, "schoolName2"=>$schoolName2, "schoolAddress2"=>$schoolAddress2, "schoolGrades2"=>$schoolGrades2, "schoolDate2"=>$schoolDate2, "gibbonFamilyID"=>$gibbonFamilyID, "parent1gibbonPersonID"=>$parent1gibbonPersonID, "parent1title"=>$parent1title, "parent1surname"=>$parent1surname, "parent1firstName"=>$parent1firstName, "parent1otherNames"=>$parent1otherNames, "parent1preferredName"=>$parent1preferredName, "parent1officialName"=>$parent1officialName, "parent1nameInCharacters"=>$parent1nameInCharacters, "parent1gender"=>$parent1gender, "parent1relationship"=>$parent1relationship, "parent1languageFirst"=>$parent1languageFirst, "parent1languageSecond"=>$parent1languageSecond, "parent1citizenship1"=>$parent1citizenship1, "parent1nationalIDCardNumber"=>$parent1nationalIDCardNumber, "parent1residencyStatus"=>$parent1residencyStatus, "parent1visaExpiryDate"=>$parent1visaExpiryDate, "parent1email"=>$parent1email, "parent1phone1Type"=>$parent1phone1Type, "parent1phone1CountryCode"=>$parent1phone1CountryCode, "parent1phone1"=>$parent1phone1, "parent1phone2Type"=>$parent1phone2Type, "parent1phone2CountryCode"=>$parent1phone2CountryCode, "parent1phone2"=>$parent1phone2, "parent1profession"=>$parent1profession, "parent1employer"=>$parent1employer, "parent2title"=>$parent2title, "parent2surname"=>$parent2surname, "parent2firstName"=>$parent2firstName, "parent2otherNames"=>$parent2otherNames, "parent2preferredName"=>$parent2preferredName, "parent2officialName"=>$parent2officialName, "parent2nameInCharacters"=>$parent2nameInCharacters, "parent2gender"=>$parent2gender, "parent2relationship"=>$parent2relationship, "parent2languageFirst"=>$parent2languageFirst, "parent2languageSecond"=>$parent2languageSecond, "parent2citizenship1"=>$parent2citizenship1, "parent2nationalIDCardNumber"=>$parent2nationalIDCardNumber, "parent2residencyStatus"=>$parent2residencyStatus, "parent2visaExpiryDate"=>$parent2visaExpiryDate, "parent2email"=>$parent2email, "parent2phone1Type"=>$parent2phone1Type, "parent2phone1CountryCode"=>$parent2phone1CountryCode, "parent2phone1"=>$parent2phone1, "parent2phone2Type"=>$parent2phone2Type, "parent2phone2CountryCode"=>$parent2phone2CountryCode, "parent2phone2"=>$parent2phone2, "parent2profession"=>$parent2profession, "parent2employer"=>$parent2employer, "siblingName1"=>$siblingName1, "siblingDOB1"=>$siblingDOB1, "siblingSchool1"=>$siblingSchool1, "siblingSchoolJoiningDate1"=>$siblingSchoolJoiningDate1, "siblingName2"=>$siblingName2, "siblingDOB2"=>$siblingDOB2, "siblingSchool2"=>$siblingSchool2, "siblingSchoolJoiningDate2"=>$siblingSchoolJoiningDate2, "siblingName3"=>$siblingName3, "siblingDOB3"=>$siblingDOB3, "siblingSchool3"=>$siblingSchool3, "siblingSchoolJoiningDate3"=>$siblingSchoolJoiningDate3, "languageChoice"=>$languageChoice, "languageChoiceExperience"=>$languageChoiceExperience, "scholarshipInterest"=>$scholarshipInterest, "scholarshipRequired"=>$scholarshipRequired, "payment"=>$payment, "companyName"=>$companyName, "companyContact"=>$companyContact, "companyAddress"=>$companyAddress, "companyEmail"=>$companyEmail, "companyPhone"=>$companyPhone, "companyAll"=>$companyAll, "gibbonFinanceFeeCategoryIDList"=>$gibbonFinanceFeeCategoryIDList, "howDidYouHear"=>$howDidYouHear, "howDidYouHearMore"=>$howDidYouHearMore, "agreement"=>$agreement, "privacy"=>$privacy, "timestamp"=>date("Y-m-d H:i:s")); 
				$sql="INSERT INTO gibbonApplicationForm SET surname=:surname, firstName=:firstName, otherNames=:otherNames, preferredName=:preferredName, officialName=:officialName, nameInCharacters=:nameInCharacters, gender=:gender, dob=:dob, languageHome=:languageHome, languageFirst=:languageFirst, languageSecond=:languageSecond, languageThird=:languageThird, countryOfBirth=:countryOfBirth, citizenship1=:citizenship1, citizenship1Passport=:citizenship1Passport, nationalIDCardNumber=:nationalIDCardNumber, residencyStatus=:residencyStatus, visaExpiryDate=:visaExpiryDate, email=:email, homeAddress=:homeAddress, homeAddressDistrict=:homeAddressDistrict, homeAddressCountry=:homeAddressCountry, phone1Type=:phone1Type, phone1CountryCode=:phone1CountryCode, phone1=:phone1, phone2Type=:phone2Type, phone2CountryCode=:phone2CountryCode, phone2=:phone2, medicalInformation=:medicalInformation, developmentInformation=:developmentInformation, gibbonSchoolYearIDEntry=:gibbonSchoolYearIDEntry, dateStart=:dateStart, gibbonYearGroupIDEntry=:gibbonYearGroupIDEntry, dayType=:dayType, schoolName1=:schoolName1, schoolAddress1=:schoolAddress1, schoolGrades1=:schoolGrades1, schoolDate1=:schoolDate1, schoolName2=:schoolName2, schoolAddress2=:schoolAddress2, schoolGrades2=:schoolGrades2, schoolDate2=:schoolDate2, gibbonFamilyID=:gibbonFamilyID, parent1gibbonPersonID=:parent1gibbonPersonID, parent1title=:parent1title, parent1surname=:parent1surname, parent1firstName=:parent1firstName, parent1otherNames=:parent1otherNames, parent1preferredName=:parent1preferredName, parent1officialName=:parent1officialName, parent1nameInCharacters=:parent1nameInCharacters, parent1gender=:parent1gender, parent1relationship=:parent1relationship, parent1languageFirst=:parent1languageFirst, parent1languageSecond=:parent1languageSecond, parent1citizenship1=:parent1citizenship1, parent1nationalIDCardNumber=:parent1nationalIDCardNumber, parent1residencyStatus=:parent1residencyStatus, parent1visaExpiryDate=:parent1visaExpiryDate, parent1email=:parent1email, parent1phone1Type=:parent1phone1Type, parent1phone1CountryCode=:parent1phone1CountryCode, parent1phone1=:parent1phone1, parent1phone2Type=:parent1phone2Type, parent1phone2CountryCode=:parent1phone2CountryCode, parent1phone2=:parent1phone2, parent1profession=:parent1profession, parent1employer=:parent1employer, parent2title=:parent2title, parent2surname=:parent2surname, parent2firstName=:parent2firstName, parent2otherNames=:parent2otherNames, parent2preferredName=:parent2preferredName, parent2officialName=:parent2officialName, parent2nameInCharacters=:parent2nameInCharacters, parent2gender=:parent2gender, parent2relationship=:parent2relationship, parent2languageFirst=:parent2languageFirst, parent2languageSecond=:parent2languageSecond, parent2citizenship1=:parent2citizenship1, parent2nationalIDCardNumber=:parent2nationalIDCardNumber, parent2residencyStatus=:parent2residencyStatus, parent2visaExpiryDate=:parent2visaExpiryDate, parent2email=:parent2email, parent2phone1Type=:parent2phone1Type, parent2phone1CountryCode=:parent2phone1CountryCode, parent2phone1=:parent2phone1, parent2phone2Type=:parent2phone2Type, parent2phone2CountryCode=:parent2phone2CountryCode, parent2phone2=:parent2phone2, parent2profession=:parent2profession, parent2employer=:parent2employer, siblingName1=:siblingName1, siblingDOB1=:siblingDOB1, siblingSchool1=:siblingSchool1, siblingSchoolJoiningDate1=:siblingSchoolJoiningDate1, siblingName2=:siblingName2, siblingDOB2=:siblingDOB2, siblingSchool2=:siblingSchool2, siblingSchoolJoiningDate2=:siblingSchoolJoiningDate2, siblingName3=:siblingName3, siblingDOB3=:siblingDOB3, siblingSchool3=:siblingSchool3, siblingSchoolJoiningDate3=:siblingSchoolJoiningDate3, languageChoice=:languageChoice, languageChoiceExperience=:languageChoiceExperience, scholarshipInterest=:scholarshipInterest, scholarshipRequired=:scholarshipRequired, payment=:payment, companyName=:companyName, companyContact=:companyContact, companyAddress=:companyAddress, companyEmail=:companyEmail, companyPhone=:companyPhone, companyAll=:companyAll, gibbonFinanceFeeCategoryIDList=:gibbonFinanceFeeCategoryIDList, howDidYouHear=:howDidYouHear, howDidYouHearMore=:howDidYouHearMore, agreement=:agreement, privacy=:privacy, timestamp=:timestamp" ;
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				//Fail 2
				$URL = $URL . "&addReturn=fail2" ;
				header("Location: {$URL}");
				break ;
			}
		
			//Last insert ID
			$AI=str_pad($connection2->lastInsertID(), 7, "0", STR_PAD_LEFT) ;
		
			//Deal with family relationships
			if ($gibbonFamily=="TRUE") {
				$relationships=$_POST[$gibbonFamilyID . "-relationships"] ;
				$relationshipsGibbonPersonIDs=$_POST[$gibbonFamilyID . "-relationshipsGibbonPersonID"] ;
				$count=0 ;
				foreach ($relationships AS $relationship) {
					try {
						$data=array("gibbonApplicationFormID"=>$AI, "gibbonPersonID"=>$relationshipsGibbonPersonIDs[$count], "relationship"=>$relationship); 
						$sql="INSERT INTO gibbonApplicationFormRelationship SET gibbonApplicationFormID=:gibbonApplicationFormID, gibbonPersonID=:gibbonPersonID, relationship=:relationship" ;
						$result=$connection2->prepare($sql);
						$result->execute($data);
					}
					catch(PDOException $e) { }
					$count++ ;
				}
				
			}
			//Deal with required documents
			$requiredDocuments=getSettingByScope($connection2, "Application Form", "requiredDocuments") ;
			if ($requiredDocuments!="" AND $requiredDocuments!=FALSE) {
				$fileCount=$_POST["fileCount"] ;
				for ($i=0; $i<$fileCount; $i++) {
					$fileName=$_POST["fileName$i"] ;
					$time=mktime() ;
					//Move attached file, if there is one
					if ($_FILES["file$i"]["tmp_name"]!="") {
						//Check for folder in uploads based on today's date
						$path=$_SESSION[$guid]["absolutePath"] ;
						if (is_dir($path ."/uploads/" . date("Y", $time) . "/" . date("m", $time))==FALSE) {
							mkdir($path ."/uploads/" . date("Y", $time) . "/" . date("m", $time), 0777, TRUE) ;
						}
						$unique=FALSE;
						while ($unique==FALSE) {
							$suffix=randomPassword(16) ;
							$attachment="uploads/" . date("Y", $time) . "/" . date("m", $time) . "/Application Document_$suffix" . strrchr($_FILES["file$i"]["name"], ".") ;
							if (!(file_exists($path . "/" . $attachment))) {
								$unique=TRUE ;
							}
						}
						if (!(move_uploaded_file($_FILES["file$i"]["tmp_name"],$path . "/" . $attachment))) {
						}
						
						//Write files to database
						try {
							$dataFile=array("gibbonApplicationFormID"=>$AI, "name"=>$fileName, "path"=>$attachment); 
							$sqlFile="INSERT INTO gibbonApplicationFormFile SET gibbonApplicationFormID=:gibbonApplicationFormID, name=:name, path=:path" ;
							$resultFile=$connection2->prepare($sqlFile);
							$resultFile->execute($dataFile);
						}
						catch(PDOException $e) { print $e->getMessage() ; }
					}
				}
			}
			
			
			//Attempt to send email to DBA
			if ($_SESSION[$guid]["organisationAdmissionsName"]!="" AND $_SESSION[$guid]["organisationAdmissionsEmail"]!="") {
				//Work out year of entry
				$extra="" ;
				try {
					$dataEntry=array("gibbonSchoolYearIDEntry"=>$gibbonSchoolYearIDEntry); 
					$sqlEntry="SELECT * FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearIDEntry" ;
					$resultEntry=$connection2->prepare($sqlEntry);
					$resultEntry->execute($dataEntry);
				}
				catch(PDOException $e) { }
				if ($resultEntry->rowCount()==1) {
					$rowEntry=$resultEntry->fetch() ;
					$extra=", for the academic year " . $rowEntry["name"] ;
				}
			
				$to = $_SESSION[$guid]["organisationAdmissionsEmail"];
				$subject = $_SESSION[$guid]["organisationNameShort"] . " Gibbon Application Form";
				$body = "You have a new application form from Gibbon" . $extra . ". Please log in and process it as soon as possible.\n\n" . $_SESSION[$guid]["systemName"] . " Administrator";
				$headers = "From: " . $_SESSION[$guid]["organisationAdministratorEmail"] ;
				mail($to, $subject, $body, $headers) ;
			}
		
			//Attempt payment if everything is set up for it
			$applicationFee=getSettingByScope($connection2, "Application Form", "applicationFee") ;
			$enablePayments=getSettingByScope($connection2, "System", "enablePayments") ;
			$paypalAPIUsername=getSettingByScope($connection2, "System", "paypalAPIUsername") ;
			$paypalAPIPassword=getSettingByScope($connection2, "System", "paypalAPIPassword") ;
			$paypalAPISignature=getSettingByScope($connection2, "System", "paypalAPISignature") ;
	
			if ($applicationFee>0 AND is_numeric($applicationFee) AND $enablePayments=="Y" AND $paypalAPIUsername!="" AND $paypalAPIPassword!="" AND $paypalAPISignature!="") {
				$URL = $_SESSION[$guid]["absoluteURL"] . "/lib/paypal/expresscheckout.php?Payment_Amount=$applicationFee&return=" . urlencode("modules/Application Form/applicationFormProcess.php?addReturn=success1&id=$AI") . "&fail=" . urlencode("modules/Application Form/applicationFormProcess.php?addReturn=success2&id=$AI") ;
				header("Location: {$URL}");
			}
			else {
				//Success 0
				$URL = $URL . "&addReturn=success0&id=$AI" ;
				header("Location: {$URL}");
			}
		}
	}
	//IF ID IS SET WE ARE JUST RETURNING TO FINALISE RECORD OF PAYMENT RECORD PAYMENT, SO LET'S DO IT.
	else {
		//Try to write back payment details
		$paymentMade='N' ;
		if ($_GET["addReturn"]=="success1") {
			$paymentMade='Y' ;
		}
		try {
			$data=array("paymentMade"=>$paymentMade, "paypalPaymentToken"=>$_GET["token"], "paypalPaymentPayerID"=>$_GET["PayerID"], "gibbonApplicationFormID"=>$_GET["id"]); 
			$sql="UPDATE gibbonApplicationForm SET paymentMade=:paymentMade, paypalPaymentToken=:paypalPaymentToken, paypalPaymentPayerID=:paypalPaymentPayerID WHERE gibbonApplicationFormID=:gibbonApplicationFormID" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			//Success 3
			$URL = $URL . "&addReturn=success3&id=" . $_GET["id"] ;
			header("Location: {$URL}");
			break ;
		}
		
		if ($paymentMade=='Y') {
			//Success 1
			$URL = $URL . "&addReturn=success1&id=" . $_GET["id"] ;
			header("Location: {$URL}");
		}
		else if ($paymentMade=='N') {
			//Success 2
			$URL = $URL . "&addReturn=success2&id=" . $_GET["id"] ;
			header("Location: {$URL}");
		}
	}
}
?>