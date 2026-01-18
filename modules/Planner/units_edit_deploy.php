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
use Gibbon\Module\Planner\Forms\PlannerFormFactory;
use Gibbon\Domain\System\SettingGateway;

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
    ->add(__('Deploy Working Copy'));

if (isActionAccessible($guid, $connection2, '/modules/Planner/units_edit_deploy.php') == false) {
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
    if ($gibbonCourseID == '' or $gibbonSchoolYearID == '' or $gibbonCourseClassID == '' or $gibbonUnitClassID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $courseGateway = $container->get(CourseGateway::class);

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
    $unit = $container->get(UnitGateway::class)->getByID($urlParams['gibbonUnitID'], ['name']);
    $values['unit'] = $unit['name'] ?? '';

    if (empty($unit)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    $step = $_GET['step'] ?? 1;
    $step = $step >=1 && $step <= 3 ? $step : 1;

    // DETAILS
    $table = DataTable::createDetails('unit');

    $table->addColumn('schoolYear', __('School Year'));
    $table->addColumn('course', __('Class'))->format(Format::using('courseClassName', ['course', 'class']));
    $table->addColumn('unit', __('Unit'));

    echo $table->render([$values]);

    $plannerEntryGateway = $container->get(PlannerEntryGateway::class);
    $unitBlockGateway = $container->get(UnitBlockGateway::class);

    // Step 1
    if ($step == 1) {
        $criteria = $plannerEntryGateway->newQueryCriteria()
            ->sortBy(['gibbonTTDayDate.date', 'gibbonTTColumnRow.timestart'])
            ->fromPOST();

        $lessonTimes = $plannerEntryGateway->queryPlannerTimeSlotsByClass($criteria, $gibbonSchoolYearID, $gibbonCourseClassID);

        $form = Form::createBlank('action', $session->get('absoluteURL').'/index.php?q=/modules/Planner/units_edit_deploy.php&step=2&'.http_build_query($urlParams));
        $form->setTitle(__('Step 1 - Select Lessons'));
        $form->setDescription(__('Use the table below to select the lessons you wish to deploy this unit to. Only lessons without existing plans can be included in the deployment.'));

        $form->setClass('bulkActionForm');

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
                return $lesson['period'].'<br/>'.Format::timeRange($lesson['timeStart'], $lesson['timeEnd']).'<br>'.($lesson['spaceName'] ?? '');
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
    }
    // Step 2
    if ($step == 2) {
        $lessons = [];
        $lessonsChecked = $_POST['lessons'] ?? [];

        // Get unit blocks
        $unitBlocks = $unitBlockGateway->selectBlocksByUnit($gibbonUnitID)->fetchAll();

        // Get date and period information for each lesson
        foreach ($lessonsChecked as $lesson) {
            list($gibbonTTDayRowClassID, $gibbonTTDayDateID) = explode('-', $lesson);
            $lessonData = $plannerEntryGateway->getPlannerTTByIDs($gibbonTTDayRowClassID, $gibbonTTDayDateID);
            $lessonData['gibbonPlannerEntryID'] = $lesson;
            $lessons[] = $lessonData;
        }

        // FORM
        $form = Form::createBlank('blocks', $session->get('absoluteURL').'/modules/Planner/units_edit_deployProcess.php?'.http_build_query($urlParams));
        $form->setFactory(PlannerFormFactory::create($pdo));
        $form->setTitle(__('Step 2 - Distribute Blocks'));


        $form->setDescription('<p>'.__('You can now add your unit blocks using the dropdown menu in each lesson. Blocks can be dragged from one lesson to another.').'</p>');
        
        $form->addHiddenValue('address', $session->get('address'));

        $deployIndex = 0;
        $deployed = 0;

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

        // Smart blocks
        $smartBlocks = [];
        $indexStart = 0;
        foreach ($lessons as $index => $lesson) {
            $customBlocks = $form->getFactory()->createCustomBlocks('blocks', $session)
                ->fromTemplate($blockTemplate)
                ->setID('blocks'.$index)
                ->addClass('lesson'.$index)
                ->settings([
                    'inputNameStrategy' => 'object',
                    'addOnEvent'        => 'click',
                    'sortable'          => true,
                    'sortGroup'         => 'unitBlocks',
                    'uniqueID'          => 'gibbonUnitClassBlockID',
                    'orderName'         => 'order',
                    'hiddenInputs'      => 'gibbonUnitBlockID,gibbonTTDayRowClassID,gibbonTTDayDateID',
                ])
                ->placeholder('');

            // $customBlocks->addToolButton('')->addClass('addBlock')->setTitle(__('Add Block'))->setIcon('outline', 'add', '', ['strokeWidth' => 2]);

            $smartBlocks[$lesson['gibbonPlannerEntryID']] = $customBlocks;
        }

        // Display the drag-drop block editor
        $form->addRow()->addContent($page->fetchFromTemplate('unitBlocks.twig.html', [
            'toolbar'     => $toolbar,
            'lessons'     => $lessons,
            'unitName'    => $unit['name'],
            'unitBlocks'  => $unitBlocks ?? [],
            'smartBlocks' => $smartBlocks ?? [],
        ]));

        // foreach ($lessons as $index => $lesson) {

        //     $form->addRow()->addHeading(($index+1).'. '.Format::dateReadable($lesson['date'], Format::FULL))
        //         ->append(Format::small($lesson['period'].' ('.Format::timeRange($lesson['timeStart'], $lesson['timeEnd']).')'));

        //     $col = $form->addRow()->addClass('')->addColumn()->addClass('blockLesson');

        //     $col->addContent('<input type="hidden" name="order[]" value="lessonHeader-'.$index.'">');
        //     $form->addHiddenValue('date'.$index, $lesson['date']);
        //     $form->addHiddenValue('timeStart'.$index, $lesson['timeStart']);
        //     $form->addHiddenValue('timeEnd'.$index, $lesson['timeEnd']);

        //     $col->addColumn()
        //         ->setClass('-mt-4')
        //         ->addSelect('blockAdd')
        //         ->fromArray($blockSelect)
        //         ->placeholder()
        //         ->setClass('blockAdd float-right w-48')
        //         ->prepend(Format::small(__('Add Block').':'));

        //     $content = '';

        //     // Attempt auto deploy
        //     $spinCount = 0;
        //     $length = ((strtotime($lesson['date'].' '.$lesson['timeEnd']) - strtotime($lesson['date'].' '.$lesson['timeStart'])) / 60);

        //     while ($spinCount < count($blocks) and $length > 0) {
        //         if (isset($blocks[$deployIndex])) {
        //             if (empty($blocks[$deployIndex]['length'])) {
        //                 ++$deployIndex;
        //             } else {
        //                 if (($length - $blocks[$deployIndex]['length']) >= 0) {
        //                     ob_start();
        //                     //makeBlock($guid,  $connection2, $deployed, $mode = 'workingDeploy', $blocks[$deployIndex]['title'], $blocks[$deployIndex]['type'], $blocks[$deployIndex]['length'], $blocks[$deployIndex]['contents'], 'N', $blocks[$deployIndex]['gibbonUnitBlockID'], '', $blocks[$deployIndex]['teachersNotes'], true);
        //                     $blockContent = ob_get_clean();

        //                     $content .= '<div class="draggable z-100">'.$blockContent.'</div>';

        //                     $length = $length - $blocks[$deployIndex]['length'];
        //                     ++$deployIndex;
        //                 }
        //             }
        //         }

        //         ++$spinCount;
        //         ++$deployed;
        //     }

        //     $col->addContent('<div class="sortableArea py-2 mt-16">'.$content.'</div>');
        // }

        $section = $form->addRow()->addClass('bg-gray-50 border px-4 sm:px-8 py-4 mb-4 mr-4 rounded-md');
        $section->addRow()->addHeading('Access', __('Access'));

        $row = $section->addRow()->setClass('flex w-full justify-between items-center py-2');
            $row->addLabel('viewableStudents', __('Viewable by Students'));
            $row->addYesNo('viewableStudents')->required();

        $row = $section->addRow()->setClass('flex w-full justify-between items-center py-2');
            $row->addLabel('viewableParents', __('Viewable by Parents'));
            $row->addYesNo('viewableParents')->required();

        $row = $form->addRow()->addSubmit();

        echo $form->getOutput();
    }

    // Print sidebar
    $page->addSidebarExtra(sidebarExtraUnits($guid, $connection2, $gibbonCourseID, $gibbonSchoolYearID));
}
