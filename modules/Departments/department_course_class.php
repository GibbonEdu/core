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

if (isActionAccessible($guid, $connection2, "/modules/Departments/department_course_class.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "You do not have access to this action." ;
	print "</div>" ;
}
else {
	$gibbonCourseClassID=$_GET["gibbonCourseClassID"] ;
	$gibbonCourseID=$_GET["gibbonCourseID"] ;
	$gibbonDepartmentID=$_GET["gibbonDepartmentID"] ;
	if ($gibbonCourseClassID=="") {
		print "<div class='error'>" ;
			print "You have not specified a learning area, course or class." ;
		print "</div>" ;
	}
	else {
		$proceed=false; 
		if ($gibbonDepartmentID!="") {
			try {
				$data=array("gibbonCourseClassID"=>$gibbonCourseClassID); 
				$sql="SELECT gibbonCourse.gibbonSchoolYearID,gibbonDepartment.name AS department, gibbonCourse.name AS courseLong, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourse.gibbonCourseID, gibbonSchoolYear.name AS year FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) JOIN gibbonSchoolYear ON (gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) JOIN gibbonDepartment ON (gibbonDepartment.gibbonDepartmentID=gibbonCourse.gibbonDepartmentID) WHERE gibbonCourseClassID=:gibbonCourseClassID" ;
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
			
			if ($result->rowCount()!=1) {
				print "<div class='error'>" ;
					print "The selected learning area does not exist." ;
				print "</div>" ;
			}
			else {
				$row=$result->fetch() ;
				$proceed=true ;
			}
		}
		else {
			try {
				$data=array("gibbonCourseClassID"=>$gibbonCourseClassID); 
				$sql="SELECT gibbonCourse.gibbonSchoolYearID, gibbonCourse.name AS courseLong, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourse.gibbonCourseID, gibbonSchoolYear.name AS year FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) JOIN gibbonSchoolYear ON (gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE gibbonCourseClassID=:gibbonCourseClassID" ;
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
			
			if ($result->rowCount()!=1) {
				print "<div class='error'>" ;
					print "The selected learning area does not exist." ;
				print "</div>" ;
			}
			else {
				$row=$result->fetch() ;
				$proceed=true ;
			}
		}
		
		if ($proceed==true) {
			//Get role within learning area
			if ($gibbonDepartmentID!="") {
				$role=getRole($_SESSION[$guid]["gibbonPersonID"], $gibbonDepartmentID, $connection2 ) ;
			}
			
			$extra="" ;
			if (($role=="Coordinator" OR $role=="Assistant Coordinator" OR $role=="Teacher (Curriculum)" OR $role=="Teacher") AND $row["gibbonSchoolYearID"]!=$_SESSION[$guid]["gibbonSchoolYearID"]) {
				$extra=" " . $row["year"];
			}
			print "<div class='trail'>" ;
			if ($gibbonDepartmentID!="") {
				print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>Home</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/departments.php'>View All</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/department.php&gibbonDepartmentID=" . $_GET["gibbonDepartmentID"] . "'>" . $row["department"] . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/department_course.php&gibbonDepartmentID=" . $_GET["gibbonDepartmentID"] . "&gibbonCourseID=" . $_GET["gibbonCourseID"] . "'>" . $row["courseLong"] . "$extra</a> ></div><div class='trailEnd'>" . $row["course"] . "." . $row["class"] . "</div>" ;
			}
			else {
				print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>Home</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/departments.php'>View All</a> > Class ></div><div class='trailEnd'>" . $row["course"] . "." . $row["class"] . "</div>" ;
			}
			print "</div>" ;
			
			$subpage=NULL ;
			if (isset($_GET["subpage"])) {
				$subpage=$_GET["subpage"] ;
			}
			if ($subpage=="") {
				$subpage="Study Plan" ;
			}
			
			print "<h2>" ;
				print $subpage ;
			print "</h2>" ;
			
			if ($subpage=="Study Plan") {
				print "<div style='margin-top: 0px' class='linkTop'>" ;
				if (isActionAccessible($guid, $connection2, "/modules/Planner/planner.php")) {
					print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Planner/planner.php&gibbonCourseClassID=$gibbonCourseClassID&viewBy=class'><img style='margin-top: 3px' title='View Planner' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/planner.gif'/></a> " ;
				}
				if (getHighestGroupedAction($guid, "/modules/Markbook/markbook_view.php", $connection2)=="View Markbook_allClassesAllData") {
					print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Markbook/markbook_view.php&gibbonCourseClassID=$gibbonCourseClassID'><img style='margin-top: 3px' title='View Markbook' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/markbook.gif'/></a> " ;
				}
				print "</div>" ;
				
				//PRINT LESSONS IN UNITS
				$count=0 ;
				try {
					$dataUnit=array("gibbonCourseClassID"=>$gibbonCourseClassID, "gibbonCourseClassID"=>$gibbonCourseClassID); 
					$sqlUnit="SELECT gibbonUnit.name, gibbonUnit.description, gibbonUnit.attachment, gibbonUnit.gibbonUnitID FROM gibbonUnit JOIN gibbonUnitClass ON (gibbonUnit.gibbonUnitID=.gibbonUnitClass.gibbonUnitID) JOIN gibbonCourse ON (gibbonUnit.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID AND gibbonUnitClass.gibbonCourseClassID=:gibbonCourseClassID AND running='Y' ORDER BY gibbonUnit.name" ;
					$resultUnit=$connection2->prepare($sqlUnit);
					$resultUnit->execute($dataUnit);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				
				if ($resultUnit->rowCount()<1) {
					print "<div class='error'>" ;
					print "There are no units to display in the specified school year" ;
					print "</div>" ;
				}
				else {
					while ($rowUnit=$resultUnit->fetch()) {
						$style="" ;
						if ($count==0) {
							$style="style='margin-top: 0px'" ;
						}
						print "<h4 $style>" ;
						print $rowUnit["name"] ;
						print "</h4>" ;
						print "<p>" ;
						print $rowUnit["description"] . "<br/>" ;
						if ($rowUnit["attachment"]!="") {
							print "<br/><a href='" . $_SESSION[$guid]["absoluteURL"] . "/" . $rowUnit["attachment"] . "'>Download Unit Outline</a></li><br/><br/>" ;
						}
						print "</p>" ;
						
						//Display lessons in this unit
						try {
							$dataLessons=array("gibbonCourseClassID"=>$gibbonCourseClassID, "gibbonUnitID"=>$rowUnit["gibbonUnitID"]); 
							$sqlLessons="SELECT gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, timeStart, timeEnd, viewableStudents, viewableParents, homework, homeworkSubmission, homeworkCrowdAssess, date, summary FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE NOT date IS NULL AND gibbonPlannerEntry.gibbonCourseClassID=:gibbonCourseClassID AND gibbonUnitID=:gibbonUnitID AND viewableStudents='Y' AND viewableParents='Y' ORDER BY date, timeStart" ; 
							$resultLessons=$connection2->prepare($sqlLessons);
							$resultLessons->execute($dataLessons);
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						
						if ($resultLessons->rowCount()>0) {
							print "<table cellspacing='0' style='width: 100%'>" ;
								print "<tr class='head'>" ;
									print "<th>" ;
										print "Lesson" ;
									print "</th>" ;
									print "<th>" ;
										print "Date" ;
									print "</th>" ;
									print "<th>" ;
										print "Time" ;
									print "</th>" ;
									print "<th>" ;
										print "Summary" ;
									print "</th>" ;
									print "<th>" ;
										print "Homework<br/><span style='font-size: 80%'>Is set?</span>" ;
									print "</th>" ;
									print "<th>" ;
										print "Action" ;
									print "</th>" ;
								print "</tr>" ;
								
								$count=0;
								$rowNum="odd" ;
								while ($rowLessons=$resultLessons->fetch()) {
									if ($count%2==0) {
										$rowNum="even" ;
									}
									else {
										$rowNum="odd" ;
									}
									$count++ ;
									
									//COLOR ROW BY STATUS!
									print "<tr class=$rowNum>" ;
										print "<td>" ;
											print "<b>" . $rowLessons["name"] . "</b><br/>" ;
										print "</td>" ;
										print "<td>" ;
											print dateConvertBack($rowLessons["date"]) ;
										print "</td>" ;
										print "<td>" ;
											print substr($rowLessons["timeStart"],0,5) . "-" . substr($rowLessons["timeEnd"],0,5) ;
										print "</td>" ;
										print "<td>" ;
											print $rowLessons["summary"] ;
										print "</td>" ;
										print "<td>" ;
											print $rowLessons["homework"] ;
											if ($rowLessons["homeworkSubmission"]=="Y") {
												print "+OS" ;
												if ($rowLessons["homeworkCrowdAssess"]=="Y") {
													print "+CA" ;
												}
											}
										print "</td>" ;
										print "<td>" ;
											print "<a class='thickbox' href='" . $_SESSION[$guid]["absoluteURL"] . "/fullscreen.php?q=/modules/Departments/department_course_class_full.php&gibbonPlannerEntryID=" . $rowLessons["gibbonPlannerEntryID"] . "&gibbonCourseClassID=$gibbonCourseClassID&width=1000&height=550'><img title='View Details' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/plus.png'/></a> " ;
										print "</td>" ;
									print "</tr>" ;
								}
							print "</table>" ;
						}
						$count++ ;
					}
				}
				
				//PRINT LESSONS IN HOOKED UNITS
				try {
					$dataHooks=array(); 
					$sqlHooks="SELECT * FROM gibbonHook WHERE type='Unit'" ;
					$resultHooks=$connection2->prepare($sqlHooks);
					$resultHooks->execute($dataHooks);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				while ($rowHooks=$resultHooks->fetch()) {
					$hookOptions=unserialize($rowHooks["options"]) ;
					if ($hookOptions["unitTable"]!="" AND $hookOptions["unitIDField"]!="" AND $hookOptions["unitCourseIDField"]!="" AND $hookOptions["unitNameField"]!="" AND $hookOptions["unitDescriptionField"]!="" AND $hookOptions["classLinkTable"]!="" AND $hookOptions["classLinkJoinFieldUnit"]!="" AND $hookOptions["classLinkJoinFieldClass"]!="" AND $hookOptions["classLinkIDField"]!="") {
						try {
							$dataHookUnits=array("gibbonCourseClassID"=>$gibbonCourseClassID); 
							$sqlHookUnits="SELECT * FROM " . $hookOptions["unitTable"] . " JOIN " . $hookOptions["classLinkTable"] . " ON (" . $hookOptions["unitTable"] . "." . $hookOptions["unitIDField"] . "=" . $hookOptions["classLinkTable"] . "." . $hookOptions["classLinkJoinFieldUnit"] . ") WHERE " . $hookOptions["classLinkJoinFieldClass"] . "=:gibbonCourseClassID ORDER BY " . $hookOptions["classLinkTable"] . "." . $hookOptions["classLinkIDField"] ;
							$resultHookUnits=$connection2->prepare($sqlHookUnits);
							$resultHookUnits->execute($dataHookUnits);
						}
						catch(PDOException $e) {
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						while ($rowHookUnits=$resultHookUnits->fetch()) {
							print "<h4>" ;
							print $rowHookUnits[$hookOptions["unitNameField"]] ;
							if ($rowHookUnits[$hookOptions["classLinkStartDateField"]]!="") {
								print "<span style='font-size: 75%; font-style: italic; font-weight: normal'> Studied from " . dateConvertBack($rowHookUnits[$hookOptions["classLinkStartDateField"]]) . "</span>" ;
							}
							if ($rowHooks["name"]!="") {
								print "<br/><span style='font-size: 75%; font-style: italic; font-weight: normal'>" . $rowHooks["name"] . " Unit</span>" ;
							}
							print "</h4>" ;
							print "<p>" ;
							print $rowHookUnits[$hookOptions["unitDescriptionField"]] ;
							print "</p>" ;
							
							//Display lessons in this unit
							try {
								$dataLessons=array("gibbonCourseClassID"=>$gibbonCourseClassID, "gibbonUnitID"=>$rowHookUnits[$hookOptions["unitIDField"]]); 
								$sqlLessons="SELECT gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, timeStart, timeEnd, viewableStudents, viewableParents, homework, homeworkSubmission, homeworkCrowdAssess, date, summary FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE NOT date IS NULL AND gibbonPlannerEntry.gibbonCourseClassID=:gibbonCourseClassID AND gibbonUnitID=:gibbonUnitID AND viewableStudents='Y' AND viewableParents='Y' ORDER BY date, timeStart" ; 
								$resultLessons=$connection2->prepare($sqlLessons);
								$resultLessons->execute($dataLessons);
							}
							catch(PDOException $e) { 
								print "<div class='error'>" . $e->getMessage() . "</div>" ; 
							}
							
							//Only show add if user has edit rights
							if ($resultLessons->rowCount()>0) {
								print "<table cellspacing='0' style='width: 100%'>" ;
									print "<tr class='head'>" ;
										print "<th>" ;
											print "Lesson" ;
										print "</th>" ;
										print "<th>" ;
											print "Date" ;
										print "</th>" ;
										print "<th>" ;
											print "Time" ;
										print "</th>" ;
										print "<th>" ;
											print "Summary" ;
										print "</th>" ;
										print "<th>" ;
											print "Homework<br/><span style='font-size: 80%'>Is set?</span>" ;
										print "</th>" ;
										print "<th>" ;
											print "Action" ;
										print "</th>" ;
									print "</tr>" ;
									
									$count=0;
									$rowNum="odd" ;
									while ($rowLessons=$resultLessons->fetch()) {
										if ($count%2==0) {
											$rowNum="even" ;
										}
										else {
											$rowNum="odd" ;
										}
										$count++ ;
										
										//COLOR ROW BY STATUS!
										print "<tr class=$rowNum>" ;
											print "<td>" ;
												print "<b>" . $rowLessons["name"] . "</b><br/>" ;
											print "</td>" ;
											print "<td>" ;
												print dateConvertBack($rowLessons["date"]) ;
											print "</td>" ;
											print "<td>" ;
												print substr($rowLessons["timeStart"],0,5) . "-" . substr($rowLessons["timeEnd"],0,5) ;
											print "</td>" ;
											print "<td>" ;
												print $rowLessons["summary"] ;
											print "</td>" ;
											print "<td>" ;
												print $rowLessons["homework"] ;
												if ($rowLessons["homeworkSubmission"]=="Y") {
													print "+OS" ;
													if ($rowLessons["homeworkCrowdAssess"]=="Y") {
														print "+CA" ;
													}
												}
											print "</td>" ;
											print "<td>" ;
												print "<a class='thickbox' href='" . $_SESSION[$guid]["absoluteURL"] . "/fullscreen.php?q=/modules/Departments/department_course_class_full.php&gibbonPlannerEntryID=" . $rowLessons["gibbonPlannerEntryID"] . "&viewBy=$viewBy&gibbonCourseClassID=$gibbonCourseClassID&date=$date&width=1000&height=550'><img title='View Details' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/plus.png'/></a> " ;
											print "</td>" ;
										print "</tr>" ;
									}
								print "</table>" ;
							}
							$count++ ;							
						}
					}
				}
				
				//PRINT OTHER LESSONS
				try {
					$dataLessons=array("gibbonCourseClassID"=>$gibbonCourseClassID); 
					$sqlLessons="SELECT gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, timeStart, timeEnd, viewableStudents, viewableParents, homework, homeworkSubmission, homeworkCrowdAssess, date, summary FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonPlannerEntry.gibbonCourseClassID=:gibbonCourseClassID AND gibbonUnitID IS NULL AND viewableStudents='Y' AND viewableParents='Y' ORDER BY date, timeStart" ; 
					$resultLessons=$connection2->prepare($sqlLessons);
					$resultLessons->execute($dataLessons);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				//Only show add if user has edit rights
				if ($resultLessons->rowCount()>0) {
					print "<h4 $style>" ;
					print "Other Lessons" ;
					print "</h4>" ;
				
					print "<table cellspacing='0' style='width: 100%'>" ;
						print "<tr class='head'>" ;
							print "<th>" ;
								print "Lesson" ;
							print "</th>" ;
							print "<th>" ;
								print "Date" ;
							print "</th>" ;
							print "<th>" ;
								print "Time" ;
							print "</th>" ;
							print "<th>" ;
								print "Summary" ;
							print "</th>" ;
							print "<th>" ;
								print "Homework<br/><span style='font-size: 80%'>Is set?</span>" ;
							print "</th>" ;
							print "<th>" ;
								print "Action" ;
							print "</th>" ;
						print "</tr>" ;
						
						$count=0;
						$rowNum="odd" ;
						while ($rowLessons=$resultLessons->fetch()) {
							if ($count%2==0) {
								$rowNum="even" ;
							}
							else {
								$rowNum="odd" ;
							}
							$count++ ;
							
							//COLOR ROW BY STATUS!
							print "<tr class=$rowNum>" ;
								print "<td>" ;
									print "<b>" . $rowLessons["name"] . "</b><br/>" ;
								print "</td>" ;
								print "<td>" ;
									print dateConvertBack($rowLessons["date"]) ;
								print "</td>" ;
								print "<td>" ;
									print substr($rowLessons["timeStart"],0,5) . "-" . substr($rowLessons["timeEnd"],0,5) ;
								print "</td>" ;
								print "<td>" ;
									print $rowLessons["summary"] ;
								print "</td>" ;
								print "<td>" ;
									print $rowLessons["homework"] ;
									if ($rowLessons["homeworkSubmission"]=="Y") {
										print "+OS" ;
										if ($rowLessons["homeworkCrowdAssess"]=="Y") {
											print "+CA" ;
										}
									}
								print "</td>" ;
								print "<td>" ;
									print "<a class='thickbox' href='" . $_SESSION[$guid]["absoluteURL"] . "/fullscreen.php?q=/modules/Departments/department_course_class_full.php&gibbonPlannerEntryID=" . $rowLessons["gibbonPlannerEntryID"] . "&viewBy=$viewBy&gibbonCourseClassID=$gibbonCourseClassID&date=$date&width=1000&height=550'><img title='View Details' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/plus.png'/></a> " ;
								print "</td>" ;
							print "</tr>" ;
						}
					print "</table>" ;
				}
			}
			else if ($subpage=="Participants") {
				print "<div class='linkTop'>" ;
				if (getHighestGroupedAction($guid, "/modules/Students/student_view_details.php", $connection2)=="View Student Profile_full") {
					print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/department_course_classExport.php?gibbonCourseClassID=$gibbonCourseClassID&address=" . $_GET["q"] . "'><img title='Export to Excel' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/download.png'/></a>" ;
				}
				print "</div>" ;
				
				try {
					$data=array("gibbonCourseClassID"=>$gibbonCourseClassID); 
					$sql="SELECT gibbonCourseClassID, gibbonCourse.nameShort AS courseName, gibbonCourseClass.nameShort AS className FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourseClassID=:gibbonCourseClassID ORDER BY gibbonCourse.name, gibbonCourseClass.name" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				if ($result->rowCount()<1) {
					print "<div class='error'>" ;
						print "The specified class does not exist.";
					print "</div>" ;
				}
				else {
					printClassGroupTable($guid, $gibbonCourseClassID, 4, $connection2) ;
				}
			}
				
			//Print sidebar
			$_SESSION[$guid]["sidebarExtra"]="" ;
			
			$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "<h2>" ;
			$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . $row["course"] . "." . $row["class"] . " Information" ;
			$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "</h2>" ;
			$_SESSION[$guid]["sidebarExtra"]= $_SESSION[$guid]["sidebarExtra"] . "<ul>" ;
			$style="" ;
			if ($subpage=="Study Plan") {
				$style="style='font-weight: bold'" ;
			}
			$_SESSION[$guid]["sidebarExtra"]= $_SESSION[$guid]["sidebarExtra"] . "<li><a $style href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=" . $_GET["q"] . "&gibbonDepartmentID=$gibbonDepartmentID&gibbonCourseID=" . $row["gibbonCourseID"] . "&gibbonCourseClassID=$gibbonCourseClassID&subpage=Study Plan'>Study Plan</a></li>" ;
			$style="" ;
			if ($subpage=="Participants") {
				$style="style='font-weight: bold'" ;
			}
			$_SESSION[$guid]["sidebarExtra"]= $_SESSION[$guid]["sidebarExtra"] . "<li><a $style href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=" . $_GET["q"] . "&gibbonDepartmentID=$gibbonDepartmentID&gibbonCourseID=" . $row["gibbonCourseID"] . "&gibbonCourseClassID=$gibbonCourseClassID&subpage=Participants'>Participants</a></li>" ;
			$_SESSION[$guid]["sidebarExtra"]= $_SESSION[$guid]["sidebarExtra"] . "</ul>" ;
			
			//Print related class list
			try {
				$dataCourse=array("gibbonCourseID"=>$row["gibbonCourseID"], "gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
				$sqlCourse="SELECT gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourse.gibbonCourseID=:gibbonCourseID AND gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY class" ;
				$resultCourse=$connection2->prepare($sqlCourse);
				$resultCourse->execute($dataCourse);
			}
			catch(PDOException $e) { 
				$_SESSION[$guid]["sidebarExtra"].="<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
			
			if ($resultCourse->rowCount()>0) {
				$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "<h2>" ;
				$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "Related Classes" ;
				$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "</h2>" ;
			
				$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "<ul>" ;
				while ($rowCourse=$resultCourse->fetch()) {
					$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "<li><a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Departments/department_course_class.php&gibbonDepartmentID=$gibbonDepartmentID&gibbonCourseID=" . $row["gibbonCourseID"] . "&gibbonCourseClassID=" . $rowCourse["gibbonCourseClassID"] . "'>" . $rowCourse["course"] . "." . $rowCourse["class"] . "</a></li>" ;
				}
				$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "</ul>" ;	
			}
			
			//Print list of all classes
			$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "<h2>" ;
			$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "Current Classes" ;
			$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "</h2>" ;
			$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "<form method='get' action='" . $_SESSION[$guid]["absoluteURL"] . "/index.php'>" ;
				$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>" ;
					$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "<tr>" ;
						$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "<td class='right'>" ;
							$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "<select style='width:160px; float: none' name='gibbonCourseClassID'>" ;
								try {
									$dataSelect=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
									$sqlSelect="SELECT gibbonCourseClassID, gibbonCourse.gibbonCourseID, gibbonCourse.nameShort AS courseName, gibbonCourseClass.nameShort AS className FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY gibbonCourse.nameShort, gibbonCourseClass.nameShort" ;
									$resultSelect=$connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								}
								catch(PDOException $e) { 
									$_SESSION[$guid]["sidebarExtra"].="<div class='error'>" . $e->getMessage() . "</div>" ; 
								}
								
								
								$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "<option value=''></option>" ;
								while ($rowSelect=$resultSelect->fetch()) {
									if ($gibbonCourseClassID==$rowSelect["gibbonCourseClassID"]) {
										$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "<option selected value='" . $rowSelect["gibbonCourseClassID"] . "'>" . htmlPrep($rowSelect["courseName"]) . "." . htmlPrep($rowSelect["className"]) . "</option>" ;
									}
									else {
										$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "<option value='" . $rowSelect["gibbonCourseClassID"] . "'>" . htmlPrep($rowSelect["courseName"]) . "." . htmlPrep($rowSelect["className"]) . "</option>" ;
									}
								}
							$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "</select>" ;
						$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "</td>" ;
						$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "<td class='right'>" ;
							$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "<input type='hidden' name='q' value='/modules/" . $_SESSION[$guid]["module"] . "/department_course_class.php'>" ;
							$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "<input type='submit' value='Go'>" ;
						$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "</td>" ;
					$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "</tr>" ;
				$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "</table>" ;
			$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "</form>" ;
			
		}
	}
}
?>