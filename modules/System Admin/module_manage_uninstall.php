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

if (isActionAccessible($guid, $connection2, "/modules/System Admin/module_manage_uninstall.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/module_manage.php'>" . _('Manage Modules') . "</a> > </div><div class='trailEnd'>" . _('Uninstall Module') . "</div>" ;
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
			$deleteReturnMessage=_("Your request failed because your inputs were invalid.") ;	
		}
		else if ($deleteReturn=="fail3") {
			$deleteReturnMessage=_("Uninstall encountered a partial fail: the module may or may not still work.") ;	
		}
		print "<div class='$class'>" ;
			print $deleteReturnMessage;
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
				print _("You have not specified one or more required parameters.") ;
			print "</div>" ;
		}
		else {
			//Let's go!
			$row=$result->fetch() ;
			?>
			<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/module_manage_uninstallProcess.php?gibbonModuleID=$gibbonModuleID&orphaned=$orphaned" ?>">
				<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
					<tr>
						<td style='width: 275px' colspan=2> 
							<b><?php print _('Are you sure you want to delete this record?') ; ?></b><br/>
							<span style="font-size: 90%; color: #cc0000"><i><?php print _('This operation cannot be undone, and may lead to loss of vital data in your system. PROCEED WITH CAUTION!') ; ?></i></span>
						</td>
					</tr>
					<tr>
						<td style='width: 275px'> 
							<b><?php print _("Remove Data") ?></b><br/>
							<span style="font-size: 90%"><i><?php print _("Would you like to remove the following tables and views from your database?") ?></i></span>
						</td>
						<td class="right">
							<?php
							if (is_file($_SESSION[$guid]["absolutePath"] . "/modules/" . $row["name"] . "/manifest.php")==FALSE) {
								print "<div class='error'>" ;
									print _("An error has occurred.") ;
								print "</div>" ;
							}
							else {
								$count=0 ;
								include($_SESSION[$guid]["absolutePath"] . "/modules/" . $row["name"] . "/manifest.php") ;
								if (is_array($moduleTables)) {
									foreach ($moduleTables AS $moduleTable) {
										$type=NULL ;
										$tokens=NULL ;
										$name="" ;
										$moduleTable=trim($moduleTable) ;
										if (substr($moduleTable, 0, 12)=="CREATE TABLE") {
											$type=_("Table") ;
										}
										else if (substr($moduleTable, 0, 11)=="CREATE VIEW") {
											$type=_("View") ;
										}
										if ($type!=NULL) {
											$tokens=preg_split('/ +/', $moduleTable);
											if (isset($tokens[2])) {
												$name=str_replace("`", "", $tokens[2]) ;
												if ($name!="") {
													print "<b>" . $type . "</b>: " . $name ;
													print " <input checked type='checkbox' name='remove[]' value='" . $type . "-" . $name . "' /><br/>" ;
													$count++ ;
												}
											}
										}
									} 
								}
								if ($count==0) {
									print _("There are no records to display.") ;
								}
							}
							?>
						</td>
					</tr>
					<tr>
						<td class="right" colspan=2>
							<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
							<input type="submit" value="<?php print _('Submit') ; ?>">
						</td>
					</tr>
				</table>
			</form>
			<?php
		}
	}
}
?>