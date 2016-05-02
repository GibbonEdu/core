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

if (isActionAccessible($guid, $connection2, '/modules/School Admin/daysOfWeek_manage.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Manage Alert Levels').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    try {
        $data = array();
        $sql = 'SELECT * FROM gibbonAlertLevel ORDER BY sequenceNumber';
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }

    //Let's go!
    ?>
	<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/alertLevelSettingsProcess.php'?>">
		<table class='smallIntBorder fullWidth' cellspacing='0'>	
			<?php
            $count = 0;
    while ($row = $result->fetch()) {
        ?>
				<tr class='break'>
					<td colspan=2> 
						<h3><?php echo __($guid, $row['name']) ?></h3>
					</td>
				</tr>
				<tr>
					<td style='width: 275px'> 
						<b><?php echo __($guid, 'Name') ?> *</b>
					</td>
					<td class="right">
						<input type='hidden' name="<?php echo 'gibbonAlertLevelID'.$count ?>" id="<?php echo 'gibbonAlertLevelID'.$count ?>" value="<?php echo $row['gibbonAlertLevelID'] ?>">
						<input type='text' name="<?php echo 'name'.$count ?>" id="<?php echo 'name'.$count ?>" maxlength=50 value="<?php echo __($guid, $row['name']) ?>" class="standardWidth">
						<script type="text/javascript">
							var <?php echo 'name'.$count ?>=new LiveValidation('<?php echo 'name'.$count ?>');
							<?php echo 'name'.$count ?>.add(Validate.Presence);
						</script>
					</td>
				</tr>
				<tr>
					<td> 
						<b><?php echo __($guid, 'Short Name') ?> *</b>
					</td>
					<td class="right">
						<input type='text' name="<?php echo 'nameShort'.$count ?>" id="<?php echo 'nameShort'.$count ?>" maxlength=4 value="<?php echo $row['nameShort'] ?>" class="standardWidth">
						<script type="text/javascript">
							var <?php echo 'nameShort'.$count ?>=new LiveValidation('<?php echo 'nameShort'.$count ?>');
							<?php echo 'nameShort'.$count ?>.add(Validate.Presence);
						</script>
					</td>
				</tr>
				<tr>
					<td> 
						<b><?php echo __($guid, 'Font/Border Color') ?> *</b><br/>
						<span class="emphasis small">RGB Hex value, without leading #.</span>
					</td>
					<td class="right">
						<input type='text' name="<?php echo 'color'.$count ?>" id="<?php echo 'color'.$count ?>" maxlength=6 value="<?php echo $row['color'] ?>" class="standardWidth">
						<script type="text/javascript">
							var <?php echo 'color'.$count ?>=new LiveValidation('<?php echo 'color'.$count ?>');
							<?php echo 'color'.$count ?>.add(Validate.Presence);
						</script>
					</td>
				</tr>
				<tr>
					<td> 
						<b><?php echo __($guid, 'Background Color') ?> *</b><br/>
						<span class="emphasis small">RGB Hex value, without leading #.</span>
					</td>
					<td class="right">
						<input type='text' name="<?php echo 'colorBG'.$count ?>" id="<?php echo 'colorBG'.$count ?>" maxlength=6 value="<?php echo $row['colorBG'] ?>" class="standardWidth">
						<script type="text/javascript">
							var <?php echo 'colorBG'.$count ?>=new LiveValidation('<?php echo 'colorBG'.$count ?>');
							<?php echo 'colorBG'.$count ?>.add(Validate.Presence);
						</script>
					</td>
				</tr>
				<tr>
					<td> 
						<b><?php echo __($guid, 'Sequence Number') ?> *</b><br/>
						<span class="emphasis small"><?php echo __($guid, 'This value cannot be changed.') ?></span>
					</td>
					<td class="right">
						<input readonly type='text' name="<?php echo 'sequenceNumber'.$count ?>" id="<?php echo 'sequenceNumber'.$count ?>" maxlength=4 value="<?php echo $row['sequenceNumber'] ?>" class="standardWidth">
					</td>
				</tr>
				<tr>
					<td colspan=2> 
						<b>Description </b> 
						<textarea name='<?php echo 'description'.$count ?>' id='<?php echo 'description'.$count ?>' rows=5 style='width: 300px'><?php echo __($guid, $row['description']) ?></textarea>
					</td>
				</tr>
				<?php
                ++$count;
    }
    ?>
			<tr>
				<td>
					<span class="emphasis small">* <?php echo __($guid, 'denotes a required field');
    ?></span>
				</td>
				<td class="right">
					<input type="hidden" name="count" value="<?php echo $count ?>">
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