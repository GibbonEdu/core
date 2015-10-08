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

$gibbonPersonUpdateID=$_GET["gibbonPersonUpdateID"] ;
$gibbonPersonID=$_POST["gibbonPersonID"] ;
$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/data_personal_edit.php&gibbonPersonUpdateID=$gibbonPersonUpdateID" ;

if (isActionAccessible($guid, $connection2, "/modules/User Admin/data_personal_edit.php")==FALSE) {
	//Fail 0
	$URL.="&updateReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	//Proceed!
	//Check if school year specified
	if ($gibbonPersonUpdateID=="" OR $gibbonPersonID=="") {
		//Fail1
		$URL.="&updateReturn=fail1" ;
		header("Location: {$URL}");
	}
	else {
		try {
			$data=array("gibbonPersonUpdateID"=>$gibbonPersonUpdateID); 
			$sql="SELECT * FROM gibbonPersonUpdate WHERE gibbonPersonUpdateID=:gibbonPersonUpdateID" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			//Fail2
			$URL.="&updateReturn=fail2" ;
			header("Location: {$URL}");
			break ;
		}
		
		if ($result->rowCount()!=1) {
			//Fail 2
			$URL.="&updateReturn=fail2" ;
			header("Location: {$URL}");
		}
		else {
			try {
				$data2=array("gibbonPersonID"=>$gibbonPersonID); 
				$sql2="SELECT * FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID" ;
				$result2=$connection2->prepare($sql2);
				$result2->execute($data2);
			}
			catch(PDOException $e) { 
				//Fail2
				$URL.="&updateReturn=fail2" ;
				header("Location: {$URL}");
				break ;
			}
		
			if ($result2->rowCount()!=1) {
				//Fail 2
				$URL.="&updateReturn=fail2" ;
				header("Location: {$URL}");
			}
			else {
				$row2=$result2->fetch() ;
			
				//Get categories
				$staff=FALSE ;
				$student=FALSE ;
				$parent=FALSE ;
				$other=FALSE ;
				$roles=explode(",", $row2["gibbonRoleIDAll"]) ;
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
				
				//Set values
				$data=array(); 
				$set="" ;
				if (isset($_POST["newtitleOn"])) {
					if ($_POST["newtitleOn"]=="on") {
						$data["title"]=$_POST["newtitle"] ;
						$set.="gibbonPerson.title=:title, " ;
					}
				}
				if (isset($_POST["newsurnameOn"])) {
					if ($_POST["newsurnameOn"]=="on") {
						$data["surname"]=$_POST["newsurname"] ;
						$set.="gibbonPerson.surname=:surname, " ;
					}
				}
				if (isset($_POST["newfirstNameOn"])) {
					if ($_POST["newfirstNameOn"]=="on") {
						$data["firstName"]=$_POST["newfirstName"] ;
						$set.="gibbonPerson.firstName=:firstName, " ;
					}
				}
				if (isset($_POST["newpreferredNameOn"])) {
					if ($_POST["newpreferredNameOn"]=="on") {
						$data["preferredName"]=$_POST["newpreferredName"] ;
						$set.="gibbonPerson.preferredName=:preferredName, " ;
					}
				}
				if (isset($_POST["newofficialNameOn"])) {
					if ($_POST["newofficialNameOn"]=="on") {
						$data["officialName"]=$_POST["newofficialName"] ;
						$set.="gibbonPerson.officialName=:officialName, " ;
					}
				}
				if (isset($_POST["newnameInCharactersOn"])) {
					if ($_POST["newnameInCharactersOn"]=="on") {
						$data["nameInCharacters"]=$_POST["newnameInCharacters"] ;
						$set.="gibbonPerson.nameInCharacters=:nameInCharacters, " ;
					}
				}
				if (isset($_POST["newdobOn"])) {
					if ($_POST["newdobOn"]=="on") {
						$data["dob"]=$_POST["newdob"] ;
						$set.="gibbonPerson.dob=:dob, " ;
					}
				}
				if (isset($_POST["newemailOn"])) {
					if ($_POST["newemailOn"]=="on") {
						$data["email"]=$_POST["newemail"] ;
						$set.="gibbonPerson.email=:email, " ;
					}
				}
				if (isset($_POST["newemailAlternateOn"])) {
					if ($_POST["newemailAlternateOn"]=="on") {
						$data["emailAlternate"]=$_POST["newemailAlternate"] ;
						$set.="gibbonPerson.emailAlternate=:emailAlternate, " ;
					}
				}
				if (isset($_POST["newaddress1On"])) {
					if ($_POST["newaddress1On"]=="on") {
						$data["address1"]=$_POST["newaddress1"] ;
						$set.="gibbonPerson.address1=:address1, " ;
					}
				}
				if (isset($_POST["newaddress1DistrictOn"])) {
					if ($_POST["newaddress1DistrictOn"]=="on") {
						$data["address1District"]=$_POST["newaddress1District"] ;
						$set.="gibbonPerson.address1District=:address1District, " ;
					}
				}
				if (isset($_POST["newaddress1CountryOn"])) {
					if ($_POST["newaddress1CountryOn"]=="on") {
						$data["address1Country"]=$_POST["newaddress1Country"] ;
						$set.="gibbonPerson.address1Country=:address1Country, " ;
					}
				}
				if (isset($_POST["newaddress2On"])) {
					if ($_POST["newaddress2On"]=="on") {
						$data["address2"]=$_POST["newaddress2"] ;
						$set.="gibbonPerson.address2=:address2, " ;
					}
				}
				if (isset($_POST["newaddress2DistrictOn"])) {
					if ($_POST["newaddress2DistrictOn"]=="on") {
						$data["address2District"]=$_POST["newaddress2District"] ;
						$set.="gibbonPerson.address2District=:address2District, " ;
					}
				}
				if (isset($_POST["newaddress2CountryOn"])) {
					if ($_POST["newaddress2CountryOn"]=="on") {
						$data["address2Country"]=$_POST["newaddress2Country"] ;
						$set.="gibbonPerson.address2Country=:address2Country, " ;
					}
				}
				if (isset($_POST["newphone1TypeOn"])) {
					if ($_POST["newphone1TypeOn"]=="on") {
						$data["phone1Type"]=$_POST["newphone1Type"] ;
						$set.="gibbonPerson.phone1Type=:phone1Type, " ;
					}
				}
				if (isset($_POST["newphone1CountryCodeOn"])) {
					if ($_POST["newphone1CountryCodeOn"]=="on") {
						$data["phone1CountryCode"]=$_POST["newphone1CountryCode"] ;
						$set.="gibbonPerson.phone1CountryCode=:phone1CountryCode, " ;
					}
				}
				if (isset($_POST["newphone1On"])) {
					if ($_POST["newphone1On"]=="on") {
						$data["phone1"]=$_POST["newphone1"] ;
						$set.="gibbonPerson.phone1=:phone1, " ;
					}
				}
				if (isset($_POST["newphone2TypeOn"])) {
					if ($_POST["newphone2TypeOn"]=="on") {
						$data["phone2Type"]=$_POST["newphone2Type"] ;
						$set.="gibbonPerson.phone2Type=:phone2Type, " ;
					}
				}
				if (isset($_POST["newphone2CountryCodeOn"])) {
					if ($_POST["newphone2CountryCodeOn"]=="on") {
						$data["phone2CountryCode"]=$_POST["newphone2CountryCode"] ;
						$set.="gibbonPerson.phone2CountryCode=:phone2CountryCode, " ;
					}
				}
				if (isset($_POST["newphone2On"])) {
					if ($_POST["newphone2On"]=="on") {
						$data["phone2"]=$_POST["newphone2"] ;
						$set.="gibbonPerson.phone2=:phone2, " ;
					}
				}
				if (isset($_POST["newphone3TypeOn"])) {
					if ($_POST["newphone3TypeOn"]=="on") {
						$data["phone3Type"]=$_POST["newphone3Type"] ;
						$set.="gibbonPerson.phone3Type=:phone3Type, " ;
					}
				}
				if (isset($_POST["newphone3CountryCodeOn"])) {
					if ($_POST["newphone3CountryCodeOn"]=="on") {
						$data["phone3CountryCode"]=$_POST["newphone3CountryCode"] ;
						$set.="gibbonPerson.phone3CountryCode=:phone3CountryCode, " ;
					}
				}
				if (isset($_POST["newphone3On"])) {
					if ($_POST["newphone3On"]=="on") {
						$data["phone3"]=$_POST["newphone3"] ;
						$set.="gibbonPerson.phone3=:phone3, " ;
					}
				}
				if (isset($_POST["newphone4TypeOn"])) {
					if ($_POST["newphone4TypeOn"]=="on") {
						$data["phone4Type"]=$_POST["newphone4Type"] ;
						$set.="gibbonPerson.phone4Type=:phone4Type, " ;
					}
				}
				if (isset($_POST["newphone4CountryCodeOn"])) {
					if ($_POST["newphone4CountryCodeOn"]=="on") {
						$data["phone4CountryCode"]=$_POST["newphone4CountryCode"] ;
						$set.="gibbonPerson.phone4CountryCode=:phone4CountryCode, " ;
					}
				}
				if (isset($_POST["newphone4On"])) {
					if ($_POST["newphone4On"]=="on") {
						$data["phone4"]=$_POST["newphone4"] ;
						$set.="gibbonPerson.phone4=:phone4, " ;
					}	
				}
				if (isset($_POST["newlanguageFirstOn"])) {
					if ($_POST["newlanguageFirstOn"]=="on") {
						$data["languageFirst"]=$_POST["newlanguageFirst"] ;
						$set.="gibbonPerson.languageFirst=:languageFirst, " ;
					}
				}
				if (isset($_POST["newlanguageSecondOn"])) {
					if ($_POST["newlanguageSecondOn"]=="on") {
						$data["languageSecond"]=$_POST["newlanguageSecond"] ;
						$set.="gibbonPerson.languageSecond=:languageSecond, " ;
					}
				}
				if (isset($_POST["newlanguageThirdOn"])) {
					if ($_POST["newlanguageThirdOn"]=="on") {
						$data["languageThird"]=$_POST["newlanguageThird"] ;
						$set.="gibbonPerson.languageThird=:languageThird, " ;
					}
				}
				if (isset($_POST["newcountryOfBirthOn"])) {
					if ($_POST["newcountryOfBirthOn"]=="on") {
						$data["countryOfBirth"]=$_POST["newcountryOfBirth"] ;
						$set.="gibbonPerson.countryOfBirth=:countryOfBirth, " ;
					}
				}
				if (isset($_POST["newethnicityOn"])) {
					if ($_POST["newethnicityOn"]=="on") {
						$data["ethnicity"]=$_POST["newethnicity"] ;
						$set.="gibbonPerson.ethnicity=:ethnicity, " ;
					}
				}
				if (isset($_POST["newcitizenship1On"])) {
					if ($_POST["newcitizenship1On"]=="on") {
						$data["citizenship1"]=$_POST["newcitizenship1"] ;
						$set.="gibbonPerson.citizenship1=:citizenship1, " ;
					}
				}
				if (isset($_POST["newcitizenship1PassportOn"])) {
					if ($_POST["newcitizenship1PassportOn"]=="on") {
						$data["citizenship1Passport"]=$_POST["newcitizenship1Passport"] ;
						$set.="gibbonPerson.citizenship1Passport=:citizenship1Passport, " ;
					}
				}
				if (isset($_POST["newcitizenship2On"])) {
					if ($_POST["newcitizenship2On"]=="on") {
						$data["citizenship2"]=$_POST["newcitizenship2"] ;
						$set.="gibbonPerson.citizenship2=:citizenship2, " ;
					}
				}
				if (isset($_POST["newcitizenship2PassportOn"])) {
					if ($_POST["newcitizenship2PassportOn"]=="on") {
						$data["citizenship2Passport"]=$_POST["newcitizenship2Passport"] ;
						$set.="gibbonPerson.citizenship2Passport=:citizenship2Passport, " ;
					}
				}
				if (isset($_POST["newreligionOn"])) {
					if ($_POST["newreligionOn"]=="on") {
						$data["religion"]=$_POST["newreligion"] ;
						$set.="gibbonPerson.religion=:religion, " ;
					}
				}
				if (isset($_POST["newnationalIDCardNumberOn"])) {
					if ($_POST["newnationalIDCardNumberOn"]=="on") {
						$data["nationalIDCardNumber"]=$_POST["newnationalIDCardNumber"] ;
						$set.="gibbonPerson.nationalIDCardNumber=:nationalIDCardNumber, " ;
					}
				}
				if (isset($_POST["newresidencyStatusOn"])) {
					if ($_POST["newresidencyStatusOn"]=="on") {
						$data["residencyStatus"]=$_POST["newresidencyStatus"] ;
						$set.="gibbonPerson.residencyStatus=:residencyStatus, " ;
					}
				}
				if (isset($_POST["newvisaExpiryDateOn"])) {
					if ($_POST["newvisaExpiryDateOn"]=="on") {
						$data["visaExpiryDate"]=$_POST["newvisaExpiryDate"] ;
						$set.="gibbonPerson.visaExpiryDate=:visaExpiryDate, " ;
					}
				}
				if (isset($_POST["newprofessionOn"])) {
					if ($_POST["newprofessionOn"]=="on") {
						$data["profession"]=$_POST["newprofession"] ;
						$set.="gibbonPerson.profession=:profession, " ;
					}
				}
				if (isset($_POST["newemployerOn"])) {
					if ($_POST["newemployerOn"]=="on") {
						$data["employer"]=$_POST["newemployer"] ;
						$set.="gibbonPerson.employer=:employer, " ;
					}
				}
				if (isset($_POST["newjobTitleOn"])) {
					if ($_POST["newjobTitleOn"]=="on") {
						$data["jobTitle"]=$_POST["newjobTitle"] ;
						$set.="gibbonPerson.jobTitle=:jobTitle, " ;
					}
				}
				if (isset($_POST["newemergency1NameOn"])) {
					if ($_POST["newemergency1NameOn"]=="on") {
						$data["emergency1Name"]=$_POST["newemergency1Name"] ;
						$set.="gibbonPerson.emergency1Name=:emergency1Name, " ;
					}
				}
				if (isset($_POST["newemergency1Number1On"])) {
					if ($_POST["newemergency1Number1On"]=="on") {
						$data["emergency1Number1"]=$_POST["newemergency1Number1"] ;
						$set.="gibbonPerson.emergency1Number1=:emergency1Number1, " ;
					}
				}
				if (isset($_POST["newemergency1Number2On"])) {
					if ($_POST["newemergency1Number2On"]=="on") {
						$data["emergency1Number2"]=$_POST["newemergency1Number2"] ;
						$set.="gibbonPerson.emergency1Number2=:emergency1Number2, " ;
					}
				}
				if (isset($_POST["newemergency1RelationshipOn"])) {
					if ($_POST["newemergency1RelationshipOn"]=="on") {
						$data["emergency1Relationship"]=$_POST["newemergency1Relationship"] ;
						$set.="gibbonPerson.emergency1Relationship=:emergency1Relationship, " ;
					}
				}
				if (isset($_POST["newemergency2NameOn"])) {
					if ($_POST["newemergency2NameOn"]=="on") {
						$data["emergency2Name"]=$_POST["newemergency2Name"] ;
						$set.="gibbonPerson.emergency2Name=:emergency2Name, " ;
					}
				}
				if (isset($_POST["newemergency2Number1On"])) {
					if ($_POST["newemergency2Number1On"]=="on") {
						$data["emergency2Number1"]=$_POST["newemergency2Number1"] ;
						$set.="gibbonPerson.emergency2Number1=:emergency2Number1, " ;
					}
				}
				if (isset($_POST["newemergency2Number2On"])) {
					if ($_POST["newemergency2Number2On"]=="on") {
						$data["emergency2Number2"]=$_POST["newemergency2Number2"] ;
						$set.="gibbonPerson.emergency2Number2=:emergency2Number2, " ;
					}
				}
				if (isset($_POST["newemergency2RelationshipOn"])) {
					if ($_POST["newemergency2RelationshipOn"]=="on") {
						$data["emergency2Relationship"]=$_POST["newemergency2Relationship"] ;
						$set.="gibbonPerson.emergency2Relationship=:emergency2Relationship, " ;
					}
				}
				if (isset($_POST["newvehicleRegistrationOn"])) {
					if ($_POST["newvehicleRegistrationOn"]=="on") {
						$data["vehicleRegistration"]=$_POST["newvehicleRegistration"] ;
						$set.="gibbonPerson.vehicleRegistration=:vehicleRegistration, " ;
					}
				}
				if (isset($_POST["newprivacyOn"])) {
					if ($_POST["newprivacyOn"]=="on") {
						$data["privacy"]=$_POST["newprivacy"] ;
						$set.="gibbonPerson.privacy=:privacy, " ;
					}
				}
				
				//DEAL WITH CUSTOM FIELDS
				//Prepare field values
				$resultFields=getCustomFields($connection2, $guid, $student, $staff, $parent, $other, NULL, TRUE) ;
				$fields=array() ;
				if ($resultFields->rowCount()>0) {
					while ($rowFields=$resultFields->fetch()) {
						if (isset($_POST["newcustom" . $rowFields["gibbonPersonFieldID"] . "On"])) {
							if (isset($_POST["newcustom" . $rowFields["gibbonPersonFieldID"]])) {
								if ($rowFields["type"]=="date") {
									$fields[$rowFields["gibbonPersonFieldID"]]=dateConvert($guid, $_POST["newcustom" . $rowFields["gibbonPersonFieldID"]]) ;
								}
								else {
									$fields[$rowFields["gibbonPersonFieldID"]]=$_POST["newcustom" . $rowFields["gibbonPersonFieldID"]] ;
								}
							
							}
						}
					}
				}
				
				$fields=serialize($fields) ;
				
				if (strlen($set)>1) {
					//Write to database
					try {
						$data["gibbonPersonID"]=$gibbonPersonID ; 
						$data["fields"]=$fields ;
						$sql="UPDATE gibbonPerson SET " . substr($set,0,(strlen($set)-2)) . ", fields=:fields WHERE gibbonPersonID=:gibbonPersonID" ;
						$result=$connection2->prepare($sql);
						$result->execute($data);
					}
					catch(PDOException $e) { 
						//Fail 2
						$URL.="&updateReturn=fail2" ;
						header("Location: {$URL}");
						break ;
					}
			
					//Write to database
					try {
						$data=array("gibbonPersonUpdateID"=>$gibbonPersonUpdateID); 
						$sql="UPDATE gibbonPersonUpdate SET status='Complete' WHERE gibbonPersonUpdateID=:gibbonPersonUpdateID" ;
						$result=$connection2->prepare($sql);
						$result->execute($data);
					}
					catch(PDOException $e) { 
						//Fail 2
						$URL.="&updateReturn=success1" ;
						header("Location: {$URL}");
						break ;
					}
			
					//Success 0
					$URL.="&updateReturn=success0" ;
					header("Location: {$URL}");
				}
				else {
					//Write to database
					try {
						$data["gibbonPersonID"]=$gibbonPersonID ; 
						$data["fields"]=$fields ;
						$sql="UPDATE gibbonPerson SET fields=:fields WHERE gibbonPersonID=:gibbonPersonID" ;
						$result=$connection2->prepare($sql);
						$result->execute($data);
					}
					catch(PDOException $e) { 
						//Fail 2
						$URL.="&updateReturn=fail2" ;
						header("Location: {$URL}");
						break ;
					}
					
					//Write to database
					try {
						$data=array("gibbonPersonUpdateID"=>$gibbonPersonUpdateID); 
						$sql="UPDATE gibbonPersonUpdate SET status='Complete' WHERE gibbonPersonUpdateID=:gibbonPersonUpdateID" ;
						$result=$connection2->prepare($sql);
						$result->execute($data);
					}
					catch(PDOException $e) { 
						//Fail 2
						$URL.="&updateReturn=success1" ;
						header("Location: {$URL}");
						break ;
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