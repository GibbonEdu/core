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

$gibbonModuleID = $_GET['gibbonModuleID'];
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address']).'/module_manage_edit.php&gibbonModuleID='.$gibbonModuleID;

if (isActionAccessible($guid, $connection2, '/modules/System Admin/module_manage_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    //Check if role specified
    if ($gibbonModuleID == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        try {
            $data = array('gibbonModuleID' => $gibbonModuleID);
            $sql = 'SELECT * FROM gibbonModule WHERE gibbonModuleID=:gibbonModuleID';
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
            $category = $_POST['category'];
            $active = $_POST['active'];

            if ($category == '' or $active == '') {
                $URL .= '&return=error3';
                header("Location: {$URL}");
            } else {
                //Write to database
                try {
                    $data = array('category' => $category, 'active' => $active, 'gibbonModuleID' => $gibbonModuleID);
                    $sql = 'UPDATE gibbonModule SET category=:category, active=:active WHERE gibbonModuleID=:gibbonModuleID';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                    exit();
                }

                //Reset cache to force top-menu reload
                $_SESSION[$guid]['pageLoads'] = null;

                $URL .= '&return=success0';
                header("Location: {$URL}");
            }
        }
    }
}
