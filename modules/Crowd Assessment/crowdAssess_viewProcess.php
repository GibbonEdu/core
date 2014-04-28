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
$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["address"]) . "/crowdAssess_view.php&gibbonPlannerEntryID=$gibbonPlannerEntryID" ;

if (isActionAccessible($guid, $connection2, "/modules/Crowd Assessment/crowdAssess_view.php")==FALSE) {
	//Fail 0
	$URL=$URL . "&updateReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	//Proceed!
	//Check if school year specified
	if ($gibbonPlannerEntryID=="" OR $gibbonPlannerEntryHomeworkID=="" OR $gibbonPersonID=="") {
		//Fail1
		$URL=$URL . "&updateReturn=fail1" ;
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
			$URL=$URL . "&updateReturn=fail2" ;
			header("Location: {$URL}");
			break ;
		}
		
		if ($result->rowCount()!=1) {
			//Fail 5
			$URL=$URL . "&updateReturn=fail5" ;
			header("Location: {$URL}");
		}
		else {
			$row=$result->fetch() ;
			
			$role=getCARole($guid, $connection2, $row["gibbonCourseClassID"]) ;
			
			if ($role=="") {
				//Fail2
				$URL=$URL . "&updateReturn=fail2" ;
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
						$URL=$URL . "&updateReturn=fail2" ;
						header("Location: {$URL}");
						break ;
					}
					
					if ($resultList->rowCount()!=1) {
						//Fail2
						$URL=$URL . "&updateReturn=fail2" ;
						header("Location: {$URL}");
					}
					else {
						//Check like status
						try {
							$dataLike=array("gibbonPlannerEntryHomeworkID"=>$gibbonPlannerEntryHomeworkID, "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
							$sqlLike="SELECT * FROM gibbonCrowdAssessLike WHERE gibbonPlannerEntryHomeworkID=:gibbonPlannerEntryHomeworkID AND gibbonPersonID=:gibbonPersonID" ;
							$resultLike=$connection2->prepare($sqlLike);
							$resultLike->execute($dataLike);
						}
						catch(PDOException $e) { 
							//Fail2
							$URL=$URL . "&updateReturn=fail2" ;
							header("Location: {$URL}");
							break ;
						}

						//INSERT
						if ($resultLike->rowCount()!=1) {
							try {
								$data=array("gibbonPlannerEntryHomeworkID"=>$gibbonPlannerEntryHomeworkID, "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
								$sql="INSERT INTO gibbonCrowdAssessLike SET gibbonPlannerEntryHomeworkID=:gibbonPlannerEntryHomeworkID, gibbonPersonID=:gibbonPersonID" ;
								$result=$connection2->prepare($sql);
								$result->execute($data);
							}
							catch(PDOException $e) { 
								//Fail2
								$URL=$URL . "&updateReturn=fail2" ;
								header("Location: {$URL}");
								break ;
							}
							//Success 0
							$URL=$URL . "&updateReturn=success0" ;
							header("Location: {$URL}");
						}
						//DELETE
						else {
							try {
								$data=array("gibbonPlannerEntryHomeworkID"=>$gibbonPlannerEntryHomeworkID, "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
								$sql="DELETE FROM gibbonCrowdAssessLike WHERE gibbonPlannerEntryHomeworkID=:gibbonPlannerEntryHomeworkID AND gibbonPersonID=:gibbonPersonID" ;
								$result=$connection2->prepare($sql);
								$result->execute($data);
							}
							catch(PDOException $e) { 
								//Fail2
								$URL=$URL . "&updateReturn=fail2" ;
								header("Location: {$URL}");
								break ;
							}
							//Success 0
							$URL=$URL . "&updateReturn=success0" ;
							header("Location: {$URL}");
						}
					}
				}
			}
		}
	}
	//Print sidebar
	$_SESSION[$guid]["sidebarExtra"]=sidebarExtra($guid, $connection2) ;
}
?>