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
use Gibbon\Support\Facades\Access;

if (isActionAccessible($guid, $connection2, '/modules/Calendar/calendar_event_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__('Manage Events'), 'calendar_event_manage.php')
        ->add(__('Add Event'));

    if (isset($_GET['editID'])) {
        $page->return->setEditLink($session->get('absoluteURL').'/index.php?q=/modules/Calendar/calendar_event_edit.php&gibbonCalendarEventID='.$_GET['editID']);
    }

    $calendarGateway = $container->get(CalendarGateway::class);
    $calendarEventTypeGateway = $container->get(CalendarEventTypeGateway::class);

    // Get values from post, coming from FullCalendar
    $source = $_GET['source'] ?? '';
    $date = $_GET['date'] ?? '';
    $allDay = $_GET['allDay'] ?? '';

    $dateStart = $date;
    $dateEnd = $date;

    // From FullCalendar date selection
    if (!empty($_GET['start']) && !empty($_GET['end'])) {
        $dateStart = date('Y-m-d', strtotime($_GET['start']));
        $dateEnd = date('Y-m-d', strtotime($_GET['end'])-86400);
    }
    
    // Get Calendars of the current school year
    $gibbonPersonIDEditor = Access::allows('Calendar', 'calendar_event_edit', 'Manage Events_all') ? null : $session->get('gibbonPersonID');
    $calendars = $calendarGateway->selectEditableCalendarsByPerson($session->get('gibbonSchoolYearID'), $gibbonPersonIDEditor)->fetchKeyPair();

    if (empty($calendars)) {
        $page->addError(__('You do not have access to this action.'));
        return;
    }

    // FORM
    $form = Form::create('event', $session->get('absoluteURL').'/modules/Calendar/calendar_event_addProcess.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('source', $source);

    if ($source == 'ajax') $form->removeMeta();

    $form->addSection('Basic Information', __('Basic Information'));

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

    // Event Dates
    $form->addSection('Event Details', __('Event Details'));

    $row = $form->addRow();
        $row->addLabel('dateStart', __('Date'));

        $row->addDate('dateStart')->chainedTo('dateEnd')->required()->setValue($dateStart);
        $row->addDate('dateEnd')->chainedFrom('dateStart')->setValue($dateEnd);

        $row->addCheckbox('allDay')
            ->description(__('All Day'))
            ->setOuterClass('w-min')
            ->inline()
            ->setValue('Y')
            ->checked('Y');

    $form->toggleVisibilityByClass('timeOptions')->onCheckbox('allDay')->whenNot('Y');

    $row = $form->addRow()->addClass('timeOptions');
        $row->addLabel('time', __('Time'));
        $row->addTime('timeStart')
            ->required();
        $row->addTime('timeEnd')
            ->chainedTo('timeStart')
            ->required();

    // Description
    $form->addSection('Description', __('Description'))->closed();

    $form->addRow()->addEditor('description', $guid)->setRows(5);


    // Event Location
    $form->addSection('Location', __('Location'))->closed();

    $row = $form->addRow();
        $row->addLabel('locationType', __('Location Type'));
        $row->addSelect('locationType')->fromArray(['Internal' => __('Internal'), 'External' => __('External')])->placeholder();

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
        $row->addUrl('locationURL')->maxLength(255);

    // STAFF
    $form->addSection('Staff', __('Staff'))->closed();

    $gibbonPersonID = $session->get('gibbonPersonID');
    $row = $form->addRow();
        $row->addLabel('gibbonPersonIDOrganiser', __('Organiser'));
        $row->addSelectStaff('gibbonPersonIDOrganiser')->placeholder()->required()->selected($gibbonPersonID);


    $row = $form->addRow();
        $row->addLabel('staff', __('Add Staff'));
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

    $form->addSection($source == 'ajax' ? 'ajax' : 'submit')->addSubmit();

    echo $form->getOutput();
}
