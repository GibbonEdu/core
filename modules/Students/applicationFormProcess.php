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

//Module includes from Finance (for setting payment log)
include '../Finance/moduleFunctions.php';

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Students/applicationForm.php';

$proceed = false;
$public = false;

if (isset($_SESSION[$guid]['username']) == false) {
    $public = true;
    //Get public access
    $access = getSettingByScope($connection2, 'Application Form', 'publicApplications');
    if ($access == 'Y') {
        $proceed = true;
    }
} else {
    if (isActionAccessible($guid, $connection2, '/modules/Students/applicationForm.php') != false) {
        $proceed = true;
    }
}

if ($proceed == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    $id = null;
    if (isset($_GET['id'])) {
        $id = $_GET['id'];
    }
    //IF ID IS NOT SET IT IS A NEW APPLICATION, SO PROCESS AND SAVE.
    if (is_null($id)) {
        //Proceed!
        //GET STUDENT FIELDS
        $surname = $_POST['surname'];
        $firstName = trim($_POST['firstName']);
        $preferredName = trim($_POST['preferredName']);
        $officialName = trim($_POST['officialName']);
        $nameInCharacters = $_POST['nameInCharacters'];
        $gender = $_POST['gender'];
        $dob = $_POST['dob'];
        if ($dob == '') {
            $dob = null;
        } else {
            $dob = dateConvert($guid, $dob);
        }
        $languageHomePrimary = $_POST['languageHomePrimary'];
        $languageHomeSecondary = $_POST['languageHomeSecondary'];
        $languageFirst = $_POST['languageFirst'];
        $languageSecond = $_POST['languageSecond'];
        $languageThird = $_POST['languageThird'];
        $countryOfBirth = $_POST['countryOfBirth'];
        $citizenship1 = $_POST['citizenship1'];
        $citizenship1Passport = $_POST['citizenship1Passport'];
        $nationalIDCardNumber = $_POST['nationalIDCardNumber'];
        $residencyStatus = $_POST['residencyStatus'];
        $visaExpiryDate = $_POST['visaExpiryDate'];
        if ($visaExpiryDate == '') {
            $visaExpiryDate = null;
        } else {
            $visaExpiryDate = dateConvert($guid, $visaExpiryDate);
        }
        $email = trim($_POST['email']);
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
        $medicalInformation = $_POST['medicalInformation'];
        $sen = $_POST['sen'];
        if ($sen == 'N') {
            $senDetails = '';
        } else {
            $senDetails = $_POST['senDetails'];
        }
        $gibbonSchoolYearIDEntry = $_POST['gibbonSchoolYearIDEntry'];
        $dayType = null;
        if (isset($_POST['dayType'])) {
            $dayType = $_POST['dayType'];
        }
        $dateStart = dateConvert($guid, $_POST['dateStart']);
        $gibbonYearGroupIDEntry = $_POST['gibbonYearGroupIDEntry'];
        $referenceEmail = null;
        if (isset($_POST['referenceEmail'])) {
            $referenceEmail = $_POST['referenceEmail'];
        }
        $schoolName1 = $_POST['schoolName1'];
        $schoolAddress1 = $_POST['schoolAddress1'];
        $schoolGrades1 = $_POST['schoolGrades1'];
        $schoolLanguage1 = $_POST['schoolLanguage1'];
        $schoolDate1 = $_POST['schoolDate1'];
        if ($schoolDate1 == '') {
            $schoolDate1 = null;
        } else {
            $schoolDate1 = dateConvert($guid, $schoolDate1);
        }
        $schoolName2 = $_POST['schoolName2'];
        $schoolAddress2 = $_POST['schoolAddress2'];
        $schoolGrades2 = $_POST['schoolGrades2'];
        $schoolLanguage2 = $_POST['schoolLanguage2'];
        $schoolDate2 = $_POST['schoolDate2'];
        if ($schoolDate2 == '') {
            $schoolDate2 = null;
        } else {
            $schoolDate2 = dateConvert($guid, $schoolDate2);
        }

        //GET FAMILY FEILDS
        $gibbonFamily = $_POST['gibbonFamily'];
        if ($gibbonFamily == 'TRUE') {
            $gibbonFamilyID = $_POST['gibbonFamilyID'];
        } else {
            $gibbonFamilyID = null;
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

        //GET PARENT1 FEILDS
        $parent1gibbonPersonID = null;
        if (isset($_POST['parent1gibbonPersonID'])) {
            $parent1gibbonPersonID = $_POST['parent1gibbonPersonID'];
        }
        $parent1title = null;
        if (isset($_POST['parent1title'])) {
            $parent1title = $_POST['parent1title'];
        }
        $parent1surname = null;
        if (isset($_POST['parent1surname'])) {
            $parent1surname = trim($_POST['parent1surname']);
        }
        $parent1firstName = null;
        if (isset($_POST['parent1firstName'])) {
            $parent1firstName = trim($_POST['parent1firstName']);
        }
        $parent1preferredName = null;
        if (isset($_POST['parent1preferredName'])) {
            $parent1preferredName = trim($_POST['parent1preferredName']);
        }
        $parent1officialName = null;
        if (isset($_POST['parent1officialName'])) {
            $parent1officialName = trim($_POST['parent1officialName']);
        }
        $parent1nameInCharacters = null;
        if (isset($_POST['parent1nameInCharacters'])) {
            $parent1nameInCharacters = $_POST['parent1nameInCharacters'];
        }
        $parent1gender = null;
        if (isset($_POST['parent1gender'])) {
            $parent1gender = $_POST['parent1gender'];
        }
        $parent1relationship = null;
        if (isset($_POST['parent1relationship'])) {
            $parent1relationship = $_POST['parent1relationship'];
        }
        $parent1languageFirst = null;
        if (isset($_POST['parent1languageFirst'])) {
            $parent1languageFirst = $_POST['parent1languageFirst'];
        }
        $parent1languageSecond = null;
        if (isset($_POST['parent1languageSecond'])) {
            $parent1languageSecond = $_POST['parent1languageSecond'];
        }
        $parent1citizenship1 = null;
        if (isset($_POST['parent1citizenship1'])) {
            $parent1citizenship1 = $_POST['parent1citizenship1'];
        }
        $parent1nationalIDCardNumber = null;
        if (isset($_POST['parent1nationalIDCardNumber'])) {
            $parent1nationalIDCardNumber = $_POST['parent1nationalIDCardNumber'];
        }
        $parent1residencyStatus = null;
        if (isset($_POST['parent1residencyStatus'])) {
            $parent1residencyStatus = $_POST['parent1residencyStatus'];
        }
        $parent1visaExpiryDate = null;
        if (isset($_POST['parent1visaExpiryDate'])) {
            if ($_POST['parent1visaExpiryDate'] != '') {
                $parent1visaExpiryDate = dateConvert($guid, $_POST['parent1visaExpiryDate']);
            }
        }
        $parent1email = null;
        if (isset($_POST['parent1email'])) {
            $parent1email = trim($_POST['parent1email']);
        }
        $parent1phone1Type = null;
        if (isset($_POST['parent1phone1Type'])) {
            $parent1phone1Type = $_POST['parent1phone1Type'];
        }
        if (isset($_POST['parent1phone1']) and $parent1phone1Type == '') {
            $parent1phone1Type = 'Other';
        }
        $parent1phone1CountryCode = null;
        if (isset($_POST['parent1phone1CountryCode'])) {
            $parent1phone1CountryCode = $_POST['parent1phone1CountryCode'];
        }
        $parent1phone1 = null;
        if (isset($_POST['parent1phone1'])) {
            $parent1phone1 = $_POST['parent1phone1'];
        }
        $parent1phone2Type = null;
        if (isset($_POST['parent1phone2Type'])) {
            $parent1phone2Type = $_POST['parent1phone2Type'];
        }
        if (isset($_POST['parent1phone2']) and $parent1phone2Type == '') {
            $parent1phone2Type = 'Other';
        }
        $parent1phone2CountryCode = null;
        if (isset($_POST['parent1phone2CountryCode'])) {
            $parent1phone2CountryCode = $_POST['parent1phone2CountryCode'];
        }
        $parent1phone2 = null;
        if (isset($_POST['parent1phone2'])) {
            $parent1phone2 = $_POST['parent1phone2'];
        }
        $parent1profession = null;
        if (isset($_POST['parent1profession'])) {
            $parent1profession = $_POST['parent1profession'];
        }
        $parent1employer = null;
        if (isset($_POST['parent1employer'])) {
            $parent1employer = $_POST['parent1employer'];
        }

        //GET PARENT2 FEILDS
        $parent2title = null;
        if (isset($_POST['parent2title'])) {
            $parent2title = $_POST['parent2title'];
        }
        $parent2surname = null;
        if (isset($_POST['parent2surname'])) {
            $parent2surname = trim($_POST['parent2surname']);
        }
        $parent2firstName = null;
        if (isset($_POST['parent2firstName'])) {
            $parent2firstName = trim($_POST['parent2firstName']);
        }
        $parent2preferredName = null;
        if (isset($_POST['parent2preferredName'])) {
            $parent2preferredName = trim($_POST['parent2preferredName']);
        }
        $parent2officialName = null;
        if (isset($_POST['parent2officialName'])) {
            $parent2officialName = trim($_POST['parent2officialName']);
        }
        $parent2nameInCharacters = null;
        if (isset($_POST['parent2nameInCharacters'])) {
            $parent2nameInCharacters = $_POST['parent2nameInCharacters'];
        }
        $parent2gender = null;
        if (isset($_POST['parent2gender'])) {
            $parent2gender = $_POST['parent2gender'];
        }
        $parent2relationship = null;
        if (isset($_POST['parent2relationship'])) {
            $parent2relationship = $_POST['parent2relationship'];
        }
        $parent2languageFirst = null;
        if (isset($_POST['parent2languageFirst'])) {
            $parent2languageFirst = $_POST['parent2languageFirst'];
        }
        $parent2languageSecond = null;
        if (isset($_POST['parent2languageSecond'])) {
            $parent2languageSecond = $_POST['parent2languageSecond'];
        }
        $parent2citizenship1 = null;
        if (isset($_POST['parent2citizenship1'])) {
            $parent2citizenship1 = $_POST['parent2citizenship1'];
        }
        $parent2nationalIDCardNumber = null;
        if (isset($_POST['parent2nationalIDCardNumber'])) {
            $parent2nationalIDCardNumber = $_POST['parent2nationalIDCardNumber'];
        }
        $parent2residencyStatus = null;
        if (isset($_POST['parent2residencyStatus'])) {
            $parent2residencyStatus = $_POST['parent2residencyStatus'];
        }
        $parent2visaExpiryDate = null;
        if (isset($_POST['parent2visaExpiryDate'])) {
            if ($_POST['parent2visaExpiryDate'] != '') {
                $parent2visaExpiryDate = dateConvert($guid, $_POST['parent2visaExpiryDate']);
            }
        }
        $parent2email = null;
        if (isset($_POST['parent2email'])) {
            $parent2email = trim($_POST['parent2email']);
        }
        $parent2phone1Type = null;
        if (isset($_POST['parent2phone1Type'])) {
            $parent2phone1Type = $_POST['parent2phone1Type'];
        }
        if (isset($_POST['parent2phone1']) and $parent2phone1Type == '') {
            $parent2phone1Type = 'Other';
        }
        $parent2phone1CountryCode = null;
        if (isset($_POST['parent2phone1CountryCode'])) {
            $parent2phone1CountryCode = $_POST['parent2phone1CountryCode'];
        }
        $parent2phone1 = null;
        if (isset($_POST['parent2phone1'])) {
            $parent2phone1 = $_POST['parent2phone1'];
        }
        $parent2phone2Type = null;
        if (isset($_POST['parent2phone2Type'])) {
            $parent2phone2Type = $_POST['parent2phone2Type'];
        }
        if (isset($_POST['parent2phone2']) and $parent2phone2Type == '') {
            $parent2phone2Type = 'Other';
        }
        $parent2phone2CountryCode = null;
        if (isset($_POST['parent2phone2CountryCode'])) {
            $parent2phone2CountryCode = $_POST['parent2phone2CountryCode'];
        }
        $parent2phone2 = null;
        if (isset($_POST['parent2phone2'])) {
            $parent2phone2 = $_POST['parent2phone2'];
        }
        $parent2profession = null;
        if (isset($_POST['parent2profession'])) {
            $parent2profession = $_POST['parent2profession'];
        }
        $parent2employer = null;
        if (isset($_POST['parent2employer'])) {
            $parent2employer = $_POST['parent2employer'];
        }

        //GET SIBLING FIELDS
        $siblingName1 = $_POST['siblingName1'];
        $siblingDOB1 = $_POST['siblingDOB1'];
        if ($siblingDOB1 == '') {
            $siblingDOB1 = null;
        } else {
            $siblingDOB1 = dateConvert($guid, $siblingDOB1);
        }
        $siblingSchool1 = $_POST['siblingSchool1'];
        $siblingSchoolJoiningDate1 = $_POST['siblingSchoolJoiningDate1'];
        if ($siblingSchoolJoiningDate1 == '') {
            $siblingSchoolJoiningDate1 = null;
        } else {
            $siblingSchoolJoiningDate1 = dateConvert($guid, $siblingSchoolJoiningDate1);
        }
        $siblingName2 = $_POST['siblingName2'];
        $siblingDOB2 = $_POST['siblingDOB2'];
        if ($siblingDOB2 == '') {
            $siblingDOB2 = null;
        } else {
            $siblingDOB2 = dateConvert($guid, $siblingDOB2);
        }
        $siblingSchool2 = $_POST['siblingSchool2'];
        $siblingSchoolJoiningDate2 = $_POST['siblingSchoolJoiningDate2'];
        if ($siblingSchoolJoiningDate2 == '') {
            $siblingSchoolJoiningDate2 = null;
        } else {
            $siblingSchoolJoiningDate2 = dateConvert($guid, $siblingSchoolJoiningDate2);
        }
        $siblingName3 = $_POST['siblingName3'];
        $siblingDOB3 = $_POST['siblingDOB3'];
        if ($siblingDOB3 == '') {
            $siblingDOB3 = null;
        } else {
            $siblingDOB3 = dateConvert($guid, $siblingDOB3);
        }
        $siblingSchool3 = $_POST['siblingSchool3'];
        $siblingSchoolJoiningDate3 = $_POST['siblingSchoolJoiningDate3'];
        if ($siblingSchoolJoiningDate3 == '') {
            $siblingSchoolJoiningDate3 = null;
        } else {
            $siblingSchoolJoiningDate3 = dateConvert($guid, $siblingSchoolJoiningDate3);
        }

        //GET PAYMENT FIELDS
        $payment = $_POST['payment'];
        $companyName = null;
        if (isset($_POST['companyName'])) {
            $companyName = $_POST['companyName'];
        }
        $companyContact = null;
        if (isset($_POST['companyContact'])) {
            $companyContact = $_POST['companyContact'];
        }
        $companyAddress = null;
        if (isset($_POST['companyAddress'])) {
            $companyAddress = $_POST['companyAddress'];
        }
        $companyEmail = null;
        if (isset($_POST['companyEmail'])) {
            $companyEmail = $_POST['companyEmail'];
        }
        $companyCCFamily = null;
        if (isset($_POST['companyCCFamily'])) {
            $companyCCFamily = $_POST['companyCCFamily'];
        }
        $companyPhone = null;
        if (isset($_POST['companyPhone'])) {
            $companyPhone = $_POST['companyPhone'];
        }
        $companyAll = null;
        if (isset($_POST['companyAll'])) {
            $companyAll = $_POST['companyAll'];
        }
        $gibbonFinanceFeeCategoryIDList = null;
        if (isset($_POST['gibbonFinanceFeeCategoryIDList'])) {
            $gibbonFinanceFeeCategoryIDArray = $_POST['gibbonFinanceFeeCategoryIDList'];
            if (count($gibbonFinanceFeeCategoryIDArray) > 0) {
                foreach ($gibbonFinanceFeeCategoryIDArray as $gibbonFinanceFeeCategoryID) {
                    $gibbonFinanceFeeCategoryIDList .= $gibbonFinanceFeeCategoryID.',';
                }
                $gibbonFinanceFeeCategoryIDList = substr($gibbonFinanceFeeCategoryIDList, 0, -1);
            }
        }

        //GET OTHER FIELDS
        $languageChoice = null;
        if (isset($_POST['languageChoice'])) {
            $languageChoice = $_POST['languageChoice'];
        }
        $languageChoiceExperience = null;
        if (isset($_POST['languageChoiceExperience'])) {
            $languageChoiceExperience = $_POST['languageChoiceExperience'];
        }
        $scholarshipInterest = null;
        if (isset($_POST['scholarshipInterest'])) {
            $scholarshipInterest = $_POST['scholarshipInterest'];
        }
        $scholarshipRequired = null;
        if (isset($_POST['scholarshipRequired'])) {
            $scholarshipRequired = $_POST['scholarshipRequired'];
        }
        $howDidYouHear = null;
        if (isset($_POST['howDidYouHear'])) {
            $howDidYouHear = $_POST['howDidYouHear'];
        }
        $howDidYouHearMore = null;
        if (isset($_POST['howDidYouHearMore'])) {
            $howDidYouHearMore = $_POST['howDidYouHearMore'];
        }
        $agreement = null;
        if (isset($_POST['agreement'])) {
            if ($_POST['agreement'] == 'on') {
                $agreement = 'Y';
            } else {
                $agreement = 'N';
            }
        }
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

        //VALIDATE INPUTS
        $familyFail = false;
        if ($gibbonFamily == 'TRUE') {
            if ($gibbonFamilyID == '') {
                $familyFail = true;
            }
        } else {
            if ($homeAddress == '' or $homeAddressDistrict == '' or $homeAddressCountry == '') {
                $familyFail = true;
            }
            if ($parent1gibbonPersonID == null) {
                if ($parent1title == '' or $parent1surname == '' or $parent1firstName == '' or $parent1preferredName == '' or $parent1officialName == '' or $parent1gender == '' or $parent1relationship == '' or $parent1phone1 == '' or $parent1profession == '') {
                    $familyFail = true;
                }
            }
            if (isset($_POST['secondParent'])) {
                if ($_POST['secondParent'] != 'No') {
                    if ($parent2title == '' or $parent2surname == '' or $parent2firstName == '' or $parent2preferredName == '' or $parent2officialName == '' or $parent2gender == '' or $parent2relationship == '' or $parent2phone1 == '' or $parent2profession == '') {
                        $familyFail = true;
                    }
                }
            }
        }
        if ($surname == '' or $firstName == '' or $preferredName == '' or $officialName == '' or $gender == '' or $dob == '' or $languageHomePrimary == '' or $languageFirst == '' or $countryOfBirth == '' or $citizenship1 == '' or $gibbonSchoolYearIDEntry == '' or $dateStart == '' or $gibbonYearGroupIDEntry == '' or $sen == '' or $howDidYouHear == '' or (isset($_POST['agreement']) and $agreement != 'Y') or $familyFail) {
            $URL .= '&return=error1';
            header("Location: {$URL}");
        } else {
            //DEAL WITH CUSTOM FIELDS
            $customRequireFail = false;
            //Prepare field values
            //CHILD
            $resultFields = getCustomFields($connection2, $guid, true, false, false, false, true, null);
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
            if ($gibbonFamily == 'FALSE') { //Only if there is no family
                //PARENT 1
                $resultFields = getCustomFields($connection2, $guid, false, false, true, false, true, null);
                $parent1fields = array();
                if ($resultFields->rowCount() > 0) {
                    while ($rowFields = $resultFields->fetch()) {
                        if (isset($_POST['parent1custom'.$rowFields['gibbonPersonFieldID']])) {
                            if ($rowFields['type'] == 'date') {
                                $parent1fields[$rowFields['gibbonPersonFieldID']] = dateConvert($guid, $_POST['parent1custom'.$rowFields['gibbonPersonFieldID']]);
                            } else {
                                $parent1fields[$rowFields['gibbonPersonFieldID']] = $_POST['parent1custom'.$rowFields['gibbonPersonFieldID']];
                            }
                        }
                        if ($rowFields['required'] == 'Y') {
                            if (isset($_POST['parent1custom'.$rowFields['gibbonPersonFieldID']]) == false) {
                                $customRequireFail = true;
                            } elseif ($_POST['parent1custom'.$rowFields['gibbonPersonFieldID']] == '') {
                                $customRequireFail = true;
                            }
                        }
                    }
                }
                if (isset($_POST['secondParent']) == false) {
                    //PARENT 2
                    $resultFields = getCustomFields($connection2, $guid, false, false, true, false, true, null);
                    $parent2fields = array();
                    if ($resultFields->rowCount() > 0) {
                        while ($rowFields = $resultFields->fetch()) {
                            if (isset($_POST['parent2custom'.$rowFields['gibbonPersonFieldID']])) {
                                if ($rowFields['type'] == 'date') {
                                    $parent2fields[$rowFields['gibbonPersonFieldID']] = dateConvert($guid, $_POST['parent2custom'.$rowFields['gibbonPersonFieldID']]);
                                } else {
                                    $parent2fields[$rowFields['gibbonPersonFieldID']] = $_POST['parent2custom'.$rowFields['gibbonPersonFieldID']];
                                }
                            }
                            if ($rowFields['required'] == 'Y') {
                                if (isset($_POST['parent2custom'.$rowFields['gibbonPersonFieldID']]) == false) {
                                    $customRequireFail = true;
                                } elseif ($_POST['parent2custom'.$rowFields['gibbonPersonFieldID']] == '') {
                                    $customRequireFail = true;
                                }
                            }
                        }
                    }
                }
            }

            if ($customRequireFail) {
                $URL .= '&return=error1';
                header("Location: {$URL}");
            } else {
                $fields = serialize($fields);
                if (isset($parent1fields)) {
                    $parent1fields = serialize($parent1fields);
                } else {
                    $parent1fields = '';
                }
                if (isset($parent2fields)) {
                    $parent2fields = serialize($parent2fields);
                } else {
                    $parent2fields = '';
                }

                //Write to database
                try {
                    $data = array('surname' => $surname, 'firstName' => $firstName, 'preferredName' => $preferredName, 'officialName' => $officialName, 'nameInCharacters' => $nameInCharacters, 'gender' => $gender, 'dob' => $dob, 'languageHomePrimary' => $languageHomePrimary, 'languageHomeSecondary' => $languageHomeSecondary, 'languageFirst' => $languageFirst, 'languageSecond' => $languageSecond, 'languageThird' => $languageThird, 'countryOfBirth' => $countryOfBirth, 'citizenship1' => $citizenship1, 'citizenship1Passport' => $citizenship1Passport, 'nationalIDCardNumber' => $nationalIDCardNumber, 'residencyStatus' => $residencyStatus, 'visaExpiryDate' => $visaExpiryDate, 'email' => $email, 'homeAddress' => $homeAddress, 'homeAddressDistrict' => $homeAddressDistrict, 'homeAddressCountry' => $homeAddressCountry, 'phone1Type' => $phone1Type, 'phone1CountryCode' => $phone1CountryCode, 'phone1' => $phone1, 'phone2Type' => $phone2Type, 'phone2CountryCode' => $phone2CountryCode, 'phone2' => $phone2, 'medicalInformation' => $medicalInformation, 'sen' => $sen, 'senDetails' => $senDetails, 'gibbonSchoolYearIDEntry' => $gibbonSchoolYearIDEntry, 'dayType' => $dayType, 'dateStart' => $dateStart, 'gibbonYearGroupIDEntry' => $gibbonYearGroupIDEntry, 'referenceEmail' => $referenceEmail, 'schoolName1' => $schoolName1, 'schoolAddress1' => $schoolAddress1, 'schoolGrades1' => $schoolGrades1, 'schoolLanguage1' => $schoolLanguage1, 'schoolDate1' => $schoolDate1, 'schoolName2' => $schoolName2, 'schoolAddress2' => $schoolAddress2, 'schoolGrades2' => $schoolGrades2, 'schoolLanguage2' => $schoolLanguage2, 'schoolDate2' => $schoolDate2, 'gibbonFamilyID' => $gibbonFamilyID, 'parent1gibbonPersonID' => $parent1gibbonPersonID, 'parent1title' => $parent1title, 'parent1surname' => $parent1surname, 'parent1firstName' => $parent1firstName, 'parent1preferredName' => $parent1preferredName, 'parent1officialName' => $parent1officialName, 'parent1nameInCharacters' => $parent1nameInCharacters, 'parent1gender' => $parent1gender, 'parent1relationship' => $parent1relationship, 'parent1languageFirst' => $parent1languageFirst, 'parent1languageSecond' => $parent1languageSecond, 'parent1citizenship1' => $parent1citizenship1, 'parent1nationalIDCardNumber' => $parent1nationalIDCardNumber, 'parent1residencyStatus' => $parent1residencyStatus, 'parent1visaExpiryDate' => $parent1visaExpiryDate, 'parent1email' => $parent1email, 'parent1phone1Type' => $parent1phone1Type, 'parent1phone1CountryCode' => $parent1phone1CountryCode, 'parent1phone1' => $parent1phone1, 'parent1phone2Type' => $parent1phone2Type, 'parent1phone2CountryCode' => $parent1phone2CountryCode, 'parent1phone2' => $parent1phone2, 'parent1profession' => $parent1profession, 'parent1employer' => $parent1employer, 'parent2title' => $parent2title, 'parent2surname' => $parent2surname, 'parent2firstName' => $parent2firstName, 'parent2preferredName' => $parent2preferredName, 'parent2officialName' => $parent2officialName, 'parent2nameInCharacters' => $parent2nameInCharacters, 'parent2gender' => $parent2gender, 'parent2relationship' => $parent2relationship, 'parent2languageFirst' => $parent2languageFirst, 'parent2languageSecond' => $parent2languageSecond, 'parent2citizenship1' => $parent2citizenship1, 'parent2nationalIDCardNumber' => $parent2nationalIDCardNumber, 'parent2residencyStatus' => $parent2residencyStatus, 'parent2visaExpiryDate' => $parent2visaExpiryDate, 'parent2email' => $parent2email, 'parent2phone1Type' => $parent2phone1Type, 'parent2phone1CountryCode' => $parent2phone1CountryCode, 'parent2phone1' => $parent2phone1, 'parent2phone2Type' => $parent2phone2Type, 'parent2phone2CountryCode' => $parent2phone2CountryCode, 'parent2phone2' => $parent2phone2, 'parent2profession' => $parent2profession, 'parent2employer' => $parent2employer, 'siblingName1' => $siblingName1, 'siblingDOB1' => $siblingDOB1, 'siblingSchool1' => $siblingSchool1, 'siblingSchoolJoiningDate1' => $siblingSchoolJoiningDate1, 'siblingName2' => $siblingName2, 'siblingDOB2' => $siblingDOB2, 'siblingSchool2' => $siblingSchool2, 'siblingSchoolJoiningDate2' => $siblingSchoolJoiningDate2, 'siblingName3' => $siblingName3, 'siblingDOB3' => $siblingDOB3, 'siblingSchool3' => $siblingSchool3, 'siblingSchoolJoiningDate3' => $siblingSchoolJoiningDate3, 'languageChoice' => $languageChoice, 'languageChoiceExperience' => $languageChoiceExperience, 'scholarshipInterest' => $scholarshipInterest, 'scholarshipRequired' => $scholarshipRequired, 'payment' => $payment, 'companyName' => $companyName, 'companyContact' => $companyContact, 'companyAddress' => $companyAddress, 'companyEmail' => $companyEmail, 'companyCCFamily' => $companyCCFamily, 'companyPhone' => $companyPhone, 'companyAll' => $companyAll, 'gibbonFinanceFeeCategoryIDList' => $gibbonFinanceFeeCategoryIDList, 'howDidYouHear' => $howDidYouHear, 'howDidYouHearMore' => $howDidYouHearMore, 'agreement' => $agreement, 'privacy' => $privacy, 'fields' => $fields, 'parent1fields' => $parent1fields, 'parent2fields' => $parent2fields, 'timestamp' => date('Y-m-d H:i:s'));
                    $sql = 'INSERT INTO gibbonApplicationForm SET surname=:surname, firstName=:firstName, preferredName=:preferredName, officialName=:officialName, nameInCharacters=:nameInCharacters, gender=:gender, dob=:dob, languageHomePrimary=:languageHomePrimary, languageHomeSecondary=:languageHomeSecondary, languageFirst=:languageFirst, languageSecond=:languageSecond, languageThird=:languageThird, countryOfBirth=:countryOfBirth, citizenship1=:citizenship1, citizenship1Passport=:citizenship1Passport, nationalIDCardNumber=:nationalIDCardNumber, residencyStatus=:residencyStatus, visaExpiryDate=:visaExpiryDate, email=:email, homeAddress=:homeAddress, homeAddressDistrict=:homeAddressDistrict, homeAddressCountry=:homeAddressCountry, phone1Type=:phone1Type, phone1CountryCode=:phone1CountryCode, phone1=:phone1, phone2Type=:phone2Type, phone2CountryCode=:phone2CountryCode, phone2=:phone2, medicalInformation=:medicalInformation, sen=:sen, senDetails=:senDetails, gibbonSchoolYearIDEntry=:gibbonSchoolYearIDEntry, dateStart=:dateStart, gibbonYearGroupIDEntry=:gibbonYearGroupIDEntry, dayType=:dayType, referenceEmail=:referenceEmail, schoolName1=:schoolName1, schoolAddress1=:schoolAddress1, schoolGrades1=:schoolGrades1, schoolLanguage1=:schoolLanguage1, schoolDate1=:schoolDate1, schoolName2=:schoolName2, schoolAddress2=:schoolAddress2, schoolGrades2=:schoolGrades2, schoolLanguage2=:schoolLanguage2, schoolDate2=:schoolDate2, gibbonFamilyID=:gibbonFamilyID, parent1gibbonPersonID=:parent1gibbonPersonID, parent1title=:parent1title, parent1surname=:parent1surname, parent1firstName=:parent1firstName, parent1preferredName=:parent1preferredName, parent1officialName=:parent1officialName, parent1nameInCharacters=:parent1nameInCharacters, parent1gender=:parent1gender, parent1relationship=:parent1relationship, parent1languageFirst=:parent1languageFirst, parent1languageSecond=:parent1languageSecond, parent1citizenship1=:parent1citizenship1, parent1nationalIDCardNumber=:parent1nationalIDCardNumber, parent1residencyStatus=:parent1residencyStatus, parent1visaExpiryDate=:parent1visaExpiryDate, parent1email=:parent1email, parent1phone1Type=:parent1phone1Type, parent1phone1CountryCode=:parent1phone1CountryCode, parent1phone1=:parent1phone1, parent1phone2Type=:parent1phone2Type, parent1phone2CountryCode=:parent1phone2CountryCode, parent1phone2=:parent1phone2, parent1profession=:parent1profession, parent1employer=:parent1employer, parent2title=:parent2title, parent2surname=:parent2surname, parent2firstName=:parent2firstName, parent2preferredName=:parent2preferredName, parent2officialName=:parent2officialName, parent2nameInCharacters=:parent2nameInCharacters, parent2gender=:parent2gender, parent2relationship=:parent2relationship, parent2languageFirst=:parent2languageFirst, parent2languageSecond=:parent2languageSecond, parent2citizenship1=:parent2citizenship1, parent2nationalIDCardNumber=:parent2nationalIDCardNumber, parent2residencyStatus=:parent2residencyStatus, parent2visaExpiryDate=:parent2visaExpiryDate, parent2email=:parent2email, parent2phone1Type=:parent2phone1Type, parent2phone1CountryCode=:parent2phone1CountryCode, parent2phone1=:parent2phone1, parent2phone2Type=:parent2phone2Type, parent2phone2CountryCode=:parent2phone2CountryCode, parent2phone2=:parent2phone2, parent2profession=:parent2profession, parent2employer=:parent2employer, siblingName1=:siblingName1, siblingDOB1=:siblingDOB1, siblingSchool1=:siblingSchool1, siblingSchoolJoiningDate1=:siblingSchoolJoiningDate1, siblingName2=:siblingName2, siblingDOB2=:siblingDOB2, siblingSchool2=:siblingSchool2, siblingSchoolJoiningDate2=:siblingSchoolJoiningDate2, siblingName3=:siblingName3, siblingDOB3=:siblingDOB3, siblingSchool3=:siblingSchool3, siblingSchoolJoiningDate3=:siblingSchoolJoiningDate3, languageChoice=:languageChoice, languageChoiceExperience=:languageChoiceExperience, scholarshipInterest=:scholarshipInterest, scholarshipRequired=:scholarshipRequired, payment=:payment, companyName=:companyName, companyContact=:companyContact, companyAddress=:companyAddress, companyEmail=:companyEmail, companyCCFamily=:companyCCFamily, companyPhone=:companyPhone, companyAll=:companyAll, gibbonFinanceFeeCategoryIDList=:gibbonFinanceFeeCategoryIDList, howDidYouHear=:howDidYouHear, howDidYouHearMore=:howDidYouHearMore, agreement=:agreement, privacy=:privacy, fields=:fields, parent1fields=:parent1fields, parent2fields=:parent2fields, timestamp=:timestamp';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                    exit();
                }

                //Last insert ID
                $AI = str_pad($connection2->lastInsertID(), 7, '0', STR_PAD_LEFT);
                $secureAI = sha1($AI.'X2J53ZGy'.$guid.$gibbonSchoolYearIDEntry);

                // Update the Application Form with a hash for looking up this record in the future
                try {
                    $data = array('gibbonApplicationFormID' => $AI, 'gibbonApplicationFormHash' => $secureAI );
                    $sql = 'UPDATE gibbonApplicationForm SET gibbonApplicationFormHash=:gibbonApplicationFormHash WHERE gibbonApplicationFormID=:gibbonApplicationFormID';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                }

                //Deal with family relationships
                if ($gibbonFamily == 'TRUE') {
                    $relationships = $_POST[$gibbonFamilyID.'-relationships'];
                    $relationshipsGibbonPersonIDs = $_POST[$gibbonFamilyID.'-relationshipsGibbonPersonID'];
                    $count = 0;
                    foreach ($relationships as $relationship) {
                        try {
                            $data = array('gibbonApplicationFormID' => $AI, 'gibbonPersonID' => $relationshipsGibbonPersonIDs[$count], 'relationship' => $relationship);
                            $sql = 'INSERT INTO gibbonApplicationFormRelationship SET gibbonApplicationFormID=:gibbonApplicationFormID, gibbonPersonID=:gibbonPersonID, relationship=:relationship';
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        } catch (PDOException $e) {
                        }
                        ++$count;
                    }
                }

                //Deal with required documents
                $requiredDocuments = getSettingByScope($connection2, 'Application Form', 'requiredDocuments');
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
                                $dataFile = array('gibbonApplicationFormID' => $AI, 'name' => $fileName, 'path' => $attachment);
                                $sqlFile = 'INSERT INTO gibbonApplicationFormFile SET gibbonApplicationFormID=:gibbonApplicationFormID, name=:name, path=:path';
                                $resultFile = $connection2->prepare($sqlFile);
                                $resultFile->execute($dataFile);
                            } catch (PDOException $e) {
                            }
                        }
                    }
                }

                // Raise a new notification event
                $event = new NotificationEvent('Students', 'New Application Form');

                $event->addRecipient($_SESSION[$guid]['organisationAdmissions']);
                $event->setNotificationText(sprintf(__('An application form has been submitted for %1$s.'), formatName('', $preferredName, $surname, 'Student')));
                $event->setActionLink("/index.php?q=/modules/Students/applicationForm_manage_edit.php&gibbonApplicationFormID=$AI&gibbonSchoolYearID=$gibbonSchoolYearIDEntry&search=");

                $event->sendNotifications($pdo, $gibbon->session);


                //Email reference form link to referee
                $applicationFormRefereeLink = getSettingByScope($connection2, 'Students', 'applicationFormRefereeLink');
                if ($applicationFormRefereeLink != '' and $referenceEmail != '' and $_SESSION[$guid]['organisationAdmissionsName'] != '' and $_SESSION[$guid]['organisationAdmissionsEmail'] != '') {
                    //Prep message
                    $subject = __($guid, 'Request For Reference');
                    $body = sprintf(__($guid, 'To whom it may concern,%4$sThis email is being sent in relation to the application of a current or former student of your school: %1$s.%4$sIn assessing their application for our school, we would like to enlist your help in completing the following reference form: %2$s.<br/><br/>Please feel free to contact me, should you have any questions in regard to this matter.%4$sRegards,%4$s%3$s'), $officialName, "<a href='$applicationFormRefereeLink' target='_blank'>$applicationFormRefereeLink</a>", $_SESSION[$guid]['organisationAdmissionsName'], '<br/><br/>');
                    $body .= "<p style='font-style: italic;'>".sprintf(__($guid, 'Email sent via %1$s at %2$s.'), $_SESSION[$guid]['systemName'], $_SESSION[$guid]['organisationName']).'</p>';
                    $bodyPlain = emailBodyConvert($body);
                    $mail = getGibbonMailer($guid);
                    $mail->SetFrom($_SESSION[$guid]['organisationAdmissionsEmail'], $_SESSION[$guid]['organisationAdmissionsName']);
                    $mail->AddAddress($referenceEmail);
                    $mail->CharSet = 'UTF-8';
                    $mail->Encoding = 'base64';
                    $mail->IsHTML(true);
                    $mail->Subject = $subject;
                    $mail->Body = $body;
                    $mail->AltBody = $bodyPlain;
                    $mail->Send();
                }

                //Notify parent 1 of application status
                if (!is_null($parent1email)) {
                    $body = sprintf(__($guid, 'Dear Parent%1$sThank you for applying for a student place at %2$s.'), '<br/><br/>', $_SESSION[$guid]['organisationName']).' ';
                    $body .= __($guid, 'Your application was successfully submitted. Our admissions team will review your application and be in touch in due course.').'<br/><br/>';
                    $body .= __($guid, 'You may continue submitting applications for siblings with the form below and they will be linked to your family data.').'<br/><br/>';
                    $body .= "<a href='{$URL}&id={$secureAI}'>{$URL}&id={$secureAI}</a><br/><br/>";
                    $body .= sprintf(__($guid, 'In the meantime, should you have any questions please contact %1$s at %2$s.'), $_SESSION[$guid]['organisationAdmissionsName'], $_SESSION[$guid]['organisationAdmissionsEmail']).'<br/><br/>';
                    $body .= "<p style='font-style: italic;'>".sprintf(__($guid, 'Email sent via %1$s at %2$s.'), $_SESSION[$guid]['systemName'], $_SESSION[$guid]['organisationName']).'</p>';
                    $bodyPlain = emailBodyConvert($body);
                    $mail = getGibbonMailer($guid);
                    $mail->SetFrom($_SESSION[$guid]['organisationAdministratorEmail'], $_SESSION[$guid]['organisationAdministratorName']);
                    $mail->AddAddress($parent1email);
                    $mail->CharSet = 'UTF-8';
                    $mail->Encoding = 'base64';
                    $mail->IsHTML(true);
                    $mail->Subject = sprintf(__($guid, '%1$s Application Form Confirmation'), $_SESSION[$guid]['organisationName']);
                    $mail->Body = $body;
                    $mail->AltBody = $bodyPlain;
                    $mail->Send();
                }

                // Handle Sibling Applications
                if (!empty($_POST['linkedApplicationFormID'])) {
                    $data = array( 'gibbonApplicationFormID' => $_POST['linkedApplicationFormID'] );
                    $sql = 'SELECT DISTINCT gibbonApplicationFormID FROM gibbonApplicationForm
                            LEFT JOIN gibbonApplicationFormLink ON (gibbonApplicationForm.gibbonApplicationFormID=gibbonApplicationFormLink.gibbonApplicationFormID1 OR gibbonApplicationForm.gibbonApplicationFormID=gibbonApplicationFormLink.gibbonApplicationFormID2)
                            WHERE (gibbonApplicationFormID=:gibbonApplicationFormID AND gibbonApplicationFormLinkID IS NULL)
                            OR gibbonApplicationFormID1=:gibbonApplicationFormID
                            OR gibbonApplicationFormID2=:gibbonApplicationFormID';
                    $resultLinked = $pdo->executeQuery($data, $sql);

                    if ($resultLinked && $resultLinked->rowCount() > 0) {
                        // Create a new link to each existing form
                        while ($linkedApplication = $resultLinked->fetch()) {
                            $data = array( 'gibbonApplicationFormID1' => $AI, 'gibbonApplicationFormID2' => $linkedApplication['gibbonApplicationFormID'] );
                            $sql = "INSERT INTO gibbonApplicationFormLink SET gibbonApplicationFormID1=:gibbonApplicationFormID1, gibbonApplicationFormID2=:gibbonApplicationFormID2 ON DUPLICATE KEY UPDATE timestamp=NOW()";
                            $resultNewLink = $pdo->executeQuery($data, $sql);
                        }
                    }
                }

                //Attempt payment if everything is set up for it
                $applicationFee = getSettingByScope($connection2, 'Application Form', 'applicationFee');
                $enablePayments = getSettingByScope($connection2, 'System', 'enablePayments');
                $paypalAPIUsername = getSettingByScope($connection2, 'System', 'paypalAPIUsername');
                $paypalAPIPassword = getSettingByScope($connection2, 'System', 'paypalAPIPassword');
                $paypalAPISignature = getSettingByScope($connection2, 'System', 'paypalAPISignature');

                if ($applicationFee > 0 and is_numeric($applicationFee) and $enablePayments == 'Y' and $paypalAPIUsername != '' and $paypalAPIPassword != '' and $paypalAPISignature != '') {
                    $_SESSION[$guid]['gatewayCurrencyNoSupportReturnURL'] = $_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Students/applicationForm.php&return=success4&id=$secureAI";
                    $URL = $_SESSION[$guid]['absoluteURL']."/lib/paypal/expresscheckout.php?Payment_Amount=$applicationFee&return=".urlencode("modules/Students/applicationFormProcess.php?return=success1&id=$secureAI&applicationFee=$applicationFee").'&fail='.urlencode("modules/Students/applicationFormProcess.php?return=success2&id=$secureAI&applicationFee=$applicationFee");
                    header("Location: {$URL}");
                } else {
                    $URL .= "&return=success0&id=$secureAI";
                    header("Location: {$URL}");
                }
            }
        }
    }
    //IF ID IS SET WE ARE JUST RETURNING TO FINALISE PAYMENT AND RECORD OF PAYMENT, SO LET'S DO IT.
    else {
        //Get returned paypal tokens, ids, etc
        $paymentMade = 'N';
        if ($_GET['return'] == 'success1') {
            $paymentMade = 'Y';
        }
        $paymentToken = null;
        if (isset($_GET['token'])) {
            $paymentToken = $_GET['token'];
        }
        $paymentPayerID = null;
        if (isset($_GET['PayerID'])) {
            $paymentPayerID = $_GET['PayerID'];
        }
        $gibbonApplicationFormID = null;
        if (isset($_GET['id'])) {
            // Find the ID based on the hash provided for added security
            $data = array( 'gibbonApplicationFormHash' => $_GET['id'] );
            $sql = "SELECT gibbonApplicationFormID FROM gibbonApplicationForm WHERE gibbonApplicationFormHash=:gibbonApplicationFormHash";
            $resultID = $pdo->executeQuery($data, $sql);

            if ($resultID && $resultID->rowCount() == 1) {
                $gibbonApplicationFormID = $resultID->fetchColumn(0);
            }
        }
        $applicationFee = null;
        if (isset($_GET['applicationFee'])) {
            $applicationFee = $_GET['applicationFee'];
        }

        //Get email parameters ready to send messages for to admissions for payment problems
        $to = $_SESSION[$guid]['organisationAdmissionsEmail'];
        $subject = $_SESSION[$guid]['organisationNameShort'].' Gibbon Application Form Payment Issue';

        //Check return values to see if we can proceed
        if ($paymentToken == '' or $gibbonApplicationFormID == '' or $applicationFee == '') {
            $body = __($guid, 'Payment via PayPal may or may not have been successful, but has not been recorded either way due to a system error. Please check your PayPal account for details. The following may be useful:')."<br/><br/>Payment Token: $paymentToken<br/><br/>Payer ID: $paymentPayerID<br/><br/>Application Form ID: $gibbonApplicationFormID<br/><br/>Application Fee: $applicationFee<br/><br/>".$_SESSION[$guid]['systemName'].' '.__($guid, 'Administrator');
            $body .= "<p style='font-style: italic;'>".sprintf(__($guid, 'Email sent via %1$s at %2$s.'), $_SESSION[$guid]['systemName'], $_SESSION[$guid]['organisationName']).'</p>';
            $bodyPlain = emailBodyConvert($body);

            $mail = getGibbonMailer($guid);
            $mail->SetFrom($_SESSION[$guid]['organisationAdministratorEmail'], $_SESSION[$guid]['organisationAdministratorName']);
            $mail->AddAddress($to);
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';
            $mail->IsHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = $bodyPlain;
            $mail->Send();

            //Success 2
            $URL .= '&return=success2&id='.$_GET['id'];
            header("Location: {$URL}");
            exit();
        } else {
            //PROCEED AND FINALISE PAYMENT
            require '../../lib/paypal/paypalfunctions.php';

            //Ask paypal to finalise the payment
            $confirmPayment = confirmPayment($guid, $applicationFee, $paymentToken, $paymentPayerID);

            $ACK = $confirmPayment['ACK'];
            $paymentTransactionID = $confirmPayment['PAYMENTINFO_0_TRANSACTIONID'];
            $paymentReceiptID = $confirmPayment['PAYMENTINFO_0_RECEIPTID'];

            //Payment was successful. Yeah!
            if ($ACK == 'Success') {
                $updateFail = false;

                //Save payment details to gibbonPayment
                $gibbonPaymentID = setPaymentLog($connection2, $guid, 'gibbonApplicationForm', $gibbonApplicationFormID, 'Online', 'Complete', $applicationFee, 'Paypal', 'Success', $paymentToken, $paymentPayerID, $paymentTransactionID, $paymentReceiptID);

                //Link gibbonPayment record to gibbonApplicationForm, and make note that payment made
                if ($gibbonPaymentID != '') {
                    try {
                        $data = array('paymentMade' => $paymentMade, 'gibbonPaymentID' => $gibbonPaymentID, 'gibbonApplicationFormID' => $gibbonApplicationFormID);
                        $sql = 'UPDATE gibbonApplicationForm SET paymentMade=:paymentMade, gibbonPaymentID=:gibbonPaymentID WHERE gibbonApplicationFormID=:gibbonApplicationFormID';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $updateFail = true;
                    }
                } else {
                    $updateFail = true;
                }

                if ($updateFail == true) {
                    $body = __($guid, 'Payment via PayPal was successful, but has not been recorded due to a system error. Please check your PayPal account for details. The following may be useful:')."<br/><br/>Payment Token: $paymentToken<br/><br/>Payer ID: $paymentPayerID<br/><br/>Application Form ID: $gibbonApplicationFormID<br/><br/>Application Fee: $applicationFee<br/><br/>".$_SESSION[$guid]['systemName'].' '.__($guid, 'Administrator');
                    $body .= "<p style='font-style: italic;'>".sprintf(__($guid, 'Email sent via %1$s at %2$s.'), $_SESSION[$guid]['systemName'], $_SESSION[$guid]['organisationName']).'</p>';
                    $bodyPlain = emailBodyConvert($body);

                    $mail = getGibbonMailer($guid);
                    $mail->SetFrom($_SESSION[$guid]['organisationAdministratorEmail'], $_SESSION[$guid]['organisationAdministratorName']);
                    $mail->AddAddress($to);
                    $mail->CharSet = 'UTF-8';
                    $mail->Encoding = 'base64';
                    $mail->IsHTML(true);
                    $mail->Subject = $subject;
                    $mail->Body = $body;
                    $mail->AltBody = $bodyPlain;
                    $mail->Send();

                    $URL .= '&return=success3&id='.$_GET['id'];
                    header("Location: {$URL}");
                    exit;
                }

                $URL .= '&return=success1&id='.$_GET['id'];
                header("Location: {$URL}");
            } else {
                $updateFail = false;

                //Save payment details to gibbonPayment
                $gibbonPaymentID = setPaymentLog($connection2, $guid, 'gibbonApplicationForm', $gibbonApplicationFormID, 'Online', 'Failure', $applicationFee, 'Paypal', 'Failure', $paymentToken, $paymentPayerID, $paymentTransactionID, $paymentReceiptID);

                //Link gibbonPayment record to gibbonApplicationForm, and make note that payment made
                if ($gibbonPaymentID != '') {
                    try {
                        $data = array('paymentMade' => $paymentMade, 'gibbonPaymentID' => $gibbonPaymentID, 'gibbonApplicationFormID' => $gibbonApplicationFormID);
                        $sql = 'UPDATE gibbonApplicationForm SET paymentMade=:paymentMade, gibbonPaymentID=:gibbonPaymentID WHERE gibbonApplicationFormID=:gibbonApplicationFormID';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $updateFail = true;
                    }
                } else {
                    $updateFail = true;
                }

                if ($updateFail == true) {
                    $body = __($guid, 'Payment via PayPal was unsuccessful, and has also not been recorded due to a system error. Please check your PayPal account for details. The following may be useful:')."<br/><br/>Payment Token: $paymentToken<br/><br/>Payer ID: $paymentPayerID<br/><br/>Application Form ID: $gibbonApplicationFormID<br/><br/>Application Fee: $applicationFee<br/><br/>".$_SESSION[$guid]['systemName'].' '.__($guid, 'Administrator');
                    $body .= "<p style='font-style: italic;'>".sprintf(__($guid, 'Email sent via %1$s at %2$s.'), $_SESSION[$guid]['systemName'], $_SESSION[$guid]['organisationName']).'</p>';
                    $bodyPlain = emailBodyConvert($body);

                    $mail = getGibbonMailer($guid);
                    $mail->SetFrom($_SESSION[$guid]['organisationAdministratorEmail'], $_SESSION[$guid]['organisationAdministratorName']);
                    $mail->AddAddress($to);
                    $mail->CharSet = 'UTF-8';
                    $mail->Encoding = 'base64';
                    $mail->IsHTML(true);
                    $mail->Subject = $subject;
                    $mail->Body = $body;
                    $mail->AltBody = $bodyPlain;
                    $mail->Send();

                    //Success 2
                    $URL .= '&return=success2&id='.$_GET['id'];
                    header("Location: {$URL}");
                    exit;
                }

                //Success 2
                $URL .= '&return=success2&id='.$_GET['id'];
                header("Location: {$URL}");
            }
        }
    }
}
