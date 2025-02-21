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

namespace Gibbon\UI\Timetable;

use Gibbon\Contracts\Services\Session;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\School\DaysOfWeekGateway;
use Gibbon\Domain\School\SchoolYearSpecialDayGateway;
use Gibbon\Domain\Timetable\TimetableGateway;
use Gibbon\Domain\Timetable\TimetableColumnGateway;

/**
 * Timetable UI
 *
 * @version  v29
 * @since    v29
 */
class Structure
{
    protected $session;
    protected $settingGateway;
    protected $daysOfWeekGateway;
    protected $specialDayGateway;
    protected $timetableGateway;
    protected $timetableColumnGateway;

    protected $weekdays;
    protected $columns;
    protected $specialDays;

    protected $currentDate;
    protected $today;

    protected $dateRange;
    
    protected $timeStart;
    protected $timeEnd;

    protected $timeRangeStart;
    protected $timeRangeEnd;

    protected $timestampStart;
    protected $timestampEnd;

    private $pixelRatio = 1.0;

    public function __construct(Session $session, SettingGateway $settingGateway, DaysOfWeekGateway $daysOfWeekGateway, SchoolYearSpecialDayGateway $specialDayGateway, TimetableGateway $timetableGateway, TimetableColumnGateway $timetableColumnGateway)
    {
        $this->session = $session;
        $this->settingGateway = $settingGateway;
        $this->daysOfWeekGateway = $daysOfWeekGateway;
        $this->specialDayGateway = $specialDayGateway;
        $this->timetableGateway = $timetableGateway;
        $this->timetableColumnGateway = $timetableColumnGateway;
    }

    public function getToday()
    {
        return $this->today;
    }

    public function getCurrentTime()
    {
        return $this->today->format('G:i');
    }

    public function getCurrentDate()
    {
        return $this->currentDate;
    }

    public function setDate($date)
    {
        $this->currentDate = \DateTimeImmutable::createFromFormat('Y-m-d', $date ?? date('Y-m-d'));
        $this->today = new \DateTimeImmutable('now');

        $this->weekdays = $this->loadWeekdays();
        $this->dateRange = $this->calculateDateRange();
    }

    public function setTimetable($gibbonTTID)
    {
        $this->columns = $this->loadColumns($gibbonTTID);
        $this->specialDays = $this->loadSpecialDays();
    }

    public function expandTimeRange($timeStart, $timeEnd)
    {
        if ($timeStart < $this->timeStart || empty($this->timeStart)) {
            $this->timeStart = $timeStart;
        }
        if ($timeEnd > $this->timeEnd || empty($this->timeEnd)) {
            $this->timeEnd = $timeEnd;
        }
    }

    public function getTimeRange() : \DatePeriod
    {
        $interval = new \DateInterval('PT1H');
        $this->timeRangeStart = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $this->getCurrentDate()->format('Y-m-d').' '.$this->timeStart);
        $this->timeRangeEnd = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $this->getCurrentDate()->format('Y-m-d').' '.$this->timeEnd);
        
        $timeRange = new \DatePeriod($this->timeRangeStart, $interval, $this->timeRangeEnd);

        return $timeRange;
    }

    public function getDateRange() : \DatePeriod
    {
        return $this->dateRange;
    }

    public function getStartDate() : string
    {
        return $this->dateRange->getStartDate()->format('Y-m-d');
    }

    public function getEndDate() : string
    {
        return $this->dateRange->getEndDate()->format('Y-m-d');
    }

    public function getWeekdays()
    {
        return $this->weekdays;
    }

    public function getColumn($date)
    {
        return $this->columns[$date] ?? [];
    }

    public function daysInWeek()
    {
        return count($this->weekdays);
    }

    public function minutesToPixels($minutes)
    {
        return round((float)$minutes * $this->pixelRatio);
    }

    public function timeToPixels($time)
    {
        if (empty($time)) return 0;

        $date = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $this->getCurrentDate()->format('Y-m-d').' '.$time);
        $diff = $date->diff($this->timeRangeStart);

        return $this->minutesToPixels(($diff->h * 60) + $diff->i);
    }

    public function timeDifference($time1, $time2)
    {
        if (empty($time1) || empty($time2)) return 0;

        $date1 = \DateTimeImmutable::createFromFormat('H:i:s', $time1 > $time2 ? $time1 : $time2);
        $date2 = \DateTimeImmutable::createFromFormat('H:i:s', $time1 > $time2 ? $time2 : $time1);

        $diff = $date1->diff($date2);

        return ($diff->h * 60) + $diff->i;
    }

    public function isCurrentWeek()
    {
        return $this->today->format('W') == $this->currentDate->format('W');
    }

    protected function loadWeekdays()
    {
        $weekdays = $this->daysOfWeekGateway->selectSchoolWeekdays()->fetchAll();

        foreach ($weekdays as $weekday) {
            $this->expandTimeRange($weekday['schoolStart'], $weekday['schoolEnd']);
        }

        return $weekdays;
    }

    protected function loadSpecialDays()
    {
        $specialDays = $this->specialDayGateway->selectSpecialDaysByDateRange($this->getStartDate(), $this->getEndDate())->fetchGroupedUnique();

        foreach ($specialDays as $specialDay) {

            if ($specialDay['type'] == 'School Closure') {
                unset($this->columns[$specialDay['date']]);
                continue;
            }

            foreach ($this->getColumn($specialDay['date']) as $index => $period) {
                if (!($period['timeStart'] >= $specialDay['schoolStart'] && $period['timeStart'] < $specialDay['schoolEnd']) 
                && !($specialDay['schoolStart'] >= $period['timeStart'] && $specialDay['schoolStart'] < $period['timeEnd'])) {
                    unset($this->columns[$specialDay['date']][$index]);
                    continue;
                }

                if (!empty($specialDay['schoolStart']) && $specialDay['schoolStart'] > $period['timeStart']) {
                    $period['timeStart'] = $specialDay['schoolStart'];
                }

                if (!empty($specialDay['schoolEnd']) && $specialDay['schoolEnd'] < $period['timeEnd']) {
                    $period['timeEnd'] = $specialDay['schoolEnd'];
                }
                $period['duration'] = $this->timeDifference($period['timeStart'], $period['timeEnd']);

                $this->columns[$specialDay['date']][$index] = $period;
            }
        }

        

        return $specialDays;
    }

    protected function loadColumns($gibbonTTID)
    {
        $columnList = $this->timetableColumnGateway->selectTTColumnsByDateRange($gibbonTTID, $this->getStartDate(), $this->getEndDate())->fetchAll();
        $columns = [];

        foreach ($columnList as $period) {
            $this->expandTimeRange($period['timeStart'], $period['timeEnd']);

            $period['duration'] = $this->timeDifference($period['timeStart'], $period['timeEnd']);
            $columns[$period['date']][$period['nameShort']] = $period;
        }

        return $columns;
    }

    protected function calculateDateRange()
    {
        $this->timestampStart = $this->currentDate->format('U');

        for ($i = 0; $i < $this->daysInWeek(); $i++) {
            $this->timestampStart = $this->timestampStart - 86400;
            if (date('D', $this->timestampStart) == $this->weekdays[0]['nameShort']) {
                break;
            }
        }
        $this->timestampEnd = $this->timestampStart + (86400 * ($this->daysInWeek() - 1));

        for ($i = 0; $i < $this->daysInWeek(); $i++) {
            $this->weekdays[$i]['date'] = date('Y-m-d', $this->timestampStart + (86400 * $i));
        }

        return new \DatePeriod(
            (new \DateTime(date('Y-m-d H:i:s', $this->timestampStart)))->modify('-1 day'),
            new \DateInterval('P1D'),
            (new \DateTime(date('Y-m-d H:i:s', $this->timestampEnd)))->modify('+1 day')
        );
    }
}
