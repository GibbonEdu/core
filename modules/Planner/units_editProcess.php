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

$gibbonSchoolYearID=$_GET["gibbonSchoolYearID"] ;
$gibbonCourseID=$_GET["gibbonCourseID"] ;
$gibbonUnitID=$_GET["gibbonUnitID"] ;
$classCount=$_POST["classCount"] ;
$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["address"]) . "/units_edit.php&gibbonUnitID=$gibbonUnitID&gibbonCourseID=$gibbonCourseID&gibbonSchoolYearID=$gibbonSchoolYearID" ;

if (isActionAccessible($guid, $connection2, "/modules/Planner/units_edit.php")==FALSE) {
	//Fail 0
	$URL.="&updateReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	$highestAction=getHighestGroupedAction($guid, $_GET["address"], $connection2) ;
	if ($highestAction==FALSE) {
		//Fail 0
		$URL.="&updateReturn=fail0$params" ;
		header("Location: {$URL}");
	}
	else {
		if (empty($_POST)) {
			$URL.="&updateReturn=fail5" ;
			header("Location: {$URL}");
		}
		else {
			//Proceed!
			//Validate Inputs
			$name=$_POST["name"] ;
			$description=$_POST["description"] ;
			$details=$_POST["details"] ;
			$license=$_POST["license"] ;
			$sharedPublic=NULL ;
			if (isset($_POST["sharedPublic"])) {
				$sharedPublic=$_POST["sharedPublic"] ;
			}
			$embeddable=$_POST["embeddable"] ;
			
			if ($gibbonSchoolYearID=="" OR $gibbonCourseID=="" OR $gibbonUnitID=="" OR $name=="" OR $description=="" OR $embeddable=="") {
				//Fail 3
				$URL.="&updateReturn=fail3" ;
				header("Location: {$URL}");
			}
			else {
				//Check access to specified course
				try {
					if ($highestAction=="Unit Planner_all") {
						$data=array("gibbonSchoolYearID"=>$gibbonSchoolYearID, "gibbonCourseID"=>$gibbonCourseID); 
						$sql="SELECT * FROM gibbonCourse WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseID=:gibbonCourseID" ;
					}
					else if ($highestAction=="Unit Planner_learningAreas") {
						$data=array("gibbonSchoolYearID"=>$gibbonSchoolYearID, "gibbonCourseID"=>$gibbonCourseID, "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
						$sql="SELECT gibbonCourseID, gibbonCourse.name, gibbonCourse.nameShort FROM gibbonCourse JOIN gibbonDepartment ON (gibbonCourse.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE gibbonDepartmentStaff.gibbonPersonID=:gibbonPersonID AND (role='Coordinator' OR role='Assistant Coordinator' OR role='Teacher (Curriculum)') AND gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseID=:gibbonCourseID ORDER BY gibbonCourse.nameShort" ;
					}
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					//Fail 2
					$URL.="&addReturn=fail2" ;
					header("Location: {$URL}");
					break ;
				}
				
				if ($result->rowCount()!=1) {
					//Fail 4
					$URL.="&updateReturn=fail4" ;
					header("Location: {$URL}");
				}
				else {
					//Check existence of specified unit
					try {
						$data=array("gibbonUnitID"=>$gibbonUnitID, "gibbonCourseID"=>$gibbonCourseID); 
						$sql="SELECT * FROM gibbonUnit WHERE gibbonUnitID=:gibbonUnitID AND gibbonCourseID=:gibbonCourseID" ;
						$result=$connection2->prepare($sql);
						$result->execute($data);
					}
					catch(PDOException $e) { 
						//Fail 2
						$URL.="&addReturn=fail2" ;
						header("Location: {$URL}");
						break ;
					}

					if ($result->rowCount()!=1) {
						//Fail 4
						$URL.="&updateReturn=fail4" ;
						header("Location: {$URL}");
					}
					else {
						$row=$result->fetch() ;
					
						//Move attached file, if there is one
						if ($_FILES['file']["tmp_name"]!="") {
							//Move attached file, if there is one
							if ($_FILES['file']["tmp_name"]!="") {
								$time=time() ;
								//Check for folder in uploads based on today's date
								$path=$_SESSION[$guid]["absolutePath"] ; ;
								if (is_dir($path ."/uploads/" . date("Y", $time) . "/" . date("m", $time))==FALSE) {
									mkdir($path ."/uploads/" . date("Y", $time) . "/" . date("m", $time), 0777, TRUE) ;
								}
								$unique=FALSE;
								$count=0 ;
								while ($unique==FALSE AND $count<100) {
									$suffix=randomPassword(16) ;
									$attachment="uploads/" . date("Y", $time) . "/" . date("m", $time) . "/" . preg_replace("/[^a-zA-Z0-9]/", "", $name) . "_$suffix" . strrchr($_FILES["file"]["name"], ".") ;
									if (!(file_exists($path . "/" . $attachment))) {
										$unique=TRUE ;
									}
									$count++ ;
								}
								
								if (!(move_uploaded_file($_FILES["file"]["tmp_name"],$path . "/" . $attachment))) {
									//Fail 5
									$URL.="&addReturn=fail5" ;
									header("Location: {$URL}");
								}
							}
						}
						else {
							$attachment=$row["attachment"] ;
						}
						
						//Update classes
						$partialFail=FALSE ;
						if ($classCount>0) {
							for ($i=0;$i<$classCount;$i++) {
								$running=$_POST["running" . $i] ;
								if ($running!="Y" AND $running!="N") {
									$running="N" ;
								}
								
								//Check to see if entry exists
								try {
									$dataUnitClass=array("gibbonUnitID"=>$gibbonUnitID, "gibbonCourseClassID"=>$_POST["gibbonCourseClassID" . $i]); 
									$sqlUnitClass="SELECT * FROM gibbonUnitClass WHERE gibbonUnitID=:gibbonUnitID AND gibbonCourseClassID=:gibbonCourseClassID" ;
									$resultUnitClass=$connection2->prepare($sqlUnitClass);
									$resultUnitClass->execute($dataUnitClass);
								}
								catch(PDOException $e) { 
									$partialFail=TRUE ;
								}
								
								if ($resultUnitClass->rowCount()>0) {
									try {
										$dataClass=array("running"=>$running, "gibbonUnitID"=>$gibbonUnitID, "gibbonCourseClassID"=>$_POST["gibbonCourseClassID" . $i]); 
										$sqlClass="UPDATE gibbonUnitClass SET running=:running WHERE gibbonUnitID=:gibbonUnitID AND gibbonCourseClassID=:gibbonCourseClassID" ;
										$resultClass=$connection2->prepare($sqlClass);
										$resultClass->execute($dataClass);
									}
									catch(PDOException $e) { 
										$partialFail=TRUE ;
									}
								}
								else {
									try {
										$dataClass=array("running"=>$running, "gibbonUnitID"=>$gibbonUnitID, "gibbonCourseClassID"=>$_POST["gibbonCourseClassID" . $i]); 
										$sqlClass="INSERT INTO gibbonUnitClass SET gibbonUnitID=:gibbonUnitID, gibbonCourseClassID=:gibbonCourseClassID, running=:running" ;
										$resultClass=$connection2->prepare($sqlClass);
										$resultClass->execute($dataClass);
									}
									catch(PDOException $e) { 
										$partialFail=TRUE ;
									}
								}
							}
						}
						
						//Update blocks
						$order="" ;
						if (isset($_POST["order"])) {
							$order=$_POST["order"] ;
						}
						$sequenceNumber=0 ;
						$dataRemove=array() ;
						$whereRemove="" ;
						if (count($order)<0) {
							//Fail 3
							$URL.="&addReturn=fail3" ;
							header("Location: {$URL}");
						}
						else {
							if (is_array($order)) {
								foreach ($order as $i) {
									$title="";
									if ($_POST["title$i"]!="Block $i") {
										$title=$_POST["title$i"] ;
									}
									$type2="";
									if ($_POST["type$i"]!="type (e.g. discussion, outcome)") {
										$type2=$_POST["type$i"];
									}
									$length="";
									if ($_POST["length$i"]!="length (min)") {
										$length=$_POST["length$i"];
									}
									$contents=$_POST["contents$i"];
									$teachersNotes=$_POST["teachersNotes$i"];
									$gibbonUnitBlockID=$_POST["gibbonUnitBlockID$i"];
									
									//Deal with outcomes
									$gibbonOutcomeIDList="" ;
									if (isset($_POST["outcomes" . $i])) {
										if (is_array($_POST["outcomes" . $i])) {
											foreach ($_POST["outcomes" . $i] AS $outcome) {
												$gibbonOutcomeIDList.=$outcome . "," ;
											}
										}
										$gibbonOutcomeIDList=substr($gibbonOutcomeIDList, 0, -1) ;
									}
									
									if ($gibbonUnitBlockID!="") {
										try {
											$dataBlock=array("gibbonUnitID"=>$gibbonUnitID, "title"=>$title, "type"=>$type2, "length"=>$length, "contents"=>$contents, "teachersNotes"=>$teachersNotes, "sequenceNumber"=>$sequenceNumber, "gibbonOutcomeIDList"=>$gibbonOutcomeIDList, "gibbonUnitBlockID"=>$gibbonUnitBlockID); 
											$sqlBlock="UPDATE gibbonUnitBlock SET gibbonUnitID=:gibbonUnitID, title=:title, type=:type, length=:length, contents=:contents, teachersNotes=:teachersNotes, sequenceNumber=:sequenceNumber, gibbonOutcomeIDList=:gibbonOutcomeIDList WHERE gibbonUnitBlockID=:gibbonUnitBlockID" ;
											$resultBlock=$connection2->prepare($sqlBlock);
											$resultBlock->execute($dataBlock);
										}
										catch(PDOException $e) { 
											$partialFail=TRUE ;
										}
										$dataRemove["gibbonUnitBlockID$sequenceNumber"]=$gibbonUnitBlockID ;
										$whereRemove.="AND NOT gibbonUnitBlockID=:gibbonUnitBlockID$sequenceNumber " ;
									}
									else {
										try {
											$dataBlock=array("gibbonUnitID"=>$gibbonUnitID, "title"=>$title, "type"=>$type2, "length"=>$length, "contents"=>$contents, "teachersNotes"=>$teachersNotes, "sequenceNumber"=>$sequenceNumber); 
											$sqlBlock="INSERT INTO gibbonUnitBlock SET gibbonUnitID=:gibbonUnitID, title=:title, type=:type, length=:length, contents=:contents, teachersNotes=:teachersNotes, sequenceNumber=:sequenceNumber" ;
											$resultBlock=$connection2->prepare($sqlBlock);
											$resultBlock->execute($dataBlock);
										}
										catch(PDOException $e) {
											print $e->getMessage() ; 
											$partialFail=TRUE ;
										}
										$dataRemove["gibbonUnitBlockID$sequenceNumber"]=$connection2->lastInsertId() ;
										$whereRemove.="AND NOT gibbonUnitBlockID=:gibbonUnitBlockID$sequenceNumber " ;
									}
									
									$sequenceNumber++ ;
								}
							}
						}
						
						//Remove orphaned blocks
						if ($whereRemove!="(") {
							try {
								$dataRemove["gibbonUnitID"]=$gibbonUnitID ; 
								$sqlRemove="DELETE FROM gibbonUnitBlock WHERE gibbonUnitID=:gibbonUnitID $whereRemove" ;
								$resultRemove=$connection2->prepare($sqlRemove);
								$resultRemove->execute($dataRemove);
							}
							catch(PDOException $e) { 
								print $e->getMessage() ;
								$partialFail=TRUE ;
							}
						}
						
						//Delete all outcomes
						try {
							$dataDelete=array("gibbonUnitID"=>$gibbonUnitID);  
							$sqlDelete="DELETE FROM gibbonUnitOutcome WHERE gibbonUnitID=:gibbonUnitID" ;
							$resultDelete=$connection2->prepare($sqlDelete);
							$resultDelete->execute($dataDelete);  
						}
						catch(PDOException $e) { 
							//Fail2
							$URL.="&updateReturn=fail2" ;
							header("Location: {$URL}");
							break ;
						}
						//Insert outcomes
						$count=0 ;
						if (isset($_POST["outcomeorder"])) {
							if (count($_POST["outcomeorder"])>0) {
								foreach ($_POST["outcomeorder"] AS $outcome) {
									if ($_POST["outcomegibbonOutcomeID$outcome"]!="") {
										try {
											$dataInsert=array("gibbonUnitID"=>$gibbonUnitID, "gibbonOutcomeID"=>$_POST["outcomegibbonOutcomeID$outcome"], "content"=>$_POST["outcomecontents$outcome"], "count"=>$count);  
											$sqlInsert="INSERT INTO gibbonUnitOutcome SET gibbonUnitID=:gibbonUnitID, gibbonOutcomeID=:gibbonOutcomeID, content=:content, sequenceNumber=:count" ;
											$resultInsert=$connection2->prepare($sqlInsert);
											$resultInsert->execute($dataInsert);
										}
										catch(PDOException $e) {
											print $e ;
											$partialFail=true ;
										}
									}
									$count++ ;
								}	
							}
						}
					
						//Write to database
						try {
							$data=array("name"=>$name, "attachment"=>$attachment, "description"=>$description, "details"=>$details, "license"=>$license, "sharedPublic"=>$sharedPublic, "embeddable"=>$embeddable, "gibbonPersonIDLastEdit"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonUnitID"=>$gibbonUnitID); 
							$sql="UPDATE gibbonUnit SET name=:name, attachment=:attachment, description=:description, details=:details, license=:license, sharedPublic=:sharedPublic, embeddable=:embeddable, gibbonPersonIDLastEdit=:gibbonPersonIDLastEdit WHERE gibbonUnitID=:gibbonUnitID" ;
							$result=$connection2->prepare($sql);
							$result->execute($data);
						}
						catch(PDOException $e) { 
							//Fail 2
							$URL.="&updateReturn=fail2" ;
							header("Location: {$URL}");
							break ;
						}

						if ($partialFail) {
							//Fail 6
							$URL.="&updateReturn=fail6" ;
							header("Location: {$URL}");
						}
						else {
							//Success 0
							$URL.="&updateReturn=success0" ;
							header("Location: {$URL}");
						}
					}
				}
			}
		}
	}
}
?>