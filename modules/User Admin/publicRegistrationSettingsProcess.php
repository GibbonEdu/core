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

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address']).'/publicRegistrationSettings.php';

if (isActionAccessible($guid, $connection2, '/modules/User Admin/publicRegistrationSettings.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    $enablePublicRegistration = $_POST['enablePublicRegistration'];
    $publicRegistrationMinimumAge = $_POST['publicRegistrationMinimumAge'];
    $publicRegistrationDefaultStatus = $_POST['publicRegistrationDefaultStatus'];
    $publicRegistrationDefaultRole = $_POST['publicRegistrationDefaultRole'];
    $publicRegistrationIntro = $_POST['publicRegistrationIntro'];
    $publicRegistrationPrivacyStatement = $_POST['publicRegistrationPrivacyStatement'];
    $publicRegistrationAgreement = $_POST['publicRegistrationAgreement'];
    $publicRegistrationPostscript = $_POST['publicRegistrationPostscript'];

    //Write to database
    $fail = false;

    try {
        $data = array('value' => $enablePublicRegistration);
        $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='User Admin' AND name='enablePublicRegistration'";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $fail = true;
    }

    try {
        $data = array('value' => $publicRegistrationMinimumAge);
        $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='User Admin' AND name='publicRegistrationMinimumAge'";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $fail = true;
    }

    try {
        $data = array('value' => $publicRegistrationDefaultStatus);
        $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='User Admin' AND name='publicRegistrationDefaultStatus'";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $fail = true;
    }

    try {
        $data = array('value' => $publicRegistrationDefaultRole);
        $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='User Admin' AND name='publicRegistrationDefaultRole'";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $fail = true;
    }

    try {
        $data = array('value' => $publicRegistrationIntro);
        $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='User Admin' AND name='publicRegistrationIntro'";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $fail = true;
    }

    try {
        $data = array('value' => $publicRegistrationPrivacyStatement);
        $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='User Admin' AND name='publicRegistrationPrivacyStatement'";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $fail = true;
    }

    try {
        $data = array('value' => $publicRegistrationAgreement);
        $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='User Admin' AND name='publicRegistrationAgreement'";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $fail = true;
    }

    try {
        $data = array('value' => $publicRegistrationPostscript);
        $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='User Admin' AND name='publicRegistrationPostscript'";
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
