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

if (isActionAccessible($guid, $connection2, '/modules/School Admin/space_manage_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/space_manage.php'>".__($guid, 'Manage Facilities')."</a> > </div><div class='trailEnd'>".__($guid, 'Edit Facility').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    //Check if school year specified
    $gibbonSpaceID = $_GET['gibbonSpaceID'];
    if ($gibbonSpaceID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonSpaceID' => $gibbonSpaceID);
            $sql = 'SELECT * FROM gibbonSpace WHERE gibbonSpaceID=:gibbonSpaceID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __($guid, 'The specified record cannot be found.');
            echo '</div>';
        } else {
            //Let's go!
            $row = $result->fetch(); ?>
			<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/space_manage_editProcess.php?gibbonSpaceID='.$gibbonSpaceID ?>">
				<table class='smallIntBorder fullWidth' cellspacing='0'>
					<tr>
						<td style='width: 275px'>
							<b><?php echo __($guid, 'Name') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Must be unique.'); ?></span>
						</td>
						<td class="right">
							<input name="name" id="name" maxlength=30 value="<?php echo htmlPrep($row['name']) ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var name2=new LiveValidation('name');
								name2.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<?php
                    $types = getSettingByScope($connection2, 'School Admin', 'facilityTypes');
            		$types = explode(',', $types); ?>
					<tr>
						<td>
							<b><?php echo __($guid, 'Type') ?> *</b><br/>
							<span class="emphasis small"></span>
						</td>
						<td class="right">
							<select name="type" id="type" class="standardWidth">
								<option value="Please select..."><?php echo __($guid, 'Please select...') ?></option>
								<?php
                                for ($i = 0; $i < count($types); ++$i) {
                                    $selected = '';
                                    if ($row['type'] == $types[$i]) {
                                        $selected = 'selected';
                                    }
                                    ?>
									<option <?php echo $selected ?> value="<?php echo trim($types[$i]) ?>"><?php echo trim($types[$i]) ?></option>
								<?php

                                }
           	 					?>
							</select>
							<script type="text/javascript">
								var type=new LiveValidation('type');
								type.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
							</script>
						</td>
					</tr>
					<tr>
						<td>
							<b><?php echo __($guid, 'Capacity') ?></b><br/>
							<span class="emphasis small"></span>
						</td>
						<td class="right">
							<input name="capacity" id="capacity" maxlength=5 value="<?php echo htmlPrep($row['capacity']) ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var capacity=new LiveValidation('capacity');
								capacity.add(Validate.Numericality);
							</script>
						</td>
					</tr>
					<tr>
						<td>
							<b><?php echo __($guid, 'Teacher\'s Computer') ?> *</b><br/>
						</td>
						<td class="right">
							<select name="computer" id="computer" class="standardWidth">
								<option <?php if ($row['computer'] == 'N') { echo 'selected '; } ?>value="N">N</option>
								<option <?php if ($row['computer'] == 'Y') { echo 'selected '; } ?>value="Y">Y</option>
							</select>
						</td>
					</tr>
					<tr>
						<td>
							<b><?php echo __($guid, 'Student Computers') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'How many are there?') ?></span>
						</td>
						<td class="right">
							<input name="computerStudent" id="computerStudent" maxlength=5 value="<?php echo htmlPrep($row['computerStudent']) ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var computerStudent=new LiveValidation('computerStudent');
								computerStudent.add(Validate.Numericality);
							</script>
						</td>
					</tr>
					<tr>
						<td>
							<b><?php echo __($guid, 'Projector') ?> *</b><br/>
						</td>
						<td class="right">
							<select name="projector" id="projector" class="standardWidth">
								<option <?php if ($row['projector'] == 'N') { echo 'selected '; } ?>value="N">N</option>
								<option <?php if ($row['projector'] == 'Y') { echo 'selected '; } ?>value="Y">Y</option>
							</select>
						</td>
					</tr>

					<tr>
						<td>
							<b><?php echo __($guid, 'TV') ?> *</b><br/>
						</td>
						<td class="right">
							<select name="tv" id="tv" class="standardWidth">
								<option <?php if ($row['tv'] == 'N') { echo 'selected '; } ?>value="N">N</option>
								<option <?php if ($row['tv'] == 'Y') { echo 'selected '; } ?>value="Y">Y</option>
							</select>
						</td>
					</tr>

					<tr>
						<td>
							<b><?php echo __($guid, 'DVD Player') ?> *</b><br/>
						</td>
						<td class="right">
							<select name="dvd" id="dvd" class="standardWidth">
								<option <?php if ($row['dvd'] == 'N') { echo 'selected '; } ?>value="N">N</option>
								<option <?php if ($row['dvd'] == 'Y') { echo 'selected '; } ?>value="Y">Y</option>
							</select>
						</td>
					</tr>

					<tr>
						<td>
							<b><?php echo __($guid, 'Hifi') ?> *</b><br/>
						</td>
						<td class="right">
							<select name="hifi" id="hifi" class="standardWidth">
								<option <?php if ($row['hifi'] == 'N') { echo 'selected '; } ?>value="N">N</option>
								<option <?php if ($row['hifi'] == 'Y') { echo 'selected '; } ?>value="Y">Y</option>
							</select>
						</td>
					</tr>

					<tr>
						<td>
							<b><?php echo __($guid, 'Speakers') ?> *</b><br/>
						</td>
						<td class="right">
							<select name="speakers" id="speakers" class="standardWidth">
								<option <?php if ($row['speakers'] == 'N') { echo 'selected '; } ?>value="N">N</option>
								<option <?php if ($row['speakers'] == 'Y') { echo 'selected '; } ?>value="Y">Y</option>
							</select>
						</td>
					</tr>

					<tr>
						<td>
							<b><?php echo __($guid, 'Interactive White Board') ?> *</b><br/>
						</td>
						<td class="right">
							<select name="iwb" id="iwb" class="standardWidth">
								<option <?php if ($row['iwb'] == 'N') { echo 'selected '; } ?>value="N">N</option>
								<option <?php if ($row['iwb'] == 'Y') { echo 'selected '; } ?>value="Y">Y</option>
							</select>
						</td>
					</tr>
					<tr>
						<td>
							<b><?php echo __($guid, 'Extension') ?></b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Room\'s internal phone number.') ?></span>
						</td>
						<td class="right">
							<input name="phoneInternal" id="phoneInternal" maxlength=5 value="<?php echo htmlPrep($row['phoneInternal']) ?>" type="text" class="standardWidth">
						</td>
					</tr>
					<tr>
						<td>
							<b><?php echo __($guid, 'Phone Number') ?></b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Room\'s external phone number.') ?></span>
						</td>
						<td class="right">
							<input name="phoneExternal" id="phoneExternal" maxlength=20 value="<?php echo htmlPrep($row['phoneExternal']) ?>" type="text" class="standardWidth">
						</td>
					</tr>
					<tr>
						<td>
							<b><?php echo __($guid, 'Comment') ?></b><br/>
						</td>
						<td class="right">
							<textarea name="comment" id="comment" rows=8 class="standardWidth"><?php echo $row['comment'] ?></textarea>
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
    }
}
?>
