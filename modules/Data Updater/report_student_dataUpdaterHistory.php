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

use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;

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

    $choices = isset($_POST['members'])? $_POST['members'] : null;
    $nonCompliant = isset($_POST['nonCompliant'])? $_POST['nonCompliant'] : null;
    $date = isset($_POST['date'])? $_POST['date'] : date($_SESSION[$guid]['i18n']['dateFormatPHP'], (time() - (604800 * 26)));

    $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/report_student_dataUpdaterHistory.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('address', $_SESSION[$guid]['address']);
    
    $row = $form->addRow();
        $row->addLabel('members', __('Students'));
        $row->addSelectStudent('members', $_SESSION[$guid]['gibbonSchoolYearID'], array('byRoll' => true, 'byName' => true))
            ->selectMultiple()
            ->isRequired()
            ->selected($choices);

    $row = $form->addRow();
        $row->addLabel('date', __('Date'))->description(__('Earliest acceptable update'));
        $row->addDate('date')->setValue($date)->isRequired();

    $row = $form->addRow();
        $row->addLabel('nonCompliant', __('Show Only Non-Compliant?'))->description(__('If not checked, show all. If checked, show only non-compliant students.'));
        $row->addCheckbox('nonCompliant')->setValue('Y')->checked($nonCompliant);
    
    $row = $form->addRow();
        $row->addSubmit();
    
    echo $form->getOutput();

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
