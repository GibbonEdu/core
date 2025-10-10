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
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Domain\Calendar\CalendarGateway;
use Gibbon\Domain\Calendar\CalendarEventTypeGateway;

if (isActionAccessible($guid, $connection2, '/modules/Calendar/calendar_event_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__('Manage Events'), 'calendar_event_manage.php')
        ->add(__('Add Event'));

    $calendarGateway = $container->get(CalendarGateway::class);
    $calendarEventTypeGateway = $container->get(CalendarEventTypeGateway::class);

    // FORM
    $form = Form::create('event', $session->get('absoluteURL').'/modules/Calendar/calendar_event_addProcess.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('address', $session->get('address'));

    $form->addRow()->addHeading(__('Basic Information'));

    // Get Calendars of the current school year
    $calendars = $calendarGateway->selectCalendarsBySchoolYear($session->get('gibbonSchoolYearID'))->fetchKeyPair();
    $row = $form->addRow();
        $row->addLabel('gibbonCalendarID', __('Calendar'));
        $row->addSelect('gibbonCalendarID')
            ->fromArray($calendars)
            ->placeholder()
            ->required();

    // Get all event types
    $types = $calendarEventTypeGateway->selectAllEventTypes()->fetchKeyPair();
    $row = $form->addRow();
        $row->addLabel('gibbonCalendarEventTypeID', __('Event Type'));
        $row->addSelect('gibbonCalendarEventTypeID')
            ->fromArray($types)
            ->placeholder()
            ->required();

    $gibbonPersonID = $session->get('gibbonPersonID');
    $row = $form->addRow();
        $row->addLabel('gibbonPersonIDOrganiser', __('Organiser'));
        $row->addSelectStaff('gibbonPersonIDOrganiser')->placeholder()->required()->selected($gibbonPersonID);

    $row = $form->addRow();
        $row->addLabel('name', __('Event Name'));
        $row->addTextField('name')->required()->maxLength(120);

    $statusList = [
        'Confirmed' => __('Confirmed'),
        'Tentative' => __('Tentative'),
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
        $row->addSelectSpace('gibbonSpaceID');

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
            ->setClass('flex-1')
            ->required();
        $col->addTime('timeEnd')
            ->chainedTo('timeStart')
            ->setClass('flex-1')
            ->required();

    // STAFF
    $form->addRow()->addHeading(__('Teachers'));

    $row = $form->addRow();
        $row->addLabel('staff', __('Staff'));
        $row->addSelectUsers('staff', $session->get('gibbonSchoolYearID'), ['includeStaff' => true, 'includeAllUsers' => false])->selectMultiple();

    $row = $form->addRow();
        $row->addLabel('role', 'Role');
        $row->addSelect('role')
            ->fromArray([
                'Organiser' => __('Organiser'),
                'Coach'     => __('Coach'),
                'Assistant' => __('Assistant'),
                'Other'     => __('Other'), 
            ]);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}

   
    //     // $targetStudents = $_GET['targetStudents'] ?? '';
    //     // $gibbonActivityID = $_GET['gibbonActivityID'] ?? '';
    //     // $gibbonGroupID = $_GET['gibbonGroupID'] ?? '';
    //     // $gibbonPersonIDList = $_GET['gibbonPersonIDList'] ?? [];

    //     // PARTICIPANTS Section (Students and Staff)
    //     $participants = [];
    //     $gibbonSchoolYearID = $session->get('gibbonSchoolYearID');
        

    //     // STAFF / teachers
    //     $staffGateway = $container->get(StaffGateway::class);
    //     $staffCriteria = $staffGateway->newQueryCriteria()
    //     ->sortBy(['surname', 'preferredName']);

    //     $teachers = array_reduce($staffGateway->queryAllStaff($staffCriteria)->toArray(), function ($array, $staff) use ($participants) {
    //         $list = in_array($staff['gibbonPersonID'], $participants) ? 'destination' : 'source';
    //         $array[$list][$staff['gibbonPersonID']] = Format::name($staff['title'], $staff['preferredName'], $staff['surname'], 'Staff', true, true);
    //         return $array;
    //     });

    //      // Students
    //     $studentGateway = $container->get(StudentGateway::class);
    //     $studentCriteria = $studentGateway->newQueryCriteria()
    //     ->sortBy(['surname', 'preferredName']);

    //     $students = array_reduce($studentGateway->queryStudentsBySchoolYear($studentCriteria, $gibbonSchoolYearID)->toArray(), function ($array, $student) use ($participants) {
    //         $list = in_array($student['gibbonPersonID'], $participants) ? 'destination' : 'source';
    //         $array['students'][$list][$student['gibbonPersonID']] = Format::name($student['title'], $student['preferredName'], $student['surname'], 'Student', true) . ' - ' . $student['formGroup'].' ('.$student['yearGroup'].')'; 
    //         $array['form'][$student['gibbonPersonID']] = $student['formGroup'];
    //         $array['year'][$student['gibbonPersonID']] = $student['yearGroup'].'.'.$student['formGroup'];
    //         return $array;
    //     });

    //     $form = Form::create('participants', $session->get('absoluteURL').'/modules/'.$session->get('module'). '/calendar_event_addProcess.php');
    //     $form->addHiddenValue('address', $session->get('address'));
    //     $form->setFactory(DatabaseFormFactory::create($pdo));
    //     $form->setTitle(__('STEP 2 - Choose Participants'));
    //     $form->setClass('noIntBorder w-full');

    //     $form->addRow()->addHeading(__('Participants'));

    //     $row = $form->addRow();
    //     $col = $row->addColumn();
    //         $col->addLabel('teachers', __('Teachers'));
    //         $multiSelect = $col->addMultiSelect('teachers')->required();

    //         $multiSelect->source()->fromArray($teachers['source'] ?? []);
    //         $multiSelect->destination()->fromArray($teachers['destination'] ?? []);
            
    //     $row = $form->addRow();
    //         $row->addLabel('role', __('Role'));
    //         $row->addSelect('role')
    //             ->fromArray([
    //                 'Organiser' => __('Organiser'),
    //                 'Coach'     => __('Coach'),
    //                 'Assistant' => __('Assistant'),
    //                 'Other'     => __('Other')
    //             ]);

    //     $row = $form->addRow();
    //     $col = $row->addColumn();
    //         $col->addLabel('students', __('Students'));

    //         $multiSelect = $col->addMultiSelect('students')
    //             ->addSortableAttribute('Form', $students['form'])
    //             ->addSortableAttribute('Year', $students['year']);

    //         $multiSelect->source()->fromArray($students['students']['source'] ?? []);
    //         $multiSelect->destination()->fromArray($students['students']['destination'] ?? []);

    //     $targetOptions = [
    //         'Messenger' => __('Messenger Group'),
    //         'Activity'  => __('Activity Enrolment'),
    //         'ManualSelect'  => __('Select Students'),
    //     ];

    //     $row = $form->addRow();
    //         $row->addLabel('targetStudents', __('Students'));
    //         $row->addSelect('targetStudents')->fromArray($targetOptions)->required()->selected($targetStudents)->placeholder();

    //     $form->toggleVisibilityByClass('targetActivity')->onSelect('targetStudents')->when('Activity');
    //     $form->toggleVisibilityByClass('targetMessenger')->onSelect('targetStudents')->when('Messenger');
    //     $form->toggleVisibilityByClass('targetSelect')->onSelect('targetStudents')->when('ManualSelect');

    //     // Activity
    //     $activities = $container->get(ActivityGateway::class)->selectActivitiesBySchoolYear($session->get('gibbonSchoolYearID'))->fetchKeyPair();
    //     $row = $form->addRow()->addClass('targetActivity');
        
    //     $col = $row->addColumn()->addClass('right flex-wrap');
    //         $col->addLabel('gibbonActivityID', __('Activity'));
    //         $col->addSelect('gibbonActivityID')->fromArray($activities)->required()->placeholder();

    //     // Messenger Groups
    //     $groups = $container->get(GroupGateway::class)->selectGroupsBySchoolYear($session->get('gibbonSchoolYearID'))->fetchKeyPair();
    //     $row = $form->addRow()->addClass('targetMessenger');
    //         $row->addLabel('gibbonGroupID', __('Messenger Group'));
    //         $row->addSelect('gibbonGroupID')->fromArray($groups)->required()->selected($gibbonGroupID)->placeholder();

    //     // Select Students
    //     $studentGateway = $container->get(StudentGateway::class);
    //     $studentCriteria = $studentGateway->newQueryCriteria()
    //         ->sortBy(['surname', 'preferredName']);

    //     // $studentList = $studentGateway->queryStudentsBySchoolYear($studentCriteria, $session->get('gibbonSchoolYearID'));
    //     // $studentList = array_reduce($studentList->toArray(), function ($group, $student) use ($gibbonPersonIDList) {
    //     //     $list = in_array($student['gibbonPersonID'], $gibbonPersonIDList) ? 'destination' : 'source';
    //     //     $group['students'][$list][$student['gibbonPersonID']] = Format::name($student['title'], $student['preferredName'], $student['surname'], 'Student', true) . ' - ' . $student['formGroup']; 
    //     //     $group['form'][$student['gibbonPersonID']] = $student['formGroup'];
    //     //     return $group;
    //     // });

    //     // $col = $form->addRow()->addClass('targetSelect')->addColumn();
    //     //     $col->addLabel('gibbonPersonIDList', __('Students'));
    //     //     $select = $col->addMultiSelect('gibbonPersonIDList')->isRequired();
    //     //     $select->addSortableAttribute(__('Form Group'), $studentList['form']);
    //     //     $select->source()->fromArray($studentList['students']['source'] ?? []);
    //     //     $select->destination()->fromArray($studentList['students']['destination'] ?? []);

    //     $row = $form->addRow();
    //         $row->addSearchSubmit($session);

    //     echo $form->getOutput();

    //     if (empty($gibbonActivityID) && empty($gibbonGroupID) && empty($gibbonPersonIDList)) {
    //         return;
    //     }

       

    //     $form->addHiddenValue('address', $session->get('address'));
    //     $form->addHiddenValue('targetStudents', $targetStudents);
    //     $form->addHiddenValue('gibbonActivityID', $gibbonActivityID);
    //     $form->addHiddenValue('gibbonGroupID', $gibbonGroupID);
    //     $form->addHiddenValue('gibbonPersonIDList', implode(',', $gibbonPersonIDList));
    //     //$form->addHiddenValue('count', count($students));

    //     $form->addRow()->addHeading('Confirm Participants', __('Confirm Participants'));

    //     $row = $form->addRow();
    //         $row->addSubmit()->addClass('flex-shrink');

    //     echo $form->getOutput();
    
