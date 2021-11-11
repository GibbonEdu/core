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

use Gibbon\Http\Url;
use Gibbon\Domain\User\UserGateway;

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
    $URLSuccess = Url::fromRoute('preferences')->withQueryParam('forceReset', 'N');
} else {
    $URLSuccess = Url::fromRoute()->withQueryParam('forceReset', 'Y');
}
$URL = Url::fromRoute('preferences')->withQueryParam('forceReset', $forceReset);

//Check passwords are not blank
if ($password == '' or $passwordNew == '' or $passwordConfirm == '') {
    header("Location: {$URL->withReturn('error1')}");
} else {
    //Check that new password is not same as old password
    if ($password == $passwordNew) {
        header("Location: {$URL->withReturn('error7')}");
    } else {
        //Check strength of password
        $passwordMatch = doesPasswordMatchPolicy($connection2, $passwordNew);

        if ($passwordMatch == false) {
            header("Location: {$URL->withReturn('error6')}");
        } else {
            //Check new passwords match
            if ($passwordNew != $passwordConfirm) {
                header("Location: {$URL->withReturn('error4')}");
            } else {
                $user = $container->get(UserGateway::class)->getByID($session->get('gibbonPersonID'), ['passwordStrong', 'passwordStrongSalt']);
                //Check current password
                if (hash('sha256', $user['passwordStrongSalt'].$password) != $user['passwordStrong']) {
                    header("Location: {$URL->withReturn('error3')}");
                } else {
                    //If answer insert fails...
                    $salt = getSalt();
                    $passwordStrong = hash('sha256', $salt.$passwordNew);
                    try {
                        $data = array('passwordStrong' => $passwordStrong, 'salt' => $salt, 'username' => $session->get('username'));
                        $sql = "UPDATE gibbonPerson SET passwordStrong=:passwordStrong, passwordStrongSalt=:salt WHERE (username=:username)";
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        header("Location: {$URL->withReturn('error2')}");
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
                            header("Location: {$URL->withReturn('errora')}");
                            exit();
                        }
                        $session->set('passwordForceReset', 'N');
                        $session->set('passwordStrongSalt', $salt);
                        $session->set('passwordStrong', $passwordStrong);
                        $session->set('pageLoads', null);
                        header("Location: {$URL->withReturn('successa')}");
                        exit() ;
                    }

                    $session->set('passwordStrongSalt', $salt);
                    $session->set('passwordStrong', $passwordStrong);
                    $session->set('pageLoads', null);
                    header("Location: {$URL->withReturn('success0')}");
                }
            }
        }
    }
}
