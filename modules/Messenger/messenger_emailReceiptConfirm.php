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

//Get variables
$key = '';
if (isset($_GET['key'])) {
    $key = $_GET['key'];
}
$gibbonPersonID = '';
if (isset($_GET['gibbonPersonID'])) {
    $gibbonPersonID = $_GET['gibbonPersonID'];
}
$gibbonMessengerID = '';
if (isset($_GET['gibbonMessengerID'])) {
    $gibbonMessengerID = $_GET['gibbonMessengerID'];
}

//Check variables
if ($key == '' or $gibbonPersonID == '' or $gibbonMessengerID == '') {
    echo "<div class='error'>";
    echo __($guid, 'You have not specified one or more required parameters.');
    echo '</div>';
} else {
    //Check for record
    $keyReadFail = false;
    try {
        $dataKeyRead = array('key' => $key, 'gibbonPersonID' => $gibbonPersonID, 'gibbonMessengerID' => $gibbonMessengerID, 'key' => $key);
        $sqlKeyRead = 'SELECT * FROM gibbonMessengerReceipt WHERE `key`=:key AND gibbonPersonID=:gibbonPersonID AND gibbonMessengerID=:gibbonMessengerID';
        $resultKeyRead = $connection2->prepare($sqlKeyRead);
        $resultKeyRead->execute($dataKeyRead);
    } catch (PDOException $e) {
        echo "<div class='error'>";
        echo __($guid, 'Your request failed due to a database error.');
        echo '</div>';
    }

    if ($resultKeyRead->rowCount() != 1) { //If not exists, report error
        echo "<div class='error'>";
        echo __($guid, 'The selected record does not exist, or you do not have access to it.');
        echo '</div>';
    } else {    //If exists check confirmed
        $rowKeyRead = $resultKeyRead->fetch();

        if ($rowKeyRead['confirmed'] == 'Y') { //If already confirmed, report success
            echo "<div class='success'>";
            echo __($guid, 'Thank you for confirming receipt and reading of this email.');
            echo '</div>';
        } else { //If not confirmed, confirm
            $keyWriteFail = false;
            try {
                $dataKeyWrite = array('key' => $key, 'gibbonPersonID' => $gibbonPersonID, 'gibbonMessengerID' => $gibbonMessengerID);
                $sqlKeyWrite = 'UPDATE gibbonMessengerReceipt SET confirmed=\'Y\', confirmedTimestamp=now() WHERE `key`=:key AND gibbonPersonID=:gibbonPersonID AND gibbonMessengerID=:gibbonMessengerID';
                $resultKeyWrite = $connection2->prepare($sqlKeyWrite);
                $resultKeyWrite->execute($dataKeyWrite);
            } catch (PDOException $e) {
                print $e->getMessage();
                $keyWriteFail = true;
            }

            if ($keyWriteFail == true) { //Report error
                echo "<div class='error'>";
                echo __($guid, 'Your request failed due to a database error.');
                echo '</div>';
            } else { //Report success
                echo "<div class='success'>";
                echo __($guid, 'Thank you for confirming receipt and reading of this email.');
                echo '</div>';
            }
        }
    }
}
