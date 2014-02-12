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

if (isActionAccessible($guid, $connection2, "/modules/Planner/units_dump.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "You do not have access to this page." ;
	print "</div>" ;
}
else {
	$highestAction=getHighestGroupedAction($guid, $_GET["q"], $connection2) ;
	if ($highestAction==FALSE) {
		print "<div class='error'>" ;
		print "The highest grouped action cannot be determined." ;
		print "</div>" ;
	}
	else {
		print "<div class='trail'>" ;
		print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>Home</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/units.php&gibbonSchoolYearID=" . $_GET["gibbonSchoolYearID"] . "&gibbonCourseID=" . $_GET["gibbonCourseID"] . "'>Manage Units</a> > </div><div class='trailEnd'>Dump Unit</div>" ;
		print "</div>" ;
		
		//Check if courseschool year specified
		$gibbonSchoolYearID=$_GET["gibbonSchoolYearID"];
		$gibbonCourseID=$_GET["gibbonCourseID"]; 
		$gibbonUnitID=$_GET["gibbonUnitID"]; 
		if ($gibbonCourseID=="" OR $gibbonSchoolYearID=="") {
			print "<div class='error'>" ;
				print "You have not specified a course." ;
			print "</div>" ;
		}
		else {
			try {
				if ($highestAction=="Manage Units_all") {
					$data=array("gibbonSchoolYearID"=>$gibbonSchoolYearID, "gibbonCourseID"=>$gibbonCourseID); 
					$sql="SELECT * FROM gibbonCourse WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseID=:gibbonCourseID" ;
				}
				else if ($highestAction=="Manage Units_learningAreas") {
					$data=array("gibbonSchoolYearID"=>$gibbonSchoolYearID, "gibbonCourseID"=>$gibbonCourseID, "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
					$sql="SELECT gibbonCourseID, gibbonCourse.name, gibbonCourse.nameShort, gibbonDepartment.gibbonDepartmentID FROM gibbonCourse JOIN gibbonDepartment ON (gibbonCourse.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE gibbonDepartmentStaff.gibbonPersonID=:gibbonPersonID AND (role='Coordinator' OR role='Assistant Coordinator' OR role='Teacher (Curriculum)') AND gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseID=:gibbonCourseID ORDER BY gibbonCourse.nameShort" ;
				}
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
	
			if ($result->rowCount()!=1) {
				print "<div class='error'>" ;
					print "The specified course cannot be found or you do not have access to it." ;
				print "</div>" ;
			}
			else {
				$row=$result->fetch() ;
				$yearName=$row["name"] ;
				$gibbonDepartmentID=$row["gibbonDepartmentID"] ;
				
				//Check if unit specified
				if ($gibbonUnitID=="") {
					print "<div class='error'>" ;
						print "You have not specified a unit." ;
					print "</div>" ;
				}
				else {
					if ($gibbonUnitID=="") {
						print "<div class='error'>" ;
							print "You have not specified a unit." ;
						print "</div>" ;
					}
					else {
						try {
							$data=array("gibbonUnitID"=>$gibbonUnitID, "gibbonCourseID"=>$gibbonCourseID); 
							$sql="SELECT gibbonCourse.nameShort AS courseName, gibbonSchoolYearID, gibbonUnit.* FROM gibbonUnit JOIN gibbonCourse ON (gibbonUnit.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonUnitID=:gibbonUnitID AND gibbonUnit.gibbonCourseID=:gibbonCourseID" ;
							$result=$connection2->prepare($sql);
							$result->execute($data);
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						
						if ($result->rowCount()!=1) {
							print "<div class='error'>" ;
								print "The specified unit cannot be found." ;
							print "</div>" ;
						}
						else {
							//Let's go!
							$row=$result->fetch() ;
							
							print "<p>" ;
							print "This page allows you to view all of the content of a selected unit (<b><u>" . $row["courseName"] . " - " . $row["name"] . "</u></b>). If you wish to take this unit out of Gibbon, simply copy and paste the contents into a word processing application." ;
							print "</p>" ;
	
							if ($row["details"]!="") {
								print "<h2>Unit Overview</h2>" ;
								print "<p>" ;
									print $row["details"] ;
								print "</p>" ;
							}
							
							print "<h2>Unit Smart Blocks</h2>" ;
							try {
								$dataBlocks=array("gibbonUnitID"=>$gibbonUnitID); 
								$sqlBlocks="SELECT * FROM gibbonUnitBlock WHERE gibbonUnitID=:gibbonUnitID ORDER BY sequenceNumber" ; 
								$resultBlocks=$connection2->prepare($sqlBlocks );
								$resultBlocks->execute($dataBlocks);
							}
							catch(PDOException $e) { 
								print "<div class='error'>" . $e->getMessage() . "</div>" ; 
							}
							
							while ($rowBlocks=$resultBlocks->fetch()) {
								if ($rowBlocks["title"]!="" OR $rowBlocks["type"]!="" OR $rowBlocks["length"]!="") {
									print "<div class='blockView' style='min-height: 35px'>" ;
										if ($rowBlocks["type"]!="" OR $rowBlocks["length"]!="") {
											$width="69%" ;
										}
										else {
											$width="100%" ;
										}
										print "<div style='padding-left: 3px; width: $width; float: left;'>" ;
											if ($rowBlocks["title"]!="") {
												print "<h5 style='padding-bottom: 2px'>" . $rowBlocks["title"] . "</h5>" ;
											}
										print "</div>" ;
										if ($rowBlocks["type"]!="" OR $rowBlocks["length"]!="") {
											print "<div style='float: right; width: 29%; padding-right: 3px; height: 55px'>" ;
												print "<div style='text-align: right; font-size: 85%; font-style: italic; margin-top: 12px; border-bottom: 1px solid #ddd; height: 21px'>" ; 
													if ($rowBlocks["type"]!="") {
														print $rowBlocks["type"] ;
														if ($rowBlocks["length"]!="") {
															print " | " ;
														}
													}
													if ($rowBlocks["length"]!="") {
														print $rowBlocks["length"] . " min" ;
													}
												print "</div>" ;
											print "</div>" ;
										}
									print "</div>" ;
								}
								if ($rowBlocks["contents"]!="") {
									print "<div style='padding: 15px 3px 10px 3px; width: 100%; text-align: justify; border-bottom: 1px solid #ddd'>" . $rowBlocks["contents"] . "</div>" ;
								}
								if ($rowBlocks["teachersNotes"]!="") {
									print "<div style='background-color: #F6CECB; padding: 0px 3px 10px 3px; width: 100%; text-align: justify; border-bottom: 1px solid #ddd'><i>Teacher's Notes:</i> " . $rowBlocks["teachersNotes"] . "</div>" ;
								}
							}
							
							print "<h2>Unit Lessons</h2>" ;
							try {
								$dataClass=array("gibbonUnitID"=>$gibbonUnitID); 
								$sqlClass="SELECT gibbonUnitClass.gibbonCourseClassID, gibbonCourseClass.nameShort FROM gibbonUnitClass JOIN gibbonCourseClass ON (gibbonUnitClass.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonUnitID=:gibbonUnitID ORDER BY nameShort" ; 
								$resultClass=$connection2->prepare($sqlClass);
								$resultClass->execute($dataClass);
							}
							catch(PDOException $e) { 
								print "<div class='error'>" . $e->getMessage() . "</div>" ; 
							}
							
							if ($resultClass->rowCount()<1) {
								print "<div class='warning'>" ;
								print "There are is no class to display." ;
								print "</div>" ;
							}
							else {
								$rowClass=$resultClass->fetch() ;
								
								print "<p>Displaying lessons from class " . $row["courseName"] . "." . $rowClass["nameShort"] . "</p>" ;
									
								try {
									$dataLessons=array("gibbonCourseClassID"=>$rowClass["gibbonCourseClassID"], "gibbonUnitID"=>$gibbonUnitID); 
									$sqlLessons="SELECT * FROM gibbonPlannerEntry WHERE gibbonCourseClassID=:gibbonCourseClassID AND gibbonUnitID=:gibbonUnitID" ;
									$resultLessons=$connection2->prepare($sqlLessons) ;
									$resultLessons->execute($dataLessons) ;
								}
								catch(PDOException $e) { 
									print "<div class='error'>" . $e->getMessage() . "</div>" ;
								}	
								
								if ($resultLessons->rowCount()<1) {
									print "<div class='warning'>" ;
									print "There are no records to display." ;
									print "</div>" ;
								}
								else {
									while ($rowLessons=$resultLessons->fetch()) {
										print "<h4>" . $rowLessons["name"] . "</h4>" ;
										print $rowLessons["description"] ;
										
										try {
											$dataBlock=array("gibbonPlannerEntryID"=>$rowLessons["gibbonPlannerEntryID"]); 
											$sqlBlock="SELECT * FROM gibbonUnitClassBlock WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID ORDER BY sequenceNumber" ; 
											$resultBlock=$connection2->prepare($sqlBlock);
											$resultBlock->execute($dataBlock);
										}
										catch(PDOException $e) { 
											print "<div class='error'>" . $e->getMessage() . "</div>" ; 
										}
							
										while ($rowBlock=$resultBlock->fetch()) {
											print "<h5 style='font-size: 85%'>" . $rowBlock["title"] . "</h5>" ;
											print "<p>" ;
											print "<b>Type</b>: " . $rowBlock["type"] . "<br/>" ;
											print "<b>Length</b>: " . $rowBlock["length"] . "<br/>" ;
											print "<b>Contents</b>: " . $rowBlock["contents"] . "<br/>" ;
											print "<b>Teacher's Notes</b>: " . $rowBlock["teachersNotes"] . "<br/>" ;
											print "</p>" ;
										}
										
									}
								}
							}
							
							//Spit out outcomes
							try {
								$dataBlocks=array("gibbonUnitID"=>$gibbonUnitID);  
								$sqlBlocks="SELECT gibbonUnitOutcome.*, scope, name, nameShort, category, gibbonYearGroupIDList FROM gibbonUnitOutcome JOIN gibbonOutcome ON (gibbonUnitOutcome.gibbonOutcomeID=gibbonOutcome.gibbonOutcomeID) WHERE gibbonUnitID=:gibbonUnitID AND active='Y' ORDER BY sequenceNumber" ;
								$resultBlocks=$connection2->prepare($sqlBlocks);
								$resultBlocks->execute($dataBlocks);
							}
							catch(PDOException $e) { 
								print "<div class='error'>" . $e->getMessage() . "</div>" ; 
							}
							if ($resultBlocks->rowCount()>0) {
								print "<h2>Outcomes</h2>" ;
								print "<table cellspacing='0' style='width: 100%'>" ;
									print "<tr class='head'>" ;
										print "<th>" ;
											print "Scope" ;
										print "</th>" ;
										print "<th>" ;
											print "Category" ;
										print "</th>" ;
										print "<th>" ;
											print "Name" ;
										print "</th>" ;
										print "<th>" ;
											print "Year Groups" ;
										print "</th>" ;
										print "<th>" ;
											print "Actions" ;
										print "</th>" ;
									print "</tr>" ;
									
									$count=0;
									$rowNum="odd" ;
									while ($rowBlocks=$resultBlocks->fetch()) {
										if ($count%2==0) {
											$rowNum="even" ;
										}
										else {
											$rowNum="odd" ;
										}
										
										//COLOR ROW BY STATUS!
										print "<tr class=$rowNum>" ;
											print "<td>" ;
												print "<b>" . $rowBlocks["scope"] . "</b><br/>" ;
												if ($rowBlocks["scope"]=="Learning Area" AND $gibbonDepartmentID!="") {
													try {
														$dataLearningArea=array("gibbonDepartmentID"=>$gibbonDepartmentID); 
														$sqlLearningArea="SELECT * FROM gibbonDepartment WHERE gibbonDepartmentID=:gibbonDepartmentID" ;
														$resultLearningArea=$connection2->prepare($sqlLearningArea);
														$resultLearningArea->execute($dataLearningArea);
													}
													catch(PDOException $e) { 
														print "<div class='error'>" . $e->getMessage() . "</div>" ; 
													}
													if ($resultLearningArea->rowCount()==1) {
														$rowLearningAreas=$resultLearningArea->fetch() ;
														print "<span style='font-size: 75%; font-style: italic'>" . $rowLearningAreas["name"] . "</span>" ;
													}
												}
											print "</td>" ;
											print "<td>" ;
												print "<b>" . $rowBlocks["category"] . "</b><br/>" ;
											print "</td>" ;
											print "<td>" ;
												print "<b>" . $rowBlocks["nameShort"] . "</b><br/>" ;
												print "<span style='font-size: 75%; font-style: italic'>" . $rowBlocks["name"] . "</span>" ;
											print "</td>" ;
											print "<td>" ;
												print getYearGroupsFromIDList($connection2, $rowBlocks["gibbonYearGroupIDList"]) ;
											print "</td>" ;
											print "<td>" ;
												print "<script type='text/javascript'>" ;	
													print "$(document).ready(function(){" ;
														print "\$(\".description-$count\").hide();" ;
														print "\$(\".show_hide-$count\").fadeIn(1000);" ;
														print "\$(\".show_hide-$count\").click(function(){" ;
														print "\$(\".description-$count\").fadeToggle(1000);" ;
														print "});" ;
													print "});" ;
												print "</script>" ;
												if ($rowBlocks["content"]!="") {
													print "<a title='View Description' class='show_hide-$count' onclick='false' href='#'><img style='padding-left: 0px' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/Default/img/page_down.png' alt='Show Comment' onclick='return false;' /></a>" ;
												}
											print "</td>" ;
										print "</tr>" ;
										if ($rowBlocks["content"]!="") {
											print "<tr class='description-$count' id='description-$count'>" ;
												print "<td colspan=6>" ;
													print $rowBlocks["content"] ;
												print "</td>" ;
											print "</tr>" ;
										}
										print "</tr>" ;
										
										$count++ ;
									}
								print "</table>" ;
							}
						}
					}
				}
			}
		}
	} 
}		
?>