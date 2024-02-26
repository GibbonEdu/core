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

use Gibbon\Domain\System\SettingGateway;
use Gibbon\Services\Format;
use Gibbon\Domain\User\UserGateway;
use Gibbon\Forms\CustomFieldHandler;
use Gibbon\Forms\PersonalDocumentHandler;
use Gibbon\Data\Validator;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

//Module includes from User Admin (for custom fields)
include '../User Admin/moduleFunctions.php';

$gibbonApplicationFormID = $_POST['gibbonApplicationFormID'] ?? '';
$gibbonSchoolYearID = $_POST['gibbonSchoolYearID'] ?? '';
$search = $_GET['search'] ?? '';
$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address'])."/applicationForm_manage_edit.php&gibbonApplicationFormID=$gibbonApplicationFormID&gibbonSchoolYearID=$gibbonSchoolYearID&search=$search";

if (isActionAccessible($guid, $connection2, '/modules/Students/applicationForm_manage_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    //Check if gibbonApplicationFormID and gibbonSchoolYearID specified

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
            $priority = $_POST['priority'] ?? '';
            $status = $_POST['status'] ?? '';
            $milestones = '';
            $settingGateway = $container->get(SettingGateway::class);
            $milestonesMaster = explode(',', $settingGateway->getSettingByScope('Application Form', 'milestones'));
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
                $dateStart = Format::dateConvert($_POST['dateStart']);
            }
            $gibbonFormGroupID = null;
            if (isset($_POST['gibbonFormGroupID']) && $_POST['gibbonFormGroupID'] != '') {
                $gibbonFormGroupID = $_POST['gibbonFormGroupID'] ?? '';
            }

            $paymentMade = !empty($_POST['paymentMade']) ? $_POST['paymentMade'] : 'N';
            $paymentMade2 = !empty($_POST['paymentMade2']) ? $_POST['paymentMade2'] : 'N';

            $username = $_POST['username'] ?? '';
            $studentID = $_POST['studentID'] ?? '';
            $notes = $_POST['notes'] ?? '';
            $surname = trim($_POST['surname'] ?? '');
            $firstName = trim($_POST['firstName'] ?? '');
            $preferredName = trim($_POST['preferredName'] ?? '');
            $officialName = trim($_POST['officialName'] ?? '');
            $nameInCharacters = $_POST['nameInCharacters'] ?? '';
            $gender = $_POST['gender'] ?? '';
            $dob = !empty($_POST['dob']) ? Format::dateConvert($_POST['dob']) : null;
            $languageHomePrimary = $_POST['languageHomePrimary'] ?? '';
            $languageHomeSecondary = $_POST['languageHomeSecondary'] ?? '';
            $languageFirst = $_POST['languageFirst'] ?? '';
            $languageSecond = $_POST['languageSecond'] ?? '';
            $languageThird = $_POST['languageThird'] ?? '';
            $countryOfBirth = $_POST['countryOfBirth'] ?? '';
            $email = trim($_POST['email'] ?? '');
            $phone1Type = $_POST['phone1Type'] ?? '';
            $phone1CountryCode = $_POST['phone1CountryCode'] ?? '';
            $phone1 = preg_replace('/[^0-9+]/', '', $_POST['phone1'] ?? '');
            $phone2Type = $_POST['phone2Type'] ?? '';
            $phone2CountryCode = $_POST['phone2CountryCode'] ?? '';
            $phone2 = preg_replace('/[^0-9+]/', '', $_POST['phone2'] ?? '');
            $medicalInformation = $_POST['medicalInformation'] ?? '';
            $sen = $_POST['sen'] ?? '';
            if ($sen == 'N') {
                $senDetails = '';
            } else {
                $senDetails = $_POST['senDetails'] ?? '';
            }
            $gibbonSchoolYearIDEntry = $_POST['gibbonSchoolYearIDEntry'] ?? '';
            $gibbonYearGroupIDEntry = $_POST['gibbonYearGroupIDEntry'] ?? '';
            $dayType = $_POST['dayType'] ?? null;
            $referenceEmail = $_POST['referenceEmail'] ?? null;
            $schoolName1 = $_POST['schoolName1'] ?? '';
            $schoolAddress1 = $_POST['schoolAddress1'] ?? '';
            $schoolGrades1 = $_POST['schoolGrades1'] ?? '';
            $schoolGrades1 = $_POST['schoolGrades1'] ?? '';
            $schoolDate1 = $_POST['schoolDate1'] ?? '';
            if ($schoolDate1 == '') {
                $schoolDate1 = null;
            } else {
                $schoolDate1 = Format::dateConvert($schoolDate1);
            }
            $schoolName2 = $_POST['schoolName2'] ?? '';
            $schoolAddress2 = $_POST['schoolAddress2'] ?? '';
            $schoolGrades2 = $_POST['schoolGrades2'] ?? '';
            $schoolGrades2 = $_POST['schoolGrades2'] ?? '';
            $schoolDate2 = $_POST['schoolDate2'] ?? '';
            if ($schoolDate2 == '') {
                $schoolDate2 = null;
            } else {
                $schoolDate2 = Format::dateConvert($schoolDate2);
            }

            //GET FAMILY FEILDS
            $gibbonFamily = $_POST['gibbonFamily'] ?? '';
            if ($gibbonFamily == 'TRUE') {
                $gibbonFamilyID = $_POST['gibbonFamilyID'] ?? '';
            } else {
                $gibbonFamilyID = null;
            }
            $homeAddress = $_POST['homeAddress'] ?? '';
            $homeAddressDistrict = $_POST['homeAddressDistrict'] ?? '';
            $homeAddressCountry = $_POST['homeAddressCountry'] ?? null;


            //GET PARENT1 FEILDS
            $parent1gibbonPersonID = $_POST['parent1gibbonPersonID'] ?? null;
            $parent1title = $_POST['parent1title'] ?? null;
            $parent1surname = trim($_POST['parent1surname'] ?? '');
            $parent1firstName = trim($_POST['parent1firstName'] ?? '');
            $parent1preferredName = trim($_POST['parent1preferredName'] ?? '');
            $parent1officialName = trim($_POST['parent1officialName'] ?? '');
            $parent1nameInCharacters = $_POST['parent1nameInCharacters'] ?? null;
            $parent1gender = $_POST['parent1gender'] ?? null;
            $parent1relationship = $_POST['parent1relationship'] ?? null;
            $parent1languageFirst = $_POST['parent1languageFirst'] ?? null;
            $parent1languageSecond = $_POST['parent1languageSecond'] ?? null;
            $parent1email = trim($_POST['parent1email'] ?? '');
            $parent1phone1Type = $_POST['parent1phone1Type'] ?? null;
            if (isset($_POST['parent1phone1']) and $parent1phone1Type == '') {
                $parent1phone1Type = 'Other';
            }
            $parent1phone1CountryCode = $_POST['parent1phone1CountryCode'] ?? null;
            $parent1phone1 = $_POST['parent1phone1'] ?? null;
            $parent1phone2Type = $_POST['parent1phone2Type'] ?? null;
            if (isset($_POST['parent1phone2']) and $parent1phone2Type == '') {
                $parent1phone2Type = 'Other';
            }
            $parent1phone2CountryCode = $_POST['parent1phone2CountryCode'] ?? null;
            $parent1phone2 = $_POST['parent1phone2'] ?? null;
            $parent1profession = $_POST['parent1profession'] ?? null;
            $parent1employer = $_POST['parent1employer'] ?? null;

            //GET PARENT2 FEILDS
            $parent2title = $_POST['parent2title'] ?? null;
            $parent2surname = trim($_POST['parent2surname'] ?? '');
            $parent2firstName = trim($_POST['parent2firstName'] ?? '');
            $parent2preferredName = trim($_POST['parent2preferredName'] ?? '');
            $parent2officialName = trim($_POST['parent2officialName'] ?? '');
            $parent2nameInCharacters = $_POST['parent2nameInCharacters'] ?? null;
            $parent2gender = $_POST['parent2gender'] ?? null;
            $parent2relationship = $_POST['parent2relationship'] ?? null;
            $parent2languageFirst = $_POST['parent2languageFirst'] ?? null;
            $parent2languageSecond = $_POST['parent2languageSecond'] ?? null;
            $parent2email = trim($_POST['parent2email'] ?? '');
            $parent2phone1Type = $_POST['parent2phone1Type'] ?? null;
            if (isset($_POST['parent2phone1']) and $parent2phone1Type == '') {
                $parent2phone1Type = 'Other';
            }
            $parent2phone1CountryCode = $_POST['parent2phone1CountryCode'] ?? null;
            $parent2phone1 = $_POST['parent2phone1'] ?? null;
            $parent2phone2Type = $_POST['parent2phone2Type'] ?? null;
            if (isset($_POST['parent2phone2']) and $parent2phone2Type == '') {
                $parent2phone2Type = 'Other';
            }
            $parent2phone2CountryCode = $_POST['parent2phone2CountryCode'] ?? null;
            $parent2phone2 = $_POST['parent2phone2'] ?? null;
            $parent2profession = $_POST['parent2profession'] ?? null;
            $parent2employer = $_POST['parent2employer'] ?? null;


            //GET SIBLING FIELDS
            $siblingName1 = $_POST['siblingName1'] ?? '';
            $siblingDOB1 = $_POST['siblingDOB1'] ?? '';
            if ($siblingDOB1 == '') {
                $siblingDOB1 = null;
            } else {
                $siblingDOB1 = Format::dateConvert($siblingDOB1);
            }
            $siblingSchool1 = $_POST['siblingSchool1'] ?? '';
            $siblingSchoolJoiningDate1 = $_POST['siblingSchoolJoiningDate1'] ?? '';
            if ($siblingSchoolJoiningDate1 == '') {
                $siblingSchoolJoiningDate1 = null;
            } else {
                $siblingSchoolJoiningDate1 = Format::dateConvert($siblingSchoolJoiningDate1);
            }
            $siblingName2 = $_POST['siblingName2'] ?? '';
            $siblingDOB2 = $_POST['siblingDOB2'] ?? '';
            if ($siblingDOB2 == '') {
                $siblingDOB2 = null;
            } else {
                $siblingDOB2 = Format::dateConvert($siblingDOB2);
            }
            $siblingSchool2 = $_POST['siblingSchool2'] ?? '';
            $siblingSchoolJoiningDate2 = $_POST['siblingSchoolJoiningDate2'] ?? '';
            if ($siblingSchoolJoiningDate2 == '') {
                $siblingSchoolJoiningDate2 = null;
            } else {
                $siblingSchoolJoiningDate2 = Format::dateConvert($siblingSchoolJoiningDate2);
            }
            $siblingName3 = $_POST['siblingName3'] ?? '';
            $siblingDOB3 = $_POST['siblingDOB3'] ?? '';
            if ($siblingDOB3 == '') {
                $siblingDOB3 = null;
            } else {
                $siblingDOB3 = Format::dateConvert($siblingDOB3);
            }
            $siblingSchool3 = $_POST['siblingSchool3'] ?? '';
            $siblingSchoolJoiningDate3 = $_POST['siblingSchoolJoiningDate3'] ?? '';
            if ($siblingSchoolJoiningDate3 == '') {
                $siblingSchoolJoiningDate3 = null;
            } else {
                $siblingSchoolJoiningDate3 = Format::dateConvert($siblingSchoolJoiningDate3);
            }

            //GET PAYMENT FIELDS
            $payment = $_POST['payment'] ?? '';
            $companyName = $_POST['companyName'] ?? null;
            $companyContact = $_POST['companyContact'] ?? null;
            $companyAddress = $_POST['companyAddress'] ?? null;
            $companyEmail = $_POST['companyEmail'] ?? null;
            $companyCCFamily = $_POST['companyCCFamily'] ?? null;
            $companyPhone = $_POST['companyPhone'] ?? null;
            $companyAll = $_POST['companyAll'] ?? null;
            $gibbonFinanceFeeCategoryIDList = null;
            if (isset($_POST['gibbonFinanceFeeCategoryIDList'])) {
                $gibbonFinanceFeeCategoryIDArray = $_POST['gibbonFinanceFeeCategoryIDList'] ?? '';
                if (count($gibbonFinanceFeeCategoryIDArray) > 0) {
                    foreach ($gibbonFinanceFeeCategoryIDArray as $gibbonFinanceFeeCategoryID) {
                        $gibbonFinanceFeeCategoryIDList .= $gibbonFinanceFeeCategoryID.',';
                    }
                    $gibbonFinanceFeeCategoryIDList = substr($gibbonFinanceFeeCategoryIDList, 0, -1);
                }
            }

            //GET OTHER FIELDS
            $languageChoice = $_POST['languageChoice'] ?? null;
            $languageChoiceExperience = $_POST['languageChoiceExperience'] ?? null;
            $scholarshipInterest = $_POST['scholarshipInterest'] ?? null;
            $scholarshipRequired = $_POST['scholarshipRequired'] ?? null;
            $howDidYouHear = $_POST['howDidYouHear'] ?? null;
            $howDidYouHearMore = $_POST['howDidYouHearMore'] ?? null;
            $privacy = null;
            if (isset($_POST['privacyOptions'])) {
                $privacyOptions = $_POST['privacyOptions'] ?? [];
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

            // Check username and student ID for uniqueness
            $userGateway = $container->get(UserGateway::class);
            if ($status == 'Pending' && !empty($studentID) && !$userGateway->unique(['studentID' => $studentID], ['studentID'])) {
                $URL .= '&return=error7';
                header("Location: {$URL}");
                exit;
            }
            if ($status == 'Pending' && !empty($username) && !$userGateway->unique(['username' => $username], ['username'])) {
                $URL .= '&return=error7';
                header("Location: {$URL}");
                exit;
            }


            if ($priority == '' or $surname == '' or $firstName == '' or $preferredName == '' or $officialName == '' or $gender == '' or $dob == '' or $languageHomePrimary == '' or $languageFirst == '' or $gibbonSchoolYearIDEntry == '' or $dateStart == '' or $gibbonYearGroupIDEntry == '' or $sen == '' or $howDidYouHear == '' or $familyFail) {
                $URL .= '&return=error3';
                header("Location: {$URL}");
            } else {
                //DEAL WITH CUSTOM FIELDS
                $customRequireFail = false;
                $customFieldHandler = $container->get(CustomFieldHandler::class);

                $params = ['student' => 1, 'applicationForm' => 1];
                $fields = $customFieldHandler->getFieldDataFromPOST('User', $params, $customRequireFail);

                $parent1fields = $parent2fields = '';
                if ($gibbonFamily != 'TRUE') { //Only if there is no family
                    $params = ['parent' => 1, 'applicationForm' => 1];
                    $parent1fields = $customFieldHandler->getFieldDataFromPOST('User', $params + ['prefix' => 'parent1custom'], $customRequireFail);
                    $parent2fields = $customFieldHandler->getFieldDataFromPOST('User', $params + ['prefix' => 'parent2custom'], $customRequireFail);
                }

                // PERSONAL DOCUMENTS
                $personalDocumentHandler = $container->get(PersonalDocumentHandler::class);
                $personalDocumentFail = false;
                $params = ['student' => true, 'applicationForm' => true];
                $personalDocumentHandler->updateDocumentsFromPOST('gibbonApplicationForm', $gibbonApplicationFormID, $params, $personalDocumentFail);

                if ($gibbonFamily == 'FALSE') { // Only if there is no family
                    $params = ['parent' => true, 'applicationForm' => true, 'prefix' => 'parent1'];
                    $personalDocumentHandler->updateDocumentsFromPOST('gibbonApplicationFormParent1', $gibbonApplicationFormID, $params, $personalDocumentFail);
    
                    if (empty($_POST['secondParent'])) {
                        $params = ['parent' => true, 'applicationForm' => true, 'prefix' => 'parent2'];
                        $personalDocumentHandler->updateDocumentsFromPOST('gibbonApplicationFormParent2', $gibbonApplicationFormID, $params, $personalDocumentFail);
                    }
                }


                if ($customRequireFail || $personalDocumentFail) {
                    $URL .= '&return=error3';
                    header("Location: {$URL}");
                } else {
                    //Write to database
                    try {
                        $data = array('priority' => $priority, 'status' => $status, 'milestones' => $milestones, 'dateStart' => $dateStart, 'gibbonFormGroupID' => $gibbonFormGroupID, 'paymentMade' => $paymentMade, 'paymentMade2' => $paymentMade2, 'notes' => $notes, 'surname' => $surname, 'firstName' => $firstName, 'preferredName' => $preferredName, 'officialName' => $officialName, 'nameInCharacters' => $nameInCharacters, 'gender' => $gender, 'username' => $username, 'dob' => $dob, 'languageHomePrimary' => $languageHomePrimary, 'languageHomeSecondary' => $languageHomeSecondary, 'languageFirst' => $languageFirst, 'languageSecond' => $languageSecond, 'languageThird' => $languageThird, 'countryOfBirth' => $countryOfBirth, 'email' => $email, 'homeAddress' => $homeAddress, 'homeAddressDistrict' => $homeAddressDistrict, 'homeAddressCountry' => $homeAddressCountry, 'phone1Type' => $phone1Type, 'phone1CountryCode' => $phone1CountryCode, 'phone1' => $phone1, 'phone2Type' => $phone2Type, 'phone2CountryCode' => $phone2CountryCode, 'phone2' => $phone2, 'medicalInformation' => $medicalInformation, 'sen' => $sen, 'senDetails' => $senDetails, 'gibbonSchoolYearIDEntry' => $gibbonSchoolYearIDEntry, 'gibbonYearGroupIDEntry' => $gibbonYearGroupIDEntry, 'dayType' => $dayType, 'referenceEmail' => $referenceEmail, 'schoolName1' => $schoolName1, 'schoolAddress1' => $schoolAddress1, 'schoolGrades1' => $schoolGrades1, 'schoolDate1' => $schoolDate1, 'schoolName2' => $schoolName2, 'schoolAddress2' => $schoolAddress2, 'schoolGrades2' => $schoolGrades2, 'schoolDate2' => $schoolDate2, 'gibbonFamilyID' => $gibbonFamilyID, 'parent1gibbonPersonID' => $parent1gibbonPersonID, 'parent1title' => $parent1title, 'parent1surname' => $parent1surname, 'parent1firstName' => $parent1firstName, 'parent1preferredName' => $parent1preferredName, 'parent1officialName' => $parent1officialName, 'parent1nameInCharacters' => $parent1nameInCharacters, 'parent1gender' => $parent1gender, 'parent1relationship' => $parent1relationship, 'parent1languageFirst' => $parent1languageFirst, 'parent1languageSecond' => $parent1languageSecond, 'parent1email' => $parent1email, 'parent1phone1Type' => $parent1phone1Type, 'parent1phone1CountryCode' => $parent1phone1CountryCode, 'parent1phone1' => $parent1phone1, 'parent1phone2Type' => $parent1phone2Type, 'parent1phone2CountryCode' => $parent1phone2CountryCode, 'parent1phone2' => $parent1phone2, 'parent1profession' => $parent1profession, 'parent1employer' => $parent1employer, 'parent2title' => $parent2title, 'parent2surname' => $parent2surname, 'parent2firstName' => $parent2firstName, 'parent2preferredName' => $parent2preferredName, 'parent2officialName' => $parent2officialName, 'parent2nameInCharacters' => $parent2nameInCharacters, 'parent2gender' => $parent2gender, 'parent2relationship' => $parent2relationship, 'parent2languageFirst' => $parent2languageFirst, 'parent2languageSecond' => $parent2languageSecond, 'parent2email' => $parent2email, 'parent2phone1Type' => $parent2phone1Type, 'parent2phone1CountryCode' => $parent2phone1CountryCode, 'parent2phone1' => $parent2phone1, 'parent2phone2Type' => $parent2phone2Type, 'parent2phone2CountryCode' => $parent2phone2CountryCode, 'parent2phone2' => $parent2phone2, 'parent2profession' => $parent2profession, 'parent2employer' => $parent2employer, 'siblingName1' => $siblingName1, 'siblingDOB1' => $siblingDOB1, 'siblingSchool1' => $siblingSchool1, 'siblingSchoolJoiningDate1' => $siblingSchoolJoiningDate1, 'siblingName2' => $siblingName2, 'siblingDOB2' => $siblingDOB2, 'siblingSchool2' => $siblingSchool2, 'siblingSchoolJoiningDate2' => $siblingSchoolJoiningDate2, 'siblingName3' => $siblingName3, 'siblingDOB3' => $siblingDOB3, 'siblingSchool3' => $siblingSchool3, 'siblingSchoolJoiningDate3' => $siblingSchoolJoiningDate3, 'languageChoice' => $languageChoice, 'languageChoiceExperience' => $languageChoiceExperience, 'scholarshipInterest' => $scholarshipInterest, 'scholarshipRequired' => $scholarshipRequired, 'payment' => $payment, 'companyName' => $companyName, 'companyContact' => $companyContact, 'companyAddress' => $companyAddress, 'companyEmail' => $companyEmail, 'companyCCFamily' => $companyCCFamily, 'companyPhone' => $companyPhone, 'companyAll' => $companyAll, 'gibbonFinanceFeeCategoryIDList' => $gibbonFinanceFeeCategoryIDList, 'howDidYouHear' => $howDidYouHear, 'howDidYouHearMore' => $howDidYouHearMore, 'studentID' => $studentID, 'privacy' => $privacy, 'fields' => $fields, 'parent1fields' => $parent1fields, 'parent2fields' => $parent2fields, 'gibbonApplicationFormID' => $gibbonApplicationFormID);
                        $sql = 'UPDATE gibbonApplicationForm SET priority=:priority, status=:status, milestones=:milestones, dateStart=:dateStart, gibbonFormGroupID=:gibbonFormGroupID, paymentMade=:paymentMade, paymentMade2=:paymentMade2, notes=:notes, surname=:surname, firstName=:firstName, preferredName=:preferredName, officialName=:officialName, nameInCharacters=:nameInCharacters, gender=:gender, username=:username, dob=:dob, languageHomePrimary=:languageHomePrimary, languageHomeSecondary=:languageHomeSecondary, languageFirst=:languageFirst, languageSecond=:languageSecond, languageThird=:languageThird, countryOfBirth=:countryOfBirth, email=:email, homeAddress=:homeAddress, homeAddressDistrict=:homeAddressDistrict, homeAddressCountry=:homeAddressCountry, phone1Type=:phone1Type, phone1CountryCode=:phone1CountryCode, phone1=:phone1, phone2Type=:phone2Type, phone2CountryCode=:phone2CountryCode, phone2=:phone2, medicalInformation=:medicalInformation, sen=:sen, senDetails=:senDetails, gibbonSchoolYearIDEntry=:gibbonSchoolYearIDEntry, gibbonYearGroupIDEntry=:gibbonYearGroupIDEntry, dayType=:dayType, referenceEmail=:referenceEmail, schoolName1=:schoolName1, schoolAddress1=:schoolAddress1, schoolGrades1=:schoolGrades1, schoolDate1=:schoolDate1, schoolName2=:schoolName2, schoolAddress2=:schoolAddress2, schoolGrades2=:schoolGrades2, schoolDate2=:schoolDate2, gibbonFamilyID=:gibbonFamilyID, parent1gibbonPersonID=:parent1gibbonPersonID, parent1title=:parent1title, parent1surname=:parent1surname, parent1firstName=:parent1firstName, parent1preferredName=:parent1preferredName, parent1officialName=:parent1officialName, parent1nameInCharacters=:parent1nameInCharacters, parent1gender=:parent1gender, parent1relationship=:parent1relationship, parent1languageFirst=:parent1languageFirst, parent1languageSecond=:parent1languageSecond, parent1email=:parent1email, parent1phone1Type=:parent1phone1Type, parent1phone1CountryCode=:parent1phone1CountryCode, parent1phone1=:parent1phone1, parent1phone2Type=:parent1phone2Type, parent1phone2CountryCode=:parent1phone2CountryCode, parent1phone2=:parent1phone2, parent1profession=:parent1profession, parent1employer=:parent1employer, parent2title=:parent2title, parent2surname=:parent2surname, parent2firstName=:parent2firstName, parent2preferredName=:parent2preferredName, parent2officialName=:parent2officialName, parent2nameInCharacters=:parent2nameInCharacters, parent2gender=:parent2gender, parent2relationship=:parent2relationship, parent2languageFirst=:parent2languageFirst, parent2languageSecond=:parent2languageSecond, parent2email=:parent2email, parent2phone1Type=:parent2phone1Type, parent2phone1CountryCode=:parent2phone1CountryCode, parent2phone1=:parent2phone1, parent2phone2Type=:parent2phone2Type, parent2phone2CountryCode=:parent2phone2CountryCode, parent2phone2=:parent2phone2, parent2profession=:parent2profession, parent2employer=:parent2employer, siblingName1=:siblingName1, siblingDOB1=:siblingDOB1, siblingSchool1=:siblingSchool1, siblingSchoolJoiningDate1=:siblingSchoolJoiningDate1, siblingName2=:siblingName2, siblingDOB2=:siblingDOB2, siblingSchool2=:siblingSchool2, siblingSchoolJoiningDate2=:siblingSchoolJoiningDate2, siblingName3=:siblingName3, siblingDOB3=:siblingDOB3, siblingSchool3=:siblingSchool3, siblingSchoolJoiningDate3=:siblingSchoolJoiningDate3, languageChoice=:languageChoice, languageChoiceExperience=:languageChoiceExperience, scholarshipInterest=:scholarshipInterest, scholarshipRequired=:scholarshipRequired, payment=:payment, companyName=:companyName, companyContact=:companyContact, companyAddress=:companyAddress, companyEmail=:companyEmail, companyCCFamily=:companyCCFamily, companyPhone=:companyPhone, companyAll=:companyAll, gibbonFinanceFeeCategoryIDList=:gibbonFinanceFeeCategoryIDList, howDidYouHear=:howDidYouHear, howDidYouHearMore=:howDidYouHearMore, studentID=:studentID, privacy=:privacy, fields=:fields, parent1fields=:parent1fields, parent2fields=:parent2fields WHERE gibbonApplicationFormID=:gibbonApplicationFormID';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $URL .= '&return=error2';
                        header("Location: {$URL}");
                        exit();
                    }

                    $partialFail = false;

                    //Deal with required documents
                    $requiredDocuments = $settingGateway->getSettingByScope('Application Form', 'requiredDocuments');
                    $internalDocuments = $settingGateway->getSettingByScope('Application Form', 'internalDocuments');
                    if ($internalDocuments != '') {
                        $requiredDocuments .= ','.$internalDocuments;
                    }
                    if ($requiredDocuments != '' and $requiredDocuments != false) {
                        $fileCount = 0;
                        if (isset($_POST['fileCount'])) {
                            $fileCount = $_POST['fileCount'] ?? '';
                        }

                        $fileUploader = new Gibbon\FileUploader($pdo, $session);

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
