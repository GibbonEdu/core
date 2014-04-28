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
include $_SESSION[$guid]["absolutePath"] . "/modules/" . getModuleName($_GET["address"]) . "/moduleFunctions.php" ;

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]["timezone"]);

$gibbonPlannerEntryID=$_GET["gibbonPlannerEntryID"] ;
$gibbonPersonID=$_SESSION[$guid]["gibbonPersonID"] ;
$viewBy=NULL ;
if (isset($_GET["viewBy"])) {
	$viewBy=$_GET["viewBy"] ;
}
$subView=NULL ;
if (isset($_GET["subView"])) {
	$subView=$_GET["subView"] ;
}
if ($viewBy!="date" AND $viewBy!="class") {
	$viewBy="date" ;
}
$gibbonCourseClassID=NULL ;
if (isset($_GET["gibbonCourseClassID"])) {
	$gibbonCourseClassID=$_GET["gibbonCourseClassID"] ;
}
$date=$_GET["date"] ;
$returnToIndex=NULL ;
if (isset($_GET["returnToIndex"])) {
	$returnToIndex=$_GET["returnToIndex"] ;
}
$gibbonPersonID2=NULL ;
if (isset($_GET["gibbonPersonID"])) {
	$gibbonPersonID2=$_GET["gibbonPersonID"] ;
}
if ($returnToIndex=="Y") {
	$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?blank=blank" ;
	$params="&viewBy=$viewBy&gibbonCourseClassID=$gibbonCourseClassID&subView=$subView&search=$gibbonPersonID" ;
}
else {
	$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["address"]) . "/planner.php&gibbonPlannerEntryID=$gibbonPlannerEntryID" ;
	
	//Params to pass back (viewBy + date or classID)
	if ($viewBy=="date") {
		$params="&viewBy=$viewBy&date=$date&search=" . $gibbonPersonID2 ;
	}
	else {
		$params="&viewBy=$viewBy&gibbonCourseClassID=$gibbonCourseClassID&subView=$subView&search=$gibbonPersonID" ;
	}
}
	
if (isActionAccessible($guid, $connection2, "/modules/Planner/planner.php")==FALSE) {
	//Fail 0
	$URL=$URL . "&updateReturn=fail0$params" ;
	header("Location: {$URL}");
}
else {
	//Proceed!
	//Check if school year specified
	if ($gibbonPlannerEntryID=="" OR ($viewBy=="class" AND $gibbonCourseClassID=="")) {
		//Fail1
		$URL=$URL . "&updateReturn=fail1$params" ;
		header("Location: {$URL}");
	}
	else {
		//Get action with highest precendence
		$highestAction=getHighestGroupedAction($guid, $_GET["address"], $connection2) ;
		if ($highestAction==FALSE) {
			//Fail 0
			$URL=$URL . "&updateReturn=fail0$params" ;
			header("Location: {$URL}");
		}
		else {
			$proceed=true ;
			if ($highestAction=="Lesson Planner_viewMyChildrensClasses") {
				
				try {
					$dataChild=array("gibbonPersonID1"=>$gibbonPersonID, "gibbonPersonID2"=>$_SESSION[$guid]["gibbonPersonID"] ); 
					$sqlChild="SELECT * FROM gibbonFamilyChild JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonFamilyChild.gibbonPersonID=:gibbonPersonID1 AND gibbonFamilyAdult.gibbonPersonID=:gibbonPersonID2 AND childDataAccess='Y'" ;
					$resultChild=$connection2->prepare($sqlChild);
					$resultChild->execute($dataChild);
				}
				catch(PDOException $e) { 
					$proceed=false ;
				}
				if ($resultChild->rowCount()!=1) {
					$proceed=false ;
				}
				else {
					$sql="(SELECT gibbonPlannerEntry.gibbonPlannerEntryID, gibbonCourseClass.gibbonCourseClassID, gibbonUnitID, gibbonHookID, gibbonPlannerEntry.gibbonCourseClassID, gibbonPlannerEntry.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, date, timeStart, timeEnd, summary, gibbonPlannerEntry.description, teachersNotes, homework, homeworkDueDateTime, homeworkDetails, viewableStudents, viewableParents, role, homeworkSubmission, homeworkSubmissionDateOpen, homeworkSubmissionDrafts, homeworkSubmissionType FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourseClassPerson.gibbonPersonID=$gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left' AND gibbonPlannerEntry.gibbonPlannerEntryID=$gibbonPlannerEntryID) UNION (SELECT gibbonPlannerEntry.gibbonPlannerEntryID, gibbonCourseClass.gibbonCourseClassID, gibbonUnitID, gibbonHookID, gibbonPlannerEntry.gibbonCourseClassID, gibbonPlannerEntry.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, date, timeStart, timeEnd, summary, gibbonPlannerEntry.description, teachersNotes, homework, homeworkDueDateTime, homeworkDetails, viewableStudents, viewableParents, role, homeworkSubmission, homeworkSubmissionDateOpen, homeworkSubmissionDrafts, homeworkSubmissionType FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonPlannerEntryGuest ON (gibbonPlannerEntryGuest.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE date='$date' AND gibbonPlannerEntryGuest.gibbonPersonID=$gibbonPersonID AND gibbonPlannerEntry.gibbonPlannerEntryID=$gibbonPlannerEntryID) ORDER BY date, timeStart" ; 
				}
			}
			if ($viewBy=="date") {
				if ($highestAction=="Lesson Planner_viewEditAllClasses" OR $highestAction=="Lesson Planner_viewAllEditMyClasses") {
					$sql="SELECT gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonPlannerEntryID=$gibbonPlannerEntryID" ;
				}
				else if ($highestAction=="Lesson Planner_viewMyClasses") {
					$sql="SELECT gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, role FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourseClassPerson.gibbonPersonID=$gibbonPersonID AND NOT role='Teacher' AND gibbonPlannerEntryID=$gibbonPlannerEntryID" ;
				}
			}
			else {
				if ($highestAction=="Lesson Planner_viewEditAllClasses" OR $highestAction=="Lesson Planner_viewAllEditMyClasses") {
					$sql="SELECT gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonPlannerEntry.gibbonCourseClassID='$gibbonCourseClassID' AND gibbonPlannerEntryID=$gibbonPlannerEntryID" ;
				}
				else if ($highestAction=="Lesson Planner_viewMyClasses") {
					$sql="SELECT gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, role FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourseClassPerson.gibbonPersonID=$gibbonPersonID AND NOT role='Teacher' AND gibbonPlannerEntry.gibbonCourseClassID='$gibbonCourseClassID' AND gibbonPlannerEntryID=$gibbonPlannerEntryID" ;
				}
			}
			
			if ($proceed==false) {
				//Fail2
				$URL=$URL . "&updateReturn=fail2$params" ;
				header("Location: {$URL}");
			}
			else {
				try {
					$data=array() ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					//Fail 2
					$URL=$URL . "&updateReturn=fail2$params" ;
					header("Location: {$URL}");
					break ;
				}
				
				if ($result->rowCount()!=1) {
					//Fail 2
					$URL=$URL . "&updateReturn=fail2$params" ;
					header("Location: {$URL}");
				}
				else {
					//Check like statatus
					try {
						$dataLike=array("gibbonPlannerEntryID"=>$gibbonPlannerEntryID, "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
						$sqlLike="SELECT * FROM gibbonPlannerEntryLike WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPersonID=:gibbonPersonID" ;
						$resultLike=$connection2->prepare($sqlLike);
						$resultLike->execute($dataLike);
					}
					catch(PDOException $e) { 
						//Fail2
						$URL=$URL . "&updateReturn=fail2$params" ;
						header("Location: {$URL}");
						break ;
					}

					//INSERT
					if ($resultLike->rowCount()!=1) {
						try {
							$data=array("gibbonPlannerEntryID"=>$gibbonPlannerEntryID, "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
							$sql="INSERT INTO gibbonPlannerEntryLike SET gibbonPlannerEntryID=:gibbonPlannerEntryID, gibbonPersonID=:gibbonPersonID" ;
							$result=$connection2->prepare($sql);
							$result->execute($data);
						}
						catch(PDOException $e) { 
							//Fail 2
							$URL=$URL . "&updateReturn=fail2$params" ;
							header("Location: {$URL}");
							break ;
						}

						//Success 0
						$URL=$URL . "$params" ;
						header("Location: {$URL}");
					}
					//DELETE
					else {
						try {
							$data=array("gibbonPlannerEntryID"=>$gibbonPlannerEntryID, "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
							$sql="DELETE FROM gibbonPlannerEntryLike WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPersonID=:gibbonPersonID" ;
							$result=$connection2->prepare($sql);
							$result->execute($data);
						}
						catch(PDOException $e) { 
							//Fail 2
							$URL=$URL . "&updateReturn=fail2$params" ;
							header("Location: {$URL}");
							break ;
						}

						//Success 0
						$URL=$URL . "$params" ;
						header("Location: {$URL}");
					}
				}
			}
		}
	}
}
?>