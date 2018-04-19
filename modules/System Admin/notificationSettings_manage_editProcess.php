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

@session_start();

$gibbonNotificationEventID = (isset($_POST['gibbonNotificationEventID']))? $_POST['gibbonNotificationEventID'] : null;
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/notificationSettings.php";

if (isActionAccessible($guid, $connection2, '/modules/System Admin/notificationSettings_manage_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    //Proceed!
    if ($gibbonNotificationEventID == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    } else {
        $gateway = new NotificationGateway($pdo);

        $result = $gateway->selectNotificationEventByID($gibbonNotificationEventID);
        if ($result->rowCount() != 1) {
            $URL .= '&return=error1';
            header("Location: {$URL}");
            exit;
        }

        $event = $result->fetch();

        $event['active'] = (isset($_POST['active']))? $_POST['active'] : $event['active'];

        $result = $gateway->updateNotificationEvent($event);

        $URL .= '&return=success0';
        header("Location: {$URL}");
        exit;
    }
}
