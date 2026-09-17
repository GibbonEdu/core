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

use Gibbon\Data\Validator;
use Gibbon\Domain\Attendance\AttendanceCodeGateway;
use Gibbon\Domain\Attendance\AttendanceLogPersonGateway;
use Gibbon\Services\Format;

require_once __DIR__ . '/../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

// Module includes
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
    // Proceed!
    $type = $_POST['type'] ?? '';
    $reason = $_POST['reason'] ?? '';
    $comment = $_POST['comment'] ?? '';

    // Get attendance code
    $resultCode = $container->get(AttendanceCodeGateway::class)->selectBy(['name' => $type, 'active' => 'Y']);

    if ($resultCode->rowCount() != 1) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        die();
    }

    $attendanceCode = $resultCode->fetch();
    $direction = $attendanceCode['direction'];

    // Check if values specified
    if ($type == '' || $direction == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {

        // UPDATE
        $attendanceLogPersonGateway = $container->get(AttendanceLogPersonGateway::class);

        $data = [
            'gibbonAttendanceCodeID' => $attendanceCode['gibbonAttendanceCodeID'],
            'type' => $type,
            'reason' => $reason,
            'comment' => $comment,
            'direction' => $direction,
            'gibbonPersonIDTaker' => $session->get('gibbonPersonID'),
            'timestampTaken' => date('Y-m-d H:i:s')
        ];

        $updated = $attendanceLogPersonGateway->updateWhere(['gibbonPersonID' => $gibbonPersonID, 'gibbonAttendanceLogPersonID' => $gibbonAttendanceLogPersonID], $data);

        if (!$updated) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
            exit();
        }

        // Success
        $URL .= '&return=success0';
        header("Location: {$URL}");
    }
}
