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

@session_start();

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/System Admin/alarm.php';

if (isActionAccessible($guid, $connection2, '/modules/System Admin/alarm.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    $gibbonAlarmID = '';
    if (isset($_GET['gibbonAlarmID'])) {
        $gibbonAlarmID = $_GET['gibbonAlarmID'];
    }

    //Validate Inputs
    if ($gibbonAlarmID == '') {
        $URL .= '&return=error3';
        header("Location: {$URL}");
    } else {
        $fail = false;

        //DEAL WITH ALARM SETTING
        //Write setting to database
        try {
            $data = array();
            $sql = "UPDATE gibbonSetting SET value='None' WHERE scope='System' AND name='alarm'";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $fail = true;
        }

        //Deal with alarm record
        try {
            $data = array('timestampEnd' => date('Y-m-d H:i:s'), 'gibbonAlarmID' => $gibbonAlarmID);
            $sql = "UPDATE gibbonAlarm SET status='Past', timestampEnd=:timestampEnd WHERE gibbonAlarmID=:gibbonAlarmID";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $fail = true;
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
