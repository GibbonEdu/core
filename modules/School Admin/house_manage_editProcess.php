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

$gibbonHouseID = $_GET['gibbonHouseID'];
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address']).'/house_manage_edit.php&gibbonHouseID='.$gibbonHouseID;

if (isActionAccessible($guid, $connection2, '/modules/School Admin/house_manage_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    //Check if school year specified
    if ($gibbonHouseID == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        try {
            $data = array('gibbonHouseID' => $gibbonHouseID);
            $sql = 'SELECT * FROM gibbonHouse WHERE gibbonHouseID=:gibbonHouseID';
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
            $nameShort = $_POST['nameShort'];

            if ($name == '' or $nameShort == '') {
                $URL .= '&return=error3';
                header("Location: {$URL}");
            } else {
                //Check unique inputs for uniquness
                try {
                    $dataCheck = array('name' => $name, 'nameShort' => $nameShort, 'gibbonHouseID' => $gibbonHouseID);
                    $sqlCheck = 'SELECT * FROM gibbonHouse WHERE (name=:name OR nameShort=:nameShort) AND NOT gibbonHouseID=:gibbonHouseID';
                    $resultCheck = $connection2->prepare($sqlCheck);
                    $resultCheck->execute($dataCheck);
                } catch (PDOException $e) {
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                    exit();
                }

                if ($resultCheck->rowCount() > 0) {
                    $URL .= '&return=error3';
                    header("Location: {$URL}");
                } else {
                    $row = $result->fetch();

                    //Sort out logo
                    $imageFail = false;
                    $logo = $_POST['logo'];
                    if (!empty($_FILES['file1']['tmp_name'])) {
                        $fileUploader = new Gibbon\FileUploader($pdo, $gibbon->session);
                        
                        $file = (isset($_FILES['file1']))? $_FILES['file1'] : null;

                        // Upload the file, return the /uploads relative path
                        $logo = $fileUploader->uploadFromPost($file, $name);

                        if (empty($logo)) {
                            $imageFail = true;
                        }
                    }

                    //Write to database
                    try {
                        $data = array('name' => $name, 'nameShort' => $nameShort, 'logo' => $logo, 'gibbonHouseID' => $gibbonHouseID);
                        $sql = 'UPDATE gibbonHouse SET name=:name, nameShort=:nameShort, logo=:logo WHERE gibbonHouseID=:gibbonHouseID';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $URL .= '&return=error2';
                        header("Location: {$URL}");
                        exit();
                    }

                    if ($imageFail) {
                        $URL .= '&return=warning1';
                        header("Location: {$URL}");
                    } else {
                        $URL .= '&return=success0';
                        header("Location: {$URL}");
                    }
                }
            }
        }
    }
}
