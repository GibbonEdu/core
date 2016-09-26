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

if (isActionAccessible($guid, $connection2, '/modules/School Admin/dashboardSettings.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Manage Dashboard Settings').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }
    ?>

	<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/dashboardSettingsProcess.php' ?>">
		<table class='smallIntBorder fullWidth' cellspacing='0'>
			<tr>
				<?php
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonSetting WHERE scope='School Admin' AND name='staffDashboardDefaultTab'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {}
                $row = $result->fetch();
                ?>
				<td style='width: 275px'>
					<b><?php echo __($guid, $row['nameDisplay']) ?></b><br/>
					<span class="emphasis small"><?php if ($row['description'] != '') { echo __($guid, $row['description']);}?></span>
				</td>
				<td class="right">
					<select name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" class="standardWidth">
                        <option <?php if ($row['value'] == '') { echo 'selected '; } ?>value=""></option>
						<option <?php if ($row['value'] == 'Planner') { echo 'selected '; } ?>value="Planner"><?php echo __($guid, 'Planner') ?></option>
                        <?php
                        try {
                            $dataHooks = array();
                            $sqlHooks = "SELECT * FROM gibbonHook WHERE type='Staff Dashboard'";
                            $resultHooks = $connection2->prepare($sqlHooks);
                            $resultHooks->execute($dataHooks);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }
                        while ($rowHooks = $resultHooks->fetch()) {
                            $selected = '';
                            if ($row['value'] == $rowHooks['name'])
                                $selected = 'selected';
                            print '<option '.$selected.' value="'.$rowHooks['name'].'">'.__($guid, $rowHooks['name']).'</option>';
                        }
                        ?>
                    </select>
				</td>
			</tr>

            <tr>
				<?php
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonSetting WHERE scope='School Admin' AND name='studentDashboardDefaultTab'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {}
                $row = $result->fetch();
                ?>
				<td style='width: 275px'>
					<b><?php echo __($guid, $row['nameDisplay']) ?></b><br/>
					<span class="emphasis small"><?php if ($row['description'] != '') { echo __($guid, $row['description']);}?></span>
				</td>
				<td class="right">
					<select name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" class="standardWidth">
                        <option <?php if ($row['value'] == '') { echo 'selected '; } ?>value=""></option>
						<option <?php if ($row['value'] == 'Planner') { echo 'selected '; } ?>value="Planner"><?php echo __($guid, 'Planner') ?></option>
                        <?php
                        try {
                            $dataHooks = array();
                            $sqlHooks = "SELECT * FROM gibbonHook WHERE type='Student Dashboard'";
                            $resultHooks = $connection2->prepare($sqlHooks);
                            $resultHooks->execute($dataHooks);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }
                        while ($rowHooks = $resultHooks->fetch()) {
                            $selected = '';
                            if ($row['value'] == $rowHooks['name'])
                                $selected = 'selected';
                            print '<option '.$selected.' value="'.$rowHooks['name'].'">'.__($guid, $rowHooks['name']).'</option>';
                        }
                        ?>
                    </select>
				</td>
			</tr>

            <tr>
				<?php
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonSetting WHERE scope='School Admin' AND name='parentDashboardDefaultTab'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {}
                $row = $result->fetch();
                ?>
				<td style='width: 275px'>
					<b><?php echo __($guid, $row['nameDisplay']) ?></b><br/>
					<span class="emphasis small"><?php if ($row['description'] != '') { echo __($guid, $row['description']);}?></span>
				</td>
				<td class="right">
					<select name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" class="standardWidth">
                        <option <?php if ($row['value'] == '') { echo 'selected '; } ?>value=""></option>
						<option <?php if ($row['value'] == 'Learning Overview') { echo 'selected '; } ?>value="Learning Overview"><?php echo __($guid, 'Learning Overview') ?></option>
						<option <?php if ($row['value'] == 'Timetable') { echo 'selected '; } ?>value="Timetable"><?php echo __($guid, 'Timetable') ?></option>
                        <option <?php if ($row['value'] == 'Activities') { echo 'selected '; } ?>value="Activities"><?php echo __($guid, 'Activities') ?></option>
                        <?php
                        try {
                            $dataHooks = array();
                            $sqlHooks = "SELECT * FROM gibbonHook WHERE type='Parental Dashboard'";
                            $resultHooks = $connection2->prepare($sqlHooks);
                            $resultHooks->execute($dataHooks);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }
                        while ($rowHooks = $resultHooks->fetch()) {
                            $selected = '';
                            if ($row['value'] == $rowHooks['name'])
                                $selected = 'selected';
                            print '<option '.$selected.' value="'.$rowHooks['name'].'">'.__($guid, $rowHooks['name']).'</option>';
                        }
                        ?>
                    </select>
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
