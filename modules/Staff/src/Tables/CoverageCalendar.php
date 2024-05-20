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

namespace Gibbon\Module\Staff\Tables;

use Gibbon\Services\Format;
use Gibbon\Domain\DataSet;
use Gibbon\Tables\DataTable;
use DateTime;
use DateInterval;
use DatePeriod;

/**
 * CoverageCalendar
 *
 * A reusable DataTable class for displaying coverage and availability in a colour-coded calendar view.
 *
 * @version v18
 * @since   v18
 */
class CoverageCalendar
{
    public static function create($coverage, $exceptions, $dateStart, $dateEnd)
    {
        $calendar = [];
        $dateRange = new DatePeriod(
            new DateTime(substr($dateStart, 0, 7).'-01'),
            new DateInterval('P1M'),
            new DateTime($dateEnd)
        );

        $coverageByDate = array_reduce($coverage, function ($group, $item) {
            if ($item['status'] == 'Cancelled' || $item['status'] == 'Declined') return $group;
            $group[$item['date']][] = $item;
            return $group;
        }, []);

        $exceptionsByDate = array_reduce($exceptions, function ($group, $item) {
            $group[$item['date']][] = $item;
            return $group;
        }, []);

        foreach ($dateRange as $month) {
            $days = [];
            for ($dayCount = 1; $dayCount <= $month->format('t'); $dayCount++) {
                $date = new DateTime($month->format('Y-m').'-'.$dayCount);
                $coverageListByDay = $coverageByDate[$date->format('Y-m-d')] ?? [];

                $coverageCount = count($coverageListByDay);

                $days[$dayCount] = [
                    'date'    => $date,
                    'number'  => $dayCount,
                    'count'   => $coverageCount,
                    'weekend' => $date->format('N') >= 6,
                    'coverage' => current($coverageListByDay),
                    'exception' => isset($exceptionsByDate[$date->format('Y-m-d')])
                        ? current($exceptionsByDate[$date->format('Y-m-d')])
                        : null,
                ];
            }

            $calendar[] = [
                'name'  => Format::monthName($month, true),
                'days'  => $days,
            ];
        }

        $table = DataTable::create('staffAbsenceCalendar')
            ->setTitle(__('Calendar'));

        $table->getRenderer()->addData('class', 'calendarTable border-collapse bg-transparent border-r-0');
        $table->addMetaData('hidePagination', true);
        $table->modifyRows(function ($values, $row) {
            return $row->setClass('bg-transparent');
        });

        $table->addColumn('name', '')->notSortable()->context('primary');

        for ($dayCount = 1; $dayCount <= 31; $dayCount++) {
            $table->addColumn($dayCount, '')
                ->context('primary')
                ->notSortable()
                ->format(function ($month) use ($dayCount) {
                    $day = $month['days'][$dayCount] ?? null;
                    if (empty($day['coverage']) || ($day['count'] <= 0 && !$day['exception'])) return '';

                    $coverage = $day['coverage'];

                    $url = 'fullscreen.php?q=/modules/Staff/coverage_view_details.php&gibbonStaffCoverageID='.$coverage['gibbonStaffCoverageID'].'&width=800&height=550';

                    $params['title'] = Format::dayOfWeekName($day['date']).'<br/>'.Format::dateReadable($day['date'], Format::MEDIUM);
                    $params['class'] = '';
                    if ($coverage['allDay'] == 'N') {
                        $params['class'] = $coverage['timeStart'] < '12:00:00' ? 'half-day-am' : 'half-day-pm';
                    }

                    if ($day['count'] > 0) {
                        $name = Format::name($coverage['titleAbsence'], $coverage['preferredNameAbsence'], $coverage['surnameAbsence'], 'Staff', false, true);
                        if (empty($name)) {
                            $name = Format::name($coverage['titleStatus'], $coverage['preferredNameStatus'], $coverage['surnameStatus'], 'Staff', false, true);
                        }
                        $params['class'] .= ' thickbox';
                        $params['title'] .= '<br/>'.$name.'<br/>'.__($coverage['status']);
                    } elseif ($day['exception']) {
                        if ($day['exception']['allDay'] == 'N') {
                            $params['class'] = $day['exception']['timeStart'] < '12:00:00' ? 'half-day-am' : 'half-day-pm';
                        }

                        $url = 'index.php?q=/modules/Staff/coverage_availability.php&gibbonPersonID='.$day['exception']['gibbonPersonID'];
                        $params['title'] .= '<br/>'.__($day['exception']['reason'] ?? 'Not Available');
                    }

                    return Format::link($url, $day['number'], $params);
                })
                ->modifyCells(function ($month, $cell) use ($dayCount) {
                    $day = $month['days'][$dayCount] ?? null;
                    if (empty($day)) return '';

                    $cell->addClass($day['date']->format('Y-m-d') == date('Y-m-d') ? 'border-2 border-gray-700' : 'border');

                    switch ($day['coverage']['status'] ?? '') {
                        case 'Requested': $cellColor = 'bg-chart2'; break;
                        case 'Accepted':  $cellColor = 'bg-chart0'; break;
                        default:          $cellColor = 'bg-gray-500';
                    }

                    if ($day['count'] > 0) $cell->addClass($cellColor);
                    elseif ($day['exception']) $cell->addClass('bg-gray-500');
                    elseif ($day['weekend']) $cell->addClass('bg-gray-200');
                    else $cell->addClass('bg-white');

                    $cell->addClass('h-3 sm:h-6');

                    return $cell;
                });
        }

        return $table->withData(new DataSet($calendar));
    }
}
