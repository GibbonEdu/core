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

include './functions.php';
include './config.php';

//New PDO DB connection
$pdo = new Gibbon\sqlConnection();
$connection2 = $pdo->getConnection();

//Start session
@session_start();

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]['timezone']);

//Create password
$password = randomPassword(8);

//Check email address is not blank
if (isset($_GET['input']))
    $input = $_GET['input'];
else
    $input = $_POST['email'];
$step = $_GET['step'];

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=parentInformation.php&sidebar=false';
$URLSuccess1 = $_SESSION[$guid]['absoluteURL'].'/index.php';

if ($input == '' or ($step != 1 and $step != 2)) {
    $URL = $URL.'&return=error0';
    header("Location: {$URL}");
    exit;
}
//Otherwise proceed
else {

    try {
        $data = array('email' => $input);
        $sql = "SELECT gibbonPersonID, email, username FROM gibbonPerson JOIN gibbonRole ON (gibbonRole.gibbonRoleID=gibbonPerson.gibbonRoleIDPrimary) WHERE (gibbonRole.category='Parent' OR gibbonRole.category='Staff') AND email=:email AND gibbonPerson.status='Full' AND canLogin<>'N' AND NOT email=''";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $URL = $URL.'&return=error2';
        header("Location: {$URL}");
        exit();
    }

    if ($result->rowCount() != 1) {
        $URL = $URL.'&return=error9';
        header("Location: {$URL}");
        exit;
    } else {
        $row = $result->fetch();
        $gibbonPersonID = $row['gibbonPersonID'];
        $email = $row['email'];
        $username = $row['username'];

        if ($step == 1) { //This is the request phase


            // Confirm parent using child's birthdate
            $birthyear = (isset($_POST['birthyear']))? $_POST['birthyear'] : NULL;
            $birthmonth = (isset($_POST['birthmonth']))? $_POST['birthmonth'] : NULL;
            $birthday = (isset($_POST['birthday']))? $_POST['birthday'] : NULL;

            if (empty($birthyear) || empty($birthmonth) || empty($birthday)) {
                $URL = $URL.'&return=error8';
                header("Location: {$URL}");
                exit;
            }

            // Format birthday in in YYYY-MM-DD
            $birthdate = $birthyear.'-'.$birthmonth.'-'.str_pad( intval(trim($birthday)), 2, '0', STR_PAD_LEFT);

            // Find a currently enroled child in this family that matches the same birthdate
            try {
                $data = array('gibbonPersonID' => $gibbonPersonID, 'birthdate' => $birthdate);
                $sql = "SELECT student.gibbonPersonID FROM gibbonFamilyAdult JOIN gibbonFamilyChild ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamilyChild.gibbonFamilyID) JOIN gibbonPerson AS student ON (gibbonFamilyChild.gibbonPersonID=student.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=student.gibbonPersonID) WHERE gibbonFamilyAdult.gibbonPersonID=:gibbonPersonID AND student.dob=:birthdate AND student.status='Full' && gibbonStudentEnrolment.gibbonSchoolYearID=(SELECT gibbonSchoolYearID FROM gibbonSchoolYear WHERE status='Current')";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $URL = $URL.'&return=error2';
                header("Location: {$URL}");
                exit();
            }

            if ($result->rowCount() == 0) {
                // Child not found, or birthdate doesn't match
                $URL = $URL.'&return=error8';
                header("Location: {$URL}");
                exit;
            }

            //Generate key
            $key = randomPassword(40);

            //Try to delete other recors for this user
            try {
                $data = array('gibbonPersonID' => $gibbonPersonID);
                $sql = "DELETE FROM gibbonPersonReset WHERE gibbonPersonID=:gibbonPersonID";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) { }

            //Insert key record
            try {
                $data = array('gibbonPersonID' => $gibbonPersonID, 'key' => $key);
                $sql = "INSERT INTO gibbonPersonReset SET gibbonPersonID=:gibbonPersonID, `key`=:key";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $URL = $URL.'&return=error2';
                header("Location: {$URL}");
                exit;
            }
            $gibbonPersonResetID = str_pad($connection2->lastInsertID(), 12, '0', STR_PAD_LEFT);

            $URL = $_SESSION[$guid]['absoluteURL']."/index.php?q=parentInformation.php&input=$input&step=2&gibbonPersonResetID=$gibbonPersonResetID&key=$key&sidebar=false";
            header("Location: {$URL}");
            exit;
        }
        else { //This is the confirmation/reset phase
            //Get URL parameters

            $proceed = false;

            if (!empty($_SESSION[$guid]['username'])) {
                // Logged in users
                $gibbonPersonID = $_SESSION[$guid]['gibbonPersonID'];
                $proceed = true;
            } else {

                // Not logged in
            	$input = $_GET['input'];
            	$key = $_GET['key'];
            	$gibbonPersonResetID = $_GET['gibbonPersonResetID'];

                if (!empty($input) && !empty($key) && !empty($gibbonPersonResetID)) {
                	//Verify authenticity of this request and check it is fresh (within 48 hours)
                	try {
                        $data = array('key' => $key, 'gibbonPersonResetID' => $gibbonPersonResetID);
                        $sql = "SELECT gibbonPersonID FROM gibbonPersonReset WHERE `key`=:key AND gibbonPersonResetID=:gibbonPersonResetID AND (timestamp > DATE_SUB(now(), INTERVAL 2 DAY))";
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $URL = $URL.'&return=error2';
                        header("Location: {$URL}");
                        exit;
                    }

                    if ($result->rowCount() == 1) {
                        $gibbonPersonID = $result->fetchColumn(0);
                        $proceed = true;
                    }
                }
            }

        	if ($proceed == false) {
                $URL = $URL.'&return=error1';
                header("Location: {$URL}");
                exit;
        	} else {


                try {
                    $data = array('gibbonPersonID' => $gibbonPersonID);
                    $sql = "SELECT username, canLogin, email, fields, gibbonFamilyAdult.gibbonFamilyID, (SELECT gibbonPersonFieldID FROM gibbonPersonField WHERE name='Account Activated') as `activationID` FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonPerson.gibbonPersonID=gibbonFamilyAdult.gibbonPersonID) WHERE gibbonPerson.gibbonPersonID=:gibbonPersonID";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $URL = $URL.'&return=error2';
                    header("Location: {$URL}");
                    exit;
                }

                if ($result->rowCount() != 1) {
                    $URL = $URL.'&return=error1';
                    header("Location: {$URL}");
                    exit;
                } else {

                    $row = $result->fetch();

                    $partialFail = false;

                    //Remove requests for this person
                    try {
                        $data = array('gibbonPersonID' => $gibbonPersonID);
                        $sql = "DELETE FROM gibbonPersonReset WHERE gibbonPersonID=:gibbonPersonID";
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) { }


                    $attachments = $_POST['attachment'];
                    $photoPath = 'uploads/photosFamily';

                    // Upload photos and update image_240 in accounts
                    if (is_array($attachments) && count($attachments) > 0) {
                        foreach ($attachments as $id => $attachment) {
                            if (empty($attachment)) continue; // Skip empty attachments

                            // Upload the data URI
                            $binary = file_get_contents( 'data://' . substr($attachment, 5) );
                            $filename = $id.'.jpg';
                            if (file_put_contents( $_SESSION[$guid]['absolutePath'].'/'.$photoPath.'/'.$filename, $binary ) !== false) {

                                // Update the photo link for this family member
                                try {
                                    $data = array('image_240' => $photoPath.'/'.$filename, 'username' => $id, 'gibbonFamilyID' => $row['gibbonFamilyID'] );
                                    $sql = "UPDATE gibbonPerson, gibbonFamilyAdult SET gibbonPerson.image_240=:image_240 WHERE gibbonPerson.gibbonPersonID=gibbonFamilyAdult.gibbonPersonID AND gibbonFamilyAdult.gibbonFamilyID=:gibbonFamilyID AND gibbonPerson.username=:username";
                                    $result = $connection2->prepare($sql);
                                    $result->execute($data);
                                } catch (PDOException $e) {
                                    $partialFail = true;
                                }
                            } else {
                                $partialFail = true;
                            }
                        }
                    }


                    $additionalPhotos = $_POST['attachmentAdditional'];
                    $additionalName = $_POST['additionalName'];
                    $additionalRelationship = $_POST['additionalRelationship'];

                    // Upload photos and create/update gibbonFamilyAdditionalPeople
                    if (is_array($additionalName) && count($additionalName) > 0) {
                        foreach ($additionalName as $id => $name) {

                            if (empty($name)) continue; // Skip empty additional people

                            $relationship = (isset($additionalRelationship[$id]))? $additionalRelationship[$id] : '';
                            $image_240 = '';

                            if (!empty($additionalPhotos[$id])) {
                                // Upload the data URI
                                $binary = file_get_contents( 'data://' . substr($additionalPhotos[$id], 5) );
                                $filename = $row['gibbonFamilyID'].'-'.$id.'.jpg';
                                if (file_put_contents( $_SESSION[$guid]['absolutePath'].'/'.$photoPath.'/'.$filename, $binary ) === false) {
                                    $partialFail = true;
                                }

                                $image_240 = $photoPath.'/'.$filename;
                            }

                            // Update the photo link for this family member
                            try {
                                $data = array('image_240' => $image_240, 'name' => $name, 'relationship' => $relationship, 'sequenceNumber' => $id, 'gibbonFamilyID' => $row['gibbonFamilyID'] );
                                $sql = "INSERT INTO gibbonFamilyAdditionalPerson SET gibbonFamilyID=:gibbonFamilyID, sequenceNumber=:sequenceNumber, name=:name, relationship=:relationship, image_240=:image_240, timestamp=CURRENT_TIMESTAMP ON DUPLICATE KEY UPDATE name=:name, relationship=:relationship, image_240=:image_240";
                                $result = $connection2->prepare($sql);
                                $result->execute($data);
                            } catch (PDOException $e) {
                                $partialFail = true;
                            }
                        }
                    }

                    // Insert a new ID card request
                    try {
                        $data = array(
                            'gibbonRecordType' => 'gibbonFamily',
                            'gibbonRecordID' => $row['gibbonFamilyID'],
                            'gibbonPersonIDRequested' => $gibbonPersonID,
                        );
                        $sql = "INSERT INTO idCardRequest SET gibbonRecordType=:gibbonRecordType, gibbonRecordID=:gibbonRecordID, gibbonPersonIDRequested=:gibbonPersonIDRequested, status='New' ON DUPLICATE KEY UPDATE status='New', gibbonPersonIDRequested=:gibbonPersonIDRequested, timestampRequested=CURRENT_TIMESTAMP";
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $partialFail = true;
                    }

                    // User cannot currently login - generate a password, and activate their account
                    if ($row['canLogin'] == 'A') {

                        //Generate a password
                        $passwordNew = randomPassword(10);
                        $salt = getSalt();
                        $passwordStrong = hash('sha256', $salt.$passwordNew);

                        $fields = array();
                        if (!empty($row['activationID'])) {
                            $fields = (!empty($row['fields']))? unserialize($row['fields']) : array();
                            $fields[$row['activationID']] = date('Y-m-d');
                        }

                        try {
                            $data = array('passwordStrong' => $passwordStrong, 'salt' => $salt, 'fields' => serialize($fields), 'gibbonPersonID' => $gibbonPersonID);
                            $sql = "UPDATE gibbonPerson SET password='', passwordStrong=:passwordStrong, passwordStrongSalt=:salt, canLogin='Y', passwordForceReset='N', failCount=0, fields=:fields WHERE gibbonPersonID=:gibbonPersonID";
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        } catch (PDOException $e) {
                            $URL .= '&return=error2';
                            header("Location: {$URL}");
                            exit;
                        }

                        require_once $_SESSION[$guid]["absolutePath"] . '/lib/PHPMailer/PHPMailerAutoload.php';

                        //Send email
                        $email = $row['email'];
                        $subject = $_SESSION[$guid]['organisationNameShort'].' '.__($guid, 'Photo upload complete: TIS login details enclosed');

                        if ($gibbon->locale->getLocale() == 'zh_HK') {
                            $body = sprintf('
感謝閣下確認帳戶並上載照片。若要上載更多家庭成員照片，請按以下指示進行。

歡迎登入澳門國際學校學生訊息系統－Gibbon。此系統可供老師紀錄學生出勤以及鍵入成績分數。另外，初中一年級至高中三年級學生亦可透過此系統，查看課堂時間表、索取課堂資料以及交功課。現學校為家長開通查看學生訊息部份。

登入：  %1$s

電郵：  %2$s
密碼：  %3$s

可供家長查看的訊息：
<ul><li>更新家長或子女的訊息</li>
<li>查看子女出勤情況</li>
<li>查看子女課堂時間表</li>
<li>查看子女參與之課後活動</li>
<li>查看子女修讀科目之課程大綱及授課單元（初中一至高中三）</li>
<li>查看子女就讀之年級（初中一至高中三）</li></ul>

請點擊以下連結 <a href="https://goo.gl/711A1S">https://goo.gl/711A1S</a> 了解詳細操作。若有任何疑問，歡迎聯絡系統部同事：
<ul><li>Brian Avery - <a mailto="brian.avery@tis.edu.mo">brian.avery@tis.edu.mo</a></li><li>Mel Varga - <a mailto="mel.varga@tis.edu.mo">mel.varga@tis.edu.mo</a></li></ul>

期望  閣下在使用系統時感到便利。', $_SESSION[$guid]['absoluteURL'], $row['email'], $passwordNew);
                        } else {
                        $body = sprintf('Thank you for confirming your account and uploading photos. If you\'re done uploading photos no further action needs taken at this time. If you need to continue uploading photos please see the login information below.

We would like to welcome you to The International School of Macao’s new Student Information System - Gibbon.  Gibbon is used by teachers to take attendance & enter report card marks and Gibbon is used by Grade 7-12 students to view their timetable, access class resources and submit school work.  We are now opening Student Information System to parents for secure access.

Login here: %1$s

Email:  %2$s
Password:  %3$s

As a parent you will be able to access the following:
<ul><li>Update your own and your child’s personal information</li><li>View your child’s attendance</li><li>View your child’s school timetable</li><li>View after school activities your child is involved in</li><li>View course outlines and unit plans of the subjects your child is studying (Grades 7-12)</li><li>View your child’s grades (Grades 7-12)</li></ul>

Please follow this link <a href="https://goo.gl/711A1S">https://goo.gl/711A1S</a> for instructions on how to access these functions. If you have any questions or would like to have individual instruction please contact one of the following Gibbon administrators:
<ul><li>Brian Avery - <a mailto="brian.avery@tis.edu.mo">brian.avery@tis.edu.mo</a></li><li>Mel Varga - <a mailto="mel.varga@tis.edu.mo">mel.varga@tis.edu.mo</a></li></ul>

We hope you will find the system helpful and easy to use.

The TIS Gibbon team', $_SESSION[$guid]['absoluteURL'], $row['email'], $passwordNew);
                        }

                        $mail = getGibbonMailer($guid);
                        $mail->AddAddress($email);

                        $mail->AddReplyTo('mel.varga@tis.edu.mo', 'The TIS Gibbon team');
                        $mail->SetFrom($_SESSION[$guid]['organisationEmail'], $_SESSION[$guid]['organisationName']);

                        $mail->CharSet="UTF-8";
                        $mail->Encoding="base64" ;
                        $mail->IsHTML(true);
                        $mail->Subject=$subject ;
                        $mail->Body = nl2br($body) ;
                        $mail->AltBody = emailBodyConvert($body) ;

                        $mail->Send();

                        //Return
                        $URL = $URLSuccess1.'?return=success2';
                        header("Location: {$URL}");
                        exit;

                    }
                    elseif ($row['canLogin'] == 'Y' && empty($_SESSION[$guid]['username'])) {
                        require_once $_SESSION[$guid]["absolutePath"] . '/lib/PHPMailer/PHPMailerAutoload.php';

                        //Send email
                        $email = $row['email'];
                        $subject = $_SESSION[$guid]['organisationNameShort'].' '.__($guid, 'Photo upload complete');
                        if ($gibbon->locale->getLocale() == 'zh_HK') {
                            $body = sprintf('
感謝閣下確認帳戶並上載照片。若需繼續上載照片，請按下登入按鈕。

登入：  %1$s

可供家長查看的訊息：
<ul><li>更新家長或子女的訊息</li>
<li>查看子女出勤情況</li>
<li>查看子女課堂時間表</li>
<li>查看子女參與之課後活動</li>
<li>查看子女修讀科目之課程大綱及授課單元（初中一至高中三）</li>
<li>查看子女就讀之年級（初中一至高中三）</li></ul>

請點擊以下連結 <a href="https://goo.gl/711A1S">https://goo.gl/711A1S</a> 了解詳細操作。若有任何疑問，歡迎聯絡系統部同事：
<ul><li>Brian Avery - <a mailto="brian.avery@tis.edu.mo">brian.avery@tis.edu.mo</a></li><li>Mel Varga - <a mailto="mel.varga@tis.edu.mo">mel.varga@tis.edu.mo</a></li></ul>

期望  閣下在使用系統時感到便利。', $_SESSION[$guid]['absoluteURL']);
                        } else {
                            $body = sprintf('Thank you for confirming your information and uploading photos. If you\'re done uploading photos no further action needs taken at this time. If you need to continue uploading photos please follow the login link below and use your existing account details.

Login here: %1$s

As a parent you will be able to access the following:
<ul><li>Update your own and your child’s personal information</li><li>View your child’s attendance</li><li>View your child’s school timetable</li><li>View after school activities your child is involved in</li><li>View course outlines and unit plans of the subjects your child is studying (Grades 7-12)</li><li>View your child’s grades (Grades 7-12)</li></ul>

Please follow this link <a href="https://goo.gl/711A1S">https://goo.gl/711A1S</a> for instructions on how to access these functions. If you have any questions or would like to have individual instruction please contact one of the following Gibbon administrators:
<ul><li>Brian Avery - <a mailto="brian.avery@tis.edu.mo">brian.avery@tis.edu.mo</a></li><li>Mel Varga - <a mailto="mel.varga@tis.edu.mo">mel.varga@tis.edu.mo</a></li></ul>

We hope you will find the system helpful and easy to use.

The TIS Gibbon team', $_SESSION[$guid]['absoluteURL']);
                        }

                        $mail = getGibbonMailer($guid);
                        $mail->AddAddress($email);

                        $mail->AddReplyTo('mel.varga@tis.edu.mo', 'The TIS Gibbon team');
                        $mail->SetFrom($_SESSION[$guid]['organisationEmail'], $_SESSION[$guid]['organisationName']);

                        $mail->CharSet="UTF-8";
                        $mail->Encoding="base64" ;
                        $mail->IsHTML(true);
                        $mail->Subject=$subject ;
                        $mail->Body = nl2br($body) ;
                        $mail->AltBody = emailBodyConvert($body) ;

                        $mail->Send();

                        //Return
                        $URL = $URLSuccess1.'?return=success3';
                        header("Location: {$URL}");
                        exit;
                    }

                    //Return
                    if ($partialFail) {
                        $URL = $URLSuccess1.'?return=warning1';
                    } else {
                        $URL = $URLSuccess1.'?return=success0';
                    }
                    header("Location: {$URL}");
                    exit;
                }
            }
        }
    }
}
