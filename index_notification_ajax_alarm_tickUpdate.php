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

// Gibbon system-wide includes
require_once './gibbon.php';

$gibbonAlarmID = $_POST['gibbonAlarmID'] ?? '';

// Proceed!
if (empty($_SESSION[$guid]['gibbonPersonID']) || $_SESSION[$guid]['gibbonRoleIDCurrentCategory'] != 'Staff') {
    die();
} elseif (empty($gibbonAlarmID)) {
    die();
} else {
    // Check confirmation of current alarm
    $data = ['gibbonAlarmID' => $gibbonAlarmID, 'today' => date('Y-m-d')];
    $sql = "SELECT gibbonPerson.gibbonPersonID, gibbonAlarmConfirmID FROM gibbonPerson JOIN gibbonStaff ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) LEFT JOIN gibbonAlarmConfirm ON (gibbonAlarmConfirm.gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonAlarmID=:gibbonAlarmID) WHERE gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<=:today) AND (dateEnd IS NULL  OR dateEnd>=:today) ORDER BY surname, preferredName";

    $result = $pdo->select($sql, $data)->fetchKeyPair();

    echo json_encode($result);
}
