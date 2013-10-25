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

@session_start() ;

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]["timezone"]);

$gibbonSchoolYearID=$_GET["gibbonSchoolYearID"];
$gibbonCourseID=$_GET["gibbonCourseID"]; 
$gibbonCourseClassID=$_GET["gibbonCourseClassID"]; 
$gibbonUnitID=$_GET["gibbonUnitID"]; 
$gibbonUnitClassID=$_GET["gibbonUnitClassID"]; 
$orders=$_POST["order"] ;

//IF UNIT DOES NOT CONTAIN HYPHEN, IT IS A GIBBON UNIT
$gibbonUnitID=$_GET["gibbonUnitID"]; 
if (strpos($gibbonUnitID,"-")==FALSE) {
	$hooked=FALSE ;
}
else {
	$hooked=TRUE ;
	$gibbonHookIDToken=substr($gibbonUnitID,11) ;
	$gibbonUnitIDToken=substr($gibbonUnitID,0,10) ;
}

$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["address"]) . "/units_edit.php&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonCourseID=$gibbonCourseID&gibbonUnitID=$gibbonUnitID" ;

if (isActionAccessible($guid, $connection2, "/modules/Planner/units_edit_deploy.php")==FALSE) {
	//Fail 0
	$URL=$URL . "&deployReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	$highestAction=getHighestGroupedAction($guid, $_GET["address"], $connection2) ;
	if ($highestAction==FALSE) {
		//Fail 0
		$URL=$URL . "&deployReturn=fail0$params" ;
		header("Location: {$URL}");
	}
	else {
		//Proceed!
		//Validate Inputs
		if ($gibbonSchoolYearID=="" OR $gibbonCourseID=="" OR $gibbonUnitID=="" OR $gibbonUnitClassID=="" OR $orders=="") {
			//Fail 3
			$URL=$URL . "&deployReturn=fail3" ;
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
				$URL=$URL . "&deployReturn=fail2a" ;
				header("Location: {$URL}");
				break ;
			}
			
			if ($result->rowCount()!=1) {
				//Fail 4
				$URL=$URL . "&deployReturn=fail4" ;
				header("Location: {$URL}");
			}
			else {
				//Check existence of specified unit
				if ($hooked==FALSE) {
					try {
						$data=array("gibbonUnitID"=>$gibbonUnitID, "gibbonCourseID"=>$gibbonCourseID); 
						$sql="SELECT gibbonCourse.nameShort AS courseName, gibbonUnit.* FROM gibbonUnit JOIN gibbonCourse ON (gibbonUnit.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonUnitID=:gibbonUnitID AND gibbonUnit.gibbonCourseID=:gibbonCourseID" ;
						$result=$connection2->prepare($sql);
						$result->execute($data);
					}
					catch(PDOException $e) { 
						//Fail 2
						$URL=$URL . "&deployReturn=fail2b" ;
						header("Location: {$URL}");
						break ;
					}
				}
				else {
					try {
						$dataHooks=array("gibbonHookID"=>$gibbonHookIDToken); 
						$sqlHooks="SELECT * FROM gibbonHook WHERE type='Unit' AND gibbonHookID=:gibbonHookID ORDER BY name" ;
						$resultHooks=$connection2->prepare($sqlHooks);
						$resultHooks->execute($dataHooks);
					}
					catch(PDOException $e) { 
						//Fail 2
						$URL=$URL . "&deployReturn=fail2c" ;
						header("Location: {$URL}");
						break ;
					}
					if ($resultHooks->rowCount()==1) {
						$rowHooks=$resultHooks->fetch() ;
						$hookOptions=unserialize($rowHooks["options"]) ;
						if ($hookOptions["unitTable"]!="" AND $hookOptions["unitIDField"]!="" AND $hookOptions["unitCourseIDField"]!="" AND $hookOptions["unitNameField"]!="" AND $hookOptions["unitDescriptionField"]!="" AND $hookOptions["classLinkTable"]!="" AND $hookOptions["classLinkJoinFieldUnit"]!="" AND $hookOptions["classLinkJoinFieldClass"]!="" AND $hookOptions["classLinkIDField"]!="") {
							try {
								$data=array("unitIDField"=>$gibbonUnitIDToken); 
								$sql="SELECT " . $hookOptions["unitTable"] . ".*, gibbonCourse.nameShort FROM " . $hookOptions["unitTable"] . " JOIN gibbonCourse ON (" . $hookOptions["unitTable"] . "." . $hookOptions["unitCourseIDField"] . "=gibbonCourse.gibbonCourseID) WHERE " . $hookOptions["unitIDField"] . "=:unitIDField" ;
								$result=$connection2->prepare($sql);
								$result->execute($data);
							}
							catch(PDOException $e) { 
								//Fail 2
								$URL=$URL . "&deployReturn=fail2d" ;
								header("Location: {$URL}");
								break ;
							}									
						}
					}
				}

				if ($result->rowCount()!=1) {
					//Fail 4
					$URL=$URL . "&deployReturn=fail4" ;
					header("Location: {$URL}");
				}
				else {
					$row=$result->fetch() ;
					
					$partialFail=false;
					
					//CREATE LESSON PLANS
					try {
						if ($hooked==FALSE) {
							$sql="LOCK TABLES gibbonPlannerEntry WRITE, gibbonUnitClassBlock WRITE" ;
						}
						else {
							$sql="LOCK TABLES gibbonPlannerEntry WRITE, gibbonUnitClassBlock WRITE, " . $hookOptions["classSmartBlockTable"] . " WRITE" ;
						}
						$result=$connection2->query($sql);   
					}
					catch(PDOException $e) { 
						//Fail 2
						$URL=$URL . "&deployReturn=fail2e" ;
						header("Location: {$URL}");
						break ; 
					}	
					
					//Get next autoincrement
					try {
						$sqlAI="SHOW TABLE STATUS LIKE 'gibbonPlannerEntry'";
						$resultAI=$connection2->query($sqlAI);   
					}
					catch(PDOException $e) { 
						//Fail 2
						$URL=$URL . "&deployReturn=fail2f" ;
						header("Location: {$URL}");
						break ;
					}	
					
					$rowAI=$resultAI->fetch();
					$AI=str_pad($rowAI['Auto_increment'], 14, "0", STR_PAD_LEFT) ;
					
					$lessonCount=0 ;
					$sequenceNumber=0 ;
					foreach ($orders AS $order) {
						//It is a lesson, so add it
						if (strpos($order, "lessonHeader-")!==FALSE) {
							if ($lessonCount!=0) {
								$AI++ ;
								$AI=str_pad($AI, 14, "0", STR_PAD_LEFT) ;
							}
							$summary="Part of the " . $row["name"] . " unit." ;
							$teachersNotes=getSettingByScope($connection2, "Planner", "teachersNotesTemplate") ;
							$viewableStudents=$_POST["viewableStudents"] ;
							$viewableParents=$_POST["viewableParents"] ;
							
							try {
								if ($hooked==FALSE) {
									$data=array("gibbonPlannerEntryID"=>$AI, "gibbonCourseClassID"=>$gibbonCourseClassID, "date"=>$_POST["date$lessonCount"], "timeStart"=>$_POST["timeStart$lessonCount"], "timeEnd"=>$_POST["timeEnd$lessonCount"], "gibbonUnitID"=>$gibbonUnitID, "name"=>$row["name"] . " " . ($lessonCount+1), "summary"=>$summary, "teachersNotes"=>$teachersNotes, "viewableParents"=>$viewableParents, "viewableStudents"=>$viewableStudents, "gibbonPersonIDCreator"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonPersonIDLastEdit"=>$_SESSION[$guid]["gibbonPersonID"]); 
									$sql="INSERT INTO gibbonPlannerEntry SET gibbonPlannerEntryID=:gibbonPlannerEntryID, gibbonCourseClassID=:gibbonCourseClassID, date=:date, timeStart=:timeStart, timeEnd=:timeEnd, gibbonUnitID=:gibbonUnitID, name=:name, summary=:summary, description='', teachersNotes=:teachersNotes, homework='N', viewableParents=:viewableParents, viewableStudents=:viewableStudents, gibbonPersonIDCreator=:gibbonPersonIDCreator, gibbonPersonIDLastEdit=:gibbonPersonIDLastEdit" ;
								}
								else {
									$data=array("gibbonPlannerEntryID"=>$AI, "gibbonCourseClassID"=>$gibbonCourseClassID, "date"=>$_POST["date$lessonCount"], "timeStart"=>$_POST["timeStart$lessonCount"], "timeEnd"=>$_POST["timeEnd$lessonCount"], "gibbonUnitID"=>$gibbonUnitIDToken, "gibbonHookID"=>$gibbonHookIDToken, "name"=>$row["name"] . " " . ($lessonCount+1), "summary"=>$summary, "teachersNotes"=>$teachersNotes, "viewableParents"=>$viewableParents, "viewableStudents"=>$viewableStudents, "gibbonPersonIDCreator"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonPersonIDLastEdit"=>$_SESSION[$guid]["gibbonPersonID"]); 
									$sql="INSERT INTO gibbonPlannerEntry SET gibbonPlannerEntryID=:gibbonPlannerEntryID, gibbonCourseClassID=:gibbonCourseClassID, date=:date, timeStart=:timeStart, timeEnd=:timeEnd, gibbonUnitID=:gibbonUnitID, gibbonHookID=:gibbonHookID, name=:name, summary=:summary, description='', teachersNotes=:teachersNotes, homework='N', viewableParents=:viewableParents, viewableStudents=:viewableStudents, gibbonPersonIDCreator=:gibbonPersonIDCreator, gibbonPersonIDLastEdit=:gibbonPersonIDLastEdit" ;
								}
								$result=$connection2->prepare($sql);
								$result->execute($data);
							}
							catch(PDOException $e) {
								$e->getMessage() ;
								$partialFail=true;
							}
							$lessonCount++ ;
						}
						//It is a block, so add it to the last added lesson
						else {
							$titles=$_POST["title" . $order] ;
							$types=$_POST["type" . $order] ;
							$lengths=$_POST["length" . $order] ;
							$contents=$_POST["contents" . $order] ;
							$teachersNotes=$_POST["teachersNotes" . $order] ;
							$gibbonUnitBlockID=$_POST["gibbonUnitBlockID" . $order] ;
							
							try {
								if ($hooked==FALSE) {
									$data=array("gibbonUnitClassID"=>$gibbonUnitClassID, "gibbonPlannerEntryID"=>$AI, "gibbonUnitBlockID"=>$gibbonUnitBlockID, "title"=>$titles, "type"=>$types, "length"=>$lengths, "contents"=>$contents, "teachersNotes"=>$teachersNotes, "sequenceNumber"=>$sequenceNumber); 
									$sql="INSERT INTO gibbonUnitClassBlock SET gibbonUnitClassID=:gibbonUnitClassID, gibbonPlannerEntryID=:gibbonPlannerEntryID, gibbonUnitBlockID=:gibbonUnitBlockID, title=:title, type=:type, length=:length, contents=:contents, teachersNotes=:teachersNotes, sequenceNumber=:sequenceNumber, complete='N'" ;
								}
								else {
									$data=array("gibbonUnitClassID"=>$gibbonUnitClassID, "gibbonPlannerEntryID"=>$AI, "gibbonUnitBlockID"=>$gibbonUnitBlockID, "title"=>$titles, "type"=>$types, "length"=>$lengths, "contents"=>$contents, "teachersNotes"=>$teachersNotes, "sequenceNumber"=>$sequenceNumber); 
									$sql="INSERT INTO " . $hookOptions["classSmartBlockTable"] . " SET " . $hookOptions["classSmartBlockJoinField"] . "=:gibbonUnitClassID, " . $hookOptions["classSmartBlockPlannerJoin"] . "=:gibbonPlannerEntryID, " . $hookOptions["classSmartBlockUnitBlockJoinField"] . "=:gibbonUnitBlockID, " . $hookOptions["classSmartBlockTitleField"] . "=:title, " . $hookOptions["classSmartBlockTypeField"] . "=:type, " . $hookOptions["classSmartBlockLengthField"] . "=:length, " . $hookOptions["classSmartBlockContentsField"] . "=:contents, " . $hookOptions["classSmartBlockTeachersNotesField"] . "=:teachersNotes, " . $hookOptions["classSmartBlockSequenceNumberField"] . "=:sequenceNumber, complete='N'" ;
								}
								$result=$connection2->prepare($sql);
								$result->execute($data);
							}
							catch(PDOException $e) { 
								print $e->getMessage() ;
								$partialFail=true;
							}
							$sequenceNumber++ ;
						}
					}

					//RETURN
					if ($partialFail==TRUE) {
						//Fail 6
						$URL=$URL . "&deployReturn=fail6" ;
						header("Location: {$URL}");
					}
					else {
						$URL=$URL . "&deployReturn=success0" ;
						header("Location: {$URL}") ;
					}
				}
			}
		}
	}
}
?>