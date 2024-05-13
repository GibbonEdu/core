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

use Gibbon\Data\Validator;
use Gibbon\Http\Url;

include './gibbon.php';

// Sanitize the whole $_POST array
$validator = $container->get(Validator::class);
$_POST = $validator->sanitize($_POST, ['personalBackground' => 'URL']);

$calendarFeedPersonal = $_POST['calendarFeedPersonal'] ?? '';
$personalBackground = $_POST['personalBackground'] ?? '';
$gibbonThemeIDPersonal = !empty($_POST['gibbonThemeIDPersonal']) ? $_POST['gibbonThemeIDPersonal'] : null;
$gibboni18nIDPersonal = !empty($_POST['gibboni18nIDPersonal']) ? $_POST['gibboni18nIDPersonal'] : null;
$receiveNotificationEmails = $_POST['receiveNotificationEmails'] ?? 'N';

//TODO: Handle requiring MFA token one last time if disabling MFA
$mfaEnable = $_POST['mfaEnable'] ?? 'N';
if ($mfaEnable == 'Y') {
    $mfaSecret = $_POST['mfaSecret'] ?? null;
} else {
    $mfaSecret = null;
}

$mfaCode = $_POST['mfaCode'] ?? null;

$URL = Url::fromRoute('preferences');

$validated = true;

// Validate the personal background URL
if (!empty($personalBackground) && filter_var($personalBackground, FILTER_VALIDATE_URL) === false) {
    $validated = false;
}

// Validate the personal calendar feed
if (!empty($calendarFeedPersonal) && filter_var($calendarFeedPersonal, FILTER_VALIDATE_EMAIL) === false) {
    $validated = false;
}


if (!$validated) {
    header("Location: {$URL->withReturn('error1')}");
    exit();
}

if ($mfaEnable == 'Y') {
    $tfa = new RobThree\Auth\TwoFactorAuth('Gibbon'); //TODO: change the name to be based on the actual value of the school's gibbon name or similar...
    if ($tfa->verifyCode($mfaSecret, $mfaCode) !== true){
        header("Location: {$URL->withReturn('error8')}");
        exit();
    }
}

try {
    $data = array('calendarFeedPersonal' => $calendarFeedPersonal, 'personalBackground' => $personalBackground, 'gibbonThemeIDPersonal' => $gibbonThemeIDPersonal, 'gibboni18nIDPersonal' => $gibboni18nIDPersonal, 'receiveNotificationEmails' => $receiveNotificationEmails, 'mfaSecret' => $mfaSecret, 'username' => $session->get('username'));
    $sql = 'UPDATE gibbonPerson SET calendarFeedPersonal=:calendarFeedPersonal, personalBackground=:personalBackground, gibbonThemeIDPersonal=:gibbonThemeIDPersonal, gibboni18nIDPersonal=:gibboni18nIDPersonal, receiveNotificationEmails=:receiveNotificationEmails, mfaSecret=:mfaSecret WHERE (username=:username)';
    $result = $connection2->prepare($sql);
    $result->execute($data);
} catch (PDOException $e) {
    header("Location: {$URL->withReturn('error2')}");
    exit();
}

//Update personal preferences in session
$session->set('calendarFeedPersonal', $calendarFeedPersonal);
$session->set('personalBackground', $personalBackground);
$session->set('gibbonThemeIDPersonal', $gibbonThemeIDPersonal);
$session->set('gibboni18nIDPersonal', $gibboni18nIDPersonal);
$session->set('receiveNotificationEmails', $receiveNotificationEmails);

//Update language settings in session (to personal preference if set, or system default if not)
if (!empty($gibboni18nIDPersonal)) {
    $data = array('gibboni18nID' => $gibboni18nIDPersonal);
    $sql = 'SELECT * FROM gibboni18n WHERE gibboni18nID=:gibboni18nID';
    $result = $connection2->prepare($sql);
    $result->execute($data);
    if ($result->rowCount() == 1) {
        $row = $result->fetch();
        setLanguageSession($guid, $row);
    }
} else {
    $data = array();
    $sql = "SELECT * FROM gibboni18n WHERE systemDefault='Y'";
    $result = $connection2->prepare($sql);
    $result->execute($data);
    if ($result->rowCount() == 1) {
        $row = $result->fetch();
        setLanguageSession($guid, $row);
    }
}

$session->set('pageLoads', null);
header("Location: {$URL->withReturn('success0')}");
