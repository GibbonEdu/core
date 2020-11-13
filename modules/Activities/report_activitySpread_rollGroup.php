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
use Gibbon\Services\Format;
use Gibbon\Tables\Prefab\ReportTable;
use Gibbon\Domain\Activities\ActivityReportGateway;
use Gibbon\Domain\Students\StudentGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Activities/report_activitySpread_rollGroup.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $gibbonRollGroupID = $_GET['gibbonRollGroupID'] ?? '';
    $status = $_GET['status'] ?? '' ;
    $dateType = getSettingByScope($connection2, 'Activities', 'dateType');

    $viewMode = $_REQUEST['format'] ?? '';

    if (empty($viewMode)) {
        $page->breadcrumbs->add(__('Activity Spread by Roll Group'));

        echo '<h2>';
        echo __('Choose Roll Group');
        echo '</h2>';

        $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/index.php','get');

        $form->setFactory(DatabaseFormFactory::create($pdo));
        $form->setClass('noIntBorder fullWidth');

        $form->addHiddenValue('q', "/modules/".$_SESSION[$guid]['module']."/report_activitySpread_rollGroup.php");

        $row = $form->addRow();
            $row->addLabel('gibbonRollGroupID', __('Roll Group'));
            $row->addSelectRollGroup('gibbonRollGroupID', $_SESSION[$guid]['gibbonSchoolYearID'])->selected($gibbonRollGroupID)->required();

        $row = $form->addRow();
            $row->addLabel('status', __('Status'));
            $row->addSelect('status')->fromArray(array('Accepted' => __('Accepted'), 'Registered' => __('Registered')))->selected($status)->required();

        $row = $form->addRow();
            $row->addFooter();
            $row->addSearchSubmit($gibbon->session);

        echo $form->getOutput();
    }

    if (empty($gibbonRollGroupID)) return;

    $activityGateway = $container->get(ActivityReportGateway::class);
    $studentGateway = $container->get(StudentGateway::class);

    // CRITERIA
    $criteria = $activityGateway->newQueryCriteria(true)
        ->searchBy($activityGateway->getSearchableColumns(), isset($_GET['search'])? $_GET['search'] : '')
        ->sortBy(['surname', 'preferredName'])
        ->pageSize(!empty($viewMode) ? 0 : 50)
        ->fromPOST();

    $rollGroups = $studentGateway->queryStudentEnrolmentByRollGroup($criteria, $gibbonRollGroupID);

    // Join a set of activity counts per student
    $rollGroups->transform(function(&$student) use ($activityGateway, $dateType, $status) {
        $activityCounts = $activityGateway->selectActivitySpreadByStudent($student['gibbonSchoolYearID'], $student['gibbonPersonID'], $dateType, $status);
        $student['activities'] = $activityCounts->fetchGroupedUnique();
    });

    // DATA TABLE
    $table = ReportTable::createPaginated('activitySpread_rollGroup', $criteria)->setViewMode($viewMode, $gibbon->session);

    $table->setTitle(__('Activity Spread by Roll Group'));

    $table->addColumn('rollGroup', __('Roll Group'))->width('10%');
    $table->addColumn('student', __('Student'))
        ->sortable(['surname', 'preferredName'])
        ->format(function ($student) use ($guid) {
            $name = Format::name('', $student['preferredName'], $student['surname'], 'Student', true);
            return Format::link($_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID='.$student['gibbonPersonID'].'&subpage=Activities', $name);
        });

    // Build a reusable formatter for activity counts
    $displayActivityCount = function($student, $key) {
        $count = isset($student['activities'][$key])? $student['activities'][$key]['count'] : 0;
        $title = ($count > 0) ? $student['activities'][$key]['activityNames'] : __('There are no records to display.');
        $extra = ($count > 0 && $student['activities'][$key]['notAccepted'] > 0) ? "<span style='color: #cc0000' title='".__('Some activities not accepted.')."'> *</span>" : '';

        return '<span title="'.$title.'">'.$count.$extra.'</span>';
    };

    if ($dateType == 'Term') {
        // Group the activity spread by term & weekday
        $terms = $activityGateway->selectActivityWeekdaysPerTerm($_SESSION[$guid]['gibbonSchoolYearID'])->fetchGrouped();
        foreach ($terms as $termName => $days) {
            $termColumn = $table->addColumn($termName, $termName);
            foreach ($days as $day) {
                $termColumn->addColumn($day['nameShort'], __($day['nameShort']))
                    ->notSortable()
                    ->format(function($student) use ($displayActivityCount, $day) {
                        $key = $day['gibbonSchoolYearTermID'].'-'.$day['gibbonDaysOfWeekID'];
                        return $displayActivityCount($student, $key);
                    });
            }
        }
    } else {
        // Group the activity spread by weekday only
        $days = $activityGateway->selectActivityWeekdays($_SESSION[$guid]['gibbonSchoolYearID'])->fetchAll();
        foreach ($days as $day) {
            $table->addColumn($day['nameShort'], $day['nameShort'])
                ->notSortable()
                ->format(function($student) use ($displayActivityCount, $day) {
                    $key = $day['gibbonDaysOfWeekID'];
                    return $displayActivityCount($student, $key);
                });
        }
    }

    echo $table->render($rollGroups);
}
