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

use Gibbon\Domain\DataSet;
use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Module\Staff\View\StaffCard;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\Staff\SubstituteGateway;
use Gibbon\Domain\Timetable\CourseGateway;
use Gibbon\Domain\Staff\StaffCoverageGateway;
use Gibbon\Domain\Staff\StaffCoverageDateGateway;
use Gibbon\Module\Staff\Tables\CoverageMiniCalendar;
use Gibbon\Tables\DataTable;
use Gibbon\Domain\School\DaysOfWeekGateway;
use Gibbon\Domain\School\SchoolYearSpecialDayGateway;

if (isActionAccessible($guid, $connection2, '/modules/Staff/coverage_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $gibbonSchoolYearID = $session->get('gibbonSchoolYearID');
    $gibbonStaffCoverageDateID = $_REQUEST['gibbonStaffCoverageDateID'] ?? '';
    
    $staffCoverageGateway = $container->get(StaffCoverageGateway::class);
    $staffCoverageDateGateway = $container->get(StaffCoverageDateGateway::class);
    $subGateway = $container->get(SubstituteGateway::class);
    $courseGateway = $container->get(CourseGateway::class);

    if (empty($gibbonStaffCoverageDateID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $coverage = $staffCoverageDateGateway->getCoverageDateDetailsByID($gibbonStaffCoverageDateID);

    if (empty($coverage)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    $settingGateway = $container->get(SettingGateway::class);
    $urgencyThreshold = $settingGateway->getSettingByScope('Staff', 'urgencyThreshold');
    $internalCoverage = $settingGateway->getSettingByScope('Staff', 'coverageInternal');

    // ABSENCE DETAILS
    if (!empty($coverage['gibbonPersonID'])) {
        $staffCard = $container->get(StaffCard::class);
        $staffCard->setPerson($coverage['gibbonPersonID'])->compose($page);
    }

    $dateObject = new \DateTimeImmutable($coverage['date']);

    $times = $staffCoverageDateGateway->getCoverageTimesByForeignTable($coverage['foreignTable'], $coverage['foreignTableID'], $coverage['date']);

    $dayOfWeek = $container->get(DaysOfWeekGateway::class)->getDayOfWeekByDate($coverage['date']);

    // DETAILS
    $table = DataTable::createDetails('coverage');

    $table->addColumn('date', __('Date'))->format(Format::using('dateReadable', ['date', '%A, %b %e']));
    $table->addColumn('period', __('Period'));
    $table->addColumn('time', __('Time'))->format(Format::using('timeRange', ['timeStart', 'timeEnd']));

    if ($coverage['foreignTable'] == 'gibbonTTDayRowClass') {
        $details = $courseGateway->getCourseClassByID($times['gibbonCourseClassID']);

        $table->addColumn('class', __('Class'))
            ->format(function($coverage) {
                if (empty($coverage['gibbonCourseID'])) return '';

                $url = './index.php?q=/modules/Departments/department_course_class.php&gibbonDepartmentID='.$coverage['gibbonDepartmentID'].'&gibbonCourseID='.$coverage['gibbonCourseID'].'&gibbonCourseClassID='.$coverage['gibbonCourseClassID'];
                return Format::link($url, Format::courseClassName($coverage['courseNameShort'], $coverage['nameShort']), ['target' => '_blank']);
            });
        
        $table->addColumn('studentsTotal', __('Students'));

        $table->addColumn('spaceName', __('Room'))
            ->format(function($coverage) {
                if (empty($coverage['gibbonSpaceID'])) return '';

                $url = './index.php?q=/modules/Timetable/tt_space_view.php&gibbonSpaceID='.$coverage['gibbonSpaceID'].'&ttDate='.Format::date($coverage['date']);
                return Format::link($url, $coverage['spaceName'] ?? '', ['target' => '_blank']);
            });
    } else {
        $details = [];
    }

    echo $table->render([array_merge($coverage, $details, $times)]);

    // CRITERIA
    $criteria = $subGateway->newQueryCriteria()
        ->sortBy('gibbonSubstitute.priority', 'DESC')
        ->sortBy(['surname', 'preferredName'])
        ->filterBy('allStaff', $internalCoverage == 'Y')
        ->fromPOST();

    // FORM
    $form = Form::create('staffCoverage', $session->get('absoluteURL').'/modules/Staff/coverage_planner_assignProcess.php');

    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->setClass('blank bulkActionForm');

    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('gibbonStaffCoverageID', $coverage['gibbonStaffCoverageID']);
    $form->addHiddenValue('gibbonStaffCoverageDateID', $gibbonStaffCoverageDateID);
    $form->addHiddenValue('date', $coverage['date']);

    $subs = $subGateway->queryAvailableSubsByDate($criteria, $coverage['date'], $coverage['timeStart'], $coverage['timeEnd']);
    $availability = $subGateway->selectUnavailableDatesByDateRange($coverage['date'], $coverage['date'])->fetchGrouped();
    
    // Check for special days for these classes
    $specialDayGateway = $container->get(SchoolYearSpecialDayGateway::class);
    $specialDay = $specialDayGateway->getSpecialDayByDate($coverage['date']);
    if (!empty($specialDay)) {
        foreach ($availability as $gibbonPersonID => $dates)
        $availability[$gibbonPersonID] = array_filter($dates, function ($item) use (&$specialDay, &$specialDayGateway, &$session) {
            if ($item['status'] != 'Teaching') return true;
            return !$specialDayGateway->getIsClassOffTimetableByDate($session->get('gibbonSchoolYearID'), $item['contextID'], $item['date']);
        });
    }

    $people = $subs->getColumn('gibbonPersonID');
    $coverageCounts = $staffCoverageGateway->selectCoverageCountsByPerson($people)->fetchGroupedUnique();
    $subs->joinColumn('gibbonPersonID', 'coverageCounts', $coverageCounts);

    $subs->transform(function (&$sub) use (&$availability) {
        $sub['dates'] = $availability[intval($sub['gibbonPersonID'])] ?? [];
        $sub['availability'] = count($sub['dates']);
        if (!$sub['available']) unset($sub);
    });
    
    $subList = $subs->toArray();
    usort($subList, function ($a, $b) {
        if ($a['availability'] == $b['availability']) {
            return ($a['coverageCounts']['totalCoverage'] ?? 0) <=> ($b['coverageCounts']['totalCoverage'] ?? 0);
        }
        return $a['availability'] <=> $b['availability'];
    });

    $subsPrepend = [];
    if (!empty($coverage['gibbonPersonIDCoverage'])) {
        $dates = $availability[intval($coverage['gibbonPersonIDCoverage'])] ?? [];
        $counts = $staffCoverageGateway->selectCoverageCountsByPerson($coverage['gibbonPersonIDCoverage'])->fetchAll();

        $subsPrepend[] = [
            'gibbonPersonID' => $coverage['gibbonPersonIDCoverage'],
            'gibbonStaffID' => $coverage['gibbonPersonIDCoverage'],
            'title'          => $coverage['titleCoverage'],
            'preferredName'  => $coverage['preferredNameCoverage'],
            'surname'        => $coverage['surnameCoverage'],
            'jobTitle'       => '',
            'dates'          => $dates,
            'availability'   => count($dates),
            'coverageCounts' => $counts[0] ?? [],
        ];
    }

    $subs = new DataSet($subsPrepend + $subList);

    $subs->transform(function (&$sub) use (&$coverage) {
        $isCovering = $sub['gibbonPersonID'] == $coverage['gibbonPersonIDCoverage'];
        $sub['dates'][] = [
            'date'      => $coverage['date'],
            'status'    => $isCovering ? 'Covering' : 'Available',
            'allDay'    => 'N',
            'timeStart' => $coverage['timeStart'],
            'timeEnd'   => $coverage['timeEnd'],
        ];
    });

    // DATA TABLE
    $row = $form->addRow();
    $table = $row->addDataTable('subsManage')->withData($subs);

    $table->setTitle(__('Substitute Availability'));

    $table->addMetaData('hidePagination', true);

    // $table->addMetaData('filterOptions', [
    //     'type:teaching' => __('Staff Type').': '.__('Teaching'),
    //     'type:support'  => __('Staff Type').': '.__('Support'),
    // ]);

    $table->modifyRows(function ($values, $row) use (&$coverage) {
        if ($values['gibbonPersonID'] == $coverage['gibbonPersonIDCoverage']) $row->addClass('selected');
        return $row;
    });

    // COLUMNS
    $table->addColumn('image_240', __('Photo'))
        ->context('primary')
        ->width('10%')
        ->notSortable()
        ->format(Format::using('userPhoto', ['image_240', 'sm']));

    $canManageCoverage = isActionAccessible($guid, $connection2, '/modules/Staff/coverage_manage.php');
    $table->addColumn('fullName', __('Name'))
        ->context('primary')
        ->description(__('Priority'))
        ->sortable(['surname', 'preferredName'])
        ->format(function ($person) use ($canManageCoverage) {
            $name = Format::name($person['title'], $person['preferredName'], $person['surname'], 'Staff', true, true);
            if (!empty($person['gibbonStaffID'])) {
                $url = './index.php?q=/modules/Staff/staff_view_details.php&gibbonPersonID='.$person['gibbonPersonID'];
            } else {
                $url = '';
            }

            return Format::link($url, $name, ['target' => '_blank']).'<br/>'.Format::small($person['type'] ?? $person['jobTitle']);
        });

    $table->addColumn('details', __('Details'))
        ->format(function ($person) {
            return Format::listDetails([
                __('Week') => $person['coverageCounts']['weekCoverage'] ?? 0,
                __('Year') => $person['coverageCounts']['totalCoverage'] ?? 0,
            ], 'ul', 'list-none text-xs text-right p-0 m-0', 'w-2/3 whitespace-nowrap');
        });

    $table->addColumn('availability', __('Availability'))
        ->context('primary')
        ->notSortable()
        ->format(function ($person) use ($dateObject, $dayOfWeek) {
            return CoverageMiniCalendar::renderTimeRange($dayOfWeek, $person['dates'] ?? [], $dateObject);
        });

    $table->addRadioColumn('gibbonPersonIDCoverage', 'gibbonPersonID')->checked($coverage['gibbonPersonIDCoverage'] ?? null);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
?>
<script>

$('#subsManage tr').removeClass('odd').removeClass('even');

$(document).on('click', 'input[id^="gibbonPersonID"]', function(event) {
    $('#subsManage tr').removeClass('selected');
    $(event.target).parents('tr').addClass('selected');
});

</script>
