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

@session_start() ;

//Module includes
include "./modules/" . $_SESSION[$guid]["module"] . "/moduleFunctions.php" ;

if (isActionAccessible($guid, $connection2, "/modules/Planner/planner_unitOverview.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "Your request failed because you do not have access to this action.") ;
	print "</div>" ;
}
else {
	$highestAction=getHighestGroupedAction($guid, $_GET["q"], $connection2) ;
	if ($highestAction==FALSE) {
		print "<div class='error'>" ;
		print __($guid, "The highest grouped action cannot be determined.") ;
		print "</div>" ;
	}
	else {
		$viewBy=NULL ;
		if (isset($_GET["viewBy"])) {
			$viewBy=$_GET["viewBy"] ;
		}
		$subView=NULL ;
		if (isset($_GET["subView"])) {
			$subView=$_GET["subView"] ;
		}
		if ($viewBy!="date" AND $viewBy!="class") {
			$viewBy="date" ;
		}
		$gibbonCourseClassID=NULL ;
		$date=NULL ;
		$dateStamp=NULL ;
		if ($viewBy=="date") {
			$date=$_GET["date"] ;
			if (isset($_GET["dateHuman"])) {
				$date=dateConvert($guid, $_GET["dateHuman"]) ;
			}
			if ($date=="") {
				$date=date("Y-m-d");
			}
			list($dateYear, $dateMonth, $dateDay)=explode('-', $date);
			$dateStamp=mktime(0, 0, 0, $dateMonth, $dateDay, $dateYear);	
		}
		else if ($viewBy=="class") {
			$class=NULL ;
			if (isset($_GET["class"])) {
				$class=$_GET["class"] ;
			}
			$gibbonCourseClassID=$_GET["gibbonCourseClassID"] ;
		}
		$replyTo=NULL ;
		if (isset($_GET["replyTo"])) {
			$replyTo=$_GET["replyTo"] ;
		}
			
		//Get class variable
		$gibbonPlannerEntryID=NULL ;
		if (isset($_GET["gibbonPlannerEntryID"])) {
			$gibbonPlannerEntryID=$_GET["gibbonPlannerEntryID"] ;
		}
		if ($gibbonPlannerEntryID=="") {
			print "<div class='warning'>" ;
				print __($guid, "You have not specified one or more required parameters.") ;
			print "</div>" ;
		}
		//Check existence of and access to this class.
		else {
			if ($highestAction=="Lesson Planner_viewMyChildrensClasses") {
				if ($_GET["search"]=="") {
					print "<div class='warning'>" ;
						print __($guid, "You have not specified one or more required parameters.") ;
					print "</div>" ;
				}
				else {
					$gibbonPersonID=$_GET["search"] ;
					try {
						$dataChild=array("gibbonPersonID1"=>$gibbonPersonID, "gibbonPersonID2"=>$_SESSION[$guid]["gibbonPersonID"] ); 
						$sqlChild="SELECT * FROM gibbonFamilyChild JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonFamilyChild.gibbonPersonID=:gibbonPersonID1 AND gibbonFamilyAdult.gibbonPersonID=:gibbonPersonID2 AND childDataAccess='Y'" ;
						$resultChild=$connection2->prepare($sqlChild);
						$resultChild->execute($dataChild);
					}
					catch(PDOException $e) { 
						print "<div class='error'>" . $e->getMessage() . "</div>" ; 
					}
					if ($resultChild->rowCount()!=1) {
						print "<div class='error'>" ;
						print __($guid, "The selected record does not exist, or you do not have access to it.") ;
						print "</div>" ;
					}
					else {
						$data=array("date"=>$date) ;
						$data=array("gibbonPlannerEntryID1"=>$gibbonPlannerEntryID) ;
						$data=array("gibbonPlannerEntryID2"=>$gibbonPlannerEntryID) ;
						$sql="(SELECT gibbonPlannerEntry.gibbonPlannerEntryID, gibbonCourseClass.gibbonCourseClassID, gibbonUnitID, gibbonHookID, gibbonPlannerEntry.gibbonCourseClassID, gibbonPlannerEntry.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, date, timeStart, timeEnd, summary, gibbonPlannerEntry.description, teachersNotes, homework, homeworkDueDateTime, homeworkDetails, viewableStudents, viewableParents, role, homeworkSubmission, homeworkSubmissionDateOpen, homeworkSubmissionDrafts, homeworkSubmissionType FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourseClassPerson.gibbonPersonID=$gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left' AND gibbonPlannerEntry.gibbonPlannerEntryID=:gibbonPlannerEntryID1) UNION (SELECT gibbonPlannerEntry.gibbonPlannerEntryID, gibbonCourseClass.gibbonCourseClassID, gibbonUnitID, gibbonHookID, gibbonPlannerEntry.gibbonCourseClassID, gibbonPlannerEntry.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, date, timeStart, timeEnd, summary, gibbonPlannerEntry.description, teachersNotes, homework, homeworkDueDateTime, homeworkDetails, viewableStudents, viewableParents, role, homeworkSubmission, homeworkSubmissionDateOpen, homeworkSubmissionDrafts, homeworkSubmissionType FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonPlannerEntryGuest ON (gibbonPlannerEntryGuest.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE date=:date AND gibbonPlannerEntryGuest.gibbonPersonID=$gibbonPersonID AND gibbonPlannerEntry.gibbonPlannerEntryID=:gibbonPlannerEntryID2) ORDER BY date, timeStart" ; 
					}
				}
			}
			else if ($highestAction=="Lesson Planner_viewMyClasses" OR $highestAction=="Lesson Planner_viewAllEditMyClasses") {
				$data=array("date"=>$date, "gibbonPlannerEntryID1"=>$gibbonPlannerEntryID, "gibbonPlannerEntryID2"=>$gibbonPlannerEntryID) ;
				$sql="(SELECT gibbonPlannerEntry.gibbonPlannerEntryID, gibbonCourseClass.gibbonCourseClassID, gibbonUnitID, gibbonHookID, gibbonPlannerEntry.gibbonCourseClassID, gibbonPlannerEntry.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, date, timeStart, timeEnd, summary, gibbonPlannerEntry.description, teachersNotes, homework, homeworkDueDateTime, homeworkDetails, viewableStudents, viewableParents, role, homeworkSubmission, homeworkSubmissionDateOpen, homeworkSubmissionDrafts, homeworkSubmissionType FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourseClassPerson.gibbonPersonID=" . $_SESSION[$guid]["gibbonPersonID"] . " AND NOT role='Student - Left' AND NOT role='Teacher - Left' AND gibbonPlannerEntry.gibbonPlannerEntryID=:gibbonPlannerEntryID1) UNION (SELECT gibbonPlannerEntry.gibbonPlannerEntryID, gibbonCourseClass.gibbonCourseClassID, gibbonUnitID, gibbonHookID, gibbonPlannerEntry.gibbonCourseClassID, gibbonPlannerEntry.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, date, timeStart, timeEnd, summary, gibbonPlannerEntry.description, teachersNotes, homework, homeworkDueDateTime, homeworkDetails, viewableStudents, viewableParents, role, homeworkSubmission, homeworkSubmissionDateOpen, homeworkSubmissionDrafts, homeworkSubmissionType FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonPlannerEntryGuest ON (gibbonPlannerEntryGuest.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE date=:date AND gibbonPlannerEntryGuest.gibbonPersonID=" . $_SESSION[$guid]["gibbonPersonID"] . " AND gibbonPlannerEntry.gibbonPlannerEntryID=:gibbonPlannerEntryID2) ORDER BY date, timeStart" ; 
			}
			else if ($highestAction=="Lesson Planner_viewEditAllClasses") {
				$data=array("gibbonPlannerEntryID"=>$gibbonPlannerEntryID) ;
				$sql="SELECT gibbonPlannerEntry.gibbonPlannerEntryID, gibbonCourseClass.gibbonCourseClassID, gibbonUnitID, gibbonHookID, gibbonPlannerEntry.gibbonCourseClassID, gibbonPlannerEntry.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, date, timeStart, timeEnd, summary, gibbonPlannerEntry.description, teachersNotes, homework, homeworkDueDateTime, homeworkDetails, viewableStudents, viewableParents, 'Teacher' AS role, homeworkSubmission, homeworkSubmissionDateOpen, homeworkSubmissionDrafts, homeworkSubmissionType FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonPlannerEntry.gibbonPlannerEntryID=:gibbonPlannerEntryID ORDER BY date, timeStart" ; 
			}
			try {
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
			
			if ($result->rowCount()!=1) {
				print "<div class='error'>" ;
					print __($guid, "The selected record does not exist, or you do not have access to it.") ;
				print "</div>" ;
			}
			else {
				$row=$result->fetch() ;
				
				$extra="" ;
				if ($viewBy=="class") {
					$extra=$row["course"] . "." . $row["class"] ;
				}
				else {
					$extra=dateConvertBack($guid, $date) ;
				}
				
				$params="" ;
				if ($_GET["date"]!="") {
					$params=$params."&date=" . $_GET["date"] ;
				}
				if ($_GET["viewBy"]!="") {
					$params=$params."&viewBy=" . $_GET["viewBy"] ;
				}
				if ($_GET["gibbonCourseClassID"]!="") {
					$params=$params."&gibbonCourseClassID=" . $_GET["gibbonCourseClassID"] ;
				}
				$params.="&subView=$subView" ;
									
				print "<div class='trail'>" ;
				print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/planner.php$params'>" . __($guid, 'Planner') . " $extra</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/planner_view_full.php$params&gibbonPlannerEntryID=$gibbonPlannerEntryID'>" . __($guid, 'View Lesson Plan') . "</a> > </div><div class='trailEnd'>" . __($guid, 'Unit Overview') . "</div>" ;
				print "</div>" ;
				
				if ($row["gibbonUnitID"]=="") {
					print __($guid, "The selected record does not exist, or you do not have access to it.") ;
				}
				else {
					//Get unit contents
					try {
						$dataUnit=array("gibbonUnitID"=>$row["gibbonUnitID"]) ;
						$sqlUnit="SELECT * FROM gibbonUnit WHERE gibbonUnitID=:gibbonUnitID" ; 
						$resultUnit=$connection2->prepare($sqlUnit);
						$resultUnit->execute($dataUnit);
					}
					catch(PDOException $e) { 
						print "<div class='error'>" . $e->getMessage() . "</div>" ; 
					}
					
					if ($resultUnit->rowCount()!=1) {
						print "<div class='error'>" ;
							print __($guid, "The selected record does not exist, or you do not have access to it.") ;
						print "</div>" ;
					}
					else {
						$rowUnit=$resultUnit->fetch() ;
						
						print "<h2>" ;
							print $rowUnit["name"] ;
						print "</h2>" ;
						print "<p>" ;
							print __($guid, "This page shows an overview of the unit that the current lesson belongs to, including all the outcomes, resources, lessons and chats for the classes you have access to.") ;
						print "</p>" ;
						
						//Set up where and data array for getting items from accessible planners
						if ($highestAction=="Lesson Planner_viewEditAllClasses" OR $highestAction=="Lesson Planner_viewAllEditMyClasses") {
							$dataPlanners=array("gibbonUnitID"=>$row["gibbonUnitID"], "gibbonCourseClassID"=>$row["gibbonCourseClassID"]) ;
							$sqlPlanners="SELECT * FROM gibbonPlannerEntry WHERE gibbonUnitID=:gibbonUnitID AND gibbonCourseClassID=:gibbonCourseClassID" ; 
						}
						else if ($highestAction=="Lesson Planner_viewMyClasses") {
							$dataPlanners=array("gibbonUnitID1"=>$row["gibbonUnitID"], "gibbonPersonID1"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonCourseClassID1"=>$row["gibbonCourseClassID"], "gibbonUnitID2"=>$row["gibbonUnitID"], "gibbonPersonID2"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonCourseClassID2"=>$row["gibbonCourseClassID"]) ;
							$sqlPlanners="(SELECT gibbonPlannerEntry.* FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonUnitID=:gibbonUnitID1 AND gibbonPersonID=:gibbonPersonID1 AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID1 AND role='Teacher')
							UNION
							(SELECT gibbonPlannerEntry.* FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonUnitID=:gibbonUnitID2 AND gibbonPersonID=:gibbonPersonID2 AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID2 AND role='Student' AND viewableStudents='Y')" ; 
						}
						else if ($highestAction=="Lesson Planner_viewMyChildrensClasses") {
							$dataPlanners=array("gibbonUnitID"=>$row["gibbonUnitID"], "gibbonCourseClassID"=>$row["gibbonCourseClassID"]) ;
							$sqlPlanners="SELECT * FROM gibbonPlannerEntry WHERE gibbonUnitID=:gibbonUnitID AND gibbonCourseClassID=:gibbonCourseClassID AND viewableParents='Y'" ; 
						}
						try {
							$resultPlanners=$connection2->prepare($sqlPlanners);
							$resultPlanners->execute($dataPlanners);
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						
						if ($resultPlanners->rowCount()<1) {
							print "<div class='error'>" ;
								print __($guid, "There are no records to display.") ;
							print "</div>" ;
						}
						else {
							$dataMulti=array() ;
							$whereMulti="(" ;
							$multiCount=0 ;
							while ($rowPlanners=$resultPlanners->fetch()) {
								$dataMulti["gibbonPlannerEntryID" . $multiCount]=$rowPlanners["gibbonPlannerEntryID"] ;
								$whereMulti.="gibbonPlannerEntryID=:gibbonPlannerEntryID" . $multiCount . " OR " ;
								$multiCount++ ;
							}
							$whereMulti=substr($whereMulti,0,-4) . ")" ;
							?>
							<script type='text/javascript'>
								$(function() {
									$( "#tabs" ).tabs({
										ajaxOptions: {
											error: function( xhr, status, index, anchor ) {
												$( anchor.hash ).html(
													"Couldn't load this tab." );
											}
										}
									});
								});
							</script>
							<?php
					
							print "<div id='tabs' style='margin: 20px 0'>" ;
								//Tab links
								print "<ul>" ;
									print "<li><a href='#tabs1'>" . __($guid, 'Unit Overview') . "</a></li>" ;
									print "<li><a href='#tabs2'>" . __($guid, 'Outcomes') . "</a></li>" ;
									print "<li><a href='#tabs3'>" . __($guid, 'Lessons') . "</a></li>" ;
									print "<li><a href='#tabs4'>" . __($guid, 'Resources') . "</a></li>" ;
								print "</ul>" ;
						
								//Tab content
								//UNIT OVERVIEW
								print "<div id='tabs1'>" ;
									if ($highestAction=="Lesson Planner_viewEditAllClasses" OR $highestAction=="Lesson Planner_viewAllEditMyClasses") {
										print $rowUnit["details"] ;
									}
									else {
										print "<p>" . $rowUnit["description"] . "</p>" ;
									}
								print "</div>" ;
								//OUTCOMES
								print "<div id='tabs2'>" ;
									try {
										$dataOutcomes=$dataMulti ;
										$dataOutcomes["gibbonUnitID"]=$row["gibbonUnitID"] ;
										$sqlOutcomes="(SELECT gibbonOutcome.*, gibbonPlannerEntryOutcome.content FROM gibbonPlannerEntryOutcome JOIN gibbonOutcome ON (gibbonPlannerEntryOutcome.gibbonOutcomeID=gibbonOutcome.gibbonOutcomeID) WHERE $whereMulti AND active='Y')
										UNION
										(SELECT gibbonOutcome.*, gibbonUnitOutcome.content FROM gibbonUnitOutcome JOIN gibbonOutcome ON (gibbonUnitOutcome.gibbonOutcomeID=gibbonOutcome.gibbonOutcomeID) WHERE gibbonUnitID=:gibbonUnitID AND active='Y')
										ORDER BY scope DESC, name" ; 
										$resultOutcomes=$connection2->prepare($sqlOutcomes);
										$resultOutcomes->execute($dataOutcomes);
									}
									catch(PDOException $e) { 
										print "<div class='error'>" . $e->getMessage() . "</div>" ; 
									}
									if ($resultOutcomes->rowCount()<1) {
										print "<div class='error'>" ;
											print __($guid, "There are no records to display.") ;
										print "</div>" ;
									}
									else {
										print "<table cellspacing='0' style='width: 100%'>" ;
											print "<tr class='head'>" ;
												print "<th>" ;
													print __($guid, "Scope") ;
												print "</th>" ;
												print "<th>" ;
													print __($guid, "Category") ;
												print "</th>" ;
												print "<th>" ;
													print __($guid, "Name") ;
												print "</th>" ;
												print "<th>" ;
													print __($guid, "Year Groups") ;
												print "</th>" ;
												print "<th>" ;
													print __($guid, "Actions") ;
												print "</th>" ;
											print "</tr>" ;
								
											$count=0;
											$rowNum="odd" ;
											while ($rowOutcomes=$resultOutcomes->fetch()) {
												if ($count%2==0) {
													$rowNum="even" ;
												}
												else {
													$rowNum="odd" ;
												}
									
												//COLOR ROW BY STATUS!
												print "<tr class=$rowNum>" ;
													print "<td>" ;
														print "<b>" . $rowOutcomes["scope"] . "</b><br/>" ;
														if ($rowOutcomes["scope"]=="Learning Area" AND $rowOutcomes["gibbonDepartmentID"]!="") {
															try {
																$dataLearningArea=array("gibbonDepartmentID"=>$rowOutcomes["gibbonDepartmentID"]); 
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
														print "<b>" . $rowOutcomes["category"] . "</b><br/>" ;
													print "</td>" ;
													print "<td>" ;
														print "<b>" . $rowOutcomes["nameShort"] . "</b><br/>" ;
														print "<span style='font-size: 75%; font-style: italic'>" . $rowOutcomes["name"] . "</span>" ;
													print "</td>" ;
													print "<td>" ;
														print getYearGroupsFromIDList($guid, $connection2, $rowOutcomes["gibbonYearGroupIDList"]) ;
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
														if ($rowOutcomes["content"]!="") {
															print "<a title='" . __($guid, 'View Description') . "' class='show_hide-$count' onclick='false' href='#'><img style='padding-left: 0px' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/page_down.png' alt='" . __($guid, 'Show Comment') . "' onclick='return false;' /></a>" ;
														}
													print "</td>" ;
												print "</tr>" ;
												if ($rowOutcomes["content"]!="") {
													print "<tr class='description-$count' id='description-$count'>" ;
														print "<td colspan=6>" ;
															print $rowOutcomes["content"] ;
														print "</td>" ;
													print "</tr>" ;
												}
												print "</tr>" ;
									
												$count++ ;
											}
										print "</table>" ;
									}
								print "</div>" ;
								//LESSONS
								print "<div id='tabs3'>" ;
									$resourceContents="" ;
									try {
										$dataLessons=$dataMulti ;
										$sqlLessons="SELECT * FROM gibbonPlannerEntry WHERE $whereMulti" ;
										$resultLessons=$connection2->prepare($sqlLessons) ;
										$resultLessons->execute($dataLessons) ;
									}
									catch(PDOException $e) { 
										print "<div class='error'>" . $e->getMessage() . "</div>" ;
									}	
							
									if ($resultLessons->rowCount()<1) {
										print "<div class='warning'>" ;
										print __($guid, "There are no records to display.") ;
										print "</div>" ;
									}
									else {
										while ($rowLessons=$resultLessons->fetch()) {
											print "<h3>" . $rowLessons["name"] . "</h3>" ;
											print $rowLessons["description"] ;
											$resourceContents.=$rowLessons["description"] ;
											if ($rowLessons["teachersNotes"]!="" AND ($highestAction=="Lesson Planner_viewAllEditMyClasses" OR $highestAction=="Lesson Planner_viewEditAllClasses")) {
												print "<div style='background-color: #F6CECB; padding: 0px 3px 10px 3px; width: 98%; text-align: justify; border-bottom: 1px solid #ddd'><p style='margin-bottom: 0px'><b>" . __($guid, "Teacher's Notes") . ":</b></p> " . $rowLessons["teachersNotes"] . "</div>" ;
												$resourceContents.=$rowLessons["teachersNotes"] ;
											}
								
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
												print "<b>" . __($guid, 'Type') . "</b>: " . $rowBlock["type"] . "<br/>" ;
												print "<b>" . __($guid, 'Length') . "</b>: " . $rowBlock["length"] . "<br/>" ;
												print "<b>" . __($guid, 'Contents') . "</b>: " . $rowBlock["contents"] . "<br/>" ;
												$resourceContents.=$rowBlock["contents"] ;
												if ($rowBlock["teachersNotes"]!="" AND ($highestAction=="Lesson Planner_viewAllEditMyClasses" OR $highestAction=="Lesson Planner_viewEditAllClasses")) {
													print "<div style='background-color: #F6CECB; padding: 0px 3px 10px 3px; width: 98%; text-align: justify; border-bottom: 1px solid #ddd'><p style='margin-bottom: 0px'><b>" . __($guid, "Teacher's Notes") . ":</b></p> " . $rowBlock["teachersNotes"] . "</div>" ;
													$resourceContents.=$rowBlock["teachersNotes"] ;
												}
												print "</p>" ;
											}
											
											//Print chats
											print "<h5 style='font-size: 85%'>" . __($guid, "Chat") . "</h5>" ;
											print "<style type=\"text/css\">" ;
												print "table.chatbox { width: 90%!important }" ;
											print "</style>" ;
											print getThread($guid, $connection2, $rowLessons["gibbonPlannerEntryID"], NULL, 0, NULL, NULL, NULL, NULL, NULL, $class[1], $_SESSION[$guid]["gibbonPersonID"], "Teacher", FALSE, TRUE) ;
										}
									}
								print "</div>" ;
								//RESOURCES
								print "<div id='tabs4'>" ;
									$noReosurces=TRUE ;
									
									//Links
									$links="" ;
									$linksArray=array() ;
									$linksCount=0;
									$dom=new DOMDocument;
									$dom->loadHTML($resourceContents);
									foreach ($dom->getElementsByTagName('a') as $node) {
										if ($node->nodeValue!="") {
											$linksArray[$linksCount]="<li><a href='" .$node->getAttribute("href") . "'>" . $node->nodeValue . "</a></li>" ;
											$linksCount++ ;
										}
									}
									
									$linksArray=array_unique($linksArray) ;
									natcasesort($linksArray) ;
									
									foreach ($linksArray AS $link) {
										$links.=$link ;
									}
									
									if ($links!="" ) {
										print "<h2>" ;
											print "Links" ;
										print "</h2>" ;
										print "<ul>" ;
											print $links ;
										print "</ul>" ;
										$noReosurces=FALSE ;
									}
									
									//Images
									$images="" ;
									$imagesArray=array() ;
									$imagesCount=0;
									$dom2=new DOMDocument;
									$dom2->loadHTML($resourceContents);
									foreach ($dom2->getElementsByTagName('img') as $node) {
										if ($node->getAttribute("src")!="") {
											$imagesArray[$imagesCount]="<img class='resource' style='margin: 10px 0; max-width: 560px' src='" . $node->getAttribute("src") . "'/><br/>" ;
											$imagesCount++ ;
										}
									}
									
									$imagesArray=array_unique($imagesArray) ;
									natcasesort($imagesArray) ;
									
									foreach ($imagesArray AS $image) {
										$images.=$image ;
									}
									
									if ($images!="" ) {
										print "<h2>" ;
											print "Images" ;
										print "</h2>" ;
										print $images ;
										$noReosurces=FALSE ;
									}
									
									//Embeds
									$embeds="" ;
									$embedsArray=array() ;
									$embedsCount=0;
									$dom2=new DOMDocument;
									$dom2->loadHTML($resourceContents);
									foreach ($dom2->getElementsByTagName('iframe') as $node) {
										if ($node->getAttribute("src")!="") {
											$embedsArray[$embedsCount]="<iframe style='max-width: 560px' width='" . $node->getAttribute("width") . "' height='" . $node->getAttribute("height") . "' src='" . $node->getAttribute("src") . "' frameborder='" . $node->getAttribute("frameborder") . "'></iframe>" ;
											$embedsCount++ ;
										}
									}
									
									$embedsArray=array_unique($embedsArray) ;
									natcasesort($embedsArray) ;
									
									foreach ($embedsArray AS $embed) {
										$embeds.=$embed ."<br/><br/>" ;
									}
									
									if ($embeds!="" ) {
										print "<h2>" ;
											print "Embeds" ;
										print "</h2>" ;
										print $embeds ;
										$noReosurces=FALSE ;
									}
									
									//No resources!
									if ($noReosurces) {
										print "<div class='error'>" ;
											print __($guid, "There are no records to display.") ;
										print "</div>" ;
									}
								print "</div>" ;
							print "</div>" ;
						}
					}
				}
			}		
		}
	} 
}		
?>