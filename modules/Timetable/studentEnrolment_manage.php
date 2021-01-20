<?php

use Gibbon\Domain\DataSet;
use Gibbon\Tables\DataTable;
use Gibbon\Domain\Timetable\CourseGateway;
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

if (isActionAccessible($guid, $connection2, '/modules/Timetable/studentEnrolment_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs->add(__('Manage Student Enrolment'));

    $gibbonSchoolYearID = $gibbon->session->get('gibbonSchoolYearID');
    $gibbonPersonID = $gibbon->session->get('gibbonPersonID');

    echo '<p>';
    echo __('This page allows departmental Coordinators and Assistant Coordinators to manage student enolment within their department.');
    echo '</p>';

    $courseGateway = $container->get(CourseGateway::class);
    
    // QUERY
    $criteria = $courseGateway->newQueryCriteria()
        ->sortBy(['gibbonCourse.nameShort', 'gibbonCourse.name'])
        ->fromPOST();
        
    $courses = $courseGateway->queryCoursesByDepartmentStaff($criteria, $gibbonSchoolYearID, $gibbonPersonID)->toArray();

    if (empty($courses)) {
        $page->addError(__('There are no records to display.'));
        return;
    }

    foreach ($courses as $course) {
        $classes = $courseGateway->selectClassesByCourseID($course['gibbonCourseID'])->fetchAll();

        // DATA TABLE
        $table = DataTable::create('courseClassEnrolment');
        $table->setTitle($course['nameShort'].' ('.$course['name'].')');

        $table->addColumn('name', __('Name'));
        $table->addColumn('nameShort', __('Short Name'));
        $table->addColumn('participantsActive', __('Participants'))->description(__('Active'));
        $table->addColumn('participantsExpected', __('Participants'))->description(__('Expected'));
        $table->addColumn('participantsTotal', __('Participants'))->description(__('Total'));

        // ACTIONS
        $table->addActionColumn()
            ->addParam('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->addParam('gibbonCourseID')
            ->addParam('gibbonCourseClassID')
            ->format(function ($class, $actions) {
                $actions->addAction('edit', __('Edit'))
                    ->setURL('/modules/Timetable/studentEnrolment_manage_edit.php');
            });

        echo $table->render($classes);
    }
}
