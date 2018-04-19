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

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address']).'/role_manage_add.php';

if (isActionAccessible($guid, $connection2, '/modules/User Admin/role_manage_add.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    //Validate Inputs
    $category = $_POST['category'];
    $name = $_POST['name'];
    $nameShort = $_POST['nameShort'];
    $description = $_POST['description'];
    $futureYearsLogin = $_POST['futureYearsLogin'];
    $pastYearsLogin = $_POST['pastYearsLogin'];
    $restriction = $_POST['restriction'];

    if (empty($category) or empty($name) or empty($nameShort) or empty($description) or empty($futureYearsLogin) or empty($pastYearsLogin) or empty($restriction) ) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        //Check unique inputs for uniqueness
        try {
            $data = array('name' => $name, 'nameShort' => $nameShort);
            $sql = 'SELECT * FROM gibbonRole WHERE name=:name OR nameShort=:nameShort';
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
                $data = array('category' => $category, 'name' => $name, 'nameShort' => $nameShort, 'description' => $description, 'futureYearsLogin' => $futureYearsLogin, 'pastYearsLogin' => $pastYearsLogin, 'restriction' => $restriction);
                $sql = "INSERT INTO gibbonRole SET category=:category, name=:name, nameShort=:nameShort, description=:description, type='Additional', futureYearsLogin=:futureYearsLogin, pastYearsLogin=:pastYearsLogin, restriction=:restriction";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
                exit();
            }

            //Last insert ID
            $AI = str_pad($connection2->lastInsertID(), 3, '0', STR_PAD_LEFT);

            $URL .= "&return=success0&editID=$AI";
            header("Location: {$URL}");
        }
    }
}
