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

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]["timezone"]);

//Check if school year specified
$gibbonPersonMedicalID=$_GET["gibbonPersonMedicalID"] ;
$gibbonPersonMedicalConditionID=$_GET["gibbonPersonMedicalConditionID"] ;
$search=$_GET["search"] ;
if ($gibbonPersonMedicalID=="" OR $gibbonPersonMedicalConditionID=="") {
	print "Fatal error loading this page!" ;
}
else {
	$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/medicalForm_manage_condition_delete.php&gibbonPersonMedicalID=$gibbonPersonMedicalID&gibbonPersonMedicalConditionID=$gibbonPersonMedicalConditionID&search=$search" ;
	$URLDelete=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/medicalForm_manage_edit.php&gibbonPersonMedicalID=$gibbonPersonMedicalID&search=$search" ;
	
	if (isActionAccessible($guid, $connection2, "/modules/Students/medicalForm_manage_condition_delete.php")==FALSE) {
		//Fail 0
		$URL.="&deleteReturn=fail0" ;
		header("Location: {$URL}");
	}
	else {
		//Proceed!
		//Check if condition specified
		if ($gibbonPersonMedicalConditionID=="") {
			//Fail1
			$URL.="&deleteReturn=fail1" ;
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
				$URL.="&deleteReturn=fail2" ;
				header("Location: {$URL}");
			}
			else {
				//Write to database
				try {
					$data=array("gibbonPersonMedicalConditionID"=>$gibbonPersonMedicalConditionID); 
					$sql="DELETE FROM gibbonPersonMedicalCondition WHERE gibbonPersonMedicalConditionID=:gibbonPersonMedicalConditionID" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					//Fail 2
					$URL.="&deleteReturn=fail2" ;
					header("Location: {$URL}");
					exit() ;
				}

				//Success 0
				$URLDelete=$URLDelete . "&deleteReturn=success0" ;
				header("Location: {$URLDelete}");
			}
		}
	}
}
?>