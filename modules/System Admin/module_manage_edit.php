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

if (isActionAccessible($guid, $connection2, '/modules/System Admin/module_manage_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/module_manage.php'>".__($guid, 'Manage Modules')."</a> > </div><div class='trailEnd'>".__($guid, 'Edit Module').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    //Check if school year specified
    $gibbonModuleID = $_GET['gibbonModuleID'];
    if ($gibbonModuleID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonModuleID' => $gibbonModuleID);
            $sql = 'SELECT * FROM gibbonModule WHERE gibbonModuleID=:gibbonModuleID';
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
            $row = $result->fetch();
            ?>
			<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/module_manage_editProcess.php?gibbonModuleID=$gibbonModuleID" ?>">
				<table class='smallIntBorder fullWidth' cellspacing='0'>	
					<tr>
						<td style='width: 275px'> 
							<b><?php echo __($guid, 'Name') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'This value cannot be changed.') ?></span>
						</td>
						<td class="right">
							<input readonly name="name" id="name" maxlength=20 value="<?php echo htmlPrep(__($guid, $row['name'])) ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var name2=new LiveValidation('name');
								name2.add(Validate.Presence);
							</script> 
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Description') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'This value cannot be changed.') ?></span>
						</td>
						<td class="right">
							<input readonly name="description" id="description" maxlength=100 value="<?php echo htmlPrep(__($guid, $row['description'])) ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var description=new LiveValidation('description');
								description.add(Validate.Presence);
							</script> 
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Category') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Determines menu structure') ?></span>
						</td>
						<td class="right">
							<input name="category" id="category" maxlength=10 value="<?php echo htmlPrep(__($guid, $row['category'])) ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var category=new LiveValidation('category');
								category.add(Validate.Presence);
							</script> 
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Active') ?> *</b><br/>
							<span class="emphasis small"></span>
						</td>
						<td class="right">
							<select name="active" id="active" class="standardWidth">
								<option <?php if ($row['active'] == 'Y') {
    echo 'selected ';
}
            ?>value="Y"><?php echo __($guid, 'Yes') ?></option>
								<option <?php if ($row['active'] == 'N') {
    echo 'selected ';
}
            ?>value="N"><?php echo __($guid, 'No') ?></option>
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
    }
}
?>