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

if (isActionAccessible($guid, $connection2, '/modules/Attendance/report_studentHistory_print.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    $gibbonPersonID = $_GET['gibbonPersonID'];

    try {
        $data = array('gibbonPersonID' => $gibbonPersonID);
        $sql = 'SELECT surname, preferredName, dateStart, dateEnd FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID';
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }

    $row = $result->fetch();

    if ($gibbonPersonID != '') {
        $output = '';
        echo '<h2>';
        echo __('Attendance History for').' '.formatName('', $row['preferredName'], $row['surname'], 'Student');
        echo '</h2>';

        report_studentHistory($guid, $gibbonPersonID, false, $_SESSION[$guid]['absoluteURL'].'/report.php?q=/modules/'.$_SESSION[$guid]['module']."/report_studentHistory_print.php&gibbonPersonID=$gibbonPersonID", $connection2, $row['dateStart'], $row['dateEnd']);
    }
}
