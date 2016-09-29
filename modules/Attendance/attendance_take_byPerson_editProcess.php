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
include '../../functions.php';
include '../../config.php';

//Module includes
include './moduleFunctions.php';

//New PDO DB connection
$pdo = new Gibbon\sqlConnection();
$connection2 = $pdo->getConnection();

@session_start();

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]['timezone']);

$gibbonAttendanceLogPersonID = isset($_POST['gibbonAttendanceLogPersonID'])? $_POST['gibbonAttendanceLogPersonID'] : '';
$gibbonPersonID = isset($_POST['gibbonPersonID'])? $_POST['gibbonPersonID'] : '';
$currentDate = isset($_POST['currentDate'])? dateConvert($guid, $_POST['currentDate']) : date('Y-m-d');

$URL = $_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Attendance/attendance_take_byPerson.php&gibbonPersonID=$gibbonPersonID&currentDate=$currentDate";

if (isActionAccessible($guid, $connection2, '/modules/Attendance/attendance_take_byPerson_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
}
else if ($gibbonAttendanceLogPersonID == '' or $gibbonPersonID == '' or $currentDate == '') {
    $URL .= '&return=error1';
    header("Location: {$URL}");
} else {
    //Proceed!
    
    $type = isset($_POST['type'])? $_POST['type'] : '';
    $reason = isset($_POST['reason'])? $_POST['reason'] : '';
    $comment = isset($_POST['comment'])? $_POST['comment'] : '';

    // Get attendance codes
    try {
        $dataCode = array( 'name' => $type );
        $sqlCode = "SELECT direction FROM gibbonAttendanceCode WHERE active = 'Y' AND name=:name LIMIT 1";
        $resultCode = $connection2->prepare($sqlCode);
        $resultCode->execute($dataCode);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }

    if ($resultCode->rowCount() != 1) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        die();
    }

    $attendanceCode = $resultCode->fetch();
    $direction = $attendanceCode['direction'];

    //Check if values specified
    if ($type == '' || $direction == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {

        //UPDATE
        try {
            $data = array('gibbonPersonID' => $gibbonPersonID, 'gibbonAttendanceLogPersonID' => $gibbonAttendanceLogPersonID, 'type' => $type, 'reason' => $reason, 'comment' => $comment, 'direction' => $direction, 'gibbonPersonIDTaker' => $_SESSION[$guid]['gibbonPersonID'] );
            $sql = 'UPDATE gibbonAttendanceLogPerson SET type=:type, reason=:reason, comment=:comment, direction=:direction, gibbonPersonIDTaker=:gibbonPersonIDTaker, timestampTaken=NOW() WHERE gibbonPersonID=:gibbonPersonID AND gibbonAttendanceLogPersonID=:gibbonAttendanceLogPersonID';
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
}
