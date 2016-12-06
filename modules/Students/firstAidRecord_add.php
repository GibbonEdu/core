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

if (isActionAccessible($guid, $connection2, '/modules/Students/firstAidRecord_add.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        echo "<div class='error'>";
        echo __($guid, 'The highest grouped action cannot be determined.');
        echo '</div>';
    } else {
        echo "<div class='trail'>";
        echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Students/firstAidRecord.php'>".__($guid, 'Manage First Aid Records')."</a> > </div><div class='trailEnd'>".__($guid, 'Add').'</div>';
        echo '</div>';

        $editLink = '';
        $editID = '';
        if (isset($_GET['editID'])) {
            $editLink = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Students/firstAidRecord_edit.php&gibbonFirstAidID='.$_GET['editID'].'&gibbonRollGroupID='.$_GET['gibbonRollGroupID'].'&gibbonYearGroupID='.$_GET['gibbonYearGroupID'];
            $editID = $_GET['editID'];
        }
        if (isset($_GET['return'])) {
            returnProcess($guid, $_GET['return'], $editLink, array('warning1' => 'Your request was successful, but some data was not properly saved.', 'success1' => 'Your request was completed successfully. You can now add extra information below if you wish.'));
        }

        $gibbonFirstAidID = null;
        if (isset($_GET['gibbonFirstAidID'])) {
            $gibbonFirstAidID = $_GET['gibbonFirstAidID'];
        }
        ?>

		<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/firstAidRecord_addProcess.php?gibbonRollGroupID='.$_GET['gibbonRollGroupID'].'&gibbonYearGroupID='.$_GET['gibbonYearGroupID'] ?>">
			<table class='smallIntBorder fullWidth' cellspacing='0'>
				<tr>
					<td style='width: 275px'>
						<b><?php echo __($guid, 'Patient') ?> *</b><br/>
						<span class="emphasis small"></span>
					</td>
					<td class="right">
						<?php
                            $gibbonPersonID = null;
							if (isset($_GET['gibbonPersonID'])) {
								$gibbonPersonID = $_GET['gibbonPersonID'];
							}
							?>
						<select name="gibbonPersonID" id="gibbonPersonID2" class="standardWidth">
							<option value="Please select..."><?php echo __($guid, 'Please select...') ?></option>
							<?php
                            try {
                                $dataSelect = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                                $sqlSelect = "SELECT * FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID AND status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') ORDER BY surname, preferredName";
                                $resultSelect = $connection2->prepare($sqlSelect);
                                $resultSelect->execute($dataSelect);
                            } catch (PDOException $e) {
                                echo "<div class='error'>".$e->getMessage().'</div>';
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
							var gibbonPersonID2=new LiveValidation('gibbonPersonID2');
							gibbonPersonID2.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
						</script>
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
						<input name="date" id="date" maxlength=10 value="<?php echo date($_SESSION[$guid]['i18n']['dateFormatPHP']) ?>" type="text" class="standardWidth">
						<script type="text/javascript">
							var date=new LiveValidation('date');
							date.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]['i18n']['dateFormatRegEx'] == '') {
							echo "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i";
							} else {
								echo $_SESSION[$guid]['i18n']['dateFormatRegEx'];
							}
										?>, failureMessage: "Use <?php if ($_SESSION[$guid]['i18n']['dateFormat'] == '') {
								echo 'dd/mm/yyyy';
							} else {
								echo $_SESSION[$guid]['i18n']['dateFormat'];
							}
							?>." } );
						</script>
						 <script type="text/javascript">
							$(function() {
								$( "#date" ).datepicker();
							});
						</script>
					</td>
				</tr>
				<script type='text/javascript'>
					$(document).ready(function(){
						autosize($('textarea'));
					});
				</script>

                <tr>
                    <td>
                        <b><?php echo __($guid, 'Time In') ?> *</b><br/>
                        <span class="emphasis small"><?php echo __($guid, 'Format: hh:mm (24hr)') ?><br/></span>
                    </td>
                    <td class="right">
                        <input name="timeIn" id="timeIn" maxlength=5 value="<?php if (isset($nexttimeIn)) { echo substr($nexttimeIn, 0, 5); } ?>" type="text" class="standardWidth">
                        <script type="text/javascript">
                            var timeIn=new LiveValidation('timeIn');
                            timeIn.add(Validate.Presence);
                            timeIn.add( Validate.Format, {pattern: /^(0[0-9]|[1][0-9]|2[0-3])[:](0[0-9]|[1-5][0-9])/i, failureMessage: "Use hh:mm" } );
                        </script>
                    </td>
                </tr>

                <tr>
					<td colspan=2>
						<b><?php echo __($guid, 'Description') ?></b><br/>
						<textarea name="description" id="description" rows=8 style="width: 100%"></textarea>
					</td>
				</tr>
                <tr>
					<td colspan=2>
						<b><?php echo __($guid, 'Action Taken') ?></b><br/>
						<textarea name="actionTaken" id="actionTaken" rows=8 style="width: 100%"></textarea>
					</td>
				</tr>
				<tr>
					<td colspan=2>
						<b><?php echo __($guid, 'Follow Up') ?></b><br/>
						<textarea name="followUp" id="followUp" rows=8 style="width: 100%"></textarea>
					</td>
				</tr>
				<tr>
					<td>
						<span class="emphasis small">* <?php echo __($guid, 'denotes a required field'); ?></span>
					</td>
					<td class="right">
						<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
						<input type="submit" value="<?php echo __($guid, 'Submit') ?>">
					</td>
				</tr>
			</table>
		</form>
		<?php
    }
}
?>
