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
include './functions.php';
include './config.php';

@session_start();

$gibbonPersonID = $_GET['gibbonPersonID'];
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php';

//Proceed!
//Check if planner specified
if ($gibbonPersonID == '' or $gibbonPersonID != $_SESSION[$guid]['gibbonPersonID']) {
    $URL .= '?return=error1';
    header("Location: {$URL}");
} else {
    try {
        $data = array('gibbonPersonID' => $gibbonPersonID);
        $sql = 'SELECT * FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID';
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $URL .= '?return=error2';
        header("Location: {$URL}");
        exit();
    }

    if ($result->rowCount() != 1) {
        $URL .= '?return=error2';
        header("Location: {$URL}");
    } else {
        //UPDATE
        try {
            $data = array('gibbonPersonID' => $gibbonPersonID);
            $sql = "UPDATE gibbonPerson SET image_240='' WHERE gibbonPersonID=:gibbonPersonID";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $URL .= '?return=error2';
            header("Location: {$URL}");
            exit();
        }

        //Update session variables
        $_SESSION[$guid]['image_240'] = '';

        //Clear cusotm sidebar
        unset($_SESSION[$guid]['index_customSidebar.php']);

        $URL .= '?return=success0';
        //Success 0
        header("Location: {$URL}");
    }
}
