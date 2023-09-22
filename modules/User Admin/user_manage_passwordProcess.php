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
use Gibbon\Data\Validator;
use Gibbon\Domain\System\LogGateway;
use Gibbon\Services\Format;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$gibbonPersonID = $_GET['gibbonPersonID'] ?? '';
$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address'])."/user_manage_password.php&gibbonPersonID=$gibbonPersonID&search=".$_GET['search'];

if (isActionAccessible($guid, $connection2, '/modules/User Admin/user_manage_password.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    //Check if person specified
    if ($gibbonPersonID == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        try {
            $data = array('gibbonPersonID' => $gibbonPersonID);
            $sql = 'SELECT * FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
            exit();
        }

        if ($result->rowCount() != 1) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
        } else {
            $person = $result->fetch();
            $passwordNew = $_POST['passwordNew'] ?? '';
            $passwordConfirm = $_POST['passwordConfirm'] ?? '';
            $passwordForceReset = $_POST['passwordForceReset'] ?? '';

            //Validate Inputs
            if ($passwordNew == '' or $passwordConfirm == '') {
                $URL .= '&return=error3';
                header("Location: {$URL}");
            } else {
                /** @var PasswordPolicy */
                $passwordPolicies = $container->get(PasswordPolicy::class);

                //Check strength of password
                if (!$passwordPolicies->validate($passwordNew)) {
                    $URL .= '&return=error6';
                    header("Location: {$URL}");
                } else {
                    //Check new passwords match
                    if ($passwordNew != $passwordConfirm) {
                        $URL .= '&return=error5';
                        header("Location: {$URL}");
                    } else {
                        $salt = getSalt();
                        $passwordStrong = hash('sha256', $salt.$passwordNew);

                        //Write to database
                        try {
                            $data = array('passwordStrong' => $passwordStrong, 'passwordStrongSalt' => $salt, 'passwordForceReset' => $passwordForceReset, 'gibbonPersonID' => $gibbonPersonID);
                            $sql = "UPDATE gibbonPerson SET passwordStrong=:passwordStrong, passwordStrongSalt=:passwordStrongSalt, passwordForceReset=:passwordForceReset, failCount=0 WHERE gibbonPersonID=:gibbonPersonID";
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        } catch (PDOException $e) {
                            $URL .= '&return=error2';
                            header("Location: {$URL}");
                            exit();
                        }

                        // Log this password change
                        $details = [
                            'gibbonPersonID' => $gibbonPersonID,
                            'name' => Format::name('', $person['preferredName'], $person['surname'], 'Staff', false, true),
                            'changedByID' => $session->get('gibbonPersonID'),
                            'changedBy' => Format::name('', $session->get('preferredName'), $session->get('surname'), 'Staff', false, true),
                        ];
                        $container->get(LogGateway::class)->addLog($session->get('gibbonSchoolYearID'), $gibbonModuleID, $session->get('gibbonPersonID'), 'User - Password Manually Changed', $details, $_SERVER['REMOTE_ADDR']);

                        $URL .= '&return=success0';
                        header("Location: {$URL}");
                    }
                }
            }
        }
    }
}
