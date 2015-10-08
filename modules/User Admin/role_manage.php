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
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . _('Manage Roles') . "</div>" ;
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
	
	if (isset($_GET["duplicateReturn"])) { $duplicateReturn=$_GET["duplicateReturn"] ; } else { $duplicateReturn="" ; }
	$duplicateReturnMessage="" ;
	$class="error" ;
	if (!($duplicateReturn=="")) {
		if ($duplicateReturn=="fail0") {
			$duplicateReturnMessage=_("Your request failed because you do not have access to this action.") ;	
		}
		else if ($duplicateReturn=="fail2") {
			$duplicateReturnMessage=_("Your request failed due to a database error.") ;	
		}
		else if ($duplicateReturn=="fail3") {
			$duplicateReturnMessage=_("Your request failed because your inputs were invalid.") ;	
		}
		else if ($duplicateReturn=="fail6") {
			$duplicateReturnMessage="Your request was successful, but some data was not properly saved." ;	
			$class="success" ;
		}
		else if ($duplicateReturn=="success0") {
			$duplicateReturnMessage=_("Your request was successful.") ;	
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
	print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/role_manage_add.php'>" .  _('Add') . "<img style='margin-left: 5px' title='" . _('Add') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/page_new.png'/></a>" ;
	print "</div>" ;
	
	if ($result->rowCount()<1) {
		print "<div class='error'>" ;
		print _("There are no records to display.") ;
		print "</div>" ;
	}
	else {
		print "<table cellspacing='0' style='width: 100%'>" ;
			print "<tr class='head'>" ;
				print "<th>" ;
					print _("Category") ;
				print "</th>" ;
				print "<th>" ;
					print _("Name") ;
				print "</th>" ;
				print "<th>" ;
					print _("Short Name") ;
				print "</th>" ;
				print "<th>" ;
					print _("Description") ;
				print "</th>" ;
				print "<th>" ;
					print _("Type") ;
				print "</th>" ;
				print "<th>" ;
					print _("Login Years") ;
				print "</th>" ;
				print "<th style='width:110px'>" ;
					print _("Action") ;
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
						print _($row["category"]) ;
					print "</td>" ;
					print "<td>" ;
						print _($row["name"]) ;
					print "</td>" ;
					print "<td>" ;
						print _($row["nameShort"]) ;
					print "</td>" ;
					print "<td>" ;
						print _($row["description"]) ;
					print "</td>" ;
					print "<td>" ;
						print _($row["type"]) ;
					print "</td>" ;
					print "<td>" ;
						if ($row["futureYearsLogin"]=="Y" AND $row["pastYearsLogin"]=="Y") {
							print _("All years") ;
						}
						else if ($row["futureYearsLogin"]=="N" AND $row["pastYearsLogin"]=="N") {
							print _("Current year only") ;
						}
						else if ($row["futureYearsLogin"]=="N") {
							print _("Current/past years only") ;
						}
						else if ($row["pastYearsLogin"]=="N") {
							print _("Current/future years only") ;
						}
					print "</td>" ;
					print "<td>" ;
						print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/role_manage_edit.php&gibbonRoleID=" . $row["gibbonRoleID"] . "'><img title='" . _('Edit') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
						if ($row["type"]=="Additional") {
							print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/role_manage_delete.php&gibbonRoleID=" . $row["gibbonRoleID"] . "'><img title='" . _('Delete') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/></a>" ;
						}
						print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/role_manage_duplicate.php&gibbonRoleID=" . $row["gibbonRoleID"] . "'><img title='" . _('Duplicate') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/copy.png'/></a> " ;
					print "</td>" ;
				print "</tr>" ;
			}
		print "</table>" ;
	}
}
?>