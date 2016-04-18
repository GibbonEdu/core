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

$gibbonExternalAssessmentStudentID=$_GET["gibbonExternalAssessmentStudentID"] ;
$gibbonPersonID=$_GET["gibbonPersonID"] ;
$search=$_GET["search"] ;
$allStudents="" ;
if (isset($_GET["allStudents"])) {
	$allStudents=$_GET["allStudents"] ;
}

if ($gibbonPersonID=="" OR $gibbonExternalAssessmentStudentID=="") {
	print "Fatal error loading this page!" ;
}
else {
	$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/externalAssessment_manage_details_delete.php&gibbonPersonID=$gibbonPersonID&gibbonExternalAssessmentStudentID=$gibbonExternalAssessmentStudentID&search=$search&allStudents=$allStudents" ;
	$URLDelete=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/externalAssessment_details.php&gibbonPersonID=$gibbonPersonID&search=$search&allStudents=$allStudents" ;
	
	if (isActionAccessible($guid, $connection2, "/modules/Formal Assessment/externalAssessment_manage_details_delete.php")==FALSE) {
		//Fail 0
		$URL.="&deleteReturn=fail0" ;
		header("Location: {$URL}");
	}
	else {
		//Proceed!
		//Check if school year specified
		if ($gibbonExternalAssessmentStudentID=="") {
			//Fail1
			$URL.="&deleteReturn=fail1" ;
			header("Location: {$URL}");
		}
		else {
			try {
				$data=array("gibbonExternalAssessmentStudentID"=>$gibbonExternalAssessmentStudentID); 
				$sql="SELECT * FROM gibbonExternalAssessmentStudent WHERE gibbonExternalAssessmentStudentID=:gibbonExternalAssessmentStudentID" ;
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
				//Delete fields
				try {
					$data=array("gibbonExternalAssessmentStudentID"=>$gibbonExternalAssessmentStudentID); 
					$sql="DELETE FROM gibbonExternalAssessmentStudentEntry WHERE gibbonExternalAssessmentStudentID=:gibbonExternalAssessmentStudentID" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					//Fail 2
					$URL.="&deleteReturn=fail2" ;
					header("Location: {$URL}");
					exit() ;
				}
				
				//Delete assessment
				try {
					$data=array("gibbonExternalAssessmentStudentID"=>$gibbonExternalAssessmentStudentID); 
					$sql="DELETE FROM gibbonExternalAssessmentStudent WHERE gibbonExternalAssessmentStudentID=:gibbonExternalAssessmentStudentID" ;
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