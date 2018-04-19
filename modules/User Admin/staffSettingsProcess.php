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

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address']).'/staffSettings.php';

if (isActionAccessible($guid, $connection2, '/modules/User Admin/staffSettings.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    $salaryScalePositions = $_POST['salaryScalePositions'];
    $responsibilityPosts = $_POST['responsibilityPosts'];
    $jobOpeningDescriptionTemplate = $_POST['jobOpeningDescriptionTemplate'];

    $nameFormatStaffFormal = $_POST['nameFormatStaffFormal'];
    $nameFormatStaffFormalReversed = $_POST['nameFormatStaffFormalReversed'];
    $nameFormatStaffInformal = $_POST['nameFormatStaffInformal'];
    $nameFormatStaffInformalReversed = $_POST['nameFormatStaffInformalReversed'];

    //Write to database
    $fail = false;

    try {
        $data = array('value' => $salaryScalePositions);
        $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Staff' AND name='salaryScalePositions'";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $fail = true;
    }

    try {
        $data = array('value' => $responsibilityPosts);
        $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Staff' AND name='responsibilityPosts'";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $fail = true;
    }

    try {
        $data = array('value' => $jobOpeningDescriptionTemplate);
        $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Staff' AND name='jobOpeningDescriptionTemplate'";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $fail = true;
    }


    try {
        $data = array('value' => $nameFormatStaffFormal);
        $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='System' AND name='nameFormatStaffFormal'";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $fail = true;
    }

    try {
        $data = array('value' => $nameFormatStaffFormalReversed);
        $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='System' AND name='nameFormatStaffFormalReversed'";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $fail = true;
    }

    try {
        $data = array('value' => $nameFormatStaffInformal);
        $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='System' AND name='nameFormatStaffInformal'";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $fail = true;
    }

    try {
        $data = array('value' => $nameFormatStaffInformalReversed);
        $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='System' AND name='nameFormatStaffInformalReversed'";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $fail = true;
    }


    if ($fail == true) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
    } else {
        //Success 0
        getSystemSettings($guid, $connection2);
        $URL .= '&return=success0';
        header("Location: {$URL}");
    }
}
