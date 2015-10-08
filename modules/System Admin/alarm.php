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

if (isActionAccessible($guid, $connection2, "/modules/System Admin/alarm.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . _('Sound Alarm') . "</div>" ;
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
		else if ($updateReturn=="success0") {
			$updateReturnMessage=_("Your request was completed successfully.") ;	
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $updateReturnMessage;
		print "</div>" ;
	} 
	?>
	
	<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/alarmProcess.php" ?>" enctype="multipart/form-data">
		<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
			<tr>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='System Admin' AND name='customAlarmSound'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { }
				$row=$result->fetch() ;
				?>
				<td> 
					<b><?php print _($row["nameDisplay"]) ?> *</b><br/>
					<span style="font-size: 90%"><i><?php if ($row["description"]!="") { print _($row["description"]) ; } ?></i></span><br/>
					<?php if ($row["value"]!="") { ?>
						<span style="font-size: 90%"><i><?php print _('Will overwrite existing attachment.') ?></i></span>
					<?php } ?>
				</td>
				<td class="right">
					<?php
					if ($row["value"]!="") {
						print _("Current attachment:") . " <a href='" . $_SESSION[$guid]["absoluteURL"] . "/" . $row["value"] . "'>" . $row["value"] . "</a><br/><br/>" ;
					}
					?>
					<input type="file" name="file" id="file"><br/><br/>
					<?php
					//Get list of acceptable file extensions
					try {
						$dataExt=array(); 
						$sqlExt="SELECT * FROM gibbonFileExtension WHERE type='Audio'" ;
						$resultExt=$connection2->prepare($sqlExt);
						$resultExt->execute($dataExt);
					}
					catch(PDOException $e) { }
					$ext="" ;
					while ($rowExt=$resultExt->fetch()) {
						$ext=$ext . "'." . $rowExt["extension"] . "'," ;
					}
					?>
			
					<script type="text/javascript">
						var file=new LiveValidation('file');
						file.add( Validate.Inclusion, { within: [<?php print $ext ;?>], failureMessage: "Illegal file type!", partialMatch: true, caseSensitive: false } );
					</script>
					<input type="hidden" name="attachmentCurrent" value="<?php print $row["value"] ?>">
				</td>
			</tr>
			<tr>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='System' AND name='alarm'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { }
				$row=$result->fetch() ;
				?>
				<td> 
					<b><?php print _($row["nameDisplay"]) ?> *</b><br/>
					<span style="font-size: 90%"><i><?php if ($row["description"]!="") { print _($row["description"]) ; } ?></i></span>
				</td>
				<td class="right">
					<select name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" style="width: 302px">
						<option <?php if ($row["value"]=="None") {print "selected ";} ?>value="None"><?php print _('None') ?></option>
						<option <?php if ($row["value"]=="General") {print "selected ";} ?>value="General"><?php print _('General') ?></option>
						<option <?php if ($row["value"]=="Lockdown") {print "selected ";} ?>value="Lockdown"><?php print _('Lockdown') ?></option>
						<?php
						if ($row["value"]!="") {
							?>
							<option <?php if ($row["value"]=="Custom") {print "selected ";} ?>value="Custom"><?php print _('Custom') ?></option>
							<?php
						}
						?>
					</select>
					<input type="hidden" name="alarmCurrent" value="<?php print $row["value"] ?>">
				</td>
			</tr>
            <tr>
				<td>
					<span style="font-size: 90%"><i>* <?php print _("denotes a required field") ; ?></i></span>
				</td>
				<td class="right">
					<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
					<input type="submit" value="<?php print _("Submit") ; ?>">
				</td>
			</tr>
		</table>
	</form>
<?php
}
?>