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
use Gibbon\Domain\Calendar\CalendarEventGateway;
use Gibbon\Domain\Calendar\CalendarEventPersonGateway;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST, ['description' => 'HTML']);

$gibbonCalendarEventID = $_POST['gibbonCalendarEventID'] ?? '';

$URL = $session->get('absoluteURL')."/index.php?q=/modules/Calendar/calendar_event_edit.php&gibbonCalendarEventID=$gibbonCalendarEventID";

if (isActionAccessible($guid, $connection2, '/modules/Calendar/calendar_event_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $partialFail = false;

    $calendarEventGateway = $container->get(CalendarEventGateway::class);
    $calendarEventPersonGateway = $container->get(CalendarEventPersonGateway::class);

    if (!$calendarEventGateway->exists($gibbonCalendarEventID)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    }

    if (empty($_POST['start']) || empty($_POST['end'])) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    }

    $dateStart = new DateTime(trim($_POST['dateStart'], '"'));
    $dateEnd = new DateTime(trim($_POST['dateEnd'], '"'));

    if (empty($dateStart) || empty($dateEnd)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    }

    $gibbonPersonIDOrganiser = $_POST['gibbonPersonIDOrganiser'] ?? '';

    $data = [
        'gibbonCalendarID'        => $_POST['gibbonCalendarID'] ?? '',
        'gibbonCalendarEventTypeID' => $_POST['gibbonCalendarEventTypeID'] ?? '',
        'name'                    => $_POST['name'] ?? '',
        'description'             => $_POST['description'] ?? '',
        'status'                  => $_POST['status'] ?? 'Tentative',
        'dateStart'               => $dateStart->format('Y-m-d'),
        'dateEnd'                 => $dateEnd->format('Y-m-d'),
        'allDay'                  => !empty($_POST['allDay']) ? $_POST['allDay'] : 'N',
        'locationType'            => $_POST['locationType'] ?? 'External',
        'gibbonPersonIDOrganiser' => $gibbonPersonIDOrganiser,
        'timestampModified'       => date('Y-m-d H:i:s'),
        'gibbonPersonIDModified'  => $session->get('gibbonPersonID') ?? '',
    ];
    
    if ($data['allDay'] == 'N') {
        $data['timeStart'] = $_POST['timeStart'] ?? '';
        $data['timeEnd'] = $_POST['timeEnd'] ?? '';
    }

    if ($data['locationType'] == 'Internal') {
        $data['gibbonSpaceID'] = $_POST['gibbonSpaceID'] ?? '';
    } else {
        $data['locationDetail'] = $_POST['locationDetail'] ?? '';
        $data['locationURL'] = $_POST['locationURL'] ?? '';
    }

    // Validate the required values are present
    if (empty($data['name']) || empty($data['gibbonCalendarID']) || empty($data['gibbonCalendarEventTypeID']) || empty($data['locationType']) || empty($data['dateStart']) || empty($data['dateEnd'])) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    }

    // Update the record
    if (!$calendarEventGateway->update($gibbonCalendarEventID, $data)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
    }

     $organiser = $calendarEventPersonGateway->selectBy(['gibbonCalendarEventID' => $gibbonCalendarEventID, 'role' => 'Organiser', 'gibbonPersonID' => $gibbonPersonIDOrganiser])->fetch();

     if (empty($organiser)) {
         $organiserData = [
            'gibbonCalendarEventID' => $gibbonCalendarEventID,
            'gibbonPersonID'   => $gibbonPersonIDOrganiser,
            'role'    => 'Organiser',
            'gibbonPersonIDModified' => $session->get('gibbonPersonID') ?? '',
            'timestampModified' => date('Y-m-d H:i:s'),
            'timestampCreated'        => date('Y-m-d H:i:s'),
            'gibbonPersonIDCreated'   => $session->get('gibbonPersonID') ?? '',
        ];

        $inserted = $calendarEventPersonGateway->insertAndUpdate($organiserData, $organiserData);
     }

    $staff = $_POST['staff'] ?? [];
    $role = $_POST['role'] ?? 'Other';

    if (!is_array($staff)) {
        $staff = [strval($staff)];
    }

    foreach ($staff as $staffPersonID) {
        $personData = [
            'gibbonCalendarEventID' => $gibbonCalendarEventID,
            'gibbonPersonID'   => $staffPersonID,
            'role'    => $role,
            'gibbonPersonIDModified' => $session->get('gibbonPersonID') ?? '',
            'timestampModified' => date('Y-m-d H:i:s'),
            'timestampCreated'        => date('Y-m-d H:i:s'),
            'gibbonPersonIDCreated'   => $session->get('gibbonPersonID') ?? '',
        ];

        $inserted = $calendarEventPersonGateway->insertAndUpdate($personData, $personData);
        $partialFail &= !$inserted;
    }
    
    $URL .= $partialFail
        ? "&return=warning1"
        : "&return=success0&editID=$gibbonCalendarEventID";
    header("Location: {$URL}");    
}