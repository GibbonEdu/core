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

if (isActionAccessible($guid, $connection2, "/modules/School Admin/markbookSettings.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . _('Markbook Settings') . "</div>" ;
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
	
	<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/markbookSettingsProcess.php" ?>">
		<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
			<tr class='break'>
				<td colspan=2> 
					<h3><?php print _('Interface Settings') ?></h3>
				</td>
			</tr>
			<tr>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='Markbook' AND name='markbookType'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { }
				$row=$result->fetch() ;
				?>
				<td style='width: 275px'> 
					<b><?php print _($row["nameDisplay"]) ?> *</b><br/>
					<span style="font-size: 90%"><i><?php if ($row["description"]!="") { print _($row["description"]) ; } ?></i></span>
				</td>
				<td class="right">
					<textarea name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" type="text" style="width: 300px" rows=4><?php if (isset($row["value"])) { print $row["value"] ; } ?></textarea>
					<script type="text/javascript">
						var <?php print $row["name"] ?>=new LiveValidation('<?php print $row["name"] ?>');
						<?php print $row["name"] ?>.add(Validate.Presence);
					</script> 
				</td>
			</tr>
			<tr>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='Markbook' AND name='attainmentAlternativeName'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { }
				$row=$result->fetch() ;
				?>
				<td> 
					<b><?php print _($row["nameDisplay"]) ?></b><br/>
					<span style="font-size: 90%"><i><?php if ($row["description"]!="") { print _($row["description"]) ; } ?></i></span>
				</td>
				<td class="right">
					<input type='text' maxlength='25' name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>"style="width: 300px" value='<?php print htmlPrep($row["value"]) ?>'>	
				</td>
			</tr>
			<tr>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='Markbook' AND name='attainmentAlternativeNameAbrev'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { }
				$row=$result->fetch() ;
				?>
				<td> 
					<b><?php print _($row["nameDisplay"]) ?></b><br/>
					<span style="font-size: 90%"><i><?php if ($row["description"]!="") { print _($row["description"]) ; } ?></i></span>
				</td>
				<td class="right">
					<input type='text' maxlength='3' name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>"style="width: 300px" value='<?php print htmlPrep($row["value"]) ?>'>	
				</td>
			</tr>
			<tr>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='Markbook' AND name='effortAlternativeName'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { }
				$row=$result->fetch() ;
				?>
				<td> 
					<b><?php print _($row["nameDisplay"]) ?></b><br/>
					<span style="font-size: 90%"><i><?php if ($row["description"]!="") { print _($row["description"]) ; } ?></i></span>
				</td>
				<td class="right">
					<input type='text' maxlength='25' name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>"style="width: 300px" value='<?php print htmlPrep($row["value"]) ?>'>	
				</td>
			</tr>
			<tr>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='Markbook' AND name='effortAlternativeNameAbrev'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { }
				$row=$result->fetch() ;
				?>
				<td> 
					<b><?php print _($row["nameDisplay"]) ?></b><br/>
					<span style="font-size: 90%"><i><?php if ($row["description"]!="") { print _($row["description"]) ; } ?></i></span>
				</td>
				<td class="right">
					<input type='text' maxlength='3' name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>"style="width: 300px" value='<?php print htmlPrep($row["value"]) ?>'>	
				</td>
			</tr>
			<tr class='break'>
				<td colspan=2> 
					<h3><?php print _('Warnings') ?></h3>
				</td>
			</tr>
			<tr>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='Markbook' AND name='showStudentAttainmentWarning'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				$row=$result->fetch() ;
				?>
				<td> 
					<b><?php print _($row["nameDisplay"]) ?> *</b><br/>
					<span style="font-size: 90%"><i><?php if ($row["description"]!="") { print _($row["description"]) ; } ?></i></span>
				</td>
				<td class="right">
					<select name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" style="width: 302px">
						<option <?php if ($row["value"]=="Y") {print "selected ";} ?>value="Y"><?php print _('Yes') ?></option>
						<option <?php if ($row["value"]=="N") {print "selected ";} ?>value="N"><?php print _('No') ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='Markbook' AND name='showStudentEffortWarning'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				$row=$result->fetch() ;
				?>
				<td> 
					<b><?php print _($row["nameDisplay"]) ?> *</b><br/>
					<span style="font-size: 90%"><i><?php if ($row["description"]!="") { print _($row["description"]) ; } ?></i></span>
				</td>
				<td class="right">
					<select name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" style="width: 302px">
						<option <?php if ($row["value"]=="Y") {print "selected ";} ?>value="Y"><?php print _('Yes') ?></option>
						<option <?php if ($row["value"]=="N") {print "selected ";} ?>value="N"><?php print _('No') ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='Markbook' AND name='showParentAttainmentWarning'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				$row=$result->fetch() ;
				?>
				<td> 
					<b><?php print _($row["nameDisplay"]) ?> *</b><br/>
					<span style="font-size: 90%"><i><?php if ($row["description"]!="") { print _($row["description"]) ; } ?></i></span>
				</td>
				<td class="right">
					<select name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" style="width: 302px">
						<option <?php if ($row["value"]=="Y") {print "selected ";} ?>value="Y"><?php print _('Yes') ?></option>
						<option <?php if ($row["value"]=="N") {print "selected ";} ?>value="N"><?php print _('No') ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='Markbook' AND name='showParentEffortWarning'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				$row=$result->fetch() ;
				?>
				<td> 
					<b><?php print _($row["nameDisplay"]) ?> *</b><br/>
					<span style="font-size: 90%"><i><?php if ($row["description"]!="") { print _($row["description"]) ; } ?></i></span>
				</td>
				<td class="right">
					<select name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" style="width: 302px">
						<option <?php if ($row["value"]=="Y") {print "selected ";} ?>value="Y"><?php print _('Yes') ?></option>
						<option <?php if ($row["value"]=="N") {print "selected ";} ?>value="N"><?php print _('No') ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='Markbook' AND name='personalisedWarnings'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				$row=$result->fetch() ;
				?>
				<td> 
					<b><?php print _($row["nameDisplay"]) ?> *</b><br/>
					<span style="font-size: 90%"><i><?php if ($row["description"]!="") { print _($row["description"]) ; } ?></i></span>
				</td>
				<td class="right">
					<select name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" style="width: 302px">
						<option <?php if ($row["value"]=="Y") {print "selected ";} ?>value="Y"><?php print _('Yes') ?></option>
						<option <?php if ($row["value"]=="N") {print "selected ";} ?>value="N"><?php print _('No') ?></option>
					</select>
				</td>
			</tr>
			
			<tr class='break'>
				<td colspan=2> 
					<h3><?php print _('Miscellaneous') ?></h3>
				</td>
			</tr>
			<tr>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='Markbook' AND name='wordpressCommentPush'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				$row=$result->fetch() ;
				?>
				<td> 
					<b><?php print _($row["nameDisplay"]) ?> *</b><br/>
					<span style="font-size: 90%"><i><?php if ($row["description"]!="") { print _($row["description"]) ; } ?></i></span>
				</td>
				<td class="right">
					<select name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" style="width: 302px">
						<option <?php if ($row["value"]=="On") {print "selected ";} ?>value="On"><?php print _('On') ?></option>
						<option <?php if ($row["value"]=="Off") {print "selected ";} ?>value="Off"><?php print _('Off') ?></option>
					</select>
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