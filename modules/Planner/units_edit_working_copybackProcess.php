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

$gibbonSchoolYearID=$_GET["gibbonSchoolYearID"];
$gibbonCourseID=$_GET["gibbonCourseID"]; 
$gibbonCourseClassID=$_GET["gibbonCourseClassID"]; 
$gibbonUnitID=$_GET["gibbonUnitID"]; 
$gibbonUnitBlockID=$_GET["gibbonUnitBlockID"]; 
$gibbonUnitClassBlockID=$_GET["gibbonUnitClassBlockID"]; 
$gibbonUnitClassID=$_GET["gibbonUnitClassID"]; 

$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Planner/units_edit_working_copyback.php&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonCourseID=$gibbonCourseID&gibbonCourseClassID=$gibbonCourseClassID&gibbonUnitID=$gibbonUnitID&gibbonUnitBlockID=$gibbonUnitBlockID&gibbonUnitClassBlockID=$gibbonUnitClassBlockID&gibbonUnitClassID=$gibbonUnitClassID" ;

if (isActionAccessible($guid, $connection2, "/modules/Planner/units_edit_working_copyback.php")==FALSE) {
	//Fail 0
	$URL=$URL . "&copyReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	$highestAction=getHighestGroupedAction($guid, "/modules/Planner/units_edit_working_copyback.php", $connection2) ;
	if ($highestAction==FALSE) {
		//Fail 0
		$URL=$URL . "&copyReturn=fail0" ;
		header("Location: {$URL}");
	}
	else {
		//Proceed!
		//Validate Inputs
		if ($gibbonSchoolYearID=="" OR $gibbonCourseID=="" OR $gibbonUnitID=="" OR $gibbonCourseClassID=="" OR $gibbonUnitClassID=="") {
			//Fail 3
			$URL=$URL . "&copyReturn=fail3" ;
			header("Location: {$URL}");
		}
		else {
			//Check access to specified course
			try {
				if ($highestAction=="Manage Units_all") {
					$data=array("gibbonSchoolYearID"=>$gibbonSchoolYearID, "gibbonCourseID"=>$gibbonCourseID); 
					$sql="SELECT * FROM gibbonCourse WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseID=:gibbonCourseID" ;
				}
				else if ($highestAction=="Manage Units_learningAreas") {
					$data=array("gibbonSchoolYearID"=>$gibbonSchoolYearID, "gibbonCourseID"=>$gibbonCourseID, "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
					$sql="SELECT gibbonCourseID, gibbonCourse.name, gibbonCourse.nameShort FROM gibbonCourse JOIN gibbonDepartment ON (gibbonCourse.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE gibbonDepartmentStaff.gibbonPersonID=:gibbonPersonID AND (role='Coordinator' OR role='Assistant Coordinator' OR role='Teacher (Curriculum)') AND gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseID=:gibbonCourseID ORDER BY gibbonCourse.nameShort" ;
				}
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				//Fail 2
				$URL=$URL . "&copyReturn=fail2" ;
				header("Location: {$URL}");
				break ;
			}
			
			if ($result->rowCount()!=1) {
				//Fail 4
				$URL=$URL . "&copyReturn=fail4" ;
				header("Location: {$URL}");
			}
			else {
				//Check existence of specified unit/class
				try {
					$data=array("gibbonUnitID"=>$gibbonUnitID, "gibbonCourseID"=>$gibbonCourseID, "gibbonUnitBlockID"=>$gibbonUnitBlockID, "gibbonUnitClassBlockID"=>$gibbonUnitClassBlockID); 
					$sql="SELECT gibbonUnitClassBlock.* FROM gibbonUnit JOIN gibbonCourse ON (gibbonUnit.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonUnitBlock ON (gibbonUnitBlock.gibbonUnitID=gibbonUnit.gibbonUnitID) JOIN gibbonUnitClassBlock ON (gibbonUnitClassBlock.gibbonUnitBlockID=gibbonUnitBlock.gibbonUnitBlockID) WHERE gibbonUnitClassBlockID=:gibbonUnitClassBlockID AND gibbonUnitBlock.gibbonUnitBlockID=:gibbonUnitBlockID AND gibbonUnit.gibbonUnitID=:gibbonUnitID AND gibbonUnit.gibbonCourseID=:gibbonCourseID" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					//Fail 2
					$URL=$URL . "&copyReturn=fail2" ;
					header("Location: {$URL}");
					break ;
				}
				
				if ($result->rowCount()!=1) {
					//Fail 4
					$URL=$URL . "&copyReturn=fail4" ;
					header("Location: {$URL}");
				}
				else {
					$row=$result->fetch() ;
					$partialFail=false;
					
					try {
						$data=array("title"=>$row["title"], "type"=>$row["type"], "length"=>$row["length"], "contents"=>$row["contents"], "teachersNotes"=>$row["teachersNotes"], "gibbonUnitBlockID"=>$gibbonUnitBlockID, "gibbonUnitID"=>$gibbonUnitID); 
						$sql="UPDATE gibbonUnitBlock SET title=:title, type=:type, length=:length, contents=:contents, teachersNotes=:teachersNotes WHERE gibbonUnitBlockID=:gibbonUnitBlockID AND gibbonUnitID=:gibbonUnitID" ;
						$result=$connection2->prepare($sql);
						$result->execute($data);
					}
					catch(PDOException $e) { 
						$partialFail=true;
					}
					
					$working=$_POST["working"] ;
					if ($working=="Y") {
						try {
							$data=array("title"=>$row["title"], "type"=>$row["type"], "length"=>$row["length"], "contents"=>$row["contents"], "teachersNotes"=>$row["teachersNotes"], "gibbonUnitBlockID"=>$gibbonUnitBlockID); 
							$sql="UPDATE gibbonUnitClassBlock SET title=:title, type=:type, length=:length, contents=:contents, teachersNotes=:teachersNotes WHERE gibbonUnitBlockID=:gibbonUnitBlockID" ;
							$result=$connection2->prepare($sql);
							$result->execute($data);
						}
						catch(PDOException $e) { 
							$partialFail=true;
						}
					}
					
						
					//RETURN
					if ($partialFail==TRUE) {
						//Fail 6
						$URL=$URL . "&copyReturn=fail6" ;
						header("Location: {$URL}");
					}
					else {
						$URL=$URL . "&copyReturn=success0" ;
						header("Location: {$URL}") ;
					}
				}
			}
		}
	}
}
?>