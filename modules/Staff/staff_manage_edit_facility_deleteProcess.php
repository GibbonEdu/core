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

$gibbonStaffID = $_GET['gibbonStaffID'];
$gibbonSpacePersonID = $_GET['gibbonSpacePersonID'];
$allStaff = '';
if (isset($_GET['allStaff'])) {
    $allStaff = $_GET['allStaff'];
}
$search = '';
if (isset($_GET['search'])) {
    $search = $_GET['search'];
}
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/staff_manage_edit_facility_delete.php&gibbonSpacePersonID=$gibbonSpacePersonID&search=$search&allStaff=$allStaff";
$URLDelete = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/staff_manage_edit.php&gibbonStaffID=$gibbonStaffID&search=$search&allStaff=$allStaff";

if (isActionAccessible($guid, $connection2, '/modules/Staff/staff_manage_edit_facility_delete.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    //Check if school year specified
    if ($gibbonSpacePersonID == '' and $gibbonStaffID == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        try {
            $data = array('gibbonSpacePersonID' => $gibbonSpacePersonID);
            $sql = 'SELECT * FROM gibbonSpacePerson WHERE gibbonSpacePersonID=:gibbonSpacePersonID';
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
            //Write to database
            try {
                $data = array('gibbonSpacePersonID' => $gibbonSpacePersonID);
                $sql = 'DELETE FROM gibbonSpacePerson WHERE gibbonSpacePersonID=:gibbonSpacePersonID';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
                exit();
            }

            $URLDelete = $URLDelete.'&return=success0';
            header("Location: {$URLDelete}");
        }
    }
}
