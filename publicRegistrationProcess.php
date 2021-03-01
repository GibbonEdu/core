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
use Gibbon\Services\Format;

include './gibbon.php';

//Module includes from User Admin (for custom fields)
include './modules/User Admin/moduleFunctions.php';

$URL = $gibbon->session->get('absoluteURL').'/index.php?q=/publicRegistration.php';

$proceed = false;

if ($gibbon->session->exists('username') == false) {
    $enablePublicRegistration = getSettingByScope($connection2, 'User Admin', 'enablePublicRegistration');
    if ($enablePublicRegistration == 'Y') {
        $proceed = true;
    }
}

if ($proceed == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
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
        exit;
    }

    // Check email address domain
    $allowedDomains = getSettingByScope($connection2, 'User Admin', 'publicRegistrationAllowedDomains');
    $allowedDomains = array_filter(array_map('trim', explode(',', $allowedDomains)));

    if (!empty($allowedDomains)) {
        $emailCheck = array_filter($allowedDomains, function ($domain) use ($email) {
            return stripos($email, $domain) !== false;
        });
        if (empty($emailCheck)) {
            $URL .= '&return=error8';
            header("Location: {$URL}");
            exit;
        }
    }

    // Check required custom fields
    $customRequireFail = false;
    $resultFields = getCustomFields($connection2, $guid, null, null, null, null, null, null, true);
    $fields = array();
    if ($resultFields->rowCount() > 0) {
        while ($rowFields = $resultFields->fetch()) {
            if (isset($_POST['custom'.$rowFields['gibbonCustomFieldID']])) {
                if ($rowFields['type'] == 'date') {
                    $fields[$rowFields['gibbonCustomFieldID']] = dateConvert($guid, $_POST['custom'.$rowFields['gibbonCustomFieldID']]);
                } else {
                    $fields[$rowFields['gibbonCustomFieldID']] = $_POST['custom'.$rowFields['gibbonCustomFieldID']];
                }
            }
            if ($rowFields['required'] == 'Y') {
                if (isset($_POST['custom'.$rowFields['gibbonCustomFieldID']]) == false) {
                    $customRequireFail = true;
                } elseif ($_POST['custom'.$rowFields['gibbonCustomFieldID']] == '') {
                    $customRequireFail = true;
                }
            }
        }
    }

    if ($customRequireFail) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    } else {
        $fields = json_encode($fields);
    }

    // Check strength of password
    $passwordMatch = doesPasswordMatchPolicy($connection2, $password);

    if ($passwordMatch == false) {
        $URL .= '&return=error6';
        header("Location: {$URL}");
        exit;
    }

    // Check uniqueness of username (and/or email, if required)
    $uniqueEmailAddress = getSettingByScope($connection2, 'User Admin', 'uniqueEmailAddress');
    if ($uniqueEmailAddress == 'Y') {
        $data = array('username' => $username, 'email' => $email);
        $sql = 'SELECT * FROM gibbonPerson WHERE username=:username OR email=:email';
        $result = $pdo->selectOne($sql, $data);
    } else {
        $data = array('username' => $username);
        $sql = 'SELECT * FROM gibbonPerson WHERE username=:username';
        $result = $pdo->selectOne($sql, $data);
    }

    if (!empty($result)) {
        $URL .= '&return=error7';
        header("Location: {$URL}");
        exit;
    } 

    // Check publicRegistrationMinimumAge
    $publicRegistrationMinimumAge = getSettingByScope($connection2, 'User Admin', 'publicRegistrationMinimumAge');

    if (!empty($publicRegistrationMinimumAge) > 0 and $publicRegistrationMinimumAge > (new DateTime('@'.Format::timestamp($dob)))->diff(new DateTime())->y) {
        $URL .= '&return=error5';
        header("Location: {$URL}");
        exit;
    }

    //Write to database
    $data = array('surname' => $surname, 'firstName' => $firstName, 'preferredName' => $preferredName, 'officialName' => $officialName, 'gender' => $gender, 'dob' => $dob, 'email' => $email, 'username' => $username, 'passwordStrong' => $passwordStrong, 'passwordStrongSalt' => $salt, 'status' => $status, 'gibbonRoleIDPrimary' => $gibbonRoleIDPrimary, 'gibbonRoleIDAll' => $gibbonRoleIDAll, 'fields' => $fields);
    $sql = "INSERT INTO gibbonPerson SET surname=:surname, firstName=:firstName, preferredName=:preferredName, officialName=:officialName, gender=:gender, dob=:dob, email=:email, username=:username, password='', passwordStrong=:passwordStrong, passwordStrongSalt=:passwordStrongSalt, status=:status, gibbonRoleIDPrimary=:gibbonRoleIDPrimary, gibbonRoleIDAll=:gibbonRoleIDAll, fields=:fields";

    $gibbonPersonID = $pdo->insert($sql, $data);

    if (empty($gibbonPersonID)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    if ($status == 'Pending Approval') {
        // Raise a new notification event
        $event = new NotificationEvent('User Admin', 'New Public Registration');

        $event->addRecipient($gibbon->session->get('organisationAdmissions'));
        $event->setNotificationText(sprintf(__('An new public registration, for %1$s, is pending approval.'), Format::name('', $preferredName, $surname, 'Student')));
        $event->setActionLink("/index.php?q=/modules/User Admin/user_manage_edit.php&gibbonPersonID=$gibbonPersonID&search=");

        $event->sendNotifications($pdo, $gibbon->session);

        $URL .= '&return=success1';
        header("Location: {$URL}");
    } else {
        $URL .= '&return=success0';
        header("Location: {$URL}");
    }
}
