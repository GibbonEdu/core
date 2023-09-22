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

use Gibbon\Domain\System\SettingGateway;
use Gibbon\Services\Format;
use Gibbon\Domain\Messenger\GroupGateway;
use Gibbon\Module\Attendance\AttendanceView;
use Gibbon\Domain\Activities\ActivityGateway;
use Gibbon\Domain\Attendance\AttendanceLogPersonGateway;

// Gibbon system-wide includes
require __DIR__ . '/../../gibbon.php';

// Module includes
require_once __DIR__ . '/moduleFunctions.php';

$urlParams = [
    'target'             => $_POST['target'] ?? '',
    'gibbonActivityID'   => $_POST['gibbonActivityID'] ?? '',
    'gibbonGroupID'      => $_POST['gibbonGroupID'] ?? '',
    'gibbonPersonIDList' => $_POST['gibbonPersonIDList'] ?? '',
];
$urlParams['gibbonPersonIDList'] = explode(',', $urlParams['gibbonPersonIDList']);
$currentDate = $_POST['currentDate'] ?? '';
$today = date('Y-m-d');

$URL = $session->get('absoluteURL')."/index.php?q=/modules/Attendance/attendance_take_adHoc.php&currentDate=".Format::date($currentDate)."&".http_build_query($urlParams);

if (isActionAccessible($guid, $connection2, '/modules/Attendance/attendance_take_adHoc.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    // Proceed!

    // Check if required values are specified
    if (empty($urlParams['target']) || empty($currentDate)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    switch ($urlParams['target']) {
        case 'Activity':
            $values = $container->get(ActivityGateway::class)->getByID($urlParams['gibbonActivityID']);
            break;
        case 'Messenger':
            $values = $container->get(GroupGateway::class)->getByID($urlParams['gibbonGroupID']);
            break;
        case 'Select':
            $values = $urlParams['gibbonPersonIDList'];
            break;
    }

    // Check if required values are specified
    if (empty($values)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Check that date is not in the future and is a school day
    if ($currentDate > $today || isSchoolOpen($guid, $currentDate, $connection2) == false) {
        $URL .= '&return=error3';
        header("Location: {$URL}");
        exit;
    }
    
    // Setup attendance class
    require_once __DIR__ . '/src/AttendanceView.php';
    $attendance = new AttendanceView($gibbon, $pdo, $container->get(SettingGateway::class));

    $attendanceLogGateway = $container->get(AttendanceLogPersonGateway::class);

    $count = $_POST['count'] ?? '';
    $partialFail = false;

    for ($i = 0; $i < $count; ++$i) {
        $gibbonPersonID = $_POST[$i.'-gibbonPersonID'] ?? '';
        $type = $_POST[$i.'-type'] ?? '';
        $reason = $_POST[$i.'-reason'] ?? '';
        $comment = $_POST[$i.'-comment'] ?? '';

        $attendanceCode = $attendance->getAttendanceCodeByType($type);
        $direction = $attendanceCode['direction'];

        // Check for last record on same day
        $data = ['gibbonPersonID' => $gibbonPersonID, 'date' => $currentDate.'%'];
        $sql = "SELECT * FROM gibbonAttendanceLogPerson WHERE gibbonPersonID=:gibbonPersonID AND date LIKE :date ORDER BY gibbonAttendanceLogPersonID DESC";
        
        $recentLog = $pdo->selectOne($sql, $data);

        // Check context and type, updating only if not a match
        $existing = false;
        $gibbonAttendanceLogPersonID = '';
        if (!empty($recentLog) && $recentLog['context'] == 'Person' && $recentLog['type'] == $type && $recentLog['direction'] == $direction ) {
            $existing = true;
            $gibbonAttendanceLogPersonID = $recentLog['gibbonAttendanceLogPersonID'];
        }

        $data = [
            'gibbonAttendanceCodeID' => $attendanceCode['gibbonAttendanceCodeID'],
            'gibbonPersonID'         => $gibbonPersonID,
            'context'                => 'Person',
            'direction'              => $direction,
            'type'                   => $type,
            'reason'                 => $reason,
            'comment'                => $comment,
            'gibbonPersonIDTaker'    => $session->get('gibbonPersonID'),
            'date'                   => $currentDate,
            'timestampTaken'         => date('Y-m-d H:i:s'),
        ];

        if (!$existing) {
            $inserted = $attendanceLogGateway->insert($data);
            $partialFail &= !$inserted;
        } else {
            $updated = $attendanceLogGateway->update($gibbonAttendanceLogPersonID, $data);
            $partialFail &= !$updated;
        }
    }

    $URL .= $partialFail
        ? '&return=warning1'
        : '&return=success0&time='.date('H-i-s');

    header("Location: {$URL}");
}
