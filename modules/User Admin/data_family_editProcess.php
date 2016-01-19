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

$gibbonFamilyUpdateID=$_GET["gibbonFamilyUpdateID"] ;
$gibbonFamilyID=$_POST["gibbonFamilyID"] ;
$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/data_family_edit.php&gibbonFamilyUpdateID=$gibbonFamilyUpdateID" ;

if (isActionAccessible($guid, $connection2, "/modules/User Admin/data_family_edit.php")==FALSE) {
	//Fail 0
	$URL.="&updateReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	//Proceed!
	//Check if school year specified
	if ($gibbonFamilyUpdateID=="" OR $gibbonFamilyID=="") {
		//Fail1
		$URL.="&updateReturn=fail1" ;
		header("Location: {$URL}");
	}
	else {
		try {
			$data=array("gibbonFamilyUpdateID"=>$gibbonFamilyUpdateID); 
			$sql="SELECT * FROM gibbonFamilyUpdate WHERE gibbonFamilyUpdateID=:gibbonFamilyUpdateID" ;
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
			//Set values
			$data=array(); 
			$set="" ;
			if (isset($_POST["newnameAddressOn"])) {
				if ($_POST["newnameAddressOn"]=="on") {
					$data["nameAddress"]=$_POST["newnameAddress"] ;
					$set.="gibbonFamily.nameAddress=:nameAddress, " ;
				}
			}
			if (isset($_POST["newhomeAddressOn"])) {
				if ($_POST["newhomeAddressOn"]=="on") {
					$data["homeAddress"]=$_POST["newhomeAddress"] ;
					$set.="gibbonFamily.homeAddress=:homeAddress, " ;
				}
			}
			if (isset($_POST["newhomeAddressDistrictOn"])) {
				if ($_POST["newhomeAddressDistrictOn"]=="on") {
					$data["homeAddressDistrict"]=$_POST["newhomeAddressDistrict"] ;
					$set.="gibbonFamily.homeAddressDistrict=:homeAddressDistrict, " ;
				}
			}
			if (isset($_POST["newhomeAddressCountryOn"])) {
				if ($_POST["newhomeAddressCountryOn"]=="on") {
					$data["homeAddressCountry"]=$_POST["newhomeAddressCountry"] ;
					$set.="gibbonFamily.homeAddressCountry=:homeAddressCountry, " ;
				}
			}
			if (isset($_POST["newlanguageHomePrimaryOn"])) {
				if ($_POST["newlanguageHomePrimaryOn"]=="on") {
					$data["languageHomePrimary"]=$_POST["newlanguageHomePrimary"] ;
					$set.="gibbonFamily.languageHomePrimary=:languageHomePrimary, " ;
				}
			}
			if (isset($_POST["newlanguageHomeSecondaryOn"])) {
				if ($_POST["newlanguageHomeSecondaryOn"]=="on") {
					$data["languageHomeSecondary"]=$_POST["newlanguageHomeSecondary"] ;
					$set.="gibbonFamily.languageHomeSecondary=:languageHomeSecondary, " ;
				}
			}
			
			if (strlen($set)>1) {
				//Write to database
				try {
					$data["gibbonFamilyID"]=$gibbonFamilyID ; 
					$sql="UPDATE gibbonFamily SET " . substr($set,0,(strlen($set)-2)) . " WHERE gibbonFamilyID=:gibbonFamilyID" ;
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
					$data=array("gibbonFamilyUpdateID"=>$gibbonFamilyUpdateID); 
					$sql="UPDATE gibbonFamilyUpdate SET status='Complete' WHERE gibbonFamilyUpdateID=:gibbonFamilyUpdateID" ;
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
					$data=array("gibbonFamilyUpdateID"=>$gibbonFamilyUpdateID); 
					$sql="UPDATE gibbonFamilyUpdate SET status='Complete' WHERE gibbonFamilyUpdateID=:gibbonFamilyUpdateID" ;
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
?>