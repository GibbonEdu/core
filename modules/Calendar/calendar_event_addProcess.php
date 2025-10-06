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

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST, ['description' => 'HTML']);

$URL = $session->get('absoluteURL')."/index.php?q=/modules/Calendar/calendar_event_add.php";

if (isActionAccessible($guid, $connection2, '/modules/Calendar/calendar_event_add.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $partialFail = false;

    $calendarEventGateway = $container->get(CalendarEventGateway::class);

    echo '<pre>';
    print_r($_POST);
    echo '</pre>';
    exit();

    if (empty($_POST['dateStart']) || empty($_POST['dateEnd'])) return;

    $dateStart = new DateTime(trim($_POST['dateStart'], '"'));
    $dateEnd = new DateTime(trim($_POST['dateEnd'], '"'));

    if (empty($dateStart) || empty($dateEnd)) return;

    $data = [
        'gibbonCalendarID'        => $_POST['gibbonCalendarID'] ?? '',
        'gibbonCalendarEventTypeID' => $_POST['gibbonCalendarEventTypeID'] ?? '',
        'name'                    => $_POST['name'] ?? '',
        'description'             => $_POST['description'] ?? '',
        'status'                  => $_POST['status'] ?? 'Tentative',
        'dateStart'               => $dateStart->format('Y-m-d'),
        'dateEnd'                 => $dateEnd->format('Y-m-d'),
        'allDay'                  => !empty($_POST['allDay']) ? ($_POST['allDay'] == 'true' ? 'Y' : 'N') :  'Y',
        'locationType'            => $_POST['locationType'] ?? 'Internal',
        'timestampCreated'        => date('Y-m-d H:i:s'),
        'gibbonPersonIDCreated'   => $session->get('gibbonPersonID'),
        'timestampModified'       => date('Y-m-d H:i:s'),
        'gibbonPersonIDModified'  => $session->get('gibbonPersonID'),
        'gibbonPersonIDOrganiser' => $_POST['gibbonPersonIDOrganiser'] ?? '',
    ];

    if ($data['allDay'] == 'N') {
        $data['timeStart'] = $dateStart->format('H:i:s');
        $data['timeEnd'] = $dateEnd->format('H:i:s');
    }

    if ($data['locationType'] == 'Internal') {
        $data['gibbonSpaceID'] = $_POST['gibbonSpaceID'] ?? '';
    } else {
        $data['locationDetail'] = $_POST['locationDetail'] ?? '';
        $data['locationURL'] = $_POST['locationURL'] ?? '';
    }

    // Validate the required values are present
    if (empty($data['name']) || empty($data['dateStart']) || empty($data['dateEnd'])) {
        return;
    }

    // Create the record
    $gibbonCalendarEventID = $calendarEventGateway->insert($data);
}
