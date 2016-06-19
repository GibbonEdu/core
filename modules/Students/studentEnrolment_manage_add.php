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

if (isActionAccessible($guid, $connection2, '/modules/Students/studentEnrolment_manage_add.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Students/studentEnrolment_manage.php&gibbonSchoolYearID='.$_GET['gibbonSchoolYearID']."'>".__($guid, 'Student Enrolment')."</a> > </div><div class='trailEnd'>".__($guid, 'Add Student Enrolment').'</div>';
    echo '</div>';

    $editLink = '';
    if (isset($_GET['editID'])) {
        $editLink = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Students/studentEnrolment_manage_edit.php&gibbonStudentEnrolmentID='.$_GET['editID'].'&search='.$_GET['search'].'&gibbonSchoolYearID='.$_GET['gibbonSchoolYearID'];
    }
    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], $editLink, null);
    }

    //Check if school year specified
    $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'];
    $search = $_GET['search'];
    if ($gibbonSchoolYearID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        if ($search != '') {
            echo "<div class='linkTop'>";
            echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Students/studentEnrolment_manage.php&gibbonSchoolYearID=$gibbonSchoolYearID&search=$search'>".__($guid, 'Back to Search Results').'</a>';
            echo '</div>';
        }
        ?>
		<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/studentEnrolment_manage_addProcess.php?gibbonSchoolYearID=$gibbonSchoolYearID&search=$search" ?>">
			<table class='smallIntBorder fullWidth' cellspacing='0'>
				<tr>
					<td style='width: 275px'>
						<b><?php echo __($guid, 'School Year') ?> *</b><br/>
						<span class="emphasis small"><?php echo __($guid, 'This value cannot be changed.') ?></span>
					</td>
					<td class="right">
						<?php
                        $yearName = '';
						try {
							$dataYear = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
							$sqlYear = 'SELECT * FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID';
							$resultYear = $connection2->prepare($sqlYear);
							$resultYear->execute($dataYear);
						} catch (PDOException $e) {
							echo "<div class='error'>".$e->getMessage().'</div>';
						}
						if ($resultYear->rowCount() == 1) {
							$rowYear = $resultYear->fetch();
							$yearName = $rowYear['name'];
						}
						?>
						<input readonly name="yearName" id="yearName" maxlength=20 value="<?php echo $yearName ?>" type="text" class="standardWidth">
						<script type="text/javascript">
							var yearName=new LiveValidation('yearName');
							yearname2.add(Validate.Presence);
						</script>
					</td>
				</tr>
				<tr>
					<td>
						<b><?php echo __($guid, 'Student') ?> *</b><br/>
						<span class="emphasis small"></span>
					</td>
					<td class="right">
						<select name="gibbonPersonID" id="gibbonPersonID" class="standardWidth">
							<?php
                            echo "<option value='Please select...'>".__($guid, 'Please select...').'</option>';
							try {
								$dataSelect = array();
								$sqlSelect = "SELECT gibbonPersonID, preferredName, surname, username FROM gibbonPerson WHERE gibbonPerson.status='Full' ORDER BY surname, preferredName";
								$resultSelect = $connection2->prepare($sqlSelect);
								$resultSelect->execute($dataSelect);
							} catch (PDOException $e) {
							}
							while ($rowSelect = $resultSelect->fetch()) {
								echo "<option value='".$rowSelect['gibbonPersonID']."'>".formatName('', htmlPrep($rowSelect['preferredName']), htmlPrep($rowSelect['surname']), 'Student', true).' ('.$rowSelect['username'].')</option>';
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
						<b><?php echo __($guid, 'Year Group') ?> *</b><br/>
						<span style="font-size: 90%"></span>
					</td>
					<td class="right">
						<select name="gibbonYearGroupID" id="gibbonYearGroupID" class="standardWidth">
							<?php
                            echo "<option value='Please select...'>".__($guid, 'Please select...').'</option>';
							try {
								$dataSelect = array();
								$sqlSelect = 'SELECT gibbonYearGroupID, name FROM gibbonYearGroup ORDER BY sequenceNumber';
								$resultSelect = $connection2->prepare($sqlSelect);
								$resultSelect->execute($dataSelect);
							} catch (PDOException $e) {
							}
							while ($rowSelect = $resultSelect->fetch()) {
								echo "<option value='".$rowSelect['gibbonYearGroupID']."'>".htmlPrep(__($guid, $rowSelect['name'])).'</option>';
							}
							?>
						</select>
						<script type="text/javascript">
							var gibbonYearGroupID=new LiveValidation('gibbonYearGroupID');
							gibbonYearGroupID.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
						</script>
					</td>
				</tr>
				<tr>
					<td>
						<b><?php echo __($guid, 'Roll Group') ?> *</b><br/>
						<span style="font-size: 90%"></span>
					</td>
					<td class="right">
						<select name="gibbonRollGroupID" id="gibbonRollGroupID" class="standardWidth">
							<?php
                            echo "<option value='Please select...'>".__($guid, 'Please select...').'</option>';
							try {
								$dataSelect = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
								$sqlSelect = 'SELECT gibbonRollGroupID, name FROM gibbonRollGroup WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name';
								$resultSelect = $connection2->prepare($sqlSelect);
								$resultSelect->execute($dataSelect);
							} catch (PDOException $e) {
							}
							while ($rowSelect = $resultSelect->fetch()) {
								echo "<option value='".$rowSelect['gibbonRollGroupID']."'>".htmlPrep($rowSelect['name']).'</option>';
							}
							?>
						</select>
						<script type="text/javascript">
							var gibbonRollGroupID=new LiveValidation('gibbonRollGroupID');
							gibbonRollGroupID.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
						</script>
					</td>
				</tr>
				<tr>
					<td>
						<b><?php echo __($guid, 'Roll Order') ?></b><br/>
						<span class="emphasis small"><?php echo __($guid, 'Must be unique to roll group if set.') ?></span>
					</td>
					<td class="right">
						<input name="rollOrder" id="rollOrder" maxlength=2 value="" type="text" class="standardWidth">
						<script type="text/javascript">
							var rollOrder=new LiveValidation('rollOrder');
							rollOrder.add(Validate.Numericality);
						</script>
					</td>
				</tr>
				<tr>
					<td>
						<span class="emphasis small">* <?php echo __($guid, 'denotes a required field'); ?></span>
					</td>
					<td class="right">
						<input name="gibbonStudentEnrolmentID" id="gibbonStudentEnrolmentID" value="<?php echo $gibbonStudentEnrolmentID ?>" type="hidden">
						<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
						<input type="submit" value="<?php echo __($guid, 'Submit'); ?>">
					</td>
				</tr>
			</table>
		</form>
		<?php

    }
}
?>
