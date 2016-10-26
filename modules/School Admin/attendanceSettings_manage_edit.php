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

if (isActionAccessible($guid, $connection2, '/modules/School Admin/attendanceSettings_manage_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
	//Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/attendanceSettings.php'>".__($guid, 'Attendance Settings')."</a> > </div><div class='trailEnd'>".__($guid, 'Edit Attendance Code').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $gibbonAttendanceCodeID = (isset($_GET['gibbonAttendanceCodeID']))? $_GET['gibbonAttendanceCodeID'] : NULL;

    if (empty($gibbonAttendanceCodeID)) {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {

	    try {
	        $data = array('gibbonAttendanceCodeID' => $gibbonAttendanceCodeID);
	        $sql = 'SELECT * FROM gibbonAttendanceCode WHERE gibbonAttendanceCodeID=:gibbonAttendanceCodeID';
	        $result = $connection2->prepare($sql);
	        $result->execute($data);
	    } catch (PDOException $e) {
	        echo "<div class='error'>".$e->getMessage().'</div>';
	    }

	    if ($result->rowCount() != 1) {
	        echo "<div class='error'>";
	        echo __($guid, 'The selected record does not exist, or you do not have access to it.');
	        echo '</div>';
	    } else {
	        //Let's go!
	        $row = $result->fetch(); ?>

			<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/attendanceSettings_manage_editProcess.php?gibbonAttendanceCodeID='.$gibbonAttendanceCodeID ?>" enctype="multipart/form-data">
				<table class='smallIntBorder fullWidth' cellspacing='0'>	
					<tr>
						<td> 
							<b><?php echo __($guid, 'Name') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Must be unique.') ?></span>
						</td>
						<td class="right">
							<input name="name" id="name" maxlength=30  value="<?php echo $row['name'] ?>" type="text" class="standardWidth">
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
							<input name="nameShort" id="nameShort" maxlength=4 value="<?php echo $row['nameShort'] ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var nameShort=new LiveValidation('nameShort');
								nameShort.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td style='width: 275px'> 
							<b><?php echo __($guid, 'Direction') ?> *</b><br/>
						</td>
						<td class="right">
							<select name="direction" id="direction" class='type standardWidth'>
								<option value='In' <?php if ($row['direction'] == 'In') echo 'selected'; ?>><?php echo __($guid, 'In Class'); ?></option>
								<option value='Out' <?php if ($row['direction'] == 'Out') echo 'selected'; ?>><?php echo __($guid, 'Out of Class'); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<td style='width: 275px'> 
							<b><?php echo __($guid, 'Scope') ?> *</b><br/>
						</td>
						<td class="right">
							<select name="scope" id="scope" class='type standardWidth'>
								<option value='Onsite' <?php if ($row['scope'] == 'Onsite') echo 'selected'; ?>><?php echo __($guid, 'Onsite'); ?></option>
								<option value='Onsite - Late' <?php if ($row['scope'] == 'Onsite - Late') echo 'selected'; ?>><?php echo __($guid, 'Onsite - Late'); ?></option>
								<option value='Offsite' <?php if ($row['scope'] == 'Offsite') echo 'selected'; ?>><?php echo __($guid, 'Offsite'); ?></option>
								<option value='Offsite - Left' <?php if ($row['scope'] == 'Offsite - Left') echo 'selected'; ?>><?php echo __($guid, 'Offsite - Left'); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Sequence Number') ?> *</b><br/>
						</td>
						<td class="right">
							<input name="sequenceNumber" id="sequenceNumber" maxlength=40 value="<?php echo $row['sequenceNumber'] ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var sequenceNumber=new LiveValidation('sequenceNumber');
								sequenceNumber.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td style='width: 275px'> 
							<b><?php echo __($guid, 'Active') ?> *</b><br/>
						</td>
						<td class="right">
							<select name="active" id="active" class='type standardWidth'>
								<option value='Y' <?php if ($row['active'] == 'Y') echo 'selected'; ?>><?php echo __($guid, 'Yes'); ?></option>
								<option value='N' <?php if ($row['active'] == 'N') echo 'selected'; ?>><?php echo __($guid, 'No'); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<td style='width: 275px'> 
							<b><?php echo __($guid, 'Reportable') ?> *</b><br/>
						</td>
						<td class="right">
							<select name="reportable" id="reportable" class='type standardWidth'>
								<option value='Y' <?php if ($row['reportable'] == 'Y') echo 'selected'; ?>><?php echo __($guid, 'Yes'); ?></option>
								<option value='N' <?php if ($row['reportable'] == 'N') echo 'selected'; ?>><?php echo __($guid, 'No'); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<td style='width: 275px'> 
							<b><?php echo __($guid, 'Allow Future Use') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Can this code be used in Set Future Absence?') ?></span>
						</td>
						<td class="right">
							<select name="future" id="future" class='type standardWidth'>
								<option value='Y' <?php if ($row['future'] == 'Y') echo 'selected'; ?>><?php echo __($guid, 'Yes'); ?></option>
								<option value='N' <?php if ($row['future'] == 'N') echo 'selected'; ?>><?php echo __($guid, 'No'); ?></option>
							</select>
						</td>
					</tr>

					<tr>
						<td> 
							<b><?php echo __($guid, 'Available to Roles') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Controls who can use this code.') ?></span>
						</td>
						<td class="right">
							<select multiple name="gibbonRoleIDAll[]" id="gibbonRoleIDAll[]" style="width: 302px; height: 130px">
								<?php
		                        try {
		                            $dataSelect = array();
		                            $sqlSelect = 'SELECT * FROM gibbonRole ORDER BY name';
		                            $resultSelect = $connection2->prepare($sqlSelect);
		                            $resultSelect->execute($dataSelect);
		                        } catch (PDOException $e) {
		                            echo "<div class='error'>".$e->getMessage().'</div>';
		                        }
								while ($rowSelect = $resultSelect->fetch()) {
									$selected = '';
									$roles = explode(',', $row['gibbonRoleIDAll']);
									foreach ($roles as $role) {
										if ($role == $rowSelect['gibbonRoleID']) {
											$selected = 'selected';
										}
									}

									echo "<option $selected value='".$rowSelect['gibbonRoleID']."'>".htmlPrep(__($guid, $rowSelect['name'])).'</option>';
								}
								?>			
							</select>
							<script type="text/javascript">
								var gibbonRoleIDPrimary=new LiveValidation('gibbonRoleIDPrimary');
								gibbonRoleIDPrimary.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
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
	}
}
?>