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

if (isActionAccessible($guid, $connection2, '/modules/Students/medicalForm_manage_add.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Students/medicalForm_manage.php'>".__($guid, 'Manage Medical Forms')."</a> > </div><div class='trailEnd'>".__($guid, 'Add Medical Form').'</div>';
    echo '</div>';

    $editLink = '';
    if (isset($_GET['editID'])) {
        $editLink = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Students/medicalForm_manage_edit.php&gibbonPersonMedicalID='.$_GET['editID'].'&search='.$_GET['search'];
    }
    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], $editLink, null);
    }

    $search = $_GET['search'];
    if ($search != '') {
        echo "<div class='linkTop'>";
        echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Students/medicalForm_manage.php&search=$search'>".__($guid, 'Back to Search Results').'</a>';
        echo '</div>';
    }

    ?>
	
	<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/medicalForm_manage_addProcess.php?search=$search" ?>">
		<table class='smallIntBorder fullWidth' cellspacing='0'>	
			<tr>
				<td style='width: 275px'> 
					<b><?php echo __($guid, 'Person') ?> *</b><br/>
				</td>
				<td class="right">
					<select class="standardWidth" name="gibbonPersonID" id="gibbonPersonID">
						<?php
                        echo "<option value='Please select...'>".__($guid, 'Please select...').'</option>';
						try {
							$dataSelect = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
							$sqlSelect = "SELECT * FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID AND status='Full' ORDER BY surname, preferredName";
							$resultSelect = $connection2->prepare($sqlSelect);
							$resultSelect->execute($dataSelect);
						} catch (PDOException $e) {
						}
						while ($rowSelect = $resultSelect->fetch()) {
							if ($gibbonPersonID == $rowSelect['gibbonPersonID']) {
								echo "<option selected value='".$rowSelect['gibbonPersonID']."'>".formatName('', htmlPrep($rowSelect['preferredName']), htmlPrep($rowSelect['surname']), 'Student', true).' ('.htmlPrep($rowSelect['nameShort']).')</option>';
							} else {
								echo "<option value='".$rowSelect['gibbonPersonID']."'>".formatName('', htmlPrep($rowSelect['preferredName']), htmlPrep($rowSelect['surname']), 'Student', true).' ('.htmlPrep($rowSelect['nameShort']).')</option>';
							}
						}
						?>					
					</select>
					<script type="text/javascript">
						var gibbonPersonID=new LiveValidation('gibbonPersonID');
						gibbonPersonID.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
					</script>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php echo __($guid, 'Blood Type') ?></b><br/>
					<span class="emphasis small"></span>
				</td>
				<td class="right">
					<select class="standardWidth" name="bloodType">
						<option value=""></option>
						<option value="O+">O+</option>
						<option value="A+">A+</option>
						<option value="B+">B+</option>
						<option value="AB+">AB+</option>
						<option value="O-">O-</option>
						<option value="A-">A-</option>
						<option value="B-">B-</option>
						<option value="AB-">AB-</option>
					</select>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php echo __($guid, 'Long-Term Medication?') ?></b><br/>
					<span class="emphasis small"></span>
				</td>
				<td class="right">
					<select class="standardWidth" name="longTermMedication">
						<option value=""></option>
						<option value="Y"><?php echo __($guid, 'Yes') ?></option>
						<option value="N"><?php echo __($guid, 'No') ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php echo __($guid, 'Medication Details') ?></b><br/>
				</td>
				<td class="right">
					<textarea name="longTermMedicationDetails" id="longTermMedicationDetails" rows=8 class="standardWidth"></textarea>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php echo __($guid, 'Tetanus Within Last 10 Years?') ?></b><br/>
					<span class="emphasis small"></span>
				</td>
				<td class="right">
					<select class="standardWidth" name="tetanusWithin10Years">
						<option value=""></option>
						<option value="Y"><?php echo __($guid, 'Yes') ?></option>
						<option value="N"><?php echo __($guid, 'No') ?></option>
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