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

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address']).'/firstAidRecord_add.php&gibbonRollGroupID='.$_GET['gibbonRollGroupID'].'&gibbonYearGroupID='.$_GET['gibbonYearGroupID'];

if (isActionAccessible($guid, $connection2, '/modules/Students/firstAidRecord_add.php') == false) {
    $URL .= '&return=error0&step=1';
    header("Location: {$URL}");
} else {
    $gibbonFirstAidID = null;
    if (isset($_POST['gibbonFirstAidID'])) {
        $gibbonFirstAidID = $_POST['gibbonFirstAidID'];
    }

    //Proceed!
    $gibbonPersonID = $_POST['gibbonPersonID'];
    $gibbonPersonIDFirstAider = $_SESSION[$guid]['gibbonPersonID'];
    $date = $_POST['date'];
    $timeIn = $_POST['timeIn'];
    $description = $_POST['description'];
    $actionTaken = $_POST['actionTaken'];
    $followUp = $_POST['followUp'];

    if ($gibbonPersonID == '' or $gibbonPersonIDFirstAider == '' or $date == '' or $timeIn == '') {
        $URL .= '&return=error1&step=1';
        header("Location: {$URL}");
    } else {
        //Write to database
        try {
            $data = array('gibbonPersonIDPatient' => $gibbonPersonID, 'gibbonPersonIDFirstAider' => $gibbonPersonIDFirstAider, 'date' => dateConvert($guid, $date), 'timeIn' => $timeIn, 'description' => $description, 'actionTaken' => $actionTaken, 'followUp' => $followUp, 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
            $sql = 'INSERT INTO gibbonFirstAid SET gibbonPersonIDPatient=:gibbonPersonIDPatient, gibbonPersonIDFirstAider=:gibbonPersonIDFirstAider, date=:date, timeIn=:timeIn, description=:description, actionTaken=:actionTaken, followUp=:followUp, gibbonSchoolYearID=:gibbonSchoolYearID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $URL .= '&return=erorr2&step=1';
            header("Location: {$URL}");
            exit();
        }

        //Last insert ID
        $AI = str_pad($connection2->lastInsertID(), 12, '0', STR_PAD_LEFT);

        $URL .= "&return=success0&editID=$AI";
        header("Location: {$URL}");
    }
}
