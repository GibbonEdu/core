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
use Gibbon\Forms\CustomFieldHandler;
use Gibbon\Forms\PersonalDocumentHandler;
use Gibbon\Data\Validator;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST, ['questions' => 'HTML']);

//Module includes from User Admin (for custom fields)
include '../User Admin/moduleFunctions.php';

$gibbonStaffApplicationFormID = $_POST['gibbonStaffApplicationFormID'] ?? '';
$search = $_GET['search'] ?? '';
$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address'])."/applicationForm_manage_edit.php&gibbonStaffApplicationFormID=$gibbonStaffApplicationFormID&search=$search";

if (isActionAccessible($guid, $connection2, '/modules/Staff/applicationForm_manage_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    //Check if gibbonStaffApplicationFormID specified

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
            $settingGateway = $container->get(SettingGateway::class);
            //Get student fields
            $priority = $_POST['priority'] ?? '';
            $status = $_POST['status'] ?? '';
            $milestones = '';
            $milestonesMaster = explode(',', $settingGateway->getSettingByScope('Staff', 'staffApplicationFormMilestones'));
            foreach ($milestonesMaster as $milestoneMaster) {
                if (isset($_POST['milestone_'.preg_replace('/\s+/', '', $milestoneMaster)])) {
                    if ($_POST['milestone_'.preg_replace('/\s+/', '', $milestoneMaster)] == 'on') {
                        $milestones .= trim($milestoneMaster).',';
                    }
                }
            }
            $milestones = substr($milestones, 0, -1);
            $dateStart = !empty($_POST['dateStart']) ? Format::dateConvert($_POST['dateStart']) : null;
            $notes = $_POST['notes'] ?? '';
            $gibbonStaffJobOpeningID = $_POST['gibbonStaffJobOpeningID'] ?? '';
            $questions = $_POST['questions'] ?? '';
            $gibbonPersonID = $_POST['gibbonPersonID'] ?? '';
            $surname = $_POST['surname'] ?? '';
            $firstName = $_POST['firstName'] ?? '';
            $preferredName = $_POST['preferredName'] ?? '';
            $officialName = $_POST['officialName'] ?? '';
            $nameInCharacters = $_POST['nameInCharacters'] ?? '';
            $gender = $_POST['gender'] ?? '';
            $dob = !empty($_POST['dob']) ? Format::dateConvert($_POST['dob']) : null;
            $languageFirst = $_POST['languageFirst'] ?? '';
            $languageSecond = $_POST['languageSecond'] ?? '';
            $languageThird = $_POST['languageThird'] ?? '';
            $countryOfBirth = $_POST['countryOfBirth'] ?? '';
            $email = $_POST['email'] ?? '';
            $phone1Type = null;
            if (isset($_POST['phone1Type'])) {
                $phone1Type = $_POST['phone1Type'] ?? '';
                if ($_POST['phone1'] != '' and $phone1Type == '') {
                    $phone1Type = 'Other';
                }
            }
            $phone1CountryCode = $_POST['phone1CountryCode'] ?? '';
            $phone1 = preg_replace('/[^0-9+]/', '', $_POST['phone1'] ?? '');
            $homeAddress = $_POST['homeAddress'] ?? '';
            $homeAddressDistrict = $_POST['homeAddressDistrict'] ?? '';
            $homeAddressCountry = $_POST['homeAddressCountry'] ?? '';
            $referenceEmail1 = $_POST['referenceEmail1'] ?? '';
            $referenceEmail2 = $_POST['referenceEmail2'] ?? '';

            if ($gibbonStaffJobOpeningID == '' or ($gibbonPersonID == null and ($surname == '' or $firstName == '' or $preferredName == '' or $officialName == '' or $gender == '' or $dob == '' or $languageFirst == '' or $email == '' or $homeAddress == '' or $homeAddressDistrict == '' or $homeAddressCountry == '' or $phone1 == '')) or (isset($_POST['referenceEmail1']) and $referenceEmail1 == '') or (isset($_POST['referenceEmail2']) and $referenceEmail2 == '')) {
                $URL .= '&return=error3';
                header("Location: {$URL}");
            } else {
                // CUSTOM FIELDS
                $customRequireFail = false;
                $customFieldHandler = $container->get(CustomFieldHandler::class);

                $fields = $customFieldHandler->getFieldDataFromPOST('User', ['staff' => 1, 'applicationForm' => 1], $customRequireFail);
                $staffFields = $customFieldHandler->getFieldDataFromPOST('Staff', ['applicationForm' => 1, 'prefix' => 'customStaff'], $customRequireFail);

                // PERSONAL DOCUMENTS
                $personalDocumentFail = false;
                $params = ['staff' => true, 'applicationForm' => true];
                $container->get(PersonalDocumentHandler::class)->updateDocumentsFromPOST('gibbonStaffApplicationForm', $gibbonStaffApplicationFormID, $params, $personalDocumentFail);

                if ($customRequireFail || $personalDocumentFail) {
                    $URL .= '&return=error3';
                    header("Location: {$URL}");
                } else {
                    //Write to database
                    try {
                        $data = array('priority' => $priority, 'status' => $status, 'milestones' => $milestones, 'dateStart' => $dateStart, 'notes' => $notes, 'surname' => $surname, 'firstName' => $firstName, 'preferredName' => $preferredName, 'officialName' => $officialName, 'nameInCharacters' => $nameInCharacters, 'gender' => $gender, 'dob' => $dob, 'languageFirst' => $languageFirst, 'languageSecond' => $languageSecond, 'languageThird' => $languageThird, 'countryOfBirth' => $countryOfBirth, 'email' => $email, 'homeAddress' => $homeAddress, 'homeAddressDistrict' => $homeAddressDistrict, 'homeAddressCountry' => $homeAddressCountry, 'phone1Type' => $phone1Type, 'phone1CountryCode' => $phone1CountryCode, 'phone1' => $phone1, 'referenceEmail1' => $referenceEmail1, 'referenceEmail2' => $referenceEmail2, 'fields' => $fields, 'staffFields' => $staffFields, 'gibbonStaffApplicationFormID' => $gibbonStaffApplicationFormID);
                        $sql = 'UPDATE gibbonStaffApplicationForm SET priority=:priority, status=:status, milestones=:milestones, dateStart=:dateStart, notes=:notes, surname=:surname, firstName=:firstName, preferredName=:preferredName, officialName=:officialName, nameInCharacters=:nameInCharacters, gender=:gender, dob=:dob, languageFirst=:languageFirst, languageSecond=:languageSecond, languageThird=:languageThird, countryOfBirth=:countryOfBirth, email=:email, homeAddress=:homeAddress, homeAddressDistrict=:homeAddressDistrict, homeAddressCountry=:homeAddressCountry, phone1Type=:phone1Type, phone1CountryCode=:phone1CountryCode, phone1=:phone1, referenceEmail1=:referenceEmail1, referenceEmail2=:referenceEmail2, fields=:fields, staffFields=:staffFields WHERE gibbonStaffApplicationFormID=:gibbonStaffApplicationFormID';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $URL .= '&return=error2';
                        header("Location: {$URL}");
                        exit();
                    }

                    $partialFail = false;

                    //Deal with required documents
                    $requiredDocuments = $settingGateway->getSettingByScope('Staff', 'staffApplicationFormRequiredDocuments');
                    if ($requiredDocuments != '' and $requiredDocuments != false) {
                        $fileCount = 0;
                        if (isset($_POST['fileCount'])) {
                            $fileCount = $_POST['fileCount'] ?? '';
                        }

                        $fileUploader = new Gibbon\FileUploader($pdo, $session);

                        for ($i = 0; $i < $fileCount; ++$i) {
                            if (empty($_FILES["file$i"]['tmp_name'])) continue;

                            $file = (isset($_FILES["file$i"]))? $_FILES["file$i"] : null;
                            $fileName = (isset($_POST["fileName$i"]))? $_POST["fileName$i"] : null;

                            // Upload the file, return the /uploads relative path
                            $attachment = $fileUploader->uploadFromPost($file, 'ApplicationDocument');

                            // Write files to database, if there is one
                            if (!empty($attachment)) {

                                    $dataFile = array('gibbonStaffApplicationFormID' => $gibbonStaffApplicationFormID, 'name' => $fileName, 'path' => $attachment);
                                    $sqlFile = 'INSERT INTO gibbonStaffApplicationFormFile SET gibbonStaffApplicationFormID=:gibbonStaffApplicationFormID, name=:name, path=:path';
                                    $resultFile = $connection2->prepare($sqlFile);
                                    $resultFile->execute($dataFile);
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
