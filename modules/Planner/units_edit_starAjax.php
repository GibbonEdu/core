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

include './moduleFunctions.php';

//New PDO DB connection
$pdo = new Gibbon\sqlConnection();
$connection2 = $pdo->getConnection();

@session_start();

$gibbonPersonID = $_POST['gibbonPersonID'];
$action = $_POST['action'];
$gibbonUnitBlockID = $_POST['gibbonUnitBlockID'];
$i = $_POST['i'];

if ($gibbonPersonID == '' or $action == '' or $gibbonUnitBlockID == '' or $i == '') { echo __($guid, 'Error');
} else {
    if ($action == 'star') {
        //Write to database
        try {
            $data = array('gibbonPersonID' => $gibbonPersonID, 'gibbonUnitBlockID' => $gibbonUnitBlockID);
            $sql = 'INSERT INTO gibbonUnitBlockStar SET gibbonPersonID=:gibbonPersonID, gibbonUnitBlockID=:gibbonUnitBlockID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
        }
        echo "<img id='unstar$i' src='".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/like_on.png'>";
    } else {
        //Delete FROM to database
        try {
            $data = array('gibbonPersonID' => $gibbonPersonID, 'gibbonUnitBlockID' => $gibbonUnitBlockID);
            $sql = 'DELETE FROM gibbonUnitBlockStar WHERE gibbonPersonID=:gibbonPersonID AND gibbonUnitBlockID=:gibbonUnitBlockID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
        }

        echo "<img id='star$i' src='".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/like_off.png'>";
    }
}
