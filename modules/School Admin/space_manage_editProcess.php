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

$gibbonSpaceID = $_GET['gibbonSpaceID'] ?? '';
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/space_manage_edit.php&gibbonSpaceID=$gibbonSpaceID";

if (isActionAccessible($guid, $connection2, '/modules/School Admin/space_manage_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    //Check if school year specified
    if ($gibbonSpaceID == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        try {
            $data = array('gibbonSpaceID' => $gibbonSpaceID);
            $sql = 'SELECT * FROM gibbonSpace WHERE gibbonSpaceID=:gibbonSpaceID';
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
            $name = $_POST['name'] ?? '';
            $type = $_POST['type'] ?? '';
            $capacity = $_POST['capacity'] ?? '';
            $computer = $_POST['computer'] ?? '';
            $computerStudent = $_POST['computerStudent'] ?? '';
            $projector = $_POST['projector'] ?? '';
            $tv = $_POST['tv'] ?? '';
            $dvd = $_POST['dvd'] ?? '';
            $hifi = $_POST['hifi'] ?? '';
            $speakers = $_POST['speakers'] ?? '';
            $iwb = $_POST['iwb'] ?? '';
            $phoneInternal = $_POST['phoneInternal'] ?? '';
            $phoneExternal = preg_replace('/[^0-9+]/', '', $_POST['phoneExternal'] ?? '');
            $comment = $_POST['comment'] ?? '';

            //Validate Inputs
            if ($name == '' or $type == '' or $computer == '' or $computerStudent == '' or $projector == '' or $tv == '' or $dvd == '' or $hifi == '' or $speakers == '' or $iwb == '') {
                $URL .= '&return=error3';
                header("Location: {$URL}");
            } else {
                //Check unique inputs for uniquness
                try {
                    $data = array('name' => $name, 'gibbonSpaceID' => $gibbonSpaceID);
                    $sql = 'SELECT * FROM gibbonSpace WHERE name=:name AND NOT gibbonSpaceID=:gibbonSpaceID';
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
                        $data = array('name' => $name, 'type' => $type, 'capacity' => $capacity, 'computer' => $computer, 'computerStudent' => $computerStudent, 'projector' => $projector, 'tv' => $tv, 'dvd' => $dvd, 'hifi' => $hifi, 'speakers' => $speakers, 'iwb' => $iwb, 'phoneInternal' => $phoneInternal, 'phoneExternal' => $phoneExternal, 'comment' => $comment, 'gibbonSpaceID' => $gibbonSpaceID);
                        $sql = 'UPDATE gibbonSpace SET name=:name, type=:type, capacity=:capacity, computer=:computer, computerStudent=:computerStudent, projector=:projector, tv=:tv, dvd=:dvd , hifi=:hifi, speakers=:speakers, iwb=:iwb, phoneInternal=:phoneInternal, phoneExternal=:phoneExternal, comment=:comment WHERE gibbonSpaceID=:gibbonSpaceID';
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
