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

if (isActionAccessible($guid, $connection2, "/modules/School Admin/messengerSettings.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __($guid, 'Manage SMS Settings') . "</div>" ;
	print "</div>" ;
	
	if (isset($_GET["return"])) { returnProcess($guid, $_GET["return"], null, null); }
	?>
	
	<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/messengerSettingsProcess.php" ?>">
		<table class='smallIntBorder fullWidth' cellspacing='0'>	
			<tr class='break'>
				<td colspan=2> 
					<h3><?php print __($guid, 'SMS Settings') ?></h3>
				</td>
			</tr>
			<tr>
				<td colspan=2> 
					<div class='warning'>
						<?php print sprintf(__($guid, 'Gibbon is designed to use the %1$sOne Way SMS%2$s gateway to send out SMS messages. This is a paid service, not affiliated with Gibbon, and you must create your own account with them before being able to send out SMSs using the Messenger module. It is possible that completing the fields below with details from other gateways may work.'), "<a href='http://onewaysms.com' target='_blank'>", "</a>") ?>
					</div>
				</td>
			</tr>
			<tr>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='Messenger' AND name='smsUsername'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { }
				$row=$result->fetch() ;
				?>
				<td style='width: 275px'> 
					<b><?php print __($guid, $row["nameDisplay"]) ?> *</b><br/>
					<span class="emphasis small"><?php if ($row["description"]!="") { print __($guid, $row["description"]) ; } ?></span>
				</td>
				<td class="right">
					<input name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" maxlength=50 value="<?php print htmlPrep($row["value"]) ?>" type="text" class="standardWidth">
				</td>
			</tr>
			
			<tr>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='Messenger' AND name='smsPassword'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { }
				$row=$result->fetch() ;
				?>
				<td> 
					<b><?php print __($guid, $row["nameDisplay"]) ?> *</b><br/>
					<span class="emphasis small"><?php if ($row["description"]!="") { print __($guid, $row["description"]) ; } ?></span>
				</td>
				<td class="right">
					<input name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" maxlength=50 value="<?php print htmlPrep($row["value"]) ?>" type="password" class="standardWidth">
				</td>
			</tr>
			
			<tr>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='Messenger' AND name='smsURL'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { }
				$row=$result->fetch() ;
				?>
				<td> 
					<b><?php print __($guid, $row["nameDisplay"]) ?> *</b><br/>
					<span class="emphasis small"><?php if ($row["description"]!="") { print __($guid, $row["description"]) ; } ?></span>
				</td>
				<td class="right">
					<input name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" maxlength=255 value="<?php print htmlPrep($row["value"]) ?>" type="text" class="standardWidth">
				</td>
			</tr>
			
			<tr>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='Messenger' AND name='smsURLCredit'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { }
				$row=$result->fetch() ;
				?>
				<td> 
					<b><?php print __($guid, $row["nameDisplay"]) ?> *</b><br/>
					<span class="emphasis small"><?php if ($row["description"]!="") { print __($guid, $row["description"]) ; } ?></span>
				</td>
				<td class="right">
					<input name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" maxlength=255 value="<?php print htmlPrep($row["value"]) ?>" type="text" class="standardWidth">
				</td>
			</tr>
			
			<tr class='break'>
				<td colspan=2> 
					<h3><?php print __($guid, 'Message Wall Settings') ?></h3>
				</td>
			</tr>
			<tr>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='Messenger' AND name='messageBubbleWidthType'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { }
				$row=$result->fetch() ;
				?>
				<td> 
					<b><?php print __($guid, $row["nameDisplay"]) ?> *</b><br/>
					<span class="emphasis small"><?php if ($row["description"]!="") { print __($guid, $row["description"]) ; } ?></span>
				</td>
				<td class="right">
					<select name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" class="standardWidth">
						<option <?php if ($row["value"]=="Regular") {print "selected ";} ?>value="Regular"><?php print __($guid, 'Regular') ?></option>
						<option <?php if ($row["value"]=="Wide") {print "selected ";} ?>value="Wide"><?php print __($guid, 'Wide') ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='Messenger' AND name='messageBubbleBGColor'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { }
				$row=$result->fetch() ;
				?>
				<td> 
					<b><?php print __($guid, $row["nameDisplay"]) ?> *</b><br/>
					<span class="emphasis small"><?php if ($row["description"]!="") { print __($guid, $row["description"]) ; } ?></span>
				</td>
				<td class="right">
					<input name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" maxlength=16 value="<?php print htmlPrep($row["value"]) ?>" type="text" class="standardWidth">
				</td>
			</tr>
			<tr>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='Messenger' AND name='messageBubbleAutoHide'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { }
				$row=$result->fetch() ;
				?>
				<td> 
					<b><?php print __($guid, $row["nameDisplay"]) ?> *</b><br/>
					<span class="emphasis small"><?php if ($row["description"]!="") { print __($guid, $row["description"]) ; } ?></span>
				</td>
				<td class="right">
					<select name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" class="standardWidth">
						<option <?php if ($row["value"]=="Y") {print "selected ";} ?>value="Y"><?php print __($guid, 'Yes') ?></option>
						<option <?php if ($row["value"]=="N") {print "selected ";} ?>value="N"><?php print __($guid, 'No') ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='Messenger' AND name='enableHomeScreenWidget'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { }
				$row=$result->fetch() ;
				?>
				<td> 
					<b><?php print __($guid, $row["nameDisplay"]) ?> *</b><br/>
					<span class="emphasis small"><?php if ($row["description"]!="") { print __($guid, $row["description"]) ; } ?></span>
				</td>
				<td class="right">
					<select name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" class="standardWidth">
						<option <?php if ($row["value"]=="Y") {print "selected ";} ?>value="Y"><?php print __($guid, 'Yes') ?></option>
						<option <?php if ($row["value"]=="N") {print "selected ";} ?>value="N"><?php print __($guid, 'No') ?></option>
					</select>
				</td>
			</tr>


			
			
			<tr>
				<td>
					<span class="emphasis small">* <?php print __($guid, "denotes a required field") ; ?></span>
				</td>
				<td class="right">
					<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
					<input type="submit" value="<?php print __($guid, "Submit") ; ?>">
				</td>
			</tr>
		</table>
	</form>
<?php
}
?>