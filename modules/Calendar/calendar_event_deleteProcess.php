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

use Gibbon\Data\Validator;
use Gibbon\Support\Facades\Access;
use Gibbon\Domain\Calendar\CalendarEventGateway;
use Gibbon\Domain\Calendar\CalendarEventPersonGateway;
use Gibbon\Domain\Attendance\AttendanceLogPersonGateway;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$gibbonCalendarEventID = $_POST['gibbonCalendarEventID'] ?? '';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Calendar/calendar_event_manage.php';

if (isActionAccessible($guid, $connection2, '/modules/Calendar/calendar_event_delete.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} elseif (empty($gibbonCalendarEventID)) {
    $URL .= '&return=error1';
    header("Location: {$URL}");
    exit;
  } else {
    // Proceed!
    $calendarEventGateway = $container->get(CalendarEventGateway::class);
    $calendarEventPersonGateway = $container->get(CalendarEventPersonGateway::class);
    $attendanceLogPersonGateway = $container->get(AttendanceLogPersonGateway::class);

    // Validate the database relationships exist
    $event = $calendarEventGateway->getEventDetailsByID($gibbonCalendarEventID, $session->get('gibbonPersonID'));
    if (empty($event)) {
        header("Location: {$URL}&return=error2");
        exit;
    } 

    // Check for access to edit this event
    if ($event['editor'] != 'Y' && !Access::allows('Calendar', 'calendar_event_edit', 'Manage Events_all')) {
        header("Location: {$URL}&return=error0");
        exit;
    }

    $eventDeleted = $calendarEventGateway->delete($gibbonCalendarEventID);

    // Remove all participants linked to this event
    $calendarEventPersonGateway->deleteWhere(['gibbonCalendarEventID' => $gibbonCalendarEventID]);

    // Remove future absences for participants linked to this event
    $attendanceLogPersonGateway->deleteWhere(['foreignTable' => 'gibbonCalendarEvent', 'foreignTableID' => $gibbonCalendarEventID]);
    
    $URL .= !$eventDeleted
        ? '&return=error2'
        : '&return=success0';

    header("Location: {$URL}");
}
