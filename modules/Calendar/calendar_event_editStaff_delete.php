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
use Gibbon\Domain\Calendar\CalendarEventGateway;
use Gibbon\Domain\Calendar\CalendarEventPersonGateway;

if (isActionAccessible($guid, $connection2, '/modules/Calendar/calendar_event_edit.php') == false) {
    //Acess denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $gibbonCalendarEventID = $_GET['gibbonCalendarEventID'] ?? '';
    $gibbonCalendarEventPersonID = $_GET['gibbonCalendarEventPersonID'] ?? '';

    $calendarEventGateway = $container->get(CalendarEventGateway::class);
    $calendarEventPersonGateway = $container->get(CalendarEventPersonGateway::class);
    
    if (!$calendarEventGateway->exists($gibbonCalendarEventID) || !$calendarEventPersonGateway->exists($gibbonCalendarEventPersonID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {
        $form = DeleteForm::createForm($session->get('absoluteURL') . '/modules/' . $session->get('module') . "/calendar_event_editStaff_deleteProcess.php");
        $form->addHiddenValue('address', $session->get('address'));
        $form->addHiddenValue('gibbonCalendarEventID', $gibbonCalendarEventID);
        $form->addHiddenValue('gibbonCalendarEventPersonID', $gibbonCalendarEventPersonID);
        
        echo $form->getOutput();
    }
}
?>
