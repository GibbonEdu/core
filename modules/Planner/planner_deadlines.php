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

$style="" ;

$highestAction=getHighestGroupedAction($guid, $_GET["q"], $connection2) ;
if (isActionAccessible($guid, $connection2, "/modules/Planner/planner_deadlines.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Set variables
	$today=date("Y-m-d");
		
	//Proceed!
	//Get viewBy, date and class variables
	$params="" ;
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
		if (isset($_GET["date"])) {
			$date=$_GET["date"] ;
		}
		if (isset($_GET["dateHuman"])) {
			$date=dateConvert($guid, $_GET["dateHuman"]) ;
		}
		if ($date=="") {
			$date=date("Y-m-d");
		}
		list($dateYear, $dateMonth, $dateDay)=explode('-', $date);
		$dateStamp=mktime(0, 0, 0, $dateMonth, $dateDay, $dateYear);	
		$params="&viewBy=date&date=$date" ;
	}
	else if ($viewBy=="class") {
		$class=NULL ;
		if (isset($_GET["class"])) {
			$class=$_GET["class"] ;
		}
		$gibbonCourseClassID=$_GET["gibbonCourseClassID"] ;
		$params="&viewBy=class&class=$class&gibbonCourseClassID=$gibbonCourseClassID" ;
	}
	list($todayYear, $todayMonth, $todayDay)=explode('-', $today);
	$todayStamp=mktime(0, 0, 0, $todayMonth, $todayDay, $todayYear);
	$show=NULL ;
	if (isset($_GET["show"])) {
		$show=$_GET["show"] ;
	}
	$gibbonCourseClassIDFilter=NULL ;
	if (isset($_GET["gibbonCourseClassIDFilter"])) {
		$gibbonCourseClassIDFilter=$_GET["gibbonCourseClassIDFilter"] ;
	}
	$gibbonPersonID=NULL ;
	if (isset($_GET["search"])) {
		$gibbonPersonID=$_GET["search"] ;
	}
					
					
	//My children's classes
	if ($highestAction=="Lesson Planner_viewMyChildrensClasses") {
		print "<div class='trail'>" ;
		print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/planner.php'>" . __($guid, 'My Children\'s Classes') . "</a> > </div><div class='trailEnd'>" . __($guid, 'Homework + Deadlines') . "</div>" ;
		print "</div>" ;
	
		//Test data access field for permission
		try {
			$data=array("gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
			$sql="SELECT * FROM gibbonFamilyAdult WHERE gibbonPersonID=:gibbonPersonID AND childDataAccess='Y'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
		if ($result->rowCount()<1) {
			print "<div class='error'>" ;
			print __($guid, "Access denied.") ;
			print "</div>" ;
		}
		else {
			//Get child list
			$count=0 ;
			$options="" ;
			while ($row=$result->fetch()) {
				try {
					$dataChild=array("gibbonFamilyID"=>$row["gibbonFamilyID"], "gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
					$sqlChild="SELECT * FROM gibbonFamilyChild JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonFamilyID=:gibbonFamilyID AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY surname, preferredName " ;
					$resultChild=$connection2->prepare($sqlChild);
					$resultChild->execute($dataChild);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				
				while ($rowChild=$resultChild->fetch()) {
					$select="" ;
					if ($rowChild["gibbonPersonID"]==$gibbonPersonID) {
						$select="selected" ;
					}
					$options=$options . "<option $select value='" . $rowChild["gibbonPersonID"] . "'>" . $rowChild["surname"] . ", " . $rowChild["preferredName"] . "</option>" ;
					$gibbonPersonIDArray[$count]=$rowChild["gibbonPersonID"] ;
					$count++ ;
				}
			}
			
			if ($count==0) {
				print "<div class='error'>" ;
				print __($guid, "Access denied.") ;
				print "</div>" ;
			}
			else if ($count==1) {
				$gibbonPersonID=$gibbonPersonIDArray[0] ;
			}
			else {
				print "<h3>" ;
				print __($guid, "Choose") ;
				print "</h3>" ;
				
				?>
				<form method="get" action="<?php print $_SESSION[$guid]["absoluteURL"]?>/index.php">
					<table class='noIntBorder' cellspacing='0' style="width: 100%">	
						<tr><td style="width: 30%"></td><td></td></tr>
						<tr>
							<td> 
								<b><?php print __($guid, 'Search For') ?></b><br/>
								<span class="emphasis small"><?php print __($guid, 'Preferred, surname, username.') ?></span>
							</td>
							<td class="right">
								<select name="search" id="search" class="standardWidth">
									<option value=""></value>
									<?php print $options ; ?> 
								</select>
							</td>
						</tr>
						<tr>
							<td colspan=2 class="right">
								<input type="hidden" name="q" value="/modules/<?php print $_SESSION[$guid]["module"] ?>/planner_deadlines.php">
								<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
								<?php
								print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/planner.php'>" . __($guid, 'Clear Search') . "</a>" ;
								?>
								<input type="submit" value="<?php print __($guid, "Submit") ; ?>">
							</td>
						</tr>
					</table>
				</form>
				<?php
			}
			
			
			if ($gibbonPersonID!="" AND $count>0) {
				//Confirm access to this student
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
					print __($guid, "The selected record does not exist, or you do not have access to it.") ;
					print "</div>" ;
				}
				else {
					$rowChild=$resultChild->fetch() ;
					
					print "<h3>" ;
					print __($guid, "Upcoming Deadlines") ;
					print "</h3>" ;
					
					$proceed=TRUE ;
					if ($viewBy=="class") {
						if ($gibbonCourseClassID=="") {
							$proceed=FALSE ;
						}
						else {
							try {
								$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonID"=>$gibbonPersonID, "gibbonCourseClassID"=>$gibbonCourseClassID); 
								$sql="SELECT gibbonCourse.gibbonCourseID, gibbonCourseClass.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourseClassPerson JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID AND role='Teacher' ORDER BY course, class" ;
								$result=$connection2->prepare($sql);
								$result->execute($data);
							}
							catch(PDOException $e) { 
								print "<div class='error'>" . $e->getMessage() . "</div>" ; 
							}
							if ($result->rowCount()!=1) {
								$proceed=FALSE ;
							}
						}
					}
					
					if ($proceed==FALSE) {
						print "<div class='error'>" ;
							print __($guid, "Your request failed because you do not have access to this action.") ;
						print "</div>" ;
					}
					else {
						try {
							$data=array("gibbonPersonID"=>$gibbonPersonID, "gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
							$sql="
							(SELECT 'teacherRecorded' AS type, gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, date, timeStart, timeEnd, viewableStudents, viewableParents, homework, homeworkDueDateTime, role FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left' AND homework='Y' AND (role='Teacher' OR (role='Student' AND viewableStudents='Y')) AND homeworkDueDateTime>'" . date("Y-m-d H:i:s") . "' AND ((date<'" . date("Y-m-d") . "') OR (date='" . date("Y-m-d") . "' AND timeEnd<='" . date("H:i:s") . "')))
							UNION
							(SELECT 'studentRecorded' AS type, gibbonPlannerEntry.gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, date, timeStart, timeEnd, 'Y' AS viewableStudents, 'Y' AS viewableParents, 'Y' AS homework, gibbonPlannerEntryStudentHomework.homeworkDueDateTime, role FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) JOIN gibbonPlannerEntryStudentHomework ON (gibbonPlannerEntryStudentHomework.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID AND gibbonPlannerEntryStudentHomework.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left' AND (role='Teacher' OR (role='Student' AND viewableStudents='Y')) AND gibbonPlannerEntryStudentHomework.homeworkDueDateTime>'" . date("Y-m-d H:i:s") . "' AND ((date<'" . date("Y-m-d") . "') OR (date='" . date("Y-m-d") . "' AND timeEnd<='" . date("H:i:s") . "')))
							ORDER BY homeworkDueDateTime, type" ;
							$result=$connection2->prepare($sql);
							$result->execute($data);
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
											
						if ($result->rowCount()<1) {
							print "<div class='success'>" ;
								print __($guid, "No upcoming deadlines!") ;
							print "</div>" ;
						}
						else {
							print "<ol>" ;
							while ($row=$result->fetch()) {
								$diff=(strtotime(substr($row["homeworkDueDateTime"],0,10)) - strtotime(date("Y-m-d")))/86400 ;
								$style="style='padding-right: 3px;'" ;
								if ($diff<2) {
									$style="style='padding-right: 3px; border-right: 10px solid #cc0000'" ;	
								}
								else if ($diff<4) {
									$style="style='padding-right: 3px; border-right: 10px solid #D87718'" ;	
								}
								print "<li $style>" ;
								if ($viewBy=="class") {
									print "<b><a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/planner_view_full.php&gibbonPlannerEntryID=" . $row["gibbonPlannerEntryID"] . "&viewBy=class&gibbonCourseClassID=$gibbonCourseClassID&search=" . $gibbonPersonID . "'>" . $row["course"] . "." . $row["class"] . "</a> - " . $row["name"] . "</b><br/>" ;
								}
								else {
									print "<b><a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/planner_view_full.php&gibbonPlannerEntryID=" . $row["gibbonPlannerEntryID"] . "&viewBy=date&date=$date&search=" . $gibbonPersonID . "'>" . $row["course"] . "." . $row["class"] . "</a> - " . $row["name"] . "</b><br/>" ;
								}
								print "<span style='margin-left: 15px; font-style: italic'>Due at " . substr($row["homeworkDueDateTime"],11,5) . " on " . dateConvertBack($guid, substr($row["homeworkDueDateTime"],0,10)) ;
								print "</li>" ;
							}
							print "</ol>" ;
						}
					}
					
					$style="" ;
					
					print "<h3>" ;
					print __($guid, "All Homework") ;
					print "</h3>" ;
					
					$filter=NULL ;
					$filter2=NULL ;
					$data=array() ;
					if ($gibbonCourseClassIDFilter!="") {
						$data["gibbonCourseClassIDFilter"]=$gibbonCourseClassIDFilter ;
						$data["gibbonCourseClassIDFilter2"]=$gibbonCourseClassIDFilter ;
						$filter=" AND gibbonPlannerEntry.gibbonCourseClassID=:gibbonCourseClassIDFilter" ;
						$filte2=" AND gibbonPlannerEntry.gibbonCourseClassID=:gibbonCourseClassIDFilte2" ;
					}
					
					try {
						$data["gibbonPersonID"]=$gibbonPersonID;
						$data["gibbonSchoolYearID"]=$_SESSION[$guid]["gibbonSchoolYearID"] ;
						$sql="
						(SELECT 'teacherRecorded' AS type, gibbonPlannerEntryID, gibbonUnitID, gibbonHookID, gibbonPlannerEntry.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, date, timeStart, timeEnd, viewableStudents, viewableParents, homework, role, homeworkDueDateTime, homeworkDetails, homeworkSubmission, homeworkSubmissionRequired FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left' AND homework='Y' AND gibbonSchoolYearID=:gibbonSchoolYearID AND (date<'" . date("Y-m-d") . "' OR (date='" . date("Y-m-d") . "' AND timeEnd<='" . date("H:i:s") . "')) $filter)
						UNION
						(SELECT 'studentRecorded' AS type, gibbonPlannerEntry.gibbonPlannerEntryID, gibbonUnitID, gibbonHookID, gibbonPlannerEntry.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, date, timeStart, timeEnd, 'Y' AS viewableStudents, 'Y' AS viewableParents, 'Y' AS homework, role, gibbonPlannerEntryStudentHomework.homeworkDueDateTime AS homeworkDueDateTime, gibbonPlannerEntryStudentHomework.homeworkDetails AS homeworkDetails, 'N' AS homeworkSubmission, '' AS homeworkSubmissionRequired FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) JOIN gibbonPlannerEntryStudentHomework ON (gibbonPlannerEntryStudentHomework.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID AND gibbonPlannerEntryStudentHomework.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID) WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left' AND gibbonSchoolYearID=:gibbonSchoolYearID AND (date<'" . date("Y-m-d") . "' OR (date='" . date("Y-m-d") . "' AND timeEnd<='" . date("H:i:s") . "')) $filter)
						ORDER BY date DESC, timeStart DESC" ; 
						$result=$connection2->prepare($sql);
						$result->execute($data);
					}
					catch(PDOException $e) { 
						print "<div class='error'>" . $e->getMessage() . "</div>" ; 
					}
				
					//Only show add if user has edit rights
					if ($result->rowCount()<1) {
						print "<div class='error'>" ;
						print __($guid, "There are no records to display.") ;
						print "</div>" ;
					}
					else {
						print "<div class='linkTop'>" ;
							print "<form method='get' action='" . $_SESSION[$guid]["absoluteURL"] . "/index.php'>" ;
								print "<table class='blank' cellspacing='0' style='float: right; width: 250px'>" ;	
									print "<tr>" ;
										print "<td style='width: 190px'>" ; 
											print "<select name='gibbonCourseClassIDFilter' id='gibbonCourseClassIDFilter' style='width:190px'>" ;
												print "<option value=''></option>" ;
												try {
													$dataSelect=array("gibbonPersonID"=>$gibbonPersonID, "gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "date"=>date("Y-m-d")); 
													$sqlSelect="SELECT DISTINCT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.gibbonCourseClassID FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left' AND homework='Y' AND gibbonSchoolYearID=:gibbonSchoolYearID AND date<=:date ORDER BY course, class" ; 
													$resultSelect=$connection2->prepare($sqlSelect);
													$resultSelect->execute($dataSelect);
												}
												catch(PDOException $e) { }
												while ($rowSelect=$resultSelect->fetch()) {
													$selected="" ;
													if ($rowSelect["gibbonCourseClassID"]==$gibbonCourseClassIDFilter) {
														$selected="selected" ;
													}
													print "<option $selected value='" . $rowSelect["gibbonCourseClassID"] . "'>" . htmlPrep($rowSelect["course"]) . "." . htmlPrep($rowSelect["class"]) . "</option>" ;
												}
											 print "</select>" ;
										print "</td>" ;
										print "<td class='right'>" ;
											print "<input type='submit' value='" . __($guid, 'Go') . "' style='margin-right: 0px'>" ;
											print "<input type='hidden' name='q' value='/modules/Planner/planner_deadlines.php'>" ;
											print "<input type='hidden' name='search' value='$gibbonPersonID'>" ;
										print "</td>" ;
									print "</tr>" ;
								print "</table>" ;
							print "</form>" ;
						print "</div>" ; 
						print "<table cellspacing='0' style='width: 100%'>" ;
							print "<tr class='head'>" ;
								print "<th>" ;
									print __($guid, "Class") . "</br>" ;
									print "<span style='font-size: 85%; font-style: italic'>" . __($guid, 'Date') . "</span>" ;
								print "</th>" ;
								print "<th>" ;
									print __($guid, "Lesson") . "</br>" ;
									print "<span style='font-size: 85%; font-style: italic'>" . __($guid, 'Unit') . "</span>" ;
								print "</th>" ;
								print "<th style='min-width: 25%'>" ;
									print __($guid, "Type") . "<br/>" ;
									print "<span style='font-size: 85%; font-style: italic'>" . __($guid, 'Details') . "</span>" ;
								print "</th>" ;
								print "<th>" ;
									print __($guid, "Deadline") ;
								print "</th>" ;
								print "<th>" ;
									print sprintf(__($guid, 'Online%1$sSubmission'), "<br/>") ;
								print "</th>" ;
								print "<th>" ;
									print __($guid, "Actions") ;
								print "</th>" ;
							print "</tr>" ;
							
							$count=0;
							$rowNum="odd" ;
							while ($row=$result->fetch()) {
								if (!($row["role"]=="Student" AND $row["viewableParents"]=="N")) {
									if ($count%2==0) {
										$rowNum="even" ;
									}
									else {
										$rowNum="odd" ;
									}
									$count++ ;
									
									//Highlight class in progress
									if ((date("Y-m-d")==$row["date"]) AND (date("H:i:s")>$row["timeStart"]) AND (date("H:i:s")<$row["timeEnd"])) {
										$rowNum="current" ;
									}
									
									//COLOR ROW BY STATUS!
									print "<tr class=$rowNum>" ;
										print "<td>" ;
											print "<b>" . $row["course"] . "." . $row["class"] . "</b></br>" ;
											print "<span style='font-size: 85%; font-style: italic'>" . dateConvertBack($guid, $row["date"]) . "</span>" ;
										print "</td>" ;
										print "<td>" ;
											print "<b>" . $row["name"] . "</b><br/>" ;
											print "<span style='font-size: 85%; font-style: italic'>" ;
												$unit=getUnit($connection2, $row["gibbonUnitID"], $row["gibbonHookID"], $row["gibbonCourseClassID"]) ;
												if (isset($unit[0])) {
													print $unit[0] ;
													if ($unit[1]!="") {
														print "<br/><i>" . $unit[1] . " Unit</i>" ;
													}
												}
											print "</span>" ;
										print "</td>" ;
										print "<td>" ;
											if ($row["type"]=="teacherRecorded") {
												print "Teacher Recorded" ;
											}
											else {
												print "Student Recorded" ;
											}
											print  "<br/>" ;
											print "<span style='font-size: 85%; font-style: italic'>" ;
												if ($row["homeworkDetails"]!="") {
													if (strlen(strip_tags($row["homeworkDetails"]))<21) {
														print strip_tags($row["homeworkDetails"]) ;
													}
													else {
														print "<span $style title='" . htmlPrep(strip_tags($row["homeworkDetails"])) . "'>" . substr(strip_tags($row["homeworkDetails"]), 0, 20) . "...</span>" ;
													}
												}
											print "</span>" ;
										print "</td>" ;
										print "<td>" ;
											print dateConvertBack($guid, substr($row["homeworkDueDateTime"],0,10)) ;
										print "</td>" ;
										print "<td>" ;
											if ($row["homeworkSubmission"]=="Y") {
												print "<b>" . $row["homeworkSubmissionRequired"] . "<br/></b>" ;
												if ($row["role"]=="Student") {
													try {
														$dataVersion=array("gibbonPlannerEntryID"=>$row["gibbonPlannerEntryID"], "gibbonPersonID"=>$gibbonPersonID); 
														$sqlVersion="SELECT * FROM gibbonPlannerEntryHomework WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPersonID=:gibbonPersonID ORDER BY count DESC" ;
														$resultVersion=$connection2->prepare($sqlVersion);
														$resultVersion->execute($dataVersion);
													}
													catch(PDOException $e) { 
														print "<div class='error'>" . $e->getMessage() . "</div>" ; 
													}

													if ($resultVersion->rowCount()<1) {
														//Before deadline
														if (date("Y-m-d H:i:s")<$row["homeworkDueDateTime"]) {
															print __($guid, "Pending") ;
														}
														//After
														else {
															if ($row["homeworkSubmissionRequired"]=="Compulsory") {
																print "<div style='color: #ff0000; font-weight: bold; border: 2px solid #ff0000; padding: 2px 4px; margin: 2px 0px'>" . __($guid, 'Incomplete') . "</div>" ;
															}
															else {
																print  __($guid, "Not submitted online") ;
															}
														}
													}
													else {
														$rowVersion=$resultVersion->fetch() ;
														if ($rowVersion["status"]=="On Time" OR $rowVersion["status"]=="Exemption") {
															print $rowVersion["status"] ;
														} 
														else {
															if ($row["homeworkSubmissionRequired"]=="Compulsory") {
																print "<div style='color: #ff0000; font-weight: bold; border: 2px solid #ff0000; padding: 2px 4px; margin: 2px 0px'>" . $rowVersion["status"] . "</div>" ;
															}
															else {
																print $rowVersion["status"] ;
															}
														}
													}
												}
											}
										print "</td>" ;
										print "<td>" ;
											print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/planner_view_full.php&search=$gibbonPersonID&gibbonPlannerEntryID=" . $row["gibbonPlannerEntryID"] . "&viewBy=class&gibbonCourseClassID=" . $row["gibbonCourseClassID"] . "'><img title='" . __($guid, 'View') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/plus.png'/></a> " ;
										print "</td>" ;
									print "</tr>" ;
								}
						}
						print "</table>" ;
					}
				}
			}
		}
	}
	else if ($highestAction=="Lesson Planner_viewMyClasses" OR $highestAction=="Lesson Planner_viewAllEditMyClasses" OR $highestAction=="Lesson Planner_viewEditAllClasses") {
		//Get current role category
		$category=getRoleCategory($_SESSION[$guid]["gibbonRoleIDCurrent"], $connection2) ;
	
		print "<div class='trail'>" ;
		print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/planner.php$params'>" . __($guid, 'Planner') . "</a> > </div><div class='trailEnd'>" . __($guid, 'Homework + Deadlines') . "</div>" ;
		print "</div>" ;
		
		//Get Smart Workflow help message
		$category=getRoleCategory($_SESSION[$guid]["gibbonRoleIDCurrent"], $connection2) ;
		if ($category=="Staff") {
			$smartWorkflowHelp=getSmartWorkflowHelp($connection2, $guid, 4) ;
			if ($smartWorkflowHelp!=false) {
				print $smartWorkflowHelp ;
			}
		}				
		
		//Proceed!
		if (isset($_GET["return"])) { returnProcess($guid, $_GET["return"], null, null); }
		
		print "<h3>" ;
		print __($guid, "Upcoming Deadlines") ;
		print "</h3>" ;
		
		$proceed=TRUE ;
		if ($viewBy=="class") {
			if ($gibbonCourseClassID=="") {
				$proceed=FALSE ;
			}
			else {
				try {
					if ($highestAction=="Lesson Planner_viewEditAllClasses" ) {
						$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonCourseClassID"=>$gibbonCourseClassID); 
						$sql="SELECT gibbonCourse.gibbonCourseID, gibbonCourseClass.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourseClass JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID ORDER BY course, class" ;
					}
					else {
						$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonCourseClassID"=>$gibbonCourseClassID, "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
						$sql="SELECT gibbonCourse.gibbonCourseID, gibbonCourseClass.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourseClassPerson JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID AND role='Teacher' ORDER BY course, class" ;
					}
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				if ($result->rowCount()!=1) {
					$proceed=FALSE ;
				}
			}
		}
		
		if ($proceed==FALSE) {
			print "<div class='error'>" ;
				print __($guid, "Your request failed because you do not have access to this action.") ;
			print "</div>" ;
		}
		else {
			try {
				if ($highestAction=="Lesson Planner_viewEditAllClasses" AND $show=="all") {
					$data=array("homeworkDueDateTime"=>date("Y-m-d H:i:s"), "date1"=>date("Y-m-d"), "date2"=>date("Y-m-d"), "timeEnd"=>date("H:i:s")); 
					$sql="SELECT gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, date, timeStart, timeEnd, viewableStudents, viewableParents, homework, homeworkDueDateTime FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE homework='Y' AND homeworkDueDateTime>:homeworkDueDateTime AND ((date<:date1) OR (date=:date2 AND timeEnd<=:timeEnd)) ORDER BY homeworkDueDateTime" ;
				}
				else {
					$data=array("gibbonPersonID"=> $_SESSION[$guid]["gibbonPersonID"], "gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
					$sql="
					(SELECT 'teacherRecorded' AS type, gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, date, timeStart, timeEnd, viewableStudents, viewableParents, homework, homeworkDueDateTime, role FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left' AND homework='Y' AND (role='Teacher' OR (role='Student' AND viewableStudents='Y')) AND homeworkDueDateTime>'" . date("Y-m-d H:i:s") . "' AND ((date<'" . date("Y-m-d") . "') OR (date='" . date("Y-m-d") . "' AND timeEnd<='" . date("H:i:s") . "')))
					UNION
					(SELECT 'studentRecorded' AS type, gibbonPlannerEntry.gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, date, timeStart, timeEnd, 'Y' AS viewableStudents, 'Y' AS viewableParents, 'Y' AS homework, gibbonPlannerEntryStudentHomework.homeworkDueDateTime, role FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) JOIN gibbonPlannerEntryStudentHomework ON (gibbonPlannerEntryStudentHomework.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID AND gibbonPlannerEntryStudentHomework.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left' AND (role='Teacher' OR (role='Student' AND viewableStudents='Y')) AND gibbonPlannerEntryStudentHomework.homeworkDueDateTime>'" . date("Y-m-d H:i:s") . "' AND ((date<'" . date("Y-m-d") . "') OR (date='" . date("Y-m-d") . "' AND timeEnd<='" . date("H:i:s") . "')))
					 ORDER BY homeworkDueDateTime, type" ;
				}
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
							
			if ($result->rowCount()<1) {
				print "<div class='success'>" ;
					print __($guid, "No upcoming deadlines!") ;
				print "</div>" ;
			}
			else {
				print "<ol>" ;
				while ($row=$result->fetch()) {
					$diff=(strtotime(substr($row["homeworkDueDateTime"],0,10)) - strtotime(date("Y-m-d")))/86400 ;
					$style="padding-right: 3px;" ;
					if ($category=="Student") {
						if ($row["type"]=="teacherRecorded") {
							//Calculate style for student-specified completion
							try {
								$dataCompletion=array("gibbonPlannerEntryID"=>$row["gibbonPlannerEntryID"], "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
								$sqlCompletion="SELECT gibbonPlannerEntryID FROM gibbonPlannerEntryStudentTracker WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPersonID=:gibbonPersonID AND homeworkComplete='Y'" ;
								$resultCompletion=$connection2->prepare($sqlCompletion);
								$resultCompletion->execute($dataCompletion);
							}
							catch(PDOException $e) { 
								print "<div class='error'>" . $e->getMessage() . "</div>" ; 
							}
							if ($resultCompletion->rowCount()==1) {
								$style.="; background-color: #B3EFC2" ;
							}
							//Calculate style for online submission completion
							try {
								$dataCompletion=array("gibbonPlannerEntryID"=>$row["gibbonPlannerEntryID"], "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
								$sqlCompletion="SELECT gibbonPlannerEntryID FROM gibbonPlannerEntryHomework WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPersonID=:gibbonPersonID AND version='Final'" ;
								$resultCompletion=$connection2->prepare($sqlCompletion);
								$resultCompletion->execute($dataCompletion);
							}
							catch(PDOException $e) { 
								print "<div class='error'>" . $e->getMessage() . "</div>" ; 
							}
							if ($resultCompletion->rowCount()==1) {
								$style.="; background-color: #B3EFC2" ;
							}
						}
						else {
							//Calculate style for student-recorded homework
							try {
								$dataCompletion=array("gibbonPlannerEntryID"=>$row["gibbonPlannerEntryID"], "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
								$sqlCompletion="SELECT gibbonPlannerEntryID FROM gibbonPlannerEntryStudentHomework WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPersonID=:gibbonPersonID AND homeworkComplete='Y'" ;
								$resultCompletion=$connection2->prepare($sqlCompletion);
								$resultCompletion->execute($dataCompletion);
							}
							catch(PDOException $e) { 
								print "<div class='error'>" . $e->getMessage() . "</div>" ; 
							}
							if ($resultCompletion->rowCount()==1) {
								$style.="; background-color: #B3EFC2" ;
							}
						}
					}
					
					//Calculate style for deadline
					if ($diff<2) {
						$style.="; border-right: 10px solid #cc0000" ;	
					}
					else if ($diff<4) {
						$style.="; border-right: 10px solid #D87718" ;	
					}
					
					print "<li style='$style'>" ;
					if ($viewBy=="class") {
						print "<b><a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/planner_view_full.php&gibbonPlannerEntryID=" . $row["gibbonPlannerEntryID"] . "&viewBy=class&gibbonCourseClassID=$gibbonCourseClassID'>" . $row["course"] . "." . $row["class"] . "</a> - " . $row["name"] . "</b><br/>" ;
					}
					else {
						print "<b><a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/planner_view_full.php&gibbonPlannerEntryID=" . $row["gibbonPlannerEntryID"] . "&viewBy=date&date=$date'>" . $row["course"] . "." . $row["class"] . "</a> - " . $row["name"] . "</b><br/>" ;
					}
					print "<span style='margin-left: 15px; font-style: italic'>Due at " . substr($row["homeworkDueDateTime"],11,5) . " on " . dateConvertBack($guid, substr($row["homeworkDueDateTime"],0,10)) ;
					print "</li>" ;
				}
				print "</ol>" ;
			}
		}
		
		print "<h3>" ;
		print __($guid, "All Homework") ;
		print "</h3>" ;
		
		$completionArray=array() ;
		if ($category=="Student") {
			try {
				$dataCompletion=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonSchoolYearID2"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonID2"=>$_SESSION[$guid]["gibbonPersonID"] ); 
				$sqlCompletion="
				(SELECT 'teacherRecorded' AS type, gibbonPlannerEntryStudentTracker.gibbonPlannerEntryID FROM gibbonPlannerEntryStudentTracker JOIN gibbonPlannerEntry ON (gibbonPlannerEntryStudentTracker.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID) JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID AND homeworkComplete='Y')
				UNION
				(SELECT 'studentRecorded' AS type, gibbonPlannerEntry.gibbonPlannerEntryID FROM gibbonPlannerEntryStudentHomework JOIN gibbonPlannerEntry ON (gibbonPlannerEntryStudentHomework.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID) JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID2 AND gibbonPersonID=:gibbonPersonID2 AND homeworkComplete='Y')
				ORDER BY gibbonPlannerEntryID, type
				" ;
				$resultCompletion=$connection2->prepare($sqlCompletion);
				$resultCompletion->execute($dataCompletion);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
			while ($rowCompletion=$resultCompletion->fetch()) {
				$completionArray[$rowCompletion["gibbonPlannerEntryID"]][0]="checked" ;
				$completionArray[$rowCompletion["gibbonPlannerEntryID"]][1]=$rowCompletion["type"] ;
			}
		}
		
		$filter=NULL ;
		$filter2=NULL ;
		$data=array() ;
		if ($gibbonCourseClassIDFilter!="") {
			$data["gibbonCourseClassIDFilter"]=$gibbonCourseClassIDFilter ;
			$data["gibbonCourseClassIDFilter2"]=$gibbonCourseClassIDFilter ;
			$filter=" AND gibbonPlannerEntry.gibbonCourseClassID=:gibbonCourseClassIDFilter" ;
			$filte2=" AND gibbonPlannerEntry.gibbonCourseClassID=:gibbonCourseClassIDFilte2" ;
		}
		
		try {
			if ($highestAction=="Lesson Planner_viewEditAllClasses" AND $show=="all") {
				$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "date1"=>date("Y-m-d"), "date2"=>date("Y-m-d"), "timeEnd"=>date("H:i:s")); 
				$sql="SELECT gibbonPlannerEntryID, gibbonUnitID, gibbonHookID, gibbonPlannerEntry.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.gibbonCourseClassID, gibbonPlannerEntry.name, date, timeStart, timeEnd, 'Y' AS viewableStudents, 'Y' AS viewableParents, homework, homeworkDueDateTime, homeworkDetails, homeworkSubmission, homeworkSubmissionRequired, homeworkCrowdAssess FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE homework='Y' AND gibbonSchoolYearID=:gibbonSchoolYearID AND (date<:date1 OR (date=:date2 AND timeEnd<=:timeEnd)) $filter ORDER BY date DESC, timeStart DESC" ; 
			}
			else {
				$data["gibbonPersonID"]=$_SESSION[$guid]["gibbonPersonID"] ;
				$data["gibbonSchoolYearID"]=$_SESSION[$guid]["gibbonSchoolYearID"] ;
				$data["gibbonPersonID"]=$_SESSION[$guid]["gibbonPersonID"] ;
				$sql="
				(SELECT 'teacherRecorded' AS type, gibbonPlannerEntryID, gibbonUnitID, gibbonHookID, gibbonPlannerEntry.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, date, timeStart, timeEnd, viewableStudents, viewableParents, homework, role, homeworkDueDateTime, homeworkDetails, homeworkSubmission, homeworkSubmissionRequired FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left' AND homework='Y' AND gibbonSchoolYearID=:gibbonSchoolYearID AND ((date<'" . date("Y-m-d") . "') OR (date='" . date("Y-m-d") . "' AND timeEnd<='" . date("H:i:s") . "')) $filter)
				UNION
				(SELECT 'studentRecorded' AS type, gibbonPlannerEntry.gibbonPlannerEntryID, gibbonUnitID, gibbonHookID, gibbonPlannerEntry.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, date, timeStart, timeEnd, 'Y' AS viewableStudents, 'Y' AS viewableParents, 'Y' AS homework, role, gibbonPlannerEntryStudentHomework.homeworkDueDateTime AS homeworkDueDateTime, gibbonPlannerEntryStudentHomework.homeworkDetails AS homeworkDetails, 'N' AS homeworkSubmission, '' AS homeworkSubmissionRequired FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) JOIN gibbonPlannerEntryStudentHomework ON (gibbonPlannerEntryStudentHomework.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID AND gibbonPlannerEntryStudentHomework.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID) WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left' AND gibbonSchoolYearID=:gibbonSchoolYearID AND ((date<'" . date("Y-m-d") . "') OR (date='" . date("Y-m-d") . "' AND timeEnd<='" . date("H:i:s") . "')) $filter)
				ORDER BY date DESC, timeStart DESC" ; 
			}
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
		
		//Only show add if user has edit rights
		if ($result->rowCount()<1) {
			print "<div class='error'>" ;
			print __($guid, "There are no records to display.") ;
			print "</div>" ;
		}
		else {
			print "<div class='linkTop'>" ;
				print "<form method='get' action='" . $_SESSION[$guid]["absoluteURL"] . "/index.php'>" ;
					print "<table class='blank' cellspacing='0' style='float: right; width: 250px'>" ;	
						print "<tr>" ;
							print "<td style='width: 190px'>" ; 
								print "<select name='gibbonCourseClassIDFilter' id='gibbonCourseClassIDFilter' style='width:190px'>" ;
									print "<option value=''></option>" ;
									try {
										if ($highestAction=="Lesson Planner_viewEditAllClasses" AND $show=="all") {
											$dataSelect=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "date"=>date("Y-m-d")); 
											$sqlSelect="SELECT DISTINCT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.gibbonCourseClassID FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE homework='Y' AND gibbonSchoolYearID=:gibbonSchoolYearID AND date<=:date ORDER BY course, class" ; 
										}
										else {
											$dataSelect=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "date"=>date("Y-m-d"), "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
											$sqlSelect="SELECT DISTINCT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.gibbonCourseClassID FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left' AND homework='Y' AND gibbonSchoolYearID=:gibbonSchoolYearID AND date<=:date ORDER BY course, class" ; 
										}
										$resultSelect=$connection2->prepare($sqlSelect);
										$resultSelect->execute($dataSelect);
									}
									catch(PDOException $e) { }
									while ($rowSelect=$resultSelect->fetch()) {
										$selected="" ;
										if ($rowSelect["gibbonCourseClassID"]==$gibbonCourseClassIDFilter) {
											$selected="selected" ;
										}
										print "<option $selected value='" . $rowSelect["gibbonCourseClassID"] . "'>" . htmlPrep($rowSelect["course"]) . "." . htmlPrep($rowSelect["class"]) . "</option>" ;
									}
								 print "</select>" ;
							print "</td>" ;
							print "<td class='right'>" ;
								print "<input type='submit' value='" . __($guid, 'Go') . "' style='margin-right: 0px'>" ;
								print "<input type='hidden' name='q' value='/modules/Planner/planner_deadlines.php'>" ;
							print "</td>" ;
						print "</tr>" ;
					print "</table>" ;
				print "</form>" ;
			print "</div>" ;
			print "<form method='post' action='" . $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/planner_deadlinesProcess.php?viewBy=$viewBy&subView=$subView&address=" . $_SESSION[$guid]["address"] . "&gibbonCourseClassIDFilter=$gibbonCourseClassIDFilter'>" ;
				print "<table cellspacing='0' style='width: 100%; margin-top: 60px'>" ;
					
					if ($category=="Student") {
						?>
						<tr>
							<td class="right" colspan=7>
								<input type="submit" value="<?php print __($guid, "Submit") ; ?>">
							</td>
						</tr>
						<?php
					}
					print "<tr class='head'>" ;
						print "<th>" ;
							print __($guid, "Class") . "</br>" ;
							print "<span style='font-size: 85%; font-style: italic'>" . __($guid, 'Date') . "</span>" ;
						print "</th>" ;
						print "<th>" ;
							print __($guid, "Lesson") . "</br>" ;
							print "<span style='font-size: 85%; font-style: italic'>" . __($guid, 'Unit') . "</span>" ;
						print "</th>" ;
						print "<th style='min-width: 25%'>" ;
							print __($guid, "Type") . "<br/>" ;
							print "<span style='font-size: 85%; font-style: italic'>" . __($guid, 'Details') . "</span>" ;
						print "</th>" ;
						print "<th>" ;
							print __($guid, "Deadline") ;
						print "</th>" ;
						
						if ($category=="Student") {
							print "<th colspan=2>" ;
								print __($guid, "Complete?") ;
							print "</th>" ;
						}
						else {
							print "<th>" ;
								print sprintf(__($guid, 'Online%1$sSubmission'), "<br/>") ;
							print "</th>" ;
						}
						print "<th>" ;
							print __($guid, "Actions") ;
						print "</th>" ;
					print "</tr>" ;
					
					$count=0;
					$rowNum="odd" ;
					while ($row=$result->fetch()) {
						if (!($row["role"]=="Student" AND $row["viewableStudents"]=="N")) {
							if ($count%2==0) {
								$rowNum="even" ;
							}
							else {
								$rowNum="odd" ;
							}
							$count++ ;
							
							//Deal with homework completion
							if ($category=="Student") {
								$now=date("Y-m-d H:i:s") ;
								if (isset($completionArray[$row["gibbonPlannerEntryID"]][0]) AND isset($completionArray[$row["gibbonPlannerEntryID"]][1])) {
									if ($completionArray[$row["gibbonPlannerEntryID"]][1]==$row["type"]) {
										$rowNum="current" ;
									}
								}
								else {
									if ($row["homeworkDueDateTime"]<$now) {
										$rowNum="error" ;
									}
								}
								$status="" ;
								$completion="" ;
								if ($row["homeworkSubmission"]=="Y") {
									$status="<b>OS: " . $row["homeworkSubmissionRequired"] . "</b><br/>" ;
									try {
										$dataVersion=array("gibbonPlannerEntryID"=>$row["gibbonPlannerEntryID"], "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
										$sqlVersion="SELECT * FROM gibbonPlannerEntryHomework WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPersonID=:gibbonPersonID ORDER BY count DESC" ;
										$resultVersion=$connection2->prepare($sqlVersion);
										$resultVersion->execute($dataVersion);
									}
									catch(PDOException $e) { 
										print "<div class='error'>" . $e->getMessage() . "</div>" ; 
									}
									if ($resultVersion->rowCount()<1) {
										//Before deadline
										if (date("Y-m-d H:i:s")<$row["homeworkDueDateTime"]) {
											if ($row["homeworkSubmissionRequired"]=="Compulsory") {
												$status.="Pending" ;
												$completion="<input disabled type='checkbox'>" ;
											}
											else {
												$status.=__($guid, "Pending") ;
												$completion="<input " . $completionArray[$row["gibbonPlannerEntryID"]] . " name='complete-$count' type='checkbox'>" ;
											}
										}
										//After
										else {
											if ($row["homeworkSubmissionRequired"]=="Compulsory") {
												$status.="<div style='color: #ff0000; font-weight: bold; border: 2px solid #ff0000; padding: 2px 4px; margin: 2px 0px'>" . __($guid, 'Incomplete') . "</div>" ;
												$completion="<input disabled type='checkbox'>" ;
											}
											else {
												$status.=__($guid, "Not submitted online") ;
												@$completion="<input " . $completionArray[$row["gibbonPlannerEntryID"]] . " name='complete-$count' type='checkbox'>" ;
											}
										}
									}
									else {
										$rowVersion=$resultVersion->fetch() ;
										if ($rowVersion["status"]=="On Time" OR $rowVersion["status"]=="Exemption") {
											$status.=$rowVersion["status"] ;
											$completion="<input disabled checked type='checkbox'>" ;
											$rowNum="current" ;
										} 
										else {
											if ($row["homeworkSubmissionRequired"]=="Compulsory") {
												$status.="<div style='color: #ff0000; font-weight: bold; border: 2px solid #ff0000; padding: 2px 4px; margin: 2px 0px'>" . $rowVersion["status"] . "</div>" ;
												$completion="<input disabled checked type='checkbox'>" ;
											}
											else {
												$status.=$rowVersion["status"] ;
												$completion="<input disabled checked type='checkbox'>" ;
											}
										}
									}
								}
								else {
									$completion="<input " ;
									if (isset($completionArray[$row["gibbonPlannerEntryID"]][0]) AND isset($completionArray[$row["gibbonPlannerEntryID"]][1])) {
										if ($completionArray[$row["gibbonPlannerEntryID"]][1]==$row["type"]) {
											$completion.=$completionArray[$row["gibbonPlannerEntryID"]][0] ;
										}
									}
									$completion.=" name='complete-$count' type='checkbox'>" ;
									$completion.="<input type='hidden' name='completeType-$count' value='" . $row["type"] . "'/>" ;
								}
							}
							
							//COLOR ROW BY STATUS!
							print "<tr class=$rowNum>" ;
								print "<td>" ;
									print "<b>" . $row["course"] . "." . $row["class"] . "</b></br>" ;
									print "<span style='font-size: 85%; font-style: italic'>" . dateConvertBack($guid, $row["date"]) . "</span>" ;
								print "</td>" ;
								print "<td>" ;
									print "<b>" . $row["name"] . "</b><br/>" ;
									print "<span style='font-size: 85%; font-style: italic'>" ;
										$unit=getUnit($connection2, $row["gibbonUnitID"], $row["gibbonHookID"], $row["gibbonCourseClassID"]) ;
										if (isset($unit[0])) {
											print $unit[0] ;
											if ($unit[1]!="") {
												print "<br/><i>" . $unit[1] . " Unit</i>" ;
											}
										}
									print "</span>" ;
								print "</td>" ;
								print "<td>" ;
									if ($row["type"]=="teacherRecorded") {
										print "Teacher Recorded" ;
									}
									else {
										print "Student Recorded" ;
									}
									print  "<br/>" ;
									print "<span style='font-size: 85%; font-style: italic'>" ;
										if ($row["homeworkDetails"]!="") {
											if (strlen(strip_tags($row["homeworkDetails"]))<21) {
												print strip_tags($row["homeworkDetails"]) ;
											}
											else {
												print "<span $style title='" . htmlPrep(strip_tags($row["homeworkDetails"])) . "'>" . substr(strip_tags($row["homeworkDetails"]), 0, 20) . "...</span>" ;
											}
										}
									print "</span>" ;
								print "</td>" ;
								print "<td>" ;
									print dateConvertBack($guid, substr($row["homeworkDueDateTime"],0,10)) ;
								print "</td>" ;
								if ($category=="Student") {
									print "<td>" ;
										print $status ;
									print "</td>" ;
									print "<td>" ;
										print $completion ;
										print "<input type='hidden' name='count[]' value='$count'>" ;
										print "<input type='hidden' name='gibbonPlannerEntryID-$count' value='" . $row["gibbonPlannerEntryID"] . "'>" ;
									print "</td>" ;
								}
								else {
									print "<td>" ;
										if ($row["homeworkSubmission"]=="Y") {
											print "<b>" . $row["homeworkSubmissionRequired"] . "</b><br/>" ;
											if ($row["role"]=="Teacher") {
												try {
													$dataVersion=array("gibbonPlannerEntryID"=>$row["gibbonPlannerEntryID"]); 
													$sqlVersion="SELECT DISTINCT gibbonPlannerEntryHomework.gibbonPersonID FROM gibbonPlannerEntryHomework JOIN gibbonPerson ON (gibbonPlannerEntryHomework.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND version='Final' AND gibbonPlannerEntryHomework.status='On Time'" ;
													$resultVersion=$connection2->prepare($sqlVersion);
													$resultVersion->execute($dataVersion);
												}
												catch(PDOException $e) { 
													print "<div class='error'>" . $e->getMessage() . "</div>" ; 
												}
												$onTime=$resultVersion->rowCount() ;
												print "<span style='font-size: 85%; font-style: italic'>On Time: $onTime</span><br/>" ;
												
												try {
													$dataVersion=array("gibbonPlannerEntryID"=>$row["gibbonPlannerEntryID"]); 
													$sqlVersion="SELECT DISTINCT gibbonPlannerEntryHomework.gibbonPersonID FROM gibbonPlannerEntryHomework JOIN gibbonPerson ON (gibbonPlannerEntryHomework.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND version='Final' AND gibbonPlannerEntryHomework.status='Late'" ;
													$resultVersion=$connection2->prepare($sqlVersion);
													$resultVersion->execute($dataVersion);
												}
												catch(PDOException $e) { 
													print "<div class='error'>" . $e->getMessage() . "</div>" ; 
												}
												$late=$resultVersion->rowCount() ;
												print "<span style='font-size: 85%; font-style: italic'>Late: $late</span><br/>" ;
												
												try {
													$dataVersion=array("gibbonCourseClassID"=>$row["gibbonCourseClassID"]); 
													$sqlVersion="SELECT gibbonCourseClassPerson.gibbonPersonID FROM gibbonCourseClassPerson JOIN gibbonPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonCourseClassID=:gibbonCourseClassID AND role='Student' AND status='Full'" ;
													$resultVersion=$connection2->prepare($sqlVersion);
													$resultVersion->execute($dataVersion);
												}
												catch(PDOException $e) { 
													print "<div class='error'>" . $e->getMessage() . "</div>" ; 
												}
												$class=$resultVersion->rowCount() ;
												if (date("Y-m-d H:i:s")<$row["homeworkDueDateTime"]) {
													print "<span style='font-size: 85%; font-style: italic'>" . __($guid, 'Pending') . ": " . ($class-$late-$onTime) . "</span><br/>" ;
												}
												else {
													if ($row["homeworkSubmissionRequired"]=="Compulsory") {
														print "<span style='font-size: 85%; font-style: italic'>" . __($guid, 'Incomplete') . ": " . ($class-$late-$onTime) . "</span><br/>" ;
													}
													else {
														print "<span style='font-size: 85%; font-style: italic'>" . __($guid, 'Not Submitted Online') . ": " . ($class-$late-$onTime) . "</span><br/>" ;
													}
												}
											}
										}
									print "</td>" ;
								}
								print "<td>" ;
									print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/planner_view_full.php&search=$gibbonPersonID&gibbonPlannerEntryID=" . $row["gibbonPlannerEntryID"] . "&viewBy=class&gibbonCourseClassID=" . $row["gibbonCourseClassID"] . "'><img title='" . __($guid, 'View') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/plus.png'/></a> " ;
								print "</td>" ;
							print "</tr>" ;
						}
					}
					if ($category=="Student") {
						?>
						<tr>
							<td class="right" colspan=7>
								<input type="submit" value="<?php print __($guid, "Submit") ; ?>">
							</td>
						</tr>
						<?php
					}
				print "</table>" ;
			print "</form>" ;
		}
	}
	
	//Print sidebar
	$_SESSION[$guid]["sidebarExtra"]=sidebarExtra($guid, $connection2, $todayStamp, $_SESSION[$guid]["gibbonPersonID"], $dateStamp, $gibbonCourseClassID ) ;
}
?>