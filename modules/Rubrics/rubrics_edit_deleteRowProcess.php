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

$gibbonRubricID=$_GET["gibbonRubricID"] ;
$gibbonRubricRowID=$_GET["gibbonRubricRowID"] ;
$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["address"]) . "/rubrics_edit.php&gibbonRubricID=$gibbonRubricID&sidebar=false" ;

if (isActionAccessible($guid, $connection2, "/modules/Rubrics/rubrics_edit.php")==FALSE) {
	//Fail 0
	$URL=$URL . "&rowDeleteReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	$highestAction=getHighestGroupedAction($guid, $_GET["address"], $connection2) ;
	if ($highestAction==FALSE) {
		//Fail2
		$URL=$URL . "&rowDeleteReturn=fail2" ;
		header("Location: {$URL}");
	}
	else {
		if ($highestAction!="Manage Rubrics_viewEditAll" AND $highestAction!="Manage Rubrics_viewAllEditLearningArea") {
			//Fail 0
			$URL=$URL . "&rowDeleteReturn=fail0" ;
			header("Location: {$URL}");
		}
		else {
			//Proceed!
			//Check if school year specified
			if ($gibbonRubricID=="" OR $gibbonRubricRowID=="") {
				//Fail1
				$URL=$URL . "&rowDeleteReturn=fail1" ;
				header("Location: {$URL}");
			}
			else {
				try {
					if ($highestAction=="Manage Rubrics_viewEditAll") {
						$data=array("gibbonRubricID"=>$gibbonRubricID); 
						$sql="SELECT * FROM gibbonRubric WHERE gibbonRubricID=:gibbonRubricID" ;
					}
					else if ($highestAction=="Manage Rubrics_viewAllEditLearningArea") {
						$data=array("gibbonRubricID"=>$gibbonRubricID, "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
						$sql="SELECT * FROM gibbonRubric JOIN gibbonDepartment ON (gibbonRubric.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) AND NOT gibbonRubric.gibbonDepartmentID IS NULL WHERE gibbonRubricID=:gibbonRubricID AND (role='Coordinator' OR role='Teacher (Curriculum)') AND gibbonPersonID=:gibbonPersonID AND scope='Learning Area'" ;
					}
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					//Fail2
					$URL=$URL . "&columnDeleteReturn=fail2" ;
					header("Location: {$URL}");
					break ;
				}
				
				if ($result->rowCount()!=1) {
					//Fail 2
					$URL=$URL . "&rowDeleteReturn=fail2" ;
					header("Location: {$URL}");
				}
				else {
					//Check for existence and association of row
					try {
						$dataRow=array("gibbonRubricID"=>$gibbonRubricID, "gibbonRubricRowID"=>$gibbonRubricRowID); 
						$sqlRow="SELECT * FROM gibbonRubric JOIN gibbonRubricRow ON (gibbonRubricRow.gibbonRubricID=gibbonRubric.gibbonRubricID) WHERE gibbonRubricRow.gibbonRubricID=:gibbonRubricID AND gibbonRubricRowID=:gibbonRubricRowID" ;
						$resultRow=$connection2->prepare($sqlRow);
						$resultRow->execute($dataRow);
					}
					catch(PDOException $e) { 
						//Fail2
						$URL=$URL . "&rowDeleteReturn=fail2" ;
						header("Location: {$URL}");
						break ;
					}
					
					if ($resultRow->rowCount()!=1) {
						//Fail 2
						$URL=$URL . "&rowDeleteReturn=fail2" ;
						header("Location: {$URL}");
					}
					else {
						//Combined delete of row and cells
						try {
							$data=array("gibbonRubricID"=>$gibbonRubricID, "gibbonRubricRowID"=>$gibbonRubricRowID); 
							$sql="DELETE FROM gibbonRubricRow WHERE gibbonRubricRow.gibbonRubricID=:gibbonRubricID AND gibbonRubricRow.gibbonRubricRowID=:gibbonRubricRowID" ;
							$result=$connection2->prepare($sql);
							$result->execute($data);
						}
						catch(PDOException $e) { 
							//Fail 2
							$URL=$URL . "&rowDeleteReturn=fail2" ;
							header("Location: {$URL}");
							break ;
						}
						
						try {
							$data=array("gibbonRubricID"=>$gibbonRubricID, "gibbonRubricRowID"=>$gibbonRubricRowID); 
							$sql="DELETE FROM gibbonRubricCell WHERE gibbonRubricCell.gibbonRubricID=:gibbonRubricID AND gibbonRubricCell.gibbonRubricRowID=:gibbonRubricRowID" ;
							$result=$connection2->prepare($sql);
							$result->execute($data);
						}
						catch(PDOException $e) { }
						
						//Success 0
						$URL=$URL . "&rowDeleteReturn=success0" ;
						header("Location: {$URL}");
					}
				}
			}
		}
	}
}
?>