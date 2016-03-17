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

$gibbonPlannerEntryID=$_POST["gibbonPlannerEntryID"] ;
$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/planner_view_full.php&gibbonPlannerEntryID=$gibbonPlannerEntryID&search=" . $_POST["search"] . $_POST["params"] ;

if (isActionAccessible($guid, $connection2, "/modules/Planner/planner_view_full.php")==FALSE) {
	//Fail 0
	$URL.="&postReturn=fail0" ;
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
		//Check if planner specified
		if ($gibbonPlannerEntryID=="") {
			//Fail1
			$URL.="&postReturn=fail1" ;
			header("Location: {$URL}");
		}
		else {
			try {
				$data=array("gibbonPlannerEntryID"=>$gibbonPlannerEntryID); 
				$sql="SELECT * FROM gibbonPlannerEntry WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID" ;
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				//Fail2
				$URL.="&postReturn=fail2" ;
				header("Location: {$URL}");
				exit() ;
			}

			if ($result->rowCount()!=1) {
				//Fail 2
				$URL.="&postReturn=fail2" ;
				header("Location: {$URL}");
			}
			else {	
				$row=$result->fetch() ;
				
				//INSERT
				$replyTo=$_POST["replyTo"] ;
				if ($_POST["replyTo"]=="") {
					$replyTo=NULL ;
				}
				//Attempt to prevent XSS attack
				$comment=$_POST["comment"] ;
				$comment=tinymceStyleStripTags($comment, $connection2) ;
				
				try {
					$dataInsert=array("gibbonPlannerEntryID"=>$gibbonPlannerEntryID, "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"], "comment"=>$comment, "replyTo"=>$replyTo); 
					$sqlInsert="INSERT INTO gibbonPlannerEntryDiscuss SET gibbonPlannerEntryID=:gibbonPlannerEntryID, gibbonPersonID=:gibbonPersonID, comment=:comment, gibbonPlannerEntryDiscussIDReplyTo=:replyTo" ;
					$resultInsert=$connection2->prepare($sqlInsert);
					$resultInsert->execute($dataInsert);
				}
				catch(PDOException $e) { 
					//Fail2
					$URL.="&postReturn=fail2" ;
					header("Location: {$URL}");
					exit() ;
				}
				
				//Work out who we are replying too
				$replyToID=NULL ;
				$dataClassGroup=array("gibbonPlannerEntryDiscussID"=>$replyTo); 
				$sqlClassGroup="SELECT * FROM gibbonPlannerEntryDiscuss WHERE gibbonPlannerEntryDiscussID=:gibbonPlannerEntryDiscussID" ;
				$resultClassGroup=$connection2->prepare($sqlClassGroup);
				$resultClassGroup->execute($dataClassGroup);
				if ($resultClassGroup->rowCount()==1) {
					$rowClassGroup=$resultClassGroup->fetch() ;
					$replyToID=$rowClassGroup["gibbonPersonID"] ;
				}
				
				//Create notification for all people in class except me
				$dataClassGroup=array("gibbonCourseClassID"=>$row["gibbonCourseClassID"]); 
				$sqlClassGroup="SELECT * FROM gibbonCourseClassPerson INNER JOIN gibbonPerson ON gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID WHERE gibbonCourseClassID=:gibbonCourseClassID AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND (NOT role='Student - Left') AND (NOT role='Teacher - Left') ORDER BY role DESC, surname, preferredName" ;
				$resultClassGroup=$connection2->prepare($sqlClassGroup);
				$resultClassGroup->execute($dataClassGroup);
				while ($rowClassGroup=$resultClassGroup->fetch()) {
					if ($rowClassGroup["gibbonPersonID"]!=$_SESSION[$guid]["gibbonPersonID"] AND $rowClassGroup["gibbonPersonID"]!=$replyToID) {
						$notificationText=sprintf(__($guid, 'Someone has commented on your lesson plan "%1$s".'), $row["name"]) ;
						setNotification($connection2, $guid, $rowClassGroup["gibbonPersonID"], $notificationText, "Planner", "/index.php?q=/modules/Planner/planner_view_full.php&gibbonPlannerEntryID=$gibbonPlannerEntryID&viewBy=date&date=" . $row["date"] . "&gibbonCourseClassID=&search=#chat") ;
					} 
				}
				
				//Create notification to person I am replying to
				if (is_null($replyToID)==FALSE) {
					$notificationText=sprintf(__($guid, 'Someone has replied to a comment you made on lesson plan "%1$s".'), $row["name"]) ;
					setNotification($connection2, $guid, $replyToID, $notificationText, "Planner", "/index.php?q=/modules/Planner/planner_view_full.php&gibbonPlannerEntryID=$gibbonPlannerEntryID&viewBy=date&date=" . $row["date"] . "&gibbonCourseClassID=&search=#chat") ;
				}
						
				//Success 0
				$URL.="&postReturn=success0" ;
				header("Location: {$URL}");	
			}
		}
	}
}
?>