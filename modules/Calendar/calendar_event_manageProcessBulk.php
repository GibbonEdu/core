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

use Gibbon\Domain\Calendar\CalendarEventGateway;
use Gibbon\Domain\Calendar\CalendarEventPersonGateway;

include '../../gibbon.php';

$action = $_POST['action'] ?? '';


$URL = $session->get('absoluteURL').'/index.php?q=/modules/Calendar/calendar_event_manage.php';

if (isActionAccessible($guid, $connection2, '/modules/Calendar/calendar_event_manage.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    $events = $_POST['gibbonCalendarEventID'] ?? [];
    $calendarEventGateway = $container->get(CalendarEventGateway::class);

    // Proceed!
    if (count($events) < 1) {
        $URL .= '&return=error3';
        header("Location: {$URL}");
    } else {
        $partialFail = false;

        if ($action == 'Duplicate' or $action == 'DuplicateParticipants') {
            foreach ($events AS $gibbonCalendarEventID) { // For every event to be duplicated
                // Check existence of event and fetch details
                $calendarEvent = $calendarEventGateway->getByID($gibbonCalendarEventID);

                if (empty($calendarEvent)) {
                    $partialFail = true;
                    continue;
                }

                $name = $calendarEvent['name'];
                $name .= ' (Copy)';

                // Write the duplicate to the database
                $data = ['gibbonCalendarID' => $calendarEvent['gibbonCalendarID'], 'gibbonCalendarEventTypeID' => $calendarEvent['gibbonCalendarEventTypeID'], 'name' => $name, 'description' => $calendarEvent['description'], 'status' => $calendarEvent['status'], 'allDay' => $calendarEvent['allDay'], 'dateStart' => $calendarEvent['dateStart'], 'dateEnd' => $calendarEvent['dateEnd'], 'timeStart' => $calendarEvent['timeStart'], 'timeEnd' => $calendarEvent['timeEnd'], 'locationType' => $calendarEvent['locationType'], 'locationDetail' => $calendarEvent['locationDetail'], 'locationURL' => $calendarEvent['locationURL'], 'gibbonSpaceID' => $calendarEvent['gibbonSpaceID'], 'foreignTable' => $calendarEvent['foreignTable'], 'foreignTableID' => $calendarEvent['foreignTableID'], 'timestampCreated' => date('Y-m-d H:i:s'), 'timestampModified' => date('Y-m-d H:i:s'), 'gibbonPersonIDCreated' => $session->get('gibbonPersonID'), 'gibbonPersonIDModified' => $session->get('gibbonPersonID'), 'gibbonPersonIDOrganiser' => $calendarEvent['gibbonPersonIDOrganiser']];

                $inserted = $calendarEventGateway->insert($data);
                // $gibbonCalendarEventID = str_pad($connection2->lastInsertID(), 8, '0', STR_PAD_LEFT);

                if ($action == 'DuplicateParticipants') {
                    $calendarEventPersonGateway = $container->get(CalendarEventPersonGateway::class);
                    $participants = $calendarEventPersonGateway->selectBy(['gibbonCalendarEventID' => $gibbonCalendarEventID]);
                    
                    while ($participant = $participants->fetch()) {
                        $data = ['gibbonCalendarEventID' => $inserted, 'gibbonPersonID' => $participant['gibbonPersonID'], 'role' => $participant['role'], 'timestampCreated' => date('Y-m-d H:i:s'), 'timestampModified' => date('Y-m-d H:i:s'), 'gibbonPersonIDCreated' => $session->get('gibbonPersonID'), 'gibbonPersonIDModified' => $session->get('gibbonPersonID')];
                        $insertedParticipant = $calendarEventPersonGateway->insert($data);
                    }
                }
            }
        }
    
        if ($partialFail == true) {
            $URL .= '&return=warning1';
            header("Location: {$URL}");
        } else {
            $URL .= '&return=success0';
            header("Location: {$URL}");
        }
    }
}