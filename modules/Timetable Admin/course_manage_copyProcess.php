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

$gibbonSchoolYearID=$_GET["gibbonSchoolYearID"] ;
$gibbonSchoolYearIDNext=$_GET["gibbonSchoolYearIDNext"] ;
$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Timetable Admin/course_manage.php&gibbonSchoolYearID=$gibbonSchoolYearIDNext" ;

if (isActionAccessible($guid, $connection2, "/modules/Timetable Admin/course_manage.php")==FALSE) {
	//Fail 0
	$URL.="&copyReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	//Proceed!
	//Check if school years specified (current and next)
	if ($gibbonSchoolYearID=="" OR $gibbonSchoolYearIDNext=="") {
		//Fail1
		$URL.="&copyReturn=fail1" ;
		header("Location: {$URL}");
	}
	else {
		//GET CURRENT COURSES
		try {
			$data=array("gibbonSchoolYearID"=>$gibbonSchoolYearID); 
			$sql="SELECT * FROM gibbonCourse WHERE gibbonSchoolYearID=:gibbonSchoolYearID" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			//Fail2
			$URL.="&copyReturn=fail2" ;
			header("Location: {$URL}");
			exit() ;
		}

		if ($result->rowCount()<1) {
			//Fail 2
			$URL.="&copyReturn=fail2" ;
			header("Location: {$URL}");
		}
		else {
			$partialFail=FALSE ;
			while ($row=$result->fetch()) {
				//Write to database
				try {
					$dataInsert=array("gibbonSchoolYearID"=>$gibbonSchoolYearIDNext, "gibbonDepartmentID"=>$row["gibbonDepartmentID"], "name"=>$row["name"], "nameShort"=>$row["nameShort"], "description"=>$row["description"], "gibbonYearGroupIDList"=>$row["gibbonYearGroupIDList"], "orderBy"=>$row["orderBy"]); 
					$sqlInsert="INSERT INTO gibbonCourse SET gibbonSchoolYearID=:gibbonSchoolYearID, gibbonDepartmentID=:gibbonDepartmentID, name=:name, nameShort=:nameShort, description=:description, gibbonYearGroupIDList=:gibbonYearGroupIDList, orderBy=:orderBy" ;
					$resultInsert=$connection2->prepare($sqlInsert);
					$resultInsert->execute($dataInsert);
				}
				catch(PDOException $e) { 
					$partialFail=TRUE ;
				}
				
				$AI=$connection2->lastInsertId();
				
				if ($AI!=NULL) {
					//NOW DEAL WITH CLASSES
					try {
						$dataClass=array("gibbonCourseID"=>$row["gibbonCourseID"]); 
						$sqlClass="SELECT * FROM gibbonCourseClass WHERE gibbonCourseID=:gibbonCourseID" ;
						$resultClass=$connection2->prepare($sqlClass);
						$resultClass->execute($dataClass);
					}
					catch(PDOException $e) { 
						$partialFail=TRUE ;
					}
					
					while ($rowClass=$resultClass->fetch()) {
						//Write to database
						try {
							$dataInsert=array("gibbonCourseID"=>$AI, "name"=>$rowClass["name"], "nameShort"=>$rowClass["nameShort"], "reportable"=>$rowClass["reportable"]); 
							$sqlInsert="INSERT INTO gibbonCourseClass SET gibbonCourseID=:gibbonCourseID, name=:name, nameShort=:nameShort, reportable=:reportable" ;
							$resultInsert=$connection2->prepare($sqlInsert);
							$resultInsert->execute($dataInsert);
						}
						catch(PDOException $e) { 
							$partialFail=TRUE ;
						}
					}

				}
			}
		
			if ($partialFail==TRUE) {
				//Fail 5
				$URL.="&copyReturn=fail5" ;
				header("Location: {$URL}");
			}
			else {
				//Success 0
				$URL.="&copyReturn=success0" ;
				header("Location: {$URL}");
			}
					
		}
	}
}
?>