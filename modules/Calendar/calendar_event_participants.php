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
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Support\Facades\Access;
use Gibbon\Forms\Prefab\BulkActionForm;
use Gibbon\Domain\Calendar\CalendarGateway;
use Gibbon\Domain\Calendar\CalendarEventGateway;
use Gibbon\Domain\Calendar\CalendarEventTypeGateway;
use Gibbon\Domain\Calendar\CalendarEventPersonGateway;
use Gibbon\Domain\Attendance\AttendanceLogPersonGateway;

if (isActionAccessible($guid, $connection2, '/modules/Calendar/calendar_event_participants.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $gibbonCalendarEventID = $_GET['gibbonCalendarEventID'] ?? '';

    if (empty($gibbonCalendarEventID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $calendarGateway = $container->get(CalendarGateway::class);
    $calendarEventGateway = $container->get(CalendarEventGateway::class);
    $calendarEventPersonGateway = $container->get(CalendarEventPersonGateway::class);
    $calendarEventTypeGateway = $container->get(CalendarEventTypeGateway::class);
    $attendanceLogGateway = $container->get(AttendanceLogPersonGateway::class);

    $page->breadcrumbs
        ->add(__('Manage Event'), 'calendar_event_manage.php')
        ->add(__('Participants'));

    $event = $calendarEventGateway->getByID($gibbonCalendarEventID);
    if (empty($event)) {
        $page->addError(__('The selected record does not exist, or you do not have access to it.'));
        return;
    }

    $isEventOwner = $session->get('gibbonPersonID') == $event['gibbonPersonIDCreated'] || $session->get('gibbonPersonID') == $event['gibbonPersonIDOrganiser'];

    // FORM
    $table = DataTable::createDetails('viewEvent');

    $table->addHeaderAction('view', __('View Event'))
        ->setURL('/modules/Calendar/calendar_event_view.php')
        ->addParam('gibbonCalendarEventID', $gibbonCalendarEventID)
        ->displayLabel();

    if (Access::allows('Calendar', 'calendar_event_edit') && $isEventOwner) {
        $table->addHeaderAction('edit', __('Edit Event'))
            ->setURL('/modules/Calendar/calendar_event_edit.php')
            ->addParam('gibbonCalendarEventID', $gibbonCalendarEventID)
            ->displayLabel();
    }

    if (Access::allows('Calendar', 'calendar_event_edit') && $isEventOwner) {
        $table->addHeaderAction('notify', __('Notify Staff'))
            ->setURL('/modules/Calendar/calendar_event_notify.php')
            ->addParam('gibbonCalendarEventID', $gibbonCalendarEventID)
            ->setIcon('notify')
            ->displayLabel();
    }

    $table->addColumn('gibbonCalendarID', __('Calendar'))
            ->format(function($event) use ($calendarGateway) {
                if (isset($event['gibbonCalendarID'])) {
                    $calendar = $calendarGateway->getByID($event['gibbonCalendarID']);
                    $output = '';
                    if (!empty($calendar)) {
                        $output .= __($calendar['name']);
                    }
                    return $output;
                }
            });
     $table->addColumn('gibbonCalendarEventType', __('Event Type'))
            ->format(function($event) use ($calendarEventTypeGateway) {
                if (isset($event['gibbonCalendarEventTypeID'])) {
                    $gibbonCalendarEventType = $calendarEventTypeGateway->getByID($event['gibbonCalendarEventTypeID']);
                    $output = '';
                    if (!empty($gibbonCalendarEventType)) {
                        $output .= __($gibbonCalendarEventType['type']);
                    }
                    return $output;
                }
            });

    $table->addColumn('name', __('Name'));

    $col = $table->addColumn('eventInfo', __('Event Details'));

    $col->addColumn('dateStart', __('Start Date'))->format(Format::using('date', 'dateStart'));

    $col->addColumn('dateEnd', __('End Date'))->format(Format::using('date', 'dateEnd'));

    if ($event['allDay'] == 'N') {
        $col->addColumn('timeStart', __('Start Time'))->format(Format::using('time', 'timeStart'));
        $col->addColumn('timeEnd', __('End Time'))->format(Format::using('time', 'timeEnd'));
    } else {
         $col->addColumn('allDay', __('When'))->format(function() {
            return __('All Day');
        });
    }

     echo $table->render([$event]);

    // QUERY
    $criteria = $calendarEventPersonGateway->newQueryCriteria()
        ->sortBy(['surname', 'preferredName', 'category'])
        ->fromPOST();

    $participants = $calendarEventPersonGateway->queryEventAttendees($criteria, $gibbonCalendarEventID);

    // Query all attendance logs for future absence records on the event date and time
    $futureAbsences = $event['allDay'] == 'Y'
        ? $attendanceLogGateway->selectFutureAttendanceLogsByDate($event['dateStart'], $event['dateEnd'])->fetchGroupedUnique()
        : $attendanceLogGateway->selectFutureAttendanceLogsByDateAndTime($event['dateStart'], $event['dateEnd'], $event['timeStart'], $event['timeEnd'])->fetchGroupedUnique();

    $futureAbsenceStudents = array_reduce($participants->toArray(), function ($group, $item) {
        if ($item['roleCategory'] == 'Student') $group[] = $item['gibbonPersonID'];
        return $group;
    }, []);

    // Find conflicts with any other events
    $conflicts = $calendarEventPersonGateway->selectEventParticipantConflicts($gibbonCalendarEventID)->fetchGroupedUnique();

    // BULK ACTION FORM
    $form = BulkActionForm::create('bulkAction', $session->get('absoluteURL').'/modules/Calendar/calendar_event_participantsProcessBulk.php');
    $form->addHiddenValue('gibbonCalendarEventID', $gibbonCalendarEventID);

    $col = $form->createBulkActionColumn([
        'Delete' => __('Delete'),
    ]);
    $col->addSubmit(__('Go'));

    // DATA TABLE FOR PARTICIPANTS
    $table = $form->addRow()->addDataTable('participants', $criteria)->withData($participants);
    $table->setTitle(__('Participants'));

    $table->addMetaData('bulkActions', $col);

    if (Access::allows('Attendance', 'attendance_take_adHoc') && $isEventOwner) {
        $table->addHeaderAction('setFutureAbsence', __('Set Future Absence'))
            ->setURL('/modules/Attendance/attendance_future_byPerson.php')
            ->addParams([
                'scope'              => 'multiple',
                'target'             => 'Select',
                'absenceType'        => $event['allDay'] == 'Y' ? 'full' : 'partial',
                'date'               => $event['dateStart'],
                'timeStart'          => $event['timeStart'],
                'timeEnd'            => $event['timeEnd'],
                'gibbonPersonIDList' => implode(',', $futureAbsenceStudents),
            ])
            ->setIcon('user-plus')
            ->setAttribute('target', '_blank')
            ->displayLabel();
    }

    $table->addHeaderAction('add', __('Add Participants'))
        ->setURL('/modules/Calendar/calendar_event_participants_add.php')
        ->addParam('gibbonCalendarEventID', $gibbonCalendarEventID)
        ->displayLabel();

    $table->addColumn('image_240', __('Photo'))
        ->context('primary')
        ->width('7%')
        ->notSortable()
        ->format(Format::using('userPhoto', ['image_240', 'xs']));

    $table->addColumn('name', __('Name'))
        ->description(__('Role'))
        ->sortable(['surname', 'preferredName'])
        ->format(Format::using('nameLinked', ['gibbonPersonID', '', 'preferredName', 'surname', 'roleCategory', true, true]))
        ->formatDetails(function ($values) {
            return Format::small($values['roleCategory']);
        });

    $table->addColumn('formGroup', __('Form Group'));

    $table->addColumn('role', __('Event Role'))
        ->description(__('Added On'))
        ->format(function ($values) {
            $status = $values['role'] != 'Attendee' ? 'message' : 'dull';
            return Format::tag(__($values['role']), $status);
        })
        ->formatDetails(function ($values) {
            return Format::small(Format::dateTime($values['timestampCreated']));
        });

    $table->addColumn('futureAbsenceStatus', __('Future Absence'))
        ->format(function ($values) use ($futureAbsences) {
            if ($values['roleCategory'] != 'Student') return '';
            if (isset($futureAbsences[$values['gibbonPersonID']]) && !empty($futureAbsences[$values['gibbonPersonID']])) {
                $absenceType = $futureAbsences[$values['gibbonPersonID']]['type'] ?? '';
                $absenceReason = $futureAbsences[$values['gibbonPersonID']]['reason'] ?? '';
                $absenceComment = $futureAbsences[$values['gibbonPersonID']]['comment'] ?? '';
                return Format::tag(__($absenceType), 'success', !empty($absenceComment) ? $absenceReason.': '.$absenceComment : $absenceReason  );
            }
            return Format::tag(__('N/A'), 'dull');
        });

    if (!empty($conflicts)) {
        $table->addColumn('conflict', __('Status'))
            ->format(function ($values) use ($conflicts) {
                if (empty($conflicts[$values['gibbonPersonID']])) return '';

                $conflict = $conflicts[$values['gibbonPersonID']];
                $url = Url::fromModuleRoute('Calendar', 'calendar_event_view')->withQueryParams(['gibbonCalendarEventID' => $conflict['gibbonCalendarEventID']]);
                return Format::link($url, Format::tag(__('Conflict'), 'warning', $conflict['event']));
            });
    }

    // ACTIONS
    $table->addActionColumn()
        ->addParam('gibbonCalendarEventPersonID')
        ->addParam('gibbonCalendarEventID', $gibbonCalendarEventID)
        ->addParam('gibbonPersonID')
        ->format(function ($event, $actions) {
            $actions->addAction('delete', __('Delete'))
                    ->setURL('/modules/Calendar/calendar_event_participants_delete.php');
        });

    $table->addCheckboxColumn('gibbonCalendarEventPersonID');

    echo $form->getOutput();
}
