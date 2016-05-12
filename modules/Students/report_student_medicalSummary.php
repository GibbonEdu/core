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

$_SESSION[$guid]['report_student_medicalSummary.php_choices'] = '';

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Students/report_student_medicalSummary.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Student Medical Data Summary').'</div>';
    echo '</div>';
    echo '<p>';
    echo __($guid, 'This report prints a summary of medical data for the selected students.');
    echo '</p>';

    echo '<h2>';
    echo __($guid, 'Choose Students');
    echo '</h2>';

    ?>
	
	<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/report_student_medicalSummary.php'?>">
		<table class='smallIntBorder fullWidth' cellspacing='0'>	
			<tr>
				<td style='width: 275px'> 
					<b><?php echo __($guid, 'Students') ?> *</b><br/>
					<span class="emphasis small"><?php echo __($guid, 'Use Control, Command and/or Shift to select multiple.') ?></span>
				</td>
				<td class="right">
					<select name="Members[]" id="Members[]" multiple style="width: 302px; height: 150px">
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
        $_SESSION[$guid]['report_student_medicalSummary.php_choices'] = $choices;

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

        echo "<div class='linkTop'>";
        echo "<a target='_blank' href='".$_SESSION[$guid]['absoluteURL'].'/report.php?q=/modules/'.$_SESSION[$guid]['module']."/report_student_medicalSummary_print.php'>".__($guid, 'Print')."<img style='margin-left: 5px' title='".__($guid, 'Print')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/print.png'/></a>";
        echo '</div>';

        echo "<table cellspacing='0' style='width: 100%'>";
        echo "<tr class='head'>";
        echo '<th>';
        echo __($guid, 'Student');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Medical Form?');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Blood Type');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Tetanus').'<br/>';
        echo "<span style='font-size: 80%'><i>".__($guid, '10 Years').'</span>';
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Last Update');
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

            try {
                $dataForm = array('gibbonPersonID' => $row['gibbonPersonID']);
                $sqlForm = 'SELECT * FROM gibbonPersonMedical WHERE gibbonPersonID=:gibbonPersonID';
                $resultForm = $connection2->prepare($sqlForm);
                $resultForm->execute($dataForm);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            if ($resultForm->rowCount() == 1) {
                $rowForm = $resultForm->fetch();
                echo "<tr class=$rowNum>";
                echo '<td>';
                echo formatName('', htmlPrep($row['preferredName']), htmlPrep($row['surname']), 'Student', true);
                echo '</td>';
                echo '<td>';
                echo __($guid, 'Yes');
                echo '</td>';
                echo '<td>';
                echo $rowForm['bloodType'];
                echo '</td>';
                echo '<td>';
                echo $rowForm['tetanusWithin10Years'];
                echo '</td>';
                echo '<td>';
                            //Get details of last medical form update
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
                                //Is last update more recent than 90 days?
                                if (substr($rowMedical['timestamp'], 0, 10) > date('Y-m-d', (time() - (90 * 24 * 60 * 60)))) {
                                    echo dateConvertBack($guid, substr($rowMedical['timestamp'], 0, 10));
                                } else {
                                    echo "<span style='color: #ff0000; font-weight: bold'>".dateConvertBack($guid, substr($rowMedical['timestamp'], 0, 10)).'</span>';
                                }
                } else {
                    echo "<span style='color: #ff0000; font-weight: bold'>".__($guid, 'NA').'</span>';
                }
                echo '</td>';
                echo '</tr>';

                    //Long term medication
                    if ($rowForm['longTermMedication'] == 'Y') {
                        echo "<tr class=$rowNum>";
                        echo '<td></td>';
                        echo "<td colspan=4 style='border-top: 1px solid #aaa'>";
                        echo '<b><i>'.__($guid, 'Long Term Medication').'</i></b>: '.$rowForm['longTermMedication'].'<br/>';
                        echo '<u><i>'.__($guid, 'Details').'</i></u>: '.$rowForm['longTermMedicationDetails'].'<br/>';
                        echo '</td>';
                        echo '</tr>';
                    }

                    //Conditions
                    $condCount = 1;
                try {
                    $dataConditions = array('gibbonPersonMedicalID' => $rowForm['gibbonPersonMedicalID']);
                    $sqlConditions = 'SELECT * FROM gibbonPersonMedicalCondition WHERE gibbonPersonMedicalID=:gibbonPersonMedicalID';
                    $resultConditions = $connection2->prepare($sqlConditions);
                    $resultConditions->execute($dataConditions);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }

                while ($rowConditions = $resultConditions->fetch()) {
                    $alert = getAlert($guid, $connection2, $rowConditions['gibbonAlertLevelID']);
                    if ($alert != false) {
                        $conditionStyle = "style='border-top: 2px solid #".$alert['color']."'";
                        echo "<tr class=$rowNum>";
                        echo '<td></td>';
                        echo "<td colspan=4 $conditionStyle>";
                        echo '<b><i>'.__($guid, 'Condition')." $condCount</i></b>: ".__($guid, $rowConditions['name']).'<br/>';
                        echo '<u><i>'.__($guid, 'Risk')."</i></u>: <span style='color: #".$alert['color']."; font-weight: bold'>".__($guid, $alert['name']).'</span><br/>';
                        if ($rowConditions['triggers'] != '') {
                            echo '<u><i>'.__($guid, 'Triggers').'</i></u>: '.$rowConditions['triggers'].'<br/>';
                        }
                        if ($rowConditions['reaction'] != '') {
                            echo '<u><i>'.__($guid, 'Reaction').'</i></u>: '.$rowConditions['reaction'].'<br/>';
                        }
                        if ($rowConditions['response'] != '') {
                            echo '<u><i>'.__($guid, 'Response').'</i></u>: '.$rowConditions['response'].'<br/>';
                        }
                        if ($rowConditions['medication'] != '') {
                            echo '<u><i>'.__($guid, 'Medication').'</i></u>: '.$rowConditions['medication'].'<br/>';
                        }
                        if ($rowConditions['lastEpisode'] != '' or $rowConditions['lastEpisodeTreatment'] != '') {
                            echo '<u><i>'.__($guid, 'Last Episode').'</i></u>: ';
                            if ($rowConditions['lastEpisode'] != '') {
                                echo dateConvertBack($guid, $rowConditions['lastEpisode']);
                            }
                            if ($rowConditions['lastEpisodeTreatment'] != '') {
                                if ($rowConditions['lastEpisode'] != '') {
                                    echo ' | ';
                                }
                                echo $rowConditions['lastEpisodeTreatment'];
                            }
                            echo '<br/>';
                        }

                        if ($rowConditions['comment'] != '') {
                            echo '<u><i>'.__($guid, 'Comment').'</i></u>: '.$rowConditions['comment'].'<br/>';
                        }
                        echo '</td>';
                        echo '</tr>';
                        ++$condCount;
                    }
                }
            } else {
                echo "<tr class=$rowNum>";
                echo '<td>';
                echo formatName('', htmlPrep($row['preferredName']), htmlPrep($row['surname']), 'Student', true);
                echo '</td>';
                echo '<td colspan=4>';
                echo "<span style='color: #ff0000; font-weight: bold'>".__($guid, 'No').'</span>';
                echo '</td>';
                echo '</tr>';
            }
        }
        if ($count == 0) {
            echo "<tr class=$rowNum>";
            echo '<td colspan=2>';
            echo __($guid, 'There are no records to display.');
            echo '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }
}
?>