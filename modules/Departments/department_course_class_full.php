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

@session_start() ;

//Module includes
include "./modules/" . $_SESSION[$guid]["module"] . "/moduleFunctions.php" ;

if (isActionAccessible($guid, $connection2, "/modules/Departments/department_course_class_full.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "You do not have access to this page." ;
	print "</div>" ;
}
else {
	//Get action with highest precendence
	$highestAction=getHighestGroupedAction($guid, $_GET["q"], $connection2) ;
	if ($highestAction==FALSE) {
		print "<div class='error'>" ;
		print "The highest grouped action cannot be determined." ;
		print "</div>" ;
	}
	else {
		//Proceed!
		//Get class variable
		$gibbonPlannerEntryID=$_GET["gibbonPlannerEntryID"] ;
		if ($gibbonPlannerEntryID=="") {
			print "<div class='warning'>" ;
				print "Lesson has not been specified ." ;
			print "</div>" ;
		}
		//Check existence of and access to this class.
		else {
			try {
				$data=array("gibbonPlannerEntryID"=>$gibbonPlannerEntryID); 
				$sql="SELECT gibbonPlannerEntryID, gibbonUnitID, gibbonHookID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, timeStart, timeEnd, viewableStudents, viewableParents, homework, homeworkDueDateTime, homeworkDetails, date, summary, gibbonPlannerEntry.description FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE viewableStudents='Y' AND viewableParents='Y' AND gibbonPlannerEntry.gibbonPlannerEntryID=:gibbonPlannerEntryID ORDER BY date, timeStart" ; 
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}

			if ($result->rowCount()!=1) {
				print "<div class='warning'>" ;
					print _("The selected record does not exist, or you do not have access to it.") ;
				print "</div>" ;
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
				
				print "<h1>" ;
					print $row["name"] . "<br/>" ;
					if ($row["gibbonUnitID"]!="") {
						try {
							$dataUnit=array("gibbonUnitID"=>$row["gibbonUnitID"]); 
							$sqlUnit="SELECT * FROM gibbonUnit WHERE gibbonUnitID=:gibbonUnitID" ;
							$resultUnit=$connection2->prepare($sqlUnit);
							$resultUnit->execute($dataUnit);
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						
						if ($resultUnit->rowCount()==1) {
							$rowUnit=$resultUnit->fetch() ;
							print "<div style='font-size: 55%; margin-top: 10px'>Unit: " . $rowUnit["name"] . "</div>" ;
						}
					}
				print "</h1>" ;
				
				print "<table class='blank' cellspacing='0' style='width: 550px; float: left;'>" ;
					print "<tr>" ;
						print "<td style='width: 33%; vertical-align: top'>" ;
							print "<span style='font-size: 115%; font-weight: bold'>Class</span><br/>" ;
							print $row["course"] . "." . $row["class"] ;
						print "</td>" ;
						print "<td style='width: 33%; vertical-align: top'>" ;
							print "<span style='font-size: 115%; font-weight: bold'>Date</span><br/>" ;
							print dateConvertBack($guid, $row["date"]) ;
						print "</td>" ;
						print "<td style='width: 33%; vertical-align: top'>" ;
							print "<span style='font-size: 115%; font-weight: bold'>Time</span><br/>" ;
							print substr($row["timeStart"],0,5) . "-" . substr($row["timeEnd"],0,5) ;
						print "</td>" ;
					print "</tr>" ;
					print "<tr>" ;
						print "<td style='padding-top: 15px; width: 33%; vertical-align: top' colspan=3>" ;
							print "<span style='font-size: 115%; font-weight: bold'>Summary</span><br/>" ;
							print $row["summary"] ;
						print "</td>" ;
					print "</tr>" ;
					
					
					
					if ($row["description"]!="") {
						print "<tr>" ;
							print "<td style='text-align: justify; padding-top: 5px; width: 33%; vertical-align: top' colspan=3>" ;
								print "<h2>Lesson Details</h2>" ;
								print $row["description"] ;
							print "</td>" ;
						print "</tr>" ;
					}
					try {
						if ($hooked==FALSE) {
							$dataBlocks=array("gibbonPlannerEntryID"=>$gibbonPlannerEntryID); 
							$sqlBlocks="SELECT * FROM gibbonUnitClassBlock WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID ORDER BY sequenceNumber" ;
						}
						else {
							$dataBlocks=array("gibbonPlannerEntryID"=>$gibbonPlannerEntryID); 
							$sqlBlocks="SELECT * FROM " . $hookOptions["classSmartBlockTable"] . " WHERE " . $hookOptions["classSmartBlockPlannerJoin"] . "=:gibbonPlannerEntryID ORDER BY sequenceNumber" ;
						}
						$resultBlocks=$connection2->prepare($sqlBlocks);
						$resultBlocks->execute($dataBlocks);
						$resultBlocksView=$connection2->prepare($sqlBlocks);
						$resultBlocksView->execute($dataBlocks);
					}
					catch(PDOException $e) { 
						print "<div class='error'>" . $e->getMessage() . "</div>" ; 
					}

					print "<tr>" ;
						print "<td style='text-align: justify; padding-top: 5px; width: 33%; vertical-align: top' colspan=3>" ;
							print "<h2>Lesson Details</h2>" ;
							if ($resultBlocks->rowCount()>0) {
								print "<div id='smartView'>" ;
									print "<form method='post' action='" . $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/planner_view_full_smartProcess.php'>" ;
										while ($rowBlocksView=$resultBlocksView->fetch()) {
											if ($rowBlocksView["title"]!="" OR $rowBlocksView["type"]!="" OR $rowBlocksView["length"]!="") {
												print "<div style='height: 35px'>" ;
													if ($rowBlocksView["type"]!="" OR $rowBlocksView["length"]!="") {
														$width="69%" ;
													}
													else {
														$width="100%" ;
													}
													print "<div style='padding-left: 3px; width: $width; float: left'>" ;
														if ($rowBlocksView["title"]!="") {
															print "<h5 style='padding-bottom: 2px'>" . $rowBlocksView["title"] . "</h5>" ;
														}
													print "</div>" ;
													print "<div style='float: right; width: 29%; padding-right: 3px'>" ;
														if ($rowBlocksView["type"]!="" OR $rowBlocksView["length"]!="") {
															print "<div style='text-align: right; font-size: 85%; font-style: italic; margin-top: 12px; border-bottom: 1px solid #ddd'>" ; 
																if ($rowBlocksView["type"]!="") {
																	print $rowBlocksView["type"] ;
																	if ($rowBlocksView["length"]!="") {
																		print " | " ;
																	}
																}
																if ($rowBlocksView["length"]!="") {
																	print $rowBlocksView["length"] . " min" ;
																}
															print "</div>" ;
														}
													print "</div>" ;
												print "</div>" ;
											}
											if ($rowBlocksView["contents"]!="") {
												print "<div style='padding: 0px 3px; width: 100%; text-align: justify; border-bottom: 1px solid #ddd''>" . $rowBlocksView["contents"] . "</div>" ;
											}
										}
									print "</form>" ;
								print "</div>" ;
							}
						print "</td>" ;
					print "</tr>" ;
					
					
					print "<tr>" ;
						print "<td style='padding-top: 15px; width: 33%; vertical-align: top' colspan=3>" ;
							print "<h2>Homework</h2>" ;
							if ($row["homework"]=="Y") {
								print "<span style='font-weight: bold; color: #CC0000'>Due on " . dateConvertBack($guid, substr($row["homeworkDueDateTime"],0,10)) . " at " . substr($row["homeworkDueDateTime"],11,5) . "</span><br/>" ;
								print $row["homeworkDetails"] . "<br/>" ;
							}
							else {
								print "No<br/>" ;
							}
						print "</td>" ;
					print "</tr>" ;
				print "</table>" ;
					
				//Resources
				print "<div style='width:400px; float: right; font-size: 115%; font-weight: bold; padding-top: 10px'>" ;
					//Unit resources
					if ($row["gibbonUnitID"]!="") {
						try {
							$dataResources=array("gibbonUnitID"=>$row["gibbonUnitID"]); 
							$sqlResources="SELECT * FROM gibbonUnit WHERE gibbonUnitID=:gibbonUnitID" ;
							$resultResources=$connection2->prepare($sqlResources);
							$resultResources->execute($dataResources);
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						if ($resultResources->rowCount()>0) {
							$rowResources=$resultResources->fetch() ;
							if ($rowResources["attachment"]!="") {
								print "<span style='font-size: 115%; font-weight: bold'>Unit Resources</span><br/>" ;
								print "<ul>" ;
									print "<li><a href='" . $_SESSION[$guid]["absoluteURL"] . "/" . $rowResources["attachment"] . "'>Unit Outline</a></li>" ;
								print "</ul>" ;
							}
						}
					}
				print "</div>" ;
			}
		}
	}
}		
?>