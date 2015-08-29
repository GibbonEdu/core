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

//Module includes from User Admin (for custom fields)
include "../User Admin/moduleFunctions.php" ;

//Module includes from Finance (for setting payment log)
include "../Finance/moduleFunctions.php" ;

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]["timezone"]);

$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Application Form/applicationForm.php" ;

$proceed=FALSE ;
$public=FALSE ;

if (isset($_SESSION[$guid]["username"])==FALSE) {
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
	$URL.="&addReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	$id=NULL ;
	if (isset($_GET["id"])) {
		$id=$_GET["id"] ;
	}
	//IF ID IS NOT SET IT IS A NEW APPLICATION, SO PROCESS AND SAVE.
	if (is_null($id)) {
		//Proceed!
		//GET STUDENT FIELDS
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
			$dob=dateConvert($guid, $dob) ;
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
			$visaExpiryDate=dateConvert($guid, $visaExpiryDate) ;
		}
		$email=$_POST["email"] ;
		$phone1Type=$_POST["phone1Type"] ; 
		if ($_POST["phone1"]!="" AND $phone1Type=="") {
			$phone1Type="Other" ;
		}
		$phone1CountryCode=$_POST["phone1CountryCode"] ; 
		$phone1=preg_replace('/[^0-9+]/', '', $_POST["phone1"]) ; 
		$phone2Type=$_POST["phone2Type"] ; 
		if ($_POST["phone2"]!="" AND $phone2Type=="") {
			$phone2Type="Other" ;
		} 
		$phone2CountryCode=$_POST["phone2CountryCode"] ; 
		$phone2=preg_replace('/[^0-9+]/', '', $_POST["phone2"]) ; 
		$medicalInformation=$_POST["medicalInformation"] ;
		$developmentInformation=$_POST["developmentInformation"] ;
		$gibbonSchoolYearIDEntry=$_POST["gibbonSchoolYearIDEntry"] ;
		$dayType=NULL ;
		if (isset($_POST["dayType"])) {
			$dayType=$_POST["dayType"] ;
		}
		$dateStart=dateConvert($guid, $_POST["dateStart"]) ;
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
			$schoolDate1=dateConvert($guid, $schoolDate1) ;
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
			$schoolDate2=dateConvert($guid, $schoolDate2) ;
		}
		
	
		//GET FAMILY FEILDS
		$gibbonFamily=$_POST["gibbonFamily"] ;
		if ($gibbonFamily=="TRUE") {
			$gibbonFamilyID=$_POST["gibbonFamilyID"] ;
		}
		else {
			$gibbonFamilyID=NULL ;
		}
		$homeAddress=NULL ;
		if (isset($_POST["homeAddress"])) {
			$homeAddress=$_POST["homeAddress"] ;
		}
		$homeAddressDistrict=NULL ;
		if (isset($_POST["homeAddressDistrict"])) {
			$homeAddressDistrict=$_POST["homeAddressDistrict"] ;
		}
		$homeAddressCountry=NULL ;
		if (isset($_POST["homeAddressCountry"])) {
			$homeAddressCountry=$_POST["homeAddressCountry"] ;
		}
			
			
		//GET PARENT1 FEILDS
		$parent1gibbonPersonID=NULL ;
		if (isset($_POST["parent1gibbonPersonID"])) {
			$parent1gibbonPersonID=$_POST["parent1gibbonPersonID"] ;
		}
		$parent1title=NULL ;
		if (isset($_POST["parent1title"])) {
			$parent1title=$_POST["parent1title"] ;
		}
		$parent1surname=NULL ;
		if (isset($_POST["parent1surname"])) {
			$parent1surname=$_POST["parent1surname"] ;
		}
		$parent1firstName=NULL ;
		if (isset($_POST["parent1firstName"])) {
			$parent1firstName=$_POST["parent1firstName"] ;
		}
		$parent1preferredName=NULL ;
		if (isset($_POST["parent1preferredName"])) {
			$parent1preferredName=$_POST["parent1preferredName"] ;
		}
		$parent1officialName=NULL ;
		if (isset($_POST["parent1officialName"])) {
			$parent1officialName=$_POST["parent1officialName"] ;
		}
		$parent1nameInCharacters=NULL ;
		if (isset($_POST["parent1nameInCharacters"])) {
			$parent1nameInCharacters=$_POST["parent1nameInCharacters"] ;
		}
		$parent1gender=NULL ;
		if (isset($_POST["parent1gender"])) {
			$parent1gender=$_POST["parent1gender"] ;
		}
		$parent1relationship=NULL ;
		if (isset($_POST["parent1relationship"])) {
			$parent1relationship=$_POST["parent1relationship"] ;
		}
		$parent1languageFirst=NULL ;
		if (isset($_POST["parent1languageFirst"])) {
			$parent1languageFirst=$_POST["parent1languageFirst"] ;
		}
		$parent1languageSecond=NULL ;
		if (isset($_POST["parent1languageSecond"])) {
			$parent1languageSecond=$_POST["parent1languageSecond"] ;
		}
		$parent1citizenship1=NULL ;
		if (isset($_POST["parent1citizenship1"])) {
			$parent1citizenship1=$_POST["parent1citizenship1"] ;
		}
		$parent1nationalIDCardNumber=NULL ;
		if (isset($_POST["parent1nationalIDCardNumber"])) {
			$parent1nationalIDCardNumber=$_POST["parent1nationalIDCardNumber"] ;
		}
		$parent1residencyStatus=NULL ;
		if (isset($_POST["parent1residencyStatus"])) {
			$parent1residencyStatus=$_POST["parent1residencyStatus"] ;
		}
		$parent1visaExpiryDate=NULL ;
		if (isset($_POST["parent1visaExpiryDate"])) {
			if ($_POST["parent1visaExpiryDate"]!="") {
				$parent1visaExpiryDate=dateConvert($guid, $_POST["parent1visaExpiryDate"]) ;
			}
		}
		$parent1email=NULL ;
		if (isset($_POST["parent1email"])) {
			$parent1email=$_POST["parent1email"] ;
		}
		$parent1phone1Type=NULL ;
		if (isset($_POST["parent1phone1Type"])) {
			$parent1phone1Type=$_POST["parent1phone1Type"] ;
		}
		if (isset($_POST["parent1phone1"]) AND $parent1phone1Type=="") {
			$parent1phone1Type="Other" ;
		} 
		$parent1phone1CountryCode=NULL ;
		if (isset($_POST["parent1phone1CountryCode"])) {
			$parent1phone1CountryCode=$_POST["parent1phone1CountryCode"] ;
		}
		$parent1phone1=NULL ;
		if (isset($_POST["parent1phone1"])) {
			$parent1phone1=$_POST["parent1phone1"] ;
		}
		$parent1phone2Type=NULL ;
		if (isset($_POST["parent1phone2Type"])) {
			$parent1phone2Type=$_POST["parent1phone2Type"] ;
		}
		if (isset($_POST["parent1phone2"]) AND $parent1phone2Type=="") {
			$parent1phone2Type="Other" ;
		} 
		$parent1phone2CountryCode=NULL ;
		if (isset($_POST["parent1phone2CountryCode"])) {
			$parent1phone2CountryCode=$_POST["parent1phone2CountryCode"] ;
		}
		$parent1phone2=NULL ;
		if (isset($_POST["parent1phone2"])) {
			$parent1phone2=$_POST["parent1phone2"] ;
		}
		$parent1profession=NULL ;
		if (isset($_POST["parent1profession"])) {
			$parent1profession=$_POST["parent1profession"] ;
		}
		$parent1employer=NULL ;
		if (isset($_POST["parent1employer"])) {
			$parent1employer=$_POST["parent1employer"] ;
		}
		
		
		//GET PARENT2 FEILDS
		$parent2title=NULL ;
		if (isset($_POST["parent2title"])) {
			$parent2title=$_POST["parent2title"] ;
		}
		$parent2surname=NULL ;
		if (isset($_POST["parent2surname"])) {
			$parent2surname=$_POST["parent2surname"] ;
		}
		$parent2firstName=NULL ;
		if (isset($_POST["parent2firstName"])) {
			$parent2firstName=$_POST["parent2firstName"] ;
		}
		$parent2preferredName=NULL ;
		if (isset($_POST["parent2preferredName"])) {
			$parent2preferredName=$_POST["parent2preferredName"] ;
		}
		$parent2officialName=NULL ;
		if (isset($_POST["parent2officialName"])) {
			$parent2officialName=$_POST["parent2officialName"] ;
		}
		$parent2nameInCharacters=NULL ;
		if (isset($_POST["parent2nameInCharacters"])) {
			$parent2nameInCharacters=$_POST["parent2nameInCharacters"] ;
		}
		$parent2gender=NULL ;
		if (isset($_POST["parent2gender"])) {
			$parent2gender=$_POST["parent2gender"] ;
		}
		$parent2relationship=NULL ;
		if (isset($_POST["parent2relationship"])) {
			$parent2relationship=$_POST["parent2relationship"] ;
		}
		$parent2languageFirst=NULL ;
		if (isset($_POST["parent2languageFirst"])) {
			$parent2languageFirst=$_POST["parent2languageFirst"] ;
		}
		$parent2languageSecond=NULL ;
		if (isset($_POST["parent2languageSecond"])) {
			$parent2languageSecond=$_POST["parent2languageSecond"] ;
		}
		$parent2citizenship1=NULL ;
		if (isset($_POST["parent2citizenship1"])) {
			$parent2citizenship1=$_POST["parent2citizenship1"] ;
		}
		$parent2nationalIDCardNumber=NULL ;
		if (isset($_POST["parent2nationalIDCardNumber"])) {
			$parent2nationalIDCardNumber=$_POST["parent2nationalIDCardNumber"] ;
		}
		$parent2residencyStatus=NULL ;
		if (isset($_POST["parent2residencyStatus"])) {
			$parent2residencyStatus=$_POST["parent2residencyStatus"] ;
		}
		$parent2visaExpiryDate=NULL ;
		if (isset($_POST["parent2visaExpiryDate"])) {
			if ($_POST["parent2visaExpiryDate"]!="") {
				$parent2visaExpiryDate=dateConvert($guid, $_POST["parent2visaExpiryDate"]) ;
			}
		}
		$parent2email=NULL ;
		if (isset($_POST["parent2email"])) {
			$parent2email=$_POST["parent2email"] ;
		}
		$parent2phone1Type=NULL ;
		if (isset($_POST["parent2phone1Type"])) {
			$parent2phone1Type=$_POST["parent2phone1Type"] ;
		}
		if (isset($_POST["parent2phone1"]) AND $parent2phone1Type=="") {
			$parent2phone1Type="Other" ;
		} 
		$parent2phone1CountryCode=NULL ;
		if (isset($_POST["parent2phone1CountryCode"])) {
			$parent2phone1CountryCode=$_POST["parent2phone1CountryCode"] ;
		}
		$parent2phone1=NULL ;
		if (isset($_POST["parent2phone1"])) {
			$parent2phone1=$_POST["parent2phone1"] ;
		}
		$parent2phone2Type=NULL ;
		if (isset($_POST["parent2phone2Type"])) {
			$parent2phone2Type=$_POST["parent2phone2Type"] ;
		}
		if (isset($_POST["parent2phone2"]) AND $parent2phone2Type=="") {
			$parent2phone2Type="Other" ;
		} 
		$parent2phone2CountryCode=NULL ;
		if (isset($_POST["parent2phone2CountryCode"])) {
			$parent2phone2CountryCode=$_POST["parent2phone2CountryCode"] ;
		}
		$parent2phone2=NULL ;
		if (isset($_POST["parent2phone2"])) {
			$parent2phone2=$_POST["parent2phone2"] ;
		}
		$parent2profession=NULL ;
		if (isset($_POST["parent2profession"])) {
			$parent2profession=$_POST["parent2profession"] ;
		}
		$parent2employer=NULL ;
		if (isset($_POST["parent2employer"])) {
			$parent2employer=$_POST["parent2employer"] ;
		}
			
	
		//GET SIBLING FIELDS
		$siblingName1=$_POST["siblingName1"] ;
		$siblingDOB1=$_POST["siblingDOB1"] ;
		if ($siblingDOB1=="") {
			$siblingDOB1=NULL ;
		}
		else {
			$siblingDOB1=dateConvert($guid, $siblingDOB1);
		}
		$siblingSchool1=$_POST["siblingSchool1"] ;
		$siblingSchoolJoiningDate1=$_POST["siblingSchoolJoiningDate1"] ;
		if ($siblingSchoolJoiningDate1=="") {
			$siblingSchoolJoiningDate1=NULL ;
		}
		else {
			$siblingSchoolJoiningDate1=dateConvert($guid, $siblingSchoolJoiningDate1) ;
		}
		$siblingName2=$_POST["siblingName2"] ;
		$siblingDOB2=$_POST["siblingDOB2"] ;
		if ($siblingDOB2=="") {
			$siblingDOB2=NULL ;
		}
		else {
			$siblingDOB2=dateConvert($guid, $siblingDOB2);
		}
		$siblingSchool2=$_POST["siblingSchool2"] ;
		$siblingSchoolJoiningDate2=$_POST["siblingSchoolJoiningDate2"] ;
		if ($siblingSchoolJoiningDate2=="") {
			$siblingSchoolJoiningDate2=NULL ;
		}
		else {
			$siblingSchoolJoiningDate2=dateConvert($guid, $siblingSchoolJoiningDate2) ;
		}
		$siblingName3=$_POST["siblingName3"] ;
		$siblingDOB3=$_POST["siblingDOB3"] ;
		if ($siblingDOB3=="") {
			$siblingDOB3=NULL ;
		}
		else {
			$siblingDOB3=dateConvert($guid, $siblingDOB3) ;
		}
		$siblingSchool3=$_POST["siblingSchool3"] ;
		$siblingSchoolJoiningDate3=$_POST["siblingSchoolJoiningDate3"] ;
		if ($siblingSchoolJoiningDate3=="") {
			$siblingSchoolJoiningDate3=NULL ;
		}
		else {
			$siblingSchoolJoiningDate3=dateConvert($guid, $siblingSchoolJoiningDate3) ;
		}
	
		//GET PAYMENT FIELDS
		$payment=$_POST["payment"] ;
		$companyName=NULL ;
		if (isset($_POST["companyName"])) {
			$companyName=$_POST["companyName"] ;
		}
		$companyContact=NULL ;
		if (isset($_POST["companyContact"])) {
			$companyContact=$_POST["companyContact"] ;
		}
		$companyAddress=NULL ;
		if (isset($_POST["companyAddress"])) {
			$companyAddress=$_POST["companyAddress"] ;
		}
		$companyEmail=NULL ;
		if (isset($_POST["companyEmail"])) {
			$companyEmail=$_POST["companyEmail"] ;
		}
		$companyCCFamily=NULL ;
		if (isset($_POST["companyCCFamily"])) {
			$companyCCFamily=$_POST["companyCCFamily"] ;
		}
		$companyPhone=NULL ;
		if (isset($_POST["companyPhone"])) {
			$companyPhone=$_POST["companyPhone"] ;
		}
		$companyAll=NULL ;
		if (isset($_POST["companyAll"])) {
			$companyAll=$_POST["companyAll"] ;
		}
		$gibbonFinanceFeeCategoryIDList=NULL ;
		if (isset($_POST["gibbonFinanceFeeCategoryIDList"])) {
			$gibbonFinanceFeeCategoryIDArray=$_POST["gibbonFinanceFeeCategoryIDList"] ;
			if (count($gibbonFinanceFeeCategoryIDArray)>0) {
				foreach ($gibbonFinanceFeeCategoryIDArray AS $gibbonFinanceFeeCategoryID) {
					$gibbonFinanceFeeCategoryIDList.=$gibbonFinanceFeeCategoryID . "," ;
				}
				$gibbonFinanceFeeCategoryIDList=substr($gibbonFinanceFeeCategoryIDList,0,-1) ;
			}
		}

		
		//GET OTHER FIELDS
		$languageChoice=NULL ;
		if (isset($_POST["languageChoice"])) {
			$languageChoice=$_POST["languageChoice"] ;
		}
		$languageChoiceExperience=NULL ;
		if (isset($_POST["languageChoiceExperience"])) {
			$languageChoiceExperience=$_POST["languageChoiceExperience"] ;
		}
		$scholarshipInterest=NULL ;
		if (isset($_POST["scholarshipInterest"])) {
			$scholarshipInterest=$_POST["scholarshipInterest"] ;
		}
		$scholarshipRequired=NULL ;
		if (isset($_POST["scholarshipRequired"])) {
			$scholarshipRequired=$_POST["scholarshipRequired"] ;
		}
		$howDidYouHear=NULL ;
		if (isset($_POST["howDidYouHear"])) {
			$howDidYouHear=$_POST["howDidYouHear"] ;
		}
		$howDidYouHearMore=NULL ;
		if (isset($_POST["howDidYouHearMore"])) {
			$howDidYouHearMore=$_POST["howDidYouHearMore"] ;
		}
		$agreement=NULL ;
		if (isset($_POST["agreement"])) {
			if ($_POST["agreement"]=="on") {
				$agreement="Y" ;
			}
			else {
				$agreement="N" ;
			}
		}
		$privacy=NULL ;
		if (isset($_POST["privacyOptions"])) {
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
	
		//VALIDATE INPUTS
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
			if ($parent1gibbonPersonID==NULL) {
				if ($parent1title=="" OR $parent1surname=="" OR $parent1firstName=="" OR $parent1preferredName=="" OR $parent1officialName=="" OR $parent1gender=="" OR $parent1relationship=="" OR $parent1phone1==""OR $parent1profession=="") {
					$familyFail=TRUE ;
				}
			}
			if (isset($_POST["secondParent"])) {
				if ($_POST["secondParent"]!="No") {
					if ($parent2title=="" OR $parent2surname=="" OR $parent2firstName=="" OR $parent2preferredName=="" OR $parent2officialName=="" OR $parent2gender=="" OR $parent2relationship=="" OR $parent2phone1==""OR $parent2profession=="") {
						$familyFail=TRUE ;
					}
				}
			}
		}
		if ($surname=="" OR $firstName=="" OR $preferredName=="" OR $officialName=="" OR $gender=="" OR $dob=="" OR $languageHome=="" OR $languageFirst=="" OR $gibbonSchoolYearIDEntry=="" OR $dateStart=="" OR $gibbonYearGroupIDEntry=="" OR $howDidYouHear=="" OR (isset($_POST["agreement"]) AND $agreement!="Y") OR $familyFail) {
			//Fail 3
			$URL.="&addReturn=fail3" ;
			header("Location: {$URL}");
		}
		else {
			//DEAL WITH CUSTOM FIELDS
			$customRequireFail=FALSE ;
			//Prepare field values
			//CHILD
			$resultFields=getCustomFields($connection2, $guid, TRUE, FALSE, FALSE, FALSE, TRUE, NULL) ;
			$fields=array() ;
			if ($resultFields->rowCount()>0) {
				while ($rowFields=$resultFields->fetch()) {
					if (isset($_POST["custom" . $rowFields["gibbonPersonFieldID"]])) {
						if ($rowFields["type"]=="date") {
							$fields[$rowFields["gibbonPersonFieldID"]]=dateConvert($guid, $_POST["custom" . $rowFields["gibbonPersonFieldID"]]) ;
						}
						else {
							$fields[$rowFields["gibbonPersonFieldID"]]=$_POST["custom" . $rowFields["gibbonPersonFieldID"]] ;
						}
					}
					if ($rowFields["required"]=="Y") {
						if (isset($_POST["custom" . $rowFields["gibbonPersonFieldID"]])==FALSE) {
							$customRequireFail=TRUE ;
						}
						else if ($_POST["custom" . $rowFields["gibbonPersonFieldID"]]=="") {
							$customRequireFail=TRUE ;
						}
					}
				}
			}
			if ($gibbonFamily=="FALSE") { //Only if there is no family
				//PARENT 1
				$resultFields=getCustomFields($connection2, $guid, FALSE, FALSE, TRUE, FALSE, TRUE, NULL) ;
				$parent1fields=array() ;
				if ($resultFields->rowCount()>0) {
					while ($rowFields=$resultFields->fetch()) {
						if (isset($_POST["parent1custom" . $rowFields["gibbonPersonFieldID"]])) {
							if ($rowFields["type"]=="date") {
								$parent1fields[$rowFields["gibbonPersonFieldID"]]=dateConvert($guid, $_POST["parent1custom" . $rowFields["gibbonPersonFieldID"]]) ;
							}
							else {
								$parent1fields[$rowFields["gibbonPersonFieldID"]]=$_POST["parent1custom" . $rowFields["gibbonPersonFieldID"]] ;
							}
						}
						if ($rowFields["required"]=="Y") {
							if (isset($_POST["parent1custom" . $rowFields["gibbonPersonFieldID"]])==FALSE) {
								$customRequireFail=TRUE ;
							}
							else if ($_POST["parent1custom" . $rowFields["gibbonPersonFieldID"]]=="") {
								$customRequireFail=TRUE ;
							}
						}
					}
				}
				if (isset($_POST["secondParent"])==FALSE) {
					//PARENT 2
					$resultFields=getCustomFields($connection2, $guid, FALSE, FALSE, TRUE, FALSE, TRUE, NULL) ;
					$parent2fields=array() ;
					if ($resultFields->rowCount()>0) {
						while ($rowFields=$resultFields->fetch()) {
							if (isset($_POST["parent2custom" . $rowFields["gibbonPersonFieldID"]])) {
								if ($rowFields["type"]=="date") {
									$parent2fields[$rowFields["gibbonPersonFieldID"]]=dateConvert($guid, $_POST["parent2custom" . $rowFields["gibbonPersonFieldID"]]) ;
								}
								else {
									$parent2fields[$rowFields["gibbonPersonFieldID"]]=$_POST["parent2custom" . $rowFields["gibbonPersonFieldID"]] ;
								}
							}
							if ($rowFields["required"]=="Y") {
								if (isset($_POST["parent2custom" . $rowFields["gibbonPersonFieldID"]])==FALSE) {
									$customRequireFail=TRUE ;
								}
								else if ($_POST["parent2custom" . $rowFields["gibbonPersonFieldID"]]=="") {
									$customRequireFail=TRUE ;
								}
							}
						}
					}
				}
			}
			
			if ($customRequireFail) {
				//Fail 3
				$URL.="&addReturn=fail3" ;
				header("Location: {$URL}");
			}
			else {
				$fields=serialize($fields) ;
				if (isset($parent1fields)) {
					$parent1fields=serialize($parent1fields) ;
				}
				else {
					$parent1fields="" ;
				}
				if (isset($parent2fields)) {
					$parent2fields=serialize($parent2fields) ;
				}
				else {
					$parent2fields="" ;
				}
			
				//Write to database
				try {
					$data=array("surname"=>$surname, "firstName"=>$firstName, "preferredName"=>$preferredName, "officialName"=>$officialName, "nameInCharacters"=>$nameInCharacters, "gender"=>$gender, "dob"=>$dob, "languageHome"=>$languageHome, "languageFirst"=>$languageFirst, "languageSecond"=>$languageSecond, "languageThird"=>$languageThird, "countryOfBirth"=>$countryOfBirth, "citizenship1"=>$citizenship1, "citizenship1Passport"=>$citizenship1Passport, "nationalIDCardNumber"=>$nationalIDCardNumber, "residencyStatus"=>$residencyStatus, "visaExpiryDate"=>$visaExpiryDate, "email"=>$email, "homeAddress"=>$homeAddress, "homeAddressDistrict"=>$homeAddressDistrict, "homeAddressCountry"=>$homeAddressCountry, "phone1Type"=>$phone1Type, "phone1CountryCode"=>$phone1CountryCode, "phone1"=>$phone1, "phone2Type"=>$phone2Type, "phone2CountryCode"=>$phone2CountryCode, "phone2"=>$phone2, "medicalInformation"=>$medicalInformation, "developmentInformation"=>$developmentInformation, "gibbonSchoolYearIDEntry"=>$gibbonSchoolYearIDEntry, "dayType"=>$dayType, "dateStart"=>$dateStart, "gibbonYearGroupIDEntry"=>$gibbonYearGroupIDEntry, "schoolName1"=>$schoolName1, "schoolAddress1"=>$schoolAddress1, "schoolGrades1"=>$schoolGrades1, "schoolDate1"=>$schoolDate1, "schoolName2"=>$schoolName2, "schoolAddress2"=>$schoolAddress2, "schoolGrades2"=>$schoolGrades2, "schoolDate2"=>$schoolDate2, "gibbonFamilyID"=>$gibbonFamilyID, "parent1gibbonPersonID"=>$parent1gibbonPersonID, "parent1title"=>$parent1title, "parent1surname"=>$parent1surname, "parent1firstName"=>$parent1firstName, "parent1preferredName"=>$parent1preferredName, "parent1officialName"=>$parent1officialName, "parent1nameInCharacters"=>$parent1nameInCharacters, "parent1gender"=>$parent1gender, "parent1relationship"=>$parent1relationship, "parent1languageFirst"=>$parent1languageFirst, "parent1languageSecond"=>$parent1languageSecond, "parent1citizenship1"=>$parent1citizenship1, "parent1nationalIDCardNumber"=>$parent1nationalIDCardNumber, "parent1residencyStatus"=>$parent1residencyStatus, "parent1visaExpiryDate"=>$parent1visaExpiryDate, "parent1email"=>$parent1email, "parent1phone1Type"=>$parent1phone1Type, "parent1phone1CountryCode"=>$parent1phone1CountryCode, "parent1phone1"=>$parent1phone1, "parent1phone2Type"=>$parent1phone2Type, "parent1phone2CountryCode"=>$parent1phone2CountryCode, "parent1phone2"=>$parent1phone2, "parent1profession"=>$parent1profession, "parent1employer"=>$parent1employer, "parent2title"=>$parent2title, "parent2surname"=>$parent2surname, "parent2firstName"=>$parent2firstName, "parent2preferredName"=>$parent2preferredName, "parent2officialName"=>$parent2officialName, "parent2nameInCharacters"=>$parent2nameInCharacters, "parent2gender"=>$parent2gender, "parent2relationship"=>$parent2relationship, "parent2languageFirst"=>$parent2languageFirst, "parent2languageSecond"=>$parent2languageSecond, "parent2citizenship1"=>$parent2citizenship1, "parent2nationalIDCardNumber"=>$parent2nationalIDCardNumber, "parent2residencyStatus"=>$parent2residencyStatus, "parent2visaExpiryDate"=>$parent2visaExpiryDate, "parent2email"=>$parent2email, "parent2phone1Type"=>$parent2phone1Type, "parent2phone1CountryCode"=>$parent2phone1CountryCode, "parent2phone1"=>$parent2phone1, "parent2phone2Type"=>$parent2phone2Type, "parent2phone2CountryCode"=>$parent2phone2CountryCode, "parent2phone2"=>$parent2phone2, "parent2profession"=>$parent2profession, "parent2employer"=>$parent2employer, "siblingName1"=>$siblingName1, "siblingDOB1"=>$siblingDOB1, "siblingSchool1"=>$siblingSchool1, "siblingSchoolJoiningDate1"=>$siblingSchoolJoiningDate1, "siblingName2"=>$siblingName2, "siblingDOB2"=>$siblingDOB2, "siblingSchool2"=>$siblingSchool2, "siblingSchoolJoiningDate2"=>$siblingSchoolJoiningDate2, "siblingName3"=>$siblingName3, "siblingDOB3"=>$siblingDOB3, "siblingSchool3"=>$siblingSchool3, "siblingSchoolJoiningDate3"=>$siblingSchoolJoiningDate3, "languageChoice"=>$languageChoice, "languageChoiceExperience"=>$languageChoiceExperience, "scholarshipInterest"=>$scholarshipInterest, "scholarshipRequired"=>$scholarshipRequired, "payment"=>$payment, "companyName"=>$companyName, "companyContact"=>$companyContact, "companyAddress"=>$companyAddress, "companyEmail"=>$companyEmail, "companyCCFamily"=>$companyCCFamily, "companyPhone"=>$companyPhone, "companyAll"=>$companyAll, "gibbonFinanceFeeCategoryIDList"=>$gibbonFinanceFeeCategoryIDList, "howDidYouHear"=>$howDidYouHear, "howDidYouHearMore"=>$howDidYouHearMore, "agreement"=>$agreement, "privacy"=>$privacy, "fields"=>$fields, "parent1fields"=>$parent1fields, "parent2fields"=>$parent2fields, "timestamp"=>date("Y-m-d H:i:s")); 
					$sql="INSERT INTO gibbonApplicationForm SET surname=:surname, firstName=:firstName, preferredName=:preferredName, officialName=:officialName, nameInCharacters=:nameInCharacters, gender=:gender, dob=:dob, languageHome=:languageHome, languageFirst=:languageFirst, languageSecond=:languageSecond, languageThird=:languageThird, countryOfBirth=:countryOfBirth, citizenship1=:citizenship1, citizenship1Passport=:citizenship1Passport, nationalIDCardNumber=:nationalIDCardNumber, residencyStatus=:residencyStatus, visaExpiryDate=:visaExpiryDate, email=:email, homeAddress=:homeAddress, homeAddressDistrict=:homeAddressDistrict, homeAddressCountry=:homeAddressCountry, phone1Type=:phone1Type, phone1CountryCode=:phone1CountryCode, phone1=:phone1, phone2Type=:phone2Type, phone2CountryCode=:phone2CountryCode, phone2=:phone2, medicalInformation=:medicalInformation, developmentInformation=:developmentInformation, gibbonSchoolYearIDEntry=:gibbonSchoolYearIDEntry, dateStart=:dateStart, gibbonYearGroupIDEntry=:gibbonYearGroupIDEntry, dayType=:dayType, schoolName1=:schoolName1, schoolAddress1=:schoolAddress1, schoolGrades1=:schoolGrades1, schoolDate1=:schoolDate1, schoolName2=:schoolName2, schoolAddress2=:schoolAddress2, schoolGrades2=:schoolGrades2, schoolDate2=:schoolDate2, gibbonFamilyID=:gibbonFamilyID, parent1gibbonPersonID=:parent1gibbonPersonID, parent1title=:parent1title, parent1surname=:parent1surname, parent1firstName=:parent1firstName, parent1preferredName=:parent1preferredName, parent1officialName=:parent1officialName, parent1nameInCharacters=:parent1nameInCharacters, parent1gender=:parent1gender, parent1relationship=:parent1relationship, parent1languageFirst=:parent1languageFirst, parent1languageSecond=:parent1languageSecond, parent1citizenship1=:parent1citizenship1, parent1nationalIDCardNumber=:parent1nationalIDCardNumber, parent1residencyStatus=:parent1residencyStatus, parent1visaExpiryDate=:parent1visaExpiryDate, parent1email=:parent1email, parent1phone1Type=:parent1phone1Type, parent1phone1CountryCode=:parent1phone1CountryCode, parent1phone1=:parent1phone1, parent1phone2Type=:parent1phone2Type, parent1phone2CountryCode=:parent1phone2CountryCode, parent1phone2=:parent1phone2, parent1profession=:parent1profession, parent1employer=:parent1employer, parent2title=:parent2title, parent2surname=:parent2surname, parent2firstName=:parent2firstName, parent2preferredName=:parent2preferredName, parent2officialName=:parent2officialName, parent2nameInCharacters=:parent2nameInCharacters, parent2gender=:parent2gender, parent2relationship=:parent2relationship, parent2languageFirst=:parent2languageFirst, parent2languageSecond=:parent2languageSecond, parent2citizenship1=:parent2citizenship1, parent2nationalIDCardNumber=:parent2nationalIDCardNumber, parent2residencyStatus=:parent2residencyStatus, parent2visaExpiryDate=:parent2visaExpiryDate, parent2email=:parent2email, parent2phone1Type=:parent2phone1Type, parent2phone1CountryCode=:parent2phone1CountryCode, parent2phone1=:parent2phone1, parent2phone2Type=:parent2phone2Type, parent2phone2CountryCode=:parent2phone2CountryCode, parent2phone2=:parent2phone2, parent2profession=:parent2profession, parent2employer=:parent2employer, siblingName1=:siblingName1, siblingDOB1=:siblingDOB1, siblingSchool1=:siblingSchool1, siblingSchoolJoiningDate1=:siblingSchoolJoiningDate1, siblingName2=:siblingName2, siblingDOB2=:siblingDOB2, siblingSchool2=:siblingSchool2, siblingSchoolJoiningDate2=:siblingSchoolJoiningDate2, siblingName3=:siblingName3, siblingDOB3=:siblingDOB3, siblingSchool3=:siblingSchool3, siblingSchoolJoiningDate3=:siblingSchoolJoiningDate3, languageChoice=:languageChoice, languageChoiceExperience=:languageChoiceExperience, scholarshipInterest=:scholarshipInterest, scholarshipRequired=:scholarshipRequired, payment=:payment, companyName=:companyName, companyContact=:companyContact, companyAddress=:companyAddress, companyEmail=:companyEmail, companyCCFamily=:companyCCFamily, companyPhone=:companyPhone, companyAll=:companyAll, gibbonFinanceFeeCategoryIDList=:gibbonFinanceFeeCategoryIDList, howDidYouHear=:howDidYouHear, howDidYouHearMore=:howDidYouHearMore, agreement=:agreement, privacy=:privacy, fields=:fields, parent1fields=:parent1fields, parent2fields=:parent2fields, timestamp=:timestamp" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					//Fail 2
					$URL.="&addReturn=fail2" ;
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
					$fileCount=0 ;
					if (isset($_POST["fileCount"])) {
						$fileCount=$_POST["fileCount"] ;
					}
					for ($i=0; $i<$fileCount; $i++) {
						$fileName=$_POST["fileName$i"] ;
						$time=time() ;
						//Move attached file, if there is one
						if ($_FILES["file$i"]["tmp_name"]!="") {
							//Check for folder in uploads based on today's date
							$path=$_SESSION[$guid]["absolutePath"] ;
							if (is_dir($path ."/uploads/" . date("Y", $time) . "/" . date("m", $time))==FALSE) {
								mkdir($path ."/uploads/" . date("Y", $time) . "/" . date("m", $time), 0777, TRUE) ;
							}
							$unique=FALSE;
							$count=0 ;
							while ($unique==FALSE AND $count<100) {
								$suffix=randomPassword(16) ;
								$attachment="uploads/" . date("Y", $time) . "/" . date("m", $time) . "/Application Document_$suffix" . strrchr($_FILES["file$i"]["name"], ".") ;
								if (!(file_exists($path . "/" . $attachment))) {
									$unique=TRUE ;
								}
								$count++ ;
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
							catch(PDOException $e) { }
						}
					}
				}
			
			
				//Attempt to notify admissions administrator
				if ($_SESSION[$guid]["organisationAdmissions"]) {
					$notificationText=sprintf(_('An application form has submitted for %1$s.'), formatName("", $preferredName, $surname, "Student")) ;
					setNotification($connection2, $guid, $_SESSION[$guid]["organisationAdmissions"], $notificationText, "Application Form", "/index.php?q=/modules/User Admin/applicationForm_manage_edit.php&gibbonApplicationFormID=$AI&gibbonSchoolYearID=$gibbonSchoolYearIDEntry&search=") ;
				}
		
				//Attempt payment if everything is set up for it
				$applicationFee=getSettingByScope($connection2, "Application Form", "applicationFee") ;
				$enablePayments=getSettingByScope($connection2, "System", "enablePayments") ;
				$paypalAPIUsername=getSettingByScope($connection2, "System", "paypalAPIUsername") ;
				$paypalAPIPassword=getSettingByScope($connection2, "System", "paypalAPIPassword") ;
				$paypalAPISignature=getSettingByScope($connection2, "System", "paypalAPISignature") ;
	
				if ($applicationFee>0 AND is_numeric($applicationFee) AND $enablePayments=="Y" AND $paypalAPIUsername!="" AND $paypalAPIPassword!="" AND $paypalAPISignature!="") {
					$_SESSION[$guid]["gatewayCurrencyNoSupportReturnURL"]=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Application Form/applicationForm.php&addReturn=success4&id=$AI" ;
					$URL=$_SESSION[$guid]["absoluteURL"] . "/lib/paypal/expresscheckout.php?Payment_Amount=$applicationFee&return=" . urlencode("modules/Application Form/applicationFormProcess.php?addReturn=success1&id=$AI&applicationFee=$applicationFee") . "&fail=" . urlencode("modules/Application Form/applicationFormProcess.php?addReturn=success2&id=$AI&applicationFee=$applicationFee") ;
					header("Location: {$URL}");
				}
				else {
					//Success 0
					$URL.="&addReturn=success0&id=$AI" ;
					header("Location: {$URL}");
				}
			}
		}
	}
	//IF ID IS SET WE ARE JUST RETURNING TO FINALISE PAYMENT AND RECORD OF PAYMENT, SO LET'S DO IT.
	else {
		//Get returned paypal tokens, ids, etc
		$paymentMade='N' ;
		if ($_GET["addReturn"]=="success1") {
			$paymentMade='Y' ;
		}
		$paymentToken=NULL ;
		if (isset($_GET["token"])) {
			$paymentToken=$_GET["token"] ;
		}
		$paymentPayerID=NULL ;
		if (isset($_GET["PayerID"])) {
			$paymentPayerID=$_GET["PayerID"] ;
		}
		$gibbonApplicationFormID=NULL ;
		if (isset($_GET["id"])) {
			$gibbonApplicationFormID=$_GET["id"] ;
		}
		$applicationFee=NULL ;
		if (isset($_GET["applicationFee"])) {
			$applicationFee=$_GET["applicationFee"] ;
		}
		
		//Get email parameters ready to send messages for to admissions for payment problems
		$to=$_SESSION[$guid]["organisationAdmissionsEmail"];
		$subject=$_SESSION[$guid]["organisationNameShort"] . " Gibbon Application Form Payment Issue";
		$headers="From: " . $_SESSION[$guid]["organisationAdministratorEmail"] ;
				
		//Check return values to see if we can proceed
		if ($paymentToken=="" OR $gibbonApplicationFormID=="" OR $applicationFee=="") {
			$body=_('Payment via PayPal may or may not have been successful, but has not been recorded either way due to a system error. Please check your PayPal account for details. The following may be useful:') . "\n\nPayment Token: $paymentToken\n\nPayer ID: $paymentPayerID\n\nApplication Form ID: $gibbonApplicationFormID\n\nApplication Fee: $applicationFee\n\n" . $_SESSION[$guid]["systemName"] . " " . _('Administrator');
			mail($to, $subject, $body, $headers) ;
			
			//Success 2
			$URL.="&addReturn=success2&id=" . $_GET["id"] ;
			header("Location: {$URL}");
			exit() ;
		}
		else {
			//PROCEED AND FINALISE PAYMENT
			require "../../lib/paypal/paypalfunctions.php" ;
		
			//Ask paypal to finalise the payment
			$confirmPayment=confirmPayment($guid, $applicationFee, $paymentToken, $paymentPayerID) ;
	
			$ACK=$confirmPayment["ACK"] ;
			$paymentTransactionID=$confirmPayment["PAYMENTINFO_0_TRANSACTIONID"] ;
			$paymentReceiptID=$confirmPayment["PAYMENTINFO_0_RECEIPTID"] ;
			
			//Payment was successful. Yeah!
			if ($ACK="Success") {
				$updateFail=false ;
				
				//Save payment details to gibbonPayment
				$gibbonPaymentID=setPaymentLog($connection2, $guid, "gibbonApplicationForm", $gibbonApplicationFormID, "Online", "Complete", $applicationFee, "Paypal", "Success", $paymentToken, $paymentPayerID, $paymentTransactionID, $paymentReceiptID) ;
				
				//Link gibbonPayment record to gibbonApplicationForm, and make note that payment made
				if ($gibbonPaymentID!="") {
					try {
						$data=array("paymentMade"=>$paymentMade, "gibbonPaymentID"=>$gibbonPaymentID, "gibbonApplicationFormID"=>$gibbonApplicationFormID); 
						$sql="UPDATE gibbonApplicationForm SET paymentMade=:paymentMade, gibbonPaymentID=:gibbonPaymentID WHERE gibbonApplicationFormID=:gibbonApplicationFormID" ;
						$result=$connection2->prepare($sql);
						$result->execute($data);
					}
					catch(PDOException $e) { 
						$updateFail=true ;
					}
				}
				else {
					$updateFail=true ;
				}
				
				if ($updateFail==true) {
					$body=_('Payment via PayPal was successful, but has not been recorded due to a system error. Please check your PayPal account for details. The following may be useful:') . "\n\nPayment Token: $paymentToken\n\nPayer ID: $paymentPayerID\n\nApplication Form ID: $gibbonApplicationFormID\n\nApplication Fee: $applicationFee\n\n" . $_SESSION[$guid]["systemName"] . " " . _('Administrator');
					mail($to, $subject, $body, $headers) ;
			
					//Success 3
					$URL.="&addReturn=success3&id=" . $_GET["id"] ;
					header("Location: {$URL}");
					exit ;
				}
				
				//Success 1
				$URL.="&addReturn=success1&id=" . $_GET["id"] ;
				header("Location: {$URL}");
			}
			else {
				$updateFail=false ;
				
				//Save payment details to gibbonPayment
				$gibbonPaymentID=setPaymentLog($connection2, $guid, "gibbonApplicationForm", $gibbonApplicationFormID, "Online", "Failure", $applicationFee, "Paypal", "Failure", $paymentToken, $paymentPayerID, $paymentTransactionID, $paymentReceiptID) ;
				
				//Link gibbonPayment record to gibbonApplicationForm, and make note that payment made
				if ($gibbonPaymentID!="") {
					try {
						$data=array("paymentMade"=>$paymentMade, "gibbonPaymentID"=>$gibbonPaymentID, "gibbonApplicationFormID"=>$gibbonApplicationFormID); 
						$sql="UPDATE gibbonApplicationForm SET paymentMade=:paymentMade, gibbonPaymentID=:gibbonPaymentID WHERE gibbonApplicationFormID=:gibbonApplicationFormID" ;
						$result=$connection2->prepare($sql);
						$result->execute($data);
					}
					catch(PDOException $e) { 
						$updateFail=true ;
					}
				}
				else {
					$updateFail=true ;
				}
				
				if ($updateFail==true) {
					$body=_('Payment via PayPal was unsuccessful, and has also not been recorded due to a system error. Please check your PayPal account for details. The following may be useful:') . "\n\nPayment Token: $paymentToken\n\nPayer ID: $paymentPayerID\n\nApplication Form ID: $gibbonApplicationFormID\n\nApplication Fee: $applicationFee\n\n" . $_SESSION[$guid]["systemName"] . " " . _('Administrator');
					mail($to, $subject, $body, $headers) ;
			
					//Success 2
					$URL.="&addReturn=success2&id=" . $_GET["id"] ;
					header("Location: {$URL}");
					exit ;
				}
				
				//Success 2
				$URL.="&addReturn=success2&id=" . $_GET["id"] ;
				header("Location: {$URL}");
			}
		}
	}
}
?>