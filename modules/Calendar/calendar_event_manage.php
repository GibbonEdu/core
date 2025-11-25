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
use Gibbon\Forms\Prefab\BulkActionForm;
use Gibbon\Domain\Calendar\CalendarGateway;
use Gibbon\Domain\Calendar\CalendarEventGateway;
use Gibbon\UI\Timetable\Palette;
use Gibbon\Support\Facades\Access;

if (isActionAccessible($guid, $connection2, '/modules/Calendar/calendar_event_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs->add(__('Manage Events'));

    $search = $_REQUEST['search'] ?? '';

    $gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? $session->get('gibbonSchoolYearID');
    $page->navigator->addSchoolYearNavigation($gibbonSchoolYearID);

    $calendarEventGateway = $container->get(CalendarEventGateway::class);
    $calendarGateway = $container->get(CalendarGateway::class);
    $palette = $container->get(Palette::class);

    $criteria = $calendarEventGateway->newQueryCriteria()
        ->searchBy($calendarEventGateway->getSearchableColumns(), $search)
        ->sortBy(['dateStart', 'timeStart'])
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
        $row->addSearchSubmit($session, __('Clear Filters'), ['view', 'sidebar']);

    echo $form->getOutput();

    // Query units
    $canManageAllEvents = Access::allows('Calendar', 'calendar_event_edit', 'Manage Events_all');
    $gibbonPersonIDEditor = $canManageAllEvents ? null : $session->get('gibbonPersonID');

    $events = $calendarEventGateway->queryEvents($criteria, $gibbonPersonIDEditor);
    $calendars = $calendarGateway->selectEditableCalendarsByPerson($session->get('gibbonSchoolYearID'), $gibbonPersonIDEditor)->fetchKeyPair();

    // FORM
    $form = BulkActionForm::create('bulkAction', $session->get('absoluteURL').'/modules/'.$session->get('module').'/calendar_event_manageProcessBulk.php');

    $bulkActions = [
        'Duplicate' => __('Duplicate'),
        'DuplicateParticipants' => __('Duplicate With Participants'),
    ];

    $col = $form->createBulkActionColumn($bulkActions);
    $col->addSubmit(__('Go'));

    // DATA TABLE
    $table = $form->addRow()->addDataTable('events', $criteria)->withData($events);

    $table->setTitle($canManageAllEvents ? __('All Events') : __('My Events'));

    if (!empty($calendars)) {
        $table->addHeaderAction('add', __('Add'))
            ->setURL('/modules/Calendar/calendar_event_add.php')
            ->displayLabel();
    }

    $table->modifyRows(function($values, $row) {
        if ($values['status'] == 'Tentative') $row->addClass('message');
        if ($values['status'] == 'Cancelled') $row->addClass('dull');
        return $row;
    });

    $table->addMetaData('filterOptions', [
        'status:confirmed' => __('Status').': '.__('Confirmed'),
        'status:tentative'  => __('Status').': '.__('Tentative'),
        'status:cancelled'  => __('Status').': '.__('Cancelled'),
    ]);

    $table->addMetaData('bulkActions', $col);

    // COLUMNS
    $table->addColumn('eventName', __('Event'))
        ->context('primary')
        ->format(function ($values) use ($palette) {
            $contrast = $palette->getHexContrastColor($values['color']);
            $border = $palette->adjustHexColor($values['color'], -0.1);
            $text = $contrast == 'white'
                ? $palette->adjustHexColor($values['color'], 0.7)
                : $palette->adjustHexColor($values['color'], -0.7);

            $style = "background-color: {$values['color']}; border-color: {$border}; color: {$text} !important;";

            return "<span class='inline-block rounded py-1 px-2 text-xs font-medium font-sans border' style='{$style}' title='{$values['calendarName']}'>{$values['eventName']}</span>";
        })
        ->formatDetails(function ($values) {
            return $values['status'] != 'Confirmed'
                ? Format::small($values['status'])
                : '';
        });

    $table->addColumn('dateStart', __('When'))
        ->format(function ($values) {
            return Format::dateRangeReadable($values['dateStart'], $values['dateEnd']);
        })
        ->formatDetails(function ($values) {
            return $values['allDay'] != 'Y'
                ? Format::small(Format::timeRange($values['timeStart'], $values['timeEnd']))
                : Format::small(__('All Day'));
        });

    $table->addColumn('locationType', __('Location'))
        ->format(function($values)  {
            if ($values['locationType'] == 'Internal') {
                return $values['space'] ?? ''; 
            }

            return !empty($values['locationURL'])
                ? Format::link($values['locationURL'], $values['locationDetail'])
                : $values['locationDetail'] ?? '';
        });

    $table->addColumn('participants', __('Participants'));

    $table->addColumn('organiser', __('Organiser'))
        ->format(Format::using('nameLinked', ['gibbonPersonIDOrganiser', 'title', 'preferredName', 'surname', 'Staff', false, true]))
        ->sortable('surname');

     // ACTIONS
    $table->addActionColumn()
        ->addParam('gibbonCalendarEventID')
        ->format(function ($values, $actions) use ($canManageAllEvents) {
            $actions->addAction('view', __('View'))
                ->setURL('/modules/Calendar/calendar_event_view.php');

            if ($values['editor'] == 'Y' || $canManageAllEvents) {
                $actions->addAction('edit', __('Edit'))
                    ->setURL('/modules/Calendar/calendar_event_edit.php');
            
                $actions->addAction('participants', __('Participants'))
                    ->setURL('/modules/Calendar/calendar_event_participants.php')
                    ->setIcon('users');

                $actions->addAction('delete', __('Delete'))
                    ->setURL('/modules/Calendar/calendar_event_delete.php');
            }
        });

    $table->addCheckboxColumn('gibbonCalendarEventID');

    echo $form->getOutput();
}
