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

if (isActionAccessible($guid, $connection2, "/modules/User Admin/data_medical.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > </div><div class='trailEnd'>" . _('Medical Data Updates') . "</div>" ;
	print "</div>" ;
	
	if (isset($_GET["deleteReturn"])) { $deleteReturn=$_GET["deleteReturn"] ; } else { $deleteReturn="" ; }
	$deleteReturnMessage="" ;
	$class="error" ;
	if (!($deleteReturn=="")) {
		if ($deleteReturn=="success0") {
			$deleteReturnMessage=_("Your request was completed successfully.") ;		
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $deleteReturnMessage;
		print "</div>" ;
	} 
	
	//Set pagination variable
	$page=1 ; if (isset($_GET["page"])) { $page=$_GET["page"] ; }
	if ((!is_numeric($page)) OR $page<1) {
		$page=1 ;
	}
	
	try {
		$data=array(); 
		$sql="SELECT gibbonPersonMedicalUpdateID, gibbonPerson.surname, gibbonPerson.preferredName, timestamp, gibbonPersonIDUpdater, gibbonPersonMedicalUpdate.status FROM gibbonPersonMedicalUpdate JOIN gibbonPerson ON (gibbonPerson.gibbonPersonID=gibbonPersonMedicalUpdate.gibbonPersonID) ORDER BY status, timestamp" ; 
		$sqlPage=$sql . " LIMIT " . $_SESSION[$guid]["pagination"] . " OFFSET " . (($page-1)*$_SESSION[$guid]["pagination"]) ; 
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
		if ($result->rowCount()>$_SESSION[$guid]["pagination"]) {
			printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]["pagination"], "top") ;
		}
	
		print "<table cellspacing='0' style='width: 100%'>" ;
			print "<tr class='head'>" ;
				print "<th>" ;
					print _("Target User") ;
				print "</th>" ;
				print "<th>" ;
					print _("Requesting User") ;
				print "</th>" ;
				print "<th>" ;
					print _("Date & Time") ;
				print "</th>" ;
				print "<th>" ;
					print _("Status") ;
				print "</th>" ;
				print "<th>" ;
					print _("Actions") ;
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
				
				if ($row["status"]=="Complete") {
					$rowNum="current" ;
				}
				$count++ ;
				
				//COLOR ROW BY STATUS!
				print "<tr class=$rowNum>" ;
					print "<td>" ;
						print formatName("", $row["preferredName"], $row["surname"], "Student", false) ;
					print "</td>" ;
					print "<td>" ;
						try {
							$dataUpdater=array("gibbonPersonID"=>$row["gibbonPersonIDUpdater"]); 
							$sqlUpdater="SELECT gibbonPerson.title, gibbonPerson.surname, gibbonPerson.preferredName FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID" ; 
							$resultUpdater=$connection2->prepare($sqlUpdater);
							$resultUpdater->execute($dataUpdater);
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						if ($resultUpdater->rowCount()==1) {
							$rowUpdater=$resultUpdater->fetch() ;
							print formatName($rowUpdater["title"], $rowUpdater["preferredName"], $rowUpdater["surname"], "Parent", false) ; 
						}
					print "</td>" ;
					print "<td>" ;
						print dateConvertBack($guid, substr($row["timestamp"],0,10)) . " at " . substr($row["timestamp"],11,5) ;
					print "</td>" ;
					print "<td>" ;
						print $row["status"] ;
					print "</td>" ;
					print "<td>" ;
						if ($row["status"]=="Pending") {
							print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/data_medical_edit.php&gibbonPersonMedicalUpdateID=" . $row["gibbonPersonMedicalUpdateID"] . "'><img title='" . _('Edit Record') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
							print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/data_medical_delete.php&gibbonPersonMedicalUpdateID=" . $row["gibbonPersonMedicalUpdateID"] . "'><img title='" . _('Delete Record') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/></a>" ;
						}
					print "</td>" ;
				print "</tr>" ;
			}
		print "</table>" ;
		
		if ($result->rowCount()>$_SESSION[$guid]["pagination"]) {
			printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]["pagination"], "bottom") ;
		}
	}
}
?>