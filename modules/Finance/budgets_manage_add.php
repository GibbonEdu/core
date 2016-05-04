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

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Finance/budgets_manage_add.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/budgets_manage.php'>".__($guid, 'Manage Budgets')."</a> > </div><div class='trailEnd'>".__($guid, 'Add Budget').'</div>';
    echo '</div>';

    $editLink = '';
    if (isset($_GET['editID'])) {
        $editLink = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Finance/budgets_manage_edit.php&gibbonFinanceBudgetID='.$_GET['editID'];
    }
    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], $editLink, array('error3' => 'Your request failed because some inputs did not meet a requirement for uniqueness.', 'warning1' => 'Your request was successful, but some data was not properly saved.'));
    }

    ?>
	<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/budgets_manage_addProcess.php' ?>">
		<table class='smallIntBorder fullWidth' cellspacing='0'>	
			<tr class='break'>
				<td colspan=2> 
					<h3><?php echo __($guid, 'General Settings') ?></h3>
				</td>
			</tr>
			<tr>
				<td style='width: 275px'> 
					<b><?php echo __($guid, 'Name') ?> *</b><br/>
					<span class="emphasis small"><?php echo __($guid, 'Must be unique.') ?></span>
				</td>
				<td class="right">
					<input name="name" id="name" maxlength=100 value="" type="text" class="standardWidth">
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
					<input name="nameShort" id="nameShort" maxlength=14 value="" type="text" class="standardWidth">
					<script type="text/javascript">
						var nameShort=new LiveValidation('nameShort');
						nameShort.add(Validate.Presence);
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
						<option value="Y"><?php echo __($guid, 'Yes') ?></option>
						<option value="N"><?php echo __($guid, 'No') ?></option>
					</select>
				</td>
			</tr>
			<?php
            $categories = getSettingByScope($connection2, 'Finance', 'budgetCategories');
    if ($categories != false) {
        $categories = explode(',', $categories);
        ?>
				<tr>
					<td> 
						<b><?php echo __($guid, 'Category') ?> *</b><br/>
						<span class="emphasis small"></span>
					</td>
					<td class="right">
						<select name="category" id="category" class="standardWidth">
							<option value="Please select..."><?php echo __($guid, 'Please select...') ?></option>
							<?php
                            for ($i = 0; $i < count($categories); ++$i) {
                                ?>
								<option value="<?php echo trim($categories[$i]) ?>"><?php echo trim($categories[$i]) ?></option>
							<?php

                            }
        					?>	
						</select>
						<script type="text/javascript">
							var category=new LiveValidation('category');
							category.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
						</script>
					</td>
				</tr>
				<?php

    } else {
        ?>
				<tr>
					<td> 
						<b><?php echo __($guid, 'Category') ?> *</b><br/>
						<span class="emphasis small"></span>
					</td>
					<td class="right">
						<input readonly name="category" id="category" value="Other" type="text" class="standardWidth">
					</td>
				</tr>
				<?php

    }
    ?>
			<tr class='break'>
				<td colspan=2> 
					<h3><?php echo __($guid, 'Staff') ?></h3>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php echo __($guid, 'Staff') ?></b><br/>
					<span class="emphasis small"><?php echo __($guid, 'Use Control, Command and/or Shift to select multiple.') ?></span>
				</td>
				<td class="right">
					<select name="staff[]" id="staff[]" multiple style="width: 302px; height: 150px">
						<?php
                        try {
                            $dataSelect = array();
                            $sqlSelect = "SELECT * FROM gibbonPerson JOIN gibbonStaff ON (gibbonPerson.gibbonPersonID=gibbonStaff.gibbonPersonID) WHERE status='Full' ORDER BY surname, preferredName";
                            $resultSelect = $connection2->prepare($sqlSelect);
                            $resultSelect->execute($dataSelect);
                        } catch (PDOException $e) {
                        }
    while ($rowSelect = $resultSelect->fetch()) {
        echo "<option value='".$rowSelect['gibbonPersonID']."'>".formatName('', htmlPrep($rowSelect['preferredName']), htmlPrep($rowSelect['surname']), 'Staff', true, true).'</option>';
    }
    ?>
					</select>
				</td>
			<tr>
				<td> 
					<b><?php echo __($guid, 'Access') ?></b><br/>
				</td>
				<td class="right">
					<select name="access" id="access" class="standardWidth">
						<option value="Full"><?php echo __($guid, 'Full') ?></option>
						<option value="Write"><?php echo __($guid, 'Write') ?></option>
						<option value="Read"><?php echo __($guid, 'Read') ?></option>
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