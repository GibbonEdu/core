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

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address']).'/dataUpdaterSettings.php';

if (isActionAccessible($guid, $connection2, '/modules/User Admin/dataUpdaterSettings.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
   

    //Write to database
    $fail = false;

    $requiredUpdates = (isset($_POST['requiredUpdates'])) ? $_POST['requiredUpdates']  : 'N';
    try {
        $data = array('value' => $requiredUpdates);
        $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Data Updater' AND name='requiredUpdates'";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $fail = true;
    }

    $requiredUpdatesByType = (isset($_POST['requiredUpdatesByType'])) ? implode(',', $_POST['requiredUpdatesByType'])  : 'Family,Personal';
    try {
        $data = array('value' => $requiredUpdatesByType);
        $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Data Updater' AND name='requiredUpdatesByType'";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $fail = true;
    }

    $cutoffDate = (isset($_POST['cutoffDate'])) ? dateConvert($guid, $_POST['cutoffDate'])  : NULL;
    try {
        $data = array('value' => $cutoffDate);
        $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Data Updater' AND name='cutoffDate'";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $fail = true;
    }

    $parentDashboardRedirect = (isset($_POST['parentDashboardRedirect'])) ? $_POST['parentDashboardRedirect'] : 'N';
    try {
        $data = array('value' => $parentDashboardRedirect);
        $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Data Updater' AND name='parentDashboardRedirect'";
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
