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

if (isActionAccessible($guid, $connection2, "/modules/Timetable/tt_view.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Get action with highest precendence
	$highestAction=getHighestGroupedAction($guid, $_GET["q"], $connection2) ;
	if ($highestAction==FALSE) {
		print "<div class='error'>" ;
		print _("The highest grouped action cannot be determined.") ;
		print "</div>" ;
	}
	else {
		$gibbonPersonID=NULL ;
		if (isset($_GET["gibbonPersonID"])) {
			$gibbonPersonID=$_GET["gibbonPersonID"] ;
		}
		$search=NULL ;
		if (isset($_GET["search"])) {
			$search=$_GET["search"] ;
		}
		$allUsers=NULL ;
		if (isset($_GET["allUsers"])) {
			$allUsers=$_GET["allUsers"] ;
		}
		$gibbonTTID=NULL ;
		if (isset($_GET["gibbonTTID"])) {
			$gibbonTTID=$_GET["gibbonTTID"] ;
		}
		
		try {
			if ($allUsers=="on") {
				$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonID"=>$gibbonPersonID); 
				$sql="SELECT gibbonPerson.gibbonPersonID, surname, preferredName, title, image_240, gibbonYearGroup.nameShort AS yearGroup, gibbonRollGroup.nameShort AS rollGroup, 'Student' AS type FROM gibbonPerson LEFT JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID) LEFT JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) LEFT JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) WHERE gibbonPerson.status='Full' AND gibbonPerson.gibbonPersonID=:gibbonPersonID ORDER BY surname, preferredName" ; 
			}
			else {
				$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonID1"=>$gibbonPersonID, "gibbonPersonID2"=>$gibbonPersonID); 
				$sql="(SELECT gibbonPerson.gibbonPersonID, gibbonStudentEnrolmentID, surname, preferredName, title, image_240, gibbonYearGroup.nameShort AS yearGroup, gibbonRollGroup.nameShort AS rollGroup, 'Student' AS type, gibbonRoleIDPrimary FROM gibbonPerson, gibbonStudentEnrolment, gibbonYearGroup, gibbonRollGroup WHERE (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) AND (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) AND (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPerson.status='Full' AND gibbonPerson.gibbonPersonID=:gibbonPersonID1) UNION (SELECT gibbonPerson.gibbonPersonID, NULL AS gibbonStudentEnrolmentID, surname, preferredName, title, image_240, NULL AS yearGroup, NULL AS rollGroup, 'Staff' AS type, gibbonRoleIDPrimary FROM gibbonPerson JOIN gibbonStaff ON (gibbonPerson.gibbonPersonID=gibbonStaff.gibbonPersonID) WHERE type='Teaching' AND gibbonPerson.status='Full' AND gibbonPerson.gibbonPersonID=:gibbonPersonID2) ORDER BY surname, preferredName" ; 
			}
			$result=$connection2->prepare($sql);
			$result->execute($data);
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
			
			print "<div class='trail'>" ;
			print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/tt.php&allUsers=$allUsers'>" . _('View Timetable by Person') . "</a> > </div><div class='trailEnd'>" . formatName($row["title"], $row["preferredName"], $row["surname"], $row["type"]) . "</div>" ;
			print "</div>" ;
			
			if (isActionAccessible($guid, $connection2, "/modules/Timetable Admin/courseEnrolment_manage_byPerson_edit.php")==TRUE) {
				$role=getRoleCategory($row["gibbonRoleIDPrimary"], $connection2) ;
				if ($role=="Student" OR $role=="Staff" OR $allUsers=="on" OR $search!="") {
					print "<div class='linkTop'>" ;
					
						if ($search!="") {
							print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Timetable/tt.php&search=" . $search . "&allUsers=$allUsers'>" . _('Back to Search Results') . "</a>" ;
						}
						if ($role=="Student" OR $role=="Staff" OR $allUsers=="on") {
							if ($search!="") {
								print " | " ;
							}
							print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Timetable Admin/courseEnrolment_manage_byPerson_edit.php&gibbonPersonID=$gibbonPersonID&gibbonSchoolYearID=" . $_SESSION[$guid]["gibbonSchoolYearID"] . "&type=$role&allUsers=$allUsers'>" . _('Edit') . "<img style='margin: 0 0 -4px 5px' title='" . _('Edit') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
						}
					print "</div>" ;
				}
			}

			print "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>" ;
				print "<tr>" ;
					print "<td style='width: 34%; vertical-align: top'>" ;
						print "<span style='font-size: 115%; font-weight: bold'>" . _('Name') . "</span><br/>" ;
						print formatName( $row["title"], $row["preferredName"], $row["surname"], $row["type"], false ) ;
						print "</td>" ;
					print "<td style='width: 33%; vertical-align: top'>" ;
						print "<span style='font-size: 115%; font-weight: bold'>" . _('Year Group') . "</span><br/>" ;
						if ($row["yearGroup"]!="") {
							print "<i>" . _($row["yearGroup"]) . "</i>" ;
						}
					print "</td>" ;
					print "<td style='width: 34%; vertical-align: top'>" ;
						print "<span style='font-size: 115%; font-weight: bold'>" . _('Roll Group') . "</span><br/>" ;
						print "<i>" . $row["rollGroup"] . "</i>" ;
					print "</td>" ;
				print "</tr>" ;
			print "</table>" ;
			
			$ttDate=NULL ;
			if (isset($_POST["ttDate"])) {
				$ttDate=dateConvertToTimestamp(dateConvert($guid, $_POST["ttDate"]));
			}
			
			if (isset($_POST["fromTT"])) {
				if ($_POST["fromTT"]=="Y") {
					if (@$_POST["schoolCalendar"]=="on" OR @$_POST["schoolCalendar"]=="Y") {
						$_SESSION[$guid]["viewCalendarSchool"]="Y" ;
					}
					else {
						$_SESSION[$guid]["viewCalendarSchool"]="N" ;
					}
				
					if (@$_POST["personalCalendar"]=="on" OR @$_POST["personalCalendar"]=="Y") {
						$_SESSION[$guid]["viewCalendarPersonal"]="Y" ;
					}
					else {
						$_SESSION[$guid]["viewCalendarPersonal"]="N" ;
					}
				}
			}
			
			$tt=renderTT($guid, $connection2, $gibbonPersonID, $gibbonTTID, FALSE, $ttDate, "/modules/Timetable/tt_view.php", "&gibbonPersonID=$gibbonPersonID&allUsers=$allUsers") ;
			if ($tt!=FALSE) {
				print $tt ;
			}
			else {
				print "<div class='error'>" ;
					print _("There are no records to display.") ;
				print "</div>" ;
			}
			
			//Set sidebar
			$_SESSION[$guid]["sidebarExtra"]=getUserPhoto($guid, $row["image_240"], 240) ;
		}
	}
}
?>