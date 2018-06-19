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

use Gibbon\Comms\NotificationEvent;

include './gibbon.php';

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/publicRegistration.php';

$proceed = false;

if (isset($_SESSION[$guid]['username']) == false) {
    $enablePublicRegistration = getSettingByScope($connection2, 'User Admin', 'enablePublicRegistration');
    if ($enablePublicRegistration == 'Y') {
        $proceed = true;
    }
}

if ($proceed == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Lock activities table
    try {
        $data = array();
        $sql = 'LOCK TABLES gibbonPerson WRITE, gibbonSetting READ, gibbonNotification WRITE, gibbonModule WRITE';
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit();
    }

    // Sanitize the whole $_POST array
    $validator = new \Gibbon\Data\Validator();
    $_POST = $validator->sanitize($_POST);

    //Proceed!
    $surname = trim($_POST['surname']);
    $firstName = trim($_POST['firstName']);
    $preferredName = trim($firstName);
    $officialName = $firstName.' '.$surname;
    $gender = $_POST['gender'];
    $dob = $_POST['dob'];
    if ($dob == '') {
        $dob = null;
    } else {
        $dob = dateConvert($guid, $dob);
    }
    $email = trim($_POST['email']);
    $username = trim($_POST['usernameCheck']);
    $password = $_POST['passwordNew'];
    $salt = getSalt();
    $passwordStrong = hash('sha256', $salt.$password);
    $status = getSettingByScope($connection2, 'User Admin', 'publicRegistrationDefaultStatus');
    $gibbonRoleIDPrimary = getSettingByScope($connection2, 'User Admin', 'publicRegistrationDefaultRole');
    $gibbonRoleIDAll = $gibbonRoleIDPrimary;

    if ($surname == '' or $firstName == '' or $preferredName == '' or $officialName == '' or $gender == '' or $dob == '' or $email == '' or $username == '' or $password == '' or $gibbonRoleIDPrimary == '' or $gibbonRoleIDPrimary == '' or ($status != 'Pending Approval' and $status != 'Full')) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        //Check strength of password
        $passwordMatch = doesPasswordMatchPolicy($connection2, $password);

        if ($passwordMatch == false) {
            $URL .= '&return=error7';
            header("Location: {$URL}");
        } else {
            //Check uniqueness of username
            try {
                $data = array('username' => $username, 'email' => $email);
                $sql = 'SELECT * FROM gibbonPerson WHERE username=:username OR email=:email';
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
                //Check publicRegistrationMinimumAge
                $publicRegistrationMinimumAge = getSettingByScope($connection2, 'User Admin', 'publicRegistrationMinimumAge');

                $ageFail = false;
                if ($publicRegistrationMinimumAge == '') {
                    $ageFail = true;
                } elseif ($publicRegistrationMinimumAge > 0 and $publicRegistrationMinimumAge > getAge($guid, dateConvertToTimestamp($dob), true, true)) {
                    $ageFail = true;
                }

                if ($ageFail == true) {
                    $URL .= '&return=warning1';
                    header("Location: {$URL}");
                } else {
                    //Write to database
                    try {
                        $data = array('surname' => $surname, 'firstName' => $firstName, 'preferredName' => $preferredName, 'officialName' => $officialName, 'gender' => $gender, 'dob' => $dob, 'email' => $email, 'username' => $username, 'passwordStrong' => $passwordStrong, 'passwordStrongSalt' => $salt, 'status' => $status, 'gibbonRoleIDPrimary' => $gibbonRoleIDPrimary, 'gibbonRoleIDAll' => $gibbonRoleIDAll);
                        $sql = "INSERT INTO gibbonPerson SET surname=:surname, firstName=:firstName, preferredName=:preferredName, officialName=:officialName, gender=:gender, dob=:dob, email=:email, username=:username, password='', passwordStrong=:passwordStrong, passwordStrongSalt=:passwordStrongSalt, status=:status, gibbonRoleIDPrimary=:gibbonRoleIDPrimary, gibbonRoleIDAll=:gibbonRoleIDAll";
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        echo $e->getMessage();
                        exit();
                        $URL .= '&return=error2';
                        header("Location: {$URL}");
                        exit();
                    }

                    $gibbonPersonID = $connection2->lastInsertId();

                    try {
                        $sqlLock = 'UNLOCK TABLES';
                        $result = $connection2->query($sqlLock);
                    } catch (PDOException $e) {
                    }

                    if ($status == 'Pending Approval') {
                        // Raise a new notification event
                        $event = new NotificationEvent('User Admin', 'New Public Registration');

                        $event->addRecipient($_SESSION[$guid]['organisationAdmissions']);
                        $event->setNotificationText(sprintf(__('An new public registration, for %1$s, is pending approval.'), formatName('', $preferredName, $surname, 'Student')));
                        $event->setActionLink("/index.php?q=/modules/User Admin/user_manage_edit.php&gibbonPersonID=$gibbonPersonID&search=");

                        $event->sendNotifications($pdo, $gibbon->session);

                        $URL .= '&return=success1';
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
