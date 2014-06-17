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
include "./moduleFunctions.php" ;

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]["timezone"]);

$gibbonSchoolYearID=$_GET["gibbonSchoolYearID"] ;
$gibbonCourseID=$_GET["gibbonCourseID"] ;
$gibbonUnitID=$_GET["gibbonUnitID"] ;
$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["address"]) . "/units_duplicate.php&gibbonUnitID=$gibbonUnitID&gibbonCourseID=$gibbonCourseID&gibbonSchoolYearID=$gibbonSchoolYearID" ;

if (isActionAccessible($guid, $connection2, "/modules/Planner/units_duplicate.php")==FALSE) {
	//Fail 0
	$URL=$URL . "&updateReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	$highestAction=getHighestGroupedAction($guid, $_GET["address"], $connection2) ;
	if ($highestAction==FALSE) {
		//Fail 0
		$URL=$URL . "&updateReturn=fail0$params" ;
		header("Location: {$URL}");
	}
	else {
		//Proceed!
		//Validate Inputs
		$gibbonCourseIDTarget=$_POST["gibbonCourseIDTarget"] ;
		$copyLessons=$_POST["copyLessons"] ;
		
		if ($gibbonSchoolYearID=="" OR $gibbonCourseID=="" OR $gibbonUnitID=="" OR $gibbonCourseIDTarget=="") {
			//Fail 3
			$URL=$URL . "&updateReturn=fail3" ;
			header("Location: {$URL}");
		}
		else {
			//Lock table
			try {
				$sql="LOCK TABLE gibbonUnit WRITE" ;
				$result=$connection2->query($sql);   
			}
			catch(PDOException $e) { 
				//Fail 2
				$URL=$URL . "&updateReturn=fail2" ;
				header("Location: {$URL}");
				break ;
			}			
			
			//Get next autoincrement for unit
			try {
				$sqlAI="SHOW TABLE STATUS LIKE 'gibbonUnit'";
				$resultAI=$connection2->query($sqlAI);   
			}
			catch(PDOException $e) { 
				//Fail 2
				$URL=$URL . "&updateReturn=fail2" ;
				header("Location: {$URL}");
				break ;
			}			
			
			$rowAI=$resultAI->fetch();
			$AI=str_pad($rowAI['Auto_increment'], 8, "0", STR_PAD_LEFT) ;
			$partialFail=FALSE ; 
			
			//Unlock locked database tables
			try {
				$sql="UNLOCK TABLES" ;
				$result=$connection2->query($sql);   
			}
			catch(PDOException $e) { }	
										
			if ($AI=="") {
				//Fail 2
				$URL=$URL . "&updateReturn=fail2" ;
				header("Location: {$URL}");
			}
			else {
				//Write to database
				try {
					$data=array("gibbonUnitID"=>$gibbonUnitID); 
					$sql="SELECT * FROM gibbonUnit WHERE gibbonUnitID=:gibbonUnitID" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					//Fail 2
					$URL=$URL . "&updateReturn=fail2" ;
					header("Location: {$URL}");
					break ;
				}
				
				if ($result->rowCount()!=1) {
					//Fail 2
					$URL=$URL . "&updateReturn=fail2" ;
					header("Location: {$URL}");
				}
				else {
					$row=$result->fetch() ;
					try {
						$data=array("gibbonCourseID"=>$gibbonCourseIDTarget, "name"=>$row["name"], "description"=>$row["description"] ,"attachment"=>$row["attachment"] ,"details"=>$row["details"], "gibbonPersonIDCreator"=>$_SESSION[$guid]["gibbonPersonID"] ,"gibbonPersonIDLastEdit"=>$_SESSION[$guid]["gibbonPersonID"]); 
						$sql="INSERT INTO gibbonUnit SET gibbonCourseID=:gibbonCourseID, name=:name, description=:description, attachment=:attachment, details=:details ,gibbonPersonIDCreator=:gibbonPersonIDCreator, gibbonPersonIDLastEdit=:gibbonPersonIDLastEdit" ;
						$result=$connection2->prepare($sql);
						$result->execute($data);
					}
					catch(PDOException $e) { 
						//Fail 2
						$URL=$URL . "&updateReturn=fail2" ;
						header("Location: {$URL}");
						break ;
					}
					
					//Copy Outcomes
					try {
						$dataOutcomes=array("gibbonUnitID"=>$gibbonUnitID); 
						$sqlOutcomes="SELECT * FROM gibbonUnitOutcome WHERE gibbonUnitID=:gibbonUnitID" ;
						$resultOutcomes=$connection2->prepare($sqlOutcomes);
						$resultOutcomes->execute($dataOutcomes);
					}
					catch(PDOException $e) { 
						$partialFail=TRUE ;
					}
					
					if ($resultOutcomes->rowCount()>0) {
						while ($rowOutcomes=$resultOutcomes->fetch()) {
							//Write to database
							try {
								$dataCopy=array("gibbonUnitID"=>$AI, "gibbonOutcomeID"=>$rowOutcomes["gibbonOutcomeID"], "sequenceNumber"=>$rowOutcomes["sequenceNumber"], "content"=>$rowOutcomes["content"]); 
								$sqlCopy="INSERT INTO gibbonUnitOutcome SET gibbonUnitID=:gibbonUnitID, gibbonOutcomeID=:gibbonOutcomeID, sequenceNumber=:sequenceNumber, content=:content" ;
								$resultCopy=$connection2->prepare($sqlCopy);
								$resultCopy->execute($dataCopy);
							}
							catch(PDOException $e) { 
								$partialFail=TRUE ;
							}
						}
					}		
					
					//Copy Lessons & resources
					if ($copyLessons=="Yes") {
						$gibbonCourseClassIDSource=$_POST["gibbonCourseClassIDSource"] ;
						$gibbonCourseClassIDTarget=$_POST["gibbonCourseClassIDTarget"] ;
						
						if ($gibbonCourseClassIDSource=="" OR count($gibbonCourseClassIDTarget)<1 OR $AI=="") {
							//Fail 1
							$URL=$URL . "&updateReturn=fail1" ;
							header("Location: {$URL}");
						}
						else {
							foreach ($gibbonCourseClassIDTarget as $t) {
								//Turn class on
								try {
									$dataOn=array("gibbonUnitID"=>$AI, "gibbonCourseClassID"=>$t); 
									$sqlOn="INSERT INTO gibbonUnitClass SET gibbonUnitID=:gibbonUnitID, gibbonCourseClassID=:gibbonCourseClassID, running='Y'" ;
									$resultOn=$connection2->prepare($sqlOn);
									$resultOn->execute($dataOn);
								}
								catch(PDOException $e) { 
									$partialFail=TRUE ; 
								}
								
								$gibbonUnitClassIDNew=$connection2->lastInsertID() ;
								
								//Get lessons
								try {
									$dataLessons=array("gibbonCourseClassID"=>$gibbonCourseClassIDSource, "gibbonUnitID"=>$gibbonUnitID); 
									$sqlLessons="SELECT * FROM gibbonPlannerEntry WHERE gibbonCourseClassID=:gibbonCourseClassID AND gibbonUnitID=:gibbonUnitID" ;
									$resultLessons=$connection2->prepare($sqlLessons);
									$resultLessons->execute($dataLessons);
								}
								catch(PDOException $e) { 
									$partialFail=TRUE ;
								}
								
								if ($resultLessons->rowCount()>0) {
									//Copy Lessons
									while ($rowLesson=$resultLessons->fetch()) {
										$copyOK=TRUE ;
										//Write to database
										try {
											$dataCopy=array("gibbonCourseClassID"=>$t, "gibbonUnitID"=>$AI, "name"=>$rowLesson["name"], "summary"=>$rowLesson["summary"], "description"=>$rowLesson["description"], "teachersNotes"=>$rowLesson["teachersNotes"], "homework"=>$rowLesson["homework"], "homeworkDetails"=>$rowLesson["homeworkDetails"], "homeworkSubmission"=>$rowLesson["homeworkSubmission"], "homeworkSubmissionDrafts"=>$rowLesson["homeworkSubmissionDrafts"], "homeworkSubmissionType"=>$rowLesson["homeworkSubmissionType"], "viewableStudents"=>$rowLesson["viewableStudents"], "viewableParents"=>$rowLesson["viewableParents"], "gibbonPersonIDCreator"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonPersonIDLastEdit"=>$_SESSION[$guid]["gibbonPersonID"]); 
											$sqlCopy="INSERT INTO gibbonPlannerEntry SET gibbonCourseClassID=:gibbonCourseClassID, gibbonUnitID=:gibbonUnitID, gibbonHookID=NULL, date=NULL, timeStart=NULL, timeEnd=NULL, name=:name, summary=:summary, description=:description, teachersNotes=:teachersNotes, homework=:homework, homeworkDueDateTime=NULL, homeworkDetails=:homeworkDetails, homeworkSubmission=:homeworkSubmission, homeworkSubmissionDateOpen=NULL, homeworkSubmissionDrafts=:homeworkSubmissionDrafts, homeworkSubmissionType=:homeworkSubmissionType, homeworkCrowdAssess='N', homeworkCrowdAssessOtherTeachersRead='N', homeworkCrowdAssessOtherParentsRead='N', homeworkCrowdAssessClassmatesParentsRead='N', homeworkCrowdAssessSubmitterParentsRead='N', homeworkCrowdAssessOtherStudentsRead='N', homeworkCrowdAssessClassmatesRead='N', viewableStudents=:viewableStudents, viewableParents=:viewableParents, gibbonPersonIDCreator=:gibbonPersonIDCreator, gibbonPersonIDLastEdit=:gibbonPersonIDLastEdit" ;
											$resultCopy=$connection2->prepare($sqlCopy);
											$resultCopy->execute($dataCopy);
										}
										catch(PDOException $e) { 
											$partialFail=TRUE ;
											$copyOK=FALSE ;
										}
										if ($copyOK==TRUE) {
											//Copy blocks for this lesson
											$gibbonPlannerEntryNew=$connection2->lastInsertID() ;
											
											try {
												$dataBlocks=array("gibbonPlannerEntryID"=>$rowLesson["gibbonPlannerEntryID"]); 
												$sqlBlocks="SELECT * FROM gibbonUnitClassBlock WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID ORDER BY sequenceNumber" ;
												$resultBlocks=$connection2->prepare($sqlBlocks);
												$resultBlocks->execute($dataBlocks);
											}
											catch(PDOException $e) { 
												$partialFail=true ;
											}
											while ($rowBlocks=$resultBlocks->fetch()) {
												try {
													$dataBlock=array("gibbonPlannerEntryID"=>$gibbonPlannerEntryNew, "gibbonUnitClassID"=>$gibbonUnitClassIDNew, "gibbonUnitBlockID"=>$rowBlocks["gibbonUnitBlockID"], "title"=>$rowBlocks["title"], "type"=>$rowBlocks["type"], "length"=>$rowBlocks["length"], "contents"=>$rowBlocks["contents"], "teachersNotes"=>$rowBlocks["teachersNotes"], "sequenceNumber"=>$rowBlocks["sequenceNumber"]); 
													$sqlBlock="INSERT INTO gibbonUnitClassBlock SET gibbonPlannerEntryID=:gibbonPlannerEntryID, gibbonUnitClassID=:gibbonUnitClassID, gibbonUnitBlockID=:gibbonUnitBlockID, title=:title, type=:type, length=:length, contents=:contents, teachersNotes=:teachersNotes, sequenceNumber=:sequenceNumber" ;
													$resultBlock=$connection2->prepare($sqlBlock);
													$resultBlock->execute($dataBlock);
												}
												catch(PDOException $e) { 
													$partialFail=true ;
												}
											}
										}
									}
								}
							}
						}
					}
				
					try {
						$dataBlocks=array("gibbonUnitID"=>$gibbonUnitID); 
						$sqlBlocks="SELECT * FROM gibbonUnitBlock WHERE gibbonUnitID=:gibbonUnitID ORDER BY sequenceNumber" ;
						$resultBlocks=$connection2->prepare($sqlBlocks);
						$resultBlocks->execute($dataBlocks);
					}
					catch(PDOException $e) { 
						$partialFail=true ;
					}
					while ($rowBlocks=$resultBlocks->fetch()) {
						try {
							$dataBlock=array("gibbonUnitID"=>$AI, "title"=>$rowBlocks["title"], "type"=>$rowBlocks["type"], "length"=>$rowBlocks["length"], "contents"=>$rowBlocks["contents"], "teachersNotes"=>$rowBlocks["teachersNotes"], "sequenceNumber"=>$rowBlocks["sequenceNumber"]); 
							$sqlBlock="INSERT INTO gibbonUnitBlock SET gibbonUnitID=:gibbonUnitID, title=:title, type=:type, length=:length, contents=:contents, teachersNotes=:teachersNotes, sequenceNumber=:sequenceNumber" ;
							$resultBlock=$connection2->prepare($sqlBlock);
							$resultBlock->execute($dataBlock);
						}
						catch(PDOException $e) { 
							$partialFail=true ;
						}
					}
					
					if ($partialFail==TRUE) {
						//Fail 6
						$URL=$URL . "&updateReturn=fail6" ;
						header("Location: {$URL}");
					}
					else {
						//Success 0
						$URL=$URL . "&updateReturn=success0" ;
						header("Location: {$URL}");
					}
				}
			}
		}
	}
}
?>