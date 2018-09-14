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

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=notifications.php';

if (isset($_GET['gibbonNotificationID']) == false) {
    $URL = $URL.'&return=error1';
    header("Location: {$URL}");
    exit();
} else {
    $gibbonNotificationID = $_GET['gibbonNotificationID'];

    //Check for existence of notification, beloning to this user
    try {
        $data = array('gibbonNotificationID' => $gibbonNotificationID, 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
        $sql = 'SELECT * FROM gibbonNotification WHERE gibbonPersonID=:gibbonPersonID AND gibbonNotificationID=:gibbonNotificationID';
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        echo $e->getMessage();
        $URL = $URL.'&return=error2';
        header("Location: {$URL}");
        exit();
    }

    if ($result->rowCount() != 1) {
        $URL = $URL.'&return=error2';
        header("Location: {$URL}");
        exit();
    } else {
        //Delete notification
        try {
            $data = array('gibbonNotificationID' => $gibbonNotificationID, 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
            $sql = 'DELETE FROM gibbonNotification WHERE gibbonPersonID=:gibbonPersonID AND gibbonNotificationID=:gibbonNotificationID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $URL = $URL.'&return=error2';
            header("Location: {$URL}");
            exit();
        }

        //Success 0
        $URL = $URL.'&return=success0';
        header("Location: {$URL}");
    }
}
