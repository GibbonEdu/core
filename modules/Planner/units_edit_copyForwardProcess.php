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
$gibbonSchoolYearIDCopyTo=$_POST["gibbonSchoolYearIDCopyTo"] ;
$gibbonCourseIDTarget=$_POST["gibbonCourseIDTarget"] ;
$nameTarget=$_POST["nameTarget"] ;
					
$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/units_edit_copyForward.php&gibbonUnitID=$gibbonUnitID&gibbonCourseID=$gibbonCourseID&gibbonCourseClassID=$gibbonCourseClassID&gibbonSchoolYearID=$gibbonSchoolYearID" ;

if (isActionAccessible($guid, $connection2, "/modules/Planner/units_edit_copyForward.php")==FALSE) {
	$URL.="&copyForwardReturn=error0" ;
	header("Location: {$URL}");
}
else {
	$highestAction=getHighestGroupedAction($guid, $_POST["address"], $connection2) ;
	if ($highestAction==FALSE) {
			$URL.="&copyForwardReturn=error0$params" ;
		header("Location: {$URL}");
	}
	else {
		//Proceed!
		if ($gibbonSchoolYearID=="" OR $gibbonCourseID=="" OR $gibbonCourseClassID=="" OR $gibbonUnitID=="" OR $gibbonSchoolYearIDCopyTo=="" OR $gibbonCourseIDTarget=="" OR $nameTarget=="") {
			$URL.="&copyForwardReturn=error3" ;
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
					$URL.="&copyForwardReturn=error2" ;
				header("Location: {$URL}");
				exit() ;
			}
			if ($result->rowCount()!=1) {
				$URL.="&copyForwardReturn=error4" ;
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
							$URL.="&copyForwardReturn=error2" ;
					header("Location: {$URL}");
					exit() ;
				}
				if ($result->rowCount()!=1) {
					$URL.="&copyForwardReturn=error4" ;
					header("Location: {$URL}");
				}
				else {
					//Write to database
					$row=$result->fetch() ;
					$partialFail=false ;
					
					//Create new unit
					try {
						$data=array("gibbonCourseID"=>$gibbonCourseIDTarget, "name"=>$nameTarget, "description"=>$row["description"], "attachment"=>$row["attachment"], "details"=>$row["details"], "gibbonPersonIDCreator"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonPersonIDLastEdit"=>$_SESSION[$guid]["gibbonPersonID"], ); 
						$sql="INSERT INTO gibbonUnit SET gibbonCourseID=:gibbonCourseID, name=:name, description=:description, attachment=:attachment, details=:details, gibbonPersonIDCreator=:gibbonPersonIDCreator, gibbonPersonIDLastEdit=:gibbonPersonIDLastEdit" ;
						$result=$connection2->prepare($sql);
						$result->execute($data);
					}
					catch(PDOException $e) { 
									$URL.="&copyForwardReturn=error2" ;
						header("Location: {$URL}");
						exit() ;
					}
					
					//Get new unit ID
					$gibbinUnitIDNew=$connection2->lastInsertID() ;
					
					if ($gibbinUnitIDNew=="") {
						$partialFail=true ;
					}
					else {
						//Read blocks from old unit
						try {
							$dataBlocks=array("gibbonUnitID"=>$gibbonUnitID, "gibbonCourseClassID"=>$gibbonCourseClassID, "gibbonCourseClassID"=>$gibbonCourseClassID); 
							$sqlBlocks="SELECT * FROM gibbonUnitClass JOIN gibbonUnitClassBlock ON (gibbonUnitClassBlock.gibbonUnitClassID=gibbonUnitClass.gibbonUnitClassID) JOIN gibbonPlannerEntry ON (gibbonPlannerEntry.gibbonPlannerEntryID=gibbonUnitClassBlock.gibbonPlannerEntryID) WHERE gibbonUnitClass.gibbonUnitID=:gibbonUnitID AND gibbonUnitClass.gibbonCourseClassID=:gibbonCourseClassID AND gibbonPlannerEntry.gibbonCourseClassID=:gibbonCourseClassID ORDER BY sequenceNumber" ;
							$resultBlocks=$connection2->prepare($sqlBlocks);
							$resultBlocks->execute($dataBlocks);
						}
						catch(PDOException $e) { 
							$partialFail=true ;
						}
						
						//Write blocks to new unit
						while ($rowBlocks=$resultBlocks->fetch()) {
							try {
								$dataBlock=array("gibbonUnitID"=>$gibbinUnitIDNew, "title"=>$rowBlocks["title"], "type"=>$rowBlocks["type"], "length"=>$rowBlocks["length"], "contents"=>$rowBlocks["contents"], "sequenceNumber"=>$rowBlocks["sequenceNumber"], "gibbonOutcomeIDList"=>$rowBlocks["gibbonOutcomeIDList"]); 
								$sqlBlock="INSERT INTO gibbonUnitBlock SET gibbonUnitID=:gibbonUnitID, title=:title, type=:type, length=:length, contents=:contents, sequenceNumber=:sequenceNumber, gibbonOutcomeIDList=:gibbonOutcomeIDList" ;
								$resultBlock=$connection2->prepare($sqlBlock);
								$resultBlock->execute($dataBlock);
							}
							catch(PDOException $e) { 
								$partialFail=true ;
							}
						}
						
						//Read outcomes from old unit
						try {
							$dataOutcomes=array("gibbonUnitID"=>$gibbonUnitID); 
							$sqlOutcomes="SELECT * FROM gibbonUnitOutcome WHERE gibbonUnitID=:gibbonUnitID" ;
							$resultOutcomes=$connection2->prepare($sqlOutcomes);
							$resultOutcomes->execute($dataOutcomes);
						}
						catch(PDOException $e) { 
							$partialFail=TRUE ; 
						}
						
						//Write outcomes to new unit
						if ($resultOutcomes->rowCount()>0) {
							while ($rowOutcomes=$resultOutcomes->fetch()) {
								//Write to database
								try {
									$dataCopy=array("gibbonUnitID"=>$gibbinUnitIDNew, "gibbonOutcomeID"=>$rowOutcomes["gibbonOutcomeID"], "sequenceNumber"=>$rowOutcomes["sequenceNumber"], "content"=>$rowOutcomes["content"]); 
									$sqlCopy="INSERT INTO gibbonUnitOutcome SET gibbonUnitID=:gibbonUnitID, gibbonOutcomeID=:gibbonOutcomeID, sequenceNumber=:sequenceNumber, content=:content" ;
									$resultCopy=$connection2->prepare($sqlCopy);
									$resultCopy->execute($dataCopy);
								}
								catch(PDOException $e) { 
									$partialFail=TRUE ; 
								}
							}
						}		
					}
					
					if ($partialFail==true) {
									$URL.="&copyForwardReturn=error6" ;
						header("Location: {$URL}");
					}
					else {
						$URLCopy=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/units_edit.php&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonCourseID=$gibbonCourseIDTarget&gibbonUnitID=$gibbinUnitIDNew" ;
						$URLCopy=$URLCopy . "&copyForwardReturn=success2" ;
						header("Location: {$URLCopy}");
					}
				}
			}
		}
	}
}
?>