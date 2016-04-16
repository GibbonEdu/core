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

$enableDescriptors=getSettingByScope($connection2, "Behaviour", "enableDescriptors") ;
$enableLevels=getSettingByScope($connection2, "Behaviour", "enableLevels") ;
$enableBehaviourLetters=getSettingByScope($connection2, "Behaviour", "enableBehaviourLetters") ;

if (isActionAccessible($guid, $connection2, "/modules/School Admin/behaviourSettings.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __($guid, 'Manage Behaviour Settings') . "</div>" ;
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
			$updateReturnMessage=__($guid, "One or more of the fields in your request failed due to a database error.") ;	
		}
		else if ($updateReturn=="fail3") {
			$updateReturnMessage=__($guid, "Your request failed because your inputs were invalid.") ;	
		}
		else if ($updateReturn=="success0") {
			$updateReturnMessage=__($guid, "Your request was completed successfully.") ;	
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $updateReturnMessage;
		print "</div>" ;
	} 
	?>
	
	<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/behaviourSettingsProcess.php" ?>">
		<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
			<tr class='break'>
				<td colspan=2> 
					<h3><?php print __($guid, 'Descriptors') ?></h3>
				</td>
			</tr>
			<tr>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='Behaviour' AND name='enableDescriptors'" ;
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
					<span style="font-size: 90%"><i><?php if ($row["description"]!="") { print __($guid, $row["description"]) ; } ?></i></span>
				</td>
				<td class="right">
					<select name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" style="width: 302px">
						<option <?php if ($row["value"]=="Y") {print "selected ";} ?>value="Y"><?php print __($guid, 'Yes') ?></option>
						<option <?php if ($row["value"]=="N") {print "selected ";} ?>value="N"><?php print __($guid, 'No') ?></option>
					</select>
				</td>
			</tr>
			<script type="text/javascript">
				$(document).ready(function(){
					 $("#enableDescriptors").click(function(){
						if ($('#enableDescriptors option:selected').val()=="Y" ) {
							$("#positiveRow").slideDown("fast", $("#positiveRow").css("display","table-row")); 
							$("#negativeRow").slideDown("fast", $("#negativeRow").css("display","table-row"));   

						} else {
							$("#positiveRow").css("display","none");
							$("#negativeRow").css("display","none");
						}
					 });
				});
			</script>
			<tr id='positiveRow' <?php if ($enableDescriptors=="N") { print " style='display: none'" ; }?>>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='Behaviour' AND name='positiveDescriptors'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { }
				$row=$result->fetch() ;
				?>
				<td style='width: 275px'> 
					<b><?php print __($guid, $row["nameDisplay"]) ?> *</b><br/>
					<span style="font-size: 90%"><i><?php if ($row["description"]!="") { print __($guid, $row["description"]) ; } ?></i></span>
				</td>
				<td class="right">
					<textarea name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" type="text" style="width: 300px" rows=4><?php print $row["value"] ?></textarea>
					<script type="text/javascript">
						var <?php print $row["name"] ?>=new LiveValidation('<?php print $row["name"] ?>');
						<?php print $row["name"] ?>.add(Validate.Presence);
					</script> 
				</td>
			</tr>
			<tr id='negativeRow' <?php if ($enableDescriptors=="N") { print " style='display: none'" ; }?>>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='Behaviour' AND name='negativeDescriptors'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { }
				$row=$result->fetch() ;
				?>
				<td> 
					<b><?php print __($guid, $row["nameDisplay"]) ?> *</b><br/>
					<span style="font-size: 90%"><i><?php if ($row["description"]!="") { print __($guid, $row["description"]) ; } ?></i></span>
				</td>
				<td class="right">
					<textarea name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" type="text" style="width: 300px" rows=4><?php print $row["value"] ?></textarea>
					<script type="text/javascript">
						var <?php print $row["name"] ?>=new LiveValidation('<?php print $row["name"] ?>');
						<?php print $row["name"] ?>.add(Validate.Presence);
					</script> 
				</td>
			</tr>
			
			
			<tr class='break'>
				<td colspan=2> 
					<h3><?php print __($guid, 'Levels') ?></h3>
				</td>
			</tr>
			<tr>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='Behaviour' AND name='enableLevels'" ;
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
					<span style="font-size: 90%"><i><?php if ($row["description"]!="") { print __($guid, $row["description"]) ; } ?></i></span>
				</td>
				<td class="right">
					<select name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" style="width: 302px">
						<option <?php if ($row["value"]=="Y") {print "selected ";} ?>value="Y"><?php print __($guid, 'Yes') ?></option>
						<option <?php if ($row["value"]=="N") {print "selected ";} ?>value="N"><?php print __($guid, 'No') ?></option>
					</select>
				</td>
			</tr>
			<script type="text/javascript">
				$(document).ready(function(){
					 $("#enableLevels").click(function(){
						if ($('#enableLevels option:selected').val()=="Y" ) {
							$("#levelsRow").slideDown("fast", $("#levelsRow").css("display","table-row"));  

						} else {
							$("#levelsRow").css("display","none");
						}
					 });
				});
			</script>
			<tr id='levelsRow' <?php if ($enableLevels=="N") { print " style='display: none'" ; }?>>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='Behaviour' AND name='levels'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { }
				$row=$result->fetch() ;
				?>
				<td> 
					<b><?php print __($guid, $row["nameDisplay"]) ?> *</b><br/>
					<span style="font-size: 90%"><i><?php if ($row["description"]!="") { print __($guid, $row["description"]) ; } ?></i></span>
				</td>
				<td class="right">
					<textarea name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" type="text" style="width: 300px" rows=4><?php print $row["value"] ?></textarea>
					<script type="text/javascript">
						var <?php print $row["name"] ?>=new LiveValidation('<?php print $row["name"] ?>');
						<?php print $row["name"] ?>.add(Validate.Presence);
					</script> 
				</td>
			</tr>
			
			
			<tr class='break'>
				<td colspan=2> 
					<h3><?php print __($guid, 'Behaviour Letters') ?></h3>
					<p><?php print sprintf(__($guid, 'By using an %1$sincluded CLI script%2$s, %3$s can be configured to automatically generate and email behaviour letters to parents and tutors, once certain negative behaviour threshold levels have been reached. In your letter text you may use the following fields: %4$s'), "<a target='_blank' href='https://gibbonedu.org/support/administrators/command-line-tools/'>", "</a>", $_SESSION[$guid]["systemName"], "[studentName], [rollGroup], [behaviourCount], [behaviourRecord]") ?></p>
				</td>
			</tr>
			<tr>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='Behaviour' AND name='enableBehaviourLetters'" ;
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
					<span style="font-size: 90%"><i><?php if ($row["description"]!="") { print __($guid, $row["description"]) ; } ?></i></span>
				</td>
				<td class="right">
					<select name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" style="width: 302px">
						<option <?php if ($row["value"]=="Y") {print "selected ";} ?>value="Y"><?php print __($guid, 'Yes') ?></option>
						<option <?php if ($row["value"]=="N") {print "selected ";} ?>value="N"><?php print __($guid, 'No') ?></option>
					</select>
				</td>
			</tr>
			<script type="text/javascript">
				$(document).ready(function(){
					 $("#enableBehaviourLetters").click(function(){
						if ($('#enableBehaviourLetters option:selected').val()=="Y" ) {
							$("#behaviourLettersLetter1CountRow").slideDown("fast", $("#behaviourLettersLetter1CountRow").css("display","table-row"));  
							$("#behaviourLettersLetter1TextRow").slideDown("fast", $("#behaviourLettersLetter1TextRow").css("display","table-row"));  
							$("#behaviourLettersLetter2CountRow").slideDown("fast", $("#behaviourLettersLetter2CountRow").css("display","table-row"));  
							$("#behaviourLettersLetter2TextRow").slideDown("fast", $("#behaviourLettersLetter2TextRow").css("display","table-row"));  
							$("#behaviourLettersLetter3CountRow").slideDown("fast", $("#behaviourLettersLetter3CountRow").css("display","table-row"));  
							$("#behaviourLettersLetter3TextRow").slideDown("fast", $("#behaviourLettersLetter3TextRow").css("display","table-row"));  
							behaviourLettersLetter1Count.enable() ;
							behaviourLettersLetter1Text.enable() ; 
							behaviourLettersLetter2Count.enable() ;
							behaviourLettersLetter2Text.enable() ; 
							behaviourLettersLetter3Count.enable() ;
							behaviourLettersLetter3Text.enable() ;
						} else {
							$("#behaviourLettersLetter1CountRow").css("display","none");
							$("#behaviourLettersLetter1TextRow").css("display","none");
							$("#behaviourLettersLetter2CountRow").css("display","none");
							$("#behaviourLettersLetter2TextRow").css("display","none");
							$("#behaviourLettersLetter3CountRow").css("display","none");
							$("#behaviourLettersLetter3TextRow").css("display","none");
							behaviourLettersLetter1Count.disable() ;
							behaviourLettersLetter1Text.disable() ;
							behaviourLettersLetter2Count.disable() ;
							behaviourLettersLetter2Text.disable() ;
							behaviourLettersLetter3Count.disable() ;
							behaviourLettersLetter3Text.disable() ;
						}
					 });
				});
			</script>
			<tr id='behaviourLettersLetter1CountRow' <?php if ($enableBehaviourLetters=="N") { print " style='display: none'" ; }?>>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='Behaviour' AND name='behaviourLettersLetter1Count'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { }
				$row=$result->fetch() ;
				?>
				<td> 
					<b><?php print __($guid, $row["nameDisplay"]) ?> *</b><br/>
					<span style="font-size: 90%"><i><?php if ($row["description"]!="") { print __($guid, $row["description"]) ; } ?></i></span>
				</td>
				<td class="right">
					<select name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" style="width: 302px">
						<option value="Please select..."><?php print __($guid, 'Please select...') ?></option>
						<?php
						for ($i=1; $i<=20; $i++) {
							?>
							<option <?php if ($i==$row["value"]) { print "selected" ; } ?> value="<?php print $i ?>"><?php print $i ?></option>
						<?php
						}
						?>
					</select>
					<script type="text/javascript">
						var <?php print $row["name"] ?>=new LiveValidation('<?php print $row["name"] ?>');
						<?php print $row["name"] ?>.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print __($guid, 'Select something!') ?>"});
						<?php if ($enableBehaviourLetters=="N") { print $row["name"] . ".disable() ;" ; } ?>
					</script> 
				</td>
			</tr>
			<tr id='behaviourLettersLetter1TextRow' <?php if ($enableBehaviourLetters=="N") { print " style='display: none'" ; }?>>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='Behaviour' AND name='behaviourLettersLetter1Text'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { }
				$row=$result->fetch() ;
				?>
				<td> 
					<b><?php print __($guid, $row["nameDisplay"]) ?> *</b><br/>
					<span style="font-size: 90%"><i><?php if ($row["description"]!="") { print __($guid, $row["description"]) ; } ?></i></span>
				</td>
				<td class="right">
					<textarea name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" type="text" style="width: 300px" rows=4><?php print $row["value"] ?></textarea>
					<script type="text/javascript">
						var <?php print $row["name"] ?>=new LiveValidation('<?php print $row["name"] ?>');
						<?php print $row["name"] ?>.add(Validate.Presence);
						<?php if ($enableBehaviourLetters=="N") { print $row["name"] . ".disable() ;" ; } ?>
					</script> 
				</td>
			</tr>
			<tr id='behaviourLettersLetter2CountRow' <?php if ($enableBehaviourLetters=="N") { print " style='display: none'" ; }?>>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='Behaviour' AND name='behaviourLettersLetter2Count'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { }
				$row=$result->fetch() ;
				?>
				<td> 
					<b><?php print __($guid, $row["nameDisplay"]) ?> *</b><br/>
					<span style="font-size: 90%"><i><?php if ($row["description"]!="") { print __($guid, $row["description"]) ; } ?></i></span>
				</td>
				<td class="right">
					<select name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" style="width: 302px">
						<option value="Please select..."><?php print __($guid, 'Please select...') ?></option>
						<?php
						for ($i=1; $i<=20; $i++) {
							?>
							<option <?php if ($i==$row["value"]) { print "selected" ; } ?> value="<?php print $i ?>"><?php print $i ?></option>
						<?php
						}
						?>
					</select>
					<script type="text/javascript">
						var <?php print $row["name"] ?>=new LiveValidation('<?php print $row["name"] ?>');
						<?php print $row["name"] ?>.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print __($guid, 'Select something!') ?>"});
						<?php if ($enableBehaviourLetters=="N") { print $row["name"] . ".disable() ;" ; } ?>
					</script> 
				</td>
			</tr>
			<tr id='behaviourLettersLetter2TextRow' <?php if ($enableBehaviourLetters=="N") { print " style='display: none'" ; }?>>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='Behaviour' AND name='behaviourLettersLetter2Text'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { }
				$row=$result->fetch() ;
				?>
				<td> 
					<b><?php print __($guid, $row["nameDisplay"]) ?> *</b><br/>
					<span style="font-size: 90%"><i><?php if ($row["description"]!="") { print __($guid, $row["description"]) ; } ?></i></span>
				</td>
				<td class="right">
					<textarea name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" type="text" style="width: 300px" rows=4><?php print $row["value"] ?></textarea>
					<script type="text/javascript">
						var <?php print $row["name"] ?>=new LiveValidation('<?php print $row["name"] ?>');
						<?php print $row["name"] ?>.add(Validate.Presence);
						<?php if ($enableBehaviourLetters=="N") { print $row["name"] . ".disable() ;" ; } ?>
					</script> 
				</td>
			</tr>
			<tr id='behaviourLettersLetter3CountRow' <?php if ($enableBehaviourLetters=="N") { print " style='display: none'" ; }?>>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='Behaviour' AND name='behaviourLettersLetter3Count'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { }
				$row=$result->fetch() ;
				?>
				<td> 
					<b><?php print __($guid, $row["nameDisplay"]) ?> *</b><br/>
					<span style="font-size: 90%"><i><?php if ($row["description"]!="") { print __($guid, $row["description"]) ; } ?></i></span>
				</td>
				<td class="right">
					<select name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" style="width: 302px">
						<option value="Please select..."><?php print __($guid, 'Please select...') ?></option>
						<?php
						for ($i=1; $i<=20; $i++) {
							?>
							<option <?php if ($i==$row["value"]) { print "selected" ; } ?> value="<?php print $i ?>"><?php print $i ?></option>
						<?php
						}
						?>
					</select>
					<script type="text/javascript">
						var <?php print $row["name"] ?>=new LiveValidation('<?php print $row["name"] ?>');
						<?php print $row["name"] ?>.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print __($guid, 'Select something!') ?>"});
						<?php if ($enableBehaviourLetters=="N") { print $row["name"] . ".disable() ;" ; } ?>
					</script> 
				</td>
			</tr>
			<tr id='behaviourLettersLetter3TextRow' <?php if ($enableBehaviourLetters=="N") { print " style='display: none'" ; }?>>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='Behaviour' AND name='behaviourLettersLetter3Text'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { }
				$row=$result->fetch() ;
				?>
				<td> 
					<b><?php print __($guid, $row["nameDisplay"]) ?> *</b><br/>
					<span style="font-size: 90%"><i><?php if ($row["description"]!="") { print __($guid, $row["description"]) ; } ?></i></span>
				</td>
				<td class="right">
					<textarea name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" type="text" style="width: 300px" rows=4><?php print $row["value"] ?></textarea>
					<script type="text/javascript">
						var <?php print $row["name"] ?>=new LiveValidation('<?php print $row["name"] ?>');
						<?php print $row["name"] ?>.add(Validate.Presence);
						<?php if ($enableBehaviourLetters=="N") { print $row["name"] . ".disable() ;" ; } ?>
					</script> 
				</td>
			</tr>
			
			
			<tr class='break'>
				<td colspan=2> 
					<h3><?php print __($guid, 'Miscellaneous') ?></h3>
				</td>
			</tr>
			<tr>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='Behaviour' AND name='policyLink'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { }
				$row=$result->fetch() ;
				?>
				<td> 
					<b><?php print __($guid, $row["nameDisplay"]) ?></b><br/>
					<span style="font-size: 90%"><i><?php if ($row["description"]!="") { print __($guid, $row["description"]) ; } ?></i></span>
				</td>
				<td class="right">
					<input type='text' name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>"style="width: 300px" value='<?php print htmlPrep($row["value"]) ?>'>
					<script type="text/javascript">
						var <?php print $row["name"] ?>=new LiveValidation('<?php print $row["name"] ?>');
						<?php print $row["name"] ?>.add( Validate.Format, { pattern: /(http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/, failureMessage: "Must start with http:// or https://" } );
					</script>	
				</td>
			</tr>
			
			<tr>
				<td>
					<span style="font-size: 90%"><i>* <?php print __($guid, "denotes a required field") ; ?></i></span>
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