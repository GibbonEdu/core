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

if (isActionAccessible($guid, $connection2, "/modules/Crowd Assessment/crowdAssess_view_discuss_post.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "You do not have access to this action." ;
	print "</div>" ;
}
else {
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>Home</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/crowdAssess.php'>View All Assessments</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/crowdAssess_view.php&gibbonPlannerEntryID=" . $_GET["gibbonPlannerEntryID"] . "'>View Assessment</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/crowdAssess_view_discuss.php&gibbonPlannerEntryID=" . $_GET["gibbonPlannerEntryID"] . "&gibbonPlannerEntryHomeworkID=" . $_GET["gibbonPlannerEntryHomeworkID"] . "&gibbonPersonID=" . $_GET["gibbonPersonID"] . "'>Discuss</a> > </div><div class='trailEnd'>Add Post</div>" ;
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
			$updateReturnMessage ="Update failed because your inputs were invalid." ;	
		}
		else if ($updateReturn=="fail4") {
			$updateReturnMessage ="Update failed some values need to be unique but were not." ;	
		}
		if ($updateReturn=="fail5") {
			$updateReturnMessage ="Update failed because you do not have access to this lesson for crowd assessment." ;	
		}
		else if ($updateReturn=="success0") {
			$updateReturnMessage ="Update was successful." ;	
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $updateReturnMessage;
		print "</div>" ;
	} 
	
	//Get class variable
	$gibbonPersonID=$_GET["gibbonPersonID"] ;
	$gibbonPlannerEntryID=$_GET["gibbonPlannerEntryID"] ;
	$gibbonPlannerEntryHomeworkID=$_GET["gibbonPlannerEntryHomeworkID"] ;
	if ($gibbonPersonID=="" OR $gibbonPlannerEntryID=="" OR $gibbonPlannerEntryHomeworkID=="") {
		print "<div class='warning'>" ;
			print "Student, lesson or homework has not been specified ." ;
		print "</div>" ;
	}
	//Check existence of and access to this class.
	else {	
		$and=" AND gibbonPlannerEntryID=$gibbonPlannerEntryID" ;
		$sql=getLessons($guid, $connection2, $and) ;
		try {
			$result=$connection2->prepare($sql[1]);
			$result->execute($sql[0]);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
		
		if ($result->rowCount()!=1) {
			print "<div class='error'>" ;
				print "You do not have permission to access the specified lesson for crowd assessment." ;
			print "</div>" ;
		}
		else {
			$row=$result->fetch() ;
			
			$role=getCARole($guid, $connection2, $row["gibbonCourseClassID"]) ;
			$replyTo=NULL ;
			if (isset($_GET["replyTo"])) {
				$replyTo=$_GET["replyTo"] ;
			}
			$sqlList=getStudents($guid, $connection2, $role, $row["gibbonCourseClassID"], $row["homeworkCrowdAssessOtherTeachersRead"], $row["homeworkCrowdAssessOtherParentsRead"], $row["homeworkCrowdAssessSubmitterParentsRead"], $row["homeworkCrowdAssessClassmatesParentsRead"], $row["homeworkCrowdAssessOtherStudentsRead"], $row["homeworkCrowdAssessClassmatesRead"], " AND gibbonPerson.gibbonPersonID=$gibbonPersonID") ;
			
			if ($sqlList[1]!="") {
				try {
					$resultList=$connection2->prepare($sqlList[1]);
					$resultList->execute($sqlList[0]);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				
				if ($resultList->rowCount()!=1) {
					print "<div class='error'>" ;
						print "There is currently no work to assess." ;
					print "</div>" ;
				}
				else {
					$rowList=$resultList->fetch() ;
					
					?>
					<form method="post" action="<? print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/crowdAssess_view_discuss_postProcess.php?gibbonPlannerEntryID=$gibbonPlannerEntryID&gibbonPlannerEntryHomeworkID=$gibbonPlannerEntryHomeworkID&address=" . $_GET["q"] . "&gibbonPersonID=$gibbonPersonID&replyTo=$replyTo" ?>">
						<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
							<tr>
								<td colspan=2> 
									<b>Write your comment below:</b> 
									<? print getEditor($guid,  TRUE, "comment" ) ?>
								</td>
							</tr>
							<tr>
								<td class="right" colspan=2>
									<input type="submit" value="Submit">
								</td>
							</tr>
						</table>
					</form>
					<?
				}
			}
		}
	}
	//Print sidebar
	$_SESSION[$guid]["sidebarExtra"]=sidebarExtra($guid, $connection2) ;
}
?>