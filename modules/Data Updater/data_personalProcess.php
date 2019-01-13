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
                
                try {
                    $dataSelect = array('gibbonPersonID' => $gibbonPersonID);
                    $sqlSelect = "SELECT surname, preferredName, gibbonPerson.gibbonPersonID, gibbonRoleIDAll FROM gibbonPerson WHERE status='Full' AND gibbonPersonID=:gibbonPersonID ORDER BY surname, preferredName";
                    $resultSelect = $connection2->prepare($sqlSelect);
                    $resultSelect->execute($dataSelect);
                } catch (PDOException $e) {
                }
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
                    try {
                        $dataCheck2 = array('gibbonFamilyID1' => $rowCheck['gibbonFamilyID'], 'gibbonFamilyID2' => $rowCheck['gibbonFamilyID']);
                        $sqlCheck2 = "(SELECT surname, preferredName, gibbonPerson.gibbonPersonID, gibbonFamilyID, gibbonRoleIDAll FROM gibbonFamilyChild JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Full' AND gibbonFamilyID=:gibbonFamilyID1) UNION (SELECT surname, preferredName, gibbonPerson.gibbonPersonID, gibbonFamilyID, gibbonRoleIDAll FROM gibbonFamilyAdult JOIN gibbonPerson ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Full' AND gibbonFamilyID=:gibbonFamilyID2)";
                        $resultCheck2 = $connection2->prepare($sqlCheck2);
                        $resultCheck2->execute($dataCheck2);
                    } catch (PDOException $e) {
                    }
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
                    $row = $result->fetch();

                    //Get categories
                    $staff = false;
                    $student = false;
                    $parent = false;
                    $other = false;
                    $roles = explode(',', $row['gibbonRoleIDAll']);
                    foreach ($roles as $role) {
                        $roleCategory = getRoleCategory($role, $connection2);
                        if ($roleCategory == 'Staff') {
                            $staff = true;
                        }
                        if ($roleCategory == 'Student') {
                            $student = true;
                        }
                        if ($roleCategory == 'Parent') {
                            $parent = true;
                        }
                        if ($roleCategory == 'Other') {
                            $other = true;
                        }
                    }

                    //Proceed!
                    $title = $_POST['title'];
                    $surname = trim($_POST['surname']);
                    $firstName = trim($_POST['firstName']);
                    $preferredName = trim($_POST['preferredName']);
                    $officialName = trim($_POST['officialName']);
                    $nameInCharacters = $_POST['nameInCharacters'];
                    $dob = $_POST['dob'];
                    if ($dob == '') {
                        $dob = null;
                    } else {
                        $dob = dateConvert($guid, $dob);
                    }
                    $email = trim($_POST['email']);
                    $emailAlternate = trim($_POST['emailAlternate']);
                    $address1 = isset($_POST['address1'])? $_POST['address1'] : '';
                    $address1District = isset($_POST['address1District'])? $_POST['address1District'] : '';
                    $address1Country = isset($_POST['address1Country'])? $_POST['address1Country'] : '';
                    $address2 = isset($_POST['address2'])? $_POST['address2'] : '';
                    $address2District = isset($_POST['address2District'])? $_POST['address2District'] : '';
                    $address2Country = isset($_POST['address2Country'])? $_POST['address2Country'] : '';
                    $phone1Type = $_POST['phone1Type'];
                    if ($_POST['phone1'] != '' and $phone1Type == '') {
                        $phone1Type = 'Other';
                    }
                    $phone1CountryCode = $_POST['phone1CountryCode'];
                    $phone1 = preg_replace('/[^0-9+]/', '', $_POST['phone1']);
                    $phone2Type = $_POST['phone2Type'];
                    if ($_POST['phone2'] != '' and $phone2Type == '') {
                        $phone2Type = 'Other';
                    }
                    $phone2CountryCode = $_POST['phone2CountryCode'];
                    $phone2 = preg_replace('/[^0-9+]/', '', $_POST['phone2']);
                    $phone3Type = $_POST['phone3Type'];
                    if ($_POST['phone3'] != '' and $phone3Type == '') {
                        $phone3Type = 'Other';
                    }
                    $phone3CountryCode = $_POST['phone3CountryCode'];
                    $phone3 = preg_replace('/[^0-9+]/', '', $_POST['phone3']);
                    $phone4Type = $_POST['phone4Type'];
                    if ($_POST['phone4'] != '' and $phone4Type == '') {
                        $phone4Type = 'Other';
                    }
                    $phone4CountryCode = $_POST['phone4CountryCode'];
                    $phone4 = preg_replace('/[^0-9+]/', '', $_POST['phone4']);
                    $languageFirst = $_POST['languageFirst'];
                    $languageSecond = $_POST['languageSecond'];
                    $languageThird = $_POST['languageThird'];
                    $countryOfBirth = $_POST['countryOfBirth'];
                    $ethnicity = $_POST['ethnicity'];
                    $citizenship1 = $_POST['citizenship1'];
                    $citizenship1Passport = $_POST['citizenship1Passport'];
                    $citizenship2 = $_POST['citizenship2'];
                    $citizenship2Passport = $_POST['citizenship2Passport'];
                    $religion = $_POST['religion'];
                    $nationalIDCardNumber = $_POST['nationalIDCardNumber'];
                    $residencyStatus = $_POST['residencyStatus'];
                    $visaExpiryDate = $_POST['visaExpiryDate'];
                    if ($visaExpiryDate == '') {
                        $visaExpiryDate = null;
                    } else {
                        $visaExpiryDate = dateConvert($guid, $visaExpiryDate);
                    }
                    $profession = null;
                    if (isset($_POST['profession'])) {
                        $profession = $_POST['profession'];
                    }
                    $employer = null;
                    if (isset($_POST['employer'])) {
                        $employer = $_POST['employer'];
                    }
                    $jobTitle = null;
                    if (isset($_POST['jobTitle'])) {
                        $jobTitle = $_POST['jobTitle'];
                    }
                    $emergency1Name = null;
                    if (isset($_POST['emergency1Name'])) {
                        $emergency1Name = $_POST['emergency1Name'];
                    }
                    $emergency1Number1 = null;
                    if (isset($_POST['emergency1Number1'])) {
                        $emergency1Number1 = $_POST['emergency1Number1'];
                    }
                    $emergency1Number2 = null;
                    if (isset($_POST['emergency1Number2'])) {
                        $emergency1Number2 = $_POST['emergency1Number2'];
                    }
                    $emergency1Relationship = null;
                    if (isset($_POST['emergency1Relationship'])) {
                        $emergency1Relationship = $_POST['emergency1Relationship'];
                    }
                    $emergency2Name = null;
                    if (isset($_POST['emergency2Name'])) {
                        $emergency2Name = $_POST['emergency2Name'];
                    }
                    $emergency2Number1 = null;
                    if (isset($_POST['emergency2Number1'])) {
                        $emergency2Number1 = $_POST['emergency2Number1'];
                    }
                    $emergency2Number2 = null;
                    if (isset($_POST['emergency2Number2'])) {
                        $emergency2Number2 = $_POST['emergency2Number2'];
                    }
                    $emergency2Relationship = null;
                    if (isset($_POST['emergency2Relationship'])) {
                        $emergency2Relationship = $_POST['emergency2Relationship'];
                    }
                    $vehicleRegistration = $_POST['vehicleRegistration'];
                    $privacy = null;
                    if (isset($_POST['privacyOptions'])) {
                        $privacyOptions = $_POST['privacyOptions'];
                        foreach ($privacyOptions as $privacyOption) {
                            if ($privacyOption != '') {
                                $privacy .= $privacyOption.', ';
                            }
                        }
                        if ($privacy != '') {
                            $privacy = substr($privacy, 0, -2);
                        } else {
                            $privacy = null;
                        }
                    }

                    //DEAL WITH CUSTOM FIELDS
                    //Prepare field values
                    $customRequireFail = false;
                    $resultFields = getCustomFields($connection2, $guid, $student, $staff, $parent, $other, null, true);
                    $fields = array();
                    if ($resultFields->rowCount() > 0) {
                        while ($rowFields = $resultFields->fetch()) {
                            if (isset($_POST['custom'.$rowFields['gibbonPersonFieldID']])) {
                                if ($rowFields['type'] == 'date') {
                                    $fields[$rowFields['gibbonPersonFieldID']] = dateConvert($guid, $_POST['custom'.$rowFields['gibbonPersonFieldID']]);
                                } else {
                                    $fields[$rowFields['gibbonPersonFieldID']] = $_POST['custom'.$rowFields['gibbonPersonFieldID']];
                                }
                            }
                            if ($highestAction != 'Update Personal Data_any' && $rowFields['required'] == 'Y') {
                                if (isset($_POST['custom'.$rowFields['gibbonPersonFieldID']]) == false) {
                                    $customRequireFail = true;
                                } elseif ($_POST['custom'.$rowFields['gibbonPersonFieldID']] == '') {
                                    $customRequireFail = true;
                                }
                            }
                        }
                    }
                    if ($customRequireFail) {
                        $URL .= '&return=error1';
                        header("Location: {$URL}");
                    } else {
                        $fields = serialize($fields);

                        //Write to database
                        $existing = $_POST['existing'];

                        try {
                            if ($existing != 'N') {
                                $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'],  'gibbonPersonID' => $gibbonPersonID, 'title' => $title, 'surname' => $surname, 'firstName' => $firstName, 'preferredName' => $preferredName, 'officialName' => $officialName, 'nameInCharacters' => $nameInCharacters, 'dob' => $dob, 'email' => $email, 'emailAlternate' => $emailAlternate, 'address1' => $address1, 'address1District' => $address1District, 'address1Country' => $address1Country, 'address2' => $address2, 'address2District' => $address2District, 'address2Country' => $address2Country, 'phone1Type' => $phone1Type, 'phone1CountryCode' => $phone1CountryCode, 'phone1' => $phone1, 'phone2Type' => $phone2Type, 'phone2CountryCode' => $phone2CountryCode, 'phone2' => $phone2, 'phone3Type' => $phone3Type, 'phone3CountryCode' => $phone3CountryCode, 'phone3' => $phone3, 'phone4Type' => $phone4Type, 'phone4CountryCode' => $phone4CountryCode, 'phone4' => $phone4, 'languageFirst' => $languageFirst, 'languageSecond' => $languageSecond, 'languageThird' => $languageThird, 'countryOfBirth' => $countryOfBirth, 'ethnicity' => $ethnicity, 'citizenship1' => $citizenship1, 'citizenship1Passport' => $citizenship1Passport, 'citizenship2' => $citizenship2, 'citizenship2Passport' => $citizenship2Passport, 'religion' => $religion, 'nationalIDCardNumber' => $nationalIDCardNumber, 'residencyStatus' => $residencyStatus, 'visaExpiryDate' => $visaExpiryDate, 'emergency1Name' => $emergency1Name, 'emergency1Number1' => $emergency1Number1, 'emergency1Number2' => $emergency1Number2, 'emergency1Relationship' => $emergency1Relationship, 'emergency2Name' => $emergency2Name, 'emergency2Number1' => $emergency2Number1, 'emergency2Number2' => $emergency2Number2, 'emergency2Relationship' => $emergency2Relationship, 'profession' => $profession, 'employer' => $employer, 'jobTitle' => $jobTitle, 'vehicleRegistration' => $vehicleRegistration, 'gibbonPersonIDUpdater' => $_SESSION[$guid]['gibbonPersonID'], 'privacy' => $privacy, 'fields' => $fields, 'gibbonPersonUpdateID' => $existing);
                                $sql = 'UPDATE gibbonPersonUpdate SET gibbonSchoolYearID=:gibbonSchoolYearID, gibbonPersonID=:gibbonPersonID, title=:title, surname=:surname, firstName=:firstName, preferredName=:preferredName, officialName=:officialName, nameInCharacters=:nameInCharacters, dob=:dob, email=:email, emailAlternate=:emailAlternate, address1=:address1, address1District=:address1District, address1Country=:address1Country, address2=:address2, address2District=:address2District, address2Country=:address2Country, phone1Type=:phone1Type, phone1CountryCode=:phone1CountryCode, phone1=:phone1, phone2Type=:phone2Type, phone2CountryCode=:phone2CountryCode, phone2=:phone2, phone3Type=:phone3Type, phone3CountryCode=:phone3CountryCode, phone3=:phone3, phone4Type=:phone4Type, phone4CountryCode=:phone4CountryCode, phone4=:phone4, languageFirst=:languageFirst, languageSecond=:languageSecond, languageThird=:languageThird, countryOfBirth=:countryOfBirth, ethnicity=:ethnicity, citizenship1=:citizenship1, citizenship1Passport=:citizenship1Passport, citizenship2=:citizenship2, citizenship2Passport=:citizenship2Passport, religion=:religion, nationalIDCardNumber=:nationalIDCardNumber, residencyStatus=:residencyStatus, visaExpiryDate=:visaExpiryDate, emergency1Name=:emergency1Name, emergency1Number1=:emergency1Number1, emergency1Number2=:emergency1Number2, emergency1Relationship=:emergency1Relationship, emergency2Name=:emergency2Name, emergency2Number1=:emergency2Number1, emergency2Number2=:emergency2Number2, emergency2Relationship=:emergency2Relationship, profession=:profession, employer=:employer, jobTitle=:jobTitle, vehicleRegistration=:vehicleRegistration, gibbonPersonIDUpdater=:gibbonPersonIDUpdater, privacy=:privacy, fields=:fields, timestamp=NOW() WHERE gibbonPersonUpdateID=:gibbonPersonUpdateID';
                            } else {
                                $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'],  'gibbonPersonID' => $gibbonPersonID, 'title' => $title, 'surname' => $surname, 'firstName' => $firstName, 'preferredName' => $preferredName, 'officialName' => $officialName, 'nameInCharacters' => $nameInCharacters, 'dob' => $dob, 'email' => $email, 'emailAlternate' => $emailAlternate, 'address1' => $address1, 'address1District' => $address1District, 'address1Country' => $address1Country, 'address2' => $address2, 'address2District' => $address2District, 'address2Country' => $address2Country, 'phone1Type' => $phone1Type, 'phone1CountryCode' => $phone1CountryCode, 'phone1' => $phone1, 'phone2Type' => $phone2Type, 'phone2CountryCode' => $phone2CountryCode, 'phone2' => $phone2, 'phone3Type' => $phone3Type, 'phone3CountryCode' => $phone3CountryCode, 'phone3' => $phone3, 'phone4Type' => $phone4Type, 'phone4CountryCode' => $phone4CountryCode, 'phone4' => $phone4, 'languageFirst' => $languageFirst, 'languageSecond' => $languageSecond, 'languageThird' => $languageThird, 'countryOfBirth' => $countryOfBirth, 'ethnicity' => $ethnicity, 'citizenship1' => $citizenship1, 'citizenship1Passport' => $citizenship1Passport, 'citizenship2' => $citizenship2, 'citizenship2Passport' => $citizenship2Passport, 'religion' => $religion, 'nationalIDCardNumber' => $nationalIDCardNumber, 'residencyStatus' => $residencyStatus, 'visaExpiryDate' => $visaExpiryDate, 'emergency1Name' => $emergency1Name, 'emergency1Number1' => $emergency1Number1, 'emergency1Number2' => $emergency1Number2, 'emergency1Relationship' => $emergency1Relationship, 'emergency2Name' => $emergency2Name, 'emergency2Number1' => $emergency2Number1, 'emergency2Number2' => $emergency2Number2, 'emergency2Relationship' => $emergency2Relationship, 'profession' => $profession, 'employer' => $employer, 'jobTitle' => $jobTitle, 'vehicleRegistration' => $vehicleRegistration, 'privacy' => $privacy, 'fields' => $fields, 'gibbonPersonIDUpdater' => $_SESSION[$guid]['gibbonPersonID']);
                                $sql = 'INSERT INTO gibbonPersonUpdate SET gibbonSchoolYearID=:gibbonSchoolYearID, gibbonPersonID=:gibbonPersonID, title=:title, surname=:surname, firstName=:firstName, preferredName=:preferredName, officialName=:officialName, nameInCharacters=:nameInCharacters, dob=:dob, email=:email, emailAlternate=:emailAlternate, address1=:address1, address1District=:address1District, address1Country=:address1Country, address2=:address2, address2District=:address2District, address2Country=:address2Country, phone1Type=:phone1Type, phone1CountryCode=:phone1CountryCode, phone1=:phone1, phone2Type=:phone2Type, phone2CountryCode=:phone2CountryCode, phone2=:phone2, phone3Type=:phone3Type, phone3CountryCode=:phone3CountryCode, phone3=:phone3, phone4Type=:phone4Type, phone4CountryCode=:phone4CountryCode, phone4=:phone4, languageFirst=:languageFirst, languageSecond=:languageSecond, languageThird=:languageThird, countryOfBirth=:countryOfBirth, ethnicity=:ethnicity, citizenship1=:citizenship1, citizenship1Passport=:citizenship1Passport, citizenship2=:citizenship2, citizenship2Passport=:citizenship2Passport, religion=:religion, nationalIDCardNumber=:nationalIDCardNumber, residencyStatus=:residencyStatus, visaExpiryDate=:visaExpiryDate, emergency1Name=:emergency1Name, emergency1Number1=:emergency1Number1, emergency1Number2=:emergency1Number2, emergency1Relationship=:emergency1Relationship, emergency2Name=:emergency2Name, emergency2Number1=:emergency2Number1, emergency2Number2=:emergency2Number2, emergency2Relationship=:emergency2Relationship, profession=:profession, employer=:employer, jobTitle=:jobTitle, vehicleRegistration=:vehicleRegistration, gibbonPersonIDUpdater=:gibbonPersonIDUpdater, privacy=:privacy, fields=:fields';
                            }
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        } catch (PDOException $e) {
                            $URL .= '&return=error2';
                            header("Location: {$URL}");
                            exit();
                        }

                        //Update matching addresses
                        $partialFail = false;
                        $matchAddressCount = 0;
                        if (isset($_POST['matchAddressCount'])) {
                            $matchAddressCount = $_POST['matchAddressCount'];
                        }
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

                        // Raise a new notification event
                        $event = new NotificationEvent('Data Updater', 'Personal Data Updates');

                        $event->addRecipient($_SESSION[$guid]['organisationDBA']);
                        $event->setNotificationText(__('A personal data update request has been submitted.'));
                        $event->setActionLink('/index.php?q=/modules/Data Updater/data_personal_manage.php');

                        $event->sendNotifications($pdo, $gibbon->session);


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
