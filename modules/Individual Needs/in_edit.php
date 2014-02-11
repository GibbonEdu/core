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

if (isActionAccessible($guid, $connection2, "/modules/Individual Needs/in_edit.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "You do not have access to this action." ;
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
		$gibbonPersonID=$_GET["gibbonPersonID"] ;
		
		print "<div class='trail'>" ;
			if ($highestAction=="Individual Needs Records_view") {
				print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>Home</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/in_view.php'>All Student Records</a> > </div><div class='trailEnd'>View Record</div>" ;
			}
			else if ($highestAction=="Individual Needs Records_viewContribute") {
				print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>Home</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/in_view.php'>All Student Records</a> > </div><div class='trailEnd'>View & Contribute To Record</div>" ;
			}
			else if ($highestAction=="Individual Needs Records_viewEdit") {
				print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>Home</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/in_view.php'>All Student Records</a> > </div><div class='trailEnd'>Edit Record</div>" ;
			}
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
			else if ($updateReturn=="fail5") {
				$updateReturnMessage ="Your request was successful, but some data was not properly saved." ;	
			}
			else if ($updateReturn=="success0") {
				$updateReturnMessage ="Your request was successful. ." ;	
				$class="success" ;
			}
			print "<div class='$class'>" ;
				print $updateReturnMessage;
			print "</div>" ;
		} 
		
		try {
			$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonID"=>$gibbonPersonID); 
			$sql="SELECT gibbonPerson.gibbonPersonID, gibbonStudentEnrolmentID, surname, preferredName, gibbonYearGroup.nameShort AS yearGroup, gibbonRollGroup.nameShort AS rollGroup, dateStart, dateEnd, image_240 FROM gibbonPerson, gibbonStudentEnrolment, gibbonYearGroup, gibbonRollGroup WHERE (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) AND (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) AND (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPerson.gibbonPersonID=:gibbonPersonID AND gibbonPerson.status='Full' ORDER BY surname, preferredName" ; 
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}

		if ($result->rowCount()!=1) {
			print "<div class='error'>" ;
			print "The specified student does not seem to exist." ;
			print "</div>" ;
		}
		else {
			$row=$result->fetch() ;
			$search=NULL ;
			if (isset($_GET["search"])) {
				$search=$_GET["search"] ;
			}
			$source=NULL ;
			if (isset($_GET["source"])) {
				$source=$_GET["source"] ;
			}
			$gibbonINDescriptorID=NULL ;
			if (isset($_GET["gibbonINDescriptorID"])) {
				$gibbonINDescriptorID=$_GET["gibbonINDescriptorID"] ;
			}
			$gibbonAlertLevelID=NULL ;
			if (isset($_GET["gibbonAlertLevelID"])) {
				$gibbonAlertLevelID=$_GET["gibbonAlertLevelID"] ;
			}
			$gibbonRollGroupID=NULL ;
			if (isset($_GET["gibbonRollGroupID"])) {
				$gibbonRollGroupID=$_GET["gibbonRollGroupID"] ;
			}
			$gibbonYearGroupID=NULL ;
			if (isset($_GET["gibbonYearGroupID"])) {
				$gibbonYearGroupID=$_GET["gibbonYearGroupID"] ;
			}
			
			
			if ($search!="" AND $source=="") {
				print "<div class='linkTop'>" ;
					print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Individual Needs/in_view.php&search=" . $search . "'>Back to Search Results</a>" ;
				print "</div>" ;
			}
			else if (($gibbonINDescriptorID!="" OR $gibbonAlertLevelID!="" OR $gibbonRollGroupID!="" OR $gibbonYearGroupID!="") AND $source=="summary") {
				print "<div class='linkTop'>" ;
					print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Individual Needs/in_summary.php&gibbonINDescriptorID=" . $gibbonINDescriptorID . "&gibbonAlertLevelID=" . $gibbonAlertLevelID . "&=gibbonRollGroupID" . $gibbonRollGroupID . "&gibbonYearGroupID=" . $gibbonYearGroupID . "'>Back to Search Results</a>" ;
				print "</div>" ;
			}

		
			print "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>" ;
				print "<tr>" ;
					print "<td style='width: 34%; vertical-align: top'>" ;
						print "<span style='font-size: 115%; font-weight: bold'>Name</span><br/>" ;
						print formatName("", $row["preferredName"], $row["surname"], "Student") ;
					print "</td>" ;
					print "<td style='width: 33%; vertical-align: top'>" ;
						print "<span style='font-size: 115%; font-weight: bold'>Year Group</span><br/>" ;
						print "<i>" . $row["yearGroup"] . "</i>" ;
					print "</td>" ;
					print "<td style='width: 34%; vertical-align: top'>" ;
						print "<span style='font-size: 115%; font-weight: bold'>Roll Group</span><br/>" ;
						print "<i>" . $row["rollGroup"] . "</i>" ;
					print "</td>" ;
				print "</tr>" ;
			print "</table>" ;
			
			print "<form method='post' action='" . $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/in_editProcess.php?gibbonPersonID=$gibbonPersonID&search=$search&source=$source&gibbonINDescriptorID=$gibbonINDescriptorID&gibbonAlertLevelID=$gibbonAlertLevelID&gibbonRollGroupID=$gibbonRollGroupID&gibbonYearGroupID=$gibbonYearGroupID'>" ;
				print "<h3>" ;
					print "Individual Needs Status" ;
				print "</h3>" ;
				if ($highestAction=="Individual Needs Records_view" OR $highestAction=="Individual Needs Records_viewContribute") {
					$statusTable=printINStatusTable($connection2, $gibbonPersonID, "disabled") ;
				}
				else if ($highestAction=="Individual Needs Records_viewEdit") {
					$statusTable=printINStatusTable($connection2, $gibbonPersonID) ;
				}
				
				
				if ($statusTable==FALSE) {
					print "<div class='error'>" ;
					print "The status table could not be created." ;
					print "</div>" ;
				}
				else {
					print $statusTable ;
				}
				
				
				print "<h3>" ;
					print "Individual Education Plan" ;
				print "</h3>" ;
								
				try {
					$dataIEP=array("gibbonPersonID"=>$gibbonPersonID); 
					$sqlIEP="SELECT * FROM gibbonIN WHERE gibbonPersonID=:gibbonPersonID" ;
					$resultIEP=$connection2->prepare($sqlIEP);
					$resultIEP->execute($dataIEP);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				if ($resultIEP->rowCount()>1) {
					print "<div class='error'>" ;
					print "Individual needs cannot be displayed due to a database error." ;
					print "</div>" ;
				}
				else {
					$rowIEP=$resultIEP->fetch() ;
					?>	
					<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
						<tr>
							<td colspan=2> 
								<span style='font-weight: bold; font-size: 135%'>Teaching Strategies</span><br/>
								<?
								if ($highestAction=="Individual Needs Records_viewEdit" OR $highestAction=="Individual Needs Records_viewContribute") {
									print getEditor($guid,  TRUE, "strategies", $rowIEP["strategies"], 20, true ) ;
								}
								else {
									print "<p>" . $rowIEP["strategies"] . "</p>" ;
								}
								?>
							</td>
						</tr>
						<tr>
							<td colspan=2 style='padding-top: 25px'> 
								<span style='font-weight: bold; font-size: 135%'>Targets</span><br/>
								<?
								if ($highestAction=="Individual Needs Records_viewEdit") {
									print getEditor($guid,  TRUE, "targets", $rowIEP["targets"], 20, true ) ;
								}
								else {
									if ($rowIEP["targets"]!="") {
										print "<p>" . $rowIEP["targets"] . "</p>" ;
									}
									else {
										print "<i>No data available.</i>" ;
									}
								}
								?>
							</td>
						</tr>
						<tr>
							<td colspan=2 style='padding-top: 25px'> 
								<span style='font-weight: bold; font-size: 135%'>Notes</span><br/>
								<?
								if ($highestAction=="Individual Needs Records_viewEdit") {
									print getEditor($guid,  TRUE, "notes", $rowIEP["notes"], 20, true ) ;
								}
								else {
									if ($rowIEP["notes"]!="") {
										print "<p>" . $rowIEP["notes"] . "</p>" ;
									}
									else {
										print "<i>No data available.</i>" ;
									}
								}
								?>
							</td>
						</tr>
						<?
						if ($highestAction=="Individual Needs Records_viewEdit" OR $highestAction=="Individual Needs Records_viewContribute") {
							?>
							<tr>
								<td class="right" colspan=2>
									<input type="hidden" name="gibbonPersonID" value="<? print $gibbonPersonID ?>">
									<input type="hidden" name="address" value="<? print $_SESSION[$guid]["address"] ?>">
									<input type="submit" value="Submit">
								</td>
							</tr>
							<?
						}
						?>
					</table>
					<?
				}
			print "</form>" ;
		}
	}
	//Set sidebar
	$_SESSION[$guid]["sidebarExtra"]=getUserPhoto($guid, $row["image_240"], 240) ;
}
?>