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

use Gibbon\Services\Format;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Students/report_students_new') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Letters Home by Roll Group'));

    try {
        $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
        $sql = "SELECT gibbonPerson.gibbonPersonID, surname, preferredName, gibbonRollGroup.nameShort AS rollGroup, gibbonFamily.gibbonFamilyID, gibbonFamily.name AS familyName
            FROM gibbonPerson
                JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID)
                JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID)
                LEFT JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID)
                LEFT JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID)
            WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID
                AND gibbonPerson.status='Full'
            ORDER BY rollGroup, surname, preferredName";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }
    if ($result->rowCount() < 1) {
        echo "<div class='error'>";
        echo __('There are no records to display.');
        echo '</div>';
    } else {
        $siblings = array();
        $currentRollGroup = '';
        $lastRollGroup = '';
        $count = 0;
        $countTotal = 0;
        $rowNum = 'odd';
        while ($row = $result->fetch()) {
            $currentRollGroup = $row['rollGroup'];

            //SPLIT INTO ROLL GROUPS
            if ($currentRollGroup != $lastRollGroup) {
                if ($lastRollGroup != '') {
                    echo '</table>';
                }
                echo '<h2>'.$row['rollGroup'].'</h2>';
                $count = 0;
                $rowNum = 'odd';
                echo "<table cellspacing='0' style='width: 100%'>";
                echo "<tr class='head'>";
                echo '<th>';
                echo __('Total Count');
                echo '</th>';
                echo '<th>';
                echo __('Form Count');
                echo '</th>';
                echo '<th>';
                echo __('Student');
                echo '</th>';
                echo '<th>';
                echo __('Younger Siblings');
                echo '</th>';
                echo '<th>';
                echo __('Family');
                echo '</th>';
                echo '<th>';
                echo __('Sibling Count');
                echo '</th>';
                echo '</tr>';
            }
            $lastRollGroup = $row['rollGroup'];

            //PUMP OUT STUDENT DATA
            //Check for older siblings
            $proceed = false;
            try {
                $dataSibling = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonFamilyID' => $row['gibbonFamilyID']);
                $sqlSibling = "SELECT gibbonPerson.gibbonPersonID, surname, preferredName, gibbonFamily.name, gibbonFamily.gibbonFamilyID
                    FROM gibbonPerson
                        JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID)
                        JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID)
                        JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID)
                    WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID
                        AND gibbonPerson.status='Full'
                        AND gibbonFamily.gibbonFamilyID=:gibbonFamilyID
                    ORDER BY gibbonFamily.gibbonFamilyID, dob";
                $resultSibling = $connection2->prepare($sqlSibling);
                $resultSibling->execute($dataSibling);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            if ($resultSibling->rowCount() == 1) {
                $proceed = true;
            } else {
                $rowSibling = $resultSibling->fetch();
                if ($rowSibling['gibbonPersonID'] == $row['gibbonPersonID']) {
                    $proceed = true;
                }
                else { //Store sibling away for later use
                    $siblings[$rowSibling['gibbonFamilyID']][$row['gibbonPersonID']] = Format::name('', $row['preferredName'], $row['surname'], 'Student', true);
                }
            }

            if ($proceed == true) {
                if ($count % 2 == 0) {
                    $rowNum = 'even';
                } else {
                    $rowNum = 'odd';
                }
                echo "<tr class=$rowNum>";
                echo "<td style='width: 20%'>";
                echo $countTotal + 1;
                echo '</td>';
                echo "<td style='width: 20%'>";
                echo $count + 1;
                echo '</td>';
                echo '<td>';
                echo Format::name('', $row['preferredName'], $row['surname'], 'Student', true);
                echo '</td>';
                echo '<td>';
                if (!empty($siblings[$row['gibbonFamilyID']]) && is_array($siblings[$row['gibbonFamilyID']])) {
                    foreach ($siblings[$row['gibbonFamilyID']] AS $sibling) {
                        echo $sibling."</br>";
                    }
                }
                echo '</td>';
                echo '<td>';
                echo $row['familyName'];
                echo '</td>';
                echo "<td style='width: 20%'>";
                echo $resultSibling->rowCount() - 1;
                echo '</td>';
                echo '</tr>';
                ++$count;
                ++$countTotal;
            }
        }
        echo '</table>';
    }
}
