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

if (isActionAccessible($guid, $connection2, "/modules/Library/report_viewOverdueItems.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . _('View Overdue Items') . "</div>" ;
	print "</div>" ;
	
	print "<h2>" ;
	print _("Report Data") ;
	print "</h2>" ;
	
	$today=date("Y-m-d") ;
	
	try {
		$data=array("today"=>$today); 
		$sql="SELECT gibbonLibraryItem.*, surname, preferredName, email FROM gibbonLibraryItem JOIN gibbonPerson ON (gibbonLibraryItem.gibbonPersonIDStatusResponsible=gibbonPerson.gibbonPersonID) WHERE gibbonLibraryItem.status='On Loan' AND borrowable='Y' AND returnExpected<:today ORDER BY surname, preferredName" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		print "<div class='error'>" . $e->getMessage() . "</div>" ; 
	}
	
	print "<table cellspacing='0' style='width: 100%'>" ;
		print "<tr class='head'>" ;
			print "<th>" ;
				print _("Borrowing User") ;
			print "</th>" ;
			print "<th>" ;
				print _("Email") ;
			print "</th>" ;
			print "<th>" ;
				print _("Item") . "<br/>" ;
				print "<span style='font-size: 85%; font-style: italic'>" . _('Author/Producer') . "</span>" ;
			print "</th>" ;
			print "<th>" ;
				print _("Due Date") ;
			print "</th>" ;
			print "<th>" ;
				print _("Days Overdue") ;
			print "</th>" ;
			print "<th style='width: 50px'>" ;
				print _("Actions") ;
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
			$count++ ;
			
			//COLOR ROW BY STATUS!
			print "<tr class=$rowNum>" ;
				print "<td>" ;
					print formatName("", $row["preferredName"], $row["surname"], "Student", true) ;
				print "</td>" ;
				print "<td>" ;
					print $row["email"] ;
				print "</td>" ;
				print "<td>" ;
					print "<b>" . $row["name"] . "</b><br/>" ;
					print "<span style='font-size: 85%; font-style: italic'>" . $row["producer"] . "</span>" ;
				print "</td>" ;
				print "<td>" ;
					print dateConvertBack($guid, $row["returnExpected"]) ;
				print "</td>" ;
				print "<td>" ;
					print (strtotime($today)-strtotime($row["returnExpected"]))/(60*60*24) ;
				print "</td>" ;
				print "<td>" ;
					print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/library_lending_item.php&gibbonLibraryItemID=" . $row["gibbonLibraryItemID"] . "&name=&gibbonLibraryTypeID=&gibbonSpaceID=&status='><img title='" . _('Edit') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
				print "</td>" ;
			print "</tr>" ;
		}
		if ($count==0) {
			print "<tr class=$rowNum>" ;
				print "<td colspan=4>" ;
					print _("There are no records to display.") ;
				print "</td>" ;
			print "</tr>" ;
		}
	print "</table>" ;
}
?>