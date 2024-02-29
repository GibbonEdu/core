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

use Gibbon\Http\Url;
use Gibbon\Data\Validator;
use Gibbon\Services\Format;
use Gibbon\Comms\NotificationEvent;
use Gibbon\Data\PasswordPolicy;
use Gibbon\Forms\CustomFieldHandler;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\User\UserStatusLogGateway;

include './gibbon.php';

//Module includes from User Admin (for custom fields)
include './modules/User Admin/moduleFunctions.php';

$URL = Url::fromRoute('publicRegistration');

$proceed = false;

$settingGateway = $container->get(SettingGateway::class);

if ($session->exists('username') == false) {
    $enablePublicRegistration = $settingGateway->getSettingByScope('User Admin', 'enablePublicRegistration');
    if ($enablePublicRegistration == 'Y') {
        $proceed = true;
    }
}

if ($proceed == false) {
    header("Location: {$URL->withReturn('error0')}");
} else {
    // Sanitize the whole $_POST array
    $validator = $container->get(Validator::class);
    $_POST = $validator->sanitize($_POST);

    // Check the honey pot field, it should always be empty
    if (!empty($_POST['emailAddress'])) {
        header("Location: {$URL->withReturn('warning1')}");
        exit;
    }

    //Proceed!
    $surname = trim($_POST['surname']);
    $firstName = trim($_POST['firstName']);
    $preferredName = trim($firstName);
    $officialName = $firstName.' '.$surname;
    $gender = $_POST['gender'] ?? '';
    $dob = $_POST['dob'] ?? '';
    if ($dob == '') {
        $dob = null;
    } else {
        $dob = Format::dateConvert($dob);
    }
    $email = trim($_POST['email']);
    $emailAlternate = (!empty($_POST['emailAlternate']) ? trim($_POST['emailAlternate']) : '');
    $username = trim($_POST['usernameCheck']);
    $password = $_POST['passwordNew'] ?? '';
    $salt = getSalt();
    $passwordStrong = hash('sha256', $salt.$password);
    $status = $settingGateway->getSettingByScope('User Admin', 'publicRegistrationDefaultStatus');
    $gibbonRoleIDPrimary = $settingGateway->getSettingByScope('User Admin', 'publicRegistrationDefaultRole');
    $gibbonRoleIDAll = $gibbonRoleIDPrimary;

    if ($surname == '' or $firstName == '' or $preferredName == '' or $officialName == '' or $gender == '' or $dob == '' or $email == '' or $username == '' or $password == '' or $gibbonRoleIDPrimary == '' or $gibbonRoleIDPrimary == '' or ($status != 'Pending Approval' and $status != 'Full')) {
        header("Location: {$URL->withReturn('error1')}");
        exit;
    }

    // Check email address domain
    $allowedDomains = $settingGateway->getSettingByScope('User Admin', 'publicRegistrationAllowedDomains');
    $allowedDomains = array_filter(array_map('trim', explode(',', $allowedDomains)));

    if (!empty($allowedDomains)) {
        $emailCheck = array_filter($allowedDomains, function ($domain) use ($email) {
            return stripos($email, $domain) !== false;
        });
        if (empty($emailCheck)) {
            header("Location: {$URL->withReturn('error8')}");
            exit;
        }
    }

    $customRequireFail = false;
    $fields = $container->get(CustomFieldHandler::class)->getFieldDataFromPOST('User', ['publicRegistration' => 1], $customRequireFail);

    if ($customRequireFail) {
        header("Location: {$URL->withReturn('error1')}");
        exit;
    }

    /** @var PasswordPolicy */
    $passwordPolicies = $container->get(PasswordPolicy::class);

    // Check strength of password
    if (!$passwordPolicies->validate($password)) {
        header("Location: {$URL->withReturn('error6')}");
        exit;
    }

    // Check uniqueness of username (and/or email, if required)
    $uniqueEmailAddress = $settingGateway->getSettingByScope('User Admin', 'uniqueEmailAddress');
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
        header("Location: {$URL->withReturn('error7')}");
        exit;
    }

    // Check publicRegistrationMinimumAge
    $publicRegistrationMinimumAge = $settingGateway->getSettingByScope('User Admin', 'publicRegistrationMinimumAge');

    if (!empty($publicRegistrationMinimumAge) > 0 and $publicRegistrationMinimumAge > (new DateTime('@'.Format::timestamp($dob)))->diff(new DateTime())->y) {
        header("Location: {$URL->withReturn('error5')}");
        exit;
    }

    //Write to database
    $data = array('surname' => $surname, 'firstName' => $firstName, 'preferredName' => $preferredName, 'officialName' => $officialName, 'gender' => $gender, 'dob' => $dob, 'email' => $email, 'emailAlternate' => $emailAlternate, 'username' => $username, 'passwordStrong' => $passwordStrong, 'passwordStrongSalt' => $salt, 'status' => $status, 'gibbonRoleIDPrimary' => $gibbonRoleIDPrimary, 'gibbonRoleIDAll' => $gibbonRoleIDAll, 'fields' => $fields);
    $sql = "INSERT INTO gibbonPerson SET surname=:surname, firstName=:firstName, preferredName=:preferredName, officialName=:officialName, gender=:gender, dob=:dob, email=:email, emailAlternate=:emailAlternate, username=:username, passwordStrong=:passwordStrong, passwordStrongSalt=:passwordStrongSalt, status=:status, gibbonRoleIDPrimary=:gibbonRoleIDPrimary, gibbonRoleIDAll=:gibbonRoleIDAll, fields=:fields";

    $gibbonPersonID = $pdo->insert($sql, $data);

    if (empty($gibbonPersonID)) {
        header("Location: {$URL->withReturn('error2')}");
        exit;
    }

    // Create the status log
    $container->get(UserStatusLogGateway::class)->insert(['gibbonPersonID' => $gibbonPersonID, 'statusOld' => $status, 'statusNew' => $status, 'reason' => __('Public Registration')]);

    if ($status == 'Pending Approval') {
        // Raise a new notification event
        $event = new NotificationEvent('User Admin', 'New Public Registration');

        $event->addRecipient($session->get('organisationAdmissions'));
        $event->setNotificationText(sprintf(__('A new public registration, for %1$s, is pending approval.'), Format::name('', $preferredName, $surname, 'Student')));
        $event->setActionLink("/index.php?q=/modules/User Admin/user_manage_edit.php&gibbonPersonID=$gibbonPersonID&search=");

        $event->sendNotifications($pdo, $session);

        header("Location: {$URL->withReturn('success1')}");
    } else {
        // Raise a new notification event
        $event = new NotificationEvent('User Admin', 'New Public Registration');

        $event->addRecipient($session->get('organisationAdmissions'));
        $event->setNotificationText(sprintf(__('A new public registration, for %1$s, is now live.'), Format::name('', $preferredName, $surname, 'Student')));
        $event->setActionLink("/index.php?q=/modules/User Admin/user_manage_edit.php&gibbonPersonID=$gibbonPersonID&search=");

        $event->sendNotifications($pdo, $session);

        header("Location: {$URL->withReturn('success0')}");
    }
}
