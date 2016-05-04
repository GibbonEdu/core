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

if (isActionAccessible($guid, $connection2, '/modules/Staff/jobOpenings_manage_add.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Staff/jobOpenings_manage.php'>".__($guid, 'Manage Job Openings')."</a> > </div><div class='trailEnd'>".__($guid, 'Add Job Opening').'</div>';
    echo '</div>';

    $editLink = '';
    if (isset($_GET['editID'])) {
        $editLink = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Staff/jobOpenings_manage_edit.php&gibbonStaffJobOpeningID='.$_GET['editID'];
    }
    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], $editLink, null);
    }

    ?>
	<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/jobOpenings_manage_addProcess.php' ?>">
		<table class='smallIntBorder fullWidth' cellspacing='0'>
			<tr>
				<td>
					<b><?php echo __($guid, 'Type') ?> *</b><br/>
				</td>
				<td class="right">
					<select name="type" id="type" class="standardWidth">
						<?php
                        echo '<option value="Please select...">'.__($guid, 'Please select...').'</option>';
                        echo "<optgroup label='--".__($guid, 'Basic')."--'>";
                            echo '<option value="Teaching">'.__($guid, 'Teaching').'</option>';
                            echo '<option value="Support">'.__($guid, 'Support').'</option>';
                        echo '</optgroup>';
                        echo "<optgroup label='--".__($guid, 'System Roles')."--'>";
                            try {
                                $dataSelect = array();
                                $sqlSelect = "SELECT * FROM gibbonRole WHERE category='Staff' ORDER BY name";
                                $resultSelect = $connection2->prepare($sqlSelect);
                                $resultSelect->execute($dataSelect);
                            } catch (PDOException $e) {
                            }
                            while ($rowSelect = $resultSelect->fetch()) {
                                echo '<option value="'.$rowSelect['name'].'">'.__($guid, $rowSelect['name']).'</option>';
                            }
                        echo '</optgroup>';
                        ?>
					</select>
					<script type="text/javascript">
						var type=new LiveValidation('type');
						type.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
					</script>
				</td>
			</tr>
			<tr>
				<td>
					<b><?php echo __($guid, 'Job Title') ?> *</b><br/>
				</td>
				<td class="right">
					<input name="jobTitle" id="jobTitle" maxlength=100 value="" type="text" class="standardWidth">
					<script type="text/javascript">
						var jobTitle=new LiveValidation('jobTitle');
						jobTitle.add(Validate.Presence);
					</script>
				</td>
			</tr>
			<tr>
				<td>
					<b><?php echo __($guid, 'Opening Date') ?> *</b><br/>
					<span class="emphasis small"><?php echo __($guid, 'Format:').' ';
    if ($_SESSION[$guid]['i18n']['dateFormat'] == '') {
        echo 'dd/mm/yyyy';
    } else {
        echo $_SESSION[$guid]['i18n']['dateFormat'];
    }
    ?></span>
				</td>
				<td class="right">
					<input name="dateOpen" id="dateOpen" maxlength=10 value="" type="text" class="standardWidth">
					<script type="text/javascript">
						var dateOpen=new LiveValidation('dateOpen');
						dateOpen.add(Validate.Presence);
						$(function() {
							$( "#dateOpen" ).datepicker();
						});
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
			<tr>
				<td colspan=2>
					<b><?php echo __($guid, 'Description') ?> *</b>
					<?php
                    //Attempt to build a signature for the user
                    $jobOpeningDescriptionTemplate = getSettingByScope($connection2, 'Staff', 'jobOpeningDescriptionTemplate');
    echo getEditor($guid,  true, 'description', $jobOpeningDescriptionTemplate, 20, true, true, false, true); ?>
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
