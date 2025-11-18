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
use Gibbon\Domain\User\UserGateway;
use Gibbon\Domain\School\FacilityGateway;
use Gibbon\Domain\Calendar\CalendarGateway;
use Gibbon\Domain\Calendar\CalendarEventGateway;
use Gibbon\Domain\Calendar\CalendarEventTypeGateway;
use Gibbon\Domain\Calendar\CalendarEventPersonGateway;

if (!isActionAccessible($guid, $connection2, '/modules/Calendar/calendar_event_view.php')) {
	$page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__('Manage Events'), 'calendar_event_manage.php')
        ->add(__('View Event'));

    $gibbonCalendarEventID = $_GET['gibbonCalendarEventID'] ?? '';

    $calendarGateway = $container->get(CalendarGateway::class);
    $calendarEventGateway = $container->get(CalendarEventGateway::class);
    $calendarEventPersonGateway = $container->get(CalendarEventPersonGateway::class);
    $calendarEventTypeGateway = $container->get(CalendarEventTypeGateway::class);

    // Get event details
    $event = $calendarEventGateway->getByID($gibbonCalendarEventID);
    if (!empty($gibbonCalendarEventID) && empty($event)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }
    // Get organiser name
    $organiser = $container->get(UserGateway::class)->getByID($event['gibbonPersonIDOrganiser'], ['preferredName', 'surname']);
    $event['preferredName'] = $organiser['preferredName'];
    $event['surname'] = $organiser['surname'];

    $isEventOwner = $session->get('gibbonPersonID') == $event['gibbonPersonIDCreated'] || $session->get('gibbonPersonID') == $event['gibbonPersonIDOrganiser'];

    // DATA TABLE TO VIEW EVENT DETAILS
    $table = DataTable::createDetails('viewEvent');

    $table->setTitle(__('View'));

    if (Access::allows('Calendar', 'calendar_event_edit') && $isEventOwner) {
        $table->addHeaderAction('edit', __('Edit Event'))
            ->setURL('/modules/Calendar/calendar_event_edit.php')
            ->addParam('gibbonCalendarEventID', $gibbonCalendarEventID)
            ->displayLabel();
    }

    if (Access::allows('Calendar', 'calendar_event_edit') && $isEventOwner) {
        $table->addHeaderAction('notify', __('Notify Staff'))
            ->setURL('/modules/Calendar/calendar_event_notify.php')
            ->addParam('gibbonCalendarEventID', $gibbonCalendarEventID)
            ->setIcon('notify')
            ->displayLabel();
    }

    $table->addColumn('gibbonCalendarID', __('Calendar'))
            ->format(function($event) use ($calendarGateway) {
                if (isset($event['gibbonCalendarID'])) {
                    $calendar = $calendarGateway->getByID($event['gibbonCalendarID']);
                    $output = '';
                    if (!empty($calendar)) {
                        $output .= __($calendar['name']);
                    }
                    return $output;
                }
            });

    $table->addColumn('gibbonCalendarEventType', __('Event Type'))
            ->format(function($event) use ($calendarEventTypeGateway) {
                if (isset($event['gibbonCalendarEventTypeID'])) {
                    $gibbonCalendarEventType = $calendarEventTypeGateway->getByID($event['gibbonCalendarEventTypeID']);
                    $output = '';
                    if (!empty($gibbonCalendarEventType)) {
                        $output .= __($gibbonCalendarEventType['type']);
                    }
                    return $output;
                }
            });

    $table->addColumn('organiser', __('Organiser'))
        ->format(Format::using('nameLinked', ['gibbonPersonIDOrganiser', '', 'preferredName', 'surname', 'Staff', false, true]));

    $table->addColumn('name', __('Name'));

    $table->addColumn('status', __('Status'));

    $col = $table->addColumn('School Information', __('Event Information'));

    $col->addColumn('dateStart', __('Start Date'))->format(Format::using('date', 'dateStart'));

    $col->addColumn('dateEnd', __('End Date'))->format(Format::using('date', 'dateEnd'));

    if ($event['allDay'] == 'N') {
        $col->addColumn('timeStart', __('Start Time'))->format(Format::using('time', 'timeStart'));
        $col->addColumn('timeEnd', __('End Time'))->format(Format::using('time', 'timeEnd'));
    } else {
         $col->addColumn('allDay', __('When'))->format(function() {
            return __('All Day');
        });
    }
    
    $col = $table->addColumn('Location Information', __('Location'));

    $col->addColumn('locationType', __('Location Type'));

    $col->addColumn('location', __('Location'))->format(function($event) use ($container) {
        $output = '';
        if ($event['locationType'] == 'Internal') {
            if (!empty($event['gibbonSpaceID'])) {
                $space = $container->get(FacilityGateway::class)->getByID($event['gibbonCalendarID']);
                if (!empty($space)) {
                    $output .= __($space['name']. ' - ' . $space['type']);
                }
                return $output;
            } 
        } else {
            if (!empty($event['locationDetail'])) {
                $output .= ($event['locationDetail']);
            }
        }
        return $output;
    });

    $col->addColumn('locationURL', __('Location URL'))
        ->format(Format::using('link', ['locationURL', 'View Link']));

    $table->addColumn('description', __('Description'));

    echo $table->render([$event]);

    // QUERY FOR DATATABLE
    $criteria = $calendarEventPersonGateway->newQueryCriteria()
        ->sortBy(['role','surname', 'preferredName'])
        ->fromPOST();
        
    $participants = $calendarEventPersonGateway->queryAllEventParticipants($criteria, $gibbonCalendarEventID);

    // DATA TABLE FOR ALL PARTICIPANTS
    $table = DataTable::createPaginated('participants', $criteria)->withData($participants);
    $table->setTitle(__('All Participants'));

    if (Access::allows('Calendar', 'calendar_event_participants')) {
        $table->addHeaderAction('participants', __('Participants'))
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

    $table->addColumn('role', __('Event Role'));

    echo $table->getOutput();
}
?>