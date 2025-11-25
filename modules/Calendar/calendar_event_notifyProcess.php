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

use Gibbon\Data\Validator;
use Gibbon\Domain\Calendar\CalendarEventGateway;
use Gibbon\Domain\Calendar\CalendarEventPersonGateway;
use Gibbon\Module\Calendar\CalendarEventNotificationProcess;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST, ['notes' => 'HTML']);
$gibbonCalendarEventID = $_POST['gibbonCalendarEventID'] ?? '';
$URL = $session->get('absoluteURL').'/index.php?q=/modules/Calendar/calendar_event_view.php&gibbonCalendarEventID='.$gibbonCalendarEventID;

if (empty($gibbonCalendarEventID)) {
    $URL .= '&return=error1';
    header("Location: {$URL}");
    exit;
}

$criteria = $container->get(CalendarEventPersonGateway::class)->newQueryCriteria()
            ->sortBy(['surname', 'preferredName', 'category'])
            ->fromPOST();
$students = $container->get(CalendarEventPersonGateway::class)->queryEventAttendees($criteria, $gibbonCalendarEventID)->toArray();
if (empty($students)) {
    $URL .= '&return=error1';
    header("Location: {$URL}");
    exit;
}

if (isActionAccessible($guid, $connection2, '/modules/Calendar/calendar_event_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $calendarEventGateway = $container->get(CalendarEventGateway::class);
    $calendarEventPersonGateway = $container->get(CalendarEventPersonGateway::class);

    $subject = $_POST['subject'] ?? '';
    $notes = $_POST['notes'] ?? '';
    $notifyGroups = $_POST['notifyGroups'] ?? [];
    $allStaff = $_POST['allStaff'] ?? 'N';
    $notificationList = isset($_POST['notificationList']) ? explode(',', $_POST['notificationList']) : [];
    $gibbonPersonIDSender = $session->get('gibbonPersonID') ?? '';
    $gibbonSchoolYearID = $session->get('gibbonSchoolYearID') ?? '';
    $organisationEmail = $session->get('organisationEmail') ?? '';

    $process = $container->get(CalendarEventNotificationProcess::class);
    $success = $process->startNotifyStaff($gibbonCalendarEventID, $subject, $notes, $notifyGroups, $allStaff, $notificationList, $gibbonPersonIDSender, $gibbonSchoolYearID, $organisationEmail);

    $URL .= !$success
        ? "&return=warning1"
        : "&return=success0";

    header("Location: {$URL}");
}
