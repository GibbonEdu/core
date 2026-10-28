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
use Gibbon\Data\PasswordPolicy;
use Gibbon\Contracts\Comms\Mailer;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\System\LogGateway;
use Gibbon\Domain\User\PersonResetGateway;
use Gibbon\Domain\User\RoleGateway;
use Gibbon\Domain\User\UserGateway;

require_once __DIR__ . '/gibbon.php';

// Load site's password policy
/** @var PasswordPolicy */
$passwordPolicy = $container->get(PasswordPolicy::class);

// Create password
$password = $passwordPolicy->generate();

// Sanitize the $_GET and $_POST arrays
$validator = $container->get(Validator::class);
$_GET = $validator->sanitize($_GET);
$_POST = $validator->sanitize($_POST);

$step = $_GET['step'] ?? '';
$URL = Url::fromRoute('passwordReset');

if (count($_POST) <= 1) {
    header("Location: {$URL->withReturn('error0')}");
    exit;
}

$input = $step == 1
    ? filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL)
    : trim($_GET['input'] ?? '');
$URL = $URL->withQueryParam('email', $input);

if ($input == '' or ($step != 1 and $step != 2)) {
    header("Location: {$URL->withReturn('error0')}");
    exit;
}

$userGateway = $container->get(UserGateway::class);
$roleGateway = $container->get(RoleGateway::class);
$resetGateway = $container->get(PersonResetGateway::class);
$logGateway = $container->get(LogGateway::class);

if ($step == 1) {
    try {
        $matches = $userGateway->selectLoginDetailsByUsername($input)->fetchAll();
    } catch (PDOException $e) {
        header("Location: {$URL->withReturn('error2')}");
        exit();
    }

    $person = count($matches) == 1
        ? $userGateway->getByID($matches[0]['gibbonPersonID'], ['gibbonPersonID', 'email', 'username', 'preferredName', 'surname', 'canLogin', 'gibbonRoleIDPrimary', 'status'])
        : [];
    $role = !empty($person['gibbonRoleIDPrimary']) ? $roleGateway->getRoleByID($person['gibbonRoleIDPrimary']) : [];

    $canSend = count($matches) == 1
        && !empty($person['email'])
        && ($person['status'] ?? '') == 'Full'
        && ($person['canLogin'] ?? '') == 'Y'
        && (empty($role['canLoginRole']) || $role['canLoginRole'] == 'Y');

    if (!$canSend) {
        header("Location: {$URL->withReturn('success0')}");
        exit;
    }

    $gibbonPersonID = $person['gibbonPersonID'];
    $email = $person['email'];
    $username = $person['username'];
    $preferredName = $person['preferredName'];
    $surname = $person['surname'];

    $recentReset = false;
    foreach ($resetGateway->selectBy(['gibbonPersonID' => $gibbonPersonID]) as $reset) {
        if (!empty($reset['timestamp']) && strtotime($reset['timestamp']) >= strtotime('-5 minutes')) {
            $recentReset = true;
            break;
        }
    }
    if ($recentReset) {
        header("Location: {$URL->withReturn('success0')}");
        exit;
    }

    $ip = substr($_SERVER['REMOTE_ADDR'] ?? '', 0, 15);
    $addressCount = 0;

    if ($ip !== '') {
        $criteria = (new QueryCriteria())
            ->filterBy('ip', $ip)
            ->filterBy('title', 'User - Forgot Password Request Initiated')
            ->filterBy('startDate', date('Y-m-d H:i:s', strtotime('-60 minutes')))
            ->pageSize(1);
        $addressCount = $logGateway->queryLogs($criteria, $session->get('gibbonSchoolYearID'))->getResultCount();
    }

    if ($addressCount >= 100) {
        header("Location: {$URL->withReturn('success0')}");
        exit;
    }

            // Use password policy to generate random string
            $randStrGenerator = new PasswordPolicy(true, true, false, 40);

            //Generate key
            $key = $randStrGenerator->generate();

            $resetGateway->deleteWhere(['gibbonPersonID' => $gibbonPersonID]);

            //Insert key record
            try {
                $gibbonPersonResetID = str_pad($resetGateway->insert(['gibbonPersonID' => $gibbonPersonID, 'key' => $key,]), 12, '0', STR_PAD_LEFT);
            } catch (PDOException $e) {
                header("Location: {$URL->withReturn('error2')}");
                exit();
            }

            //Send email
            $subject = $session->get('organisationNameShort').' '.__('Gibbon Password Reset');
            $body = sprintf(__('A password reset request has been initiated for account %1$s, which is registered to this email address.%2$sIf you did not initiate this request, please ignore this email.%2$sIf you do wish to reset your password, please use the link below to access the reset form:%2$s%3$s%2$s%4$s'), $username, "\n\n", '', '');

            $mail = $container->get(Mailer::class);
            $mail->AddAddress($email);

            if ($session->exists('organisationEmail') && $session->get('organisationEmail') != '') {
                $mail->SetFrom($session->get('organisationEmail'), $session->get('organisationName'));
            } else {
                $mail->SetFrom($session->get('organisationAdministratorEmail'), $session->get('organisationName'));
            }

            $mail->Subject = $subject;
            $mail->renderBody('mail/email.twig.html', [
                'title'  => __('Password Reset'),
                'body'   => nl2br(trim($body, "\n")),
                'button' => [
                    'url'  => Url::fromRoute('passwordReset')->withAbsoluteUrl()->withQueryParams([
                        'input' => $input,
                        'step' => 2,
                        'gibbonPersonResetID' => $gibbonPersonResetID,
                        'key' => $key,
                    ]),
                    'external' => true,
                    'text' => __('Click Here'),
                ],
            ]);

            $details = [
                'gibbonPersonID' => $gibbonPersonID,
                'name' => Format::name('', $preferredName, $surname, 'Staff', false, true),
                'IPAddress' => $_SERVER['REMOTE_ADDR'],
            ];

            if ($mail->Send()) {
                // Log this password reset request
                $logGateway->addLog($session->get('gibbonSchoolYearID'), 'User Admin', null, 'User - Forgot Password Request Initiated', $details, $_SERVER['REMOTE_ADDR']);

                header("Location: {$URL->withReturn('success0')}");
                exit;
            } else {
                $logGateway->addLog($session->get('gibbonSchoolYearID'), 'User Admin', null, 'User - Forgot Password Request Email Failed', $details, $_SERVER['REMOTE_ADDR']);

                header("Location: {$URL->withReturn('success0')}");
                exit;
            }
} else { //This is the confirmation/reset phase
            //Get URL parameters
        	$key = $_GET['key'] ?? '';
        	$gibbonPersonResetID = $_GET['gibbonPersonResetID'] ?? '';

        	//Verify authenticity of this request and check it is fresh (within 48 hours)
            $reset = $resetGateway->getByID($gibbonPersonResetID);
            $fresh = !empty($reset)
                && hash_equals((string) ($reset['key'] ?? ''), (string) $key)
                && strtotime((string) ($reset['timestamp'] ?? '')) >= strtotime('-2 days');

        	if (!$fresh) {
                header("Location: {$URL->withReturn('error2')}");
                exit();
        	} else {
                $gibbonPersonID = $reset['gibbonPersonID'];

                $person = $userGateway->getByID($gibbonPersonID, ['gibbonPersonID', 'preferredName', 'surname', 'canLogin', 'gibbonRoleIDPrimary', 'status']);
                if (($person['status'] ?? '') != 'Full') {
                    $person = [];
                }

                $role = !empty($person['gibbonRoleIDPrimary']) ? $roleGateway->getRoleByID($person['gibbonRoleIDPrimary']) : [];

                if (empty($person)) {
                    header("Location: {$URL->withReturn('error4')}");
                    exit;
                }
                if (($person['canLogin'] ?? '') != 'Y') {
                    header("Location: {$URL->withReturn('fail2')}");
                    exit;
                }
                if (!empty($role['canLoginRole']) && $role['canLoginRole'] != 'Y') {
                    header("Location: {$URL->withReturn('fail9')}");
                    exit;
                }

                $preferredName = $person['preferredName'];
                $surname = $person['surname'];
                $passwordNew = $_POST['passwordNew'] ?? '';
                $passwordConfirm = $_POST['passwordConfirm'] ?? '';

                //Check passwords are not blank
                if ($passwordNew == '' or $passwordConfirm == '') {
                    header("Location: {$URL->withReturn('error1')}");
                    exit();
                } else {
                    //Check that new password is not same as old password
                    if ($password == $passwordNew) {
                        header("Location: {$URL->withReturn('error7')}");
                        exit();
                    } else {
                        /** @var PasswordPolicy */
                        $passwordPolicies = $container->get(PasswordPolicy::class);

                        //Check strength of password
                        if (!$passwordPolicies->validate($passwordNew)) {
                            header("Location: {$URL->withReturn('error6')}");
                            exit();
                        } else {
                            //Check new passwords match
                            if ($passwordNew != $passwordConfirm) {
                                header("Location: {$URL->withReturn('error5')}");
                                exit();
                            } else {
                                //Update password
                                $salt = getSalt();
                                $passwordStrong = hash('sha256', $salt.$passwordNew);
                                try {
                                    $userGateway->update($gibbonPersonID, [
                                        'passwordStrong' => $passwordStrong,
                                        'passwordStrongSalt' => $salt,
                                        'passwordForceReset' => 'N',
                                        'failCount' => 0,
                                    ]);
                                } catch (PDOException $e) {
                                    header("Location: {$URL->withReturn('error2')}");
                                    exit();
                                }

                                $resetGateway->deleteWhere(['gibbonPersonID' => $gibbonPersonID]);

                                // Log this password change
                                $details = [
                                    'gibbonPersonID' => $gibbonPersonID,
                                    'name' => Format::name('', $preferredName, $surname, 'Staff', false, true),
                                    'IPAddress' => $_SERVER['REMOTE_ADDR'],
                                ];
                                $logGateway->addLog($session->get('gibbonSchoolYearID'), 'User Admin', null, 'User - Password Changed Through Forgot Password', $details, $_SERVER['REMOTE_ADDR']);

                                //Return
                header("Location: {$URL->withReturn('success1')}");
                exit();
                            }
                        }
                    }
                }
            }
}
