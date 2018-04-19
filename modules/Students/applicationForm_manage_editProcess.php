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

@session_start();

//Module includes from User Admin (for custom fields)
include '../User Admin/moduleFunctions.php';

$gibbonApplicationFormID = $_POST['gibbonApplicationFormID'];
$gibbonSchoolYearID = $_POST['gibbonSchoolYearID'];
$search = $_GET['search'];
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/applicationForm_manage_edit.php&gibbonApplicationFormID=$gibbonApplicationFormID&gibbonSchoolYearID=$gibbonSchoolYearID&search=$search";

if (isActionAccessible($guid, $connection2, '/modules/Students/applicationForm_manage_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    //Check if school year specified

    if ($gibbonApplicationFormID == '' or $gibbonSchoolYearID == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        try {
            $data = array('gibbonApplicationFormID' => $gibbonApplicationFormID);
            $sql = 'SELECT * FROM gibbonApplicationForm WHERE gibbonApplicationFormID=:gibbonApplicationFormID';
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
            $milestonesMaster = explode(',', getSettingByScope($connection2, 'Application Form', 'milestones'));
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
            $gibbonRollGroupID = null;
            if (isset($_POST['gibbonRollGroupID']) && $_POST['gibbonRollGroupID'] != '') {
                $gibbonRollGroupID = $_POST['gibbonRollGroupID'];
            }
            $paymentMade = 'N';
            if (isset($_POST['paymentMade'])) {
                $paymentMade = $_POST['paymentMade'];
            }
            $notes = $_POST['notes'];
            $surname = trim($_POST['surname']);
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
            $phone1CountryCode = $_POST['phone1CountryCode'];
            $phone1 = preg_replace('/[^0-9+]/', '', $_POST['phone1']);
            $phone2Type = $_POST['phone2Type'];
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
            $gibbonYearGroupIDEntry = $_POST['gibbonYearGroupIDEntry'];
            $dayType = null;
            if (isset($_POST['dayType'])) {
                $dayType = $_POST['dayType'];
            }
            $referenceEmail = null;
            if (isset($_POST['referenceEmail'])) {
                $referenceEmail = $_POST['referenceEmail'];
            }
            $schoolName1 = $_POST['schoolName1'];
            $schoolAddress1 = $_POST['schoolAddress1'];
            $schoolGrades1 = $_POST['schoolGrades1'];
            $schoolGrades1 = $_POST['schoolGrades1'];
            $schoolDate1 = $_POST['schoolDate1'];
            if ($schoolDate1 == '') {
                $schoolDate1 = null;
            } else {
                $schoolDate1 = dateConvert($guid, $schoolDate1);
            }
            $schoolName2 = $_POST['schoolName2'];
            $schoolAddress2 = $_POST['schoolAddress2'];
            $schoolGrades2 = $_POST['schoolGrades2'];
            $schoolGrades2 = $_POST['schoolGrades2'];
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
            $privacy = null;
            if (isset($_POST['privacyOptions'])) {
                $privacyOptions = $_POST['privacyOptions'];
                foreach ($privacyOptions as $privacyOption) {
                    if ($privacyOption != '') {
                        $privacy .= $privacyOption.',';
                    }
                }
                if ($privacy != '') {
                    $privacy = substr($privacy, 0, -1);
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
            }
            if ($priority == '' or $surname == '' or $firstName == '' or $preferredName == '' or $officialName == '' or $gender == '' or $dob == '' or $languageHomePrimary == '' or $languageFirst == '' or $gibbonSchoolYearIDEntry == '' or $dateStart == '' or $gibbonYearGroupIDEntry == '' or $sen == '' or $howDidYouHear == '' or $familyFail) {
                $URL .= '&return=error3';
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
                if ($gibbonFamily != 'TRUE') { //Only if there is no family
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
                        }
                    }
                }

                if ($customRequireFail) {
                    $URL .= '&return=error3';
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
                        $data = array('priority' => $priority, 'status' => $status, 'milestones' => $milestones, 'dateStart' => $dateStart, 'gibbonRollGroupID' => $gibbonRollGroupID, 'paymentMade' => $paymentMade, 'notes' => $notes, 'surname' => $surname, 'firstName' => $firstName, 'preferredName' => $preferredName, 'officialName' => $officialName, 'nameInCharacters' => $nameInCharacters, 'gender' => $gender, 'dob' => $dob, 'languageHomePrimary' => $languageHomePrimary, 'languageHomeSecondary' => $languageHomeSecondary, 'languageFirst' => $languageFirst, 'languageSecond' => $languageSecond, 'languageThird' => $languageThird, 'countryOfBirth' => $countryOfBirth, 'citizenship1' => $citizenship1, 'citizenship1Passport' => $citizenship1Passport, 'nationalIDCardNumber' => $nationalIDCardNumber, 'residencyStatus' => $residencyStatus, 'visaExpiryDate' => $visaExpiryDate, 'email' => $email, 'homeAddress' => $homeAddress, 'homeAddressDistrict' => $homeAddressDistrict, 'homeAddressCountry' => $homeAddressCountry, 'phone1Type' => $phone1Type, 'phone1CountryCode' => $phone1CountryCode, 'phone1' => $phone1, 'phone2Type' => $phone2Type, 'phone2CountryCode' => $phone2CountryCode, 'phone2' => $phone2, 'medicalInformation' => $medicalInformation, 'sen' => $sen, 'senDetails' => $senDetails, 'gibbonSchoolYearIDEntry' => $gibbonSchoolYearIDEntry, 'gibbonYearGroupIDEntry' => $gibbonYearGroupIDEntry, 'dayType' => $dayType, 'referenceEmail' => $referenceEmail, 'schoolName1' => $schoolName1, 'schoolAddress1' => $schoolAddress1, 'schoolGrades1' => $schoolGrades1, 'schoolDate1' => $schoolDate1, 'schoolName2' => $schoolName2, 'schoolAddress2' => $schoolAddress2, 'schoolGrades2' => $schoolGrades2, 'schoolDate2' => $schoolDate2, 'gibbonFamilyID' => $gibbonFamilyID, 'parent1gibbonPersonID' => $parent1gibbonPersonID, 'parent1title' => $parent1title, 'parent1surname' => $parent1surname, 'parent1firstName' => $parent1firstName, 'parent1preferredName' => $parent1preferredName, 'parent1officialName' => $parent1officialName, 'parent1nameInCharacters' => $parent1nameInCharacters, 'parent1gender' => $parent1gender, 'parent1relationship' => $parent1relationship, 'parent1languageFirst' => $parent1languageFirst, 'parent1languageSecond' => $parent1languageSecond, 'parent1citizenship1' => $parent1citizenship1, 'parent1nationalIDCardNumber' => $parent1nationalIDCardNumber, 'parent1residencyStatus' => $parent1residencyStatus, 'parent1visaExpiryDate' => $parent1visaExpiryDate, 'parent1email' => $parent1email, 'parent1phone1Type' => $parent1phone1Type, 'parent1phone1CountryCode' => $parent1phone1CountryCode, 'parent1phone1' => $parent1phone1, 'parent1phone2Type' => $parent1phone2Type, 'parent1phone2CountryCode' => $parent1phone2CountryCode, 'parent1phone2' => $parent1phone2, 'parent1profession' => $parent1profession, 'parent1employer' => $parent1employer, 'parent2title' => $parent2title, 'parent2surname' => $parent2surname, 'parent2firstName' => $parent2firstName, 'parent2preferredName' => $parent2preferredName, 'parent2officialName' => $parent2officialName, 'parent2nameInCharacters' => $parent2nameInCharacters, 'parent2gender' => $parent2gender, 'parent2relationship' => $parent2relationship, 'parent2languageFirst' => $parent2languageFirst, 'parent2languageSecond' => $parent2languageSecond, 'parent2citizenship1' => $parent2citizenship1, 'parent2nationalIDCardNumber' => $parent2nationalIDCardNumber, 'parent2residencyStatus' => $parent2residencyStatus, 'parent2visaExpiryDate' => $parent2visaExpiryDate, 'parent2email' => $parent2email, 'parent2phone1Type' => $parent2phone1Type, 'parent2phone1CountryCode' => $parent2phone1CountryCode, 'parent2phone1' => $parent2phone1, 'parent2phone2Type' => $parent2phone2Type, 'parent2phone2CountryCode' => $parent2phone2CountryCode, 'parent2phone2' => $parent2phone2, 'parent2profession' => $parent2profession, 'parent2employer' => $parent2employer, 'siblingName1' => $siblingName1, 'siblingDOB1' => $siblingDOB1, 'siblingSchool1' => $siblingSchool1, 'siblingSchoolJoiningDate1' => $siblingSchoolJoiningDate1, 'siblingName2' => $siblingName2, 'siblingDOB2' => $siblingDOB2, 'siblingSchool2' => $siblingSchool2, 'siblingSchoolJoiningDate2' => $siblingSchoolJoiningDate2, 'siblingName3' => $siblingName3, 'siblingDOB3' => $siblingDOB3, 'siblingSchool3' => $siblingSchool3, 'siblingSchoolJoiningDate3' => $siblingSchoolJoiningDate3, 'languageChoice' => $languageChoice, 'languageChoiceExperience' => $languageChoiceExperience, 'scholarshipInterest' => $scholarshipInterest, 'scholarshipRequired' => $scholarshipRequired, 'payment' => $payment, 'companyName' => $companyName, 'companyContact' => $companyContact, 'companyAddress' => $companyAddress, 'companyEmail' => $companyEmail, 'companyCCFamily' => $companyCCFamily, 'companyPhone' => $companyPhone, 'companyAll' => $companyAll, 'gibbonFinanceFeeCategoryIDList' => $gibbonFinanceFeeCategoryIDList, 'howDidYouHear' => $howDidYouHear, 'howDidYouHearMore' => $howDidYouHearMore, 'privacy' => $privacy, 'fields' => $fields, 'parent1fields' => $parent1fields, 'parent2fields' => $parent2fields, 'gibbonApplicationFormID' => $gibbonApplicationFormID);
                        $sql = 'UPDATE gibbonApplicationForm SET priority=:priority, status=:status, milestones=:milestones, dateStart=:dateStart, gibbonRollGroupID=:gibbonRollGroupID, paymentMade=:paymentMade, notes=:notes, surname=:surname, firstName=:firstName, preferredName=:preferredName, officialName=:officialName, nameInCharacters=:nameInCharacters, gender=:gender, dob=:dob, languageHomePrimary=:languageHomePrimary, languageHomeSecondary=:languageHomeSecondary, languageFirst=:languageFirst, languageSecond=:languageSecond, languageThird=:languageThird, countryOfBirth=:countryOfBirth, citizenship1=:citizenship1, citizenship1Passport=:citizenship1Passport, nationalIDCardNumber=:nationalIDCardNumber, residencyStatus=:residencyStatus, visaExpiryDate=:visaExpiryDate, email=:email, homeAddress=:homeAddress, homeAddressDistrict=:homeAddressDistrict, homeAddressCountry=:homeAddressCountry, phone1Type=:phone1Type, phone1CountryCode=:phone1CountryCode, phone1=:phone1, phone2Type=:phone2Type, phone2CountryCode=:phone2CountryCode, phone2=:phone2, medicalInformation=:medicalInformation, sen=:sen, senDetails=:senDetails, gibbonSchoolYearIDEntry=:gibbonSchoolYearIDEntry, gibbonYearGroupIDEntry=:gibbonYearGroupIDEntry, dayType=:dayType, referenceEmail=:referenceEmail, schoolName1=:schoolName1, schoolAddress1=:schoolAddress1, schoolGrades1=:schoolGrades1, schoolDate1=:schoolDate1, schoolName2=:schoolName2, schoolAddress2=:schoolAddress2, schoolGrades2=:schoolGrades2, schoolDate2=:schoolDate2, gibbonFamilyID=:gibbonFamilyID, parent1gibbonPersonID=:parent1gibbonPersonID, parent1title=:parent1title, parent1surname=:parent1surname, parent1firstName=:parent1firstName, parent1preferredName=:parent1preferredName, parent1officialName=:parent1officialName, parent1nameInCharacters=:parent1nameInCharacters, parent1gender=:parent1gender, parent1relationship=:parent1relationship, parent1languageFirst=:parent1languageFirst, parent1languageSecond=:parent1languageSecond, parent1citizenship1=:parent1citizenship1, parent1nationalIDCardNumber=:parent1nationalIDCardNumber, parent1residencyStatus=:parent1residencyStatus, parent1visaExpiryDate=:parent1visaExpiryDate, parent1email=:parent1email, parent1phone1Type=:parent1phone1Type, parent1phone1CountryCode=:parent1phone1CountryCode, parent1phone1=:parent1phone1, parent1phone2Type=:parent1phone2Type, parent1phone2CountryCode=:parent1phone2CountryCode, parent1phone2=:parent1phone2, parent1profession=:parent1profession, parent1employer=:parent1employer, parent2title=:parent2title, parent2surname=:parent2surname, parent2firstName=:parent2firstName, parent2preferredName=:parent2preferredName, parent2officialName=:parent2officialName, parent2nameInCharacters=:parent2nameInCharacters, parent2gender=:parent2gender, parent2relationship=:parent2relationship, parent2languageFirst=:parent2languageFirst, parent2languageSecond=:parent2languageSecond, parent2citizenship1=:parent2citizenship1, parent2nationalIDCardNumber=:parent2nationalIDCardNumber, parent2residencyStatus=:parent2residencyStatus, parent2visaExpiryDate=:parent2visaExpiryDate, parent2email=:parent2email, parent2phone1Type=:parent2phone1Type, parent2phone1CountryCode=:parent2phone1CountryCode, parent2phone1=:parent2phone1, parent2phone2Type=:parent2phone2Type, parent2phone2CountryCode=:parent2phone2CountryCode, parent2phone2=:parent2phone2, parent2profession=:parent2profession, parent2employer=:parent2employer, siblingName1=:siblingName1, siblingDOB1=:siblingDOB1, siblingSchool1=:siblingSchool1, siblingSchoolJoiningDate1=:siblingSchoolJoiningDate1, siblingName2=:siblingName2, siblingDOB2=:siblingDOB2, siblingSchool2=:siblingSchool2, siblingSchoolJoiningDate2=:siblingSchoolJoiningDate2, siblingName3=:siblingName3, siblingDOB3=:siblingDOB3, siblingSchool3=:siblingSchool3, siblingSchoolJoiningDate3=:siblingSchoolJoiningDate3, languageChoice=:languageChoice, languageChoiceExperience=:languageChoiceExperience, scholarshipInterest=:scholarshipInterest, scholarshipRequired=:scholarshipRequired, payment=:payment, companyName=:companyName, companyContact=:companyContact, companyAddress=:companyAddress, companyEmail=:companyEmail, companyCCFamily=:companyCCFamily, companyPhone=:companyPhone, companyAll=:companyAll, gibbonFinanceFeeCategoryIDList=:gibbonFinanceFeeCategoryIDList, howDidYouHear=:howDidYouHear, howDidYouHearMore=:howDidYouHearMore, privacy=:privacy, fields=:fields, parent1fields=:parent1fields, parent2fields=:parent2fields WHERE gibbonApplicationFormID=:gibbonApplicationFormID';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $URL .= '&return=error2';
                        header("Location: {$URL}");
                        exit();
                    }

                    $partialFail = false;

                    //Deal with required documents
                    $requiredDocuments = getSettingByScope($connection2, 'Application Form', 'requiredDocuments');
                    $internalDocuments = getSettingByScope($connection2, 'Application Form', 'internalDocuments');
                    if ($internalDocuments != '') {
                        $requiredDocuments .= ','.$internalDocuments;
                    }
                    if ($requiredDocuments != '' and $requiredDocuments != false) {
                        $fileCount = 0;
                        if (isset($_POST['fileCount'])) {
                            $fileCount = $_POST['fileCount'];
                        }

                        $fileUploader = new Gibbon\FileUploader($pdo, $gibbon->session);

                        for ($i = 0; $i < $fileCount; ++$i) {
                            if (empty($_FILES["file$i"]['tmp_name'])) continue;

                            $fileName = (isset($_POST["fileName$i"]))? $_POST["fileName$i"] : null;

                            // Handle multiple file uploads (and transpose array)
                            $uploads = array();
                            foreach ($_FILES["file$i"] as $key => $subarr) {
                                foreach ($subarr as $subkey => $subvalue) {
                                    $uploads[$subkey][$key] = $subvalue;
                                }
                            }

                            foreach ($uploads as $file) {
                                if ($file['error'] == UPLOAD_ERR_NO_FILE) continue;

                                // Upload the file, return the /uploads relative path
                                $attachment = $fileUploader->uploadFromPost($file, 'ApplicationDocument');
    
                                // Write files to database, if there is one
                                if (!empty($attachment)) {
                                    try {
                                        $dataFile = array('gibbonApplicationFormID' => $gibbonApplicationFormID, 'name' => $fileName, 'path' => $attachment);
                                        $sqlFile = "INSERT INTO gibbonApplicationFormFile SET gibbonApplicationFormID=:gibbonApplicationFormID, name=:name, path=:path";
                                        $resultFile = $connection2->prepare($sqlFile);
                                        $resultFile->execute($dataFile);
                                    } catch (PDOException $e) {
                                        $partialFail = true;
                                    }
                                } else {
                                    $partialFail = true;
                                }
                            }
                        }

                        $attachments = (isset($_POST['attachment']))? $_POST['attachment'] : array();

                        // File is flagged for deletion if the attachment path has been removed
                        foreach ($attachments as $gibbonApplicationFormFileID => $attachment) {
                            if (!empty($gibbonApplicationFormFileID) && empty($attachment)) {
                                try {
                                    $dataFile = array('gibbonApplicationFormFileID' => $gibbonApplicationFormFileID);
                                    $sqlFile = "DELETE FROM gibbonApplicationFormFile WHERE gibbonApplicationFormFileID=:gibbonApplicationFormFileID";
                                    $resultFile = $connection2->prepare($sqlFile);
                                    $resultFile->execute($dataFile);
                                } catch (PDOException $e) {
                                    $partialFail = true;
                                }
                            }
                        }
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
                                $data = array( 'gibbonApplicationFormID1' => $gibbonApplicationFormID, 'gibbonApplicationFormID2' => $linkedApplication['gibbonApplicationFormID'] );
                                $sql = "INSERT INTO gibbonApplicationFormLink SET gibbonApplicationFormID1=:gibbonApplicationFormID1, gibbonApplicationFormID2=:gibbonApplicationFormID2 ON DUPLICATE KEY UPDATE timestamp=NOW()";
                                $resultNewLink = $pdo->executeQuery($data, $sql);
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
