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

use Gibbon\Url;

include './gibbon.php';

$URLBack = Url::fromRoute('notifications');
$gibbonNotificationID = $_GET['gibbonNotificationID'] ?? '';

if (empty($gibbonNotificationID) || !$gibbon->session->has('gibbonPersonID')) {
    header("Location: {$URLBack->withReturn('error1')}");
    exit();
} else {
    // Check for existence of notification, belonging to this user
    $data = array('gibbonNotificationID' => $gibbonNotificationID, 'gibbonPersonID' => $gibbon->session->get('gibbonPersonID'));
    $sql = "SELECT * FROM gibbonNotification WHERE gibbonPersonID=:gibbonPersonID AND gibbonNotificationID=:gibbonNotificationID";

    $notification = $pdo->selectOne($sql, $data);

    if (empty($notification)) {
        header("Location: {$URLBack->withReturn('error2')}");
        exit();
    } else {
        $URL = $gibbon->session->get('absoluteURL').$notification['actionLink'];

        //Archive notification
        $data = array('gibbonNotificationID' => $gibbonNotificationID, 'gibbonPersonID' => $gibbon->session->get('gibbonPersonID'));
        $sql = "UPDATE gibbonNotification SET status='Archived' WHERE gibbonPersonID=:gibbonPersonID AND gibbonNotificationID=:gibbonNotificationID";

        $pdo->update($sql, $data);

        if (!$pdo->getQuerySuccess()) {
            header("Location: {$URLBack->withReturn('error2')}");
            exit();
        }

        //Success 0
        header("Location: {$URL}");
    }
}
