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
use Gibbon\Services\Format;

include '../../gibbon.php';

//Module includes for User Admin (for custom fields)
include '../User Admin/moduleFunctions.php';

$gibbonPersonID = $_GET['gibbonPersonID'];
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/data_personal.php&gibbonPersonID=$gibbonPersonID";

if (isActionAccessible($guid, $connection2, '/modules/Data Updater/data_personal.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    //Check if school year specified
    if ($gibbonPersonID == '') {
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
            $self = false;
            if ($highestAction == 'Update Personal Data_any') {
                $URLSuccess = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Data Updater/data_personal.php&gibbonPersonID='.$gibbonPersonID;
                
                
                    $dataSelect = array('gibbonPersonID' => $gibbonPersonID);
                    $sqlSelect = "SELECT surname, preferredName, gibbonPerson.gibbonPersonID, gibbonRoleIDAll FROM gibbonPerson WHERE status='Full' AND gibbonPersonID=:gibbonPersonID ORDER BY surname, preferredName";
                    $resultSelect = $connection2->prepare($sqlSelect);
                    $resultSelect->execute($dataSelect);
                $checkCount = $resultSelect->rowCount();
                $self = true;
            } else {
                $URLSuccess = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Data Updater/data_updates.php&gibbonPersonID='.$gibbonPersonID;
                
                try {
                    $dataCheck = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                    $sqlCheck = "SELECT gibbonFamilyAdult.gibbonFamilyID, name FROM gibbonFamilyAdult JOIN gibbonFamily ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) WHERE gibbonPersonID=:gibbonPersonID AND childDataAccess='Y' ORDER BY name";
                    $resultCheck = $connection2->prepare($sqlCheck);
                    $resultCheck->execute($dataCheck);
                } catch (PDOException $e) {
                    echo $e->getMessage();
                }
                while ($rowCheck = $resultCheck->fetch()) {
                    
                        $dataCheck2 = array('gibbonFamilyID1' => $rowCheck['gibbonFamilyID'], 'gibbonFamilyID2' => $rowCheck['gibbonFamilyID']);
                        $sqlCheck2 = "(SELECT surname, preferredName, gibbonPerson.gibbonPersonID, gibbonFamilyID, gibbonRoleIDAll FROM gibbonFamilyChild JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Full' AND gibbonFamilyID=:gibbonFamilyID1) UNION (SELECT surname, preferredName, gibbonPerson.gibbonPersonID, gibbonFamilyID, gibbonRoleIDAll FROM gibbonFamilyAdult JOIN gibbonPerson ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Full' AND gibbonFamilyID=:gibbonFamilyID2)";
                        $resultCheck2 = $connection2->prepare($sqlCheck2);
                        $resultCheck2->execute($dataCheck2);
                    while ($rowCheck2 = $resultCheck2->fetch()) {
                        if ($gibbonPersonID == $rowCheck2['gibbonPersonID']) {
                            ++$checkCount;
                        }
                        //Check for self
                        if ($rowCheck2['gibbonPersonID'] == $_SESSION[$guid]['gibbonPersonID']) {
                            $self = true;
                        }
                    }
                }
            }

            if ($self == false and $gibbonPersonID == $_SESSION[$guid]['gibbonPersonID']) {
                ++$checkCount;
            }

            if ($checkCount < 1) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
            } else {
                //Get user data
                try {
                    $data = array('gibbonPersonID' => $gibbonPersonID);
                    $sql = "SELECT * FROM gibbonPerson WHERE status='Full' AND gibbonPersonID=:gibbonPersonID ORDER BY surname, preferredName";
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
                    exit();
                } else {
                    $values = $result->fetch();

                    //Get categories
                    $staff = $student = $parent = $other = false;
                    $roles = explode(',', $values['gibbonRoleIDAll']);
                    foreach ($roles as $role) {
                        $roleCategory = getRoleCategory($role, $connection2);
                        $staff = $staff || ($roleCategory == 'Staff');
                        $student = $student || ($roleCategory == 'Student');
                        $parent = $parent || ($roleCategory == 'Parent');
                        $other = $other || ($roleCategory == 'Other');
                    }

                    //Proceed!
                    $data = [
                        'gibbonPersonID'             => $gibbonPersonID,
                        'title'                      => $_POST['title'] ?? $values['title'],
                        'surname'                    => $_POST['surname'] ?? $values['surname'],
                        'firstName'                  => $_POST['firstName'] ?? $values['firstName'],
                        'preferredName'              => $_POST['preferredName'] ?? $values['preferredName'],
                        'officialName'               => $_POST['officialName'] ?? $values['officialName'],
                        'nameInCharacters'           => $_POST['nameInCharacters'] ?? $values['nameInCharacters'],
                        'dob'                        => isset($_POST['dob']) ? Format::dateConvert($_POST['dob']) : $values['dob'],
                        'email'                      => $_POST['email'] ?? $values['email'],
                        'emailAlternate'             => $_POST['emailAlternate'] ?? $values['emailAlternate'],
                        'address1'                   => $_POST['address1'] ?? $values['address1'],
                        'address1District'           => $_POST['address1District'] ?? $values['address1District'],
                        'address1Country'            => $_POST['address1Country'] ?? $values['address1Country'],
                        'address2'                   => $_POST['address2'] ?? $values['address2'],
                        'address2District'           => $_POST['address2District'] ?? $values['address2District'],
                        'address2Country'            => $_POST['address2Country'] ?? $values['address2Country'],
                        'phone1Type'                 => $_POST['phone1Type'] ?? $values['phone1Type'],
                        'phone1CountryCode'          => $_POST['phone1CountryCode'] ?? $values['phone1CountryCode'],
                        'phone1'                     => $_POST['phone1'] ?? $values['phone1'],
                        'phone2Type'                 => $_POST['phone2Type'] ?? $values['phone2Type'],
                        'phone2CountryCode'          => $_POST['phone2CountryCode'] ?? $values['phone2CountryCode'],
                        'phone2'                     => $_POST['phone2'] ?? $values['phone2'],
                        'phone3Type'                 => $_POST['phone3Type'] ?? $values['phone3Type'],
                        'phone3CountryCode'          => $_POST['phone3CountryCode'] ?? $values['phone3CountryCode'],
                        'phone3'                     => $_POST['phone3'] ?? $values['phone3'],
                        'phone4Type'                 => $_POST['phone4Type'] ?? $values['phone4Type'],
                        'phone4CountryCode'          => $_POST['phone4CountryCode'] ?? $values['phone4CountryCode'],
                        'phone4'                     => $_POST['phone4'] ?? $values['phone4'],
                        'languageFirst'              => $_POST['languageFirst'] ?? $values['languageFirst'],
                        'languageSecond'             => $_POST['languageSecond'] ?? $values['languageSecond'],
                        'languageThird'              => $_POST['languageThird'] ?? $values['languageThird'],
                        'countryOfBirth'             => $_POST['countryOfBirth'] ?? $values['countryOfBirth'],
                        'ethnicity'                  => $_POST['ethnicity'] ?? $values['ethnicity'],
                        'citizenship1'               => $_POST['citizenship1'] ?? $values['citizenship1'],
                        'citizenship1Passport'       => $_POST['citizenship1Passport'] ?? $values['citizenship1Passport'],
                        'citizenship1PassportExpiry' => !empty($_POST['citizenship1PassportExpiry']) ? Format::dateConvert($_POST['citizenship1PassportExpiry']) : $values['citizenship1PassportExpiry'],
                        'citizenship2'               => $_POST['citizenship2'] ?? $values['citizenship2'],
                        'citizenship2Passport'       => $_POST['citizenship2Passport'] ?? $values['citizenship2Passport'],
                        'citizenship2PassportExpiry' => !empty($_POST['citizenship2PassportExpiry']) ? Format::dateConvert($_POST['citizenship2PassportExpiry']) : $values['citizenship2PassportExpiry'],
                        'religion'                   => $_POST['religion'] ?? $values['religion'],
                        'nationalIDCardNumber'       => $_POST['nationalIDCardNumber'] ?? $values['nationalIDCardNumber'],
                        'residencyStatus'            => $_POST['residencyStatus'] ?? $values['residencyStatus'],
                        'visaExpiryDate'             => isset($_POST['visaExpiryDate']) ? Format::dateConvert($_POST['visaExpiryDate']) : $values['visaExpiryDate'],
                        'emergency1Name'             => $_POST['emergency1Name'] ?? $values['emergency1Name'],
                        'emergency1Number1'          => $_POST['emergency1Number1'] ?? $values['emergency1Number1'],
                        'emergency1Number2'          => $_POST['emergency1Number2'] ?? $values['emergency1Number2'],
                        'emergency1Relationship'     => $_POST['emergency1Relationship'] ?? $values['emergency1Relationship'],
                        'emergency2Name'             => $_POST['emergency2Name'] ?? $values['emergency2Name'],
                        'emergency2Number1'          => $_POST['emergency2Number1'] ?? $values['emergency2Number1'],
                        'emergency2Number2'          => $_POST['emergency2Number2'] ?? $values['emergency2Number2'],
                        'emergency2Relationship'     => $_POST['emergency2Relationship'] ?? $values['emergency2Relationship'],
                        'profession'                 => $_POST['profession'] ?? $values['profession'],
                        'employer'                   => $_POST['employer'] ?? $values['employer'],
                        'jobTitle'                   => $_POST['jobTitle'] ?? $values['jobTitle'],
                        'vehicleRegistration'        => $_POST['vehicleRegistration'] ?? $values['vehicleRegistration'],
                    ];
 
                    $data = array_map('trim', $data);

                    // Date handling - ensure NULL value
                    if (empty($data['dob'])) $data['dob'] = null;
                    if (empty($data['visaExpiryDate'])) $data['visaExpiryDate'] = null;
                    if (empty($data['citizenship1PassportExpiry']) || $data['citizenship1PassportExpiry'] == '0000-00-00') $data['citizenship1PassportExpiry'] = null;
                    if (empty($data['citizenship2PassportExpiry']) || $data['citizenship2PassportExpiry'] == '0000-00-00') $data['citizenship2PassportExpiry'] = null;

                    // Matching addresses
                    $matchAddressCount = $_POST['matchAddressCount'] ?? 0;

                    // Phone number filtering
                    for ($i = 1; $i <= 4; $i++) {
                        $data["phone{$i}"] = preg_replace('/[^0-9+]/', '', $data["phone{$i}"]);
                        if (!empty($data["phone{$i}"]) && empty($data["phone{$i}Type"])) {
                            $data["phone{$i}Type"] = 'Other';
                        }
                    }
                    
                    // Student privacy settings
                    $data['privacy'] = isset($_POST['privacyOptions']) && is_array($_POST['privacyOptions'])
                        ? implode(', ', $_POST['privacyOptions'])
                        : null;

                    // COMPARE VALUES: Has the data changed?
                    $dataChanged = $matchAddressCount > 0 ? true : false;
                    foreach ($values as $key => $value) {
                        if (!isset($data[$key])) continue; // Skip fields we don't plan to update

                        if ($data[$key] != $value) {
                            $dataChanged = true;
                        }
                    }

                    //DEAL WITH CUSTOM FIELDS
                    //Prepare field values
                    $customRequireFail = false;
                    $existingFields = json_decode($values['fields'], true);
                    $resultFields = getCustomFields($connection2, $guid, $student, $staff, $parent, $other, null, true);
                    $fields = [];
                    if ($resultFields->rowCount() > 0) {
                        while ($rowFields = $resultFields->fetch()) {
                            $fieldID = $rowFields['gibbonPersonFieldID'];
                            $fieldValue = $_POST['custom'.$fieldID] ?? null;
                            $existingValue = $existingFields[$fieldID] ?? null;

                            if ($existingValue != $fieldValue) {
                                $dataChanged = true;
                            }

                            if (!is_null($fieldValue)) {
                                $fields[$fieldID] = ($rowFields['type'] == 'date')
                                    ? Format::dateConvert($fieldValue)
                                    : $fieldValue;
                            }
                            if ($highestAction != 'Update Personal Data_any') {
                                if ($rowFields['required'] == 'Y' && empty($fieldValue)) {
                                    $customRequireFail = true;
                                }
                            }
                        }
                    }
                    if ($customRequireFail) {
                        $URL .= '&return=error1';
                        header("Location: {$URL}");
                    } else {
                        $data['fields'] = json_encode($fields);

                        //Write to database
                        $existing = $_POST['existing'] ?? 'N';

                        // Auto-accept updates where no data had changed
                        $data['status'] = $dataChanged ? 'Pending' : 'Complete';
                        $data['gibbonSchoolYearID'] = $_SESSION[$guid]['gibbonSchoolYearID'];
                        $data['gibbonPersonIDUpdater'] = $_SESSION[$guid]['gibbonPersonID'];
                        $data['timestamp'] = date('Y-m-d H:i:s');


                        if ($existing != 'N') {
                            $data['gibbonPersonUpdateID'] = $existing;
                            $sql = 'UPDATE gibbonPersonUpdate SET `status`=:status, gibbonSchoolYearID=:gibbonSchoolYearID, gibbonPersonID=:gibbonPersonID, title=:title, surname=:surname, firstName=:firstName, preferredName=:preferredName, officialName=:officialName, nameInCharacters=:nameInCharacters, dob=:dob, email=:email, emailAlternate=:emailAlternate, address1=:address1, address1District=:address1District, address1Country=:address1Country, address2=:address2, address2District=:address2District, address2Country=:address2Country, phone1Type=:phone1Type, phone1CountryCode=:phone1CountryCode, phone1=:phone1, phone2Type=:phone2Type, phone2CountryCode=:phone2CountryCode, phone2=:phone2, phone3Type=:phone3Type, phone3CountryCode=:phone3CountryCode, phone3=:phone3, phone4Type=:phone4Type, phone4CountryCode=:phone4CountryCode, phone4=:phone4, languageFirst=:languageFirst, languageSecond=:languageSecond, languageThird=:languageThird, countryOfBirth=:countryOfBirth, ethnicity=:ethnicity, citizenship1=:citizenship1, citizenship1Passport=:citizenship1Passport, citizenship1PassportExpiry=:citizenship1PassportExpiry, citizenship2=:citizenship2, citizenship2Passport=:citizenship2Passport, citizenship2PassportExpiry=:citizenship2PassportExpiry, religion=:religion, nationalIDCardNumber=:nationalIDCardNumber, residencyStatus=:residencyStatus, visaExpiryDate=:visaExpiryDate, emergency1Name=:emergency1Name, emergency1Number1=:emergency1Number1, emergency1Number2=:emergency1Number2, emergency1Relationship=:emergency1Relationship, emergency2Name=:emergency2Name, emergency2Number1=:emergency2Number1, emergency2Number2=:emergency2Number2, emergency2Relationship=:emergency2Relationship, profession=:profession, employer=:employer, jobTitle=:jobTitle, vehicleRegistration=:vehicleRegistration, gibbonPersonIDUpdater=:gibbonPersonIDUpdater, privacy=:privacy, fields=:fields, timestamp=:timestamp WHERE gibbonPersonUpdateID=:gibbonPersonUpdateID';
                        } else {
                            $sql = 'INSERT INTO gibbonPersonUpdate SET `status`=:status, gibbonSchoolYearID=:gibbonSchoolYearID, gibbonPersonID=:gibbonPersonID, title=:title, surname=:surname, firstName=:firstName, preferredName=:preferredName, officialName=:officialName, nameInCharacters=:nameInCharacters, dob=:dob, email=:email, emailAlternate=:emailAlternate, address1=:address1, address1District=:address1District, address1Country=:address1Country, address2=:address2, address2District=:address2District, address2Country=:address2Country, phone1Type=:phone1Type, phone1CountryCode=:phone1CountryCode, phone1=:phone1, phone2Type=:phone2Type, phone2CountryCode=:phone2CountryCode, phone2=:phone2, phone3Type=:phone3Type, phone3CountryCode=:phone3CountryCode, phone3=:phone3, phone4Type=:phone4Type, phone4CountryCode=:phone4CountryCode, phone4=:phone4, languageFirst=:languageFirst, languageSecond=:languageSecond, languageThird=:languageThird, countryOfBirth=:countryOfBirth, ethnicity=:ethnicity, citizenship1=:citizenship1, citizenship1Passport=:citizenship1Passport, citizenship1PassportExpiry=:citizenship1PassportExpiry, citizenship2=:citizenship2, citizenship2Passport=:citizenship2Passport, citizenship2PassportExpiry=:citizenship2PassportExpiry, religion=:religion, nationalIDCardNumber=:nationalIDCardNumber, residencyStatus=:residencyStatus, visaExpiryDate=:visaExpiryDate, emergency1Name=:emergency1Name, emergency1Number1=:emergency1Number1, emergency1Number2=:emergency1Number2, emergency1Relationship=:emergency1Relationship, emergency2Name=:emergency2Name, emergency2Number1=:emergency2Number1, emergency2Number2=:emergency2Number2, emergency2Relationship=:emergency2Relationship, profession=:profession, employer=:employer, jobTitle=:jobTitle, vehicleRegistration=:vehicleRegistration, gibbonPersonIDUpdater=:gibbonPersonIDUpdater, privacy=:privacy, fields=:fields, timestamp=:timestamp';
                        }
                        $pdo->statement($sql, $data);


                        //Update matching addresses
                        $partialFail = false;

                        if ($matchAddressCount > 0) {
                            for ($i = 0; $i < $matchAddressCount; ++$i) {
                                if (!empty($_POST[$i.'-matchAddress'])) {
                                    $sqlAddress = '';
                                    try {
                                        $dataCheck = array('gibbonPersonID' => $_POST[$i.'-matchAddress'], 'gibbonPersonIDUpdater' => $_SESSION[$guid]['gibbonPersonID']);
                                        $sqlCheck = "SELECT * FROM gibbonPersonUpdate WHERE gibbonPersonID=:gibbonPersonID AND gibbonPersonIDUpdater=:gibbonPersonIDUpdater AND status='Pending'";
                                        $resultCheck = $connection2->prepare($sqlCheck);
                                        $resultCheck->execute($dataCheck);
                                    } catch (PDOException $e) {
                                        $partialFail = true;
                                    }

                                    if ($resultCheck->rowCount() > 1) {
                                        $partialFail = true;
                                    } elseif ($resultCheck->rowCount() == 1) {
                                        $rowCheck = $resultCheck->fetch();
                                        $dataAddress = array('gibbonPersonID' => $_POST[$i.'-matchAddress'], 'address1' => $address1, 'address1District' => $address1District, 'address1Country' => $address1Country, 'gibbonPersonIDUpdater' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonPersonUpdateID' => $rowCheck['gibbonPersonUpdateID']);
                                        $sqlAddress = 'UPDATE gibbonPersonUpdate SET gibbonPersonID=:gibbonPersonID, address1=:address1, address1District=:address1District, address1Country=:address1Country, gibbonPersonIDUpdater=:gibbonPersonIDUpdater WHERE gibbonPersonUpdateID=:gibbonPersonUpdateID';
                                    } else {
                                        $dataAddress = array('gibbonPersonID' => $_POST[$i.'-matchAddress'], 'address1' => $address1, 'address1District' => $address1District, 'address1Country' => $address1Country, 'gibbonPersonIDUpdater' => $_SESSION[$guid]['gibbonPersonID']);
                                        $sqlAddress = 'INSERT INTO gibbonPersonUpdate SET gibbonPersonID=:gibbonPersonID, address1=:address1, address1District=:address1District, address1Country=:address1Country, gibbonPersonIDUpdater=:gibbonPersonIDUpdater';
                                    }
                                    if ($sqlAddress != '') {
                                        try {
                                            $resultAddress = $connection2->prepare($sqlAddress);
                                            $resultAddress->execute($dataAddress);
                                        } catch (PDOException $e) {
                                            $partialFail = true;
                                        }
                                    }
                                }
                            }
                        }

                        if ($dataChanged) {
                            // Raise a new notification event
                            $event = new NotificationEvent('Data Updater', 'Personal Data Updates');

                            $event->addRecipient($_SESSION[$guid]['organisationDBA']);
                            $event->setNotificationText(__('A personal data update request has been submitted.'));
                            $event->setActionLink('/index.php?q=/modules/Data Updater/data_personal_manage.php');

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
    }
}
