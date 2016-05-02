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

if (isActionAccessible($guid, $connection2, '/modules/School Admin/space_manage_add.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/space_manage.php'>".__($guid, 'Manage Facilities')."</a> > </div><div class='trailEnd'>".__($guid, 'Add Facility').'</div>';
    echo '</div>';

    $editLink = '';
    if (isset($_GET['editID'])) {
        $editLink = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/School Admin/space_manage_edit.php&gibbonSpaceID='.$_GET['editID'];
    }
    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], $editLink, null);
    }

    ?>
	<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/space_manage_addProcess.php' ?>">
		<table class='smallIntBorder fullWidth' cellspacing='0'>	
			<tr>
				<td style='width: 275px'> 
					<b><?php echo __($guid, 'Name') ?> *</b><br/>
					<span class="emphasis small"><?php echo __($guid, 'Must be unique.');
    ?></span>
				</td>
				<td class="right">
					<input name="name" id="name" maxlength=30 value="" type="text" class="standardWidth">
					<script type="text/javascript">
						var name2=new LiveValidation('name');
						name2.add(Validate.Presence);
					</script>
				</td>
			</tr>
			<?php
            $types = getSettingByScope($connection2, 'School Admin', 'facilityTypes');
    $types = explode(',', $types);
    ?>
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
                            ?>
							<option value="<?php echo trim($types[$i]) ?>"><?php echo trim($types[$i]) ?></option>
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
					<b><?php echo __($guid, 'User 1') ?></b>
				</td>
				<td class="right">
					<select class="standardWidth" name="gibbonPersonID1">
						<?php
                        echo "<option value=''></option>";
    try {
        $data = array();
        $sql = "SELECT * FROM gibbonPerson JOIN gibbonStaff ON (gibbonPerson.gibbonPersonID=gibbonStaff.gibbonPersonID) WHERE status='Full' ORDER BY surname, preferredName";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }
    while ($row = $result->fetch()) {
        echo "<option value='".$row['gibbonPersonID']."'>".formatName('', htmlPrep($row['preferredName']), htmlPrep($row['surname']), 'Staff', true, true).'</option>';
    }
    ?>				
					</select>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php echo __($guid, 'User 2') ?></b>
				</td>
				<td class="right">
					<select class="standardWidth" name="gibbonPersonID2">
						<?php
                        echo "<option value=''></option>";
    try {
        $data = array();
        $sql = "SELECT * FROM gibbonPerson JOIN gibbonStaff ON (gibbonPerson.gibbonPersonID=gibbonStaff.gibbonPersonID) WHERE status='Full' ORDER BY surname, preferredName";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }
    while ($row = $result->fetch()) {
        echo "<option value='".$row['gibbonPersonID']."'>".formatName('', htmlPrep($row['preferredName']), htmlPrep($row['surname']), 'Staff', true, true).'</option>';
    }
    ?>				
					</select>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php echo __($guid, 'Capacity') ?></b><br/>
					<span class="emphasis small"></span>
				</td>
				<td class="right">
					<input name="capacity" id="capacity" maxlength=5 value="0" type="text" class="standardWidth">
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
						<option value="N"><?php echo __($guid, 'No') ?></option>
						<option value="Y"><?php echo __($guid, 'Yes') ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php echo __($guid, 'Student Computers') ?> *</b><br/>
					<span class="emphasis small"><?php echo __($guid, 'How many are there') ?></span>
				</td>
				<td class="right">
					<input name="computerStudent" id="computerStudent" maxlength=5 value="0" type="text" class="standardWidth">
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
						<option value="N"><?php echo __($guid, 'No') ?></option>
						<option value="Y"><?php echo __($guid, 'Yes') ?></option>
					</select>
				</td>
			</tr>
			
			<tr>
				<td> 
					<b><?php echo __($guid, 'TV') ?> *</b><br/>
				</td>
				<td class="right">
					<select name="tv" id="tv" class="standardWidth">
						<option value="N"><?php echo __($guid, 'No') ?></option>
						<option value="Y"><?php echo __($guid, 'Yes') ?></option>
					</select>
				</td>
			</tr>
			
			<tr>
				<td> 
					<b><?php echo __($guid, 'DVD Player') ?> *</b><br/>
				</td>
				<td class="right">
					<select name="dvd" id="dvd" class="standardWidth">
						<option value="N"><?php echo __($guid, 'No') ?></option>
						<option value="Y"><?php echo __($guid, 'Yes') ?></option>
					</select>
				</td>
			</tr>
			
			<tr>
				<td> 
					<b><?php echo __($guid, 'Hifi') ?> *</b><br/>
				</td>
				<td class="right">
					<select name="hifi" id="hifi" class="standardWidth">
						<option value="N"><?php echo __($guid, 'No') ?></option>
						<option value="Y"><?php echo __($guid, 'Yes') ?></option>
					</select>
				</td>
			</tr>
			
			<tr>
				<td> 
					<b><?php echo __($guid, 'Speakers') ?> *</b><br/>
				</td>
				<td class="right">
					<select name="speakers" id="speakers" class="standardWidth">
						<option value="N"><?php echo __($guid, 'No') ?></option>
						<option value="Y"><?php echo __($guid, 'Yes') ?></option>
					</select>
				</td>
			</tr>
			
			<tr>
				<td> 
					<b><?php echo __($guid, 'Interactive White Board') ?> *</b><br/>
				</td>
				<td class="right">
					<select name="iwb" id="iwb" class="standardWidth">
						<option value="N"><?php echo __($guid, 'No') ?></option>
						<option value="Y"><?php echo __($guid, 'Yes') ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php echo __($guid, 'Extension') ?></b><br/>
					<span class="emphasis small"><?php echo __($guid, 'Room\'s internal phone number.') ?></span>
				</td>
				<td class="right">
					<input name="phoneInternal" id="phoneInternal" maxlength=5 value="<?php echo $row['phoneInternal'] ?>" type="text" class="standardWidth">
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php echo __($guid, 'Phone Number') ?></b><br/>
					<span class="emphasis small"><?php echo __($guid, 'Room\'s external phone number.') ?></span>
				</td>
				<td class="right">
					<input name="phoneExternal" id="phoneExternal" maxlength=20 value="<?php echo $row['phoneExternal'] ?>" type="text" class="standardWidth">
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
					<span class="emphasis small">* <?php echo __($guid, 'denotes a required field');
    ?></span>
				</td>
				<td class="right">
					<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
					<input type="submit" value="<?php echo __($guid, 'Submit');
    ?>">
				</td>
			</tr>
		</table>
	</form>
	<?php

}
?>