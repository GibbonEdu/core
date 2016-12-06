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

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]['timezone']);

$gibbonStaffID = $_GET['gibbonStaffID'];
$gibbonPersonID = $_GET['gibbonPersonID'];
$search = $_GET['search'];

if ($gibbonStaffID == '' or $gibbonPersonID == '') { echo 'Fatal error loading this page!';
} else {
    $URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/staff_manage_edit_facility_add.php&gibbonPersonID=$gibbonPersonID&gibbonStaffID=$gibbonStaffID&search=$search";

    if (isActionAccessible($guid, $connection2, '/modules/Staff/staff_manage_edit_facility_add.php') == false) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
    } else {
        //Proceed!
        //Check if person specified
        if ($gibbonStaffID == '' or $gibbonPersonID == '') {
            $URL .= '&return=error1';
            header("Location: {$URL}");
        } else {
            try {
                $data = array('gibbonStaffID' => $gibbonStaffID, 'gibbonPersonID' => $gibbonPersonID);
                $sql = 'SELECT gibbonStaff.*, preferredName, surname FROM gibbonStaff JOIN gibbonPerson ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonStaffID=:gibbonStaffID AND gibbonPerson.gibbonPersonID=:gibbonPersonID';
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
                $gibbonSpaceID = $_POST['gibbonSpaceID'];
                $usageType = $_POST['usageType'];

                if ($gibbonSpaceID == '') {
                    $URL .= '&return=error1&step=1';
                    header("Location: {$URL}");
                } else {
                    //Write to database
                    try {
                        $data = array('gibbonPersonID' => $gibbonPersonID, 'gibbonSpaceID' => $gibbonSpaceID, 'usageType' => $usageType);
                        $sql = 'INSERT INTO gibbonSpacePerson SET gibbonPersonID=:gibbonPersonID, gibbonSpaceID=:gibbonSpaceID, usageType=:usageType';
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
