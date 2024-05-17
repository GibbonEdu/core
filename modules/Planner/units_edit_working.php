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
    if ($gibbonCourseID == '' or $gibbonSchoolYearID == '' or $gibbonCourseClassID == '' or $gibbonUnitClassID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $plannerEntryGateway = $container->get(PlannerEntryGateway::class);
    $unitBlockGateway = $container->get(UnitBlockGateway::class);
    $unitClassBlockGateway = $container->get(UnitClassBlockGateway::class);
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

    // DETAILS
    $table = DataTable::createDetails('unit');

    $table->addColumn('schoolYear', __('School Year'));
    $table->addColumn('course', __('Class'))->format(Format::using('courseClassName', ['course', 'class']));
    $table->addColumn('unit', __('Unit'));

    echo $table->render([$values]);

    // Get unit blocks
    $blocks = $unitBlockGateway->selectBlocksByUnit($gibbonUnitID)->fetchAll();

    $blockCount = 1;
    $blockSelect = array_reduce($blocks, function ($group, $item) use (&$blockCount) {
        $group[$item['gibbonUnitBlockID']] = $blockCount.') '.$item['title'];
        $blockCount++;
        return $group;
    }, []);

    // FORM
    $form = Form::create('action', $session->get('absoluteURL').'/modules/Planner/units_edit_workingProcess.php?'.http_build_query($urlParams));
    
    $form->setTitle(__('Lessons & Blocks'));
    $form->setDescription(__('You can now add your unit blocks using the dropdown menu in each lesson. Blocks can be dragged from one lesson to another.').Format::alert(__('Deploying lessons only works for units with smart blocks. If you have duplicated a unit from a past year that does not have smart blocks, be sure to edit the lessons manually and assign a new date to them.'), 'message'));

    $form->addHiddenValue('address', $session->get('address'));

    $form->addHeaderAction('add', __('Add'))
        ->setURL('/modules/Planner/units_edit_working_add.php')
        ->addParams($urlParams)
        ->displayLabel();

    // Display lessons and blocks
    $lessons = $plannerEntryGateway->selectPlannerEntriesByUnitAndClass($gibbonUnitID, $gibbonCourseClassID)->fetchAll();
    $blockCount = 0;

    foreach ($lessons as $index => $lesson) {

        // Setup header links for this lesson
        $lessonLink = $form->getFactory()->createWebLink(($index+1).'. '.$lesson['name'])
            ->setURL($session->get('absoluteURL').'/index.php?q=/modules/Planner/planner_view_full.php')
            ->addParam('gibbonCourseClassID', $lesson['gibbonCourseClassID'])
            ->addParam('gibbonPlannerEntryID', $lesson['gibbonPlannerEntryID'])
            ->addParam('viewBy', 'class')
            ->addConfirmation(__('Are you sure you want to jump to this lesson? Any unsaved changes will be lost.'))
            ->addClass('text-gray-800 underline')
            ->getOutput();

        $deleteLink = $form->getFactory()->createWebLink('<img title="'.__('Delete').'" src="./themes/'.$session->get('gibbonThemeName').'/img/garbage.png">')
            ->setURL($session->get('absoluteURL').'/modules/Planner/units_edit_working_lessonDelete.php')
            ->addParams($urlParams)
            ->addParam('gibbonCourseClassID', $lesson['gibbonCourseClassID'])
            ->addParam('gibbonPlannerEntryID', $lesson['gibbonPlannerEntryID'])
            ->addParam('address', $_GET['q'])
            ->addClass('float-right ml-2')
            ->addConfirmation(__('Are you sure you want to delete this record? Any unsaved changes will be lost.'))
            ->getOutput();

        $times = $plannerEntryGateway->getPlannerTTByClassTimes($gibbonCourseClassID, $lesson['date'], $lesson['timeStart'], $lesson['timeEnd']);
        $lessonTiming = !empty($times)
            ? Format::small($times['period'].' ('.Format::timeRange($times['timeStart'], $times['timeEnd']).')')
            : Format::small(Format::timeRange($lesson['timeStart'], $lesson['timeEnd']));

        // Display the heading
        $heading = $form->addRow()->addHeading($lessonLink . $deleteLink)
            ->append(Format::small(Format::dateReadable($lesson['date'], Format::FULL)).'<br/>')
            ->append($lessonTiming.'<br/>')
            ->append(Format::small($times['spaceName'] ?? ''));

        $col = $form->addRow()->addClass('')->addColumn()->addClass('blockLesson');

        $col->addContent('<input type="hidden" name="order[]" value="lessonHeader-'.$index.'">');
        $form->addHiddenValue('gibbonPlannerEntryID'.$index, $lesson['gibbonPlannerEntryID']);
        $form->addHiddenValue('date'.$index, $lesson['date']);
        $form->addHiddenValue('timeStart'.$index, $lesson['timeStart']);
        $form->addHiddenValue('timeEnd'.$index, $lesson['timeEnd']);

        $col->addColumn()
            ->setClass('-mt-4')
            ->addSelect('blockAdd')
            ->fromArray($blockSelect)
            ->placeholder()
            ->setClass('blockAdd float-right w-48')
            ->prepend(Format::small(__('Add Block').':'));

        $content = '';
        $classBlocks = $unitClassBlockGateway->selectBlocksByLessonAndClass($lesson['gibbonPlannerEntryID'], $gibbonCourseClassID);

        foreach ($classBlocks as $block) {
            ob_start();
            makeBlock($guid,  $connection2, $blockCount, $mode = 'workingEdit', $block['title'], $block['type'], $block['length'], $block['contents'], $block['complete'], $block['gibbonUnitBlockID'], $block['gibbonUnitClassBlockID'], $block['teachersNotes'], true);
            $blockContent = ob_get_clean();
            $blockCount++;

            $content .= '<div class="draggable z-100">'.$blockContent.'</div>';
        }

        $col->addContent('<div class="sortableArea py-2 mt-16">'.$content.'</div>');

    }

    $form->addRow()->addSubmit();

    echo $form->getOutput();

    // Print sidebar
    $page->addSidebarExtra(sidebarExtraUnits($guid, $connection2, $gibbonCourseID, $gibbonSchoolYearID));
}
?>

<script>
var count = <?php echo $blockCount ?? 0; ?>;

$('.sortableArea').sortable({
    revert: false,
    tolerance: 25,
    connectWith: ".sortableArea",
    items: "div.draggable",
    receive: function(event,ui) {

    },
    beforeStop: function (event, ui) {
        newItem=ui.item;
    }
});

$( ".draggable" ).draggable({
    connectToSortable: ".sortableArea",
});

$('.blockAdd').change(function () {
    if ($(this).val() == '') return;

    var parent = $(this).parents('.blockLesson');
    var sortable = $('.sortableArea', parent);

    $(sortable).append($('<div class="draggable z-100">').load("<?php echo $session->get('absoluteURL'); ?>/modules/Planner/units_add_blockAjax.php?mode=workingEdit&gibbonUnitID=<?php echo $gibbonUnitID; ?>&gibbonUnitBlockID=" + $(this).val(), "id=" + count) );
    count++;
});

</script>
