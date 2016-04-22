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

$gibbonSchoolYearID=$_POST["gibbonSchoolYearID"] ;
$gibbonCourseClassID=$_POST["gibbonCourseClassID"] ;
$gibbonCourseID=$_POST["gibbonCourseID"] ;
$gibbonUnitID=$_POST["gibbonUnitID"] ;
$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/units_edit_copyBack.php&gibbonUnitID=$gibbonUnitID&gibbonCourseID=$gibbonCourseID&gibbonCourseClassID=$gibbonCourseClassID&gibbonSchoolYearID=$gibbonSchoolYearID" ;
$URLCopy=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/units_edit.php&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonCourseID=$gibbonCourseID&gibbonUnitID=$gibbonUnitID" ;

if (isActionAccessible($guid, $connection2, "/modules/Planner/units_edit_copyBack.php")==FALSE) {
	//Fail 0
	$URL.="&addReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	$highestAction=getHighestGroupedAction($guid, $_POST["address"], $connection2) ;
	if ($highestAction==FALSE) {
		//Fail 0
		$URL.="&updateReturn=fail0$params" ;
		header("Location: {$URL}");
	}
	else {
		//Proceed!
		if ($gibbonSchoolYearID=="" OR $gibbonCourseID=="" OR $gibbonCourseClassID=="" OR $gibbonUnitID=="") {
			//Fail 3
			$URL.="&addReturn=fail3" ;
			header("Location: {$URL}");
		}
		else {
			//Check access to specified course
			try {
				if ($highestAction=="Unit Planner_all") {
					$data=array("gibbonSchoolYearID"=>$gibbonSchoolYearID, "gibbonCourseID"=>$gibbonCourseID); 
					$sql="SELECT * FROM gibbonCourse WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseID=:gibbonCourseID" ;
				}
				else if ($highestAction=="Unit Planner_learningAreas") {
					$data=array("gibbonSchoolYearID"=>$gibbonSchoolYearID, "gibbonCourseID"=>$gibbonCourseID, "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
					$sql="SELECT gibbonCourseID, gibbonCourse.name, gibbonCourse.nameShort FROM gibbonCourse JOIN gibbonDepartment ON (gibbonCourse.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE gibbonDepartmentStaff.gibbonPersonID=:gibbonPersonID AND (role='Coordinator' OR role='Assistant Coordinator' OR role='Teacher (Curriculum)') AND gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseID=:gibbonCourseID ORDER BY gibbonCourse.nameShort" ;
				}
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				//Fail 2
				$URL.="&addReturn=fail2" ;
				header("Location: {$URL}");
				exit() ;
			}
			if ($result->rowCount()!=1) {
				//Fail 4
				$URL.="&addReturn=fail4" ;
				header("Location: {$URL}");
			}
			else {
				//Check existence of specified unit
				try {
					$data=array("gibbonUnitID"=>$gibbonUnitID, "gibbonCourseID"=>$gibbonCourseID); 
					$sql="SELECT * FROM gibbonUnit WHERE gibbonUnitID=:gibbonUnitID AND gibbonCourseID=:gibbonCourseID" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					//Fail 2
					$URL.="&addReturn=fail2" ;
					header("Location: {$URL}");
					exit() ;
				}
				if ($result->rowCount()!=1) {
					//Fail 4
					$URL.="&addReturn=fail4" ;
					header("Location: {$URL}");
				}
				else {
					//Write to database
					try {
						$data=array("gibbonUnitID"=>$gibbonUnitID); 
						$sql="DELETE FROM gibbonUnitBlock WHERE gibbonUnitID=:gibbonUnitID" ;
						$result=$connection2->prepare($sql);
						$result->execute($data);
					}
					catch(PDOException $e) { 
						//Fail 2
						$URL.="&copyReturn=fail2" ;
						header("Location: {$URL}");
						exit() ;
					}
					
					try {
						$dataBlocks=array("gibbonUnitID"=>$gibbonUnitID, "gibbonCourseClassID"=>$gibbonCourseClassID, "gibbonCourseClassID"=>$gibbonCourseClassID); 
						$sqlBlocks="SELECT * FROM gibbonUnitClass JOIN gibbonUnitClassBlock ON (gibbonUnitClassBlock.gibbonUnitClassID=gibbonUnitClass.gibbonUnitClassID) JOIN gibbonPlannerEntry ON (gibbonPlannerEntry.gibbonPlannerEntryID=gibbonUnitClassBlock.gibbonPlannerEntryID) WHERE gibbonUnitClass.gibbonUnitID=:gibbonUnitID AND gibbonUnitClass.gibbonCourseClassID=:gibbonCourseClassID AND gibbonPlannerEntry.gibbonCourseClassID=:gibbonCourseClassID ORDER BY sequenceNumber" ;
						$resultBlocks=$connection2->prepare($sqlBlocks);
						$resultBlocks->execute($dataBlocks);
					}
					catch(PDOException $e) { 
						//Fail 2
						$URL.="&copyReturn=fail2" ;
						header("Location: {$URL}");
						exit() ;
					}
					
					$partialFail=false ;
					while ($rowBlocks=$resultBlocks->fetch()) {
						try {
							$dataBlock=array("gibbonUnitID"=>$gibbonUnitID, "title"=>$rowBlocks["title"], "type"=>$rowBlocks["type"], "length"=>$rowBlocks["length"], "contents"=>$rowBlocks["contents"], "sequenceNumber"=>$rowBlocks["sequenceNumber"], "gibbonOutcomeIDList"=>$rowBlocks["gibbonOutcomeIDList"]); 
							$sqlBlock="INSERT INTO gibbonUnitBlock SET gibbonUnitID=:gibbonUnitID, title=:title, type=:type, length=:length, contents=:contents, sequenceNumber=:sequenceNumber, gibbonOutcomeIDList=:gibbonOutcomeIDList" ;
							$resultBlock=$connection2->prepare($sqlBlock);
							$resultBlock->execute($dataBlock);
						}
						catch(PDOException $e) { 
							$partialFail=true ;
						}
					}
				
					if ($partialFail==true) {
						//Fail 2
						$URL.="&copyReturn=fail6" ;
						header("Location: {$URL}");
					}
					else {
						//Success 0
						$URLCopy=$URLCopy . "&copyReturn=success0" ;
						header("Location: {$URLCopy}");
					}
				}
			}
		}
	}
}
?>