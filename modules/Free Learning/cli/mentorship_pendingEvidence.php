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
use Gibbon\Comms\NotificationSender;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Module\FreeLearning\Domain\UnitStudentGateway;

$_POST['address'] = '/modules/Free Learning/report_workPendingApproval.php';

require __DIR__.'/../../../gibbon.php';

// Setup some of the globals
getSystemSettings($guid, $connection2);
setCurrentSchoolYear($guid, $connection2);
Format::setupFromSession($container->get('session'));

//Check for CLI, so this cannot be run through browser
$settingGateway = $container->get(SettingGateway::class);
$remoteCLIKey = $settingGateway->getSettingByScope('System Admin', 'remoteCLIKey');
$remoteCLIKeyInput = $_GET['remoteCLIKey'] ?? null;
if (!(isCommandLineInterface() OR ($remoteCLIKey != '' AND $remoteCLIKey == $remoteCLIKeyInput))) {
    print __('This script cannot be run from a browser, only via CLI.');
    return;
}

if ($session->get('organisationEmail') == '') {
    echo __('This script cannot be run, as no school email address has been set.');
    return;
}

// Override the ini to keep this process alive
ini_set('memory_limit', '2048M');
ini_set('max_execution_time', 1800);
set_time_limit(1800);

$evidenceOutstandingPrompt = $settingGateway->getSettingByScope('Free Learning', 'evidenceOutstandingPrompt');

$gibbonSchoolYearID = $session->get('gibbonSchoolYearID');
$notificationSender = $container->get(NotificationSender::class);

// Get the list of mentors with requests older than 7 days
$mentors = $container->get(UnitStudentGateway::class)->selectEvidencePending($gibbonSchoolYearID, null, $evidenceOutstandingPrompt)->fetchKeyPair();

// Loop over each mentor and add a notification to send
foreach ($mentors as $gibbonPersonID => $count) {
    $actionText = __m('You have {count} item(s) of work pending approval older than {evidenceOutstandingPrompt} days. Please click below or visit the Work Pending Approval page to view and manage your requests.', ['count' => $count, 'evidenceOutstandingPrompt' => $evidenceOutstandingPrompt]);
    $actionLink = '/index.php?q=/modules/Free Learning/report_workPendingApproval.php';
    $notificationSender->addNotification($gibbonPersonID, $actionText, 'Free Learning', $actionLink);
}

$sendReport = $notificationSender->sendNotifications();

// Notify admin
$actionText = __m('A Free Learning CLI script ({name}) has run.', ['name' => 'Pending Evidence Requests']).'<br/><br/>';
$actionText .= __('Date').': '.Format::date(date('Y-m-d')).'<br/>';
$actionText .= __('Total Count').': '.($sendReport['emailSent'] + $sendReport['emailFailed']).'<br/>';
$actionText .= __('Send Succeed Count').': '.$sendReport['emailSent'].'<br/>';
$actionText .= __('Send Fail Count').': '.$sendReport['emailFailed'];

$actionLink = '/index.php?q=/modules/Free Learning/report_workPendingApproval.php';

$notificationSender = $container->get(NotificationSender::class);
$notificationSender->addNotification($session->get('organisationAdministrator'), $actionText, 'Free Learning', $actionLink);
$notificationSender->sendNotifications();

// Output the result to terminal
echo sprintf('Sent %1$s emails: %2$s emails sent, %3$s emails failed.', $sendReport['emailSent'] + $sendReport['emailFailed'], $sendReport['emailSent'], $sendReport['emailFailed'])."\n";
