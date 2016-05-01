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

if (isActionAccessible($guid, $connection2, '/modules/School Admin/activitySettings.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Manage Activity Settings').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }
    ?>
	
	<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/activitySettingsProcess.php' ?>">
		<table class='smallIntBorder fullWidth' cellspacing='0'>	
			<tr>
				<?php
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonSetting WHERE scope='Activities' AND name='dateType'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                }
    $row = $result->fetch();
    ?>
				<td style='width: 275px'> 
					<b><?php echo __($guid, $row['nameDisplay']) ?> *</b><br/>
					<span class="emphasis small"><?php if ($row['description'] != '') {
    echo __($guid, $row['description']);
}
    ?></span>
				</td>
				<td class="right">
					<select name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" class="standardWidth">
						<option <?php if ($row['value'] == 'Date') {
    echo 'selected ';
}
    ?>value="Date"><?php echo __($guid, 'Date') ?></option>
						<option <?php if ($row['value'] == 'Term') {
    echo 'selected ';
}
    ?>value="Term"><?php echo __($guid, 'Term') ?></option>
					</select>
				</td>
			</tr>
			<script type="text/javascript">
				$(document).ready(function(){
					<?php if ($row['value'] == 'Date') {
    ?> 
						$("#maxPerTermRow").css("display","none");
					<?php 
}
    ?>
							
					$("#dateType").change(function(){
						if ($('#dateType option:selected').val()=="Term" ) {
							$("#maxPerTermRow").slideDown("fast", $("#maxPerTermRow").css("display","table-row")); 
						}
						else {
							$("#maxPerTermRow").css("display","none");
						}
					 });
				});
			</script>
			<tr id='maxPerTermRow'>
				<?php
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonSetting WHERE scope='Activities' AND name='maxPerTerm'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                }
    $row = $result->fetch();
    ?>
				<td> 
					<b><?php echo __($guid, $row['nameDisplay']) ?> *</b><br/>
					<span class="emphasis small"><?php if ($row['description'] != '') {
    echo __($guid, $row['description']);
}
    ?></span>
				</td>
				<td class="right">
					<select name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" class="standardWidth">
						<option <?php if ($row['value'] == '0') {
    echo 'selected ';
}
    ?>value="0"><?php echo __($guid, '0') ?></option>
						<option <?php if ($row['value'] == '1') {
    echo 'selected ';
}
    ?>value="1"><?php echo __($guid, '1') ?></option>
						<option <?php if ($row['value'] == '2') {
    echo 'selected ';
}
    ?>value="2"><?php echo __($guid, '2') ?></option>
						<option <?php if ($row['value'] == '3') {
    echo 'selected ';
}
    ?>value="3"><?php echo __($guid, '3') ?></option>
						<option <?php if ($row['value'] == '4') {
    echo 'selected ';
}
    ?>value="4"><?php echo __($guid, '4') ?></option>
						<option <?php if ($row['value'] == '5') {
    echo 'selected ';
}
    ?>value="5"><?php echo __($guid, '5') ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<?php
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonSetting WHERE scope='Activities' AND name='access'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                }
    $row = $result->fetch();
    ?>
				<td> 
					<b><?php echo __($guid, $row['nameDisplay']) ?> *</b><br/>
					<span class="emphasis small"><?php if ($row['description'] != '') {
    echo __($guid, $row['description']);
}
    ?></span>
				</td>
				<td class="right">
					<select name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" class="standardWidth">
						<option <?php if ($row['value'] == 'None') {
    echo 'selected ';
}
    ?>value="None"><?php echo __($guid, 'None') ?></option>
						<option <?php if ($row['value'] == 'View') {
    echo 'selected ';
}
    ?>value="View"><?php echo __($guid, 'View') ?></option>
						<option <?php if ($row['value'] == 'Register') {
    echo 'selected ';
}
    ?>value="Register"><?php echo __($guid, 'Register') ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<?php
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonSetting WHERE scope='Activities' AND name='payment'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                }
    $row = $result->fetch();
    ?>
				<td> 
					<b><?php echo __($guid, $row['nameDisplay']) ?> *</b><br/>
					<span class="emphasis small"><?php if ($row['description'] != '') {
    echo __($guid, $row['description']);
}
    ?></span>
				</td>
				<td class="right">
					<select name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" class="standardWidth">
						<option <?php if ($row['value'] == 'None') {
    echo 'selected ';
}
    ?>value="None"><?php echo __($guid, 'None') ?></option>
						<option <?php if ($row['value'] == 'Single') {
    echo 'selected ';
}
    ?>value="Single"><?php echo __($guid, 'Single') ?></option>
						<option <?php if ($row['value'] == 'Per Activity') {
    echo 'selected ';
}
    ?>value="Per Activity"><?php echo __($guid, 'Per Activity') ?></option>
						<option <?php if ($row['value'] == 'Single + Per Activity') {
    echo 'selected ';
}
    ?>value="Single + Per Activity"><?php echo __($guid, 'Single + Per Activity') ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<?php
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonSetting WHERE scope='Activities' AND name='enrolmentType'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                }
    $row = $result->fetch();
    ?>
				<td> 
					<b><?php echo __($guid, $row['nameDisplay']) ?> *</b><br/>
					<span class="emphasis small"><?php if ($row['description'] != '') {
    echo __($guid, $row['description']);
}
    ?></span>
				</td>
				<td class="right">
					<select name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" class="standardWidth">
						<option <?php if ($row['value'] == 'Competitive') {
    echo 'selected ';
}
    ?>value="Competitive"><?php echo __($guid, 'Competitive') ?></option>
						<option <?php if ($row['value'] == 'Selection') {
    echo 'selected ';
}
    ?>value="Selection"><?php echo __($guid, 'Selection') ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<?php
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonSetting WHERE scope='Activities' AND name='backupChoice'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                }
    $row = $result->fetch();
    ?>
				<td> 
					<b><?php echo __($guid, $row['nameDisplay']) ?> *</b><br/>
					<span class="emphasis small"><?php if ($row['description'] != '') {
    echo __($guid, $row['description']);
}
    ?></span>
				</td>
				<td class="right">
					<select name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" class="standardWidth">
						<option <?php if ($row['value'] == 'N') {
    echo 'selected ';
}
    ?>value="N"><?php echo __($guid, 'No') ?></option>
						<option <?php if ($row['value'] == 'Y') {
    echo 'selected ';
}
    ?>value="Y"><?php echo __($guid, 'Yes') ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<?php
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonSetting WHERE scope='Activities' AND name='activityTypes'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                }
    $row = $result->fetch();
    ?>
				<td> 
					<b><?php echo __($guid, $row['nameDisplay']) ?></b><br/>
					<span class="emphasis small"><?php if ($row['description'] != '') {
    echo __($guid, $row['description']);
}
    ?></span>
				</td>
				<td class="right">
					<textarea name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" rows=4 type="text" class="standardWidth"><?php echo $row['value'] ?></textarea>
				</td>
			</tr>
			
			<tr>
				<?php
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonSetting WHERE scope='Activities' AND name='disableExternalProviderSignup'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                }
    $row = $result->fetch();
    ?>
				<td> 
					<b><?php echo __($guid, $row['nameDisplay']) ?> *</b><br/>
					<span class="emphasis small"><?php if ($row['description'] != '') {
    echo __($guid, $row['description']);
}
    ?></span>
				</td>
				<td class="right">
					<select name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" class="standardWidth">
						<option <?php if ($row['value'] == 'N') {
    echo 'selected ';
}
    ?>value="N"><?php echo __($guid, 'No') ?></option>
						<option <?php if ($row['value'] == 'Y') {
    echo 'selected ';
}
    ?>value="Y"><?php echo __($guid, 'Yes') ?></option>
					</select>
				</td>
			</tr>
			
			<tr>
				<?php
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonSetting WHERE scope='Activities' AND name='hideExternalProviderCost'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                }
    $row = $result->fetch();
    ?>
				<td> 
					<b><?php echo __($guid, $row['nameDisplay']) ?> *</b><br/>
					<span class="emphasis small"><?php if ($row['description'] != '') {
    echo __($guid, $row['description']);
}
    ?></span>
				</td>
				<td class="right">
					<select name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" class="standardWidth">
						<option <?php if ($row['value'] == 'N') {
    echo 'selected ';
}
    ?>value="N"><?php echo __($guid, 'No') ?></option>
						<option <?php if ($row['value'] == 'Y') {
    echo 'selected ';
}
    ?>value="Y"><?php echo __($guid, 'Yes') ?></option>
					</select>
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