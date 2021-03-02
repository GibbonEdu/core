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

include './gibbon.php';

//Check to see if academic year id variables are set, if not set them
if ($gibbon->session->exists('gibbonAcademicYearID') == false or $gibbon->session->exists('gibbonSchoolYearName') == false) {
    setCurrentSchoolYear($guid, $connection2);
}

// Sanitize the whole $_POST array
$validator = new \Gibbon\Data\Validator();
$_POST = $validator->sanitize($_POST);

$calendarFeedPersonal = $_POST['calendarFeedPersonal'] ?? '';
$personalBackground = $_POST['personalBackground'] ?? '';
$gibbonThemeIDPersonal = $_POST['gibbonThemeIDPersonal'] ?? null;
$gibboni18nIDPersonal = $_POST['gibboni18nIDPersonal'] ?? null;
$receiveNotificationEmails = $_POST['receiveNotificationEmails'] ?? 'N';

$URL = $gibbon->session->get('absoluteURL').'/index.php?q=preferences.php';

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
    $URL .= '&return=error1';
    header("Location: {$URL}");
    exit();
}

try {
    $data = array('calendarFeedPersonal' => $calendarFeedPersonal, 'personalBackground' => $personalBackground, 'gibbonThemeIDPersonal' => $gibbonThemeIDPersonal, 'gibboni18nIDPersonal' => $gibboni18nIDPersonal, 'receiveNotificationEmails' => $receiveNotificationEmails, 'username' => $gibbon->session->get('username'));
    $sql = 'UPDATE gibbonPerson SET calendarFeedPersonal=:calendarFeedPersonal, personalBackground=:personalBackground, gibbonThemeIDPersonal=:gibbonThemeIDPersonal, gibboni18nIDPersonal=:gibboni18nIDPersonal, receiveNotificationEmails=:receiveNotificationEmails WHERE (username=:username)';
    $result = $connection2->prepare($sql);
    $result->execute($data);
} catch (PDOException $e) {
    $URL .= '&return=error2';
    header("Location: {$URL}");
    exit();
}

//Update personal preferences in session
$gibbon->session->set('calendarFeedPersonal', $calendarFeedPersonal);
$gibbon->session->set('personalBackground', $personalBackground);
$gibbon->session->set('gibbonThemeIDPersonal', $gibbonThemeIDPersonal);
$gibbon->session->set('gibboni18nIDPersonal', $gibboni18nIDPersonal);
$gibbon->session->set('receiveNotificationEmails', $receiveNotificationEmails);

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

$gibbon->session->set('pageLoads', null);
$URL .= '&return=success0';
header("Location: {$URL}");
