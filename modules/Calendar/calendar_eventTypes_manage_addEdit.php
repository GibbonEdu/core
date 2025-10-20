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
use Gibbon\Domain\Calendar\CalendarEventTypeGateway;

if (isActionAccessible($guid, $connection2, '/modules/Calendar/calendar_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $gibbonCalendarEventTypeID = $_GET['gibbonCalendarEventTypeID'] ?? '';
    $action = !empty($gibbonCalendarEventTypeID) ? 'edit' : 'add';

    $page->breadcrumbs
        ->add(__('Manage Calendars'), 'calendar_manage.php')
        ->add(__('Manage Event Types'), 'calendar_eventTypes_manage.php')
        ->add($action == 'edit' ? __('Edit Event Type') : __('Add Event Type'));
    
    if (empty($gibbonCalendarEventTypeID) && isset($_GET['editID'])) {
        $page->return->setEditLink($session->get('absoluteURL').'/index.php?q=/modules/Calendar/calendar_eventTypes_manage_addEdit.php&gibbonCalendarEventTypeID='.$_GET['editID']);
    }

    $calendarEventTypeGateway = $container->get(CalendarEventTypeGateway::class);
    $values = $calendarEventTypeGateway->getByID($gibbonCalendarEventTypeID);

    if (!empty($gibbonCalendarEventTypeID) && empty($values)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    // FORM
    $form = Form::create('calendarEventTypes', $session->get('absoluteURL').'/modules/Calendar/calendar_eventTypes_manage_addEditProcess.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->addMeta()->addDefaultContent($action);
    $form->enableQuickSave($action == 'edit');

    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('gibbonCalendarEventTypeID', $gibbonCalendarEventTypeID);

    // EVENT TYPE
    $form->addRow()->addHeading(__('Event Types'));

    $row = $form->addRow();
        $row->addLabel('type', __('Type'));
        $row->addTextField('type')->required()->maxLength(60);

    $row = $form->addRow();
        $row->addLabel('color', __('Colour'));
        $row->addColor('color')->required();
        
    $row = $form->addRow();
        $row->addSubmit();

    $form->loadAllValuesFrom($values);

    echo $form->getOutput();
}
