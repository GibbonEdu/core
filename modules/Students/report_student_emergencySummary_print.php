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

if (isActionAccessible($guid, $connection2, '/modules/Students/report_student_emergencySummary_print.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    $choices = $_SESSION[$guid]['report_student_emergencySummary.php_choices'];

    if (count($choices) > 0) {
        echo '<h2>';
        echo __($guid, 'Student Emergency Data Summary');
        echo '</h2>';
        echo '<p>';
        echo __($guid, 'This report prints a summary of emergency data for the selected students. In case of emergency, please try to contact parents first, and if they cannot be reached then contact the listed emergency contacts.');
        echo '</p>';

        try {
            $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
            $sqlWhere = ' AND (';
            for ($i = 0; $i < count($choices); ++$i) {
                $data[$choices[$i]] = $choices[$i];
                $sqlWhere = $sqlWhere.'gibbonPerson.gibbonPersonID=:'.$choices[$i].' OR ';
            }
            $sqlWhere = substr($sqlWhere, 0, -4);
            $sqlWhere = $sqlWhere.')';
            $sql = "SELECT surname, preferredName, gibbonPerson.gibbonPersonID, gibbonRollGroup.name AS name, emergency1Name, emergency1Number1, emergency1Number2, emergency1Relationship, emergency2Name, emergency2Number1, emergency2Number2, emergency2Relationship FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE status='Full' AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID $sqlWhere ORDER BY surname, preferredName";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        echo "<div class='linkTop'>";
        echo "<a href='javascript:window.print()'>".__($guid, 'Print')."<img style='margin-left: 5px' title='".__($guid, 'Print')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/print.png'/></a>";
        echo '</div>';

        echo "<table class='mini' cellspacing='0' style='width: 100%'>";
        echo "<tr class='head'>";
        echo '<th>';
        echo __($guid, 'Student');
        echo '</th>';
        echo '<th colspan=3>';
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

            echo "<tr class=$rowNum>";
            echo '<td>';
            echo formatName('', $row['preferredName'], $row['surname'], 'Student', true);
            echo '</td>';
            echo '<td colspan=3>';
                        //Get details of last personal data form update
                        try {
                            $dataMedical = array('gibbonPersonID' => $row['gibbonPersonID']);
                            $sqlMedical = "SELECT * FROM gibbonPersonUpdate WHERE gibbonPersonID=:gibbonPersonID AND status='Complete' ORDER BY timestamp DESC";
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

            echo "<tr class=$rowNum>";
            echo '<td></td>';
            echo "<td style='border-top: 1px solid #aaa; vertical-align: top'>";
            echo '<b><i>'.__($guid, 'Parents').'</i></b><br/>';
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
                    $sqlFamily2 = 'SELECT gibbonPerson.* FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonPerson.gibbonPersonID=gibbonFamilyAdult.gibbonPersonID) WHERE gibbonFamilyID=:gibbonFamilyID ORDER BY contactPriority, surname, preferredName';
                    $resultFamily2 = $connection2->prepare($sqlFamily2);
                    $resultFamily2->execute($dataFamily2);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }
                while ($rowFamily2 = $resultFamily2->fetch()) {
                    echo '<u>'.formatName($rowFamily2['title'], $rowFamily2['preferredName'], $rowFamily2['surname'], 'Parent').'</u><br/>';
                    $numbers = 0;
                    for ($i = 1; $i < 5; ++$i) {
                        if ($rowFamily2['phone'.$i] != '') {
                            if ($rowFamily2['phone'.$i.'Type'] != '') {
                                echo '<i>'.$rowFamily2['phone'.$i.'Type'].':</i> ';
                            }
                            if ($rowFamily2['phone'.$i.'CountryCode'] != '') {
                                echo '+'.$rowFamily2['phone'.$i.'CountryCode'].' ';
                            }
                            echo $rowFamily2['phone'.$i].'<br/>';
                            ++$numbers;
                        }
                    }
                    if ($numbers == 0) {
                        echo "<span style='font-size: 85%; font-style: italic'>".__($guid, 'No number available.').'</span><br/>';
                    }
                }
            }
            echo '</td>';
            echo "<td style='border-top: 1px solid #aaa; vertical-align: top'>";
            echo '<b><i>'.__($guid, 'Emergency Contact 1').'</i></b><br/>';
            echo '<u><i>'.__($guid, 'Name').'</i></u>: '.$row['emergency1Name'].'<br/>';
            echo '<u><i>'.__($guid, 'Number').'</i></u>: '.$row['emergency1Number1'].'<br/>';
            if ($row['emergency1Number2'] !== '') {
                echo '<u><i>'.__($guid, 'Number 2').'</i></u>: '.$row['emergency1Number2'].'<br/>';
            }
            if ($row['emergency1Relationship'] !== '') {
                echo '<u><i>'.__($guid, 'Relationship').'</i></u>: '.$row['emergency1Relationship'].'<br/>';
            }
            echo '</td>';
            echo "<td style='border-top: 1px solid #aaa; vertical-align: top'>";
            echo '<b><i>'.__($guid, 'Emergency Contact 2').'</i></b><br/>';
            echo '<u><i>'.__($guid, 'Name').'</i></u>: '.$row['emergency2Name'].'<br/>';
            echo '<u><i>'.__($guid, 'Number').'</i></u>: '.$row['emergency2Number1'].'<br/>';
            if ($row['emergency2Number2'] !== '') {
                echo '<u><i>'.__($guid, 'Number 2').'</i></u>: '.$row['emergency2Number2'].'<br/>';
            }
            if ($row['emergency2Relationship'] !== '') {
                echo '<u><i>'.__($guid, 'Relationship').'</i></u>: '.$row['emergency2Relationship'].'<br/>';
            }
            echo '</td>';
            echo '</tr>';
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
