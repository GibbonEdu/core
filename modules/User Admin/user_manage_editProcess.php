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

//Module includes
include './moduleFunctions.php';

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]['timezone']);

$gibbonPersonID = $_GET['gibbonPersonID'];
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/user_manage_edit.php&gibbonPersonID=$gibbonPersonID&search=".$_GET['search'];

if (isActionAccessible($guid, $connection2, '/modules/User Admin/user_manage_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    //Check if school year specified
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

            $attachment1 = $_POST['attachment1'];
            $birthCertificateScan = $_POST['birthCertificateScanCurrent'];
            $nationalIDCardScan = $_POST['nationalIDCardScanCurrent'];
            $citizenship1PassportScan = $_POST['citizenship1PassportScanCurrent'];

            //Proceed!
            $title = $_POST['title'];
            $surname = trim($_POST['surname']);
            $firstName = trim($_POST['firstName']);
            $preferredName = trim($_POST['preferredName']);
            $officialName = trim($_POST['officialName']);
            $nameInCharacters = $_POST['nameInCharacters'];
            $gender = $_POST['gender'];
            $username = $_POST['username'];
            $status = $_POST['status'];
            $canLogin = $_POST['canLogin'];
            $passwordForceReset = $_POST['passwordForceReset'];
            $gibbonRoleIDPrimary = $_POST['gibbonRoleIDPrimary'];
            $gibbonRoleIDAll = '';
            $containsPrimary = false;
            $choices = $_POST['gibbonRoleIDAll'];
            if (count($choices) > 0) {
                foreach ($choices as $t) {
                    $gibbonRoleIDAll .= $t.',';
                    if ($t == $gibbonRoleIDPrimary) {
                        $containsPrimary = true;
                    }
                }
            }
            if ($containsPrimary == false) {
                $gibbonRoleIDAll = $gibbonRoleIDPrimary.','.$gibbonRoleIDAll;
            }
            $gibbonRoleIDAll = substr($gibbonRoleIDAll, 0, -1);
            $dob = $_POST['dob'];
            if ($dob == '') {
                $dob = null;
            } else {
                $dob = dateConvert($guid, $dob);
            }
            $email = trim($_POST['email']);
            $emailAlternate = trim($_POST['emailAlternate']);
            $address1 = $_POST['address1'];
            $address1District = $_POST['address1District'];
            $address1Country = $_POST['address1Country'];
            $address2 = $_POST['address2'];
            $address2District = $_POST['address2District'];
            $address2Country = $_POST['address2Country'];
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
            $website = $_POST['website'];
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

            $gibbonHouseID = $_POST['gibbonHouseID'];
            if ($gibbonHouseID == '') {
                $gibbonHouseID = null;
            }
            $studentID = null;
            if (isset($_POST['studentID'])) {
                $studentID = $_POST['studentID'];
            }
            $dateStart = $_POST['dateStart'];
            if ($dateStart == '') {
                $dateStart = null;
            } else {
                $dateStart = dateConvert($guid, $dateStart);
            }
            $dateEnd = $_POST['dateEnd'];
            if ($dateEnd == '') {
                $dateEnd = null;
            } else {
                $dateEnd = dateConvert($guid, $dateEnd);
            }
            $gibbonSchoolYearIDClassOf = null;
            if (isset($_POST['gibbonSchoolYearIDClassOf'])) {
                $gibbonSchoolYearIDClassOf = $_POST['gibbonSchoolYearIDClassOf'];
            }
            $lastSchool = null;
            if (isset($_POST['lastSchool'])) {
                $lastSchool = $_POST['lastSchool'];
            }
            $nextSchool = null;
            if (isset($_POST['nextSchool'])) {
                $nextSchool = $_POST['nextSchool'];
            }
            $departureReason = null;
            if (isset($_POST['departureReason'])) {
                $departureReason = $_POST['departureReason'];
            }
            $transport = null;
            if (isset($_POST['transport'])) {
                $transport = $_POST['transport'];
            }
            $transportNotes = null;
            if (isset($_POST['transportNotes'])) {
                $transportNotes = $_POST['transportNotes'];
            }
            $lockerNumber = null;
            if (isset($_POST['lockerNumber'])) {
                $lockerNumber = $_POST['lockerNumber'];
            }

            $vehicleRegistration = $_POST['vehicleRegistration'];
            $privacyOptions = null;
            $privacy = '';
            if (isset($_POST['privacyOptions'])) {
                $privacyOptions = $_POST['privacyOptions'];
                foreach ($privacyOptions as $privacyOption) {
                    if ($privacyOption != '') {
                        $privacy .= $privacyOption.',';
                    }
                }
            }
            if ($privacy != '') {
                $privacy = substr($privacy, 0, -1);
            } else {
                $privacy = null;
            }
            $privacy_old = $row['privacy'];

            $studentAgreements = null;
            $agreements = '';
            if (isset($_POST['studentAgreements'])) {
                $studentAgreements = $_POST['studentAgreements'];
                foreach ($studentAgreements as $studentAgreement) {
                    if ($studentAgreement != '') {
                        $agreements .= $studentAgreement.',';
                    }
                }
            }
            if ($agreements != '') {
                $agreements = substr($agreements, 0, -1);
            } else {
                $agreements = null;
            }

            $dayType = null;
            if (isset($_POST['dayType'])) {
                $dayType = $_POST['dayType'];
            }

            //Validate Inputs
            if ($surname == '' or $firstName == '' or $preferredName == '' or $officialName == '' or $gender == '' or $username == '' or $status == '' or $gibbonRoleIDPrimary == '') {
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
                    if (!empty($_FILES['file1']['tmp_name']) or !empty($_FILES['birthCertificateScan']['tmp_name']) or !empty($_FILES['nationalIDCardScan']['tmp_name']) or !empty($_FILES['citizenship1PassportScan']['tmp_name']))
                    {
                        $path = $_SESSION[$guid]['absolutePath'];
                        $fileUploader = new Gibbon\FileUploader($pdo, $gibbon->session);
                        
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

                        //Move birth certificate scan if there is one
                        if (!empty($_FILES['birthCertificateScan']['tmp_name'])) {
                            $file = (isset($_FILES['birthCertificateScan']))? $_FILES['birthCertificateScan'] : null;

                            // Upload the file, return the /uploads relative path
                            $fileUploader->setFileSuffixType(Gibbon\FileUploader::FILE_SUFFIX_ALPHANUMERIC);
                            $birthCertificateScan = $fileUploader->uploadFromPost($file, $username.'_birthCertificate');

                            if (empty($birthCertificateScan)) {
                                $imageFail = true;
                            } else {
                                //Check image sizes
                                $size2 = getimagesize($path.'/'.$birthCertificateScan);
                                $width2 = $size2[0];
                                $height2 = $size2[1];
                                if ($width2 > 1440 or $height2 > 900) {
                                    $birthCertificateScan = '';
                                    $imageFail = true;
                                }
                            }
                        }

                        //Move ID Card scan file, if there is one
                        if (!empty($_FILES['nationalIDCardScan']['tmp_name'])) {
                            $file = (isset($_FILES['nationalIDCardScan']))? $_FILES['nationalIDCardScan'] : null;

                            // Upload the file, return the /uploads relative path
                            $fileUploader->setFileSuffixType(Gibbon\FileUploader::FILE_SUFFIX_ALPHANUMERIC);
                            $nationalIDCardScan = $fileUploader->uploadFromPost($file, $username.'_idscan');

                            if (empty($nationalIDCardScan)) {
                                $imageFail = true;
                            } else {
                                //Check image sizes
                                $size3 = getimagesize($path.'/'.$nationalIDCardScan);
                                $width3 = $size3[0];
                                $height3 = $size3[1];
                                if ($width3 > 1440 or $height3 > 900) {
                                    $nationalIDCardScan = '';
                                    $imageFail = true;
                                }
                            }
                        }

                        //Move passport scan file, if there is one
                        if (!empty($_FILES['citizenship1PassportScan']['tmp_name'])) {
                            $file = (isset($_FILES['citizenship1PassportScan']))? $_FILES['citizenship1PassportScan'] : null;

                            // Upload the file, return the /uploads relative path
                            $fileUploader->setFileSuffixType(Gibbon\FileUploader::FILE_SUFFIX_ALPHANUMERIC);
                            $citizenship1PassportScan = $fileUploader->uploadFromPost($file, $username.'_passportscan');

                            if (empty($citizenship1PassportScan)) {
                                $imageFail = true;
                            } else {
                                //Check image sizes
                                $size4 = getimagesize($path.'/'.$citizenship1PassportScan);
                                $width4 = $size4[0];
                                $height4 = $size4[1];
                                if ($width4 > 1440 or $height4 > 900) {
                                    $citizenship1PassportScan = '';
                                    $imageFail = true;
                                }
                            }
                        }
                    }

                    //DEAL WITH CUSTOM FIELDS
                    //Prepare field values
                    $customRequireFail = false;
                    $resultFields = getCustomFields($connection2, $guid, $student, $staff, $parent, $other);
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
                            $data = array('title' => $title, 'surname' => $surname, 'firstName' => $firstName, 'preferredName' => $preferredName, 'officialName' => $officialName, 'nameInCharacters' => $nameInCharacters, 'gender' => $gender, 'status' => $status, 'canLogin' => $canLogin, 'passwordForceReset' => $passwordForceReset, 'gibbonRoleIDPrimary' => $gibbonRoleIDPrimary, 'gibbonRoleIDAll' => $gibbonRoleIDAll, 'dob' => $dob, 'email' => $email, 'emailAlternate' => $emailAlternate, 'address1' => $address1, 'address1District' => $address1District, 'address1Country' => $address1Country, 'address2' => $address2, 'address2District' => $address2District, 'address2Country' => $address2Country, 'phone1Type' => $phone1Type, 'phone1CountryCode' => $phone1CountryCode, 'phone1' => $phone1, 'phone2Type' => $phone2Type, 'phone2CountryCode' => $phone2CountryCode, 'phone2' => $phone2, 'phone3Type' => $phone3Type, 'phone3CountryCode' => $phone3CountryCode, 'phone3' => $phone3, 'phone4Type' => $phone4Type, 'phone4CountryCode' => $phone4CountryCode, 'phone4' => $phone4, 'website' => $website, 'languageFirst' => $languageFirst, 'languageSecond' => $languageSecond, 'languageThird' => $languageThird, 'countryOfBirth' => $countryOfBirth, 'birthCertificateScan' => $birthCertificateScan, 'ethnicity' => $ethnicity, 'citizenship1' => $citizenship1, 'citizenship1Passport' => $citizenship1Passport, 'citizenship1PassportScan' => $citizenship1PassportScan, 'citizenship2' => $citizenship2, 'citizenship2Passport' => $citizenship2Passport, 'religion' => $religion, 'nationalIDCardNumber' => $nationalIDCardNumber, 'nationalIDCardScan' => $nationalIDCardScan, 'residencyStatus' => $residencyStatus, 'visaExpiryDate' => $visaExpiryDate, 'emergency1Name' => $emergency1Name, 'emergency1Number1' => $emergency1Number1, 'emergency1Number2' => $emergency1Number2, 'emergency1Relationship' => $emergency1Relationship, 'emergency2Name' => $emergency2Name, 'emergency2Number1' => $emergency2Number1, 'emergency2Number2' => $emergency2Number2, 'emergency2Relationship' => $emergency2Relationship, 'profession' => $profession, 'employer' => $employer, 'jobTitle' => $jobTitle, 'attachment1' => $attachment1, 'gibbonHouseID' => $gibbonHouseID, 'studentID' => $studentID, 'dateStart' => $dateStart, 'dateEnd' => $dateEnd, 'gibbonSchoolYearIDClassOf' => $gibbonSchoolYearIDClassOf, 'lastSchool' => $lastSchool, 'nextSchool' => $nextSchool, 'departureReason' => $departureReason, 'transport' => $transport, 'transportNotes' => $transportNotes, 'lockerNumber' => $lockerNumber, 'vehicleRegistration' => $vehicleRegistration, 'privacy' => $privacy, 'agreements' => $agreements, 'dayType' => $dayType, 'fields' => $fields, 'gibbonPersonID' => $gibbonPersonID);
                            $sql = 'UPDATE gibbonPerson SET title=:title, surname=:surname, firstName=:firstName, preferredName=:preferredName, officialName=:officialName, nameInCharacters=:nameInCharacters, gender=:gender, status=:status, canLogin=:canLogin, passwordForceReset=:passwordForceReset, gibbonRoleIDPrimary=:gibbonRoleIDPrimary, gibbonRoleIDAll=:gibbonRoleIDAll, dob=:dob, email=:email, emailAlternate=:emailAlternate, address1=:address1, address1District=:address1District, address1Country=:address1Country, address2=:address2, address2District=:address2District, address2Country=:address2Country, phone1Type=:phone1Type, phone1CountryCode=:phone1CountryCode, phone1=:phone1, phone2Type=:phone2Type, phone2CountryCode=:phone2CountryCode, phone2=:phone2, phone3Type=:phone3Type, phone3CountryCode=:phone3CountryCode, phone3=:phone3, phone4Type=:phone4Type, phone4CountryCode=:phone4CountryCode, phone4=:phone4, website=:website, languageFirst=:languageFirst, languageSecond=:languageSecond, languageThird=:languageThird, countryOfBirth=:countryOfBirth, birthCertificateScan=:birthCertificateScan, ethnicity=:ethnicity,  citizenship1=:citizenship1, citizenship1Passport=:citizenship1Passport, citizenship1PassportScan=:citizenship1PassportScan, citizenship2=:citizenship2,  citizenship2Passport=:citizenship2Passport, religion=:religion, nationalIDCardNumber=:nationalIDCardNumber, nationalIDCardScan=:nationalIDCardScan, residencyStatus=:residencyStatus, visaExpiryDate=:visaExpiryDate, emergency1Name=:emergency1Name, emergency1Number1=:emergency1Number1, emergency1Number2=:emergency1Number2, emergency1Relationship=:emergency1Relationship, emergency2Name=:emergency2Name, emergency2Number1=:emergency2Number1, emergency2Number2=:emergency2Number2, emergency2Relationship=:emergency2Relationship, profession=:profession, employer=:employer, jobTitle=:jobTitle, image_240=:attachment1, gibbonHouseID=:gibbonHouseID, studentID=:studentID, dateStart=:dateStart, dateEnd=:dateEnd, gibbonSchoolYearIDClassOf=:gibbonSchoolYearIDClassOf, lastSchool=:lastSchool, nextSchool=:nextSchool, departureReason=:departureReason, transport=:transport, transportNotes=:transportNotes, lockerNumber=:lockerNumber, vehicleRegistration=:vehicleRegistration, privacy=:privacy, studentAgreements=:agreements, dayType=:dayType, fields=:fields WHERE gibbonPersonID=:gibbonPersonID';
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        } catch (PDOException $e) {
                            $URL .= '&return=error2';
                            header("Location: {$URL}");
                            exit();
                        }

                        //Deal with change to privacy settings
                        if ($student and getSettingByScope($connection2, 'User Admin', 'privacy') == 'Y') {
                            if ($privacy_old != $privacy) {

                                //Notify tutor
                                try {
                                    $dataDetail = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $gibbonPersonID);
                                    $sqlDetail = 'SELECT gibbonPersonIDTutor, gibbonPersonIDTutor2, gibbonPersonIDTutor3 FROM gibbonRollGroup JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) JOIN gibbonPerson ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonStudentEnrolment.gibbonPersonID=:gibbonPersonID';
                                    $resultDetail = $connection2->prepare($sqlDetail);
                                    $resultDetail->execute($dataDetail);
                                } catch (PDOException $e) {
                                }
                                if ($resultDetail->rowCount() == 1) {

                                    $rowDetail = $resultDetail->fetch();
                                    $name = formatName('', $preferredName, $surname, 'Student', false);
                                    $notificationText = sprintf(__($guid, 'Your tutee, %1$s, has had their privacy settings altered.'), $name);
                                    if ($rowDetail['gibbonPersonIDTutor'] != null and $rowDetail['gibbonPersonIDTutor'] != $_SESSION[$guid]['gibbonPersonID']) {
                                        setNotification($connection2, $guid, $rowDetail['gibbonPersonIDTutor'], $notificationText, 'Students', "/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=$gibbonPersonID&search=");
                                    }
                                    if ($rowDetail['gibbonPersonIDTutor2'] != null and $rowDetail['gibbonPersonIDTutor2'] != $_SESSION[$guid]['gibbonPersonID']) {
                                        setNotification($connection2, $guid, $rowDetail['gibbonPersonIDTutor2'], $notificationText, 'Students', "/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=$gibbonPersonID&search=");
                                    }
                                    if ($rowDetail['gibbonPersonIDTutor3'] != null and $rowDetail['gibbonPersonIDTutor3'] != $_SESSION[$guid]['gibbonPersonID']) {
                                        setNotification($connection2, $guid, $rowDetail['gibbonPersonIDTutor3'], $notificationText, 'Students', "/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=$gibbonPersonID&search=");
                                    }
                                }

                                //Set log
                                $gibbonModuleID=getModuleIDFromName($connection2, 'User Admin') ;
                                $privacyValues=array() ;
                                $privacyValues['oldValue'] = $privacy_old ;
                                $privacyValues['newValue'] = $privacy ;
                                setLog($connection2, $_SESSION[$guid]["gibbonSchoolYearID"], $gibbonModuleID, $_SESSION[$guid]["gibbonPersonID"], 'Privacy - Value Changed', $privacyValues, $_SERVER['REMOTE_ADDR']) ;
                            }
                        }

                        //Update matching addresses
                        $partialFail = false;
                        $matchAddressCount = null;
                        if (isset($_POST['matchAddressCount'])) {
                            $matchAddressCount = $_POST['matchAddressCount'];
                        }
                        if ($matchAddressCount > 0) {
                            for ($i = 0; $i < $matchAddressCount; ++$i) {
                                if ($_POST[$i.'-matchAddress'] != '') {
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
                        if ($partialFail == true) {
                            $URL .= '&return=warning1';
                            header("Location: {$URL}");
                        } else {
                            if ($imageFail) {
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
    }
}
