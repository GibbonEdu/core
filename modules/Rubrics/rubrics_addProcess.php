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
$pdo = new Gibbon\sqlConnection();
$connection2 = $pdo->getConnection();

@session_start() ;

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]["timezone"]);

//Search & Filters
$search=NULL ;
if (isset($_GET["search"])) {
	$search=$_GET["search"] ;
}
$filter2=NULL ;
if (isset($_GET["filter2"])) {
	$filter2=$_GET["filter2"] ;
}

$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/rubrics_add.php&search=$search&filter2=$filter2" ;
$URLSuccess=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/rubrics_edit.php&sidebar=false&search=$search&filter2=$filter2" ;

if (isActionAccessible($guid, $connection2, "/modules/Rubrics/rubrics_add.php")==FALSE) {
	//Fail 0
	$URL.="&addReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	$highestAction=getHighestGroupedAction($guid, $_POST["address"], $connection2) ;
	if ($highestAction==FALSE) {
		//Fail2
		$URL.="&addReturn=fail2" ;
		header("Location: {$URL}");
	}
	else {
		if ($highestAction!="Manage Rubrics_viewEditAll" AND $highestAction!="Manage Rubrics_viewAllEditLearningArea") {
			//Fail 0
			$URL.="&addReturn=fail0" ;
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
			$gibbonScaleID=NULL ;
			if ($_POST["gibbonScaleID"]!="") {
				$gibbonScaleID=$_POST["gibbonScaleID"] ;
			}
						
			if ($scope=="" OR ($scope=="Learning Area" AND $gibbonDepartmentID=="") OR $name=="" OR $active=="") {
				//Fail 3
				$URL.="&addReturn=fail3" ;
				header("Location: {$URL}");
			}
			else {
				//Lock table
				try {
					$sql="LOCK TABLES gibbonRubric WRITE" ;
					$result=$connection2->query($sql);   
				}
				catch(PDOException $e) { 
					//Fail 2
					$URL.="&addReturn=fail2" ;
					header("Location: {$URL}");
					exit() ;
				}		
				
				//Get next autoincrement
				try {
					$sqlAI="SHOW TABLE STATUS LIKE 'gibbonRubric'";
					$resultAI=$connection2->query($sqlAI);   
				}
				catch(PDOException $e) { 
					//Fail 2
					$URL.="&addReturn=fail2" ;
					header("Location: {$URL}");
					exit() ;
				}

				$rowAI=$resultAI->fetch();
				$AI=str_pad($rowAI['Auto_increment'], 8, "0", STR_PAD_LEFT) ;
				
				if ($AI=="") {
					//Fail 2
					$URL.="&addReturn=fail2" ;
					header("Location: {$URL}");
				}
				else {
					//Write to database
					try {
						$data=array("scope"=>$scope, "gibbonDepartmentID"=>$gibbonDepartmentID, "name"=>$name, "active"=>$active, "category"=>$category, "description"=>$description, "gibbonYearGroupIDList"=>$gibbonYearGroupIDList, "gibbonScaleID"=>$gibbonScaleID, "gibbonPersonIDCreator"=>$_SESSION[$guid]["gibbonPersonID"]); 
						$sql="INSERT INTO gibbonRubric SET scope=:scope, gibbonDepartmentID=:gibbonDepartmentID, name=:name, active=:active, category=:category, description=:description, gibbonYearGroupIDList=:gibbonYearGroupIDList, gibbonScaleID=:gibbonScaleID, gibbonPersonIDCreator=:gibbonPersonIDCreator" ;
						$result=$connection2->prepare($sql);
						$result->execute($data);
					}
					catch(PDOException $e) { 
						//Fail 2
						$URL.="&addReturn=fail2" ;
						header("Location: {$URL}");
						exit() ;
					}
					
					//Unlock module table
					try {
						$sql="UNLOCK TABLES" ;
						$result=$connection2->query($sql);   
					}
					catch(PDOException $e) { 
						//Fail 2
						$URL.="&addReturn=fail2" ;
						header("Location: {$URL}");
						exit() ;
					}

					//Create rows & columns
					for ($i=1; $i<=$_POST["rows"]; $i++) {
						try {
							$data=array("gibbonRubricID"=>$AI, "title"=>"Row $i", "sequenceNumber"=>$i); 
							$sql="INSERT INTO gibbonRubricRow SET gibbonRubricID=:gibbonRubricID, title=:title, sequenceNumber=:sequenceNumber" ;
							$result=$connection2->prepare($sql);
							$result->execute($data);
						}
						catch(PDOException $e) { }
					}
					for ($i=1; $i<=$_POST["columns"]; $i++) {
						try {
							$data=array("gibbonRubricID"=>$AI, "title"=>"Column $i", "sequenceNumber"=>$i); 
							$sql="INSERT INTO gibbonRubricColumn SET gibbonRubricID=:gibbonRubricID, title=:title, sequenceNumber=:sequenceNumber" ;
							$result=$connection2->prepare($sql);
							$result->execute($data);
						}
						catch(PDOException $e) { }
					}
					
					//Success 0
					$URL=$URLSuccess . "&addReturn=success0&gibbonRubricID=$AI" ;
					header("Location: {$URL}");
				}
			}
		}
	}
}
?>