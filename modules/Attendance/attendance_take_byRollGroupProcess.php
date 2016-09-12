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

$gibbonRollGroupID = $_POST['gibbonRollGroupID'];
$currentDate = $_POST['currentDate'];
$today = date('Y-m-d');
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/attendance_take_byRollGroup.php&gibbonRollGroupID=$gibbonRollGroupID&currentDate=".dateConvertBack($guid, $currentDate);

if (isActionAccessible($guid, $connection2, '/modules/Attendance/attendance_take_byRollGroup.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    //Check if school year specified
    if ($gibbonRollGroupID == '' and $currentDate == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        try {
            $data = array('gibbonRollGroupID' => $gibbonRollGroupID);
            $sql = 'SELECT * FROM gibbonRollGroup WHERE gibbonRollGroupID=:gibbonRollGroupID';
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
                        $direction = 'In';
                        if ($_POST[$i.'-type'] == 'Absent' or $_POST[$i.'-type'] == 'Absent - Excused' or $_POST[$i.'-type'] == 'Left' or $_POST[$i.'-type'] == 'Left - Early') {
                            $direction = 'Out';
                        }
                        $type = $_POST[$i.'-type'];
                        $reason = $_POST[$i.'-reason'];
                        $comment = $_POST[$i.'-comment'];

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

                        if ($result->rowCount() < 1) {
                            //If no records then create one
                            try {
                                $dataUpdate = array('gibbonPersonID' => $gibbonPersonID, 'direction' => $direction, 'type' => $type, 'reason' => $reason, 'comment' => $comment, 'gibbonPersonIDTaker' => $_SESSION[$guid]['gibbonPersonID'], 'date' => $currentDate, 'timestampTaken' => date('Y-m-d H:i:s'));
                                $sqlUpdate = 'INSERT INTO gibbonAttendanceLogPerson SET gibbonPersonID=:gibbonPersonID, direction=:direction, type=:type, reason=:reason, comment=:comment, gibbonPersonIDTaker=:gibbonPersonIDTaker, date=:date, timestampTaken=:timestampTaken';
                                $resultUpdate = $connection2->prepare($sqlUpdate);
                                $resultUpdate->execute($dataUpdate);
                            } catch (PDOException $e) {
                                $fail = true;
                            }
                        } else {
                            $row = $result->fetch();

                            //If direction same then update
                            if ($row['direction'] == $direction) {
                                try {
                                    $dataUpdate = array('gibbonPersonID' => $gibbonPersonID, 'direction' => $direction, 'type' => $type, 'reason' => $reason, 'comment' => $comment, 'gibbonPersonIDTaker' => $_SESSION[$guid]['gibbonPersonID'], 'date' => $currentDate, 'timestampTaken' => date('Y-m-d H:i:s'), 'gibbonAttendanceLogPersonID' => $row['gibbonAttendanceLogPersonID']);
                                    $sqlUpdate = 'UPDATE gibbonAttendanceLogPerson SET gibbonPersonID=:gibbonPersonID, direction=:direction, type=:type, reason=:reason, comment=:comment, gibbonPersonIDTaker=:gibbonPersonIDTaker, date=:date, timestampTaken=:timestampTaken WHERE gibbonAttendanceLogPersonID=:gibbonAttendanceLogPersonID';
                                    $resultUpdate = $connection2->prepare($sqlUpdate);
                                    $resultUpdate->execute($dataUpdate);
                                } catch (PDOException $e) {
                                    $fail = true;
                                }
                            }
                            //Else create a new record
                            else {
                                try {
                                    $dataUpdate = array('gibbonPersonID' => $gibbonPersonID, 'direction' => $direction, 'type' => $type, 'reason' => $reason, 'comment' => $comment, 'gibbonPersonIDTaker' => $_SESSION[$guid]['gibbonPersonID'], 'date' => $currentDate, 'timestampTaken' => date('Y-m-d H:i:s'));
                                    $sqlUpdate = 'INSERT INTO gibbonAttendanceLogPerson SET gibbonPersonID=:gibbonPersonID, direction=:direction, type=:type, reason=:reason, comment=:comment, gibbonPersonIDTaker=:gibbonPersonIDTaker, date=:date, timestampTaken=:timestampTaken';
                                    $resultUpdate = $connection2->prepare($sqlUpdate);
                                    $resultUpdate->execute($dataUpdate);
                                } catch (PDOException $e) {
                                    $fail = true;
                                }
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
