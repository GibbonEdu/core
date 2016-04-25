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

$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/alertLevelSettings.php" ;

if (isActionAccessible($guid, $connection2, "/modules/School Admin/alertLevelSettings.php")==FALSE) {
	//Fail 0
	$URL.="&updateReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	$count=$_POST["count"] ; 
	$partialFail=FALSE ;
	//Proceed!
	if ($count<1) {
		//Fail 2
		$URL.="&updateReturn=fail2" ;
		header("Location: {$URL}");
	}
	else {
		for ($i=0; $i<$count; $i++) {
			$gibbonAlertLevelID=$_POST["gibbonAlertLevelID" . $i] ;
			$name=$_POST["name" . $i] ;
			$nameShort=$_POST["nameShort" . $i] ;
			$color=$_POST["color" . $i] ;
			$colorBG=$_POST["colorBG" . $i] ;
			$description=$_POST["description" . $i] ;
			
			//Validate Inputs
			if ($gibbonAlertLevelID=="" OR $name=="" OR $nameShort=="" OR $color=="" OR $colorBG=="") {
				$partialFail=TRUE ;
			}
			else {
				try {
					$dataUpdate=array("name"=>$name, "nameShort"=>$nameShort, "color"=>$color, "colorBG"=>$colorBG, "description"=>$description, "gibbonAlertLevelID"=>$gibbonAlertLevelID); 
					$sqlUpdate="UPDATE gibbonAlertLevel SET name=:name, nameShort=:nameShort, color=:color, colorBG=:colorBG, description=:description WHERE gibbonAlertLevelID=:gibbonAlertLevelID" ;
					$resultUpdate=$connection2->prepare($sqlUpdate);
					$resultUpdate->execute($dataUpdate);
				}
				catch(PDOException $e) { 
					$partialFail=FALSE ;
				}
			}
		}
		
		//Deal with failed update
		if ($partialFail==TRUE) {
			//Fail 4
			$URL.="&updateReturn=fail4" ;
			header("Location: {$URL}");
		}	
		else {
			//Success 0
			$URL.="&updateReturn=success0" ;
			header("Location: {$URL}");
		}
	}
}
?>