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

//Gibbon system-wide includes
include './functions.php';
include './config.php';

//New PDO DB connection
$pdo = new Gibbon\sqlConnection();
$connection2 = $pdo->getConnection();

@session_start();

$gibbonAlarmID = $_GET['gibbonAlarmID'];
$gibbonPersonID = $_GET['gibbonPersonID'];
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php';

//Proceed!
if ($gibbonAlarmID == '' or $gibbonPersonID == '') {
    header("Location: {$URL}");
} else {
    //Check alarm
    try {
        $data = array('gibbonAlarmID' => $gibbonAlarmID);
        $sql = 'SELECT * FROM gibbonAlarm WHERE gibbonAlarmID=:gibbonAlarmID';
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
    }

    if ($result->rowCount() == 1) {
        $row = $result->fetch();

        //Check confirmation of alarm
        try {
            $dataConfirm = array('gibbonAlarmID' => $gibbonAlarmID, 'gibbonPersonID' => $gibbonPersonID);
            $sqlConfirm = 'SELECT * FROM gibbonAlarmConfirm WHERE gibbonAlarmID=:gibbonAlarmID AND gibbonPersonID=:gibbonPersonID';
            $resultConfirm = $connection2->prepare($sqlConfirm);
            $resultConfirm->execute($dataConfirm);
        } catch (PDOException $e) {
        }

        if ($resultConfirm->rowCount() == 0) {
            //Insert confirmation
            try {
                $dataConfirm = array('gibbonAlarmID' => $gibbonAlarmID, 'gibbonPersonID' => $gibbonPersonID, 'timestamp' => date('Y-m-d H:i:s'));
                $sqlConfirm = 'INSERT INTO gibbonAlarmConfirm SET gibbonAlarmID=:gibbonAlarmID, gibbonPersonID=:gibbonPersonID, timestamp=:timestamp';
                $resultConfirm = $connection2->prepare($sqlConfirm);
                $resultConfirm->execute($dataConfirm);
            } catch (PDOException $e) {
            }
        }
    }

    //Success 0
    header("Location: {$URL}");
}
