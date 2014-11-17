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

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]["timezone"]);

$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/dataUpdaterSettings.php" ;

if (isActionAccessible($guid, $connection2, "/modules/User Admin/applicationFormSettings.php")==FALSE) {
	//Fail 0
	$URL.="&updateReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	//Proceed!
	$array=array() ;
	if (isset($_POST["title"])) { if ($_POST["title"]=="on") { $array["title"]="Y" ; } else { $array["title"]="N" ; } }
	if (isset($_POST["surname"])) { if ($_POST["surname"]=="on") { $array["surname"]="Y" ; } else { $array["surname"]="N" ; } }
	if (isset($_POST["firstName"])) { if ($_POST["firstName"]=="on") { $array["firstName"]="Y" ; } else { $array["firstName"]="N" ; } }
	if (isset($_POST["preferredName"])) { if ($_POST["preferredName"]=="on") { $array["preferredName"]="Y" ; } else { $array["preferredName"]="N" ; } }
	if (isset($_POST["officialName"])) { if ($_POST["officialName"]=="on") { $array["officialName"]="Y" ; } else { $array["officialName"]="N" ; } }
	if (isset($_POST["nameInCharacters"])) { if ($_POST["nameInCharacters"]=="on") { $array["nameInCharacters"]="Y" ; } else { $array["nameInCharacters"]="N" ; } }
	if (isset($_POST["dob"])) { if ($_POST["dob"]=="on") { $array["dob"]="Y" ; } else { $array["dob"]="N" ; } }
	if (isset($_POST["email"])) { if ($_POST["email"]=="on") { $array["email"]="Y" ; } else { $array["email"]="N" ; } }
	if (isset($_POST["emailAlternate"])) { if ($_POST["emailAlternate"]=="on") { $array["emailAlternate"]="Y" ; } else { $array["emailAlternate"]="N" ; } }
	if (isset($_POST["address1"])) { if ($_POST["address1"]=="on") { $array["address1"]="Y" ; } else { $array["address1"]="N" ; } }
	if (isset($_POST["address1District"])) { if ($_POST["address1District"]=="on") { $array["address1District"]="Y" ; } else { $array["address1District"]="N" ; } }
	if (isset($_POST["address1Country"])) { if ($_POST["address1Country"]=="on") { $array["address1Country"]="Y" ; } else { $array["address1Country"]="N" ; } }
	if (isset($_POST["address2"])) { if ($_POST["address2"]=="on") { $array["address2"]="Y" ; } else { $array["address2"]="N" ; } }
	if (isset($_POST["address2District"])) { if ($_POST["address2District"]=="on") { $array["address2District"]="Y" ; } else { $array["address2District"]="N" ; } }
	if (isset($_POST["address2Country"])) { if ($_POST["address2Country"]=="on") { $array["address2Country"]="Y" ; } else { $array["address2Country"]="N" ; } }
	if (isset($_POST["phone1Type"])) { if ($_POST["phone1Type"]=="on") { $array["phone1Type"]="Y" ; } else { $array["phone1Type"]="N" ; } }
	if (isset($_POST["phone1CountryCode"])) { if ($_POST["phone1CountryCode"]=="on") { $array["phone1CountryCode"]="Y" ; } else { $array["phone1CountryCode"]="N" ; } }
	if (isset($_POST["phone1"])) { if ($_POST["phone1"]=="on") { $array["phone1"]="Y" ; } else { $array["phone1"]="N" ; } }
	if (isset($_POST["phone2"])) { if ($_POST["phone2"]=="on") { $array["phone2"]="Y" ; } else { $array["phone2"]="N" ; } }
	if (isset($_POST["phone3"])) { if ($_POST["phone3"]=="on") { $array["phone3"]="Y" ; } else { $array["phone3"]="N" ; } }
	if (isset($_POST["phone4"])) { if ($_POST["phone4"]=="on") { $array["phone4"]="Y" ; } else { $array["phone4"]="N" ; } }
	if (isset($_POST["languageFirst"])) { if ($_POST["languageFirst"]=="on") { $array["languageFirst"]="Y" ; } else { $array["languageFirst"]="N" ; } }
	if (isset($_POST["languageSecond"])) { if ($_POST["languageSecond"]=="on") { $array["languageSecond"]="Y" ; } else { $array["languageSecond"]="N" ; } }
	if (isset($_POST["languageThird"])) { if ($_POST["languageThird"]=="on") { $array["languageThird"]="Y" ; } else { $array["languageThird"]="N" ; } }
	if (isset($_POST["countryOfBirth"])) { if ($_POST["countryOfBirth"]=="on") { $array["countryOfBirth"]="Y" ; } else { $array["countryOfBirth"]="N" ; } } 
	if (isset($_POST["ethnicity"])) { if ($_POST["ethnicity"]=="on") { $array["ethnicity"]="Y" ; } else { $array["ethnicity"]="N" ; } }
	if (isset($_POST["citizenship1"])) { if ($_POST["citizenship1"]=="on") { $array["citizenship1"]="Y" ; } else { $array["citizenship1"]="N" ; } }
	if (isset($_POST["citizenship1Passport"])) { if ($_POST["citizenship1Passport"]=="on") { $array["citizenship1Passport"]="Y" ; } else { $array["citizenship1Passport"]="N" ; } }
	if (isset($_POST["citizenship2"])) { if ($_POST["citizenship2"]=="on") { $array["citizenship2"]="Y" ; } else { $array["citizenship2"]="N" ; } }
	if (isset($_POST["citizenship2Passport"])) { if ($_POST["citizenship2Passport"]=="on") { $array["citizenship2Passport"]="Y" ; } else { $array["citizenship2Passport"]="N" ; } }
	if (isset($_POST["religion"])) { if ($_POST["religion"]=="on") { $array["religion"]="Y" ; } else { $array["religion"]="N" ; } }
	if (isset($_POST["nationalIDCardNumber"])) { if ($_POST["nationalIDCardNumber"]=="on") { $array["nationalIDCardNumber"]="Y" ; } else { $array["nationalIDCardNumber"]="N" ; } }
	if (isset($_POST["residencyStatus"])) { if ($_POST["residencyStatus"]=="on") { $array["residencyStatus"]="Y" ; } else { $array["residencyStatus"]="N" ; } }
	if (isset($_POST["visaExpiryDate"])) { if ($_POST["visaExpiryDate"]=="on") { $array["visaExpiryDate"]="Y" ; } else { $array["visaExpiryDate"]="N" ; } }
	if (isset($_POST["profession"])) { if ($_POST["profession"]=="on") { $array["profession"]="Y" ; } else { $array["profession"]="N" ; } }
	if (isset($_POST["employer"])) { if ($_POST["employer"]=="on") { $array["employer"]="Y" ; } else { $array["employer"]="N" ; } }
	if (isset($_POST["jobTitle"])) { if ($_POST["jobTitle"]=="on") { $array["jobTitle"]="Y" ; } else { $array["jobTitle"]="N" ; } }
	if (isset($_POST["emergency1Name"])) { if ($_POST["emergency1Name"]=="on") { $array["emergency1Name"]="Y" ; } else { $array["emergency1Name"]="N" ; } }
	if (isset($_POST["emergency1Number1"])) { if ($_POST["emergency1Number1"]=="on") { $array["emergency1Number1"]="Y" ; } else { $array["emergency1Number1"]="N" ; } }
	if (isset($_POST["emergency1Number2"])) { if ($_POST["emergency1Number2"]=="on") { $array["emergency1Number2"]="Y" ; } else { $array["emergency1Number2"]="N" ; } }
	if (isset($_POST["emergency1Relationship"])) { if ($_POST["emergency1Relationship"]=="on") { $array["emergency1Relationship"]="Y" ; } else { $array["emergency1Relationship"]="N" ; } }
	if (isset($_POST["emergency2Name"])) { if ($_POST["emergency2Name"]=="on") { $array["emergency2Name"]="Y" ; } else { $array["emergency2Name"]="N" ; } }
	if (isset($_POST["emergency2Number1"])) { if ($_POST["emergency2Number1"]=="on") { $array["emergency2Number1"]="Y" ; } else { $array["emergency2Number1"]="N" ; } }
	if (isset($_POST["emergency2Number2"])) { if ($_POST["emergency2Number2"]=="on") { $array["emergency2Number2"]="Y" ; } else { $array["emergency2Number2"]="N" ; } }
	if (isset($_POST["emergency2Relationship"])) { if ($_POST["emergency2Relationship"]=="on") { $array["emergency2Relationship"]="Y" ; } else { $array["emergency2Relationship"]="N" ; } }
	if (isset($_POST["vehicleRegistration"])) { if ($_POST["vehicleRegistration"]=="on") { $array["vehicleRegistration"]="Y" ; } else { $array["vehicleRegistration"]="N" ; } }
	
	//Write to database
	$fail=FALSE ;
	
	try {
		$data=array("value"=>serialize($array)); 
		$sql="UPDATE gibbonSetting SET value=:value WHERE scope='User Admin' AND name='personalDataUpdaterRequiredFields'" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		$fail=TRUE ; 
	}
	
	if ($fail==TRUE) {
		//Fail 2
		$URL.="&updateReturn=fail2" ;
		header("Location: {$URL}");
	}
	else {
		//Success 0
		getSystemSettings($guid, $connection2) ;
		$URL.="&updateReturn=success0" ;
		header("Location: {$URL}");
	}
}
?>