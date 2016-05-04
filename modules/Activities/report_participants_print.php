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

if (isActionAccessible($guid, $connection2, '/modules/Activities/report_participants_print.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    $gibbonActivityID = $_GET['gibbonActivityID'];

    try {
        $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonActivityID' => $gibbonActivityID);
        $sql = "SELECT name, programStart, programEnd, gibbonPerson.gibbonPersonID, surname, preferredName, gibbonRollGroupID, gibbonActivityStudent.status FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonActivityStudent ON (gibbonActivityStudent.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonActivity ON (gibbonActivityStudent.gibbonActivityID=gibbonActivity.gibbonActivityID) WHERE gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonActivity.gibbonSchoolYearID=:gibbonSchoolYearID AND NOT gibbonActivityStudent.status='Not Accepted' AND gibbonActivity.gibbonActivityID=:gibbonActivityID ORDER BY gibbonActivityStudent.status, surname, preferredName";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }

    $row = $result->fetch();

    if ($gibbonActivityID != '') {
        $output = '';

        $date = '';
        if (substr($row['programStart'], 0, 4) == substr($row['programEnd'], 0, 4)) {
            if (substr($row['programStart'], 5, 2) == substr($row['programEnd'], 5, 2)) {
                $date = ' ('.date('F', mktime(0, 0, 0, substr($row['programStart'], 5, 2))).' '.substr($row['programStart'], 0, 4).')';
            } else {
                $date = ' ('.date('F', mktime(0, 0, 0, substr($row['programStart'], 5, 2))).' - '.date('F', mktime(0, 0, 0, substr($row['programEnd'], 5, 2))).' '.substr($row['programStart'], 0, 4).')';
            }
        } else {
            $date = ' ('.date('F', mktime(0, 0, 0, substr($row['programStart'], 5, 2))).' '.substr($row['programStart'], 0, 4).' - '.date('F', mktime(0, 0, 0, substr($row['programEnd'], 5, 2))).' '.substr($row['programEnd'], 0, 4).')';
        }
        echo '<h2>';

        echo __($guid, 'Participants for').' '.$row['name'].$date;
        echo '</h2>';

        if ($result->rowCount() < 1) {
            echo "<div class='error'>";
            echo __($guid, 'There are no records to display.');
            echo '</div>';
        } else {
            echo "<div class='linkTop'>";
            echo "<a href='javascript:window.print()'>".__($guid, 'Print')."<img style='margin-left: 5px' title='".__($guid, 'Print')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/print.png'/></a>";
            echo '</div>';

            $lastPerson = '';

            echo "<table class='mini' cellspacing='0' style='width: 100%'>";
            echo "<tr class='head'>";
            echo '<th>';
            echo __($guid, 'Roll Group');
            echo '</th>';
            echo '<th>';
            echo __($guid, 'Name');
            echo '</th>';
            echo '<th>';
            echo __($guid, 'Status');
            echo '</th>';
            echo '<th>';
            echo __($guid, 'Parental Contacts');
            echo '</th>';
            echo '</tr>';

            $count = 0;
            $rowNum = 'odd';
            try {
                $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonActivityID' => $gibbonActivityID);
                $sql = "SELECT name, programStart, programEnd, gibbonPerson.gibbonPersonID, surname, preferredName, gibbonRollGroupID, gibbonActivityStudent.status FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonActivityStudent ON (gibbonActivityStudent.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonActivity ON (gibbonActivityStudent.gibbonActivityID=gibbonActivity.gibbonActivityID) WHERE gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonActivity.gibbonSchoolYearID=:gibbonSchoolYearID AND NOT gibbonActivityStudent.status='Not Accepted' AND gibbonActivity.gibbonActivityID=:gibbonActivityID ORDER BY gibbonActivityStudent.status, surname, preferredName";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }
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
                try {
                    $dataRollGroup = array('gibbonRollGroupID' => $row['gibbonRollGroupID']);
                    $sqlRollGroup = 'SELECT * FROM gibbonRollGroup WHERE gibbonRollGroupID=:gibbonRollGroupID';
                    $resultRollGroup = $connection2->prepare($sqlRollGroup);
                    $resultRollGroup->execute($dataRollGroup);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }

                if ($resultRollGroup->rowCount() < 1) {
                    echo '<i>'.__($guid, 'Unknown').'</i>';
                } else {
                    $rowRollGroup = $resultRollGroup->fetch();
                    echo $rowRollGroup['name'];
                }

                echo '</td>';
                echo '<td>';
                echo formatName('', $row['preferredName'], $row['surname'], 'Student', true);
                echo '</td>';
                echo '<td>';
                echo $row['status'];
                echo '</td>';
                echo '<td>';
                try {
                    $dataFamily = array('gibbonPersonID' => $row['gibbonPersonID']);
                    $sqlFamily = 'SELECT * FROM gibbonFamily JOIN gibbonFamilyChild ON (gibbonFamily.gibbonFamilyID=gibbonFamilyChild.gibbonFamilyID) WHERE gibbonPersonID=:gibbonPersonID';
                    $resultFamily = $connection2->prepare($sqlFamily);
                    $resultFamily->execute($dataFamily);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }
                if ($resultFamily->rowCount() > 0) {
                    while ($rowFamily = $resultFamily->fetch()) {
                        //Get adults conditions
						try {
							$dataMember = array('gibbonFamilyID' => $rowFamily['gibbonFamilyID']);
							$sqlMember = "SELECT * FROM gibbonFamilyAdult JOIN gibbonPerson ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonFamilyID=:gibbonFamilyID AND contactCall='Y' ORDER BY contactPriority, surname, preferredName";
							$resultMember = $connection2->prepare($sqlMember);
							$resultMember->execute($dataMember);
						} catch (PDOException $e) {
							echo "<div class='error'>".$e->getMessage().'</div>';
						}
                        while ($rowMember = $resultMember->fetch()) {
                            if ($rowMember['phone1'] != '' or $rowMember['phone2'] != '' or $rowMember['phone3'] != '' or $rowMember['phone4'] != '') {
                                echo '<b>'.formatName($rowMember['title'], $rowMember['preferredName'], $rowMember['surname'], 'Parent', false).'</b><br/>';
                                for ($i = 1; $i < 5; ++$i) {
                                    if ($rowMember['phone'.$i] != '') {
                                        if ($rowMember['phone'.$i.'Type'] != '') {
                                            echo '<i>'.$rowMember['phone'.$i.'Type'].':</i> ';
                                        }
                                        if ($rowMember['phone'.$i.'CountryCode'] != '') {
                                            echo '+'.$rowMember['phone'.$i.'CountryCode'].' ';
                                        }
                                        echo $rowMember['phone'.$i].'<br/>';
                                    }
                                }
                            }
                        }
                    }
                }
                echo '</td>';
                echo '</tr>';

                $lastPerson = $row['gibbonPersonID'];
            }
        }
        if ($count == 0) {
            echo "<tr class=$rowNum>";
            echo '<td colspan=4>';
            echo __($guid, 'You do not have access to this action.');
            echo '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }
}
