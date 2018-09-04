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

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address']).'/in_archive.php';

if (isActionAccessible($guid, $connection2, '/modules/Individual Needs/in_archive.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    $deleteCurrentPlans = $_POST['deleteCurrentPlans'];
    $title = $_POST['title'];
    $gibbonPersonIDs = isset($_POST['gibbonPersonID'])? $_POST['gibbonPersonID'] : array();
    if (!is_array($gibbonPersonIDs)) {
        $gibbonPersonIDs = array($gibbonPersonIDs);
    }

    if ($deleteCurrentPlans == '' or $title == '' or count($gibbonPersonIDs) < 1) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        $partialFail = false;

        //SCAN THROUGH EACH USER
        foreach ($gibbonPersonIDs as $gibbonPersonID) {
            $userFail = false;
            //Get each user's record
            try {
                $data = array('gibbonPersonID' => $gibbonPersonID);
                $sql = "SELECT surname, preferredName, gibbonIN.* FROM gibbonPerson JOIN gibbonIN ON (gibbonIN.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE status='Full' AND gibbonPerson.gibbonPersonID=:gibbonPersonID ORDER BY surname, preferredName";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $userFail = true;
                $partialFail = true;
            }
            if ($result->rowCount() != 1) {
                $userFail = true;
                $partialFail = true;
            }

            if ($userFail == false) {
                $userUpdateFail = false;
                $row = $result->fetch();

                //Check for descriptors, and write to array
                $descriptors = array();
                $descriptorsCount = 0;
                try {
                    $dataDesciptors = array('gibbonPersonID' => $gibbonPersonID);
                    $sqlDesciptors = 'SELECT * FROM gibbonINPersonDescriptor WHERE gibbonPersonID=:gibbonPersonID';
                    $resultDesciptors = $connection2->prepare($sqlDesciptors);
                    $resultDesciptors->execute($dataDesciptors);
                } catch (PDOException $e) {
                    $partialFail = true;
                }
                while ($rowDesciptors = $resultDesciptors->fetch()) {
                    $descriptors[$descriptorsCount]['gibbonINDescriptorID'] = $rowDesciptors['gibbonINDescriptorID'];
                    $descriptors[$descriptorsCount]['gibbonAlertLevelID'] = $rowDesciptors['gibbonAlertLevelID'];
                    ++$descriptorsCount;
                }
                $descriptors = serialize($descriptors);

                //Make archive of record
                try {
                    $dataUpdate = array('strategies' => $row['strategies'], 'targets' => $row['targets'], 'notes' => $row['notes'], 'gibbonPersonID' => $gibbonPersonID, 'title' => $title, 'descriptors' => $descriptors);
                    $sqlUpdate = 'INSERT INTO gibbonINArchive SET gibbonPersonID=:gibbonPersonID, strategies=:strategies, targets=:targets, notes=:notes, archiveTitle=:title, descriptors=:descriptors, archiveTimestamp=now()';
                    $resultUpdate = $connection2->prepare($sqlUpdate);
                    $resultUpdate->execute($dataUpdate);
                } catch (PDOException $e) {
                    $userUpdateFail = true;
                    $partialFail = true;
                }

                //If copy was successful and deleteCurrentPlans=Y, update current record to blank IEP fields
                if ($deleteCurrentPlans == 'Y' and $userUpdateFail == false) {
                    try {
                        $dataUpdate = array('gibbonPersonID' => $gibbonPersonID);
                        $sqlUpdate = "UPDATE gibbonIN SET strategies='', targets='', notes='' WHERE gibbonPersonID=:gibbonPersonID";
                        $resultUpdate = $connection2->prepare($sqlUpdate);
                        $resultUpdate->execute($dataUpdate);
                    } catch (PDOException $e) {
                        $partialFail = true;
                    }
                }
            }
        }

        //DEAL WITH OUTCOME
        if ($partialFail) {
            $URL .= '&return=warning1';
            header("Location: {$URL}");
        } else {
            $URL .= '&return=success0';
            header("Location: {$URL}");
        }
    }
}
