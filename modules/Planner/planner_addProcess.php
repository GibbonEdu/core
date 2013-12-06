<?
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

$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["address"]) . "/planner_add.php" ;

if (isActionAccessible($guid, $connection2, "/modules/Planner/planner_add.php")==FALSE) {
	//Fail 0
	$URL=$URL . "&addReturn=fail0" ;
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
		if (empty($_POST)) {
			$URL=$URL . "&addReturn=fail6" ;
			header("Location: {$URL}");
		}
		else {
			//Proceed!
			//Validate Inputs
			$viewBy=$_GET["viewBy"] ;
			$subView=$_GET["subView"] ;
			if ($viewBy!="date" AND $viewBy!="class") {
				$viewBy="date" ;
			}
			$gibbonCourseClassID=$_POST["gibbonCourseClassID"] ;
			$date=dateConvert($_POST["date"]) ;
			$timeStart=$_POST["timeStart"] ;
			$timeEnd=$_POST["timeEnd"] ;
			$gibbonUnitID=$_POST["gibbonUnitID"] ;
			if ($gibbonUnitID=="") {
				$gibbonUnitID=NULL ;
				$gibbonHookID=NULL ;
			}
			else {
				//Check for hooked unit (will have - in value)
				if (strpos($gibbonUnitID, "-")==FALSE OR strpos($gibbonUnitID, "-")==0) {
					//No hook
					$gibbonUnitID=$gibbonUnitID ;
					$gibbonHookID=NULL ;
				}
				else {
					//Hook!
					$gibbonUnitID=substr($_POST["gibbonUnitID"],0,strpos($gibbonUnitID, "-")) ;
					$gibbonHookID=substr($_POST["gibbonUnitID"],(strpos($_POST["gibbonUnitID"], "-")+1)) ;
				}
			}
			$name=$_POST["name"] ;
			$summary=$_POST["summary"] ;
			$description=$_POST["description"] ;
			$teachersNotes=$_POST["teachersNotes"] ;
			$homework=$_POST["homework"] ;
			if ($_POST["homework"]=="Yes") {
				$homework="Y" ;
				$homeworkDetails=$_POST["homeworkDetails"] ;
				if ($_POST["homeworkDueDateTime"]!="") {
					$homeworkDueDateTime=$_POST["homeworkDueDateTime"] . ":59" ;
				}
				else {
					$homeworkDueDateTime="23:59:59" ;
				}
				if ($_POST["homeworkDueDate"]!="") {
					$homeworkDueDate=dateConvert($_POST["homeworkDueDate"]) . " " . $homeworkDueDateTime ;
				}
				
				if ($_POST["homeworkSubmission"]=="Yes") {
					$homeworkSubmission="Y" ;
					if ($_POST["homeworkSubmissionDateOpen"]!="") {
						$homeworkSubmissionDateOpen=dateConvert($_POST["homeworkSubmissionDateOpen"]) ;
					}
					else {
						$homeworkSubmissionDateOpen=dateConvert($_POST["date"]) ;
					}
					$homeworkSubmissionDrafts=$_POST["homeworkSubmissionDrafts"] ;
					$homeworkSubmissionType=$_POST["homeworkSubmissionType"] ;
					$homeworkSubmissionRequired=$_POST["homeworkSubmissionRequired"] ;
					if ($_POST["homeworkCrowdAssess"]=="Yes") {
						$homeworkCrowdAssess="Y" ;
						if ($_POST["homeworkCrowdAssessOtherTeachersRead"]=="on") {
							$homeworkCrowdAssessOtherTeachersRead="Y" ;
						}
						else {
							$homeworkCrowdAssessOtherTeachersRead="N" ;
						}
						if ($_POST["homeworkCrowdAssessClassmatesRead"]=="on") {
							$homeworkCrowdAssessClassmatesRead="Y" ;
						}
						else {
							$homeworkCrowdAssessClassmatesRead="N" ;
						}
						if ($_POST["homeworkCrowdAssessOtherStudentsRead"]=="on") {
							$homeworkCrowdAssessOtherStudentsRead="Y" ;
						}
						else {
							$homeworkCrowdAssessOtherStudentsRead="N" ;
						}
						if ($_POST["homeworkCrowdAssessSubmitterParentsRead"]=="on") {
							$homeworkCrowdAssessSubmitterParentsRead="Y" ;
						}
						else {
							$homeworkCrowdAssessSubmitterParentsRead="N" ;
						}
						if ($_POST["homeworkCrowdAssessClassmatesParentsRead"]=="on") {
							$homeworkCrowdAssessClassmatesParentsRead="Y" ;
						}
						else {
							$homeworkCrowdAssessClassmatesParentsRead="N" ;
						}
						if ($_POST["homeworkCrowdAssessOtherParentsRead"]=="on") {
							$homeworkCrowdAssessOtherParentsRead="Y" ;
						}
						else {
							$homeworkCrowdAssessOtherParentsRead="N" ;
						}
					}
					else {
						$homeworkCrowdAssess="N" ;
						$homeworkCrowdAssessOtherTeachersRead="N" ;
						$homeworkCrowdAssessClassmatesRead="N" ;
						$homeworkCrowdAssessOtherStudentsRead="N" ;
						$homeworkCrowdAssessSubmitterParentsRead="N" ;
						$homeworkCrowdAssessClassmatesParentsRead="N" ;
						$homeworkCrowdAssessOtherParentsRead="N" ;
					}
				}
				else {
					$homeworkSubmission="N" ;
					$homeworkSubmissionDateOpen=NULL ;
					$homeworkSubmissionType="" ;
					$homeworkSubmissionDrafts=NULL ;
					$homeworkSubmissionRequired=NULL ;
					$homeworkCrowdAssess="N" ;
					$homeworkCrowdAssessOtherTeachersRead="N" ;
					$homeworkCrowdAssessClassmatesRead="N" ;
					$homeworkCrowdAssessOtherStudentsRead="N" ;
					$homeworkCrowdAssessSubmitterParentsRead="N" ;
					$homeworkCrowdAssessClassmatesParentsRead="N" ;
					$homeworkCrowdAssessOtherParentsRead="N" ;
				}
			}
			else {
				$homework="N" ;
				$homeworkDueDate=NULL ;
				$homeworkDetails="" ;
				$homeworkSubmission="N" ;
				$homeworkSubmissionDateOpen=NULL ;
				$homeworkSubmissionType="" ;
				$homeworkSubmissionDrafts=NULL ;
				$homeworkSubmissionRequired=NULL ;
				$homeworkCrowdAssess="N" ;
				$homeworkCrowdAssessOtherTeachersRead="N" ;
				$homeworkCrowdAssessClassmatesRead="N" ;
				$homeworkCrowdAssessOtherStudentsRead="N" ;
				$homeworkCrowdAssessSubmitterParentsRead="N" ;
				$homeworkCrowdAssessClassmatesParentsRead="N" ;
				$homeworkCrowdAssessOtherParentsRead="N" ;
			}
		
			$viewableParents=$_POST["viewableParents"] ;
			$viewableStudents=$_POST["viewableStudents"] ;
			$twitterSearch=$_POST["twitterSearch"] ;
			$gibbonPersonIDCreator=$_SESSION[$guid]["gibbonPersonID"] ;
			$gibbonPersonIDLastEdit=$_SESSION[$guid]["gibbonPersonID"] ;
			
			//Params to pass back (viewBy + date or classID)
			if ($viewBy=="date") {
				$params="&viewBy=$viewBy&date=$date" ;
			}
			else {
				$params="&viewBy=$viewBy&gibbonCourseClassID=$gibbonCourseClassID&subView=$subView" ;
			}
			
			//Lock markbook column table
			try {
				$sql="LOCK TABLES gibbonPlannerEntry WRITE, gibbonPlannerEntryGuest WRITE, gibbonCourseClassPerson WRITE, gibbonPlannerEntryOutcome WRITE" ;
				$result=$connection2->query($sql);   
			}
			catch(PDOException $e) { 
				//Fail 2
				$URL=$URL . "&addReturn=fail2$params" ;
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
				$URL=$URL . "&addReturn=fail2$params" ;
				header("Location: {$URL}");
				break ;
			}	
						
			$rowAI=$resultAI->fetch();
			$AI=str_pad($rowAI['Auto_increment'], 14, "0", STR_PAD_LEFT) ;
			
			if ($viewBy=="" OR $gibbonCourseClassID=="" OR $date=="" OR $timeStart=="" OR $timeEnd=="" OR $name=="" OR $summary=="" OR $homework=="" OR $viewableParents=="" OR $viewableStudents=="" OR ($homework=="Y" AND ($homeworkDetails=="" OR $homeworkDueDate==""))) {
				//Fail 3
				$URL=$URL . "&addReturn=fail3$params" ;
				header("Location: {$URL}");
			}
			else {
				$partialFail=FALSE ;
				
				//Scan through guests
				$guests=NULL ;
				if (isset($_POST["guests"])) {
					$guests=$_POST["guests"] ;
				}
				$role=$_POST["role"] ;
				if ($role=="") {
					$role="Student" ;
				}
				if (count($guests)>0) {
					foreach ($guests as $t) {
						//Check to see if person is already registered in this class
						try {
							$dataGuest=array("gibbonPersonID"=>$t, "gibbonCourseClassID"=>$gibbonCourseClassID); 
							$sqlGuest="SELECT * FROM gibbonCourseClassPerson WHERE gibbonPersonID=:gibbonPersonID AND gibbonCourseClassID=:gibbonCourseClassID" ;
							$resultGuest=$connection2->prepare($sqlGuest);
							$resultGuest->execute($dataGuest);
						}
						catch(PDOException $e) { 
							$partialFail=TRUE ;
						}
						
						if ($resultGuest->rowCount()==0) {
							try {
								$data=array("gibbonPersonID"=>$t, "gibbonPlannerEntryID"=>$AI, "role"=>$role); 
								$sql="INSERT INTO gibbonPlannerEntryGuest SET gibbonPersonID=:gibbonPersonID, gibbonPlannerEntryID=:gibbonPlannerEntryID, role=:role" ;
								$result=$connection2->prepare($sql);
								$result->execute($data);
							}
							catch(PDOException $e) { 
								$partialFail=TRUE ;
							}
						}
					}
				}
				
				//Insert outcomes
				$count=0 ;
				if (isset($_POST["outcomeorder"])) {
					if (count($_POST["outcomeorder"])>0) {
						foreach ($_POST["outcomeorder"] AS $outcome) {
							if ($_POST["outcomegibbonOutcomeID$outcome"]!="") {
								try {
									$dataInsert=array("AI"=>$AI, "gibbonOutcomeID"=>$_POST["outcomegibbonOutcomeID$outcome"], "content"=>$_POST["outcomecontents$outcome"], "count"=>$count);  
									$sqlInsert="INSERT INTO gibbonPlannerEntryOutcome SET gibbonPlannerEntryID=:AI, gibbonOutcomeID=:gibbonOutcomeID, content=:content, sequenceNumber=:count" ;
									$resultInsert=$connection2->prepare($sqlInsert);
									$resultInsert->execute($dataInsert);
								}
								catch(PDOException $e) {
									$partialFail=true ;
								}
							}
							$count++ ;
						}	
					}
				}
			
				//Write to database
				try {
					$data=array("gibbonCourseClassID"=>$gibbonCourseClassID, "date"=>$date, "timeStart"=>$timeStart, "timeEnd"=>$timeEnd, "gibbonUnitID"=>$gibbonUnitID, "gibbonHookID"=>$gibbonHookID, "name"=>$name, "summary"=>$summary, "description"=>$description, "teachersNotes"=>$teachersNotes, "homework"=>$homework, "homeworkDueDate"=>$homeworkDueDate, "homeworkDetails"=>$homeworkDetails, "homeworkSubmission"=>$homeworkSubmission, "homeworkSubmissionDateOpen"=>$homeworkSubmissionDateOpen, "homeworkSubmissionDrafts"=>$homeworkSubmissionDrafts, "homeworkSubmissionType"=>$homeworkSubmissionType, "homeworkSubmissionRequired"=>$homeworkSubmissionRequired, "homeworkCrowdAssess"=>$homeworkCrowdAssess, "homeworkCrowdAssessOtherTeachersRead"=>$homeworkCrowdAssessOtherTeachersRead, "homeworkCrowdAssessClassmatesRead"=>$homeworkCrowdAssessClassmatesRead, "homeworkCrowdAssessOtherStudentsRead"=>$homeworkCrowdAssessOtherStudentsRead, "homeworkCrowdAssessSubmitterParentsRead"=>$homeworkCrowdAssessSubmitterParentsRead, "homeworkCrowdAssessClassmatesParentsRead"=>$homeworkCrowdAssessClassmatesParentsRead, "homeworkCrowdAssessOtherParentsRead"=>$homeworkCrowdAssessOtherParentsRead, "viewableParents"=>$viewableParents, "viewableStudents"=>$viewableStudents, "twitterSearch"=>$twitterSearch, "gibbonPersonIDCreator"=>$gibbonPersonIDCreator, "gibbonPersonIDLastEdit"=>$gibbonPersonIDLastEdit); 
					$sql="INSERT INTO gibbonPlannerEntry SET gibbonCourseClassID=:gibbonCourseClassID, date=:date, timeStart=:timeStart, timeEnd=:timeEnd, gibbonUnitID=:gibbonUnitID, gibbonHookID=:gibbonHookID, name=:name, summary=:summary, description=:description, teachersNotes=:teachersNotes, homework=:homework, homeworkDueDateTime=:homeworkDueDate, homeworkDetails=:homeworkDetails, homeworkSubmission=:homeworkSubmission, homeworkSubmissionDateOpen=:homeworkSubmissionDateOpen, homeworkSubmissionDrafts=:homeworkSubmissionDrafts, homeworkSubmissionType=:homeworkSubmissionType, homeworkSubmissionRequired=:homeworkSubmissionRequired, homeworkCrowdAssess=:homeworkCrowdAssess, homeworkCrowdAssessOtherTeachersRead=:homeworkCrowdAssessOtherTeachersRead, homeworkCrowdAssessClassmatesRead=:homeworkCrowdAssessClassmatesRead, homeworkCrowdAssessOtherStudentsRead=:homeworkCrowdAssessOtherStudentsRead, homeworkCrowdAssessSubmitterParentsRead=:homeworkCrowdAssessSubmitterParentsRead, homeworkCrowdAssessClassmatesParentsRead=:homeworkCrowdAssessClassmatesParentsRead, homeworkCrowdAssessOtherParentsRead=:homeworkCrowdAssessOtherParentsRead, viewableParents=:viewableParents, viewableStudents=:viewableStudents, twitterSearch=:twitterSearch, gibbonPersonIDCreator=:gibbonPersonIDCreator, gibbonPersonIDLastEdit=:gibbonPersonIDLastEdit" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					//Fail 2
					$URL=$URL . "&addReturn=fail2$params" ;
					header("Location: {$URL}");
					break ;
				}
				
				//Unlock module table
				try {
					$sql="UNLOCK TABLES" ;
					$result=$connection2->query($sql);   
				}
				catch(PDOException $e) { }	
				
				if ($partialFail==TRUE) {
					//Fail 5
					$URL=$URL . "&addReturn=fail5$params" ;
					header("Location: {$URL}");
				}
				else {
					//Jump to Markbook?
					$markbook=$_POST["markbook"] ;
					if ($markbook=="Y") {
						$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Markbook/markbook_edit_add.php&gibbonPlannerEntryID=$AI&gibbonCourseClassID=$gibbonCourseClassID&gibbonUnitID=" . $_POST["gibbonUnitID"] . "&viewableParents=$viewableParents&viewableStudents=$viewableStudents&name=$name&summary=$summary&addReturnPlanner=success0" ;
						header("Location: {$URL}");
					}
					else {
						//Success 0
						$URL=$URL . "&addReturn=success0$params" ;
						header("Location: {$URL}");
					}
				}
			}
		}
	}
}
?>