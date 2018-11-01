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

$gibbonFileExtensionID = $_GET['gibbonFileExtensionID'];
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address']).'/fileExtensions_manage_edit.php&gibbonFileExtensionID='.$gibbonFileExtensionID;

if (isActionAccessible($guid, $connection2, '/modules/School Admin/fileExtensions_manage_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    //Check if school year specified
    if ($gibbonFileExtensionID == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        try {
            $data = array('gibbonFileExtensionID' => $gibbonFileExtensionID);
            $sql = 'SELECT * FROM gibbonFileExtension WHERE gibbonFileExtensionID=:gibbonFileExtensionID';
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
            $extension = strtolower($_POST['extension']);
            $name = $_POST['name'];
            $type = $_POST['type'];

            $illegalFileExtensions = Gibbon\FileUploader::getIllegalFileExtensions();

            if ($extension == '' or $name == '' or $type == '' or in_array($extension, $illegalFileExtensions)) {
                $URL .= '&return=error3';
                header("Location: {$URL}");
            } else {
                //Check unique inputs for uniquness
                try {
                    $data = array('name' => $name, 'gibbonFileExtensionID' => $gibbonFileExtensionID);
                    $sql = 'SELECT * FROM gibbonFileExtension WHERE (name=:name) AND NOT gibbonFileExtensionID=:gibbonFileExtensionID';
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
                        $data = array('extension' => $extension, 'name' => $name, 'type' => $type, 'gibbonFileExtensionID' => $gibbonFileExtensionID);
                        $sql = 'UPDATE gibbonFileExtension SET extension=:extension, name=:name, type=:type WHERE gibbonFileExtensionID=:gibbonFileExtensionID';
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
