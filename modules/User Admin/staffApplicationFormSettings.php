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

if (isActionAccessible($guid, $connection2, "/modules/User Admin/staffApplicationFormSettings.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __($guid, 'Staff Application Form Settings') . "</div>" ;
	print "</div>" ;
	
	if (isset($_GET["return"])) { returnProcess($guid, $_GET["return"], null, null); }
	?>
	
	<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/staffApplicationFormSettingsProcess.php" ?>">
		<table class='smallIntBorder fullWidth' cellspacing='0'>	
			<tr class='break'>
				<td colspan=2> 
					<h3><?php print __($guid, 'General Options') ?></h3>
				</td>
			</tr>
			<tr>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='Staff' AND name='staffApplicationFormIntroduction'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				$row=$result->fetch() ;
				?>
				<td style='width: 275px'> 
					<b><?php print __($guid, $row["nameDisplay"]) ?></b><br/>
					<span class="emphasis small"><?php if ($row["description"]!="") { print __($guid, $row["description"]) ; } ?></span>
				</td>
				<td class="right">
					<textarea name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" rows=12 class="standardWidth"><?php print $row["value"] ?></textarea>
				</td>
			</tr>
			<tr>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='Staff' AND name='staffApplicationFormQuestions'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				$row=$result->fetch() ;
				?>
				<td style='width: 275px'> 
					<b><?php print __($guid, $row["nameDisplay"]) ?></b><br/>
					<span class="emphasis small"><?php if ($row["description"]!="") { print __($guid, $row["description"]) ; } ?></span>
				</td>
				<td class="right">
					<textarea name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" rows=12 class="standardWidth"><?php print $row["value"] ?></textarea>
				</td>
			</tr>
			<tr>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='Staff' AND name='applicationFormRefereeLink'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				$row=$result->fetch() ;
				?>
				<td style='width: 275px'> 
					<b><?php print __($guid, $row["nameDisplay"]) ?></b><br/>
					<span class="emphasis small"><?php if ($row["description"]!="") { print __($guid, $row["description"]) ; } ?></span>
				</td>
				<td stclass="right">
					<input name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" maxlength=255 value="<?php print htmlPrep($row["value"]) ?>" type="text" class="standardWidth">
					<script type="text/javascript">
						var <?php print $row["name"] ?>=new LiveValidation('<?php print $row["name"] ?>');
						<?php print $row["name"] ?>.add( Validate.Format, { pattern: /(http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/, failureMessage: "Must start with http:// or https://" } );
					</script> 
				</td>
			</tr>
			<tr>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='Staff' AND name='staffApplicationFormPostscript'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				$row=$result->fetch() ;
				?>
				<td> 
					<b><?php print __($guid, $row["nameDisplay"]) ?></b><br/>
					<span class="emphasis small"><?php if ($row["description"]!="") { print __($guid, $row["description"]) ; } ?></span>
				</td>
				<td class="right">
					<textarea name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" rows=12 class="standardWidth"><?php print $row["value"] ?></textarea>
				</td>
			</tr>
			<tr>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='Staff' AND name='staffApplicationFormAgreement'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				$row=$result->fetch() ;
				?>
				<td> 
					<b><?php print __($guid, $row["nameDisplay"]) ?></b><br/>
					<span class="emphasis small"><?php if ($row["description"]!="") { print __($guid, $row["description"]) ; } ?></span>
				</td>
				<td class="right">
					<textarea name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" rows=8 class="standardWidth"><?php print $row["value"] ?></textarea>
				</td>
			</tr>
			<tr>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='Staff' AND name='staffApplicationFormMilestones'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { }
				$row=$result->fetch() ;
				?>
				<td> 
					<b><?php print __($guid, $row["nameDisplay"]) ?></b><br/>
					<span class="emphasis small"><?php if ($row["description"]!="") { print __($guid, $row["description"]) ; } ?></span>
				</td>
				<td class="right">
					<textarea name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" rows=4 type="text" class="standardWidth"><?php print $row["value"] ?></textarea>
				</td>
			</tr>
			
			<tr class='break'>
				<td colspan=2> 
					<h3><?php print __($guid, 'Required Documents Options') ?></h3>
				</td>
			</tr>
			<tr>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='Staff' AND name='staffApplicationFormRequiredDocuments'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { }
				$row=$result->fetch() ;
				?>
				<td> 
					<b><?php print __($guid, $row["nameDisplay"]) ?></b><br/>
					<span class="emphasis small"><?php if ($row["description"]!="") { print __($guid, $row["description"]) ; } ?></span>
				</td>
				<td class="right">
					<textarea name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" rows=4 type="text" class="standardWidth"><?php print $row["value"] ?></textarea>
				</td>
			</tr>
			<tr>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='Staff' AND name='staffApplicationFormRequiredDocumentsText'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { }
				$row=$result->fetch() ;
				?>
				<td> 
					<b><?php print __($guid, $row["nameDisplay"]) ?></b><br/>
					<span class="emphasis small"><?php if ($row["description"]!="") { print __($guid, $row["description"]) ; } ?></span>
				</td>
				<td class="right">
					<textarea name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" rows=4 type="text" class="standardWidth"><?php print $row["value"] ?></textarea>
				</td>
			</tr>
			<tr>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='Staff' AND name='staffApplicationFormRequiredDocumentsCompulsory'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				$row=$result->fetch() ;
				?>
				<td> 
					<b><?php print __($guid, $row["nameDisplay"]) ?> *</b><br/>
					<span class="emphasis small"><?php if ($row["description"]!="") { print __($guid, $row["description"]) ; } ?></span>
				</td>
				<td class="right">
					<select name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" class="standardWidth">
						<option <?php if ($row["value"]=="N") {print "selected ";} ?>value="N">No</option>
						<option <?php if ($row["value"]=="Y") {print "selected ";} ?>value="Y">Yes</option>
					</select>
				</td>
			</tr>
			
			
			<tr class='break'>
				<td colspan=2> 
					<h3><?php print __($guid, 'Acceptance Options') ?></h3>
				</td>
			</tr>
			<tr>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='Staff' AND name='staffApplicationFormUsernameFormat'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				$row=$result->fetch() ;
				?>
				<td> 
					<b><?php print __($guid, $row["nameDisplay"]) ?></b><br/>
					<span class="emphasis small"><?php if ($row["description"]!="") { print __($guid, $row["description"]) ; } ?></span>
				</td>
				<td class="right">
					<input type='text' name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" class="standardWidth" value='<?php print htmlPrep($row["value"]) ?>'>
				</td>
			</tr>
			<tr>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='Staff' AND name='staffApplicationFormNotificationMessage'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				$row=$result->fetch() ;
				?>
				<td> 
					<b><?php print __($guid, $row["nameDisplay"]) ?></b><br/>
					<span class="emphasis small"><?php if ($row["description"]!="") { print __($guid, $row["description"]) ; } ?></span>
				</td>
				<td class="right">
					<textarea name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" rows=8 class="standardWidth"><?php print $row["value"] ?></textarea>
				</td>
			</tr>
			<tr>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='Staff' AND name='staffApplicationFormNotificationDefault'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				$row=$result->fetch() ;
				?>
				<td> 
					<b><?php print __($guid, $row["nameDisplay"]) ?> *</b><br/>
					<span class="emphasis small"><?php if ($row["description"]!="") { print __($guid, $row["description"]) ; } ?></span>
				</td>
				<td class="right">
					<select name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" class="standardWidth">
						<option <?php if ($row["value"]=="Y") {print "selected ";} ?>value="Y"><?php print ynExpander($guid, 'Y') ?></option>
						<option <?php if ($row["value"]=="N") {print "selected ";} ?>value="N"><?php print ynExpander($guid, 'N') ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='Staff' AND name='staffApplicationFormDefaultEmail'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				$row=$result->fetch() ;
				?>
				<td> 
					<b><?php print __($guid, $row["nameDisplay"]) ?></b><br/>
					<span class="emphasis small"><?php if ($row["description"]!="") { print __($guid, $row["description"]) ; } ?></span>
				</td>
				<td class="right">
					<input type='text' name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" class="standardWidth" value='<?php print htmlPrep($row["value"]) ?>'>
				</td>
			</tr>
			<tr>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='Staff' AND name='staffApplicationFormDefaultWebsite'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				$row=$result->fetch() ;
				?>
				<td> 
					<b><?php print __($guid, $row["nameDisplay"]) ?></b><br/>
					<span class="emphasis small"><?php if ($row["description"]!="") { print __($guid, $row["description"]) ; } ?></span>
				</td>
				<td class="right">
					<input type='text' name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" class="standardWidth" value='<?php print htmlPrep($row["value"]) ?>'>
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