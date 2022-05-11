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
use Gibbon\Comms\EmailTemplate;
use Gibbon\Contracts\Comms\Mailer;
use Gibbon\Domain\Admissions\AdmissionsAccountGateway;
use Gibbon\Services\Format;

require_once '../../gibbon.php';

$gibbonFormID = $_POST['gibbonFormID'] ?? '';
$email = $_POST['admissionsLoginEmail'] ?? '';

$URL = Url::fromModuleRoute('Admissions', 'applicationSelect');

if (empty($email)) {
    header("Location: {$URL->withReturn('error0')}");
    exit;
} else {
    // Proceed!
    if (empty($gibbonFormID)) {
        header("Location: {$URL->withReturn('error1')}");
        exit;
    }

    $admissionsAccountGateway = $container->get(AdmissionsAccountGateway::class);

    $account = $admissionsAccountGateway->getAccountByEmail($email);

    if (empty($account)) {
        // New account
        $accessID = $admissionsAccountGateway->getUniqueAccessID($guid.$email);
        $gibbonAdmissionsAccountID = $admissionsAccountGateway->insert(['email' => $email, 'accessID' => $accessID]);
        $accountType = 'new';
    } else {   
        // Existing account
        $accessID = $account['accessID'];
        $gibbonAdmissionsAccountID = $account['gibbonAdmissionsAccountID'];
        $accountType = 'existing';
    }

    if (empty($gibbonAdmissionsAccountID) || empty($accessID)) {
        header("Location: {$URL->withReturn('error2')}");
        exit;
    }

    // Redirect if a new application form was selected
    if ($gibbonFormID != 'existing') {
        $URL = Url::fromModuleRoute('Admissions', 'applicationForm')->withQueryParams([
            'gibbonFormID' => $gibbonFormID,
            'accessID'     => $accessID,
            'accountType'  => $accountType,
        ]);
        header("Location: {$URL}");
        exit;
    }

    // Cannot continue if this is a new account - no existing forms
    if ($accountType != 'existing') {
        header("Location: {$URL->withReturn('error4')}");
        exit;
    }

    // Generate a unique access token and update the admissions account
    $accessToken = $admissionsAccountGateway->getUniqueAccessToken($guid.$accessID);
    $accessExpiry = date('Y-m-d H:i:s', strtotime("+2 days"));
    $admissionsAccountGateway->update($gibbonAdmissionsAccountID, ['accessToken' => $accessToken, 'timestampTokenExpire' => $accessExpiry]);
    
    // Handle sending email link to existing admissions account
    $template = $container->get(EmailTemplate::class)->setTemplate('Admissions Account Access');
    $data = ['email' => $email, 'date' => Format::date(date('Y-m-d')) ];

    $mail = $container->get(Mailer::class);

    $mail->AddAddress($email);
    $mail->setDefaultSender($template->renderSubject($data));

    $mail->renderBody('mail/email.twig.html', [
        'title'  => $template->renderSubject($data),
        'body'   => $template->renderBody($data),
        'button' => [
            'url'  => Url::fromModuleRoute('Admissions', 'applicationView')
                ->withQueryParams(['acc' => $accessID, 'tok' => $accessToken])
                ->withAbsoluteUrl(),
            'text' => __('Access your Account'),
            'external' => true,
        ],
    ]);

    if ($mail->Send()) {
        header("Location: {$URL->withReturn('success1')}");
    } else {
        header("Location: {$URL->withQueryParam('email', $email)->withReturn('error5')}");
    }

    
}
