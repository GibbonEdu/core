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

if (isActionAccessible($guid, $connection2, "/modules/System Admin/update.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . _('Update') . "</div>" ;
	print "</div>" ;
	
	if (isset($_GET["updateReturn"])) { $updateReturn=$_GET["updateReturn"] ; } else { $updateReturn="" ; }
	$updateReturnMessage="" ;
	$class="error" ;
	if (!($updateReturn=="")) {
		if ($updateReturn=="fail0") {
			$updateReturnMessage=_("Your request failed because you do not have access to this action.") ;	
		}
		else if ($updateReturn=="fail1") {
			$updateReturnMessage=_("Your request failed because your inputs were invalid.") ;	
		}
		else if ($updateReturn=="fail2") {
			$updateReturnMessage=_("One or more of the fields in your request failed due to a database error.") ;	
		}
		else if ($updateReturn=="fail3") {
			$updateReturnMessage=_("Your request failed because your inputs were invalid.") ;	
		}
		else if ($updateReturn=="fail5") {
			$updateReturnMessage=_("Some aspects of your request failed, but others were successful. The elements that failed are shown below:") ;	
		}
		else if ($updateReturn=="success0") {
			$updateReturnMessage=_("Your request was completed successfully.") ;	
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $updateReturnMessage ;
			if (isset($_SESSION[$guid]["systemUpdateError"])) {
				if ($_SESSION[$guid]["systemUpdateError"]!="") {
					print "<br/><br/>" ;
					print _("The following SQL statements caused errors:") . " " . $_SESSION[$guid]["systemUpdateError"] ;
				}
				$_SESSION[$guid]["systemUpdateError"]=NULL ;
			}
		print "</div>" ;
	} 
	
	getSystemSettings($guid, $connection2) ;
	
	$versionDB=getSettingByScope( $connection2, "System", "version" ) ;
	$versionCode=$version ;
	
	print "<p>" ;
		print _("This page allows you to semi-automatically update your Gibbon installation to a new version. You need to take care of the file updates, and based on the new files, Gibbon will do the database upgrades.") ;
	print "</p>" ;
	
	$cuttingEdgeCode=getSettingByScope( $connection2, "System", "cuttingEdgeCode" ) ;
	if ($cuttingEdgeCode!="Y") {
		//Check for new version of Gibbon
		print getCurrentVersion($guid, $connection2, $version) ;
	
		if ($updateReturn=="success0") {
			print "<p>" ;
				print "<b>" . _('You seem to be all up to date, good work buddy!') . "</b>" ;
			print "</p>" ;
		}
		else if ((float)$versionDB==(float)$versionCode) {
			//Instructions on how to update
			print "<h3>" ;
				print _("Update Instructions") ;
			print "</h3>" ;
			print "<ol>" ;
				print "<li>" . sprintf(_('You are currently using Gibbon v%1$s.'), $versionCode) . "</i></li>" ;
				print "<li>" . sprintf(_('Check %1$s for a newer version of Gibbon.'), "<a target='_blank' href='https://gibbonedu.org/download'>the Gibbon download page</a>") . "</li>" ;
				print "<li>" . _('Download the latest version, and unzip it on your computer.') . "</li>" ;
				print "<li>" . _('Use an FTP client to upload the new files to your server, making sure not to overwrite any additional modules and themes previously added to the system.') . "</li>" ;
				print "<li>" . _('Reload this page and follow the instructions to update your database to the latest version.') . "</li>" ;
			print "</ol>" ;
		}
		else if ((float)$versionDB>(float)$versionCode) {
			//Error
			print "<div class='error'>" ;
				print _("An error has occurred determining the version of the system you are using.") ;
			print "</div>" ;
		}
		else if ((float)$versionDB<(float)$versionCode) {
			//Time to update
			print "<h3>" ;
				print _("Datebase Update") ;
			print "</h3>" ;
			print "<p>" ;
				print sprintf(_('It seems that you have updated your Gibbon code to a new version, and are ready to update your databse from v%1$s to v%2$s. <b>Click "Submit" below to continue. This operation cannot be undone: backup your entire database prior to running the update!'), $versionDB, $versionCode) . "</b>" ;
			print "</p>" ;
			?>
			<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/updateProcess.php?type=regularRelease" ?>">
				<table cellspacing='0' style="width: 100%">	
					<tr>
						<td class="right"> 
							<input type="hidden" name="versionDB" value="<?php print $versionDB ?>">
							<input type="hidden" name="versionCode" value="<?php print $versionCode ?>">
							<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
							<input type="submit" value="<?php print _("Submit") ; ?>">
						</td>
					</tr>
				</table>
			</form>
			<?php
		}
	}
	else {
		$cuttingEdgeCodeLine=getSettingByScope( $connection2, "System", "cuttingEdgeCodeLine" ) ;
		if ($cuttingEdgeCodeLine=="" OR is_null($cuttingEdgeCodeLine)) {
			$cuttingEdgeCodeLine=0 ;
		}
		
		//Check to see if there are any updates
		include "./CHANGEDB.php" ;
		$versionMax=$sql[(count($sql))][0] ;
		$sqlTokens=explode(";end", $sql[(count($sql))][1]) ;
		$versionMaxLinesMax=(count($sqlTokens)-1) ;	
		$update=FALSE ;
		if ((float)$versionMax>(float)$versionDB) {
			$update=TRUE ;
		}
		else {
			if ($versionMaxLinesMax>$cuttingEdgeCodeLine) {
				$update=TRUE ;
			}
		}
		
		//Go! Start with warning about cutting edge code
		print "<div class='warning'>" ;
			print _('Your system is set up to run Cutting Edge code, which may or may not be as reliable as regular release code. Backup before installing, and avoid using cutting edge in production.') ;
		print "</div>" ;
		
		if ($updateReturn=="success0") {
			print "<p>" ;
				print "<b>" . _('You seem to be all up to date, good work buddy!') . "</b>" ;
			print "</p>" ;
		}
		else if ($update==FALSE) {
			//Instructions on how to update
			print "<h3>" ;
				print _("Update Instructions") ;
			print "</h3>" ;
			print "<ol>" ;
				print "<li>" . sprintf(_('You are currently using Cutting Edge Gibbon v%1$s'), $versionCode) . "</i></li>" ;
				print "<li>" . sprintf(_('Check %1$s to get the latest commits.'), "<a target='_blank' href='https://github.com/GibbonEdu/core'>our GitHub repo</a>") . "</li>" ;
				print "<li>" . _('Download the latest commits, and unzip it on your computer.') . "</li>" ;
				print "<li>" . _('Use an FTP client to upload the new files to your server, making sure not to overwrite any additional modules and themes previously added to the system.') . "</li>" ;
				print "<li>" . _('Reload this page and follow the instructions to update your database to the latest version.') . "</li>" ;
			print "</ol>" ;
		}
		else if ($update==TRUE) {
			//Time to update
			print "<h3>" ;
				print _("Datebase Update") ;
			print "</h3>" ;
			print "<p>" ;
				print sprintf(_('It seems that you have updated your Gibbon code to a new version, and are ready to update your databse from v%1$s line %2$s to v%3$s line %4$s. <b>Click "Submit" below to continue. This operation cannot be undone: backup your entire database prior to running the update!'), $versionDB, $cuttingEdgeCodeLine, $versionCode, $versionMaxLinesMax) . "</b>" ;
			print "</p>" ;
			?>
			<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/updateProcess.php?type=cuttingEdge" ?>">
				<table cellspacing='0' style="width: 100%">	
					<tr>
						<td class="right"> 
							<input type="hidden" name="versionDB" value="<?php print $versionDB ?>">
							<input type="hidden" name="versionCode" value="<?php print $versionCode ?>">
							<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
							<input type="submit" value="<?php print _("Submit") ; ?>">
						</td>
					</tr>
				</table>
			</form>
			<?php
		}
	}
}
?>