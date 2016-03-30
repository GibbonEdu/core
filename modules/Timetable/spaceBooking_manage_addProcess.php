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

//Module includes
include "./moduleFunctions.php" ;

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]["timezone"]);

$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/spaceBooking_manage_add.php" ;

if (isActionAccessible($guid, $connection2, "/modules/Timetable/spaceBooking_manage_add.php")==FALSE) {
	//Fail 0
	$URL.="&addReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	//Get action with highest precendence
	$highestAction=getHighestGroupedAction($guid, $_POST["address"], $connection2) ;
	if ($highestAction==FALSE) {
		//Fail 0
		$URL.="&updateReturn=fail0$params" ;
		header("Location: {$URL}");
	}
	else {
		//Proceed!
		$foreignKey=$_POST["foreignKey"] ;
		$foreignKeyID=$_POST["foreignKeyID"] ;
		$dates=$_POST["dates"] ;
		$timeStart=$_POST["timeStart"] ;
		$timeEnd=$_POST["timeEnd"] ;
		$repeat=$_POST["repeat"] ;
		$repeatDaily=NULL ;
		$repeatWeekly=NULL ;
		if ($repeat=="Daily") {
			$repeatDaily=$_POST["repeatDaily"] ;
		}
		else if ($repeat=="Weekly") {
			$repeatWeekly=$_POST["repeatWeekly"] ;
		}
		
		//Validate Inputs
		if ($foreignKey=="" OR $foreignKeyID=="" OR $timeStart=="" OR $timeEnd=="" OR $repeat=="" OR count($dates)<1) {
			//Fail 3
			$URL.="&addReturn=fail3" ;
			header("Location: {$URL}");
		}
		else {
			//Lock tables
			try {
				$sql="LOCK TABLE gibbonDaysOfWeek WRITE, gibbonSchoolYear WRITE, gibbonSchoolYearSpecialDay WRITE, gibbonSchoolYearTerm WRITE, gibbonTTColumnRow WRITE, gibbonTTDay WRITE, gibbonTTDayDate WRITE, gibbonTTDayRowClass WRITE, gibbonTTSpaceBooking WRITE, gibbonTTSpaceChange WRITE" ;
				$result=$connection2->query($sql);   
			}
			catch(PDOException $e) { 
				//Fail 2
				$URL.="&duplicateReturn=fail2" ;
				header("Location: {$URL}");
				exit() ;
			}	
					
			$failCount=0 ;
			$available="" ;
			//Scroll through all dates
			foreach ($dates AS $date) {
				$available=isSpaceFree($guid, $connection2, $foreignKey, $foreignKeyID, $date, $timeStart, $timeEnd) ;
				if ($available==FALSE) {
					$failCount++ ;
				}
				else {
					//Write to database
					try {
						$data=array("foreignKey"=>$foreignKey, "foreignKeyID"=>$foreignKeyID, "date"=>$date, "timeStart"=>$timeStart, "timeEnd"=>$timeEnd, "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
						$sql="INSERT INTO gibbonTTSpaceBooking SET foreignKey=:foreignKey, foreignKeyID=:foreignKeyID, date=:date, timeStart=:timeStart, timeEnd=:timeEnd, gibbonPersonID=:gibbonPersonID" ;
						$result=$connection2->prepare($sql);
						$result->execute($data);
					}
					catch(PDOException $e) { 
						$failCount++ ;
					}
				}
			}
			
			$successCount=count($dates)-$failCount ;
			
			//Unlock locked database tables
			try {
				$sql="UNLOCK TABLES" ;
				$result=$connection2->query($sql);   
			}
			catch(PDOException $e) { }	

			if ($successCount==0) {
				//Fail 4
				$URL.="&addReturn=fail4" ;
				header("Location: {$URL}");
			}
			else if ($successCount<count($dates)) {
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
}
?>