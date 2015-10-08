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

$gibbonFamilyID=$_GET["gibbonFamilyID"] ;
$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/data_family.php&gibbonFamilyID=$gibbonFamilyID" ;

if (isActionAccessible($guid, $connection2, "/modules/Data Updater/data_family.php")==FALSE) {
	//Fail 0
	$URL.="&updateReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	//Proceed!
	//Check if school year specified
	if ($gibbonFamilyID=="") {
		//Fail1
		$URL.="&updateReturn=fail1" ;
		header("Location: {$URL}");
	}
	else {
		//Get action with highest precendence
		$highestAction=getHighestGroupedAction($guid, $_POST["address"], $connection2) ;
		if ($highestAction==FALSE) {
			//Fail 0
			$URL.="&updateReturn=fail0$params" ;
			header("Location: {$URL}");
		}
		else {
			//Check access to person
			if ($highestAction=="Update Family Data_any") {
				try {
					$dataCheck=array("gibbonFamilyID"=>$gibbonFamilyID); 
					$sqlCheck="SELECT name, gibbonFamily.gibbonFamilyID FROM gibbonFamily WHERE gibbonFamilyID=:gibbonFamilyID" ;
					$resultCheck=$connection2->prepare($sqlCheck);
					$resultCheck->execute($dataCheck);
				}
				catch(PDOException $e) { }
			}
			else {
				try {
					$dataCheck=array("gibbonFamilyID"=>$gibbonFamilyID, "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
					$sqlCheck="SELECT name, gibbonFamily.gibbonFamilyID FROM gibbonFamily JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) WHERE gibbonPersonID=:gibbonPersonID AND childDataAccess='Y' AND gibbonFamily.gibbonFamilyID=:gibbonFamilyID" ;
					$resultCheck=$connection2->prepare($sqlCheck);
					$resultCheck->execute($dataCheck);
				}
				catch(PDOException $e) { }
			}
			
			if ($resultCheck->rowCount()!=1) {
				//Fail 2
				$URL.="&updateReturn=fail2a" ;
				header("Location: {$URL}");
			}
			else {
				//Proceed!
				$nameAddress=$_POST["nameAddress"] ; 	
				$homeAddress=$_POST["homeAddress"] ;
				$homeAddressDistrict=$_POST["homeAddressDistrict"] ;
				$homeAddressCountry=$_POST["homeAddressCountry"] ;
				$languageHomePrimary=$_POST["languageHomePrimary"] ;
				$languageHomeSecondary=$_POST["languageHomeSecondary"] ;
				
				//Attempt to send email to DBA
				if ($_SESSION[$guid]["organisationDBA"]!="") {
					$notificationText=sprintf(_('A family data update request has been submitted.')) ;
					setNotification($connection2, $guid, $_SESSION[$guid]["organisationDBA"], $notificationText, "Data Updater", "/index.php?q=/modules/User Admin/data_family.php") ;
				}
				
				//Write to database
				$existing=$_POST["existing"] ;
				
				try {
					if ($existing!="N") {
						$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "nameAddress"=>$nameAddress, "homeAddress"=>$homeAddress, "homeAddressDistrict"=>$homeAddressDistrict, "homeAddressCountry"=>$homeAddressCountry, "languageHomePrimary"=>$languageHomePrimary, "languageHomeSecondary"=>$languageHomeSecondary, "gibbonPersonIDUpdater"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonFamilyUpdateID"=>$existing); 
						$sql="UPDATE gibbonFamilyUpdate SET gibbonSchoolYearID=:gibbonSchoolYearID, nameAddress=:nameAddress, homeAddress=:homeAddress, homeAddressDistrict=:homeAddressDistrict, homeAddressCountry=:homeAddressCountry, languageHomePrimary=:languageHomePrimary, languageHomeSecondary=:languageHomeSecondary, gibbonPersonIDUpdater=:gibbonPersonIDUpdater WHERE gibbonFamilyUpdateID=:gibbonFamilyUpdateID" ;
					}
					else {
						$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonFamilyID"=>$gibbonFamilyID, "nameAddress"=>$nameAddress, "homeAddress"=>$homeAddress, "homeAddressDistrict"=>$homeAddressDistrict, "homeAddressCountry"=>$homeAddressCountry, "languageHomePrimary"=>$languageHomePrimary, "languageHomeSecondary"=>$languageHomeSecondary, "gibbonPersonIDUpdater"=>$_SESSION[$guid]["gibbonPersonID"]); 
						$sql="INSERT INTO gibbonFamilyUpdate SET gibbonSchoolYearID=:gibbonSchoolYearID, gibbonFamilyID=:gibbonFamilyID, nameAddress=:nameAddress, homeAddress=:homeAddress, homeAddressDistrict=:homeAddressDistrict, homeAddressCountry=:homeAddressCountry, languageHomePrimary=:languageHomePrimary, languageHomeSecondary=:languageHomeSecondary, gibbonPersonIDUpdater=:gibbonPersonIDUpdater" ;
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
				
				//Success 0
				$URL.="&updateReturn=success0" ;
				header("Location: {$URL}");
			}
		}
	}
}
?>