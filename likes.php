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

print "<div class='trail'>" ;
print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > </div><div class='trailEnd'>" . _("Likes") . "</div>" ;
print "</div>" ;
print "<p>" ;
print _("This page shows you a break down of all your likes in the current year, and they have been earned.") ;
print "</p>" ;

//Count planner likes
$resultLike=countLikesByRecipient($connection2, $_SESSION[$guid]["gibbonPersonID"], "result", $_SESSION[$guid]["gibbonSchoolYearID"]) ;
if ($resultLike==FALSE) {
	print "<div class='error'>" . _('An error has occurred.') . "</div>" ; 
}
else {
	if ($resultLike->rowCount()<1) {
		print "<div class='error'>" ;
		print _("There are no records to display.") ;
		print "</div>" ;
	}
	else {
		print "<table cellspacing='0' style='width: 100%'>" ;
			print "<tr class='head'>" ;
				print "<th style='width: 90px'>" ;
					print _("Photo") ;
				print "</th>" ;
				print "<th style='width: 180px'>" ;
					print _("Giver") ;
					print "<span style='font-size: 85%; font-style: italic'>" . _("Role") . "</span>" ;
				print "</th>" ;
				print "<th>" ;
					print _("Title") . "<br/>" ;
					print "<span style='font-size: 85%; font-style: italic'>" . _("Comment") . "</span>" ;
				print "</th>" ;
				print "<th style='width: 70px'>" ;
					print _("Date") ;
				print "</th>" ;
			print "</tr>" ;
		
			$count=0;
			$rowNum="odd" ;
			while ($row=$resultLike->fetch()) {
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
						print getUserPhoto($guid, $row["image_240"], 75) ;
					print "</td>" ;
					print "<td>" ;
						$roleCategory=getRoleCategory($row["gibbonRoleIDPrimary"], $connection2) ;
						if ($roleCategory=="Student" AND isActionAccessible($guid, $connection2, "/modules/Students/student_view_details.php")) {
							print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=" . $row["gibbonPersonID"] . "'>" . formatName("", $row["preferredName"], $row["surname"], $roleCategory, false) . "</a><br/>" ;
							print "<span style='font-size: 85%; font-style: italic'>" . _($roleCategory) . "</i>" ;
						}
						else {
							print formatName("", $row["preferredName"], $row["surname"], $roleCategory, false) . "<br/>" ;
							print "<span style='font-size: 85%; font-style: italic'>" . _($roleCategory) . "</i>" ;
						}
					print "</td>" ;
					print "<td>" ;
						print _($row["title"]) . "<br/>" ;
						print "<span style='font-size: 85%; font-style: italic'>" . $row["comment"] . "</span>" ;
					print "</td>" ;
					print "<td>" ;
						print dateConvertBack($guid, substr($row["timestamp"],0,10)) ;
					print "</td>" ;
				print "</tr>" ;
			}
		print "</table>" ;
	}
}
?>



