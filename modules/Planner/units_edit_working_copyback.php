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
use Gibbon\Tables\DataTable;
use Gibbon\Domain\Timetable\CourseGateway;

// Module includes
require_once __DIR__ . '/moduleFunctions.php';

$urlParams = [
    'gibbonSchoolYearID' => $_GET['gibbonSchoolYearID'] ?? '',
    'gibbonCourseID' => $_GET['gibbonCourseID'] ?? '',
    'gibbonCourseClassID' => $_GET['gibbonCourseClassID'] ?? '',
    'gibbonUnitID' => $_GET['gibbonUnitID'] ?? '',
    'gibbonUnitBlockID' => $_GET['gibbonUnitBlockID'] ?? '',
    'gibbonUnitClassBlockID' => $_GET['gibbonUnitClassBlockID'] ?? '',
    'gibbonUnitClassID' => $_GET['gibbonUnitClassID'] ?? '',
];

$page->breadcrumbs
    ->add(__('Unit Planner'), 'units.php', $urlParams)
    ->add(__('Edit Unit'), 'units_edit.php', $urlParams)
    ->add(__('Edit Working Copy'), 'units_edit_working.php', $urlParams)
    ->add(__('Copy Back Block'));

if (isActionAccessible($guid, $connection2, '/modules/Planner/units_edit_working_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Get action with highest precendence
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
    if ($urlParams['gibbonCourseID'] == '' or $urlParams['gibbonSchoolYearID'] == '' or $urlParams['gibbonCourseClassID'] == '' or $urlParams['gibbonUnitClassID'] == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    } 

    $courseGateway = $container->get(CourseGateway::class);

    // Check access to specified course
    if ($highestAction == 'Unit Planner_all') {
        $result = $courseGateway->selectCourseDetailsByClass($urlParams['gibbonCourseClassID']);
    } elseif ($highestAction == 'Unit Planner_learningAreas') {
        $result = $courseGateway->selectCourseDetailsByClassAndPerson($urlParams['gibbonCourseClassID'], $gibbon->session->get('gibbonPersonID'));
    }

    if ($result->rowCount() != 1) {
        $page->addError(__('The selected record does not exist, or you do not have access to it.'));
        return;
    } 
            
    $values = $result->fetch();

    // Check if unit specified
    if ($urlParams['gibbonUnitID'] == '' or $urlParams['gibbonUnitBlockID'] == '' or $urlParams['gibbonUnitClassBlockID'] == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    } 

    $data = ['gibbonUnitID' => $urlParams['gibbonUnitID'], 'gibbonCourseID' => $urlParams['gibbonCourseID'], 'gibbonUnitBlockID' => $urlParams['gibbonUnitBlockID'], 'gibbonUnitClassBlockID' => $urlParams['gibbonUnitClassBlockID']];
    $sql = 'SELECT gibbonUnitClassBlock.title AS block, gibbonCourse.nameShort AS courseName, gibbonUnit.name as unit FROM gibbonUnit JOIN gibbonCourse ON (gibbonUnit.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonUnitBlock ON (gibbonUnitBlock.gibbonUnitID=gibbonUnit.gibbonUnitID) JOIN gibbonUnitClassBlock ON (gibbonUnitClassBlock.gibbonUnitBlockID=gibbonUnitBlock.gibbonUnitBlockID) WHERE gibbonUnitClassBlockID=:gibbonUnitClassBlockID AND gibbonUnitBlock.gibbonUnitBlockID=:gibbonUnitBlockID AND gibbonUnit.gibbonUnitID=:gibbonUnitID AND gibbonUnit.gibbonCourseID=:gibbonCourseID';
    $result = $pdo->select($sql, $data);

    if ($result->rowCount() != 1) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }
    $values += $result->fetch();

    // DETAILS
    $table = DataTable::createDetails('unit');

    $table->addColumn('schoolYear', __('School Year'));
    $table->addColumn('course', __('Class'))->format(Format::using('courseClassName', ['course', 'class']));
    $table->addColumn('unit', __('Unit'));

    $table->addColumn('block', __('Block Title'));

    echo $table->render([$values]);

    // FORM
    $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/units_edit_working_copybackProcess.php?'.http_build_query($urlParams));

    $form->setTitle(__('Copy Back Block'));
    $form->setDescription(__('This action will use the selected block to replace the equivalent block in the master unit. The option below also lets you replace the equivalent block in all other working units within the unit.'));

    $form->addHiddenValue('address', $_SESSION[$guid]['address']);
    $form->addHiddenValues($urlParams);

    $row = $form->addRow();
        $row->addLabel('working', __('Include Working Units?'));
        $row->addYesNo('working')->required()->selected('N');

    $form->addRow()->addConfirmSubmit();

    echo $form->getOutput();
    
    // Print sidebar
    $_SESSION[$guid]['sidebarExtra'] = sidebarExtraUnits($guid, $connection2, $urlParams['gibbonCourseID'], $urlParams['gibbonSchoolYearID']);
}
