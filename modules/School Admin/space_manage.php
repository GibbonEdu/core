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

if (isActionAccessible($guid, $connection2, "/modules/School Admin/space_manage.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __($guid, 'Manage Facilities') . "</div>" ;
	print "</div>" ;
	
	if (isset($_GET["return"])) { returnProcess($guid, $_GET["return"], null, null); }
	
	//Set pagination variable
	$page=1 ; if (isset($_GET["page"])) { $page=$_GET["page"] ; }
	if ((!is_numeric($page)) OR $page<1) {
		$page=1 ;
	}
	
	try {
		$data=array(); 
		$sql="SELECT * FROM gibbonSpace ORDER BY name" ; 
		$sqlPage=$sql . " LIMIT " . $_SESSION[$guid]["pagination"] . " OFFSET " . (($page-1)*$_SESSION[$guid]["pagination"]) ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		print "<div class='error'>" . $e->getMessage() . "</div>" ; 
	}
	
	print "<div class='linkTop'>" ;
	print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/space_manage_add.php'>" .  __($guid, 'Add') . "<img style='margin-left: 5px' title='" . __($guid, 'Add') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/page_new.png'/></a>" ;
	print "</div>" ;
	
	if ($result->rowCount()<1) {
		print "<div class='error'>" ;
		print __($guid, "There are no records to display.") ;
		print "</div>" ;
	}
	else {
		if ($result->rowCount()>$_SESSION[$guid]["pagination"]) {
			printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]["pagination"], "top") ;
		}
	
		print "<table cellspacing='0' style='width: 100%'>" ;
			print "<tr class='head'>" ;
				print "<th>" ;
					print __($guid, "Name") ;
				print "</th>" ;
				print "<th>" ;
					print __($guid, "Type") ;
				print "</th>" ;
				print "<th>" ;
					print __($guid, "Staff") ;
				print "</th>" ;
				print "<th>" ;
					print __($guid, "Capacity") ;
				print "</th>" ;
				print "<th>" ;
					print __($guid, "Facilities") ;
				print "</th>" ;
				print "<th>" ;
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
				$count++ ;
				
				//COLOR ROW BY STATUS!
				print "<tr class=$rowNum>" ;
					print "<td>" ;
						print $row["name"] ;
					print "</td>" ;
					print "<td>" ;
						print $row["type"] ;
					print "</td>" ;
					print "<td>" ;
						try {
							$dataStaff=array("gibbonSpaceID"=>$row["gibbonSpaceID"]); 
							$sqlStaff="SELECT surname, preferredName FROM gibbonPerson JOIN gibbonSpace ON (gibbonPerson.gibbonPersonID=gibbonSpace.gibbonPersonID1 OR gibbonPerson.gibbonPersonID=gibbonSpace.gibbonPersonID2) WHERE gibbonSpaceID=:gibbonSpaceID AND status='Full' ORDER BY surname, preferredName" ;
							$resultStaff=$connection2->prepare($sqlStaff);
							$resultStaff->execute($dataStaff);
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						while ($rowStaff=$resultStaff->fetch()) {
							print formatName("", $rowStaff["preferredName"], $rowStaff["surname"], "Staff", true, true) . "<br/>" ;
						}
					print "</td>" ;
					print "<td>" ;
						print $row["capacity"] ;
					print "</td>" ;
					print "<td>" ;
						if ($row["computer"]=="Y") {
							print __($guid, "Teaching computer") . "<br/>" ;
						}
						if ($row["computerStudent"]>0) {
							print $row["computerStudent"] . " student computers<br/>" ;
						}
						if ($row["projector"]=="Y") {
							print __($guid, "Projector") . "<br/>" ;
						}
						if ($row["tv"]=="Y") {
							print __($guid, "TV") . "<br/>" ;
						}
						if ($row["dvd"]=="Y") {
							print __($guid, "DVD Player") . "<br/>" ;
						}
						if ($row["hifi"]=="Y") {
							print __($guid, "Hifi") . "<br/>" ;
						}
						if ($row["speakers"]=="Y") {
							print __($guid, "Speakers") . "<br/>" ;
						}
						if ($row["iwb"]=="Y") {
							print __($guid, "Interactive White Board") . "<br/>" ;
						}
						if ($row["phoneInternal"]!="") {
							print __($guid, "Extension Number") . ": " . $row["phoneInternal"] . "<br/>" ;
						}
						if ($row["phoneExternal"]!="") {
							print __($guid, "Phone Number") . ": " . $row["phoneExternal"] . "<br/>" ;
						}
					print "</td>" ;
					print "<td>" ;
						print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/space_manage_edit.php&gibbonSpaceID=" . $row["gibbonSpaceID"] . "'><img title='" . __($guid, 'Edit') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
						print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/space_manage_delete.php&gibbonSpaceID=" . $row["gibbonSpaceID"] . "'><img title='" . __($guid, 'Delete') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/></a>" ;
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