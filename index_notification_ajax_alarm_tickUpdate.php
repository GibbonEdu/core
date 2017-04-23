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

$gibbonAlarmID = $_POST['gibbonAlarmID'];
$gibbonPersonID = $_POST['gibbonPersonID'];

//Proceed!
if ($gibbonAlarmID == '' or $gibbonPersonID == '') { echo "<div class='error'>";
    echo __($guid, 'An error has occurred.');
    echo '</div>';
} else {
    //Check confirmation of alarm
    try {
        $dataConfirm = array('gibbonAlarmID' => $gibbonAlarmID, 'gibbonAlarmID2' => $gibbonAlarmID, 'gibbonPersonID' => $gibbonPersonID);
        $sqlConfirm = 'SELECT surname, preferredName, gibbonAlarmConfirmID, gibbonPerson.gibbonPersonID AS confirmer, gibbonAlarm.gibbonPersonID as sounder FROM gibbonPerson JOIN gibbonAlarm ON (gibbonAlarm.gibbonAlarmID=:gibbonAlarmID) LEFT JOIN gibbonAlarmConfirm ON (gibbonAlarmConfirm.gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonAlarmConfirm.gibbonAlarmID=:gibbonAlarmID2) WHERE gibbonPerson.gibbonPersonID=:gibbonPersonID';
        $resultConfirm = $connection2->prepare($sqlConfirm);
        $resultConfirm->execute($dataConfirm);
    } catch (PDOException $e) {
        echo $e->getMessage();
    }

    if ($resultConfirm->rowCount() != 1) {
        echo "<div class='error'>";
        echo __($guid, 'An error has occurred.');
        echo '</div>';
    } else {
        $rowConfirm = $resultConfirm->fetch();

        echo "<td style='color: #fff'>";
        echo formatName('', $rowConfirm['preferredName'], $rowConfirm['surname'], 'Staff', true, true).'<br/>';
        echo '</td>';
        echo "<td style='color: #fff'>";
        if ($rowConfirm['sounder'] == $rowConfirm['confirmer']) {
            echo __($guid, 'NA');
        } else {
            if ($rowConfirm['gibbonAlarmConfirmID'] != '') {
                echo "<img src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/iconTick.png'/> ";
            }
        }
        echo '</td>';
        echo "<td style='color: #fff'>";
        if ($rowConfirm['sounder'] != $rowConfirm['confirmer']) {
            if ($rowConfirm['gibbonAlarmConfirmID'] == '') {
                echo "<a target='_parent' href='".$_SESSION[$guid]['absoluteURL'].'/index_notification_ajax_alarmConfirmProcess.php?gibbonPersonID='.$rowConfirm['confirmer']."&gibbonAlarmID=$gibbonAlarmID'><img title='".__($guid, 'Confirm')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/iconTick_light.png'/></a> ";
            }
        }
        echo '</td>';
    }
}
