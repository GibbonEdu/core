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
use Gibbon\Domain\DataSet;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Domain\Planner\UnitGateway;
use Gibbon\Forms\Prefab\BulkActionForm;
use Gibbon\Domain\Timetable\CourseGateway;
use Gibbon\Domain\Planner\PlannerEntryGateway;

// Module includes
require_once __DIR__ . '/moduleFunctions.php';

$gibbonSchoolYearID = $_GET['gibbonSchoolYearID'] ?? '';
$gibbonCourseID = $_GET['gibbonCourseID'] ?? '';
$gibbonCourseClassID = $_GET['gibbonCourseClassID'] ?? '';
$gibbonUnitID = $_GET['gibbonUnitID'] ?? '';
$gibbonUnitClassID = $_GET['gibbonUnitClassID'] ?? '';

$urlParams = compact('gibbonSchoolYearID', 'gibbonCourseID', 'gibbonCourseClassID', 'gibbonUnitID', 'gibbonUnitClassID');

$page->breadcrumbs
    ->add(__('Unit Planner'), 'units.php', $urlParams)
    ->add(__('Edit Unit'), 'units_edit.php', $urlParams)
    ->add(__('Edit Working Copy'), 'units_edit_working.php', $urlParams)
    ->add(__('Add Lessons'));

if (isActionAccessible($guid, $connection2, '/modules/Planner/units_edit_working_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Get action with highest precedence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        $page->addError(__('The highest grouped action cannot be determined.'));
        return;
    }
    
    // Proceed!
    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    // Check if course & school year specified
    if ($gibbonCourseID == '' or $gibbonSchoolYearID == '' or $gibbonCourseClassID == '' or $gibbonUnitClassID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    } 

    $courseGateway = $container->get(CourseGateway::class);

    // Check access to specified course
    if ($highestAction == 'Unit Planner_all') {
        $result = $courseGateway->selectCourseDetailsByClass($gibbonCourseClassID);
    } elseif ($highestAction == 'Unit Planner_learningAreas') {
        $result = $courseGateway->selectCourseDetailsByClassAndPerson($gibbonCourseClassID, $gibbon->session->get('gibbonPersonID'));
    }

    if ($result->rowCount() != 1) {
        $page->addError(__('The selected record does not exist, or you do not have access to it.'));
        return;
    } 

    $values = $result->fetch();

    // Get the unit details
    $unit = $container->get(UnitGateway::class)->getByID($urlParams['gibbonUnitID'], ['name']);
    $values['unit'] = $unit['name'] ?? '';

    // DETAILS
    $table = DataTable::createDetails('unit');

    $table->addColumn('schoolYear', __('School Year'));
    $table->addColumn('course', __('Class'))->format(Format::using('courseClassName', ['course', 'class']));
    $table->addColumn('unit', __('Unit'));
    
    echo $table->render([$values]);

    $plannerEntryGateway = $container->get(PlannerEntryGateway::class);

    $criteria = $plannerEntryGateway->newQueryCriteria()
        ->sortBy(['gibbonTTDayDate.date', 'gibbonTTColumnRow.timestart'])
        ->fromPOST();

    $lessonTimes = $plannerEntryGateway->queryPlannerTimeSlotsByClass($criteria, $gibbonSchoolYearID, $gibbonCourseClassID);

    $form = Form::create('action', $gibbon->session->get('absoluteURL').'/modules/Planner/units_edit_working_addProcess.php?'.http_build_query($urlParams));
    $form->setTitle(__('Choose Lessons'));
    $form->setDescription(__('Use the table below to select the lessons you wish to deploy this unit to. Only lessons without existing plans can be included in the deployment.'));

    $form->setClass('w-full blank bulkActionForm');
    $form->addHiddenValue('address', $gibbon->session->get('address'));

    $table = $form->addRow()->addDataTable('lessons', $criteria)->withData($lessonTimes);
    $table->addMetaData('hidePagination', true);

    $lastTerm = '';
    $lastTermDay = '';
    $table->modifyRows(function ($lesson, $row) use (&$lastTerm, &$lastTermDay) {
        $format = '<tr class="dull"><td class="font-bold">%1$s</td><td colspan="9">%2$s</td></tr>';

        // Add term start and end dates to the table
        if ($lesson['termName'] != $lastTerm) {
            $row->prepend(sprintf($format, __('Start of {termName}', ['termName' => $lesson['termName']]), Format::date($lesson['firstDay'])));
            if (!empty($lastTerm)) {
                $row->prepend(sprintf($format, __('End of {termName}', ['termName' => $lastTerm]), Format::date($lastTermDay)));
            }

            $lastTerm = $lesson['termName'];
            $lastTermDay = $lesson['lastDay'];
        }

        // Add special days to the table
        if (!empty($lesson['specialDay'])) {
            $row->addClass('hidden');
            $row->append(sprintf($format, $lesson['specialDay'], Format::date($lesson['date'])));
        }

        if ($lesson['date'] < date('Y-m-d')) $row->addClass('error');
        return $row;
    });

    $count = 0;
    $table->addColumn('lessonNum', __('Lesson Number'))
        ->notSortable()
        ->format(function($lesson) use (&$count) {
            if (!empty($lesson['specialDay'])) return '';
            $count++;
            return __('Lesson {count}', ['count' => $count]);
        });

    $table->addColumn('date', __('Date'))
        ->notSortable()
        ->format(Format::using('date', 'date'));

    $table->addColumn('day', __('Day'))
        ->notSortable()
        ->format(Format::using('date', ['date', 'D']));

    $table->addColumn('month', __('Month'))
        ->notSortable()
        ->format(Format::using('date', ['date', 'M']));

    $table->addColumn('period', __('TT Period/Time'))
        ->notSortable()
        ->format(function($lesson) {
            return $lesson['period'].'<br/>'.Format::timeRange($lesson['timeStart'], $lesson['timeEnd']);
        });

    $table->addColumn('lesson', __('Planned Lesson'))
        ->notSortable();

    $table->addCheckboxColumn('lessons', 'identifier')
        ->width('8%')
        ->format(function($lesson) {
            return !empty($lesson['gibbonPlannerEntryID']) ? ' ' : null;
        });

    $form->addRow()->addSubmit();

    echo $form->getOutput();

    // Print sidebar
    $page->addSidebarExtra(sidebarExtraUnits($guid, $connection2, $gibbonCourseID, $gibbonSchoolYearID));
}
