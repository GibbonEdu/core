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

$gibbonRoleID = $_GET['gibbonRoleID'];
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address']).'/role_manage_edit.php&gibbonRoleID='.$gibbonRoleID;

if (isActionAccessible($guid, $connection2, '/modules/User Admin/role_manage_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    //Check if role specified
    if ($gibbonRoleID == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
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
            //Validate Inputs
            $category = $_POST['category'];
            $name = $_POST['name'];
            $nameShort = $_POST['nameShort'];
            $description = $_POST['description'];
            $futureYearsLogin = $_POST['futureYearsLogin'];
            $pastYearsLogin = $_POST['pastYearsLogin'];

            if ($category == '' or $name == '' or $nameShort == '' or $description == '' or $futureYearsLogin == '' or $pastYearsLogin == '') {
                $URL .= '&return=error3';
                header("Location: {$URL}");
            } else {
                //Check unique inputs for uniquness
                try {
                    $data = array('name' => $name, 'nameShort' => $nameShort, 'gibbonRoleID' => $gibbonRoleID);
                    $sql = 'SELECT * FROM gibbonRole WHERE (name=:name OR nameShort=:nameShort) AND NOT gibbonRoleID=:gibbonRoleID';
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
                        $data = array('category' => $category, 'name' => $name, 'nameShort' => $nameShort, 'description' => $description, 'futureYearsLogin' => $futureYearsLogin, 'pastYearsLogin' => $pastYearsLogin, 'gibbonRoleID' => $gibbonRoleID);
                        $sql = 'UPDATE gibbonRole SET category=:category, name=:name, nameShort=:nameShort, description=:description, futureYearsLogin=:futureYearsLogin, pastYearsLogin=:pastYearsLogin WHERE gibbonRoleID=:gibbonRoleID';
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
