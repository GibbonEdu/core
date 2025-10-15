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

use Gibbon\Forms\Form;
use Gibbon\Domain\DataSet;
use Gibbon\Services\Format;
use Gibbon\Domain\User\UserGateway;
use Gibbon\Forms\DatabaseFormFactory;
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

    $calendarEventGateway = $container->get(CalendarEventGateway::class);
    $calendarEventPersonGateway = $container->get(CalendarEventPersonGateway::class);
    $calendarGateway = $container->get(CalendarGateway::class);
    $calendarEventTypeGateway = $container->get(CalendarEventTypeGateway::class);

     // Get event details
    $values = $calendarEventGateway->getByID($gibbonCalendarEventID);
    if (!empty($gibbonCalendarEventID) && empty($values)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }
    $organiser = $container->get(UserGateway::class)->getByID($values['gibbonPersonIDOrganiser'], ['preferredName', 'surname']);

    $form = Form::create('viewEvent', '');
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHeaderAction('enrolment', __('Enrolment'))
    ->setURL('/modules/Calendar/calendar_event_enrolment.php')
    ->addParam('gibbonCalendarEventID', $gibbonCalendarEventID)
      ->setIcon('attendance')
    ->displayLabel();

    $form->addRow()->addHeading(__('Basic Information'));

    // Get Calendars of the current school year
    $calendars = $calendarGateway->selectCalendarsBySchoolYear($session->get('gibbonSchoolYearID'))->fetchKeyPair();
    $row = $form->addRow();
        $row->addLabel('gibbonCalendarID', __('Calendar'));
        $row->addSelect('gibbonCalendarID')
            ->fromArray($calendars)
            ->readonly();

    // Get all event types
    $types = $calendarEventTypeGateway->selectAllEventTypes()->fetchKeyPair();
    $row = $form->addRow();
        $row->addLabel('gibbonCalendarEventTypeID', __('Event Type'));
        $row->addSelect('gibbonCalendarEventTypeID')
            ->fromArray($types)
            ->readonly();

    $row = $form->addRow();
        $row->addLabel('gibbonPersonIDOrganiser', __('Organiser'));
        $row->addContent(Format::nameLinked($values['gibbonPersonIDOrganiser'], '', $organiser['preferredName'], $organiser['surname'], 'Staff', false, true))
            ->wrap('<div class="text-left w-full text-sm">', '</div>');

    $row = $form->addRow();
        $row->addLabel('name', __('Name'));
        $row->addTextField('name')->readonly();

    $row = $form->addRow();
        $row->addLabel('status', __('Status'));
        $row->addTextField('status')->readonly();

    $row = $form->addRow();
        $row->addLabel('dateStart', __('Start Date'));
        $row->addDate('dateStart')
            ->readonly();

    $row = $form->addRow();
        $row->addLabel('dateEnd', __('End Date'));
        $row->addDate('dateEnd')
            ->readonly();

    if ($values['allDay'] == 'N') {
        $row =  $form->addRow();
            $row->addLabel('timeStart', __('Start Time'));
            $row->addTime('timeStart')->readonly();

        $row =  $form->addRow();
            $row->addLabel('timeEnd', __('End Time'));
            $row->addTime('timeEnd')->readonly();
    } else {
        $row = $form->addRow();
            $row->addLabel('allDay', __('When'));
            $row->addCheckbox('allDay')
                ->description(__('All Day'))
                ->inline()
                ->setValue('Y')
                ->checked('Y')
                ->wrap('<div class="standardWidth floatRight">', '</div>')
                ->readonly();
    }
    
    $row = $form->addRow();
        $row->addLabel('locationType', __('Location Type'));
        $row->addTextField('locationType')
            ->readonly();

     if ($values['locationType'] == 'Internal') {
        $row = $form->addRow();
            $row->addLabel('gibbonSpaceID', __('Location'));
            $row->addSelectSpace('gibbonSpaceID')->readonly();
     } else {
        $row = $form->addRow();
            $row->addLabel('locationDetail', __('Location Details'));
            $row->addTextField('locationDetail')->readonly();

        if (!empty($values['locationURL'])) {
            $row->addLabel('locationURL', __('Location URL'));
            $row->addURL('locationURL')->readonly();
        }
    }

    $row = $form->addRow();
        $col = $row->addColumn();
        $col->addLabel('description', __('Description'));
        $col->addContent('description');

    $form->loadAllValuesFrom($values);
    echo $form->getOutput();

    // QUERY FOR DATATABLE
    $criteria = $calendarEventPersonGateway->newQueryCriteria()
        ->sortBy(['role','surname', 'preferredName'])
        ->fromPOST();
        
    $participants = $calendarEventPersonGateway->queryEventEnrolment($criteria, $gibbonCalendarEventID);

    // BULK ACTION FORM
    $form = Form::create('participants', '');

    // DATA TABLE FOR ALL PARTICIPANTS
    $table = $form->addRow()->addDataTable('participants', $criteria)->withData($participants);
    $table->setTitle(__('All Participants'));

    $table->addColumn('name', __('Name'))
        ->sortable(['surname', 'preferredName'])
        ->format(Format::using('nameLinked', ['gibbonPersonID', '', 'preferredName', 'surname', 'Student', true, false]));

    $table->addColumn('category', __('Role'));

    $table->addColumn('formGroup', __('Form Group'));

    $table->addColumn('role', __('Event Role'));

    $table->addColumn('timestampCreated', __('Added on'))->format(Format::using('dateTime', 'timestampCreated'));

    echo $form->getOutput();
}
?>