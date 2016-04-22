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

if (isActionAccessible($guid, $connection2, "/modules/Crowd Assessment/crowdAssess_view_discuss_post.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/crowdAssess.php'>" . __($guid, 'View All Assessments') . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/crowdAssess_view.php&gibbonPlannerEntryID=" . $_GET["gibbonPlannerEntryID"] . "'>" . __($guid, 'View Assessment') . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/crowdAssess_view_discuss.php&gibbonPlannerEntryID=" . $_GET["gibbonPlannerEntryID"] . "&gibbonPlannerEntryHomeworkID=" . $_GET["gibbonPlannerEntryHomeworkID"] . "&gibbonPersonID=" . $_GET["gibbonPersonID"] . "'>" . __($guid, 'Discuss') . "</a> > </div><div class='trailEnd'>" . __($guid, 'Add Post') . "</div>" ;
	print "</div>" ;

	if (isset($_GET["return"])) { returnProcess($guid, $_GET["return"], null, null); }
	
	//Get class variable
	$gibbonPersonID=$_GET["gibbonPersonID"] ;
	$gibbonPlannerEntryID=$_GET["gibbonPlannerEntryID"] ;
	$gibbonPlannerEntryHomeworkID=$_GET["gibbonPlannerEntryHomeworkID"] ;
	if ($gibbonPersonID=="" OR $gibbonPlannerEntryID=="" OR $gibbonPlannerEntryHomeworkID=="") {
		print "<div class='warning'>" ;
			print __($guid, "You have not specified one or more required parameters.") ;
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
				print __($guid, "The selected record does not exist, or you do not have access to it.") ;
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
						print __($guid, "There is currently no work to assess.") ;
					print "</div>" ;
				}
				else {
					$rowList=$resultList->fetch() ;
					
					?>
					<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/crowdAssess_view_discuss_postProcess.php?gibbonPlannerEntryID=$gibbonPlannerEntryID&gibbonPlannerEntryHomeworkID=$gibbonPlannerEntryHomeworkID&address=" . $_GET["q"] . "&gibbonPersonID=$gibbonPersonID&replyTo=$replyTo" ?>">
						<table class='smallIntBorder fullWidth' cellspacing='0'>	
							<tr>
								<td colspan=2> 
									<b><?php print __($guid, 'Write your comment below:') ?></b> 
									<?php print getEditor($guid,  TRUE, "comment" ) ?>
								</td>
							</tr>
							<tr>
								<td class="right" colspan=2>
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