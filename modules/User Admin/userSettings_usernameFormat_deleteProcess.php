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

$gibbonUsernameFormatID = isset($_POST['gibbonUsernameFormatID'])? $_POST['gibbonUsernameFormatID'] : '';
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address']).'/userSettings.php&gibbonUsernameFormatID='.$gibbonUsernameFormatID;

if (isActionAccessible($guid, $connection2, '/modules/User Admin/userSettings.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    try {
        $data = array('gibbonUsernameFormatID' => $gibbonUsernameFormatID);
        $sql = "DELETE FROM gibbonUsernameFormat WHERE gibbonUsernameFormatID=:gibbonUsernameFormatID";
        $result = $pdo->executeQuery($data, $sql);
    } catch (PDOException $e) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    //Success 0
    $URL .= '&return=success0';
    header("Location: {$URL}");
    exit;
}
