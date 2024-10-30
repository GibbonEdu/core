<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

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
use Gibbon\Data\Validator;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$gibbonFamilyID = $_GET['gibbonFamilyID'] ?? '';
$address = $_POST['address'] ?? '';
$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($address)."/data_family.php&gibbonFamilyID=$gibbonFamilyID";

if (isActionAccessible($guid, $connection2, '/modules/Data Updater/data_family.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    //Check if gibbonFamilyID specified
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
                $URLSuccess = $session->get('absoluteURL').'/index.php?q=/modules/Data Updater/data_family.php&gibbonFamilyID='.$gibbonFamilyID;


                    $dataCheck = array('gibbonFamilyID' => $gibbonFamilyID);
                    $sqlCheck = 'SELECT gibbonFamily.* FROM gibbonFamily WHERE gibbonFamilyID=:gibbonFamilyID';
                    $resultCheck = $connection2->prepare($sqlCheck);
                    $resultCheck->execute($dataCheck);
            } else {
                $URLSuccess = $session->get('absoluteURL').'/index.php?q=/modules/Data Updater/data_updates.php&gibbonFamilyID='.$gibbonFamilyID;


                    $dataCheck = array('gibbonFamilyID' => $gibbonFamilyID, 'gibbonPersonID' => $session->get('gibbonPersonID'));
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
                    'gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'),
                    'nameAddress' => $_POST['nameAddress'] ?? '',
                    'homeAddress' => $_POST['homeAddress'] ?? '',
                    'homeAddressDistrict' => $_POST['homeAddressDistrict'] ?? '',
                    'homeAddressCountry' => $_POST['homeAddressCountry'] ?? '',
                    'languageHomePrimary' => $_POST['languageHomePrimary'] ?? '',
                    'languageHomeSecondary' => $_POST['languageHomeSecondary'] ?? '',
                    'gibbonPersonIDUpdater' => $session->get('gibbonPersonID'),
                ];

                // COMPARE VALUES: Has the data changed?
                $dataChanged = false;
                foreach ($values as $key => $value) {
                    if (!isset($data[$key])) continue; // Skip fields we don't plan to update
                    if (empty($data[$key]) && empty($value)) continue; // Nulls, false and empty strings should cause no change

                    if ($data[$key] != $value) {
                        $dataChanged = true;
                    }
                }

                // Auto-accept updates where no data had changed
                $data['status'] = $dataChanged ? 'Pending' : 'Complete';

                //Write to database
                $existing = $_POST['existing'] ?? 'N';
                $data['gibbonSchoolYearID'] = $session->get('gibbonSchoolYearID');
                $data['gibbonPersonIDUpdater'] = $session->get('gibbonPersonID');
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

                    $event->addRecipient($session->get('organisationDBA'));
                    $event->setNotificationText(__('A family data update request has been submitted.'));
                    $event->setActionLink('/index.php?q=/modules/Data Updater/data_family_manage.php');

                    $event->sendNotifications($pdo, $session);
                }

                $URLSuccess .= '&return=success0';
                header("Location: {$URLSuccess}");
            }
        }
    }
}
