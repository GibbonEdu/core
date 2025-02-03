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

use Gibbon\Comms\EmailTemplate;
use Gibbon\Comms\NotificationEvent;
use Gibbon\Contracts\Comms\Mailer;
use Gibbon\Domain\Finance\PettyCashGateway;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\User\FamilyGateway;
use Gibbon\Services\Format;

require getcwd().'/../gibbon.php';

//Check for CLI, so this cannot be run through browser
$settingGateway = $container->get(SettingGateway::class);
$remoteCLIKey = $settingGateway->getSettingByScope('System Admin', 'remoteCLIKey');
$remoteCLIKeyInput = $_GET['remoteCLIKey'] ?? null;
if (!(isCommandLineInterface() OR ($remoteCLIKey != '' AND $remoteCLIKey == $remoteCLIKeyInput))) {
	print __("This script cannot be run from a browser, only via CLI.") ;
}
else {

    // Initialize the notification sender & gateway objects
    $pettyCashGateway = $container->get(PettyCashGateway::class);
    $familyGateway = $container->get(FamilyGateway::class);

    // Prepare the mailer & email template
    $template = $container->get(EmailTemplate::class)->setTemplate('Staff Petty Cash');

    $mail = $container->get(Mailer::class);
    $mail->SMTPKeepAlive = true;

    // Get a list of students with an outstanding balance
    $staff = $pettyCashGateway->selectPettyCashBalanceByStaff($session->get('gibbonSchoolYearID'))->fetchAll();
    $emails = [];
    $emailIndex = 1;

    foreach ($staff as $templateData) {
        // Setup the email recipients
        $mail->ClearAddresses();
        $mail->AddAddress($templateData['email']);

        $mail->SetFrom($session->get('organisationEmail'), $session->get('organisationName'));
        $mail->AddReplyTo($session->get('organisationEmail'));
        $mail->setDefaultSender($template->renderSubject($templateData));

        $mail->renderBody('mail/message.twig.html', [
            'title'  => $template->renderSubject($templateData),
            'body'   => $template->renderBody($templateData),
        ]);

        // Send email and record the result
        $sent = $mail->Send();

        $emails[$emailIndex] = Format::name($templateData['title'], $templateData['preferredName'], $templateData['surname'], 'Staff').': '.$templateData['email'].' ($'.$templateData['amount'].') - '. ($sent ? __('Sent') : __('Failed') );
        $emailIndex++;
    }

    // Raise a new notification event
    $event = new NotificationEvent('Finance', 'Petty Cash Notification');

    $event->setNotificationText(__('A Petty Cash CLI script has run, sending {count} emails.', ['count' => count($emails)]));
    $event->setNotificationDetails($emails);
    $event->setActionLink('/index.php?q=/modules/Finance/pettyCash.php');

    // Notify admin
    $event->addRecipient($session->get('organisationAdministrator'));

    // Push the event to the notification sender
    $sendReport = $event->sendNotifications($pdo, $session);

    // Output the result to terminal
    echo sprintf('Sent %1$s notifications: %2$s inserts, %3$s updates, %4$s emails sent, %5$s emails failed.', $sendReport['count'], $sendReport['inserts'], $sendReport['updates'], $sendReport['emailSent'], $sendReport['emailFailed'])."\n";
}
