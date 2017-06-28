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

$gibbonTTDayID = $_GET['gibbonTTDayID'];
$gibbonTTID = $_GET['gibbonTTID'];
$gibbonSchoolYearID = $_GET['gibbonSchoolYearID'];

if ($gibbonTTID == '' or $gibbonSchoolYearID == '') { echo 'Fatal error loading this page!';
} else {
    $URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/tt_edit_day_edit.php&gibbonTTID=$gibbonTTID&gibbonTTDayID=$gibbonTTDayID&gibbonSchoolYearID=$gibbonSchoolYearID";

    if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/tt_edit_day_edit.php') == false) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
    } else {
        //Proceed!
        //Check if tt specified
        if ($gibbonTTDayID == '') {
            $URL .= '&return=error1';
            header("Location: {$URL}");
        } else {
            try {
                $data = array('gibbonTTDayID' => $gibbonTTDayID);
                $sql = 'SELECT * FROM gibbonTTDay WHERE gibbonTTDayID=:gibbonTTDayID';
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
                $color = $_POST['color'];
                $fontColor = $_POST['fontColor'];
                $gibbonTTColumnID = $_POST['gibbonTTColumnID'];

                if ($name == '' or $nameShort == '' or $gibbonTTColumnID == '') {
                    $URL .= '&return=error3';
                    header("Location: {$URL}");
                } else {
                    //Check unique inputs for uniquness
                    try {
                        $data = array('name' => $name, 'nameShort' => $nameShort, 'gibbonTTID' => $gibbonTTID, 'gibbonTTDayID' => $gibbonTTDayID);
                        $sql = 'SELECT * FROM gibbonTTDay WHERE ((name=:name) OR (nameShort=:nameShort)) AND gibbonTTID=:gibbonTTID AND NOT gibbonTTDayID=:gibbonTTDayID';
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
                            $data = array('name' => $name, 'nameShort' => $nameShort, 'color' => $color, 'fontColor' => $fontColor, 'gibbonTTColumnID' => $gibbonTTColumnID, 'gibbonTTDayID' => $gibbonTTDayID);
                            $sql = 'UPDATE gibbonTTDay SET name=:name, nameShort=:nameShort, color=:color, fontColor=:fontColor, gibbonTTColumnID=:gibbonTTColumnID WHERE gibbonTTDayID=:gibbonTTDayID';
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
