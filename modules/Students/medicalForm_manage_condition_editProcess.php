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

$gibbonPersonMedicalID=$_GET["gibbonPersonMedicalID"] ;
$gibbonPersonMedicalConditionID=$_GET["gibbonPersonMedicalConditionID"] ;
$search=$_GET["search"] ;
if ($gibbonPersonMedicalID=="" OR $gibbonPersonMedicalConditionID=="") {
	print "Fatal error loading this page!" ;
}
else {
	$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/medicalForm_manage_condition_edit.php&gibbonPersonMedicalID=$gibbonPersonMedicalID&gibbonPersonMedicalConditionID=$gibbonPersonMedicalConditionID&search=$search" ;
	
	if (isActionAccessible($guid, $connection2, "/modules/Students/medicalForm_manage_condition_edit.php")==FALSE) {
		//Fail 0
		$URL.="&updateReturn=fail0" ;
		header("Location: {$URL}");
	}
	else {
		//Proceed!
		//Check if person specified
		if ($gibbonPersonMedicalConditionID=="") {
			//Fail1
			$URL.="&updateReturn=fail1" ;
			header("Location: {$URL}");
		}
		else {
			try {
				$data=array("gibbonPersonMedicalConditionID"=>$gibbonPersonMedicalConditionID); 
				$sql="SELECT * FROM gibbonPersonMedicalCondition WHERE gibbonPersonMedicalConditionID=:gibbonPersonMedicalConditionID" ;
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				//Fail2
				$URL.="&deleteReturn=fail2" ;
				header("Location: {$URL}");
				exit() ;
			}
			
			if ($result->rowCount()!=1) {
				//Fail 2
				$URL.="&updateReturn=fail2" ;
				header("Location: {$URL}");
			}
			else {
				//Validate Inputs
				$name=$_POST["name"] ;
				$gibbonAlertLevelID=$_POST["gibbonAlertLevelID"] ;
				$triggers=$_POST["triggers"] ;
				$reaction=$_POST["reaction"] ;
				$response=$_POST["response"] ;
				$medication=$_POST["medication"] ;
				if ($_POST["lastEpisode"]=="") {
					$lastEpisode=NULL ;
				}
				else {
					$lastEpisode=dateConvert($guid, $_POST["lastEpisode"]) ;
				}
				$lastEpisodeTreatment=$_POST["lastEpisodeTreatment"] ;
				$comment=$_POST["comment"] ;
				
				if ($name=="" OR $gibbonAlertLevelID=="") {
					//Fail 3
					$URL.="&updateReturn=fail3" ;
					header("Location: {$URL}");
				}
				else {
					//Write to database
					try {
						$data=array("gibbonPersonMedicalID"=>$gibbonPersonMedicalID, "name"=>$name, "gibbonAlertLevelID"=>$gibbonAlertLevelID, "triggers"=>$triggers, "reaction"=>$reaction, "response"=>$response, "medication"=>$medication, "lastEpisode"=>$lastEpisode, "lastEpisodeTreatment"=>$lastEpisodeTreatment, "comment"=>$comment, "gibbonPersonMedicalConditionID"=>$gibbonPersonMedicalConditionID); 
						$sql="UPDATE gibbonPersonMedicalCondition SET gibbonPersonMedicalID=:gibbonPersonMedicalID, name=:name, gibbonAlertLevelID=:gibbonAlertLevelID, triggers=:triggers, reaction=:reaction, response=:response, medication=:medication, lastEpisode=:lastEpisode, lastEpisodeTreatment=:lastEpisodeTreatment, comment=:comment WHERE gibbonPersonMedicalConditionID=:gibbonPersonMedicalConditionID" ;
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