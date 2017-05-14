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

$name = $_POST['name'];
$nameShort = $_POST['nameShort'];
$nameShortDisplay = $_POST['nameShortDisplay'];
$active = $_POST['active'];
$count = $_POST['count'];
$gibbonYearGroupIDList = '';
for ($i = 0; $i < $count; ++$i) {
    if (isset($_POST["gibbonYearGroupIDCheck$i"])) {
        if ($_POST["gibbonYearGroupIDCheck$i"] == 'on') {
            $gibbonYearGroupIDList = $gibbonYearGroupIDList.$_POST["gibbonYearGroupID$i"].',';
        }
    }
}
$gibbonYearGroupIDList = substr($gibbonYearGroupIDList, 0, (strlen($gibbonYearGroupIDList) - 1));
$gibbonSchoolYearID = $_POST['gibbonSchoolYearID'];
$gibbonTTID = $_POST['gibbonTTID'];

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address']).'/tt_edit.php&gibbonTTID='.$gibbonTTID.'&gibbonSchoolYearID='.$_POST['gibbonSchoolYearID'];

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/tt_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    //Check if special day specified
    if ($gibbonTTID == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        try {
            $data = array('gibbonTTID' => $gibbonTTID);
            $sql = 'SELECT * FROM gibbonTT WHERE gibbonTTID=:gibbonTTID';
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
            if ($name == '' or $nameShort == '' or $nameShortDisplay == '' or $gibbonSchoolYearID == '') {
                $URL .= '&return=error3';
                header("Location: {$URL}");
            } else {
                //Check unique inputs for uniquness
                try {
                    $data = array('name' => $name, 'gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonTTID' => $gibbonTTID);
                    $sql = 'SELECT * FROM gibbonTT WHERE (name=:name AND gibbonSchoolYearID=:gibbonSchoolYearID) AND NOT gibbonTTID=:gibbonTTID';
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
                        $data = array('name' => $name, 'nameShort' => $nameShort, 'nameShortDisplay' => $nameShortDisplay, 'active' => $active, 'gibbonYearGroupIDList' => $gibbonYearGroupIDList, 'gibbonTTID' => $gibbonTTID);
                        $sql = 'UPDATE gibbonTT SET name=:name, nameShort=:nameShort, nameShortDisplay=:nameShortDisplay, active=:active, gibbonYearGroupIDList=:gibbonYearGroupIDList WHERE gibbonTTID=:gibbonTTID';
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
