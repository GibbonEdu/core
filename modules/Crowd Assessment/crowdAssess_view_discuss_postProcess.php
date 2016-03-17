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
$gibbonPlannerEntryHomeworkID=$_GET["gibbonPlannerEntryHomeworkID"] ;
$gibbonPersonID=$_GET["gibbonPersonID"] ;

$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["address"]) . "/crowdAssess_view_discuss.php&gibbonPlannerEntryID=$gibbonPlannerEntryID&gibbonPlannerEntryHomeworkID=$gibbonPlannerEntryHomeworkID&gibbonPersonID=$gibbonPersonID" ;
								
if (isActionAccessible($guid, $connection2, "/modules/Crowd Assessment/crowdAssess_view_discuss_post.php")==FALSE) {
	//Fail 0
	$URL.="&updateReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	//Proceed!
	//Check if school year specified
	if ($gibbonPlannerEntryID=="" OR $gibbonPlannerEntryHomeworkID=="" OR $gibbonPersonID=="") {
		//Fail1
		$URL.="&updateReturn=fail1" ;
		header("Location: {$URL}");
	}
	else {
		$and=" AND gibbonPlannerEntryID=$gibbonPlannerEntryID" ;
		$sql=getLessons($guid, $connection2, $and) ;
		try {
			$result=$connection2->prepare($sql[1]);
			$result->execute($sql[0]);
		}
		catch(PDOException $e) { 
			//Fail2
			$URL.="&updateReturn=fail2" ;
			header("Location: {$URL}");
			exit() ;
		}
		
		if ($result->rowCount()!=1) {
			//Fail 5
			$URL.="&updateReturn=fail5" ;
			header("Location: {$URL}");
		}
		else {
			$row=$result->fetch() ;
			
			$role=getCARole($guid, $connection2, $row["gibbonCourseClassID"]) ;
			
			if ($role=="") {
				//Fail2
				$URL.="&updateReturn=fail2" ;
				header("Location: {$URL}");
			}
			else {
				$sqlList=getStudents($guid, $connection2, $role, $row["gibbonCourseClassID"], $row["homeworkCrowdAssessOtherTeachersRead"], $row["homeworkCrowdAssessOtherParentsRead"], $row["homeworkCrowdAssessSubmitterParentsRead"], $row["homeworkCrowdAssessClassmatesParentsRead"], $row["homeworkCrowdAssessOtherStudentsRead"], $row["homeworkCrowdAssessClassmatesRead"], " AND gibbonPerson.gibbonPersonID=$gibbonPersonID") ;
				
				if ($sqlList[1]!="") {
					try {
						$resultList=$connection2->prepare($sqlList[1]);
						$resultList->execute($sqlList[0]);
					}
					catch(PDOException $e) { 
						//Fail2
						$URL.="&updateReturn=fail2" ;
						header("Location: {$URL}");
						exit() ;
					}
					
					if ($resultList->rowCount()!=1) {
						//Fail2
						$URL.="&updateReturn=fail2" ;
						header("Location: {$URL}");
					}
					else {
						//INSERT
						$replyTo=NULL ;
						if ($_GET["replyTo"]!="") {
							$replyTo=$_GET["replyTo"] ;
						}
						
						//Attempt to prevent XSS attack
						$comment=$_POST["comment"] ;
						$comment=tinymceStyleStripTags($comment, $connection2) ;
						
						try {
							$data=array("gibbonPlannerEntryHomeworkID"=>$gibbonPlannerEntryHomeworkID, "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"], "comment"=>$comment, "replyTo"=>$replyTo); 
							$sql="INSERT INTO gibbonCrowdAssessDiscuss SET gibbonPlannerEntryHomeworkID=:gibbonPlannerEntryHomeworkID, gibbonPersonID=:gibbonPersonID, comment=:comment, gibbonCrowdAssessDiscussIDReplyTo=:replyTo" ;
							$result=$connection2->prepare($sql);
							$result->execute($data);
						}
						catch(PDOException $e) { 
							//Fail 2
							$URL.="&updateReturn=fail2" ;
							header("Location: {$URL}");
							exit() ;
						}
						$hash="" ;
						if ($_GET["replyTo"]!="") {
							$hash="#" . $_GET["replyTo"] ;	
						}
						
						//Work out who we are replying too
						$replyToID=NULL ;
						$dataClassGroup=array("gibbonCrowdAssessDiscussID"=>$replyTo); 
						$sqlClassGroup="SELECT * FROM gibbonCrowdAssessDiscuss WHERE gibbonCrowdAssessDiscussID=:gibbonCrowdAssessDiscussID" ;
						$resultClassGroup=$connection2->prepare($sqlClassGroup);
						$resultClassGroup->execute($dataClassGroup);
						if ($resultClassGroup->rowCount()==1) {
							$rowClassGroup=$resultClassGroup->fetch() ;
							$replyToID=$rowClassGroup["gibbonPersonID"] ;
						}
						
						//Get lesson plan name
						$dataLesson=array("gibbonPlannerEntryID"=>$gibbonPlannerEntryID); 
						$sqlLesson="SELECT * FROM gibbonPlannerEntry WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID" ;
						$resultLesson=$connection2->prepare($sqlLesson);
						$resultLesson->execute($dataLesson);
						if ($resultLesson->rowCount()==1) {
							$rowLesson=$resultLesson->fetch() ;
							$name=$rowLesson["name"] ;
						}

						//Create notification for homework owner, as long as it is not me.
						if ($gibbonPersonID!=$_SESSION[$guid]["gibbonPersonID"] AND $gibbonPersonID!=$replyToID) {
							$notificationText=sprintf(__($guid, 'Someone has commented on your homework for lesson plan "%1$s".'), $name) ;
							setNotification($connection2, $guid, $gibbonPersonID, $notificationText, "Crowd Assessment", "/index.php?q=/modules/Crowd Assessment/crowdAssess_view_discuss.php&gibbonPlannerEntryID=$gibbonPlannerEntryID&gibbonPlannerEntryHomeworkID=$gibbonPlannerEntryHomeworkID&gibbonPersonID=$gibbonPersonID") ;
						} 

						//Create notification to person I am replying to
						if (is_null($replyToID)==FALSE) {
							$notificationText=sprintf(__($guid, 'Someone has replied to a comment on homework for lesson plan "%1$s".'), $name) ;
							setNotification($connection2, $guid, $replyToID, $notificationText, "Crowd Assessment", "/index.php?q=/modules/Crowd Assessment/crowdAssess_view_discuss.php&gibbonPlannerEntryID=$gibbonPlannerEntryID&gibbonPlannerEntryHomeworkID=$gibbonPlannerEntryHomeworkID&gibbonPersonID=$gibbonPersonID") ;
						}
						
						//Success 0
						$URL.="&updateReturn=success0$hash" ;
						header("Location: {$URL}");
					}
				}
			}
		}
	}
}
?>