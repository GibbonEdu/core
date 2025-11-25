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
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Domain\Calendar\CalendarEventGateway;
use Gibbon\Domain\Calendar\CalendarEventPersonGateway;
use Gibbon\Services\Format;
use Gibbon\Support\Facades\Access;

if (isActionAccessible($guid, $connection2, '/modules/Calendar/calendar_event_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__('Manage Events'), 'calendar_event_manage.php')
        ->add(__('Notify Staff'));

    $page->return->addReturns(['error3' => __('This event does not have any attendees.')]);

    $gibbonCalendarEventID = $_GET['gibbonCalendarEventID'] ?? '';

    $calendarEventGateway = $container->get(CalendarEventGateway::class);
    $calendarEventPersonGateway = $container->get(CalendarEventPersonGateway::class);

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

    // Get all student participants
    $criteria = $calendarEventPersonGateway->newQueryCriteria()
        ->sortBy(['surname', 'preferredName', 'category'])
        ->fromPOST();
    $students = $calendarEventPersonGateway->queryEventAttendees($criteria, $gibbonCalendarEventID)->toArray();

    if (empty($students)) {
        $page->addError(__('This event does not have any attendees.'));
        return;
    }

    $form = Form::create('eventNotification', $session->get('absoluteURL').'/modules/Calendar/calendar_event_notifyProcess.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('gibbonCalendarEventID', $gibbonCalendarEventID);

    // NOTES
    $form->addRow()->addHeading('Message Details', __('Message Details'));

    $subject = $event['name'] . ($event['allDay'] != 'Y' ? ', ' .Format::dateRangeReadable($event['dateStart'], $event['dateEnd']) : '');
    $col = $form->addRow()->addColumn();
        $col->addLabel('subject', __('Subject'));
        $col->addTextField('subject')->maxLength(120)->setValue($subject);

    $col = $form->addRow()->addColumn();
        $col->addLabel('notes', __('Notes'))->description(__('Optional notes that will be shared with email recipients.'));
        $col->addEditor('notes', $guid)->setRows(5);

    // NOTIFICATIONS
    $form->addRow()->addHeading('Notifications', __('Notifications'));

    $form->toggleVisibilityByClass('notifyGroups')->onCheckbox('allStaff')->whenNot('Y');
    
    $row = $form->addRow();
        $row->addLabel('notify', __('Automatically Notify'));
        $row->addCheckbox('allStaff')
            ->description(__('All Staff'))
            ->setValue('Y');

    $row = $form->addRow();
        $row->addCheckbox('notifyGroups')->fromArray([
            'participants' => __('Staff').' '.__('Participants'),
            'HOY'          => __('Head of Year'),
            'tutors'       => __('Form Tutors'),
            'teachers'     => __('Class Teachers'),
            'INAssistant'  => __('Educational Assistants'),
        ])->checkAll()->addClass('notifyGroups');

    $row = $form->addRow();
        $row->addLabel('notificationList', __('Notify Additional People'))->addClass('notifyGroups');
        $row->addFinder('notificationList')
            ->addClass('notifyGroups')
            ->fromAjax($session->get('absoluteURL').'/modules/Staff/staff_searchAjax.php')
            ->setParameter('resultsLimit', 10)
            ->resultsFormatter('function(item){ return "<li class=\'\'><div class=\'inline-block bg-cover w-12 h-12 ml-2 rounded-full bg-gray-200 border border-gray-400 bg-no-repeat\' style=\'background-image: url(" + item.image + ");\'></div><div class=\'inline-block px-4 truncate\'>" + item.name + "<br/><span class=\'inline-block opacity-75 truncate text-xxs\'>" + item.jobTitle + "</span></div></li>"; }');

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
