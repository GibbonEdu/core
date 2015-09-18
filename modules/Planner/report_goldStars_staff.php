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

if (isActionAccessible($guid, $connection2, "/modules/Planner/report_goldStars_staff.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . _('Staff Like') . "</div>" ;
	print "</div>" ;
	
	try {
		$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
		$sql="SELECT gibbonPerson.gibbonPersonID AS personID, surname, preferredName, COUNT(*) as likes FROM gibbonLike JOIN gibbonPerson ON (gibbonLike.gibbonPersonIDRecipient=gibbonPerson.gibbonPersonID) JOIN gibbonStaff ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE status='Full' AND gibbonSchoolYearID=:gibbonSchoolYearID GROUP BY gibbonPerson.gibbonPersonID ORDER BY likes DESC" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		print "<div class='error'>" . $e->getMessage() . "</div>" ; 
	}

	print "<table cellspacing=\"0\"style='width: 100%'>" ;
		print "<tr class='head'>" ;
			print "<th style='width: 100px'>" ;
				print _("Position") ;
			print "</th>" ;
			print "<th>" ;
				print _("Teacher") ;
			print "</th>" ;
			print "<th>" ;
				print _("Likes") ;
			print "</th>" ;
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
					print $count ;
				print "</td>" ;
				print "<td>" ;
					print formatName("", $row["preferredName"], $row["surname"], "Staff", false, true) ;
				print "</td>" ;
				print "<td>" ;
					print $row["likes"] ;
				print "</td>" ;
			print "</tr>" ;
		}
		if ($count==0) {
			print "<tr class=$rowNum>" ;
				print "<td colspan=3>" ;
					print _("There are no records to display.") ;
				print "</td>" ;
			print "</tr>" ;
		}
	print "</table>" ;
}
?>