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

$name = $_POST['name'];
$nameShort = $_POST['nameShort'];
$gibbonExternalAssessmentID = $_POST['gibbonExternalAssessmentID'];
$description = $_POST['description'];
$active = $_POST['active'];
$allowFileUpload = $_POST['allowFileUpload'];

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address']).'/externalAssessments_manage_edit.php&gibbonExternalAssessmentID='.$gibbonExternalAssessmentID;

if (isActionAccessible($guid, $connection2, '/modules/School Admin/externalAssessments_manage_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    if ($gibbonExternalAssessmentID == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        try {
            $data = array('gibbonExternalAssessmentID' => $gibbonExternalAssessmentID);
            $sql = 'SELECT * FROM gibbonExternalAssessment WHERE gibbonExternalAssessmentID=:gibbonExternalAssessmentID';
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
            if ($name == '' or $nameShort == '' or $description == '' or $active == '') {
                $URL .= '&return=error3';
                header("Location: {$URL}");
            } else {
                //Check unique inputs for uniquness
                try {
                    $data = array('name' => $name, 'nameShort' => $nameShort, 'gibbonExternalAssessmentID' => $gibbonExternalAssessmentID);
                    $sql = 'SELECT * FROM gibbonExternalAssessment WHERE (name=:name OR nameShort=:nameShort) AND NOT gibbonExternalAssessmentID=:gibbonExternalAssessmentID';
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
                        $data = array('name' => $name, 'nameShort' => $nameShort, 'description' => $description, 'active' => $active, 'allowFileUpload' => $allowFileUpload, 'gibbonExternalAssessmentID' => $gibbonExternalAssessmentID);
                        $sql = 'UPDATE gibbonExternalAssessment SET name=:name, nameShort=:nameShort, `description`=:description, active=:active, allowFileUpload=:allowFileUpload WHERE gibbonExternalAssessmentID=:gibbonExternalAssessmentID';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $URL .= '&return=error2';
                        header("Location: {$URL}");
                        exit();
                    }

                    $URL .= '&return=success0';
                    header("Location: {$URL}");
                }
            }
        }
    }
}
