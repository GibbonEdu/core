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
if ($session->exists('gibbonAcademicYearID') == false or $session->exists('gibbonSchoolYearName') == false) {
    setCurrentSchoolYear($guid, $connection2);
}

//Check password address is not blank
$password = $_POST['password'] ?? '';
$passwordNew = $_POST['passwordNew'] ?? '';
$passwordConfirm = $_POST['passwordConfirm'] ?? '';
$forceReset = $session->get('passwordForceReset');

if ($forceReset != 'Y') {
    $forceReset = 'N';
    $URLSuccess = $session->get('absoluteURL')."/index.php?q=preferences.php&forceReset=N";
} else {
    $URLSuccess = $session->get('absoluteURL')."/index.php?forceReset=Y";
}
$URL = $session->get('absoluteURL')."/index.php?q=preferences.php&forceReset=".$forceReset;

//Check passwords are not blank
if ($password == '' or $passwordNew == '' or $passwordConfirm == '') {
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
                //Check current password
                if (hash('sha256', $session->get('passwordStrongSalt').$password) != $session->get('passwordStrong')) {
                    $URL .= '&return=error3';
                    header("Location: {$URL}");
                } else {
                    //If answer insert fails...
                    $salt = getSalt();
                    $passwordStrong = hash('sha256', $salt.$passwordNew);
                    try {
                        $data = array('passwordStrong' => $passwordStrong, 'salt' => $salt, 'username' => $session->get('username'));
                        $sql = "UPDATE gibbonPerson SET password='', passwordStrong=:passwordStrong, passwordStrongSalt=:salt WHERE (username=:username)";
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $URL .= '&return=error2';
                        header("Location: {$URL}");
                        exit();
                    }

                    //Check for forceReset and take action
                    if ($forceReset == 'Y') {
                        //Update passwordForceReset field
                        try {
                            $data = array('username' => $session->get('username'));
                            $sql = "UPDATE gibbonPerson SET passwordForceReset='N' WHERE username=:username";
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        } catch (PDOException $e) {
                            $URL .= '&return=errora';
                            header("Location: {$URL}");
                            exit();
                        }
                        $session->set('passwordForceReset', 'N');
                        $session->set('passwordStrongSalt', $salt);
                        $session->set('passwordStrong', $passwordStrong);
                        $session->set('pageLoads', null);
                        $URLSuccess .= '&return=successa';
                        header("Location: {$URLSuccess}");
                        exit() ;
                    }

                    $session->set('passwordStrongSalt', $salt);
                    $session->set('passwordStrong', $passwordStrong);
                    $session->set('pageLoads', null);
                    $URLSuccess .= '&return=success0';
                    header("Location: {$URLSuccess}");
                }
            }
        }
    }
}
