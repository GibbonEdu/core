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
$pdo = new sqlConnection();
$connection2 = $pdo->getConnection();

@session_start() ;

//Module includes from User Admin (for custom fields)
include "../User Admin/moduleFunctions.php" ;

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]["timezone"]);

$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Staff/applicationForm.php" ;

$proceed=FALSE ;
$public=FALSE ;
if (isset($_SESSION[$guid]["username"])==FALSE) {
	$public=TRUE ;
	$proceed=TRUE ;
}
else {
	if (isActionAccessible($guid, $connection2, "/modules/Staff/applicationForm.php")!=FALSE) {
		$proceed=TRUE ;
	}
}

if ($proceed==FALSE) {
	//Fail 0
	$URL.="&addReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	//Proceed!
	$gibbonStaffJobOpeningIDs=$_POST["gibbonStaffJobOpeningID"] ;
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
		$visaExpiryDate=dateConvert($guid, $visaExpiryDate) ;
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
	$agreement=NULL ;
	if (isset($_POST["agreement"])) {
		if ($_POST["agreement"]=="on") {
			$agreement="Y" ;
		}
		else {
			$agreement="N" ;
		}
	}

	//VALIDATE INPUTS
	if (count($gibbonStaffJobOpeningIDs)<1 OR ($gibbonPersonID==NULL AND ($surname=="" OR $firstName=="" OR $preferredName=="" OR $officialName=="" OR $gender=="" OR $dob=="" OR $languageFirst=="" OR $email=="" OR $homeAddress=="" OR $homeAddressDistrict=="" OR $homeAddressCountry=="" OR $phone1=="")) OR (isset($_POST["agreement"]) AND $agreement!="Y")) {
		//Fail 3
		$URL.="&addReturn=fail3" ;
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
			$URL.="&addReturn=fail3" ;
			header("Location: {$URL}");
		}
		else {
			$fields=serialize($fields) ;
			$partialFail=FALSE ;
			$ids="" ;
			
			//Submit one copy for each job opening checking
			foreach ($gibbonStaffJobOpeningIDs AS $gibbonStaffJobOpeningID) {
				$thisFail=FALSE ;
				
				//Write to database
				try {
					$data=array("gibbonStaffJobOpeningID"=>$gibbonStaffJobOpeningID, "questions"=>$questions, "gibbonPersonID"=>$gibbonPersonID, "surname"=>$surname, "firstName"=>$firstName, "preferredName"=>$preferredName, "officialName"=>$officialName, "nameInCharacters"=>$nameInCharacters, "gender"=>$gender, "dob"=>$dob, "languageFirst"=>$languageFirst, "languageSecond"=>$languageSecond, "languageThird"=>$languageThird, "countryOfBirth"=>$countryOfBirth, "citizenship1"=>$citizenship1, "citizenship1Passport"=>$citizenship1Passport, "nationalIDCardNumber"=>$nationalIDCardNumber, "residencyStatus"=>$residencyStatus, "visaExpiryDate"=>$visaExpiryDate, "email"=>$email, "homeAddress"=>$homeAddress, "homeAddressDistrict"=>$homeAddressDistrict, "homeAddressCountry"=>$homeAddressCountry, "phone1Type"=>$phone1Type, "phone1CountryCode"=>$phone1CountryCode, "phone1"=>$phone1, "agreement"=>$agreement, "fields"=>$fields, "timestamp"=>date("Y-m-d H:i:s")); 
					$sql="INSERT INTO gibbonStaffApplicationForm SET gibbonStaffJobOpeningID=:gibbonStaffJobOpeningID, questions=:questions, gibbonPersonID=:gibbonPersonID, surname=:surname, firstName=:firstName, preferredName=:preferredName, officialName=:officialName, nameInCharacters=:nameInCharacters, gender=:gender, dob=:dob, languageFirst=:languageFirst, languageSecond=:languageSecond, languageThird=:languageThird, countryOfBirth=:countryOfBirth, citizenship1=:citizenship1, citizenship1Passport=:citizenship1Passport, nationalIDCardNumber=:nationalIDCardNumber, residencyStatus=:residencyStatus, visaExpiryDate=:visaExpiryDate, email=:email, homeAddress=:homeAddress, homeAddressDistrict=:homeAddressDistrict, homeAddressCountry=:homeAddressCountry, phone1Type=:phone1Type, phone1CountryCode=:phone1CountryCode, phone1=:phone1, agreement=:agreement, fields=:fields, timestamp=:timestamp" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print $e->getMessage() ; exit() ;
					$partialFail=TRUE ;
					$thisFail=TRUE ;
				}
	
				if (!$thisFail) {
					//Last insert ID
					$AI=str_pad($connection2->lastInsertID(), 7, "0", STR_PAD_LEFT) ;
					$ids.=$AI . ", " ;
					
					//Deal with required documents
					$requiredDocuments=getSettingByScope($connection2, "Staff", "staffApplicationFormRequiredDocuments") ;
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
									$dataFile=array("gibbonStaffApplicationFormID"=>$AI, "name"=>$fileName, "path"=>$attachment); 
									$sqlFile="INSERT INTO gibbonStaffApplicationFormFile SET gibbonStaffApplicationFormID=:gibbonStaffApplicationFormID, name=:name, path=:path" ;
									$resultFile=$connection2->prepare($sqlFile);
									$resultFile->execute($dataFile);
								}
								catch(PDOException $e) { }
							}
						}
					}
		
					//Attempt to notify admissions administrator
					if ($_SESSION[$guid]["organisationHR"]) {
						$notificationText=sprintf(__($guid, 'An application form has been submitted for %1$s.'), formatName("", $preferredName, $surname, "Student")) ;
						setNotification($connection2, $guid, $_SESSION[$guid]["organisationHR"], $notificationText, "Staff Application Form", "/index.php?q=/modules/Staff/applicationForm_manage_edit.php&gibbonStaffApplicationFormID=$AI&search=") ;
					}
				}
			}
			
			if ($ids!="" ) {
				$ids=substr($ids, 0, -2) ;
			}
			
			if ($partialFail==TRUE) {
				//Fail 5
				$URL.="&addReturn=success1&id=$ids" ;
				header("Location: {$URL}");
			}
			else {
				//Success 0
				$URL.="&addReturn=success0&id=$ids" ;
				header("Location: {$URL}");
			}
		}
	}
}
?>