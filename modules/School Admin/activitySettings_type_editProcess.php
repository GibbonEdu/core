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

include '../../gibbon.php';

$gibbonActivityTypeID = isset($_GET['gibbonActivityTypeID'])? $_GET['gibbonActivityTypeID'] : '';
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/activitySettings_type_edit.php&gibbonActivityTypeID=".$gibbonActivityTypeID;

if (isActionAccessible($guid, $connection2, '/modules/School Admin/activitySettings_type_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    //Validate Inputs
    $description = (isset($_POST['description']))? $_POST['description'] : NULL;
    $access = (isset($_POST['access']))? $_POST['access'] : NULL;
    $enrolmentType = (isset($_POST['enrolmentType']))? $_POST['enrolmentType'] : NULL;
    $maxPerStudent = (isset($_POST['maxPerStudent']))? $_POST['maxPerStudent'] : 0;
    $waitingList = (isset($_POST['waitingList']))? $_POST['waitingList'] : 'Y';
    $backupChoice = (isset($_POST['backupChoice']))? $_POST['backupChoice'] : 'Y';

    if (empty($gibbonActivityTypeID) || $access == '' || $enrolmentType == '' || $backupChoice == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        //Check unique inputs for uniquness in current school year
        try {
            $data = array('gibbonActivityTypeID' => $gibbonActivityTypeID);
            $sql = 'SELECT name FROM gibbonActivityType WHERE gibbonActivityTypeID=:gibbonActivityTypeID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
            exit();
        }

        if ($result->rowCount() == 0) {
            $URL .= '&return=error3';
            header("Location: {$URL}");
        } else {
            //Write to database
            try {
                $data = array('gibbonActivityTypeID' => $gibbonActivityTypeID, 'description' => $description, 'access' => $access, 'enrolmentType' => $enrolmentType, 'maxPerStudent' => $maxPerStudent, 'waitingList' => $waitingList, 'backupChoice' => $backupChoice);
                $sql = "UPDATE gibbonActivityType SET description=:description, access=:access, enrolmentType=:enrolmentType, maxPerStudent=:maxPerStudent, waitingList=:waitingList, backupChoice=:backupChoice WHERE gibbonActivityTypeID=:gibbonActivityTypeID";

                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
                exit();
            }

            $URL .= "&return=success0";
            header("Location: {$URL}");
        }
    }
}
