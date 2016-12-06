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

if (isActionAccessible($guid, $connection2, '/modules/Data Updater/report_student_dataUpdaterHistory.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Student Data Updater History').'</div>';
    echo '</div>';
    echo '<p>';
    echo __($guid, 'This report allows a user to select a range of students and check whether or not they have had their personal and medical data updated after a specified date.');
    echo '</p>';

    echo '<h2>';
    echo __($guid, 'Choose Students');
    echo '</h2>';

    $nonCompliant = null;
    if (isset($_POST['nonCompliant'])) {
        $nonCompliant = $_POST['nonCompliant'];
    }
    $date = null;
    if (isset($_POST['date'])) {
        $date = $_POST['date'];
    }
    ?>

	<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/report_student_dataUpdaterHistory.php'?>">
		<table class='smallIntBorder fullWidth' cellspacing='0'>
			<tr>
				<td style='width: 275px'>
					<b><?php echo __($guid, 'Students') ?> *</b><br/>
					<span class="emphasis small"><?php echo __($guid, 'Use Control, Command and/or Shift to select multiple.') ?></span>
				</td>
				<td class="right">
					<select name="Members[]" id="Members[]" multiple class='standardWidth' style="height: 150px">
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
                                $sqlSelect = 'SELECT gibbonPerson.gibbonPersonID, preferredName, surname, gibbonRollGroup.name AS name FROM gibbonPerson, gibbonStudentEnrolment, gibbonRollGroup WHERE gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID AND gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY surname, preferredName';
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
					<span style="font-size: 85%"><i><?php echo __($guid, 'Earliest acceptable update') ?><br/><?php echo __($guid, 'Format:') ?> <?php if ($_SESSION[$guid]['i18n']['dateFormat'] == '') { echo 'dd/mm/yyyy';
					} else {
						echo $_SESSION[$guid]['i18n']['dateFormat'];
					}
					?></span>
				</td>
				<td class="right">
					<input name="date" id="date" maxlength=10 value="<?php if ($date != '') { echo $date; } else { echo date($_SESSION[$guid]['i18n']['dateFormatPHP'], (time() - (604800 * 26))); } ?>" type="text" class="standardWidth">
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
					 	date.add(Validate.Presence);
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
				<b><?php echo __($guid, 'Show Only Non-Compliant?') ?></b><br/>
				<span style="font-size: 85%"><i><?php echo __($guid, 'If not checked, show all. If checked, show only non-compliant students.') ?></i><br/>
				</span>
			</td>
			<td class="right">
				<input <?php if ($nonCompliant == 'Y') { echo 'checked'; } ?> type='checkbox' name='nonCompliant' value='Y'/>
			</td>
		</tr>
			<tr>
				<td colspan=2 class="right">
					<input type="submit" value="<?php echo __($guid, 'Submit'); ?>">
				</td>
			</tr>
		</table>
	</form>
	<?php

    $choices = null;
    if (isset($_POST['Members'])) {
        $choices = $_POST['Members'];
    }

    if (count($choices) > 0) {
        echo '<h2>';
        echo __($guid, 'Report Data');
        echo '</h2>';

        try {
            $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
            $sqlWhere = ' AND (';
            for ($i = 0; $i < count($choices); ++$i) {
                $data[$choices[$i]] = $choices[$i];
                $sqlWhere = $sqlWhere.'gibbonPerson.gibbonPersonID=:'.$choices[$i].' OR ';
            }
            $sqlWhere = substr($sqlWhere, 0, -4);
            $sqlWhere = $sqlWhere.')';
            $sql = "SELECT surname, preferredName, gibbonPerson.gibbonPersonID, gibbonRollGroup.name AS name FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE status='Full' AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID $sqlWhere ORDER BY surname, preferredName";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        echo "<table cellspacing='0' style='width: 100%'>";
        echo "<tr class='head'>";
        echo '<th>';

        echo '</th>';
        echo '<th>';
        echo __($guid, 'Student');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Roll Group');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Personal Data');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Medical Data');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Parent Emails');
        echo '</th>';
        echo '</tr>';

        $count = 0;
        $rowNum = 'odd';
        while ($row = $result->fetch()) {
            //Calculate personal
                $personal = '';
            $personalFail = false;
            try {
                $dataPersonal = array('gibbonPersonID' => $row['gibbonPersonID']);
                $sqlPersonal = "SELECT * FROM gibbonPersonUpdate WHERE gibbonPersonID=:gibbonPersonID AND status='Complete' ORDER BY timestamp DESC";
                $resultPersonal = $connection2->prepare($sqlPersonal);
                $resultPersonal->execute($dataPersonal);
            } catch (PDOException $e) {
            }
            if ($resultPersonal->rowCount() > 0) {
                $rowPersonal = $resultPersonal->fetch();
                if (dateConvert($guid, $date) <= substr($rowPersonal['timestamp'], 0, 10)) {
                    $personal = dateConvertBack($guid, substr($rowPersonal['timestamp'], 0, 10));
                } else {
                    $personal = "<span style='color: #ff0000; font-weight: bold'>".dateConvertBack($guid, substr($rowPersonal['timestamp'], 0, 10)).'</span>';
                    $personalFail = true;
                }
            } else {
                $personal = "<span style='color: #ff0000; font-weight: bold'>".__($guid, 'No data').'</span>';
                $personalFail = true;
            }

                //Calculate medical
                $medical = '';
            $medicalFail = false;
            try {
                $dataMedical = array('gibbonPersonID' => $row['gibbonPersonID']);
                $sqlMedical = "SELECT * FROM gibbonPersonMedicalUpdate WHERE gibbonPersonID=:gibbonPersonID AND status='Complete' ORDER BY timestamp DESC";
                $resultMedical = $connection2->prepare($sqlMedical);
                $resultMedical->execute($dataMedical);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }
            if ($resultMedical->rowCount() > 0) {
                $rowMedical = $resultMedical->fetch();
                if (dateConvert($guid, $date) <= substr($rowMedical['timestamp'], 0, 10)) {
                    $medical = dateConvertBack($guid, substr($rowMedical['timestamp'], 0, 10));
                } else {
                    $medical = "<span style='color: #ff0000; font-weight: bold'>".dateConvertBack($guid, substr($rowMedical['timestamp'], 0, 10)).'</span>';
                    $medicalFail = true;
                }
            } else {
                $medical = "<span style='color: #ff0000; font-weight: bold'>".__($guid, 'No data').'</span>';
                $medicalFail = true;
            }

            if ($personalFail or $medicalFail or $nonCompliant == '') {
                if ($count % 2 == 0) {
                    $rowNum = 'even';
                } else {
                    $rowNum = 'odd';
                }
                ++$count;

                //COLOR ROW BY STATUS!
                echo "<tr class=$rowNum>";
                echo '<td>';
                echo $count;
                echo '</td>';
                echo '<td>';
                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID='.$row['gibbonPersonID']."'>".formatName('', htmlPrep($row['preferredName']), htmlPrep($row['surname']), 'Student', true).'</a>';
                echo '</td>';
                echo '<td>';
                echo $row['name'];
                echo '</td>';
                echo '<td>';
                echo $personal;
                echo '</td>';
                echo '<td>';
                echo $medical;
                echo '</td>';
                echo '<td>';
                try {
                    $dataFamily = array('gibbonPersonID' => $row['gibbonPersonID']);
                    $sqlFamily = 'SELECT gibbonFamilyID FROM gibbonFamilyChild WHERE gibbonPersonID=:gibbonPersonID';
                    $resultFamily = $connection2->prepare($sqlFamily);
                    $resultFamily->execute($dataFamily);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }
                while ($rowFamily = $resultFamily->fetch()) {
                    try {
                        $dataFamily2 = array('gibbonFamilyID' => $rowFamily['gibbonFamilyID']);
                        $sqlFamily2 = 'SELECT * FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonPerson.gibbonPersonID=gibbonFamilyAdult.gibbonPersonID) WHERE gibbonFamilyID=:gibbonFamilyID ORDER BY contactPriority, surname, preferredName';
                        $resultFamily2 = $connection2->prepare($sqlFamily2);
                        $resultFamily2->execute($dataFamily2);
                    } catch (PDOException $e) {
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }
                    $emails = '';
                    while ($rowFamily2 = $resultFamily2->fetch()) {
                        if ($rowFamily2['contactPriority'] == 1) {
                            if ($rowFamily2['email'] != '') {
                                $emails .= $rowFamily2['email'].', ';
                            }
                        } elseif ($rowFamily2['contactEmail'] == 'Y') {
                            if ($rowFamily2['email'] != '') {
                                $emails .= $rowFamily2['email'].', ';
                            }
                        }
                    }
                    if ($emails != '') {
                        echo substr($emails, 0, -2);
                    }
                }
                echo '</td>';

                echo '</tr>';
            }
        }
        if ($count == 0) {
            echo "<tr class=$rowNum>";
            echo '<td colspan=6>';
            echo __($guid, 'There are no records to display.');
            echo '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }
}
?>
