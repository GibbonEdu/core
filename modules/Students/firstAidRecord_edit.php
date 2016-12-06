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

if (isActionAccessible($guid, $connection2, '/modules/Students/firstAidRecord_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Get action with highest precendence
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Students/firstAidRecord.php'>".__($guid, 'Manage First Aid Records')."</a> > </div><div class='trailEnd'>".__($guid, 'Edit').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    //Check if school year specified
    $gibbonFirstAidID = $_GET['gibbonFirstAidID'];
    if ($gibbonFirstAidID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonFirstAidID' => $gibbonFirstAidID);
            $sql = "SELECT gibbonFirstAid.*, patient.surname AS surnamePatient, patient.preferredName AS preferredNamePatient, firstAider.title, firstAider.surname AS surnameFirstAider, firstAider.preferredName AS preferredNameFirstAider
                FROM gibbonFirstAid
                    JOIN gibbonPerson AS patient ON (gibbonFirstAid.gibbonPersonIDPatient=patient.gibbonPersonID)
                    JOIN gibbonPerson AS firstAider ON (gibbonFirstAid.gibbonPersonIDFirstAider=firstAider.gibbonPersonID)
                    JOIN gibbonStudentEnrolment ON (patient.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID)
                    JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID)
                    JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID)
                WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonFirstAidID=:gibbonFirstAidID";
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
            $row = $result->fetch();
            ?>
			<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/firstAidRecord_editProcess.php?gibbonFirstAidID=$gibbonFirstAidID&gibbonRollGroupID=".$_GET['gibbonRollGroupID'].'&gibbonYearGroupID='.$_GET['gibbonYearGroupID'] ?>">
				<table class='smallIntBorder fullWidth' cellspacing='0'>
					<tr>
						<td style='width: 275px'>
							<b><?php echo __($guid, 'Patient') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'This value cannot be changed.') ?></span>
						</td>
						<td class="right">
							<?php
                            try {
                                $dataSelect = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $row['gibbonPersonIDPatient']);
                                $sqlSelect = "SELECT * FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID AND status='Full' AND gibbonPerson.gibbonPersonID=:gibbonPersonID ORDER BY surname, preferredName";
                                $resultSelect = $connection2->prepare($sqlSelect);
                                $resultSelect->execute($dataSelect);
                            } catch (PDOException $e) {
                            }
							if ($resultSelect->rowCount() == 1) {
								$rowSelect = $resultSelect->fetch();
							}

							?>
							<input type="hidden" name="gibbonPersonID" value="<?php echo $row['gibbonPersonID'] ?>">
							<input readonly name="name" id="name" value="<?php echo formatName('', $rowSelect['preferredName'], $rowSelect['surname'], 'Student') ?>" type="text" class="standardWidth">
						</td>
					</tr>
                    <tr>
                        <td style='width: 275px'>
                            <b><?php echo __($guid, 'First Aider') ?> *</b><br/>
                            <span class="emphasis small"><?php echo __($guid, 'This value cannot be changed.') ?></span>
                        </td>
                        <td class="right">
                            <input readonly name="name" id="name" value="<?php echo formatName('', $_SESSION[$guid]['preferredName'], $_SESSION[$guid]['surname'], 'Student') ?>" type="text" class="standardWidth">
                        </td>
                    </tr>
					<tr>
						<td>
							<b><?php echo __($guid, 'Date') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Format:') ?> <?php if ($_SESSION[$guid]['i18n']['dateFormat'] == '') {
								echo 'dd/mm/yyyy';
							} else {
								echo $_SESSION[$guid]['i18n']['dateFormat'];
							}
            				?></span>
						</td>
						<td class="right">
							<input readonly name="date" id="date" maxlength=10 value="<?php echo dateConvertBack($guid, $row['date']) ?>" type="text" class="standardWidth">
						</td>
					</tr>

					<tr>
						<td>
							<b><?php echo __($guid, 'Time In') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'This value cannot be changed.') ?></span>
						</td>
						<td class="right">
							<input name="timeIn" id="timeIn" readonly="readonly" maxlength=20 value="<?php echo substr($row['timeIn'], 0, -3) ?>" type="text" class="standardWidth">
						</td>
					</tr>
                    <tr>
                        <td>
                            <b><?php echo __($guid, 'Time Out') ?></b><br/>
                            <span class="emphasis small"><?php echo __($guid, 'Format: hh:mm (24hr)') ?><br/></span>
                        </td>
                        <td class="right">
                            <input name="timeOut" id="timeOut" maxlength=5 value="<?php echo substr($row['timeOut'], 0, 5); ?>" type="text" class="standardWidth">
                            <script type="text/javascript">
                                var timeOut=new LiveValidation('timeOut');
                                timeOut.add( Validate.Format, {pattern: /^(0[0-9]|[1][0-9]|2[0-3])[:](0[0-9]|[1-5][0-9])/i, failureMessage: "Use hh:mm" } );
                            </script>
                        </td>
                    </tr>

					<script type='text/javascript'>
						$(document).ready(function(){
							autosize($('textarea'));
						});
					</script>
					<tr>
						<td colspan=2>
							<b><?php echo __($guid, 'Description') ?></b><br/>
							<p style="width: 100%"><?php echo htmlPrep($row['description']) ?></p>
						</td>
					</tr>
					<tr>
						<td colspan=2>
							<b><?php echo __($guid, 'Action Taken') ?></b><br/>
							<p style="width: 100%"><?php echo htmlPrep($row['actionTaken']) ?></p>
						</td>
					</tr>
					<tr>
						<td colspan=2>
							<b><?php echo __($guid, 'Follow Up') ?></b><br/>
							<textarea name="followUp" id="followUp" rows=8 style="width: 100%"><?php echo htmlPrep($row['followUp']) ?></textarea>
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
