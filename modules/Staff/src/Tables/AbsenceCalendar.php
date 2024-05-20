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
 * AbsenceCalendar
 *
 * A reusable DataTable class for displaying absences in a colour-coded calendar view.
 *
 * @version v18
 * @since   v18
 */
class AbsenceCalendar
{
    public static function create($absences, $dateStart, $dateEnd)
    {
        $calendar = [];
        $dateRange = new DatePeriod(
            new DateTime(substr($dateStart, 0, 7).'-01'),
            new DateInterval('P1M'),
            new DateTime($dateEnd)
        );

        foreach ($dateRange as $month) {
            $days = [];
            for ($dayCount = 1; $dayCount <= $month->format('t'); $dayCount++) {
                $date = new DateTime($month->format('Y-m').'-'.$dayCount);
                $absenceListByDay = $absences[$date->format('Y-m-d')] ?? [];
                $absenceCount = count($absenceListByDay);

                $days[$dayCount] = [
                    'date'    => $date,
                    'number'  => $dayCount,
                    'count'   => $absenceCount,
                    'weekend' => $date->format('N') >= 6,
                    'absence' => current($absenceListByDay),
                ];
            }

            $calendar[] = [
                'name'  => Format::monthName($month, true),
                'days'  => $days,
            ];
        }

        $table = DataTable::create('staffAbsenceCalendar');
        $table->setTitle(__('Calendar'));
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
                    if (empty($day) || $day['count'] <= 0) return '';

                    $url = 'fullscreen.php?q=/modules/Staff/absences_view_details.php&gibbonStaffAbsenceID='.$day['absence']['gibbonStaffAbsenceID'].'&width=800&height=550';
                    $title = Format::dayOfWeekName($day['date']).'<br/>'.Format::dateReadable($day['date'], Format::MEDIUM);
                    $title .= '<br/>'.$day['absence']['type'];
                    $classes = ['thickbox'];
                    if ($day['absence']['allDay'] == 'N') {
                        $classes[] = $day['absence']['timeStart'] < '12:00:00' ? 'half-day-am' : 'half-day-pm';
                    }

                    return Format::link($url, $day['number'], ['title' => $title, 'class' => implode(' ', $classes)]);
                })
                ->modifyCells(function ($month, $cell) use ($dayCount) {
                    $day = $month['days'][$dayCount] ?? null;
                    if (empty($day)) return '';

                    $cell->addClass($day['date']->format('Y-m-d') == date('Y-m-d') ? 'border-2 border-gray-700' : 'border');

                    if ($day['count'] > 0) $cell->addClass('bg-chart'.($day['absence']['sequenceNumber'] % 10));
                    elseif ($day['weekend']) $cell->addClass('bg-gray-200');
                    else $cell->addClass('bg-white');

                    $cell->addClass('h-3 sm:h-6');

                    return $cell;
                });
        }

        return $table->withData(new DataSet($calendar));
    }
}
