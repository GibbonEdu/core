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
$mode=$_POST["mode"] ;
$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Planner/planner_view_full.php&gibbonPlannerEntryID=$gibbonPlannerEntryID" . $_POST["params"] ;
							
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
		if ($gibbonPlannerEntryID=="" OR $mode=="" OR ($mode!="view" AND $mode!="edit")) {
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
				$row=$result->fetch() ;
				
				//CHECK IF UNIT IS GIBBON OR HOOKED
				if ($row["gibbonHookID"]==NULL) {
					$hooked=FALSE ;
					$gibbonUnitID=$row["gibbonUnitID"]; 
				}
				else {
					$hooked=TRUE ;
					$gibbonUnitIDToken=$row["gibbonUnitID"]; 
					$gibbonHookIDToken=$row["gibbonHookID"]; 
					
					try {
						$dataHooks=array("gibbonHookID"=>$gibbonHookIDToken); 
						$sqlHooks="SELECT * FROM gibbonHook WHERE type='Unit' AND gibbonHookID=:gibbonHookID ORDER BY name" ;
						$resultHooks=$connection2->prepare($sqlHooks);
						$resultHooks->execute($dataHooks);
					}
					catch(PDOException $e) { }
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
							catch(PDOException $e) { }									
						}
					}
				}
			
				$partialFail=false ;
				if ($mode=="view") {
					$ids=$_POST["gibbonUnitClassBlockID"] ;
					for ($i=0; $i<count($ids); $i++) {
						if ($ids[$i]=="" ) {
							$partialFail=true ;
						}
						else {
							$complete="N" ;
							if (isset($_POST["complete$i"])) {
								if ($_POST["complete$i"]=="on") {
									$complete="Y" ;
								}
							}
							//Write to database
							try {
								if ($hooked==FALSE) {
									$data=array("complete"=>$complete, "gibbonUnitClassBlockID"=>$ids[$i]); 
									$sql="UPDATE gibbonUnitClassBlock SET complete=:complete WHERE gibbonUnitClassBlockID=:gibbonUnitClassBlockID" ;
								}
								else {
									$data=array("complete"=>$complete, "gibbonUnitClassBlockID"=>$ids[$i]); 
									$sql="UPDATE " . $hookOptions["classSmartBlockTable"] . " SET complete=:complete WHERE " . $hookOptions["classSmartBlockIDField"] . "=:gibbonUnitClassBlockID" ;
								}
								$result=$connection2->prepare($sql);
								$result->execute($data);
							}
							catch(PDOException $e) {
								print "Here" ;
								print $e->getMessage() ; 
								$partialFail=true ;
							}
						}
					}
				}
				else {
					$order=$_POST["order"] ;
					$seq=$_POST["minSeq"] ;
					
					foreach ($order as $i) {
						$id=$_POST["gibbonUnitClassBlockID$i"] ;
						$title=$_POST["title$i"] ;
						$type=$_POST["type$i"] ;
						$length=$_POST["length$i"] ;
						$contents=$_POST["contents$i"] ;
						$teachersNotes=$_POST["teachersNotes$i"] ;
						$complete="N" ;
						if ($_POST["complete$i"]=="on") {
							$complete="Y" ;
						}
						
						//Write to database
						try {
							if ($hooked==FALSE) {
								$data=array("title"=>$title, "type"=>$type, "length"=>$length, "contents"=>$contents, "teachersNotes"=>$teachersNotes, "complete"=>$complete, "sequenceNumber"=>$seq, "gibbonUnitClassBlockID"=>$id); 
								$sql="UPDATE gibbonUnitClassBlock SET title=:title, type=:type, length=:length, contents=:contents, teachersNotes=:teachersNotes, complete=:complete, sequenceNumber=:sequenceNumber WHERE gibbonUnitClassBlockID=:gibbonUnitClassBlockID" ;
							}
							else {
								$data=array("title"=>$title, "type"=>$type, "length"=>$length, "contents"=>$contents, "teachersNotes"=>$teachersNotes, "complete"=>$complete, "sequenceNumber"=>$seq, "gibbonUnitClassBlockID"=>$id); 
								$sql="UPDATE " . $hookOptions["classSmartBlockTable"] . " SET " . $hookOptions["classSmartBlockTitleField"] . "=:title, " . $hookOptions["classSmartBlockTypeField"] . "=:type, " . $hookOptions["classSmartBlockLengthField"] . "=:length, " . $hookOptions["classSmartBlockContentsField"] . "=:contents, " . $hookOptions["classSmartBlockTeachersNotesField"] . "=:teachersNotes, " . $hookOptions["classSmartBlockCompleteField"] . "=:complete, " . $hookOptions["classSmartBlockSequenceNumberField"] . "=:sequenceNumber WHERE " . $hookOptions["classSmartBlockIDField"] . "=:gibbonUnitClassBlockID" ;
							}
							$result=$connection2->prepare($sql);
							$result->execute($data);
						}
						catch(PDOException $e) { 
							$partialFail=true ;
						}
						$seq++ ;
					}
				}
				
				//Return final verdict
				if ($partialFail==true) {
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
?>