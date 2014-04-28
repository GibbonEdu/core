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

$gibbonOutcomeID=$_GET["gibbonOutcomeID"] ;
$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/outcomes_edit.php&gibbonOutcomeID=$gibbonOutcomeID" ;

if (isActionAccessible($guid, $connection2, "/modules/Planner/outcomes_edit.php")==FALSE) {
	//Fail 0
	$URL=$URL . "&updateReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	$highestAction=getHighestGroupedAction($guid, $_POST["address"], $connection2) ;
	if ($highestAction==FALSE) {
		//Fail2
		$URL=$URL . "&updateReturn=fail2" ;
		header("Location: {$URL}");
	}
	else {
		if ($highestAction!="Manage Outcomes_viewEditAll" AND $highestAction!="Manage Outcomes_viewAllEditLearningArea") {
			//Fail 0
			$URL=$URL . "&updateReturn=fail0" ;
			header("Location: {$URL}");
		}
		else {
			//Proceed!
			//Check if school year specified
			if ($gibbonOutcomeID=="") {
				//Fail1
				$URL=$URL . "&updateReturn=fail1" ;
				header("Location: {$URL}");
			}
			else {
				try {
					if ($highestAction=="Manage Outcomes_viewEditAll") {
						$data=array("gibbonOutcomeID"=>$gibbonOutcomeID); 
						$sql="SELECT * FROM gibbonOutcome WHERE gibbonOutcomeID=:gibbonOutcomeID" ;
					}
					else if ($highestAction=="Manage Outcomes_viewAllEditLearningArea") {
						$data=array("gibbonOutcomeID"=>$gibbonOutcomeID, "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
						$sql="SELECT * FROM gibbonOutcome JOIN gibbonDepartment ON (gibbonOutcome.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) AND NOT gibbonOutcome.gibbonDepartmentID IS NULL WHERE gibbonOutcomeID=:gibbonOutcomeID AND (role='Coordinator' OR role='Teacher (Curriculum)') AND gibbonPersonID=:gibbonPersonID AND scope='Learning Area'" ;
					}
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					//Fail2
					$URL=$URL . "&deleteReturn=fail2" ;
					header("Location: {$URL}");
					break ;
				}
			
				if ($result->rowCount()!=1) {
					//Fail 2
					$URL=$URL . "&updateReturn=fail2" ;
					header("Location: {$URL}");
				}
				else {
					//Proceed!
					$scope=$_POST["scope"] ;
					if ($scope=="Learning Area") {
						$gibbonDepartmentID=$_POST["gibbonDepartmentID"] ;
					}
					else {
						$gibbonDepartmentID=NULL ;
					}
					$name=$_POST["name"] ;
					$nameShort=$_POST["nameShort"] ;
					$active=$_POST["active"] ;
					$category=$_POST["category"] ;
					$description=$_POST["description"] ;
					$gibbonYearGroupIDList="" ;
					for ($i=0; $i<$_POST["count"]; $i++) {
						if (isset($_POST["gibbonYearGroupIDCheck$i"])) {
							if ($_POST["gibbonYearGroupIDCheck$i"]=="on") {
								$gibbonYearGroupIDList=$gibbonYearGroupIDList . $_POST["gibbonYearGroupID$i"] . "," ;
							}
						}
					}
					$gibbonYearGroupIDList=substr($gibbonYearGroupIDList,0,(strlen($gibbonYearGroupIDList)-1)) ;
							
					if ($scope=="" OR ($scope=="Learning Area" AND $gibbonDepartmentID=="") OR $name=="" OR $nameShort=="" OR $active=="") {
						//Fail 3
						$URL=$URL . "&updateReturn=fail3" ;
						header("Location: {$URL}");
					}
					else {
						//Write to database
						try {
							$data=array("scope"=>$scope, "gibbonDepartmentID"=>$gibbonDepartmentID, "name"=>$name, "nameShort"=>$nameShort, "active"=>$active, "category"=>$category, "description"=>$description, "gibbonYearGroupIDList"=>$gibbonYearGroupIDList, "gibbonOutcomeID"=>$gibbonOutcomeID); 
							$sql="UPDATE gibbonOutcome SET scope=:scope, gibbonDepartmentID=:gibbonDepartmentID, name=:name, nameShort=:nameShort, active=:active, category=:category, description=:description, gibbonYearGroupIDList=:gibbonYearGroupIDList WHERE gibbonOutcomeID=:gibbonOutcomeID" ;
							$result=$connection2->prepare($sql);
							$result->execute($data);
						}
						catch(PDOException $e) { 
							//Fail 2
							$URL=$URL . "&addReturn=fail2" ;
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
?>