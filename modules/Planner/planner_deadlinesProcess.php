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

$viewBy=$_GET["viewBy"] ;
$subView=$_GET["subView"] ;
if ($viewBy!="date" AND $viewBy!="class") {
	$viewBy="date" ;
}
$gibbonCourseClassID=NULL ;
if (isset($_POST["gibbonCourseClassID"])) {
	$gibbonCourseClassID=$_POST["gibbonCourseClassID"] ;
}
$date=NULL ;
if (isset($_POST["date"])) {
	$date=dateConvert($guid, $_POST["date"]) ;
}
$gibbonCourseClassIDFilter=NULL ;
if (isset($_GET["gibbonCourseClassIDFilter"])) {
	$gibbonCourseClassIDFilter=$_GET["gibbonCourseClassIDFilter"] ;
}

$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["address"]) . "/planner_deadlines.php&gibbonCourseClassIDFilter=$gibbonCourseClassIDFilter" ;

//Params to pass back (viewBy + date or classID)
if ($viewBy=="date") {
	$params="&viewBy=$viewBy&date=$date" ;
}
else {
	$params="&viewBy=$viewBy&gibbonCourseClassID=$gibbonCourseClassID&subView=$subView" ;
}

if (isActionAccessible($guid, $connection2, "/modules/Planner/planner_deadlines.php")==FALSE) {
	print "gere" ;
	$URL.="&return=error0$params" ;
	header("Location: {$URL}");
}
else {
	$category=getRoleCategory($_SESSION[$guid]["gibbonRoleIDCurrent"], $connection2) ;
	if ($category!="Student") {
			$URL.="&return=error0$params" ;
		header("Location: {$URL}");
	}
	else {
		//Check for existing completion
		$completionArray=array() ;
		try {
			$dataCompletion=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonSchoolYearID2"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonID2"=>$_SESSION[$guid]["gibbonPersonID"] ); 
			$sqlCompletion="
			(SELECT 'teacherRecorded' AS type, gibbonPlannerEntryStudentTracker.gibbonPlannerEntryID FROM gibbonPlannerEntryStudentTracker JOIN gibbonPlannerEntry ON (gibbonPlannerEntryStudentTracker.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID) JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID AND homeworkComplete='Y')
			UNION
			(SELECT 'studentRecorded' AS type, gibbonPlannerEntry.gibbonPlannerEntryID FROM gibbonPlannerEntryStudentHomework JOIN gibbonPlannerEntry ON (gibbonPlannerEntryStudentHomework.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID) JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID2 AND gibbonPersonID=:gibbonPersonID2 AND homeworkComplete='Y')
			ORDER BY gibbonPlannerEntryID, type
			" ;
			$resultCompletion=$connection2->prepare($sqlCompletion);
			$resultCompletion->execute($dataCompletion);
		}
		catch(PDOException $e) { 
			$URL.="&return=error2$params" ;
			header("Location: {$URL}");
			exit() ;
		}
		
		while ($rowCompletion=$resultCompletion->fetch()) {
			if (isset($rowCompletion["gibbonPlannerEntryID"])) {
				$completionArray[$rowCompletion["gibbonPlannerEntryID"]]="Y" ;
			}
		}
		
		$partialFail=false ;
		
		//Insert new records
		foreach ($_POST["count"] as $count) {
			if (isset($_POST["complete-$count"])) {
				if ($_POST["complete-$count"]=="on") {
					if (isset($completionArray[$_POST["gibbonPlannerEntryID-$count"]])==FALSE) {
						if (@$_POST["completeType-$count"]=="teacherRecorded") { //Teacher recorded
							try {
								$data=array("gibbonPlannerEntryID"=>$_POST["gibbonPlannerEntryID-$count"], "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
								$sql="INSERT INTO gibbonPlannerEntryStudentTracker SET gibbonPlannerEntryID=:gibbonPlannerEntryID, gibbonPersonID=:gibbonPersonID, homeworkComplete='Y'" ;
								$result=$connection2->prepare($sql);
								$result->execute($data);
							}
							catch(PDOException $e) { 
								$partialFail=TRUE ;
							}
						}
						else { //Student recorded
							try {
								$data=array("gibbonPlannerEntryID"=>$_POST["gibbonPlannerEntryID-$count"], "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
								$sql="UPDATE gibbonPlannerEntryStudentHomework SET homeworkComplete='Y' WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPersonID=:gibbonPersonID" ;
								$result=$connection2->prepare($sql);
								$result->execute($data);
							}
							catch(PDOException $e) { 
								$partialFail=TRUE ;
							}
						}
					}
				}
			}
		}
		
		//Turn unchecked records off
		foreach ($_POST["count"] as $count) {
			if (isset($completionArray[$_POST["gibbonPlannerEntryID-$count"]])) {
				if ($completionArray[$_POST["gibbonPlannerEntryID-$count"]]=="Y") {
					if (isset($_POST["complete-$count"])==FALSE) {
						if (@$_POST["completeType-$count"]=="teacherRecorded") { //Teacher recorded
							try {
								$data=array("gibbonPlannerEntryID"=>$_POST["gibbonPlannerEntryID-$count"], "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
								$sql="UPDATE gibbonPlannerEntryStudentTracker SET homeworkComplete='N' WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPersonID=:gibbonPersonID" ;
								$result=$connection2->prepare($sql);
								$result->execute($data);
							}
							catch(PDOException $e) { 
								$partialFail=TRUE ;
							}
						}
						else { //Student recorded
							try {
								$data=array("gibbonPlannerEntryID"=>$_POST["gibbonPlannerEntryID-$count"], "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
								$sql="UPDATE gibbonPlannerEntryStudentHomework SET homeworkComplete='N' WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPersonID=:gibbonPersonID" ;
								$result=$connection2->prepare($sql);
								$result->execute($data);
							}
							catch(PDOException $e) { 
								$partialFail=TRUE ;
							}
						}
					}
				}
			}
		}
		
		if ($partialFail==TRUE) {
			$URL.="&return=warning1$params" ;
			header("Location: {$URL}");
		}
		else {
			$URL.="&return=success0$params" ;
			header("Location: {$URL}");
		}
	}
}
?>