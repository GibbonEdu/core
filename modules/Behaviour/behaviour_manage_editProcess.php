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

$gibbonBehaviourID=$_GET["gibbonBehaviourID"] ;
$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/behaviour_manage_edit.php&gibbonBehaviourID=$gibbonBehaviourID&gibbonPersonID=" . $_GET["gibbonPersonID"] . "&gibbonRollGroupID=" . $_GET["gibbonRollGroupID"] . "&gibbonYearGroupID=" . $_GET["gibbonYearGroupID"] . "&type=" .$_GET["type"] ;

if (isActionAccessible($guid, $connection2, "/modules/Behaviour/behaviour_manage_edit.php")==FALSE) {
	//Fail 0
	$URL.="&updateReturn=fail0" ;
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
		if ($gibbonBehaviourID=="") {
			//Fail1
			$URL.="&updateReturn=fail1" ;
			header("Location: {$URL}");
		}
		else {
			try {
				if ($highestAction=="Manage Behaviour Records_all") {
					$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonBehaviourID"=>$gibbonBehaviourID); 
					$sql="SELECT gibbonBehaviour.*, student.surname AS surnameStudent, student.preferredName AS preferredNameStudent, creator.surname AS surnameCreator, creator.preferredName AS preferredNameCreator, creator.title FROM gibbonBehaviour JOIN gibbonPerson AS student ON (gibbonBehaviour.gibbonPersonID=student.gibbonPersonID) JOIN gibbonPerson AS creator ON (gibbonBehaviour.gibbonPersonIDCreator=creator.gibbonPersonID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonBehaviourID=:gibbonBehaviourID ORDER BY date DESC" ; 
				}
				else if ($highestAction=="Manage Behaviour Records_my") {
					$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonBehaviourID"=>$gibbonBehaviourID, "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
					$sql="SELECT gibbonBehaviour.*, student.surname AS surnameStudent, student.preferredName AS preferredNameStudent, creator.surname AS surnameCreator, creator.preferredName AS preferredNameCreator, creator.title FROM gibbonBehaviour JOIN gibbonPerson AS student ON (gibbonBehaviour.gibbonPersonID=student.gibbonPersonID) JOIN gibbonPerson AS creator ON (gibbonBehaviour.gibbonPersonIDCreator=creator.gibbonPersonID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonBehaviourID=:gibbonBehaviourID AND gibbonPersonIDCreator=:gibbonPersonID ORDER BY date DESC" ; 
				}
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				//Fail2
				$URL.="&updateReturn=fail2" ;
				header("Location: {$URL}");
				break ;
			}
		
			if ($result->rowCount()!=1) {
				//Fail 2
				$URL.="&updateReturn=fail2" ;
				header("Location: {$URL}");
			}
			else {
				$gibbonPersonID=$_POST["gibbonPersonID"] ; 
				$date=$_POST["date"] ; 
				$type=$_POST["type"] ; 
				$descriptor=$_POST["descriptor"] ; 
				$level=$_POST["level"] ; 
				$comment=$_POST["comment"] ; 
				$followup=$_POST["followup"] ; 
				if ($_POST["gibbonPlannerEntryID"]=="") {
					$gibbonPlannerEntryID=NULL ; 
				}
				else {
					$gibbonPlannerEntryID=$_POST["gibbonPlannerEntryID"] ; 
				}
				
				if ($gibbonPersonID=="" OR $date=="" OR $type=="" OR $descriptor=="") {
					//Fail 3
					$URL.="&updateReturn=fail3" ;
					header("Location: {$URL}");
				}
				else {
					try {
						$data=array("gibbonPersonID"=>$gibbonPersonID, "date"=>dateConvert($guid, $date), "type"=>$type, "descriptor"=>$descriptor, "level"=>$level, "comment"=>$comment, "followup"=>$followup, "gibbonPlannerEntryID"=>$gibbonPlannerEntryID, "gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonBehaviourID"=>$gibbonBehaviourID); 
						$sql="UPDATE gibbonBehaviour SET gibbonPersonID=:gibbonPersonID, date=:date, type=:type, descriptor=:descriptor, level=:level, comment=:comment, followup=:followup, gibbonPlannerEntryID=:gibbonPlannerEntryID, gibbonSchoolYearID=:gibbonSchoolYearID WHERE gibbonBehaviourID=:gibbonBehaviourID" ;
						$result=$connection2->prepare($sql);
						$result->execute($data);
					}
					catch(PDOException $e) { 
						//Fail 2
						$URL.="&updateReturn=fail2" ;
						header("Location: {$URL}");
						break ;
					}
			
					//Success 0
					$URL.="&updateReturn=success0" ;
					header("Location: {$URL}");			
				}
			}
		}
	}
}
?>