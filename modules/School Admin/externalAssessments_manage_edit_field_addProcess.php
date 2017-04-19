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

//New PDO DB connection
$pdo = new Gibbon\sqlConnection();
$connection2 = $pdo->getConnection();

@session_start();

$gibbonExternalAssessmentID = $_POST['gibbonExternalAssessmentID'];
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/externalAssessments_manage_edit_field_add.php&gibbonExternalAssessmentID=$gibbonExternalAssessmentID";

if (isActionAccessible($guid, $connection2, '/modules/School Admin/externalAssessments_manage_edit_field_add.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    //Validate Inputs
    $name = $_POST['name'];
    $category = $_POST['category'];
    $order = $_POST['order'];
    $gibbonScaleID = $_POST['gibbonScaleID'];
    $gibbonYearGroupIDList = '';
    for ($i = 0; $i < $_POST['count']; ++$i) {
        if (isset($_POST["gibbonYearGroupIDCheck$i"])) {
            if ($_POST["gibbonYearGroupIDCheck$i"] == 'on') {
                $gibbonYearGroupIDList = $gibbonYearGroupIDList.$_POST["gibbonYearGroupID$i"].',';
            }
        }
    }
    $gibbonYearGroupIDList = substr($gibbonYearGroupIDList, 0, (strlen($gibbonYearGroupIDList) - 1));

    if ($gibbonExternalAssessmentID == '' or $name == '' or $category == '' or $order == '' or $gibbonScaleID == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        //Write to database
        try {
            $data = array('gibbonExternalAssessmentID' => $gibbonExternalAssessmentID, 'name' => $name, 'category' => $category, 'order' => $order, 'gibbonScaleID' => $gibbonScaleID, 'gibbonYearGroupIDList' => $gibbonYearGroupIDList);
            $sql = 'INSERT INTO gibbonExternalAssessmentField SET gibbonExternalAssessmentID=:gibbonExternalAssessmentID, name=:name, category=:category, `order`=:order, gibbonScaleID=:gibbonScaleID, gibbonYearGroupIDList=:gibbonYearGroupIDList';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
            exit();
        }

        //Last insert ID
        $AI = str_pad($connection2->lastInsertID(), 6, '0', STR_PAD_LEFT);

        //Success 0
        $URL .= "&return=success0&editID=$AI";
        header("Location: {$URL}");
    }
}
