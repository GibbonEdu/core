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

@session_start() ;

if (isActionAccessible($guid, $connection2, "/modules/System Admin/module_manage_install.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/module_manage.php'>Manage Modules</a> > </div><div class='trailEnd'>Install Module</div>" ;
	print "</div>" ;
	
	if (isset($_GET["addReturn"])) { $addReturn=$_GET["addReturn"] ; } else { $addReturn="" ; }
	$addReturnMessage ="" ;
	$class="error" ;
	if (!($addReturn=="")) {
		if ($addReturn=="fail0") {
			$addReturnMessage ="Install failed because you do not have access to this action." ;	
		}
		else if ($addReturn=="fail2") {
			$addReturnMessage ="Install failed due to a database error." ;	
		}
		else if ($addReturn=="fail3") {
			$addReturnMessage ="Install failed because either the manifest file was invalid or the uploads folder is not accessible to _www" ;	
		}
		else if ($addReturn=="fail4") {
			$addReturnMessage ="Install failed because a module with the same name is already installed." ;	
		}
		else if ($addReturn=="fail5") {
			$addReturnMessage ="Install failed, but module was added to the system and set non-active." ;	
		}
		else if ($addReturn=="fail6") {
			$addReturnMessage ="Install was successful, but module could not be activated." ;
		}
		else if ($addReturn=="success0") {
			$addReturnMessage ="Install was successful. You can Install another module if you wish." ;	
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $addReturnMessage;
			if (isset($_SESSION[$guid]["moduleInstallError"])) {
				if ($_SESSION[$guid]["moduleInstallError"]!="") {
					print "The following SQL statements caused errors: " . $_SESSION[$guid]["moduleInstallError"] ;
				}
				$_SESSION[$guid]["moduleInstallError"]=NULL ;
			}
		print "</div>" ;
	} 
	
	//Check for uploads folder and see if it is writable.
	$file = "uploads";
	if(!is_dir($file)) {
  		$class="error" ;
	   print "<div class='$class'>" ;
	   print "Your uploads folder doesn't exist so you cannot install modules.";
	   print "</div>";
  	}
	if(!is_writable("uploads")) {
		$class="error" ;
		print "<div class='$class'>" ;
		print "Your uploads folder in the root of ". $_SESSION[$guid]["systemName"]. " is not writable. You cannot install modules until this is rectified.";
		print "</div>";
	}
	
	?>
	<form method="post" action="<? print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/module_manage_installProcess.php?return=" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=" . $_GET["q"] . "&address=" . $_SESSION[$guid]["address"] ?>" enctype="multipart/form-data">
		<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
			<tr>
				<td> 
					<b>Module Manifest *</b><br/>
					<span style="font-size: 90%"><i>1. Unzip module in server /modules.<br/>2. Make local copy of manifest.php.<br/>3. Open manifest.php and check the module name ($name).<br/>4. Rename the folder you unziped to the server to match $name.<br/>5. Make sure you have a folder named uploads in the root of <? print $_SESSION[$guid]["systemName"] ?><br/>6. Check that uploads is writable by _www.<br/>7. Select manifest.php.</i></span>
				</td>
				<td class="right">
					<input type="file" name="file" id="file"><br/><br/>
					<?
					print getMaxUpload() ;
					?>
					
					<script type="text/javascript">
						var file=new LiveValidation('file');
						file.add(Validate.Presence);
					</script>
				</td>
			</tr>
			<tr>
				<td>
					<span style="font-size: 90%"><i>* <? print _("denotes a required field") ; ?></i></span>
				</td>
				<td class="right">
					<input type="submit" value="<? print _("Submit") ; ?>">
				</td>
			</tr>
		</table>
	</form>
	<?
}
?>