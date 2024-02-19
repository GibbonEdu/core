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

use Gibbon\Data\Validator;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Services\Format;
use Gibbon\Contracts\Comms\Mailer;
use Gibbon\Comms\NotificationEvent;
use Gibbon\Forms\CustomFieldHandler;
use Gibbon\Forms\PersonalDocumentHandler;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

//Check to see if system settings are set from databases
if (!$session->has('systemSettingsSet')) {
    getSystemSettings($guid, $connection2);
}

//Module includes from User Admin (for custom fields)
include '../User Admin/moduleFunctions.php';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Staff/applicationForm.php';

$settingGateway = $container->get(SettingGateway::class);

$proceed = false;
$public = false;
if (!$session->has('username')) {
    $public = true;
    //Get public access
    $access = $settingGateway->getSettingByScope('Staff Application Form', 'staffApplicationFormPublicApplications');
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

    // Check the honey pot field, it should always be empty
    if (!empty($_POST['emailAddress'])) {
        $URL .= '&return=warning1';
        header("Location: {$URL}");
        exit;
    }

    $gibbonStaffJobOpeningIDs = $_POST['gibbonStaffJobOpeningID'] ?? '';
    $questions = $_POST['questions'] ?? '';
    $gibbonPersonID = !empty($_POST['gibbonPersonID']) ? $_POST['gibbonPersonID'] : null;
    $surname = $_POST['surname'] ?? '';
    $firstName = $_POST['firstName'] ?? '';
    $preferredName = $_POST['preferredName'] ?? '';
    $officialName = $_POST['officialName'] ?? '';
    $nameInCharacters = $_POST['nameInCharacters'] ?? '';
    $gender = $_POST['gender'] ?? 'Unspecified';
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
    $phone1CountryCode = null;
    if (isset($_POST['phone1CountryCode'])) {
        $phone1CountryCode = $_POST['phone1CountryCode'] ?? '';
    }
    $phone1 = null;
    if (isset($_POST['phone1'])) {
        $phone1 = preg_replace('/[^0-9+]/', '', $_POST['phone1']);
    }
    $homeAddress = $_POST['homeAddress'] ?? '';
    $homeAddressDistrict = $_POST['homeAddressDistrict'] ?? '';
    $homeAddressCountry = $_POST['homeAddressCountry'] ?? '';
    $referenceEmail1 = $_POST['referenceEmail1'] ?? '';
    $referenceEmail2 = $_POST['referenceEmail2'] ?? '';
    $agreement = isset($_POST['agreement']) ? ($_POST['agreement'] == 'on' ? 'Y' : 'N') : null;


    //VALIDATE INPUTS
    if (count($gibbonStaffJobOpeningIDs) < 1 or ($gibbonPersonID == null and ($surname == '' or $firstName == '' or $preferredName == '' or $officialName == '' or $gender == '' or $dob == '' or $languageFirst == '' or $email == '' or $homeAddress == '' or $homeAddressDistrict == '' or $homeAddressCountry == '' or $phone1 == '')) or (isset($_POST['referenceEmail1']) and $referenceEmail1 == '') or (isset($_POST['referenceEmail2']) and $referenceEmail2 == '') or (isset($_POST['agreement']) and $agreement != 'Y')) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        //DEAL WITH CUSTOM FIELDS
        $customRequireFail = false;
        $customFieldHandler = $container->get(CustomFieldHandler::class);

        $fields = $customFieldHandler->getFieldDataFromPOST('User', ['staff' => 1, 'applicationForm' => 1], $customRequireFail);
        $staffFields = $customFieldHandler->getFieldDataFromPOST('Staff', ['applicationForm' => 1, 'prefix' => 'customStaff'], $customRequireFail);

        if ($customRequireFail) {
            $URL .= '&return=error1';
            header("Location: {$URL}");
        } else {
            $partialFail = false;
            $ids = '';

            //Deal with required documents
            $uploadedDocuments = array();
            $requiredDocuments = $settingGateway->getSettingByScope('Staff', 'staffApplicationFormRequiredDocuments');
            if (!empty($requiredDocuments)) {
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
                    $attachment = $fileUploader->uploadFromPost($file, 'StaffApplicationDocument');

                    if (!empty($attachment)) {
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
                        $data = array('gibbonStaffJobOpeningID' => $gibbonStaffJobOpeningID, 'questions' => $questions, 'gibbonPersonID' => $gibbonPersonID, 'surname' => $surname, 'firstName' => $firstName, 'preferredName' => $preferredName, 'officialName' => $officialName, 'nameInCharacters' => $nameInCharacters, 'gender' => $gender, 'dob' => $dob, 'languageFirst' => $languageFirst, 'languageSecond' => $languageSecond, 'languageThird' => $languageThird, 'countryOfBirth' => $countryOfBirth, 'email' => $email, 'homeAddress' => $homeAddress, 'homeAddressDistrict' => $homeAddressDistrict, 'homeAddressCountry' => $homeAddressCountry, 'phone1Type' => $phone1Type, 'phone1CountryCode' => $phone1CountryCode, 'phone1' => $phone1, 'referenceEmail1' => $referenceEmail1, 'referenceEmail2' => $referenceEmail2, 'agreement' => $agreement, 'staffFields' => $staffFields, 'fields' => $fields, 'timestamp' => date('Y-m-d H:i:s'));
                        $sql = 'INSERT INTO gibbonStaffApplicationForm SET gibbonStaffJobOpeningID=:gibbonStaffJobOpeningID, questions=:questions, gibbonPersonID=:gibbonPersonID, surname=:surname, firstName=:firstName, preferredName=:preferredName, officialName=:officialName, nameInCharacters=:nameInCharacters, gender=:gender, dob=:dob, languageFirst=:languageFirst, languageSecond=:languageSecond, languageThird=:languageThird, countryOfBirth=:countryOfBirth, email=:email, homeAddress=:homeAddress, homeAddressDistrict=:homeAddressDistrict, homeAddressCountry=:homeAddressCountry, phone1Type=:phone1Type, phone1CountryCode=:phone1CountryCode, phone1=:phone1, referenceEmail1=:referenceEmail1, referenceEmail2=:referenceEmail2, agreement=:agreement, fields=:fields, staffFields=:staffFields, timestamp=:timestamp';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        exit();
                        $partialFail = true;
                        $thisFail = true;
                    }

                    if (!$thisFail) {
                        //Last insert ID
                        $AI = str_pad($connection2->lastInsertID(), 7, '0', STR_PAD_LEFT);
                        $ids .= $AI.', ';

                        // PERSONAL DOCUMENTS
                        $params = ['staff' => true, 'applicationForm' => true];
                        $container->get(PersonalDocumentHandler::class)->updateDocumentsFromPOST('gibbonStaffApplicationForm', $AI, $params, $partialFail);

                        // Attach required documents
                        if ($requiredDocuments != false && !empty($uploadedDocuments) && is_array($uploadedDocuments)) {
                            foreach ($uploadedDocuments as $fileName => $attachment) {
                                //Write files to database, one for each attachment

                                    $dataFile = array('gibbonStaffApplicationFormID' => $AI, 'name' => $fileName, 'path' => $attachment);
                                    $sqlFile = 'INSERT INTO gibbonStaffApplicationFormFile SET gibbonStaffApplicationFormID=:gibbonStaffApplicationFormID, name=:name, path=:path';
                                    $resultFile = $connection2->prepare($sqlFile);
                                    $resultFile->execute($dataFile);
                            }
                        }

                        // Raise a new notification event
                        $event = new NotificationEvent('Staff', 'New Application Form');

                        $event->addRecipient($session->get('organisationHR'));
                        $event->setNotificationText(sprintf(__('An application form has been submitted for %1$s.'), Format::name('', $preferredName, $surname, 'Student')));
                        $event->setActionLink("/index.php?q=/modules/Staff/applicationForm_manage_edit.php&gibbonStaffApplicationFormID=$AI&search=");

                        $event->sendNotifications($pdo, $session);

                        //Email reference form link to referee
                        $applicationFormRefereeLink = unserialize($settingGateway->getSettingByScope('Staff', 'applicationFormRefereeLink'));
                        if (is_array($applicationFormRefereeLink) && !empty($applicationFormRefereeLink[$type]) and ($referenceEmail1 != '' or $refereeEmail2 != '') and $session->get('organisationHRName') != '' and $session->get('organisationHREmail') != '') {
                            //Prep message
                            $subject = __('Request For Reference');
                            $body = sprintf(__('To whom it may concern,%4$sThis email is being sent in relation to the job application of an individual who has nominated you as a referee: %1$s.%4$sIn assessing their application for the post of %5$s at our school, we would like to enlist your help in completing the following reference form: %2$s.<br/><br/>Please feel free to contact me, should you have any questions in regard to this matter.%4$sRegards,%4$s%3$s'), Format::name('', $preferredName, $surname, 'Staff', false, true), "<a href='" . $applicationFormRefereeLink[$type] . "' target='_blank'>" . $applicationFormRefereeLink[$type] . "</a>", $session->get('organisationHRName'), '<br/><br/>', $jobTitle);

                            $mail = $container->get(Mailer::class);
                            $mail->SetFrom($session->get('organisationHREmail'), $session->get('organisationHRName'));
                            if ($referenceEmail1 != '') {
                                $mail->AddBCC($referenceEmail1);
                            }
                            if ($referenceEmail2 != '') {
                                $mail->AddBCC($referenceEmail2);
                            }
                            $mail->Subject = $subject;
                            $mail->renderBody('mail/email.twig.html', [
                                'title'  => $subject,
                                'body'   => $body,
                                'button' => [
                                    'url'  => $applicationFormRefereeLink[$type],
                                    'text' => __('Click Here'),
                                    'external' => true,
                                ],
                            ]);

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
