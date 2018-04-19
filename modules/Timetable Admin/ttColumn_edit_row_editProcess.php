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

$gibbonTTColumnRowID = $_POST['gibbonTTColumnRowID'];
$gibbonTTColumnID = $_POST['gibbonTTColumnID'];

if ($gibbonTTColumnID == '') { echo 'Fatal error loading this page!';
} else {
    $URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/ttColumn_edit_row_edit.php&gibbonTTColumnID=$gibbonTTColumnID&gibbonTTColumnRowID=$gibbonTTColumnRowID";

    if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/ttColumn_edit_row_edit.php') == false) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
    } else {
        //Proceed!
        //Check if tt specified
        if ($gibbonTTColumnRowID == '') {
            $URL .= '&return=error1';
            header("Location: {$URL}");
        } else {
            try {
                $data = array('gibbonTTColumnRowID' => $gibbonTTColumnRowID);
                $sql = 'SELECT * FROM gibbonTTColumnRow WHERE gibbonTTColumnRowID=:gibbonTTColumnRowID';
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
                $timeStart = $_POST['timeStart'];
                $timeEnd = $_POST['timeEnd'];
                $type = $_POST['type'];

                if ($name == '' or $nameShort == '' or $timeStart == '' or $timeEnd == '' or $type == '') {
                    $URL .= '&return=error3';
                    header("Location: {$URL}");
                } else {
                    //Check unique inputs for uniquness
                    try {
                        $data = array('name' => $name, 'nameShort' => $nameShort, 'gibbonTTColumnID' => $gibbonTTColumnID, 'gibbonTTColumnRowID' => $gibbonTTColumnRowID);
                        $sql = 'SELECT * FROM gibbonTTColumnRow WHERE (name=:name OR nameShort=:nameShort) AND gibbonTTColumnID=:gibbonTTColumnID AND NOT gibbonTTColumnRowID=:gibbonTTColumnRowID';
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
                            $data = array('name' => $name, 'nameShort' => $nameShort, 'timeStart' => $timeStart, 'timeEnd' => $timeEnd, 'type' => $type, 'gibbonTTColumnRowID' => $gibbonTTColumnRowID);
                            $sql = 'UPDATE gibbonTTColumnRow SET name=:name, nameShort=:nameShort, timeStart=:timeStart, timeEnd=:timeEnd, type=:type WHERE gibbonTTColumnRowID=:gibbonTTColumnRowID';
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
}
