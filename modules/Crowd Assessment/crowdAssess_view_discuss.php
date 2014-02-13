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

if (isActionAccessible($guid, $connection2, "/modules/Crowd Assessment/crowdAssess_view_discuss.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "You do not have access to this action." ;
	print "</div>" ;
}
else {
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>Home</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/crowdAssess.php'>View All Assessments</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/crowdAssess_view.php&gibbonPlannerEntryID=" . $_GET["gibbonPlannerEntryID"] . "'>View Assessment</a> > </div><div class='trailEnd'>Discuss</div>" ;
	print "</div>" ;
	
	if (isset($_GET["updateReturn"])) { $updateReturn=$_GET["updateReturn"] ; } else { $updateReturn="" ; }
	$updateReturnMessage ="" ;
	$class="error" ;
	if (!($updateReturn=="")) {
		if ($updateReturn=="fail0") {
			$updateReturnMessage ="Your request failed because you do not have access to this action." ;	
		}
		else if ($updateReturn=="fail1") {
			$updateReturnMessage ="Your request failed because your inputs were invalid." ;	
		}
		else if ($updateReturn=="fail2") {
			$updateReturnMessage ="Your request failed due to a database error." ;	
		}
		else if ($updateReturn=="fail3") {
			$updateReturnMessage ="Your request failed because your inputs were invalid." ;	
		}
		else if ($updateReturn=="fail4") {
			$updateReturnMessage ="Your request failed because your inputs were invalid." ;	
		}
		if ($updateReturn=="fail5") {
			$updateReturnMessage ="Your request failed because you do not have access to this action." ;	
		}
		else if ($updateReturn=="success0") {
			$updateReturnMessage ="Your request was completed successfully." ;	
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
					
					//Get details of homework
					try {
						$dataWork=array("gibbonPlannerEntryID"=>$gibbonPlannerEntryID, "gibbonPersonID"=>$gibbonPersonID, "gibbonPlannerEntryHomeworkID"=>$gibbonPlannerEntryHomeworkID); 
						$sqlWork="SELECT * FROM gibbonPlannerEntryHomework WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPersonID=:gibbonPersonID AND gibbonPlannerEntryHomeworkID=:gibbonPlannerEntryHomeworkID ORDER BY count DESC" ;
						$resultWork=$connection2->prepare($sqlWork);
						$resultWork->execute($dataWork);
					}
					catch(PDOException $e) { 
						print "<div class='error'>" . $e->getMessage() . "</div>" ; 
					}

					if ($resultWork->rowCount()!=1) {
						print "<div class='error'>" ;
							print "There is currently no work to assess." ;
						print "</div>" ;
					}
					else {
						$rowWork=$resultWork->fetch() ;
						
						print "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>" ;
							print "<tr>" ;
								print "<td style='width: 33%; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>Student</span><br/>" ;
									print "<a href='index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=" . $rowList["gibbonPersonID"] . "'>" . formatName("", $rowList["preferredName"], $rowList["surname"], "Student") . "</a>" ;
								print "<td style='width: 34%; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>Version</span><br/>" ;
									if ($rowWork["version"]=="Final") {
										$linkText="Final" ;
									}
									else {
										$linkText="Draft" . $rowWork["count"] ;
									}
									
									if ($rowWork["type"]=="File") {
										print "<span title='" . $rowWork["version"] . ". Submitted at " . substr($rowWork["timestamp"],11,5) . " on " . dateConvertBack($guid, substr($rowWork["timestamp"],0,10)) . "'><a href='" . $_SESSION[$guid]["absoluteURL"] . "/" . $rowWork["location"] ."'>$linkText</a></span>" ;
									}
									else {
										print "<span title='" . $rowWork["version"] . ". Submitted at " . substr($rowWork["timestamp"],11,5) . " on " . dateConvertBack($guid, substr($rowWork["timestamp"],0,10)) . "'><a target='_blank' href='" . $rowWork["location"] ."'>$linkText</a></span>" ;
									}
								print "</td>" ;
								print "</td>" ;
								print "<td style='width: 34%; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>Like Count</span><br/>" ;
									try {
										$dataLike=array("gibbonPlannerEntryHomeworkID"=>$rowWork["gibbonPlannerEntryHomeworkID"]); 
										$sqlLike="SELECT * FROM gibbonCrowdAssessLike WHERE gibbonPlannerEntryHomeworkID=:gibbonPlannerEntryHomeworkID" ;
										$resultLike=$connection2->prepare($sqlLike);
										$resultLike->execute($dataLike);
									}
									catch(PDOException $e) { 
										print "<div class='error'>" . $e->getMessage() . "</div>" ; 
									}
									print $resultLike->rowCount() ;
								print "</td>" ;
							print "</tr>" ;
						print "</table>" ;
						
						print "<div style='margin: 0px' class='linkTop'>" ;
						print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/crowdAssess_view_discuss_post.php&gibbonPersonID=$gibbonPersonID&gibbonPlannerEntryID=$gibbonPlannerEntryID&gibbonPlannerEntryHomeworkID=$gibbonPlannerEntryHomeworkID'><img title='New' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/page_new.gif'/></a>" ;
						print "</div>" ;
						
						print "<div style='margin-bottom: 0px' class='success'>" ;
							print "Items in <span style='color: #c00'>red</span> are new since your last login. Items in green are older." ;
						print "</div>" ;
						
						//Get discussion
						print getThread($guid, $connection2, $rowWork["gibbonPlannerEntryHomeworkID"], NULL, 0, NULL, $gibbonPersonID, $gibbonPlannerEntryID) ;
						
						print "<br/><br/>" ;
					}
				}
			}
		}
	}
	//Print sidebar
	$_SESSION[$guid]["sidebarExtra"]=sidebarExtra($guid, $connection2) ;
}
?>