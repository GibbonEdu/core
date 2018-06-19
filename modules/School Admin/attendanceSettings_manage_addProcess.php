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

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/attendanceSettings.php";

if (isActionAccessible($guid, $connection2, '/modules/School Admin/attendanceSettings_manage_add.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    //Validate Inputs
    $name = (isset($_POST['name']))? $_POST['name'] : NULL;
    $nameShort = (isset($_POST['nameShort']))? $_POST['nameShort'] : NULL;
    $direction = (isset($_POST['direction']))? $_POST['direction'] : NULL;
    $scope = (isset($_POST['scope']))? $_POST['scope'] : NULL;
    $sequenceNumber = (isset($_POST['sequenceNumber']))? $_POST['sequenceNumber'] : NULL;
    $active = (isset($_POST['active']))? $_POST['active'] : NULL;
    $reportable = (isset($_POST['reportable']))? $_POST['reportable'] : NULL;
    $future = (isset($_POST['future']))? $_POST['future'] : NULL;

    $gibbonRoleIDArray = (isset($_POST['gibbonRoleIDAll']))? $_POST['gibbonRoleIDAll'] : NULL;
    $gibbonRoleIDAll = (is_array($gibbonRoleIDArray))? implode(',', $gibbonRoleIDArray) : $gibbonRoleIDArray;

    if ($gibbonRoleIDAll == '' or $name == '' or $nameShort == '' or $direction == '' or $scope == '' or $sequenceNumber == '' or $active == '' or $reportable == '' or $future == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        //Check unique inputs for uniquness in current school year
        try {
            $data = array('name' => $name, 'nameShort' => $nameShort);
            $sql = 'SELECT name, nameShort FROM gibbonAttendanceCode WHERE (name=:name OR nameShort=:nameShort)';
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
                $data = array('name' => $name, 'nameShort' => $nameShort, 'type' => 'Additional', 'direction' => $direction, 'scope' => $scope, 'sequenceNumber' => $sequenceNumber, 'active' => $active, 'reportable' => $reportable, 'future' => $future, 'gibbonRoleIDAll' => $gibbonRoleIDAll  );

                $sql = 'INSERT INTO gibbonAttendanceCode SET name=:name, nameShort=:nameShort, type=:type, direction=:direction, scope=:scope, sequenceNumber=:sequenceNumber, active=:active, reportable=:reportable, future=:future, gibbonRoleIDAll=:gibbonRoleIDAll ';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
                exit();
            }

            //Last insert ID
            $AI = str_pad($connection2->lastInsertID(), 5, '0', STR_PAD_LEFT);

            $URL .= "&return=success0&editID=$AI";
            header("Location: {$URL}");
        }
    }
}
