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

//Gibbon system-wide includes
include '../../gibbon.php';

//Module includes
include './moduleFunctions.php';

$gibbonAttendanceLogPersonID = isset($_GET['gibbonAttendanceLogPersonID'])? $_GET['gibbonAttendanceLogPersonID'] : '';
$gibbonPersonID = isset($_GET['gibbonPersonID'])? $_GET['gibbonPersonID'] : '';
$currentDate = isset($_GET['currentDate'])? dateConvertBack($guid, $_GET['currentDate']) : '';

$URL = $_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Attendance/attendance_take_byPerson.php&gibbonPersonID=$gibbonPersonID&currentDate=$currentDate";

if (isActionAccessible($guid, $connection2, '/modules/Attendance/attendance_take_byPerson_delete.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
}
else if ($gibbonAttendanceLogPersonID == '' or $gibbonPersonID == '' or $currentDate == '') {
    $URL .= '&return=error1';
    header("Location: {$URL}");
} else {
    //Proceed!
    try {
        $data = array('gibbonPersonID' => $gibbonPersonID, 'gibbonAttendanceLogPersonID' => $gibbonAttendanceLogPersonID);
        $sql = "DELETE FROM gibbonAttendanceLogPerson WHERE gibbonPersonID=:gibbonPersonID AND gibbonAttendanceLogPersonID=:gibbonAttendanceLogPersonID";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit();
    }

    //Success 0
    $URL .= '&return=success0';
    header("Location: {$URL}");
}
