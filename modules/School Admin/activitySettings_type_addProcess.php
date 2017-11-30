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

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/activitySettings_type_add.php";

if (isActionAccessible($guid, $connection2, '/modules/School Admin/activitySettings_type_add.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    //Validate Inputs
    $name = (isset($_POST['name']))? $_POST['name'] : NULL;
    $description = (isset($_POST['description']))? $_POST['description'] : NULL;
    $access = (isset($_POST['access']))? $_POST['access'] : NULL;
    $enrolmentType = (isset($_POST['enrolmentType']))? $_POST['enrolmentType'] : NULL;
    $maxPerStudent = (isset($_POST['maxPerStudent']))? $_POST['maxPerStudent'] : 0;
    $waitingList = (isset($_POST['waitingList']))? $_POST['waitingList'] : 'Y';
    $backupChoice = (isset($_POST['backupChoice']))? $_POST['backupChoice'] : 'Y';

    if ($name == '' || $access == '' || $enrolmentType == '' || $backupChoice == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        //Check unique inputs for uniquness in current school year
        try {
            $data = array('name' => $name);
            $sql = 'SELECT name FROM gibbonActivityType WHERE name=:name';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
            exit();
        }

        if ($result->rowCount() > 0) {
            $URL .= '&return=error3';
            header("Location: {$URL}");
        } else {
            //Write to database
            try {
                $data = array('name' => $name, 'description' => $description, 'access' => $access, 'enrolmentType' => $enrolmentType, 'maxPerStudent' => $maxPerStudent, 'waitingList' => $waitingList, 'backupChoice' => $backupChoice);
                $sql = "INSERT INTO gibbonActivityType SET name=:name, description=:description, access=:access, enrolmentType=:enrolmentType, maxPerStudent=:maxPerStudent, waitingList=:waitingList, backupChoice=:backupChoice";

                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
                exit();
            }

            //Last insert ID
            $AI = str_pad($connection2->lastInsertID(), 6, '0', STR_PAD_LEFT);

            $URL .= "&return=success0&editID=$AI";
            header("Location: {$URL}");
        }
    }
}
