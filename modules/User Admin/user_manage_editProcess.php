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
use Gibbon\Comms\NotificationEvent;
use Gibbon\Comms\NotificationSender;
use Gibbon\Domain\System\LogGateway;
use Gibbon\Forms\CustomFieldHandler;
use Gibbon\Forms\PersonalDocumentHandler;
use Gibbon\Domain\System\NotificationGateway;
use Gibbon\Domain\User\UserStatusLogGateway;
use Gibbon\Data\Validator;
use Gibbon\Domain\User\RoleGateway;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST, ['website' => 'URL']);

//Module includes
include './moduleFunctions.php';

$logGateway = $container->get(LogGateway::class);
$gibbonPersonID = $_GET['gibbonPersonID'] ?? '';
$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address'])."/user_manage_edit.php&gibbonPersonID=$gibbonPersonID&search=".$_GET['search'];

if (isActionAccessible($guid, $connection2, '/modules/User Admin/user_manage_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    //Check if gibbonPersonID specified
    if ($gibbonPersonID == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        try {
            $data = array('gibbonPersonID' => $gibbonPersonID);
            $sql = 'SELECT * FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID';
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
            $row = $result->fetch();

            //Get categories
            $staff = false;
            $student = false;
            $parent = false;
            $other = false;
            $roles = explode(',', $row['gibbonRoleIDAll']);

            /** @var RoleGateway */
            $roleGateway = $container->get(RoleGateway::class);

            foreach ($roles as $role) {
                $roleCategory = $roleGateway->getRoleCategory($role);
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
            $title = $_POST['title'] ?? '';
            $surname = trim($_POST['surname'] ?? '');
            $firstName = trim($_POST['firstName'] ?? '');
            $preferredName = trim($_POST['preferredName'] ?? '');
            $officialName = trim($_POST['officialName'] ?? '');
            $nameInCharacters = $_POST['nameInCharacters'] ?? '';
            $gender = $_POST['gender'] ?? '';
            $username = isset($_POST['username'])? $_POST['username'] : $values['username'];
            $status = $_POST['status'] ?? '';
            $canLogin = $_POST['canLogin'] ?? '';
            $passwordForceReset = $_POST['passwordForceReset'] ?? '';

            // Put together an array of this user's current roles
            $currentUserRoles = (is_array($session->get('gibbonRoleIDAll'))) ? array_column($session->get('gibbonRoleIDAll'), 0) : array();
            $currentUserRoles[] = $session->get('gibbonRoleIDPrimary');

            $sqlRoles = 'SELECT gibbonRoleID, restriction, name FROM gibbonRole';
            $resultRoles = $connection2->prepare($sqlRoles);
            $resultRoles->execute();

            $gibbonRoleIDAll = array();
            $gibbonRoleIDPrimary = $row['gibbonRoleIDPrimary'];

            $selectedRoleIDPrimary = (isset($_POST['gibbonRoleIDPrimary'])) ? $_POST['gibbonRoleIDPrimary'] : null;
            $selectedRoleIDAll = (isset($_POST['gibbonRoleIDAll'])) ? $_POST['gibbonRoleIDAll'] : array();

            if ($resultRoles && $resultRoles->rowCount() > 0) {
                while ($rowRole = $resultRoles->fetch()) {

                    if ($rowRole['restriction'] == 'Admin Only') {
                        if (in_array('001', $currentUserRoles)) {
                            // Add selected roles only if they meet the restriction
                            if (in_array($rowRole['gibbonRoleID'], $selectedRoleIDAll)) {
                                $gibbonRoleIDAll[] = $rowRole['gibbonRoleID'];
                            }

                            if ($rowRole['gibbonRoleID'] == $selectedRoleIDPrimary) {
                                // Prevent primary role being changed to a restricted role (via modified POST)
                                $gibbonRoleIDPrimary = $selectedRoleIDPrimary;
                            }
                        } else if (in_array($rowRole['gibbonRoleID'], $roles)) {
                            // Add existing restricted roles because they cannot be removed by this user
                            $gibbonRoleIDAll[] = $rowRole['gibbonRoleID'];
                        }
                    } else if ($rowRole['restriction'] == 'Same Role') {
                        if (in_array($rowRole['gibbonRoleID'], $currentUserRoles) || in_array('001', $currentUserRoles)) {
                            if (in_array($rowRole['gibbonRoleID'], $selectedRoleIDAll)) {
                                $gibbonRoleIDAll[] = $rowRole['gibbonRoleID'];
                            }

                            if ($rowRole['gibbonRoleID'] == $selectedRoleIDPrimary) {
                                $gibbonRoleIDPrimary = $selectedRoleIDPrimary;
                            }
                        } else if (in_array($rowRole['gibbonRoleID'], $roles)) {
                            $gibbonRoleIDAll[] = $rowRole['gibbonRoleID'];
                        }
                    } else {
                        if (in_array($rowRole['gibbonRoleID'], $selectedRoleIDAll)) {
                            $gibbonRoleIDAll[] = $rowRole['gibbonRoleID'];
                        }

                        if ($rowRole['gibbonRoleID'] == $selectedRoleIDPrimary) {
                            $gibbonRoleIDPrimary = $selectedRoleIDPrimary;
                        }
                    }
                }
            }

            // Ensure the primary role is always in the all roles list
            if (!in_array($gibbonRoleIDPrimary, $gibbonRoleIDAll)) {
                $gibbonRoleIDAll[] = $gibbonRoleIDPrimary;
            }

            $gibbonRoleIDAll = (is_array($gibbonRoleIDAll))? implode(',', array_unique($gibbonRoleIDAll)) : $row['gibbonRoleIDAll'];

            $dob = !empty($_POST['dob']) ? Format::dateConvert($_POST['dob']) : null;
            $email = trim($_POST['email'] ?? '');
            $emailAlternate = trim($_POST['emailAlternate'] ?? '');
            $address1 = $_POST['address1'] ?? '';
            $address1District = $_POST['address1District'] ?? '';
            $address1Country = $_POST['address1Country'] ?? '';
            $address2 = $_POST['address2'] ?? '';
            $address2District = $_POST['address2District'] ?? '';
            $address2Country = $_POST['address2Country'] ?? '';
            $phone1Type = $_POST['phone1Type'] ?? '';
            if ($_POST['phone1'] != '' && $phone1Type == '') {
                $phone1Type = 'Other';
            }
            $phone1CountryCode = $_POST['phone1CountryCode'] ?? '';
            $phone1 = preg_replace('/[^0-9+]/', '', $_POST['phone1'] ?? '');
            $phone2Type = $_POST['phone2Type'] ?? '';
            if ($_POST['phone2'] != '' && $phone2Type == '') {
                $phone2Type = 'Other';
            }
            $phone2CountryCode = $_POST['phone2CountryCode'] ?? '';
            $phone2 = preg_replace('/[^0-9+]/', '', $_POST['phone2'] ?? '');
            $phone3Type = $_POST['phone3Type'] ?? '';
            if ($_POST['phone3'] != '' && $phone3Type == '') {
                $phone3Type = 'Other';
            }
            $phone3CountryCode = $_POST['phone3CountryCode'] ?? '';
            $phone3 = preg_replace('/[^0-9+]/', '', $_POST['phone3'] ?? '');
            $phone4Type = $_POST['phone4Type'] ?? '';
            if ($_POST['phone4'] != '' && $phone4Type == '') {
                $phone4Type = 'Other';
            }
            $phone4CountryCode = $_POST['phone4CountryCode'] ?? '';
            $phone4 = preg_replace('/[^0-9+]/', '', $_POST['phone4'] ?? '');
            $website = $_POST['website'] ?? '';
            $languageFirst = $_POST['languageFirst'] ?? '';
            $languageSecond = $_POST['languageSecond'] ?? '';
            $languageThird = $_POST['languageThird'] ?? '';
            $countryOfBirth = $_POST['countryOfBirth'] ?? '';
            $ethnicity = $_POST['ethnicity'] ?? '';
            $religion = $_POST['religion'] ?? '';

            $profession = $_POST['profession'] ?? null;
            $employer = $_POST['employer'] ?? null;
            $jobTitle = $_POST['jobTitle'] ?? null;

            $emergency1Name = $_POST['emergency1Name'] ?? null;
            $emergency1Number1 = $_POST['emergency1Number1'] ?? null;
            $emergency1Number2 = $_POST['emergency1Number2'] ?? null;
            $emergency1Relationship = $_POST['emergency1Relationship'] ?? null;

            $emergency2Name = $_POST['emergency2Name'] ?? null;
            $emergency2Number1 = $_POST['emergency2Number1'] ?? null;
            $emergency2Number2 = $_POST['emergency2Number2'] ?? null;
            $emergency2Relationship = $_POST['emergency2Relationship'] ?? null;

            $gibbonHouseID = !empty($_POST['gibbonHouseID']) ? $_POST['gibbonHouseID'] : null;
            $studentID = $_POST['studentID'] ?? null;
            $dateStart = !empty($_POST['dateStart']) ? Format::dateConvert($_POST['dateStart']) : null;
            $dateEnd = !empty($_POST['dateEnd']) ? Format::dateConvert($_POST['dateEnd']) : null;
            $gibbonSchoolYearIDClassOf = !empty($_POST['gibbonSchoolYearIDClassOf']) ? $_POST['gibbonSchoolYearIDClassOf'] : null;
            $lastSchool = $_POST['lastSchool'] ?? null;
            $nextSchool = $_POST['nextSchool'] ?? null;
            $departureReason = $_POST['departureReason'] ?? null;
            $transport = $_POST['transport'] ?? null;
            $transportNotes = $_POST['transportNotes'] ?? null;
            $lockerNumber = $_POST['lockerNumber'] ?? null;
            $vehicleRegistration = $_POST['vehicleRegistration'] ?? '';

            $privacy = !empty($_POST['privacyOptions']) ? implode(',', $_POST['privacyOptions']) : null;
            $privacy_old = $row['privacy'];
            $agreements = !empty($_POST['studentAgreements']) ? implode(',', $_POST['studentAgreements']) : null;
            $dayType = $_POST['dayType'] ?? null;

            //Validate Inputs
            if ($surname == '' || $firstName == '' || $preferredName == '' || $officialName == '' || $gender == '' || $username == '' || $status == '' || $gibbonRoleIDPrimary == '') {
                $URL .= '&return=error3';
                header("Location: {$URL}");
            } else {
                //Check unique inputs for uniquness
                try {
                    $data = array('username' => $username, 'gibbonPersonID' => $gibbonPersonID);
                    $sql = 'SELECT * FROM gibbonPerson WHERE username=:username AND NOT gibbonPersonID=:gibbonPersonID';
                    if ($studentID != '') {
                        $data = array('username' => $username, 'gibbonPersonID' => $gibbonPersonID, 'studentID' => $studentID);
                        $sql = 'SELECT * FROM gibbonPerson WHERE (username=:username OR studentID=:studentID) AND NOT gibbonPersonID=:gibbonPersonID ';
                    }
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                    exit();
                }

                if ($result->rowCount() > 0) {
                    $URL .= '&return=error3';
                    header("Location: {$URL}");
                } else {
                    $imageFail = false;
                    if (!empty($_FILES['file1']['tmp_name']))
                    {
                        $path = $session->get('absolutePath');
                        $fileUploader = new Gibbon\FileUploader($pdo, $session);

                        //Move 240 attached file, if there is one
                        if (!empty($_FILES['file1']['tmp_name'])) {
                            $file = (isset($_FILES['file1']))? $_FILES['file1'] : null;

                            // Upload the file, return the /uploads relative path
                            $fileUploader->setFileSuffixType(Gibbon\FileUploader::FILE_SUFFIX_INCREMENTAL);
                            $attachment1 = $fileUploader->uploadFromPost($file, $username.'_240');

                            if (empty($attachment1)) {
                                $imageFail = true;
                            } else {
                                //Check image sizes
                                if (function_exists('getimagesize') && function_exists('imagecreatetruecolor')) {
                                    $imageSize = getimagesize($path.'/'.$attachment1);
                                    $width = $imageSize[0] ?? '';
                                    $height = $imageSize[1] ?? '';
                                    $aspect = $height / $width;

                                    if ($width > 360 || $height > 480 || $aspect < 1.2 || $aspect > 1.4) {
                                        $src_x = $src_y = $dst_x = $dst_y = 0;
                                        $src_y = 0;
                                        $src_w = $width;
                                        $src_h = $height;
                                        $maxWidth = 360;
                                        $maxHeight = 480;

                                        // New crop if needed
                                        if ($aspect < 1.2) {
                                            $src_w = $height / 1.2;
                                            $src_x = ($width - $src_w) / 2;
                                        }
                                        else if ($aspect > 1.4) {
                                            $src_h = $width * 1.4;
                                            $src_y = ($height - $src_h) / 2;
                                        }

                                        $dst_w = $src_w;
                                        $dst_h = $src_h;

                                        // New compressed image if needed
                                        if ($src_w > $maxWidth) {
                                            $new_ratio = $maxWidth / $src_w;
                                            $dst_w = $maxWidth;
                                            $dst_h = $src_h * $new_ratio;
                                        }
                                        if ($src_h > $maxHeight) {
                                            $new_ratio = $maxHeight / $src_h;
                                            $dst_h = $maxHeight;
                                            $dst_w = $src_w * $new_ratio;
                                        }

                                        $imagePath =  $path.'/'.$attachment1;

                                        // Resampling the image
                                        if (!empty($imageSize) && file_exists($imagePath)) {
                                            $image_p = imagecreatetruecolor($dst_w, $dst_h);
                                            $extension = mb_substr(mb_strrchr(strtolower($imagePath), '.'), 1);

                                            switch ($extension) {
                                                case 'png':     $image = imagecreatefrompng($imagePath); break;
                                                case 'gif':     $image = imagecreatefromgif($imagePath); break;
                                                case 'webp':    $image = imagecreatefromwebp($imagePath); break;
                                                default:        $image = imagecreatefromjpeg($imagePath);
                                            }

                                            imagecopyresampled($image_p, $image,
                                                            $dst_x, $dst_y,
                                                            $src_x, $src_y,
                                                            $dst_w, $dst_h,
                                                            $src_w, $src_h);

                                            imagedestroy($image);
                                            imagejpeg($image_p, $imagePath, 100);
                                        }
                                    }
                                }
                            }
                        }
                    } else {
                        // Remove the attachment if it has been deleted, otherwise retain the original value
                        $attachment1 = empty($_POST['attachment1']) ? '' : $row['image_240'];
                    }

                    // CUSTOM FIELDS
                    $customRequireFail = false;
                    $params = compact('student', 'staff', 'parent', 'other');
                    $fields = $container->get(CustomFieldHandler::class)->getFieldDataFromPOST('User', $params, $customRequireFail);

                    // PERSONAL DOCUMENTS
                    $personalDocumentFail = false;
                    $params = compact('student', 'staff', 'parent', 'other');
                    $container->get(PersonalDocumentHandler::class)->updateDocumentsFromPOST('gibbonPerson', $gibbonPersonID, $params, $personalDocumentFail);

                    if ($customRequireFail) {
                        $URL .= '&return=error3';
                        header("Location: {$URL}");
                    } else {
                        //Write to database
                        try {
                            $data = array('title' => $title, 'surname' => $surname, 'firstName' => $firstName, 'preferredName' => $preferredName, 'officialName' => $officialName, 'nameInCharacters' => $nameInCharacters, 'gender' => $gender, 'username' => $username, 'status' => $status, 'canLogin' => $canLogin, 'passwordForceReset' => $passwordForceReset, 'gibbonRoleIDPrimary' => $gibbonRoleIDPrimary, 'gibbonRoleIDAll' => $gibbonRoleIDAll, 'dob' => $dob, 'email' => $email, 'emailAlternate' => $emailAlternate, 'address1' => $address1, 'address1District' => $address1District, 'address1Country' => $address1Country, 'address2' => $address2, 'address2District' => $address2District, 'address2Country' => $address2Country, 'phone1Type' => $phone1Type, 'phone1CountryCode' => $phone1CountryCode, 'phone1' => $phone1, 'phone2Type' => $phone2Type, 'phone2CountryCode' => $phone2CountryCode, 'phone2' => $phone2, 'phone3Type' => $phone3Type, 'phone3CountryCode' => $phone3CountryCode, 'phone3' => $phone3, 'phone4Type' => $phone4Type, 'phone4CountryCode' => $phone4CountryCode, 'phone4' => $phone4, 'website' => $website, 'languageFirst' => $languageFirst, 'languageSecond' => $languageSecond, 'languageThird' => $languageThird, 'countryOfBirth' => $countryOfBirth, 'ethnicity' => $ethnicity, 'religion' => $religion, 'emergency1Name' => $emergency1Name, 'emergency1Number1' => $emergency1Number1, 'emergency1Number2' => $emergency1Number2, 'emergency1Relationship' => $emergency1Relationship, 'emergency2Name' => $emergency2Name, 'emergency2Number1' => $emergency2Number1, 'emergency2Number2' => $emergency2Number2, 'emergency2Relationship' => $emergency2Relationship, 'profession' => $profession, 'employer' => $employer, 'jobTitle' => $jobTitle, 'attachment1' => $attachment1, 'gibbonHouseID' => $gibbonHouseID, 'studentID' => $studentID, 'dateStart' => $dateStart, 'dateEnd' => $dateEnd, 'gibbonSchoolYearIDClassOf' => $gibbonSchoolYearIDClassOf, 'lastSchool' => $lastSchool, 'nextSchool' => $nextSchool, 'departureReason' => $departureReason, 'transport' => $transport, 'transportNotes' => $transportNotes, 'lockerNumber' => $lockerNumber, 'vehicleRegistration' => $vehicleRegistration, 'privacy' => $privacy, 'agreements' => $agreements, 'dayType' => $dayType, 'fields' => $fields, 'gibbonPersonID' => $gibbonPersonID);
                            $sql = 'UPDATE gibbonPerson SET title=:title, surname=:surname, firstName=:firstName, preferredName=:preferredName, officialName=:officialName, nameInCharacters=:nameInCharacters, gender=:gender, username=:username, status=:status, canLogin=:canLogin, passwordForceReset=:passwordForceReset, gibbonRoleIDPrimary=:gibbonRoleIDPrimary, gibbonRoleIDAll=:gibbonRoleIDAll, dob=:dob, email=:email, emailAlternate=:emailAlternate, address1=:address1, address1District=:address1District, address1Country=:address1Country, address2=:address2, address2District=:address2District, address2Country=:address2Country, phone1Type=:phone1Type, phone1CountryCode=:phone1CountryCode, phone1=:phone1, phone2Type=:phone2Type, phone2CountryCode=:phone2CountryCode, phone2=:phone2, phone3Type=:phone3Type, phone3CountryCode=:phone3CountryCode, phone3=:phone3, phone4Type=:phone4Type, phone4CountryCode=:phone4CountryCode, phone4=:phone4, website=:website, languageFirst=:languageFirst, languageSecond=:languageSecond, languageThird=:languageThird, countryOfBirth=:countryOfBirth, ethnicity=:ethnicity,  religion=:religion, emergency1Name=:emergency1Name, emergency1Number1=:emergency1Number1, emergency1Number2=:emergency1Number2, emergency1Relationship=:emergency1Relationship, emergency2Name=:emergency2Name, emergency2Number1=:emergency2Number1, emergency2Number2=:emergency2Number2, emergency2Relationship=:emergency2Relationship, profession=:profession, employer=:employer, jobTitle=:jobTitle, image_240=:attachment1, gibbonHouseID=:gibbonHouseID, studentID=:studentID, dateStart=:dateStart, dateEnd=:dateEnd, gibbonSchoolYearIDClassOf=:gibbonSchoolYearIDClassOf, lastSchool=:lastSchool, nextSchool=:nextSchool, departureReason=:departureReason, transport=:transport, transportNotes=:transportNotes, lockerNumber=:lockerNumber, vehicleRegistration=:vehicleRegistration, privacy=:privacy, studentAgreements=:agreements, dayType=:dayType, fields=:fields WHERE gibbonPersonID=:gibbonPersonID';
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        } catch (PDOException $e) {
                            $URL .= '&return=error2';
                            header("Location: {$URL}");
                            exit();
                        }

                        if ($row['status'] != $status) {
                            $statusReason = $_POST['statusReason'] ?? '';

                            $userStatusLogGateway = $container->get(UserStatusLogGateway::class);
                            $userStatusLogGateway->insert(['gibbonPersonID' => $gibbonPersonID, 'statusOld' => $row['status'], 'statusNew' => $status, 'reason' => $statusReason]);
                        }

                        //Deal with change to privacy settings
                        if ($student && $container->get(SettingGateway::class)->getSettingByScope('User Admin', 'privacy') == 'Y') {
                            if ($privacy_old != $privacy && !(empty($privacy_old) && empty($privacy))) {

                                //Notify tutor

                                    $dataDetail = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonPersonID' => $gibbonPersonID);
                                    $sqlDetail = 'SELECT gibbonPersonIDTutor, gibbonPersonIDTutor2, gibbonPersonIDTutor3, gibbonYearGroupID FROM gibbonFormGroup JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID) JOIN gibbonPerson ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonStudentEnrolment.gibbonPersonID=:gibbonPersonID';
                                    $resultDetail = $connection2->prepare($sqlDetail);
                                    $resultDetail->execute($dataDetail);
                                if ($resultDetail->rowCount() == 1) {

                                    $rowDetail = $resultDetail->fetch();

                                    // Initialize the notification sender & gateway objects
                                    $notificationGateway = $container->get(NotificationGateway::class);
                                    $notificationSender = $container->get(NotificationSender::class);

                                    // Raise a new notification event
                                    $event = new NotificationEvent('Students', 'Updated Privacy Settings');

                                    $staffName = Format::name('', $session->get('preferredName'), $session->get('surname'), 'Staff', false, true);
                                    $studentName = Format::name('', $preferredName, $surname, 'Student', false);
                                    $actionLink = "/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=$gibbonPersonID&search=";

                                    $privacyText = __('Privacy').' (<i>'.__('New Value').'</i>): ';
                                    $privacyText .= !empty($privacy) ? $privacy : __('None');

                                    $notificationText = sprintf(__('%1$s has altered the privacy settings for %2$s.'), $staffName, $studentName).'<br/><br/>';
                                    $notificationText .= $privacyText;

                                    $event->setNotificationText($notificationText);
                                    $event->setActionLink($actionLink);

                                    $event->addScope('gibbonPersonIDStudent', $gibbonPersonID);
                                    $event->addScope('gibbonYearGroupID', $rowDetail['gibbonYearGroupID']);

                                    // Add event listeners to the notification sender
                                    $event->pushNotifications($notificationGateway, $notificationSender);

                                    // Add direct notifications to form group tutors
                                    if ($event->getEventDetails($notificationGateway, 'active') == 'Y') {
                                        $notificationText = sprintf(__('Your tutee, %1$s, has had their privacy settings altered.'), $studentName).'<br/><br/>';
                                        $notificationText .= $privacyText;

                                        if ($rowDetail['gibbonPersonIDTutor'] != null && $rowDetail['gibbonPersonIDTutor'] != $session->get('gibbonPersonID')) {
                                            $notificationSender->addNotification($rowDetail['gibbonPersonIDTutor'], $notificationText, 'Students', $actionLink);
                                        }
                                        if ($rowDetail['gibbonPersonIDTutor2'] != null && $rowDetail['gibbonPersonIDTutor2'] != $session->get('gibbonPersonID')) {
                                            $notificationSender->addNotification($rowDetail['gibbonPersonIDTutor2'], $notificationText, 'Students', $actionLink);
                                        }
                                        if ($rowDetail['gibbonPersonIDTutor3'] != null && $rowDetail['gibbonPersonIDTutor3'] != $session->get('gibbonPersonID')) {
                                            $notificationSender->addNotification($rowDetail['gibbonPersonIDTutor3'], $notificationText, 'Students', $actionLink);
                                        }
                                    }

                                    // Send all notifications
                                    $notificationSender->sendNotifications();
                                }

                                //Set log
                                $gibbonModuleID=getModuleIDFromName($connection2, 'User Admin') ;
                                $privacyValues=array() ;
                                $privacyValues['oldValue'] = $privacy_old ;
                                $privacyValues['newValue'] = $privacy ;
                                $logGateway->addLog($session->get("gibbonSchoolYearID"), $gibbonModuleID, $session->get("gibbonPersonID"), 'Privacy - Value Changed', $privacyValues, $_SERVER['REMOTE_ADDR']) ;
                            }
                        }

                        //Update matching addresses
                        $partialFail = false;
                        $matchAddressCount = null;
                        if (isset($_POST['matchAddressCount'])) {
                            $matchAddressCount = $_POST['matchAddressCount'] ?? '';
                        }
                        if ($matchAddressCount > 0) {
                            for ($i = 0; $i < $matchAddressCount; ++$i) {
                                if (!empty($_POST[$i.'-matchAddress'])) {
                                    try {
                                        $dataAddress = array('address1' => $address1, 'address1District' => $address1District, 'address1Country' => $address1Country, 'gibbonPersonID' => $_POST[$i.'-matchAddress']);
                                        $sqlAddress = 'UPDATE gibbonPerson SET address1=:address1, address1District=:address1District, address1Country=:address1Country WHERE gibbonPersonID=:gibbonPersonID';
                                        $resultAddress = $connection2->prepare($sqlAddress);
                                        $resultAddress->execute($dataAddress);
                                    } catch (PDOException $e) {
                                        $partialFail = true;
                                    }
                                }
                            }
                        }
                        if ($partialFail || $personalDocumentFail) {
                            $URL .= '&return=warning1';
                            header("Location: {$URL}");
                        } else if ($imageFail) {
                            $URL .= '&return=warning3';
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
}
