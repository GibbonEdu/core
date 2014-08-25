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

if (isActionAccessible($guid, $connection2, "/modules/Crowd Assessment/crowdAssess.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . _('View All Assessments') . "</div>" ;
	print "</div>" ;
	
	$sql=getLessons($guid, $connection2) ;
	
	try {
		$result=$connection2->prepare($sql[1]);
		$result->execute($sql[0]);
	}
	catch(PDOException $e) { 
		print "<div class='error'>" . $e->getMessage() . "</div>" ; 
	}
	
	print "<p>" ;
		print _("The list below shows all lessons in which there is work that you can crowd assess.") ;
	print "</p>" ;
	
	if ($result->rowCount()<1) {
		print "<div class='error'>" ;
		print _("There are currently no lessons to for you to crowd asess.") ;
		print "</div>" ;
	}
	else {
		print "<table cellspacing='0' style='width: 100%'>" ;
			print "<tr class='head'>" ;
				print "<th>" ;
					print _("Class") ;
				print "</th>" ;
				print "<th>" ;
					print _("Lesson") . "</br>" ;
					print "<span style='font-size: 85%; font-style: italic'>" . _('Unit') . "</span>" ;
				print "</th>" ;
				print "<th>" ;
					print _("Date") ;
				print "</th>" ;
				print "<th>" ;
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
						print $row["course"] . "." . $row["class"] ;
					print "</td>" ;
					print "<td>" ;
						print "<b>" . $row["name"] . "</b><br/>" ;
						print "<span style='font-size: 85%; font-style: italic'>" ;
							if ($row["gibbonUnitID"]!="") {
								try {
									$dataUnit=array("gibbonUnitID"=>$row["gibbonUnitID"]); 
									$sqlUnit="SELECT * FROM gibbonUnit WHERE gibbonUnitID=:gibbonUnitID" ;
									$resultUnit=$connection2->prepare($sqlUnit);
									$resultUnit->execute($dataUnit);
								}
								catch(PDOException $e) { }
								if ($resultUnit->rowCount()==1) {
									$rowUnit=$resultUnit->fetch() ;
									print $rowUnit["name"] ;
								}
							}
						print "</span>" ;
					print "</td>" ;
					print "<td>" ;
						print dateConvertBack($guid, $row["date"]) ;
					print "</td>" ;
					print "<td>" ;
						print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/crowdAssess_view.php&gibbonPlannerEntryID=" . $row["gibbonPlannerEntryID"] . "'><img title='" . _('View Details') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/plus.png'/></a> " ;
					print "</td>" ;
				print "</tr>" ;
			}
		print "</table>" ;
	}
}
?>