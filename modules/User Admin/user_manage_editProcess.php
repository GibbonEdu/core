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

//Module includes
include "./moduleFunctions.php" ;

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]["timezone"]);

$gibbonPersonID=$_GET["gibbonPersonID"] ;
$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/user_manage_edit.php&gibbonPersonID=$gibbonPersonID&search=" . $_GET["search"] ;

if (isActionAccessible($guid, $connection2, "/modules/User Admin/user_manage_edit.php")==FALSE) {
	//Fail 0
	$URL.="&updateReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	//Proceed!
	//Check if school year specified
	if ($gibbonPersonID=="") {
		//Fail1
		$URL.="&updateReturn=fail1" ;
		header("Location: {$URL}");
	}
	else {
		try {
			$data=array("gibbonPersonID"=>$gibbonPersonID); 
			$sql="SELECT * FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			//Fail2
			$URL.="&deleteReturn=fail2" ;
			header("Location: {$URL}");
			break ;
		}
		
		if ($result->rowCount()!=1) {
			//Fail 2
			$URL.="&updateReturn=fail2" ;
			header("Location: {$URL}");
		}
		else {
			$row=$result->fetch() ;
			
			//Get categories
			$staff=FALSE ;
			$student=FALSE ;
			$parent=FALSE ;
			$other=FALSE ;
			$roles=explode(",", $row["gibbonRoleIDAll"]) ;
			foreach ($roles AS $role) {
				$roleCategory=getRoleCategory($role, $connection2) ;
				if ($roleCategory=="Staff") {
					$staff=TRUE ;
				} 
				if ($roleCategory=="Student") {
					$student=TRUE ;
				} 
				if ($roleCategory=="Parent") {
					$parent=TRUE ;
				} 
				if ($roleCategory=="Other") {
					$other=TRUE ;
				} 
			}
		
			$attachment1=$_POST["attachment1"] ;
			$nationalIDCardScan=$_POST["nationalIDCardScanCurrent"] ;
			$citizenship1PassportScan=$_POST["citizenship1PassportScanCurrent"] ;
			
			//Proceed!
			$title=$_POST["title"] ; 	
			$surname=$_POST["surname"] ;
			$firstName=$_POST["firstName"] ;
			$preferredName=$_POST["preferredName"] ;
			$officialName=$_POST["officialName"] ;
			$nameInCharacters=$_POST["nameInCharacters"] ;
			$gender=$_POST["gender"] ;
			$username=$_POST["username"] ;
			$status=$_POST["status"] ;
			$canLogin=$_POST["canLogin"] ;
			$passwordForceReset=$_POST["passwordForceReset"] ;
			$gibbonRoleIDPrimary=$_POST["gibbonRoleIDPrimary"] ;
			$gibbonRoleIDAll="" ;
			$containsPrimary=FALSE ;
			$choices=$_POST["gibbonRoleIDAll"] ;
			if (count($choices)>0) {
				foreach ($choices as $t) {
					$gibbonRoleIDAll.=$t . "," ;
					if ($t==$gibbonRoleIDPrimary) {
						$containsPrimary=TRUE ;
					}
				}
			}
			if ($containsPrimary==FALSE) {
				$gibbonRoleIDAll=$gibbonRoleIDPrimary . "," . $gibbonRoleIDAll ;
			}
			$gibbonRoleIDAll=substr($gibbonRoleIDAll,0,-1) ;
			$dob=$_POST["dob"] ;
			if ($dob=="") {
				$dob=NULL ;
			}
			else {
				$dob=dateConvert($guid, $dob) ;
			}
			$email=$_POST["email"] ;
			$emailAlternate=$_POST["emailAlternate"] ;
			$address1=$_POST["address1"] ; 	
			$address1District=$_POST["address1District"] ; 	
			$address1Country=$_POST["address1Country"] ; 	
			$address2=$_POST["address2"] ;
			$address2District=$_POST["address2District"] ; 	
			$address2Country=$_POST["address2Country"] ; 
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
			$phone3Type=$_POST["phone3Type"] ;  
			if ($_POST["phone3"]!="" AND $phone3Type=="") {
				$phone3Type="Other" ;
			} 
			$phone3CountryCode=$_POST["phone3CountryCode"] ; 
			$phone3=preg_replace('/[^0-9+]/', '', $_POST["phone3"]) ; 
			$phone4Type=$_POST["phone4Type"] ; 
			if ($_POST["phone4"]!="" AND $phone4Type=="") {
				$phone4Type="Other" ;
			}  
			$phone4CountryCode=$_POST["phone4CountryCode"] ; 
			$phone4=preg_replace('/[^0-9+]/', '', $_POST["phone4"]) ; 
			$website=$_POST["website"] ;
			$languageFirst=$_POST["languageFirst"] ;
			$languageSecond=$_POST["languageSecond"] ;
			$languageThird=$_POST["languageThird"] ;
			$countryOfBirth=$_POST["countryOfBirth"] ;
			$ethnicity=$_POST["ethnicity"] ; 
			$citizenship1=$_POST["citizenship1"] ;
			$citizenship1Passport=$_POST["citizenship1Passport"] ;
			$citizenship2=$_POST["citizenship2"] ; 
			$citizenship2Passport=$_POST["citizenship2Passport"] ;
			$religion=$_POST["religion"] ;
			$nationalIDCardNumber=$_POST["nationalIDCardNumber"] ;
			$residencyStatus=$_POST["residencyStatus"] ;
			$visaExpiryDate=$_POST["visaExpiryDate"] ;
			if ($visaExpiryDate=="") {
				$visaExpiryDate=NULL ;
			}
			else {
				$visaExpiryDate=dateConvert($guid, $visaExpiryDate) ;
			}
			
			$profession=NULL ;
			if (isset($_POST["profession"])) {
				$profession=$_POST["profession"] ;
			}
			
			$employer=NULL ;
			if (isset($_POST["employer"])) {
				$employer=$_POST["employer"] ;
			}
			
			$jobTitle=NULL ;
			if (isset($_POST["jobTitle"])) {
				$jobTitle=$_POST["jobTitle"] ;
			}
			
			$emergency1Name=NULL ;
			if (isset($_POST["emergency1Name"])) {
				$emergency1Name=$_POST["emergency1Name"] ;
			}
			$emergency1Number1=NULL ;
			if (isset($_POST["emergency1Number1"])) {
				$emergency1Number1=$_POST["emergency1Number1"] ;
			}
			$emergency1Number2=NULL ;
			if (isset($_POST["emergency1Number2"])) {
				$emergency1Number2=$_POST["emergency1Number2"] ;
			}
			$emergency1Relationship=NULL ;
			if (isset($_POST["emergency1Relationship"])) {
				$emergency1Relationship=$_POST["emergency1Relationship"] ;
			}
			$emergency2Name=NULL ;
			if (isset($_POST["emergency2Name"])) {
				$emergency2Name=$_POST["emergency2Name"] ;
			}
			$emergency2Number1=NULL ;
			if (isset($_POST["emergency2Number1"])) {
				$emergency2Number1=$_POST["emergency2Number1"] ;
			}
			$emergency2Number2=NULL ;
			if (isset($_POST["emergency2Number2"])) {
				$emergency2Number2=$_POST["emergency2Number2"] ;
			}
			$emergency2Relationship=NULL ;
			if (isset($_POST["emergency2Relationship"])) {
				$emergency2Relationship=$_POST["emergency2Relationship"] ;
			}
			
			
			$gibbonHouseID=$_POST["gibbonHouseID"] ;
			if ($gibbonHouseID=="") {
				$gibbonHouseID=NULL ;
			}
			$studentID=NULL ;
			if (isset($_POST["studentID"])) {
				$studentID=$_POST["studentID"] ;
			}
			$dateStart=$_POST["dateStart"] ;
			if ($dateStart=="") {
				$dateStart=NULL ;
			}
			else {
				$dateStart=dateConvert($guid, $dateStart) ;
			}
			$dateEnd=$_POST["dateEnd"] ;
			if ($dateEnd=="") {
				$dateEnd=NULL ;
			}
			else {
				$dateEnd=dateConvert($guid, $dateEnd) ;
			}
			$gibbonSchoolYearIDClassOf=NULL ;
			if (isset($_POST["gibbonSchoolYearIDClassOf"])) {
				$gibbonSchoolYearIDClassOf=$_POST["gibbonSchoolYearIDClassOf"] ;
			}
			$lastSchool=NULL ;
			if (isset($_POST["lastSchool"])) {
				$lastSchool=$_POST["lastSchool"] ;
			}
			$nextSchool=NULL ;
			if (isset($_POST["nextSchool"])) {
				$nextSchool=$_POST["nextSchool"] ;
			}
			$departureReason=NULL ;
			if (isset($_POST["departureReason"])) {
				$departureReason=$_POST["departureReason"] ;
			}
			$transport=NULL ;
			if (isset($_POST["transport"])) {
				$transport=$_POST["transport"] ;
			}
			$transportNotes=NULL ;
			if (isset($_POST["transportNotes"])) {
				$transportNotes=$_POST["transportNotes"] ;
			}
			$lockerNumber=NULL ;
			if (isset($_POST["lockerNumber"])) {
				$lockerNumber=$_POST["lockerNumber"] ;
			}
			
			$vehicleRegistration=$_POST["vehicleRegistration"] ;
			$privacyOptions=NULL ;
			$privacy="" ;
			if (isset($_POST["privacyOptions"])) {
				$privacyOptions=$_POST["privacyOptions"] ;
				foreach ($privacyOptions AS $privacyOption) {
					if ($privacyOption!="") {
						$privacy.=$privacyOption . "," ;
					}
				}
			}
			if ($privacy!="") {
				$privacy=substr($privacy,0,-1) ;
			}
			else {
				$privacy=NULL ;
			}
			$studentAgreements=NULL ;
			$agreements="" ;
			if (isset($_POST["studentAgreements"])) {
				$studentAgreements=$_POST["studentAgreements"] ;
				foreach ($studentAgreements AS $studentAgreement) {
					if ($studentAgreement!="") {
						$agreements.=$studentAgreement . "," ;
					}
				}
			}
			if ($agreements!="") {
				$agreements=substr($agreements,0,-1) ;
			}
			else {
				$agreements=NULL ;
			}
			
			$dayType=NULL ;
			if (isset($_POST["dayType"])) {
				$dayType=$_POST["dayType"] ;
			}
			
			//Validate Inputs
			if ($surname=="" OR $firstName=="" OR $preferredName=="" OR $officialName=="" OR $gender=="" OR $username=="" OR $status=="" OR $gibbonRoleIDPrimary=="") {
				//Fail 3
				$URL.="&updateReturn=fail3" ;
				header("Location: {$URL}");
			}
			else {
				//Check unique inputs for uniquness
				try {
					$data=array("username"=>$username, "gibbonPersonID"=>$gibbonPersonID); 
					$sql="SELECT * FROM gibbonPerson WHERE username=:username AND NOT gibbonPersonID=:gibbonPersonID" ;
					if ($studentID!="") {
						$data=array("username"=>$username, "gibbonPersonID"=>$gibbonPersonID, "studentID"=>$studentID); 
						$sql="SELECT * FROM gibbonPerson WHERE (username=:username OR studentID=:studentID) AND NOT gibbonPersonID=:gibbonPersonID " ;
					}
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					//Fail 2
					$URL.="&updateReturn=fail2" ;
					header("Location: {$URL}");
					break ;
				}
				
				if ($result->rowCount()>0) {
					//Fail 4
					$URL.="&updateReturn=fail4" ;
					header("Location: {$URL}");
				}
				else {
					$imageFail=FALSE ;
					if ($_FILES['file1']["tmp_name"]!="" OR $_FILES['nationalIDCardScan']["tmp_name"]!="" OR $_FILES['citizenship1PassportScan']["tmp_name"]!="") {
						$time=time() ;
						//Check for folder in uploads based on today's date
						$path=$_SESSION[$guid]["absolutePath"];
						if (is_dir($path ."/uploads/" . date("Y", $time) . "/" . date("m", $time))==FALSE) {
							mkdir($path ."/uploads/" . date("Y", $time) . "/" . date("m", $time), 0777, TRUE) ;
						}
						//Move 240 attached file, if there is one
						if ($_FILES['file1']["tmp_name"]!="") {
							$unique=FALSE;
							$count=0 ;
							while ($unique==FALSE AND $count<100) {
								if ($count==0) {
									$attachment1="uploads/" . date("Y", $time) . "/" . date("m", $time) . "/" . $username . "_240" . strrchr($_FILES["file1"]["name"], ".") ;
								}
								else {
									$attachment1="uploads/" . date("Y", $time) . "/" . date("m", $time) . "/" . $username . "_240" . "_$count" . strrchr($_FILES["file1"]["name"], ".") ;
								}
								
								if (!(file_exists($path . "/" . $attachment1))) {
									$unique=TRUE ;
								}
								$count++ ;
							}
							if (!(move_uploaded_file($_FILES["file1"]["tmp_name"],$path . "/" . $attachment1))) {
								$attachment1="" ;
								$imageFail=TRUE ;
							}
						}
						
						//Move ID Card scan file, if there is one
						if ($_FILES['nationalIDCardScan']["tmp_name"]!="") {
							$unique=FALSE;
							$count=0 ;
							while ($unique==FALSE AND $count<100) {
								$suffix=randomPassword(16) ;
								if ($count==0) {
									$nationalIDCardScan="uploads/" . date("Y", $time) . "/" . date("m", $time) . "/" . $username . "_idscan_" . $suffix ;
								}
								else {
									$nationalIDCardScan="uploads/" . date("Y", $time) . "/" . date("m", $time) . "/" . $username . "_idscan" . "_$count_" . $suffix ;
								}
								
								if (!(file_exists($path . "/" . $nationalIDCardScan))) {
									$unique=TRUE ;
								}
								$count++ ;
							}
							if (!(move_uploaded_file($_FILES["nationalIDCardScan"]["tmp_name"],$path . "/" . $nationalIDCardScan))) {
								$nationalIDCardScan="" ;
								$imageFail=TRUE ;
							}
						}
						
						//Move passport scan file, if there is one
						if ($_FILES['citizenship1PassportScan']["tmp_name"]!="") {
							$unique=FALSE;
							$count=0 ;
							while ($unique==FALSE AND $count<100) {
								$suffix=randomPassword(16) ;
								if ($count==0) {
									$citizenship1PassportScan="uploads/" . date("Y", $time) . "/" . date("m", $time) . "/" . $username . "_passportscan_" . $suffix ;
								}
								else {
									$citizenship1PassportScan="uploads/" . date("Y", $time) . "/" . date("m", $time) . "/" . $username . "_passportscan_" . "_$count_" . $suffix ;
								}
								
								if (!(file_exists($path . "/" . $citizenship1PassportScan))) {
									$unique=TRUE ;
								}
								$count++ ;
							}
							if (!(move_uploaded_file($_FILES["citizenship1PassportScan"]["tmp_name"],$path . "/" . $citizenship1PassportScan))) {
								$citizenship1PassportScan="" ;
								$imageFail=TRUE ;
							}
						}
						
						//Check image sizes
						if ($attachment1!="") {
							$size1=getimagesize($path . "/" . $attachment1) ;
							$width1=$size1[0] ;
							$height1=$size1[1] ;
							$aspect1=$height1/$width1 ;
							if ($width1>360 OR $height1>480 OR $aspect1<1.2 OR $aspect1>1.4) {
								$attachment1="" ;
								$imageFail=TRUE ;
							}
						}
						if ($nationalIDCardScan!="") {
							$size3=getimagesize($path . "/" . $nationalIDCardScan) ;
							$width3=$size3[0] ;
							$height3=$size3[1] ;
							if ($width3>1440 OR $height3>900) {
								$nationalIDCardScan="" ;
								$imageFail=TRUE ;
							}
						}
						if ($citizenship1PassportScan!="") {
							$size4=getimagesize($path . "/" . $citizenship1PassportScan) ;
							$width4=$size4[0] ;
							$height4=$size4[1] ;
							if ($width4>1440 OR $height4>900) {
								$citizenship1PassportScan="" ;
								$imageFail=TRUE ;
							}
						}
					}
					
					//DEAL WITH CUSTOM FIELDS
					//Prepare field values
					$customRequireFail=FALSE ;
					$resultFields=getCustomFields($connection2, $guid, $student, $staff, $parent, $other) ;
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
							$data=array("title"=>$title, "surname"=>$surname, "firstName"=>$firstName, "preferredName"=>$preferredName, "officialName"=>$officialName, "nameInCharacters"=>$nameInCharacters, "gender"=>$gender, "status"=>$status, "canLogin"=>$canLogin, "passwordForceReset"=>$passwordForceReset, "gibbonRoleIDPrimary"=>$gibbonRoleIDPrimary, "gibbonRoleIDAll"=>$gibbonRoleIDAll, "dob"=>$dob, "email"=>$email, "emailAlternate"=>$emailAlternate, "address1"=>$address1, "address1District"=>$address1District, "address1Country"=>$address1Country, "address2"=>$address2, "address2District"=>$address2District, "address2Country"=>$address2Country, "phone1Type"=>$phone1Type, "phone1CountryCode"=>$phone1CountryCode, "phone1"=>$phone1, "phone2Type"=>$phone2Type, "phone2CountryCode"=>$phone2CountryCode, "phone2"=>$phone2, "phone3Type"=>$phone3Type, "phone3CountryCode"=>$phone3CountryCode, "phone3"=>$phone3, "phone4Type"=>$phone4Type, "phone4CountryCode"=>$phone4CountryCode, "phone4"=>$phone4, "website"=>$website, "languageFirst"=>$languageFirst, "languageSecond"=>$languageSecond, "languageThird"=>$languageThird, "countryOfBirth"=>$countryOfBirth, "ethnicity"=>$ethnicity, "citizenship1"=>$citizenship1, "citizenship1Passport"=>$citizenship1Passport, "citizenship1PassportScan"=>$citizenship1PassportScan, "citizenship2"=>$citizenship2, "citizenship2Passport"=>$citizenship2Passport, "religion"=>$religion, "nationalIDCardNumber"=>$nationalIDCardNumber, "nationalIDCardScan"=>$nationalIDCardScan, "residencyStatus"=>$residencyStatus, "visaExpiryDate"=>$visaExpiryDate, "emergency1Name"=>$emergency1Name, "emergency1Number1"=>$emergency1Number1, "emergency1Number2"=>$emergency1Number2, "emergency1Relationship"=>$emergency1Relationship, "emergency2Name"=>$emergency2Name, "emergency2Number1"=>$emergency2Number1, "emergency2Number2"=>$emergency2Number2, "emergency2Relationship"=>$emergency2Relationship, "profession"=>$profession, "employer"=>$employer, "jobTitle"=>$jobTitle, "attachment1"=>$attachment1, "gibbonHouseID"=>$gibbonHouseID, "studentID"=>$studentID, "dateStart"=>$dateStart, "dateEnd"=>$dateEnd, "gibbonSchoolYearIDClassOf"=>$gibbonSchoolYearIDClassOf, "lastSchool"=>$lastSchool, "nextSchool"=>$nextSchool, "departureReason"=>$departureReason, "transport"=>$transport, "transportNotes"=>$transportNotes, "lockerNumber"=>$lockerNumber, "vehicleRegistration"=>$vehicleRegistration, "privacy"=>$privacy, "agreements"=>$agreements, "dayType"=>$dayType, "fields"=>$fields, "gibbonPersonID"=>$gibbonPersonID) ;
							$sql="UPDATE gibbonPerson SET title=:title, surname=:surname, firstName=:firstName, preferredName=:preferredName, officialName=:officialName, nameInCharacters=:nameInCharacters, gender=:gender, status=:status, canLogin=:canLogin, passwordForceReset=:passwordForceReset, gibbonRoleIDPrimary=:gibbonRoleIDPrimary, gibbonRoleIDAll=:gibbonRoleIDAll, dob=:dob, email=:email, emailAlternate=:emailAlternate, address1=:address1, address1District=:address1District, address1Country=:address1Country, address2=:address2, address2District=:address2District, address2Country=:address2Country, phone1Type=:phone1Type, phone1CountryCode=:phone1CountryCode, phone1=:phone1, phone2Type=:phone2Type, phone2CountryCode=:phone2CountryCode, phone2=:phone2, phone3Type=:phone3Type, phone3CountryCode=:phone3CountryCode, phone3=:phone3, phone4Type=:phone4Type, phone4CountryCode=:phone4CountryCode, phone4=:phone4, website=:website, languageFirst=:languageFirst, languageSecond=:languageSecond, languageThird=:languageThird, countryOfBirth=:countryOfBirth, ethnicity=:ethnicity,  citizenship1=:citizenship1, citizenship1Passport=:citizenship1Passport, citizenship1PassportScan=:citizenship1PassportScan, citizenship2=:citizenship2,  citizenship2Passport=:citizenship2Passport, religion=:religion, nationalIDCardNumber=:nationalIDCardNumber, nationalIDCardScan=:nationalIDCardScan, residencyStatus=:residencyStatus, visaExpiryDate=:visaExpiryDate, emergency1Name=:emergency1Name, emergency1Number1=:emergency1Number1, emergency1Number2=:emergency1Number2, emergency1Relationship=:emergency1Relationship, emergency2Name=:emergency2Name, emergency2Number1=:emergency2Number1, emergency2Number2=:emergency2Number2, emergency2Relationship=:emergency2Relationship, profession=:profession, employer=:employer, jobTitle=:jobTitle, image_240=:attachment1, gibbonHouseID=:gibbonHouseID, studentID=:studentID, dateStart=:dateStart, dateEnd=:dateEnd, gibbonSchoolYearIDClassOf=:gibbonSchoolYearIDClassOf, lastSchool=:lastSchool, nextSchool=:nextSchool, departureReason=:departureReason, transport=:transport, transportNotes=:transportNotes, lockerNumber=:lockerNumber, vehicleRegistration=:vehicleRegistration, privacy=:privacy, studentAgreements=:agreements, dayType=:dayType, fields=:fields WHERE gibbonPersonID=:gibbonPersonID" ;
							$result=$connection2->prepare($sql);
							$result->execute($data);
						}
						catch(PDOException $e) { 
							//Fail 2
							$URL.="&updateReturn=fail2" ;
							header("Location: {$URL}");
							break ;
						}
					
						//Update matching addresses
						$partialFail=false ;
						$matchAddressCount=NULL ;
						if (isset($_POST["matchAddressCount"])) {
							$matchAddressCount=$_POST["matchAddressCount"] ;
						}
						if ($matchAddressCount>0) {
							for ($i=0; $i<$matchAddressCount; $i++) {
								if ($_POST[$i . "-matchAddress"]!="") {
									try {
										$dataAddress=array("address1"=>$address1, "address1District"=>$address1District, "address1Country"=>$address1Country, "gibbonPersonID"=>$_POST[$i . "-matchAddress"]); 
										$sqlAddress="UPDATE gibbonPerson SET address1=:address1, address1District=:address1District, address1Country=:address1Country WHERE gibbonPersonID=:gibbonPersonID" ;
										$resultAddress=$connection2->prepare($sqlAddress);
										$resultAddress->execute($dataAddress);
									}
									catch(PDOException $e) { 
										$partialFail=true ;
									}
								}
							}
						}
						if ($partialFail==TRUE) {
							//Fail 5
							$URL.="&updateReturn=fail5" ;
							header("Location: {$URL}");
						}
						else {
							if ($imageFail) {
								//Success 1
								$URL.="&updateReturn=success1" ;
								header("Location: {$URL}");
							}
							else {
								//Success 0
								$URL.="&updateReturn=success0" ;
								header("Location: {$URL}");
							}
						}
					}
				}
			}
		}
	}
}
?>