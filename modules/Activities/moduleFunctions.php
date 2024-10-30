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

function num2alpha($n)
{
    for ($r = ''; $n >= 0; $n = intval($n / 26) - 1) {
        $r = chr($n % 26 + 0x41).$r;
    }

    return $r;
}

function getActivityWeekDays($connection2, $gibbonActivityID)
{
    // Get the time slots for this activity to determine weekdays
    $data = array('gibbonActivityID' => $gibbonActivityID);
    $sql = 'SELECT nameShort FROM gibbonActivitySlot JOIN gibbonDaysOfWeek ON (gibbonActivitySlot.gibbonDaysOfWeekID=gibbonDaysOfWeek.gibbonDaysOfWeekID) WHERE gibbonActivityID=:gibbonActivityID ORDER BY gibbonDaysOfWeek.gibbonDaysOfWeekID';
    $result = $connection2->prepare($sql);
    $result->execute($data);

    return $result->fetchAll(PDO::FETCH_COLUMN);
}

function getActivitySessions($guid, $connection2, $weekDays, $timespan, $sessionAttendanceData)
{
    $activitySlots = array();

    if (count($timespan) > 0) {
        // Iterate one day at a time from start to end, adding the weekdays that match a time slot
        for ($time = $timespan['start']; $time <= $timespan['end']; $time += 86400) {
            $day = date('Y-m-d', $time);
            if (!isSchoolOpen($guid, $day, $connection2)) continue;

            if (isset($sessionAttendanceData[ $day ])) {
                $activitySlots[$day] = $time;
            } elseif (in_array(date('D', $time), $weekDays)) {
                $activitySlots[$day] = $time;
            }
        }

        foreach ($sessionAttendanceData as $sessionDate => $sessionData) {
            $activitySlots[$sessionDate] = strtotime($sessionDate);
        }

        ksort($activitySlots);
    }

    return $activitySlots;
}

function getActivityTimespan($connection2, $gibbonActivityID, $gibbonSchoolYearTermIDList)
{
    global $container;

    // Figure out what kind of dateType we're using
    $dateType = $container->get(SettingGateway::class)->getSettingByScope('Activities', 'dateType');
    if ($dateType != 'Date') {
        if (empty($gibbonSchoolYearTermIDList)) {
            return [];
        }

        $data = ['gibbonSchoolYearTermIDList' => $gibbonSchoolYearTermIDList];
        $sql = "SELECT MIN(UNIX_TIMESTAMP(firstDay)) as start, MAX(UNIX_TIMESTAMP(lastDay)) as end FROM gibbonSchoolYearTerm WHERE FIND_IN_SET(gibbonSchoolYearTermID, :gibbonSchoolYearTermIDList)";
        $result = $connection2->prepare($sql);
        $result->execute($data);

        $timespan = $result->fetch();
    } else {
        $data = ['gibbonActivityID' => $gibbonActivityID];
        $sql = 'SELECT UNIX_TIMESTAMP(programStart) as start, UNIX_TIMESTAMP(programEnd) as end FROM gibbonActivity WHERE gibbonActivityID=:gibbonActivityID';
        $result = $connection2->prepare($sql);
        $result->execute($data);

        $timespan = $result->fetch();
    }

    return $timespan;
}
