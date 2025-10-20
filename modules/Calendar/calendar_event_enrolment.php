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

use Gibbon\Http\Url;
use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Forms\Prefab\BulkActionForm;
use Gibbon\Domain\Calendar\CalendarGateway;
use Gibbon\Domain\Calendar\CalendarEventGateway;
use Gibbon\Domain\Calendar\CalendarEventTypeGateway;
use Gibbon\Domain\Calendar\CalendarEventPersonGateway;

if (isActionAccessible($guid, $connection2, '/modules/Calendar/calendar_event_enrolment.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $gibbonCalendarEventID = $_GET['gibbonCalendarEventID'] ?? '';

    if (empty($gibbonCalendarEventID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $calendarEventGateway = $container->get(CalendarEventGateway::class);
    $calendarEventPersonGateway = $container->get(CalendarEventPersonGateway::class);
    $calendarGateway = $container->get(CalendarGateway::class);
    $calendarEventTypeGateway = $container->get(CalendarEventTypeGateway::class);
    
    $page->breadcrumbs
        ->add(__('Manage Event'), 'calendar_event_manage.php')
        ->add(__('Event Enrolment'));

    $event = $calendarEventGateway->getByID($gibbonCalendarEventID);
    if (empty($event)) {
        $page->addError(__('The selected record does not exist, or you do not have access to it.'));
        return;
    }

    // FORM
    $form = Form::create('eventEnrolment', $session->get('absoluteURL').'/index.php');

     $form->addHeaderAction('view', __('View Event'))
        ->setURL('/modules/Calendar/calendar_event_view.php')
        ->addParam('gibbonCalendarEventID', $gibbonCalendarEventID)
        ->displayLabel();

    $calendars = $calendarGateway->selectCalendarsBySchoolYear($session->get('gibbonSchoolYearID'))->fetchKeyPair();
    $row = $form->addRow();
        $row->addLabel('gibbonCalendarID', __('Calendar'));
        $row->addSelect('gibbonCalendarID')
            ->fromArray($calendars)
            ->selected($event['gibbonCalendarID'])->readonly();

    // Get all event types
    $types = $calendarEventTypeGateway->selectAllEventTypes()->fetchKeyPair();
    $row = $form->addRow();
        $row->addLabel('gibbonCalendarEventTypeID', __('Event Type'));
        $row->addSelect('gibbonCalendarEventTypeID')
            ->fromArray($types)
            ->selected($event['gibbonCalendarEventTypeID'])
            ->readonly();
            
    $row = $form->addRow();
        $row->addLabel('name', __('Name'));
        $row->addTextField('name')->readOnly()->setValue($event['name']);

    echo $form->getOutput();

    // QUERY
    $criteria = $calendarEventPersonGateway->newQueryCriteria()
        ->sortBy(['surname', 'preferredName', 'category'])
        ->fromPOST();

    $participants = $calendarEventPersonGateway->queryEnrolledAttendees($criteria, $gibbonCalendarEventID);

    // BULK ACTION FORM
    $form = BulkActionForm::create('bulkAction', $session->get('absoluteURL').'/modules/Calendar/calendar_event_enrolmentProcessBulk.php');
    $form->addHiddenValue('gibbonCalendarEventID', $gibbonCalendarEventID);

    $col = $form->createBulkActionColumn([
        'Delete' => __('Delete'),
    ]);
    $col->addSubmit(__('Go'));

    // DATA TABLE FOR PARTICIPANTS
    $table = $form->addRow()->addDataTable('participants', $criteria)->withData($participants);
    $table->setTitle(__('Attendees'));

    $table->addMetaData('bulkActions', $col);

    $table->addHeaderAction('add', __('Add Student'))
        ->setURL('/modules/Calendar/calendar_event_enrolment_add.php')
        ->addParam('gibbonCalendarEventID', $gibbonCalendarEventID)
        ->displayLabel();

    $table->addColumn('name', __('Name'))
        ->sortable(['surname', 'preferredName'])
        ->format(Format::using('nameLinked', ['gibbonPersonID', '', 'preferredName', 'surname', 'Student', true, false]));

    $table->addColumn('formGroup', __('Form Group'));

    $table->addColumn('category', __('Role'));

    $table->addColumn('role', __('Event Role'));

    $table->addColumn('timestampCreated', __('Timestamp'))->format(Format::using('dateTime', 'timestampCreated'));

    // ACTIONS
    $table->addActionColumn()
        ->addParam('gibbonCalendarEventPersonID')
        ->addParam('gibbonCalendarEventID', $gibbonCalendarEventID)
        ->addParam('gibbonPersonID')
        ->format(function ($event, $actions) {
            $actions->addAction('delete', __('Delete'))
                    ->setURL('/modules/Calendar/calendar_event_enrolment_delete.php');
        });

    $table->addCheckboxColumn('gibbonCalendarEventPersonID');

    echo $form->getOutput();
}