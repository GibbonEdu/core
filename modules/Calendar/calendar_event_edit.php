<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

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
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Domain\Messenger\GroupGateway;
use Gibbon\Domain\Students\StudentGateway;
use Gibbon\Domain\Calendar\CalendarGateway;
use Gibbon\Domain\Activities\ActivityGateway;
use Gibbon\Domain\Calendar\CalendarEventGateway;
use Gibbon\Domain\Calendar\CalendarEventTypeGateway;

if (isActionAccessible($guid, $connection2, '/modules/Calendar/calendar_event_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $gibbonCalendarEventID = $_GET['gibbonCalendarEventID'] ?? '';
    $action = !empty($gibbonCalendarEventID) ? 'edit' : '';

    $page->breadcrumbs
        ->add(__('Manage Events'), 'calendar_event_manage.php')
        ->add(__('Edit Event'));

    if (empty($gibbonCalendarEventID) && isset($_GET['editID'])) {
        $page->return->setEditLink($session->get('absoluteURL').'/index.php?q=/modules/Calendar/calendar_event_edit.php&gibbonCalendarEventID='.$_GET['editID']);
    }
    
    $calendarEventGateway = $container->get(CalendarEventGateway::class);
    $calendarGateway = $container->get(CalendarGateway::class);
    $calendarEventTypeGateway = $container->get(CalendarEventTypeGateway::class);
    
    // Editing an event
    $values = $calendarEventGateway->getByID($gibbonCalendarEventID);
    if (!empty($gibbonCalendarEventID) && empty($values)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    // FORM
    $form = Form::create('event', $session->get('absoluteURL').'/modules/Calendar/calendar_event_addEditProcess.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->addMeta()->addDefaultContent($action);
    $form->enableQuickSave($action == 'edit');

    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('gibbonCalendarEventID', $gibbonCalendarEventID);

    $form->addRow()->addHeading(__('Basic Information'));

    // Get Calendars of the current school year
    $calendars = $calendarGateway->selectCalendarsBySchoolYear($session->get('gibbonSchoolYearID'))->fetchKeyPair();
    $row = $form->addRow();
        $row->addLabel('gibbonCalendarID', __('Calendar'));
        $row->addSelect('gibbonCalendarID')
            ->fromArray($calendars)
            ->selected($values['gibbonCalendarID'])
            ->placeholder()
            ->required();

    // Get all event types
    $types = $calendarEventTypeGateway->selectAllEventTypes()->fetchKeyPair();
    $row = $form->addRow();
        $row->addLabel('gibbonCalendarEventTypeID', __('Event Type'));
        $row->addSelect('gibbonCalendarEventTypeID')
            ->fromArray($types)
            ->selected($values['gibbonCalendarEventTypeID'])
            ->placeholder()
            ->required();

    $row = $form->addRow();
        $row->addLabel('gibbonPersonIDOrganiser', __('Organiser'));
        $row->addSelectStaff('gibbonPersonIDOrganiser')->placeholder()->required()->selected($values['gibbonPersonIDOrganiser']);

    $row = $form->addRow();
        $row->addLabel('name', __('Name'));
        $row->addTextField('name')->required()->maxLength(120);

    // Status can be changed to cancelled only during Edit
    $statusList = [
        'Confirmed' => __('Confirmed'),
        'Tentative' => __('Tentative'),
        'Cancelled' => __('Cancelled'),
    ];

    $row = $form->addRow();
        $row->addLabel('status', __('Event Status'));
        $row->addSelect('status')
            ->fromArray($statusList)
            ->required();

    $col = $form->addRow()->addColumn();
        $col->addLabel('description', __('Description'));
        $col->addEditor('description', $guid);

    $form->addRow()->addHeading(__('Event Details'));

    // Event Location
    $row = $form->addRow();
        $row->addLabel('locationType', __('Location Type'));
        $row->addSelect('locationType')->fromArray(['Internal' => __('Internal'), 'External' => __('External')])->required()->placeholder();

    $form->toggleVisibilityByClass('internal')->onSelect('locationType')->when('Internal');

    $row = $form->addRow()->addClass('internal');
        $row->addLabel('location', __('Location'));
        $row->addSelectSpace('gibbonSpaceID')->placeholder();

    $form->toggleVisibilityByClass('external')->onSelect('locationType')->when('External');

    $row = $form->addRow()->addClass('external');
        $row->addLabel('locationDetail', __('Location Details'));
        $row->addTextField('locationDetail');

    $row = $form->addRow()->addClass('external');
        $row->addLabel('locationURL', __('Location URL'));
        $row->addTextField('locationURL')->maxLength(255);

    // Event Dates
    $date = $_GET['date'] ?? '';
    $row = $form->addRow();
        $row->addLabel('dateStart', __('Start Date'));
        $row->addDate('dateStart')->chainedTo('dateEnd')->required()->setValue($date);

    $row = $form->addRow();
        $row->addLabel('dateEnd', __('End Date'));
        $row->addDate('dateEnd')->chainedFrom('dateStart')->required()->setValue($date);

    $row = $form->addRow();
        $row->addLabel('allDay', __('When'));
        $row->addCheckbox('allDay')
            ->description(__('All Day'))
            ->inline()
            ->setValue('Y')
            ->checked('Y')
            ->wrap('<div class="standardWidth floatRight">', '</div>');

    $form->toggleVisibilityByClass('timeOptions')->onCheckbox('allDay')->whenNot('Y');

    $row = $form->addRow()->addClass('timeOptions');
        $row->addLabel('timeStart', __('Time'));
        $col = $row->addColumn('timeStart')->addClass('right inline gap-2');
        $col->addTime('timeStart')
            ->required();
        $col->addTime('timeEnd')
            ->chainedTo('timeStart')
            ->required();

    $col = $form->addRow()->addClass('schoolClosedOverride hidden')->addColumn();
        $col->addAlert(__('One or more selected dates are not a school day. Check here to confirm if you would like to include these dates.'), 'warning');
        $col->addCheckbox('schoolClosedOverride')
            ->description(__('Confirm'))
            ->setClass('text-right pr-1')
            ->setValue('Y');

    // PARTICIPANTS (Students and Staff)

    $form->addRow()->addHeading(__('Participants'));


    $row = $form->addRow();
        $row->addLabel('staff', __('Staff'));
        $row->addSelectUsers('staff', $session->get('gibbonSchoolYearID'), ['includeStaff' => true])->selectMultiple();
            
    $row = $form->addRow();
        $row->addLabel('role', __('Role'));
        $row->addSelect('role')
            ->fromArray([
                'Organiser' => __('Organiser'),
                'Coach'     => __('Coach'),
                'Assistant' => __('Assistant'),
                'Other'     => __('Other')
            ]);

    $gibbonActivityID = $_GET['gibbonActivityID'] ?? '';
    $gibbonGroupID = $_GET['gibbonGroupID'] ?? '';
    $gibbonPersonIDList = $_GET['gibbonPersonIDList'] ?? [];
    $targetStudents = $_GET['targetStudents'] ?? '';

    // $form = Form::create('filter', $session->get('absoluteURL') . '/index.php', 'get');
    // $form->setFactory(DatabaseFormFactory::create($pdo));
    // $form->setTitle(__('Choose targetStudents'));
    // $form->setClass('noIntBorder fullWidth');

    // $form->addHiddenValue('q', '/modules/Calendar/calendar_event_addEditAjax.php');

     $targetOptions = [
        'Messenger'    => __('Messenger Group'),
        'Activity' => __('Activity Enrolment'),
        'Select'   => __('Select Students'),
    ];

    $row = $form->addRow();
        $row->addLabel('targetStudents', __('Students'));
        $row->addSelect('targetStudents')->fromArray($targetOptions)->required()->placeholder();

    $form->toggleVisibilityByClass('targetActivity')->onSelect('targetStudents')->when('Activity');
    $form->toggleVisibilityByClass('targetMessenger')->onSelect('targetStudents')->when('Messenger');
    $form->toggleVisibilityByClass('targetSelect')->onSelect('targetStudents')->when('Select');

    // Activity
    $activities = $container->get(ActivityGateway::class)->selectActivitiesBySchoolYear($session->get('gibbonSchoolYearID'))->fetchKeyPair();
    $row = $form->addRow()->addClass('targetActivity');
        $row->addLabel('gibbonActivityID', __('Activity'));
        $row->addSelect('gibbonActivityID')->fromArray($activities)->selected($gibbonActivityID)->required()->placeholder();

    // Messenger Groups
    $groups = $container->get(GroupGateway::class)->selectGroupsBySchoolYear($session->get('gibbonSchoolYearID'))->fetchKeyPair();
    $row = $form->addRow()->addClass('targetMessenger');
        $row->addLabel('gibbonGroupID', __('Messenger Group'));
        $row->addSelect('gibbonGroupID')->fromArray($groups)->selected($gibbonGroupID)->required()->placeholder();

    // Select Students
    $studentGateway = $container->get(StudentGateway::class);
    $studentCriteria = $studentGateway->newQueryCriteria()
        ->sortBy(['surname', 'preferredName']);

    $studentList = $studentGateway->queryStudentsBySchoolYear($studentCriteria, $session->get('gibbonSchoolYearID'));
    $studentList = array_reduce($studentList->toArray(), function ($group, $student) use ($gibbonPersonIDList) {
        $list = in_array($student['gibbonPersonID'], $gibbonPersonIDList) ? 'destination' : 'source';
        $group['students'][$list][$student['gibbonPersonID']] = Format::name($student['title'], $student['preferredName'], $student['surname'], 'Student', true) . ' - ' . $student['formGroup']; 
        $group['form'][$student['gibbonPersonID']] = $student['formGroup'];
        return $group;
    });

    $col = $form->addRow()->addClass('targetSelect')->addColumn();
        $col->addLabel('gibbonPersonIDList', __('Students'));
        $select = $col->addMultiSelect('gibbonPersonIDList')->isRequired();
        $select->addSortableAttribute(__('Form Group'), $studentList['form']);
        $select->source()->fromArray($studentList['students']['source'] ?? []);
        $select->destination()->fromArray($studentList['students']['destination'] ?? []);

  
 

    $row = $form->addRow();
        $row->addSubmit();

    $form->loadAllValuesFrom($values);

    echo $form->getOutput();
}
?>

<script>
$(document).ready(function() {
    // Check if the event falls on a school day
    $('#dateStart, #dateEnd').on('change', function() {
        $.ajax({
            url: "./modules/Calendar/calendar_event_addEditAjax.php",
            data: {
                'dateStart': $('#dateStart').val(),
                'dateEnd': $('#dateEnd').val(),
            },
            type: 'POST',
            success: function(data) {
                if (data === '0') {
                    $('.schoolClosedOverride').removeClass('hidden');
                    $('#schoolClosedOverride').prop('disabled', false);
                } else {
                    $('.schoolClosedOverride').addClass('hidden');
                    $('#schoolClosedOverride').prop('disabled', true);
                }
            }
        });
    });
});
</script>
