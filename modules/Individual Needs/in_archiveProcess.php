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

$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/in_archive.php" ;

if (isActionAccessible($guid, $connection2, "/modules/Individual Needs/in_archive.php")==FALSE) {
	//Fail 0
	$URL.="&return=error0" ;
	header("Location: {$URL}");
}
else {
	$deleteCurrentPlans=$_POST["deleteCurrentPlans"] ; 
	$title=$_POST["title"] ; 
	$gibbonPersonIDs=$_POST["gibbonPersonID"] ; 

	if ($deleteCurrentPlans=="" OR $title=="" OR count($gibbonPersonIDs)<1) {
		//Fail 3
		$URL.="&return=error1" ;
		header("Location: {$URL}");
	}
	else {
		$partialFail=FALSE ;
		
		//SCAN THROUGH EACH USER
		foreach ($gibbonPersonIDs AS $gibbonPersonID) {
			$userFail=FALSE ;
			//Get each user's record
			try {
				$data=array("gibbonPersonID"=>$gibbonPersonID); 
				$sql="SELECT surname, preferredName, gibbonIN.* FROM gibbonPerson JOIN gibbonIN ON (gibbonIN.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE status='Full' AND gibbonPerson.gibbonPersonID=:gibbonPersonID ORDER BY surname, preferredName" ;
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				$userFail=TRUE ;
				$partialFail=TRUE ;
				
			}
			if ($result->rowCount()!=1) {
				$userFail=TRUE ;
				$partialFail=TRUE ;
			}
			
			if ($userFail==FALSE) {
				$userUpdateFail=FALSE ;
				$row=$result->fetch() ;
				
				//Check for descriptors, and write to array
				$descriptors=array() ;
				$descriptorsCount=0 ;
				try {
					$dataDesciptors=array("gibbonPersonID"=>$gibbonPersonID); 
					$sqlDesciptors="SELECT * FROM gibbonINPersonDescriptor WHERE gibbonPersonID=:gibbonPersonID" ;
					$resultDesciptors=$connection2->prepare($sqlDesciptors);
					$resultDesciptors->execute($dataDesciptors);
				}
				catch(PDOException $e) { 
					$partialFail=TRUE ;
				}
				while ($rowDesciptors=$resultDesciptors->fetch()) {
					$descriptors[$descriptorsCount]["gibbonINDescriptorID"]=$rowDesciptors["gibbonINDescriptorID"] ;
					$descriptors[$descriptorsCount]["gibbonAlertLevelID"]=$rowDesciptors["gibbonAlertLevelID"] ;
					$descriptorsCount++ ;
				}
				$descriptors=serialize($descriptors) ;
				
				//Make archive of record
				try {
					$dataUpdate=array("strategies"=>$row["strategies"], "targets"=>$row["targets"], "notes"=>$row["notes"], "gibbonPersonID"=>$gibbonPersonID, "title"=>$title, "descriptors"=>$descriptors); 
					$sqlUpdate="INSERT INTO gibbonINArchive SET gibbonPersonID=:gibbonPersonID, strategies=:strategies, targets=:targets, notes=:notes, archiveTitle=:title, descriptors=:descriptors, archiveTimestamp=now()" ;
					$resultUpdate=$connection2->prepare($sqlUpdate);
					$resultUpdate->execute($dataUpdate);
				}
				catch(PDOException $e) { 
					$userUpdateFail=TRUE ;
					$partialFail=TRUE ;
				}
				
				//If copy was successful and deleteCurrentPlans=Y, update current record to blank IEP fields
				if ($deleteCurrentPlans=="Y" AND $userUpdateFail==FALSE) {
					try {
						$dataUpdate=array("gibbonPersonID"=>$gibbonPersonID); 
						$sqlUpdate="UPDATE gibbonIN SET strategies='', targets='', notes='' WHERE gibbonPersonID=:gibbonPersonID" ;
						$resultUpdate=$connection2->prepare($sqlUpdate);
						$resultUpdate->execute($dataUpdate);
					}
					catch(PDOException $e) { 
						$partialFail=TRUE ;
					}
				}
			}
		}
		
		//DEAL WITH OUTCOME
		if ($partialFail) {
			//Fail 5
			$URL.="&return=warning1" ;
			header("Location: {$URL}");
		}
		else {
			//Success 0
			$URL.="&return=success0" ;
			header("Location: {$URL}");
		}
	}
}
?>