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

if (isActionAccessible($guid, $connection2, '/modules/Finance/budgets_manage_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/budgets_manage.php'>".__($guid, 'Manage Budgets')."</a> > </div><div class='trailEnd'>".__($guid, 'Edit Budget').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, array('error3' => 'Your request failed because some inputs did not meet a requirement for uniqueness.', 'error4' => 'Your request failed due to an attachment error.'));
    }

    //Check if school year specified
    $gibbonFinanceBudgetID = $_GET['gibbonFinanceBudgetID'];
    if ($gibbonFinanceBudgetID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonFinanceBudgetID' => $gibbonFinanceBudgetID);
            $sql = 'SELECT * FROM gibbonFinanceBudget WHERE gibbonFinanceBudgetID=:gibbonFinanceBudgetID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __($guid, 'The specified record does not exist.');
            echo '</div>';
        } else {
            //Let's go!
            $row = $result->fetch(); ?>
			<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/budgets_manage_editProcess.php?gibbonFinanceBudgetID=$gibbonFinanceBudgetID" ?>">
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
							<input name="name" id="name" maxlength=100 value="<?php echo $row['name'] ?>" type="text" class="standardWidth">
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
							<input name="nameShort" id="nameShort" maxlength=14 value="<?php echo $row['nameShort'] ?>" type="text" class="standardWidth">
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
								<option <?php if ($row['active'] == 'Y') { echo 'selected'; } ?> value="Y"><?php echo __($guid, 'Yes') ?></option>
								<option <?php if ($row['active'] == 'N') { echo 'selected'; } ?> value="N"><?php echo __($guid, 'No') ?></option>
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
                                        $selected = '';
                                        if (trim($categories[$i]) == $row['category']) {
                                            $selected = 'selected';
                                        }
                                        echo "<option $selected value=\"".trim($categories[$i]).'">'.trim($categories[$i]).'</option>';
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
							<h3><?php echo __($guid, 'Current Staff') ?></h3>
						</td>
					</tr>
					<tr>
						<td colspan=2>
							<?php
                            try {
                                $data = array('gibbonFinanceBudgetID' => $gibbonFinanceBudgetID);
                                $sql = "SELECT preferredName, surname, gibbonFinanceBudgetPerson.* FROM gibbonFinanceBudgetPerson JOIN gibbonPerson ON (gibbonFinanceBudgetPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonFinanceBudgetID=:gibbonFinanceBudgetID AND gibbonPerson.status='Full' ORDER BY FIELD(access,'Full','Write','Read'), surname, preferredName";
                                $result = $connection2->prepare($sql);
                                $result->execute($data);
                            } catch (PDOException $e) {
                                echo "<div class='error'>".$e->getMessage().'</div>';
                            }

							if ($result->rowCount() < 1) {
								echo "<div class='error'>";
								echo __($guid, 'There are no records to display.');
								echo '</div>';
							} else {
								echo '<i><b>Warning</b>: If you delete a member of staff, any unsaved changes to this record will be lost!</i>';
								echo "<table cellspacing='0' style='width: 100%'>";
								echo "<tr class='head'>";
								echo '<th>';
								echo __($guid, 'Name');
								echo '</th>';
								echo '<th>';
								echo __($guid, 'Access');
								echo '</th>';
								echo '<th>';
								echo __($guid, 'Action');
								echo '</th>';
								echo '</tr>';

								$count = 0;
								$rowNum = 'odd';
								while ($row = $result->fetch()) {
									if ($count % 2 == 0) {
										$rowNum = 'even';
									} else {
										$rowNum = 'odd';
									}
									++$count;

									//COLOR ROW BY STATUS!
									echo "<tr class=$rowNum>";
									echo '<td>';
									echo formatName('', $row['preferredName'], $row['surname'], 'Staff', true, true);
									echo '</td>';
									echo '<td>';
									echo $row['access'];
									echo '</td>';
									echo '<td>';
									echo "<a onclick='return confirm(\"Are you sure you want to delete this record? Unsaved changes will be lost.\")' href='".$_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/budgets_manage_edit_staff_deleteProcess.php?address='.$_GET['q'].'&gibbonFinanceBudgetPersonID='.$row['gibbonFinanceBudgetPersonID']."&gibbonFinanceBudgetID=$gibbonFinanceBudgetID'><img title='".__($guid, 'Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a>";
									echo '</td>';
									echo '</tr>';
								}
								echo '</table>';
							}
							?>
						</td>
					</tr>
					<tr class='break'>
						<td colspan=2>
							<h3><?php echo __($guid, 'New Staff') ?></h3>
						</td>
					</tr>
					<tr>
					<td>
						<b>Staff</b><br/>
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

					<tr id='roleLARow'>
						<td>
							<b><?php echo __($guid, 'Role') ?></b><br/>
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
    }
}
?>
