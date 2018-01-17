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

//Check to see if academic year id variables are set, if not set them
if (isset($_SESSION[$guid]['gibbonAcademicYearID']) == false or isset($_SESSION[$guid]['gibbonSchoolYearName']) == false) {
    setCurrentSchoolYear($guid, $connection2);
}

$calendarFeedPersonal = isset($_POST['calendarFeedPersonal'])? $_POST['calendarFeedPersonal'] : '';
$personalBackground = isset($_POST['personalBackground'])? $_POST['personalBackground'] : '';
$gibbonThemeIDPersonal = !empty($_POST['gibbonThemeIDPersonal'])? $_POST['gibbonThemeIDPersonal'] : null;
$gibboni18nIDPersonal = !empty($_POST['gibboni18nIDPersonal'])? $_POST['gibboni18nIDPersonal'] : null;
$receiveNotificationEmails = isset($_POST['receiveNotificationEmails'])? $_POST['receiveNotificationEmails'] : 'N';

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=preferences.php';

// Validate the personal background URL
if (!empty($personalBackground) && filter_var($personalBackground, FILTER_VALIDATE_URL) === false) {
    $URL .= '&return=error1';
    header("Location: {$URL}");
    exit();
}

try {
    $data = array('calendarFeedPersonal' => $calendarFeedPersonal, 'personalBackground' => $personalBackground, 'gibbonThemeIDPersonal' => $gibbonThemeIDPersonal, 'gibboni18nIDPersonal' => $gibboni18nIDPersonal, 'receiveNotificationEmails' => $receiveNotificationEmails, 'username' => $_SESSION[$guid]['username']);
    $sql = 'UPDATE gibbonPerson SET calendarFeedPersonal=:calendarFeedPersonal, personalBackground=:personalBackground, gibbonThemeIDPersonal=:gibbonThemeIDPersonal, gibboni18nIDPersonal=:gibboni18nIDPersonal, receiveNotificationEmails=:receiveNotificationEmails WHERE (username=:username)';
    $result = $connection2->prepare($sql);
    $result->execute($data);
} catch (PDOException $e) {
    $URL .= '&return=error2';
    header("Location: {$URL}");
    exit();
}

$smartWorkflowHelp = isset($_POST['smartWorkflowHelp'])? $_POST['smartWorkflowHelp'] : null;
if (!empty($smartWorkflowHelp)) {
    try {
        $data = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'], 'smartWorkflowHelp' => $smartWorkflowHelp);
        $sql = "UPDATE gibbonStaff SET smartWorkflowHelp=:smartWorkflowHelp WHERE gibbonPersonID=:gibbonPersonID";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit();
    }
}

//Update personal preferences in session
$_SESSION[$guid]['calendarFeedPersonal'] = $calendarFeedPersonal;
$_SESSION[$guid]['personalBackground'] = $personalBackground;
$_SESSION[$guid]['gibbonThemeIDPersonal'] = $gibbonThemeIDPersonal;
$_SESSION[$guid]['gibboni18nIDPersonal'] = $gibboni18nIDPersonal;
$_SESSION[$guid]['receiveNotificationEmails'] = $receiveNotificationEmails;

//Update language settings in session (to personal preference if set, or system default if not)
if (!is_null($gibboni18nIDPersonal)) {
    try {
        $data = array('gibboni18nID' => $gibboni18nIDPersonal);
        $sql = 'SELECT * FROM gibboni18n WHERE gibboni18nID=:gibboni18nID';
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
    }
    if ($result->rowCount() == 1) {
        $row = $result->fetch();
        setLanguageSession($guid, $row);
    }
} else {
    try {
        $data = array();
        $sql = "SELECT * FROM gibboni18n WHERE systemDefault='Y'";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
    }
    if ($result->rowCount() == 1) {
        $row = $result->fetch();
        setLanguageSession($guid, $row);
    }
}

$_SESSION[$guid]['pageLoads'] = null;
$URL .= '&return=success0';
header("Location: {$URL}");
