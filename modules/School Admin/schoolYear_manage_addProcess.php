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

$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/schoolYear_manage_add.php" ;

if (isActionAccessible($guid, $connection2, "/modules/School Admin/schoolYear_manage_add.php")==FALSE) {
	//Fail 0
	$URL.="&addReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	//Proceed!
	//Validate Inputs
	$name=$_POST["name"] ;
	$status=$_POST["status"] ;
	$sequenceNumber=$_POST["sequenceNumber"] ;
	$firstDay=dateConvert($guid, $_POST["firstDay"]) ;
	$lastDay=dateConvert($guid, $_POST["lastDay"]) ;
	
	if ($name=="" OR $status=="" OR $sequenceNumber=="" OR is_numeric($sequenceNumber)==FALSE OR $firstDay=="" OR $lastDay=="") {
		//Fail 3
		$URL.="&addReturn=fail3" ;
		header("Location: {$URL}");
	}
	else {
		//Check unique inputs for uniquness
		try {
			$data=array("name"=>$name, "sequenceNumber"=>$sequenceNumber); 
			$sql="SELECT * FROM gibbonSchoolYear WHERE name=:name OR sequenceNumber=:sequenceNumber" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			//Fail 2
			$URL.="&addReturn=fail2" ;
			header("Location: {$URL}");
			break ;
		}
		
		if ($result->rowCount()>0) {
			//Fail 4
			$URL.="&addReturn=fail4" ;
			header("Location: {$URL}");
		}
		else {	
			//Check for other currents
			$currentFail=FALSE ;
			if ($status=="Current") {
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSchoolYear WHERE status='Current'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					//Fail 2
					$URL.="&addReturn=fail2" ;
					header("Location: {$URL}");
					break ;
				}
				if ($result->rowCount()>0) {
					//Fail 4
					$URL.="&addReturn=fail4" ;
					header("Location: {$URL}");
					break ;
				}
			}
			
			if ($currentFail==FALSE) {	
				//Write to database
				try {
					$data=array("name"=>$name, "status"=>$status, "sequenceNumber"=>$sequenceNumber, "firstDay"=>$firstDay, "lastDay"=>$lastDay); 
					$sql="INSERT INTO gibbonSchoolYear SET name=:name, status=:status, sequenceNumber=:sequenceNumber, firstDay=:firstDay, lastDay=:lastDay" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					//Fail 2
					$URL.="&addReturn=fail2" ;
					header("Location: {$URL}");
				}
			
				//Success 0
				$URL.="&addReturn=success0" ;
				header("Location: {$URL}");
			}
		}
	}
}
?>