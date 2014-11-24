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
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . _('Manage Modules') . "</div>" ;
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
	
	if (isset($_GET["addReturn"])) { $addReturn=$_GET["addReturn"] ; } else { $addReturn="" ; }
	$addReturnMessage ="" ;
	$class="error" ;
	if (!($addReturn=="")) {
		if ($addReturn=="fail0") {
			$addReturnMessage =_("Your request failed because you do not have access to this action.") ;	
		}
		else if ($addReturn=="fail2") {
			$addReturnMessage =_("Your request failed due to a database error.") ;	
		}
		else if ($addReturn=="fail3") {
			$addReturnMessage =_("Install failed because either the module name was not given or the manifest file was invalid.") ;	
		}
		else if ($addReturn=="fail4") {
			$addReturnMessage =("Install failed because a module with the same name is already installed.") ;	
		}
		else if ($addReturn=="fail5") {
			$addReturnMessage =_("Install failed, but module was added to the system and set non-active.") ;	
		}
		else if ($addReturn=="fail6") {
			$addReturnMessage =_("Install was successful, but module could not be activated.") ;
		}
		else if ($addReturn=="success0") {
			$addReturnMessage =_("Your request was successful. You can now add another record if you wish.") ;	
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $addReturnMessage;
			if (isset($_SESSION[$guid]["moduleInstallError"])) {
				if ($_SESSION[$guid]["moduleInstallError"]!="") {
					print _("The following SQL statements caused errors:") . " " . $_SESSION[$guid]["moduleInstallError"] ;
				}
				$_SESSION[$guid]["moduleInstallError"]=NULL ;
			}
		print "</div>" ;
	} 
	
	//Get modules from database, and store in an array
	try {
		$data=array(); 
		$sql="SELECT * FROM gibbonModule ORDER BY name" ; 
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		print "<div class='error'>" . $e->getMessage() . "</div>" ; 
	}
	$modulesSQL=array() ;
	while ($row=$result->fetch()) {
		$modulesSQL[$row["name"]]=$row ;
	}
	
	//Get list of modules in /modules directory
	$modulesFS=glob($_SESSION[$guid]["absolutePath"] . "/modules/*" , GLOB_ONLYDIR);

	print "<div class='warning'>" ;
		print sprintf(_('To install a module upload the module folder to %1$s on your server and then refresh this page. After refresh, the module should appear in the list below: use the install button in the Actions column to set it up.'), "<b><u>" . $_SESSION[$guid]["absolutePath"] . "/modules/</u></b>") ;
	print "</div>" ;
	
	if (count($modulesFS)<1) {
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
				print "<th>" ;
					print _("Status") ;
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
			foreach ($modulesFS AS $moduleFS) {
				$moduleName=substr($moduleFS, strlen($_SESSION[$guid]["absolutePath"] ."/modules/")) ;
				
				if ($count%2==0) {
					$rowNum="even" ;
				}
				else {
					$rowNum="odd" ;
				}
				$installed=TRUE ;
				if (isset($modulesSQL[$moduleName])==FALSE) {
					$installed=FALSE ;
					$rowNum="warning" ;
				}
				
				$count++ ;
				
				//COLOR ROW BY STATUS!
				print "<tr class=$rowNum>" ;
					print "<td>" ;
						print _($moduleName) ;
					print "</td>" ;
					if ($installed) {
						print "<td>" ;
							print _("Installed") ;
						print "</td>" ;
					}
					else {
						//Check for valid manifest
						$manifestOK=FALSE ;
						if (include $_SESSION[$guid]["absolutePath"] . "/modules/$moduleName/manifest.php") {
							if ($name!="" AND $description!="" AND $version!="") {
								if ($name==$moduleName) {
									$manifestOK=TRUE ;
								}
							}
						}
						if ($manifestOK) {
							print "<td colspan=6>" ;
								print _("Inactive") ;
							print "</td>" ;
						}
						else {
							print "<td colspan=7>" ;
								print _("Module error") ;
							print "</td>" ;
						}
					}
					if ($installed) {
						print "<td>" ;
							print _($modulesSQL[$moduleName]["description"]) ;
						print "</td>" ;
						print "<td>" ;
							print _($modulesSQL[$moduleName]["type"]) ;
						print "</td>" ;
						print "<td>" ;
							print ynExpander($modulesSQL[$moduleName]["active"]) ;
						print "</td>" ;
						print "<td>" ;
							if ($row["type"]=="Additional") {
								print "v" . $modulesSQL[$moduleName]["version"] ;
							}
							else {
								print "v" . $version ;
							}
						print "</td>" ;
						print "<td>" ;
							if ($row["url"]!="") {
								print "<a href='" . $modulesSQL[$moduleName]["url"] . "'>" . $modulesSQL[$moduleName]["author"] . "</a>" ;
							}
							else {
								print $modulesSQL[$moduleName]["author"] ;
							}
						print "</td>" ;
						print "<td style='width: 120px'>" ;
							print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/module_manage_edit.php&gibbonModuleID=" . $modulesSQL[$moduleName]["gibbonModuleID"] . "'><img title='" . _('Edit') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
							if ($modulesSQL[$moduleName]["type"]=="Additional") {
								print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/module_manage_uninstall.php&gibbonModuleID=" . $modulesSQL[$moduleName]["gibbonModuleID"] . "'><img title='Uninstall' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/></a>" ;
								print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/module_manage_update.php&gibbonModuleID=" . $modulesSQL[$moduleName]["gibbonModuleID"] . "'><img title='Update' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/delivery2.png'/></a>" ;
							}
						print "</td>" ;
					}
					else {
						if ($manifestOK) {
							print "<td>" ;
								print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/module_manage_installProcess.php?name=" . urlencode($moduleName) . "'><img title='" . _('Install') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/page_new.png'/></a>" ;
							print "</td>" ;
						}
					}
				print "</tr>" ;
			}
		print "</table>" ;
	}
}
?>