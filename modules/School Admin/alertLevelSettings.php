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

if (isActionAccessible($guid, $connection2, "/modules/School Admin/daysOfWeek_manage.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __($guid, 'Manage Alert Levels') . "</div>" ;
	print "</div>" ;
	
	if (isset($_GET["updateReturn"])) { $updateReturn=$_GET["updateReturn"] ; } else { $updateReturn="" ; }
	$updateReturnMessage="" ;
	$class="error" ;
	if (!($updateReturn=="")) {
		if ($updateReturn=="fail0") {
			$updateReturnMessage=__($guid, "Your request failed because you do not have access to this action.") ;	
		}
		else if ($updateReturn=="fail1") {
			$updateReturnMessage=__($guid, "Your request failed because your inputs were invalid.") ;	
		}
		else if ($updateReturn=="fail2") {
			$updateReturnMessage=__($guid, "Your request failed due to a database error.") ;	
		}
		else if ($updateReturn=="fail3") {
			$updateReturnMessage=__($guid, "Your request failed because your inputs were invalid.") ;	
		}
		else if ($updateReturn=="fail4") {
			$updateReturnMessage=__($guid, "Your request was successful, but some data was not properly saved.") ;
		}
		else if ($updateReturn=="success0") {
			$updateReturnMessage=__($guid, "Your request was completed successfully.") ;	
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $updateReturnMessage;
		print "</div>" ;
	} 
	
	try {
		$data=array(); 
		$sql="SELECT * FROM gibbonAlertLevel ORDER BY sequenceNumber" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		print "<div class='error'>" . $e->getMessage() . "</div>" ; 
	}

	//Let's go!
	?>
	<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/alertLevelSettingsProcess.php"?>">
		<table class='smallIntBorder fullWidth' cellspacing='0'>	
			<?php
			$count=0 ;
			while($row=$result->fetch()) {
				?>
				<tr class='break'>
					<td colspan=2> 
						<h3><?php print __($guid, $row["name"]) ?></h3>
					</td>
				</tr>
				<tr>
					<td style='width: 275px'> 
						<b><?php print __($guid, 'Name') ?> *</b>
					</td>
					<td class="right">
						<input type='hidden' name="<?php print "gibbonAlertLevelID" .$count ?>" id="<?php print "gibbonAlertLevelID" .$count ?>" value="<?php print $row["gibbonAlertLevelID"] ?>">
						<input type='text' name="<?php print "name" .$count ?>" id="<?php print "name" .$count ?>" maxlength=50 value="<?php print __($guid, $row["name"]) ?>" class="standardWidth">
						<script type="text/javascript">
							var <?php print "name" .$count ?>=new LiveValidation('<?php print "name" .$count ?>');
							<?php print "name" .$count ?>.add(Validate.Presence);
						</script>
					</td>
				</tr>
				<tr>
					<td> 
						<b><?php print __($guid, 'Short Name') ?> *</b>
					</td>
					<td class="right">
						<input type='text' name="<?php print "nameShort" .$count ?>" id="<?php print "nameShort" .$count ?>" maxlength=4 value="<?php print $row["nameShort"] ?>" class="standardWidth">
						<script type="text/javascript">
							var <?php print "nameShort" .$count ?>=new LiveValidation('<?php print "nameShort" .$count ?>');
							<?php print "nameShort" .$count ?>.add(Validate.Presence);
						</script>
					</td>
				</tr>
				<tr>
					<td> 
						<b><?php print __($guid, 'Font/Border Color') ?> *</b><br/>
						<span class="emphasis small">RGB Hex value, without leading #.</span>
					</td>
					<td class="right">
						<input type='text' name="<?php print "color" .$count ?>" id="<?php print "color" .$count ?>" maxlength=6 value="<?php print $row["color"] ?>" class="standardWidth">
						<script type="text/javascript">
							var <?php print "color" .$count ?>=new LiveValidation('<?php print "color" .$count ?>');
							<?php print "color" .$count ?>.add(Validate.Presence);
						</script>
					</td>
				</tr>
				<tr>
					<td> 
						<b><?php print __($guid, 'Background Color') ?> *</b><br/>
						<span class="emphasis small">RGB Hex value, without leading #.</span>
					</td>
					<td class="right">
						<input type='text' name="<?php print "colorBG" .$count ?>" id="<?php print "colorBG" .$count ?>" maxlength=6 value="<?php print $row["colorBG"] ?>" class="standardWidth">
						<script type="text/javascript">
							var <?php print "colorBG" .$count ?>=new LiveValidation('<?php print "colorBG" .$count ?>');
							<?php print "colorBG" .$count ?>.add(Validate.Presence);
						</script>
					</td>
				</tr>
				<tr>
					<td> 
						<b><?php print __($guid, 'Sequence Number') ?> *</b><br/>
						<span class="emphasis small"><?php print __($guid, 'This value cannot be changed.') ?></span>
					</td>
					<td class="right">
						<input readonly type='text' name="<?php print "sequenceNumber" .$count ?>" id="<?php print "sequenceNumber" .$count ?>" maxlength=4 value="<?php print $row["sequenceNumber"] ?>" class="standardWidth">
					</td>
				</tr>
				<tr>
					<td colspan=2> 
						<b>Description </b> 
						<textarea name='<?php print "description" .$count ?>' id='<?php print "description" .$count ?>' rows=5 style='width: 300px'><?php print __($guid, $row["description"]) ?></textarea>
					</td>
				</tr>
				<?php
				$count++ ;
			}
			?>
			<tr>
				<td>
					<span class="emphasis small">* <?php print __($guid, "denotes a required field") ; ?></span>
				</td>
				<td class="right">
					<input type="hidden" name="count" value="<?php print $count ?>">
					<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
					<input type="submit" value="<?php print __($guid, "Submit") ; ?>">
				</td>
			</tr>
		</table>
	</form>
	<?php
}
?>