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

include '../../functions.php';
include '../../config.php';

//New PDO DB connection
try {
    $connection2 = new PDO("mysql:host=$databaseServer;dbname=$databaseName;charset=utf8", $databaseUsername, $databasePassword);
    $connection2->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $connection2->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo $e->getMessage();
}

@session_start();

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]['timezone']);

$gibbonActivityID = $_GET['gibbonActivityID'];
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/activities_attendance.php&gibbonActivityID=$gibbonActivityID";

if (isActionAccessible($guid, $connection2, '/modules/Activities/activities_attendanceProcess.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!

    $gibbonPersonID = $_POST['gibbonPersonID'];

    $highestAction = getHighestGroupedAction($guid, '/modules/Activities/activities_attendance.php', $connection2);

    if($highestAction == "Enter Activity Attendance_leader") {
        try {
            $dataCheck = array("gibbonPersonID" => $gibbonPersonID, "gibbonActivityID" => $gibbonActivityID);
            $sqlCheck = "SELECT role FROM gibbonActivityStaff WHERE gibbonActivityID=:gibbonActivityID AND gibbonPersonID=:gibbonPersonID";
            $resultCheck = $connection2->prepare($sqlCheck);
            $resultCheck->execute($dataCheck);

            if ($resultCheck->rowCount() > 0) {
                $row = $resultCheck->fetch();
                if ($row["role"] != "Organiser" && $row["role"] != "Assistant") {
                    $URL .= '&return=error0';
                    header("Location: {$URL}");
                }
            } else {
                $URL .= '&return=error0';
                header("Location: {$URL}");
            }
        } catch (PDOException $e) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
        }
    }

    $sessions = (isset($_POST['sessions'])) ? $_POST['sessions'] : null;
    $attendance = (isset($_POST['attendance'])) ? $_POST['attendance'] : null;

    if ($gibbonActivityID == '' || $gibbonPersonID == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } elseif (empty($sessions)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        $partialFail = false;

        // Iterate through the session columns
        foreach ($sessions as $i => $session) {
            $sessionTimestamp = $session;
            $sessionDate = date('Y-m-d', $sessionTimestamp);

            if (empty($sessionTimestamp) || empty($sessionDate)) {
                $URL .= '&return=error1';
                header("Location: {$URL}");

                return;
            }

            $sessionAttendance = (isset($attendance[$i])) ? serialize($attendance[$i]) : '';

            try {
                $data = array('gibbonActivityID' => $gibbonActivityID, 'date' => $sessionDate);
                $sql = 'SELECT gibbonActivityAttendanceID FROM gibbonActivityAttendance WHERE gibbonActivityID=:gibbonActivityID AND date=:date';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $partialFail = true;
            }

            // INSERT
            if ($result->rowCount() <= 0) {

                // Skip sessions we're not recording attendance for
                if (!isset($attendance[$i]) || empty($attendance[$i]) || !is_array($attendance[$i])) {
                    continue;
                }

                try {
                    $data = array('gibbonActivityID' => $gibbonActivityID, 'gibbonPersonIDTaker' => $gibbonPersonID, 'attendance' => $sessionAttendance, 'date' => $sessionDate);
                    $sql = 'INSERT INTO gibbonActivityAttendance SET gibbonActivityID=:gibbonActivityID, gibbonPersonIDTaker=:gibbonPersonIDTaker, attendance=:attendance, date=:date ';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $partialFail = true;
                }
            }
            // UPDATE
            else {
                $gibbonActivityAttendanceID = $result->fetchColumn(0);

                try {
                    $data = array('gibbonActivityAttendanceID' => $gibbonActivityAttendanceID, 'gibbonPersonIDTaker' => $gibbonPersonID, 'attendance' => $sessionAttendance, 'date' => $sessionDate);
                    $sql = 'UPDATE gibbonActivityAttendance SET gibbonPersonIDTaker=:gibbonPersonIDTaker, attendance=:attendance, date=:date WHERE gibbonActivityAttendanceID=:gibbonActivityAttendanceID';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $partialFail = true;
                }
            }
        }

        if ($partialFail == true) {
            $URL .= '&return=warning1';
            header("Location: {$URL}");
        } else {
            $URL .= '&return=success0';
            header("Location: {$URL}");
        }
    }
}
