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

if (isActionAccessible($guid, $connection2, '/modules/School Admin/resourceSettings.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Manage Resource Settings').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }
    ?>
	
	<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/resourceSettingsProcess.php' ?>">
		<table class='smallIntBorder fullWidth' cellspacing='0'>	
			<tr>
				<?php
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonSetting WHERE scope='Resources' AND name='categories'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {}
                $row = $result->fetch();
                ?>
				<td style='width: 275px'> 
					<b><?php echo __($guid, $row['nameDisplay']) ?> *</b><br/>
					<span class="emphasis small"><?php if ($row['description'] != '') {
    echo __($guid, $row['description']);}?></span>
				</td>
				<td class="right">
					<textarea name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" type="text" class="standardWidth" rows=4><?php echo $row['value'] ?></textarea>
					<script type="text/javascript">
						var <?php echo $row['name'] ?>=new LiveValidation('<?php echo $row['name'] ?>');
						<?php echo $row['name'] ?>.add(Validate.Presence);
					</script> 
				</td>
			</tr>
			<tr>
				<?php
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonSetting WHERE scope='Resources' AND name='purposesGeneral'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {}
                $row = $result->fetch();
                ?>
				<td> 
					<b><?php echo __($guid, $row['nameDisplay']) ?> *</b><br/>
					<span class="emphasis small"><?php if ($row['description'] != '') {
    echo __($guid, $row['description']);}?></span>
				</td>
				<td class="right">
					<textarea name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" type="text" class="standardWidth" rows=4><?php echo $row['value'] ?></textarea>
					<script type="text/javascript">
						var <?php echo $row['name'] ?>=new LiveValidation('<?php echo $row['name'] ?>');
						<?php echo $row['name'] ?>.add(Validate.Presence);
					</script> 
				</td>
			</tr>
			<tr>
				<?php
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonSetting WHERE scope='Resources' AND name='purposesRestricted'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {}
                $row = $result->fetch();
                ?>
				<td> 
					<b><?php echo __($guid, $row['nameDisplay']) ?></b><br/>
					<span class="emphasis small"><?php if ($row['description'] != '') {
    echo __($guid, $row['description']);}?></span>
				</td>
				<td class="right">
					<textarea name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" type="text" class="standardWidth" rows=4><?php echo $row['value'] ?></textarea>
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