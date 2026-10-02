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
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Module\Attendance\AttendanceView;
use Gibbon\Domain\Attendance\AttendanceLogPersonGateway;
use Gibbon\Domain\User\UserGateway;

//Gibbon system-wide includes
require __DIR__ . '/../../gibbon.php';

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

$gibbonPersonID = $_GET['gibbonPersonID'] ?? '';
$currentDate = $_POST['currentDate'] ?? '';
$today = date('Y-m-d');
$address = $_POST['address'] ?? '';
$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($address)."/attendance_take_byPerson.php&gibbonPersonID=$gibbonPersonID&currentDate=".Format::date($currentDate);

if (isActionAccessible($guid, $connection2, '/modules/Attendance/attendance_take_byPerson.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    // Proceed!
    // Check if gibbonPersonID and currentDate specified
    if ($gibbonPersonID == '' and $currentDate == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        $result = $container->get(UserGateway::class)->getUserDetails($gibbonPersonID, $session->get('gibbonSchoolYearID'));

        if (empty($result)) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
        } else {
            // Check that date is not in the future
            if ($currentDate > $today) {
                $URL .= '&return=error3';
                header("Location: {$URL}");
            } else {
                // Check that date is a school day
                if (isSchoolOpen($guid, $currentDate, $connection2) == false) {
                    $URL .= '&return=error3';
                    header("Location: {$URL}");
                } else {
                    //Write to database
                    require_once __DIR__ . '/src/AttendanceView.php';
                    $attendanceLogPersonGateway = $container->get(AttendanceLogPersonGateway::class);
                    $attendance = new AttendanceView($gibbon, $pdo, $container->get(SettingGateway::class));

                    $fail = false;
                    $type = $_POST['type'] ?? '';
                    $reason = $_POST['reason'] ?? '';
                    $comment = $_POST['comment'] ?? '';

                    $attendanceCode = $attendance->getAttendanceCodeByType($type);
                    $direction = $attendanceCode['direction'];

                    // Check for last record on same day
                    $result = $container->get(AttendanceLogPersonGateway::class)->selectAttendanceLogsByPersonAndDate($gibbonPersonID, $currentDate.'%', 'N');

                    // Check context and type, updating only if not a match
                    $existing = false ;
                    $gibbonAttendanceLogPersonID = '';
                    if ($result->rowCount() > 0) {
                        $row = $result->fetch();
                        if ($row['context'] == 'Person' && $row['type'] == $type) {
                            $existing = true ;
                            $gibbonAttendanceLogPersonID = $row['gibbonAttendanceLogPersonID'];
                        }
                    }

                    if (!$existing) {
                        // If no records then create one
                        $data = [
                            'gibbonAttendanceCodeID' => $attendanceCode['gibbonAttendanceCodeID'],
                            'gibbonPersonID' => $gibbonPersonID,
                            'type' => $type,
                            'context' => 'Person',
                            'reason' => $reason,
                            'comment' => $comment,
                            'direction' => $direction,
                            'gibbonPersonIDTaker' => $session->get('gibbonPersonID'),
                            'date' => $currentDate,
                            'timestampTaken' => date('Y-m-d H:i:s')
                        ];

                        $inserted = $attendanceLogPersonGateway->insert($data);

                        if (!$inserted) {
                            $fail = true;
                        }

                    } else {
                        
                        // If direction same then update
                        if ($row['direction'] == $direction && $row['gibbonCourseClassID'] == 0) {
                            $dataUpdate = [
                                'gibbonAttendanceCodeID' => $attendanceCode['gibbonAttendanceCodeID'],
                                'gibbonPersonID' => $gibbonPersonID,
                                'direction' => $direction,
                                'context' => 'Person',
                                'type' => $type,
                                'reason' => $reason,
                                'comment' => $comment,
                                'gibbonPersonIDTaker' => $session->get('gibbonPersonID'),
                                'date' => $currentDate,
                                'timestampTaken' => date('Y-m-d H:i:s')
                            ];

                            $updated = $attendanceLogPersonGateway->update($row['gibbonAttendanceLogPersonID'], $dataUpdate);

                            if (!$updated) {
                                $fail = true;
                            }
                        } else {  // Else create a new record
                            
                            $data = [
                                'gibbonAttendanceCodeID' => $attendanceCode['gibbonAttendanceCodeID'],
                                'gibbonPersonID' => $gibbonPersonID,
                                'direction' => $direction,
                                'context' => 'Person',
                                'type' => $type,
                                'reason' => $reason,
                                'comment' => $comment,
                                'gibbonPersonIDTaker' => $session->get('gibbonPersonID'),
                                'date' => $currentDate,
                                'timestampTaken' => date('Y-m-d H:i:s')
                            ];

                            $inserted = $attendanceLogPersonGateway->insert($data);

                            if (!$inserted) {
                                $fail = true;
                            }
                        }
                    }
                }
            }

            if ($fail == true) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
            } else {
                $URL .= '&return=success0';
                header("Location: {$URL}");
            }
        }
    }
}
