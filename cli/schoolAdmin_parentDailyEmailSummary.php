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

use Gibbon\View\View;
use Gibbon\Services\Format;
use Gibbon\Contracts\Comms\Mailer;
use Gibbon\Comms\NotificationEvent;
use Gibbon\Domain\User\FamilyGateway;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\Attendance\AttendanceLogPersonGateway;

$_POST['address'] = '/modules/School Admin/emailSummarySettings.php';

require __DIR__.'/../gibbon.php';

//Check for CLI, so this cannot be run through browser
$settingGateway = $container->get(SettingGateway::class);
$remoteCLIKey = $settingGateway->getSettingByScope('System Admin', 'remoteCLIKey');
$remoteCLIKeyInput = $_GET['remoteCLIKey'] ?? null;
if (!(isCommandLineInterface() OR ($remoteCLIKey != '' AND $remoteCLIKey == $remoteCLIKeyInput))) {
    echo __('This script cannot be run from a browser, only via CLI.');
    return;
}

if (isSchoolOpen($guid, date('Y-m-d'), $connection2, true) == false) {
    echo __('School is not open, so no emails will be sent.');
    return;
}

if ($session->get('organisationEmail') == '') {
    echo __('This script cannot be run, as no school email address has been set.');
    return;
}

$parentDailyEmailSummaryIntroduction = $settingGateway->getSettingByScope('School Admin', 'parentDailyEmailSummaryIntroduction');
$parentDailyEmailSummaryPostScript = $settingGateway->getSettingByScope('School Admin', 'parentDailyEmailSummaryPostScript');

// Override the ini to keep this process alive
ini_set('memory_limit', '2048M');
ini_set('max_execution_time', 1800);
set_time_limit(1800);

// Prep for email sending later
$mail = $container->get(Mailer::class);
$mail->SMTPKeepAlive = true;

$sendReport = ['emailSent' => 0, 'emailFailed' => 0, 'emailErrors' => ''];

$currentDate = date('Y-m-d');
$gibbonSchoolYearID = $session->get('gibbonSchoolYearID');

$familyGateway = $container->get(FamilyGateway::class);
$attendanceLogGateway = $container->get(AttendanceLogPersonGateway::class);
$view = $container->get(View::class);

$schoolLogCriteria = $attendanceLogGateway->newQueryCriteria()
    ->sortBy('timestampTaken')
    ->filterBy('notClass', true);

$classLogCriteria = $attendanceLogGateway->newQueryCriteria()
    ->sortBy(['timeStart', 'timeEnd', 'timestampTaken']);

// Get all student data grouped by family
$families = $familyGateway->selectFamiliesWithActiveStudents($gibbonSchoolYearID)->fetchGrouped();

foreach ($families as $gibbonFamilyID => $students) {
    // Get the adults in this family and filter by email settings
    $familyAdults = $familyGateway->selectAdultsByFamily($gibbonFamilyID, true)->fetchAll();
    $familyAdults = array_filter($familyAdults, function ($parent) {
        return $parent['status'] == 'Full' && $parent['contactEmail'] == 'Y' && !empty($parent['email']);
    });

    if (empty($familyAdults)) continue;
    $content = '';

    foreach ($students as $student) {
        // Get school-wide attendance logs
        $logs = $attendanceLogGateway->queryByPersonAndDate($schoolLogCriteria, $student['gibbonPersonID'], $currentDate);
        $schoolLog = $logs->getRow(count($logs) - 1);

        // Get class attendance logs
        $classLogs = $attendanceLogGateway->queryClassAttendanceByPersonAndDate($classLogCriteria, $gibbonSchoolYearID, $student['gibbonPersonID'], $currentDate);
        $classLogs = array_filter($classLogs->toArray(), function ($log) {
            return !empty($log['gibbonAttendanceLogPersonID']);
        });

        // Format the student attendance log for emailing
        $content .= $view->fetchFromTemplate('cli/parentDailyEmailSummary.twig.html', [
            'student' => $student,
            'schoolLog' => $schoolLog,
            'classLogs' => $classLogs,
        ]);
    }

    // Format the email subject and content
    $subject = __('Daily Attendance Summary for {context}', ['context' => Format::date(date('Y-m-d'))]);

    $body = $parentDailyEmailSummaryIntroduction;
    $body .= '<br/><br/>';
    $body .= $content;
    $body .= '<br/>';
    $body .= $parentDailyEmailSummaryPostScript;

    // Add recipients and sender
    foreach ($familyAdults as $adult) {
        $mail->AddAddress($adult['email'], Format::name('', $adult['preferredName'], $adult['surname'], 'Parent', false, true));
    }
    $mail->setDefaultSender($subject);
    $mail->renderBody('mail/message.twig.html', [
        'title'  => $subject,
        'body'   => $body,
    ]);

    // Send
    if ($mail->Send()) {
        $sendReport['emailSent']++;
    } else {
        $parentContact1 = current($familyAdults);
        $sendReport['emailErrors'] .= sprintf(__('An error (%1$s) occurred sending an email to %2$s.'), 'failed to send', $parentContact1['preferredName'].' '.$parentContact1['surname']).'<br/>';
        $sendReport['emailFailed']++;
    }

    // Clear addresses
    $mail->ClearAllRecipients();
    $mail->clearReplyTos();
}

// Close SMTP connection
$mail->smtpClose();


// Raise a new notification event
$event = new NotificationEvent('School Admin', 'Parent Daily Email Summary');

$body = __('Date').': '.Format::date(date('Y-m-d')).'<br/>';
$body .= __('Total Count').': '.($sendReport['emailSent'] + $sendReport['emailFailed']).'<br/>';
$body .= __('Send Succeed Count').': '.$sendReport['emailSent'].'<br/>';
$body .= __('Send Fail Count').': '.$sendReport['emailFailed'].'<br/><br/>';
$body .= $sendReport['emailErrors'];

$event->setNotificationText(__('A School Admin CLI script has run.').'<br/><br/>'.$body);
$event->setActionLink('/index.php?q=/modules/School Admin/emailSummarySettings.php');

// Notify admin
$event->addRecipient($session->get('organisationAdministrator'));

// Send all notifications
$event->sendNotifications($pdo, $session);

// Output the result to terminal
echo sprintf('Sent %1$s emails: %2$s emails sent, %3$s emails failed.', $sendReport['emailSent'] + $sendReport['emailFailed'], $sendReport['emailSent'], $sendReport['emailFailed'])."\n";
