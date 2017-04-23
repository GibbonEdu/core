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
$pdo = new Gibbon\sqlConnection();
$connection2 = $pdo->getConnection();

@session_start();

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]['timezone']);

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address']).'/alarm.php';

if (isActionAccessible($guid, $connection2, '/modules/System Admin/alarm.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    $alarm = $_POST['alarm'];
    $attachmentCurrent = $_POST['attachmentCurrent'];
    $alarmCurrent = $_POST['alarmCurrent'];

    //Validate Inputs
    if ($alarm != 'None' and $alarm != 'General' and $alarm != 'Lockdown' and $alarm != 'Custom' and $alarmCurrent != '') {
        $URL .= '&return=error3';
        header("Location: {$URL}");
    } else {
        $fail = false;

        //DEAL WITH CUSTOM SOUND SETTING
        $time = time();
        //Move attached file, if there is one
        if (!empty($_FILES['file']['tmp_name'])) {
            $fileUploader = new Gibbon\FileUploader($pdo, $gibbon->session);
                
            $file = (isset($_FILES['file']))? $_FILES['file'] : null;

            // Upload the file, return the /uploads relative path
            $attachment = $fileUploader->uploadFromPost($file, 'alarmSound');
                
            if (empty($attachment)) {
                $URL .= '&return=error1';
                header("Location: {$URL}");
                exit;
            }
        } else {
            $attachment = $attachmentCurrent;
        }

        //Write setting to database
        try {
            $data = array('value' => $attachment);
            $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='System Admin' AND name='customAlarmSound'";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $fail = true;
        }

        //DEAL WITH ALARM SETTING
        //Write setting to database
        try {
            $data = array('alarm' => $alarm);
            $sql = "UPDATE gibbonSetting SET value=:alarm WHERE scope='System' AND name='alarm'";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $fail = true;
        }

        //Check for existing alarm
        $checkFail = false;
        try {
            $data = array();
            $sql = "SELECT * FROM gibbonAlarm WHERE status='Current'";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $checkFail = true;
        }

        //Alarm is being turned on, so insert new record
        if ($alarm == 'General' or $alarm == 'Lockdown' or $alarm == 'Custom') {
            if ($checkFail == true) {
                $fail = true;
            } else {
                if ($result->rowCount() == 0) {
                    //Write alarm to database
                    try {
                        $data = array('type' => $alarm, 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'], 'timestampStart' => date('Y-m-d H:i:s'));
                        $sql = "INSERT INTO gibbonAlarm SET type=:type, status='Current', gibbonPersonID=:gibbonPersonID, timestampStart=:timestampStart";
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $fail = true;
                    }
                } else {
                    $row = $result->fetch();
                    try {
                        $data = array('type' => $alarm, 'gibbonAlarmID' => $row['gibbonAlarmID']);
                        $sql = 'UPDATE gibbonAlarm SET type=:type WHERE gibbonAlarmID=:gibbonAlarmID';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $fail = true;
                    }
                }
            }
        } elseif ($alarmCurrent != $alarm) {
            if ($result->rowCount() == 1) {
                $row = $result->fetch();
                try {
                    $data = array('timestampEnd' => date('Y-m-d H:i:s'), 'gibbonAlarmID' => $row['gibbonAlarmID']);
                    $sql = "UPDATE gibbonAlarm SET status='Past', timestampEnd=:timestampEnd WHERE gibbonAlarmID=:gibbonAlarmID";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $fail = true;
                }
            } else {
                $fail = true;
            }
        }

        if ($fail == true) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
        } else {
            getSystemSettings($guid, $connection2);
            $URL .= '&return=success0';
            header("Location: {$URL}");
        }
    }
}
