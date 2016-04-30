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
$dateStamp=$_GET["dateStamp"] ;
$gibbonTTDayID=$_GET["gibbonTTDayID"] ;

if ($gibbonSchoolYearID=="" OR $dateStamp=="") {
	print "Fatal error loading this page!" ;
}
else {

	$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/ttDates_edit.php&gibbonSchoolYearID=$gibbonSchoolYearID&dateStamp=$dateStamp" ;
	
	if (isActionAccessible($guid, $connection2, "/modules/Timetable Admin/ttDates_edit_delete.php")==FALSE) {
			$URL.="&return=error0" ;
		header("Location: {$URL}");
	}
	else {
		//Proceed!
		//Check if school year specified
		if ($gibbonTTDayID=="") {
				$URL.="&return=error1" ;
			header("Location: {$URL}");
		}
		else {
			try {
				$data=array("date"=>date("Y-m-d",$dateStamp), "gibbonTTDayID"=>$gibbonTTDayID); 
				$sql="SELECT * FROM gibbonTTDayDate WHERE gibbonTTDayID=:gibbonTTDayID AND date=:date" ;
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
					$URL.="&return=error2" ;
				header("Location: {$URL}");
				exit() ;
			}
			
			if ($result->rowCount()<1) {
					$URL.="&return=error2" ;
				header("Location: {$URL}");
			}
			else {
				//Write to database
				try {
					$data=array("date"=>date("Y-m-d",$dateStamp), "gibbonTTDayID"=>$gibbonTTDayID); 
					$sql="DELETE FROM gibbonTTDayDate WHERE gibbonTTDayID=:gibbonTTDayID AND date=:date" ;
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