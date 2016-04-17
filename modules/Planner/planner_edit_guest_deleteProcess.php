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

$gibbonPlannerEntryID=$_GET["gibbonPlannerEntryID"] ;
$gibbonPlannerEntryGuestID=$_GET["gibbonPlannerEntryGuestID"] ;
$viewBy=$_GET["viewBy"] ;
$subView=$_GET["subView"] ;
if ($viewBy!="date" AND $viewBy!="class") {
	$viewBy="date" ;
}
$gibbonCourseClassID=$_GET["gibbonCourseClassID"] ;
$date=$_GET["date"] ;
$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["address"]) . "/planner_edit.php&gibbonPlannerEntryID=$gibbonPlannerEntryID" ;

//Params to pass back (viewBy + date or classID)
if ($viewBy=="date") {
	$params="&viewBy=$viewBy&date=$date" ;
}
else {
	$params="&viewBy=$viewBy&gibbonCourseClassID=$gibbonCourseClassID&subView=$subView" ;
}
	
if (isActionAccessible($guid, $connection2, "/modules/Planner/planner_edit.php")==FALSE) {
	//Fail 0
	$URL.="&deleteReturn=fail0$params" ;
	header("Location: {$URL}");
}
else {
	$highestAction=getHighestGroupedAction($guid, $_GET["address"], $connection2) ;
	if ($highestAction==FALSE) {
		//Fail 0
		$URL.="&updateReturn=fail0$params" ;
		header("Location: {$URL}");
	}
	else {
		//Proceed!
		
		//Check if school year specified
		if ($gibbonPlannerEntryID=="" OR $gibbonPlannerEntryGuestID=="" OR ($viewBy=="class" AND $gibbonCourseClassID=="Y")) {
			//Fail1
			$URL.="&deleteReturn=fail1$params" ;
			header("Location: {$URL}");
		}
		else {
			try {
				if ($viewBy=="date") {
					if ($highestAction=="Lesson Planner_viewEditAllClasses" ) {
						$data=array("date"=>$date, "gibbonPlannerEntryID"=>$gibbonPlannerEntryID); 
						$sql="SELECT gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE date=:date AND gibbonPlannerEntryID=:gibbonPlannerEntryID" ;
					}
					else {
						$data=array("date"=>$date, "gibbonPlannerEntryID"=>$gibbonPlannerEntryID, "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
						$sql="SELECT gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, role FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND role='Teacher' AND date=:date AND gibbonPlannerEntryID=:gibbonPlannerEntryID" ;
					}
				}
				else {
					if ($highestAction=="Lesson Planner_viewEditAllClasses" ) {
						$data=array("gibbonCourseClassID"=>$gibbonCourseClassID, "gibbonPlannerEntryID"=>$gibbonPlannerEntryID); 
						$sql="SELECT gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonPlannerEntry.gibbonCourseClassID=:gibbonCourseClassID AND gibbonPlannerEntryID=:gibbonPlannerEntryID" ;
					}
					else {
						$data=array("gibbonCourseClassID"=>$gibbonCourseClassID, "gibbonPlannerEntryID"=>$gibbonPlannerEntryID, "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
						$sql="SELECT gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, role FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND role='Teacher' AND gibbonPlannerEntry.gibbonCourseClassID=:gibbonCourseClassID AND gibbonPlannerEntryID=:gibbonPlannerEntryID" ;
					}
				}
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				//Fail2
				$URL.="&deleteReturn=fail2$params" ;
				header("Location: {$URL}");
				exit() ;
			}
		
			if ($result->rowCount()!=1) {
				//Fail 2
				$URL.="&deleteReturn=fail2$params" ;
				header("Location: {$URL}");
			}
			else {
				//Write to database
				try {
					$data=array("gibbonPlannerEntryGuestID"=>$gibbonPlannerEntryGuestID); 
					$sql="DELETE FROM gibbonPlannerEntryGuest WHERE gibbonPlannerEntryGuestID=:gibbonPlannerEntryGuestID" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					//Fail 2
					$URL.="&deleteReturn=fail2$params" ;
					header("Location: {$URL}");
					exit() ; 
				}
				
				//Success 0
				$URL.="&deleteReturn=success0$params" ;
				header("Location: {$URL}");
			}
		}
	}
}
?>