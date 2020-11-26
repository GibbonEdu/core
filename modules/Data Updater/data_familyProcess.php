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

use Gibbon\Comms\NotificationEvent;

include '../../gibbon.php';

$gibbonFamilyID = $_GET['gibbonFamilyID'];
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/data_family.php&gibbonFamilyID=$gibbonFamilyID";

if (isActionAccessible($guid, $connection2, '/modules/Data Updater/data_family.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    //Check if school year specified
    if ($gibbonFamilyID == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        //Get action with highest precendence
        $highestAction = getHighestGroupedAction($guid, $_POST['address'], $connection2);
        if ($highestAction == false) {
            $URL .= "&return=error0$params";
            header("Location: {$URL}");
        } else {
            //Check access to person
            if ($highestAction == 'Update Family Data_any') {
                $URLSuccess = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Data Updater/data_family.php&gibbonFamilyID='.$gibbonFamilyID;

                
                    $dataCheck = array('gibbonFamilyID' => $gibbonFamilyID);
                    $sqlCheck = 'SELECT gibbonFamily.* FROM gibbonFamily WHERE gibbonFamilyID=:gibbonFamilyID';
                    $resultCheck = $connection2->prepare($sqlCheck);
                    $resultCheck->execute($dataCheck);
            } else {
                $URLSuccess = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Data Updater/data_updates.php&gibbonFamilyID='.$gibbonFamilyID;

                
                    $dataCheck = array('gibbonFamilyID' => $gibbonFamilyID, 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                    $sqlCheck = "SELECT gibbonFamily.* FROM gibbonFamily JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) WHERE gibbonPersonID=:gibbonPersonID AND childDataAccess='Y' AND gibbonFamily.gibbonFamilyID=:gibbonFamilyID";
                    $resultCheck = $connection2->prepare($sqlCheck);
                    $resultCheck->execute($dataCheck);
            }

            if ($resultCheck->rowCount() != 1) {
                $URL .= '&return=warning';
                header("Location: {$URL}");
            } else {
                $values = $resultCheck->fetch();

                //Proceed!
                $data = [
                    'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'],
                    'nameAddress' => $_POST['nameAddress'] ?? '',
                    'homeAddress' => $_POST['homeAddress'] ?? '',
                    'homeAddressDistrict' => $_POST['homeAddressDistrict'] ?? '',
                    'homeAddressCountry' => $_POST['homeAddressCountry'] ?? '',
                    'languageHomePrimary' => $_POST['languageHomePrimary'] ?? '',
                    'languageHomeSecondary' => $_POST['languageHomeSecondary'] ?? '',
                    'gibbonPersonIDUpdater' => $_SESSION[$guid]['gibbonPersonID'],
                ];

                // COMPARE VALUES: Has the data changed?
                $dataChanged = false;
                foreach ($values as $key => $value) {
                    if (!isset($data[$key])) continue; // Skip fields we don't plan to update

                    if ($data[$key] != $value) {
                        $dataChanged = true;
                    }
                }

                // Auto-accept updates where no data had changed
                $data['status'] = $dataChanged ? 'Pending' : 'Complete';

                //Write to database
                $existing = $_POST['existing'] ?? 'N';
                $data['gibbonSchoolYearID'] = $_SESSION[$guid]['gibbonSchoolYearID'];
                $data['gibbonPersonIDUpdater'] = $_SESSION[$guid]['gibbonPersonID'];
                $data['timestamp'] = date('Y-m-d H:i:s');

                if ($existing != 'N') {
                    $data['gibbonFamilyUpdateID'] = $existing;
                    $sql = 'UPDATE gibbonFamilyUpdate SET `status`=:status, gibbonSchoolYearID=:gibbonSchoolYearID, nameAddress=:nameAddress, homeAddress=:homeAddress, homeAddressDistrict=:homeAddressDistrict, homeAddressCountry=:homeAddressCountry, languageHomePrimary=:languageHomePrimary, languageHomeSecondary=:languageHomeSecondary, gibbonPersonIDUpdater=:gibbonPersonIDUpdater, timestamp=:timestamp WHERE gibbonFamilyUpdateID=:gibbonFamilyUpdateID';
                } else {
                    $data['gibbonFamilyID'] = $gibbonFamilyID;
                    $sql = 'INSERT INTO gibbonFamilyUpdate SET `status`=:status, gibbonSchoolYearID=:gibbonSchoolYearID, gibbonFamilyID=:gibbonFamilyID, nameAddress=:nameAddress, homeAddress=:homeAddress, homeAddressDistrict=:homeAddressDistrict, homeAddressCountry=:homeAddressCountry, languageHomePrimary=:languageHomePrimary, languageHomeSecondary=:languageHomeSecondary, gibbonPersonIDUpdater=:gibbonPersonIDUpdater, timestamp=:timestamp';
                }
                $pdo->statement($sql, $data);


                if ($dataChanged) {
                    // Raise a new notification event
                    $event = new NotificationEvent('Data Updater', 'Family Data Updates');

                    $event->addRecipient($_SESSION[$guid]['organisationDBA']);
                    $event->setNotificationText(__('A family data update request has been submitted.'));
                    $event->setActionLink('/index.php?q=/modules/Data Updater/data_family_manage.php');

                    $event->sendNotifications($pdo, $gibbon->session);
                }

                $URLSuccess .= '&return=success0';
                header("Location: {$URLSuccess}");
            }
        }
    }
}
