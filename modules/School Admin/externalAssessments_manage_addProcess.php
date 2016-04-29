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

$name=$_POST["name"] ;
$nameShort=$_POST["nameShort"] ;
$description=$_POST["description"] ;
$active=$_POST["active"] ;
$allowFileUpload=$_POST["allowFileUpload"] ;

$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/externalAssessments_manage_add.php" ;

if (isActionAccessible($guid, $connection2, "/modules/School Admin/externalAssessments_manage_add.php")==FALSE) {
	$URL.="&return=error0" ;
	header("Location: {$URL}");
}
else {
	//Proceed!
	//Validate Inputs
	if ($name=="" OR $nameShort=="" OR $description=="" OR $active=="") {
		$URL.="&return=error1" ;
		header("Location: {$URL}");
	}
	else {
		//Check unique inputs for uniquness
		try {
			$data=array("name"=>$name, "nameShort"=>$nameShort); 
			$sql="SELECT * FROM gibbonExternalAssessment WHERE name=:name OR nameShort=:nameShort" ;
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
				$data=array("name"=>$name, "nameShort"=>$nameShort, "description"=>$description, "active"=>$active, "allowFileUpload"=>$allowFileUpload); 
				$sql="INSERT INTO gibbonExternalAssessment SET name=:name, nameShort=:nameShort, `description`=:description, active=:active, allowFileUpload=:allowFileUpload" ;
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				$URL.="&return=error2" ;
				header("Location: {$URL}");
				exit() ;
			}
			
			//Last insert ID
			$AI=str_pad($connection2->lastInsertID(), 4, "0", STR_PAD_LEFT) ;

			$URL.="&return=success0&editID=$AI" ;
			header("Location: {$URL}");
		}
	}
}
?>