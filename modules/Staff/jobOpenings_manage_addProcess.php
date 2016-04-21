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
$pdo = new Gibbon\sqlConnection();
$connection2 = $pdo->getConnection();

@session_start() ;

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]["timezone"]);

$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/jobOpenings_manage_add.php" ;

if (isActionAccessible($guid, $connection2, "/modules/Staff/jobOpenings_manage_add.php")==FALSE) {
	//Fail 0
	$URL.="&addReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	//Proceed!
	//Validate Inputs
	$type=$_POST["type"] ;
	$jobTitle=$_POST["jobTitle"] ;
	$dateOpen=dateConvert($guid, $_POST["dateOpen"]) ;
	$active=$_POST["active"] ;
	$description=$_POST["description"] ;
	
	if ($type=="" OR $jobTitle=="" OR $dateOpen=="" OR $active=="" OR $description=="") {
		//Fail 3
		$URL.="&addReturn=fail3" ;
		header("Location: {$URL}");
	}
	else {
		//Write to database
		try {
			$data=array("type"=>$type, "jobTitle"=>$jobTitle, "dateOpen"=>$dateOpen, "active"=>$active, "description"=>$description, "gibbonPersonIDCreator"=>$_SESSION[$guid]["gibbonPersonID"]); 
			$sql="INSERT INTO gibbonStaffJobOpening SET type=:type, jobTitle=:jobTitle, dateOpen=:dateOpen, active=:active, description=:description, gibbonPersonIDCreator=:gibbonPersonIDCreator" ;
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
?>