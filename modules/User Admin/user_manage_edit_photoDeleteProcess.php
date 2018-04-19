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

//Gibbon system-wide includes
include '../../gibbon.php';

//Module includes
include './moduleFunctions.php';

$gibbonPersonID = $_GET['gibbonPersonID'];
$search = '';
if (isset($_GET['search'])) {
    $search = $_GET['search'];
}
$size = $_GET['size'];
$URL = $_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/User Admin/user_manage_edit.php&gibbonPersonID=$gibbonPersonID&search=$search";

if (isActionAccessible($guid, $connection2, '/modules/User Admin/user_manage_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    //Check if planner specified
    if ($gibbonPersonID == '' or $size == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        try {
            $data = array('gibbonPersonID' => $gibbonPersonID);
            $sql = 'SELECT * FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID';
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
            //UPDATE
            try {
                $sizeField = null;
                if ($size == '240') {
                    $sizeField = 'image_240';
                } elseif ($size == 'birthCertificate') {
                    $sizeField = 'birthCertificateScan';
                } elseif ($size == 'passport') {
                    $sizeField = 'citizenship1PassportScan';
                } elseif ($size == 'id') {
                    $sizeField = 'nationalIDCardScan';
                }
                $data = array('gibbonPersonID' => $gibbonPersonID);
                $sql = "UPDATE gibbonPerson SET $sizeField='' WHERE gibbonPersonID=:gibbonPersonID";
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
