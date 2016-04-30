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

$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/spaceChange_manage_add.php" ;

if (isActionAccessible($guid, $connection2, "/modules/Timetable/spaceChange_manage_add.php")==FALSE) {
	$URL.="&return=error0" ;
	header("Location: {$URL}");
}
else {
	//Get action with highest precendence
	$highestAction=getHighestGroupedAction($guid, $_POST["address"], $connection2) ;
	if ($highestAction==FALSE) {
			$URL.="&return=error0$params" ;
		header("Location: {$URL}");
	}
	else {
		//Proceed!
		$gibbonTTDayRowClassID=substr($_POST["gibbonTTDayRowClassID"], 0, 12) ; 	
		$date=substr($_POST["gibbonTTDayRowClassID"], 13) ; 	
		$gibbonSpaceID=NULL ;
		if ($_POST["gibbonSpaceID"]!="") {
			$gibbonSpaceID=$_POST["gibbonSpaceID"] ;
		}
		
		//Validate Inputs
		if ($gibbonTTDayRowClassID=="" OR $date=="") {
			$URL.="&return=error1" ;
			header("Location: {$URL}");
		}
		else {
			//Check unique inputs for uniquness
			try {
				$data=array("gibbonTTDayRowClassID"=>$gibbonTTDayRowClassID, "date"=>$date); 
				$sql="SELECT * FROM gibbonTTSpaceChange WHERE gibbonTTDayRowClassID=:gibbonTTDayRowClassID AND date=:date" ;
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
					$data=array("gibbonTTDayRowClassID"=>$gibbonTTDayRowClassID, "date"=>$date, "gibbonSpaceID"=>$gibbonSpaceID, "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
					$sql="INSERT INTO gibbonTTSpaceChange SET gibbonTTDayRowClassID=:gibbonTTDayRowClassID, date=:date, gibbonSpaceID=:gibbonSpaceID, gibbonPersonID=:gibbonPersonID" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
							$URL.="&return=error2" ;
					header("Location: {$URL}");
					exit() ;
				}
			
					$URL.="&return=success0" ;
				header("Location: {$URL}");
			}
		}
	}
}
?>