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

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

$page->breadcrumbs->add(__('Staff Likes'));

if (isActionAccessible($guid, $connection2, '/modules/Planner/report_goldStars_staff.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    try {
        $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
        $sql = "SELECT gibbonPerson.gibbonPersonID AS personID, surname, preferredName, COUNT(*) as likes FROM gibbonLike JOIN gibbonPerson ON (gibbonLike.gibbonPersonIDRecipient=gibbonPerson.gibbonPersonID) JOIN gibbonStaff ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE status='Full' AND gibbonSchoolYearID=:gibbonSchoolYearID GROUP BY gibbonPerson.gibbonPersonID ORDER BY likes DESC";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }

    echo "<table cellspacing=\"0\"style='width: 100%'>";
    echo "<tr class='head'>";
    echo "<th style='width: 100px'>";
    echo __('Position');
    echo '</th>';
    echo '<th>';
    echo __('Teacher');
    echo '</th>';
    echo '<th>';
    echo __('Likes');
    echo '</th>';
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

            //COLOR ROW BY STATUS!
            echo "<tr class=$rowNum>";
        echo '<td>';
        echo $count;
        echo '</td>';
        echo '<td>';
        echo formatName('', $row['preferredName'], $row['surname'], 'Staff', false, true);
        echo '</td>';
        echo '<td>';
        echo $row['likes'];
        echo '</td>';
        echo '</tr>';
    }
    if ($count == 0) {
        echo "<tr class=$rowNum>";
        echo '<td colspan=3>';
        echo __('There are no records to display.');
        echo '</td>';
        echo '</tr>';
    }
    echo '</table>';
}
