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
use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;
use Gibbon\Domain\User\UserGateway;
use Gibbon\Domain\School\SchoolYearGateway;
use Gibbon\Domain\Staff\StaffCoverageGateway;
use Gibbon\Domain\Staff\SubstituteGateway;
use Gibbon\Module\Staff\View\CoverageTodayView;
use Gibbon\Module\Staff\View\StaffCard;
use Gibbon\Module\Staff\Tables\AbsenceFormats;
use Gibbon\Module\Staff\Tables\CoverageCalendar;

if (isActionAccessible($guid, $connection2, '/modules/Staff/coverage_my.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs->add(__('My Coverage'));

    $gibbonPersonID = $session->get('gibbonPersonID');
    $displayCount = 0;

    $schoolYearGateway = $container->get(SchoolYearGateway::class);
    $staffCoverageGateway = $container->get(StaffCoverageGateway::class);
    $substituteGateway = $container->get(SubstituteGateway::class);
    $userGateway = $container->get(UserGateway::class);
    $settingGateway = $container->get(SettingGateway::class);

    $urgencyThreshold = $settingGateway->getSettingByScope('Staff', 'urgencyThreshold');
    $coverageMode =  $settingGateway->getSettingByScope('Staff', 'coverageMode');

    // TODAY'S COVERAGE
    $criteria = $staffCoverageGateway->newQueryCriteria(true)
        ->sortBy('timeStart')
        ->filterBy('status:Accepted')
        ->filterBy('dateStart:'.date('Y-m-d'))
        ->filterBy('dateEnd:'.date('Y-m-d'))
        ->fromPOST('staffCoverageToday');

    $todaysCoverage = $staffCoverageGateway->queryCoverageByPersonCovering($criteria, $session->get('gibbonSchoolYearID'), $gibbonPersonID);

    if (count($todaysCoverage) > 0) {
        $page->write('<h2>'.__("Today's Coverage").'</h2>');

        $substituteInfo = $settingGateway->getSettingByScope('Staff', 'substituteInfo');
        if (!empty($substituteInfo)) {
            $page->write('<p>'.$substituteInfo.'</p>');
        }

        foreach ($todaysCoverage as $coverage) {
            $status = Format::dateRangeReadable($coverage['dateStart'], $coverage['dateEnd']).' - ';
            $status .= $coverage['allDay'] == 'Y'
                ? __('All Day')
                : Format::timeRange($coverage['timeStart'], $coverage['timeEnd']);

            // Staff Card
            $container->get(StaffCard::class)
                ->setPerson($coverage['gibbonPersonID'])
                ->setStatus($status)
                ->compose($page);

            // Today's Coverage View Composer
            $container->get(CoverageTodayView::class)
                ->setCoverage($coverage['gibbonStaffCoverageID'], $coverage['gibbonPersonID'])
                ->compose($page);
        }
        $displayCount++;
    }


    // TEACHER COVERAGE
    $criteria = $staffCoverageGateway->newQueryCriteria(true)
        ->sortBy('dateStart', 'DESC')
        ->filterBy('date:upcoming')
        ->fromPOST('staffCoverageSelf');

    $coverage = $staffCoverageGateway->queryCoverageByPersonAbsent($criteria, $session->get('gibbonSchoolYearID'), $gibbonPersonID, false);
    if (isActionAccessible($guid, $connection2, '/modules/Staff/coverage_request.php') || $coverage->getResultCount() > 0) {
        $coverageByTimetable = count(array_filter($coverage->toArray(), function($item) {
            return !empty($item['gibbonTTDayRowClassID']);
        }));

        $table = DataTable::createPaginated('staffCoverageSelf', $criteria);
        $table->setTitle(__('My Coverage'));

        $table->modifyRows(function ($coverage, $row) {
            if ($coverage['status'] == 'Accepted') $row->addClass('current');
            if ($coverage['status'] == 'Declined') $row->addClass('error');
            if ($coverage['status'] == 'Cancelled') $row->addClass('dull');
            return $row;
        });

        $table->addMetaData('filterOptions', [
            'date:upcoming'    => __('Upcoming'),
            'date:past'        => __('Past'),
            'status:requested' => __('Status').': '.__('Requested'),
            'status:accepted'  => __('Status').': '.__('Accepted'),
            'status:declined'  => __('Status').': '.__('Declined'),
            'status:cancelled' => __('Status').': '.__('Cancelled'),
        ]);

        if ($coverageByTimetable) {
            $table->addColumn('date', __('Date'))
                ->format(Format::using('dateReadable', 'date'))
                ->formatDetails(function ($coverage) {
                    return Format::small(Format::dayOfWeekName($coverage['date']));
                });

            $table->addColumn('period', __('Period'))
                    ->description(__('Time'))
                    ->formatDetails([AbsenceFormats::class, 'timeDetails']);

            $table->addColumn('contextName', __('Cover'));
        } else {
            $table->addColumn('date', __('Date'))
                ->context('primary')
                ->format([AbsenceFormats::class, 'dateDetails']);
        }

        $table->addColumn('status', __('Status'))
            ->format(function ($coverage) use ($urgencyThreshold) {
                return AbsenceFormats::coverageStatus($coverage, $urgencyThreshold);
            });

        $table->addColumn('requested', __('Coverage'))
            ->context('primary')
            ->sortable(['surnameCoverage', 'preferredNameCoverage'])
            ->format([AbsenceFormats::class, 'substituteDetails']);

        $table->addColumn('notesCoverage', __('Notes'))
            ->format(function ($coverage) {
                return Format::truncate($coverage['notesStatus'], 60);
            })
            ->formatDetails(function ($coverage) {
                return Format::small(Format::truncate($coverage['notesCoverage'], 60));
            });

        $table->addActionColumn()
            ->addParam('gibbonStaffCoverageID')
            ->addParam('gibbonStaffAbsenceID')
            ->format(function ($coverage, $actions) use ($guid, $connection2, $coverageMode) {
                $actions->addAction('view', __('View Details'))
                    ->isModal(800, 550)
                    ->setURL('/modules/Staff/coverage_view_details.php');

                if ($coverage['status'] == 'Requested' || $coverage['status'] == 'Pending' || $coverage['status'] == 'Accepted') {
                    $actions->addAction('edit', __('Edit'))
                        ->setURL('/modules/Staff/coverage_view_edit.php');
                }
                   
                if ($coverage['status'] == 'Requested' || ($coverage['status'] == 'Accepted' && $coverage['dateEnd'] >= date('Y-m-d'))) {
                    $actions->addAction('cancel', __('Cancel'))
                        ->setIcon('iconCross')
                        ->setURL('/modules/Staff/coverage_view_cancel.php');
                }

                $canRequestCoverage = isActionAccessible($guid, $connection2, '/modules/Staff/coverage_request.php') && (($coverageMode == 'Requested' && $coverage['absenceStatus'] == 'Approved') || ($coverageMode == 'Assigned' && $coverage['absenceStatus'] != 'Declined'));

                if ($canRequestCoverage && !empty($coverage['gibbonStaffAbsenceID']) && $coverage['status'] == 'Declined') {
                    $actions->addAction('coverage', __('Request Coverage'))
                        ->setIcon('attendance')
                        ->setURL('/modules/Staff/coverage_request.php');
                }
            });

        echo $table->render($coverage);
        $displayCount++;
    }

    // SUBSTITUTE COVERAGE
    $internalCoverage = $settingGateway->getSettingByScope('Staff', 'coverageInternal');
    $substitute = $substituteGateway->getSubstituteByPerson($gibbonPersonID, $internalCoverage);
    if (!empty($substitute)) {
        $criteria = $staffCoverageGateway->newQueryCriteria();

        $coverage = $staffCoverageGateway->queryCoverageByPersonCovering($criteria, $session->get('gibbonSchoolYearID'), $gibbonPersonID, false);
        $exceptions = $substituteGateway->queryUnavailableDatesBySub($criteria, $session->get('gibbonSchoolYearID'), $gibbonPersonID);
        $schoolYear = $schoolYearGateway->getSchoolYearByID($session->get('gibbonSchoolYearID'));

        // CALENDAR VIEW
        if ($internalCoverage == 'N') {
            $table = CoverageCalendar::create($coverage->toArray(), $exceptions->toArray(), $schoolYear['firstDay'], $schoolYear['lastDay']);

            $table->addHeaderAction('availability', __('Edit Availability'))
                ->setURL('/modules/Staff/coverage_availability.php')
                ->setIcon('planner')
                ->displayLabel();

            echo $table->getOutput().'<br/>';
        }

        // QUERY
        $criteria = $staffCoverageGateway->newQueryCriteria(true)
            ->sortBy('date', 'DESC')
            ->filterBy('date:upcoming')
            ->fromPOST('staffCoverageOther');

        $coverage = $staffCoverageGateway->queryCoverageByPersonCovering($criteria, $session->get('gibbonSchoolYearID'), $gibbonPersonID);

        // DATA TABLE
        $table = DataTable::createPaginated('staffCoverageOther', $criteria);
        $table->setTitle(__('Coverage Requests'));

        $table->modifyRows(function ($coverage, $row) {
            if ($coverage['status'] == 'Accepted') $row->addClass('current');
            if ($coverage['status'] == 'Declined') $row->addClass('error');
            if ($coverage['status'] == 'Cancelled') $row->addClass('dull');
            return $row;
        });

        $table->addMetaData('filterOptions', [
            'date:upcoming'    => __('Upcoming'),
            'date:past'        => __('Past'),
            'status:requested' => __('Status').': '.__('Requested'),
            'status:accepted'  => __('Status').': '.__('Accepted'),
            'status:declined'  => __('Status').': '.__('Declined'),
            'status:cancelled' => __('Status').': '.__('Cancelled'),
        ]);

        $table->addColumn('status', __('Status'))
            ->width('15%')
            ->format(function ($coverage) use ($urgencyThreshold) {
                return AbsenceFormats::coverageStatus($coverage, $urgencyThreshold);
            });

        $table->addColumn('date', __('Date'))
            ->context('primary')
            ->format([AbsenceFormats::class, 'dateDetails']);

        $table->addColumn('requested', __('Person'))
            ->context('primary')
            ->width('30%')
            ->sortable(['surname', 'preferredName'])
            ->format([AbsenceFormats::class, 'personDetails']);

        $table->addColumn('notesStatus', __('Comment'))
            ->format(function ($coverage) {
                return Format::truncate($coverage['notesStatus'], 60);
            });

        $table->addActionColumn()
            ->addParam('gibbonStaffCoverageID')
            ->format(function ($coverage, $actions) {

                if ($coverage['status'] == 'Requested') {
                    $actions->addAction('accept', __('Accept'))
                        ->setIcon('iconTick')
                        ->setURL('/modules/Staff/coverage_view_accept.php');

                    $actions->addAction('decline', __('Decline'))
                        ->setIcon('iconCross')
                        ->setURL('/modules/Staff/coverage_view_decline.php');
                } else {
                    $actions->addAction('view', __('View Details'))
                        ->isModal(800, 550)
                        ->setURL('/modules/Staff/coverage_view_details.php');
                }
            });

        echo $table->render($coverage);
        $displayCount++;
    }

    if ($displayCount == 0) {
        echo $page->getBlankSlate();
    }
}
