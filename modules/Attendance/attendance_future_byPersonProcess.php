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
use Gibbon\Domain\User\UserGateway;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Module\Attendance\AttendanceView;
use Gibbon\Domain\Attendance\AttendanceLogPersonGateway;

//Gibbon system-wide includes
include __DIR__ . '/../../gibbon.php';

//Module includes
include __DIR__ . '/moduleFunctions.php';

$address = $_POST['address'] ?? '';

$gibbonPersonID = $_POST['gibbonPersonIDList'] ?? $_POST['gibbonPersonID'] ?? '';

$urlParams = [
    'scope'            => $_POST['scope'] ?? '',
    'absenceType'      => $_POST['absenceType'] ?? 'full',
    'target'           => $_POST['target'] ?? '',
    'gibbonActivityID' => $_POST['gibbonActivityID'] ?? '',
    'gibbonGroupID'    => $_POST['gibbonGroupID'] ?? '',
    'date'             => $_POST['date'] ?? $_POST['dateStart'] ?? '',
    'timeStart'        => $_POST['timeStart'] ?? '',
    'timeEnd'          => $_POST['timeEnd'] ?? '',
];

$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($address)."/attendance_future_byPerson.php&gibbonPersonIDList=$gibbonPersonID&".http_build_query($urlParams);

if (isActionAccessible($guid, $connection2, '/modules/Attendance/attendance_future_byPerson.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    //Check if person specified
    if (empty($gibbonPersonID) || empty($urlParams['scope'])) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        $userGateway = $container->get(UserGateway::class);
        $attendanceLogGateway = $container->get(AttendanceLogPersonGateway::class);

        $gibbonPersonID = explode(',', $gibbonPersonID);

        $personCheck = true ;
        foreach ($gibbonPersonID as $gibbonPersonIDCurrent) {
            $person = $userGateway->getByID($gibbonPersonIDCurrent);
            if (empty($person)) {
                $personCheck = false;
            }
        }

        if (!$personCheck) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
            exit;
        }

        require_once __DIR__ . '/src/AttendanceView.php';
        $attendance = new AttendanceView($gibbon, $pdo, $container->get(SettingGateway::class));

        $partialFail = false;
        $partialFailSchoolClosed = false;

        $type = $_POST['type'] ?? '';
        $reason = $_POST['reason'] ?? '';
        $comment = $_POST['comment'] ?? '';
        $courseList = $_POST['courses'] ?? '';

        $attendanceCode = $attendance->getAttendanceCodeByType($type);
        $direction = $attendanceCode['direction'];

        $dateStart = !empty($_POST['dateStart']) ? Format::dateConvert($_POST['dateStart']) : null;
        $dateEnd = !empty($_POST['dateEnd']) ? Format::dateConvert($_POST['dateEnd']) : $dateStart;
        $today = date('Y-m-d');

        // Check to see if date is in the future and is a school day.
        if ($dateStart == '' or ($dateEnd != '' and $dateEnd < $dateStart) or $dateStart < $today) {
            $URL .= '&return=error8';
            header("Location: {$URL}");
            exit;
        }

        // Loop through days
        $dateStartStamp = Format::timestamp($dateStart);
        $dateEndStamp = Format::timestamp($dateEnd);
        for ($i = $dateStartStamp; $i <= $dateEndStamp; $i = ($i + 86400)) {
            $date = date('Y-m-d', $i);

            // Only add if school is open on this day
            if (!isSchoolOpen($guid, $date, $connection2)) {
                $partialFailSchoolClosed = true; 
                continue;
            }

            foreach ($gibbonPersonID as $gibbonPersonIDCurrent) {
                // Check for record on same day
                $existingLog = $attendanceLogGateway->selectNonClassAttendanceLogsByPersonAndDate($gibbonPersonIDCurrent, $date)->fetchAll();

                if (!empty($existingLog) AND $urlParams['absenceType'] == 'full') {
                    if ($urlParams['scope'] == 'single') {
                        $URL .= '&return=error7';
                        header("Location: {$URL}");
                        exit;
                    }
                }

                // Handle full-day absenses normally
                if ($urlParams['absenceType'] == 'full') {
                    $data = [
                        'gibbonAttendanceCodeID' => $attendanceCode['gibbonAttendanceCodeID'],
                        'gibbonPersonID' => $gibbonPersonIDCurrent,
                        'context' => 'Future',
                        'direction' => $direction,
                        'type' => $type,
                        'reason' => $reason,
                        'comment' => $comment,
                        'gibbonPersonIDTaker' => $session->get('gibbonPersonID'),
                        'date' => $date,
                        'timestampTaken' => date('Y-m-d H:i:s'),
                    ];

                    $gibbonAttendanceLogPersonID = $attendanceLogGateway->insert($data);
                    if (empty($gibbonAttendanceLogPersonID)) $partialFail = true;

                // Handle partial absences per-class
                } else if ($urlParams['absenceType'] == 'partial') {

                    $courses = $courseList[$gibbonPersonIDCurrent] ?? [];
                    if (!empty($courses) && is_array($courses)) {

                        foreach ($courses as $classID) {
                            list($gibbonCourseClassID, $gibbonTTDayRowClassID) = explode('-', $classID);

                            $data = [
                                'gibbonAttendanceCodeID' => $attendanceCode['gibbonAttendanceCodeID'],
                                'gibbonPersonID' => $gibbonPersonIDCurrent,
                                'context' => 'Class',
                                'direction' => $direction,
                                'type' => $type,
                                'reason' => $reason,
                                'comment' => $comment,
                                'gibbonPersonIDTaker' => $session->get('gibbonPersonID'),
                                'date' => $date,
                                'timestampTaken' => date('Y-m-d H:i:s'),
                                'gibbonCourseClassID' => $gibbonCourseClassID,
                                'gibbonTTDayRowClassID' => $gibbonTTDayRowClassID,
                            ];

                            $gibbonAttendanceLogPersonID = $attendanceLogGateway->insert($data);
                            if (empty($gibbonAttendanceLogPersonID)) $partialFail = true;
                        }
                    }
                }
                
            }
        }

        if ($partialFailSchoolClosed == true) {
            $URL .= '&return=warning2';
            header("Location: {$URL}");
        }
        else if ($partialFail == true) {
            $URL .= '&return=warning1';
            header("Location: {$URL}");
        } else {
            $URL .= '&return=success0';
            header("Location: {$URL}");
        }
    }

}
