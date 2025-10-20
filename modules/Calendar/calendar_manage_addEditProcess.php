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
use Gibbon\Domain\Calendar\CalendarGateway;
use Gibbon\Domain\Calendar\CalendarEditorGateway;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST, ['summary' => 'HTML']);

$gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? $session->get('gibbonSchoolYearID');
$gibbonCalendarID = $_REQUEST['gibbonCalendarID'] ?? null;

$URL = $session->get('absoluteURL')."/index.php?q=/modules/Calendar/calendar_manage_addEdit.php&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonCalendarID=$gibbonCalendarID";

if (isActionAccessible($guid, $connection2, '/modules/Calendar/calendar_manage_addEdit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $partialFail = false;

    $calendarGateway = $container->get(CalendarGateway::class);
    $editorGateway = $container->get(CalendarEditorGateway::class);

    $data = [
        'gibbonSchoolYearID' => $gibbonSchoolYearID,
        'name'               => $_POST['name'] ?? '',
        'description'        => $_POST['description'] ?? '',
        'color'              => $_POST['color'] ?? '',
        'summary'            => $_POST['summary'] ?? '',
        'public'             => $_POST['public'] ?? 'N',
        'viewableStaff'      => $_POST['viewableStaff'] ?? 'N',
        'viewableStudents'   => $_POST['viewableStudents'] ?? 'N',
        'viewableParents'    => $_POST['viewableParents'] ?? 'N',
        'viewableOther'      => $_POST['viewableOther'] ?? 'N',
    ];

    // Validate the required values are present
    if (empty($data['name'])) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Validate that this record is unique
    if (!$calendarGateway->unique($data, ['name', 'gibbonSchoolYearID'], $gibbonCalendarID)) {
        $URL .= '&return=error7';
        header("Location: {$URL}");
        exit;
    }

    // Create the record
    if (!empty($gibbonCalendarID)) {
        $calendarGateway->update($gibbonCalendarID, $data);
    } else {
        $gibbonCalendarID = $calendarGateway->insert($data);
    }

    if (empty($gibbonCalendarID)) {
        $URL .= "&return=error2";
        header("Location: {$URL}");
        exit;
    }

    // Update the editors
    $editors = $_POST['editors'] ?? '';
    $editorIDs = [];
    foreach ($editors as $person) {
        $editorData = [
            'gibbonCalendarID' => $gibbonCalendarID,
            'gibbonPersonID'   => $person['gibbonPersonID'],
            'editAllEvents'    => $person['editAllEvents'] ?? 'N',
        ];

        $gibbonCalendarEditorID = $person['gibbonCalendarEditorID'] ?? '';

        if (!empty($gibbonCalendarEditorID)) {
            $partialFail &= !$editorGateway->update($gibbonCalendarEditorID, $editorData);
        } else {
            $gibbonCalendarEditorID = $editorGateway->insert($editorData);
            $partialFail &= !$gibbonCalendarEditorID;
        }

        $editorIDs[] = str_pad($gibbonCalendarEditorID, 10, '0', STR_PAD_LEFT);
    }

    // Cleanup editor that have been deleted
    $editorGateway->deleteEditorsNotInList($gibbonCalendarID, $editorIDs);

    $URL .= $partialFail
        ? "&return=warning1"
        : "&return=success0&editID=$gibbonCalendarID";

    header("Location: {$URL}");
}
