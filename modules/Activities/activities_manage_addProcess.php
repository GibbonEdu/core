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

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address']).'/activities_manage_add.php&search='.$_GET['search'].'&gibbonSchoolYearTermID='.$_GET['gibbonSchoolYearTermID'];

if (isActionAccessible($guid, $connection2, '/modules/Activities/activities_manage_add.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    $name = $_POST['name'];
    $provider = $_POST['provider'];
    $active = $_POST['active'];
    $registration = $_POST['registration'];
    $dateType = $_POST['dateType'];
    if ($dateType == 'Term') {
        $gibbonSchoolYearTermIDList = isset($_POST['gibbonSchoolYearTermIDList'])? $_POST['gibbonSchoolYearTermIDList'] : array();
        $gibbonSchoolYearTermIDList = implode(',', $gibbonSchoolYearTermIDList);
    } elseif ($dateType == 'Date') {
        $listingStart = dateConvert($guid, $_POST['listingStart']);
        $listingEnd = dateConvert($guid, $_POST['listingEnd']);
        $programStart = dateConvert($guid, $_POST['programStart']);
        $programEnd = dateConvert($guid, $_POST['programEnd']);
    }
    $gibbonYearGroupIDList = isset($_POST['gibbonYearGroupIDList'])? $_POST['gibbonYearGroupIDList'] : array();
    $gibbonYearGroupIDList = implode(',', $gibbonYearGroupIDList);

    $maxParticipants = $_POST['maxParticipants'];
    if (getSettingByScope($connection2, 'Activities', 'payment') == 'None' or getSettingByScope($connection2, 'Activities', 'payment') == 'Single') {
        $paymentOn = false;
        $payment = null;
        $paymentType = null;
        $paymentFirmness = null;
    } else {
        $paymentOn = true;
        $payment = $_POST['payment'];
        $paymentType = $_POST['paymentType'];
        $paymentFirmness = $_POST['paymentFirmness'];
    }
    $description = $_POST['description'];

    if ($dateType == '' or $name == '' or $provider == '' or $active == '' or $registration == '' or $maxParticipants == '' or ($paymentOn and ($payment == '' or $paymentType == '' or $paymentFirmness == '')) or ($dateType == 'Date' and ($listingStart == '' or $listingEnd == '' or $programStart == '' or $programEnd == ''))) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        //Write to database
        $type = '';
        if (isset($_POST['type'])) {
            $type = $_POST['type'];
        }

        try {
            if ($dateType == 'Date') {
                $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'name' => $name, 'provider' => $provider, 'type' => $type, 'active' => $active, 'registration' => $registration, 'listingStart' => $listingStart, 'listingEnd' => $listingEnd, 'programStart' => $programStart, 'programEnd' => $programEnd, 'gibbonYearGroupIDList' => $gibbonYearGroupIDList, 'maxParticipants' => $maxParticipants, 'payment' => $payment, 'paymentType' => $paymentType, 'paymentFirmness' => $paymentFirmness, 'description' => $description);
                $sql = "INSERT INTO gibbonActivity SET gibbonSchoolYearID=:gibbonSchoolYearID, name=:name, provider=:provider, type=:type, active=:active, registration=:registration, gibbonSchoolYearTermIDList='', listingStart=:listingStart, listingEnd=:listingEnd, programStart=:programStart, programEnd=:programEnd, gibbonYearGroupIDList=:gibbonYearGroupIDList, maxParticipants=:maxParticipants, payment=:payment, paymentType=:paymentType, paymentFirmness=:paymentFirmness, description=:description";
            } else {
                $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'name' => $name, 'provider' => $provider, 'type' => $type, 'active' => $active, 'registration' => $registration, 'gibbonSchoolYearTermIDList' => $gibbonSchoolYearTermIDList, 'gibbonYearGroupIDList' => $gibbonYearGroupIDList, 'maxParticipants' => $maxParticipants, 'payment' => $payment, 'paymentType' => $paymentType, 'paymentFirmness' => $paymentFirmness, 'description' => $description);
                $sql = 'INSERT INTO gibbonActivity SET gibbonSchoolYearID=:gibbonSchoolYearID, name=:name, provider=:provider, type=:type, active=:active, registration=:registration, gibbonSchoolYearTermIDList=:gibbonSchoolYearTermIDList, listingStart=NULL, listingEnd=NULL, programStart=NULL, programEnd=NULL, gibbonYearGroupIDList=:gibbonYearGroupIDList, maxParticipants=:maxParticipants, payment=:payment, paymentType=:paymentType, paymentFirmness=:paymentFirmness, description=:description';
            }
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
            exit();
        }

        //Last insert ID
        $AI = str_pad($connection2->lastInsertID(), 14, '0', STR_PAD_LEFT);

        //Scan through slots
        $partialFail = false;
        for ($i = 1; $i < 3; ++$i) {
            $gibbonDaysOfWeekID = $_POST["gibbonDaysOfWeekID$i"];
            $timeStart = $_POST["timeStart$i"];
            $timeEnd = $_POST["timeEnd$i"];
            $type = 'Internal';
            if (isset($_POST['slot'.$i.'Location'])) {
                $type = $_POST['slot'.$i.'Location'];
            }
            $gibbonSpaceID = null;
            if ($type == 'Internal') {
                $gibbonSpaceID = isset($_POST["gibbonSpaceID$i"])? $_POST["gibbonSpaceID$i"] : null;
                $locationExternal = '';
            } else {
                $locationExternal = $_POST['location'.$i.'External'];
            }

            if ($gibbonDaysOfWeekID != '' and $timeStart != '' and $timeEnd != '') {
                try {
                    $data = array('AI' => $AI, 'gibbonDaysOfWeekID' => $gibbonDaysOfWeekID, 'timeStart' => $timeStart, 'timeEnd' => $timeEnd, 'gibbonSpaceID' => $gibbonSpaceID, 'locationExternal' => $locationExternal);
                    $sql = 'INSERT INTO gibbonActivitySlot SET gibbonActivityID=:AI, gibbonDaysOfWeekID=:gibbonDaysOfWeekID, timeStart=:timeStart, timeEnd=:timeEnd, gibbonSpaceID=:gibbonSpaceID, locationExternal=:locationExternal';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $partialFail = true;
                }
            }
        }

        //Scan through staff
        $staff = isset($_POST['staff'])? $_POST['staff'] : null;
        $role = isset($_POST['role']) ? $_POST['role'] : 'Other';

        if (count($staff) > 0) {
            foreach ($staff as $t) {
                //Check to see if person is already registered in this activity
                try {
                    $dataGuest = array('gibbonPersonID' => $t, 'gibbonActivityID' => $AI);
                    $sqlGuest = 'SELECT * FROM gibbonActivityStaff WHERE gibbonPersonID=:gibbonPersonID AND gibbonActivityID=:gibbonActivityID';
                    $resultGuest = $connection2->prepare($sqlGuest);
                    $resultGuest->execute($dataGuest);
                } catch (PDOException $e) {
                    $partialFail = true;
                }

                if ($resultGuest->rowCount() == 0) {
                    try {
                        $data = array('gibbonPersonID' => $t, 'gibbonActivityID' => $AI, 'role' => $role);
                        $sql = 'INSERT INTO gibbonActivityStaff SET gibbonPersonID=:gibbonPersonID, gibbonActivityID=:gibbonActivityID, role=:role';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        echo "here<div class='error'>".$e->getMessage().'</div>';
                        $partialFail = true;
                    }
                }
            }
        }

        if ($partialFail == true) {
            $URL .= '&return=warning1';
            header("Location: {$URL}");
        } else {
            $URL .= "&return=success0&editID=$AI";
            header("Location: {$URL}");
        }
    }
}
