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

@session_start();

$enableDescriptors = getSettingByScope($connection2, 'Behaviour', 'enableDescriptors');
$enableLevels = getSettingByScope($connection2, 'Behaviour', 'enableLevels');
$enableBehaviourLetters = getSettingByScope($connection2, 'Behaviour', 'enableBehaviourLetters');

if (isActionAccessible($guid, $connection2, '/modules/School Admin/behaviourSettings.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Manage Behaviour Settings').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }
    ?>

	<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/behaviourSettingsProcess.php' ?>">
		<table class='smallIntBorder fullWidth' cellspacing='0'>
			<tr class='break'>
				<td colspan=2>
					<h3><?php echo __($guid, 'Descriptors') ?></h3>
				</td>
			</tr>
			<tr>
				<?php
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonSetting WHERE scope='Behaviour' AND name='enableDescriptors'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }
                $row = $result->fetch();
                ?>
				<td>
					<b><?php echo __($guid, $row['nameDisplay']) ?> *</b><br/>
					<span class="emphasis small"><?php if ($row['description'] != '') { echo __($guid, $row['description']);}?></span>
				</td>
				<td class="right">
					<select name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" class="standardWidth">
						<option <?php if ($row['value'] == 'Y') { echo 'selected '; } ?>value="Y"><?php echo __($guid, 'Yes') ?></option>
						<option <?php if ($row['value'] == 'N') { echo 'selected '; } ?>value="N"><?php echo __($guid, 'No') ?></option>
					</select>
				</td>
			</tr>
			<script type="text/javascript">
				$(document).ready(function(){
					 $("#enableDescriptors").click(function(){
						if ($('#enableDescriptors').val()=="Y" ) {
							$("#positiveRow").slideDown("fast", $("#positiveRow").css("display","table-row"));
							$("#negativeRow").slideDown("fast", $("#negativeRow").css("display","table-row"));

						} else {
							$("#positiveRow").css("display","none");
							$("#negativeRow").css("display","none");
						}
					 });
				});
			</script>
			<tr id='positiveRow' <?php if ($enableDescriptors == 'N') { echo " style='display: none'"; } ?>>
				<?php
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonSetting WHERE scope='Behaviour' AND name='positiveDescriptors'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {}
                $row = $result->fetch();
                ?>
				<td style='width: 275px'>
					<b><?php echo __($guid, $row['nameDisplay']) ?> *</b><br/>
					<span class="emphasis small"><?php if ($row['description'] != '') { echo __($guid, $row['description']);}?></span>
				</td>
				<td class="right">
					<textarea name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" type="text" class="standardWidth" rows=4><?php echo $row['value'] ?></textarea>
					<script type="text/javascript">
						var <?php echo $row['name'] ?>=new LiveValidation('<?php echo $row['name'] ?>');
						<?php echo $row['name'] ?>.add(Validate.Presence);
					</script>
				</td>
			</tr>
			<tr id='negativeRow' <?php if ($enableDescriptors == 'N') { echo " style='display: none'"; } ?>>
				<?php
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonSetting WHERE scope='Behaviour' AND name='negativeDescriptors'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {}
                $row = $result->fetch();
                ?>
				<td>
					<b><?php echo __($guid, $row['nameDisplay']) ?> *</b><br/>
					<span class="emphasis small"><?php if ($row['description'] != '') { echo __($guid, $row['description']);}?></span>
				</td>
				<td class="right">
					<textarea name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" type="text" class="standardWidth" rows=4><?php echo $row['value'] ?></textarea>
					<script type="text/javascript">
						var <?php echo $row['name'] ?>=new LiveValidation('<?php echo $row['name'] ?>');
						<?php echo $row['name'] ?>.add(Validate.Presence);
					</script>
				</td>
			</tr>


			<tr class='break'>
				<td colspan=2>
					<h3><?php echo __($guid, 'Levels') ?></h3>
				</td>
			</tr>
			<tr>
				<?php
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonSetting WHERE scope='Behaviour' AND name='enableLevels'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }
                $row = $result->fetch();
                ?>
				<td>
					<b><?php echo __($guid, $row['nameDisplay']) ?> *</b><br/>
					<span class="emphasis small"><?php if ($row['description'] != '') { echo __($guid, $row['description']);}?></span>
				</td>
				<td class="right">
					<select name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" class="standardWidth">
						<option <?php if ($row['value'] == 'Y') { echo 'selected '; } ?>value="Y"><?php echo __($guid, 'Yes') ?></option>
						<option <?php if ($row['value'] == 'N') { echo 'selected '; } ?>value="N"><?php echo __($guid, 'No') ?></option>
					</select>
				</td>
			</tr>
			<script type="text/javascript">
				$(document).ready(function(){
					 $("#enableLevels").click(function(){
						if ($('#enableLevels').val()=="Y" ) {
							$("#levelsRow").slideDown("fast", $("#levelsRow").css("display","table-row"));

						} else {
							$("#levelsRow").css("display","none");
						}
					 });
				});
			</script>
			<tr id='levelsRow' <?php if ($enableLevels == 'N') { echo " style='display: none'"; } ?>>
				<?php
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonSetting WHERE scope='Behaviour' AND name='levels'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {}
                $row = $result->fetch();
                ?>
				<td>
					<b><?php echo __($guid, $row['nameDisplay']) ?> *</b><br/>
					<span class="emphasis small"><?php if ($row['description'] != '') { echo __($guid, $row['description']);}?></span>
				</td>
				<td class="right">
					<textarea name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" type="text" class="standardWidth" rows=4><?php echo $row['value'] ?></textarea>
					<script type="text/javascript">
						var <?php echo $row['name'] ?>=new LiveValidation('<?php echo $row['name'] ?>');
						<?php echo $row['name'] ?>.add(Validate.Presence);
					</script>
				</td>
			</tr>


			<tr class='break'>
				<td colspan=2>
					<h3><?php echo __($guid, 'Behaviour Letters') ?></h3>
					<p><?php echo sprintf(__($guid, 'By using an %1$sincluded CLI script%2$s, %3$s can be configured to automatically generate and email behaviour letters to parents and tutors, once certain negative behaviour threshold levels have been reached. In your letter text you may use the following fields: %4$s'), "<a target='_blank' href='https://gibbonedu.org/support/administrators/command-line-tools/'>", '</a>', $_SESSION[$guid]['systemName'], '[studentName], [rollGroup], [behaviourCount], [behaviourRecord]') ?></p>
				</td>
			</tr>
			<tr>
				<?php
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonSetting WHERE scope='Behaviour' AND name='enableBehaviourLetters'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }
                $row = $result->fetch();
                ?>
				<td>
					<b><?php echo __($guid, $row['nameDisplay']) ?> *</b><br/>
					<span class="emphasis small"><?php if ($row['description'] != '') { echo __($guid, $row['description']);}?></span>
				</td>
				<td class="right">
					<select name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" class="standardWidth">
						<option <?php if ($row['value'] == 'Y') { echo 'selected '; } ?>value="Y"><?php echo __($guid, 'Yes') ?></option>
						<option <?php if ($row['value'] == 'N') { echo 'selected '; } ?>value="N"><?php echo __($guid, 'No') ?></option>
					</select>
				</td>
			</tr>
			<script type="text/javascript">
				$(document).ready(function(){
					 $("#enableBehaviourLetters").click(function(){
						if ($('#enableBehaviourLetters').val()=="Y" ) {
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
			<tr id='behaviourLettersLetter1CountRow' <?php if ($enableBehaviourLetters == 'N') { echo " style='display: none'"; } ?>>
				<?php
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonSetting WHERE scope='Behaviour' AND name='behaviourLettersLetter1Count'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {}
                $row = $result->fetch();
                ?>
				<td>
					<b><?php echo __($guid, $row['nameDisplay']) ?> *</b><br/>
					<span class="emphasis small"><?php if ($row['description'] != '') { echo __($guid, $row['description']);}?></span>
				</td>
				<td class="right">
					<select name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" class="standardWidth">
						<option value="Please select..."><?php echo __($guid, 'Please select...') ?></option>
						<?php
                        for ($i = 1; $i <= 20; ++$i) {
                            ?>
							<option <?php if ($i == $row['value']) { echo 'selected'; } ?> value="<?php echo $i ?>"><?php echo $i ?></option>
						<?php

                        }
   				 		?>
					</select>
					<script type="text/javascript">
						var <?php echo $row['name'] ?>=new LiveValidation('<?php echo $row['name'] ?>');
						<?php echo $row['name'] ?>.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
						<?php if ($enableBehaviourLetters == 'N') { echo $row['name'].'.disable() ;'; } ?>
					</script>
				</td>
			</tr>
			<tr id='behaviourLettersLetter1TextRow' <?php if ($enableBehaviourLetters == 'N') { echo " style='display: none'"; } ?>>
				<?php
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonSetting WHERE scope='Behaviour' AND name='behaviourLettersLetter1Text'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {}
                $row = $result->fetch();
                ?>
				<td>
					<b><?php echo __($guid, $row['nameDisplay']) ?> *</b><br/>
					<span class="emphasis small"><?php if ($row['description'] != '') { echo __($guid, $row['description']);}?></span>
				</td>
				<td class="right">
					<textarea name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" type="text" class="standardWidth" rows=4><?php echo $row['value'] ?></textarea>
					<script type="text/javascript">
						var <?php echo $row['name'] ?>=new LiveValidation('<?php echo $row['name'] ?>');
						<?php echo $row['name'] ?>.add(Validate.Presence);
						<?php if ($enableBehaviourLetters == 'N') { echo $row['name'].'.disable() ;'; } ?>
					</script>
				</td>
			</tr>
			<tr id='behaviourLettersLetter2CountRow' <?php if ($enableBehaviourLetters == 'N') { echo " style='display: none'"; } ?>>
				<?php
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonSetting WHERE scope='Behaviour' AND name='behaviourLettersLetter2Count'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {}
                $row = $result->fetch();
                ?>
				<td>
					<b><?php echo __($guid, $row['nameDisplay']) ?> *</b><br/>
					<span class="emphasis small"><?php if ($row['description'] != '') { echo __($guid, $row['description']);}?></span>
				</td>
				<td class="right">
					<select name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" class="standardWidth">
						<option value="Please select..."><?php echo __($guid, 'Please select...') ?></option>
						<?php
                        for ($i = 1; $i <= 20; ++$i) {
                            ?>
							<option <?php if ($i == $row['value']) { echo 'selected'; } ?> value="<?php echo $i ?>"><?php echo $i ?></option>
						<?php

                        }
   				 		?>
					</select>
					<script type="text/javascript">
						var <?php echo $row['name'] ?>=new LiveValidation('<?php echo $row['name'] ?>');
						<?php echo $row['name'] ?>.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
						<?php if ($enableBehaviourLetters == 'N') { echo $row['name'].'.disable() ;'; } ?>
					</script>
				</td>
			</tr>
			<tr id='behaviourLettersLetter2TextRow' <?php if ($enableBehaviourLetters == 'N') { echo " style='display: none'"; } ?>>
				<?php
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonSetting WHERE scope='Behaviour' AND name='behaviourLettersLetter2Text'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {}
                $row = $result->fetch();
                ?>
				<td>
					<b><?php echo __($guid, $row['nameDisplay']) ?> *</b><br/>
					<span class="emphasis small"><?php if ($row['description'] != '') { echo __($guid, $row['description']);}?></span>
				</td>
				<td class="right">
					<textarea name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" type="text" class="standardWidth" rows=4><?php echo $row['value'] ?></textarea>
					<script type="text/javascript">
						var <?php echo $row['name'] ?>=new LiveValidation('<?php echo $row['name'] ?>');
						<?php echo $row['name'] ?>.add(Validate.Presence);
						<?php if ($enableBehaviourLetters == 'N') { echo $row['name'].'.disable() ;'; } ?>
					</script>
				</td>
			</tr>
			<tr id='behaviourLettersLetter3CountRow' <?php if ($enableBehaviourLetters == 'N') { echo " style='display: none'"; } ?>>
				<?php
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonSetting WHERE scope='Behaviour' AND name='behaviourLettersLetter3Count'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {}
                $row = $result->fetch();
                ?>
				<td>
					<b><?php echo __($guid, $row['nameDisplay']) ?> *</b><br/>
					<span class="emphasis small"><?php if ($row['description'] != '') { echo __($guid, $row['description']);}?></span>
				</td>
				<td class="right">
					<select name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" class="standardWidth">
						<option value="Please select..."><?php echo __($guid, 'Please select...') ?></option>
						<?php
                        for ($i = 1; $i <= 20; ++$i) {
                            ?>
							<option <?php if ($i == $row['value']) { echo 'selected'; } ?> value="<?php echo $i ?>"><?php echo $i ?></option>
						<?php

                        }
   				 		?>
					</select>
					<script type="text/javascript">
						var <?php echo $row['name'] ?>=new LiveValidation('<?php echo $row['name'] ?>');
						<?php echo $row['name'] ?>.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
						<?php if ($enableBehaviourLetters == 'N') { echo $row['name'].'.disable() ;'; } ?>
					</script>
				</td>
			</tr>
			<tr id='behaviourLettersLetter3TextRow' <?php if ($enableBehaviourLetters == 'N') { echo " style='display: none'"; } ?>>
				<?php
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonSetting WHERE scope='Behaviour' AND name='behaviourLettersLetter3Text'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {}
                $row = $result->fetch();
                ?>
				<td>
					<b><?php echo __($guid, $row['nameDisplay']) ?> *</b><br/>
					<span class="emphasis small"><?php if ($row['description'] != '') { echo __($guid, $row['description']);}?></span>
				</td>
				<td class="right">
					<textarea name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" type="text" class="standardWidth" rows=4><?php echo $row['value'] ?></textarea>
					<script type="text/javascript">
						var <?php echo $row['name'] ?>=new LiveValidation('<?php echo $row['name'] ?>');
						<?php echo $row['name'] ?>.add(Validate.Presence);
						<?php if ($enableBehaviourLetters == 'N') { echo $row['name'].'.disable() ;'; } ?>
					</script>
				</td>
			</tr>


			<tr class='break'>
				<td colspan=2>
					<h3><?php echo __($guid, 'Miscellaneous') ?></h3>
				</td>
			</tr>
			<tr>
				<?php
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonSetting WHERE scope='Behaviour' AND name='policyLink'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {}
                $row = $result->fetch();
                ?>
				<td>
					<b><?php echo __($guid, $row['nameDisplay']) ?></b><br/>
					<span class="emphasis small"><?php if ($row['description'] != '') { echo __($guid, $row['description']);}?></span>
				</td>
				<td class="right">
					<input type='text' name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>"class="standardWidth" value='<?php echo htmlPrep($row['value']) ?>'>
					<script type="text/javascript">
						var <?php echo $row['name'] ?>=new LiveValidation('<?php echo $row['name'] ?>');
						<?php echo $row['name'] ?>.add( Validate.Format, { pattern: /(http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/, failureMessage: "Must start with http:// or https://" } );
					</script>
				</td>
			</tr>

			<tr>
				<td>
					<span class="emphasis small">* <?php echo __($guid, 'denotes a required field'); ?></span>
				</td>
				<td class="right">
					<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
					<input type="submit" value="<?php echo __($guid, 'Submit'); ?>">
				</td>
			</tr>
		</table>
	</form>
<?php

}
?>
