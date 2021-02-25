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

use Gibbon\View\View;
use Gibbon\Services\Format;
use Gibbon\Contracts\Comms\Mailer;
use Gibbon\Comms\NotificationEvent;
use Gibbon\Domain\User\UserGateway;
use Gibbon\Domain\School\YearGroupGateway;
use Gibbon\Domain\Students\StudentGateway;
use Gibbon\Domain\RollGroups\RollGroupGateway;
use Gibbon\Domain\Attendance\AttendanceLogPersonGateway;

$_POST['address'] = '/modules/School Admin/emailSummarySettings.php';

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

// Override the ini to keep this process alive
ini_set('memory_limit', '2048M');
ini_set('max_execution_time', 1800);
set_time_limit(1800);

// Prep for email sending later
$mail = $container->get(Mailer::class);
$mail->SMTPKeepAlive = true;
$sendReport = ['emailSent' => 0, 'emailFailed' => 0, 'emailErrors' => ''];

$currentDate = date('Y-m-d');
$gibbonSchoolYearID = $gibbon->session->get('gibbonSchoolYearID');

// Setup reusable gateways and criteria
$userGateway = $container->get(UserGateway::class);
$studentGateway = $container->get(StudentGateway::class);
$yearGroupGateway = $container->get(YearGroupGateway::class);
$rollGroupGateway = $container->get(RollGroupGateway::class);
$attendanceLogGateway = $container->get(AttendanceLogPersonGateway::class);
$view = $container->get(View::class);

$schoolLogCriteria = $attendanceLogGateway->newQueryCriteria()
    ->sortBy('timestampTaken')
    ->filterBy('notClass', true);

$classLogCriteria = $attendanceLogGateway->newQueryCriteria()
    ->sortBy(['timeStart', 'timeEnd', 'timestampTaken']);

$studentCriteria = $attendanceLogGateway->newQueryCriteria()
    ->sortBy(['gibbonYearGroup.sequenceNumber', 'gibbonRollGroup.nameShort', 'gibbonPerson.surname', 'gibbonPerson.preferredName']);

// Get all active students grouped by year group and form group
$allStudents = $studentGateway->queryStudentsBySchoolYear($studentCriteria, $gibbonSchoolYearID)->toArray();
$yearGroups = array_reduce($allStudents, function ($group, $item) {
    $group[$item['gibbonYearGroupID']][$item['gibbonRollGroupID']][] = $item;
    return $group;
}, []);

// Loop over each year group and roll group to send summary emails
foreach ($yearGroups as $gibbonYearGroupID => $rollGroups) {

    $yearGroupContent = '';
    $yearGroup = $yearGroupGateway->getByID($gibbonYearGroupID);

    if (empty($yearGroup)) continue;

    foreach ($rollGroups as $gibbonRollGroupID => $students) {
    
        $rollGroupContent = '';
        $rollGroup = $rollGroupGateway->getByID($gibbonRollGroupID);
        
        if (empty($rollGroup)) continue;

        foreach ($students as $student) {
            // Get school-wide attendance logs
            $logs = $attendanceLogGateway->queryByPersonAndDate($schoolLogCriteria, $student['gibbonPersonID'], $currentDate);
            $schoolLog = $logs->getRow(count($logs) - 1);

            // Get class attendance logs
            $classLogs = $attendanceLogGateway->queryClassAttendanceByPersonAndDate($classLogCriteria, $gibbonSchoolYearID, $student['gibbonPersonID'], $currentDate);

            // Format the student attendance log for emailing
            $content = $view->fetchFromTemplate('cli/tutorDailyEmailSummary.twig.html', [
                'student' => $student,
                'schoolLog' => $schoolLog,
                'classLogs' => $classLogs,
            ]);

            $rollGroupContent .= $content;
            $yearGroupContent .= $content;
        }

        // Format the email subject
        $subject = __('Daily Attendance Summary for {context}', ['context' => $rollGroup['nameShort'].' '.Format::date(date('Y-m-d'))]);

        // Add recipients and sender
        $tutors = $rollGroupGateway->selectTutorsByRollGroup($gibbonRollGroupID);
        foreach ($tutors as $tutor) {
            $mail->AddAddress($tutor['email'], Format::name('', $tutor['preferredName'], $tutor['surname'], 'Staff', false, true));
        }
        $mail->setDefaultSender($subject);
        $mail->renderBody('mail/message.twig.html', [
            'title'  => $subject,
            'body'   => $rollGroupContent,
        ]);

        // Send
        if ($mail->Send()) {
            $sendReport['emailSent']++;
        } else {
            $sendReport['emailFailed']++;
            $sendReport['emailErrors'] .= sprintf(__('An error (%1$s) occurred sending an email to %2$s.'), 'failed to send', $tutor['preferredName'].' '.$tutor['surname']).'<br/>';
        }

        // Clear addresses
        $mail->ClearAllRecipients();
        $mail->clearReplyTos();
    }

    // Send a year group summary to the HOY
    $HOY = $userGateway->getByID($yearGroup['gibbonPersonIDHOY']);
    if (!empty($HOY)) {
        $subject = __('Daily Attendance Summary for {context}', ['context' => $yearGroup['nameShort'].' '.Format::date(date('Y-m-d'))]);

        // Add recipients and sender
        $mail->AddAddress($HOY['email'], Format::name('', $HOY['preferredName'], $HOY['surname'], 'Staff', false, true));
        $mail->setDefaultSender($subject);
        $mail->renderBody('mail/message.twig.html', [
            'title'  => $subject,
            'body'   => $yearGroupContent,
        ]);

        // Send
        if ($mail->Send()) {
            $sendReport['emailSent']++;
        } else {
            $sendReport['emailFailed']++;
            $sendReport['emailErrors'] .= sprintf(__('An error (%1$s) occurred sending an email to %2$s.'), 'failed to send', $HOY['preferredName'].' '.$HOY['surname']).'<br/>';
        }

        // Clear addresses
        $mail->ClearAllRecipients();
        $mail->clearReplyTos();
    }
}

// Close SMTP connection
$mail->smtpClose();


// Raise a new notification event
$event = new NotificationEvent('School Admin', 'Tutor Daily Email Summary');

$body = __('Date').': '.Format::date(date('Y-m-d')).'<br/>';
$body .= __('Total Count').': '.($sendReport['emailSent'] + $sendReport['emailFailed']).'<br/>';
$body .= __('Send Succeed Count').': '.$sendReport['emailSent'].'<br/>';
$body .= __('Send Fail Count').': '.$sendReport['emailFailed'].'<br/><br/>';
$body .= $sendReport['emailErrors'];

$event->setNotificationText(__('A School Admin CLI script has run.').'<br/><br/>'.$body);
$event->setActionLink('/index.php?q=/modules/School Admin/emailSummarySettings.php');

// Notify admin
$event->addRecipient($_SESSION[$guid]['organisationAdministrator']);

// Send all notifications
$event->sendNotifications($pdo, $gibbon->session);


// Output the result to terminal
echo sprintf('Sent %1$s emails: %2$s emails sent, %3$s emails failed.', $sendReport['emailSent'] + $sendReport['emailFailed'], $sendReport['emailSent'], $sendReport['emailFailed'])."\n";
