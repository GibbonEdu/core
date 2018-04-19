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

$gibbonAttendanceCodeID = (isset($_GET['gibbonAttendanceCodeID']))? $_GET['gibbonAttendanceCodeID'] : NULL;
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/attendanceSettings.php";

if (isActionAccessible($guid, $connection2, '/modules/School Admin/attendanceSettings_manage_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    if ($gibbonAttendanceCodeID == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        try {
            $data = array('gibbonAttendanceCodeID' => $gibbonAttendanceCodeID);
            $sql = 'SELECT gibbonAttendanceCodeID FROM gibbonAttendanceCode WHERE gibbonAttendanceCodeID=:gibbonAttendanceCodeID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
            exit();
        }

        if ($result->rowCount() != 1) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
        } else {
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
                    $data = array('name' => $name, 'nameShort' => $nameShort, 'gibbonAttendanceCodeID' => $gibbonAttendanceCodeID);
                    $sql = 'SELECT name, nameShort FROM gibbonAttendanceCode WHERE (name=:name OR nameShort=:nameShort) AND NOT gibbonAttendanceCodeID=:gibbonAttendanceCodeID';
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
                        $data = array( 'gibbonAttendanceCodeID' => $gibbonAttendanceCodeID, 'name' => $name, 'nameShort' => $nameShort, 'direction' => $direction, 'scope' => $scope, 'sequenceNumber' => $sequenceNumber, 'active' => $active, 'reportable' => $reportable, 'future' => $future, 'gibbonRoleIDAll' => $gibbonRoleIDAll );

                        $sql = 'UPDATE gibbonAttendanceCode SET name=:name, nameShort=:nameShort, direction=:direction, scope=:scope, sequenceNumber=:sequenceNumber, active=:active, reportable=:reportable, future=:future, gibbonRoleIDAll=:gibbonRoleIDAll WHERE gibbonAttendanceCodeID=:gibbonAttendanceCodeID';
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
    }
}
