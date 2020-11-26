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

$gibbonFinanceInvoiceeID = $_GET['gibbonFinanceInvoiceeID'];
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/data_finance.php&gibbonFinanceInvoiceeID=$gibbonFinanceInvoiceeID";

if (isActionAccessible($guid, $connection2, '/modules/Data Updater/data_finance.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    //Check if school year specified
    if ($gibbonFinanceInvoiceeID == '') {
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
            $checkCount = 0;
            if ($highestAction == 'Update Finance Data_any') {
                $URLSuccess = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Data Updater/data_finance.php&gibbonFinanceInvoiceeID='.$gibbonFinanceInvoiceeID;
                
                
                    $dataSelect = array('gibbonFinanceInvoiceeID' => $gibbonFinanceInvoiceeID);
                    $sqlSelect = "SELECT surname, preferredName, gibbonPerson.gibbonPersonID, gibbonFinanceInvoicee.* FROM gibbonFinanceInvoicee JOIN gibbonPerson ON (gibbonFinanceInvoicee.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE status='Full' AND gibbonFinanceInvoiceeID=:gibbonFinanceInvoiceeID ORDER BY surname, preferredName";
                    $resultSelect = $connection2->prepare($sqlSelect);
                    $resultSelect->execute($dataSelect);
                $checkCount = $resultSelect->rowCount();
                $values = $resultSelect->fetch();
            } else {
                $URLSuccess = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Data Updater/data_updates.php&gibbonFinanceInvoiceeID='.$gibbonFinanceInvoiceeID;
                
                
                    $dataCheck = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                    $sqlCheck = "SELECT gibbonFamilyAdult.gibbonFamilyID, name FROM gibbonFamilyAdult JOIN gibbonFamily ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) WHERE gibbonPersonID=:gibbonPersonID AND childDataAccess='Y' ORDER BY name";
                    $resultCheck = $connection2->prepare($sqlCheck);
                    $resultCheck->execute($dataCheck);
                while ($rowCheck = $resultCheck->fetch()) {
                    
                        $dataCheck2 = array('gibbonFamilyID' => $rowCheck['gibbonFamilyID']);
                        $sqlCheck2 = "SELECT surname, preferredName, gibbonPerson.gibbonPersonID, gibbonFamilyID, gibbonFinanceInvoicee.* FROM gibbonFamilyChild JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonFinanceInvoicee ON (gibbonFinanceInvoicee.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Full' AND gibbonFamilyID=:gibbonFamilyID";
                        $resultCheck2 = $connection2->prepare($sqlCheck2);
                        $resultCheck2->execute($dataCheck2);
                    while ($rowCheck2 = $resultCheck2->fetch()) {
                        if ($gibbonFinanceInvoiceeID == $rowCheck2['gibbonFinanceInvoiceeID']) {
                            ++$checkCount;
                            $values = $rowCheck2;
                        }
                    }
                }
            }

            if ($checkCount < 1) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
            } else {
                //Proceed!
                $invoiceTo = $_POST['invoiceTo'] ?? '';
                if ($invoiceTo == 'Company') {
                    $data = [
                        'invoiceTo' => $invoiceTo,
                        'companyName' => $_POST['companyName'] ?? '',
                        'companyContact' => $_POST['companyContact'] ?? '',
                        'companyAddress' => $_POST['companyAddress'] ?? '',
                        'companyEmail' => $_POST['companyEmail'] ?? '',
                        'companyCCFamily' => $_POST['companyCCFamily'] ?? '',
                        'companyPhone' => $_POST['companyPhone'] ?? '',
                        'companyAll' => $_POST['companyAll'] ?? '',
                        'gibbonFinanceFeeCategoryIDList' => $_POST['gibbonFinanceFeeCategoryIDList'] ?? '',
                    ];

                    if ($data['companyAll'] == 'N') {
                        $data['gibbonFinanceFeeCategoryIDList'] = is_array($data['gibbonFinanceFeeCategoryIDList'])
                            ? implode(',', $data['gibbonFinanceFeeCategoryIDList'])
                            : $data['gibbonFinanceFeeCategoryIDList'];
                    }
                } else {
                    $data = [
                        'invoiceTo' => $invoiceTo,
                        'companyName' => '',
                        'companyContact' => '',
                        'companyAddress' => '',
                        'companyEmail' => '',
                        'companyCCFamily' => '',
                        'companyPhone' => '',
                        'companyAll' => '',
                        'gibbonFinanceFeeCategoryIDList' => '',
                    ];
                }

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
                $data['gibbonSchoolYearID'] = $_SESSION[$guid]['gibbonSchoolYearID'];
                $data['gibbonPersonIDUpdater'] = $_SESSION[$guid]['gibbonPersonID'];
                $data['timestamp'] = date('Y-m-d H:i:s');

                //Write to database
                $existing = $_POST['existing'];

                if ($existing != 'N') {
                    $data['gibbonFinanceInvoiceeUpdateID'] = $existing;
                    $sql = 'UPDATE gibbonFinanceInvoiceeUpdate SET `status`=:status, gibbonSchoolYearID=:gibbonSchoolYearID, invoiceTo=:invoiceTo, companyName=:companyName, companyContact=:companyContact, companyAddress=:companyAddress, companyEmail=:companyEmail, companyCCFamily=:companyCCFamily, companyPhone=:companyPhone, companyAll=:companyAll, gibbonFinanceFeeCategoryIDList=:gibbonFinanceFeeCategoryIDList, gibbonPersonIDUpdater=:gibbonPersonIDUpdater, timestamp=:timestamp WHERE gibbonFinanceInvoiceeUpdateID=:gibbonFinanceInvoiceeUpdateID';
                } else {
                    $data['gibbonFinanceInvoiceeID'] = $gibbonFinanceInvoiceeID;
                    $sql = 'INSERT INTO gibbonFinanceInvoiceeUpdate SET `status`=:status, gibbonSchoolYearID=:gibbonSchoolYearID, gibbonFinanceInvoiceeID=:gibbonFinanceInvoiceeID, invoiceTo=:invoiceTo, companyName=:companyName, companyContact=:companyContact, companyAddress=:companyAddress, companyEmail=:companyEmail, companyCCFamily=:companyCCFamily, companyPhone=:companyPhone, companyAll=:companyAll, gibbonFinanceFeeCategoryIDList=:gibbonFinanceFeeCategoryIDList, gibbonPersonIDUpdater=:gibbonPersonIDUpdater, timestamp=:timestamp';
                }
                $pdo->statement($sql, $data);

                if ($dataChanged) {
                    // Raise a new notification event
                    $event = new NotificationEvent('Data Updater', 'Finance Data Updates');

                    $event->addRecipient($_SESSION[$guid]['organisationDBA']);
                    $event->setNotificationText(__('A finance data update request has been submitted.'));
                    $event->setActionLink('/index.php?q=/modules/Data Updater/data_finance_manage.php');

                    $event->sendNotifications($pdo, $gibbon->session);
                }


                $URLSuccess .= '&return=success0';
                header("Location: {$URLSuccess}");
            }
        }
    }
}
