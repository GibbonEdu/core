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
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Domain\Calendar\CalendarGateway;
use Gibbon\Domain\Calendar\CalendarEventGateway;

if (isActionAccessible($guid, $connection2, '/modules/Calendar/calendar_event_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs->add(__('Manage Events'));

    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if (empty($highestAction)) {
        $page->addError(__('You do not have access to this action.'));
        return;
    }

    $search = $_REQUEST['search'] ?? '';

    $gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? $session->get('gibbonSchoolYearID');
    $page->navigator->addSchoolYearNavigation($gibbonSchoolYearID);

    $calendarEventGateway = $container->get(CalendarEventGateway::class);

    $criteria = $calendarEventGateway->newQueryCriteria()
        ->searchBy($calendarEventGateway->getSearchableColumns(), $search)
        ->sortBy(['dateStart', 'eventName', 'status'])
        ->fromPOST();

    // SEARCH
    $form = Form::create('filters', $session->get('absoluteURL').'/index.php', 'get');
    $form->setClass('noIntBorder w-full');

    $form->addHiddenValue('q', '/modules/Calendar/calendar_event_manage.php');

    $row = $form->addRow();
        $row->addLabel('search', __('Search For'))->description(__m('Event name, type, description, organiser, calendar name'));
        $row->addTextField('search')->setValue($criteria->getSearchText())->maxLength(30);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSearchSubmit($session, 'Clear Filters', ['view', 'sidebar']);

    echo $form->getOutput();

    // Query units
    $gibbonPersonID = $highestAction == 'Manage Events_my' ? $session->get('gibbonPersonID') : null;
    $events = $calendarEventGateway->queryEvents($criteria, $gibbonPersonID);

    // DATA TABLE
    $table = DataTable::createPaginated('events', $criteria);

    $table->setTitle($highestAction == 'Manage Events_all' ? __('All Events') : __('My Events'));

    $table->addHeaderAction('add', __('Add'))
        ->setURL('/modules/Calendar/calendar_event_add.php')
        ->displayLabel();

    $table->modifyRows(function($values, $row) {
        if ($values['status'] == 'Cancelled') $row->addClass('dull');
        if ($values['status'] == 'Tentative') $row->addClass('error');
        return $row;
    });

    $table->addMetaData('filterOptions', [
        'status:confirmed' => __('Status').': '.__('Confirmed'),
        'status:tentative'  => __('Status').': '.__('Tentative'),
        'status:cancelled'  => __('Status').': '.__('Cancelled'),
    ]);

    // COLUMNS

    if (!empty($values['description'])) {
        $table->addExpandableColumn('description')
            ->format(function ($values) {
                if (!empty($values['description'])) {
                    return formatExpandableSection(__('Description'), $values['description']);
                }
                return '';
            });
    }

    $table->addColumn('eventName', __('Event Name'))
        ->context('primary');

    $table->addColumn('status', __('Status'));

    $table->addColumn('calendarName', __('Calendar'))->context('primary');

    $table->addColumn('type', __('Event Type'))->context('primary');

    $table->addColumn('dateStart', __('First Day'))
        ->format(Format::using('dateReadable', ['dateStart']));

    $table->addColumn('dateEnd', __('Last Day'))
        ->format(Format::using('dateReadable', ['dateEnd']));

    $table->addColumn('locationType', __('Location Type'));

    $table->addColumn('organiser', __('Organiser'))
        ->format(Format::using('name', ['title', 'preferredName', 'surname', 'Staff', false, true]))
        ->sortable('surname');

    $table->addActionColumn()
        ->addParam('gibbonCalendarEventID')
        ->format(function ($row, $actions) {
            $actions->addAction('edit', __('Edit'))
                ->setURL('/modules/Calendar/calendar_event_edit.php');

            $actions->addAction('delete', __('Delete'))
                ->setURL('/modules/Calendar/calendar_event_delete.php');
        });

    echo $table->render($events);
}
