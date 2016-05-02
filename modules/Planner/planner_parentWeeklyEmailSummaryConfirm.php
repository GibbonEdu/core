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

@session_start();

//Get variables
$gibbonSchoolYearID = '';
if (isset($_GET['gibbonSchoolYearID'])) {
    $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'];
}
$key = '';
if (isset($_GET['key'])) {
    $key = $_GET['key'];
}
$gibbonPersonIDStudent = '';
if (isset($_GET['gibbonPersonIDStudent'])) {
    $gibbonPersonIDStudent = $_GET['gibbonPersonIDStudent'];
}
$gibbonPersonIDParent = '';
if (isset($_GET['gibbonPersonIDParent'])) {
    $gibbonPersonIDParent = $_GET['gibbonPersonIDParent'];
}

//Check variables
if ($gibbonSchoolYearID == '' or $key == '' or $gibbonPersonIDStudent == '' or $gibbonPersonIDParent == '') {
    echo "<div class='error'>";
    echo __($guid, 'You have not specified one or more required parameters.');
    echo '</div>';
} else {
    //Check for record
    $keyReadFail = false;
    try {
        $dataKeyRead = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonPersonIDStudent' => $gibbonPersonIDStudent, 'gibbonPersonIDParent' => $gibbonPersonIDParent, 'key' => $key);
        $sqlKeyRead = 'SELECT * FROM gibbonPlannerParentWeeklyEmailSummary WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonIDStudent=:gibbonPersonIDStudent AND gibbonPersonIDParent=:gibbonPersonIDParent AND `key`=:key';
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
            echo __($guid, 'Thank you for confirmed receipt and reading of this email.');
            echo '</div>';
        } else { //If not confirmed, confirm
            $keyWriteFail = false;
            try {
                $dataKeyWrite = array('gibbonPersonIDStudent' => $gibbonPersonIDStudent, 'gibbonPersonIDParent' => $gibbonPersonIDParent, 'key' => $key);
                $sqlKeyWrite = "UPDATE gibbonPlannerParentWeeklyEmailSummary SET confirmed='Y' WHERE gibbonPersonIDStudent=:gibbonPersonIDStudent AND gibbonPersonIDParent=:gibbonPersonIDParent AND `key`=:key";
                $resultKeyWrite = $connection2->prepare($sqlKeyWrite);
                $resultKeyWrite->execute($dataKeyWrite);
            } catch (PDOException $e) {
                $keyWriteFail = true;
            }

            if ($keyWriteFail == true) { //Report error
                echo "<div class='error'>";
                echo __($guid, 'Your request failed due to a database error.');
                echo '</div>';
            } else { //Report success
                echo "<div class='success'>";
                echo __($guid, 'Thank you for confirmed receipt and reading of this email.');
                echo '</div>';
            }
        }
    }
}
