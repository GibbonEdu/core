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

include './gibbon.php';

$URLBack = $_SESSION[$guid]['absoluteURL'].'/index.php?q=notifications.php';
$gibbonNotificationID = $_GET['gibbonNotificationID'] ?? '';

if (empty($gibbonNotificationID) || empty($_SESSION[$guid]['gibbonPersonID'])) {
    $URLBack = $URLBack.'&return=error1';
    header("Location: {$URLBack}");
    exit();
} else {
    // Check for existence of notification, belonging to this user
    $data = array('gibbonNotificationID' => $gibbonNotificationID, 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
    $sql = "SELECT * FROM gibbonNotification WHERE gibbonPersonID=:gibbonPersonID AND gibbonNotificationID=:gibbonNotificationID";
    
    $notification = $pdo->selectOne($sql, $data);

    if (empty($notification)) {
        $URLBack = $URLBack.'&return=error2';
        header("Location: {$URLBack}");
        exit();
    } else {
        $URL = $_SESSION[$guid]['absoluteURL'].$notification['actionLink'];

        //Archive notification
        $data = array('gibbonNotificationID' => $gibbonNotificationID, 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
        $sql = "UPDATE gibbonNotification SET status='Archived' WHERE gibbonPersonID=:gibbonPersonID AND gibbonNotificationID=:gibbonNotificationID";
            
        $pdo->update($sql, $data);

        if (!$pdo->getQuerySuccess()) {
            $URLBack = $URLBack.'&return=error2';
            header("Location: {$URLBack}");
            exit();
        }

        //Success 0
        header("Location: {$URL}");
    }
}
