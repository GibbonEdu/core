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

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]["timezone"]);

$gibbonTTSpaceChangeID=$_GET["gibbonTTSpaceChangeID"] ;
$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/spaceChange_manage_delete.php&gibbonTTSpaceChangeID=" . $gibbonTTSpaceChangeID ;
$URLDelete=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/spaceChange_manage.php" ;

if (isActionAccessible($guid, $connection2, "/modules/Timetable/spaceChange_manage_delete.php")==FALSE) {
	//Fail 0
	$URL=$URL . "&deleteReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	//Get action with highest precendence
	$highestAction=getHighestGroupedAction($guid, $_POST["address"], $connection2) ;
	if ($highestAction==FALSE) {
		//Fail 0
		$URL=$URL . "&updateReturn=fail0$params" ;
		header("Location: {$URL}");
	}
	else {
		//Proceed!
		//Check if school year specified
		if ($gibbonTTSpaceChangeID=="") {
			//Fail1
			$URL=$URL . "&deleteReturn=fail1" ;
			header("Location: {$URL}");
		}
		else {
			try {
				if ($highestAction=="Manage Space Changes_allClasses") {
					$data=array("gibbonTTSpaceChangeID"=>$gibbonTTSpaceChangeID); 
					$sql="SELECT gibbonTTSpaceChangeID, gibbonTTSpaceChange.date, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, spaceOld.name AS spaceOld, spaceNew.name AS spaceNew FROM gibbonTTSpaceChange JOIN gibbonTTDayRowClass ON (gibbonTTSpaceChange.gibbonTTDayRowClassID=gibbonTTDayRowClass.gibbonTTDayRowClassID) JOIN gibbonCourseClass ON (gibbonTTDayRowClass.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) LEFT JOIN gibbonSpace AS spaceOld ON (gibbonTTDayRowClass.gibbonSpaceID=spaceOld.gibbonSpaceID) LEFT JOIN gibbonSpace AS spaceNew ON (gibbonTTSpaceChange.gibbonSpaceID=spaceNew.gibbonSpaceID) WHERE gibbonTTSpaceChangeID=:gibbonTTSpaceChangeID ORDER BY date, course, class" ; 
				}
				else {
					$data=array("gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonTTSpaceChangeID"=>$gibbonTTSpaceChangeID); 
					$sql="SELECT gibbonTTSpaceChangeID, gibbonTTSpaceChange.date, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, spaceOld.name AS spaceOld, spaceNew.name AS spaceNew FROM gibbonTTSpaceChange JOIN gibbonTTDayRowClass ON (gibbonTTSpaceChange.gibbonTTDayRowClassID=gibbonTTDayRowClass.gibbonTTDayRowClassID)  JOIN gibbonCourseClass ON (gibbonTTDayRowClass.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) LEFT JOIN gibbonSpace AS spaceOld ON (gibbonTTDayRowClass.gibbonSpaceID=spaceOld.gibbonSpaceID) LEFT JOIN gibbonSpace AS spaceNew ON (gibbonTTSpaceChange.gibbonSpaceID=spaceNew.gibbonSpaceID) WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND gibbonTTSpaceChangeID=:gibbonTTSpaceChangeID ORDER BY date, course, class" ; 
				}
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				//Fail2
				$URL=$URL . "&deleteReturn=fail2" ;
				header("Location: {$URL}");
				break ;
			}

			if ($result->rowCount()!=1) {
				//Fail 2
				$URL=$URL . "&deleteReturn=fail2" ;
				header("Location: {$URL}");
			}
			else {
				//Write to database
				try {
					$data=array("gibbonTTSpaceChangeID"=>$gibbonTTSpaceChangeID); 
					$sql="DELETE FROM gibbonTTSpaceChange WHERE gibbonTTSpaceChangeID=:gibbonTTSpaceChangeID" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					//Fail2
					$URL=$URL . "&deleteReturn=fail2" ;
					header("Location: {$URL}");
					break ;
				}
	
				//Success 0
				$URLDelete=$URLDelete . "&deleteReturn=success0" ;
				header("Location: {$URLDelete}");
			}
		}
	}
}
?>