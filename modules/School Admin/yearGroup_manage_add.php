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

if (isActionAccessible($guid, $connection2, '/modules/School Admin/yearGroup_manage_add.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/yearGroup_manage.php'>".__($guid, 'Manage Year Groups')."</a> > </div><div class='trailEnd'>".__($guid, 'Add Year Group').'</div>';
    echo '</div>';

    $editLink = '';
    if (isset($_GET['editID'])) {
        $editLink = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/School Admin/yearGroup_manage_edit.php&gibbonYearGroupID='.$_GET['editID'];
    }
    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], $editLink, null);
    }

    ?>
	<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/yearGroup_manage_addProcess.php' ?>">
		<table class='smallIntBorder fullWidth' cellspacing='0'>	
			<tr>
				<td style='width: 275px'> 
					<b><?php echo __($guid, 'Name') ?> *</b><br/>
					<span class="emphasis small"><?php echo __($guid, 'Must be unique.') ?></span>
				</td>
				<td class="right">
					<input name="name" id="name" value="" type="text" class="standardWidth">
					<script type="text/javascript">
						var name2=new LiveValidation('name');
						name2.add(Validate.Presence);
					</script>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php echo __($guid, 'Short Name') ?> *</b><br/>
					<span class="emphasis small"><?php echo __($guid, 'Must be unique.') ?></span>
				</td>
				<td class="right">
					<input name="nameShort" id="nameShort" value="" type="text" class="standardWidth">
					<script type="text/javascript">
						var nameShort=new LiveValidation('nameShort');
						nameShort.add(Validate.Presence);
					</script>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php echo __($guid, 'Sequence Number') ?> *</b><br/>
					<span class="emphasis small"><?php echo __($guid, 'Must be unique. Controls chronological ordering.') ?></span>
				</td>
				<td class="right">
					<input name="sequenceNumber" ID="sequenceNumber" value="" type="text" class="standardWidth">
					<script type="text/javascript">
						var sequenceNumber=new LiveValidation('sequenceNumber');
						sequenceNumber.add(Validate.Numericality);
					 	sequenceNumber.add(Validate.Presence);
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