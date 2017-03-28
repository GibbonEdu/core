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

use Gibbon\Domain\System\NotificationGateway;

include '../../functions.php';
include '../../config.php';

//New PDO DB connection
$pdo = new Gibbon\sqlConnection();
$connection2 = $pdo->getConnection();

@session_start();

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]['timezone']);

$gibbonNotificationEventID = (isset($_GET['gibbonNotificationEventID']))? $_GET['gibbonNotificationEventID'] : null;
$gibbonNotificationListenerID = (isset($_GET['gibbonNotificationListenerID']))? $_GET['gibbonNotificationListenerID'] : null;
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['address'])."/notificationSettings_manage_edit.php&gibbonNotificationEventID=".$gibbonNotificationEventID;

if (isActionAccessible($guid, $connection2, '/modules/System Admin/notificationSettings_manage_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    //Proceed!
    if (empty($gibbonNotificationEventID) || empty($gibbonNotificationListenerID)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    } else {
        $gateway = new NotificationGateway($pdo);

        $result = $gateway->selectNotificationListener($gibbonNotificationListenerID);
        if ($result->rowCount() != 1) {
            $URL .= '&return=error1';
            header("Location: {$URL}");
            exit;
        }

        $result = $gateway->deleteNotificationListener($gibbonNotificationListenerID);

        $URL .= '&return=success0';
        header("Location: {$URL}");
        exit;
    }
}
