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

$gibbonExternalAssessmentFieldID = $_GET['gibbonExternalAssessmentFieldID'];
$gibbonExternalAssessmentID = $_GET['gibbonExternalAssessmentID'];
if ($gibbonExternalAssessmentID == '') { echo 'Fatal error loading this page!';
} else {
    $URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/externalAssessments_manage_edit_field_edit.php&gibbonExternalAssessmentID=$gibbonExternalAssessmentID&gibbonExternalAssessmentFieldID=$gibbonExternalAssessmentFieldID";

    if (isActionAccessible($guid, $connection2, '/modules/School Admin/externalAssessments_manage_edit_field_edit.php') == false) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
    } else {
        //Proceed!
        //Check if tt specified
        if ($gibbonExternalAssessmentFieldID == '') {
            $URL .= '&return=error1';
            header("Location: {$URL}");
        } else {
            try {
                $data = array('gibbonExternalAssessmentFieldID' => $gibbonExternalAssessmentFieldID);
                $sql = 'SELECT * FROM gibbonExternalAssessmentField WHERE gibbonExternalAssessmentFieldID=:gibbonExternalAssessmentFieldID';
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
                $name = $_POST['name'];
                $category = $_POST['category'];
                $order = $_POST['order'];
                $gibbonScaleID = $_POST['gibbonScaleID'];
                $gibbonYearGroupIDList = '';
                if (!empty($_POST['gibbonYearGroupIDList']) && is_array($_POST['gibbonYearGroupIDList'])) {
                    $gibbonYearGroupIDList = implode(',', $_POST['gibbonYearGroupIDList']);
                }

                if ($gibbonExternalAssessmentID == '' or $name == '' or $category == '' or $order == '' or $gibbonScaleID == '') {
                    $URL .= '&return=error3';
                    header("Location: {$URL}");
                } else {
                    //Write to database
                    try {
                        $data = array('name' => $name, 'category' => $category, 'order' => $order, 'gibbonScaleID' => $gibbonScaleID, 'gibbonYearGroupIDList' => $gibbonYearGroupIDList, 'gibbonExternalAssessmentFieldID' => $gibbonExternalAssessmentFieldID);
                        $sql = 'UPDATE gibbonExternalAssessmentField SET name=:name, category=:category, `order`=:order, gibbonScaleID=:gibbonScaleID, gibbonYearGroupIDList=:gibbonYearGroupIDList WHERE gibbonExternalAssessmentFieldID=:gibbonExternalAssessmentFieldID';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        echo 'Here';
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
