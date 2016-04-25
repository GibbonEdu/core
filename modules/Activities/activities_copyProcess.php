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
$pdo = new Gibbon\sqlConnection();
$connection2 = $pdo->getConnection();

@session_start() ;

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]["timezone"]);

$gibbonSchoolYearID=$_POST["gibbonSchoolYearID"] ;
$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/activities_copy.php" ;

if (isActionAccessible($guid, $connection2, "/modules/Activities/activities_copy.php")==FALSE) {
	//Fail 0
	$URL.="&return=error0" ;
	header("Location: {$URL}");
}
else {
	//Proceed!
	//Check if school year specified
	if ($gibbonSchoolYearID=="") {
		//Fail1
		$URL.="&return=error1" ;
		header("Location: {$URL}");
	}
	else {
		try {
			$data=array("gibbonSchoolYearID"=>$gibbonSchoolYearID); 
			$sql="SELECT * FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			//Fail2
			$URL.="&return=error2" ;
			header("Location: {$URL}");
			exit() ; 
		}
		
		if ($result->rowCount()!=1) {
			//Fail 2
			$URL.="&return=error2" ;
			header("Location: {$URL}");
		}
		else {
			//Validate Inputs
			$gibbonSchoolYearIDTarget=$_POST["gibbonSchoolYearIDTarget"] ;
			
			if ($gibbonSchoolYearIDTarget=="") {
				//Fail 3
				$URL.="&return=error1" ;
				header("Location: {$URL}");
			}
			else {
				$partialFail=FALSE ;
				
				//Scan through activities in current year
				try {
					$data=array("gibbonSchoolYearID"=>$gibbonSchoolYearID); 
					$sql="SELECT * FROM gibbonActivity WHERE gibbonSchoolYearID=:gibbonSchoolYearID" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) {
					$partialFail=TRUE ;
				}
				
				while ($row=$result->fetch()) {
					$activityFail=FALSE ;
					//Insert current activity in target year
					try {
						$dataInsert=array("gibbonSchoolYearID"=>$gibbonSchoolYearIDTarget, "name"=>$row["name"], "provider"=>$row["provider"], "type"=>$row["type"], "active"=>$row["active"], "listingStart"=>$row["listingStart"], "listingEnd"=>$row["listingEnd"], "programStart"=>$row["programStart"], "programEnd"=>$row["programEnd"], "gibbonSchoolYearTermIDList"=>$row["gibbonSchoolYearTermIDList"], "gibbonYearGroupIDList"=>$row["gibbonYearGroupIDList"], "maxParticipants"=>$row["maxParticipants"], "payment"=>$row["payment"], "description"=>$row["description"]); 
						$sqlInsert="INSERT INTO gibbonActivity SET gibbonSchoolYearID=:gibbonSchoolYearID, name=:name, provider=:provider, type=:type, active=:active, listingStart=:listingStart, listingEnd=:listingEnd, programStart=:programStart, programEnd=:programEnd, gibbonSchoolYearTermIDList=:gibbonSchoolYearTermIDList, gibbonYearGroupIDList=:gibbonYearGroupIDList, maxParticipants=:maxParticipants, payment=:payment, description=:description" ;
						$resultInsert=$connection2->prepare($sqlInsert);
						$resultInsert->execute($dataInsert); 
					}
					catch(PDOException $e) { 
						print $e->getMessage() ;
						$activityFail=TRUE ;
						$partialFail=TRUE ;
					}
					
					if ($activityFail==FALSE) {
						//Get last insert ID
						$gibbonActivityID=$connection2->lastInsertID() ;
						
						if (is_numeric($gibbonActivityID)==FALSE) {
							$partialFail=TRUE ;
						}
						else {
							//Get current activities' slots in current year
							try {
								$dataSelect=array("gibbonActivityID"=>$row["gibbonActivityID"]); 
								$sqlSelect="SELECT * FROM gibbonActivityStaff WHERE gibbonActivityID=:gibbonActivityID" ;
								$resultSelect=$connection2->prepare($sqlSelect);
								$resultSelect->execute($dataSelect);
							}
							catch(PDOException $e) {
								$partialFail=TRUE ;
							}
							
							while ($rowSelect=$resultSelect->fetch()) {
								//Insert current activities' slots into target year
								try {
									$dataInsert2=array("gibbonActivityID"=>$gibbonActivityID, "gibbonPersonID"=>$rowSelect["gibbonPersonID"], "role"=>$rowSelect["role"]); 
									$sqlInsert2="INSERT INTO gibbonActivityStaff SET gibbonActivityID=:gibbonActivityID, gibbonPersonID=:gibbonPersonID, role=:role" ;
									$resultInsert2=$connection2->prepare($sqlInsert2);
									$resultInsert2->execute($dataInsert2);
								}
								catch(PDOException $e) {
									$partialFail=TRUE ;
								}
							}
							
					
							//Get current activities' staff in current year
							try {
								$dataSelect=array("gibbonActivityID"=>$row["gibbonActivityID"]); 
								$sqlSelect="SELECT * FROM gibbonActivitySlot WHERE gibbonActivityID=:gibbonActivityID" ;
								$resultSelect=$connection2->prepare($sqlSelect);
								$resultSelect->execute($dataSelect);
							}
							catch(PDOException $e) {
								$partialFail=TRUE ;
							}
							
							while ($rowSelect=$resultSelect->fetch()) {
								//Insert current activities' staff into target year
								try {
									$dataInsert2=array("gibbonActivityID"=>$gibbonActivityID, "gibbonSpaceID"=>$rowSelect["gibbonSpaceID"], "locationExternal"=>$rowSelect["locationExternal"], "gibbonDaysOfWeekID"=>$rowSelect["gibbonDaysOfWeekID"], "timeStart"=>$rowSelect["timeStart"], "timeEnd"=>$rowSelect["timeEnd"]); 
									$sqlInsert2="INSERT INTO gibbonActivitySlot SET gibbonActivityID=:gibbonActivityID, gibbonSpaceID=:gibbonSpaceID, locationExternal=:locationExternal, gibbonDaysOfWeekID=:gibbonDaysOfWeekID, timeStart=:timeStart, timeEnd=:timeEnd" ;
									$resultInsert2=$connection2->prepare($sqlInsert2);
									$resultInsert2->execute($dataInsert2);
								}
								catch(PDOException $e) {
									$partialFail=TRUE ;
								}
							}
						}
					}
				}
				
				if ($partialFail==TRUE) {
					//Unknown Error
					$URL.="&return=warning1" ;
					header("Location: {$URL}");
				}
				else {
					//Success 0
					$URL.="&return=success0" ;
					header("Location: {$URL}");
				}
			}
		}
	}
}
?>