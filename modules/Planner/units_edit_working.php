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
use Gibbon\Domain\Planner\UnitBlockGateway;
use Gibbon\Domain\Planner\PlannerEntryGateway;
use Gibbon\Domain\Planner\UnitClassBlockGateway;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Http\Url;

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
    ->add(__('Edit Working Copy'));

if (isActionAccessible($guid, $connection2, '/modules/Planner/units_edit_working.php') == false) {
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
    // Check if course & school year specified
    if ($gibbonCourseID == '' or $gibbonSchoolYearID == '' or $gibbonCourseClassID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $plannerEntryGateway = $container->get(PlannerEntryGateway::class);
    $unitBlockGateway = $container->get(UnitBlockGateway::class);
    $unitClassBlockGateway = $container->get(UnitClassBlockGateway::class);
    $courseGateway = $container->get(CourseGateway::class);
    $unitGateway = $container->get(UnitGateway::class);

    // Check access to specified course
    if ($highestAction == 'Unit Planner_all') {
        $result = $courseGateway->selectCourseDetailsByClass($gibbonCourseClassID);
    } elseif ($highestAction == 'Unit Planner_learningAreas') {
        $result = $courseGateway->selectCourseDetailsByClassAndPerson($gibbonCourseClassID, $session->get('gibbonPersonID'));
    }

    if ($result->rowCount() != 1) {
        $page->addError(__('The selected record does not exist, or you do not have access to it.'));
        return;
    }

    $values = $result->fetch();

    // Get the unit details
    $unit = $unitGateway->getByID($urlParams['gibbonUnitID'], ['name']);
    $values['unit'] = $unit['name'] ?? '';

    if (empty($gibbonUnitClassID)) {
        $urlParams['gibbonUnitClassID'] = $unitGateway->getUnitClassIDByUnit($urlParams['gibbonUnitID'], $urlParams['gibbonCourseClassID']);
    }

    if (empty($unit) || empty($urlParams['gibbonUnitClassID'])) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    // DETAILS
    $table = DataTable::createDetails('unit');

    $table->addColumn('schoolYear', __('School Year'));
    $table->addColumn('course', __('Class'))->format(Format::using('courseClassName', ['course', 'class']));
    $table->addColumn('unit', __('Unit'));

    echo $table->render([$values]);

    // Get unit blocks
    $unitBlocks = $unitBlockGateway->selectBlocksByUnit($gibbonUnitID)->fetchAll();

    // FORM
    $form = Form::createBlank('blocks', $session->get('absoluteURL').'/modules/Planner/units_edit_workingProcess.php?'.http_build_query($urlParams));
    
    $form->setTitle(__('Lessons & Blocks'));

    $form->setDescription('<p>'.__('You can now add your unit blocks using the dropdown menu in each lesson. Blocks can be dragged from one lesson to another.').'</p>'.Format::alert(__('Deploying lessons only works for units with smart blocks. If you have duplicated a unit from a past year that does not have smart blocks, be sure to edit the lessons manually and assign a new date to them.'), 'message'));

    $form->addHiddenValue('address', $session->get('address'));

    $form->addHeaderAction('add', __('Add Lessons'))
        ->setURL(Url::fromModuleRoute('Planner', 'units_edit_working_add.php')->withQueryParams($urlParams)->withFragment('now'))

        ->displayLabel();
    $form->addHeaderAction('planner', __('View Planner'))
        ->setURL(Url::fromModuleRoute('Planner', 'planner.php')->withQueryParams($urlParams + ['viewBy' => 'class']))
        ->displayLabel();

    // Smart Block Template
    $blockTemplate = $form->getFactory()->createTable()->setClass('blank w-full');
        $row = $blockTemplate->addRow();
        $row->addTextField('title')
            ->setClass('title focus:bg-white')
            ->placeholder(__('Title'));

        $row = $blockTemplate->addRow()->addClass('flex justify-between mt-1');
            $row->addTextField('type')->placeholder(__('type (e.g. discussion, outcome)'))
                ->setClass('flex-1 focus:bg-white mr-1');
            $row->addTextField('length')->placeholder(__('length (min)'))
                ->setClass('w-48 focus:bg-white')->prepend('');

        $smartBlockTemplate = $container->get(SettingGateway::class)->getSettingByScope('Planner', 'smartBlockTemplate');
        $col = $blockTemplate->addRow()->addClass('showHide w-full')->addColumn();
            $col->addLabel('contentsLabel', __('Block Contents'));
            $col->addTextArea('contents')->addData('tinymce')->addData('media', '1')->setRows(20)->setValue($smartBlockTemplate);

        $col = $blockTemplate->addRow()->addClass('showHide w-full')->addColumn();
            $col->addLabel('teachersNotesLabel', __('Teacher\'s Notes'));
            $col->addTextArea('teachersNotes')->addData('tinymce')->addData('media', '1')->setRows(5);

    $toolbar = $form->getFactory()->createRow()->addClass('flex flex-wrap items-center gap-2');
    $toolbar->addButton(__('Deploy All'))->setSize('sm')->setIcon('solid', 'arrow-down-on-square')->setAttribute('@click', 'handleDeployAll()');
    $toolbar->addButton(__('Deploy Each'))->setSize('sm')->setIcon('solid', 'arrow-down-on-square-stack')->setAttribute('@click', 'handleDeployEach()');
    $toolbar->addButton(__('Rename Lessons'))->setSize('sm')->setIcon('solid', 'pencil-square')->setAttribute('@click', 'handleRenameLessons()');
    $toolbar->addButton(__('Clear All'))->setSize('sm')->setIcon('solid', 'delete')->setAttribute('@click', 'handleClearAll()');

    // Display lessons and blocks
    $lessons = $plannerEntryGateway->selectPlannerEntriesByUnitAndClass($gibbonUnitID, $gibbonCourseClassID)->fetchAll();
    $blockCount = 0;

    $lessons = array_map(function($lesson) use ($plannerEntryGateway) {
        $times = $plannerEntryGateway->getPlannerTTByClassTimes($lesson['gibbonCourseClassID'], $lesson['date'], $lesson['timeStart'], $lesson['timeEnd']);
        $lesson = array_merge($lesson, $times);
        return $lesson;
    }, $lessons);

    $lessonBlocks = $unitClassBlockGateway->selectBlocksByUnitAndClass($gibbonUnitID, $gibbonCourseClassID)->fetchGrouped();
    $lessonBlockCount = array_reduce($lessonBlocks, function ($group, $item) {
        return $group + count($item);
    }, 0);

    $smartBlocks = [];
    $indexStart = 0;
    foreach ($lessons as $index => $lesson) {
        $blocks = $lessonBlocks[$lesson['gibbonPlannerEntryID']] ?? [];
        $customBlocks = $form->getFactory()->createCustomBlocks('blocks', $session)
            ->fromTemplate($blockTemplate)
            ->setID('blocks'.$index.'_')
            ->addClass('lesson'.$lesson['gibbonPlannerEntryID'])
            ->settings([
                'inputNameStrategy' => 'object',
                'addOnEvent'        => 'click',
                'sortable'          => true,
                'sortGroup'         => 'unitBlocks',
                'uniqueID'          => 'gibbonUnitClassBlockID',
                'orderName'         => 'order',
                'indexStart'        => $indexStart,
                'indexNext'         => $lessonBlockCount,
                'hiddenInputs'      => 'gibbonUnitBlockID,complete',
            ])
            ->placeholder('')
            ->addBlocks($blocks);

        $customBlocks->addToolButton('')->setSize('sm')->addClass('addBlock')->setTitle(__('Add Block'))->setIcon('solid', 'add', '', ['strokeWidth' => 2]);

        if ($index > 0) {
            $customBlocks->addToolButton('')->setSize('sm')->setIcon('solid', 'arrow-up-circle')->setTitle(__('Move Back'))->setAttribute('@click', 'moveBlocks(blocks, '.$index.', '.($index - 1).')');
        }
        if ($index < count($lessons) - 1 ) {
            $customBlocks->addToolButton('')->setSize('sm')->setIcon('solid', 'arrow-down-circle')->setTitle(__('Move Forward'))->setAttribute('@click', 'moveBlocks(blocks, '.$index.', '.($index + 1).')');
        }

        $smartBlocks[$lesson['gibbonPlannerEntryID']] = $customBlocks;
        $indexStart += count($blocks);
    }

    // Display the drag-drop block editor
    $form->addRow()->addContent($page->fetchFromTemplate('unitBlocks.twig.html', [
        'toolbar'      => $toolbar,
        'lessons'      => $lessons,
        'unitName'     => $unit['name'],
        'unitBlocks'   => $unitBlocks,
        'lessonBlocks' => $lessonBlocks,
        'smartBlocks'  => $smartBlocks,
    ]));

    $form->addHiddenValue('unitBlockCount', count($unitBlocks));

    $row = $form->addRow()->addSubmit();

    echo $form->getOutput();

    // Print sidebar
    $page->addSidebarExtra(sidebarExtraUnits($guid, $connection2, $gibbonCourseID, $gibbonSchoolYearID));
}
