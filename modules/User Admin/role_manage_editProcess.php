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
            $values = $result->fetch();

            //Validate Inputs
            $category = $_POST['category'];
            $name = $_POST['name'];
            $nameShort = $_POST['nameShort'];
            $description = $_POST['description'];
            $canLoginRole = isset($_POST['canLoginRole'])? $_POST['canLoginRole'] : 'Y';
            $futureYearsLogin = isset($_POST['futureYearsLogin'])? $_POST['futureYearsLogin'] : $values['futureYearsLogin'];
            $pastYearsLogin = isset($_POST['pastYearsLogin'])? $_POST['pastYearsLogin'] : $values['pastYearsLogin'];
            $restriction = $_POST['restriction'];

            if (empty($category) or empty($name) or empty($nameShort) or empty($description) or empty($futureYearsLogin) or empty($pastYearsLogin) or empty($restriction) ) {
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
                        $data = array('category' => $category, 'name' => $name, 'nameShort' => $nameShort, 'description' => $description, 'canLoginRole' => $canLoginRole, 'futureYearsLogin' => $futureYearsLogin, 'pastYearsLogin' => $pastYearsLogin, 'restriction' => $restriction, 'gibbonRoleID' => $gibbonRoleID);
                        $sql = 'UPDATE gibbonRole SET category=:category, name=:name, nameShort=:nameShort, description=:description, canLoginRole=:canLoginRole, futureYearsLogin=:futureYearsLogin, pastYearsLogin=:pastYearsLogin, restriction=:restriction WHERE gibbonRoleID=:gibbonRoleID';
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
