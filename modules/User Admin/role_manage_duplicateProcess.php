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

//Module includes
include './moduleFunctions.php';

$gibbonRoleID = $_GET['gibbonRoleID'];
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/role_manage.php&gibbonRoleID=$gibbonRoleID";

if (isActionAccessible($guid, $connection2, '/modules/User Admin/role_manage_duplicate.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    //Validate Inputs
    $name = $_POST['name'];
    $nameShort = $_POST['nameShort'];

    if ($gibbonRoleID == '' or $name == '' or $nameShort == '') {
        $URL .= '&return=error3';
        header("Location: {$URL}");
    } else {
        //Lock table
        try {
            $sql = 'LOCK TABLE gibbonRole WRITE, gibbonPermission WRITE';
            $result = $connection2->query($sql);
        } catch (PDOException $e) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
            exit();
        }

        //Get next autoincrement for unit
        try {
            $sqlAI = "SHOW TABLE STATUS LIKE 'gibbonRole'";
            $resultAI = $connection2->query($sqlAI);
        } catch (PDOException $e) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
            exit();
        }

        $rowAI = $resultAI->fetch();
        $AI = str_pad($rowAI['Auto_increment'], 8, '0', STR_PAD_LEFT);
        $partialFail = false;

        if ($AI == '') {
            $URL .= '&return=error2';
            header("Location: {$URL}");
        } else {
            //Write to database
            try {
                $data = array('gibbonRoleID' => $gibbonRoleID);
                $sql = 'SELECT * FROM gibbonRole WHERE gibbonRoleID=:gibbonRoleID';
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
                $row = $result->fetch();
                try {
                    $data = array('gibbonRoleID' => $AI, 'category' => $row['category'], 'name' => $name, 'nameShort' => $nameShort, 'description' => $row['description'], 'restriction' => $row['restriction']);
                    $sql = "INSERT INTO gibbonRole SET gibbonRoleID=:gibbonRoleID, category=:category, name=:name, nameShort=:nameShort, description=:description, type='Additional', restriction=:restriction";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                    exit();
                }

                //Duplicate permissions
                try {
                    $dataPermissions = array('gibbonRoleID' => $gibbonRoleID);
                    $sqlPermissions = 'SELECT * FROM gibbonPermission WHERE gibbonRoleID=:gibbonRoleID';
                    $resultPermissions = $connection2->prepare($sqlPermissions);
                    $resultPermissions->execute($dataPermissions);
                } catch (PDOException $e) {
                    $partialFail = true;
                    echo $e->getMessage();
                }

                while ($rowPermissions = $resultPermissions->fetch()) {
                    $copyOK = true;
                    try {
                        $dataCopy = array('gibbonRoleID' => $AI, 'gibbonActionID' => $rowPermissions['gibbonActionID']);
                        $sqlCopy = 'INSERT INTO gibbonPermission SET gibbonRoleID=:gibbonRoleID, gibbonActionID=:gibbonActionID';
                        $resultCopy = $connection2->prepare($sqlCopy);
                        $resultCopy->execute($dataCopy);
                    } catch (PDOException $e) {
                        $partialFail = true;
                    }
                }

                //Unlock locked database tables
                try {
                    $sql = 'UNLOCK TABLES';
                    $result = $connection2->query($sql);
                } catch (PDOException $e) {
                }

                if ($partialFail == true) {
                    $URL .= '&return=error6';
                    header("Location: {$URL}");
                } else {
                    $URL .= '&return=success0';
                    header("Location: {$URL}");
                }
            }
        }
    }
}
