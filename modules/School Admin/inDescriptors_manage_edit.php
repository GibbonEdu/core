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

if (isActionAccessible($guid, $connection2, '/modules/School Admin/inDescriptors_manage_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/inDescriptors_manage.php'>".__($guid, 'Manage Individual Needs Descriptors')."</a> > </div><div class='trailEnd'>".__($guid, 'Edit Descriptor').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    //Check if school year specified
    $gibbonINDescriptorID = $_GET['gibbonINDescriptorID'];
    if ($gibbonINDescriptorID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonINDescriptorID' => $gibbonINDescriptorID);
            $sql = 'SELECT * FROM gibbonINDescriptor WHERE gibbonINDescriptorID=:gibbonINDescriptorID';
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
			<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/inDescriptors_manage_editProcess.php?gibbonINDescriptorID='.$gibbonINDescriptorID ?>">
				<table class='smallIntBorder fullWidth' cellspacing='0'>	
					<tr>
						<td style='width: 275px'> 
							<b><?php echo __($guid, 'Name') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Must be unique.'); ?></span>
						</td>
						<td class="right">
							<input name="name" id="name" maxlength=50 value="<?php echo __($guid, $row['name']) ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var name2=new LiveValidation('name');
								name2.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Short Name') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Must be unique.'); ?></span>
						</td>
						<td class="right">
							<input name="nameShort" id="nameShort" maxlength=5 value="<?php echo __($guid, $row['nameShort']) ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var nameShort=new LiveValidation('nameShort');
								nameShort.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Sequence Number') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Must be unique.'); ?></span>
						</td>
						<td class="right">
							<input name="sequenceNumber" id="sequenceNumber" maxlength=5 value="<?php echo $row['sequenceNumber'] ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var sequenceNumber=new LiveValidation('sequenceNumber');
								sequenceNumber.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Description') ?></b><br/>
						</td>
						<td class="right">
							<textarea name="description" id="description" rows=8 class="standardWidth"><?php echo __($guid, $row['description']) ?></textarea>
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