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

$value=$_POST["value"] ;
$descriptor=$_POST["descriptor"] ;
$sequenceNumber=$_POST["sequenceNumber"] ;
$isDefault=$_POST["isDefault"] ;

$gibbonScaleID=$_POST["gibbonScaleID"] ;

$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/gradeScales_manage_edit_grade_add.php&gibbonScaleID=$gibbonScaleID" ;

if (isActionAccessible($guid, $connection2, "/modules/School Admin/gradeScales_manage_edit_grade_add.php")==FALSE) {
	//Fail 0
	$URL.="&addReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	//Proceed!
	//Validate Inputs
	if ($gibbonScaleID=="" OR $value=="" OR $descriptor=="" OR $sequenceNumber=="" OR $isDefault=="") {
		//Fail 3
		$URL.="&addReturn=fail3" ;
		header("Location: {$URL}");
	}
	else {
		//Check unique inputs for uniquness
		try {
			$data=array("value"=>$value, "sequenceNumber"=>$sequenceNumber, "gibbonScaleID"=>$gibbonScaleID); 
			$sql="SELECT * FROM gibbonScaleGrade WHERE ((value=:value) OR (sequenceNumber=:sequenceNumber)) AND gibbonScaleID=:gibbonScaleID" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			//Fail 2
			$URL.="&addReturn=fail2" ;
			header("Location: {$URL}");
			exit() ;
		}

		if ($result->rowCount()>0) {
			//Fail 4
			$URL.="&addReturn=fail4" ;
			header("Location: {$URL}");
		}
		else {	
			//If isDefault is Y, then set all other grades in scale to N
			if ($isDefault=="Y") {
				try {
					$data=array("gibbonScaleID"=>$gibbonScaleID); 
					$sql="UPDATE gibbonScaleGrade SET isDefault='N' WHERE gibbonScaleID=:gibbonScaleID" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					//Fail 2
					$URL.="&addReturn=fail2" ;
					header("Location: {$URL}");
					exit() ;
				}
			}
			
			//Write to database
			try {
				$data=array("gibbonScaleID"=>$gibbonScaleID, "value"=>$value, "descriptor"=>$descriptor, "sequenceNumber"=>$sequenceNumber, "isDefault"=>$isDefault); 
				$sql="INSERT INTO gibbonScaleGrade SET gibbonScaleID=:gibbonScaleID, value=:value, descriptor=:descriptor, sequenceNumber=:sequenceNumber, isDefault=:isDefault" ;
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				//Fail 2
				$URL.="&addReturn=fail2" ;
				header("Location: {$URL}");
				exit() ;
			}
			
			//Success 0
			$URL.="&addReturn=success0" ;
			header("Location: {$URL}");
		}
	}
}
?>