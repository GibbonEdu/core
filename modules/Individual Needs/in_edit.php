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

if (isActionAccessible($guid, $connection2, "/modules/Individual Needs/in_edit.php")==FALSE) {
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
		$gibbonPersonID=$_GET["gibbonPersonID"] ;
		
		print "<div class='trail'>" ;
			if ($highestAction=="Individual Needs Records_view") {
				print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/in_view.php'>" . __($guid, 'All Student Records') . "</a> > </div><div class='trailEnd'>" . __($guid, 'View Individual Needs Record') . "</div>" ;
			}
			else if ($highestAction=="Individual Needs Records_viewContribute") {
				print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/in_view.php'>" . __($guid, 'All Student Records') . "</a> > </div><div class='trailEnd'>" . __($guid, 'View & Contribute To Individual Needs Record') . "</div>" ;
			}
			else if ($highestAction=="Individual Needs Records_viewEdit") {
				print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/in_view.php'>" . __($guid, 'All Student Records') . "</a> > </div><div class='trailEnd'>" . __($guid, 'Edit Individual Needs Record') . "</div>" ;
			}
		print "</div>" ;

		if (isset($_GET["return"])) { returnProcess($guid, $_GET["return"], null, array("success0" => "Your request was completed successfully.")); }
		
		try {
			$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonID"=>$gibbonPersonID); 
			$sql="SELECT gibbonPerson.gibbonPersonID, gibbonStudentEnrolmentID, surname, preferredName, gibbonYearGroup.name AS yearGroup, gibbonRollGroup.nameShort AS rollGroup, dateStart, dateEnd, image_240 FROM gibbonPerson, gibbonStudentEnrolment, gibbonYearGroup, gibbonRollGroup WHERE (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) AND (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) AND (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPerson.gibbonPersonID=:gibbonPersonID AND gibbonPerson.status='Full' ORDER BY surname, preferredName" ; 
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
					print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Individual Needs/in_view.php&search=" . $search . "'>" . __($guid, 'Back to Search Results') . "</a>" ;
				print "</div>" ;
			}
			else if (($gibbonINDescriptorID!="" OR $gibbonAlertLevelID!="" OR $gibbonRollGroupID!="" OR $gibbonYearGroupID!="") AND $source=="summary") {
				print "<div class='linkTop'>" ;
					print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Individual Needs/in_summary.php&gibbonINDescriptorID=" . $gibbonINDescriptorID . "&gibbonAlertLevelID=" . $gibbonAlertLevelID . "&=gibbonRollGroupID" . $gibbonRollGroupID . "&gibbonYearGroupID=" . $gibbonYearGroupID . "'>" . __($guid, 'Back to Search Results') . "</a>" ;
				print "</div>" ;
			}
			
			$gibbonINArchiveID=NULL ;
			if (isset($_POST["gibbonINArchiveID"])) {
				if ($_POST["gibbonINArchiveID"]!="") {
					$gibbonINArchiveID=$_POST["gibbonINArchiveID"] ;
				}
			}
			$archiveStrategies=NULL ;
			$archiveTargets=NULL ;
			$archiveNotes=NULL ;
			$archiveDescriptors=NULL ;
		
			try {
				$dataArchive=array("gibbonPersonID"=>$gibbonPersonID); 
				$sqlArchive="SELECT * FROM gibbonINArchive WHERE gibbonPersonID=:gibbonPersonID ORDER BY archiveTimestamp DESC" ; 
				$resultArchive=$connection2->prepare($sqlArchive);
				$resultArchive->execute($dataArchive);
			}
			catch(PDOException $e) { }
			if ($resultArchive->rowCount()>0) {
				print "<div class='linkTop'>" ;
					print "<form method='post' action='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/in_edit.php&gibbonPersonID=$gibbonPersonID&search=$search&source=$source&gibbonINDescriptorID=$gibbonINDescriptorID&gibbonAlertLevelID=$gibbonAlertLevelID&gibbonRollGroupID=$gibbonRollGroupID&gibbonYearGroupID=$gibbonYearGroupID'>" ;
						print __($guid, "Archived Plans") . " " ;
						print "<select name=\"gibbonINArchiveID\" style=\"float: none; width: 200px; margin-top: -10px; margin-bottom: 5px\">" ;
							print "<option value=''>" . __($guid, 'Current Plan') . "</option>" ;
							while ($rowArchive=$resultArchive->fetch()) {
								$selected="" ;
								if ($rowArchive["gibbonINArchiveID"]==$gibbonINArchiveID) {
									$selected="selected" ;
									$archiveStrategies=$rowArchive["strategies"] ;
									$archiveTargets=$rowArchive["targets"] ;
									$archiveNotes=$rowArchive["notes"] ;
									$archiveDescriptors=$rowArchive["descriptors"] ;
								}
								print "<option $selected value='" . $rowArchive["gibbonINArchiveID"] . "'>" . $rowArchive["archiveTitle"] . " (" . dateConvertBack($guid, substr($rowArchive["archiveTimestamp"], 0, 10)) . ")</option>" ;
							}
						print "</select>" ;
						print "<input style='margin-top: 0px; margin-right: -2px' type='submit' value='" . __($guid, 'Go') . "'>" ;
					print "</form>" ;
				print "</div>" ;
			}

		
			print "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>" ;
				print "<tr>" ;
					print "<td style='width: 34%; vertical-align: top'>" ;
						print "<span style='font-size: 115%; font-weight: bold'>" . __($guid, 'Name') . "</span><br/>" ;
						print formatName("", $row["preferredName"], $row["surname"], "Student") ;
					print "</td>" ;
					print "<td style='width: 33%; vertical-align: top'>" ;
						print "<span style='font-size: 115%; font-weight: bold'>" . __($guid, 'Year Group') . "</span><br/>" ;
						print "<i>" . __($guid, $row["yearGroup"]) . "</i>" ;
					print "</td>" ;
					print "<td style='width: 34%; vertical-align: top'>" ;
						print "<span style='font-size: 115%; font-weight: bold'>" . __($guid, 'Roll Group') . "</span><br/>" ;
						print "<i>" . $row["rollGroup"] . "</i>" ;
					print "</td>" ;
				print "</tr>" ;
			print "</table>" ;
			
			print "<form method='post' action='" . $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/in_editProcess.php?gibbonPersonID=$gibbonPersonID&search=$search&source=$source&gibbonINDescriptorID=$gibbonINDescriptorID&gibbonAlertLevelID=$gibbonAlertLevelID&gibbonRollGroupID=$gibbonRollGroupID&gibbonYearGroupID=$gibbonYearGroupID'>" ;				
				print "<h3>" ;
					print __($guid, "Individual Needs Status") ;
				print "</h3>" ;
				if ($highestAction=="Individual Needs Records_view" OR $highestAction=="Individual Needs Records_viewContribute") {
					$statusTable=printINStatusTable($connection2, $gibbonPersonID, "disabled") ;
				}
				else if ($highestAction=="Individual Needs Records_viewEdit") {
					if ($gibbonINArchiveID!="") {
						$statusTable=printINStatusTable($connection2, $gibbonPersonID, "disabled", $archiveDescriptors) ;
					}
					else {
						$statusTable=printINStatusTable($connection2, $gibbonPersonID) ;
					}
				}
			
			
				if ($statusTable==FALSE) {
					print "<div class='error'>" ;
					print __($guid, "Your request failed due to a database error.") ;
					print "</div>" ;
				}
				else {
					print $statusTable ;
				}
			
			
				print "<h3>" ;
					print __($guid, "Individual Education Plan") ;
				print "</h3>" ;
			
				if (is_null($gibbonINArchiveID)==FALSE) { //SHOW ARCHIVE
					?>
					<table class='smallIntBorder fullWidth' cellspacing='0'>	
						<tr>
							<td colspan=2 style='padding-top: 25px'> 
								<span style='font-weight: bold; font-size: 135%'><?php print __($guid, 'Targets') ?></span><br/>
								<?php
								print "<p>" . $archiveTargets . "</p>" ;
								?>
							</td>
						</tr>
						<tr>
							<td colspan=2> 
								<span style='font-weight: bold; font-size: 135%'><?php print __($guid, 'Teaching Strategies') ?></span><br/>
								<?php
								print "<p>" . $archiveStrategies . "</p>" ;
								?>
							</td>
						</tr>
						<tr>
							<td colspan=2 style='padding-top: 25px'> 
								<span style='font-weight: bold; font-size: 135%'><?php print __($guid, 'Notes & Review') ?></span><br/>
								<?php
								print "<p>" . $archiveNotes . "</p>" ;
								?>
							</td>
						</tr>
					</table>
					<?php
				}
				else { //SHOW CURRENT
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
						print __($guid, "Your request failed due to a database error.") ;
						print "</div>" ;
					}
					else {
						$rowIEP=$resultIEP->fetch() ;
						?>	
						<table class='smallIntBorder fullWidth' cellspacing='0'>	
							<tr>
								<td colspan=2 style='padding-top: 25px'> 
									<span style='font-weight: bold; font-size: 135%'><?php print __($guid, 'Targets') ?></span><br/>
									<?php
									if ($highestAction=="Individual Needs Records_viewEdit") {
										print getEditor($guid,  TRUE, "targets", $rowIEP["targets"], 20, true ) ;
									}
									else {
										print "<p>" . $rowIEP["targets"] . "</p>" ;
									}
									?>
								</td>
							</tr>
							<tr>
								<td colspan=2> 
									<span style='font-weight: bold; font-size: 135%'><?php print __($guid, 'Teaching Strategies') ?></span><br/>
									<?php
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
									<span style='font-weight: bold; font-size: 135%'><?php print __($guid, 'Notes & Review') ?></span><br/>
									<?php
									if ($highestAction=="Individual Needs Records_viewEdit") {
										print getEditor($guid,  TRUE, "notes", $rowIEP["notes"], 20, true ) ;
									}
									else {
										print "<p>" . $rowIEP["notes"] . "</p>" ;
									}
									?>
								</td>
							</tr>
							<?php
							if ($highestAction=="Individual Needs Records_viewEdit" OR $highestAction=="Individual Needs Records_viewContribute") {
								?>
								<tr>
									<td class="right" colspan=2>
										<input type="hidden" name="gibbonPersonID" value="<?php print $gibbonPersonID ?>">
										<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
										<input type="submit" value="<?php print __($guid, "Submit") ; ?>">
									</td>
								</tr>
								<?php
							}
							?>
						</table>
						<?php
						
					}
				}
			print "</form>" ;
		}
	}
	//Set sidebar
	$_SESSION[$guid]["sidebarExtra"]=getUserPhoto($guid, $row["image_240"], 240) ;
}
?>