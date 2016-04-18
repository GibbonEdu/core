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
$pdo = new Gibbon\sqlConnection();
$connection2 = $pdo->getConnection();

@session_start() ;

//Module includes from User Admin (for custom fields)
include "../User Admin/moduleFunctions.php" ;

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]["timezone"]);

$gibbonStaffApplicationFormID=$_POST["gibbonStaffApplicationFormID"] ;
$search=$_GET["search"] ;
$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/applicationForm_manage_edit.php&gibbonStaffApplicationFormID=$gibbonStaffApplicationFormID&search=$search" ;

if (isActionAccessible($guid, $connection2, "/modules/Staff/applicationForm_manage_edit.php")==FALSE) {
	//Fail 0
	$URL.="&updateReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	//Proceed!
	//Check if school year specified
	
	if ($gibbonStaffApplicationFormID=="") {
		//Fail1
		$URL.="&updateReturn=fail1" ;
		header("Location: {$URL}");
	}
	else {
		try {
			$data=array("gibbonStaffApplicationFormID"=>$gibbonStaffApplicationFormID); 
			$sql="SELECT * FROM gibbonStaffApplicationForm WHERE gibbonStaffApplicationFormID=:gibbonStaffApplicationFormID" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			//Fail2
			$URL.="&updateReturn=fail2" ;
			header("Location: {$URL}");
			exit() ;
		}

		if ($result->rowCount()!=1) {
			//Fail 2
			$URL.="&updateReturn=fail2" ;
			header("Location: {$URL}");
		}
		else {
			//Proceed!
			//Get student fields
			$priority=$_POST["priority"] ;
			$status=$_POST["status"] ;
			$milestones="" ;
			$milestonesMaster=explode(",", getSettingByScope($connection2, "Staff", "staffApplicationFormMilestones")) ;
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
				$dateStart=dateConvert($guid, $_POST["dateStart"]) ;
			}
			$notes=$_POST["notes"] ;
			$gibbonStaffJobOpeningID=$_POST["gibbonStaffJobOpeningID"] ;
			$questions="" ;
			if (isset($_POST["questions"])) {
				$questions=$_POST["questions"] ;
			}
			$gibbonPersonID=NULL ;
			if (isset($_POST["gibbonPersonID"])) {
				$gibbonPersonID=$_POST["gibbonPersonID"] ;
			}
			$surname=NULL ;
			if (isset($_POST["surname"])) {
				$surname=$_POST["surname"] ;
			}
			$firstName=NULL ;
			if (isset($_POST["firstName"])) {
				$firstName=$_POST["firstName"] ;
			}
			$preferredName=NULL ;
			if (isset($_POST["preferredName"])) {
				$preferredName=$_POST["preferredName"] ;
			}
			$officialName=NULL ;
			if (isset($_POST["officialName"])) {
				$officialName=$_POST["officialName"] ;
			}
			$nameInCharacters=NULL ;
			if (isset($_POST["nameInCharacters"])) {
				$nameInCharacters=$_POST["nameInCharacters"] ;
			}
			$gender=NULL ;
			if (isset($_POST["gender"])) {
				$gender=$_POST["gender"] ;
			}
			$dob=NULL ;
			if (isset($_POST["dob"])) {
				$dob=dateConvert($guid, $_POST["dob"]) ;
			}
			$languageFirst=NULL ;
			if (isset($_POST["languageFirst"])) {
				$languageFirst=$_POST["languageFirst"] ;
			}
			$languageSecond=NULL ;
			if (isset($_POST["languageSecond"])) {
				$languageSecond=$_POST["languageSecond"] ;
			}
			$languageThird=NULL ;
			if (isset($_POST["languageThird"])) {
				$languageThird=$_POST["languageThird"] ;
			}
			$countryOfBirth=NULL ;
			if (isset($_POST["countryOfBirth"])) {
				$countryOfBirth=$_POST["countryOfBirth"] ;
			}
			$citizenship1=NULL ;
			if (isset($_POST["citizenship1"])) {
				$citizenship1=$_POST["citizenship1"] ;
			}
			$citizenship1Passport=NULL ;
			if (isset($_POST["citizenship1Passport"])) {
				$citizenship1Passport=$_POST["citizenship1Passport"] ;
			}
			$nationalIDCardNumber=NULL ;
			if (isset($_POST["nationalIDCardNumber"])) {
				$nationalIDCardNumber=$_POST["nationalIDCardNumber"] ;
			}
			$residencyStatus=NULL ;
			if (isset($_POST["residencyStatus"])) {
				$residencyStatus=$_POST["residencyStatus"] ;
			}
			$visaExpiryDate=NULL ;
			if (isset($_POST["visaExpiryDate"]) AND $_POST["visaExpiryDate"]!="") {
				$visaExpiryDate=dateConvert($guid, $_POST["visaExpiryDate"]) ;
			}
			$email=NULL ;
			if (isset($_POST["email"])) {
				$email=$_POST["email"] ;
			}
			$phone1Type=NULL ;
			if (isset($_POST["phone1Type"])) {
				$phone1Type=$_POST["phone1Type"] ;
				if ($_POST["phone1"]!="" AND $phone1Type=="") {
					$phone1Type="Other" ;
				}	
			} 
			$phone1CountryCode=NULL ;
			if (isset($_POST["phone1CountryCode"])) {
				$phone1CountryCode=$_POST["phone1CountryCode"] ; 
			}
			$phone1=NULL ;
			if (isset($_POST["phone1"])) {
				$phone1=preg_replace('/[^0-9+]/', '', $_POST["phone1"]) ; 
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
			
			if ($gibbonStaffJobOpeningID=="" OR ($gibbonPersonID==NULL AND ($surname=="" OR $firstName=="" OR $preferredName=="" OR $officialName=="" OR $gender=="" OR $dob=="" OR $languageFirst=="" OR $email=="" OR $homeAddress=="" OR $homeAddressDistrict=="" OR $homeAddressCountry=="" OR $phone1==""))) {
				//Fail 3
				$URL.="&updateReturn=fail3" ;
				header("Location: {$URL}");
			}
			else {
				//DEAL WITH CUSTOM FIELDS
				$customRequireFail=FALSE ;
				//Prepare field values
				$resultFields=getCustomFields($connection2, $guid, FALSE, TRUE, FALSE, FALSE, TRUE, NULL) ;
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
			
				if ($customRequireFail) {
					//Fail 3
					$URL.="&updateReturn=fail3" ;
					header("Location: {$URL}");
				}
				else {
					$fields=serialize($fields) ;
					
					//Write to database
					try {
						$data=array("priority"=>$priority, "status"=>$status, "milestones"=>$milestones, "dateStart"=>$dateStart, "notes"=>$notes, "surname"=>$surname, "firstName"=>$firstName, "preferredName"=>$preferredName, "officialName"=>$officialName, "nameInCharacters"=>$nameInCharacters, "gender"=>$gender, "dob"=>$dob, "languageFirst"=>$languageFirst, "languageSecond"=>$languageSecond, "languageThird"=>$languageThird, "countryOfBirth"=>$countryOfBirth, "citizenship1"=>$citizenship1, "citizenship1Passport"=>$citizenship1Passport, "nationalIDCardNumber"=>$nationalIDCardNumber, "residencyStatus"=>$residencyStatus, "visaExpiryDate"=>$visaExpiryDate, "email"=>$email, "homeAddress"=>$homeAddress, "homeAddressDistrict"=>$homeAddressDistrict, "homeAddressCountry"=>$homeAddressCountry, "phone1Type"=>$phone1Type, "phone1CountryCode"=>$phone1CountryCode, "phone1"=>$phone1, "fields"=>$fields, "gibbonStaffApplicationFormID"=>$gibbonStaffApplicationFormID); 
						$sql="UPDATE gibbonStaffApplicationForm SET priority=:priority, status=:status, milestones=:milestones, dateStart=:dateStart, notes=:notes, surname=:surname, firstName=:firstName, preferredName=:preferredName, officialName=:officialName, nameInCharacters=:nameInCharacters, gender=:gender, dob=:dob, languageFirst=:languageFirst, languageSecond=:languageSecond, languageThird=:languageThird, countryOfBirth=:countryOfBirth, citizenship1=:citizenship1, citizenship1Passport=:citizenship1Passport, nationalIDCardNumber=:nationalIDCardNumber, residencyStatus=:residencyStatus, visaExpiryDate=:visaExpiryDate, email=:email, homeAddress=:homeAddress, homeAddressDistrict=:homeAddressDistrict, homeAddressCountry=:homeAddressCountry, phone1Type=:phone1Type, phone1CountryCode=:phone1CountryCode, phone1=:phone1, fields=:fields WHERE gibbonStaffApplicationFormID=:gibbonStaffApplicationFormID" ;
						$result=$connection2->prepare($sql);
						$result->execute($data);
					}
					catch(PDOException $e) { 
						//Fail 2
						$URL.="&updateReturn=fail2" ;
						header("Location: {$URL}");
						exit() ;
					}
				
					//Success 0
					$URL.="&updateReturn=success0" ;
					header("Location: {$URL}");
				}
			}
		}
	}
}
?>