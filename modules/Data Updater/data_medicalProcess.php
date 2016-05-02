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

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]['timezone']);

$gibbonPersonID = $_GET['gibbonPersonID'];
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/data_medical.php&gibbonPersonID=$gibbonPersonID";

if (isActionAccessible($guid, $connection2, '/modules/Data Updater/data_medical.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    $highestAction = getHighestGroupedAction($guid, $_POST['address'], $connection2);
    if ($highestAction == false) {
        $URL .= "&return=error0$params";
        header("Location: {$URL}");
    } else {
        //Proceed!
        //Check if school year specified
        if ($gibbonPersonID == '') {
            $URL .= '&return=error1';
            header("Location: {$URL}");
        } else {
            //Check access to person
            $checkCount = 0;
            if ($highestAction == 'Update Medical Data_any') {
                try {
                    $dataSelect = array();
                    $sqlSelect = "SELECT surname, preferredName, gibbonPerson.gibbonPersonID FROM gibbonPerson WHERE status='Full' ORDER BY surname, preferredName";
                    $resultSelect = $connection2->prepare($sqlSelect);
                    $resultSelect->execute($dataSelect);
                } catch (PDOException $e) {
                    $URL .= "&return=error2$params";
                    header("Location: {$URL}");
                    exit();
                }
                $checkCount = $resultSelect->rowCount();
            } else {
                try {
                    $dataCheck = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                    $sqlCheck = "SELECT gibbonFamilyAdult.gibbonFamilyID, name FROM gibbonFamilyAdult JOIN gibbonFamily ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) WHERE gibbonPersonID=:gibbonPersonID AND childDataAccess='Y' ORDER BY name";
                    $resultCheck = $connection2->prepare($sqlCheck);
                    $resultCheck->execute($dataCheck);
                } catch (PDOException $e) {
                    $URL .= "&return=error2$params";
                    header("Location: {$URL}");
                    exit();
                }
                while ($rowCheck = $resultCheck->fetch()) {
                    try {
                        $dataCheck2 = array('gibbonFamilyID' => $rowCheck['gibbonFamilyID'], 'gibbonFamilyID2' => $rowCheck['gibbonFamilyID']);
                        $sqlCheck2 = '(SELECT surname, preferredName, gibbonPerson.gibbonPersonID, gibbonFamilyID FROM gibbonFamilyChild JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonFamilyID=:gibbonFamilyID) UNION (SELECT surname, preferredName, gibbonPerson.gibbonPersonID, gibbonFamilyID FROM gibbonFamilyAdult JOIN gibbonPerson ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonFamilyID=:gibbonFamilyID2)';
                        $resultCheck2 = $connection2->prepare($sqlCheck2);
                        $resultCheck2->execute($dataCheck2);
                    } catch (PDOException $e) {
                        $URL .= "&return=error2$params";
                        header("Location: {$URL}");
                        exit();
                    }
                    while ($rowCheck2 = $resultCheck2->fetch()) {
                        if ($gibbonPersonID == $rowCheck2['gibbonPersonID']) {
                            ++$checkCount;
                        }
                    }
                }
            }
            if ($checkCount < 1) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
            } else {
                $existing = $_POST['existing'];
                if ($existing != 'N') {
                    $AI = $existing;
                } else {
                    //Lock table
                    try {
                        $sqlLock = 'LOCK TABLES gibbonPersonMedicalUpdate WRITE, gibbonPersonMedicalConditionUpdate WRITE, gibbonNotification WRITE, gibbonModule WRITE, gibbonPerson WRITE';
                        $resultLock = $connection2->query($sqlLock);
                    } catch (PDOException $e) {
                        $URL .= '&return=error2';
                        header("Location: {$URL}");
                        exit();
                    }

                    //Get next autoincrement
                    try {
                        $sqlAI = "SHOW TABLE STATUS LIKE 'gibbonPersonMedicalUpdate'";
                        $resultAI = $connection2->query($sqlAI);
                    } catch (PDOException $e) {
                        $URL .= '&return=error2';
                        header("Location: {$URL}");
                        exit();
                    }

                    $rowAI = $resultAI->fetch();
                    $AI = str_pad($rowAI['Auto_increment'], 12, '0', STR_PAD_LEFT);
                }

                //Get medical form fields
                //Proceed!
                if ($_POST['gibbonPersonMedicalID'] != '') {
                    $gibbonPersonMedicalID = $_POST['gibbonPersonMedicalID'];
                } else {
                    $gibbonPersonMedicalID = null;
                }

                $bloodType = $_POST['bloodType'];
                $longTermMedication = $_POST['longTermMedication'];
                $longTermMedicationDetails = $_POST['longTermMedicationDetails'];
                $tetanusWithin10Years = $_POST['tetanusWithin10Years'];

                //Update existing medical conditions
                $partialFail = false;
                $count = 0;
                if (isset($_POST['count'])) {
                    $count = $_POST['count'];
                }

                if ($existing != 'N') {
                    for ($i = 0; $i < $count; ++$i) {
                        if ($AI != '') {
                            $gibbonPersonMedicalUpdateID = $AI;
                        } else {
                            $gibbonPersonMedicalUpdateID = null;
                        }
                        $gibbonPersonMedicalConditionID = $_POST["gibbonPersonMedicalConditionID$i"];
                        $gibbonPersonMedicalConditionUpdateID = $_POST["gibbonPersonMedicalConditionUpdateID$i"];
                        $name = $_POST["name$i"];
                        $gibbonAlertLevelID = $_POST["gibbonAlertLevelID$i"];
                        $triggers = $_POST["triggers$i"];
                        $reaction = $_POST["reaction$i"];
                        $response = $_POST["response$i"];
                        $medication = $_POST["medication$i"];
                        if ($_POST["lastEpisode$i"] != '') {
                            $lastEpisode = dateConvert($guid, $_POST["lastEpisode$i"]);
                        } else {
                            $lastEpisode = null;
                        }
                        $lastEpisodeTreatment = $_POST["lastEpisodeTreatment$i"];
                        $comment = $_POST["comment$i"];

                        try {
                            $data = array('gibbonPersonMedicalUpdateID' => $gibbonPersonMedicalUpdateID, 'gibbonPersonMedicalID' => $gibbonPersonMedicalID, 'name' => $name, 'gibbonAlertLevelID' => $gibbonAlertLevelID, 'triggers' => $triggers, 'reaction' => $reaction, 'response' => $response, 'medication' => $medication, 'lastEpisode' => $lastEpisode, 'lastEpisodeTreatment' => $lastEpisodeTreatment, 'comment' => $comment, 'gibbonPersonIDUpdater' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonPersonMedicalConditionUpdateID' => $gibbonPersonMedicalConditionUpdateID);
                            $sql = 'UPDATE gibbonPersonMedicalConditionUpdate SET gibbonPersonMedicalUpdateID=:gibbonPersonMedicalUpdateID, gibbonPersonMedicalID=:gibbonPersonMedicalID, name=:name, gibbonAlertLevelID=:gibbonAlertLevelID, triggers=:triggers, reaction=:reaction, response=:response, medication=:medication, lastEpisode=:lastEpisode, lastEpisodeTreatment=:lastEpisodeTreatment, comment=:comment, gibbonPersonIDUpdater=:gibbonPersonIDUpdater WHERE gibbonPersonMedicalConditionUpdateID=:gibbonPersonMedicalConditionUpdateID';
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        } catch (PDOException $e) {
                            $partialFail = true;
                        }
                    }
                } else {
                    for ($i = 0; $i < $count; ++$i) {
                        if ($AI != '') {
                            $gibbonPersonMedicalUpdateID = $AI;
                        } else {
                            $gibbonPersonMedicalUpdateID = null;
                        }
                        $gibbonPersonMedicalConditionID = $_POST["gibbonPersonMedicalConditionID$i"];
                        $name = $_POST["name$i"];
                        $gibbonAlertLevelID = $_POST["gibbonAlertLevelID$i"];
                        $triggers = $_POST["triggers$i"];
                        $reaction = $_POST["reaction$i"];
                        $response = $_POST["response$i"];
                        $medication = $_POST["medication$i"];
                        if ($_POST["lastEpisode$i"] != '') {
                            $lastEpisode = dateConvert($guid, $_POST["lastEpisode$i"]);
                        } else {
                            $lastEpisode = null;
                        }
                        $lastEpisodeTreatment = $_POST["lastEpisodeTreatment$i"];
                        $comment = $_POST["comment$i"];

                        try {
                            $data = array('gibbonPersonMedicalUpdateID' => $gibbonPersonMedicalUpdateID, 'gibbonPersonMedicalConditionID' => $gibbonPersonMedicalConditionID, 'gibbonPersonMedicalID' => $gibbonPersonMedicalID, 'name' => $name, 'gibbonAlertLevelID' => $gibbonAlertLevelID, 'triggers' => $triggers, 'reaction' => $reaction, 'response' => $response, 'medication' => $medication, 'lastEpisode' => $lastEpisode, 'lastEpisodeTreatment' => $lastEpisodeTreatment, 'comment' => $comment, 'gibbonPersonIDUpdater' => $_SESSION[$guid]['gibbonPersonID']);
                            $sql = 'INSERT INTO gibbonPersonMedicalConditionUpdate SET gibbonPersonMedicalUpdateID=:gibbonPersonMedicalUpdateID, gibbonPersonMedicalConditionID=:gibbonPersonMedicalConditionID, gibbonPersonMedicalID=:gibbonPersonMedicalID, name=:name, gibbonAlertLevelID=:gibbonAlertLevelID, triggers=:triggers, reaction=:reaction, response=:response, medication=:medication, lastEpisode=:lastEpisode, lastEpisodeTreatment=:lastEpisodeTreatment, comment=:comment, gibbonPersonIDUpdater=:gibbonPersonIDUpdater';
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        } catch (PDOException $e) {
                            $partialFail = true;
                        }
                    }
                }

                //Add new medical condition
                if (isset($_POST['addCondition'])) {
                    if ($_POST['addCondition'] == 'Yes') {
                        if ($_POST['name'] != '' and $_POST['gibbonAlertLevelID'] != '') {
                            if ($AI != '') {
                                $gibbonPersonMedicalUpdateID = $AI;
                            } else {
                                $gibbonPersonMedicalUpdateID = null;
                            }
                            $name = $_POST['name'];
                            $gibbonAlertLevelID = null;
                            if ($_POST['gibbonAlertLevelID'] != 'Please select...') {
                                $gibbonAlertLevelID = $_POST['gibbonAlertLevelID'];
                            }
                            $triggers = $_POST['triggers'];
                            $reaction = $_POST['reaction'];
                            $response = $_POST['response'];
                            $medication = $_POST['medication'];
                            if ($_POST['lastEpisode'] != '') {
                                $lastEpisode = dateConvert($guid, $_POST['lastEpisode']);
                            } else {
                                $lastEpisode = null;
                            }
                            $lastEpisodeTreatment = $_POST['lastEpisodeTreatment'];
                            $comment = $_POST['comment'];

                            try {
                                $data = array('gibbonPersonMedicalUpdateID' => $gibbonPersonMedicalUpdateID, 'gibbonPersonMedicalID' => $gibbonPersonMedicalID, 'name' => $name, 'gibbonAlertLevelID' => $gibbonAlertLevelID, 'triggers' => $triggers, 'reaction' => $reaction, 'response' => $response, 'medication' => $medication, 'lastEpisode' => $lastEpisode, 'lastEpisodeTreatment' => $lastEpisodeTreatment, 'comment' => $comment, 'gibbonPersonIDUpdater' => $_SESSION[$guid]['gibbonPersonID']);
                                $sql = 'INSERT INTO gibbonPersonMedicalConditionUpdate SET gibbonPersonMedicalUpdateID=:gibbonPersonMedicalUpdateID, gibbonPersonMedicalID=:gibbonPersonMedicalID, name=:name, gibbonAlertLevelID=:gibbonAlertLevelID, triggers=:triggers, reaction=:reaction, response=:response, medication=:medication, lastEpisode=:lastEpisode, lastEpisodeTreatment=:lastEpisodeTreatment, comment=:comment, gibbonPersonIDUpdater=:gibbonPersonIDUpdater';
                                $result = $connection2->prepare($sql);
                                $result->execute($data);
                            } catch (PDOException $e) {
                                $partialFail = true;
                            }
                        }
                    }
                }

                //Attempt to notify to DBA
                if ($_SESSION[$guid]['organisationDBA'] != '') {
                    $notificationText = sprintf(__($guid, 'A medical data update request has been submitted.'));
                    setNotification($connection2, $guid, $_SESSION[$guid]['organisationDBA'], $notificationText, 'Data Updater', '/index.php?q=/modules/User Admin/data_medical.php');
                }

                //Write to database
                try {
                    if ($existing != 'N') {
                        $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonMedicalID' => $gibbonPersonMedicalID, 'gibbonPersonID' => $gibbonPersonID, 'bloodType' => $bloodType, 'longTermMedication' => $longTermMedication, 'longTermMedicationDetails' => $longTermMedicationDetails, 'tetanusWithin10Years' => $tetanusWithin10Years, 'gibbonPersonIDUpdater' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonPersonMedicalUpdateID' => $existing);
                        $sql = 'UPDATE gibbonPersonMedicalUpdate SET gibbonSchoolYearID=:gibbonSchoolYearID, gibbonPersonMedicalID=:gibbonPersonMedicalID, gibbonPersonID=:gibbonPersonID, bloodType=:bloodType, longTermMedication=:longTermMedication, longTermMedicationDetails=:longTermMedicationDetails, tetanusWithin10Years=:tetanusWithin10Years, gibbonPersonIDUpdater=:gibbonPersonIDUpdater WHERE gibbonPersonMedicalUpdateID=:gibbonPersonMedicalUpdateID';
                    } else {
                        $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonMedicalID' => $gibbonPersonMedicalID, 'gibbonPersonID' => $gibbonPersonID, 'bloodType' => $bloodType, 'longTermMedication' => $longTermMedication, 'longTermMedicationDetails' => $longTermMedicationDetails, 'tetanusWithin10Years' => $tetanusWithin10Years, 'gibbonPersonIDUpdater' => $_SESSION[$guid]['gibbonPersonID']);
                        $sql = 'INSERT INTO gibbonPersonMedicalUpdate SET gibbonSchoolYearID=:gibbonSchoolYearID, gibbonPersonMedicalID=:gibbonPersonMedicalID, gibbonPersonID=:gibbonPersonID, bloodType=:bloodType, longTermMedication=:longTermMedication, longTermMedicationDetails=:longTermMedicationDetails, tetanusWithin10Years=:tetanusWithin10Years, gibbonPersonIDUpdater=:gibbonPersonIDUpdater';
                    }
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                    exit();
                }

                if ($existing == 'N') {
                    try {
                        $sqlLock = 'UNLOCK TABLES';
                        $result = $connection2->query($sqlLock);
                    } catch (PDOException $e) {
                    }
                }

                if ($partialFail == true) {
                    $URL .= '&return=error1';
                    header("Location: {$URL}");
                } else {
                    $URL .= '&return=success0';
                    header("Location: {$URL}");
                }
            }
        }
    }
}
