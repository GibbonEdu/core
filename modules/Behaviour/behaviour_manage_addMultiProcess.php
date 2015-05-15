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

$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/behaviour_manage_addMulti.php&gibbonPersonID=" . $_GET["gibbonPersonID"] . "&gibbonRollGroupID=" . $_GET["gibbonRollGroupID"] . "&gibbonYearGroupID=" . $_GET["gibbonYearGroupID"] . "&type=" .$_GET["type"] ;

if (isActionAccessible($guid, $connection2, "/modules/Behaviour/behaviour_manage_add.php")==FALSE) {
	//Fail 0
	$URL.="&addReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	//Proceed!
	if (isset($_POST["gibbonPersonIDMulti"])) {
		$gibbonPersonIDMulti=$_POST["gibbonPersonIDMulti"] ; 
	}
	else {
		$gibbonPersonIDMulti=NULL ; 
	}
	$date=$_POST["date"] ; 
	$type=$_POST["type"] ; 
	$descriptor=$_POST["descriptor"] ; 
	$level=$_POST["level"] ; 
	$comment=$_POST["comment"] ; 
	$followup=$_POST["followup"] ; 
		
	if (is_null($gibbonPersonIDMulti)==TRUE OR $date=="" OR $type=="" OR $descriptor=="") {
		//Fail 3
		$URL.="&addReturn=fail3" ;
		header("Location: {$URL}");
	}
	else {
		$partialFail=FALSE ;
		
		foreach ($gibbonPersonIDMulti AS $gibbonPersonID) {
			//Write to database
			try {
				$data=array("gibbonPersonID"=>$gibbonPersonID, "date"=>dateConvert($guid, $date), "type"=>$type, "descriptor"=>$descriptor, "level"=>$level, "comment"=>$comment, "followup"=>$followup, "gibbonPersonIDCreator"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
				$sql="INSERT INTO gibbonBehaviour SET gibbonPersonID=:gibbonPersonID, date=:date, type=:type, descriptor=:descriptor, level=:level, comment=:comment, followup=:followup, gibbonPersonIDCreator=:gibbonPersonIDCreator, gibbonSchoolYearID=:gibbonSchoolYearID" ;
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				$partialFail=TRUE ;	
			}
		}
		
		if ($partialFail==TRUE) {
			//Fail 5
			$URL.="&addReturn=fail5" ;
			header("Location: {$URL}");
		}
		else {
			//Success 0
			$URL.="&addReturn=success0" ;
			header("Location: {$URL}");
		}
	}
}
?>