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

if (isActionAccessible($guid, $connection2, "/modules/System Admin/module_manage.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > </div><div class='trailEnd'>" . _('Manage Modules') . "</div>" ;
	print "</div>" ;
	
	if (isset($_GET["deleteReturn"])) { $deleteReturn=$_GET["deleteReturn"] ; } else { $deleteReturn="" ; }
	$deleteReturnMessage="" ;
	$class="error" ;
	if (!($deleteReturn=="")) {
		if ($deleteReturn=="success0") {
			$deleteReturnMessage="Uninstall was successful. You will still need to remove the module's files and database tables yourself." ;	
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
		$sql="SELECT * FROM gibbonModule ORDER BY name" ; 
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		print "<div class='error'>" . $e->getMessage() . "</div>" ; 
	}
	
	print "<div class='linkTop'>" ;
	print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/module_manage_install.php'><img title='" . _('Add New Record') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/page_new.gif'/></a>" ;
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
					print _("Name") ;
				print "</th>" ;
				print "<th style='width: 200px;'>" ;
					print _("Description") ;
				print "</th>" ;
				print "<th>" ;
					print _("Type") ;
				print "</th>" ;
				print "<th>" ;
					print _("Active") ;
				print "</th>" ;
				print "<th>" ;
					print _("Version") ;
				print "</th>" ;
				print "<th>" ;
					print _("Author") ;
				print "</th>" ;
				print "<th>" ;
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
						print $row["name"] ;
					print "</td>" ;
					print "<td>" ;
						print $row["description"] ;
					print "</td>" ;
					print "<td>" ;
						print $row["type"] ;
					print "</td>" ;
					print "<td>" ;
						print $row["active"] ;
					print "</td>" ;
					print "<td>" ;
						if ($row["type"]=="Additional") {
							print "v" . $row["version"] ;
						}
						else {
							print "v" . $version ;
						}
					print "</td>" ;
					print "<td>" ;
						if ($row["url"]!="") {
							print "<a href='" . $row["url"] . "'>" . $row["author"] . "</a>" ;
						}
						else {
							print $row["author"] ;
						}
					print "</td>" ;
					print "<td style='width: 120px'>" ;
						if ($row["type"]=="Additional") {
							print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/module_manage_edit.php&gibbonModuleID=" . $row["gibbonModuleID"] . "'><img title='" . _('Edit Record') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
							print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/module_manage_uninstall.php&gibbonModuleID=" . $row["gibbonModuleID"] . "'><img title='Uninstall' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/></a>" ;
							print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/module_manage_update.php&gibbonModuleID=" . $row["gibbonModuleID"] . "'><img title='Update' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/delivery2.png'/></a>" ;
						}
					print "</td>" ;
				print "</tr>" ;
			}
		print "</table>" ;
	}
}
?>