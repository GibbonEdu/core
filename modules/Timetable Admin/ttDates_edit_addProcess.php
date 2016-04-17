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
$pdo = new sqlConnection();
$connection2 = $pdo->getConnection();

@session_start() ;

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]["timezone"]);

$gibbonSchoolYearID=$_POST["gibbonSchoolYearID"] ;
$dateStamp=$_POST["dateStamp"] ;
$gibbonTTDayID=$_POST["gibbonTTDayID"] ;

$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/ttDates_edit_add.php&gibbonSchoolYearID=$gibbonSchoolYearID&dateStamp=" . $dateStamp ;

if (isActionAccessible($guid, $connection2, "/modules/Timetable Admin/ttDates_edit_add.php")==FALSE) {
	//Fail 0
	$URL.="&addReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	//Proceed!
	//Validate Inputs
	if ($gibbonSchoolYearID=="" OR $dateStamp=="" OR $gibbonTTDayID=="") {
		//Fail 3
		$URL.="&addReturn=fail3" ;
		header("Location: {$URL}");
	}
	else {
		if (isSchoolOpen($guid, date("Y-m-d", $dateStamp), $connection2, TRUE)==FALSE) {
			//Fail 3
			$URL.="&addReturn=fail3" ;
			header("Location: {$URL}");
		}
		else {
			try {
				$data=array("gibbonSchoolYearID"=>$gibbonSchoolYearID); 
				$sql="SELECT * FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID" ;
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				//Fail 2
				$URL.="&addReturn=fail2" ;
				header("Location: {$URL}");
				exit() ;
			}

			if ($result->rowCount()!=1) {
				//Fail 2
				$URL.="&addReturn=fail2" ;
				header("Location: {$URL}");
			}
			else {
				//Write to database
				try {
					$data=array("gibbonTTDayID"=>$gibbonTTDayID, "date"=>date("Y-m-d", $dateStamp)); 
					$sql="INSERT INTO gibbonTTDayDate SET gibbonTTDayID=:gibbonTTDayID, date=:date" ;
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
}
?>