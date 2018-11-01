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

$date = $_POST['date'];
$type = $_POST['type'];
$name = $_POST['name'];
$description = $_POST['description'];
$gibbonSchoolYearID = $_POST['gibbonSchoolYearID'];
$dateStamp = $_POST['dateStamp'];
$gibbonSchoolYearTermID = $_POST['gibbonSchoolYearTermID'];
$firstDay = $_POST['firstDay'];
$lastDay = $_POST['lastDay'];
$schoolOpen = null;
if (!empty($_POST['schoolOpenH']) && is_numeric($_POST['schoolOpenH']) && is_numeric($_POST['schoolOpenM'])) {
    $schoolOpen = $_POST['schoolOpenH'].':'.$_POST['schoolOpenM'].':00';
}
$schoolStart = null;
if (!empty($_POST['schoolStartH']) && is_numeric($_POST['schoolStartH']) && is_numeric($_POST['schoolStartM'])) {
    $schoolStart = $_POST['schoolStartH'].':'.$_POST['schoolStartM'].':00';
}
$schoolEnd = null;
if (!empty($_POST['schoolEndH']) && is_numeric($_POST['schoolEndH']) && is_numeric($_POST['schoolEndM'])) {
    $schoolEnd = $_POST['schoolEndH'].':'.$_POST['schoolEndM'].':00';
}
$schoolClose = null;
if (!empty($_POST['schoolCloseH']) && is_numeric($_POST['schoolCloseH']) && is_numeric($_POST['schoolCloseM'])) {
    $schoolClose = $_POST['schoolCloseH'].':'.$_POST['schoolCloseM'].':00';
}

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/schoolYearSpecialDay_manage.php&gibbonSchoolYearID=$gibbonSchoolYearID";

if (isActionAccessible($guid, $connection2, '/modules/School Admin/schoolYearSpecialDay_manage_add.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    //Validate Inputs
    if ($date == '' or $type == '' or $name == '' or $gibbonSchoolYearID == '' or $dateStamp == '' or $gibbonSchoolYearTermID == '' or $firstDay == '' or $lastDay == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        //Lock table
        try {
            $sql = 'LOCK TABLE gibbonSchoolYearSpecialDay WRITE';
            $result = $connection2->query($sql);
        } catch (PDOException $e) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
            exit();
        }

        //Check unique inputs for uniquness
        try {
            $data = array('date' => dateConvert($guid, $date));
            $sql = 'SELECT * FROM gibbonSchoolYearSpecialDay WHERE date=:date';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
            exit();
        }

        if ($dateStamp < $firstDay or $dateStamp > $lastDay) {
            $URL .= '&return=warning1';
            header("Location: {$URL}");
        } else {
            if ($result->rowCount() > 0) {
                $URL .= '&return=error3';
                header("Location: {$URL}");
            } else {
                //Write to database
                try {
                    $data = array('gibbonSchoolYearTermID' => $gibbonSchoolYearTermID, 'date' => dateConvert($guid, $date), 'type' => $type, 'name' => $name, 'description' => $description, 'schoolOpen' => $schoolOpen, 'schoolStart' => $schoolStart, 'schoolEnd' => $schoolEnd, 'schoolClose' => $schoolClose);
                    $sql = 'INSERT INTO gibbonSchoolYearSpecialDay SET gibbonSchoolYearTermID=:gibbonSchoolYearTermID, date=:date, type=:type, name=:name, description=:description,schoolOpen=:schoolOpen, schoolStart=:schoolStart, schoolEnd=:schoolEnd, schoolClose=:schoolClose';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                    exit();
                }

                //Unlock locked database tables
                try {
                    $sql = 'UNLOCK TABLES';
                    $result = $connection2->query($sql);
                } catch (PDOException $e) {
                }

                $URL .= '&return=success0';
                header("Location: {$URL}");
            }
        }
    }
}
