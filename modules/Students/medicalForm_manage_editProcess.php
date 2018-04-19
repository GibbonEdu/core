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

$gibbonPersonMedicalID = $_GET['gibbonPersonMedicalID'];
$search = $_GET['search'];
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/medicalForm_manage_edit.php&gibbonPersonMedicalID=$gibbonPersonMedicalID&search=$search";

if (isActionAccessible($guid, $connection2, '/modules/Students/medicalForm_manage_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    //Check if medical form specified
    if ($gibbonPersonMedicalID == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        try {
            $data = array('gibbonPersonMedicalID' => $gibbonPersonMedicalID);
            $sql = 'SELECT * FROM gibbonPersonMedical WHERE gibbonPersonMedicalID=:gibbonPersonMedicalID';
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
            $bloodType = $_POST['bloodType'];
            $longTermMedication = $_POST['longTermMedication'];
            $longTermMedicationDetails = (isset($_POST['longTermMedicationDetails']) ? $_POST['longTermMedicationDetails'] : '');
            $tetanusWithin10Years = $_POST['tetanusWithin10Years'];
            $comment = $_POST['comment'];

            //Write to database
            try {
                $data = array('bloodType' => $bloodType, 'longTermMedication' => $longTermMedication, 'longTermMedicationDetails' => $longTermMedicationDetails, 'tetanusWithin10Years' => $tetanusWithin10Years, 'comment' => $comment, 'gibbonPersonMedicalID' => $gibbonPersonMedicalID);
                $sql = 'UPDATE gibbonPersonMedical SET bloodType=:bloodType, longTermMedication=:longTermMedication, longTermMedicationDetails=:longTermMedicationDetails, tetanusWithin10Years=:tetanusWithin10Years, comment=:comment WHERE gibbonPersonMedicalID=:gibbonPersonMedicalID';
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
