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
use Gibbon\Domain\Planner\UnitGateway;
use Gibbon\Domain\Timetable\CourseGateway;

// Module includes
require_once __DIR__ . '/moduleFunctions.php';

$urlParams = [
    'gibbonSchoolYearID' => $_GET['gibbonSchoolYearID'] ?? '',
    'gibbonCourseID' => $_GET['gibbonCourseID'] ?? '',
    'gibbonCourseClassID' => $_GET['gibbonCourseClassID'] ?? '',
    'gibbonUnitID' => $_GET['gibbonUnitID'] ?? '',
    'gibbonUnitClassID' => $_GET['gibbonUnitClassID'] ?? '',
];

$page->breadcrumbs
    ->add(__('Unit Planner'), 'units.php', $urlParams)
    ->add(__('Edit Unit'), 'units_edit.php', $urlParams)
    ->add(__('Smart Block'));

if (isActionAccessible($guid, $connection2, '/modules/Planner/units_edit_smartBlockify.php') == false) {
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
        $result = $courseGateway->selectCourseDetailsByClassAndPerson($urlParams['gibbonCourseClassID'], $session->get('gibbonPersonID'));
    }

    if ($result->rowCount() != 1) {
        $page->addError(__('The selected record does not exist, or you do not have access to it.'));
        return;
    }

    $values = $result->fetch();

    // Get the unit details
    $unit = $container->get(UnitGateway::class)->getByID($urlParams['gibbonUnitID'], ['name']);
    $values['unit'] = $unit['name'] ?? '';

    if (empty($unit)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    // DETAILS
    $table = DataTable::createDetails('unit');

    $table->addColumn('schoolYear', __('School Year'));
    $table->addColumn('course', __('Class'))->format(Format::using('courseClassName', ['course', 'class']));
    $table->addColumn('unit', __('Unit'));

    echo $table->render([$values]);

    // FORM
    $form = Form::create('action', $session->get('absoluteURL').'/modules/'.$session->get('module').'/units_edit_smartBlockifyProcess.php?'.http_build_query($urlParams));

    $form->setTitle(__('Smart Blockify'));
    $form->setDescription(sprintf(__('This function allows you to take all of the lesson content (Details and Teacher\'s Notes) from the selected working unit (%1$s in %2$s) and use them to create new Smart Blocks in the master unit, which are then used to replace the original content in the working unit. In this way you can quickl "smart blockify" an existing unit.'), $values['name'], Format::courseClassName($values['course'], $values['class'])));

    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValues($urlParams);

    $row = $form->addRow();
        $col = $row->addColumn();
        $col->addContent(__('Are you sure you want to proceed with this request?'))->wrap('<strong>', '</strong>');
        $col->addContent(__('This operation cannot be undone, and may lead to loss of vital data in your system. PROCEED WITH CAUTION!'))->wrap('<span style="color: #cc0000"><i>', '</i></span>');

    $form->addRow()->addConfirmSubmit();

    echo $form->getOutput();

    // Print sidebar
    $session->set('sidebarExtra', sidebarExtraUnits($guid, $connection2, $urlParams['gibbonCourseID'], $urlParams['gibbonSchoolYearID']));
}
