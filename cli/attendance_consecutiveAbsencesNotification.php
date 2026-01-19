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
use Gibbon\Services\Format;
use Gibbon\Comms\NotificationEvent;
use Gibbon\Comms\NotificationSender;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\System\NotificationGateway;
use Gibbon\Domain\Attendance\AttendanceLogPersonGateway;

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
$today = date('Y-m-d');
$timestamp = Format::timestamp($today);
$count = 0;
$spin = 0;
$max = 100;
$schoolDays  = [];

while ($count < $threshold and $spin <= $max) {
    $checkDate = date('Y-m-d', ($timestamp - ($spin * 86400)));
    if (isSchoolOpen($guid, $checkDate, $connection2 )) {
        $schoolDays[] = $checkDate;
        ++$count;
    }
    ++$spin;
}

if ((empty($schoolDays)) ) {
    print __("No school days found.") ;
    return;
}

$absentStudents = $container->get(AttendanceLogPersonGateway::class)->selectConsecutiveAbsencesByPersonAndDates($schoolDays, $session->get('gibbonSchoolYearID'), $threshold);

if (empty($absentStudents)) {
    print __("No absent students found.") ;
    return;
}

// Initialize the notification sender & gateway objects
$notificationGateway = $container->get(NotificationGateway::class);
$notificationSender = $container->get(NotificationSender::class);

// Raise a new notification event
$event = new NotificationEvent('Attendance', 'Consecutive Absences Notification');
$studentsList = [];

if ($event->getEventDetails($notificationGateway, 'active') == 'Y') {
    if ($absentStudents->rowCount() > 0) {
        while ($row = $absentStudents->fetch()) { // For every staff
            $studentName = $row['surname']. ', ' . $row['preferredName'] . ' - ' . $row['formGroup'];
            $url = Url::fromModuleRoute('Attendance', 'report_studentHistory.php')->withQueryParams(['gibbonPersonID' => $row['gibbonPersonID']]);
            $studentsList[] = Format::link($url, $studentName);
        }
    }
}

$event->setNotificationText(__('The following students have been consecutively absent for the last 3 or more school days (including today)').'<br/></br>'.Format::list($studentsList));
$event->setActionLink('/index.php?q=/modules/Attendance/report_consecutiveAbsences.php&numberOfSchoolDays='.$threshold);

$event->pushNotifications($notificationGateway, $notificationSender);
// Send all notifications
$sendReport = $notificationSender->sendNotifications();

 // Output the result to terminal
echo sprintf('Sent %1$s notifications: %2$s inserts, %3$s updates, %4$s emails sent, %5$s emails failed.', $sendReport['count'], $sendReport['inserts'], $sendReport['updates'], $sendReport['emailSent'], $sendReport['emailFailed'])."\n";
