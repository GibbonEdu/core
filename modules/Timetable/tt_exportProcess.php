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

use Gibbon\Domain\School\SchoolYearGateway;
use Gibbon\Domain\System\NotificationGateway;
use Gibbon\Domain\Timetable\TimetableDayDateGateway;
use Gibbon\UI\Timetable\TimetableAccess;

require_once __DIR__ . '/../../gibbon.php';

$gibbonPersonID = $container->get(TimetableAccess::class)->getExportablePersonID($_POST['superSecretHiddenValue'] ?? '');
$sessionPersonID = $session->get('gibbonPersonID');
$gibbonSchoolYearID = $session->get('gibbonSchoolYearID');

if ($gibbonPersonID == '') {
    $page->addError(__('You do not have access to this action.'));
    return;
}

$schoolYear = $container->get(SchoolYearGateway::class)->getSchoolYearByID($gibbonSchoolYearID);
if (empty($schoolYear)) {
    $page->addError(__('You do not have access to this action.'));
    return;
}

date_default_timezone_set($session->get('timezone'));
$vCalendar = new \Eluceo\iCal\Component\Calendar('Calendar Export');
$prefix = $_POST['prefix'] ?? '';
$reminderMinutes = (int)($_POST['options'] ?? 0);

$periods = $container->get(TimetableDayDateGateway::class)->selectTimetabledPeriodsByPersonAndDateRange(
    $gibbonPersonID,
    $schoolYear['firstDay'],
    $schoolYear['lastDay']
);

while ($period = $periods->fetch()) {
    $vEvent = new \Eluceo\iCal\Component\Event();
    if ($reminderMinutes > 0) {
        $vAlarm = new \Eluceo\iCal\Component\Alarm();
        $vAlarm->setAction('DISPLAY');
        $vAlarm->setTrigger('-PT'.$reminderMinutes.'M');
        $vAlarm->setDescription('Event description');
        $vEvent->addComponent($vAlarm);
    }

    $vEvent->setDtStart(new \DateTime($period['date'].' '.$period['timeStart']));
    $vEvent->setDtEnd(new \DateTime($period['date'].' '.$period['timeEnd']));
    $vEvent->setSummary($prefix != '' ? $prefix.' | '.$period['courseName'] : $period['courseName']);
    $vEvent->setUseTimezone(true);
    $vCalendar->addComponent($vEvent);
}

// Subscribe the caller only when exporting their own timetable.
if ($gibbonPersonID == $sessionPersonID) {
    $notificationGateway = $container->get(NotificationGateway::class);
    $event = $notificationGateway->selectNotificationEventByName('Timetable', 'Updated Timetable Subscriber')->fetch();

    if (!empty($event)) {
        $listeners = $notificationGateway->selectAllNotificationListeners($event['gibbonNotificationEventID'])->fetchAll();
        $alreadyListening = false;
        foreach ($listeners as $listener) {
            if ($listener['gibbonPersonID'] == $sessionPersonID) {
                $alreadyListening = true;
                break;
            }
        }

        if (!$alreadyListening) {
            $notificationGateway->insertNotificationListener([
                'gibbonNotificationEventID' => $event['gibbonNotificationEventID'],
                'gibbonPersonID' => $sessionPersonID,
                'scopeType' => 'All',
                'scopeID' => 0,
                'scopeContext' => null,
            ]);
        }
    }
}

header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: attachment; filename="cal.ics"');
echo $vCalendar->render();
