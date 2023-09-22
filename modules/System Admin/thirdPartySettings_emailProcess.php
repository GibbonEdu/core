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

use Gibbon\Services\Format;
use Gibbon\Contracts\Comms\Mailer;
use Gibbon\Data\Validator;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$URL = $session->get('absoluteURL').'/index.php?q=/modules/System Admin/thirdPartySettings.php';

if (isActionAccessible($guid, $connection2, '/modules/System Admin/thirdPartySettings.php') == false) {
    // Access denied
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $email = $_GET['email'] ?? $session->get('email');

    $mail = $container->get(Mailer::class);
    $mail->SMTPDebug = 2;
    $mail->Debugoutput = 'error_log';

    $mail->AddAddress($email);
    $mail->setDefaultSender(__('Test Email'));
    $mail->renderBody('mail/message.twig.html', [
        'title'  => __('Test Email'),
        'body'   => '',
        'details' => [
            __('Recipient') => $email,
            __('Date')      => Format::dateTime(date('Y-m-d H:i:s')),
        ]
    ]);

    $sent = $mail->Send();

    if (!$sent) {
        $session->set('testEmailError', $mail->ErrorInfo);
        $session->set('testEmailRecipient', $email);
    }
    
    $URL .= !$sent 
        ? '&return=error10'
        : '&return=success0';
    header("Location: " . $URL);
}
