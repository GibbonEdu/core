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

if (isActionAccessible($guid, $connection2, "/modules/User Admin/role_manage.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __($guid, 'Manage Roles') . "</div>" ;
	print "</div>" ;
	
	if (isset($_GET["deleteReturn"])) { $deleteReturn=$_GET["deleteReturn"] ; } else { $deleteReturn="" ; }
	$deleteReturnMessage="" ;
	$class="error" ;
	if (!($deleteReturn=="")) {
		if ($deleteReturn=="success0") {
			$deleteReturnMessage=__($guid, "Your request was completed successfully.") ;		
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $deleteReturnMessage;
		print "</div>" ;
	} 
	
	if (isset($_GET["duplicateReturn"])) { $duplicateReturn=$_GET["duplicateReturn"] ; } else { $duplicateReturn="" ; }
	$duplicateReturnMessage="" ;
	$class="error" ;
	if (!($duplicateReturn=="")) {
		if ($duplicateReturn=="fail0") {
			$duplicateReturnMessage=__($guid, "Your request failed because you do not have access to this action.") ;	
		}
		else if ($duplicateReturn=="fail2") {
			$duplicateReturnMessage=__($guid, "Your request failed due to a database error.") ;	
		}
		else if ($duplicateReturn=="fail3") {
			$duplicateReturnMessage=__($guid, "Your request failed because your inputs were invalid.") ;	
		}
		else if ($duplicateReturn=="fail6") {
			$duplicateReturnMessage="Your request was successful, but some data was not properly saved." ;	
			$class="success" ;
		}
		else if ($duplicateReturn=="success0") {
			$duplicateReturnMessage=__($guid, "Your request was successful.") ;	
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $duplicateReturnMessage;
		print "</div>" ;
	} 

	try {
		$data=array(); 
		$sql="SELECT * FROM gibbonRole ORDER BY type, name" ; 
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		print "<div class='error'>" . $e->getMessage() . "</div>" ; 
	}
	
	print "<div class='linkTop'>" ;
	print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/role_manage_add.php'>" .  __($guid, 'Add') . "<img style='margin-left: 5px' title='" . __($guid, 'Add') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/page_new.png'/></a>" ;
	print "</div>" ;
	
	if ($result->rowCount()<1) {
		print "<div class='error'>" ;
		print __($guid, "There are no records to display.") ;
		print "</div>" ;
	}
	else {
		print "<table cellspacing='0' style='width: 100%'>" ;
			print "<tr class='head'>" ;
				print "<th>" ;
					print __($guid, "Category") ;
				print "</th>" ;
				print "<th>" ;
					print __($guid, "Name") ;
				print "</th>" ;
				print "<th>" ;
					print __($guid, "Short Name") ;
				print "</th>" ;
				print "<th>" ;
					print __($guid, "Description") ;
				print "</th>" ;
				print "<th>" ;
					print __($guid, "Type") ;
				print "</th>" ;
				print "<th>" ;
					print __($guid, "Login Years") ;
				print "</th>" ;
				print "<th style='width:110px'>" ;
					print __($guid, "Action") ;
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
						print __($guid, $row["category"]) ;
					print "</td>" ;
					print "<td>" ;
						print __($guid, $row["name"]) ;
					print "</td>" ;
					print "<td>" ;
						print __($guid, $row["nameShort"]) ;
					print "</td>" ;
					print "<td>" ;
						print __($guid, $row["description"]) ;
					print "</td>" ;
					print "<td>" ;
						print __($guid, $row["type"]) ;
					print "</td>" ;
					print "<td>" ;
						if ($row["futureYearsLogin"]=="Y" AND $row["pastYearsLogin"]=="Y") {
							print __($guid, "All years") ;
						}
						else if ($row["futureYearsLogin"]=="N" AND $row["pastYearsLogin"]=="N") {
							print __($guid, "Current year only") ;
						}
						else if ($row["futureYearsLogin"]=="N") {
							print __($guid, "Current/past years only") ;
						}
						else if ($row["pastYearsLogin"]=="N") {
							print __($guid, "Current/future years only") ;
						}
					print "</td>" ;
					print "<td>" ;
						print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/role_manage_edit.php&gibbonRoleID=" . $row["gibbonRoleID"] . "'><img title='" . __($guid, 'Edit') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
						if ($row["type"]=="Additional") {
							print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/role_manage_delete.php&gibbonRoleID=" . $row["gibbonRoleID"] . "'><img title='" . __($guid, 'Delete') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/></a>" ;
						}
						print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/role_manage_duplicate.php&gibbonRoleID=" . $row["gibbonRoleID"] . "'><img title='" . __($guid, 'Duplicate') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/copy.png'/></a> " ;
					print "</td>" ;
				print "</tr>" ;
			}
		print "</table>" ;
	}
}
?>