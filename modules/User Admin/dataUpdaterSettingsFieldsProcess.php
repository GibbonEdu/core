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
    $settings = $_POST['settings'] ?? [];

    //Write to database
    $data = array('value' => serialize($settings));
    $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='User Admin' AND name='personalDataUpdaterRequiredFields'";

    $updated = $pdo->update($sql, $data);

    if (!$updated) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
    } else {
        $URL .= '&return=success0';
        header("Location: {$URL}");
    }
}
