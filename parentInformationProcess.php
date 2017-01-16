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

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=parentInformation.php';
$URLSuccess1 = $_SESSION[$guid]['absoluteURL'].'/index.php';

if ($input == '' or ($step != 1 and $step != 2)) {
    $URL = $URL.'&return=error0';
    header("Location: {$URL}");
}
//Otherwise proceed
else {

    try {
        $data = array('email' => $input, 'username' => $input);
        $sql = "SELECT gibbonPersonID, email, username FROM gibbonPerson JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDAll LIKE CONCAT('%', gibbonRole.gibbonRoleID, '%')) WHERE gibbonRole.category='Parent' AND (email=:email OR username=:username) AND gibbonPerson.status='Full' AND NOT email=''";
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
                exit();
            }

            // Format birthday in in YYYY-MM-DD
            $birthdate = $birthyear.'-'.$birthmonth.'-'.str_pad( intval(trim($birthday)), 2, '0');

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
                exit();
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
                exit();
            }
            $gibbonPersonResetID = str_pad($connection2->lastInsertID(), 12, '0', STR_PAD_LEFT);

            $URL = $_SESSION[$guid]['absoluteURL']."/index.php?q=/parentInformation.php&input=$input&step=2&gibbonPersonResetID=$gibbonPersonResetID&key=$key";
            header("Location: {$URL}");

            // require $_SESSION[$guid]["absolutePath"] . '/lib/PHPMailer/PHPMailerAutoload.php';

            // //Send email
            // $subject = $_SESSION[$guid]['organisationNameShort'].' '.__($guid, 'Gibbon Account Confirmed');
            // $body = sprintf(__($guid, 'You\'ve successfully confirmed your account and are now ready to set your password which can be used to login to Gibbon for the first time.%2$sTo continue please use the link below (this link will only be vaild for the next 48 hours):%2$s%3$s%2$s%4$s'), $username, "\n\n", $_SESSION[$guid]['absoluteURL']."/index.php?q=/parentInformation.php&input=$input&step=2&gibbonPersonResetID=$gibbonPersonResetID&key=$key", $_SESSION[$guid]['systemName']." Administrator");

            // $mail = getGibbonMailer($guid);
            // $mail->AddAddress($email);

            // if (isset($_SESSION[$guid]['organisationEmail']) && $_SESSION[$guid]['organisationEmail'] != '') {
            //     $mail->SetFrom($_SESSION[$guid]['organisationEmail'], $_SESSION[$guid]['organisationName']);
            // } else {
            //     $mail->SetFrom($_SESSION[$guid]['organisationAdministratorEmail'], $_SESSION[$guid]['organisationName']);
            // }
            
            // $mail->CharSet="UTF-8";
            // $mail->Encoding="base64" ;
            // $mail->IsHTML(true);
            // $mail->Subject=$subject ;
            // $mail->Body = nl2br($body) ;
            // $mail->AltBody = emailBodyConvert($body) ;

            // if ($mail->Send()) {
            //     $URL = $URL.'&return=success0';
            //     header("Location: {$URL}");
            // } else {
            //     $URL = $URL.'&return=error3';
            //     header("Location: {$URL}");
            // }
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
                        exit();
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
        	} else {


                try {
                    $data = array('gibbonPersonID' => $gibbonPersonID);
                    $sql = "SELECT username, canLogin, email, gibbonFamilyAdult.gibbonFamilyID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonPerson.gibbonPersonID=gibbonFamilyAdult.gibbonPersonID) WHERE gibbonPerson.gibbonPersonID=:gibbonPersonID";
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
                            if (file_put_contents( $_SESSION[$guid]['absolutePath'].'/'.$photoPath.'/'.$id.'.jpg', $binary ) !== false) {

                                // Update the photo link for this family member
                                try {
                                    $data = array('image_240' => $photoPath.'/'.$id.'.jpg', 'username' => $id, 'gibbonFamilyID' => $row['gibbonFamilyID'] );
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

                    // User cannot currently login - generate a password, and activate their account
                    if ($row['canLogin'] == 'N') {

                        //Generate a password
                        $passwordNew = randomPassword(10);
                        $salt = getSalt();
                        $passwordStrong = hash('sha256', $salt.$passwordNew);
                        try {
                            $data = array('passwordStrong' => $passwordStrong, 'salt' => $salt, 'gibbonPersonID' => $gibbonPersonID);
                            $sql = "UPDATE gibbonPerson SET password='', passwordStrong=:passwordStrong, passwordStrongSalt=:salt, canLogin='Y', passwordForceReset='N', failCount=0 WHERE gibbonPersonID=:gibbonPersonID";
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        } catch (PDOException $e) {
                            $URL .= '&return=error2';
                            header("Location: {$URL}");
                            exit;
                        }

                        require $_SESSION[$guid]["absolutePath"] . '/lib/PHPMailer/PHPMailerAutoload.php';

                        //Send email
                        $email = $row['email'];
                        $subject = $_SESSION[$guid]['organisationNameShort'].' '.__($guid, 'Gibbon Account Confirmed - You may now login');
                        $body = sprintf(__($guid, 'You\'ve successfully confirmed your account. Below you will find your username and password which can be used to login to Gibbon for the first time.%1$sUsername: %2$s%1$sPassword: %3$s%1$sTo continue please use the link below:%1$s%4$s%1$s'), "\n\n", $row['email'], $passwordNew, $_SESSION[$guid]['absoluteURL']."/index.php", $_SESSION[$guid]['systemName']." Administrator");

                        $mail = getGibbonMailer($guid);
                        $mail->AddAddress($email);

                        if (isset($_SESSION[$guid]['organisationEmail']) && $_SESSION[$guid]['organisationEmail'] != '') {
                            $mail->SetFrom($_SESSION[$guid]['organisationEmail'], $_SESSION[$guid]['organisationName']);
                        } else {
                            $mail->SetFrom($_SESSION[$guid]['organisationAdministratorEmail'], $_SESSION[$guid]['organisationName']);
                        }

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
