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

if (isActionAccessible($guid, $connection2, '/modules/School Admin/attendanceSettings.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Attendance Settings').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }
    ?>

	<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/attendanceSettingsProcess.php' ?>">
		<table class='smallIntBorder fullWidth' cellspacing='0'>
			<?php
            $yearGroups = getYearGroups($connection2);
			if ($yearGroups == '') {
				echo "<tr class='break'>";
				echo '<td colspan=2>';
				echo "<div class='error'>";
				echo __($guid, 'There are no records to display.');
				echo '</div>';
				echo '</td>';
				echo '</tr>';
			} else {
        	?>
			<tr class='break'>
				<td colspan=2>
					<h3><?php echo __($guid, 'Attendance').' - '.__($guid, 'Year Groups') ?></h3>
				</td>
			</tr>
			<tr>
				<td>
					<b><?php echo __($guid, 'Enabled by Year Group'); ?></b><br/>
				</td>
				<td class="right">
					<?php
					for ($i = 0; $i < count($yearGroups); $i = $i + 2) {
						$checked = '';
						echo __($guid, $yearGroups[($i + 1)])." <input $checked type='checkbox' name='gibbonYearGroupID_'".($i) / 2 ."' value='".$yearGroups[$i]."'><br/>";
					}
					?>
				</td>
			</tr>
			<?php } ?>
			<tr class='break'>
				<td colspan=2>
					<h3><?php echo __($guid, 'Medical'); ?></h3>
				</td>
			</tr>
			<tr>
				<td>
					<b><?php echo __($guid, 'Predefined Symptoms'); ?></b><br/>
				</td>
				<td class="right">
					<textarea name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" type="text" class="standardWidth" rows=4><?php if (isset($row['value'])) { echo $row['value']; } ?></textarea>
					<script type="text/javascript">
						var <?php echo $row['name'] ?>=new LiveValidation('<?php echo $row['name'] ?>');
						<?php echo $row['name'] ?>.add(Validate.Presence);
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
