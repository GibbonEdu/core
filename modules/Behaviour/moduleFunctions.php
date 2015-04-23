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

function getBehaviourRecord($guid, $gibbonPersonID, $connection2) {
	try {
		$dataYears=array("gibbonPersonID"=>$gibbonPersonID); 
		$sqlYears="SELECT * FROM gibbonStudentEnrolment JOIN gibbonSchoolYear ON (gibbonStudentEnrolment.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE gibbonPersonID=:gibbonPersonID ORDER BY sequenceNumber DESC" ;
		$resultYears=$connection2->prepare($sqlYears);
		$resultYears->execute($dataYears);
	}
	catch(PDOException $e) { 
		print "<div class='error'>" . $e->getMessage() . "</div>" ; 
	}

	if ($resultYears->rowCount()<1) {
		print "<div class='error'>" ;
		print _("There are no records to display.") ;
		print "</div>" ;
	}
	else {
		print "<div class='linkTop'>" ;
			$policyLink=getSettingByScope($connection2, "Behaviour", "policyLink") ;
			if (isActionAccessible($guid, $connection2, "/modules/Behaviour/behaviour_manage.php")==TRUE) {
				print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Behaviour/behaviour_manage_add.php&gibbonPersonID=$gibbonPersonID&gibbonRollGroupID=&gibbonYearGroupID=&type='>" . _('Add') . "<img style='margin: 0 0 -4px 5px' title='" . _('Add') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/page_new.png'/></a>" ;
				if ($policyLink!="") {
					print " | " ;
				}
			}
			if ($policyLink!="") {
				print "<a href='$policyLink'>" . _('View Behaviour Policy') . "</a>" ;
			}
		print "</div>" ;
		
		$yearCount=0 ;
		while ($rowYears=$resultYears->fetch()) {
			
			$class="" ;
			if ($yearCount==0) {
				$class="class='top'" ;
			}
			print "<h3 $class>" ;
			print $rowYears["name"] ;
			print "</h3>" ;
			
			$yearCount++ ;
			
			try {
				$data=array("gibbonPersonID"=>$gibbonPersonID, "gibbonSchoolYearID"=>$rowYears["gibbonSchoolYearID"]); 
				$sql="SELECT gibbonBehaviour.*, title, surname, preferredName FROM gibbonBehaviour JOIN gibbonPerson ON (gibbonBehaviour.gibbonPersonIDCreator=gibbonPerson.gibbonPersonID) WHERE gibbonBehaviour.gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY timestamp DESC" ;
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
							print _("Date") ;
						print "</th>" ;
						print "<th>" ;
							print _("Type") ;
						print "</th>" ;
						print "<th>" ;
							print _("Descriptor") ;
						print "</th>" ;
						print "<th>" ;
							print _("Level") ;
						print "</th>" ;
						print "<th>" ;
							print _("Teacher") ;
						print "</th>" ;
						print "<th>" ;
							print _("Actions") ;
						print "</th>" ;
					print "</tr>" ;
					
					$rowNum="odd" ;
					$count=0;
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
								if (substr($row["timestamp"],0,10)>$row["date"]) {
									print _("Updated:") . " " . dateConvertBack($guid, substr($row["timestamp"],0,10)) . "<br/>" ;
									print _("Incident:") . " " . dateConvertBack($guid, $row["date"]) . "<br/>" ;
								}
								else {
									print dateConvertBack($guid, $row["date"]) . "<br/>" ;
								}
							print "</td>" ;
							print "<td style='text-align: center'>" ;
								if ($row["type"]=="Negative") {
									print "<img src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/iconCross.png'/> " ;
								}
								else if ($row["type"]=="Positive") {
									print "<img src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/iconTick.png'/> " ;
								}
							print "</td>" ;
							print "<td>" ;
								print trim($row["descriptor"]) ;
							print "</td>" ;
							print "<td>" ;
								print trim($row["level"]) ;
							print "</td>" ;
							print "<td>" ;
								print formatName($row["title"], $row["preferredName"], $row["surname"], "Staff", false, true) . "</b><br/>" ;
							print "</td>" ;
							print "<td>" ;
								print "<script type='text/javascript'>" ;	
									print "$(document).ready(function(){" ;
										print "\$(\".comment-$count-$yearCount\").hide();" ;
										print "\$(\".show_hide-$count-$yearCount\").fadeIn(1000);" ;
										print "\$(\".show_hide-$count-$yearCount\").click(function(){" ;
										print "\$(\".comment-$count-$yearCount\").fadeToggle(1000);" ;
										print "});" ;
									print "});" ;
								print "</script>" ;
								if ($row["comment"]!="" OR $row["followup"]!="") {
									print "<a title='" . _('View Description') . "' class='show_hide-$count-$yearCount' onclick='false' href='#'><img style='padding-right: 5px' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/Default/img/page_down.png' alt='" . _('Show Comment') . "' onclick='return false;' /></a>" ;
								}
							print "</td>" ;
						print "</tr>" ;
						if ($row["comment"]!="" OR $row["followup"]!="") {
							if ($row["type"]=="Positive") {
								$bg="background-color: #D4F6DC;" ;
							}
							else {
								$bg="background-color: #F6CECB;" ;
							}
							print "<tr class='comment-$count-$yearCount' id='comment-$count-$yearCount'>" ;
								print "<td style='$bg' colspan=6>" ;
									if ($row["comment"]!="") {
										print "<b>" . _('Incident') . "</b><br/>" ;
										print nl2brr($row["comment"]) . "<br/><br/>" ;
									}
									if ($row["followup"]!="") {
										print "<b>" . _('Follow Up') . "</b><br/>" ;
										print nl2brr($row["followup"]) . "<br/><br/>" ;
									}
								print "</td>" ;
							print "</tr>" ;
						}
					}
				print "</table>" ;
			}
		}
	}
}
?>
