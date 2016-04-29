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

if (isActionAccessible($guid, $connection2, "/modules/Planner/planner_view_full_post.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Get action with highest precendence
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
		$gibbonPlannerEntryID=$_GET["gibbonPlannerEntryID"] ;
		
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
						$sql="(SELECT gibbonPlannerEntry.gibbonPlannerEntryID, gibbonCourseClass.gibbonCourseClassID, gibbonUnitID, gibbonHookID, gibbonPlannerEntry.gibbonCourseClassID, gibbonPlannerEntry.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, date, timeStart, timeEnd, summary, gibbonPlannerEntry.description, teachersNotes, homework, homeworkDueDateTime, homeworkDetails, viewableStudents, viewableParents, role, homeworkSubmission, homeworkSubmissionDateOpen, homeworkSubmissionDrafts, homeworkSubmissionType FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourseClassPerson.gibbonPersonID=$gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left' AND gibbonPlannerEntry.gibbonPlannerEntryID=$gibbonPlannerEntryID) UNION (SELECT gibbonPlannerEntry.gibbonPlannerEntryID, gibbonCourseClass.gibbonCourseClassID, gibbonUnitID, gibbonHookID, gibbonPlannerEntry.gibbonCourseClassID, gibbonPlannerEntry.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, date, timeStart, timeEnd, summary, gibbonPlannerEntry.description, teachersNotes, homework, homeworkDueDateTime, homeworkDetails, viewableStudents, viewableParents, role, homeworkSubmission, homeworkSubmissionDateOpen, homeworkSubmissionDrafts, homeworkSubmissionType FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonPlannerEntryGuest ON (gibbonPlannerEntryGuest.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE date=:date AND gibbonPlannerEntryGuest.gibbonPersonID=$gibbonPersonID AND gibbonPlannerEntry.gibbonPlannerEntryID=$gibbonPlannerEntryID) ORDER BY date, timeStart" ; 
					}
				}
			}
			else if ($highestAction=="Lesson Planner_viewMyClasses" OR $highestAction=="Lesson Planner_viewAllEditMyClasses" ) {
				$data=array("date"=>$date) ;
				$sql="(SELECT gibbonPlannerEntry.gibbonPlannerEntryID, gibbonCourseClass.gibbonCourseClassID, gibbonUnitID, gibbonHookID, gibbonPlannerEntry.gibbonCourseClassID, gibbonPlannerEntry.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, date, timeStart, timeEnd, summary, gibbonPlannerEntry.description, teachersNotes, homework, homeworkDueDateTime, homeworkDetails, viewableStudents, viewableParents, role, homeworkSubmission, homeworkSubmissionDateOpen, homeworkSubmissionDrafts, homeworkSubmissionType FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourseClassPerson.gibbonPersonID=" . $_SESSION[$guid]["gibbonPersonID"] . " AND NOT role='Student - Left' AND NOT role='Teacher - Left' AND gibbonPlannerEntry.gibbonPlannerEntryID=$gibbonPlannerEntryID) UNION (SELECT gibbonPlannerEntry.gibbonPlannerEntryID, gibbonCourseClass.gibbonCourseClassID, gibbonUnitID, gibbonHookID, gibbonPlannerEntry.gibbonCourseClassID, gibbonPlannerEntry.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, date, timeStart, timeEnd, summary, gibbonPlannerEntry.description, teachersNotes, homework, homeworkDueDateTime, homeworkDetails, viewableStudents, viewableParents, role, homeworkSubmission, homeworkSubmissionDateOpen, homeworkSubmissionDrafts, homeworkSubmissionType FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonPlannerEntryGuest ON (gibbonPlannerEntryGuest.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE date=:date AND gibbonPlannerEntryGuest.gibbonPersonID=" . $_SESSION[$guid]["gibbonPersonID"] . " AND gibbonPlannerEntry.gibbonPlannerEntryID=$gibbonPlannerEntryID) ORDER BY date, timeStart" ; 
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
				print "<div class='warning'>" ;
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
				print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/planner.php$params'>" . __($guid, 'Planner') . " $extra</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/planner_view_full.php$params&gibbonPlannerEntryID=$gibbonPlannerEntryID'>" . __($guid, 'View Lesson Plan') . "</a> > </div><div class='trailEnd'>" . __($guid, 'Add Comment') . "</div>" ;
				print "</div>" ;
				
				if (isset($_GET["return"])) { returnProcess($guid, $_GET["return"], null, null); }
		
				if (($row["role"]=="Student" AND $row["viewableStudents"]=="N") AND ($highestAction=="Lesson Planner_viewMyChildrensClasses" AND $row["viewableParents"]=="N")) {
					print "<div class='warning'>" ;
						print __($guid, "The selected record does not exist, or you do not have access to it.") ;
					print "</div>" ;
				}
				else {						
					print "<h2>" ;
					print __($guid, "Planner Discussion Post") ;
					print "</h2>" ;

					?>
					<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/planner_view_full_postProcess.php" ?>">
						<table class='smallIntBorder fullWidth' cellspacing='0'>	
							<tr>
								<td colspan=2> 
									<b><?php print __($guid, 'Write your comment below:') ?></b> 
									<?php print getEditor($guid,  TRUE, "comment", "", 20, TRUE ) ?>
								</td>
							</tr>
							<tr>
								<td class="right" colspan=2>
									<?php
									print "<input type='hidden' name='search' value='" . $_GET["search"] . "'>" ;
									print "<input type='hidden' name='replyTo' value='" . $replyTo . "'>" ;
									print "<input type='hidden' name='params' value='$params'>" ;
									print "<input type='hidden' name='gibbonPlannerEntryID' value='$gibbonPlannerEntryID'>" ;
									print "<input type='hidden' name='address' value='" . $_SESSION[$guid]["address"] . "'>" ;
									?>
									
									<input type="submit" value="<?php print __($guid, "Submit") ; ?>">
								</td>
							</tr>
						</table>
					</form>
					<?php
				}
			}
		}
	}
}
?>