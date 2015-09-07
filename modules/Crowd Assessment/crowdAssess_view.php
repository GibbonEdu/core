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

if (isActionAccessible($guid, $connection2, "/modules/Crowd Assessment/crowdAssess_view.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/crowdAssess.php'>" . _('View All Assessments') . "</a> > </div><div class='trailEnd'>" . _('View Assessment') . "</div>" ;
	print "</div>" ;
	
	if (isset($_GET["updateReturn"])) { $updateReturn=$_GET["updateReturn"] ; } else { $updateReturn="" ; }
	$updateReturnMessage="" ;
	$class="error" ;
	if (!($updateReturn=="")) {
		if ($updateReturn=="fail0") {
			$updateReturnMessage=_("Your request failed because you do not have access to this action.") ;	
		}
		else if ($updateReturn=="fail1") {
			$updateReturnMessage=_("Your request failed because your inputs were invalid.") ;	
		}
		else if ($updateReturn=="fail2") {
			$updateReturnMessage=_("Your request failed due to a database error.") ;	
		}
		else if ($updateReturn=="fail3") {
			$updateReturnMessage=_("Your request failed because your inputs were invalid.") ;	
		}
		else if ($updateReturn=="fail4") {
			$updateReturnMessage=_("Your request failed because your inputs were invalid.") ;	
		}
		if ($updateReturn=="fail5") {
			$updateReturnMessage=_("Your request failed because you do not have access to this action.") ;	
		}
		else if ($updateReturn=="success0") {
			$updateReturnMessage=_("Your request was completed successfully.") ;	
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $updateReturnMessage;
		print "</div>" ;
	} 
	
	//Get class variable
	$gibbonPlannerEntryID=$_GET["gibbonPlannerEntryID"] ;
	if ($gibbonPlannerEntryID=="") {
		print "<div class='warning'>" ;
			print _('You have not specified one or more required parameters.') ;
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
				print _("The selected record does not exist, or you do not have access to it.") ;
			print "</div>" ;
		}
		else {
			$row=$result->fetch() ;
			
			print "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>" ;
				print "<tr>" ;
					print "<td style='width: 34%; vertical-align: top'>" ;
						print "<span style='font-size: 115%; font-weight: bold'>" . _('Class') . "</span><br/>" ;
						print $row["course"] . "." . $row["class"] ;
					print "</td>" ;
					print "<td style='width: 33%; vertical-align: top'>" ;
						print "<span style='font-size: 115%; font-weight: bold'>" . _('Name') . "</span><br/>" ;
						print $row["name"] ;
					print "</td>" ;
					print "<td style='width: 34%; vertical-align: top'>" ;
						print "<span style='font-size: 115%; font-weight: bold'>" . _('Date') . "</span><br/>" ;
						print dateConvertBack($guid, $row["date"]) ;
					print "</td>" ;
				print "</tr>" ;
				print "<tr>" ;
					print "<td style='padding-top: 15px; width: 34%; vertical-align: top' colspan=3>" ;
						print "<span style='font-size: 115%; font-weight: bold'>" . _('Homework Details') . "</span><br/>" ;
						print $row["homeworkDetails"] ;
					print "</td>" ;
				print "</tr>" ;
			print "</table>" ;
			
			$role=getCARole($guid, $connection2, $row["gibbonCourseClassID"]) ;
			
			$sqlList=getStudents($guid, $connection2, $role, $row["gibbonCourseClassID"], $row["homeworkCrowdAssessOtherTeachersRead"], $row["homeworkCrowdAssessOtherParentsRead"], $row["homeworkCrowdAssessSubmitterParentsRead"], $row["homeworkCrowdAssessClassmatesParentsRead"], $row["homeworkCrowdAssessOtherStudentsRead"], $row["homeworkCrowdAssessClassmatesRead"]) ;
			
			//Return $sqlList as table
			if ($sqlList[1]!="") {
				try {
					$resultList=$connection2->prepare($sqlList[1]);
					$resultList->execute($sqlList[0]);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				
				if ($resultList->rowCount()<1) {
					print "<div class='error'>" ;
						print "There is currently no work to assess." ;
					print "</div>" ;
				}
				else {
					print "<table cellspacing='0' style='width: 100%'>" ;
						print "<tr class='head'>" ;
							print "<th>" ;
								print _("Student") ;
							print "</th>" ;
							print "<th>" ;
								print _("Read") ;
							print "</th>" ;
							print "<th>" ;
								print _("Star") ;
							print "</th>" ;
							print "<th>" ;
								print _("Comments") ;
							print "</th>" ;
							print "<th>" ;
								print _("Discuss") ;
							print "</th>" ;
						print "</tr>" ;
						
						$count=0;
						$rowNum="odd" ;
						while ($rowList=$resultList->fetch()) {
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
									print "<a href='index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=" . $rowList["gibbonPersonID"] . "'>" . formatName("", $rowList["preferredName"], $rowList["surname"], "Student", true) . "</a>" ;
								print "</td>" ;
								print "<td>" ;
									$rowWork=NULL ;
									try {
										$dataWork=array("gibbonPlannerEntryID"=>$gibbonPlannerEntryID, "gibbonPersonID"=>$rowList["gibbonPersonID"]); 
										$sqlWork="SELECT * FROM gibbonPlannerEntryHomework WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPersonID=:gibbonPersonID ORDER BY count DESC" ;
										$resultWork=$connection2->prepare($sqlWork);
										$resultWork->execute($dataWork);
									}
									catch(PDOException $e) { 
										print "<div class='error'>" . $e->getMessage() . "</div>" ; 
									}
									if ($resultWork->rowCount()>0) {
										$rowWork=$resultWork->fetch() ;
										
										if ($rowWork["status"]=="Exemption") {
											$linkText="Exemption" ;
										}
										else if ($rowWork["version"]=="Final") {
											$linkText="Final" ;
										}
										else {
											$linkText="Draft" . $rowWork["count"] ;
										}
										
										if ($rowWork["type"]=="File") {
											print "<span title='" . $rowWork["version"] . ". Submitted at " . substr($rowWork["timestamp"],11,5) . " on " . dateConvertBack($guid, substr($rowWork["timestamp"],0,10)) . "'><a href='" . $_SESSION[$guid]["absoluteURL"] . "/" . $rowWork["location"] ."'>$linkText</a></span>" ;
										}
										else if ($rowWork["type"]=="Link") {
											print "<span title='" . $rowWork["version"] . ". Submitted at " . substr($rowWork["timestamp"],11,5) . " on " . dateConvertBack($guid, substr($rowWork["timestamp"],0,10)) . "'><a target='_blank' href='" . $rowWork["location"] ."'>$linkText</a></span>" ;
										}
										else {
											print "<span title='Recorded at " . substr($rowWork["timestamp"],11,5) . " on " . dateConvertBack($guid, substr($rowWork["timestamp"],0,10)) . "'>$linkText</span>" ;
										}
									}
								print "</td>" ;
								print "<td>" ;
									if ($rowWork["gibbonPlannerEntryHomeworkID"]!="" AND $rowList["gibbonPersonID"]!=$_SESSION[$guid]["gibbonPersonID"] AND $rowWork["status"]!="Exemption") {
										$likesGiven=countLikesByContextAndGiver($connection2, "Crowd Assessment", "gibbonPlannerEntryHomeworkID", $rowWork["gibbonPlannerEntryHomeworkID"], $_SESSION[$guid]["gibbonPersonID"]) ;
										if ($likesGiven!=1) {
											print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/modules/Crowd Assessment/crowdAssess_viewProcess.php?gibbonPlannerEntryID=$gibbonPlannerEntryID&gibbonPlannerEntryHomeworkID=" . $rowWork["gibbonPlannerEntryHomeworkID"] . "&address=" . $_GET["q"] . "&gibbonPersonID=" . $rowList["gibbonPersonID"] . "'><img src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/like_off.png'></a>" ;
										}
										else {
											print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/modules/Crowd Assessment/crowdAssess_viewProcess.php?gibbonPlannerEntryID=$gibbonPlannerEntryID&gibbonPlannerEntryHomeworkID=" . $rowWork["gibbonPlannerEntryHomeworkID"] . "&address=" . $_GET["q"] . "&gibbonPersonID=" . $rowList["gibbonPersonID"] . "'><img src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/like_on.png'></a>" ;
										}
										
										$likesTotal=countLikesByContext($connection2, "Crowd Assessment", "gibbonPlannerEntryHomeworkID", $rowWork["gibbonPlannerEntryHomeworkID"]) ;
										print " x " . $likesTotal ;
									}
								print "</td>" ;
								print "<td>" ;
									$dataDiscuss=array("gibbonPlannerEntryHomeworkID"=>$rowWork["gibbonPlannerEntryHomeworkID"]); 
									$sqlDiscuss="SELECT gibbonCrowdAssessDiscuss.*, title, surname, preferredName, category FROM gibbonCrowdAssessDiscuss JOIN gibbonPerson ON (gibbonCrowdAssessDiscuss.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) WHERE gibbonPlannerEntryHomeworkID=:gibbonPlannerEntryHomeworkID" ;
									$resultDiscuss=$connection2->prepare($sqlDiscuss);
									$resultDiscuss->execute($dataDiscuss);
									print $resultDiscuss->rowCount() ;
								print "</td>" ;
								print "<td>" ;
									if ($rowWork["gibbonPlannerEntryHomeworkID"]!="" AND $rowWork["status"]!="Exemption") {
										print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/crowdAssess_view_discuss.php&gibbonPlannerEntryID=$gibbonPlannerEntryID&gibbonPlannerEntryHomeworkID=" . $rowWork["gibbonPlannerEntryHomeworkID"] . "&gibbonPersonID=" . $rowList["gibbonPersonID"] . "'><img title='" . _('View') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/plus.png'/></a> " ;
									}
								print "</td>" ;
							print "</tr>" ;
						}
					print "</table>" ;
				}
			}
		}
	}
}
?>