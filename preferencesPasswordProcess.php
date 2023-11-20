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

use Gibbon\Data\PasswordPolicy;
use Gibbon\Http\Url;
use Gibbon\Domain\User\UserGateway;

include './gibbon.php';

//Check password address is not blank
$password = $_POST['password'] ?? '';
$passwordNew = $_POST['passwordNew'] ?? '';
$passwordConfirm = $_POST['passwordConfirm'] ?? '';
$forceReset = $session->get('passwordForceReset');

$mfaEnable = $_POST['mfaEnable'] ?? 'N';
$mfaSecret = $_POST['mfaSecret'] ?? null;
$mfaCode = $_POST['mfaCode'] ?? null;

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
    //Check the mfaCode is correct
    if ($mfaEnable == 'Y') {
        $tfa = new RobThree\Auth\TwoFactorAuth('Gibbon'); //TODO: change the name to be based on the actual value of the school's gibbon name or similar...
        if ($tfa->verifyCode($mfaSecret, $mfaCode) !== true){
            header("Location: {$URL->withReturn('error8')}");
            exit();
        }
    }
    //Check that new password is not same as old password
    if ($password == $passwordNew) {
        header("Location: {$URL->withReturn('error7')}");
    } else {
        /** @var PasswordPolicy */
        $passwordPolicies = $container->get(PasswordPolicy::class);

        //Check strength of password
        if (!$passwordPolicies->validate($passwordNew)) {
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
