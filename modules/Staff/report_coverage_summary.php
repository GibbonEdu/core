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

use Gibbon\Domain\System\SettingGateway;
use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Services\Format;
use Gibbon\Tables\Prefab\ReportTable;
use Gibbon\Domain\School\SchoolYearGateway;
use Gibbon\Domain\Staff\StaffCoverageGateway;
use Gibbon\Domain\Staff\SubstituteGateway;
use Gibbon\Module\Staff\Tables\AbsenceFormats;

if (isActionAccessible($guid, $connection2, '/modules/Staff/report_coverage_summary.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $viewMode = $_REQUEST['format'] ?? '';
    $dateFormat = $session->get('i18n')['dateFormatPHP'];
    $gibbonSchoolYearID = $session->get('gibbonSchoolYearID');
    $gibbonPersonID = $_GET['gibbonPersonID'] ?? '';
    $substituteType = $_GET['substituteType'] ?? '';
    $month = $_GET['month'] ?? '';

    $settingGateway = $container->get(SettingGateway::class);
    $internalCoverage = $settingGateway->getSettingByScope('Staff', 'coverageInternal');
    $urgencyThreshold = $settingGateway->getSettingByScope('Staff', 'urgencyThreshold');

    $schoolYearGateway = $container->get(SchoolYearGateway::class);
    $staffCoverageGateway = $container->get(StaffCoverageGateway::class);
    $substituteGateway = $container->get(SubstituteGateway::class);

    // COVERAGE DATA
    $schoolYear = $schoolYearGateway->getSchoolYearByID($gibbonSchoolYearID);

    // Setup the date range for this school year
    $dateStart = new DateTime(substr($schoolYear['firstDay'], 0, 7).'-01');
    $dateEnd = new DateTime($schoolYear['lastDay']);

    $months = [];
    $dateRange = new DatePeriod($dateStart, new DateInterval('P1M'), $dateEnd);

    // Translated array of months in the current school year
    foreach ($dateRange as $monthDate) {
        $months[$monthDate->format('Y-m-d')] = Format::monthName($monthDate->format('Y-m-d')).' '.$monthDate->format('Y');
    }

    // Setup the date range used for this report
    if (!empty($month)) {
        $monthDate = new DateTimeImmutable($month);
        $dateStart = $monthDate->modify('first day of this month');
        $dateEnd = $monthDate->modify('last day of this month');

        $dateRange = new DatePeriod($dateStart, new DateInterval('P1M'), $dateEnd);
    } else {
        $dateStart = new DateTime($schoolYear['firstDay']);
    }

    // Get all substitutes
    $status = $_GET['status'] ?? 'Full';
    $criteria = $substituteGateway->newQueryCriteria()
        ->filterBy('allStaff', $internalCoverage == 'Y')
        ->filterBy('status', $status == 'Full' ? $status : '')
        ->sortBy(['active', 'surname', 'preferredName'])
        ->fromPOST();

    $substitutes = $substituteGateway->queryAllSubstitutes($criteria);

    if (empty($viewMode)) {
        $page->breadcrumbs->add(__('Staff Coverage Summary'));

        $form = Form::create('filter', $session->get('absoluteURL').'/index.php', 'get');
        $form->setFactory(DatabaseFormFactory::create($pdo));
        $form->setTitle(__('Filter'));
        $form->setClass('noIntBorder fullWidth');

        $form->addHiddenValue('address', $session->get('address'));
        $form->addHiddenValue('q', '/modules/Staff/report_coverage_summary.php');

        $subsByID = array_map(function ($sub) {
            return $sub['gibbonPersonID'];
        }, $substitutes->toArray());

        $row = $form->addRow()->addClass('substitutes');
            $row->addLabel('gibbonPersonID', __('Substitute'));
            $row->addSelectUsersFromList('gibbonPersonID', $subsByID)
                ->placeholder()
                ->selected($gibbonPersonID ?? '');

        $row = $form->addRow();
            $row->addLabel('month', __('Month'));
            $row->addSelect('month')->fromArray(['' => __('All')])->fromArray($months)->selected($month);

        $row = $form->addRow();
                $row->addLabel('Status', __('All Staff'))->description(__('Include all staff, regardless of status and current employment.'));
                $row->addCheckbox('status')->setValue('on')->checked($status);

        $row = $form->addRow();
            $row->addSearchSubmit($session);

        echo $form->getOutput();
    }


    if (!empty($gibbonPersonID)) {
        // COVERAGE SUMMARY BY SUBSTITUTE
        $criteria = $staffCoverageGateway->newQueryCriteria(true)
            ->sortBy(['date', 'timeStart'])
            ->filterBy('dateStart', $dateStart->format('Y-m-d'))
            ->filterBy('dateEnd', $dateEnd->format('Y-m-d'))
            ->fromPOST('staffCoverage'.$gibbonPersonID);

        $coverage = $staffCoverageGateway->queryCoverageByPersonCovering($criteria, $gibbonSchoolYearID, $gibbonPersonID, false);

        // DATA TABLE
        $table = ReportTable::createPaginated('staffCoverage'.$gibbonPersonID, $criteria)->setViewMode($viewMode, $session);
        $table->setTitle(__('Report'));
        $table->setDescription(Format::dateRangeReadable($dateStart->format('Y-m-d'), $dateEnd->format('Y-m-d')));

        $table->addColumn('status', __('Status'))
            ->width('15%')
            ->format(function ($coverage) use ($urgencyThreshold) {
                return AbsenceFormats::coverageStatus($coverage, $urgencyThreshold);
            });

        $table->addColumn('date', __('Date'))
            ->context('primary')
            ->width('18%')
            ->format([AbsenceFormats::class, 'dateDetails']);

        $table->addColumn('value', __('Value'));

        $table->addColumn('requested', __('Person'))
            ->context('primary')
            ->width('20%')
            ->sortable(['absence.surname', 'absence.preferredName'])
            ->format([AbsenceFormats::class, 'personDetails']);

        $table->addColumn('notesStatus', __('Comment'))
            ->format(function ($coverage) {
                return Format::truncate($coverage['notesStatus'], 60);
            });

        $table->addActionColumn()
            ->addParam('gibbonStaffCoverageID')
            ->format(function ($coverage, $actions) {
                $actions->addAction('view', __('View Details'))
                    ->isModal(800, 550)
                    ->setURL('/modules/Staff/coverage_view_details.php');
            });

        echo $table->render($coverage);
    } else {
        // COVERAGE SUMMARY BY DATE RANGE
        $coverage = $staffCoverageGateway->selectCoverageByDateRange($dateStart->format('Y-m-d'), $dateEnd->format('Y-m-d'))->fetchAll();
        $coverageByPerson = array_reduce($coverage, function ($group, $item) {
            $date = Format::date($item['date'], 'Y-m');
            $group[$item['gibbonPersonIDCoverage']][$date][] = $item['value'];
            return $group;
        }, []);

        $substitutes->transform(function (&$sub) use (&$coverageByPerson) {
            $sub['coverage'] = $coverageByPerson[$sub['gibbonPersonID']] ?? [];
            $sub['total'] = array_reduce($sub['coverage'], function($total, $item) {
                $total += array_sum($item);
                return $total;
            }, 0);
        });

        // DATA TABLE
        $table = ReportTable::createPaginated('staffCoverage', $criteria)->setViewMode($viewMode, $session);
        $table->setTitle(__('Report'));
        $table->setDescription(Format::dateRangeReadable($dateStart->format('Y-m-d'), $dateEnd->format('Y-m-d')));

        $table->modifyRows(function ($person, $row) use ($internalCoverage) {
            if (!empty($person['status']) && $person['status'] != 'Full') $row->addClass('error');
            if ($internalCoverage == 'N' && $person['active'] != 'Y') $row->addClass('error');
            return $row;
        });

        // COLUMNS
        $table->addColumn('fullName', __('Name'))
            ->sortable(['surname', 'preferredName'])
            ->format(function ($person) use ($session) {
                $text = Format::name($person['title'], $person['preferredName'], $person['surname'], 'Staff', true, true);
                $url = $session->get('absoluteURL').'/index.php?q=/modules/Staff/report_coverage_summary.php&gibbonPersonID='.$person['gibbonPersonID'];
                $output = Format::link($url, $text);
                return $output;
            });

        $count = 0;
        foreach ($dateRange as $monthDate) {
            $table->addColumn('month'.$count, Format::monthName($monthDate, true))->description(Format::date($monthDate, 'Y'))
                ->notSortable()
                ->format(function ($sub) use ($monthDate) {
                    $sum =  array_sum($sub['coverage'][$monthDate->format('Y-m')] ?? []);
                    return $sum > 0 ? $sum : '';
                });
            $count++;
        }

        if (empty($month)) {
            $table->addColumn('total', __('Total'))->notSortable();
        }

        echo $table->render($substitutes);
    }
}
