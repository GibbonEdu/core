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

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/attendance_studentSelfRegister.php";

if (isActionAccessible($guid, $connection2, '/modules/Attendance/attendance_studentSelfRegister.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    $studentSelfRegistrationIPAddresses = getSettingByScope($connection2, 'Attendance', 'studentSelfRegistrationIPAddresses');
    $realIP = getIPAddress();
    if ($studentSelfRegistrationIPAddresses == '' || is_null($studentSelfRegistrationIPAddresses)) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
    } else {
        //Check if school day
        $currentDate = date('Y-m-d');
        if (isSchoolOpen($guid, $currentDate, $connection2, true) == false) {
            $URL .= '&return=error0';
            header("Location: {$URL}");
        }
        else {
            //Check for existence of records today
            try {
                $data = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'], 'date' => $currentDate);
                $sql = "SELECT type FROM gibbonAttendanceLogPerson WHERE gibbonPersonID=:gibbonPersonID AND date=:date ORDER BY timestampTaken DESC";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
                exit();
            }

            if ($result->rowCount() > 0) { //Records! Return error
                $URL .= '&return=error1';
                header("Location: {$URL}");
            }
            else { //If no records, set status to Present
                $inRange = false ;
                foreach (explode(',', $studentSelfRegistrationIPAddresses) as $ipAddress) {
                    if (trim($ipAddress) == $realIP)
                        $inRange = true ;
                }

                $status = (isset($_POST['status']))? $_POST['status'] : null;

                if (!$inRange && $status == 'Absent') {
                    try {
                        $dataUpdate = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonPersonIDTaker' => $_SESSION[$guid]['gibbonPersonID'], 'date' => $currentDate, 'timestampTaken' => date('Y-m-d H:i:s'));
                        $sqlUpdate = 'INSERT INTO gibbonAttendanceLogPerson SET gibbonAttendanceCodeID=(SELECT gibbonAttendanceCodeID FROM gibbonAttendanceCode WHERE name=\'Absent\'), gibbonPersonID=:gibbonPersonID, direction=\'Out\', type=\'Absent\', context=\'Self Registration\', reason=\'\', comment=\'\', gibbonPersonIDTaker=:gibbonPersonIDTaker, gibbonCourseClassID=NULL, date=:date, timestampTaken=:timestampTaken';
                        $resultUpdate = $connection2->prepare($sqlUpdate);
                        $resultUpdate->execute($dataUpdate);
                    } catch (PDOException $e) {
                        $URL .= '&return=error2';
                        header("Location: {$URL}");
                        exit();
                    }

                    //Give student a like for their effort
                    $gibbonAttendanceLogPersonID = $connection2->lastInsertId();
                    setLike($connection2, 'Attendance', $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonAttendanceLogPersonID', $gibbonAttendanceLogPersonID, $_SESSION[$guid]['gibbonPersonID'], $_SESSION[$guid]['gibbonPersonID'], 'Attendance - Self Registration');
                    $_SESSION[$guid]['pageLoads'] = null;

                    $URL .= '&return=success0';
                    header("Location: {$URL}");
                    exit();
                }
                else if ($inRange && $status == 'Present') {
                    try {
                        $dataUpdate = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonPersonIDTaker' => $_SESSION[$guid]['gibbonPersonID'], 'date' => $currentDate, 'timestampTaken' => date('Y-m-d H:i:s'));
                        $sqlUpdate = 'INSERT INTO gibbonAttendanceLogPerson SET gibbonAttendanceCodeID=(SELECT gibbonAttendanceCodeID FROM gibbonAttendanceCode WHERE name=\'Present\'), gibbonPersonID=:gibbonPersonID, direction=\'In\', type=\'Present\', context=\'Self Registration\', reason=\'\', comment=\'\', gibbonPersonIDTaker=:gibbonPersonIDTaker, gibbonCourseClassID=NULL, date=:date, timestampTaken=:timestampTaken';
                        $resultUpdate = $connection2->prepare($sqlUpdate);
                        $resultUpdate->execute($dataUpdate);
                    } catch (PDOException $e) {
                        $URL .= '&return=error2';
                        header("Location: {$URL}");
                        exit();
                    }

                    //Give student a like for their effort
                    $gibbonAttendanceLogPersonID = $connection2->lastInsertId();
                    setLike($connection2, 'Attendance', $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonAttendanceLogPersonID', $gibbonAttendanceLogPersonID, $_SESSION[$guid]['gibbonPersonID'], $_SESSION[$guid]['gibbonPersonID'], 'Attendance - Self Registration');
                    $_SESSION[$guid]['pageLoads'] = null;

                    $URL .= '&return=success0';
                    header("Location: {$URL}");
                    exit();
                }
                else {
                    $URL .= '&return=error0';
                    header("Location: {$URL}");
                }
            }
        }
    }
}
