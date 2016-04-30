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

//Gibbon system-wide includes
include "../../functions.php" ;
include "../../config.php" ;

//Module includes
include "./moduleFunctions.php" ;

//New PDO DB connection
$pdo = new Gibbon\sqlConnection();
$connection2 = $pdo->getConnection();

@session_start() ;

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]["timezone"]);

$gibbonPlannerEntryID=$_GET["gibbonPlannerEntryID"] ;
$gibbonPlannerEntryDiscussID=$_GET["gibbonPlannerEntryDiscussID"] ;
$date=$_GET["date"] ;
$gibbonCourseClassID=$_GET["gibbonCourseClassID"] ;
$viewBy=$_GET["viewBy"] ;
$subView=$_GET["subView"] ;
$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Planner/planner_view_full.php&gibbonPlannerEntryID=$gibbonPlannerEntryID&search=" . $_GET["search"] . "&date=$date&viewBy=$viewBy&subView=$subView&gibbonCourseClassID=$gibbonCourseClassID" ;

if (isActionAccessible($guid, $connection2, "/modules/Planner/planner_view_full.php")==FALSE) {
	$URL.="&return=error0" ;
	header("Location: {$URL}");
}
else {
	//Proceed!
	//Check if planner specified
	if ($gibbonPlannerEntryID=="" OR $gibbonPlannerEntryDiscussID=="") {
		$URL.="&return=error1" ;
		header("Location: {$URL}");
	}
	else {
		try {
			$data=array("gibbonPlannerEntryID"=>$gibbonPlannerEntryID); 
			$sql="SELECT * FROM gibbonPlannerEntry WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID" ;
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
			//INSERT
			try {
				$data=array("gibbonPlannerEntryDiscussID"=>$gibbonPlannerEntryDiscussID); 
				$sql="DELETE FROM gibbonPlannerEntryDiscuss WHERE gibbonPlannerEntryDiscussID=:gibbonPlannerEntryDiscussID" ;
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
?>