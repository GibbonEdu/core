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

$gibbonStaffApplicationFormID = $_POST['gibbonStaffApplicationFormID'];
$search = $_GET['search'];
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/applicationForm_manage_reject.php&gibbonStaffApplicationFormID=$gibbonStaffApplicationFormID&search=$search";
$URLReject = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/applicationForm_manage.php&search=$search";

if (isActionAccessible($guid, $connection2, '/modules/Staff/applicationForm_manage_reject.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    //Check if school year specified

    if ($gibbonStaffApplicationFormID == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        try {
            $data = array('gibbonStaffApplicationFormID' => $gibbonStaffApplicationFormID);
            $sql = 'SELECT * FROM gibbonStaffApplicationForm WHERE gibbonStaffApplicationFormID=:gibbonStaffApplicationFormID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
            exit();
        }

        if ($result->rowCount() != 1) {
            $URL .= '&eeturn=error2';
            header("Location: {$URL}");
        } else {
            //Write to database
            try {
                $data = array('gibbonStaffApplicationFormID' => $gibbonStaffApplicationFormID);
                $sql = "UPDATE gibbonStaffApplicationForm SET status='Rejected' WHERE gibbonStaffApplicationFormID=:gibbonStaffApplicationFormID";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
                exit();
            }

            $URLReject = $URLReject.'&return=success0';
            header("Location: {$URLReject}");
        }
    }
}
