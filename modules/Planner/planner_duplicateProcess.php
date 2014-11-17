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

$gibbonPlannerEntryID=$_GET["gibbonPlannerEntryID"] ;
$viewBy=$_POST["viewBy"] ;
$subView=$_POST["subView"] ;
if ($viewBy!="date" AND $viewBy!="class") {
	$viewBy="date" ;
}
$gibbonCourseClassID=$_POST["gibbonCourseClassID"] ;
$date=dateConvert($guid, $_POST["date"]) ;
$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/planner_duplicate.php&gibbonPlannerEntryID=$gibbonPlannerEntryID" ;

//Params to pass back (viewBy + date or classID)
if ($viewBy=="date") {
	$params="&viewBy=$viewBy&date=$date" ;
}
else {
	$params="&viewBy=$viewBy&gibbonCourseClassID=$gibbonCourseClassID&subView=$subView" ;
}

if (isActionAccessible($guid, $connection2, "/modules/Planner/planner_duplicate.php")==FALSE) {
	//Fail 0
	$URL.="&updateReturn=fail0$params" ;
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
		//Check if school year specified
		if ($gibbonPlannerEntryID=="" OR ($viewBy=="class" AND $gibbonCourseClassID=="Y")) {
			//Fail1
			$URL.="&updateReturn=fail1$params" ;
			header("Location: {$URL}");
		}
		else {
			try {
				if ($highestAction=="Lesson Planner_viewEditAllClasses" ) {
					$data=array("gibbonPlannerEntryID"=>$gibbonPlannerEntryID); 
					$sql="SELECT *, gibbonPlannerEntry.description AS description FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID" ;
				}
				else {
					$data=array("gibbonPlannerEntryID"=>$gibbonPlannerEntryID, "gibbonPersonID"=> $_SESSION[$guid]["gibbonPersonID"]); 
					$sql="SELECT *, gibbonPlannerEntry.description AS description FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND role='Teacher' AND gibbonPlannerEntryID=:gibbonPlannerEntryID" ;
				}
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				//Fail2
				$URL.="&updateReturn=fail2$params" ;
				header("Location: {$URL}");
				break ;
			}
			
			if ($result->rowCount()!=1) {
				//Fail 2
				$URL.="&updateReturn=fail2$params" ;
				header("Location: {$URL}");
			}
			else {
				$row=$result->fetch() ;
				
				//Validate Inputs
				$name=$_POST["name"] ;
				$timeStart=$_POST["timeStart"] ;
				$timeEnd=$_POST["timeEnd"] ;
				$summary=$row["summary"] ;
				$description=$row["description"] ;
				
				$keepUnit=NULL ;
				$gibbonUnitClassID=NULL ;
				if (isset($_POST["keepUnit"])) {
					$keepUnit=$_POST["keepUnit"] ;
				}
				if ($keepUnit=="Y") {
					$gibbonUnitClassID=$_POST["gibbonUnitClassID"] ;
					$gibbonUnitID=$row["gibbonUnitID"] ;
					$gibbonHookID=$row["gibbonHookID"] ;
					if ($gibbonHookID=="") {
						$gibbonHookID=NULL ;
					}
				}
				else {
					$gibbonUnitID=NULL ;
					$gibbonHookID=$row["gibbonHookID"] ;
					if ($gibbonHookID=="") {
						$gibbonHookID=NULL ;
					}
				}
				$teachersNotes=$row["teachersNotes"] ;
				$homework=$row["homework"] ;
				$homework=$row["homework"] ;
				$homeworkDetails=$row["homeworkDetails"] ;
				if ($row["homeworkDueDateTime"]=="") {
					$homeworkDueDate=NULL ;
				}
				else {
					$homeworkDueDate=$row["homeworkDueDateTime"] ;
				}
				$homeworkSubmission=$row["homeworkSubmission"] ;
				if ($row["homeworkSubmissionDateOpen"]=="") {
					$homeworkSubmissionDateOpen=NULL ;
				}
				else {
					$homeworkSubmissionDateOpen=$row["homeworkSubmissionDateOpen"] ;
				}
				$homeworkSubmissionDrafts=$row["homeworkSubmissionDrafts"] ;
				$homeworkSubmissionType=$row["homeworkSubmissionType"] ;
				$homeworkSubmissionRequired=$row["homeworkSubmissionRequired"] ;
				$homeworkCrowdAssess=$row["homeworkCrowdAssess"] ;
				$homeworkCrowdAssessOtherTeachersRead=$row["homeworkCrowdAssessOtherTeachersRead"] ;
				$homeworkCrowdAssessClassmatesRead=$row["homeworkCrowdAssessClassmatesRead"] ;
				$homeworkCrowdAssessOtherStudentsRead=$row["homeworkCrowdAssessOtherStudentsRead"] ;
				$homeworkCrowdAssessSubmitterParentsRead=$row["homeworkCrowdAssessSubmitterParentsRead"] ;
				$homeworkCrowdAssessClassmatesParentsRead=$row["homeworkCrowdAssessClassmatesParentsRead"] ;
				$homeworkCrowdAssessOtherParentsRead=$row["homeworkCrowdAssessOtherParentsRead"] ;
				$viewableParents=$row["viewableParents"] ;
				$viewableStudents=$row["viewableStudents"] ;
				$gibbonPersonIDCreator=$_SESSION[$guid]["gibbonPersonID"] ;
				$gibbonPersonIDLastEdit=$_SESSION[$guid]["gibbonPersonID"] ;
				
				if ($viewBy=="" OR $gibbonCourseClassID=="" OR $date=="" OR $timeStart=="" OR $timeEnd=="" OR $name=="" OR $summary=="" OR $homework=="" OR $viewableParents=="" OR $viewableStudents=="" OR ($homework=="Y" AND ($homeworkDetails=="" OR $homeworkDueDate==""))) {
					//Fail 3
					$URL.="&updateReturn=fail3$params" ;
					header("Location: {$URL}");
				}
				else {
					//Lock markbook column table
					try {
						$sql="LOCK TABLES gibbonPlannerEntry WRITE, gibbonPlannerEntryGuest WRITE, gibbonCourseClassPerson WRITE" ;
						$result=$connection2->query($sql);   
					}
					catch(PDOException $e) { 
						//Fail 2
						$URL.="&updateReturn=fail2$params" ;
						header("Location: {$URL}");
						break ;
					}	
					
					//Get next autoincrement
					try {
						$sqlAI="SHOW TABLE STATUS LIKE 'gibbonPlannerEntry'";
						$resultAI=$connection2->query($sqlAI);   
					}
					catch(PDOException $e) { 
						//Fail 2
						$URL.="&updateReturn=fail2$params" ;
						header("Location: {$URL}");
						break ;
					}	
					
					$rowAI=$resultAI->fetch();
					$AI=str_pad($rowAI['Auto_increment'], 14, "0", STR_PAD_LEFT) ;
					
					//Write to database
					try {
						$data=array("gibbonCourseClassID"=>$gibbonCourseClassID, "date"=>$date, "timeStart"=>$timeStart, "timeEnd"=>$timeEnd, "gibbonUnitID"=>$gibbonUnitID, "gibbonHookID"=>$gibbonHookID, "name"=>$name, "summary"=>$summary, "description"=>$description, "teachersNotes"=>$teachersNotes, "homework"=>$homework, "homeworkDueDate"=>$homeworkDueDate, "homeworkDetails"=>$homeworkDetails, "homeworkSubmission"=>$homeworkSubmission, "homeworkSubmissionDateOpen"=>$homeworkSubmissionDateOpen, "homeworkSubmissionDrafts"=>$homeworkSubmissionDrafts, "homeworkSubmissionType"=>$homeworkSubmissionType, "homeworkSubmissionRequired"=>$homeworkSubmissionRequired, "homeworkCrowdAssess"=>$homeworkCrowdAssess, "homeworkCrowdAssessOtherTeachersRead"=>$homeworkCrowdAssessOtherTeachersRead, "homeworkCrowdAssessClassmatesRead"=>$homeworkCrowdAssessClassmatesRead, "homeworkCrowdAssessOtherStudentsRead"=>$homeworkCrowdAssessOtherStudentsRead, "homeworkCrowdAssessSubmitterParentsRead"=>$homeworkCrowdAssessSubmitterParentsRead, "homeworkCrowdAssessClassmatesParentsRead"=>$homeworkCrowdAssessClassmatesParentsRead, "homeworkCrowdAssessOtherParentsRead"=>$homeworkCrowdAssessOtherParentsRead, "viewableParents"=>$viewableParents, "viewableStudents"=>$viewableStudents, "gibbonPersonIDCreator"=>$gibbonPersonIDCreator, "gibbonPersonIDLastEdit"=>$gibbonPersonIDLastEdit); 
						$sql="INSERT INTO gibbonPlannerEntry SET gibbonCourseClassID=:gibbonCourseClassID, date=:date, timeStart=:timeStart, timeEnd=:timeEnd, gibbonUnitID=:gibbonUnitID, gibbonHookID=:gibbonHookID, name=:name, summary=:summary, description=:description, teachersNotes=:teachersNotes, homework=:homework, homeworkDueDateTime=:homeworkDueDate, homeworkDetails=:homeworkDetails, homeworkSubmission=:homeworkSubmission, homeworkSubmissionDateOpen=:homeworkSubmissionDateOpen, homeworkSubmissionDrafts=:homeworkSubmissionDrafts, homeworkSubmissionType=:homeworkSubmissionType, homeworkSubmissionRequired=:homeworkSubmissionRequired, homeworkCrowdAssess=:homeworkCrowdAssess, homeworkCrowdAssessOtherTeachersRead=:homeworkCrowdAssessOtherTeachersRead, homeworkCrowdAssessClassmatesRead=:homeworkCrowdAssessClassmatesRead, homeworkCrowdAssessOtherStudentsRead=:homeworkCrowdAssessOtherStudentsRead, homeworkCrowdAssessSubmitterParentsRead=:homeworkCrowdAssessSubmitterParentsRead, homeworkCrowdAssessClassmatesParentsRead=:homeworkCrowdAssessClassmatesParentsRead, homeworkCrowdAssessOtherParentsRead=:homeworkCrowdAssessOtherParentsRead, viewableParents=:viewableParents, viewableStudents=:viewableStudents, gibbonPersonIDCreator=:gibbonPersonIDCreator, gibbonPersonIDLastEdit=:gibbonPersonIDLastEdit" ;
						$result=$connection2->prepare($sql);
						$result->execute($data);
					}
					catch(PDOException $e) { 
						//Fail 2
						$URL.="&updateReturn=fail2$params" ;
						header("Location: {$URL}");
						break ;
					}
					
					//Unlock module table
					try {
						$sql="UNLOCK TABLES" ;
						$result=$connection2->query($sql);   
					}
					catch(PDOException $e) { 
						//Fail 2
						$URL.="&updateReturn=fail2$params" ;
						header("Location: {$URL}");
						break ;
					}
					
					$partialFail=FALSE ;

					//Try to duplicate MB columns
					$duplicate=$_POST["duplicate"] ;
					if ($duplicate=="Y") {
						try {
							$dataMarkbook=array("gibbonPlannerEntryID"=>$gibbonPlannerEntryID); 
							$sqlMarkbook="SELECT * FROM gibbonMarkbookColumn WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID" ;
							$resultMarkbook=$connection2->prepare($sqlMarkbook);
							$resultMarkbook->execute($dataMarkbook);
						}
						catch(PDOException $e) { 
							$partialFail=true ;
						}
						while ($rowMarkbook=$resultMarkbook->fetch()) {
							try {
								$dataMarkbookInsert=array("gibbonUnitID"=>$gibbonUnitID, "gibbonPlannerEntryID"=>$AI, "gibbonCourseClassID"=>$gibbonCourseClassID, "name"=>$rowMarkbook["name"], "description"=>$rowMarkbook["description"], "type"=>$rowMarkbook["type"], "attainment"=>$rowMarkbook["attainment"], "gibbonScaleIDAttainment"=>$rowMarkbook["gibbonScaleIDAttainment"], "effort"=>$rowMarkbook["effort"], "gibbonScaleIDEffort"=>$rowMarkbook["gibbonScaleIDEffort"], "comment"=>$rowMarkbook["comment"], "viewableStudents"=>$rowMarkbook["viewableStudents"], "viewableParents"=>$rowMarkbook["viewableParents"], "attachment"=>$rowMarkbook["attachment"], "gibbonPersonID1"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonPersonID2"=>$_SESSION[$guid]["gibbonPersonID"]); 
								$sqlMarkbookInsert="INSERT INTO gibbonMarkbookColumn SET gibbonUnitID=:gibbonUnitID, gibbonPlannerEntryID=:gibbonPlannerEntryID, gibbonCourseClassID=:gibbonCourseClassID, name=:name, description=:description, type=:type, attainment=:attainment, gibbonScaleIDAttainment=:gibbonScaleIDAttainment, effort=:effort, gibbonScaleIDEffort=:gibbonScaleIDEffort, comment=:comment, completeDate=NULL, complete='N' ,viewableStudents=:viewableStudents, viewableParents=:viewableParents ,attachment=:attachment, gibbonPersonIDCreator=:gibbonPersonID1, gibbonPersonIDLastEdit=:gibbonPersonID2" ;
								$resultMarkbookInsert=$connection2->prepare($sqlMarkbookInsert);
								$resultMarkbookInsert->execute($dataMarkbookInsert);
							}
							catch(PDOException $e) { 
								$partialFail=true ;
							}
						}
					}
					
					//DUPLICATE SMART BLOCKS
					if ($gibbonUnitClassID!=NULL) {
						try {
							$dataBlocks=array("gibbonPlannerEntryID"=>$gibbonPlannerEntryID); 
							$sqlBlocks="SELECT * FROM gibbonUnitClassBlock WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID" ;
							$resultBlocks=$connection2->prepare($sqlBlocks);
							$resultBlocks->execute($dataBlocks);
						}
						catch(PDOException $e) { 
							$partialFail=true ;
						}
						while ($rowBlocks=$resultBlocks->fetch()) {
							try {
								$dataBlocksInsert=array("gibbonUnitClassID"=>$gibbonUnitClassID, "gibbonPlannerEntryID"=>$AI, "gibbonUnitBlockID"=>$rowBlocks["gibbonUnitBlockID"], "title"=>$rowBlocks["title"], "type"=>$rowBlocks["type"], "length"=>$rowBlocks["length"], "contents"=>$rowBlocks["contents"], "teachersNotes"=>$rowBlocks["teachersNotes"], "sequenceNumber"=>$rowBlocks["sequenceNumber"], "gibbonOutcomeIDList"=>$rowBlocks["gibbonOutcomeIDList"]); 
								$sqlBlocksInsert="INSERT INTO gibbonUnitClassBlock SET gibbonUnitClassID=:gibbonUnitClassID, gibbonPlannerEntryID=:gibbonPlannerEntryID, gibbonUnitBlockID=:gibbonUnitBlockID, title=:title, type=:type, length=:length, contents=:contents, teachersNotes=:teachersNotes, sequenceNumber=:sequenceNumber, gibbonOutcomeIDList=:gibbonOutcomeIDList, complete='N'" ;
								$resultBlocksInsert=$connection2->prepare($sqlBlocksInsert);
								$resultBlocksInsert->execute($dataBlocksInsert);
							}
							catch(PDOException $e) { 
								$partialFail=true ;
							}
						}
					}
						
					//DUPLICATE OUTCOMES
					try {
						$dataBlocks=array("gibbonPlannerEntryID"=>$gibbonPlannerEntryID); 
						$sqlBlocks="SELECT * FROM gibbonPlannerEntryOutcome WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID" ;
						$resultBlocks=$connection2->prepare($sqlBlocks);
						$resultBlocks->execute($dataBlocks);
					}
					catch(PDOException $e) { 
						$partialFail=true ;
					}
					while ($rowBlocks=$resultBlocks->fetch()) {
						try {
							$dataBlocksInsert=array("gibbonPlannerEntryID"=>$AI, "gibbonOutcomeID"=>$rowBlocks["gibbonOutcomeID"], "sequenceNumber"=>$rowBlocks["sequenceNumber"], "content"=>$rowBlocks["content"]); 
							$sqlBlocksInsert="INSERT INTO gibbonPlannerEntryOutcome SET gibbonPlannerEntryID=:gibbonPlannerEntryID, gibbonOutcomeID=:gibbonOutcomeID, sequenceNumber=:sequenceNumber, content=:content" ;
							$resultBlocksInsert=$connection2->prepare($sqlBlocksInsert);
							$resultBlocksInsert->execute($dataBlocksInsert);
						}
						catch(PDOException $e) { 
							$partialFail=true ;
						}
					}
					
					if ($partialFail==TRUE) {
						//Fail 5
						$URL.="&updateReturn=fail5$params" ;
						header("Location: {$URL}");
					}
					else {
						//Success 0
						$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/planner_edit.php&gibbonPlannerEntryID=$AI" ;
						$URL.="&duplicateReturn=success0$params" ;
						header("Location: {$URL}");
					}
				}
			}
		}
	}
}
?>