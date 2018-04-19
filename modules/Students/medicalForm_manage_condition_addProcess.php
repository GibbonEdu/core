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

$gibbonPersonMedicalID = $_POST['gibbonPersonMedicalID'];
$search = $_GET['search'];

if ($gibbonPersonMedicalID == '') { echo 'Fatal error loading this page!';
} else {
    $URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/medicalForm_manage_condition_add.php&gibbonPersonMedicalID=$gibbonPersonMedicalID&search=$search";

    if (isActionAccessible($guid, $connection2, '/modules/Students/medicalForm_manage_condition_add.php') == false) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
    } else {
        //Proceed!
        //Check if person specified
        if ($gibbonPersonMedicalID == '') {
            $URL .= '&return=error1';
            header("Location: {$URL}");
        } else {
            try {
                $data = array('gibbonPersonMedicalID' => $gibbonPersonMedicalID);
                $sql = 'SELECT * FROM gibbonPersonMedical WHERE gibbonPersonMedicalID=:gibbonPersonMedicalID';
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
                $gibbonAlertLevelID = $_POST['gibbonAlertLevelID'];
                $triggers = $_POST['triggers'];
                $reaction = $_POST['reaction'];
                $response = $_POST['response'];
                $medication = $_POST['medication'];
                if ($_POST['lastEpisode'] == '') {
                    $lastEpisode = null;
                } else {
                    $lastEpisode = dateConvert($guid, $_POST['lastEpisode']);
                }
                $lastEpisodeTreatment = $_POST['lastEpisodeTreatment'];
                $comment = $_POST['comment'];

                if ($name == '' or $gibbonAlertLevelID == '') {
                    $URL .= '&return=error1';
                    header("Location: {$URL}");
                } else {
                    //Write to database
                    try {
                        $data = array('gibbonPersonMedicalID' => $gibbonPersonMedicalID, 'name' => $name, 'gibbonAlertLevelID' => $gibbonAlertLevelID, 'triggers' => $triggers, 'reaction' => $reaction, 'response' => $response, 'medication' => $medication, 'lastEpisode' => $lastEpisode, 'lastEpisodeTreatment' => $lastEpisodeTreatment, 'comment' => $comment);
                        $sql = 'INSERT INTO gibbonPersonMedicalCondition SET gibbonPersonMedicalID=:gibbonPersonMedicalID, name=:name, gibbonAlertLevelID=:gibbonAlertLevelID, triggers=:triggers, reaction=:reaction, response=:response, medication=:medication, lastEpisode=:lastEpisode, lastEpisodeTreatment=:lastEpisodeTreatment, comment=:comment';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $URL .= '&return=error2';
                        header("Location: {$URL}");
                        exit();
                    }

                    //Last insert ID
                    $AI = str_pad($connection2->lastInsertID(), 12, '0', STR_PAD_LEFT);

                    $URL .= "&return=success0&editID=$AI";
                    header("Location: {$URL}");
                }
            }
        }
    }
}
