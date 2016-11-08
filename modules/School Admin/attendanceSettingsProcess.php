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

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address']).'/attendanceSettings.php';

if (isActionAccessible($guid, $connection2, '/modules/School Admin/attendanceSettings.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    $fail = false;

    $attendanceReasons = (isset($_POST['attendanceReasons'])) ? $_POST['attendanceReasons'] : NULL;
    try {
        $data = array('value' => $attendanceReasons);
        $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Attendance' AND name='attendanceReasons'";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $fail = true;
    }

    $attendanceCLINotifyByRollGroup = (isset($_POST['attendanceCLINotifyByRollGroup'])) ? $_POST['attendanceCLINotifyByRollGroup'] : NULL;
    try {
        $data = array('value' => $attendanceCLINotifyByRollGroup);
        $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Attendance' AND name='attendanceCLINotifyByRollGroup'";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $fail = true;
    }

    $attendanceCLINotifyByClass = (isset($_POST['attendanceCLINotifyByClass'])) ? $_POST['attendanceCLINotifyByClass'] : NULL;
    try {
        $data = array('value' => $attendanceCLINotifyByClass);
        $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Attendance' AND name='attendanceCLINotifyByClass'";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $fail = true;
    }

    $attendanceCLIAdditionalUsers = (isset($_POST['attendanceCLIAdditionalUsers'])) ? $_POST['attendanceCLIAdditionalUsers'] : NULL;
    try {
        $attendanceCLIAdditionalUserList = (is_array($attendanceCLIAdditionalUsers))? implode(',', $attendanceCLIAdditionalUsers) : '';
        $data = array('value' => $attendanceCLIAdditionalUserList);
        $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Attendance' AND name='attendanceCLIAdditionalUsers'";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $fail = true;
    }

    /*
    $attendanceMedicalReasons = (isset($_POST['attendanceMedicalReasons'])) ? $_POST['attendanceMedicalReasons'] : NULL;
    try {
        $data = array('value' => $attendanceMedicalReasons);
        $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Attendance' AND name='attendanceMedicalReasons'";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $fail = true;
    }

    $attendanceEnableMedicalTracking = (isset($_POST['attendanceEnableMedicalTracking'])) ? $_POST['attendanceEnableMedicalTracking'] : NULL;
    try {
        $data = array('value' => $attendanceEnableMedicalTracking);
        $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Attendance' AND name='attendanceEnableMedicalTracking'";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $fail = true;
    }

    // Move this to a Medical Settings page, eventually
    $medicalIllnessSymptoms = (isset($_POST['medicalIllnessSymptoms'])) ? $_POST['medicalIllnessSymptoms'] : NULL;
    try {
        $data = array('value' => $medicalIllnessSymptoms);
        $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Students' AND name='medicalIllnessSymptoms'";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $fail = true;
    }

    */


   //RETURN RESULTS
   if ($fail == true) {
       $URL .= '&return=error2';
       header("Location: {$URL}");
   } else {
       //Success 0
        $URL .= '&return=success0';
       header("Location: {$URL}");
   }
}
