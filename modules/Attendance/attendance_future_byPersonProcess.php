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

use Gibbon\Module\Attendance\AttendanceView;

//Gibbon system-wide includes
include __DIR__ . '/../../gibbon.php';

//Module includes
include __DIR__ . '/moduleFunctions.php';

$gibbonPersonID = $_POST['gibbonPersonID'] ?? '';
$scope = $_POST['scope'] ?? '';
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/attendance_future_byPerson.php&gibbonPersonID=$gibbonPersonID&scope=$scope";

if (isActionAccessible($guid, $connection2, '/modules/Attendance/attendance_future_byPerson.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    //Check if person specified
    if ($gibbonPersonID == '' & $scope == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        $gibbonPersonID = explode(',', $gibbonPersonID);

        $personCheck = true ;
        foreach ($gibbonPersonID as $gibbonPersonIDCurrent) {
            try {
                $data = array('gibbonPersonID' => $gibbonPersonIDCurrent);
                $sql = 'SELECT * FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $personCheck = false;
            }
            if ($result->rowCount() != 1) {
                $personCheck = false;
            }
        }

        if (!$personCheck) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
        } else {
            //Write to database
            require_once __DIR__ . '/src/AttendanceView.php';
            $attendance = new AttendanceView($gibbon, $pdo);

            $partialFail = false;
            $partialFailSchoolClosed = false;
            
            $type = $_POST['type'] ?? '';
            $reason = $_POST['reason'] ?? '';
            $comment = $_POST['comment'] ?? '';

            $attendanceCode = $attendance->getAttendanceCodeByType($type);
            $direction = $attendanceCode['direction'];

            $absenceType = $_POST['absenceType'] ?? 'full';

            $dateStart = '';
            if ($_POST['dateStart'] != '') {
                $dateStart = dateConvert($guid, $_POST['dateStart']);
            }
            $dateEnd = $dateStart;
            if ($_POST['dateEnd'] != '') {
                $dateEnd = dateConvert($guid, $_POST['dateEnd']);
            }
            $today = date('Y-m-d');

            //Check to see if date is in the future and is a school day.
            if ($dateStart == '' or ($dateEnd != '' and $dateEnd < $dateStart) or $dateStart <= $today) {
                $URL .= '&return=error8';
                header("Location: {$URL}");
            } else {
                //Scroll through days

                $dateStartStamp = dateConvertToTimestamp($dateStart);
                $dateEndStamp = dateConvertToTimestamp($dateEnd);
                for ($i = $dateStartStamp; $i <= $dateEndStamp; $i = ($i + 86400)) {
                    $date = date('Y-m-d', $i);

                    if (isSchoolOpen($guid, $date, $connection2)) { //Only add if school is open on this day
                        foreach ($gibbonPersonID as $gibbonPersonIDCurrent) {
                            //Check for record on same day
                            try {
                                $data = array('gibbonPersonID' => $gibbonPersonIDCurrent, 'date' => "$date%");
                                $sql = 'SELECT * FROM gibbonAttendanceLogPerson WHERE gibbonPersonID=:gibbonPersonID AND gibbonCourseClassID IS NULL AND date LIKE :date ORDER BY date DESC';
                                $result = $connection2->prepare($sql);
                                $result->execute($data);
                            } catch (PDOException $e) {
                                $partialFail = true;
                            }

                            if ($result->rowCount() > 0 AND $absenceType == 'full') {
                                $partialFail = true;
                            } else {
                                // Handle full-day absenses normally
                                if ($absenceType == 'full') {
                                    try {
                                        $dataUpdate = array('gibbonPersonID' => $gibbonPersonIDCurrent, 'direction' => $direction, 'type' => $type, 'reason' => $reason, 'comment' => $comment, 'gibbonPersonIDTaker' => $_SESSION[$guid]['gibbonPersonID'], 'date' => $date, 'timestampTaken' => date('Y-m-d H:i:s'));
                                        $sqlUpdate = 'INSERT INTO gibbonAttendanceLogPerson SET gibbonAttendanceCodeID=(SELECT gibbonAttendanceCodeID FROM gibbonAttendanceCode WHERE name=:type), gibbonPersonID=:gibbonPersonID, direction=:direction, type=:type, context=\'Future\', reason=:reason, comment=:comment, gibbonPersonIDTaker=:gibbonPersonIDTaker, date=:date, timestampTaken=:timestampTaken';
                                        $resultUpdate = $connection2->prepare($sqlUpdate);
                                        $resultUpdate->execute($dataUpdate);
                                    } catch (PDOException $e) {
                                        $partialFail = true;
                                    }

                                // Handle partial absenses per-class
                                } else if ($absenceType == 'partial') {

                                    // Return error if full-day absense already recorded
                                    if ($result->rowCount() > 0) {
                                        $URL .= '&return=error7';
                                        header("Location: {$URL}");
                                        exit();
                                    } else {
                                        $courses = $_POST['courses'] ?? null;
                                        if (!empty($courses) && is_array($courses)) {
                                            foreach ($courses as $course) {
                                                try {
                                                    $dataUpdate = array('gibbonPersonID' => $gibbonPersonIDCurrent, 'direction' => $direction, 'type' => $type, 'reason' => $reason, 'comment' => $comment, 'gibbonPersonIDTaker' => $_SESSION[$guid]['gibbonPersonID'], 'date' => $date, 'gibbonCourseClassID' => $course, 'timestampTaken' => date('Y-m-d H:i:s'));
                                                    $sqlUpdate = 'INSERT INTO gibbonAttendanceLogPerson SET gibbonAttendanceCodeID=(SELECT gibbonAttendanceCodeID FROM gibbonAttendanceCode WHERE name=:type), gibbonPersonID=:gibbonPersonID, direction=:direction, type=:type, context=\'Class\', reason=:reason, comment=:comment, gibbonPersonIDTaker=:gibbonPersonIDTaker, date=:date, gibbonCourseClassID=:gibbonCourseClassID, timestampTaken=:timestampTaken';
                                                    $resultUpdate = $connection2->prepare($sqlUpdate);
                                                    $resultUpdate->execute($dataUpdate);
                                                } catch (PDOException $e) {
                                                    $partialFail = true;
                                                }
                                            }
                                            $URL .= '&absenceType=partial&date=' . $_POST['dateStart'] ?? ''; //Redirect to exact state of submit form
                                        } else {
                                            // Return error if no courses selected for partial absence
                                            $URL .= '&return=error1';
                                            header("Location: {$URL}");
                                            exit();
                                        }
                                    }
                                } else {
                                    $URL .= '&return=error1';
                                    header("Location: {$URL}");
                                    exit();
                                }
                            }
                        }

                    } else {
                        $partialFailSchoolClosed = true;
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
}
