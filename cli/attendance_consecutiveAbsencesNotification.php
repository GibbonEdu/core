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
use Gibbon\Comms\EmailTemplate;
use Gibbon\Contracts\Comms\Mailer;
use Gibbon\Comms\NotificationEvent;
use Gibbon\Comms\NotificationSender;
use Gibbon\Domain\User\FamilyGateway;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\Finance\PettyCashGateway;
use Gibbon\Domain\System\NotificationGateway;

require getcwd().'/../gibbon.php';

// Check for CLI, so this cannot be run through browser
$settingGateway = $container->get(SettingGateway::class);
$remoteCLIKey = $settingGateway->getSettingByScope('System Admin', 'remoteCLIKey');
$remoteCLIKeyInput = $_GET['remoteCLIKey'] ?? null;

if (!(isCommandLineInterface() OR ($remoteCLIKey != '' AND $remoteCLIKey == $remoteCLIKeyInput))) {
    print __("This script cannot be run from a browser, only via CLI.") ;
    return;
}

// Override the ini to keep this process alive
ini_set('memory_limit', '2048M');
ini_set('max_execution_time', 1800);
set_time_limit(1800);

$threshold = 3;
$date = date('Y-m-d');
$timestamp = Format::timestamp($date);


    
    if ($inclusive == true)  $timestamp += 86400;

    $count = 0;
    $spin = 1;
    $max = max($n, 100);
    $lastNSchoolDays = array();
    while ($count < $n and $spin <= $max) {
        $date = date('Y-m-d', ($timestamp - ($spin * 86400)));
        if (isSchoolOpen($guid, $date, $connection2 )) {
            $lastNSchoolDays[$count] = $date;
            ++$count;
        }
        ++$spin;
    }

    return $lastNSchoolDays;


    // Initialize the notification sender & gateway objects
    // $pettyCashGateway = $container->get(PettyCashGateway::class);
    // $familyGateway = $container->get(FamilyGateway::class);

    // // Prepare the mailer & email template
    // $template = $container->get(EmailTemplate::class)->setTemplate('Staff Petty Cash');

    // $mail = $container->get(Mailer::class);
    // $mail->SMTPKeepAlive = true;

    // // Get a list of students with an outstanding balance
    // $staff = $pettyCashGateway->selectPettyCashBalanceByStaff($session->get('gibbonSchoolYearID'))->fetchAll();
    // $emails = [];
    // $emailIndex = 1;

    // foreach ($staff as $templateData) {
    //     // Setup the email recipients
    //     $mail->ClearAddresses();
    //     $mail->AddAddress($templateData['email']);

    //     $mail->SetFrom($session->get('organisationEmail'), $session->get('organisationName'));
    //     $mail->AddReplyTo($session->get('organisationEmail'));
    //     $mail->setDefaultSender($template->renderSubject($templateData));

    //     $mail->renderBody('mail/message.twig.html', [
    //         'title'  => $template->renderSubject($templateData),
    //         'body'   => $template->renderBody($templateData),
    //     ]);

    //     // Send email and record the result
    //     $sent = $mail->Send();

    //     $emails[$emailIndex] = Format::name($templateData['title'], $templateData['preferredName'], $templateData['surname'], 'Staff').': '.$templateData['email'].' ($'.$templateData['amount'].') - '. ($sent ? __('Sent') : __('Failed') );
    //     $emailIndex++;
    // }


    // Initialize the notification sender & gateway objects
    $notificationGateway = $container->get(NotificationGateway::class);
    $notificationSender = $container->get(NotificationSender::class);

    // Raise a new notification event
    $event = new NotificationEvent('Attendance', 'Consecutive Absences Notification');

    $event->setNotificationText(__('A Notify Consecutive Absences CLI script has run, sending {count} emails.', ['count' => count($emails)]));
    $event->setNotificationDetails($emails);
    $event->setActionLink('/index.php?q=/modules/Finance/pettyCash.php');

    // Notify admin
    $event->addRecipient($session->get('organisationAdministrator'));

    // Push the event to the notification sender
    $sendReport = $event->sendNotifications($pdo, $session);

    // Output the result to terminal
    echo sprintf('Sent %1$s notifications: %2$s inserts, %3$s updates, %4$s emails sent, %5$s emails failed.', $sendReport['count'], $sendReport['inserts'], $sendReport['updates'], $sendReport['emailSent'], $sendReport['emailFailed'])."\n";

