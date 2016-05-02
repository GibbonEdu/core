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
    try {
        $data = array('gibbonActivityID' => $gibbonActivityID);
        $sql = 'SELECT nameShort FROM gibbonActivitySlot JOIN gibbonDaysOfWeek ON (gibbonActivitySlot.gibbonDaysOfWeekID=gibbonDaysOfWeek.gibbonDaysOfWeekID) WHERE gibbonActivityID=:gibbonActivityID ORDER BY gibbonDaysOfWeek.gibbonDaysOfWeekID';
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }

    return $result->fetchAll(PDO::FETCH_COLUMN);
}

function getActivitySessions($weekDays, $timespan, $sessionAttendanceData)
{
    $activitySlots = array();

    // Iterate one day at a time from start to end, adding the weekdays that match a time slot
    for ($time = $timespan['start']; $time <= $timespan['end']; $time += 86400) {
        $day = date('Y-m-d', $time);
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

    return $activitySlots;
}

function getActivityTimespan($connection2, $gibbonActivityID, $gibbonSchoolYearTermIDList)
{
    $timespan = array();

    // Figure out what kind of dateType we're using
    $dateType = getSettingByScope($connection2, 'Activities', 'dateType');
    if ($dateType != 'Date') {
        if (empty($gibbonSchoolYearTermIDList)) {
            return array();
        }

        try {
            $data = array();
            $sql = 'SELECT MIN(UNIX_TIMESTAMP(firstDay)) as start, MAX(UNIX_TIMESTAMP(lastDay)) as end FROM gibbonSchoolYearTerm WHERE gibbonSchoolYearTermID IN ('.$gibbonSchoolYearTermIDList.')';
            $result = $connection2->prepare($sql);
            $result->execute();
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }
        $timespan = $result->fetch();
    } else {
        try {
            $data = array('gibbonActivityID' => $gibbonActivityID);
            $sql = 'SELECT UNIX_TIMESTAMP(programStart) as start, UNIX_TIMESTAMP(programEnd) as end FROM gibbonActivity WHERE gibbonActivityID=:gibbonActivityID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }
        $timespan = $result->fetch();
    }

    return $timespan;
}
