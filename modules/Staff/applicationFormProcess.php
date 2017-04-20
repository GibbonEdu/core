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
require '../../lib/PHPMailer/PHPMailerAutoload.php';

//New PDO DB connection
$pdo = new Gibbon\sqlConnection();
$connection2 = $pdo->getConnection();

@session_start();

//Check to see if system settings are set from databases
if (empty($_SESSION[$guid]['systemSettingsSet'])) {
    getSystemSettings($guid, $connection2);
}

//Module includes from User Admin (for custom fields)
include '../User Admin/moduleFunctions.php';

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Staff/applicationForm.php';

$proceed = false;
$public = false;
if (isset($_SESSION[$guid]['username']) == false) {
    $public = true;
    //Get public access
    $access = getSettingByScope($connection2, 'Staff Application Form', 'staffApplicationFormPublicApplications');
    if ($access == 'Y') {
        $proceed = true;
    }
} else {
    if (isActionAccessible($guid, $connection2, '/modules/Staff/applicationForm.php') != false) {
        $proceed = true;
    }
}

if ($proceed == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    $gibbonStaffJobOpeningIDs = $_POST['gibbonStaffJobOpeningID'];
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
        $visaExpiryDate = dateConvert($guid, $visaExpiryDate);
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
    $agreement = null;
    if (isset($_POST['agreement'])) {
        if ($_POST['agreement'] == 'on') {
            $agreement = 'Y';
        } else {
            $agreement = 'N';
        }
    }

    //VALIDATE INPUTS
    if (count($gibbonStaffJobOpeningIDs) < 1 or ($gibbonPersonID == null and ($surname == '' or $firstName == '' or $preferredName == '' or $officialName == '' or $gender == '' or $dob == '' or $languageFirst == '' or $email == '' or $homeAddress == '' or $homeAddressDistrict == '' or $homeAddressCountry == '' or $phone1 == '')) or (isset($_POST['referenceEmail1']) and $referenceEmail1 == '') or (isset($_POST['referenceEmail2']) and $referenceEmail2 == '') or (isset($_POST['agreement']) and $agreement != 'Y')) {
        $URL .= '&return=error1';
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
            $URL .= '&return=error1';
            header("Location: {$URL}");
        } else {
            $fields = serialize($fields);
            $partialFail = false;
            $ids = '';

            //Deal with required documents
            $uploadedDocuments = array();
            $requiredDocuments = getSettingByScope($connection2, 'Staff', 'staffApplicationFormRequiredDocuments');
            if (!empty($requiredDocuments)) {
                $fileCount = 0;
                if (isset($_POST['fileCount'])) {
                    $fileCount = $_POST['fileCount'];
                }
                for ($i = 0; $i < $fileCount; ++$i) {
                    $fileName = $_POST["fileName$i"];
                    $time = time();
                    //Move attached file, if there is one
                    if ($_FILES["file$i"]['tmp_name'] != '') {
                        //Check for folder in uploads based on today's date
                        $path = $_SESSION[$guid]['absolutePath'];
                        if (is_dir($path.'/uploads/'.date('Y', $time).'/'.date('m', $time)) == false) {
                            mkdir($path.'/uploads/'.date('Y', $time).'/'.date('m', $time), 0777, true);
                        }
                        $unique = false;
                        $count = 0;
                        while ($unique == false and $count < 100) {
                            $suffix = randomPassword(16);
                            $attachment = 'uploads/'.date('Y', $time).'/'.date('m', $time)."/Application Document_$suffix".strrchr($_FILES["file$i"]['name'], '.');
                            if (!(file_exists($path.'/'.$attachment))) {
                                $unique = true;
                            }
                            ++$count;
                        }
                        if (!(move_uploaded_file($_FILES["file$i"]['tmp_name'], $path.'/'.$attachment))) {
                            // Make one more attempt at moving the file, using gibbon root path
                            $basePath = str_replace('\\', '/', dirname(__FILE__));
                            $basePath = str_replace('modules/Staff', '', $basePath);
                            $basePath = rtrim($basePath, '/');

                            move_uploaded_file($_FILES["file$i"]['tmp_name'], $basePath.'/'.$attachment);
                        }

                        // Create an array of uploaded files
                        $uploadedDocuments[$fileName] = $attachment;
                    }
                }
            }

            //Submit one copy for each job opening checking
            foreach ($gibbonStaffJobOpeningIDs as $gibbonStaffJobOpeningID) {
                $thisFail = false;

                try {
                    $data = array('gibbonStaffJobOpeningID' => $gibbonStaffJobOpeningID);
                    $sql = 'SELECT gibbonStaffJobOpeningID, jobTitle, type FROM gibbonStaffJobOpening WHERE gibbonStaffJobOpeningID=:gibbonStaffJobOpeningID';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $partialFail = true;
                }
                if ($result->rowCount() != 1) {
                    $partialFail = true;
                } else {
                    $row = $result->fetch();
                    $jobTitle = $row['jobTitle'];
                    $type = $row['type'];

                    //Write to database
                    try {
                        $data = array('gibbonStaffJobOpeningID' => $gibbonStaffJobOpeningID, 'questions' => $questions, 'gibbonPersonID' => $gibbonPersonID, 'surname' => $surname, 'firstName' => $firstName, 'preferredName' => $preferredName, 'officialName' => $officialName, 'nameInCharacters' => $nameInCharacters, 'gender' => $gender, 'dob' => $dob, 'languageFirst' => $languageFirst, 'languageSecond' => $languageSecond, 'languageThird' => $languageThird, 'countryOfBirth' => $countryOfBirth, 'citizenship1' => $citizenship1, 'citizenship1Passport' => $citizenship1Passport, 'nationalIDCardNumber' => $nationalIDCardNumber, 'residencyStatus' => $residencyStatus, 'visaExpiryDate' => $visaExpiryDate, 'email' => $email, 'homeAddress' => $homeAddress, 'homeAddressDistrict' => $homeAddressDistrict, 'homeAddressCountry' => $homeAddressCountry, 'phone1Type' => $phone1Type, 'phone1CountryCode' => $phone1CountryCode, 'phone1' => $phone1, 'referenceEmail1' => $referenceEmail1, 'referenceEmail2' => $referenceEmail2, 'agreement' => $agreement, 'fields' => $fields, 'timestamp' => date('Y-m-d H:i:s'));
                        $sql = 'INSERT INTO gibbonStaffApplicationForm SET gibbonStaffJobOpeningID=:gibbonStaffJobOpeningID, questions=:questions, gibbonPersonID=:gibbonPersonID, surname=:surname, firstName=:firstName, preferredName=:preferredName, officialName=:officialName, nameInCharacters=:nameInCharacters, gender=:gender, dob=:dob, languageFirst=:languageFirst, languageSecond=:languageSecond, languageThird=:languageThird, countryOfBirth=:countryOfBirth, citizenship1=:citizenship1, citizenship1Passport=:citizenship1Passport, nationalIDCardNumber=:nationalIDCardNumber, residencyStatus=:residencyStatus, visaExpiryDate=:visaExpiryDate, email=:email, homeAddress=:homeAddress, homeAddressDistrict=:homeAddressDistrict, homeAddressCountry=:homeAddressCountry, phone1Type=:phone1Type, phone1CountryCode=:phone1CountryCode, phone1=:phone1, referenceEmail1=:referenceEmail1, referenceEmail2=:referenceEmail2, agreement=:agreement, fields=:fields, timestamp=:timestamp';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        echo $e->getMessage();
                        exit();
                        $partialFail = true;
                        $thisFail = true;
                    }

                    if (!$thisFail) {
                        //Last insert ID
                        $AI = str_pad($connection2->lastInsertID(), 7, '0', STR_PAD_LEFT);
                        $ids .= $AI.', ';

                        // Attach required documents
                        if ($requiredDocuments != false && !empty($uploadedDocuments) && is_array($uploadedDocuments)) {
                            foreach ($uploadedDocuments as $fileName => $attachment) {
                                //Write files to database, one for each attachment
                                try {
                                    $dataFile = array('gibbonStaffApplicationFormID' => $AI, 'name' => $fileName, 'path' => $attachment);
                                    $sqlFile = 'INSERT INTO gibbonStaffApplicationFormFile SET gibbonStaffApplicationFormID=:gibbonStaffApplicationFormID, name=:name, path=:path';
                                    $resultFile = $connection2->prepare($sqlFile);
                                    $resultFile->execute($dataFile);
                                } catch (PDOException $e) {
                                }
                            }
                        }

                        //Attempt to notify HR administrator
                        if ($_SESSION[$guid]['organisationHR']) {
                            $notificationText = sprintf(__($guid, 'An application form has been submitted for %1$s.'), formatName('', $preferredName, $surname, 'Student'));
                            setNotification($connection2, $guid, $_SESSION[$guid]['organisationHR'], $notificationText, 'Staff Application Form', "/index.php?q=/modules/Staff/applicationForm_manage_edit.php&gibbonStaffApplicationFormID=$AI&search=");
                        }

                        //Email reference form link to referee
                        $applicationFormRefereeLink = unserialize(getSettingByScope($connection2, 'Staff', 'applicationFormRefereeLink'));
                        if ($applicationFormRefereeLink[$type] != '' and ($referenceEmail1 != '' or $refereeEmail2 != '') and $_SESSION[$guid]['organisationHRName'] != '' and $_SESSION[$guid]['organisationHREmail'] != '') {
                            //Prep message
                            $subject = __($guid, 'Request For Reference');
                            $body = sprintf(__($guid, 'To whom it may concern,%4$sThis email is being sent in relation to the job application of an individual who has nominated you as a referee: %1$s.%4$sIn assessing their application for the post of %5$s at our school, we would like to enlist your help in completing the following reference form: %2$s.<br/><br/>Please feel free to contact me, should you have any questions in regard to this matter.%4$sRegards,%4$s%3$s'), formatName('', $preferredName, $surname, 'Staff', false, true), "<a href='" . $applicationFormRefereeLink[$type] . "' target='_blank'>" . $applicationFormRefereeLink[$type] . "</a>", $_SESSION[$guid]['organisationHRName'], '<br/><br/>', $jobTitle);
                            $body .= "<p style='font-style: italic;'>".sprintf(__($guid, 'Email sent via %1$s at %2$s.'), $_SESSION[$guid]['systemName'], $_SESSION[$guid]['organisationName']).'</p>';
                            $bodyPlain = emailBodyConvert($body);

                            $mail = getGibbonMailer($guid);
                            $mail->IsSMTP();
                            $mail->SetFrom($_SESSION[$guid]['organisationHREmail'], $_SESSION[$guid]['organisationHRName']);
                            if ($referenceEmail1 != '') {
                                $mail->AddBCC($referenceEmail1);
                            }
                            if ($referenceEmail2 != '') {
                                $mail->AddBCC($referenceEmail2);
                            }
                            $mail->CharSet = 'UTF-8';
                            $mail->Encoding = 'base64';
                            $mail->IsHTML(true);
                            $mail->Subject = $subject;
                            $mail->Body = $body;
                            $mail->AltBody = $bodyPlain;

                            $mail->Send();
                        }
                    }
                }
            }

            if ($ids != '') {
                $ids = substr($ids, 0, -2);
            }

            if ($partialFail == true) {
                $URL .= "&add=warning1&id=$ids";
                header("Location: {$URL}");
            } else {
                $URL .= "&return=success0&id=$ids";
                header("Location: {$URL}");
            }
        }
    }
}
