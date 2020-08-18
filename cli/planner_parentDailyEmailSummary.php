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
use Gibbon\Domain\User\FamilyGateway;
use Gibbon\Domain\Attendance\AttendanceLogPersonGateway;

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

ini_set('error_reporting', E_ALL);
ini_set('display_errors', '1');

// Prep for email sending later
$mail = $container->get(Mailer::class);
$mail->SMTPKeepAlive = true;
$sendReport = ['emailSent' => 0, 'emailFailed' => 0];

$currentDate = date('Y-m-d');
$gibbonSchoolYearID = $gibbon->session->get('gibbonSchoolYearID');

$familyGateway = $container->get(FamilyGateway::class);
$attendanceLogGateway = $container->get(AttendanceLogPersonGateway::class);
$view = $container->get(View::class);

$schoolLogCriteria = $attendanceLogGateway->newQueryCriteria()
    ->sortBy('timestampTaken')
    ->filterBy('notClass', true);

$classLogCriteria = $attendanceLogGateway->newQueryCriteria()
    ->sortBy(['timeStart', 'timeEnd', 'timestampTaken']);

$families = $familyGateway->selectFamiliesWithActiveStudents($gibbonSchoolYearID)->fetchGrouped();
$families = array_slice($families, 0, 1);

foreach ($families as $gibbonFamilyID => $students) {
    // Get the adults in this family and filter by email settings
    $familyAdults = $familyGateway->selectAdultsByFamily($gibbonFamilyID, true)->fetchAll();
    $familyAdults = array_filter($familyAdults, function ($adult) {
        return $adult['contactEmail'] == 'Y';
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
        if (!empty($schoolLog) && !empty($schoolLog)) {
            $content .= $view->fetchFromTemplate('cli/attendanceEmail.twig.html', [
                'student' => $student,
                'schoolLog' => $schoolLog,
                'classLogs' => $classLogs,

            ]);
        }
    }
    // echo '<pre>';
    // print_r($familyAdults);
    // echo '<pre>';

    // How do we handle no attendance record for this day?
    if (empty($content)) {
        $content = __('There is currently no attendance data today for the selected student.');
    }

    // Format the email subject and content
    $subject = __('Daily Attendance Summary via {system}', [
        'system' => $_SESSION[$guid]['systemName'],
        'organisation' => $_SESSION[$guid]['organisationNameShort'],
    ]);

    $body = $parentDailyEmailSummaryIntroduction;
    $body .= '<br/><br/>';
    $body .= $content;
    // $body .= '<hr style="border:none;border-bottom:1px solid #ececec;margin:1.5rem 0;width:100%;">';
    $body .= '<br/><br/>';
    $body .= $parentDailyEmailSummaryPostScript;

    // Add recipients and sender
    foreach ($familyAdults as $adult) {
        $mail->AddAddress($adult['email'], Format::name('', $adult['preferredName'], $adult['surname'], 'Parent', false, true));
    }
    $mail->setDefaultSender($subject);
    $mail->renderBody('mail/message.twig.html', [
        'title'  => __('Daily Attendance Summary'),
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
}

// echo '<pre>';
// print_r($families);
// echo '<pre>';


// Close SMTP connection
$mail->smtpClose();


// Output the result to terminal
echo sprintf('Sent %1$s notifications: %2$s inserts, %3$s updates, %4$s emails sent, %5$s emails failed.', $sendReport['emailSent'] + $sendReport['emailFailed'], $sendReport['inserts'] ?? 0, $sendReport['updates'] ?? 0, $sendReport['emailSent'], $sendReport['emailFailed'])."\n";
