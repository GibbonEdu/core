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

$gibbonRollGroupID = $_POST['gibbonRollGroupID'];
$currentDate = $_POST['currentDate'];
$today = date('Y-m-d');
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/attendance_take_byRollGroup.php&gibbonRollGroupID=$gibbonRollGroupID&currentDate=".dateConvertBack($guid, $currentDate);

if (isActionAccessible($guid, $connection2, '/modules/Attendance/attendance_take_byRollGroup.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    $highestAction = getHighestGroupedAction($guid, '/modules/Attendance/attendance_take_byRollGroup.php', $connection2);
    if ($highestAction == false) {
        echo "<div class='error'>";
        echo __($guid, 'The highest grouped action cannot be determined.');
        echo '</div>';
    } else {
        //Proceed!
        //Check if school year specified
        if ($gibbonRollGroupID == '' and $currentDate == '') {
            $URL .= '&return=error1';
            header("Location: {$URL}");
        } else {
            try {
                if ($highestAction == 'Attendance By Roll Group_all') {
                    $data = array('gibbonRollGroupID' => $gibbonRollGroupID);
                    $sql = 'SELECT * FROM gibbonRollGroup WHERE gibbonRollGroupID=:gibbonRollGroupID';
                }
                else {
                    $data = array('gibbonRollGroupID' => $gibbonRollGroupID, 'gibbonPersonIDTutor1' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonPersonIDTutor2' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonPersonIDTutor3' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                    $sql = "SELECT * FROM gibbonRollGroup WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND (gibbonPersonIDTutor=:gibbonPersonIDTutor1 OR gibbonPersonIDTutor2=:gibbonPersonIDTutor2 OR gibbonPersonIDTutor3=:gibbonPersonIDTutor3) AND gibbonRollGroup.attendance = 'Y' AND gibbonRollGroupID=:gibbonRollGroupID ORDER BY LENGTH(name), name";
                }
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
                exit();
            }

            if ($result->rowCount() != 1) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
            } else {
                //Check that date is not in the future
                if ($currentDate > $today) {
                    $URL .= '&return=error3';
                    header("Location: {$URL}");
                } else {
                    //Check that date is a school day
                    if (isSchoolOpen($guid, $currentDate, $connection2) == false) {
                        $URL .= '&return=error3';
                        header("Location: {$URL}");
                    } else {
                        //Write to database
                        require_once $_SESSION[$guid]["absolutePath"] . '/modules/Attendance/src/attendanceView.php';
                        $attendance = new Module\Attendance\attendanceView($gibbon, $pdo);

                        try {
                            $data = array('gibbonPersonIDTaker' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonRollGroupID' => $gibbonRollGroupID, 'date' => $currentDate, 'timestampTaken' => date('Y-m-d H:i:s'));
                            $sql = 'INSERT INTO gibbonAttendanceLogRollGroup SET gibbonPersonIDTaker=:gibbonPersonIDTaker, gibbonRollGroupID=:gibbonRollGroupID, date=:date, timestampTaken=:timestampTaken';
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        } catch (PDOException $e) {
                            $URL .= '&return=error2';
                            header("Location: {$URL}");
                            exit();
                        }

                        $count = $_POST['count'];
                        $partialFail = false;

                        for ($i = 0; $i < $count; ++$i) {
                            $gibbonPersonID = $_POST[$i.'-gibbonPersonID'];
                            $type = $_POST[$i.'-type'];
                            $reason = $_POST[$i.'-reason'];
                            $comment = $_POST[$i.'-comment'];

                            $attendanceCode = $attendance->getAttendanceCodeByType($type);
                            $direction = $attendanceCode['direction'];

                            //Check for last record on same day
                            try {
                                $data = array('gibbonPersonID' => $gibbonPersonID, 'date' => $currentDate.'%');
                                $sql = 'SELECT * FROM gibbonAttendanceLogPerson WHERE gibbonPersonID=:gibbonPersonID AND date LIKE :date ORDER BY gibbonAttendanceLogPersonID DESC';
                                $result = $connection2->prepare($sql);
                                $result->execute($data);
                            } catch (PDOException $e) {
                                $URL .= '&return=error2';
                                header("Location: {$URL}");
                                exit();
                            }

                            //Check context and type, updating only if not a match
                            $existing = false ;
                            $gibbonAttendanceLogPersonID = '';
                            if ($result->rowCount()>0) {
                                $row=$result->fetch() ;
                                if ($row['context'] == 'Roll Group' && $row['type'] == $type && $row['direction'] == $direction ) {
                                    $existing = true ;
                                    $gibbonAttendanceLogPersonID = $row['gibbonAttendanceLogPersonID'];
                                }
                            }

    						if (!$existing) {
                                //If no records then create one
                                try {
                                    $dataUpdate = array('gibbonPersonID' => $gibbonPersonID, 'direction' => $direction, 'type' => $type, 'reason' => $reason, 'comment' => $comment, 'gibbonPersonIDTaker' => $_SESSION[$guid]['gibbonPersonID'], 'date' => $currentDate, 'timestampTaken' => date('Y-m-d H:i:s'));
                                    $sqlUpdate = 'INSERT INTO gibbonAttendanceLogPerson SET gibbonAttendanceCodeID=(SELECT gibbonAttendanceCodeID FROM gibbonAttendanceCode WHERE name=:type), gibbonPersonID=:gibbonPersonID, direction=:direction, type=:type, context=\'Roll Group\', reason=:reason, comment=:comment, gibbonPersonIDTaker=:gibbonPersonIDTaker, date=:date, timestampTaken=:timestampTaken';
                                    $resultUpdate = $connection2->prepare($sqlUpdate);
                                    $resultUpdate->execute($dataUpdate);
                                } catch (PDOException $e) {
                                    $fail = true;
                                }
                            } else {
                                try {
                                    $dataUpdate = array('gibbonPersonID' => $gibbonPersonID, 'direction' => $direction, 'type' => $type, 'reason' => $reason, 'comment' => $comment, 'gibbonPersonIDTaker' => $_SESSION[$guid]['gibbonPersonID'], 'date' => $currentDate, 'timestampTaken' => date('Y-m-d H:i:s'), 'gibbonAttendanceLogPersonID' => $row['gibbonAttendanceLogPersonID']);
                                    $sqlUpdate = 'UPDATE gibbonAttendanceLogPerson SET gibbonAttendanceCodeID=(SELECT gibbonAttendanceCodeID FROM gibbonAttendanceCode WHERE name=:type), gibbonPersonID=:gibbonPersonID, direction=:direction, type=:type, context=\'Roll Group\', reason=:reason, comment=:comment, gibbonPersonIDTaker=:gibbonPersonIDTaker, date=:date, timestampTaken=:timestampTaken WHERE gibbonAttendanceLogPersonID=:gibbonAttendanceLogPersonID';
                                    $resultUpdate = $connection2->prepare($sqlUpdate);
                                    $resultUpdate->execute($dataUpdate);
                                } catch (PDOException $e) {
                                    $fail = true;
                                }
                            }
                        }

                        if ($partialFail == true) {
                            $URL .= '&return=warning1';
                            header("Location: {$URL}");
                        } else {
                            $URL .= '&return=success0&time='.date('H-i-s');
                            header("Location: {$URL}");
                        }
                    }
                }
            }
        }
    }
}
