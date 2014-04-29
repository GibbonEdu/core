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

$_SESSION[$guid]["report_student_emergencySummary.php_choices"]="" ;

//Module includes
include "./modules/" . $_SESSION[$guid]["module"] . "/moduleFunctions.php" ;

if (isActionAccessible($guid, $connection2, "/modules/Library/report_catalogSummary.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > </div><div class='trailEnd'>" . _('Catalog Summary') . "</div>" ;
	print "</div>" ;
	
	print "<h3>" ;
		print _("Search & Filter") ;
	print "</h3>" ;
	
	//Get current filter values
	$ownershipType=NULL ;
	if (isset($_POST["ownershipType"])) {
		$ownershipType=trim($_POST["ownershipType"]) ;
	}
	if ($ownershipType=="") {
		if (isset($_GET["ownershipType"])) {
			$ownershipType=trim($_GET["ownershipType"]) ;
		}
	}
	$gibbonLibraryTypeID=NULL ;
	if (isset($_POST["gibbonLibraryTypeID"])) {
		$gibbonLibraryTypeID=trim($_POST["gibbonLibraryTypeID"]) ;
	}
	if ($gibbonLibraryTypeID=="") {
		if (isset($_GET["gibbonLibraryTypeID"])) {
			$gibbonLibraryTypeID=trim($_GET["gibbonLibraryTypeID"]) ;
		}
	}
	$gibbonSpaceID=NULL ;
	if (isset($_POST["gibbonSpaceID"])) {
		$gibbonSpaceID=trim($_POST["gibbonSpaceID"]) ;
	}
	if ($gibbonSpaceID=="") {
		if (isset($_GET["gibbonSpaceID"])) {
			$gibbonSpaceID=trim($_GET["gibbonSpaceID"]) ;
		}
	}
	$status=NULL ;
	if (isset($_POST["status"])) {
		$status=trim($_POST["status"]) ;
	}
	if ($status=="") {
		if (isset($_GET["status"])) {
			$status=trim($_GET["status"]) ;
		}
	}
	
	//Display filters
	print "<form method='post' action='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Library/report_catalogSummary.php'>" ;
		print "<table class='noIntBorder' cellspacing='0' style='width: 100%'>" ;
			?>
			<tr>
				<td> 
					<b><?php print _('Ownership Type') ?></b><br/>
					<span style="font-size: 90%"><i></i></span>
				</td>
				<td class="right">
					<?php
					print "<select name='ownershipType' id='ownershipType' style='width:302px'>" ;
						print "<option " ; if ($ownershipType=="") { print "selected " ; } print "value=''></option>" ;
						print "<option " ; if ($ownershipType=="School") { print "selected " ; } print "value='School'>" . _('School') . "</option>" ;
						print "<option " ; if ($ownershipType=="Individual") { print "selected " ; } print "value='Individual'>" . _('Individual') . "</option>" ;
					print "</select>" ;
					?>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print _('Type') ?></b><br/>
					<span style="font-size: 90%"><i></i></span>
				</td>
				<td class="right">
					<?php
					try {
						$dataType=array(); 
						$sqlType="SELECT * FROM gibbonLibraryType WHERE active='Y' ORDER BY name" ;
						$resultType=$connection2->prepare($sqlType);
						$resultType->execute($dataType);
					}
					catch(PDOException $e) { 
						print "<div class='error'>" . $e->getMessage() . "</div>" ; 
					}
					print "<select name='gibbonLibraryTypeID' id='gibbonLibraryTypeID' style='width:302px'>" ;
						print "<option value=''></option>" ;
						while ($rowType=$resultType->fetch()) {
							$selected="" ;
							if ($rowType["gibbonLibraryTypeID"]==$gibbonLibraryTypeID) {
								$selected="selected" ;
							}
							print "<option $selected value='" . $rowType["gibbonLibraryTypeID"] . "'>" . $rowType["name"] . "</option>" ;
						}
					print "</select>" ;	
					?>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print _('Location') ?> *</b><br/>
					<span style="font-size: 90%"><i></i></span>
				</td>
				<td class="right">
					<?php
					try {
						$dataLocation=array(); 
						$sqlLocation="SELECT * FROM gibbonSpace ORDER BY name" ;
						$resultLocation=$connection2->prepare($sqlLocation);
						$resultLocation->execute($dataLocation);
					}
					catch(PDOException $e) { 
						print "<div class='error'>" . $e->getMessage() . "</div>" ; 
					}
					print "<select name='gibbonSpaceID' id='gibbonSpaceID' style='width:302px'>" ;
						print "<option value=''></option>" ;
						while ($rowLocation=$resultLocation->fetch()) {
							$selected="" ;
							if ($rowLocation["gibbonSpaceID"]==$gibbonSpaceID) {
								$selected="selected" ;
							}
							print "<option $selected value='" . $rowLocation["gibbonSpaceID"] . "'>" . $rowLocation["name"] . "</option>" ;
						}
					print "</select>" ;	
					?>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print _('Status') ?></b><br/>
					<span style="font-size: 90%"><i></i></span>
				</td>
				<td class="right">
					<?php
					print "<select name='status' id='status' style='width:302px'>" ;
						print "<option value=''></option>" ;
						print "<option " ; if ($status=="Available") { print "selected " ; } print "value='Available'>" . _('Available') . "</option>" ;
						print "<option " ; if ($status=="Decommissioned") { print "selected " ; } print "value='Decommissioned'>" . _('Decommissioned') . "</option>" ;
						print "<option " ; if ($status=="In Use") { print "selected " ; } print "value='In Use'>" . _('In Use') . "</option>" ;
						print "<option " ; if ($status=="Lost") { print "selected " ; } print "value='Lost'>" . _('Lost') . "</option>" ;
						print "<option " ; if ($status=="On Loan") { print "selected " ; } print "value='On Loan'>" . _('On Loan') . "</option>" ;
						print "<option " ; if ($status=="Repair") { print "selected " ; } print "value='Repair'>" . _('Repair') . "</option>" ;
						print "<option " ; if ($status=="Reserved") { print "selected " ; } print "value='Reserved'>" . _('Reserved') . "</option>" ;
					print "</select>" ;
					?>
				</td>
			</tr>
			<?php
			print "<tr>" ;
				print "<td class='right' colspan=2>" ;
					print "<input type='hidden' name='q' value='" . $_GET["q"] . "'>" ;
					print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Library/report_catalogSummary.php'>" . _('Clear Filters') . "</a> " ;
					print "<input type='submit' value='" . _('Go') . "'>" ;
				print "</td>" ;
			print "</tr>" ;
		print "</table>" ;
	print "</form>" ;
	
	print "<h3>" ;
		print _("Report Data") ;
	print "</h3>" ;
	
	print "<div class='linkTop'>" ;
	print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/report_catalogSummaryExport.php?address=" . $_GET["q"] . "&ownershipType=$ownershipType&gibbonLibraryTypeID=$gibbonLibraryTypeID&gibbonSpaceID=$gibbonSpaceID&status=$status'><img title='" . _('Export to Excel') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/download.png'/></a>" ;
	print "</div>" ;
	
	//Search with filters applied
	try {
		$data=array(); 
		$sqlWhere="WHERE " ;
		if ($ownershipType!="") {
			$data["ownershipType"]=$ownershipType ;
			$sqlWhere.="ownershipType=:ownershipType AND " ; 
		}
		if ($gibbonLibraryTypeID!="") {
			$data["gibbonLibraryTypeID"]=$gibbonLibraryTypeID;
			$sqlWhere.="gibbonLibraryTypeID=:gibbonLibraryTypeID AND " ; 
		}
		if ($gibbonSpaceID!="") {
			$data["gibbonSpaceID"]=$gibbonSpaceID;
			$sqlWhere.="gibbonSpaceID=:gibbonSpaceID AND " ; 
		}
		if ($status!="") {
			$data["status"]=$status;
			$sqlWhere.="status=:status AND " ; 
		}
		if ($sqlWhere=="WHERE ") {
			$sqlWhere="" ;
		}
		else {
			$sqlWhere=substr($sqlWhere,0,-5) ;
		}
		$sql="SELECT * FROM gibbonLibraryItem $sqlWhere ORDER BY id" ; 
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		print "<div class='error'>" . $e->getMessage() . "</div>" ; 
	}
		
	if ($result->rowCount()<1) {
		print "<div class='error'>" ;
		print _("There are no records to display.") ;
		print "</div>" ;
	}
	else {
		print "<table cellspacing='0' style='width: 100%'>" ;
			print "<tr class='head'>" ;
				print "<th>" ;
					print _("School ID") . "<br/>" ;
					print "<span style='font-style: italic; font-size: 85%'>" . _('Type') . "</span>" ;
				print "</th>" ;
				print "<th>" ;
					print _("Name") . "<br/>" ;
					print "<span style='font-size: 85%; font-style: italic'>" . _('Producer') . "</span>" ;
				print "</th>" ;
				print "<th>" ;
					print _("Location") ;
				print "</th>" ;
				print "<th>" ;
					print _("Ownership") . "<br/>" ;
					print "<span style='font-size: 85%; font-style: italic'>" . _('User/Owner') . "</span>" ;
				print "</th>" ;
				print "<th>" ;
					print _("Status") . "<br/>" ;
					print "<span style='font-size: 85%; font-style: italic'>" . _('Borrowable') . "</span>" ;
				print "</th>" ;
				print "<th>" ;
					print _("Purchase Date") . "<br/>" ;
					print "<span style='font-size: 85%; font-style: italic'>" . _('Vendor') . "</span>" ;
				print "</th>" ;
			print "</tr>" ;
			
			$count=0;
			$rowNum="odd" ;
			while ($row=$result->fetch()) {
				if ($count%2==0) {
					$rowNum="even" ;
				}
				else {
					$rowNum="odd" ;
				}
				
				//COLOR ROW BY STATUS!
				print "<tr class=$rowNum>" ;
					print "<td>" ;
						print "<b>" . $row["id"] . "</b><br/>" ;
						print "<span style='font-style: italic; font-size: 85%'>" ;
							try {
								$dataType=array("gibbonLibraryTypeID"=>$row["gibbonLibraryTypeID"]); 
								$sqlType="SELECT name FROM gibbonLibraryType WHERE gibbonLibraryTypeID=:gibbonLibraryTypeID" ;
								$resultType=$connection2->prepare($sqlType);
								$resultType->execute($dataType);
							}
							catch(PDOException $e) { 
								print "<div class='error'>" . $e->getMessage() . "</div>" ; 
							}
							if ($resultType->rowCount()==1) {
								$rowType=$resultType->fetch() ;
								print $rowType["name"] . "<br/>" ;
							}
						print "</span>" ;
					print "</td>" ;
					print "<td>" ;
						print "<b>" . $row["name"] . "</b><br/>" ;
						print "<span style='font-size: 85%; font-style: italic'>" . $row["producer"] . "</span>" ;
					print "</td>" ;
					print "<td>" ;
						if ($row["gibbonSpaceID"]!="") {
							try {
								$dataSpace=array("gibbonSpaceID"=>$row["gibbonSpaceID"]); 
								$sqlSpace="SELECT * FROM gibbonSpace WHERE gibbonSpaceID=:gibbonSpaceID" ;
								$resultSpace=$connection2->prepare($sqlSpace);
								$resultSpace->execute($dataSpace);
							}
							catch(PDOException $e) { 
								print "<div class='error'>" . $e->getMessage() . "</div>" ; 
							}
							if ($resultSpace->rowCount()==1) {
								$rowSpace=$resultSpace->fetch() ;
								print $rowSpace["name"] . "<br/>" ;
							}
						}
						if ($row["locationDetail"]!="") {
							print "<span style='font-size: 85%; font-style: italic'>" . $row["locationDetail"] . "</span>" ;
						}
					print "</td>" ;
					print "<td>" ;
						if ($row["ownershipType"]=="School") {
							print $_SESSION[$guid]["organisationNameShort"] . "<br/>" ;
						}
						else if ($row["ownershipType"]=="Individual") {
							print "Individual<br/>" ;
						}
						if ($row["gibbonPersonIDOwnership"]!="") {
							try {
								$dataPerson=array("gibbonPersonID"=>$row["gibbonPersonIDOwnership"]); 
								$sqlPerson="SELECT title, preferredName, surname FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID" ;
								$resultPerson=$connection2->prepare($sqlPerson);
								$resultPerson->execute($dataPerson);
							}
							catch(PDOException $e) { 
								print "<div class='error'>" . $e->getMessage() . "</div>" ; 
							}
							if ($resultPerson->rowCount()==1) {
								$rowPerson=$resultPerson->fetch() ;
								print "<span style='font-size: 85%; font-style: italic'>" . formatName($rowPerson["title"], $rowPerson["preferredName"], $rowPerson["surname"], "Staff", FALSE, TRUE) . "</span>" ;
							}
						}
					print "</td>" ;
					print "<td>" ;
						print $row["status"] . "<br/>" ;
						print "<span style='font-size: 85%; font-style: italic'>" . $row["borrowable"] . "</span>" ;
					print "</td>" ;
					print "<td>" ;
						if ($row["purchaseDate"]=="") {
							print "<i>" . _('Unknown') . "</i><br/>" ;
						}
						else {
							print dateConvertBack($guid, $row["purchaseDate"]) . "<br/>" ;
						}
						print "<span style='font-size: 85%; font-style: italic'>" . $row["vendor"] . "</span>" ;
					print "</td>" ;
				print "</tr>" ;
				
				$count++ ;
			}
		print "</table>" ;
	}
}
?>