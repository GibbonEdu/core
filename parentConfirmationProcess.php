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

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=parentConfirmation.php';
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

            $URL = $_SESSION[$guid]['absoluteURL']."/index.php?q=/parentConfirmation.php&input=$input&step=2&gibbonPersonResetID=$gibbonPersonResetID&key=$key";
            header("Location: {$URL}");

            // require $_SESSION[$guid]["absolutePath"] . '/lib/PHPMailer/PHPMailerAutoload.php';

            // //Send email
            // $subject = $_SESSION[$guid]['organisationNameShort'].' '.__($guid, 'Gibbon Account Confirmed');
            // $body = sprintf(__($guid, 'You\'ve successfully confirmed your account and are now ready to set your password which can be used to login to Gibbon for the first time.%2$sTo continue please use the link below (this link will only be vaild for the next 48 hours):%2$s%3$s%2$s%4$s'), $username, "\n\n", $_SESSION[$guid]['absoluteURL']."/index.php?q=/parentConfirmation.php&input=$input&step=2&gibbonPersonResetID=$gibbonPersonResetID&key=$key", $_SESSION[$guid]['systemName']." Administrator");

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
            
            print_r($_FILES);
            echo $_FILES['canvasImage']['tmp_name'];
            die();

        	$input = $_GET['input'];
        	$key = $_GET['key'];
        	$gibbonPersonResetID = $_GET['gibbonPersonResetID'];

        	//Verify authenticity of this request and check it is fresh (within 48 hours)
        	try {
                $data = array('key' => $key, 'gibbonPersonResetID' => $gibbonPersonResetID);
                $sql = "SELECT * FROM gibbonPersonReset WHERE `key`=:key AND gibbonPersonResetID=:gibbonPersonResetID AND (timestamp > DATE_SUB(now(), INTERVAL 2 DAY))";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $URL = $URL.'&return=error2';
                header("Location: {$URL}");
                exit();
            }

        	if ($result->rowCount() != 1) {
                $URL = $URL.'&return=error2';
                header("Location: {$URL}");
        	} else {
                $row = $result->fetch();
                $gibbonPersonID = $row['gibbonPersonID'];
                $passwordNew = $_POST['passwordNew'];
                $passwordConfirm = $_POST['passwordConfirm'];

                //Check passwords are not blank
                if ($passwordNew == '' or $passwordConfirm == '') {
                    $URL .= '&return=error1';
                    header("Location: {$URL}");
                } else {
                    //Check that new password is not same as old password
                    if ($password == $passwordNew) {
                        $URL .= '&return=error7';
                        header("Location: {$URL}");
                    } else {
                        //Check strength of password
                        $passwordMatch = doesPasswordMatchPolicy($connection2, $passwordNew);

                        if ($passwordMatch == false) {
                            $URL .= '&return=error6';
                            header("Location: {$URL}");
                        } else {
                            //Check new passwords match
                            if ($passwordNew != $passwordConfirm) {
                                $URL .= '&return=error4';
                                header("Location: {$URL}");
                            } else {
                                //Update password
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
                                    exit();
                                }

                                //Remove requests for this person
                                try {
                                    $data = array('gibbonPersonID' => $gibbonPersonID);
                                    $sql = "DELETE FROM gibbonPersonReset WHERE gibbonPersonID=:gibbonPersonID";
                                    $result = $connection2->prepare($sql);
                                    $result->execute($data);
                                } catch (PDOException $e) { }

                                //Return
                                $URL = $URLSuccess1.'?return=success1';
                                header("Location: {$URL}");
                            }
                        }
                    }
                }
            }
        }
    }
}
