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
use Gibbon\Domain\Calendar\CalendarEventGateway;
use Gibbon\Domain\Calendar\CalendarEventPersonGateway;

include '../../gibbon.php';

$action = $_POST['action'] ?? '';
$gibbonCalendarEventID = $_POST['gibbonCalendarEventID'] ?? '';

$URL = $session->get('absoluteURL')."/index.php?q=/modules/Calendar/calendar_event_enrolment.php&gibbonCalendarEventID=$gibbonCalendarEventID";

if (isActionAccessible($guid, $connection2, '/modules/Calendar/calendar_event_enrolment.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    // Proceed!

    $calendarEventGateway = $container->get(CalendarEventGateway::class);
    $calendarEventPersonGateway = $container->get(CalendarEventPersonGateway::class);

    $attendees = $_POST['gibbonCalendarEventPersonID'] ?? [];

    if (empty($action) || ($action != 'Delete')) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }
    
    // Check if person specified
    if (empty($attendees)) {
        $URL .= '&return=error3';
        header("Location: {$URL}");
        exit;
    } 

    $event = $calendarEventGateway->getByID($gibbonCalendarEventID);
    if (empty($event)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    } 

    $partialFail = false;
    
    foreach ($attendees AS $gibbonCalendarEventPersonID) {
        $eventAttendee = $calendarEventPersonGateway->getByID($gibbonCalendarEventPersonID);
       
        if (empty($eventAttendee)) {
            $partialFail = true;
            continue;
        }

        if ($action == 'Delete') {
            $calendarEventPersonGateway->delete($gibbonCalendarEventPersonID);
        }
    }

    $URL .= $partialFail
        ? '&return=warning1'
        : '&return=success0';
    header("Location: {$URL}");
}
