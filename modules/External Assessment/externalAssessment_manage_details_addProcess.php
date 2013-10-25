<?
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

$count=$_POST["count"] ;
$gibbonPersonID=$_POST["gibbonPersonID"] ;
$gibbonExternalAssessmentID=$_POST["gibbonExternalAssessmentID"] ;
$date=dateConvert($_POST["date"]) ;
$search=$_GET["search"] ;

$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/externalAssessment_manage_details_add.php&gibbonExternalAssessmentID=$gibbonExternalAssessmentID&gibbonPersonID=$gibbonPersonID&step=2&search=$search" ;

if (isActionAccessible($guid, $connection2, "/modules/External Assessment/externalAssessment_manage_details_add.php")==FALSE) {
	//Fail 0
	$URL=$URL . "&addReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	//Proceed!
	//Validate Inputs
	if ($count=="" OR $gibbonPersonID=="" OR $gibbonExternalAssessmentID=="" OR $date=="") {
		//Fail 3
		$URL=$URL . "&addReturn=fail3" ;
		header("Location: {$URL}");
	}
	else {
		//Lock markbook column table
		try {
			$sqlLock="LOCK TABLES gibbonExternalAssessmentStudent WRITE, gibbonExternalAssessmentStudentEntry WRITE" ;
			$resultLock=$connection2->query($sqlLock);   
		}
		catch(PDOException $e) { 
			//Fail 2
			$URL=$URL . "&addReturn=fail2$params" ;
			header("Location: {$URL}");
			break ;
		}			

		//Get next autoincrement
		try {
			$sqlAI="SHOW TABLE STATUS LIKE 'gibbonExternalAssessmentStudent'";
			$resultAI=$connection2->query($sqlAI);   
		}
		catch(PDOException $e) { 
			//Fail 2
			$URL=$URL . "&addReturn=fail2$params" ;
			header("Location: {$URL}");
			break ;
		}		
		
		$rowAI=$resultAI->fetch() ;
		$AI=str_pad($rowAI['Auto_increment'], 14, "0", STR_PAD_LEFT) ;
		
		//Scan through fields
		$partialFail=FALSE ;
		for ($i=0; $i<$count; $i++) {
			$gibbonExternalAssessmentFieldID=$_POST[$i . "-gibbonExternalAssessmentFieldID"] ;
			if ($_POST[$i . "-gibbonScaleGradeID"]=="") {
				$gibbonScaleGradeID=NULL ;
			}
			else {
				$gibbonScaleGradeID=$_POST[$i . "-gibbonScaleGradeID"] ;
			}
			if ($_POST[$i . "-gibbonScaleGradeIDPAS"]=="") {
				$gibbonScaleGradeIDPAS=NULL ;
			}
			else {
				$gibbonScaleGradeIDPAS=$_POST[$i . "-gibbonScaleGradeIDPAS"] ;
			}
			
			if ($gibbonExternalAssessmentFieldID!="") {
				try {
					$data=array("AI"=>$AI, "gibbonExternalAssessmentFieldID"=>$gibbonExternalAssessmentFieldID, "gibbonScaleGradeID"=>$gibbonScaleGradeID, "gibbonScaleGradeIDPAS"=>$gibbonScaleGradeIDPAS); 
					$sql="INSERT INTO gibbonExternalAssessmentStudentEntry SET gibbonExternalAssessmentStudentID=:AI, gibbonExternalAssessmentFieldID=:gibbonExternalAssessmentFieldID, gibbonScaleGradeID=:gibbonScaleGradeID, gibbonScaleGradeIDPrimaryAssessmentScale=:gibbonScaleGradeIDPAS" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					$partialFail=TRUE ; 
				}
			}
		}
		
		//Write to database
		try {
			$data=array("gibbonExternalAssessmentID"=>$gibbonExternalAssessmentID, "gibbonPersonID"=>$gibbonPersonID, "date"=>$date); 
			$sql="INSERT INTO gibbonExternalAssessmentStudent SET gibbonExternalAssessmentID=:gibbonExternalAssessmentID, gibbonPersonID=:gibbonPersonID, date=:date" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			//Fail 2
			$URL=$URL . "&addReturn=fail2$params" ;
			header("Location: {$URL}");
			break ;
		}
		
		//Unlock module table
		try {
			$sql="UNLOCK TABLES" ;
			$result=$connection2->query($sql);   
		}
		catch(PDOException $e) { }

		if ($partialFail==TRUE) {
			//Fail 5
			$URL=$URL . "&addReturn=fail5$params" ;
			header("Location: {$URL}");
		}
		else {
			//Success 0
			$URL=$URL . "&addReturn=success0$params" ;
			header("Location: {$URL}");
		}
	}
}
?>