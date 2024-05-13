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

use Gibbon\Services\Format;
use Gibbon\Comms\NotificationEvent;
use Gibbon\Data\PasswordPolicy;
use Gibbon\Domain\Staff\StaffGateway;
use Gibbon\Domain\Students\StudentGateway;
use Gibbon\Domain\Timetable\CourseEnrolmentGateway;
use Gibbon\Domain\User\UserStatusLogGateway;
use Gibbon\Data\Validator;

include '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST, ['website' => 'URL']);

$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address']).'/user_manage_add.php&search='.$_GET['search'];

if (isActionAccessible($guid, $connection2, '/modules/User Admin/user_manage_add.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    $title = $_POST['title'] ?? '';
    $surname = trim($_POST['surname'] ?? '');
    $firstName = trim($_POST['firstName'] ?? '');
    $preferredName = trim($_POST['preferredName'] ?? '');
    $officialName = trim($_POST['officialName'] ?? '');
    $nameInCharacters = $_POST['nameInCharacters'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['passwordNew'] ?? '';
    $passwordConfirm = $_POST['passwordConfirm'] ?? '';
    $status = $_POST['status'] ?? '';
    $canLogin = $_POST['canLogin'] ?? '';
    $passwordForceReset = $_POST['passwordForceReset'] ?? '';
    $gibbonRoleIDPrimary = $_POST['gibbonRoleIDPrimary'] ?? '';
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
    if ($_POST['phone1'] != '' and $phone1Type == '') {
        $phone1Type = 'Other';
    }
    $phone1CountryCode = $_POST['phone1CountryCode'] ?? '';
    $phone1 = preg_replace('/[^0-9+]/', '', $_POST['phone1'] ?? '');
    $phone2Type = $_POST['phone2Type'] ?? '';
    if ($_POST['phone2'] != '' and $phone2Type == '') {
        $phone2Type = 'Other';
    }
    $phone2CountryCode = $_POST['phone2CountryCode'] ?? '';
    $phone2 = preg_replace('/[^0-9+]/', '', $_POST['phone2'] ?? '');
    $phone3Type = $_POST['phone3Type'] ?? '';
    if ($_POST['phone3'] != '' and $phone3Type == '') {
        $phone3Type = 'Other';
    }
    $phone3CountryCode = $_POST['phone3CountryCode'] ?? '';
    $phone3 = preg_replace('/[^0-9+]/', '', $_POST['phone3'] ?? '');
    $phone4Type = $_POST['phone4Type'] ?? '';
    if ($_POST['phone4'] != '' and $phone4Type == '') {
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
    $profession = $_POST['profession'] ?? '';
    $employer = $_POST['employer'] ?? '';
    $jobTitle = $_POST['jobTitle'] ?? '';
    $emergency1Name = $_POST['emergency1Name'] ?? '';
    $emergency1Number1 = $_POST['emergency1Number1'] ?? '';
    $emergency1Number2 = $_POST['emergency1Number2'] ?? '';
    $emergency1Relationship = $_POST['emergency1Relationship'] ?? '';
    $emergency2Name = $_POST['emergency2Name'] ?? '';
    $emergency2Number1 = $_POST['emergency2Number1'] ?? '';
    $emergency2Number2 = $_POST['emergency2Number2'] ?? '';
    $emergency2Relationship = $_POST['emergency2Relationship'] ?? '';
    $profession = $_POST['profession'] ?? '';
    $employer = $_POST['employer'] ?? '';
    $jobTitle = $_POST['jobTitle'] ?? '';
    $gibbonHouseID = !empty($_POST['gibbonHouseID']) ? $_POST['gibbonHouseID'] : null;
    $studentID = $_POST['studentID'] ?? '';
    $dateStart = !empty($_POST['dateStart']) ? Format::dateConvert($_POST['dateStart']) : null;

    $gibbonSchoolYearIDClassOf = !empty($_POST['gibbonSchoolYearIDClassOf']) ? $_POST['gibbonSchoolYearIDClassOf'] : null;
    $lastSchool = $_POST['lastSchool'] ?? '';
    $transport = $_POST['transport'] ?? '';
    $transportNotes = $_POST['transportNotes'] ?? '';
    $lockerNumber = $_POST['lockerNumber'] ?? '';
    $vehicleRegistration = $_POST['vehicleRegistration'] ?? '';

    $privacy = !empty($_POST['privacyOptions']) ? implode(',', $_POST['privacyOptions']) : null;
    $agreements = !empty($_POST['studentAgreements']) ? implode(',', $_POST['studentAgreements']) : null;
    $dayType = $_POST['dayType'] ?? null;

    //Validate Inputs
    if ($surname == '' or $firstName == '' or $preferredName == '' or $officialName == '' or $gender == '' or $username == '' or $password == '' or $passwordConfirm == '' or $status == '' or $gibbonRoleIDPrimary == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        //Check unique inputs for uniquness
        try {
            $data = array('username' => $username);
            $sql = 'SELECT * FROM gibbonPerson WHERE username=:username';
            if ($studentID != '') {
                $data = array('username' => $username, 'studentID' => $studentID);
                $sql = 'SELECT * FROM gibbonPerson WHERE username=:username OR studentID=:studentID';
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
            //Check passwords for match
            if ($password != $passwordConfirm) {
                $URL .= '&return=warning1';
                header("Location: {$URL}");
            } else {
                /** @var PasswordPolicy */
                $passwordPolicies = $container->get(PasswordPolicy::class);

                //Check strength of password
                if (!$passwordPolicies->validate($password)) {
                    $URL .= '&return=error7';
                    header("Location: {$URL}");
                } else {
                    $attachment1 = null;
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
                                $size1 = getimagesize($path.'/'.$attachment1);
                                $width1 = $size1[0];
                                $height1 = $size1[1];
                                $aspect1 = $height1 / $width1;
                                if ($width1 > 360 or $height1 > 480 or $aspect1 < 1.2 or $aspect1 > 1.4) {
                                    $attachment1 = '';
                                    $imageFail = true;
                                }
                            }
                        }
                    }

                    $salt = getSalt();
                    $passwordStrong = hash('sha256', $salt.$password);

                    //Write to database
                    try {
                        $data = array('title' => $title, 'surname' => $surname, 'firstName' => $firstName, 'preferredName' => $preferredName, 'officialName' => $officialName, 'nameInCharacters' => $nameInCharacters, 'gender' => $gender, 'username' => $username, 'passwordStrong' => $passwordStrong, 'passwordStrongSalt' => $salt, 'status' => $status, 'canLogin' => $canLogin, 'passwordForceReset' => $passwordForceReset, 'gibbonRoleIDPrimary' => $gibbonRoleIDPrimary, 'gibbonRoleIDAll' => $gibbonRoleIDPrimary, 'dob' => $dob, 'email' => $email, 'emailAlternate' => $emailAlternate, 'address1' => $address1, 'address1District' => $address1District, 'address1Country' => $address1Country, 'address2' => $address2, 'address2District' => $address2District, 'address2Country' => $address2Country, 'phone1Type' => $phone1Type, 'phone1CountryCode' => $phone1CountryCode, 'phone1' => $phone1, 'phone2Type' => $phone2Type, 'phone2CountryCode' => $phone2CountryCode, 'phone2' => $phone2, 'phone3Type' => $phone3Type, 'phone3CountryCode' => $phone3CountryCode, 'phone3' => $phone3, 'phone4Type' => $phone4Type, 'phone4CountryCode' => $phone4CountryCode, 'phone4' => $phone4, 'website' => $website, 'languageFirst' => $languageFirst, 'languageSecond' => $languageSecond, 'languageThird' => $languageThird, 'countryOfBirth' => $countryOfBirth, 'ethnicity' => $ethnicity, 'religion' => $religion, 'emergency1Name' => $emergency1Name, 'emergency1Number1' => $emergency1Number1, 'emergency1Number2' => $emergency1Number2, 'emergency1Relationship' => $emergency1Relationship, 'emergency2Name' => $emergency2Name, 'emergency2Number1' => $emergency2Number1, 'emergency2Number2' => $emergency2Number2, 'emergency2Relationship' => $emergency2Relationship, 'profession' => $profession, 'employer' => $employer, 'jobTitle' => $jobTitle, 'attachment1' => $attachment1, 'gibbonHouseID' => $gibbonHouseID, 'studentID' => $studentID, 'dateStart' => $dateStart, 'gibbonSchoolYearIDClassOf' => $gibbonSchoolYearIDClassOf, 'lastSchool' => $lastSchool, 'transport' => $transport, 'transportNotes' => $transportNotes, 'lockerNumber' => $lockerNumber, 'vehicleRegistration' => $vehicleRegistration, 'privacy' => $privacy, 'agreements' => $agreements, 'dayType' => $dayType);
                        $sql = "INSERT INTO gibbonPerson SET title=:title, surname=:surname, firstName=:firstName, preferredName=:preferredName, officialName=:officialName, nameInCharacters=:nameInCharacters, gender=:gender, username=:username, passwordStrong=:passwordStrong, passwordStrongSalt=:passwordStrongSalt, status=:status, canLogin=:canLogin, passwordForceReset=:passwordForceReset, gibbonRoleIDPrimary=:gibbonRoleIDPrimary, gibbonRoleIDAll=:gibbonRoleIDAll, dob=:dob, email=:email, emailAlternate=:emailAlternate, address1=:address1, address1District=:address1District, address1Country=:address1Country, address2=:address2, address2District=:address2District, address2Country=:address2Country, phone1Type=:phone1Type, phone1CountryCode=:phone1CountryCode, phone1=:phone1, phone2Type=:phone2Type, phone2CountryCode=:phone2CountryCode, phone2=:phone2, phone3Type=:phone3Type, phone3CountryCode=:phone3CountryCode, phone3=:phone3, phone4Type=:phone4Type, phone4CountryCode=:phone4CountryCode, phone4=:phone4, website=:website, languageFirst=:languageFirst, languageSecond=:languageSecond, languageThird=:languageThird, countryOfBirth=:countryOfBirth, ethnicity=:ethnicity, religion=:religion, emergency1Name=:emergency1Name, emergency1Number1=:emergency1Number1, emergency1Number2=:emergency1Number2, emergency1Relationship=:emergency1Relationship, emergency2Name=:emergency2Name, emergency2Number1=:emergency2Number1, emergency2Number2=:emergency2Number2, emergency2Relationship=:emergency2Relationship, profession=:profession, employer=:employer, jobTitle=:jobTitle, image_240=:attachment1, gibbonHouseID=:gibbonHouseID, studentID=:studentID, dateStart=:dateStart, gibbonSchoolYearIDClassOf=:gibbonSchoolYearIDClassOf, lastSchool=:lastSchool, transport=:transport, transportNotes=:transportNotes, lockerNumber=:lockerNumber, vehicleRegistration=:vehicleRegistration, privacy=:privacy, studentAgreements=:agreements, dayType=:dayType";
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $URL .= '&return=error2';
                        header("Location: {$URL}");
                        exit();
                    }

                    //Last insert ID
                    $AI = str_pad($connection2->lastInsertID(), 10, '0', STR_PAD_LEFT);

                    // Create the status log
                    $container->get(UserStatusLogGateway::class)->insert(['gibbonPersonID' => $AI, 'statusOld' => $status, 'statusNew' => $status, 'reason' => __('Created')]);

                    // Create a staff record for this new user
                    $staffRecord = $_POST['staffRecord'] ?? 'N';
                    if ($staffRecord == 'Y' && !empty($AI)) {
                        $inserted = $container->get(StaffGateway::class)->insert([
                            'gibbonPersonID' => $AI,
                            'jobTitle'       => $_POST['jobTitle'] ?? '',
                            'type'           => $_POST['staffType'] ?? '',
                        ]);
                        if ($inserted) {
                            // Raise a new notification event
                            $event = new NotificationEvent('Staff', 'New Staff');
                            $event->setNotificationText(__('A new staff member has been added: {name} ({username}) {jobTitle}', [
                                'name' => Format::name('', $preferredName, $surname, 'Staff', false, true),
                                'username' => $username,
                                'jobTitle' => $_POST['jobTitle'] ?? '',
                            ]));
                            $event->setActionLink('/index.php?q=/modules/Staff/staff_view_details.php&gibbonPersonID='.$AI.'&allStaff=&search=');
                            $event->sendNotifications($pdo, $session);
                        }
                    }

                    // Create a student record for this new user
                    $studentRecord = $_POST['studentRecord'] ?? 'N';
                    if ($studentRecord == 'Y' && !empty($AI)) {
                        $studentData = [
                            'gibbonPersonID'        => $AI,
                            'gibbonSchoolYearID'    => $session->get('gibbonSchoolYearID') ?? '',
                            'gibbonYearGroupID'     => $_POST['gibbonYearGroupID'] ?? '',
                            'gibbonFormGroupID'     => $_POST['gibbonFormGroupID'] ?? '',
                            'rollOrder'             => !empty($_POST['rollOrder']) ? $_POST['rollOrder'] : null
                        ];
                        $inserted = $container->get(StudentGateway::class)->insert($studentData);
                        if ($inserted) {
                            // Handle automatic course enrolment if enabled
                            $autoEnrolStudent = $_POST['autoEnrolStudent'] ?? 'N';
                            if ($autoEnrolStudent == 'Y') {
                                $inserted = $container->get(CourseEnrolmentGateway::class)->insertAutomaticCourseEnrolments($studentData['gibbonFormGroupID'], $AI);

                                if (!$inserted) {
                                    $URL .= "&return=warning1&editID=$AI";
                                    header("Location: {$URL}");
                                    exit;
                                }
                            }
                        }
                    }

                    if ($imageFail) {
                        $URL .= "&return=warning3&editID=$AI";
                        header("Location: {$URL}");
                    } else {
                        $URL .= "&return=success0&editID=$AI";
                        header("Location: {$URL}");
                    }
                }
            }
        }
    }
}
