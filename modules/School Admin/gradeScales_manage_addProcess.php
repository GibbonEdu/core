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

$name=$_POST["name"] ;
$nameShort=$_POST["nameShort"] ;
$usage=$_POST["usage"] ;
$active=$_POST["active"] ;
$numeric=$_POST["numeric"] ;

$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/gradeScales_manage_add.php" ;

if (isActionAccessible($guid, $connection2, "/modules/School Admin/gradeScales_manage_add.php")==FALSE) {
	//Fail 0
	$URL=$URL . "&addReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	//Proceed!
	//Validate Inputs
	if ($name=="" OR $nameShort=="" OR $usage=="" OR $active=="" OR $numeric=="") {
		//Fail 3
		$URL=$URL . "&addReturn=fail3" ;
		header("Location: {$URL}");
	}
	else {
		//Check unique inputs for uniquness
		try {
			$data=array("name"=>$name, "nameShort"=>$nameShort); 
			$sql="SELECT * FROM gibbonScale WHERE name=:name OR nameShort=:nameShort" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			//Fail 2
			$URL=$URL . "&addReturn=fail2" ;
			header("Location: {$URL}");
			break ;
		}
		
		if ($result->rowCount()>0) {
			//Fail 4
			$URL=$URL . "&addReturn=fail4" ;
			header("Location: {$URL}");
		}
		else {	
			//Write to database
			try {
				$data=array("name"=>$name, "nameShort"=>$nameShort, "usage"=>$usage, "active"=>$active, "numeric"=>$numeric); 
				$sql="INSERT INTO gibbonScale SET name=:name, nameShort=:nameShort, `usage`=:usage, lowestAcceptable=NULL, active=:active, `numeric`=:numeric" ;
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				//Fail 2
				$URL=$URL . "&addReturn=fail2" ;
				header("Location: {$URL}");
				break ;
			}
			
			//Success 0
			$URL=$URL . "&addReturn=success0" ;
			header("Location: {$URL}");
		}
	}
}
?>