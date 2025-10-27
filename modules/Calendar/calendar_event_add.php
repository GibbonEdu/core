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

use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Domain\Calendar\CalendarGateway;
use Gibbon\Domain\Calendar\CalendarEventTypeGateway;

if (isActionAccessible($guid, $connection2, '/modules/Calendar/calendar_event_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__('Manage Events'), 'calendar_event_manage.php')
        ->add(__('Add Event'));

    $calendarGateway = $container->get(CalendarGateway::class);
    $calendarEventTypeGateway = $container->get(CalendarEventTypeGateway::class);

    // FORM
    $form = Form::create('event', $session->get('absoluteURL').'/modules/Calendar/calendar_event_addProcess.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('address', $session->get('address'));

    $form->addRow()->addHeading(__('Basic Information'));

    // Get Calendars of the current school year
    $calendars = $calendarGateway->selectCalendarsBySchoolYear($session->get('gibbonSchoolYearID'))->fetchKeyPair();
    $row = $form->addRow();
        $row->addLabel('gibbonCalendarID', __('Calendar'));
        $row->addSelect('gibbonCalendarID')
            ->fromArray($calendars)
            ->placeholder()
            ->required();

    // Get all event types
    $types = $calendarEventTypeGateway->selectAllEventTypes()->fetchKeyPair();
    $row = $form->addRow();
        $row->addLabel('gibbonCalendarEventTypeID', __('Event Type'));
        $row->addSelect('gibbonCalendarEventTypeID')
            ->fromArray($types)
            ->placeholder()
            ->required();

    $gibbonPersonID = $session->get('gibbonPersonID');
    $row = $form->addRow();
        $row->addLabel('gibbonPersonIDOrganiser', __('Organiser'));
        $row->addSelectStaff('gibbonPersonIDOrganiser')->placeholder()->required()->selected($gibbonPersonID);

    $row = $form->addRow();
        $row->addLabel('name', __('Event Name'));
        $row->addTextField('name')->required()->maxLength(120);

    $statusList = [
        'Confirmed' => __('Confirmed'),
        'Tentative' => __('Tentative'),
    ];

    $row = $form->addRow();
        $row->addLabel('status', __('Event Status'));
        $row->addSelect('status')
            ->fromArray($statusList)
            ->required();

    $col = $form->addRow()->addColumn();
        $col->addLabel('description', __('Description'));
        $col->addEditor('description', $guid);

    $form->addRow()->addHeading(__('Event Details'));

    // Event Location
    $row = $form->addRow();
        $row->addLabel('locationType', __('Location Type'));
        $row->addSelect('locationType')->fromArray(['Internal' => __('Internal'), 'External' => __('External')])->required()->placeholder();

    $form->toggleVisibilityByClass('internal')->onSelect('locationType')->when('Internal');

    $row = $form->addRow()->addClass('internal');
        $row->addLabel('location', __('Location'));
        $row->addSelectSpace('gibbonSpaceID');

    $form->toggleVisibilityByClass('external')->onSelect('locationType')->when('External');

    $row = $form->addRow()->addClass('external');
        $row->addLabel('locationDetail', __('Location Details'));
        $row->addTextField('locationDetail');

    $row = $form->addRow()->addClass('external');
        $row->addLabel('locationURL', __('Location URL'));
        $row->addTextField('locationURL')->maxLength(255);

    // Event Dates
    $date = $_GET['date'] ?? '';
    $row = $form->addRow();
        $row->addLabel('dateStart', __('Start Date'));
        $row->addDate('dateStart')->chainedTo('dateEnd')->required()->setValue($date);

    $row = $form->addRow();
        $row->addLabel('dateEnd', __('End Date'));
        $row->addDate('dateEnd')->chainedFrom('dateStart')->required()->setValue($date);

    $row = $form->addRow();
        $row->addLabel('allDay', __('When'));
        $row->addCheckbox('allDay')
            ->description(__('All Day'))
            ->inline()
            ->setValue('Y')
            ->checked('Y')
            ->wrap('<div class="standardWidth floatRight">', '</div>');

    $form->toggleVisibilityByClass('timeOptions')->onCheckbox('allDay')->whenNot('Y');

    $row = $form->addRow()->addClass('timeOptions');
        $row->addLabel('time', __('Time'));
        $col = $row->addColumn('timeStart')->addClass('right inline gap-2');
        $col->addTime('timeStart')
            ->setClass('flex-1')
            ->required();
        $col->addTime('timeEnd')
            ->chainedTo('timeStart')
            ->setClass('flex-1')
            ->required();

    // STAFF
    $form->addRow()->addHeading(__('Teachers'));

    $row = $form->addRow();
        $row->addLabel('staff', __('Staff'));
        $row->addSelectUsers('staff', $session->get('gibbonSchoolYearID'), ['includeStaff' => true])->selectMultiple();

    $row = $form->addRow();
        $row->addLabel('role', 'Role');
        $row->addSelect('role')
            ->fromArray([
                'Organiser' => __('Organiser'),
                'Coach'     => __('Coach'),
                'Assistant' => __('Assistant'),
                'Other'     => __('Other'), 
            ]);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}