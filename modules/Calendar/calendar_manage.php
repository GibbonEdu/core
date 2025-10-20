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

use Gibbon\Tables\DataTable;
use Gibbon\Domain\Calendar\CalendarGateway;
use Gibbon\Services\Format;

if (isActionAccessible($guid, $connection2, '/modules/Calendar/calendar_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs->add(__('Manage Calendars'));

    $gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? $session->get('gibbonSchoolYearID');
    $page->navigator->addSchoolYearNavigation($gibbonSchoolYearID);

    $calendarGateway = $container->get(CalendarGateway::class);

    $criteria = $calendarGateway->newQueryCriteria()
        ->sortBy('sequenceNumber')
        ->fromPOST();

    $calendars = $calendarGateway->queryCalendars($criteria, $gibbonSchoolYearID);

    // DATA TABLE
    $table = DataTable::createPaginated('calendars', $criteria);

    $table->addHeaderAction('add', __('Add'))
        ->setURL('/modules/Calendar/calendar_manage_addEdit.php')
        ->addParam('gibbonSchoolYearID', $gibbonSchoolYearID)
        ->displayLabel();

    $table->addHeaderAction('editTypes', __('Event Types'))
        ->setURL('/modules/Calendar/calendar_eventTypes_manage.php')
        ->addParam('gibbonSchoolYearID', $gibbonSchoolYearID)
        ->setIcon('squares-plus')
        ->displayLabel();

    // COLUMNS
    $table->addDraggableColumn('gibbonCalendarID', $session->get('absoluteURL').'/modules/Calendar/calendar_manage_orderAjax.php');

    $table->addColumn('color', __('Colour'))
        ->width('8%')
        ->format(function ($values) {
            return !empty($values['color'])? Format::colorSwatch($values['color']) : '';
        });

    $table->addColumn('name', __('Name'));

    $table->addColumn('description', __('Description'));

    $table->addActionColumn()
        ->addParam('gibbonCalendarID')
        ->addParam('gibbonSchoolYearID', $gibbonSchoolYearID)
        ->format(function ($row, $actions) {
            $actions->addAction('edit', __('Edit'))
                ->setURL('/modules/Calendar/calendar_manage_addEdit.php');

            $actions->addAction('delete', __('Delete'))
                ->setURL('/modules/Calendar/calendar_manage_delete.php');
        });

    echo $table->render($calendars);
}
