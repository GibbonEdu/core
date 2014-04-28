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
$currentDate=$_POST["currentDate"] ;
$today=date("Y-m-d");
$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/planner_view_full.php&gibbonPlannerEntryID=$gibbonPlannerEntryID" . $_POST["params"] ;
							
if (isActionAccessible($guid, $connection2, "/modules/Planner/planner_view_full.php")==FALSE) {
	//Fail 0
	$URL=$URL . "&updateReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	$highestAction=getHighestGroupedAction($guid, $_POST["address"], $connection2) ;
	if ($highestAction==FALSE) {
		//Fail 0
		$URL=$URL . "&updateReturn=fail0$params" ;
		header("Location: {$URL}");
	}
	else {
		//Proceed!
		//Check if planner specified
		if ($gibbonPlannerEntryID=="") {
			//Fail1
			$URL=$URL . "&updateReturn=fail1" ;
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
				//Check that date is not in the future
				if ($currentDate>$today) {
					//Fail 4
					$URL=$URL . "&updateReturn=fail4" ;
					header("Location: {$URL}");
				}
				else {
					//Check that date is a school day
					if (isSchoolOpen($guid, $currentDate, $connection2)==FALSE) {
						//Fail 5
						$URL=$URL . "&updateReturn=fail5" ;
						header("Location: {$URL}");
					}
					else {
						//Write to database
						try {
							$data=array("gibbonPersonIDTaker"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonPlannerEntryID"=>$gibbonPlannerEntryID, "timestampTaken"=>date("Y-m-d H:i:s")); 
							$sql="INSERT INTO gibbonPlannerEntryAttendanceLog SET gibbonPersonIDTaker=:gibbonPersonIDTaker, gibbonPlannerEntryID=:gibbonPlannerEntryID, timestampTaken=:timestampTaken" ;
							$result=$connection2->prepare($sql);
							$result->execute($data);
						}
						catch(PDOException $e) { 
							//Fail 2
							$URL=$URL . "&updateReturn=fail2" ;
							header("Location: {$URL}");
							break ;
						}

						$count=$_POST["countStudents"] ;
						$partialFail=FALSE ;
						
						for ($i=0; $i<$count; $i++) {
							$gibbonPersonID=$_POST[$i . "-gibbonPersonID"] ;
							$type=$_POST[$i . "-type"] ;
							
							//Check for last record on same day
							$recordCheck=true ;
							try {
								$data=array("gibbonPersonID"=>$gibbonPersonID, "gibbonPlannerEntryID"=>$gibbonPlannerEntryID); 
								$sql="SELECT * FROM gibbonPlannerEntryAttendance WHERE gibbonPersonID=:gibbonPersonID AND gibbonPlannerEntryID=:gibbonPlannerEntryID" ;
								$result=$connection2->prepare($sql);
								$result->execute($data);
							}
							catch(PDOException $e) { 
								$partialFail=TRUE ;
								$recordCheck=false ;
							}
							
							if ($recordCheck) {
								if ($result->rowCount()!=1) {
									//If no records then create one
									try {
										$dataUpdate=array("gibbonPersonID"=>$gibbonPersonID, "gibbonPlannerEntryID"=>$gibbonPlannerEntryID, "type"=>$type, "gibbonPersonIDTaker"=>$_SESSION[$guid]["gibbonPersonID"]); 
										$sqlUpdate="INSERT INTO gibbonPlannerEntryAttendance SET gibbonPersonID=:gibbonPersonID, gibbonPlannerEntryID=:gibbonPlannerEntryID, type=:type, gibbonPersonIDTaker=:gibbonPersonIDTaker" ;
										$resultUpdate=$connection2->prepare($sqlUpdate);
										$resultUpdate->execute($dataUpdate);
									}
									catch(PDOException $e) { 
										$partialFail=TRUE ;
									}
								}
								else {
									$row=$result->fetch() ;
									try {
										$dataUpdate=array("type"=>$type, "gibbonPersonIDTaker"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonPlannerEntryAttendanceID"=>$row["gibbonPlannerEntryAttendanceID"]); 
										$sqlUpdate="UPDATE gibbonPlannerEntryAttendance SET type=:type, gibbonPersonIDTaker=:gibbonPersonIDTaker WHERE gibbonPlannerEntryAttendanceID=:gibbonPlannerEntryAttendanceID" ;
										$resultUpdate=$connection2->prepare($sqlUpdate);
										$resultUpdate->execute($dataUpdate);
									}
									catch(PDOException $e) { 
										$partialFail=TRUE ;
									}
								}
							}
						}
					}
					
					if ($partialFail==TRUE) {
						//Fail 3
						$URL=$URL . "&updateReturn=fail3" ;
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