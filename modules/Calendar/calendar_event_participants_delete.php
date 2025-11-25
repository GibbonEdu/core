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

use Gibbon\Forms\Prefab\DeleteForm;
use Gibbon\Domain\Calendar\CalendarEventPersonGateway;
use Gibbon\Support\Facades\Access;
use Gibbon\Domain\Calendar\CalendarEventGateway;

if (isActionAccessible($guid, $connection2, '/modules/Calendar/calendar_event_participants_delete.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $calendarEventGateway = $container->get(CalendarEventGateway::class);

    // Check if gibbonCalendarEventID and gibbonPersonID specified
    $gibbonPersonID = $_GET['gibbonPersonID'] ?? '';
    $gibbonCalendarEventID = $_GET['gibbonCalendarEventID'] ?? '';
    $gibbonCalendarEventPersonID = $_GET['gibbonCalendarEventPersonID'] ?? '';
    if ($gibbonCalendarEventPersonID == '' or $gibbonCalendarEventID == '' or $gibbonPersonID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {
        // Let's go!
        $participant = $container->get(CalendarEventPersonGateway::class)->getByID($gibbonCalendarEventPersonID);

        // Get event details
        $event = $calendarEventGateway->getEventDetailsByID($gibbonCalendarEventID, $session->get('gibbonPersonID'));
        if (empty($gibbonCalendarEventID) || empty($event)) {
            $page->addError(__('The specified record cannot be found.'));
            return;
        }

        // Check for access to edit this event
        $canEditEvent = $event['editor'] == 'Y' && Access::allows('Calendar', 'calendar_event_edit');
        if (!$canEditEvent && !Access::allows('Calendar', 'calendar_event_edit', 'Manage Events_all')) {
            $page->addError(__('The selected record does not exist, or you do not have access to it.'));
            return;
        }
        
        if (empty($participant)) {
            $page->addError(__('The specified record cannot be found.'));
            return;
        }
        
        $form = DeleteForm::createForm($session->get('absoluteURL').'/modules/Calendar/calendar_event_participants_deleteProcess.php', false, false);
        $form->addHiddenValue('gibbonCalendarEventPersonID', $gibbonCalendarEventPersonID);
        $form->addHiddenValue('gibbonPersonID', $gibbonPersonID);
        $form->addHiddenValue('gibbonCalendarEventID', $gibbonCalendarEventID);

        $form->addRow()->addConfirmSubmit();
        echo $form->getOutput();
    }
}
?>
