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

$gibbonSchoolYearIDCopyTo = null ;
if (isset($_POST['gibbonSchoolYearIDCopyTo']))
    $gibbonSchoolYearIDCopyTo = $_POST['gibbonSchoolYearIDCopyTo'];
$action = $_POST['action'];
$search = $_POST['search'];

if (($gibbonSchoolYearIDCopyTo == '' and $action != 'Delete') or $action == '') { echo 'Fatal error loading this page!';
} else {
    $URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/activities_manage.php&search=$search";

    if (isActionAccessible($guid, $connection2, '/modules/Activities/activities_manage.php') == false) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
    } else {
        $activities = isset($_POST['gibbonActivityID'])? $_POST['gibbonActivityID'] : array();

        //Proceed!
        //Check if person specified
        if (count($activities) < 1) {
            $URL .= '&return=error3';
            header("Location: {$URL}");
        } else {
            $partialFail = false;
            if ($action == 'Duplicate' or $action == 'DuplicateParticipants') {
                foreach ($activities AS $gibbonActivityID) { //For every activity to be copied
                    //Check existence of activity and fetch details
                    try {
                        $data = array('gibbonActivityID' => $gibbonActivityID);
                        $sql = 'SELECT * FROM gibbonActivity WHERE gibbonActivityID=:gibbonActivityID';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $partialFail = true;
                    }

                    if ($result->rowCount() != 1) {
                        $partialFail = true;
                    } else {
                        $row = $result->fetch();
                        $name = $row['name'];
                        if ($gibbonSchoolYearIDCopyTo == $_SESSION[$guid]['gibbonSchoolYearID']) {
                            $name .= ' (Copy)';
                        }

                        //Write the duplicate to the database
                        try {
                            $data = array('gibbonSchoolYearID' => $gibbonSchoolYearIDCopyTo, 'active' => $row['active'], 'registration' => $row['registration'], 'name' => $name, 'provider' => $row['provider'], 'type' => $row['type'], 'gibbonSchoolYearTermIDList' => $row['gibbonSchoolYearTermIDList'], 'listingStart' => $row['listingStart'], 'listingEnd' => $row['listingEnd'], 'programStart' => $row['programStart'], 'programEnd' => $row['programEnd'], 'gibbonYearGroupIDList' => $row['gibbonYearGroupIDList'], 'maxParticipants' => $row['maxParticipants'], 'description' => $row['description'], 'payment' => $row['payment'], 'paymentType' => $row['paymentType'], 'paymentFirmness' => $row['paymentFirmness']);
                            $sql = 'INSERT INTO gibbonActivity SET gibbonSchoolYearID=:gibbonSchoolYearID, active=:active, registration=:registration, name=:name, provider=:provider, type=:type, gibbonSchoolYearTermIDList=:gibbonSchoolYearTermIDList, listingStart=:listingStart, listingEnd=:listingEnd, programStart=:programStart, programEnd=:programEnd, gibbonYearGroupIDList=:gibbonYearGroupIDList, maxParticipants=:maxParticipants, description=:description, payment=:payment, paymentType=:paymentType, paymentFirmness=:paymentFirmness';
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        } catch (PDOException $e) {
                            $partialFail = true;
                        }

                        //Last insert ID
                        $AI = str_pad($connection2->lastInsertID(), 8, '0', STR_PAD_LEFT);

                        //Check and create staff
                        try {
                            $dataParticipants = array('gibbonActivityID' => $gibbonActivityID);
                            $sqlParticipants = 'SELECT * FROM gibbonActivityStaff WHERE gibbonActivityID=:gibbonActivityID';
                            $resultParticipants = $connection2->prepare($sqlParticipants);
                            $resultParticipants->execute($dataParticipants);
                        } catch (PDOException $e) {
                            $partialFail = true;
                        }
                        while ($rowParticipants = $resultParticipants->fetch()) {
                            try {
                                $dataParticipants2 = array('gibbonActivityID' => $AI, 'gibbonPersonID' => $rowParticipants['gibbonPersonID'], 'role' => $rowParticipants['role']);
                                $sqlParticipants2 = 'INSERT INTO gibbonActivityStaff SET gibbonActivityID=:gibbonActivityID, gibbonPersonID=:gibbonPersonID, role=:role';
                                $resultParticipants2 = $connection2->prepare($sqlParticipants2);
                                $resultParticipants2->execute($dataParticipants2);
                            } catch (PDOException $e) {
                                $partialFail = true;
                            }
                        }

                        //Check and create slots
                        try {
                            $dataParticipants = array('gibbonActivityID' => $gibbonActivityID);
                            $sqlParticipants = 'SELECT * FROM gibbonActivitySlot WHERE gibbonActivityID=:gibbonActivityID';
                            $resultParticipants = $connection2->prepare($sqlParticipants);
                            $resultParticipants->execute($dataParticipants);
                        } catch (PDOException $e) {
                            $partialFail = true;
                        }
                        while ($rowParticipants = $resultParticipants->fetch()) {
                            try {
                                $dataParticipants2 = array('gibbonActivityID' => $AI, 'gibbonSpaceID' => $rowParticipants['gibbonSpaceID'], 'locationExternal' => $rowParticipants['locationExternal'], 'gibbonDaysOfWeekID' => $rowParticipants['gibbonDaysOfWeekID'], 'timeStart' => $rowParticipants['timeStart'], 'timeEnd' => $rowParticipants['timeEnd']);
                                $sqlParticipants2 = 'INSERT INTO gibbonActivitySlot SET gibbonActivityID=:gibbonActivityID, gibbonSpaceID=:gibbonSpaceID, locationExternal=:locationExternal, gibbonDaysOfWeekID=:gibbonDaysOfWeekID, timeStart=:timeStart, timeEnd=:timeEnd';
                                $resultParticipants2 = $connection2->prepare($sqlParticipants2);
                                $resultParticipants2->execute($dataParticipants2);
                            } catch (PDOException $e) {
                                $partialFail = true;
                            }
                        }

                        //Deal with participants
                        if ($action == 'DuplicateParticipants') {
                            //Check and create staff
                            try {
                                $dataParticipants = array('gibbonActivityID' => $gibbonActivityID);
                                $sqlParticipants = 'SELECT * FROM gibbonActivityStudent WHERE gibbonActivityID=:gibbonActivityID';
                                $resultParticipants = $connection2->prepare($sqlParticipants);
                                $resultParticipants->execute($dataParticipants);
                            } catch (PDOException $e) {
                                $partialFail = true;
                            }
                            while ($rowParticipants = $resultParticipants->fetch()) {
                                try {
                                    $dataParticipants2 = array('gibbonActivityID' => $AI, 'gibbonPersonID' => $rowParticipants['gibbonPersonID'], 'status' => $rowParticipants['status'], 'timestamp' => $rowParticipants['timestamp']);
                                    $sqlParticipants2 = "INSERT INTO gibbonActivityStudent SET gibbonActivityID=:gibbonActivityID, gibbonPersonID=:gibbonPersonID, status=:status, timestamp=:timestamp, gibbonActivityIDBackup=NULL, invoiceGenerated='N', gibbonFinanceInvoiceID=NULL";
                                    $resultParticipants2 = $connection2->prepare($sqlParticipants2);
                                    $resultParticipants2->execute($dataParticipants2);
                                } catch (PDOException $e) {
                                    $partialFail = true;
                                }
                            }
                        }
                    }
                }
            }
            if ($action == 'Delete') {
                foreach ($activities AS $gibbonActivityID) { //For every activity to be copied
                    //Check existence of activity and fetch details
                    try {
                        $data = array('gibbonActivityID' => $gibbonActivityID);
                        $sql = 'SELECT * FROM gibbonActivity WHERE gibbonActivityID=:gibbonActivityID';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $partialFail = true;
                    }

                    if ($result->rowCount() != 1) {
                        $partialFail = true;
                    } else {
                        try {
                            $data = array('gibbonActivityID' => $gibbonActivityID);
                            $sql = 'DELETE FROM gibbonActivity WHERE gibbonActivityID=:gibbonActivityID';
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        } catch (PDOException $e) {
                            $partialFail = true;
                        }
                    }
                }
            }
            else {
                $URL .= '&return=error3';
                header("Location: {$URL}");
            }

            if ($partialFail == true) {
                $URL .= '&return=warning1';
                header("Location: {$URL}");
            } else {
                $URL .= '&return=success0';
                header("Location: {$URL}");
            }
        }
    }
}
