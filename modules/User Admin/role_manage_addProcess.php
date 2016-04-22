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

$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/role_manage_add.php" ;

if (isActionAccessible($guid, $connection2, "/modules/User Admin/role_manage_add.php")==FALSE) {
	//Fail 0
	$URL.="&addReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	//Proceed!
	//Validate Inputs
	$category=$_POST["category"] ;
	$name=$_POST["name"] ;
	$nameShort=$_POST["nameShort"] ;
	$description=$_POST["description"] ;
	$futureYearsLogin=$_POST["futureYearsLogin"] ;
	$pastYearsLogin=$_POST["pastYearsLogin"] ;
	
	if ($category=="" OR $name=="" OR $nameShort=="" OR $description=="" OR $futureYearsLogin=="" OR $pastYearsLogin=="") {
		//Fail 3
		$URL.="&addReturn=fail3" ;
		header("Location: {$URL}");
	}
	else {
		//Check unique inputs for uniqueness
		try {
			$data=array("name"=>$name, "nameShort"=>$nameShort); 
			$sql="SELECT * FROM gibbonRole WHERE name=:name OR nameShort=:nameShort" ;
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
			//Write to database
			try {
				$data=array("category"=>$category, "name"=>$name, "nameShort"=>$nameShort, "description"=>$description, "futureYearsLogin"=>$futureYearsLogin, "pastYearsLogin"=>$pastYearsLogin); 
				$sql="INSERT INTO gibbonRole SET category=:category, name=:name, nameShort=:nameShort, description=:description, type='Additional', futureYearsLogin=:futureYearsLogin, pastYearsLogin=:pastYearsLogin" ;
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