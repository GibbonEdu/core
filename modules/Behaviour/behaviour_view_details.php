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

$enableDescriptors=getSettingByScope($connection2, "Behaviour", "enableDescriptors") ;
$enableLevels=getSettingByScope($connection2, "Behaviour", "enableLevels") ;

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]["timezone"]);

if (isActionAccessible($guid, $connection2, "/modules/Behaviour/behaviour_view_details.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	$gibbonPersonID=$_GET["gibbonPersonID"] ;
	
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Behaviour/behaviour_view.php'>" . _('View Behaviour Records') . "</a> > </div><div class='trailEnd'>" . _('View Student Record') . "</div>" ;
	print "</div>" ;
	
	try {
		$data=array("gibbonPersonID"=>$gibbonPersonID, "gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
		$sql="SELECT * FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) WHERE status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonPerson.gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID" ;
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
		
		if ($_GET["search"]!="") {
			print "<div class='linkTop'>" ;
				print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Behaviour/behaviour_view.php&search=" . $_GET["search"] . "'>" . _('Back to Search Results') . "</a>" ;
			print "</div>" ;
		}
	
		print "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>" ;
			print "<tr>" ;
				print "<td style='width: 34%; vertical-align: top'>" ;
					print "<span style='font-size: 115%; font-weight: bold'>" . _('Name') . "</span><br/>" ;
					print formatName("", $row["preferredName"], $row["surname"], "Student") ;
				print "</td>" ;
				print "<td style='width: 33%; vertical-align: top'>" ;
					print "<span style='font-size: 115%; font-weight: bold'>" . _('Year Group') . "</span><br/>" ;
					try {
						$dataDetail=array("gibbonYearGroupID"=>$row["gibbonYearGroupID"]); 
						$sqlDetail="SELECT * FROM gibbonYearGroup WHERE gibbonYearGroupID=:gibbonYearGroupID" ;
						$resultDetail=$connection2->prepare($sqlDetail);
						$resultDetail->execute($dataDetail);
					}
					catch(PDOException $e) { 
						print "<div class='error'>" . $e->getMessage() . "</div>" ; 
					}
					if ($resultDetail->rowCount()==1) {
						$rowDetail=$resultDetail->fetch() ;
						print "<i>" . _($rowDetail["name"]) . "</i>" ;
					}
				print "</td>" ;
				print "<td style='width: 34%; vertical-align: top'>" ;
					print "<span style='font-size: 115%; font-weight: bold'>" . _('Roll Group') . "</span><br/>" ;
					try {
						$dataDetail=array("gibbonRollGroupID"=>$row["gibbonRollGroupID"]); 
						$sqlDetail="SELECT * FROM gibbonRollGroup WHERE gibbonRollGroupID=:gibbonRollGroupID" ;
						$resultDetail=$connection2->prepare($sqlDetail);
						$resultDetail->execute($dataDetail);
					}
					catch(PDOException $e) { 
						print "<div class='error'>" . $e->getMessage() . "</div>" ; 
					}
					if ($resultDetail->rowCount()==1) {
						$rowDetail=$resultDetail->fetch() ;
						print "<i>" . $rowDetail["name"] . "</i>" ;
					}
				print "</td>" ;
			print "</tr>" ;
		print "</table>" ;
		
		getBehaviourRecord($guid, $gibbonPersonID, $connection2) ;
	}
}
?>