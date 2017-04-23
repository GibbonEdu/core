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

//Module includes from User Admin (for custom fields)
include '../User Admin/moduleFunctions.php';

$gibbonStaffApplicationFormID = $_POST['gibbonStaffApplicationFormID'];
$search = $_GET['search'];
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/applicationForm_manage_edit.php&gibbonStaffApplicationFormID=$gibbonStaffApplicationFormID&search=$search";

if (isActionAccessible($guid, $connection2, '/modules/Staff/applicationForm_manage_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    //Check if school year specified

    if ($gibbonStaffApplicationFormID == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        try {
            $data = array('gibbonStaffApplicationFormID' => $gibbonStaffApplicationFormID);
            $sql = 'SELECT * FROM gibbonStaffApplicationForm WHERE gibbonStaffApplicationFormID=:gibbonStaffApplicationFormID';
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
            //Proceed!
            //Get student fields
            $priority = $_POST['priority'];
            $status = $_POST['status'];
            $milestones = '';
            $milestonesMaster = explode(',', getSettingByScope($connection2, 'Staff', 'staffApplicationFormMilestones'));
            foreach ($milestonesMaster as $milestoneMaster) {
                if (isset($_POST['milestone_'.preg_replace('/\s+/', '', $milestoneMaster)])) {
                    if ($_POST['milestone_'.preg_replace('/\s+/', '', $milestoneMaster)] == 'on') {
                        $milestones .= trim($milestoneMaster).',';
                    }
                }
            }
            $milestones = substr($milestones, 0, -1);
            $dateStart = null;
            if ($_POST['dateStart'] != '') {
                $dateStart = dateConvert($guid, $_POST['dateStart']);
            }
            $notes = $_POST['notes'];
            $gibbonStaffJobOpeningID = $_POST['gibbonStaffJobOpeningID'];
            $questions = '';
            if (isset($_POST['questions'])) {
                $questions = $_POST['questions'];
            }
            $gibbonPersonID = null;
            if (isset($_POST['gibbonPersonID'])) {
                $gibbonPersonID = $_POST['gibbonPersonID'];
            }
            $surname = null;
            if (isset($_POST['surname'])) {
                $surname = $_POST['surname'];
            }
            $firstName = null;
            if (isset($_POST['firstName'])) {
                $firstName = $_POST['firstName'];
            }
            $preferredName = null;
            if (isset($_POST['preferredName'])) {
                $preferredName = $_POST['preferredName'];
            }
            $officialName = null;
            if (isset($_POST['officialName'])) {
                $officialName = $_POST['officialName'];
            }
            $nameInCharacters = null;
            if (isset($_POST['nameInCharacters'])) {
                $nameInCharacters = $_POST['nameInCharacters'];
            }
            $gender = null;
            if (isset($_POST['gender'])) {
                $gender = $_POST['gender'];
            }
            $dob = null;
            if (isset($_POST['dob'])) {
                $dob = dateConvert($guid, $_POST['dob']);
            }
            $languageFirst = null;
            if (isset($_POST['languageFirst'])) {
                $languageFirst = $_POST['languageFirst'];
            }
            $languageSecond = null;
            if (isset($_POST['languageSecond'])) {
                $languageSecond = $_POST['languageSecond'];
            }
            $languageThird = null;
            if (isset($_POST['languageThird'])) {
                $languageThird = $_POST['languageThird'];
            }
            $countryOfBirth = null;
            if (isset($_POST['countryOfBirth'])) {
                $countryOfBirth = $_POST['countryOfBirth'];
            }
            $citizenship1 = null;
            if (isset($_POST['citizenship1'])) {
                $citizenship1 = $_POST['citizenship1'];
            }
            $citizenship1Passport = null;
            if (isset($_POST['citizenship1Passport'])) {
                $citizenship1Passport = $_POST['citizenship1Passport'];
            }
            $nationalIDCardNumber = null;
            if (isset($_POST['nationalIDCardNumber'])) {
                $nationalIDCardNumber = $_POST['nationalIDCardNumber'];
            }
            $residencyStatus = null;
            if (isset($_POST['residencyStatus'])) {
                $residencyStatus = $_POST['residencyStatus'];
            }
            $visaExpiryDate = null;
            if (isset($_POST['visaExpiryDate']) and $_POST['visaExpiryDate'] != '') {
                $visaExpiryDate = dateConvert($guid, $_POST['visaExpiryDate']);
            }
            $email = null;
            if (isset($_POST['email'])) {
                $email = $_POST['email'];
            }
            $phone1Type = null;
            if (isset($_POST['phone1Type'])) {
                $phone1Type = $_POST['phone1Type'];
                if ($_POST['phone1'] != '' and $phone1Type == '') {
                    $phone1Type = 'Other';
                }
            }
            $phone1CountryCode = null;
            if (isset($_POST['phone1CountryCode'])) {
                $phone1CountryCode = $_POST['phone1CountryCode'];
            }
            $phone1 = null;
            if (isset($_POST['phone1'])) {
                $phone1 = preg_replace('/[^0-9+]/', '', $_POST['phone1']);
            }
            $homeAddress = null;
            if (isset($_POST['homeAddress'])) {
                $homeAddress = $_POST['homeAddress'];
            }
            $homeAddressDistrict = null;
            if (isset($_POST['homeAddressDistrict'])) {
                $homeAddressDistrict = $_POST['homeAddressDistrict'];
            }
            $homeAddressCountry = null;
            if (isset($_POST['homeAddressCountry'])) {
                $homeAddressCountry = $_POST['homeAddressCountry'];
            }
            $referenceEmail1 = '';
            if (isset($_POST['referenceEmail1'])) {
                $referenceEmail1 = $_POST['referenceEmail1'];
            }
            $referenceEmail2 = '';
            if (isset($_POST['referenceEmail2'])) {
                $referenceEmail2 = $_POST['referenceEmail2'];
            }

            if ($gibbonStaffJobOpeningID == '' or ($gibbonPersonID == null and ($surname == '' or $firstName == '' or $preferredName == '' or $officialName == '' or $gender == '' or $dob == '' or $languageFirst == '' or $email == '' or $homeAddress == '' or $homeAddressDistrict == '' or $homeAddressCountry == '' or $phone1 == '')) or (isset($_POST['referenceEmail1']) and $referenceEmail1 == '') or (isset($_POST['referenceEmail2']) and $referenceEmail2 == '')) {
                $URL .= '&return=error3';
                header("Location: {$URL}");
            } else {
                //DEAL WITH CUSTOM FIELDS
                $customRequireFail = false;
                //Prepare field values
                $resultFields = getCustomFields($connection2, $guid, false, true, false, false, true, null);
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
                        if ($rowFields['required'] == 'Y') {
                            if (isset($_POST['custom'.$rowFields['gibbonPersonFieldID']]) == false) {
                                $customRequireFail = true;
                            } elseif ($_POST['custom'.$rowFields['gibbonPersonFieldID']] == '') {
                                $customRequireFail = true;
                            }
                        }
                    }
                }

                if ($customRequireFail) {
                    $URL .= '&return=error3';
                    header("Location: {$URL}");
                } else {
                    $fields = serialize($fields);

                    //Write to database
                    try {
                        $data = array('priority' => $priority, 'status' => $status, 'milestones' => $milestones, 'dateStart' => $dateStart, 'notes' => $notes, 'surname' => $surname, 'firstName' => $firstName, 'preferredName' => $preferredName, 'officialName' => $officialName, 'nameInCharacters' => $nameInCharacters, 'gender' => $gender, 'dob' => $dob, 'languageFirst' => $languageFirst, 'languageSecond' => $languageSecond, 'languageThird' => $languageThird, 'countryOfBirth' => $countryOfBirth, 'citizenship1' => $citizenship1, 'citizenship1Passport' => $citizenship1Passport, 'nationalIDCardNumber' => $nationalIDCardNumber, 'residencyStatus' => $residencyStatus, 'visaExpiryDate' => $visaExpiryDate, 'email' => $email, 'homeAddress' => $homeAddress, 'homeAddressDistrict' => $homeAddressDistrict, 'homeAddressCountry' => $homeAddressCountry, 'phone1Type' => $phone1Type, 'phone1CountryCode' => $phone1CountryCode, 'phone1' => $phone1, 'referenceEmail1' => $referenceEmail1, 'referenceEmail2' => $referenceEmail2, 'fields' => $fields, 'gibbonStaffApplicationFormID' => $gibbonStaffApplicationFormID);
                        $sql = 'UPDATE gibbonStaffApplicationForm SET priority=:priority, status=:status, milestones=:milestones, dateStart=:dateStart, notes=:notes, surname=:surname, firstName=:firstName, preferredName=:preferredName, officialName=:officialName, nameInCharacters=:nameInCharacters, gender=:gender, dob=:dob, languageFirst=:languageFirst, languageSecond=:languageSecond, languageThird=:languageThird, countryOfBirth=:countryOfBirth, citizenship1=:citizenship1, citizenship1Passport=:citizenship1Passport, nationalIDCardNumber=:nationalIDCardNumber, residencyStatus=:residencyStatus, visaExpiryDate=:visaExpiryDate, email=:email, homeAddress=:homeAddress, homeAddressDistrict=:homeAddressDistrict, homeAddressCountry=:homeAddressCountry, phone1Type=:phone1Type, phone1CountryCode=:phone1CountryCode, phone1=:phone1, referenceEmail1=:referenceEmail1, referenceEmail2=:referenceEmail2, fields=:fields WHERE gibbonStaffApplicationFormID=:gibbonStaffApplicationFormID';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $URL .= '&return=error2';
                        header("Location: {$URL}");
                        exit();
                    }

                    $partialFail = false;

                    //Deal with required documents
                    $requiredDocuments = getSettingByScope($connection2, 'Staff', 'staffApplicationFormRequiredDocuments');
                    if ($requiredDocuments != '' and $requiredDocuments != false) {
                        $fileCount = 0;
                        if (isset($_POST['fileCount'])) {
                            $fileCount = $_POST['fileCount'];
                        }

                        $fileUploader = new Gibbon\FileUploader($pdo, $gibbon->session);

                        for ($i = 0; $i < $fileCount; ++$i) {
                            if (empty($_FILES["file$i"]['tmp_name'])) continue;

                            $file = (isset($_FILES["file$i"]))? $_FILES["file$i"] : null;
                            $fileName = (isset($_POST["fileName$i"]))? $_POST["fileName$i"] : null;

                            // Upload the file, return the /uploads relative path
                            $attachment = $fileUploader->uploadFromPost($file, 'ApplicationDocument');

                            // Write files to database, if there is one
                            if (!empty($attachment)) {
                                try {
                                    $dataFile = array('gibbonStaffApplicationFormID' => $gibbonStaffApplicationFormID, 'name' => $fileName, 'path' => $attachment);
                                    $sqlFile = 'INSERT INTO gibbonStaffApplicationFormFile SET gibbonStaffApplicationFormID=:gibbonStaffApplicationFormID, name=:name, path=:path';
                                    $resultFile = $connection2->prepare($sqlFile);
                                    $resultFile->execute($dataFile);
                                } catch (PDOException $e) {
                                }
                            } else {
                                $partialFail = true;
                            }
                        }
                    }

                    if ($partialFail == true) {
                        $URL .= '&return=warning1';
                        header("Location: {$URL}");
                    } else {
                       $URL .= '&return=success0';
                        header("Location: {$URL}");
                    }
                }
            }
        }
    }
}
