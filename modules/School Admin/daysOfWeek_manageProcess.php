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

$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/daysOfWeek_manage.php" ;

if (isActionAccessible($guid, $connection2, "/modules/School Admin/daysOfWeek_manage.php")==FALSE) {
	//Fail 0
	$URL.="&updateReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	//Proceed!
	try {
		$data=array(); 
		$sql="SELECT * FROM gibbonDaysOfWeek WHERE name='Monday' OR name='Tuesday' OR name='Wednesday' OR name='Thursday' OR name='Friday' OR name='Saturday' OR name='Sunday' ORDER BY sequenceNumber" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		//Fail2
		$URL.="&updateReturn=fail2" ;
		header("Location: {$URL}");
		exit() ;
	}

	if ($result->rowCount()!=7) {
		//Fail 2
		$URL.="&updateReturn=fail2" ;
		header("Location: {$URL}");
	}
	else {
		$valid=TRUE ;
		$unqiue=TRUE ;
		$update=TRUE ;
		
		while ($row=$result->fetch()) {
			$name=$row["name"] ;
			$sequenceNumber=$_POST[$name."sequenceNumber"] ;
			$schoolDay=$_POST[$name."schoolDay"] ;
			
			if (is_numeric($_POST[$name."schoolOpenH"]) AND is_numeric($_POST[$name."schoolOpenM"])) {
				$schoolOpen=$_POST[$name."schoolOpenH"] . ":" . $_POST[$name."schoolOpenM"] . ":00" ;
			}
			else {
				$schoolOpen=NULL ;
			}
			
			if (is_numeric($_POST[$name."schoolStartH"]) AND is_numeric($_POST[$name."schoolStartM"])) {
				$schoolStart=$_POST[$name."schoolStartH"] . ":" . $_POST[$name."schoolStartM"] . ":00" ;
			}
			else {
				$schoolStart=NULL ;
			}
			
			if (is_numeric($_POST[$name."schoolEndH"]) AND is_numeric($_POST[$name."schoolEndM"])) {
				$schoolEnd=$_POST[$name."schoolEndH"] . ":" . $_POST[$name."schoolEndM"] . ":00" ;
			}
			else {
				$schoolEnd=NULL ;
			}
			
			if (is_numeric($_POST[$name."schoolCloseH"]) AND is_numeric($_POST[$name."schoolCloseM"])) {
				$schoolClose=$_POST[$name."schoolCloseH"] . ":" . $_POST[$name."schoolCloseM"] . ":00" ;
			}
			else {
				$schoolClose=NULL ;
			}
			
			//Validate Inputs
			if ($sequenceNumber=="" OR is_numeric($sequenceNumber)==FALSE OR ($schoolDay!='Y' AND $schoolDay!="N")) {
				$valid=FALSE ;
			}
			
			//Run SQL
			try {
				$dataUpdate=array("sequenceNumber"=>$sequenceNumber, "schoolDay"=>$schoolDay, "schoolOpen"=>$schoolOpen, "schoolStart"=>$schoolStart, "schoolEnd"=>$schoolEnd, "schoolClose"=>$schoolClose, "name"=>$name); 
				$sqlUpdate="UPDATE gibbonDaysOfWeek SET sequenceNumber=:sequenceNumber, schoolDay=:schoolDay, schoolOpen=:schoolOpen, schoolStart=:schoolStart, schoolEnd=:schoolEnd, schoolClose=:schoolClose WHERE name=:name" ;
				$resultUpdate=$connection2->prepare($sqlUpdate);
				$resultUpdate->execute($dataUpdate);
			}
			catch(PDOException $e) { 
				$update=FALSE ;
			}
		}
		
		//Deal with invalid or not unique
		if ($valid!=TRUE) {
			//Fail 3
			$URL.="&updateReturn=fail3" ;
			header("Location: {$URL}");
		}	
		else {
			//Deal with failed update
			if ($update!=TRUE) {
				//Fail 2
				$URL.="&updateReturn=fail2" ;
				header("Location: {$URL}");
			}	
			else {
				//Success 0
				$URL.="&updateReturn=success0" ;
				header("Location: {$URL}");
			}
		}
	}
}
?>