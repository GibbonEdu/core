<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

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
use Gibbon\Data\Validator;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

//Module includes
include './moduleFunctions.php';

$gibbonAttendanceLogPersonID = $_POST['gibbonAttendanceLogPersonID'] ?? '';
$gibbonPersonID = $_POST['gibbonPersonID'] ?? '';
$currentDate = $_POST['currentDate'] ?? Format::date(date('Y-m-d'));

$URL = $session->get('absoluteURL')."/index.php?q=/modules/Attendance/attendance_take_byPerson.php&gibbonPersonID=$gibbonPersonID&currentDate=$currentDate";

if (isActionAccessible($guid, $connection2, '/modules/Attendance/attendance_take_byPerson_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
}
else if ($gibbonAttendanceLogPersonID == '' or $gibbonPersonID == '' or $currentDate == '') {
    $URL .= '&return=error1';
    header("Location: {$URL}");
} else {
    //Proceed!

    $type = $_POST['type'] ?? '';
    $reason = $_POST['reason'] ?? '';
    $comment = $_POST['comment'] ?? '';

    // Get attendance codes

        $dataCode = array( 'name' => $type );
        $sqlCode = "SELECT direction FROM gibbonAttendanceCode WHERE active = 'Y' AND name=:name LIMIT 1";
        $resultCode = $connection2->prepare($sqlCode);
        $resultCode->execute($dataCode);

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
            $data = array('gibbonPersonID' => $gibbonPersonID, 'gibbonAttendanceLogPersonID' => $gibbonAttendanceLogPersonID, 'type' => $type, 'reason' => $reason, 'comment' => $comment, 'direction' => $direction, 'gibbonPersonIDTaker' => $session->get('gibbonPersonID') );
            $sql = 'UPDATE gibbonAttendanceLogPerson SET gibbonAttendanceCodeID=(SELECT gibbonAttendanceCodeID FROM gibbonAttendanceCode WHERE name=:type), type=:type, reason=:reason, comment=:comment, direction=:direction, gibbonPersonIDTaker=:gibbonPersonIDTaker, timestampTaken=NOW() WHERE gibbonPersonID=:gibbonPersonID AND gibbonAttendanceLogPersonID=:gibbonAttendanceLogPersonID';
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
