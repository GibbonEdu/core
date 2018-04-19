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

@session_start();

$name = $_POST['name'];
$nameShort = $_POST['nameShort'];
$color = $_POST['color'];
$fontColor = $_POST['fontColor'];
$gibbonSchoolYearID = $_POST['gibbonSchoolYearID'];
$gibbonTTID = $_POST['gibbonTTID'];
$gibbonTTColumnID = $_POST['gibbonTTColumnID'];

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/tt_edit_day_add.php&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonTTID=$gibbonTTID";

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/tt_edit_day_add.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    //Validate Inputs
    if ($gibbonSchoolYearID == '' or $gibbonTTID == '' or $name == '' or $nameShort == '' or $gibbonTTColumnID == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        //Check unique inputs for uniquness
        try {
            $data = array('name' => $name, 'nameShort' => $nameShort, 'gibbonTTID' => $gibbonTTID);
            $sql = 'SELECT * FROM gibbonTTDay WHERE ((name=:name) OR (nameShort=:nameShort)) AND gibbonTTID=:gibbonTTID';
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
                $data = array('gibbonTTID' => $gibbonTTID, 'name' => $name, 'nameShort' => $nameShort, 'color' => $color, 'fontColor' => $fontColor, 'gibbonTTColumnID' => $gibbonTTColumnID);
                $sql = 'INSERT INTO gibbonTTDay SET gibbonTTID=:gibbonTTID, name=:name, nameShort=:nameShort, color=:color, fontColor=:fontColor, gibbonTTColumnID=:gibbonTTColumnID';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
                exit();
            }

            //Last insert ID
            $AI = str_pad($connection2->lastInsertID(), 6, '0', STR_PAD_LEFT);

            $URL .= "&return=success0&editID=$AI";
            header("Location: {$URL}");
        }
    }
}
