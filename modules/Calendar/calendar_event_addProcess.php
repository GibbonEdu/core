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
use Gibbon\Domain\Calendar\CalendarGateway;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST, ['description' => 'HTML']);

$source = $_POST['source'] ?? '';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Calendar/calendar_event_add.php';
$URLSuccess = $source == 'ajax'
    ? $session->get('absoluteURL').'/index.php?q=/modules/Calendar/calendar_view.php'
    : $session->get('absoluteURL').'/index.php?q=/modules/Calendar/calendar_event_add.php';

if (isActionAccessible($guid, $connection2, '/modules/Calendar/calendar_event_add.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $partialFail = false;

    $calendarGateway = $container->get(CalendarGateway::class);
    $calendarEventGateway = $container->get(CalendarEventGateway::class);
    $calendarEventPersonGateway = $container->get(CalendarEventPersonGateway::class);

    $gibbonPersonIDOrganiser = $_POST['gibbonPersonIDOrganiser'] ?? '';

    $data = [
        'gibbonCalendarID'        => $_POST['gibbonCalendarID'] ?? '',
        'gibbonCalendarEventTypeID' => $_POST['gibbonCalendarEventTypeID'] ?? '',
        'name'                    => $_POST['name'] ?? '',
        'description'             => $_POST['description'] ?? '',
        'status'                  => $_POST['status'] ?? 'Tentative',
        'dateStart'               => $_POST['dateStart'] ?? '',
        'dateEnd'                 => $_POST['dateEnd'] ?? $_POST['dateStart'] ?? '',
        'allDay'                  => !empty($_POST['allDay']) ? $_POST['allDay'] : 'N',
        'timeStart'               => $_POST['timeStart'] ?? null,
        'timeEnd'                 => $_POST['timeEnd'] ?? null,   
        'locationType'            => $_POST['locationType'] ?? 'External',
        'locationDetail'          => $_POST['locationDetail'] ?? '',
        'locationURL'             => $_POST['locationURL'] ?? '',
        'gibbonSpaceID'           => $_POST['gibbonSpaceID'] ?? null,
        'gibbonPersonIDOrganiser' => $gibbonPersonIDOrganiser,
        'timestampCreated'        => date('Y-m-d H:i:s'),
        'gibbonPersonIDCreated'   => $session->get('gibbonPersonID') ?? '',
        'timestampModified'       => date('Y-m-d H:i:s'),
        'gibbonPersonIDModified'  => $session->get('gibbonPersonID') ?? '',
    ];
    
    // Validate the required values are present
    if (empty($data['name']) || empty($data['dateStart']) || empty($data['dateEnd'])) {
        header("Location: {$URL}&return=error1");
        exit;
    }

    // Get Calendars of the current school year
    $gibbonPersonIDEditor = Access::allows('Calendar', 'calendar_event_edit', 'Manage Events_all') ? null : $session->get('gibbonPersonID');
    $calendars = $calendarGateway->selectEditableCalendarsByPerson($session->get('gibbonSchoolYearID'), $gibbonPersonIDEditor)->fetchKeyPair();

    if (empty($calendars) || empty($calendars[$data['gibbonCalendarID']])) {
        header("Location: {$URL}&return=error0");
        exit;
    }

    // Create the record
    $gibbonCalendarEventID = $calendarEventGateway->insert($data);

    if (!$gibbonCalendarEventID) {
        header("Location: {$URL}&return=error2");
        exit;
    }

    // Scan through staff
    $staff = $_POST['staff'] ?? [];
    $role = $_POST['role'] ?? 'Other';

    if (!is_array($staff)) {
        $staff = [strval($staff)];
    }

    foreach ($staff as $staffPersonID) {
        $personData = [
            'gibbonCalendarEventID'  => $gibbonCalendarEventID,
            'gibbonPersonID'         => $staffPersonID,
            'role'                   => $role,
            'gibbonPersonIDCreated'  => $session->get('gibbonPersonID') ?? '',
            'timestampCreated'       => date('Y-m-d H:i:s'),
            'gibbonPersonIDModified' => $session->get('gibbonPersonID') ?? '',
            'timestampModified'      => date('Y-m-d H:i:s'),
        ];

        $gibbonCalendarEventPersonID = $calendarEventPersonGateway->insert($personData);
        $partialFail &= !$gibbonCalendarEventPersonID;
    }

    // Add the organiser to the particapants list
    $organiserData = [
            'gibbonCalendarEventID'  => $gibbonCalendarEventID,
            'gibbonPersonID'         => $gibbonPersonIDOrganiser,
            'role'                   => 'Organiser',
            'gibbonPersonIDCreated'  => $session->get('gibbonPersonID') ?? '',
            'timestampCreated'       => date('Y-m-d H:i:s'),
            'gibbonPersonIDModified' => $session->get('gibbonPersonID') ?? '',
            'timestampModified'      => date('Y-m-d H:i:s'),
        ];

    $gibbonCalendarEventPersonID = $calendarEventPersonGateway->insert($organiserData);
    $partialFail &= !$gibbonCalendarEventPersonID;

    $URLSuccess .= $partialFail
        ? "&return=warning1"
        : "&return=success0&editID=$gibbonCalendarEventID";
    header("Location: {$URLSuccess}");
}
