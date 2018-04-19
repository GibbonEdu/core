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

include '../../functions.php';
include '../../config.php';

@session_start();

$value = $_POST['value'];
$descriptor = $_POST['descriptor'];
$sequenceNumber = $_POST['sequenceNumber'];
$isDefault = $_POST['isDefault'];

$gibbonScaleID = $_POST['gibbonScaleID'];

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/gradeScales_manage_edit_grade_add.php&gibbonScaleID=$gibbonScaleID";

if (isActionAccessible($guid, $connection2, '/modules/School Admin/gradeScales_manage_edit_grade_add.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    //Validate Inputs
    if ($gibbonScaleID == '' or $value == '' or $descriptor == '' or $sequenceNumber == '' or $isDefault == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        //Check unique inputs for uniquness
        try {
            $data = array('value' => $value, 'sequenceNumber' => $sequenceNumber, 'gibbonScaleID' => $gibbonScaleID);
            $sql = 'SELECT * FROM gibbonScaleGrade WHERE ((value=:value) OR (sequenceNumber=:sequenceNumber)) AND gibbonScaleID=:gibbonScaleID';
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
            //If isDefault is Y, then set all other grades in scale to N
            if ($isDefault == 'Y') {
                try {
                    $data = array('gibbonScaleID' => $gibbonScaleID);
                    $sql = "UPDATE gibbonScaleGrade SET isDefault='N' WHERE gibbonScaleID=:gibbonScaleID";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                    exit();
                }
            }

            //Write to database
            try {
                $data = array('gibbonScaleID' => $gibbonScaleID, 'value' => $value, 'descriptor' => $descriptor, 'sequenceNumber' => $sequenceNumber, 'isDefault' => $isDefault);
                $sql = 'INSERT INTO gibbonScaleGrade SET gibbonScaleID=:gibbonScaleID, value=:value, descriptor=:descriptor, sequenceNumber=:sequenceNumber, isDefault=:isDefault';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
                exit();
            }

            //Last insert ID
            $AI = str_pad($connection2->lastInsertID(), 7, '0', STR_PAD_LEFT);

            $URL .= "&return=success0&editID=$AI";
            header("Location: {$URL}");
        }
    }
}
