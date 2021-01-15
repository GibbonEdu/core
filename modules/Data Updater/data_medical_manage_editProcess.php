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

use Gibbon\Services\Format;
use Gibbon\Comms\NotificationEvent;
use Gibbon\Domain\Students\MedicalGateway;
use Gibbon\Domain\Students\StudentGateway;

include '../../gibbon.php';

$gibbonPersonMedicalUpdateID = $_GET['gibbonPersonMedicalUpdateID'] ?? '';
$gibbonPersonID = $_POST['gibbonPersonID'] ?? '';
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/data_medical_manage_edit.php&gibbonPersonMedicalUpdateID=$gibbonPersonMedicalUpdateID";

if (isActionAccessible($guid, $connection2, '/modules/Data Updater/data_medical_manage_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    //Check if school year specified
    if ($gibbonPersonMedicalUpdateID == '' or $gibbonPersonID == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        $medicalGateway = $container->get(MedicalGateway::class);

        try {
            $data = array('gibbonPersonMedicalUpdateID' => $gibbonPersonMedicalUpdateID);
            $sql = 'SELECT * FROM gibbonPersonMedicalUpdate WHERE gibbonPersonMedicalUpdateID=:gibbonPersonMedicalUpdateID';
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
            $row = $result->fetch();
            $gibbonPersonMedicalID = $row['gibbonPersonMedicalID'];
            $conditions = [];

            //Set values
            $data = array();
            $sqlSet = '';
            if (isset($_POST['bloodTypeOn'])) {
                if ($_POST['bloodTypeOn'] == 'on') {
                    $data['bloodType'] = $_POST['bloodType'];
                    $sqlSet .= 'bloodType=:bloodType, ';
                }
            }
            if (isset($_POST['longTermMedicationOn'])) {
                if ($_POST['longTermMedicationOn'] == 'on') {
                    $data['longTermMedication'] = $_POST['longTermMedication'];
                    $sqlSet .= 'longTermMedication=:longTermMedication, ';
                }
            }
            if (isset($_POST['longTermMedicationDetailsOn'])) {
                if ($_POST['longTermMedicationDetailsOn'] == 'on') {
                    $data['longTermMedicationDetails'] = $_POST['longTermMedicationDetails'];
                    $sqlSet .= 'longTermMedicationDetails=:longTermMedicationDetails, ';
                }
            }
            if (isset($_POST['tetanusWithin10YearsOn'])) {
                if ($_POST['tetanusWithin10YearsOn'] == 'on') {
                    $data['tetanusWithin10Years'] = $_POST['tetanusWithin10Years'];
                    $sqlSet .= 'tetanusWithin10Years=:tetanusWithin10Years, ';
                }
            }
            if (isset($_POST['commentOn'])) {
                if ($_POST['commentOn'] == 'on') {
                    $data['comment'] = $_POST['comment'];
                    $sqlSet .= 'comment=:comment, ';
                }
            }

            $partialFail = false;

            //Write to database
            //If form already exisits
            $count = 0;
            $count2 = 0;
            if ($_POST['formExists'] == true) {
                //Scan through existing conditions
                if (isset($_POST['count'])) {
                    $count = $_POST['count'];
                }
                for ($i = 0; $i < $count; ++$i) {
                    $dataCond = array();
                    $sqlSetCond = '';
                    if (isset($_POST["nameOn$i"])) {
                        if ($_POST["nameOn$i"] == 'on') {
                            $dataCond['name'] = $_POST["name$i"];
                            $sqlSetCond .= 'name=:name, ';
                        }
                    }
                    if (isset($_POST["gibbonAlertLevelIDOn$i"])) {
                        if ($_POST["gibbonAlertLevelIDOn$i"] == 'on') {
                            if ($_POST["gibbonAlertLevelID$i"] != '') {
                                $dataCond['gibbonAlertLevelID'] = $_POST["gibbonAlertLevelID$i"];
                                $sqlSetCond .= 'gibbonAlertLevelID=:gibbonAlertLevelID, ';

                                $gibbonAlertLevelID = $dataCond['gibbonAlertLevelID'];
                                $alert = getAlert($guid, $connection2, $gibbonAlertLevelID);
                                if (!empty($_POST["gibbonPersonMedicalConditionID$i"]) && ($alert['name'] == 'High' || $alert['name'] == 'Medium')) {
                                    $condition = $medicalGateway->getMedicalConditionByID($_POST["gibbonPersonMedicalConditionID$i"]);
                                    $conditions[] = $condition['name'] ?? '';
                                }
                            }
                        }
                    }
                    if (isset($_POST["triggersOn$i"])) {
                        if ($_POST["triggersOn$i"] == 'on') {
                            $dataCond['triggers'] = $_POST["triggers$i"];
                            $sqlSetCond .= 'triggers=:triggers, ';
                        }
                    }
                    if (isset($_POST["reactionOn$i"])) {
                        if ($_POST["reactionOn$i"] == 'on') {
                            $dataCond['reaction'] = $_POST["reaction$i"];
                            $sqlSetCond .= 'reaction=:reaction, ';
                        }
                    }
                    if (isset($_POST["responseOn$i"])) {
                        if ($_POST["responseOn$i"] == 'on') {
                            $dataCond['response'] = $_POST["response$i"];
                            $sqlSetCond .= 'response=:response, ';
                        }
                    }
                    if (isset($_POST["medicationOn$i"])) {
                        if ($_POST["medicationOn$i"] == 'on') {
                            $dataCond['medication'] = $_POST["medication$i"];
                            $sqlSetCond .= 'medication=:medication, ';
                        }
                    }
                    if (isset($_POST["lastEpisodeOn$i"])) {
                        if ($_POST["lastEpisodeOn$i"] == 'on') {
                            if ($_POST["lastEpisode$i"] != '') {
                                $dataCond['lastEpisode'] = $_POST["lastEpisode$i"];
                                $sqlSetCond .= 'lastEpisode=:lastEpisode, ';
                            } else {
                                $sqlSetCond .= 'lastEpisode=NULL, ';
                            }
                        }
                    }
                    if (isset($_POST["lastEpisodeTreatmentOn$i"])) {
                        if ($_POST["lastEpisodeTreatmentOn$i"] == 'on') {
                            $dataCond['lastEpisodeTreatment'] = $_POST["lastEpisodeTreatment$i"];
                            $sqlSetCond .= 'lastEpisodeTreatment=:lastEpisodeTreatment, ';
                        }
                    }
                    if (isset($_POST["commentOn$i"])) {
                        if ($_POST["commentOn$i"] == 'on') {
                            $dataCond['comment'] = $_POST["comment$i"];
                            $sqlSetCond .= 'comment=:comment, ';
                        }
                    }

                    if (!empty($_POST["attachmentOn$i"]) && $_POST["attachmentOn$i"] == 'on') {
                        $dataCond['attachment'] = $_POST["attachment$i"] ?? null;
                        $sqlSetCond .= 'attachment=:attachment, ';
                    }

                    try {
                        $dataCond['gibbonPersonMedicalID'] = $gibbonPersonMedicalID;
                        $dataCond['gibbonPersonMedicalConditionID'] = $_POST["gibbonPersonMedicalConditionID$i"];
                        $sqlCond = "UPDATE gibbonPersonMedicalCondition SET $sqlSetCond gibbonPersonMedicalID=:gibbonPersonMedicalID WHERE gibbonPersonMedicalConditionID=:gibbonPersonMedicalConditionID";
                        $resultCond = $connection2->prepare($sqlCond);
                        $resultCond->execute($dataCond);
                    } catch (PDOException $e) {
                        $partialFail = true;
                    }
                }

                //Scan through new conditions
                if (isset($_POST['count2'])) {
                    $count2 = $_POST['count2'];
                }
                for ($i = ($count + 1); $i <= ($count + $count2); ++$i) {
                    if (isset($_POST["nameOn$i"]) && $_POST["nameOn$i"] == 'on' && $_POST["gibbonPersonMedicalConditionUpdateID$i"] != '') {
                        $dataCond = array();
                        $sqlSetCond = '';
                        if (isset($_POST["nameOn$i"])) {
                            if ($_POST["nameOn$i"] == 'on') {
                                $dataCond['name'] = $_POST["name$i"];
                                $sqlSetCond .= 'name=:name, ';
                            }
                        }
                        if (isset($_POST["gibbonAlertLevelIDOn$i"])) {
                            if ($_POST["gibbonAlertLevelIDOn$i"] == 'on') {
                                if ($_POST["gibbonAlertLevelID$i"] != '') {
                                    $dataCond['gibbonAlertLevelID'] = $_POST["gibbonAlertLevelID$i"];
                                    $sqlSetCond .= 'gibbonAlertLevelID=:gibbonAlertLevelID, ';

                                    $gibbonAlertLevelID = $dataCond['gibbonAlertLevelID'];
                                    $alert = getAlert($guid, $connection2, $gibbonAlertLevelID);
                                    if (!empty($_POST["name$i"]) && ($alert['name'] == 'High' || $alert['name'] == 'Medium')) {
                                        $conditions[] = $_POST["name$i"] ?? '';
                                    }
                                }
                            }
                        }
                        if (isset($_POST["triggersOn$i"])) {
                            if ($_POST["triggersOn$i"] == 'on') {
                                $dataCond['triggers'] = $_POST["triggers$i"];
                                $sqlSetCond .= 'triggers=:triggers, ';
                            }
                        }
                        if (isset($_POST["reactionOn$i"])) {
                            if ($_POST["reactionOn$i"] == 'on') {
                                $dataCond['reaction'] = $_POST["reaction$i"];
                                $sqlSetCond .= 'reaction=:reaction, ';
                            }
                        }
                        if (isset($_POST["responseOn$i"])) {
                            if ($_POST["responseOn$i"] == 'on') {
                                $dataCond['response'] = $_POST["response$i"];
                                $sqlSetCond .= 'response=:response, ';
                            }
                        }
                        if (isset($_POST["medicationOn$i"])) {
                            if ($_POST["medicationOn$i"] == 'on') {
                                $dataCond['medication'] = $_POST["medication$i"];
                                $sqlSetCond .= 'medication=:medication, ';
                            }
                        }
                        if (isset($_POST["lastEpisodeOn$i"])) {
                            if ($_POST["lastEpisodeOn$i"] == 'on') {
                                if ($_POST["lastEpisode$i"] != '') {
                                    $dataCond['lastEpisode'] = $_POST["lastEpisode$i"];
                                    $sqlSetCond .= 'lastEpisode=:lastEpisode, ';
                                } else {
                                    $sqlSetCond .= 'lastEpisode=NULL, ';
                                }
                            }
                        }
                        if (isset($_POST["lastEpisodeTreatmentOn$i"])) {
                            if ($_POST["lastEpisodeTreatmentOn$i"] == 'on') {
                                $dataCond['lastEpisodeTreatment'] = $_POST["lastEpisodeTreatment$i"];
                                $sqlSetCond .= 'lastEpisodeTreatment=:lastEpisodeTreatment, ';
                            }
                        }
                        if (isset($_POST["commentOn$i"])) {
                            if ($_POST["commentOn$i"] == 'on') {
                                $dataCond['comment'] = $_POST["comment$i"];
                                $sqlSetCond .= 'comment=:comment, ';
                            }
                        }

                        if (!empty($_POST["attachmentOn$i"]) && $_POST["attachmentOn$i"] == 'on') {
                            $dataCond['attachment'] = $_POST["attachment$i"] ?? null;
                            $sqlSetCond .= 'attachment=:attachment, ';
                        }

                        try {
                            $dataCond['gibbonPersonMedicalID'] = $gibbonPersonMedicalID;
                            $sqlCond = "INSERT INTO gibbonPersonMedicalCondition SET $sqlSetCond gibbonPersonMedicalID=:gibbonPersonMedicalID";
                            $resultCond = $connection2->prepare($sqlCond);
                            $resultCond->execute($dataCond);
                        } catch (PDOException $e) {
                            $partialFail = true;
                        }

                        try {
                            $dataCond = array('gibbonPersonMedicalConditionID' => $connection2->lastInsertID(), 'gibbonPersonMedicalConditionUpdateID' => $_POST["gibbonPersonMedicalConditionUpdateID$i"]);
                            $sqlCond = 'UPDATE gibbonPersonMedicalConditionUpdate SET gibbonPersonMedicalConditionID=:gibbonPersonMedicalConditionID WHERE gibbonPersonMedicalConditionUpdateID=:gibbonPersonMedicalConditionUpdateID';
                            $resultCond = $connection2->prepare($sqlCond);
                            $resultCond->execute($dataCond);
                        } catch (PDOException $e) {
                            $partialFail = true;
                        }
                    }
                }

                try {
                    $data['gibbonPersonMedicalID'] = $gibbonPersonMedicalID;
                    $data['gibbonPersonID'] = $gibbonPersonID;
                    $sql = "UPDATE gibbonPersonMedical SET $sqlSet gibbonPersonMedicalID=:gibbonPersonMedicalID WHERE gibbonPersonID=:gibbonPersonID";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                    exit();
                }

                if ($partialFail == true) {
                    $URL .= '&return=warning1';
                    header("Location: {$URL}");
                } else {
                    //Write to database
                    try {
                        $data = array('gibbonPersonMedicalUpdateID' => $gibbonPersonMedicalUpdateID);
                        $sql = "UPDATE gibbonPersonMedicalUpdate SET status='Complete' WHERE gibbonPersonMedicalUpdateID=:gibbonPersonMedicalUpdateID";
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $URL .= '&return=warning1';
                        header("Location: {$URL}");
                        exit();
                    }
                }
            }

            //If form does not already exist
            else {
                try {
                    if ($sqlSet != '') {
                        $data['gibbonPersonID'] = $gibbonPersonID;
                        $sql = 'INSERT INTO gibbonPersonMedical SET gibbonPersonID=:gibbonPersonID, '.substr($sqlSet, 0, (strlen($sqlSet) - 2));
                    } else {
                        $data['gibbonPersonID'] = $gibbonPersonID;
                        $sql = 'INSERT INTO gibbonPersonMedical SET gibbonPersonID=:gibbonPersonID';
                    }
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                    exit();
                }

                $gibbonPersonMedicalID = $connection2->lastInsertID();

                //Scan through new conditions
                if (isset($_POST['count2'])) {
                    $count2 = $_POST['count2'];
                }
                for ($i = ($count + 1); $i <= ($count + $count2); ++$i) {
                    if ($_POST["nameOn$i"] == 'on' and $_POST["gibbonAlertLevelIDOn$i"] == 'on') {
                        //Scan through existing conditions
                        $dataCond = array();
                        $sqlSetCond = '';
                        if (isset($_POST["nameOn$i"])) {
                            if ($_POST["nameOn$i"] == 'on') {
                                $dataCond['name'] = $_POST["name$i"];
                                $sqlSetCond .= 'name=:name, ';

                            }
                        }
                        if (isset($_POST["gibbonAlertLevelIDOn$i"])) {
                            if ($_POST["gibbonAlertLevelIDOn$i"] == 'on') {
                                if ($_POST["gibbonAlertLevelID$i"] != '') {
                                    $dataCond['gibbonAlertLevelID'] = $_POST["gibbonAlertLevelID$i"];
                                    $sqlSetCond .= 'gibbonAlertLevelID=:gibbonAlertLevelID, ';

                                    $gibbonAlertLevelID = $dataCond['gibbonAlertLevelID'];
                                    $alert = getAlert($guid, $connection2, $gibbonAlertLevelID);
                                    if (!empty($_POST["name$i"]) && ($alert['name'] == 'High' || $alert['name'] == 'Medium')) {
                                        $conditions[] = $_POST["name$i"];
                                    }
                                }
                            }
                        }
                        if (isset($_POST["triggersOn$i"])) {
                            if ($_POST["triggersOn$i"] == 'on') {
                                $dataCond['triggers'] = $_POST["triggers$i"];
                                $sqlSetCond .= 'triggers=:triggers, ';
                            }
                        }
                        if (isset($_POST["reactionOn$i"])) {
                            if ($_POST["reactionOn$i"] == 'on') {
                                $dataCond['reaction'] = $_POST["reaction$i"];
                                $sqlSetCond .= 'reaction=:reaction, ';
                            }
                        }
                        if (isset($_POST["responseOn$i"])) {
                            if ($_POST["responseOn$i"] == 'on') {
                                $dataCond['response'] = $_POST["response$i"];
                                $sqlSetCond .= 'response=:response, ';
                            }
                        }
                        if (isset($_POST["medicationOn$i"])) {
                            if ($_POST["medicationOn$i"] == 'on') {
                                $dataCond['medication'] = $_POST["medication$i"];
                                $sqlSetCond .= 'medication=:medication, ';
                            }
                        }
                        if (isset($_POST["lastEpisodeOn$i"])) {
                            if ($_POST["lastEpisodeOn$i"] == 'on') {
                                if ($_POST["lastEpisode$i"] != '') {
                                    $dataCond['lastEpisode'] = $_POST["lastEpisode$i"];
                                    $sqlSetCond .= 'lastEpisode=:lastEpisode, ';
                                } else {
                                    $sqlSetCond .= 'lastEpisode=NULL, ';
                                }
                            }
                        }
                        if (isset($_POST["lastEpisodeTreatmentOn$i"])) {
                            if ($_POST["lastEpisodeTreatmentOn$i"] == 'on') {
                                $dataCond['lastEpisodeTreatment'] = $_POST["lastEpisodeTreatment$i"];
                                $sqlSetCond .= 'lastEpisodeTreatment=:lastEpisodeTreatment, ';
                            }
                        }
                        if (isset($_POST["commentOn$i"])) {
                            if ($_POST["commentOn$i"] == 'on') {
                                $dataCond['comment'] = $_POST["comment$i"];
                                $sqlSetCond .= 'comment=:comment, ';
                            }
                        }

                        if (!empty($_POST["attachmentOn$i"]) && $_POST["attachmentOn$i"] == 'on') {
                            $dataCond['attachment'] = $_POST["attachment$i"] ?? null;
                            $sqlSetCond .= 'attachment=:attachment, ';
                        }

                        try {
                            $dataCond['gibbonPersonMedicalID'] = $gibbonPersonMedicalID;
                            $sqlCond = "INSERT INTO gibbonPersonMedicalCondition SET $sqlSetCond gibbonPersonMedicalID=:gibbonPersonMedicalID";
                            $resultCond = $connection2->prepare($sqlCond);
                            $resultCond->execute($dataCond);
                        } catch (PDOException $e) {
                            $partialFail = true;
                        }

                        try {
                            $dataCond = array('gibbonPersonMedicalConditionID' => $connection2->lastInsertID(), 'gibbonPersonMedicalConditionUpdateID' => $_POST["gibbonPersonMedicalConditionUpdateID$i"]);
                            $sqlCond = 'UPDATE gibbonPersonMedicalConditionUpdate SET gibbonPersonMedicalConditionID=:gibbonPersonMedicalConditionID WHERE gibbonPersonMedicalConditionUpdateID=:gibbonPersonMedicalConditionUpdateID';
                            $resultCond = $connection2->prepare($sqlCond);
                            $resultCond->execute($dataCond);
                        } catch (PDOException $e) {
                            $partialFail = true;
                        }
                    }
                }

                if ($partialFail == true) {
                    $URL .= '&return=warning1';
                    header("Location: {$URL}");
                } else {
                    //Write to database
                    try {
                        $data = array('gibbonPersonMedicalUpdateID' => $gibbonPersonMedicalUpdateID);
                        $sql = "UPDATE gibbonPersonMedicalUpdate SET status='Complete' WHERE gibbonPersonMedicalUpdateID=:gibbonPersonMedicalUpdateID";
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $URL .= '&updateReturn=success1';
                        header("Location: {$URL}");
                        exit();
                    }
                }
            }

            if (!empty($conditions)) {
                $student = $container->get(StudentGateway::class)->selectActiveStudentByPerson($gibbon->session->get('gibbonSchoolYearID'), $gibbonPersonID)->fetch();
                $alert = getAlert($guid, $connection2, $gibbonAlertLevelID);

                // Raise a new notification event
                $event = new NotificationEvent('Students', 'Medical Condition');
                $event->addScope('gibbonPersonIDStudent', $student['gibbonPersonID']);
                $event->addScope('gibbonYearGroupID', $student['gibbonYearGroupID']);

                $event->setNotificationText(__('{name} has a new or updated medical condition ({condition}) with a {risk} risk level.', [
                    'name' => Format::name('', $student['preferredName'], $student['surname'], 'Student', false, true),
                    'condition' => implode(', ', $conditions),
                    'risk' => $alert['name'],
                ]));
                $event->setActionLink('/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID='.$student['gibbonPersonID'].'&search=&allStudents=&subpage=Medical');

                // Send all notifications
                $sendReport = $event->sendNotifications($pdo, $gibbon->session);
            }

            $URL .= '&return=success0';
            header("Location: {$URL}");
        }
    }
}
