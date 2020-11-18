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

use Gibbon\FileUploader;
use Gibbon\Services\Format;
use Gibbon\Comms\NotificationEvent;
use Gibbon\Domain\Students\MedicalGateway;
use Gibbon\Domain\DataUpdater\MedicalUpdateGateway;

include '../../gibbon.php';

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
                $URLSuccess = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Data Updater/data_medical.php&gibbonPersonID='.$gibbonPersonID;

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
                $URLSuccess = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Data Updater/data_updates.php&gibbonPersonID='.$gibbonPersonID;

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
                // Proceed!
                $gibbonPersonMedicalID = $_POST['gibbonPersonMedicalID'] ?? null;
                $data = [
                    'gibbonPersonMedicalID'     => $gibbonPersonMedicalID,
                    'gibbonPersonID'            => $gibbonPersonID,
                    'bloodType'                 => $_POST['bloodType'] ?? '',
                    'longTermMedication'        => $_POST['longTermMedication'] ?? 'N',
                    'longTermMedicationDetails' => $_POST['longTermMedicationDetails'] ?? '',
                    'tetanusWithin10Years'      => $_POST['tetanusWithin10Years'] ?? '',
                    'comment'                   => $_POST['comment'] ?? '',
                ];

                // Get medical form fields
                $medicalGateway = $container->get(MedicalGateway::class);
                $values = $medicalGateway->getByID($gibbonPersonMedicalID);

                // COMPARE VALUES: Has the data changed?
                $dataChanged = empty($values);
                foreach ($values as $key => $value) {
                    if (!isset($data[$key])) continue; // Skip fields we don't plan to update

                    if ($data[$key] != $value) {
                        $dataChanged = true;
                    }
                }

                // Write to database
                $existing = $_POST['existing'] ?? 'N';
                $data['gibbonSchoolYearID'] = $_SESSION[$guid]['gibbonSchoolYearID'];
                $data['gibbonPersonIDUpdater'] = $_SESSION[$guid]['gibbonPersonID'];
                $data['timestamp'] = date('Y-m-d H:i:s');
                
                if ($existing != 'N') {
                    $gibbonPersonMedicalUpdateID = $existing;
                    $data['gibbonPersonMedicalUpdateID'] = $gibbonPersonMedicalUpdateID;
                    $sql = 'UPDATE gibbonPersonMedicalUpdate SET gibbonSchoolYearID=:gibbonSchoolYearID, gibbonPersonMedicalID=:gibbonPersonMedicalID, gibbonPersonID=:gibbonPersonID, bloodType=:bloodType, longTermMedication=:longTermMedication, longTermMedicationDetails=:longTermMedicationDetails, tetanusWithin10Years=:tetanusWithin10Years, comment=:comment, gibbonPersonIDUpdater=:gibbonPersonIDUpdater, timestamp=:timestamp WHERE gibbonPersonMedicalUpdateID=:gibbonPersonMedicalUpdateID';
                    $pdo->update($sql, $data);
                } else {
                    $sql = 'INSERT INTO gibbonPersonMedicalUpdate SET gibbonSchoolYearID=:gibbonSchoolYearID, gibbonPersonMedicalID=:gibbonPersonMedicalID, gibbonPersonID=:gibbonPersonID, bloodType=:bloodType, longTermMedication=:longTermMedication, longTermMedicationDetails=:longTermMedicationDetails, tetanusWithin10Years=:tetanusWithin10Years, comment=:comment, gibbonPersonIDUpdater=:gibbonPersonIDUpdater, timestamp=:timestamp';
                    $gibbonPersonMedicalUpdateID = $pdo->insert($sql, $data);
                }

                // Update existing medical conditions
                $partialFail = false;
                $count = $_POST['count'] ?? 0;

                for ($i = 0; $i < $count; ++$i) {
                    $data = [
                        'gibbonPersonMedicalID' => $gibbonPersonMedicalID,
                        'gibbonPersonMedicalUpdateID' => $gibbonPersonMedicalUpdateID,
                        'name'                  => $_POST["name$i"] ?? '',
                        'gibbonAlertLevelID'    => $_POST["gibbonAlertLevelID$i"] ?? '',
                        'triggers'              => $_POST["triggers$i"] ?? '',
                        'reaction'              => $_POST["reaction$i"] ?? '',
                        'response'              => $_POST["response$i"] ?? '',
                        'medication'            => $_POST["medication$i"] ?? '',
                        'lastEpisode'           => !empty($_POST["lastEpisode$i"]) ? Format::dateConvert($_POST["lastEpisode$i"]) : null,
                        'lastEpisodeTreatment'  => $_POST["lastEpisodeTreatment$i"] ?? '',
                        'comment'               => $_POST["commentCond$i"] ?? '',
                        'attachment'            => $_POST["attachment$i"] ?? null,
                        'gibbonPersonIDUpdater' => $_SESSION[$guid]['gibbonPersonID'],
                    ];

                    if (!empty($_FILES["attachment$i"]['tmp_name'])) {
                        // Upload the file, return the /uploads relative path
                        $fileUploader = new FileUploader($pdo, $gibbon->session);
                        $data['attachment'] = $fileUploader->uploadFromPost($_FILES["attachment$i"]);
    
                        if (empty($data['attachment'])) {
                            $partialFail = true;
                        }
                    }

                    // Get the values of the current condition
                    $gibbonPersonMedicalConditionID = $_POST["gibbonPersonMedicalConditionID$i"] ?? null;
                    $condition = $medicalGateway->getMedicalConditionByID($gibbonPersonMedicalConditionID);
                    if (empty($condition)) {
                        $dataChanged = true;
                    }

                    // Check for values that have changed
                    foreach ($condition as $key => $value) {
                        if (!isset($data[$key])) continue; // Skip fields we don't plan to update
                        if ($data[$key] != $value) {
                            $dataChanged = true;
                        }
                    }

                    $data['timestamp'] = date('Y-m-d H:i:s');

                    if ($existing != 'N' && !empty($_POST["gibbonPersonMedicalConditionUpdateID$i"])) {
                        $data['gibbonPersonMedicalConditionUpdateID'] = $_POST["gibbonPersonMedicalConditionUpdateID$i"] ?? '';
                        $sql = 'UPDATE gibbonPersonMedicalConditionUpdate SET gibbonPersonMedicalUpdateID=:gibbonPersonMedicalUpdateID, gibbonPersonMedicalID=:gibbonPersonMedicalID, name=:name, gibbonAlertLevelID=:gibbonAlertLevelID, triggers=:triggers, reaction=:reaction, response=:response, medication=:medication, lastEpisode=:lastEpisode, lastEpisodeTreatment=:lastEpisodeTreatment, comment=:comment, attachment=:attachment, gibbonPersonIDUpdater=:gibbonPersonIDUpdater, timestamp=:timestamp WHERE gibbonPersonMedicalConditionUpdateID=:gibbonPersonMedicalConditionUpdateID';
                        $pdo->update($sql, $data);
                    } else {
                        $data['gibbonPersonMedicalConditionID'] = $gibbonPersonMedicalConditionID;
                        $sql = 'INSERT INTO gibbonPersonMedicalConditionUpdate SET gibbonPersonMedicalUpdateID=:gibbonPersonMedicalUpdateID, gibbonPersonMedicalConditionID=:gibbonPersonMedicalConditionID, gibbonPersonMedicalID=:gibbonPersonMedicalID, name=:name, gibbonAlertLevelID=:gibbonAlertLevelID, triggers=:triggers, reaction=:reaction, response=:response, medication=:medication, lastEpisode=:lastEpisode, lastEpisodeTreatment=:lastEpisodeTreatment, comment=:comment, attachment=:attachment, gibbonPersonIDUpdater=:gibbonPersonIDUpdater, timestamp=:timestamp';
                        $gibbonPersonMedicalConditionUpdateID = $pdo->insert($sql, $data);
                    }
                }

                //Add new medical condition
                if (isset($_POST['addCondition']) && $_POST['addCondition'] == 'Yes') {
                    $dataChanged = true;
                    $data = [
                        'gibbonPersonMedicalUpdateID' => $gibbonPersonMedicalUpdateID,
                        'gibbonPersonMedicalID'       => $gibbonPersonMedicalID,
                        'name'                        => $_POST['name'] ?? '',
                        'gibbonAlertLevelID'          => $_POST['gibbonAlertLevelID'] ?? '',
                        'triggers'                    => $_POST['triggers'] ?? '',
                        'reaction'                    => $_POST['reaction'] ?? '',
                        'response'                    => $_POST['response'] ?? '',
                        'medication'                  => $_POST['medication'] ?? '',
                        'lastEpisode'                 => !empty($_POST['lastEpisode']) ? Format::dateConvert($_POST['lastEpisode']) :  null,
                        'lastEpisodeTreatment'        => $_POST['lastEpisodeTreatment'] ?? '',
                        'comment'                     => $_POST['commentCond'] ?? '',
                        'attachment'                  => $_POST['attachment'] ?? '',
                        'gibbonPersonIDUpdater'       => $_SESSION[$guid]['gibbonPersonID'],
                        'timestamp'                   => date('Y-m-d H:i:s'),
                    ];

                    if (!empty($_FILES['attachment']['tmp_name'])) {
                        // Upload the file, return the /uploads relative path
                        $fileUploader = new FileUploader($pdo, $gibbon->session);
                        $data['attachment'] = $fileUploader->uploadFromPost($_FILES['attachment']);
    
                        if (empty($data['attachment'])) {
                            $partialFail = true;
                        }
                    }

                    if (!empty($data['name']) and !empty($data['gibbonAlertLevelID'])) {
                        $sql = 'INSERT INTO gibbonPersonMedicalConditionUpdate SET gibbonPersonMedicalUpdateID=:gibbonPersonMedicalUpdateID, gibbonPersonMedicalID=:gibbonPersonMedicalID, name=:name, gibbonAlertLevelID=:gibbonAlertLevelID, triggers=:triggers, reaction=:reaction, response=:response, medication=:medication, lastEpisode=:lastEpisode, lastEpisodeTreatment=:lastEpisodeTreatment, comment=:comment, attachment=:attachment, gibbonPersonIDUpdater=:gibbonPersonIDUpdater, timestamp=:timestamp';
                        $pdo->insert($sql, $data);
                    } else {
                        $partialFail = true;
                    }
                }

                // If no data has changed in the medical form and any conditions, then auto-accept the changes
                if ($dataChanged == false) {
                    $container->get(MedicalUpdateGateway::class)->update($gibbonPersonMedicalUpdateID, ['status' => 'Complete']);
                } else {
                    // Raise a new notification event
                    $event = new NotificationEvent('Data Updater', 'Medical Form Updates');

                    $event->addRecipient($_SESSION[$guid]['organisationDBA']);
                    $event->setNotificationText(__('A medical data update request has been submitted.'));
                    $event->setActionLink('/index.php?q=/modules/Data Updater/data_medical_manage.php');

                    $event->sendNotifications($pdo, $gibbon->session);
                }

                if ($partialFail == true) {
                    $URL .= '&return=warning1';
                    header("Location: {$URL}");
                } else {
                    $URLSuccess .= '&return=success0';
                    header("Location: {$URLSuccess}");
                }
            }
        }
    }
}
