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
use Gibbon\Domain\Calendar\CalendarEventTypeGateway;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$gibbonCalendarEventTypeID = $_REQUEST['gibbonCalendarEventTypeID'] ?? null;

$URL = $session->get('absoluteURL')."/index.php?q=/modules/Calendar/calendar_eventTypes_manage_addEdit.php&gibbonCalendarEventTypeID=$gibbonCalendarEventTypeID";

if (isActionAccessible($guid, $connection2, '/modules/Calendar/calendar_eventTypes_manage_addEdit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $partialFail = false;

    $calendarEventTypeGateway = $container->get(CalendarEventTypeGateway::class);

    // Update the type
    $data = [
        'type'               => $_POST['type'] ?? '',
        'color'              => $_POST['color'] ?? '',
    ];

    // Validate the required values are present
    if (empty($data['type'])) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

     // Validate that this record is unique
    if (!$calendarEventTypeGateway->unique($data, ['type'], $gibbonCalendarEventTypeID)) {
        $URL .= '&return=error7';
        header("Location: {$URL}");
        exit;
    }

     // Create the record
    if (!empty($gibbonCalendarEventTypeID)) {
        $calendarEventTypeGateway->update($gibbonCalendarEventTypeID, $data);
    } else {
        $gibbonCalendarEventTypeID = $calendarEventTypeGateway->insert($data);
    }

    if (empty($gibbonCalendarEventTypeID)) {
        $URL .= "&return=error2";
        header("Location: {$URL}");
        exit;
    }

     $URL .= $partialFail
        ? "&return=warning1"
        : "&return=success0&editID=$gibbonCalendarEventTypeID";

    header("Location: {$URL}");
}
