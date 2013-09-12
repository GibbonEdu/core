<?
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

session_start() ;

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]["timezone"]);

$gibbonSchoolYearID=$_GET["gibbonSchoolYearID"] ;
$gibbonCourseID=$_GET["gibbonCourseID"] ;
$classCount=$_POST["classCount"] ;
$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["address"]) . "/units_add.php&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonCourseID=$gibbonCourseID" ;
$URLSuccess=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["address"]) . "/units_edit.php&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonCourseID=$gibbonCourseID" ;

if (isActionAccessible($guid, $connection2, "/modules/Planner/units_add.php")==FALSE) {
	//Fail 0
	$URL = $URL . "&addReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	$highestAction=getHighestGroupedAction($guid, $_GET["address"], $connection2) ;
	if ($highestAction==FALSE) {
		//Fail 0
		$URL = $URL . "&updateReturn=fail0$params" ;
		header("Location: {$URL}");
	}
	else {
		if (!(isset($_POST))) {
			//Fail 5
			$URL = $URL . "&addReturn=fail5" ;
			header("Location: {$URL}");
		}
		else {
			//Proceed!
			//Validate Inputs
			$name=$_POST["name"] ;
			$description=$_POST["description"] ;
			$details=$_POST["details"] ;
			
			if ($gibbonSchoolYearID=="" OR $gibbonCourseID=="" OR $name=="" OR $description=="") {
				//Fail 3
				$URL = $URL . "&addReturn=fail3" ;
				header("Location: {$URL}");
			}
			else {
				//Check access to specified course
				try {
					if ($highestAction=="Manage Units_all") {
						$data=array("gibbonSchoolYearID"=>$gibbonSchoolYearID, "gibbonCourseID"=>$gibbonCourseID); 
						$sql="SELECT * FROM gibbonCourse WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseID=:gibbonCourseID" ;
					}
					else if ($highestAction=="Manage Units_learningAreas") {
						$data=array("gibbonSchoolYearID"=>$gibbonSchoolYearID, "gibbonCourseID"=>$gibbonCourseID, "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
						$sql="SELECT gibbonCourseID, gibbonCourse.name, gibbonCourse.nameShort FROM gibbonCourse JOIN gibbonDepartment ON (gibbonCourse.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE gibbonDepartmentStaff.gibbonPersonID=:gibbonPersonID AND (role='Coordinator' OR role='Assistant Coordinator' OR role='Teacher (Curriculum)') AND gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseID=:gibbonCourseID ORDER BY gibbonCourse.nameShort" ;
					}
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					//Fail 2
					$URL = $URL . "&addReturn=fail2" . $e->getMessage() ;
					header("Location: {$URL}");
					break ;
				}
				
				if ($result->rowCount()!=1) {
					//Fail 4
					$URL = $URL . "&addReturn=fail4" ;
					header("Location: {$URL}");
				}
				else {
					//Lock markbook column table
					try {
						$sql="LOCK TABLES gibbonUnit WRITE, gibbonUnitClass WRITE, gibbonUnitBlock WRITE,  gibbonUnitOutcome WRITE" ;
						$result=$connection2->query($sql);   
					}
					catch(PDOException $e) { 
						//Fail 2
						$URL = $URL . "&addReturn=fail2" ;
						header("Location: {$URL}");
						break ;
					}	
						
					//Get next autoincrement
					try {
						$sqlAI="SHOW TABLE STATUS LIKE 'gibbonUnit'";
						$resultAI=$connection2->query($sqlAI);   
					}
					catch(PDOException $e) { 
						//Fail 2
						$URL = $URL . "&addReturn=fail2" ;
						header("Location: {$URL}");
						break ;
					}		

					$rowAI=$resultAI->fetch();
					$AI=str_pad($rowAI['Auto_increment'], 10, "0", STR_PAD_LEFT) ;
					
					//Move attached file, if there is one
					if ($_FILES['file']["tmp_name"]!="") {
						//Move attached file, if there is one
						if ($_FILES['file']["tmp_name"]!="") {
							$time=mktime() ;
							//Check for folder in uploads based on today's date
							$path=$_SESSION[$guid]["absolutePath"] ; ;
							if (is_dir($path ."/uploads/" . date("Y", $time) . "/" . date("m", $time))==FALSE) {
								mkdir($path ."/uploads/" . date("Y", $time) . "/" . date("m", $time), 0777, TRUE) ;
							}
							$unique=FALSE;
							while ($unique==FALSE) {
								$suffix=randomPassword(16) ;
								$attachment="uploads/" . date("Y", $time) . "/" . date("m", $time) . "/" . preg_replace("/[^a-zA-Z0-9\s]/", "", $name) . "_$suffix" . strrchr($_FILES["file"]["name"], ".") ;
								if (!(file_exists($path . "/" . $attachment))) {
									$unique=TRUE ;
								}
							}
							
							if (!(move_uploaded_file($_FILES["file"]["tmp_name"],$path . "/" . $attachment))) {
								//Fail 5
								$URL = $URL . "&addReturn=fail5" ;
								header("Location: {$URL}");
							}
						}
					}
					else {
						$attachment="" ;
					}
					
					//ADD CLASS RECORDS
					$partialFail=FALSE ;
						if ($classCount>0) {
							for ($i=0;$i<$classCount;$i++) {
								$running=$_POST["running" . $i] ;
								if ($running!="Y" AND $running!="N") {
									$running="N" ;
								}
								
								try {
									$dataClass=array("gibbonUnitID"=>$AI, "gibbonCourseClassID"=>$_POST["gibbonCourseClassID" . $i], "running"=>$running); 
									$sqlClass="INSERT INTO gibbonUnitClass SET gibbonUnitID=:gibbonUnitID, gibbonCourseClassID=:gibbonCourseClassID, running=:running" ;
									$resultClass=$connection2->prepare($sqlClass);
									$resultClass->execute($dataClass);
								}
								catch(PDOException $e) { 
									$partialFail=TRUE ;
								}
							}
						}
					
					//ADD BLOCKS IF SMART
					$blockCount=($_POST["blockCount"]-1) ;
					$sequenceNumber=0 ;
					if ($blockCount>0) {
						$order=$_POST["order"] ;
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
									
							if ($title!="" OR $contents!="") {
								try {
									$dataBlock=array("gibbonUnitID"=>$AI, "title"=>$title, "type"=>$type2, "length"=>$length, "contents"=>$contents, "teachersNotes"=>$teachersNotes, "sequenceNumber"=>$sequenceNumber); 
									$sqlBlock="INSERT INTO gibbonUnitBlock SET gibbonUnitID=:gibbonUnitID, title=:title, type=:type, length=:length, contents=:contents, teachersNotes=:teachersNotes, sequenceNumber=:sequenceNumber" ;
									$resultBlock=$connection2->prepare($sqlBlock);
									$resultBlock->execute($dataBlock);
								}
								catch(PDOException $e) { 
									$partialFail=TRUE ;
								}
								$sequenceNumber++ ;
							}
						}
					}
					
					//Insert outcomes
					$count=0 ;
					if (count($_POST["outcomeorder"])>0) {
						foreach ($_POST["outcomeorder"] AS $outcome) {
							if ($_POST["outcomegibbonOutcomeID$outcome"]!="") {
								try {
									$dataInsert=array("AI"=>$AI, "gibbonOutcomeID"=>$_POST["outcomegibbonOutcomeID$outcome"], "content"=>$_POST["outcomecontents$outcome"], "count"=>$count);  
									$sqlInsert="INSERT INTO gibbonUnitOutcome SET gibbonUnitID=:AI, gibbonOutcomeID=:gibbonOutcomeID, content=:content, sequenceNumber=:count" ;
									$resultInsert=$connection2->prepare($sqlInsert);
									$resultInsert->execute($dataInsert);
								}
								catch(PDOException $e) {
									$partialFail=true ;
								}
							}
							$count++ ;
						}	
					}
					
					
					//Write to database
					try {
						$data=array("gibbonCourseID"=>$gibbonCourseID, "name"=>$name, "description"=>$description, "attachment"=>$attachment, "details"=>$details, "gibbonPersonIDCreator"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonPersonIDLastEdit"=>$_SESSION[$guid]["gibbonPersonID"], ); 
						$sql="INSERT INTO gibbonUnit SET gibbonCourseID=:gibbonCourseID, name=:name, description=:description, attachment=:attachment, details=:details, gibbonPersonIDCreator=:gibbonPersonIDCreator, gibbonPersonIDLastEdit=:gibbonPersonIDLastEdit" ;
						$result=$connection2->prepare($sql);
						$result->execute($data);
					}
					catch(PDOException $e) { 
						//Fail 2
						$URL = $URL . "&addReturn=fail2" ;
						header("Location: {$URL}");
						break ;
					}
			
					//Unlock module table
					try {
						$sql="UNLOCK TABLES" ;
						$result=$connection2->query($sql);   
					}
					catch(PDOException $e) { }		
					
					if ($partialFail==TRUE) {
						//Fail 6
						$URL = $URL . "&addReturn=fail6" ;
						header("Location: {$URL}");
					}
					else {
						//Success 0
						$URLSuccess = $URLSuccess . "&addReturn=success0&gibbonUnitID=$AI" ;
						header("Location: {$URLSuccess}") ;
					}
				}
			}
		}
	}
}
?>