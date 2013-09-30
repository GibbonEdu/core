<?
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

session_start() ;

if (isActionAccessible($guid, $connection2, "/modules/System Admin/module_manage_update.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "You do not have access to this action." ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>Home</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/module_manage.php'>Manage Modules</a> > </div><div class='trailEnd'>Update Module</div>" ;
	print "</div>" ;
	
	$updateReturn = $_GET["updateReturn"] ;
	$updateReturnMessage ="" ;
	$class="error" ;
	if (!($updateReturn=="")) {
		if ($updateReturn=="fail0") {
			$updateReturnMessage ="Update failed because you do not have access to this action." ;	
		}
		else if ($updateReturn=="fail1") {
			$updateReturnMessage ="Update failed because a required parameter was not set." ;	
		}
		else if ($updateReturn=="fail2") {
			$updateReturnMessage ="Update of one or more fields failed due to a database error." ;	
		}
		else if ($updateReturn=="fail3") {
			$updateReturnMessage ="Update failed because your inputs were invalid." ;	
		}
		else if ($updateReturn=="fail5") {
			$updateReturnMessage ="The update failed." ;	
		}
		else if ($updateReturn=="success0") {
			$updateReturnMessage ="Update was successful." ;	
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $updateReturnMessage;
		print "</div>" ;
	} 
	
	//Check if school year specified
	$gibbonModuleID=$_GET["gibbonModuleID"] ;
	if ($gibbonModuleID=="") {
		print "<div class='error'>" ;
			print "You have not specified a module." ;
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
				print "The specified module cannot be found." ;
			print "</div>" ;
		}
		else {
			//Let's go!
			$row=$result->fetch() ;
			
			$versionDB=$row["version"] ;
			if (file_exists($_SESSION[$guid]["absolutePath"] . "/modules/" . $row["name"] . "/version.php")) {
				include $_SESSION[$guid]["absolutePath"] . "/modules/" . $row["name"] . "/version.php" ;
			}	
			$versionCode=$moduleVersion ;
			
			print "<p>" ;
				print "This page allows you to semi-automatically update the " . htmlPrep($row["name"]) . " module to a new version. You need to take care of the file updates, and based on the new files, Gibbon will do the database upgrades." ;
			print "</p>" ;
			
			if ($updateReturn=="success0") {
				print "<p>" ;
					print "<b>You seem to be all up to date, good work!</b>" ;
				print "</p>" ;
			}
			else if ($versionDB>$versionCode OR $versionCode=="") {
				//Error
				print "<div class='error'>" ;
					print "An error has occurred determining the version of the system you are using." ;
				print "</div>" ;
			}
			else if ($versionDB==$versionCode) {
				//Instructions on how to update
				print "<h3>" ;
					print "Update Instructions" ;
				print "</h3>" ;
				print "<ol>" ;
					print "<li>You are currently using " . htmlPrep($row["name"]) . " v$versionCode.</i></li>" ;
					print "<li>Check <a target='_blank' href='http://www.gibbonedu.org'>gibbonedu.org</a> for a newer version of this module.</li>" ;
					print "<li>Download the latest version, and unzip it on your computer.</li>" ;
					print "<li>Use an FTP client to upload the new files to your server\'s modules folder</li>" ;
					print "<li>Reload this page and follow the instructions to update your database to the latest version.</li>" ;
				print "</ol>" ;
			}
			else if ($versionDB<$versionCode) {
				//Time to update
				print "<h3>" ;
					print "Datebase Update" ;
				print "</h3>" ;
				print "<p>" ;
					print "It seems that you have updated your " . htmlPrep($row["name"]) . " module code to a new version, and are ready to update your databse from v$versionDB to v$versionCode. <b>Click \"Submit\" below to continue. This operation cannot be undone: backup your entire database prior to running the update!</b>" ;
				print "</p>" ;
				?>
				<form method="post" action="<? print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/module_manage_updateProcess.php?&gibbonModuleID=$gibbonModuleID" ?>">
					<table cellspacing='0' style="width: 100%">	
						<tr>
							<td class="right"> 
								<input type="hidden" name="versionDB" value="<? print $versionDB ?>">
								<input type="hidden" name="versionCode" value="<? print $versionCode ?>">
								<input type="hidden" name="address" value="<? print $_SESSION[$guid]["address"] ?>">
								<input type="submit" value="Submit">
							</td>
						</tr>
					</table>
				</form>
				<?
			}
		}
	}
}
?>