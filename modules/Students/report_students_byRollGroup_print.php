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

if (isActionAccessible($guid, $connection2, '/modules/Students/report_students_byRollGroup_print.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    $gibbonRollGroupID = (isset($_GET['gibbonRollGroupID']) ? $_GET['gibbonRollGroupID'] : null);
    $view = (isset($_GET['view']) ? $_GET['view'] : 'Basic');

    //Proceed!
    echo '<h2>';
    echo __($guid, 'Students by Roll Group');
    echo '</h2>';

    if ($gibbonRollGroupID != '') {
        if ($gibbonRollGroupID != '*') {
            try {
                $data = array('gibbonRollGroupID' => $gibbonRollGroupID);
                $sql = 'SELECT * FROM gibbonRollGroup WHERE gibbonRollGroupID=:gibbonRollGroupID';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            if ($result->rowCount() == 1) {
                $row = $result->fetch();
                echo "<p style='margin-bottom: 0px'><b>".__($guid, 'Roll Group').'</b>: '.$row['name'].'</p>';

                //Show Tutors
                try {
                    $dataDetail = array('gibbonPersonIDTutor' => $row['gibbonPersonIDTutor'], 'gibbonPersonIDTutor2' => $row['gibbonPersonIDTutor2'], 'gibbonPersonIDTutor3' => $row['gibbonPersonIDTutor3']);
                    $sqlDetail = 'SELECT title, surname, preferredName FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonIDTutor OR gibbonPersonID=:gibbonPersonIDTutor2 OR gibbonPersonID=:gibbonPersonIDTutor3';
                    $resultDetail = $connection2->prepare($sqlDetail);
                    $resultDetail->execute($dataDetail);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }
                if ($resultDetail->rowCount() > 0) {
                    $tutorCount = 0;
                    echo "<p style=''><b>".__($guid, 'Tutors').'</b>: ';
                    while ($rowDetail = $resultDetail->fetch()) {
                        echo formatName($rowDetail['title'], $rowDetail['preferredName'], $rowDetail['surname'], 'Staff');
                        ++$tutorCount;
                        if ($tutorCount < $resultDetail->rowCount()) {
                            echo ', ';
                        }
                    }
                    echo '</p>';
                }
            }
        }

        try {
            if ($gibbonRollGroupID == '*') {
                $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                $sql = "SELECT * FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY gibbonRollGroup.nameShort, surname, preferredName";
            } else {
                $data = array('gibbonRollGroupID' => $gibbonRollGroupID);
                $sql = "SELECT * FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonStudentEnrolment.gibbonRollGroupID=:gibbonRollGroupID ORDER BY surname, preferredName";
            }
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
        echo __($guid, 'Roll Group');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Student');
        echo '</th>';
        if ($view == 'Extended') {
            echo '<th>';
            echo __($guid, 'Gender');
            echo '</th>';
            echo '<th>';
            echo __($guid, 'Age').'<br/>';
            echo "<span style='font-style: italic; font-size: 85%'>".__($guid, 'DOB').'</span>';
            echo '</th>';
            echo '<th>';
            echo __($guid, 'Nationality');
            echo '</th>';
            echo '<th>';
            echo __($guid, 'Transport');
            echo '</th>';
            echo '<th>';
            echo __($guid, 'House');
            echo '</th>';
            echo '<th>';
            echo __($guid, 'Locker');
            echo '</th>';
            echo '<th>';
            echo __($guid, 'Medical');
            echo '</th>';
        }
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

            //COLOR ROW BY STATUS!
            echo "<tr class=$rowNum>";
            echo '<td>';
            echo $row['name'];
            echo '</td>';
            echo '<td>';
            echo formatName('', $row['preferredName'], $row['surname'], 'Student', true);
            echo '</td>';
            if ($view == 'Extended') {
                echo '<td>';
                echo $row['gender'];
                echo '</td>';
                echo '<td>';
                if (is_null($row['dob']) == false and $row['dob'] != '0000-00-00') {
                    echo getAge($guid, dateConvertToTimestamp($row['dob']), true).'<br/>';
                    echo "<span style='font-style: italic; font-size: 85%'>".dateConvertBack($guid, $row['dob']).'</span>';
                }
                echo '</td>';
                echo '<td>';
                if ($row['citizenship1'] != '') {
                    echo $row['citizenship1'].'<br/>';
                }
                if ($row['citizenship2'] != '') {
                    echo $row['citizenship2'].'<br/>';
                }
                echo '</td>';
                echo '<td>';
                echo $row['transport'];
                echo '</td>';
                echo '<td>';
                if ($row['gibbonHouseID'] != '') {
                    try {
                        $dataHouse = array('gibbonHouseID' => $row['gibbonHouseID']);
                        $sqlHouse = 'SELECT * FROM gibbonHouse WHERE gibbonHouseID=:gibbonHouseID';
                        $resultHouse = $connection2->prepare($sqlHouse);
                        $resultHouse->execute($dataHouse);
                    } catch (PDOException $e) {
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }
                    if ($resultHouse->rowCount() == 1) {
                        $rowHouse = $resultHouse->fetch();
                        echo $rowHouse['name'];
                    }
                }
                echo '</td>';
                echo '<td>';
                echo $row['lockerNumber'];
                echo '</td>';
                echo '<td>';
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
                    if ($rowForm['longTermMedication'] == 'Y') {
                        echo '<b><i>'.__($guid, 'Long Term Medication').'</i></b>: '.$rowForm['longTermMedicationDetails'].'<br/>';
                    }
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
                        echo '<b><i>'.__($guid, 'Condition')." $condCount</i></b> ";
                        echo ': '.__($guid, $rowConditions['name']);

                        $alert = getAlert($guid, $connection2, $rowConditions['gibbonAlertLevelID']);
                        if ($alert != false) {
                            echo " <span style='color: #".$alert['color']."; font-weight: bold'>(".__($guid, $alert['name']).' '.__($guid, 'Risk').')</span>';
                            echo '<br/>';
                            ++$condCount;
                        }
                    }
                } else {
                    echo '<i>No medical data</i>';
                }

                echo '</td>';
            }
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
