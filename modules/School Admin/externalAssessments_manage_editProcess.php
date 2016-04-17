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

$name=$_POST["name"] ;
$nameShort=$_POST["nameShort"] ;
$gibbonExternalAssessmentID=$_POST["gibbonExternalAssessmentID"] ;
$description=$_POST["description"] ;
$active=$_POST["active"] ;
$allowFileUpload=$_POST["allowFileUpload"] ;

$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/externalAssessments_manage_edit.php&gibbonExternalAssessmentID=" . $gibbonExternalAssessmentID ;

if (isActionAccessible($guid, $connection2, "/modules/School Admin/externalAssessments_manage_edit.php")==FALSE) {
	//Fail 0
	$URL.="&updateReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	//Proceed!
	if ($gibbonExternalAssessmentID=="") {
		//Fail1
		$URL.="&updateReturn=fail1" ;
		header("Location: {$URL}");
	}
	else {
		try {
			$data=array("gibbonExternalAssessmentID"=>$gibbonExternalAssessmentID); 
			$sql="SELECT * FROM gibbonExternalAssessment WHERE gibbonExternalAssessmentID=:gibbonExternalAssessmentID" ;
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
			if ($name=="" OR $nameShort=="" OR $description=="" OR $active=="") {
				//Fail 3
				$URL.="&updateReturn=fail3" ;
				header("Location: {$URL}");
			}
			else {
				//Check unique inputs for uniquness
				try {
					$data=array("name"=>$name, "nameShort"=>$nameShort, "gibbonExternalAssessmentID"=>$gibbonExternalAssessmentID); 
					$sql="SELECT * FROM gibbonExternalAssessment WHERE (name=:name OR nameShort=:nameShort) AND NOT gibbonExternalAssessmentID=:gibbonExternalAssessmentID" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					//Fail 2
					$URL.="&updateReturn=fail2" ;
					header("Location: {$URL}");
					exit() ;
				}
				
				if ($result->rowCount()>0) {
					//Fail 4
					$URL.="&updateReturn=fail4" ;
					header("Location: {$URL}");
				}
				else {
					//Write to database
					try {
						$data=array("name"=>$name, "nameShort"=>$nameShort, "description"=>$description, "active"=>$active, "allowFileUpload"=>$allowFileUpload, "gibbonExternalAssessmentID"=>$gibbonExternalAssessmentID); 
						$sql="UPDATE gibbonExternalAssessment SET name=:name, nameShort=:nameShort, `description`=:description, active=:active, allowFileUpload=:allowFileUpload WHERE gibbonExternalAssessmentID=:gibbonExternalAssessmentID" ;
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