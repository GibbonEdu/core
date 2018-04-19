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

if (isset($_GET['action']) == false or isset($_GET['gibbonNotificationID']) == false) {
    $URLBack = $URLBack.'&return=error1';
    header("Location: {$URLBack}");
    exit();
} else {
    $gibbonNotificationID = $_GET['gibbonNotificationID'];
    $URL = $_SESSION[$guid]['absoluteURL'].$_GET['action'];

    //Check for existence of notification, beloning to this user
    try {
        $data = array('gibbonNotificationID' => $gibbonNotificationID, 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
        $sql = 'SELECT * FROM gibbonNotification WHERE gibbonPersonID=:gibbonPersonID AND gibbonNotificationID=:gibbonNotificationID';
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        echo $e->getMessage();
        $URLBack = $URLBack.'&return=error2';
        header("Location: {$URLBack}");
        exit();
    }

    if ($result->rowCount() != 1) {
        $URLBack = $URLBack.'&return=error2';
        header("Location: {$URLBack}");
        exit();
    } else {
        //Archive notification
        try {
            $data = array('gibbonNotificationID' => $gibbonNotificationID, 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
            $sql = "UPDATE gibbonNotification SET status='Archived' WHERE gibbonPersonID=:gibbonPersonID AND gibbonNotificationID=:gibbonNotificationID";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $URLBack = $URLBack.'&return=error2';
            header("Location: {$URLBack}");
            exit();
        }

        //Success 0
        header("Location: {$URL}");
    }
}
