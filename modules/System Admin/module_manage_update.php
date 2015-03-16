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

if (isActionAccessible($guid, $connection2, "/modules/System Admin/module_manage_update.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/module_manage.php'>" . _('Manage Modules') . "</a> > </div><div class='trailEnd'>" . _('Update Module') . "</div>" ;
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
			$updateReturnMessage=_("Your request failed due to a database error.") ;	
		}
		else if ($updateReturn=="fail3") {
			$updateReturnMessage=_("Your request failed because your inputs were invalid.") ;	
		}
		else if ($updateReturn=="fail5") {
			$updateReturnMessage=_("Your request failed.") ;	
		}
		else if ($updateReturn=="success0") {
			$updateReturnMessage=_("Your request was completed successfully.") ;	
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $updateReturnMessage ;
			if (isset($_SESSION[$guid]["moduleUpdateError"])) {
				if ($_SESSION[$guid]["moduleUpdateError"]!="") {
					print "<br/><br/>" ;
					print _("The following SQL statements caused errors:") . " " . $_SESSION[$guid]["moduleUpdateError"] ;
				}
				$_SESSION[$guid]["moduleUpdateError"]=NULL ;
			}
		print "</div>" ;
	} 
	
	//Check if school year specified
	$gibbonModuleID=$_GET["gibbonModuleID"] ;
	if ($gibbonModuleID=="") {
		print "<div class='error'>" ;
			print _("You have not specified one or more required parameters.") ;
		print "</div>" ;
	}
	else {
		try {
			$data=array("gibbonModuleID"=>$gibbonModuleID); 
			$sql="SELECT * FROM gibbonModule WHERE gibbonModuleID=:gibbonModuleID" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
		
		if ($result->rowCount()!=1) {
			print "<div class='error'>" ;
				print _("The specified record cannot be found.") ;
			print "</div>" ;
		}
		else {
			//Let's go!
			$row=$result->fetch() ;
			
			$versionDB=$row["version"] ;
			if (file_exists($_SESSION[$guid]["absolutePath"] . "/modules/" . $row["name"] . "/version.php")) {
				include $_SESSION[$guid]["absolutePath"] . "/modules/" . $row["name"] . "/version.php" ;
			}	
			@$versionCode=$moduleVersion ;
			
			print "<p>" ;
				print sprintf(_('This page allows you to semi-automatically update the %1$s module to a new version. You need to take care of the file updates, and based on the new files, Gibbon will do the database upgrades.'), htmlPrep($row["name"])) ;
			print "</p>" ;
			
			if ($updateReturn=="success0") {
				print "<p>" ;
					print "<b>" . _('You seem to be all up to date, good work!') . "</b>" ;
				print "</p>" ;
			}
			else if ($versionDB>$versionCode OR $versionCode=="") {
				//Error
				print "<div class='error'>" ;
					print _("An error has occurred determining the version of the system you are using.") ;
				print "</div>" ;
			}
			else if ($versionDB==$versionCode) {
				//Instructions on how to update
				print "<h3>" ;
					print _("Update Instructions") ;
				print "</h3>" ;
				print "<ol>" ;
					print "<li>" . sprintf(_('You are currently using %1$s v%2$s.'),  htmlPrep($row["name"]), $versionCode) . "</i></li>" ;
					print "<li>" . sprintf(_('Check %1$s for a newer version of this module.'), "<a target='_blank' href='http://www.gibbonedu.org'>gibbonedu.org</a>") . "</li>" ;
					print "<li>" . _('Download the latest version, and unzip it on your computer.') . "</li>" ;
					print "<li>" . _('Use an FTP client to upload the new files to your server\'s modules folder.') . "</li>" ;
					print "<li>" . _('Reload this page and follow the instructions to update your database to the latest version.') . "</li>" ;
				print "</ol>" ;
			}
			else if ($versionDB<$versionCode) {
				//Time to update
				print "<h3>" ;
					print _("Datebase Update") ;
				print "</h3>" ;
				print "<p>" ;
					print sprintf(_('It seems that you have updated your %1$s module code to a new version, and are ready to update your databse from v%2$s to v%3$s. <b>Click "Submit" below to continue. This operation cannot be undone: backup your entire database prior to running the update!'), htmlPrep($row["name"]), $versionDB, $versionCode) . "</b>" ;
				print "</p>" ;
				?>
				<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/module_manage_updateProcess.php?&gibbonModuleID=$gibbonModuleID" ?>">
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
}
?>