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

use Gibbon\Services\Format;
use Gibbon\Contracts\Comms\Mailer;

$_POST['address'] = '/modules/Planner/index.php';

require __DIR__.'/../gibbon.php';

// Setup some of the globals
getSystemSettings($guid, $connection2);
setCurrentSchoolYear($guid, $connection2);
Format::setupFromSession($container->get('session'));

if (!isCommandLineInterface()) {
    print __('This script cannot be run from a browser, only via CLI.');
    return;
}

if (isSchoolOpen($guid, date('Y-m-d'), $connection2, true) == false) { 
    echo __('School is not open, so no emails will be sent.');
    return;
}

if ($_SESSION[$guid]['organisationEmail'] == '') {
    echo __('This script cannot be run, as no school email address has been set.');
    return;
}

$parentDailyEmailSummaryIntroduction = getSettingByScope($connection2, 'Planner', 'parentDailyEmailSummaryIntroduction');
$parentDailyEmailSummaryPostScript = getSettingByScope($connection2, 'Planner', 'parentDailyEmailSummaryPostScript');

// Override the ini to keep this process alive
ini_set('memory_limit', '2048M');
ini_set('max_execution_time', 1800);
set_time_limit(1800);

// Prep for email sending later
$mail = $container->get(Mailer::class);
$mail->SMTPKeepAlive = true;
$sendReport = ['emailSent' => 0, 'emailFailed' => 0];



// Send a receipt
$studentName = Format::name('', $values['preferredName'], $values['surname'], 'Student', false, true);
$subject = __('Daily Summary for {name} via {system} at {organisation}', [
    'name' => $studentName,
    'system' => $_SESSION[$guid]['systemName'],
    'organisation' => $_SESSION[$guid]['organisationNameShort'],
]);
$body = $parentDailyEmailSummaryIntroduction;
$body .= '<br/><br/>';
$body .= 'Content';
$body .= '<br/><br/>';
$body .= $parentDailyEmailSummaryPostScript;

$mail->AddAddress($values['parent1email']);
$mail->setDefaultSender($subject);
$mail->renderBody('mail/message.twig.html', [
    'title'  => __('Daily Summary for {name}', ['name' => $studentName]),
    'body'   => $body,
]);

// Send
if ($mail->Send()) {
    $sendReport['emailSent']++;
} else {
    $sendReport['emailFailed']++;
}

// Clear addresses
$mail->ClearAllRecipients();


// Close SMTP connection
$mail->smtpClose();


// Output the result to terminal
echo sprintf('Sent %1$s notifications: %2$s inserts, %3$s updates, %4$s emails sent, %5$s emails failed.', $sendReport['emailSent'] + $sendReport['emailFailed'], $sendReport['inserts'] ?? 0, $sendReport['updates'] ?? 0, $sendReport['emailSent'], $sendReport['emailFailed'])."\n";
