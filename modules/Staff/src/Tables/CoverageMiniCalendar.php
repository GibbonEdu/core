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

namespace Gibbon\Module\Staff\Tables;

use Gibbon\Services\Format;
use Gibbon\Domain\DataSet;
use Gibbon\Tables\DataTable;
use DateTime;
use DateInterval;
use DatePeriod;

/**
 * CoverageMiniCalendar
 *
 * A reusable DataTable class for displaying coverage and availability in a colour-coded calendar view.
 *
 * @version v19
 * @since   v19
 */
class CoverageMiniCalendar
{
    public static function renderTimeRange($availabilityByDate, $date)
    {
        $title = '';
        foreach ($availabilityByDate as $availability) {
            $title .= __($availability['status']).': ';
            $title .= $availability['allDay'] == 'N'
                ? Format::timeRange($availability['timeStart'], $availability['timeEnd'])
                : __('All Day');
            $title .= '<br/>';
        }

        $output = '<div class="flex h-12 border" style="min-width: 8rem;" title="'.$title.'">';

        $timeRange = new DatePeriod($date->modify('8:30am'), new DateInterval('PT10M'), $date->modify('4pm'));

        foreach ($timeRange as $time) {
            $class = 'bg-white';

            $timeStart = $time->format('H:i:s');
            $timeEnd = $time->modify('+9 minutes')->format('H:i:s');

            foreach ($availabilityByDate as $availability) {
                switch ($availability['status']) {
                    case 'Not Available':   $highlight = 'bg-gray-500'; break;
                    case 'Absent':          $highlight = 'bg-gray-500'; break;
                    case 'Teaching':        $highlight = 'bg-blue-500'; break;
                    default:                $highlight = 'bg-purple-500';
                }

                if ($availability['allDay'] == 'Y') $class = $highlight;
                if ($timeStart <= $availability['timeEnd'] && $timeEnd >= $availability['timeStart']) $class = $highlight;
            }
            $output .= '<div class="flex-1 '.$class.'"></div>';
        }
        $output .= '</div>';

        return $output;
    }
}
