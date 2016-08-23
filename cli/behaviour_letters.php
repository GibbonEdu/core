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

require getcwd().'/../config.php';
require getcwd().'/../functions.php';
require getcwd().'/../lib/PHPMailer/PHPMailerAutoload.php';

//New PDO DB connection
$pdo = new Gibbon\sqlConnection();
$connection2 = $pdo->getConnection();

@session_start();

getSystemSettings($guid, $connection2);

setCurrentSchoolYear($guid, $connection2);

//Set up for i18n via gettext
if (isset($_SESSION[$guid]['i18n']['code'])) {
    if ($_SESSION[$guid]['i18n']['code'] != null) {
        putenv('LC_ALL='.$_SESSION[$guid]['i18n']['code']);
        setlocale(LC_ALL, $_SESSION[$guid]['i18n']['code']);
        bindtextdomain('gibbon', getcwd().'/../i18n');
        textdomain('gibbon');
    }
}

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]['timezone']);

//Check for CLI, so this cannot be run through browser
if (php_sapi_name() != 'cli') { echo __($guid, 'This script cannot be run from a browser, only via CLI.');
} else {
    $emailSendCount = 0;
    $emailFailCount = 0;

    //Get settings
    $enableDescriptors = getSettingByScope($connection2, 'Behaviour', 'enableDescriptors');
    $enableLevels = getSettingByScope($connection2, 'Behaviour', 'enableLevels');
    $enableBehaviourLetters = getSettingByScope($connection2, 'Behaviour', 'enableBehaviourLetters');
    if ($enableBehaviourLetters == 'Y') {
        $behaviourLettersLetter1Count = getSettingByScope($connection2, 'Behaviour', 'behaviourLettersLetter1Count');
        $behaviourLettersLetter1Text = getSettingByScope($connection2, 'Behaviour', 'behaviourLettersLetter1Text');
        $behaviourLettersLetter2Count = getSettingByScope($connection2, 'Behaviour', 'behaviourLettersLetter2Count');
        $behaviourLettersLetter2Text = getSettingByScope($connection2, 'Behaviour', 'behaviourLettersLetter2Text');
        $behaviourLettersLetter3Count = getSettingByScope($connection2, 'Behaviour', 'behaviourLettersLetter3Count');
        $behaviourLettersLetter3Text = getSettingByScope($connection2, 'Behaviour', 'behaviourLettersLetter3Text');

        if ($behaviourLettersLetter1Count != '' and $behaviourLettersLetter1Text != '' and $behaviourLettersLetter2Count != '' and $behaviourLettersLetter2Text != '' and $behaviourLettersLetter3Count != '' and $behaviourLettersLetter3Text != '' and is_numeric($behaviourLettersLetter1Count) and is_numeric($behaviourLettersLetter2Count) and is_numeric($behaviourLettersLetter3Count)) {
            //SCAN THROUGH ALL STUDENTS
            try {
                $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                $sql = "SELECT gibbonPerson.gibbonPersonID, preferredName, surname, gibbonRollGroup.gibbonRollGroupID, gibbonRollGroup.name AS rollGroup, 'Student' AS role, gibbonPersonIDTutor, gibbonPersonIDTutor2, gibbonPersonIDTutor3 FROM gibbonPerson, gibbonStudentEnrolment, gibbonRollGroup WHERE gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID AND gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID AND status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY surname, preferredName";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
            }

            if ($result->rowCount() > 0) {
                while ($row = $result->fetch()) { //For every student
                    $studentName = formatName('', $row['preferredName'], $row['surname'], 'Student', false);
                    $rollGroup = $row['rollGroup'];

                    //Check count of negative behaviour records in the current year
                    try {
                        $dataBehaviour = array('gibbonPersonID' => $row['gibbonPersonID'], 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                        $sqlBehaviour = "SELECT * FROM gibbonBehaviour WHERE gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID AND type='Negative'";
                        $resultBehaviour = $connection2->prepare($sqlBehaviour);
                        $resultBehaviour->execute($dataBehaviour);
                    } catch (PDOException $e) {
                    }
                    $behaviourCount = $resultBehaviour->rowCount();
                    if ($behaviourCount > 0) { //Only worry about students with more than zero negative records in the current year
                        //Get most recent letter entry
                        try {
                            $dataLetters = array('gibbonPersonID' => $row['gibbonPersonID'], 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                            $sqlLetters = 'SELECT * FROM gibbonBehaviourLetter WHERE gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY timestamp DESC LIMIT 0, 1';
                            $resultLetters = $connection2->prepare($sqlLetters);
                            $resultLetters->execute($dataLetters);
                        } catch (PDOException $e) {
                        }

                        $newLetterRequired = false;
                        $newLetterRequiredLevel = null;
                        $newLetterRequiredStatus = null;
                        $issueExistingLetter = false;
                        $issueExistingLetterLevel = null;
                        $issueExistingLetterID = null;

                        //DECIDE WHAT LETTERS TO SEND
                        if ($resultLetters->rowCount() != 1) { //NO LETTER EXISTS
                            $lastLetterLevel = null;
                            $lastLetterStatus = null;

                            if ($behaviourCount >= $behaviourLettersLetter3Count) { //Student is over or equal to level 3
                                $newLetterRequired = true;
                                $newLetterRequiredLevel = 3;
                                $newLetterRequiredStatus = 'Issued';
                            } elseif ($behaviourCount >= $behaviourLettersLetter2Count and $behaviourCount < $behaviourLettersLetter3Count) { //Student is equal to or greater than level 2 but less than level 3
                                $newLetterRequired = true;
                                $newLetterRequiredLevel = 2;
                                $newLetterRequiredStatus = 'Issued';
                            } elseif ($behaviourCount >= $behaviourLettersLetter1Count and $behaviourCount < $behaviourLettersLetter2Count) { //Student is equal to or greater than level 1 but less than level 2
                                $newLetterRequired = true;
                                $newLetterRequiredLevel = 1;
                                $newLetterRequiredStatus = 'Issued';
                            } elseif ($behaviourCount == ($behaviourLettersLetter1Count - 1)) { //Student is one less than level 1
                                $newLetterRequired = true;
                                $newLetterRequiredLevel = 1;
                                $newLetterRequiredStatus = 'Warning';
                            }
                        } else { //YES LETTER EXISTS
                            $rowLetters = $resultLetters->fetch();
                            $lastLetterLevel = $rowLetters['letterLevel'];
                            $lastLetterStatus = $rowLetters['status'];

                            if ($behaviourCount > $rowLetters['recordCountAtCreation']) { //Only consider action if count has increased since creation (stops second day issue of warning when count has not changed)
                                if ($lastLetterStatus == 'Warning') { //Last letter is warning
                                    if ($behaviourCount >= ${'behaviourLettersLetter'.$lastLetterLevel.'Count'} and $behaviourCount < ${'behaviourLettersLetter'.($lastLetterLevel + 1).'Count'}) { //Count escalted to above warning, and less than next full level
                                        $issueExistingLetter = true;
                                        $issueExistingLetterID = $rowLetters['gibbonBehaviourLetterID'];
                                        $issueExistingLetterLevel = $rowLetters['letterLevel'];
                                    } elseif ($behaviourCount >= ${'behaviourLettersLetter'.($lastLetterLevel + 1).'Count'}) { //Count escalated to equal to or above next level
                                        $newLetterRequired = true;
                                        if ($behaviourCount >= $behaviourLettersLetter3Count) {
                                            $newLetterRequiredLevel = 3;
                                        } elseif ($behaviourCount >= $behaviourLettersLetter2Count and $behaviourCount < $behaviourLettersLetter3Count) {
                                            $newLetterRequiredLevel = 2;
                                        }
                                        $newLetterRequiredStatus = 'Issued';
                                    }
                                } else { //Last letter is issued
                                    if ($behaviourCount == (${'behaviourLettersLetter'.($lastLetterLevel + 1).'Count'} - 1)) { //Count escalated to next warning
                                        $newLetterRequired = true;
                                        if ($behaviourCount == ($behaviourLettersLetter3Count - 1)) {
                                            $newLetterRequiredLevel = 3;
                                        } elseif ($behaviourCount == ($behaviourLettersLetter2Count - 1)) {
                                            $newLetterRequiredLevel = 2;
                                        }
                                        $newLetterRequiredStatus = 'Warning';
                                    } elseif ($behaviourCount > (${'behaviourLettersLetter'.($lastLetterLevel + 1).'Count'} - 1)) { //Count escalated above next warning
                                        $newLetterRequired = true;
                                        if ($behaviourCount >= $behaviourLettersLetter3Count) {
                                            $newLetterRequiredLevel = 3;
                                        } elseif ($behaviourCount >= $behaviourLettersLetter2Count) {
                                            $newLetterRequiredLevel = 2;
                                        }
                                        $newLetterRequiredStatus = 'Issued';
                                    }
                                }
                            }
                        }

                        //SEND LETTERS ACCORDING TO DECISIONS ABOVE
                        $email = false;
                        $letterUpdateFail = false;
                        $gibbonBehaviourLetterID = null;

                        //PREPARE LETTER BODY
                        $body = '';
                        if ($issueExistingLetter or ($newLetterRequired and $newLetterRequiredStatus == 'Issued')) {
                            if ($issueExistingLetter) {
                                $body = ${'behaviourLettersLetter'.$issueExistingLetterLevel.'Text'};
                            } else {
                                $body = ${'behaviourLettersLetter'.$newLetterRequiredLevel.'Text'};
                            }
                            //Prepare behaviour record for replacement
                            $behaviourRecord = '<ul>';
                            try {
                                $dataBehaviourRecord = array('gibbonPersonID' => $row['gibbonPersonID'], 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                                $sqlBehaviourRecord = "SELECT * FROM gibbonBehaviour WHERE gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID AND type='Negative' ORDER BY timestamp DESC";
                                $resultBehaviourRecord = $connection2->prepare($sqlBehaviourRecord);
                                $resultBehaviourRecord->execute($dataBehaviourRecord);
                            } catch (PDOException $e) {
                            }
                            while ($rowBehaviourRecord = $resultBehaviourRecord->fetch()) {
                                $behaviourRecord .= '<li>';
                                $behaviourRecord .= dateConvertBack($guid, substr($rowBehaviourRecord['timestamp'], 0, 10));
                                if ($enableDescriptors == 'Y' and $rowBehaviourRecord['descriptor'] != '') {
                                    $behaviourRecord .= ' - '.$rowBehaviourRecord['descriptor'];
                                }
                                if ($enableLevels == 'Y' and $rowBehaviourRecord['level'] != '') {
                                    $behaviourRecord .= ' - '.$rowBehaviourRecord['level'];
                                }
                                $behaviourRecord .= '</li>';
                            }
                            $behaviourRecord .= '</ul>';

                            //Peform required text replacements
                            $body = str_replace('[studentName]', $studentName, $body);
                            $body = str_replace('[rollGroup]', $rollGroup, $body);
                            $body = str_replace('[behaviourCount]', $behaviourCount, $body);
                            $body = str_replace('[behaviourRecord]', $behaviourRecord, $body);
                            $body = str_replace('[systemEmailSignature]', '<i>'.sprintf(__($guid, 'Email sent via %1$s at %2$s.'), $_SESSION[$guid]['systemName'], $_SESSION[$guid]['organisationName']).'</i>', $body);
                        }

                        if ($issueExistingLetter) { //Issue existing letter
                            //Update record
                            $gibbonBehaviourLetterID = $issueExistingLetterID;
                            try {
                                $dataLetter = array('body' => $body, 'gibbonBehaviourLetterID' => $gibbonBehaviourLetterID);
                                $sqlLetter = "UPDATE gibbonBehaviourLetter SET status='Issued', body=:body WHERE gibbonBehaviourLetterID=:gibbonBehaviourLetterID";
                                $resultLetter = $connection2->prepare($sqlLetter);
                                $resultLetter->execute($dataLetter);
                            } catch (PDOException $e) {
                                $letterUpdateFail = true;
                            }

                            if ($letterUpdateFail == false) {
                                //Flag parents to receive email
                                $email = true;

                                //Notify tutor(s)
                                $notificationText = sprintf(__($guid, 'A student (%1$s) in your form group has received a behaviour letter.'), $studentName);
                                if ($row['gibbonPersonIDTutor'] != '') {
                                    setNotification($connection2, $guid, $row['gibbonPersonIDTutor'], $notificationText, 'Behaviour', '/index.php?q=/modules/Behaviour/behaviour_letters.php&gibbonPersonID='.$row['gibbonPersonID']);
                                }
                                if ($row['gibbonPersonIDTutor2'] != '') {
                                    setNotification($connection2, $guid, $row['gibbonPersonIDTutor2'], $notificationText, 'Behaviour', '/index.php?q=/modules/Behaviour/behaviour_letters.php&gibbonPersonID='.$row['gibbonPersonID']);
                                }
                                if ($row['gibbonPersonIDTutor3'] != '') {
                                    setNotification($connection2, $guid, $row['gibbonPersonIDTutor3'], $notificationText, 'Behaviour', '/index.php?q=/modules/Behaviour/behaviour_letters.php&gibbonPersonID='.$row['gibbonPersonID']);
                                }
                            }
                        } elseif ($newLetterRequired) { //Issue new letter
                            if ($newLetterRequiredStatus == 'Warning') { //It's a warning
                                //Create new record
                                try {
                                    $dataLetter = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $row['gibbonPersonID'], 'letterLevel' => $newLetterRequiredLevel, 'recordCountAtCreation' => $behaviourCount, 'body' => $body);
                                    $sqlLetter = "INSERT INTO gibbonBehaviourLetter SET gibbonSchoolYearID=:gibbonSchoolYearID, gibbonPersonID=:gibbonPersonID, letterLevel=:letterLevel, status='Warning', recordCountAtCreation=:recordCountAtCreation, body=:body";
                                    $resultLetter = $connection2->prepare($sqlLetter);
                                    $resultLetter->execute($dataLetter);
                                } catch (PDOException $e) {
                                    $letterUpdateFail = true;
                                }

                                if ($letterUpdateFail == false) {
                                    $gibbonBehaviourLetterID = $connection2->lastInsertID();

                                    //Notify tutor(s)
                                    $notificationText = sprintf(___($guid, 'A warning has been issued for a student (%1$s) in your form group, pending a behaviour letter.'), $studentName);
                                    if ($row['gibbonPersonIDTutor'] != '') {
                                        setNotification($connection2, $guid, $row['gibbonPersonIDTutor'], $notificationText, 'Behaviour', '/index.php?q=/modules/Behaviour/behaviour_letters.php&gibbonPersonID='.$row['gibbonPersonID']);
                                    }
                                    if ($row['gibbonPersonIDTutor2'] != '') {
                                        setNotification($connection2, $guid, $row['gibbonPersonIDTutor2'], $notificationText, 'Behaviour', '/index.php?q=/modules/Behaviour/behaviour_letters.php&gibbonPersonID='.$row['gibbonPersonID']);
                                    }
                                    if ($row['gibbonPersonIDTutor3'] != '') {
                                        setNotification($connection2, $guid, $row['gibbonPersonIDTutor3'], $notificationText, 'Behaviour', '/index.php?q=/modules/Behaviour/behaviour_letters.php&gibbonPersonID='.$row['gibbonPersonID']);
                                    }

                                    //Notify teachers
                                    $notificationText = sprintf(___($guid, 'A warning has been issued for a student (%1$s) in one of your classes, pending a behaviour letter.'), $studentName);
                                    try {
                                        $dataTeachers = array('gibbonPersonID' => $row['gibbonPersonID']);
                                        $sqlTeachers = "SELECT DISTINCT teacher.gibbonPersonID FROM gibbonPerson AS teacher JOIN gibbonCourseClassPerson AS teacherClass ON (teacherClass.gibbonPersonID=teacher.gibbonPersonID)  JOIN gibbonCourseClassPerson AS studentClass ON (studentClass.gibbonCourseClassID=teacherClass.gibbonCourseClassID) JOIN gibbonPerson AS student ON (studentClass.gibbonPersonID=student.gibbonPersonID) JOIN gibbonCourseClass ON (studentClass.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE teacher.status='Full' AND teacherClass.role='Teacher' AND studentClass.role='Student' AND student.gibbonPersonID=:gibbonPersonID AND gibbonCourse.gibbonSchoolYearID=(SELECT gibbonSchoolYearID FROM gibbonSchoolYear WHERE status='Current') ORDER BY teacher.preferredName, teacher.surname, teacher.email ;";
                                        $resultTeachers = $connection2->prepare($sqlTeachers);
                                        $resultTeachers->execute($dataTeachers);
                                    } catch (PDOException $e) {
                                    }
                                    while ($rowTeachers = $resultTeachers->fetch()) {
                                        setNotification($connection2, $guid, $rowTeachers['gibbonPersonID'], $notificationText, 'Behaviour', '/index.php?q=/modules/Behaviour/behaviour_letters.php&gibbonPersonID='.$row['gibbonPersonID']);
                                    }
                                }
                            } else { //It's being issued
                                //Create new record
                                try {
                                    $dataLetter = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $row['gibbonPersonID'], 'letterLevel' => $newLetterRequiredLevel, 'recordCountAtCreation' => $behaviourCount, 'body' => $body);
                                    $sqlLetter = "INSERT INTO gibbonBehaviourLetter SET gibbonSchoolYearID=:gibbonSchoolYearID, gibbonPersonID=:gibbonPersonID, letterLevel=:letterLevel, status='Issued', recordCountAtCreation=:recordCountAtCreation, body=:body";
                                    $resultLetter = $connection2->prepare($sqlLetter);
                                    $resultLetter->execute($dataLetter);
                                } catch (PDOException $e) {
                                    $letterUpdateFail = true;
                                }

                                if ($letterUpdateFail == false) {
                                    //Flag parents to receive email
                                    $email = true;

                                    $gibbonBehaviourLetterID = $connection2->lastInsertID();

                                    //Notify tutor(s)
                                    $notificationText = sprintf(__($guid, 'A student (%1$s) in your form group has received a behaviour letter.'), $studentName);
                                    if ($row['gibbonPersonIDTutor'] != '') {
                                        setNotification($connection2, $guid, $row['gibbonPersonIDTutor'], $notificationText, 'Behaviour', '/index.php?q=/modules/Behaviour/behaviour_letters.php&gibbonPersonID='.$row['gibbonPersonID']);
                                    }
                                    if ($row['gibbonPersonIDTutor2'] != '') {
                                        setNotification($connection2, $guid, $row['gibbonPersonIDTutor2'], $notificationText, 'Behaviour', '/index.php?q=/modules/Behaviour/behaviour_letters.php&gibbonPersonID='.$row['gibbonPersonID']);
                                    }
                                    if ($row['gibbonPersonIDTutor3'] != '') {
                                        setNotification($connection2, $guid, $row['gibbonPersonIDTutor3'], $notificationText, 'Behaviour', '/index.php?q=/modules/Behaviour/behaviour_letters.php&gibbonPersonID='.$row['gibbonPersonID']);
                                    }
                                }
                            }
                        }

                        //DEAL WIITH EMAILS
                        if ($email) {
                            $recipientList = '';
                            //Send emails
                            try {
                                $dataMember = array('gibbonPersonID' => $row['gibbonPersonID']);
                                $sqlMember = "SELECT DISTINCT email, preferredName, surname FROM gibbonFamilyChild JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonPerson ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonFamilyChild.gibbonPersonID=:gibbonPersonID AND gibbonPerson.status='Full' AND contactEmail='Y' ORDER BY contactPriority, surname, preferredName";
                                $resultMember = $connection2->prepare($sqlMember);
                                $resultMember->execute($dataMember);
                            } catch (PDOException $e) {
                            }
                            while ($rowMember = $resultMember->fetch()) {
                                ++$emailSendCount;
                                if ($rowMember['email'] == '') {
                                    ++$emailFailCount;
                                } else {
                                    $recipientList .= $rowMember['email'].', ';

                                    //Prep message
                                    $body .= '<br/><br/><i>'.sprintf(__($guid, 'Email sent via %1$s at %2$s.'), $_SESSION[$guid]['systemName'], $_SESSION[$guid]['organisationName']).'</i>';
                                    $bodyPlain = emailBodyConvert($body);

                                    $mail = new PHPMailer();
                                    $mail->IsSMTP();
                                    $mail->AddAddress($rowMember['email'], $rowMember['surname'].', '.$rowMember['preferredName']);
                                    if ($_SESSION[$guid]['organisationEmail'] != '') {
                                        $mail->SetFrom($_SESSION[$guid]['organisationEmail'], $_SESSION[$guid]['organisationName']);
                                    } else {
                                        $mail->SetFrom($_SESSION[$guid]['organisationAdministratorEmail'], $_SESSION[$guid]['organisationAdministratorName']);
                                    }
                                    $mail->CharSet = 'UTF-8';
                                    $mail->Encoding = 'base64';
                                    $mail->IsHTML(true);
                                    $mail->Subject = sprintf(__($guid, 'Behaviour Letter for %1$s via %2$s at %3$s'), $row['surname'].', '.$row['preferredName'].' ('.$row['rollGroup'].')', $_SESSION[$guid]['systemName'], $_SESSION[$guid]['organisationName']);
                                    $mail->Body = $body;
                                    $mail->AltBody = $bodyPlain;

                                    if (!$mail->Send()) {
                                        echo 'Here'.'<br/>';
                                        ++$emailFailCount;
                                    }
                                }
                            }

                            if ($recipientList != '') {
                                $recipientList = substr($recipientList, 0, -2);

                                //Record email recipients in letter record
                                try {
                                    $dataUpdate = array('recipientList' => $recipientList, 'gibbonBehaviourLetterID' => $gibbonBehaviourLetterID);
                                    $sqlUpdate = 'UPDATE gibbonBehaviourLetter set recipientList=:recipientList WHERE gibbonBehaviourLetterID=:gibbonBehaviourLetterID';
                                    $resultUpdate = $connection2->prepare($sqlUpdate);
                                    $resultUpdate->execute($dataUpdate);
                                } catch (PDOException $e) {
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    //Notify admin
    if ($email == false) {
        $notificationText = __($guid, 'The Behaviour Letter CLI script has run: no emails were sent.');
        setNotification($connection2, $guid, $_SESSION[$guid]['organisationAdministrator'], $notificationText, 'Behaviour', '/index.php?q=/modules/Behaviour/behaviour_letters.php');
    } else {
        $notificationText = sprintf(__($guid, 'The Behaviour Letter CLI script has run: %1$s emails were sent, of which %2$s failed.'), $emailSendCount, $emailFailCount);
        setNotification($connection2, $guid, $_SESSION[$guid]['organisationAdministrator'], $notificationText, 'User Admin', '/index.php?q=/modules/Behaviour/behaviour_letters.php');
    }
}
