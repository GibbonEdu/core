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
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Domain\Messenger\GroupGateway;
use Gibbon\Domain\Students\StudentGateway;
use Gibbon\Domain\Activities\ActivityGateway;
use Gibbon\Domain\Calendar\CalendarEventGateway;
use Gibbon\Domain\Calendar\CalendarEventPersonGateway;


if (isActionAccessible($guid, $connection2, '/modules/Calendar/calendar_event_enrolment.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $gibbonCalendarEventID = (isset($_GET['gibbonCalendarEventID'])) ? $_GET['gibbonCalendarEventID'] : null;
    $calendarEventGateway = $container->get(CalendarEventGateway::class);
    $calendarEventPersonGateway = $container->get(CalendarEventPersonGateway::class);

    $urlParams = ['gibbonCalendarEventID' => $_GET['gibbonCalendarEventID']];

    $page->breadcrumbs
        ->add(__('Manage Activities'), 'calendar_event_manage.php')
        ->add(__('Event Enrolment'), 'calendar_event_enrolment.php',  $urlParams)
        ->add(__('Add Student'));

    if (empty($gibbonCalendarEventID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $event = $calendarEventGateway->getByID($gibbonCalendarEventID);
    if (empty($event)) {
        $page->addError(__('The selected record does not exist, or you do not have access to it.'));
        return;
    }

    $form = Form::create('studentEnrolment', $session->get('absoluteURL').'/modules/'.$session->get('module')."/calendar_event_enrolment_addProcess.php?gibbonCalendarEventID=".$gibbonCalendarEventID);
    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->addHiddenValue('address', $session->get('address'));
    
    $form->setTitle(__('Choose Students'));

     $targetOptions = [
        'Messenger'    => __('Messenger Group'),
        'Activity' => __('Activity Enrolment'),
        'Class'   => __('Class Enrolment'),
        'manualSelect'   => __('Select Manually'),
    ];

    $row = $form->addRow();
        $row->addLabel('targetStudents', __('Students'));
        $row->addSelect('targetStudents')->fromArray($targetOptions)->required()->placeholder();

    $form->toggleVisibilityByClass('targetActivity')->onSelect('targetStudents')->when('Activity');
    $form->toggleVisibilityByClass('targetMessenger')->onSelect('targetStudents')->when('Messenger');
    $form->toggleVisibilityByClass('targetClass')->onSelect('targetStudents')->when('Class');
    $form->toggleVisibilityByClass('targetSelect')->onSelect('targetStudents')->when('manualSelect');

    // Activity
    $activities = $container->get(ActivityGateway::class)->selectActivitiesBySchoolYear($session->get('gibbonSchoolYearID'))->fetchKeyPair();
    $row = $form->addRow()->addClass('targetActivity');
        $row->addLabel('gibbonActivityID', __('Activity'));
        $row->addSelect('gibbonActivityID')->fromArray($activities)->required()->placeholder();

    // Messenger Groups
    $groups = $container->get(GroupGateway::class)->selectGroupsBySchoolYear($session->get('gibbonSchoolYearID'))->fetchKeyPair();
    $row = $form->addRow()->addClass('targetMessenger');
        $row->addLabel('gibbonGroupID', __('Messenger Group'));
        $row->addSelect('gibbonGroupID')->fromArray($groups)->required()->placeholder();

    // Class Enrolments
    $row = $form->addRow()->addClass('targetClass');
        $row->addLabel('gibbonCourseClassID', __('Class'));
        $row->addSelectClass('gibbonCourseClassID', $session->get('gibbonSchoolYearID'), $session->get('gibbonPersonID'))
        ->required()
        ->placeholder();

    // Select Attendees
    $row = $form->addRow();
        $col = $row->addColumn()->addClass('targetSelect')->addColumn();
            $col->addLabel('participants', __('Attendees'));
            $col->addSelectUsers('participants', $session->get('gibbonSchoolYearID'), ['includeStudents' => true, 'useMultiSelect' => true])
                ->required()
                ->mergeGroupings();

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}