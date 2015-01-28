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

$gibbonPlannerEntryID=$_GET["gibbonPlannerEntryID"] ;
$params="" ;
if (isset($_GET["date"])) {
	$params=$params."&date=" . $_GET["date"] ;
}
if (isset($_GET["viewBy"])) {
	$params=$params."&viewBy=" . $_GET["viewBy"] ;
}
if (isset($_GET["gibbonCourseClassID"])) {
	$params=$params."&gibbonCourseClassID=" . $_GET["gibbonCourseClassID"] ;
}
$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Planner/planner_view_full.php&gibbonPlannerEntryID=$gibbonPlannerEntryID$params" ;

if (isActionAccessible($guid, $connection2, "/modules/Planner/planner_view_full.php")==FALSE) {
	//Fail 0
	$URL.="&updateReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	//Proceed!
	//Check if planner specified
	if ($gibbonPlannerEntryID=="") {
		//Fail1
		$URL.="&updateReturn=fail1" ;
		header("Location: {$URL}");
	}
	else {
		try {
			$data=array("gibbonPlannerEntryID"=>$gibbonPlannerEntryID, "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
			$sql="SELECT * FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPersonID=:gibbonPersonID AND role='Student'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			//Fail2
			$URL.="&updateReturn=fail2" ;
			header("Location: {$URL}");
			break ;
		}

		if ($result->rowCount()!=1) {
			//Fail 2
			$URL.="&updateReturn=fail2" ;
			header("Location: {$URL}");
		}
		else {	
			//Get variables
			$homework=$_POST["homework"] ;
			if ($_POST["homework"]=="Yes") {
				$homework="Y" ;
				//Attempt to prevent XSS attack
				$homeworkDetails=$_POST["homeworkDetails"] ;
				$homeworkDetails=tinymceStyleStripTags($homeworkDetails, $connection2) ;
				if ($_POST["homeworkDueDateTime"]!="") {
					$homeworkDueDateTime=$_POST["homeworkDueDateTime"] . ":59" ;
				}
				else {
					$homeworkDueDateTime="21:00:00" ;
				}
				if ($_POST["homeworkDueDate"]!="") {
					$homeworkDueDate=dateConvert($guid, $_POST["homeworkDueDate"]) . " " . $homeworkDueDateTime ;
				}
			}
			else {
				$homework="N" ;
				$homeworkDueDate=NULL ;
				$homeworkDetails="" ;
			}
			
			
			if ($homework=="N") { //IF HOMEWORK NO, DELETE ANY RECORDS
				try {
					$data=array("gibbonPlannerEntryID"=>$gibbonPlannerEntryID, "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
					$sql="DELETE FROM gibbonPlannerEntryStudentHomework WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPersonID=:gibbonPersonID" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					//Fail2
					$URL.="&updateReturn=fail2" ;
					header("Location: {$URL}");
					break ;
				}
				
				//Success 0
				$URL.="&updateReturn=success0" ;
				header("Location: {$URL}");
			}
			else { //IF HOMEWORK YES, DEAL WITH RECORDS
				//Check for record
				try {
					$data=array("gibbonPlannerEntryID"=>$gibbonPlannerEntryID, "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
					$sql="SELECT * FROM gibbonPlannerEntryStudentHomework WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPersonID=:gibbonPersonID" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					//Fail2
					$URL.="&updateReturn=fail2" ;
					header("Location: {$URL}");
					break ;
				}
			
				if ($result->rowCount()>1) { //Error!
					//Fail2
					$URL.="&updateReturn=fail2" ;
					header("Location: {$URL}");
					break ;
				}
				if ($result->rowCount()==1) { //Exists, so update
					try {
						$data=array("gibbonPlannerEntryID"=>$gibbonPlannerEntryID, "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"], "homeworkDueDateTime"=>$homeworkDueDate, "homeworkDetails"=>$homeworkDetails); 
						$sql="UPDATE gibbonPlannerEntryStudentHomework SET homeworkDueDateTime=:homeworkDueDateTime, homeworkDetails=:homeworkDetails WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPersonID=:gibbonPersonID" ;
						$result=$connection2->prepare($sql);
						$result->execute($data);
					}
					catch(PDOException $e) { 
						//Fail 2
						$URL.="&updateReturn=fail2" ;
						header("Location: {$URL}");
						break ;
					}
					
					//Success 0
					$URL.="&updateReturn=success0" ;
					header("Location: {$URL}");
				}
				else { //Does not exist, so create
					//Write to database
					try {
						$data=array("gibbonPlannerEntryID"=>$gibbonPlannerEntryID, "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"], "homeworkDueDateTime"=>$homeworkDueDate, "homeworkDetails"=>$homeworkDetails); 
						$sql="INSERT INTO gibbonPlannerEntryStudentHomework SET gibbonPlannerEntryID=:gibbonPlannerEntryID, gibbonPersonID=:gibbonPersonID, homeworkDueDateTime=:homeworkDueDateTime, homeworkDetails=:homeworkDetails" ;
						$result=$connection2->prepare($sql);
						$result->execute($data);
					}
					catch(PDOException $e) { 
						//Fail 2
						$URL.="&updateReturn=fail2" ;
						header("Location: {$URL}");
						break ;
					}
					
					//Success 0
					$URL.="&updateReturn=success0" ;
					header("Location: {$URL}");
				}
			}
		}
	}
}
?>