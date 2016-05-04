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

$enableDescriptors = getSettingByScope($connection2, 'Behaviour', 'enableDescriptors');
$enableLevels = getSettingByScope($connection2, 'Behaviour', 'enableLevels');

if (isActionAccessible($guid, $connection2, '/modules/Behaviour/behaviour_manage_add.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Get action with highest precendence
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Behaviour/behaviour_manage.php'>".__($guid, 'Manage Behaviour Records')."</a> > </div><div class='trailEnd'>".__($guid, 'Add Multiple').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    echo "<div class='linkTop'>";
    $policyLink = getSettingByScope($connection2, 'Behaviour', 'policyLink');
    if ($policyLink != '') {
        echo "<a target='_blank' href='$policyLink'>".__($guid, 'View Behaviour Policy').'</a>';
    }
    if ($_GET['gibbonPersonID'] != '' or $_GET['gibbonRollGroupID'] != '' or $_GET['gibbonYearGroupID'] != '' or $_GET['type'] != '') {
        if ($policyLink != '') {
            echo ' | ';
        }
        echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Behaviour/behaviour_manage.php&gibbonPersonID='.$_GET['gibbonPersonID'].'&gibbonRollGroupID='.$_GET['gibbonRollGroupID'].'&gibbonYearGroupID='.$_GET['gibbonYearGroupID'].'&type='.$_GET['type']."'>".__($guid, 'Back to Search Results').'</a>';
    }
    echo '</div>';?>

	<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/behaviour_manage_addMultiProcess.php?gibbonPersonID='.$_GET['gibbonPersonID'].'&gibbonRollGroupID='.$_GET['gibbonRollGroupID'].'&gibbonYearGroupID='.$_GET['gibbonYearGroupID'].'&type='.$_GET['type'] ?>">
		<table class='smallIntBorder fullWidth' cellspacing='0'>	
			<tr>
				<td style='width: 275px'> 
					<b><?php echo __($guid, 'Students') ?> *</b><br/>
					<span class="emphasis small"><?php echo __($guid, 'Use Control, Command and/or Shift to select multiple.') ?> </span>
				</td>
				<td class="right">
					<select multiple name="gibbonPersonIDMulti[]" id="gibbonPersonIDMulti[]" style="width: 302px; height:150px">
						<optgroup label='--<?php echo __($guid, 'Students by Roll Group') ?>--'>
							<?php
                            try {
                                $dataSelect = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                                $sqlSelect = "SELECT gibbonPerson.gibbonPersonID, preferredName, surname, gibbonRollGroup.name AS name FROM gibbonPerson, gibbonStudentEnrolment, gibbonRollGroup WHERE gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID AND gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID AND status='FULL' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name, surname, preferredName";
                                $resultSelect = $connection2->prepare($sqlSelect);
                                $resultSelect->execute($dataSelect);
                            } catch (PDOException $e) {
                            }
							while ($rowSelect = $resultSelect->fetch()) {
								echo "<option value='".$rowSelect['gibbonPersonID']."'>".htmlPrep($rowSelect['name']).' - '.formatName('', htmlPrep($rowSelect['preferredName']), htmlPrep($rowSelect['surname']), 'Student', true).'</option>';
							}
							?>
						</optgroup>
						<optgroup label='--<?php echo __($guid, 'Students by Name') ?>--'>
							<?php
                            try {
                                $dataSelect = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                                $sqlSelect = "SELECT gibbonPerson.gibbonPersonID, preferredName, surname, gibbonRollGroup.name AS name FROM gibbonPerson, gibbonStudentEnrolment, gibbonRollGroup WHERE gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID AND gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID AND status='FULL' AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY surname, preferredName";
                                $resultSelect = $connection2->prepare($sqlSelect);
                                $resultSelect->execute($dataSelect);
                            } catch (PDOException $e) {
                            }
							while ($rowSelect = $resultSelect->fetch()) {
								echo "<option value='".$rowSelect['gibbonPersonID']."'>".formatName('', htmlPrep($rowSelect['preferredName']), htmlPrep($rowSelect['surname']), 'Student', true).' ('.htmlPrep($rowSelect['name']).')</option>';
							}
							?>
						</optgroup>
					</select>
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
			<tr>
				<td> 
					<b><?php echo __($guid, 'Type') ?> *</b><br/>
					<span class="emphasis small"></span>
				</td>
				<td class="right">
					<select name="type" id="type" class="standardWidth">
						<option value="Positive"><?php echo __($guid, 'Positive') ?></option>
						<option value="Negative"><?php echo __($guid, 'Negative') ?></option>
					</select>
				</td>
			</tr>
			<?php
            if ($enableDescriptors == 'Y') {
                try {
                    $sqlPositive = "SELECT * FROM gibbonSetting WHERE scope='Behaviour' AND name='positiveDescriptors'";
                    $resultPositive = $connection2->query($sqlPositive);
                    $sqlNegative = "SELECT * FROM gibbonSetting WHERE scope='Behaviour' AND name='negativeDescriptors'";
                    $resultNegative = $connection2->query($sqlNegative);
                } catch (PDOException $e) {
                }

                if ($resultPositive->rowCount() == 1 and $resultNegative->rowCount() == 1) {
                    $rowPositive = $resultPositive->fetch();
                    $rowNegative = $resultNegative->fetch();

                    $optionsPositive = $rowPositive['value'];
                    $optionsNegative = $rowNegative['value'];

                    if ($optionsPositive != '' and $optionsNegative != '') {
                        $optionsPositive = explode(',', $optionsPositive);
                        $optionsNegative = explode(',', $optionsNegative);
                        ?>
						<tr>
							<td> 
								<b><?php echo __($guid, 'Descriptor') ?> *</b><br/>
								<span class="emphasis small"></span>
							</td>
							<td class="right">
								<select name="descriptor" id="descriptor" class="standardWidth">
									<option value="Please select..."><?php echo __($guid, 'Please select...') ?></option>
									<?php
                                    for ($i = 0; $i < count($optionsPositive); ++$i) {
                                        ?>
										<option class='Positive' value="<?php echo trim($optionsPositive[$i]) ?>"><?php echo trim($optionsPositive[$i]) ?></option>
									<?php

                                    }
                        			?>
									<?php
                                    for ($i = 0; $i < count($optionsNegative); ++$i) {
                                        ?>
										<option class='Negative' value="<?php echo trim($optionsNegative[$i]) ?>"><?php echo trim($optionsNegative[$i]) ?></option>
									<?php

                                    }
                        			?>								</select>
								<script type="text/javascript">
									var descriptor=new LiveValidation('descriptor');
									descriptor.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
								</script>
								 <script type="text/javascript">
									$("#descriptor").chainedTo("#type");
								</script>
							</td>
						</tr>
						<?php

                    }
                }
            }

    if ($enableLevels == 'Y') {
        $optionsLevels = getSettingByScope($connection2, 'Behaviour', 'levels');
        if ($optionsLevels != '') {
            $optionsLevels = explode(',', $optionsLevels);
            ?>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Level') ?> *</b><br/>
							<span class="emphasis small"></span>
						</td>
						<td class="right">
							<select name="level" id="level" class="standardWidth">
								<option value="Please select..."><?php echo __($guid, 'Please select...') ?></option>
								<?php
                                for ($i = 0; $i < count($optionsLevels); ++$i) {
                                    ?>
									<option value="<?php echo trim($optionsLevels[$i]) ?>"><?php echo trim($optionsLevels[$i]) ?></option>
								<?php

                                }
           	 					?>
							</select>
							<script type="text/javascript">
								var level=new LiveValidation('level');
								level.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
							</script>
						</td>
					</tr>
					<?php

						}
					}
					?>
			<script type='text/javascript'>
				$(document).ready(function(){
					autosize($('textarea'));
				});
			</script>
			<tr>
				<td colspan=2> 
					<b><?php echo __($guid, 'Incident') ?></b><br/>
					<textarea name="comment" id="comment" rows=8 style="width: 100%"></textarea>
				</td>
			</tr>
			<tr>
				<td colspan=2> 
					<b><?php echo __($guid, 'Follow Up') ?></b><br/>
					<textarea name="followup" id="followup" rows=8 style="width: 100%"></textarea>
				</td>
			</tr>
			<tr>
				<td>
					<span class="emphasis small">* <?php echo __($guid, 'denotes a required field'); ?></span>
				</td>
				<td class="right">
					<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
					<input type="submit" value="<?php echo __($guid, 'Next') ?>">
				</td>
			</tr>
		</table>
	</form>
	<?php

}
?>