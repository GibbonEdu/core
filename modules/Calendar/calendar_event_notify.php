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

if (isActionAccessible($guid, $connection2, '/modules/Calendar/calendar_event_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__('Manage Events'), 'calendar_event_manage.php')
        ->add(__('Notify Event'));

    $gibbonCalendarEventID = $_GET['gibbonCalendarEventID'] ?? '';

    $calendarEventGateway = $container->get(CalendarEventGateway::class);
    $calendarEventPersonGateway = $container->get(CalendarEventPersonGateway::class);

    // Get event details
    $values = $calendarEventGateway->getByID($gibbonCalendarEventID);
    if (!empty($gibbonCalendarEventID) && empty($values)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    $form = Form::create('eventNotification', $session->get('absoluteURL').'/modules/Calendar/calendar_event_notifyProcess.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('gibbonCalendarEventID', $gibbonCalendarEventID);

    // NOTES
    $form->addRow()->addHeading('notes', __('Notes'));

    $col = $form->addRow()->addColumn();
        $col->addLabel('notes', __('Add Notes to the Email'))->description(__('If provided, this note will be shared with all the email recipients.'));
        $col->addTextArea('notes');

    // NOTIFICATIONS
    $form->addRow()->addHeading('Notifications', __('Notifications'));

    $form->toggleVisibilityByClass('notifyGroups')->onCheckbox('allStaff')->whenNot('Y');
    
    $row = $form->addRow();
        $row->addLabel('notify', __('Automatically Notify'));
        $row->addCheckbox('allStaff')
            ->description(__('All staff'))
            ->setValue('Y');

    $row = $form->addRow();
        $row->addCheckbox('notifyGroups')->fromArray([
            'HOY'      => __('Head of Year'),
            'tutors'   => __('Form Tutors'),
            'teachers' => __('Class Teachers'),
            'INAssistant' => __('LSAs'),
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