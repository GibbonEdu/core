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

include "./modules/System Admin/moduleFunctions.php" ;

@session_start() ;

if (isActionAccessible($guid, $connection2, "/modules/System Admin/theme_manage.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __($guid, 'Manage Themes') . "</div>" ;
	print "</div>" ;
	
	$returns=array() ;
	$returns["warning0"] = __($guid, "Uninstall was successful. You will still need to remove the theme's files yourself.") ;
	$returns["success0"] = __($guid, "Uninstall was successful.") ;
	$returns["success1"] = __($guid, "Install was successful.") ;
	$returns["error3"] = __($guid, "Your request failed because your manifest file was invalid.") ;
	$returns["error4"] = __($guid, "Your request failed because a theme with the same name is already installed.") ;
	if (isset($_GET["return"])) { returnProcess($guid, $_GET["return"], null, $returns); }
	
	//Get themes from database, and store in an array
	try {
		$data=array(); 
		$sql="SELECT * FROM gibbonTheme ORDER BY name" ; 
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		print "<div class='error'>" . $e->getMessage() . "</div>" ; 
	}
	$themesSQL=array() ;
	while ($row=$result->fetch()) {
		$themesSQL[$row["name"]][0]=$row ;
		$themesSQL[$row["name"]][1]="orphaned" ;
	}
	
	//Get list of modules in /modules directory
	$themesFS=glob($_SESSION[$guid]["absolutePath"] . "/themes/*" , GLOB_ONLYDIR);
	
	print "<div class='warning'>" ;
		print sprintf(__($guid, 'To install a theme, upload the theme folder to %1$s on your server and then refresh this page. After refresh, the theme should appear in the list below: use the install button in the Actions column to set it up.'), "<b><u>" . $_SESSION[$guid]["absolutePath"] . "/modules/</u></b>") ;
	print "</div>" ;
	
	if (count($themesFS)<1) {
		print "<div class='error'>" ;
		print __($guid, "There are no records to display.") ;
		print "</div>" ;
	}
	else {
		print "<form method='post' action='" . $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/theme_manageProcess.php'>" ;
			print "<table cellspacing='0' style='width: 100%'>" ;
				print "<tr class='head'>" ;
					print "<th>" ;
						print __($guid, "Name") ;
					print "</th>" ;
					print "<th>" ;
						print __($guid, "Status") ;
					print "</th>" ;
					print "<th>" ;
						print __($guid, "Description") ;
					print "</th>" ;
					print "<th>" ;
						print __($guid, "Version") ;
					print "</th>" ;
					print "<th>" ;
						print __($guid, "Author") ;
					print "</th>" ;
					print "<th>" ;
						print __($guid, "Active") ;
					print "</th>" ;
					print "<th style='width: 50px'>" ;
						print __($guid, "Action") ;
					print "</th>" ;
				print "</tr>" ;
				
				$count=0;
				$rowNum="odd" ;
				foreach ($themesFS AS $themesFS) {
					$themeName=substr($themesFS, strlen($_SESSION[$guid]["absolutePath"] ."/themes/")) ;
					$themesSQL[$themeName][1]="present" ;
					
					if ($count%2==0) {
						$rowNum="even" ;
					}
					else {
						$rowNum="odd" ;
					}
					
					$installed=TRUE ;
					if (isset($themesSQL[$themeName][0])==FALSE) {
						$installed=FALSE ;
						$rowNum="warning" ;
					}
				
					$count++ ;
					
					//COLOR ROW BY STATUS!
					print "<tr class=$rowNum>" ;
						print "<td>" ;
							print __($guid, $themeName) ;
						print "</td>" ;
						if ($installed) {
							print "<td>" ;
								print __($guid, "Installed") ;
							print "</td>" ;
						}
						else {
							//Check for valid manifest
							$manifestOK=FALSE ;
							if (include $_SESSION[$guid]["absolutePath"] . "/themes/$themeName/manifest.php") {
								if ($name!="" AND $description!="" AND $version!="") {
									if ($name==$themeName) {
										$manifestOK=TRUE ;
									}
								}
							}
							if ($manifestOK) {
								print "<td colspan=5>" ;
									print __($guid, "Not Installed") ;
								print "</td>" ;
							}
							else {
								print "<td colspan=6>" ;
									print __($guid, "Theme Error") ;
								print "</td>" ;
							}
						}
						if ($installed) {
							print "<td>" ;
								print $themesSQL[$themeName][0]["description"] ;
							print "</td>" ;
							print "<td>" ;
								if ($themesSQL[$themeName][0]["name"]=="Default") {
									print "v" . $version ;
								}
								else {
									$themeVerison=getThemeVersion($themeName, $guid) ;
									if ($themeVerison>$themesSQL[$themeName][0]["version"]) {
										//Update database
										try {
											$data=array("version"=>$themeVerison, "gibbonThemeID"=>$themesSQL[$themeName][0]["gibbonThemeID"] ); 
											$sql="UPDATE gibbonTheme SET version=:version WHERE gibbonThemeID=:gibbonThemeID" ; 
											$result=$connection2->prepare($sql);
											$result->execute($data);
										}
										catch(PDOException $e) { 
											print "<div class='error'>" . $e->getMessage() . "</div>" ; 
										}
										
									}
									else {
										$themeVerison=$themesSQL[$themeName][0]["version"] ;
									}
									
									
									print "v" . $themeVerison ;
								}
							print "</td>" ;
							print "<td>" ;
								if ($themesSQL[$themeName][0]["url"]!="") {
									print "<a href='" . $themesSQL[$themeName][0]["url"] . "'>" . $themesSQL[$themeName][0]["author"] . "</a>" ;
								}
								else {
									print $themesSQL[$themeName][0]["author"] ;
								}
							print "</td>" ;
							print "<td>" ;
								if ($themesSQL[$themeName][0]["active"]=="Y") {
									print "<input checked type='radio' name='gibbonThemeID' value='" . $themesSQL[$themeName][0]["gibbonThemeID"] . "'>" ;
								}
								else {
									print "<input type='radio' name='gibbonThemeID' value='" . $themesSQL[$themeName][0]["gibbonThemeID"] . "'>" ;
								}
							print "</td>" ;
							print "<td>" ;
								if ($themesSQL[$themeName][0]["name"]!="Default") {
									print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/theme_manage_uninstall.php&gibbonThemeID=" . $themesSQL[$themeName][0]["gibbonThemeID"] . "'><img title='" . __($guid, 'Remove Record') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/></a>" ;
								}
							print "</td>" ;
						}
						else {
							if ($manifestOK) {
								print "<td>" ;
									print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/theme_manage_installProcess.php?name=" . urlencode($themeName) . "'><img title='" . __($guid, 'Install') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/page_new.png'/></a>" ;
								print "</td>" ;
							}
						}
					print "</tr>" ;
				}
				print "<tr>" ;
					print "<td colspan=7 class='right'>" ;
						?>
						<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
						<input type="submit" value="<?php print __($guid, "Submit") ; ?>">
						<?php
					print "</td>" ;
				print "</tr>" ;
			print "</table>" ;
			
		print "</form>" ;
	}
	
	//Find and display orphaned themes
	$orphans=FALSE ;
	foreach($themesSQL AS $themeSQL) {
		if ($themeSQL[1]=="orphaned") {
			$orphans=TRUE ;
		}
	}
	
	if ($orphans) {
		print "<h2 style='margin-top: 40px'>" ;
			print __($guid, "Orphaned Themes") ;
		print "</h2>" ;
		print "<p>" ;
			print __($guid, "These themes are installed in the database, but are missing from within the file system.") ;
		print "</p>" ;
		
		print "<table cellspacing='0' style='width: 100%'>" ;
			print "<tr class='head'>" ;
				print "<th>" ;
					print __($guid, "Name") ;
				print "</th>" ;
				print "<th style='width: 50px'>" ;
					print __($guid, "Action") ;
				print "</th>" ;
			print "</tr>" ;
			
			$count=0;
			$rowNum="odd" ;
			foreach($themesSQL AS $themeSQL) {
				if ($themeSQL[1]=="orphaned") {
					$themeName=$themeSQL[0]["name"] ;
					
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
							print __($guid, $themeName) ;
						print "</td>" ;
						print "<td>" ;
							print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/theme_manage_uninstall.php&gibbonThemeID=" . $themesSQL[$themeName][0]["gibbonThemeID"] . "&orphaned=true'><img title='" . __($guid, 'Remove Record') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/></a>" ;
						print "</td>" ;
					print "</tr>" ;
				}
			}
			print "<tr>" ;
				print "<td colspan=7 class='right'>" ;
					?>
					<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
					<input type="submit" value="<?php print __($guid, "Submit") ; ?>">
					<?php
				print "</td>" ;
			print "</tr>" ;
		print "</table>" ;
	}
}
?>