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

$orphaned="" ;
if (isset($_GET["orphaned"])) {
	if ($_GET["orphaned"]=="true") {
		$orphaned="true" ;
	}
}

if (isActionAccessible($guid, $connection2, "/modules/System Admin/theme_manage_uninstall.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/theme_manage.php'>" . _('Manage Themes') . "</a> > </div><div class='trailEnd'>" . _('Uninstall Theme') . "</div>" ;
	print "</div>" ;
	
	if (isset($_GET["deleteReturn"])) { $deleteReturn=$_GET["deleteReturn"] ; } else { $deleteReturn="" ; }
	$deleteReturnMessage="" ;
	$class="error" ;
	if (!($deleteReturn=="")) {
		if ($deleteReturn=="fail0") {
			$deleteReturnMessage=_("Your request failed because you do not have access to this action.") ;	
		}
		else if ($deleteReturn=="fail1") {
			$deleteReturnMessage=_("Your request failed because your inputs were invalid.") ;	
		}
		else if ($deleteReturn=="fail2") {
			$deleteReturnMessage=_("Your request encountered a partial fail: the module may or may not still work.") ;	
		}
		else if ($deleteReturn=="fail3") {
			$deleteReturnMessage=_("Your request failed because theme does not exist or is active.") ;	
		}
		print "<div class='$class'>" ;
			print $deleteReturnMessage;
		print "</div>" ;
	} 
	
	//Check if school year specified
	$gibbonThemeID=$_GET["gibbonThemeID"] ;
	if ($gibbonThemeID=="") {
		print "<div class='error'>" ;
			print _("You have not specified one or more required parameters.") ;
		print "</div>" ;
	}
	else {
		try {
			$data=array("gibbonThemeID"=>$gibbonThemeID); 
			$sql="SELECT * FROM gibbonTheme WHERE gibbonThemeID=:gibbonThemeID AND active='N'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}

		if ($result->rowCount()!=1) {
			print "<div class='error'>" ;
				print ("The specified theme cannot be found or is active and so cannot be removed.") ;
			print "</div>" ;
		}
		else {
			//Let's go!
			$row=$result->fetch() ;
			?>
			<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/theme_manage_uninstallProcess.php?gibbonThemeID=$gibbonThemeID&orphaned=$orphaned" ?>">
				<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
					<tr>
						<td style='width: 275px'> 
							<b><?php print _('Are you sure you want to delete this record?') ; ?></b><br/>
							<span style="font-size: 90%; color: #cc0000"><i><?php print _('This operation cannot be undone, and may lead to loss of vital data in your system. PROCEED WITH CAUTION!') ; ?></i></span>
						</td>
						<td class="right">
							
						</td>
					</tr>
					<tr>
						<td> 
							<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
							<input type="submit" value="<?php print _('Yes') ; ?>">
						</td>
						<td class="right">
							
						</td>
					</tr>
				</table>
			</form>
			<?php
		}
	}
}
?>