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

if (isActionAccessible($guid, $connection2, "/modules/Planner/planner_view_full.php")==FALSE) {
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
				$date=dateConvert($_GET["dateHuman"]) ;
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
		$gibbonPersonID=NULL;
		
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
			$data=array() ;
			$gibbonPersonID=NULL ;
			if (isset($_GET["search"])) {
				$gibbonPersonID=$_GET["search"] ;
			}
			if ($highestAction=="Lesson Planner_viewMyChildrensClasses") {
				if ($gibbonPersonID=="") {
					print "<div class='warning'>" ;
						print "Lesson cannot be displayed due to a system error." ;
					print "</div>" ;
					
				}
				else {
					try {
						$dataChild=array("gibbonPersonID"=>$gibbonPersonID, "gibbonPersonID2"=>$_SESSION[$guid]["gibbonPersonID"]); 
						$sqlChild="SELECT * FROM gibbonFamilyChild JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonFamilyChild.gibbonPersonID=:gibbonPersonID AND gibbonFamilyAdult.gibbonPersonID=:gibbonPersonID2 AND childDataAccess='Y'" ;
						$resultChild=$connection2->prepare($sqlChild);
						$resultChild->execute($dataChild);
					}
					catch(PDOException $e) { 
						print "<div class='error'>" . $e->getMessage() . "</div>" ; 
					}
					
					if ($resultChild->rowCount()<1) {
						print "<div class='error'>" ;
						print "You do not have access to the specified student." ;
						print "</div>" ;
						
					}
					else {
						$sql="(SELECT gibbonPlannerEntry.gibbonPlannerEntryID, gibbonCourseClass.gibbonCourseClassID, gibbonUnitID, gibbonHookID, gibbonPlannerEntry.gibbonCourseClassID, gibbonPlannerEntry.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, date, timeStart, timeEnd, summary, gibbonPlannerEntry.description, teachersNotes, homework, homeworkDueDateTime, homeworkDetails, viewableStudents, viewableParents, role, homeworkSubmission, homeworkSubmissionDateOpen, homeworkSubmissionDrafts, homeworkSubmissionType, homeworkSubmissionRequired, twitterSearch FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourseClassPerson.gibbonPersonID=$gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left' AND gibbonPlannerEntry.gibbonPlannerEntryID=$gibbonPlannerEntryID) UNION (SELECT gibbonPlannerEntry.gibbonPlannerEntryID, gibbonCourseClass.gibbonCourseClassID, gibbonUnitID, gibbonHookID, gibbonPlannerEntry.gibbonCourseClassID, gibbonPlannerEntry.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, date, timeStart, timeEnd, summary, gibbonPlannerEntry.description, teachersNotes, homework, homeworkDueDateTime, homeworkDetails, viewableStudents, viewableParents, role, homeworkSubmission, homeworkSubmissionDateOpen, homeworkSubmissionDrafts, homeworkSubmissionType, homeworkSubmissionRequired, twitterSearch FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonPlannerEntryGuest ON (gibbonPlannerEntryGuest.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE date='$date' AND gibbonPlannerEntryGuest.gibbonPersonID=$gibbonPersonID AND gibbonPlannerEntry.gibbonPlannerEntryID=$gibbonPlannerEntryID) ORDER BY date, timeStart" ; 
					}
				}
			}
			else if ($highestAction=="Lesson Planner_viewMyClasses") {
				$data=array("date"=>$date, "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonPlannerEntryID"=>$gibbonPlannerEntryID) ;
				$sql="(SELECT gibbonPlannerEntry.gibbonPlannerEntryID, gibbonCourseClass.gibbonCourseClassID, gibbonUnitID, gibbonHookID, gibbonPlannerEntry.gibbonCourseClassID, gibbonPlannerEntry.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, date, timeStart, timeEnd, summary, gibbonPlannerEntry.description, teachersNotes, homework, homeworkDueDateTime, homeworkDetails, viewableStudents, viewableParents, role, homeworkSubmission, homeworkSubmissionDateOpen, homeworkSubmissionDrafts, homeworkSubmissionType, homeworkSubmissionRequired, twitterSearch FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left' AND gibbonPlannerEntry.gibbonPlannerEntryID=$gibbonPlannerEntryID) UNION (SELECT gibbonPlannerEntry.gibbonPlannerEntryID, gibbonCourseClass.gibbonCourseClassID, gibbonUnitID, gibbonHookID, gibbonPlannerEntry.gibbonCourseClassID, gibbonPlannerEntry.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, date, timeStart, timeEnd, summary, gibbonPlannerEntry.description, teachersNotes, homework, homeworkDueDateTime, homeworkDetails, viewableStudents, viewableParents, role, homeworkSubmission, homeworkSubmissionDateOpen, homeworkSubmissionDrafts, homeworkSubmissionType, homeworkSubmissionRequired, twitterSearch FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonPlannerEntryGuest ON (gibbonPlannerEntryGuest.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE date=:date AND gibbonPlannerEntryGuest.gibbonPersonID=" . $_SESSION[$guid]["gibbonPersonID"] . " AND gibbonPlannerEntry.gibbonPlannerEntryID=:gibbonPlannerEntryID) ORDER BY date, timeStart" ; 
			}
			else if ($highestAction=="Lesson Planner_viewEditAllClasses" OR $highestAction=="Lesson Planner_viewAllEditMyClasses") {
				$sql="SELECT gibbonCourse.gibbonCourseID, gibbonPlannerEntry.gibbonPlannerEntryID, gibbonCourseClass.gibbonCourseClassID, gibbonUnitID, gibbonHookID, gibbonPlannerEntry.gibbonCourseClassID, gibbonPlannerEntry.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, date, timeStart, timeEnd, summary, gibbonPlannerEntry.description, teachersNotes, homework, homeworkDueDateTime, homeworkDetails, viewableStudents, viewableParents, 'Teacher' AS role, homeworkSubmission, homeworkSubmissionDateOpen, homeworkSubmissionDrafts, homeworkSubmissionType, homeworkSubmissionRequired, twitterSearch, gibbonDepartmentID FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonPlannerEntry.gibbonPlannerEntryID=$gibbonPlannerEntryID ORDER BY date, timeStart" ; 
				$teacher=FALSE ;
				try {
					$dataTeacher=array("gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonPlannerEntryID"=>$gibbonPlannerEntryID, "gibbonPersonID2"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonPlannerEntryID2"=>$gibbonPlannerEntryID, "date2"=>$date); 
					$sqlTeacher="(SELECT gibbonPlannerEntry.gibbonPlannerEntryID, gibbonCourseClass.gibbonCourseClassID, gibbonUnitID, gibbonHookID, gibbonPlannerEntry.gibbonCourseClassID, gibbonPlannerEntry.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, date, timeStart, timeEnd, summary, gibbonPlannerEntry.description, teachersNotes, homework, homeworkDueDateTime, homeworkDetails, viewableStudents, viewableParents, role, homeworkSubmission, homeworkSubmissionDateOpen, homeworkSubmissionDrafts, homeworkSubmissionType, homeworkSubmissionRequired, twitterSearch FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left' AND gibbonPlannerEntry.gibbonPlannerEntryID=:gibbonPlannerEntryID) UNION (SELECT gibbonPlannerEntry.gibbonPlannerEntryID, gibbonCourseClass.gibbonCourseClassID, gibbonUnitID, gibbonHookID, gibbonPlannerEntry.gibbonCourseClassID, gibbonPlannerEntry.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, date, timeStart, timeEnd, summary, gibbonPlannerEntry.description, teachersNotes, homework, homeworkDueDateTime, homeworkDetails, viewableStudents, viewableParents, role, homeworkSubmission, homeworkSubmissionDateOpen, homeworkSubmissionDrafts, homeworkSubmissionType, homeworkSubmissionRequired, twitterSearch FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonPlannerEntryGuest ON (gibbonPlannerEntryGuest.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE date=:date2 AND gibbonPlannerEntryGuest.gibbonPersonID=:gibbonPersonID2 AND gibbonPlannerEntry.gibbonPlannerEntryID=:gibbonPlannerEntryID2) ORDER BY date, timeStart" ; 
					$resultTeacher=$connection2->prepare($sqlTeacher);
					$resultTeacher->execute($dataTeacher);
				}
				catch(PDOException $e) { }
				if ($resultTeacher->rowCount()>0) {
					$teacher=TRUE ;
				}
			}
			
			if (isset($sql)) {		
				try {
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}		
				if ($result->rowCount()!=1) {
					print "<div class='warning'>" ;
						print "Lesson does not exist or you do not have access to it." ;
					print "</div>" ;
				}
				else {
					$row=$result->fetch() ;
					$gibbonDepartmentID=NULL ;
					if (isset($row["gibbonDepartmentID"])) {
						$gibbonDepartmentID=$row["gibbonDepartmentID"] ;
					}
				
					//CHECK IF UNIT IS GIBBON OR HOOKED
					if ($row["gibbonHookID"]==NULL) {
						$hooked=FALSE ;
						$gibbonUnitID=$row["gibbonUnitID"]; 
					
						//Get gibbonUnitClassID
						try {
							$dataUnitClass=array("gibbonCourseClassID"=>$row["gibbonCourseClassID"], "gibbonUnitID"=>$gibbonUnitID); 
							$sqlUnitClass="SELECT gibbonUnitClassID FROM gibbonUnitClass WHERE gibbonCourseClassID=:gibbonCourseClassID AND gibbonUnitID=:gibbonUnitID" ;
							$resultUnitClass=$connection2->prepare($sqlUnitClass);
							$resultUnitClass->execute($dataUnitClass);
						}
						catch(PDOException $e) {}
						if ($resultUnitClass->rowCount()==1) {
							$rowUnitClass=$resultUnitClass->fetch() ;
							$gibbonUnitClassID=$rowUnitClass["gibbonUnitClassID"] ;
						}
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
					
						//Get gibbonUnitClassID
						try {
							$dataUnitClass=array("gibbonCourseClassID"=>$row["gibbonCourseClassID"], "gibbonUnitID"=>$gibbonUnitIDToken); 
							$sqlUnitClass="SELECT " . $hookOptions["classLinkIDField"] . " FROM " . $hookOptions["classLinkTable"] . " WHERE " . $hookOptions["classLinkJoinFieldClass"] . "=:gibbonCourseClassID AND " . $hookOptions["classLinkJoinFieldUnit"] . "=:gibbonUnitID" ;
							$resultUnitClass=$connection2->prepare($sqlUnitClass);
							$resultUnitClass->execute($dataUnitClass);
						}
						catch(PDOException $e) { print $e->getMessage() ;}
						if ($resultUnitClass->rowCount()==1) {
							$rowUnitClass=$resultUnitClass->fetch() ;
							$gibbonUnitClassID=$rowUnitClass[$hookOptions["classLinkIDField"]] ;
						}
					}
				
					$extra="" ;
					if ($viewBy=="class") {
						$extra=$row["course"] . "." . $row["class"] ;
					}
					else {
						$extra=dateConvertBack($date) ;
					}
				
					$params="&gibbonPlannerEntryID=$gibbonPlannerEntryID" ;
					if ($date!="") {
						$params=$params."&date=" . $_GET["date"] ;
					}
					if ($viewBy!="") {
						$params=$params."&viewBy=" . $_GET["viewBy"] ;
					}
					if ($gibbonCourseClassID!="") {
						$params=$params."&gibbonCourseClassID=" . $_GET["gibbonCourseClassID"] ;
					}
					$params=$params."&subView=$subView" ;
					
					print "<div class='trail'>" ;
					print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>Home</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/planner.php$params'>Planner $extra</a> > </div><div class='trailEnd'>View Lesson Plan</div>" ;
					print "</div>" ;
				
					if (isset($_GET["updateReturn"])) { $updateReturn=$_GET["updateReturn"] ; } else { $updateReturn="" ; }
					$updateReturnMessage ="" ;
					$class="error" ;
					if (!($updateReturn=="")) {
						if ($updateReturn=="fail0") {
							$updateReturnMessage ="Update failed because you do not have access to this action." ;	
						}
						else if ($updateReturn=="fail1") {
							$updateReturnMessage ="Update failed because a required parameter was not set." ;	
						}
						else if ($updateReturn=="fail2") {
							$updateReturnMessage ="Update failed due to a database error." ;	
						}
						else if ($updateReturn=="fail3") {
							$updateReturnMessage ="Part of the update failed due to a database error." ;	
						}
						else if ($updateReturn=="fail4") {
							$updateReturnMessage ="Update failed because specified date is in the future." ;	
						}
						else if ($updateReturn=="fail5") {
							$updateReturnMessage ="Update failed because school is closed on the specified date." ;	
						}
						else if ($updateReturn=="fail6") {
							$updateReturnMessage ="Update failed due to a problem with your link or file." ;	
						}
						else if ($updateReturn=="success0") {
							$updateReturnMessage ="Update was successful." ;	
							$class="success" ;
						}
						print "<div class='$class'>" ;
							print $updateReturnMessage;
						print "</div>" ;
					} 
				
					if (isset($_GET["postReturn"])) { $postReturn=$_GET["postReturn"] ; } else { $postReturn="" ; }
					$postReturnMessage ="" ;
					$class="error" ;
					if (!($postReturn=="")) {
						if ($postReturn=="fail0") {
							$postReturnMessage ="Post failed because you do not have access to this action." ;	
						}
						else if ($postReturn=="fail1") {
							$postReturnMessage ="Post failed because a required parameter was not set." ;	
						}
						else if ($postReturn=="fail2") {
							$postReturnMessage ="Post failed due to a database error." ;	
						}
						else if ($postReturn=="success0") {
							$postReturnMessage ="Post was successful." ;	
							$class="success" ;
						}
						print "<div class='$class'>" ;
							print $postReturnMessage;
						print "</div>" ;
					} 
				
					if (isset($_GET["deleteReturn"])) { $deleteReturn=$_GET["deleteReturn"] ; } else { $deleteReturn="" ; }
					$deleteReturnMessage ="" ;
					$class="error" ;
					if (!($deleteReturn=="")) {
						if ($deleteReturn=="fail0") {
							$deleteReturnMessage ="Delete failed because you do not have access to this action." ;	
						}
						else if ($deleteReturn=="fail1") {
							$deleteReturnMessage ="Delete failed because a required parameter was not set." ;	
						}
						else if ($deleteReturn=="fail2") {
							$deleteReturnMessage ="Delete failed due to a database error." ;	
						}
						else if ($deleteReturn=="success0") {
							$deleteReturnMessage ="Delete was successful." ;	
							$class="success" ;
						}
						print "<div class='$class'>" ;
							print $deleteReturnMessage;
						print "</div>" ;
					} 
		

					if ($gibbonCourseClassID=="") {
						$gibbonCourseClassID=$row["gibbonCourseClassID"] ;
					}
					if (($row["role"]=="Student" AND $row["viewableStudents"]=="N") AND ($highestAction=="Lesson Planner_viewMyChildrensClasses" AND $row["viewableParents"]=="N")) {
						print "<div class='warning'>" ;
							print "Lesson does not exist or you do not have access to it." ;
						print "</div>" ;
					}
					else {
						print "<div style='height:50px'>" ;
							print "<h2>" ;
								print $row["name"] . "<br>" ;
								$unit=getUnit($connection2, $row["gibbonUnitID"], $row["gibbonHookID"], $row["gibbonCourseClassID"]) ;
								if (isset($unit[0])) {
									if ($unit[0]!="") {
										if ($unit[1]!="") {
											print "<div style='font-weight: normal; font-style: italic; font-size: 60%; margin-top: -5px'>$unit[1] Unit: " . $unit[0] . "</div>" ;
											$unitType=$unit[1] ;
										}
										else {
											print "<div style='font-weight: normal; font-style: italic; font-size: 60%; margin-top: -5px'>Unit: " . $unit[0] . "</div>" ;
										}
									}
								}
							print "</h2>" ;
							print "<div style='float: right; width: 35%; padding-right: 3px; margin-top: -52px'>" ;
								if (strstr($row["role"],"Guest")==FALSE) {
									//Links to previous and next lessons
									print "<p style='text-align: right; margin-top: 10px'>" ;
										print "<span style='font-size: 85%'><i>For this class:</i></span><br/>" ;
										try {
											if ($row["role"]=="Teacher") {
												$dataPrevious=array("gibbonCourseClassID"=>$row["gibbonCourseClassID"], "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"], "date1"=>$row["date"], "date2"=>$row["date"], "timeStart"=>$row["timeStart"]); 
												$sqlPrevious="SELECT gibbonPlannerEntry.gibbonCourseClassID, gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, timeStart, timeEnd, viewableStudents, viewableParents, homework, role FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonPlannerEntry.gibbonCourseClassID=:gibbonCourseClassID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left' AND (date<:date1 OR (date=:date2 AND timeStart<:timeStart)) ORDER BY date DESC, timeStart DESC" ; 
											}
											else {
												if ($highestAction=="Lesson Planner_viewMyChildrensClasses") {
													$dataPrevious=array("gibbonCourseClassID"=>$row["gibbonCourseClassID"], "gibbonPersonID"=>$gibbonPersonID, "date1"=>$row["date"], "date2"=>$row["date"], "timeStart"=>$row["timeStart"]); 
													$sqlPrevious="SELECT gibbonPlannerEntry.gibbonCourseClassID, gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, timeStart, timeEnd, viewableStudents, viewableParents, homework, role FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonPlannerEntry.gibbonCourseClassID=:gibbonCourseClassID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left' AND (date<:date1 OR (date=:date2 AND timeStart<:timeStart)) AND viewableParents='Y' ORDER BY date DESC, timeStart DESC" ; 
												}
												else {
													$dataPrevious=array("gibbonCourseClassID"=>$row["gibbonCourseClassID"], "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"], "date1"=>$row["date"], "date2"=>$row["date"], "timeStart"=>$row["timeStart"]); 
													$sqlPrevious="SELECT gibbonPlannerEntry.gibbonCourseClassID, gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, timeStart, timeEnd, viewableStudents, viewableParents, homework, role FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonPlannerEntry.gibbonCourseClassID=:gibbonCourseClassID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left' AND (date<:date1 OR (date=:date2 AND timeStart<:timeStart)) AND viewableStudents='Y' ORDER BY date DESC, timeStart DESC" ; 
												}
											}
											$resultPrevious=$connection2->prepare($sqlPrevious);
											$resultPrevious->execute($dataPrevious);
										}
										catch(PDOException $e) { 
											print "<div class='error'>" . $e->getMessage() . "</div>" ; 
										}
										if ($resultPrevious->rowCount()>0) {
											$rowPrevious=$resultPrevious->fetch() ;
											print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Planner/planner_view_full.php&search=$gibbonPersonID&gibbonPlannerEntryID=" . $rowPrevious["gibbonPlannerEntryID"] . "&viewBy=$viewBy&subView=$subView&gibbonCourseClassID=" . $rowPrevious["gibbonCourseClassID"] . "&date=$date'>Previous Lesson</a>" ;
										}
										else {
											print "Previous Lesson" ;
										}
									
										print " | " ;
									
									
										try {
											if ($row["role"]=="Teacher") {
												$dataNext=array("gibbonCourseClassID"=>$row["gibbonCourseClassID"], "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"], "date1"=>$row["date"], "date2"=>$row["date"], "timeStart"=>$row["timeStart"]); 
												$sqlNext="SELECT gibbonPlannerEntry.gibbonCourseClassID, gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, timeStart, timeEnd, viewableStudents, viewableParents, homework, role FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonPlannerEntry.gibbonCourseClassID=:gibbonCourseClassID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left' AND (date>:date1 OR (date=:date2 AND timeStart>:timeStart)) ORDER BY date, timeStart" ; 
											}
											else {
												if ($highestAction=="Lesson Planner_viewMyChildrensClasses") {
													$dataNext=array("gibbonCourseClassID"=>$row["gibbonCourseClassID"], "gibbonPersonID"=>$gibbonPersonID, "date1"=>$row["date"], "date2"=>$row["date"], "timeStart"=>$row["timeStart"]); 
													$sqlNext="SELECT gibbonPlannerEntry.gibbonCourseClassID, gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, timeStart, timeEnd, viewableStudents, viewableParents, homework, role FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonPlannerEntry.gibbonCourseClassID=:gibbonCourseClassID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left' AND (date>:date1 OR (date=:date2 AND timeStart>:timeStart)) AND viewableParents='Y' ORDER BY date, timeStart" ; 
												}
												else {
													$dataNext=array("gibbonCourseClassID"=>$row["gibbonCourseClassID"], "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"], "date1"=>$row["date"], "date2"=>$row["date"], "timeStart"=>$row["timeStart"]); 
													$sqlNext="SELECT gibbonPlannerEntry.gibbonCourseClassID, gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, timeStart, timeEnd, viewableStudents, viewableParents, homework, role FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonPlannerEntry.gibbonCourseClassID=:gibbonCourseClassID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left' AND (date>:date1 OR (date=:date2 AND timeStart>:timeStart)) AND viewableStudents='Y' ORDER BY date, timeStart" ; 
												}
											}
											$resultNext=$connection2->prepare($sqlNext);
											$resultNext->execute($dataNext);
										}
										catch(PDOException $e) { 
											print "<div class='error'>" . $e->getMessage() . "</div>" ; 
										}
										if ($resultNext->rowCount()>0) {
											$rowNext=$resultNext->fetch() ;
											print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Planner/planner_view_full.php&search=$gibbonPersonID&gibbonPlannerEntryID=" . $rowNext["gibbonPlannerEntryID"] . "&viewBy=$viewBy&subView=$subView&gibbonCourseClassID=" . $rowNext["gibbonCourseClassID"] . "&date=$date'>Next Lesson</a>" ;
										}
										else {
											print "Next Lesson" ;
										}
									print "</p>" ;
								}				
							print "</div>" ;
						print "</div>" ;
					
						if ($row["role"]=="Teacher") {
							print "<div class='linkTop'>" ;
								print "<tr>" ;
									print "<td colspan=3>" ;
										print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Planner/planner_edit.php&viewBy=$viewBy&gibbonCourseClassID=$gibbonCourseClassID&gibbonPlannerEntryID=$gibbonPlannerEntryID&date=$date&subView=$subView'>Edit Lesson<img style='margin: 0 0 -4px 3px' title='Edit Lesson' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> | " ;
										try {
											$dataMarkbook=array("gibbonCourseClassID"=>$row["gibbonCourseClassID"], "gibbonPlannerEntryID"=>$gibbonPlannerEntryID); 
											$sqlMarkbook="SELECT * FROM gibbonMarkbookColumn WHERE gibbonCourseClassID=:gibbonCourseClassID AND gibbonPlannerEntryID=:gibbonPlannerEntryID" ;
											$resultMarkbook=$connection2->prepare($sqlMarkbook);
											$resultMarkbook->execute($dataMarkbook);
										}
										catch(PDOException $e) { 
											print "<div class='error'>" . $e->getMessage() . "</div>" ; 
										}
										if ($resultMarkbook->rowCount()==1) {
											$rowMarkbook=$resultMarkbook->fetch() ;
											print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Markbook/markbook_edit_data.php&gibbonCourseClassID=$gibbonCourseClassID&gibbonMarkbookColumnID=" . $rowMarkbook["gibbonMarkbookColumnID"] . "'>Linked Markbook<img style='margin: 0 0 -4px 3px' title='View Details' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/plus.png'/></a> | " ;
										}
										print "<input title=\"Includes student data & teacher's notes\" type='checkbox' name='confidentialPlan' class='confidentialPlan' value='Yes' />" ;
										print "<span title=\"Includes student data & teacher's notes\" style='font-size: 85%; font-weight: normal; font-style: italic'> Show Confidential Data</span>" ;
									print "</td>" ;
								print "</tr>" ;
							print "</div>" ;
						}
						print "<table class='smallIntBorder' cellspacing='0' style='width: 100%;'>" ;
							print "<tr>" ;
								print "<td style='width: 33%; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>Class</span><br/>" ;
									print $row["course"] . "." . $row["class"] ;
								print "</td>" ;
								print "<td style='width: 33%; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>Date</span><br/>" ;
									print dateConvertBack($row["date"]) ;
								print "</td>" ;
								print "<td style='width: 33%; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>Time</span><br/>" ;
									if ($row["timeStart"]!="" AND $row["timeEnd"]!="") {
										print substr($row["timeStart"],0,5) . "-" . substr($row["timeEnd"],0,5) ;
									}
								print "</td>" ;
							print "</tr>" ;
							if ($row["summary"]!="") {
								print "<tr>" ;
									print "<td style='padding-top: 5px; width: 33%; vertical-align: top' colspan=3>" ;
										print "<span style='font-size: 115%; font-weight: bold'>Summary</span><br/>" ;
										print $row["summary"] ;
									print "</td>" ;
								print "</tr>" ;
							}
						print "</table>" ;
					
						print "<h2 style='padding-top: 30px'>Lesson Content</h2>" ;
					
						print "<table class='smallIntBorder' cellspacing='0' style='width: 100%;'>" ;
							//LESSON CONTENTS
							//Get Smart Blocks
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
							if ($row["description"]!="") { 
								print "<tr>" ;
									print "<td style='text-align: justify; padding-top: 5px; width: 33%; vertical-align: top' colspan=3>" ;
										print $row["description"] ;
									print "</td>" ;
								print "</tr>" ;
							}
							if ($row["gibbonUnitID"]!="") {
								print "<tr>" ;
									print "<td style='text-align: justify; padding-top: 5px; width: 33%; vertical-align: top' colspan=3>" ;
										if ($row["role"]=="Teacher" AND $teacher==TRUE) {
											print "<div class='odd' style='padding: 5px; margin-top: 0px; text-align: right; border-bottom: 1px solid #666; border-top: 1px solid #666'>" ;
												print "<i>Smart Blocks</i>: " ;
												if ($resultBlocks->rowCount()>0) {
													print "<a class='active' href='#' id='viewBlocks'>View Details</a> | <a href='#' id='editBlocks'>Edit Blocks</a> | " ;
												}
												if ($hooked==FALSE) {
													print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/units_edit_working.php&gibbonCourseClassID=$gibbonCourseClassID&gibbonCourseID=" . $row["gibbonCourseID"] . "&gibbonUnitID=" . $row["gibbonUnitID"] . "&gibbonSchoolYearID=" . $_SESSION[$guid]["gibbonSchoolYearID"] . "&gibbonUnitClassID=$gibbonUnitClassID'>Edit Unit</a> " ;
												}
												else {
													print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/units_edit_working.php&gibbonCourseClassID=$gibbonCourseClassID&gibbonCourseID=" . $row["gibbonCourseID"] . "&gibbonUnitID=" . $gibbonUnitIDToken . "-" . $gibbonHookIDToken . "&gibbonSchoolYearID=" . $_SESSION[$guid]["gibbonSchoolYearID"] . "&gibbonUnitClassID=$gibbonUnitClassID'>Edit Unit</a> " ;
												}
											print "</div>" ;
										}
										if ($resultBlocks->rowCount()>0) {
											if ($row["role"]=="Teacher" AND $teacher==TRUE) {
												?>
												<script type="text/javascript">
													$(document).ready(function(){
														$("#smartEdit").hide() ;
												
														$('#viewBlocks').click(function() {
															$("#smartView").show() ;
															$("#viewBlocks").addClass("active") ;
															$("#smartEdit").hide() ;
															$("#editBlocks").removeClass("active") ;
														}) ;
														$('#editBlocks').click(function() {
															$("#smartView").hide() ;
															$("#viewBlocks").removeClass("active") ;
															$("#smartEdit").show() ;
															$("#editBlocks").addClass("active") ;
														}) ;
													}) ;
												</script>
												<?
												print "<div id='smartEdit'>" ;
													print "<form method='post' action='" . $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/planner_view_full_smartProcess.php'>" ;
														?>
														<style>
															#sortable { list-style-type: none; margin: 0; padding: 0; width: 100%; }
															#sortable div.ui-state-default { margin: 0 0px 5px 0px; padding: 5px; font-size: 100%; min-height: 58px; }
															div.ui-state-default_dud { margin: 5px 0px 5px 0px; padding: 5px; font-size: 100%; min-height: 58px; }
															html>body #sortable li { min-height: 58px; line-height: 1.2em; }
															#sortable .ui-state-highlight { margin-bottom: 5px; min-height: 58px; line-height: 1.2em; width: 100%; }
														</style>
														<script type="text/javascript">
															$(function() {
																$( "#sortable" ).sortable({
																	placeholder: "ui-state-highlight",
																	axis: 'y'
																});
															});
														</script>
												
														<div class="sortable" id="sortable" style='width: 100%; padding: 5px 0px 0px 0px; border-top: 1px dotted #666; border-bottom: 1px dotted #666'>
															<? 
															$i=1 ;
															$minSeq=0 ;
															while ($rowBlocks=$resultBlocks->fetch()) {
																if ($i==1) {
																	$minSeq=$rowBlocks["sequenceNumber"] ;
																}
																if ($hooked==FALSE) {
																	makeBlock($guid, $connection2, $i, "plannerEdit", $rowBlocks["title"], $rowBlocks["type"], $rowBlocks["length"], $rowBlocks["contents"], $rowBlocks["complete"], "", $rowBlocks["gibbonUnitClassBlockID"], $rowBlocks["teachersNotes"]) ;
																}
																else {
																	makeBlock($guid, $connection2, $i, "plannerEdit", $rowBlocks[$hookOptions["classSmartBlockTitleField"]], $rowBlocks[$hookOptions["classSmartBlockTypeField"]], $rowBlocks[$hookOptions["classSmartBlockLengthField"]], $rowBlocks[$hookOptions["classSmartBlockContentsField"]], $rowBlocks[$hookOptions["classSmartBlockCompleteField"]], "", $rowBlocks[$hookOptions["classSmartBlockIDField"]], $rowBlocks[$hookOptions["classSmartBlockTeachersNotesField"]]) ;
																}
																$i++ ;
															}
															?>
														</div>
														<?
														print "<div style='text-align: right; margin-top: 3px'>" ;
															print "<input type='hidden' name='minSeq' value='$minSeq'>" ;
															print "<input type='hidden' name='mode' value='edit'>" ;
															print "<input type='hidden' name='params' value='$params'>" ;
															print "<input type='hidden' name='gibbonPlannerEntryID' value='$gibbonPlannerEntryID'>" ;
															print "<input type='hidden' name='address' value='" . $_SESSION[$guid]["address"] . "'>" ;
															print "<input type='submit' value='Submit'>" ;
														print "</div>" ;
													print "</form>" ;
												print "</div>" ;
											}
											print "<div id='smartView' style='background-color: #EDF7FF'>" ;
												print "<form method='post' action='" . $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/planner_view_full_smartProcess.php'>" ;
													$blockCount=0 ;
													while ($rowBlocksView=$resultBlocksView->fetch()) {
														if ($rowBlocksView["title"]!="" OR $rowBlocksView["type"]!="" OR $rowBlocksView["length"]!="") {
															print "<div class='blockView' style='min-height: 35px'>" ;
																if ($rowBlocksView["type"]!="" OR $rowBlocksView["length"]!="") {
																	$width="69%" ;
																}
																else {
																	$width="100%" ;
																}
																print "<div style='padding-left: 3px; width: $width; float: left;'>" ;
																	if ($rowBlocksView["title"]!="") {
																		print "<h5 style='padding-bottom: 2px'>" . $rowBlocksView["title"] . "</h5>" ;
																	}
																print "</div>" ;
																if ($rowBlocksView["type"]!="" OR $rowBlocksView["length"]!="") {
																	print "<div style='float: right; width: 29%; padding-right: 3px; height: 35px'>" ;
																		print "<div style='text-align: right; font-size: 85%; font-style: italic; margin-top: 2px; border-bottom: 1px solid #ddd; height: 21px; padding-top: 4px'>" ; 
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
																	print "</div>" ;
																}
															print "</div>" ;
														}
														if ($rowBlocksView["contents"]!="") {
															print "<div style='padding: 15px 3px 10px 3px; width: 98%; text-align: justify; border-bottom: 1px solid #ddd'>" . $rowBlocksView["contents"] . "</div>" ;
														}
														if ($rowBlocksView["teachersNotes"]!="" AND ($highestAction=="Lesson Planner_viewAllEditMyClasses" OR $highestAction=="Lesson Planner_viewEditAllClasses")) {
															print "<div class='teachersNotes' style='background-color: #F6CECB; display: none; padding: 0px 3px 10px 3px; width: 98%; text-align: justify; border-bottom: 1px solid #ddd'><i>Teacher's Notes:</i> " . $rowBlocksView["teachersNotes"] . "</div>" ;
														}
														$checked="" ;
														if ($rowBlocksView["complete"]=="Y") {
															$checked="checked" ;
														}
														if ($row["role"]=="Teacher" AND $teacher==TRUE) {
															print "<div style='text-align: right; font-weight: bold; margin-top: 20px'>Complete? <input name='complete$blockCount' style='margin-right: 2px' type='checkbox' $checked></div>" ;
														}
														else {
															print "<div style='text-align: right; font-weight: bold; margin-top: 20px'>Complete? <input disabled name='complete$blockCount' style='margin-right: 2px' type='checkbox' $checked></div>" ;
														}
														if ($hooked==FALSE) {
															print "<input type='hidden' name='gibbonUnitClassBlockID[]' value='" . $rowBlocksView["gibbonUnitClassBlockID"] . "'>" ;
														}
														else {
															print "<input type='hidden' name='gibbonUnitClassBlockID[]' value='" . $rowBlocksView["ibPYPUnitWorkingSmartBlockID"] . "'>" ;
														}
													
														print "<div style='padding: 3px 3px 3px 0px ; width: 100%; text-align: justify; border-bottom: 1px solid #666' ></div>" ;
														$blockCount++ ;
													}
													if ($row["role"]=="Teacher" AND $teacher==TRUE) {
														print "<div style='text-align: right; margin-top: 3px'>" ;
															print "<input type='hidden' name='mode' value='view'>" ;
															print "<input type='hidden' name='params' value='$params'>" ;
															print "<input type='hidden' name='gibbonPlannerEntryID' value='$gibbonPlannerEntryID'>" ;
															print "<input type='hidden' name='address' value='" . $_SESSION[$guid]["address"] . "'>" ;
															print "<input type='submit' value='Submit'>" ;
														print "</div>" ;
													}
												print "</form>" ;
											print "</div>" ;
										}
									print "</td>" ;
								print "</tr>" ;
							}
												
							if ($resultBlocks->rowCount()<1 AND $row["description"]=="") {
								print "<tr>" ;
									print "<td style='text-align: justify; padding-top: 5px; width: 33%; vertical-align: top' colspan=3>" ;
										print "<div class='error'>" ;
											print "This lesson has not had any content assigned to it." ;
										print "</div>" ;
									print "</td>" ;
								print "</tr>" ;
							}
						
						
							if ($row["teachersNotes"]!="" AND ($highestAction=="Lesson Planner_viewAllEditMyClasses" OR $highestAction=="Lesson Planner_viewEditAllClasses")) {
								print "<tr class='break' id='teachersNotes'>" ;
									print "<td style='text-align: justify; padding-top: 5px; width: 33%; vertical-align: top' colspan=3>" ;
										print "<h3>Teacher's Notes</h3>" ;
										print "<div style='background-color: #F6CECB; '>" . $row["teachersNotes"] . "</div>" ;
									print "</td>" ;
								print "</tr>" ;
							}
							print "<tr class='break'>" ;
								print "<td style='padding-top: 5px; width: 33%; vertical-align: top' colspan=3>" ;
									print "<h3>Homework</h3>" ;
								print "</td>" ;
							print "</tr>" ;
							print "<tr>" ;
								print "<td style='padding-top: 5px; width: 33%; vertical-align: top' colspan=3>" ;
									if ($row["homework"]=="Y") {
										print "<span style='font-weight: bold; color: #CC0000'>Due on " . dateConvertBack(substr($row["homeworkDueDateTime"],0,10)) . " at " . substr($row["homeworkDueDateTime"],11,5) . "</span><br/>" ;
										print $row["homeworkDetails"] . "<br>" ;
										if ($row["homeworkSubmission"]=="Y") {
											if ($row["role"]=="Student" AND ($highestAction=="Lesson Planner_viewMyClasses" OR $highestAction=="Lesson Planner_viewAllEditMyClasses")) {
												print "<span style='font-size: 115%; font-weight: bold'>Online Submission</span><br/>" ;
												print "<i>Online submission is <b>" . strtolower($row["homeworkSubmissionRequired"]) . "</b> for this homework.</i><br/>" ;
												if (date("Y-m-d")<$row["homeworkSubmissionDateOpen"]) {
													print "<i>Submission opens on " . dateConvertBack($row["homeworkSubmissionDateOpen"]) . "</i>" ;
												}
												else {
													//Check previous submissions!
													try {
														$dataVersion=array("gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonPlannerEntryID"=>$row["gibbonPlannerEntryID"]); 
														$sqlVersion="SELECT * FROM gibbonPlannerEntryHomework WHERE gibbonPersonID=:gibbonPersonID AND gibbonPlannerEntryID=:gibbonPlannerEntryID ORDER BY count" ;
														$resultVersion=$connection2->prepare($sqlVersion);
														$resultVersion->execute($dataVersion);
													}
													catch(PDOException $e) { 
														print "<div class='error'>" . $e->getMessage() . "</div>" ; 
													}
												
													$latestVersion="" ;
													$count=0;
													$rowNum="odd" ;
													if ($resultVersion->rowCount()>0) {
														?>
														<table cellspacing='0' style="width: 100%">	
															<tr class='head'>
																<th> 
																	Count<br/>
																</th>
																<th> 
																	Version<br/>
																</th>
																<th> 
																	Status<br/>
																</th>
																<th> 
																	Date/Time<br/>
																</th>
																<th> 
																	View<br/>
																</td>
															</tr>
															<?
															while ($rowVersion=$resultVersion->fetch()) {
																if ($count%2==0) {
																	$rowNum="even" ;
																}
																else {
																	$rowNum="odd" ;
																}
																$count++ ;
															
																print "<tr class=$rowNum>" ;
																?>
															
																	<td> 
																		<? print $rowVersion["count"] ?><br/>
																	</td>
																	<td>
																		<? print $rowVersion["version"] ?><br/>
																	</td>
																	<td>
																		<? print $rowVersion["status"] ?><br/>
																	</td>
																	<td>
																		<? print substr($rowVersion["timestamp"],11,5) . " " . dateConvertBack(substr($rowVersion["timestamp"],0,10)) ?><br/>
																	</td>
																	<td>
																		<? 
																		if ($rowVersion["type"]=="File") {
																			print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/" . $rowVersion["location"] ."'>" . $rowVersion["location"] . "</a>" ;
																		}
																		else {
																			print "<a href='" . $rowVersion["location"] ."'>" . $rowVersion["location"] . "</a>" ;
																	
																		}
																		?>
																	</td>
																</tr>
																<?
																$latestVersion=$rowVersion["version"] ;
															}
															?>
														</table>
														<?
													}
												
													if ($latestVersion!="Final") {
														$status="On Time" ;
														if (date("Y-m-d H:i:s")>$row["homeworkDueDateTime"]) {
															print "<span style='color: #C00; font-stlye: italic'>The deadline has passed. Your work will be marked as late.</span><br/>" ;
															$status="Late" ;
														}
													
														?>
														<form method="post" action="<? print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/planner_view_full_submitProcess.php?address=" . $_GET["q"] . $params . "&gibbonPlannerEntryID=" . $row["gibbonPlannerEntryID"] ?>" enctype="multipart/form-data">
															<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
																<tr>
																	<td> 
																		<b>Type *</b><br/>
																	</td>
																	<td class="right">
																		<?
																		if ($row["homeworkSubmissionType"]=="Link") {
																			print "<input readonly id='type' name='type' type='text' value='Link' style='width: 302px'>" ;
																		}
																		else if ($row["homeworkSubmissionType"]=="File") {
																			print "<input readonly id='type' name='type' type='text' value='File' style='width: 302px'>" ;
																			}
																		else {
																			?>
																			<input checked type="radio" id="type" name="type" class="type" value="Link" /> Link
																			<input type="radio" id="type" name="type" class="type" value="File" /> File
																			<?
																		}
																		?>
																	</td>
																</tr>
																<tr>
																	<td> 
																		<b>Version *</b><br/>
																	</td>
																	<td class="right">
																		<?
																		print "<select style='float: none; width: 302px' name='version'>" ;
																			if ($row["homeworkSubmissionDrafts"]>0 AND $status!="Late" AND $resultVersion->rowCount()<$row["homeworkSubmissionDrafts"]) {
																				print "<option value='Draft'>Draft</option>" ;
																			}
																			print "<option value='Final'>Final</option>" ;
																		print "</select>" ;
																		?>
																	</td>
																</tr>
															
																<script type="text/javascript">
																	/* Subbmission type control */
																	$(document).ready(function(){
																		<?
																		if ($row["homeworkSubmissionType"]=="Link") {
																			?>
																			$("#fileRow").css("display","none");
																			<?
																		}
																		else if ($row["homeworkSubmissionType"]=="File") {
																			?>
																			$("#linkRow").css("display","none");
																			<?
																		}
																		else {
																			?>
																			$("#fileRow").css("display","none");
																			$("#linkRow").slideDown("fast", $("#linkRow").css("display","table-row")); 
																			<?
																		}
																		?>
																	
																		$(".type").click(function(){
																			if ($('input[name=type]:checked').val() == "Link" ) {
																				$("#fileRow").css("display","none");
																				$("#linkRow").slideDown("fast", $("#linkRow").css("display","table-row")); 
																			} else {
																				$("#linkRow").css("display","none");
																				$("#fileRow").slideDown("fast", $("#fileRow").css("display","table-row")); 
																			}
																		 });
																	});
																</script>
															
																<tr id="fileRow">
																	<td> 
																		<b>Submit File *</b><br/>
																	</td>
																	<td class="right">
																		<input type="file" name="file" id="file"><br/><br/>
																		<?
																		print getMaxUpload() ;
																	
																		//Get list of acceptable file extensions
																		try {
																			$dataExt=array(); 
																			$sqlExt="SELECT * FROM gibbonFileExtension" ;
																			$resultExt=$connection2->prepare($sqlExt);
																			$resultExt->execute($dataExt);
																		}
																		catch(PDOException $e) { }
																		$ext="" ;
																		while ($rowExt=$resultExt->fetch()) {
																			$ext=$ext . "'." . $rowExt["extension"] . "'," ;
																		}
																		?>
																	
																		<script type="text/javascript">
																			var file=new LiveValidation('file');
																			file.add( Validate.Inclusion, { within: [<? print $ext ;?>], failureMessage: "Illegal file type!", partialMatch: true, caseSensitive: false } );
																		</script>
																	</td>
																</tr>
																<tr id="linkRow">
																	<td> 
																		<b>Submit Link *</b><br/>
																	</td>
																	<td class="right">
																		<input name="link" id="link" maxlength=255 value="" type="text" style="width: 300px">
																		<script type="text/javascript">
																			var link=new LiveValidation('link');
																			link.add( Validate.Inclusion, { within: ['http://', 'https://'], failureMessage: "Address must start with http:// or https://", partialMatch: true } );
																		</script>
																	</td>
																</tr>
																<tr>
																	<td class="right" colspan=2>
																		<?									
																		print "<input type='hidden' name='lesson' value='" . $row["name"] . "'>" ;
																		print "<input type='hidden' name='count' value='$count'>" ;
																		print "<input type='hidden' name='status' value='$status'>" ;
																		print "<input type='hidden' name='gibbonPlannerEntryID' value='$gibbonPlannerEntryID'>" ;
																		print "<input type='hidden' name='currentDate' value='" . $row["date"] . "'>" ;
																		?>
																		<input type="submit" value="Submit">
																	</td>
																</tr>
															</table>
														</form>
														<?
													}
												}
											}
											else if ($row["role"]=="Student" AND $highestAction=="Lesson Planner_viewMyChildrensClasses") {
												print "<span style='font-size: 115%; font-weight: bold'>Online Submission</span><br/>" ;
												print "<i>Online submission is <b>" . strtolower($row["homeworkSubmissionRequired"]) . "</b> for this homework.</i><br/>" ;
												if (date("Y-m-d")<$row["homeworkSubmissionDateOpen"]) {
													print "<i>Submission opens on " . dateConvertBack($row["homeworkSubmissionDateOpen"]) . "</i>" ;
												}
												else {
													//Check previous submissions!
													try {
														$dataVersion=array("gibbonPersonID"=>$gibbonPersonID, "gibbonPlannerEntryID"=>$row["gibbonPlannerEntryID"]); 
														$sqlVersion="SELECT * FROM gibbonPlannerEntryHomework WHERE gibbonPersonID=:gibbonPersonID AND gibbonPlannerEntryID=:gibbonPlannerEntryID" ;
														$resultVersion=$connection2->prepare($sqlVersion);
														$resultVersion->execute($dataVersion);
													}
													catch(PDOException $e) { 
														print "<div class='error'>" . $e->getMessage() . "</div>" ; 
													}
													$latestVersion="" ;
													$count=0;
													$rowNum="odd" ;
													if ($resultVersion->rowCount()<1) {
														if (date("Y-m-d H:i:s")>$row["homeworkDueDateTime"]) {
															print "<span style='color: #C00; font-stlye: italic'>The deadline has passed, and no work has been submitted.</span><br/>" ;
														}
													}
													else {
														?>
														<table cellspacing='0' style="width: 100%">	
															<tr class='head'>
																<th> 
																	Count<br/>
																</th>
																<th> 
																	Version<br/>
																</th>
																<th> 
																	Status<br/>
																</th>
																<th> 
																	Date/Time<br/>
																</th>
																<th> 
																	View<br/>
																</td>
															</tr>
															<?
															while ($rowVersion=$resultVersion->fetch()) {
																if ($count%2==0) {
																	$rowNum="even" ;
																}
																else {
																	$rowNum="odd" ;
																}
																$count++ ;
															
																print "<tr class=$rowNum>" ;
																?>
															
																	<td> 
																		<? print $rowVersion["count"] ?><br/>
																	</td>
																	<td>
																		<? print $rowVersion["version"] ?><br/>
																	</td>
																	<td>
																		<? print $rowVersion["status"] ?><br/>
																	</td>
																	<td>
																		<? print substr($rowVersion["timestamp"],11,5) . " " . dateConvertBack(substr($rowVersion["timestamp"],0,10)) ?><br/>
																	</td>
																	<td>
																		<? 
																		if ($rowVersion["type"]=="File") {
																			print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/" . $rowVersion["location"] ."'>" . $rowVersion["location"] . "</a>" ;
																		}
																		else {
																			print "<a href='" . $rowVersion["location"] ."'>" . $rowVersion["location"] . "</a>" ;
																	
																		}
																		?>
																	</td>
																</tr>
																<?
																$latestVersion=$rowVersion["version"] ;
															}
															?>
														</table>
														<?
													}
												}
											}
											else if ($row["role"]=="Teacher") {
												print "<span style='font-size: 115%; font-weight: bold'>Online Submission</span><br/>" ;
												print "<i>Online submission is <b>" . strtolower($row["homeworkSubmissionRequired"]) . "</b> for this homework.</i><br/>" ;
											
												if ($teacher==TRUE) {
													//List submissions
													try {
														$dataClass=array("gibbonCourseClassID"=>$row["gibbonCourseClassID"]); 
														$sqlClass="SELECT * FROM gibbonCourseClassPerson INNER JOIN gibbonPerson ON gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID WHERE gibbonCourseClassID=:gibbonCourseClassID AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND role='Student' ORDER BY role DESC, surname, preferredName" ;
														$resultClass=$connection2->prepare($sqlClass);
														$resultClass->execute($dataClass);
													}
													catch(PDOException $e) { 
														print "<div class='error'>" . $e->getMessage() . "</div>" ; 
													}
													$count=0;
													$rowNum="odd" ;
													if ($resultClass->rowCount()>0) {
														?>
														<table cellspacing='0' style="width: 100%">	
															<tr class='head'>
																<th> 
																	Student<br/>
																</th>
																<th> 
																	Status<br/>
																</th>
																<th> 
																	Version<br/>
																</th>
																<th> 
																	Date/Time<br/>
																</th>
																<th> 
																	View<br/>
																</th>
																<th>
																	Action<br/>
																</th>
															</tr>
															<?
															while ($rowClass=$resultClass->fetch()) {
																if ($count%2==0) {
																	$rowNum="even" ;
																}
																else {
																	$rowNum="odd" ;
																}
																$count++ ;
															
																print "<tr class=$rowNum>" ;
																?>
																
																	<td> 
																		<? print "<a href='index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=" . $rowClass["gibbonPersonID"] . "'>" . formatName("", $rowClass["preferredName"], $rowClass["surname"], "Student", true) . "</a>" ?><br/>
																	</td>
																
																	<?
																
																	try {
																		$dataVersion=array("gibbonPlannerEntryID"=>$row["gibbonPlannerEntryID"], "gibbonPersonID"=>$rowClass["gibbonPersonID"]); 
																		$sqlVersion="SELECT * FROM gibbonPlannerEntryHomework WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPersonID=:gibbonPersonID ORDER BY count DESC" ;
																		$resultVersion=$connection2->prepare($sqlVersion);
																		$resultVersion->execute($dataVersion);
																	}
																	catch(PDOException $e) { 
																		print "<div class='error'>" . $e->getMessage() . "</div>" ; 
																	}
																	if ($resultVersion->rowCount()<1) {
																		?>
																		<td colspan=4> 
																			<?
																			//Before deadline
																			if (date("Y-m-d H:i:s")<$row["homeworkDueDateTime"]) {
																				print "Pending" ;
																			}
																			//After
																			else {
																				if ($rowClass["dateStart"]>$row["date"]) {
																					print "<span title='Student joined school after lesson was taught.' style='color: #000; font-weight: normal; border: 2px none #ff0000; padding: 2px 4px'>NA</span>" ;
																				}
																				else {
																					if ($row["homeworkSubmissionRequired"]=="Compulsory") {
																						print "<span title='Incomplete' style='color: #ff0000; font-weight: bold; border: 2px solid #ff0000; padding: 2px 4px'>Incomplete</span>" ;
																					}
																					else {
																						print "Not submitted online" ;
																					}
																				}
																			}
																			?>
																		</td>
																		<td>
																			<? 
																			print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Planner/planner_view_full_submit_edit.php&gibbonPlannerEntryID=$gibbonPlannerEntryID&viewBy=$viewBy&subView=$subView&gibbonCourseClassID=$gibbonCourseClassID&date=$date&search=" . $gibbonPersonID . "&gibbonPersonID=" . $rowClass["gibbonPersonID"] . "&submission=false'><img title='Edit' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;						
																			?>
																		</td>
																		<?
																	}
																	else {
																		$rowVersion=$resultVersion->fetch() ;
																		?>
																		<td>
																			<? 
																			if ($rowVersion["status"]=="On Time" OR $rowVersion["status"]=="Exemption") {
																				print $rowVersion["status"] ;
																			} 
																			else {
																				print "<span style='color: #ff0000; font-weight: bold; border: 2px solid #ff0000; padding: 2px 4px'>" . $rowVersion["status"] . "</span>" ;
																			}
																			?>
																		</td>
																		<td>
																			<? 
																			print $rowVersion["version"] ;
																			if ($rowVersion["version"]=="Draft") {
																				print " " . $rowVersion["count"] ;
																			}
																			?>
																		</td>
																		<td>
																			<? print substr($rowVersion["timestamp"],11,5) . " " . dateConvertBack(substr($rowVersion["timestamp"],0,10)) ?><br/>
																		</td>
																		<td>
																			<?
																			$locationPrint=$rowVersion["location"] ;
																			if (strlen($locationPrint)>15) {
																				$locationPrint=substr($locationPrint,0,15) . "..." ;
																			}
																			if ($rowVersion["type"]=="File") {
																				print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/" . $rowVersion["location"] ."'>" . $locationPrint . "</a>" ;
																			}
																			else {
																				print "<a target='_blank' href='" . $rowVersion["location"] ."'>" . $locationPrint . "</a>" ;
																		
																			}
																			?>
																		</td>
																		<td>
																			<? 
																			print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Planner/planner_view_full_submit_edit.php&gibbonPlannerEntryID=$gibbonPlannerEntryID&viewBy=$viewBy&subView=$subView&gibbonCourseClassID=$gibbonCourseClassID&date=$date&width=1000&height=550&search=" . $gibbonPersonID . "&gibbonPlannerEntryHomeworkID=" . $rowVersion["gibbonPlannerEntryHomeworkID"] . "&submission=true'><img title='Edit' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;						
																			print "<a onclick='return confirm(\"Are you sure you wish to delete this submission?\")' href='" . $_SESSION[$guid]["absoluteURL"] . "/modules/Planner/planner_view_full_submit_deleteProcess.php?gibbonPlannerEntryID=$gibbonPlannerEntryID&viewBy=$viewBy&subView=$subView&gibbonCourseClassID=$gibbonCourseClassID&date=$date&width=1000&height=550&search=$gibbonPersonID&gibbonPlannerEntryHomeworkID=" . $rowVersion["gibbonPlannerEntryHomeworkID"] . "'><img title='Delete' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/></a>" ;
																			?>
																		</td>
																		<?
																	}
																	?>
																</tr>
																<?
															}
															?>
														</table>
														<?
													}
												}
											}
										}
									}
									else if ($row["homework"]=="N") {
										print "No<br/>" ;
									}
									print "</td>" ;
								print "</tr>" ;
						
							//Lesson outcomes
							try {
								$dataOutcomes=array("gibbonPlannerEntryID"=>$row["gibbonPlannerEntryID"]);  
								$sqlOutcomes="SELECT gibbonPlannerEntryOutcome.*, scope, name, nameShort, category, gibbonYearGroupIDList FROM gibbonPlannerEntryOutcome JOIN gibbonOutcome ON (gibbonPlannerEntryOutcome.gibbonOutcomeID=gibbonOutcome.gibbonOutcomeID) WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND active='Y' ORDER BY sequenceNumber" ;
								$resultOutcomes=$connection2->prepare($sqlOutcomes);
								$resultOutcomes->execute($dataOutcomes);
							}
							catch(PDOException $e) { 
								print "<div class='error'>" . $e->getMessage() . "</div>" ; 
							}
						
							if ($resultOutcomes->rowCount()>0) {
								print "<tr class='break'>" ;
									print "<td style='text-align: justify; padding-top: 5px; width: 33%; vertical-align: top' colspan=3>" ;
										print "<h3>Lesson Outcomes</h3>" ;
									print "</td>" ;
								print "</tr>" ;
								print "<tr>" ;
									print "<td style='text-align: justify; padding-top: 5px; width: 33%; vertical-align: top' colspan=3>" ;
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
														if ($rowOutcomes["scope"]=="Learning Area" AND $gibbonDepartmentID!="") {
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
														print "<b>" . $rowOutcomes["category"] . "</b><br/>" ;
													print "</td>" ;
													print "<td>" ;
														print "<b>" . $rowOutcomes["nameShort"] . "</b><br/>" ;
														print "<span style='font-size: 75%; font-style: italic'>" . $rowOutcomes["name"] . "</span>" ;
													print "</td>" ;
													print "<td>" ;
														print getYearGroupsFromIDList($connection2, $rowOutcomes["gibbonYearGroupIDList"]) ;
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
															print "<a title='View Description' class='show_hide-$count' onclick='false' href='#'><img style='padding-left: 0px' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/Default/img/page_down.png' alt='Show Comment' onclick='return false;' /></a>" ;
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
									print "</td>" ;
								print "</tr>" ;
							}
						
						
							if ($row["twitterSearch"]!="") {
								print "<tr>" ;
									print "<td style='text-align: justify; padding-top: 5px; width: 33%; vertical-align: top' colspan=3>" ;
										print "<h2>Twitter</h2><br/>" ;
										?>
										<iframe style='margin-left: -10px; margin-top: -10px; border: none; width: 640px; overflow: none; height: 400px' src="<? print $_SESSION[$guid]["absoluteURL"] ?>/modules/Planner/twitter.php?twitter=<? print str_replace("#", "^", $row["twitterSearch"]) ?>"></iframe>
										<?
									print "</td>" ;
								print "</tr>" ;
							}
						print "</table>" ;
					
						print "<h2>Chat</h2>" ;
						print "<table class='smallIntBorder' cellspacing='0' style='width: 100%;'>" ;
							print "<tr>" ;
								print "<td style='text-align: justify; padding-top: 5px; width: 33%; vertical-align: top; max-width: 752px!important' colspan=3>" ;
								
									print "<div style='margin: 0px' class='linkTop'>" ;
									print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/planner_view_full.php$params'><img title='Refresh' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/refresh.png'/></a> <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Planner/planner_view_full_post.php&gibbonPlannerEntryID=$gibbonPlannerEntryID&viewBy=$viewBy&subView=$subView&gibbonCourseClassID=$gibbonCourseClassID&date=$date&search=" . $gibbonPersonID . "'><img title='New' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/page_new.gif'/></a> " ;						
									print "</div>" ;
							
									print "<div style='margin-bottom: 0px' class='success'>" ;
										print "Items in <span style='color: #c00'>red</span> are new since your last login. Items in green are older." ;
									print "</div>" ;
								
									//Get discussion
									print getThread($guid, $connection2, $gibbonPlannerEntryID, NULL, 0, NULL, $viewBy, $subView, $date, $class, $gibbonCourseClassID, $gibbonPersonID, $row["role"]) ;
							
								print "</td>" ;
							print "</tr>" ;
						print "</table>" ;
						
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
							}
							if ($row["role"]=="Teacher") {
								//Check for outcomes
								try {
									$dataOutcomes=array("gibbonUnitID"=>$row["gibbonUnitID"]);  
									$sqlOutcomes="SELECT gibbonUnitOutcome.*, scope, name, nameShort, category, gibbonYearGroupIDList FROM gibbonUnitOutcome JOIN gibbonOutcome ON (gibbonUnitOutcome.gibbonOutcomeID=gibbonOutcome.gibbonOutcomeID) WHERE gibbonUnitID=:gibbonUnitID AND active='Y' ORDER BY sequenceNumber" ;
									$resultOutcomes=$connection2->prepare($sqlOutcomes);
									$resultOutcomes->execute($dataOutcomes);
								}
								catch(PDOException $e) { 
									print "<div class='error'>" . $e->getMessage() . "</div>" ; 
								}
								print "</table>" ;
				
								if ($rowResources["attachment"]!="" OR $rowResources["details"]!="" OR $resultOutcomes->rowCount()>0) {
									print "<h2 style='padding-top: 30px'>Unit Content</h2>" ;
									print "<table class='smallIntBorder' cellspacing='0' style='width: 100%;'>" ;
										print "<tr>" ;
											print "<td style='text-align: justify; padding-top: 5px; width: 33%; vertical-align: top' colspan=3>" ;
												if ($rowResources["details"]!="") {
													print $rowResources["details"] ;
												}
												if ($rowResources["attachment"]!="") {
													print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/" . $rowResources["attachment"] . "'>Downloadable Unit Outline</a>" ;
												}
											print "</td>" ;
										print "</tr>" ;
										if ($resultOutcomes->rowCount()>0) {
											print "<tr class='break'>" ;
												print "<td style='text-align: justify; padding-top: 5px; width: 33%; vertical-align: top' colspan=3>" ;
													print "<h3>Unit Outcomes</h3>" ;
												print "</td>" ;
											print "</tr>" ;
											print "<tr>" ;
												print "<td style='text-align: justify; padding-top: 5px; width: 33%; vertical-align: top' colspan=3>" ;
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
																	if ($rowOutcomes["scope"]=="Learning Area" AND $gibbonDepartmentID!="") {
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
																	print "<b>" . $rowOutcomes["category"] . "</b><br/>" ;
																print "</td>" ;
																print "<td>" ;
																	print "<b>" . $rowOutcomes["nameShort"] . "</b><br/>" ;
																	print "<span style='font-size: 75%; font-style: italic'>" . $rowOutcomes["name"] . "</span>" ;
																print "</td>" ;
																print "<td>" ;
																	print getYearGroupsFromIDList($connection2, $rowOutcomes["gibbonYearGroupIDList"]) ;
																print "</td>" ;
																print "<td>" ;
																	print "<script type='text/javascript'>" ;	
																		print "$(document).ready(function(){" ;
																			print "\$(\".unitDescription-$count\").hide();" ;
																			print "\$(\".unitShow_hide-$count\").fadeIn(1000);" ;
																			print "\$(\".unitShow_hide-$count\").click(function(){" ;
																			print "\$(\".unitDescription-$count\").fadeToggle(1000);" ;
																			print "});" ;
																		print "});" ;
																	print "</script>" ;
																	if ($rowOutcomes["content"]!="") {
																		print "<a class='unitShow_hide-$count' onclick='false' href='#'><img style='padding-left: 0px' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/Default/img/page_down.png' alt='Show Comment' onclick='return false;' /></a>" ;
																	}
																print "</td>" ;
															print "</tr>" ;
															if ($rowOutcomes["content"]!="") {
																print "<tr class='unitDescription-$count' id='unitDescription-$count'>" ;
																	print "<td colspan=6>" ;
																		print $rowOutcomes["content"] ;
																	print "</td>" ;
																print "</tr>" ;
															}
															print "</tr>" ;

															$count++ ;
														}
													print "</table>" ;
												print "</td>" ;
											print "</tr>" ;
										}
									print "</table>" ;
								}
							}
							else {
								if ($rowResources["attachment"]!="") {
									print "<h2 style='padding-top: 30px'>Unit Content</h2>" ;
									print "<table class='smallIntBorder' cellspacing='0' style='width: 100%;'>" ;
										print "<tr>" ;
											print "<td style='text-align: justify; padding-top: 5px; width: 33%; vertical-align: top' colspan=3>" ;
												print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/" . $rowResources["attachment"] . "'>Downloadable Unit Outline</a>" ;
											print "</td>" ;
										print "</tr>" ;
									print "</table>" ;
								}
							}
						}
			
						//Participants & Attendance
						$gibbonCourseClassID=$row["gibbonCourseClassID"] ;
						$columns=2 ;
						try {
							$dataClassGroup=array("gibbonCourseClassID"=>$gibbonCourseClassID); 
							$sqlClassGroup="SELECT * FROM gibbonCourseClassPerson INNER JOIN gibbonPerson ON gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID WHERE gibbonCourseClassID=:gibbonCourseClassID AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND (NOT role='Student - Left') AND (NOT role='Teacher - Left') ORDER BY role DESC, surname, preferredName" ;
							$resultClassGroup=$connection2->prepare($sqlClassGroup);
							$resultClassGroup->execute($dataClassGroup);
						}
						catch(PDOException $e) { 
							$_SESSION[$guid]["sidebarExtra"].="<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
					
						$_SESSION[$guid]["sidebarExtra"]="<div style='border-left: 1px dotted #666; width:260px; float: right; font-size: 115%; font-weight: bold; margin-top: 8px; padding-left: 25px'>Participants & Attendance<br/>" ;
							//Show attendance log for the current day
							if ($row["role"]=="Teacher" AND $teacher==TRUE) {
								try {
									$dataLog=array("gibbonPlannerEntryID"=>$gibbonPlannerEntryID); 
									$sqlLog="SELECT * FROM gibbonPlannerEntryAttendanceLog, gibbonPerson WHERE gibbonPlannerEntryAttendanceLog.gibbonPersonIDTaker=gibbonPerson.gibbonPersonID AND gibbonPlannerEntryID=:gibbonPlannerEntryID ORDER BY timestampTaken" ;
									$resultLog=$connection2->prepare($sqlLog);
									$resultLog->execute($dataLog);
								}
								catch(PDOException $e) { 
									$_SESSION[$guid]["sidebarExtra"].="<div class='error'>" . $e->getMessage() . "</div>" ; 
								}
								if ($resultLog->rowCount()<1) {
									$_SESSION[$guid]["sidebarExtra"].="<div class='error'>" ;
										$_SESSION[$guid]["sidebarExtra"].="Attendance has not been taken. The entries below are a best-guess, not actual data.";
									$_SESSION[$guid]["sidebarExtra"].="</div>" ;
								}
								else {
									$_SESSION[$guid]["sidebarExtra"].="<div class='success'>" ;
										$_SESSION[$guid]["sidebarExtra"].="Attendance has been taken at the following times for this lesson:";
										$_SESSION[$guid]["sidebarExtra"].="<ul style='margin-left: 20px'>" ;
										while ($rowLog=$resultLog->fetch()) {
											$_SESSION[$guid]["sidebarExtra"].="<li>" . substr($rowLog["timestampTaken"],11,5) . " " . dateConvertBack(substr($rowLog["timestampTaken"],0,10)) . " by " . formatName($rowLog["title"], $rowLog["preferredName"], $rowLog["surname"], "Staff", false, true) ."</li>" ;
										}
										$_SESSION[$guid]["sidebarExtra"].="</ul>" ;
									$_SESSION[$guid]["sidebarExtra"].="</div>" ;
								}
							}
						
							$_SESSION[$guid]["sidebarExtra"].="<form method='post' action='" . $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/planner_view_fullProcess.php'>" ;
								$_SESSION[$guid]["sidebarExtra"].="<table class='noIntBorder' cellspacing='0' style='width:260px; float: right; margin-bottom: 30px'>" ;
									$count=0 ;
									$countStudents=0 ;
									while ($rowClassGroup=$resultClassGroup->fetch()) {
										if ($count%$columns==0) {
											$_SESSION[$guid]["sidebarExtra"].="<tr>" ;
										}
									
										//Get attendance status for students
										$status="" ;
										if ($rowClassGroup["role"]=="Student") {
											//Check for record
											try {
												$dataAtt=array("gibbonPlannerEntryID"=>$gibbonPlannerEntryID, "gibbonPersonID"=>$rowClassGroup["gibbonPersonID"]); 
												$sqlAtt="SELECT * FROM gibbonPlannerEntryAttendance WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPersonID=:gibbonPersonID" ;
												$resultAtt=$connection2->prepare($sqlAtt);
												$resultAtt->execute($dataAtt);
											}
											catch(PDOException $e) { 
												$_SESSION[$guid]["sidebarExtra"].="<div class='error'>" . $e->getMessage() . "</div>" ; 
											}
											if ($resultAtt->rowCount()==1) {
												$rowAtt=$resultAtt->fetch() ;
												$status=$rowAtt["type"] ;
											}
										
											//Check for school attendance
											if ($status=="") {
												try {
													$dataAtt=array("date"=>$row["date"], "gibbonPersonID"=>$rowClassGroup["gibbonPersonID"]); 
													$sqlAtt="SELECT * FROM gibbonAttendanceLogPerson WHERE date=:date AND gibbonPersonID=:gibbonPersonID ORDER BY gibbonAttendanceLogPersonID DESC" ;
													$resultAtt=$connection2->prepare($sqlAtt);
													$resultAtt->execute($dataAtt);
												}
												catch(PDOException $e) { 
													$_SESSION[$guid]["sidebarExtra"].="<div class='error'>" . $e->getMessage() . "</div>" ; 
												}
												if ($resultAtt->rowCount()>0) {
													$rowAtt=$resultAtt->fetch() ;
													if ($rowAtt["type"]=="Absent" OR $rowAtt["type"]=="Left - Early" OR $rowAtt["type"]=="Left" OR $rowAtt["type"]=="Present - Offsite") {
														$status="Absent" ;
													}
												}
											}
										}
									
										if ($status=="Absent" OR $status=="Left - Early" OR $status=="Left" OR $status=="Present - Offsite") {
											$_SESSION[$guid]["sidebarExtra"].="<td style='border: 1px solid #CC0000; background-color: #F6CECB; width:20%; text-align: center; vertical-align: top'>" ;
										}
										else {
											$_SESSION[$guid]["sidebarExtra"].="<td style='border: 1px solid #rgba (1,1,1,0); width:20%; text-align: center; vertical-align: top'>" ;
										}
									
										//Alerts, if permission allows
										$_SESSION[$guid]["sidebarExtra"].=getAlertBar($guid, $connection2, $rowClassGroup["gibbonPersonID"], $rowClassGroup["privacy"], "id='confidentialPlan$count'") ;
		
										//Get photos
										$_SESSION[$guid]["sidebarExtra"].="<div>" ;
											$_SESSION[$guid]["sidebarExtra"].=getUserPhoto($guid, $rowClassGroup["image_75"], 75) ;
										
											if ($row["role"]=="Teacher" AND $teacher==TRUE) {
												if ($rowClassGroup["role"]=="Student") {
													try {
														$dataLike=array("gibbonPlannerEntryID"=>$row["gibbonPlannerEntryID"],"gibbonPersonID"=>$rowClassGroup["gibbonPersonID"]); 
														$sqlLike="SELECT * FROM gibbonBehaviour WHERE type='Positive' AND gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPersonID=:gibbonPersonID" ;
														$resultLike=$connection2->prepare($sqlLike);
														$resultLike->execute($dataLike); 
													}
													catch(PDOException $e) { }
													//HEY SHORTY IT'S YOUR BIRTHDAY!
													$daysUntilNextBirthday=daysUntilNextBirthday($rowClassGroup["dob"]) ;
													if ($daysUntilNextBirthday==0) {
														$_SESSION[$guid]["sidebarExtra"].="<img title='" . $rowClassGroup["preferredName"] . "&#39;s birthday today!' style='margin: -24px 0 0 0; width: 25px; height: 25px' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/gift_pink.png'/>" ;
													}
													else if ($daysUntilNextBirthday>0 AND $daysUntilNextBirthday<8) {
														$_SESSION[$guid]["sidebarExtra"].="<img title='$daysUntilNextBirthday day" ;
														if ($daysUntilNextBirthday!=1) {
															$_SESSION[$guid]["sidebarExtra"].="s" ;
														}
														$_SESSION[$guid]["sidebarExtra"].=" until " . $rowClassGroup["preferredName"] . "&#39;s birthday!' style='margin: -24px 0 0 0; width: 25px; height: 25px' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/gift.png'/>" ;
													}
										
													$_SESSION[$guid]["sidebarExtra"].="<div id='star" . $rowClassGroup["gibbonPersonID"] . "'>" ;
														$_SESSION[$guid]["sidebarExtra"].="<script type=\"text/javascript\">" ;
															$_SESSION[$guid]["sidebarExtra"].="$(document).ready(function(){" ;
																$_SESSION[$guid]["sidebarExtra"].="$(\"#starAdd" . $rowClassGroup["gibbonPersonID"] . "\").click(function(){" ;
																	$_SESSION[$guid]["sidebarExtra"].="$(\"#star" . $rowClassGroup["gibbonPersonID"] . "\").load(\"" . $_SESSION[$guid]["absoluteURL"] . "/modules/Planner/planner_view_full_starAjax.php\",{\"gibbonPersonID\": \"" . $rowClassGroup["gibbonPersonID"] . "\", \"gibbonPlannerEntryID\": \"" . $row["gibbonPlannerEntryID"] . "\"});" ;
																$_SESSION[$guid]["sidebarExtra"].="});" ;
															$_SESSION[$guid]["sidebarExtra"].="});" ;
														$_SESSION[$guid]["sidebarExtra"].="</script>" ;
														if ($resultLike->rowCount()!=1) {
															$_SESSION[$guid]["sidebarExtra"].="<a id='starAdd" . $rowClassGroup["gibbonPersonID"] . "' onclick='return false;' href='#'><img style='margin-top: -30px; margin-left: 60px' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/like_off.png'></a>" ;
														}
														else {
															$rowLike=$resultLike->fetch() ;
															$_SESSION[$guid]["sidebarExtra"].="<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Behaviour/behaviour_manage_edit.php&gibbonBehaviourID=" . $rowLike["gibbonBehaviourID"] . "'><img style='margin-top: -30px; margin-left: 60px' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/like_on.png'></a>" ;
														}
													
													$_SESSION[$guid]["sidebarExtra"].="</div>" ;
												}
											}
										$_SESSION[$guid]["sidebarExtra"].="</div>" ;
									
									
										if ($row["role"]=="Teacher" AND $teacher==TRUE) {
											if ($rowClassGroup["role"]=="Student") {
												$_SESSION[$guid]["sidebarExtra"].="<input type='hidden' name='$countStudents-gibbonPersonID' value='" . $rowClassGroup["gibbonPersonID"] . "'>" ;
												$_SESSION[$guid]["sidebarExtra"].="<select style='float: none; width:84px; margin: 3px 0px 3px 0px' name='$countStudents-type'>" ;
													$_SESSION[$guid]["sidebarExtra"].="<option " ; if ($status=="Present") { $_SESSION[$guid]["sidebarExtra"].="selected " ; } ; $_SESSION[$guid]["sidebarExtra"].="value='Present'>Present</option>" ;
													$_SESSION[$guid]["sidebarExtra"].="<option " ; if ($status=="Present - Late") { $_SESSION[$guid]["sidebarExtra"].="selected " ; } ; $_SESSION[$guid]["sidebarExtra"].="value='Present - Late'>Present - Late</option>" ;
													$_SESSION[$guid]["sidebarExtra"].="<option " ; if ($status=="Absent") { $_SESSION[$guid]["sidebarExtra"].="selected " ; } ; $_SESSION[$guid]["sidebarExtra"].="value='Absent'>Absent</option>" ;
													$_SESSION[$guid]["sidebarExtra"].="<option " ; if ($status=="Left") { $_SESSION[$guid]["sidebarExtra"].="selected " ; } ; $_SESSION[$guid]["sidebarExtra"].="value='Left'>Left</option>" ;
													$_SESSION[$guid]["sidebarExtra"].="<option " ; if ($status=="Left - Early") { $_SESSION[$guid]["sidebarExtra"].="selected " ; } ; $_SESSION[$guid]["sidebarExtra"].="value='Left - Early'>Left - Early</option>" ;
												$_SESSION[$guid]["sidebarExtra"].="</select>" ;
											}
										}
									
										if ($rowClassGroup["role"]=="Student") {
											$_SESSION[$guid]["sidebarExtra"].="<div style='padding-top: 5px'><b><a href='index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=" . $rowClassGroup["gibbonPersonID"] . "'>" . formatName("", $rowClassGroup["preferredName"], $rowClassGroup["surname"], "Student") . "</a></b><br/>" ;
										}
										else {
											$_SESSION[$guid]["sidebarExtra"].="<div style='padding-top: 35px'><b>" . formatName($rowClassGroup["title"], $rowClassGroup["preferredName"], $rowClassGroup["surname"], "Staff") . "</b><br/>" ;
										}
									
										$_SESSION[$guid]["sidebarExtra"].="<i>" . $rowClassGroup["role"] . "</i><br/><br/></div>" ;
										$_SESSION[$guid]["sidebarExtra"].="</td>" ;
									
										if ($count%$columns==($columns-1)) {
											$_SESSION[$guid]["sidebarExtra"].="</tr>" ;
										}
									
										$count++ ;
										if ($rowClassGroup["role"]=="Student") {
											$countStudents++ ;
										}
									
									}
								
									for ($i=0;$i<$columns-($count%$columns);$i++) {
										$_SESSION[$guid]["sidebarExtra"].="<td style='width:20%;'></td>" ;
									}
								
									if ($count%$columns!=0) {
										$_SESSION[$guid]["sidebarExtra"].="</tr>" ;
									}
								
									if ($row["role"]=="Teacher" AND $teacher==TRUE) {
										$_SESSION[$guid]["sidebarExtra"].="<tr>" ;
											$_SESSION[$guid]["sidebarExtra"].="<td class='right' colspan=5>" ;
												$_SESSION[$guid]["sidebarExtra"].="<input type='hidden' name='params' value='$params'>" ;
												$_SESSION[$guid]["sidebarExtra"].="<input type='hidden' name='gibbonPlannerEntryID' value='$gibbonPlannerEntryID'>" ;
												$_SESSION[$guid]["sidebarExtra"].="<input type='hidden' name='currentDate' value='" . $row["date"] . "'>" ;
												$_SESSION[$guid]["sidebarExtra"].="<input type='hidden' name='countStudents' value='$countStudents'>" ;
												$_SESSION[$guid]["sidebarExtra"].="<input type='hidden' name='address' value='" . $_SESSION[$guid]["address"] . "'>" ;
												$_SESSION[$guid]["sidebarExtra"].="<input type='submit' value='Submit'>" ;
											$_SESSION[$guid]["sidebarExtra"].="</td>" ;
										$_SESSION[$guid]["sidebarExtra"].="</tr>" ;
									}
								$_SESSION[$guid]["sidebarExtra"].="</table>" ;
							$_SESSION[$guid]["sidebarExtra"].="</form>" ;
						
						//Guests
						try {
							$dataClassGroup=array("gibbonPlannerEntryID"=>$gibbonPlannerEntryID); 
							$sqlClassGroup="SELECT * FROM gibbonPlannerEntryGuest INNER JOIN gibbonPerson ON gibbonPlannerEntryGuest.gibbonPersonID=gibbonPerson.gibbonPersonID JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND status='Full' ORDER BY role DESC, surname, preferredName" ;
							$resultClassGroup=$connection2->prepare($sqlClassGroup);
							$resultClassGroup->execute($dataClassGroup);
						}
						catch(PDOException $e) { 
							$_SESSION[$guid]["sidebarExtra"].="<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						if ($resultClassGroup->rowCount()>0) {
							$_SESSION[$guid]["sidebarExtra"].="<span style='font-size: 115%; font-weight: bold; padding-top: 21px'>Guests<br/></span>" ;
							$_SESSION[$guid]["sidebarExtra"].="<table cellspacing='0' style='width:260px; float: right'>" ;
								$count2=0 ;
								$count2Students=0 ;
								while ($rowClassGroup=$resultClassGroup->fetch()) {
									if ($count2%$columns==0) {
										$_SESSION[$guid]["sidebarExtra"].="<tr>" ;
									}
								
									$_SESSION[$guid]["sidebarExtra"].="<td style='border: 1px solid #ffffff; width:20%; text-align: center; vertical-align: top'>" ;
								
									$_SESSION[$guid]["sidebarExtra"].=getUserPhoto($guid, $rowClassGroup["image_75"], 75) ;
								
									$_SESSION[$guid]["sidebarExtra"].="<div style='padding-top: 5px'><b>" . formatName($rowClassGroup["title"], $rowClassGroup["preferredName"], $rowClassGroup["surname"], "Staff") . "</b><br/>" ;
								
									$_SESSION[$guid]["sidebarExtra"].="<i>" . $rowClassGroup["role"] . "</i><br/><br/></div>" ;
									$_SESSION[$guid]["sidebarExtra"].="</td>" ;
								
									if ($count2%$columns==($columns-1)) {
										$_SESSION[$guid]["sidebarExtra"].="</tr>" ;
									}
								
									$count2++ ;
									if ($rowClassGroup["role"]=="Student") {
										$count2Students++ ;
									}
								
								}
							
								for ($i=0;$i<$columns-($count2%$columns);$i++) {
									$_SESSION[$guid]["sidebarExtra"].="<td style='width:20%;'></td>" ;
								}
							
								if ($count2%$columns!=0) {
									$_SESSION[$guid]["sidebarExtra"].="</tr>" ;
								}
							$_SESSION[$guid]["sidebarExtra"].="</table>" ;	
						}
						$_SESSION[$guid]["sidebarExtra"].="</div>" ;
						?>
						<script type="text/javascript">
							/* Confidential Control */
							$(document).ready(function(){
								$("#teachersNotes").slideUp("fast");
								$(".teachersNotes").slideUp("fast");
								<?
								for ($i=0; $i<$count; $i++) {
									?>
									$("#confidentialPlan<? print $i ?>").css("display","none");
									<?
								}
								?>
							
								$(".confidentialPlan").click(function(){
									if ($('input[name=confidentialPlan]:checked').val() == "Yes" ) {
										$("#teachersNotes").slideDown("fast", $(".teachersNotes").css("{'display' : 'table-row', 'border' : 'right'}")); 
										$(".teachersNotes").slideDown("fast", $("#teachersNotes").css("{'display' : 'table-row', 'border' : 'right'}")); 
										<?
										for ($i=0; $i<$count; $i++) {
											?>
											$("#confidentialPlan<? print $i ?>").slideDown("fast", $("#confidentialPlan<? print $i ?>").css("{'display' : 'table-row', 'border' : 'right'}")); 
											<?
										}
										?>
									} 
									else {
										$("#teachersNotes").slideUp("fast"); 
										$(".teachersNotes").slideUp("fast"); 
										<?
										for ($i=0; $i<$count; $i++) {
											?>
											$("#confidentialPlan<? print $i ?>").slideUp("fast"); 
											<?
										}
										?>
									}
								 });
							});
						</script>
						<?
					}
				}
			}
		}
	}
}		
?>