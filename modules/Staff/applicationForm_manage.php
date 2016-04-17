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

if (isActionAccessible($guid, $connection2, "/modules/Staff/applicationForm_manage.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __($guid, 'Manage Applications') . "</div>" ;
	print "</div>" ;
	
	if (isset($_GET["deleteReturn"])) { $deleteReturn=$_GET["deleteReturn"] ; } else { $deleteReturn="" ; }
	$deleteReturnMessage="" ;
	$class="error" ;
	if (!($deleteReturn=="")) {
		if ($deleteReturn=="success0") {
			$deleteReturnMessage=__($guid, "Your request was completed successfully.") ;		
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $deleteReturnMessage;
		print "</div>" ;
	}
	
	if (isset($_GET["rejectReturn"])) { $rejectReturn=$_GET["rejectReturn"] ; } else { $rejectReturn="" ; }
	$rejectReturnMessage="" ;
	$class="error" ;
	if (!($rejectReturn=="")) {
		if ($rejectReturn=="success0") {
			$rejectReturnMessage="Application was sucessfully rejected." ;	
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $rejectReturnMessage;
		print "</div>" ;
	} 
	
	//Set pagination variable
	$page=1 ; if (isset($_GET["page"])) { $page=$_GET["page"] ; }
	if ((!is_numeric($page)) OR $page<1) {
		$page=1 ;
	}
	
	$search="" ;
	if (isset($_GET["search"])) {
		$search=$_GET["search"] ;
	}
	
	print "<h4>" ;
	print __($guid, "Search") ;
	print "</h2>" ;
	?>
	<form method="get" action="<?php print $_SESSION[$guid]["absoluteURL"]?>/index.php">
		<table class='noIntBorder' cellspacing='0' style="width: 100%">	
			<tr><td style="width: 40%"></td><td></td></tr>
			<tr>
				<td> 
					<b><?php print __($guid, 'Search For') ?></b><br/>
					<span class="emphasis small"><?php print __($guid, 'Application ID, preferred, surname') ?></span>
				</td>
				<td class="right">
					<input name="search" id="search" maxlength=20 value="<?php print $search ?>" type="text" class="standardWidth">
				</td>
			</tr>
			<tr>
				<td colspan=2 class="right">
					<input type="hidden" name="q" value="/modules/<?php print $_SESSION[$guid]["module"] ?>/applicationForm_manage.php">
					<input type="hidden" name="gibbonSchoolYearID" value="<?php print $gibbonSchoolYearID ?>">
					<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
					<?php
					print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/applicationForm_manage.php'>" . __($guid, 'Clear Search') . "</a>" ;
					?>
					<input type="submit" value="<?php print __($guid, "Submit") ; ?>">
				</td>
			</tr>
		</table>
	</form>
	<?php
	
	print "<h4>" ;
	print __($guid, "View") ;
	print "</h2>" ;
	
	
	try {
		$data=array(); 
		$sql="SELECT gibbonStaffApplicationForm.*, gibbonStaffJobOpening.jobTitle FROM gibbonStaffApplicationForm JOIN gibbonStaffJobOpening ON (gibbonStaffApplicationForm.gibbonStaffJobOpeningID=gibbonStaffJobOpening.gibbonStaffJobOpeningID) LEFT JOIN gibbonPerson ON (gibbonStaffApplicationForm.gibbonPersonID=gibbonPerson.gibbonPersonID) ORDER BY gibbonStaffApplicationForm.status, priority DESC, timestamp DESC" ; 
		if ($search!="") {
			$data=array("search"=>"%$search%", "search1"=>"%$search%", "search2"=>"%$search%", "search3"=>"%$search%", "search4"=>"%$search%"); 
			$sql="SELECT gibbonStaffApplicationForm.*, gibbonStaffJobOpening.jobTitle FROM gibbonStaffApplicationForm JOIN gibbonStaffJobOpening ON (gibbonStaffApplicationForm.gibbonStaffJobOpeningID=gibbonStaffJobOpening.gibbonStaffJobOpeningID) LEFT JOIN gibbonPerson ON (gibbonStaffApplicationForm.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE (gibbonStaffApplicationFormID LIKE :search OR gibbonStaffApplicationForm.preferredName LIKE :search1 OR gibbonStaffApplicationForm.surname LIKE :search2 OR gibbonPerson.preferredName LIKE :search3 OR gibbonPerson.surname LIKE :search4) ORDER BY gibbonStaffApplicationForm.status, priority DESC, timestamp DESC" ; 
		}
		$sqlPage=$sql . " LIMIT " . $_SESSION[$guid]["pagination"] . " OFFSET " . (($page-1)*$_SESSION[$guid]["pagination"]) ; 
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		print "<div class='error'>" . $e->getMessage() . "</div>" ; 
	}
	
	if ($result->rowCount()<1) {
		print "<div class='error'>" ;
		print "There are no records display." ;
		print "</div>" ;
	}
	else {
		if ($result->rowCount()>$_SESSION[$guid]["pagination"]) {
			printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]["pagination"], "top", "search=$search") ;
		}
	
		print "<table cellspacing='0' style='width: 100%'>" ;
			print "<tr class='head'>" ;
				print "<th>" ;
					print __($guid, "ID") ;
				print "</th>" ;
				print "<th>" ;
					print __($guid, "Applicant") . "<br/><span style='font-style: italic; font-size: 85%'>" . __($guid, "Application Date") . "</span>" ;
				print "</th>" ;
				print "<th>" ;
					print __($guid, "Position") ;
				print "</th>" ;
				print "<th>" ;
					print __($guid, "Status") . "<br/><span style='font-style: italic; font-size: 85%'>" . __($guid, 'Milestones') . "</span>" ;
				print "</th>" ;
				print "<th>" ;
					print __($guid, "Priority") ;
				print "</th>" ;
				print "<th style='width: 80px'>" ;
					print __($guid, "Actions") ;
				print "</th>" ;
			print "</tr>" ;
			
			$count=0;
			$rowNum="odd" ;
			try {
				$resultPage=$connection2->prepare($sqlPage);
				$resultPage->execute($data);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
			while ($row=$resultPage->fetch()) {
				if ($count%2==0) {
					$rowNum="even" ;
				}
				else {
					$rowNum="odd" ;
				}
				
				if ($row["status"]=="Accepted") {
					$rowNum="current" ;
				}
				else if ($row["status"]=="Rejected" OR $row["status"]=="Withdrawn") {
					$rowNum="error" ;
				}
				
				$count++ ;
				
				//COLOR ROW BY STATUS!
				print "<tr class=$rowNum>" ;
					print "<td>" ;
						print ltrim($row["gibbonStaffApplicationFormID"], "0") ;
					print "</td>" ;
					print "<td>" ;
						if ($row["gibbonPersonID"]!=NULL AND isActionAccessible($guid, $connection2, "/modules/Staff/staff_view.php")) {
							print "<b><a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Staff/staff_view_details.php&gibbonPersonID=" . $row["gibbonPersonID"] . "'>" . formatName("", $row["preferredName"], $row["surname"], "Student", true) . "</a></b><br/>" ;
						}
						else {
							print "<b>" . formatName("", $row["preferredName"], $row["surname"], "Student", true) . "</b><br/>" ;
						}
						print "<span style='font-style: italic; font-size: 85%'>" . dateConvertBack($guid, substr($row["timestamp"],0,10))  . "</span>" ;
					print "</td>" ;
					print "<td>" ;
						print $row["jobTitle"] ;
					print "</td>" ;
					print "<td>" ;
						print "<b>" . $row["status"] . "</b>" ;
						if ($row["status"]=="Pending") {
							$milestones=explode(",", $row["milestones"]) ;
							foreach ($milestones as $milestone) {
								print "<br/><span style='font-style: italic; font-size: 85%'>" . trim($milestone) . "</span>" ;
							}
						}
					print "</td>" ;
					print "<td>" ;
						print $row["priority"] ;
					print "</td>" ;
					print "<td>" ;
						if ($row["status"]=="Pending" OR $row["status"]=="Waiting List") {
							print "<a style='margin-left: 1px' href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/applicationForm_manage_accept.php&gibbonStaffApplicationFormID=" . $row["gibbonStaffApplicationFormID"] . "&search=$search'><img title='" . __($guid, 'Accept') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/iconTick.png'/></a>" ;
							print "<a style='margin-left: 5px' href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/applicationForm_manage_reject.php&gibbonStaffApplicationFormID=" . $row["gibbonStaffApplicationFormID"] . "&search=$search'><img title='" . __($guid, 'Reject') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/iconCross.png'/></a>" ;
							print "<br/>" ;
						}
						print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/applicationForm_manage_edit.php&gibbonStaffApplicationFormID=" . $row["gibbonStaffApplicationFormID"] . "&search=$search'><img title='" . __($guid, 'Edit') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
						print " <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/applicationForm_manage_delete.php&gibbonStaffApplicationFormID=" . $row["gibbonStaffApplicationFormID"] . "&search=$search'><img style='margin-left: 4px' title='" . __($guid, 'Delete') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/></a>" ;
						
					print "</td>" ;
				print "</tr>" ;
			}
		print "</table>" ;
		
		if ($result->rowCount()>$_SESSION[$guid]["pagination"]) {
			printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]["pagination"], "bottom", "search=$search") ;
		}
	}
}
?>