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

namespace Gibbon\Module\Attendance;

use DatePeriod;
use DateInterval;
use DateTimeImmutable;
use Gibbon\Domain\DataSet;
use Gibbon\Services\Format;
use Gibbon\Contracts\Database\Connection;
use Gibbon\Domain\School\SchoolYearTermGateway;
use Gibbon\Domain\Attendance\AttendanceLogPersonGateway;

/**
 * Student History Data
 *
 * @version v18
 * @since   v18
 */
class StudentHistoryData
{
    protected $pdo;
    protected $termGateway;
    protected $attendanceLogGateway;

    public function __construct(Connection $pdo, SchoolYearTermGateway $termGateway, AttendanceLogPersonGateway $attendanceLogGateway)
    {
        $this->pdo = $pdo;
        $this->termGateway = $termGateway;
        $this->attendanceLogGateway = $attendanceLogGateway;
    }

    /**
     * Build a data set of attendance logs grouped by term.
     *
     * @param string $gibbonSchoolYearID
     * @param string $gibbonPersonID
     * @param string $dateStart     Y-m-d
     * @param string $dateEnd       Y-m-d
     * @return DataSet
     */
    public function getAttendanceData($gibbonSchoolYearID, $gibbonPersonID, $dateStart, $dateEnd)
    {
        $connection2 = $this->pdo->getConnection();

        $countClassAsSchool = getSettingByScope($connection2, 'Attendance', 'countClassAsSchool');
        $firstDayOfTheWeek = getSettingByScope($connection2, 'System', 'firstDayOfTheWeek');

        // Get Logs
        $logs = $this->attendanceLogGateway
            ->selectAllAttendanceLogsByPerson($gibbonSchoolYearID, $gibbonPersonID)
            ->fetchGrouped();

        // Get Weekdays
        $sql = "SELECT nameShort, name FROM gibbonDaysOfWeek where schoolDay='Y'";
        $daysOfWeek = $this->pdo->select($sql)->fetchKeyPair();
        if ($firstDayOfTheWeek == 'Sunday' && in_array('Sunday', $daysOfWeek)) {
            $daysOfWeek = array('Sun' => 'Sunday') + $daysOfWeek;
        }
        if ($firstDayOfTheWeek == 'Saturday' && in_array('Saturday', $daysOfWeek)) {
            $daysOfWeek = array('Sat' => 'Saturday') + $daysOfWeek;
        }

        // Get Terms
        $criteria = $this->termGateway->newQueryCriteria(true)
            ->filterBy('schoolYear', $gibbonSchoolYearID)
            ->filterBy('firstDay', date('Y-m-d'))
            ->sortBy('firstDay');

        $terms = $this->termGateway->querySchoolYearTerms($criteria)->toArray();
        $today = new DateTimeImmutable();
        $classLogs = [];

        foreach ($terms as $index => $term) {
            $specialDays = $this->termGateway->selectSchoolClosuresByTerm($term['gibbonSchoolYearTermID'])->fetchKeyPair();

            $firstDay = new DateTimeImmutable($term['firstDay']);
            $lastDay = new DateTimeImmutable($term['lastDay']);

            $dateRange = new DatePeriod(
                $firstDay->modify($firstDayOfTheWeek == 'Monday' ? "Monday this week" : "Sunday last week"),
                new DateInterval('P1D'),
                $lastDay->modify('+1 day')
            );

            $dayCount = 0;
            foreach ($dateRange as $i => $date) {
                if ($date > $today) continue;
                if ($date > $lastDay) continue;

                $week = floor($dayCount / count($daysOfWeek));
                $weekday = $date->format('D');
                $dateYmd = $date->format('Y-m-d');

                if (!isset($daysOfWeek[$weekday])) continue;

                $absentCount = $presentCount = $partialCount = 0;

                $logs[$dateYmd] = array_map(function ($log) use (&$absentCount, &$presentCount, &$partialCount) {
                    if ($log['direction'] == 'Out' && $log['scope'] == 'Offsite') {
                        $log['status'] = 'absent';
                        $log['statusClass'] = 'error';
                        $absentCount++;
                    } elseif ($log['scope'] == 'Onsite - Late' || $log['scope'] == 'Offsite - Late' || $log['scope'] == 'Offsite - Left') {
                        $log['status'] = 'partial';
                        $log['statusClass'] = 'warning';
                        $partialCount++;
                    } else {
                        $log['status'] = 'present';
                        $log['statusClass'] = $log['scope'] == 'Offsite' ? 'message' : 'success';
                        $presentCount++;
                    }

                    return $log;
                }, $logs[$dateYmd] ?? []);

                // Filter class logs for End of Day purposes
                if ($countClassAsSchool == 'N') {
                    $classLogs[$dateYmd] = array_filter($logs[$dateYmd], function ($log) {
                        return $log['context'] == 'Class';
                    });
                    $logs[$dateYmd] = array_filter($logs[$dateYmd], function ($log) {
                        return $log['context'] <> 'Class';
                    });
                }
                
                $endOfDay = isset($logs[$dateYmd]) ? end($logs[$dateYmd]) : [];

                // Handle cases where school-wide attendance does not exist, but class attendance does
                if (empty($endOfDay) && !empty($classLogs)) {
                    $endOfDay = [
                        'date'        => $dateYmd,
                        'type'        => 'Incomplete',
                        'status'      => '',
                        'statusClass' => 'dull border-gray-700',
                    ];
                }

                $dayData = [
                    'date'            => $dateYmd,
                    'dateDisplay'     => Format::date($date),
                    'logs'            => $logs[$dateYmd] ?? [],
                    'classLogs'       => $classLogs[$dateYmd] ?? [],
                    'endOfDay'        => $endOfDay,
                    'specialDay'      => $specialDays[$dateYmd] ?? '',
                    'outsideTerm'     => $date < $firstDay || $date > $lastDay,
                    'beforeStartDate' => !empty($dateStart) && $dateYmd < $dateStart,
                    'afterEndDate'    => !empty($dateEnd) && $dateYmd > $dateEnd,
                    'absentCount'     => $absentCount,
                    'presentCount'    => $presentCount,
                    'partialCount'    => $partialCount,
                    'gibbonPersonID'  => $gibbonPersonID,
                ];

                $terms[$index]['daysOfWeek'] = $daysOfWeek;
                $terms[$index]['weeks'][$week][$weekday] = $dayData;
                $dayCount++;
            }
        }

        return new DataSet($terms);
    }
}
