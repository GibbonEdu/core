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
use Gibbon\Services\Format;
use Gibbon\Domain\Calendar\CalendarEventGateway;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST, ['summary' => 'HTML']);

$gibbonCalendarEventID = $_REQUEST['gibbonCalendarEventID'] ?? null;

$URL = $session->get('absoluteURL')."/index.php?q=/modules/Calendar/calendar_event_addEdit.php&gibbonCalendarEventID=$gibbonCalendarEventID";

if (isActionAccessible($guid, $connection2, '/modules/Calendar/calendar_manage.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $partialFail = false;

    $eventGateway = $container->get(CalendarEventGateway::class);
    $values = $eventGateway->getByID($gibbonCalendarEventID);

    echo '<pre>';
    print_r($_POST);
    echo '</pre>';

    if (empty($_POST['start']) || empty($_POST['end'])) return;

    $dateStart = new DateTime(trim($_POST['start'], '"'));
    $dateEnd = new DateTime(trim($_POST['end'], '"'));

    if (empty($dateStart) || empty($dateEnd)) return;

    $data = [
        'name'                    => $_POST['title'] ?? $values['name'] ?? '',
        'dateStart'               => $dateStart->format('Y-m-d'),
        'dateEnd'                 => $dateEnd->format('Y-m-d'),
        'allDay'                  => !empty($_POST['allDay']) ? ($_POST['allDay'] == 'true' ? 'Y' : 'N') : ($values['alLDay'] ?? 'Y'),
        'timestampModified'       => date('Y-m-d H:i:s'),
        'gibbonPersonIDModified'  => $session->get('gibbonPersonID'),
        'gibbonPersonIDOrganiser' => $values['gibbonPersonIDOrganiser'] ?? $session->get('gibbonPersonID'),
    ];

    if ($data['allDay'] == 'N') {
        $data['timeStart'] = $dateStart->format('H:i:s');
        $data['timeEnd'] = $dateEnd->format('H:i:s');
    }

    if (empty($gibbonCalendarEventID)) {
        $data['timestampCreated'] = date('Y-m-d H:i:s');
        $data['gibbonPersonIDCreated'] = $session->get('gibbonPersonID');
    }

    // Validate the required values are present
    if (empty($data['name']) || empty($data['dateStart']) || empty($data['dateEnd'])) {
        return;
    }

    // Create the record
    if (!empty($gibbonCalendarEventID)) {
        $eventGateway->update($gibbonCalendarEventID, $data);
    } else {
        $gibbonCalendarEventID = $eventGateway->insert($data);
    }

    echo '<pre>';
    print_r($data);
    echo '</pre>';
}
