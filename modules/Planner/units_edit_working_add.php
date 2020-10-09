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
        

    if ($highestAction == 'Unit Planner_all') {
        $data = ['gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonCourseID' => $gibbonCourseID, 'gibbonCourseClassID' => $gibbonCourseClassID];
        $sql = 'SELECT *, gibbonSchoolYear.name AS year, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) JOIN gibbonSchoolYear ON (gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourse.gibbonCourseID=:gibbonCourseID AND gibbonCourseClassID=:gibbonCourseClassID';
    } elseif ($highestAction == 'Unit Planner_learningAreas') {
        $data = ['gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonCourseID' => $gibbonCourseID, 'gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']];
        $sql = "SELECT gibbonCourse.gibbonCourseID, gibbonCourse.name, gibbonCourse.nameShort, gibbonSchoolYear.name AS year, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) JOIN gibbonSchoolYear ON (gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) JOIN gibbonDepartment ON (gibbonCourse.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE gibbonDepartmentStaff.gibbonPersonID=:gibbonPersonID AND (role='Coordinator' OR role='Assistant Coordinator' OR role='Teacher (Curriculum)') AND gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourse.gibbonCourseID=:gibbonCourseID AND gibbonCourseClassID=:gibbonCourseClassID ORDER BY gibbonCourse.nameShort";
    }
    $result = $pdo->select($sql, $data);

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

    $table->addColumn('year', __('School Year'));
    $table->addColumn('course', __('Class'))->format(Format::using('courseClassName', ['course', 'class']));
    $table->addColumn('unit', __('Unit'));
    
    echo $table->render([$values]);


    // Find all unplanned slots for this class.
    $data = ['gibbonCourseClassID' => $gibbonCourseClassID];
    $sql = 'SELECT timeStart, timeEnd, date, gibbonTTColumnRow.name AS period, gibbonTTDayRowClassID, gibbonTTDayDateID FROM gibbonTTDayRowClass JOIN gibbonTTColumnRow ON (gibbonTTDayRowClass.gibbonTTColumnRowID=gibbonTTColumnRow.gibbonTTColumnRowID) JOIN gibbonTTColumn ON (gibbonTTColumnRow.gibbonTTColumnID=gibbonTTColumn.gibbonTTColumnID) JOIN gibbonTTDay ON (gibbonTTDayRowClass.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) JOIN gibbonTTDayDate ON (gibbonTTDayDate.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) WHERE gibbonCourseClassID=:gibbonCourseClassID ORDER BY date, timestart';
    $resultNext = $pdo->select($sql, $data);

    $count = 0;
    $lessons = [];
    while ($rowNext = $resultNext->fetch()) {

        $data = ['date' => $rowNext['date'], 'timeStart' => $rowNext['timeStart'], 'timeEnd' => $rowNext['timeEnd'], 'gibbonCourseClassID' => $gibbonCourseClassID];
        $sql = 'SELECT * FROM gibbonPlannerEntry WHERE date=:date AND timeStart=:timeStart AND timeEnd=:timeEnd AND gibbonCourseClassID=:gibbonCourseClassID';
        $resultPlanner = $pdo->select($sql, $data);

        if ($resultPlanner->rowCount() == 0) {
            $lessons[$count][0] = 'Unplanned';
            $lessons[$count][1] = $rowNext['date'];
            $lessons[$count][2] = $rowNext['timeStart'];
            $lessons[$count][3] = $rowNext['timeEnd'];
            $lessons[$count][4] = $rowNext['period'];
            $lessons[$count][6] = $rowNext['gibbonTTDayRowClassID'];
            $lessons[$count][7] = $rowNext['gibbonTTDayDateID'];
        } else {
            $rowPlanner = $resultPlanner->fetch();
            $lessons[$count][0] = 'Planned';
            $lessons[$count][1] = $rowNext['date'];
            $lessons[$count][2] = $rowNext['timeStart'];
            $lessons[$count][3] = $rowNext['timeEnd'];
            $lessons[$count][4] = $rowNext['period'];
            $lessons[$count][5] = $rowPlanner['name'];
            $lessons[$count][6] = false;
            $lessons[$count][7] = false;
        }

        // Check for special days
        $data = ['date' => $rowNext['date']];
        $sql = 'SELECT * FROM gibbonSchoolYearSpecialDay WHERE date=:date';
        $resultSpecial = $pdo->select($sql, $data);

        if ($resultSpecial->rowCount() == 1) {
            $rowSpecial = $resultSpecial->fetch();
            $lessons[$count][8] = $rowSpecial['type'];
            $lessons[$count][9] = $rowSpecial['schoolStart'];
            $lessons[$count][10] = $rowSpecial['schoolEnd'];
        } else {
            $lessons[$count][8] = false;
            $lessons[$count][9] = false;
            $lessons[$count][10] = false;
        }

        ++$count;
    }

    if (count($lessons) < 1) {
        echo Format::alert(__('There are no records to display.'));
        return;
    } 

    // CRITERIA
    $unitGateway = $container->get(UnitGateway::class);
    $criteria = $unitGateway->newQueryCriteria(true);


    // FORM
    $form = Form::createTable('action', $_SESSION[$guid]['absoluteURL'].'/modules/Planner/units_edit_workingProcess.php?'.http_build_query($urlParams));
    
    $form->setTitle(__('Choose Lessons'));
    $form->setDescription(__('Use the table below to select the lessons you wish to deploy this unit to. Only lessons without existing plans can be included in the deployment.'));
    $form->setClass('blank w-full');


    $count = 0;

    // DATA TABLE
    $table = $form->addRow()->addDataTable('staffManage', $criteria)->withData(new DataSet($lessons));

    $table->modifyRows(function ($lesson, $row) {
        if ($lesson[1] >= date('Y-m-d')) $row->addClass('error');
        return $row;
    });

    $table->addColumn('count', __('Lesson Number'))
        ->notSortable()
        ->format(function ($lesson) use (&$count) {
            $count++;
            return __('Lesson {number}', ['number' => $count]);
        });

    $table->addColumn('lesson', __('Lesson Number'));
    $table->addColumn('date', __('Date'));
    $table->addColumn('day', __('Day'));
    $table->addColumn('month', __('Month'));
    $table->addColumn('time', __('TT Period Time'));
    $table->addColumn('planned', __('Planned Lesson'));

    $table->addCheckboxColumn('gibbonPlannerEntryID');


    $form->addRow()->addSubmit();

    echo $form->getOutput();


    // Get term dates
    $terms = [];
    $termCount = 0;

    $data = ['gibbonSchoolYearID' => $gibbonSchoolYearID];
    $sql = 'SELECT * FROM gibbonSchoolYearTerm WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY sequenceNumber';
    $resultTerms = $pdo->select($sql, $data);

    while ($rowTerms = $resultTerms->fetch()) {
        $terms[$termCount][0] = $rowTerms['firstDay'];
        $terms[$termCount][1] = __('Start of').' '.$rowTerms['nameShort'];
        ++$termCount;
        $terms[$termCount][0] = $rowTerms['lastDay'];
        $terms[$termCount][1] = __('End of').' '.$rowTerms['nameShort'];
        ++$termCount;
    }
    // Get school closure special days
    $specials = [];
    $specialCount = 0;

    $data = ['gibbonSchoolYearID' => $gibbonSchoolYearID];
    $sql = "SELECT gibbonSchoolYearSpecialDay.date, gibbonSchoolYearSpecialDay.name FROM gibbonSchoolYearSpecialDay JOIN gibbonSchoolYearTerm ON (gibbonSchoolYearSpecialDay.gibbonSchoolYearTermID=gibbonSchoolYearTerm.gibbonSchoolYearTermID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND type='School Closure' ORDER BY date";
    $resultSpecial = $pdo->select($sql, $data);

    $lastName = '';
    $currentName = '';
    $originalDate = '';
    while ($rowSpecial = $resultSpecial->fetch()) {
        $currentName = $rowSpecial['name'];
        $currentDate = $rowSpecial['date'];
        if ($currentName != $lastName) {
            $currentName = $rowSpecial['name'];
            $specials[$specialCount][0] = $rowSpecial['date'];
            $specials[$specialCount][1] = $rowSpecial['name'];
            $specials[$specialCount][2] = dateConvertBack($guid, $rowSpecial['date']);
            $originalDate = dateConvertBack($guid, $rowSpecial['date']);
            ++$specialCount;
        } else {
            if ((strtotime($currentDate) - strtotime($lastDate)) == 86400) {
                $specials[$specialCount - 1][2] = $originalDate.' - '.dateConvertBack($guid, $rowSpecial['date']);
            } else {
                $currentName = $rowSpecial['name'];
                $specials[$specialCount][0] = $rowSpecial['date'];
                $specials[$specialCount][1] = $rowSpecial['name'];
                $specials[$specialCount][2] = dateConvertBack($guid, $rowSpecial['date']);
                $originalDate = dateConvertBack($guid, $rowSpecial['date']);
                ++$specialCount;
            }
        }
        $lastName = $rowSpecial['name'];
        $lastDate = $rowSpecial['date'];
    }

    echo "<form method='post' action='".$_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/units_edit_working_addProcess.php?gibbonUnitID=$gibbonUnitID&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonCourseID=$gibbonCourseID&gibbonCourseClassID=$gibbonCourseClassID&gibbonUnitClassID=$gibbonUnitClassID&address=".$_GET['q']."'>";
    echo "<table cellspacing='0' style='width: 100%'>";
    echo "<tr class='head'>";
    echo '<th>';
    echo sprintf(__('Lesson%1$sNumber'), '<br/>');
    echo '</th>';
    echo '<th>';
    echo __('Date');
    echo '</th>';
    echo '<th>';
    echo __('Day');
    echo '</th>';
    echo '<th>';
    echo __('Month');
    echo '</th>';
    echo '<th>';
    echo sprintf(__('TT Period%1$sTime'), '<br/>');
    echo '</th>';
    echo '<th>';
    echo sprintf(__('Planned%1$sLesson'), '<br/>');
    echo '</th>';
    echo '<th>';
    echo __('Include?');
    echo '</th>';
    echo '</tr>';

    $count = 0;
    $termCount = 0;
    $specialCount = 0;
    $classCount = 0;
    $rowNum = 'odd';
    $divide = false; // Have we passed gotten to today yet?

    foreach ($lessons as $lesson) {
        if ($count % 2 == 0) {
            $rowNum = 'even';
        } else {
            $rowNum = 'odd';
        }

        $style = '';
        if ($lesson[1] >= date('Y-m-d') and $divide == false) {
            $divide = true;
            $style = "style='border-top: 2px solid #333'";
        }

        if ($divide == false) {
            $rowNum = 'error';
        }
        ++$count;

        // Spit out row for start of term
        while ($lesson['1'] >= $terms[$termCount][0] and $termCount < (count($terms) - 1)) {
            if (substr($terms[$termCount][1], 0, 3) == 'End' and $lesson['1'] == $terms[$termCount][0]) {
                break;
            } else {
                echo "<tr class='dull'>";
                echo '<td>';
                echo '<b>'.$terms[$termCount][1].'</b>';
                echo '</td>';
                echo '<td colspan=6>';
                echo dateConvertBack($guid, $terms[$termCount][0]);
                echo '</td>';
                echo '</tr>';
                ++$termCount;
            }
        }

        // Spit out row for special day
        while ($lesson['1'] >= @$specials[$specialCount][0] and $specialCount < count($specials)) {
            echo "<tr class='dull'>";
            echo '<td>';
            echo '<b>'.$specials[$specialCount][1].'</b>';
            echo '</td>';
            echo '<td colspan=6>';
            echo $specials[$specialCount][2];
            echo '</td>';
            echo '</tr>';
            ++$specialCount;
        }

        // COLOR ROW BY STATUS!
        if ($lesson[8] != 'School Closure') {
            echo "<tr class=$rowNum>";
            echo "<td $style>";
            echo '<b>Lesson '.($classCount + 1).'</b>';
            echo '</td>';
            echo "<td $style>";
            echo dateConvertBack($guid, $lesson['1']).'<br/>';
            if ($lesson[8] == 'Timing Change') {
                echo '<u>'.$lesson[8].'</u><br/><i>('.substr($lesson[9], 0, 5).'-'.substr($lesson[10], 0, 5).')</i>';
            }
            echo '</td>';
            echo "<td $style>";
            echo date('D', dateConvertToTimestamp($lesson['1']));
            echo '</td>';
            echo "<td $style>";
            echo date('M', dateConvertToTimestamp($lesson['1']));
            echo '</td>';
            echo "<td $style>";
            echo $lesson['4'].'<br/>';
            echo substr($lesson['2'], 0, 5).' - '.substr($lesson['3'], 0, 5);
            echo '</td>';
            echo "<td $style>";
            if ($lesson['0'] == 'Planned') {
                echo $lesson['5'].'<br/>';
            }
            echo '</td>';
            echo "<td $style>";
            if ($lesson['0'] == 'Unplanned') {
                echo "<input name='deploy$count' type='checkbox'>";
                echo "<input name='date$count' type='hidden' value='".$lesson['1']."'>";
                echo "<input name='timeStart$count' type='hidden' value='".$lesson['2']."'>";
                echo "<input name='timeEnd$count' type='hidden' value='".$lesson['3']."'>";
                echo "<input name='period$count' type='hidden' value='".$lesson['4']."'>";
                echo "<input name='gibbonTTDayRowClassID$count' type='hidden' value='".$lesson['6']."'>";
                echo "<input name='gibbonTTDayDateID$count' type='hidden' value='".$lesson['7']."'>";
            }
            echo '</td>';
            echo '</tr>';
            ++$classCount;
        }

        // Spit out row for end of term
        while ($lesson['1'] >= @$terms[$termCount][0] and $termCount < count($terms) and substr($terms[$termCount][1], 0, 3) == 'End') {
            echo "<tr class='dull'>";
            echo '<td>';
            echo '<b>'.$terms[$termCount][1].'</b>';
            echo '</td>';
            echo '<td colspan=6>';
            echo dateConvertBack($guid, $terms[$termCount][0]);
            echo '</td>';
            echo '</tr>';
            ++$termCount;
        }
    }

    if (@$terms[$termCount][0] != '') {
        echo "<tr class='dull'>";
        echo '<td>';
        echo '<b><u>'.$terms[$termCount][1].'</u></b>';
        echo '</td>';
        echo '<td colspan=6>';
        echo dateConvertBack($guid, $terms[$termCount][0]);
        echo '</td>';
        echo '</tr>';
    }

    echo '<tr>';
    echo "<td class='right' colspan=7>";
    echo "<input name='count' id='count' value='$count' type='hidden'>";
    echo "<input id='submit' type='submit' value='Submit'>";
    echo '</td>';
    echo '</tr>';
    echo '</table>';
    echo '</form>';

    // Print sidebar
    $_SESSION[$guid]['sidebarExtra'] = sidebarExtraUnits($guid, $connection2, $gibbonCourseID, $gibbonSchoolYearID);
}
