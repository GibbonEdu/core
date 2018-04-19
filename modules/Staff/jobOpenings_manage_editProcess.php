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

$gibbonStaffJobOpeningID = $_GET['gibbonStaffJobOpeningID'];
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address']).'/jobOpenings_manage_edit.php&gibbonStaffJobOpeningID='.$gibbonStaffJobOpeningID;

if (isActionAccessible($guid, $connection2, '/modules/Staff/jobOpenings_manage_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    //Check if role specified
    if ($gibbonStaffJobOpeningID == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        try {
            $data = array('gibbonStaffJobOpeningID' => $gibbonStaffJobOpeningID);
            $sql = 'SELECT * FROM gibbonStaffJobOpening WHERE gibbonStaffJobOpeningID=:gibbonStaffJobOpeningID';
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
            $type = $_POST['type'];
            $jobTitle = $_POST['jobTitle'];
            $dateOpen = dateConvert($guid, $_POST['dateOpen']);
            $active = $_POST['active'];
            $description = $_POST['description'];

            if ($type == '' or $jobTitle == '' or $dateOpen == '' or $active == '' or $description == '') {
                $URL .= '&return=error3';
                header("Location: {$URL}");
            } else {
                //Write to database
                try {
                    $data = array('type' => $type, 'jobTitle' => $jobTitle, 'dateOpen' => $dateOpen, 'active' => $active, 'description' => $description, 'gibbonStaffJobOpeningID' => $gibbonStaffJobOpeningID);
                    $sql = 'UPDATE gibbonStaffJobOpening SET type=:type, jobTitle=:jobTitle, dateOpen=:dateOpen, active=:active, description=:description WHERE gibbonStaffJobOpeningID=:gibbonStaffJobOpeningID';
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
