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

include 'functions.php';
include 'config.php';

@session_start();

//New PDO DB connection
$pdo = new Gibbon\sqlConnection();
$connection2 = $pdo->getConnection();

setCurrentSchoolYear($guid, $connection2);

//The current/actual school year info, just in case we are working in a different year
$_SESSION[$guid]['gibbonSchoolYearIDCurrent'] = $_SESSION[$guid]['gibbonSchoolYearID'];
$_SESSION[$guid]['gibbonSchoolYearNameCurrent'] = $_SESSION[$guid]['gibbonSchoolYearName'];
$_SESSION[$guid]['gibbonSchoolYearSequenceNumberCurrent'] = $_SESSION[$guid]['gibbonSchoolYearSequenceNumber'];

$_SESSION[$guid]['pageLoads'] = null;

$URL = './index.php';

// Sanitize the whole $_POST array
$validator = new \Gibbon\Data\Validator();
$_POST = $validator->sanitize($_POST);

//Get and store POST variables from calling page
$username = $_POST['username'];
$password = $_POST['password'];

if (($username == '') or ($password == '')) {
    $URL .= '?loginReturn=fail0b';
    header("Location: {$URL}");
    exit;
}
//VALIDATE LOGIN INFORMATION
else {
    try {
        $data = array('username' => $username);
        $sql = "SELECT gibbonPerson.*, futureYearsLogin, pastYearsLogin, canLogin FROM gibbonPerson LEFT JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) WHERE ((username=:username OR (LOCATE('@', :username)>0 AND email=:username) ) AND (status='Full'))";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
    }

    //Test to see if username exists and is unique
    if ($result->rowCount() != 1) {
        setLog($connection2, $_SESSION[$guid]['gibbonSchoolYearIDCurrent'], null, null, 'Login - Failed', array('username' => $username, 'reason' => 'Username does not exist'), $_SERVER['REMOTE_ADDR']);
        $URL .= '?loginReturn=fail1';
        header("Location: {$URL}");
        exit;
    } else {
        $row = $result->fetch();

        // Insufficient privledges to login
        if ($row['canLogin'] != 'Y') {
            $URL .= '?loginReturn=fail2';
            header("Location: {$URL}");
            exit;
        }

        // Set the username explicity, to handle logging in with email
        $username = $row['username'];

        //Check fail count, reject & alert if 3rd time
        if ($row['failCount'] >= 3) {
            try {
                $dataSecure = array('lastFailIPAddress' => $_SERVER['REMOTE_ADDR'], 'lastFailTimestamp' => date('Y-m-d H:i:s'), 'failCount' => ($row['failCount'] + 1), 'username' => $username);
                $sqlSecure = 'UPDATE gibbonPerson SET lastFailIPAddress=:lastFailIPAddress, lastFailTimestamp=:lastFailTimestamp, failCount=:failCount WHERE (username=:username)';
                $resultSecure = $connection2->prepare($sqlSecure);
                $resultSecure->execute($dataSecure);
            } catch (PDOException $e) {
            }

            if ($row['failCount'] == 3) {
                // Raise a new notification event
                $event = new NotificationEvent('User Admin', 'Login - Failed');

                $event->addRecipient($_SESSION[$guid]['organisationAdministrator']);
                $event->setNotificationText(sprintf(__('Someone failed to login to account "%1$s" 3 times in a row.'), $username));
                $event->setActionLink('/index.php?q=/modules/User Admin/user_manage.php&search='.$username);

                $event->sendNotifications($pdo, $gibbon->session);
            }

            setLog($connection2, $_SESSION[$guid]['gibbonSchoolYearIDCurrent'], null, $row['gibbonPersonID'], 'Login - Failed', array('username' => $username, 'reason' => 'Too many failed logins'), $_SERVER['REMOTE_ADDR']);
            $URL .= '?loginReturn=fail6';
            header("Location: {$URL}");
            exit;
        } else {
            $passwordTest = false;
            //If strong password exists
            $salt = $row['passwordStrongSalt'];
            $passwordStrong = $row['passwordStrong'];
            if ($passwordStrong != '' and $salt != '') {
                if (hash('sha256', $row['passwordStrongSalt'].$password) == $row['passwordStrong']) {
                    $passwordTest = true;
                }
            }
            //If only weak password exists
            elseif ($row['password'] != '') {
                if ($row['password'] == md5($password)) {
                    $passwordTest = true;

                    //Migrate to strong password
                    $salt = getSalt();
                    $passwordStrong = hash('sha256', $salt.$password);

                    try {
                        $dataSecure = array('passwordStrong' => $passwordStrong, 'passwordStrongSalt' => $salt, 'username' => $username);
                        $sqlSecure = "UPDATE gibbonPerson SET password='', passwordStrong=:passwordStrong, passwordStrongSalt=:passwordStrongSalt WHERE (username=:username)";
                        $resultSecure = $connection2->prepare($sqlSecure);
                        $resultSecure->execute($dataSecure);
                    } catch (PDOException $e) {
                        $passwordTest = false;
                    }
                }
            }

            //Test to see if password matches username
            if ($passwordTest != true) {
                //FAIL PASSWORD
                try {
                    $dataSecure = array('lastFailIPAddress' => $_SERVER['REMOTE_ADDR'], 'lastFailTimestamp' => date('Y-m-d H:i:s'), 'failCount' => ($row['failCount'] + 1), 'username' => $username);
                    $sqlSecure = 'UPDATE gibbonPerson SET lastFailIPAddress=:lastFailIPAddress, lastFailTimestamp=:lastFailTimestamp, failCount=:failCount WHERE (username=:username)';
                    $resultSecure = $connection2->prepare($sqlSecure);
                    $resultSecure->execute($dataSecure);
                } catch (PDOException $e) {
                    $passwordTest = false;
                }

                setLog($connection2, $_SESSION[$guid]['gibbonSchoolYearIDCurrent'], null, $row['gibbonPersonID'], 'Login - Failed', array('username' => $username, 'reason' => 'Incorrect password'), $_SERVER['REMOTE_ADDR']);
                $URL .= '?loginReturn=fail1';
                header("Location: {$URL}");
                exit;
            } else {
                if ($row['gibbonRoleIDPrimary'] == '' or count(getRoleList($row['gibbonRoleIDAll'], $connection2)) == 0) {
                    //FAILED TO SET ROLES
                    setLog($connection2, $_SESSION[$guid]['gibbonSchoolYearIDCurrent'], null, $row['gibbonPersonID'], 'Login - Failed', array('username' => $username, 'reason' => 'Failed to set role(s)'), $_SERVER['REMOTE_ADDR']);
                    $URL .= '?loginReturn=fail2';
                    header("Location: {$URL}");
                    exit;
                } else {
                    //Allow for non-current school years to be specified
                    if ($_POST['gibbonSchoolYearID'] != $_SESSION[$guid]['gibbonSchoolYearID']) {
                        if ($row['futureYearsLogin'] != 'Y' and $row['pastYearsLogin'] != 'Y') { //NOT ALLOWED DUE TO CONTROLS ON ROLE, KICK OUT!
                            setLog($connection2, $_SESSION[$guid]['gibbonSchoolYearIDCurrent'], null, $row['gibbonPersonID'], 'Login - Failed', array('username' => $username, 'reason' => 'Not permitted to access non-current school year'), $_SERVER['REMOTE_ADDR']);
                            $URL .= '?loginReturn=fail9';
                            header("Location: {$URL}");
                            exit();
                        } else {
                            //Get details on requested school year
                            try {
                                $dataYear = array('gibbonSchoolYearID' => $_POST['gibbonSchoolYearID']);
                                $sqlYear = 'SELECT * FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID';
                                $resultYear = $connection2->prepare($sqlYear);
                                $resultYear->execute($dataYear);
                            } catch (PDOException $e) {
                            }

                            //Check number of rows returned.
                            //If it is not 1, show error
                            if (!($resultYear->rowCount() == 1)) {
                                die('Configuration Error: there is a problem accessing the current Academic Year from the database.');
                            }
                            //Else get year details
                            else {
                                $rowYear = $resultYear->fetch();
                                if ($row['futureYearsLogin'] != 'Y' and $_SESSION[$guid]['gibbonSchoolYearSequenceNumber'] < $rowYear['sequenceNumber']) { //POSSIBLY NOT ALLOWED DUE TO CONTROLS ON ROLE, CHECK YEAR
                                    setLog($connection2, $_SESSION[$guid]['gibbonSchoolYearIDCurrent'], null, $row['gibbonPersonID'], 'Login - Failed', array('username' => $username, 'reason' => 'Not permitted to access non-current school year'), $_SERVER['REMOTE_ADDR']);
                                    $URL .= '?loginReturn=fail9';
                                    header("Location: {$URL}");
                                    exit();
                                } elseif ($row['pastYearsLogin'] != 'Y' and $_SESSION[$guid]['gibbonSchoolYearSequenceNumber'] > $rowYear['sequenceNumber']) { //POSSIBLY NOT ALLOWED DUE TO CONTROLS ON ROLE, CHECK YEAR
                                    setLog($connection2, $_SESSION[$guid]['gibbonSchoolYearIDCurrent'], null, $row['gibbonPersonID'], 'Login - Failed', array('username' => $username, 'reason' => 'Not permitted to access non-current school year'), $_SERVER['REMOTE_ADDR']);
                                    $URL .= '?loginReturn=fail9';
                                    header("Location: {$URL}");
                                    exit();
                                } else { //ALLOWED
                                    $_SESSION[$guid]['gibbonSchoolYearID'] = $rowYear['gibbonSchoolYearID'];
                                    $_SESSION[$guid]['gibbonSchoolYearName'] = $rowYear['name'];
                                    $_SESSION[$guid]['gibbonSchoolYearSequenceNumber'] = $rowYear['sequenceNumber'];
                                }
                            }
                        }
                    }

                    //USER EXISTS, SET SESSION VARIABLES
                    $gibbon->session->createUserSession($username, $row);

                    // Set these from local values
                    $gibbon->session->set('passwordStrong', $passwordStrong);
                    $gibbon->session->set('passwordStrongSalt', $salt);
                    $gibbon->session->set('googleAPIAccessToken', null);

                    //Allow for non-system default language to be specified from login form
                    if (@$_POST['gibboni18nID'] != $_SESSION[$guid]['i18n']['gibboni18nID']) {
                        try {
                            $dataLanguage = array('gibboni18nID' => $_POST['gibboni18nID']);
                            $sqlLanguage = 'SELECT * FROM gibboni18n WHERE gibboni18nID=:gibboni18nID';
                            $resultLanguage = $connection2->prepare($sqlLanguage);
                            $resultLanguage->execute($dataLanguage);
                        } catch (PDOException $e) {
                        }
                        if ($resultLanguage->rowCount() == 1) {
                            $rowLanguage = $resultLanguage->fetch();
                            setLanguageSession($guid, $rowLanguage, false);
                        }
                    } else {
                        //If no language specified, get user preference if it exists
                        if (!is_null($_SESSION[$guid]['gibboni18nIDPersonal'])) {
                            try {
                                $dataLanguage = array('gibboni18nID' => $_SESSION[$guid]['gibboni18nIDPersonal']);
                                $sqlLanguage = "SELECT * FROM gibboni18n WHERE active='Y' AND gibboni18nID=:gibboni18nID";
                                $resultLanguage = $connection2->prepare($sqlLanguage);
                                $resultLanguage->execute($dataLanguage);
                            } catch (PDOException $e) {
                            }
                            if ($resultLanguage->rowCount() == 1) {
                                $rowLanguage = $resultLanguage->fetch();
                                setLanguageSession($guid, $rowLanguage);
                            }
                        }
                    }

                    //Make best effort to set IP address and other details, but no need to error check etc.
                    try {
                        $data = array('lastIPAddress' => $_SERVER['REMOTE_ADDR'], 'lastTimestamp' => date('Y-m-d H:i:s'), 'failCount' => 0, 'username' => $username);
                        $sql = 'UPDATE gibbonPerson SET lastIPAddress=:lastIPAddress, lastTimestamp=:lastTimestamp, failCount=:failCount WHERE username=:username';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                    }

                    if (isset($_GET['q'])) {
                        if ($_GET['q'] == '/publicRegistration.php') {
                            $URL = './index.php';
                        } else {
                            $URL = './index.php?q='.$_GET['q'];
                        }
                    } else {
                        $URL = './index.php';
                    }
                    setLog($connection2, $_SESSION[$guid]['gibbonSchoolYearIDCurrent'], null, $row['gibbonPersonID'], 'Login - Success', array('username' => $username), $_SERVER['REMOTE_ADDR']);
                    header("Location: {$URL}");
                    exit;
                }
            }
        }
    }
}
