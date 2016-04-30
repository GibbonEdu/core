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

$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/schoolYearTerm_manage_add.php" ;

if (isActionAccessible($guid, $connection2, "/modules/School Admin/schoolYearTerm_manage_add.php")==FALSE) {
	$URL.="&return=error0" ;
	header("Location: {$URL}");
}
else {
	//Proceed!
	//Validate Inputs
	$gibbonSchoolYearID=$_POST["gibbonSchoolYearID"] ;
	$sequenceNumber=$_POST["sequenceNumber"] ;
	$name=$_POST["name"] ;
	$nameShort=$_POST["nameShort"] ;
	$firstDay=dateConvert($guid, $_POST["firstDay"]) ;
	$lastDay=dateConvert($guid, $_POST["lastDay"]) ;
	
	if ($gibbonSchoolYearID=="" OR $name=="" OR $nameShort=="" OR $sequenceNumber=="" OR is_numeric($sequenceNumber)==FALSE OR $firstDay=="" OR $lastDay=="") {
		$URL.="&return=error1" ;
		header("Location: {$URL}");
	}
	else {
		//Check unique inputs for uniquness
		try {
			$data=array("sequenceNumber"=>$sequenceNumber); 
			$sql="SELECT * FROM gibbonSchoolYearTerm WHERE (sequenceNumber=:sequenceNumber)" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$URL.="&return=error2" ;
			header("Location: {$URL}");
			exit() ;
		}
		
		if ($result->rowCount()>0) {
			$URL.="&return=error3" ;
			header("Location: {$URL}");
		}
		else {	
			//Write to database
			try {
				$data=array("gibbonSchoolYearID"=>$gibbonSchoolYearID, "name"=>$name, "nameShort"=>$nameShort, "sequenceNumber"=>$sequenceNumber, "firstDay"=>$firstDay, "lastDay"=>$lastDay); 
				$sql="INSERT INTO gibbonSchoolYearTerm SET gibbonSchoolYearID=:gibbonSchoolYearID, name=:name, nameShort=:nameShort, sequenceNumber=:sequenceNumber, firstDay=:firstDay, lastDay=:lastDay" ;
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				$URL.="&return=error2" ;
				header("Location: {$URL}");
				exit() ;
			}
			
			//Last insert ID
			$AI=str_pad($connection2->lastInsertID(), 5, "0", STR_PAD_LEFT) ;

			$URL.="&return=success0&editID=$AI" ;
			header("Location: {$URL}");
		}
	}
}
?>