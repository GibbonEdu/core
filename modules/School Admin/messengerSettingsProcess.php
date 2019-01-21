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

include '../../gibbon.php';

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address']).'/messengerSettings.php';

if (isActionAccessible($guid, $connection2, '/modules/School Admin/messengerSettings.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    $messageBubbleWidthType = $_POST['messageBubbleWidthType'];
    $messageBubbleBGColor = $_POST['messageBubbleBGColor'];
    $messageBubbleAutoHide = $_POST['messageBubbleAutoHide'];
    $enableHomeScreenWidget = $_POST['enableHomeScreenWidget'];
    $messageBcc = $_POST['messageBcc'];

    //Write to database
    $fail = false;

    try {
        $data = array('value' => $messageBubbleWidthType);
        $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Messenger' AND name='messageBubbleWidthType'";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $fail = true;
    }

    try {
        $data = array('value' => $messageBubbleBGColor);
        $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Messenger' AND name='messageBubbleBGColor'";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $fail = true;
    }

    try {
        $data = array('value' => $messageBubbleAutoHide);
        $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Messenger' AND name='messageBubbleAutoHide'";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $fail = true;
    }

    try {
        $data = array('value' => $enableHomeScreenWidget);
        $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Messenger' AND name='enableHomeScreenWidget'";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $fail = true;
    }

    try {
        $data = array('value' => $messageBcc);
        $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Messenger' AND name='messageBcc'";
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
