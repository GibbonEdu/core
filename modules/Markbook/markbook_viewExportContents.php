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

include '../../config.php';

//New PDO DB connection
$pdo = new Gibbon\sqlConnection();
$connection2 = $pdo->getConnection();

//Get alternative header names
$attainmentAlternativeName = getSettingByScope($connection2, 'Markbook', 'attainmentAlternativeName');
$attainmentAlternativeNameAbrev = getSettingByScope($connection2, 'Markbook', 'attainmentAlternativeNameAbrev');
$effortAlternativeName = getSettingByScope($connection2, 'Markbook', 'effortAlternativeName');
$effortAlternativeNameAbrev = getSettingByScope($connection2, 'Markbook', 'effortAlternativeNameAbrev');

@session_start();

$gibbonCourseClassID = $_GET['gibbonCourseClassID'];
$gibbonMarkbookColumnID = $_SESSION[$guid]['exportToExcelParams'];
if (isActionAccessible($guid, $connection2, '/modules/Markbook/markbook_view.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    $alert = getAlert($guid, $connection2, 002);

    //Proceed!
    echo "<table cellspacing='0'>";
    echo '<tr>';
    echo '<td colspan=4>';
    echo "<h1 style='margin-bottom: 20px'>";
    echo 'Markbook Data';
    echo '</h1>';
    echo '</td>';
    echo '</tr>';

    try {
        $dataStudents = array('gibbonCourseClassID' => $gibbonCourseClassID);
        $sqlStudents = "SELECT title, surname, preferredName, gibbonPerson.gibbonPersonID, dateStart FROM gibbonCourseClassPerson JOIN gibbonPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE role='Student' AND gibbonCourseClassID=:gibbonCourseClassID AND status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') ORDER BY surname, preferredName";
        $resultStudents = $connection2->prepare($sqlStudents);
        $resultStudents->execute($dataStudents);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }
    if ($resultStudents->rowCount() < 1) {
        echo "<div class='error'>";
        echo __($guid, 'There are no records to display.');
        echo '</div>';
    } else {
        echo '<tr>';
        echo '<td>';
        echo '<b>Student</b>';
        echo '</td>';
        echo '<td>';
        echo '<b>';
        if ($attainmentAlternativeName != '') {
            echo $attainmentAlternativeName;
        } else {
            echo __($guid, 'Attainment');
        }
        echo '</b>';
        echo '</td>';
        echo '<td>';
        echo '<b>';
        if ($effortAlternativeName != '') {
            echo $effortAlternativeName;
        } else {
            echo __($guid, 'Effort');
        }
        echo '</b>';
        echo '</td>';
        echo '<td>';
        echo '<b>Comment</b>';
        echo '</td>';
        echo '</tr>';

        while ($rowStudents = $resultStudents->fetch()) {
            //COLOR ROW BY STATUS!
                echo '<tr>';
            echo '<td>';
            echo formatName('', $rowStudents['preferredName'], $rowStudents['surname'], 'Student', true);
            echo '</td>';

            try {
                $dataEntry = array('gibbonMarkbookColumnID' => $gibbonMarkbookColumnID, 'gibbonPersonIDStudent' => $rowStudents['gibbonPersonID']);
                $sqlEntry = 'SELECT * FROM gibbonMarkbookEntry WHERE gibbonMarkbookColumnID=:gibbonMarkbookColumnID AND gibbonPersonIDStudent=:gibbonPersonIDStudent';
                $resultEntry = $connection2->prepare($sqlEntry);
                $resultEntry->execute($dataEntry);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }
            if ($resultEntry->rowCount() == 1) {
                $rowEntry = $resultEntry->fetch();
                $styleAttainment = '';
                if ($rowEntry['attainmentConcern'] == 'Y') {
                    $styleAttainment = "style='color: #".$alert['color'].'; font-weight: bold; border: 2px solid #'.$alert['color'].'; padding: 2px 4px; background-color: #'.$alert['colorBG']."'";
                }
                echo "<td style='text-align: center'>";
                $attainment = $rowEntry['attainmentValue'];
                if ($rowEntry['attainmentValue'] == 'Complete') {
                    $attainment = 'CO';
                } elseif ($rowEntry['attainmentValue'] == 'Incomplete') {
                    $attainment = 'IC';
                }
                echo "<span $styleAttainment title='".htmlPrep($rowEntry['attainmentDescriptor'])."'>$attainment</span>";
                echo '</td>';
                $styleEffort = '';
                if ($rowEntry['effortConcern'] == 'Y') {
                    $styleEffort = "style='color: #".$alert['color'].'; font-weight: bold; border: 2px solid #'.$alert['color'].'; padding: 2px 4px; background-color: #'.$alert['colorBG']."'";
                }
                $effort = $rowEntry['effortValue'];
                if ($rowEntry['effortValue'] == 'Complete') {
                    $effort = 'CO';
                } elseif ($rowEntry['effortValue'] == 'Incomplete') {
                    $effort = 'IC';
                }
                echo "<td style='text-align: center;'>";
                echo "<span $styleEffort title='".htmlPrep($rowEntry['effortDescriptor'])."'>$effort</span>";
                echo '</td>';
                echo "<td style='text-align: center;'>";
                $style = '';
                if ($rowEntry['comment'] != '') {
                    echo "<span $style title='".htmlPrep($rowEntry['comment'])."'>".substr($rowEntry['comment'], 0, 10).'...</span>';
                }
                echo '</td>';
            } else {
                echo '<td colspan=3>';
                echo 'No data.';
                echo '</td>';
            }
            echo '</tr>';
        }
    }
    echo '</table>';
}

$_SESSION[$guid]['exportToExcelParams'] = '';
