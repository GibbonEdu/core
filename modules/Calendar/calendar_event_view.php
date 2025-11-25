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
use Gibbon\Tables\DataTable;
use Gibbon\Support\Facades\Access;
use Gibbon\Domain\Calendar\CalendarEventGateway;
use Gibbon\Domain\Calendar\CalendarEventPersonGateway;

if (!isActionAccessible($guid, $connection2, '/modules/Calendar/calendar_event_view.php')) {
	$page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__('Manage Events'), 'calendar_event_manage.php')
        ->add(__('View Event'));

    $gibbonCalendarEventID = $_GET['gibbonCalendarEventID'] ?? '';

    $calendarEventGateway = $container->get(CalendarEventGateway::class);
    $calendarEventPersonGateway = $container->get(CalendarEventPersonGateway::class);

    // Get event details
    $event = $calendarEventGateway->getEventDetailsByID($gibbonCalendarEventID, $session->get('gibbonPersonID'));
    if (empty($gibbonCalendarEventID) || empty($event)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    $canEditEvent = $event['editor'] == 'Y' && Access::allows('Calendar', 'calendar_event_edit');

    // DATA TABLE TO VIEW EVENT DETAILS
    $table = DataTable::createDetails('viewEvent');

    $table->setTitle(__('View'));

    if ($canEditEvent) {
        $table->addHeaderAction('edit', __('Edit Event'))
            ->setURL('/modules/Calendar/calendar_event_edit.php')
            ->addParam('gibbonCalendarEventID', $gibbonCalendarEventID)
            ->displayLabel();

        $table->addHeaderAction('notify', __('Notify Staff'))
            ->setURL('/modules/Calendar/calendar_event_notify.php')
            ->addParam('gibbonCalendarEventID', $gibbonCalendarEventID)
            ->setIcon('notify')
            ->displayLabel();
    }

    $table->addColumn('name', __('Event Name'))->addClass('col-span-2');

    $table->addColumn('status', __('Event Status'));

    $table->addColumn('calendarName', __('Calendar'));

    $table->addColumn('eventType', __('Event Type'));

    $table->addColumn('organiser', __('Organiser'))
        ->format(Format::using('nameLinked', ['gibbonPersonIDOrganiser', '', 'organiserPreferredName', 'organiserSurname', 'Staff', false, true]));

    if (!empty($event['description'])) {
        $table->addColumn('description', __('Description'))->addClass('col-span-3');
    }

    $col = $table->addColumn('Event Details', __('Event Details'));

    $col->addColumn('dateStart', __('Date'))->format(Format::using('dateRange', ['dateStart', 'dateEnd']));

    $col->addColumn('allDay', __('When'))
        ->format(function($values) {
            if ($values['allDay'] == 'N') return Format::timeRange($values['timeStart'], $values['timeEnd']);
            return __('All Day');
        });

    if (!empty($event['locationType'])) {
        $col->addColumn('location', __('Location'))->format(function($values)  {
            if ($values['locationType'] == 'Internal') {
                return $values['space']; 
            }

            return !empty($values['locationURL'])
                ? Format::link($values['locationURL'], $values['locationDetail'])
                : $values['locationDetail'];
        });
    }

    echo $table->render([$event]);

    // QUERY FOR DATATABLE
    $criteria = $calendarEventPersonGateway->newQueryCriteria()
        ->sortBy('role', 'DESC')
        ->sortBy(['roleCategory', 'surname', 'preferredName'])
        ->fromPOST();
        
    $participants = $calendarEventPersonGateway->queryAllEventParticipants($criteria, $gibbonCalendarEventID);

    // DATA TABLE FOR ALL PARTICIPANTS
    $table = DataTable::createPaginated('participants', $criteria)->withData($participants);
    $table->setTitle(__('All Participants & Staff'));

    if ($canEditEvent && Access::allows('Calendar', 'calendar_event_participants')) {
        $table->addHeaderAction('participants', __('Edit Participants'))
            ->setURL('/modules/Calendar/calendar_event_participants.php')
            ->addParam('gibbonCalendarEventID', $gibbonCalendarEventID)
            ->setIcon('attendance')
            ->displayLabel();
    }

    $table->addColumn('image_240', __('Photo'))
        ->context('primary')
        ->width('7%')
        ->notSortable()
        ->format(Format::using('userPhoto', ['image_240', 'xs']));

    $table->addColumn('name', __('Name'))
        ->sortable(['surname', 'preferredName'])
        ->format(Format::using('nameLinked', ['gibbonPersonID', '', 'preferredName', 'surname', 'roleCategory', true, true]));

    $table->addColumn('roleCategory', __('Role'));

    $table->addColumn('formGroup', __('Form Group'));

    $table->addColumn('role', __('Event Role'))
        ->format(function ($values) {
            $status = $values['role'] != 'Attendee' ? 'message' : 'dull';
            return Format::tag(__($values['role']), $status);
        });

    echo $table->getOutput();
}
?>
