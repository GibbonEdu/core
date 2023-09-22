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

use Gibbon\Services\Format;
use Gibbon\Domain\Staff\StaffGateway;
use Gibbon\Domain\Staff\StaffCoverageGateway;
use Gibbon\Domain\Staff\StaffCoverageDateGateway;
use Gibbon\Domain\Staff\StaffDutyPersonGateway;

if (isActionAccessible($guid, $connection2, '/modules/Staff/coverage_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $gibbonSchoolYearID = $session->get('gibbonSchoolYearID');
    $dateFormat = $session->get('i18n')['dateFormatPHP'];
    $date = isset($_REQUEST['date'])? DateTimeImmutable::createFromFormat('Y-m-d', $_REQUEST['date']) :new DateTimeImmutable();
    
    $staffCoverageGateway = $container->get(StaffCoverageGateway::class);
    $staffCoverageDateGateway = $container->get(StaffCoverageDateGateway::class);
    $staffDutyPersonGateway = $container->get(StaffDutyPersonGateway::class);
    $staffGateway = $container->get(StaffGateway::class);
    
    if (empty($_REQUEST['date'])) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $times = $staffCoverageDateGateway->selectCoveragePeriodsByDate($gibbonSchoolYearID, $date->format('Y-m-d'))->fetchGroupedUnique();

    $dutyList = $staffDutyPersonGateway->selectDutyByWeekday($date->format('l'))->fetchAll();
    $coverageList = $staffCoverageGateway->selectCoverageByTimetableDate($gibbonSchoolYearID, $date->format('Y-m-d'))->fetchAll();

    // Remove duty from the list that is being covered
    $dutyList = array_filter($dutyList, function ($item) use ($coverageList) {
        $coverageCount = count(array_filter($coverageList, function ($coverage) use ($item) {
            return $coverage['gibbonStaffDutyID'] == $item['gibbonStaffDutyID'];
        }));

        return $coverageCount <= 0;
    });

    // Add any missing coverage to the times
    foreach ($coverageList as $coverage) {
        $groupBy = 'tt-'.$coverage['timeStart'].'-'.$coverage['timeEnd'];
        if (empty($times[$groupBy]) && $coverage['context'] == 'Activity') {
            $times[$groupBy] = [
                'type'      => 'Activity',
                'period'    => 'Activities',
                'timeStart' => $coverage['timeStart'],
                'timeEnd'   => $coverage['timeEnd'],
            ];
        }
    }

    $fullList = array_merge($coverageList, $dutyList);
    $output = '';
    $lastTimeRange = '';

    foreach ($times as $groupBy => $timeSlot) {

        $coverageByTT = array_filter($fullList, function($item) use ($timeSlot) {
            return $item['timeStart'] >= $timeSlot['timeStart'] && $item['timeEnd'] <= $timeSlot['timeEnd'];
        });

        usort($coverageByTT, function ($a, $b) {
            return $a['timeStart'] <=> $b['timeStart'];
        });

        if (!empty($groupBy)) {
            $output .= __($timeSlot['period']).'<br/>';
        }

        foreach ($coverageByTT as $coverage) {
            if (($coverage['context'] == 'Class' || $coverage['context'] == 'Activity') && ($coverage['status'] != 'Accepted' && $coverage['status'] != 'Not Required')) continue;

            $details = '';

            if ($coverage['context'] == 'Class' || $coverage['context'] == 'Activity') {
                $details .= $coverage['initialsAbsence'].' - ';
                $details .= $coverage['contextName'].' '.$coverage['space'].' - ';
                
            } else if ($coverage['contextName'] == 'Staff Duty') {

                if (!empty($lastTimeRange) && $lastTimeRange != $coverage['timeStart'].$coverage['timeEnd']) {
                    $details .= '<br/>';
                }
                $lastTimeRange = $coverage['timeStart'].$coverage['timeEnd'];

                $details .= ($coverage['nameShort'] ?? $coverage['space'] ?? $coverage['context']).' - ';
                $details .= !($coverage['timeStart'] == $timeSlot['timeStart'] && $coverage['timeEnd'] == $timeSlot['timeEnd'])
                    ? Format::timeRange($coverage['timeStart'], $coverage['timeEnd']).' - '
                    : '';
            }

            if ($coverage['context'] == 'Class' && $coverage['status'] == 'Not Required') {
                $details .= Format::bold('no cover required');
            } else {
                $preferredName = $coverage['preferredNameCoverage'] ?? $coverage['preferredName'] ?? '';
                $surname = $coverage['surnameCoverage'] ?? $coverage['surname'] ?? '';
                $isUnique = $staffGateway->getIsPreferredNameUnique($preferredName);

                $details .= Format::bold(!$isUnique? $preferredName.' '.$surname : $preferredName);
            }

            $output .= $details.'<br/>';
        }

        $output .= '<br/>';
    }

    echo '<div class="text-sm mt-6 overflow-y-auto">'.$output.'</div>';
    
}
