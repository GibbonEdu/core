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

$gibbonSchoolYearID=$_GET["gibbonSchoolYearID"] ;
$dates=0 ;
if (isset($_POST["dates"])) {
	$dates=$_POST["dates"] ;
}
$gibbonTTDayID=$_POST["gibbonTTDayID"] ;
$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["q"]) . "/ttDates.php&gibbonSchoolYearID=$gibbonSchoolYearID" ;

if (isActionAccessible($guid, $connection2, "/modules/Timetable Admin/ttDates_edit_add.php")==FALSE) {
	$URL.="&return=error0" ;
	header("Location: {$URL}");
}
else {
	//Proceed!
	//Validate Inputs
	if ($gibbonSchoolYearID=="" OR $dates=="" OR count($dates)<1 OR $gibbonTTDayID=="") {
		$URL.="&return=error1" ;
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
			$URL.="&return=error2" ;
			header("Location: {$URL}");
			exit() ;
		}

		if ($result->rowCount()!=1) {
			$URL.="&return=error2" ;
			header("Location: {$URL}");
		}
		else {
			$partialFail=FALSE ;
			foreach ($dates AS $date) {
				if (isSchoolOpen($guid, date("Y-m-d", $date), $connection2, TRUE)==FALSE) {
					$partialFail=TRUE ;
				}
				else {
					//Check if a day from the TT is already set. Not enough time to add this now, but should do this one day
					
					//Write to database
					try {
						$data=array("gibbonTTDayID"=>$gibbonTTDayID, "date"=>date("Y-m-d", $date)); 
						$sql="INSERT INTO gibbonTTDayDate SET gibbonTTDayID=:gibbonTTDayID, date=:date" ;
						$result=$connection2->prepare($sql);
						$result->execute($data);
					}
					catch(PDOException $e) { 
						$partialFail=TRUE ;
					}
				}
			}
			
			//Report result
			if ($partialFail==TRUE) {
				$URL.="&return=warning1" ;
				header("Location: {$URL}");
			}
			else {
				$URL.="&return=success0" ;
				header("Location: {$URL}");
			}
		}
	}
}
?>