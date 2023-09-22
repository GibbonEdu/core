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

namespace Gibbon\Module\Reports\Sources;

use DatePeriod;
use DateInterval;
use DateTimeImmutable;
use Gibbon\Module\Reports\DataSource;

class Attendance extends DataSource
{
    private static $schoolYearTerms;
    private static $daysOfWeek;
    private static $schoolClosures;

    public function getSchema()
    {
        return [
            'total' => ['numberBetween', 150, 200],
            'present' => ['numberBetween', 100, 150],
            'absent' => ['numberBetween', 1, 25],
            'partial'   => ['numberBetween', 1, 25],
            'late'   => ['numberBetween', 1, 25],
            'left'   => ['numberBetween', 1, 25],
        ];
    }

    public function getData($ids = [])
    {
        if (empty(static::$schoolYearTerms)) {
            $data = ['gibbonReportID' => $ids['gibbonReportID']];
            $sql = "SELECT gibbonSchoolYear.gibbonSchoolYearID, gibbonSchoolYearTerm.firstDay, gibbonSchoolYearTerm.lastDay
                    FROM gibbonReport 
                    JOIN gibbonSchoolYear ON (gibbonSchoolYear.gibbonSchoolYearID=gibbonReport.gibbonSchoolYearID)
                    JOIN gibbonSchoolYearTerm ON (gibbonSchoolYearTerm.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID)
                    WHERE gibbonReport.gibbonReportID=:gibbonReportID";
            static::$schoolYearTerms = $this->db()->select($sql, $data)->fetchAll();
        }

        if (empty(static::$daysOfWeek)) {
            $sql = "SELECT nameShort, name FROM gibbonDaysOfWeek where schoolDay='Y'";
            static::$daysOfWeek = $this->db()->select($sql)->fetchKeyPair();
        }

        if (empty(static::$schoolClosures)) {
            $data = ['gibbonReportID' => $ids['gibbonReportID']];
            $sql = "SELECT gibbonSchoolYearSpecialDay.date, gibbonSchoolYearSpecialDay.name 
                    FROM gibbonReport 
                    JOIN gibbonSchoolYearTerm ON (gibbonSchoolYearTerm.gibbonSchoolYearID=gibbonReport.gibbonSchoolYearID)
                    JOIN gibbonSchoolYearSpecialDay ON (gibbonSchoolYearTerm.gibbonSchoolYearTermID=gibbonSchoolYearSpecialDay.gibbonSchoolYearTermID)
                    WHERE gibbonReport.gibbonReportID=:gibbonReportID AND gibbonSchoolYearSpecialDay.type='School Closure'
                    ORDER BY date";
            static::$schoolClosures = $this->db()->select($sql, $data)->fetchKeyPair();
        }

        $data = [
            'gibbonStudentEnrolmentID' => $ids['gibbonStudentEnrolmentID'],
            'gibbonReportID'       => $ids['gibbonReportID']
        ];
        $sql = "SELECT gibbonAttendanceLogPerson.date, gibbonReport.gibbonReportID, gibbonAttendanceLogPerson.gibbonCourseClassID, gibbonAttendanceLogPerson.date, gibbonAttendanceLogPerson.timestampTaken, gibbonAttendanceLogPerson.type, gibbonAttendanceLogPerson.context, gibbonAttendanceCode.scope, gibbonAttendanceCode.direction
                FROM gibbonReport
                JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonSchoolYearID=gibbonReport.gibbonSchoolYearID)
                JOIN gibbonSchoolYear ON (gibbonSchoolYear.gibbonSchoolYearID=gibbonReport.gibbonSchoolYearID)
                JOIN gibbonAttendanceLogPerson ON (gibbonAttendanceLogPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID)
                JOIN gibbonAttendanceCode ON (gibbonAttendanceCode.gibbonAttendanceCodeID=gibbonAttendanceLogPerson.gibbonAttendanceCodeID)
                WHERE gibbonReport.gibbonReportID=:gibbonReportID
                AND gibbonStudentEnrolment.gibbonStudentEnrolmentID=:gibbonStudentEnrolmentID
                AND gibbonAttendanceLogPerson.date>=gibbonSchoolYear.firstDay
                AND gibbonAttendanceLogPerson.date<=CURDATE()
                AND gibbonAttendanceLogPerson.context <> 'Class'
                GROUP BY gibbonAttendanceLogPerson.gibbonAttendanceLogPersonID
                ORDER BY gibbonAttendanceLogPerson.date, gibbonAttendanceLogPerson.timestampTaken";

        $values = $this->db()->select($sql, $data)->fetchGrouped();

        $attendance = ['total' => 0, 'present' => 0, 'absent' => 0, 'late' => 0, 'left' => 0];

        foreach (static::$schoolYearTerms as $term) {
            $dateRange = new DatePeriod(
                new DateTimeImmutable($term['firstDay']),
                new DateInterval('P1D'),
                (new DateTimeImmutable($term['lastDay']))->modify('+1 day')
            );

            foreach ($dateRange as $date) {
                if ($date->format('Y-m-d') > date('Y-m-d')) continue;

                if (!isset(static::$daysOfWeek[$date->format('D')])) continue;

                if (isset(static::$schoolClosures[$date->format('Y-m-d')])) continue;

                $attendance['total']++;

                $logs = $values[$date->format('Y-m-d')] ?? [];
                $endOfDay = end($logs);

                if (empty($logs)) continue;

                if ($endOfDay['direction'] == 'Out' && $endOfDay['scope'] == 'Offsite') {
                    $attendance['absent']++;
                } elseif ($endOfDay['scope'] == 'Onsite - Late' || $endOfDay['scope'] == 'Offsite - Late') {
                    $attendance['late']++;
                } elseif ($endOfDay['scope'] == 'Offsite - Left') {
                    $attendance['left']++;
                } elseif ($endOfDay['direction'] == 'In' || $endOfDay['scope'] == 'Onsite') {
                    $attendance['present']++;
                }
            }
        }

        $attendance['partial'] = $attendance['late'] + $attendance['left'];
        
        return $attendance;
    }
}
