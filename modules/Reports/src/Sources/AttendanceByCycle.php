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

use Gibbon\Module\Reports\DataSource;

class AttendanceByCycle extends DataSource
{
    protected $countClassAsSchool;

    public function getSchema()
    {
        return [
            1 => [
                'present' => ['randomDigit'],
                'absent' => ['randomDigit'],
                'late'   => ['randomDigit'],
            ],
            2 => [
                'present' => ['randomDigit'],
                'absent' => ['randomDigit'],
                'late'   => ['randomDigit'],
            ],
            3 => [
                'present' => ['randomDigit'],
                'absent'  => ['randomDigit'],
                'late'    => ['randomDigit'],
            ],
        ];
    }

    public function getData($ids = [])
    {
        if (empty($this->countClassAsSchool)) {
            $this->countClassAsSchool = $this->db()->selectOne("SELECT value FROM gibbonSetting WHERE scope='Attendance' AND name='countClassAsSchool'");
        }

        $data = [
            'gibbonStudentEnrolmentID' => $ids['gibbonStudentEnrolmentID'],
            'gibbonReportID'       => $ids['gibbonReportID']
        ];
        $sql = "SELECT DISTINCT MAX(gibbonReportingCycle.cycleNumber), gibbonReport.gibbonReportID, gibbonAttendanceLogPerson.gibbonCourseClassID, gibbonAttendanceLogPerson.date, gibbonAttendanceLogPerson.timestampTaken, gibbonAttendanceLogPerson.type, gibbonAttendanceLogPerson.context, gibbonAttendanceCode.scope, gibbonAttendanceCode.direction
                FROM gibbonReport
                JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonSchoolYearID=gibbonReport.gibbonSchoolYearID)
                JOIN gibbonSchoolYear ON (gibbonSchoolYear.gibbonSchoolYearID=gibbonStudentEnrolment.gibbonSchoolYearID)
                JOIN gibbonYearGroup ON (FIND_IN_SET(gibbonYearGroup.gibbonYearGroupID, gibbonReport.gibbonYearGroupIDList) )
                JOIN gibbonReportingCycle ON (gibbonReportingCycle.gibbonSchoolYearID=gibbonReport.gibbonSchoolYearID )
                JOIN gibbonAttendanceLogPerson ON (gibbonAttendanceLogPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID AND gibbonAttendanceLogPerson.date BETWEEN gibbonReportingCycle.dateStart AND gibbonReportingCycle.dateEnd)
                JOIN gibbonAttendanceCode ON (gibbonAttendanceCode.gibbonAttendanceCodeID=gibbonAttendanceLogPerson.gibbonAttendanceCodeID)
                WHERE gibbonReport.gibbonReportID=:gibbonReportID
                AND gibbonReportingCycle.dateStart<=CURDATE()
                AND FIND_IN_SET(gibbonYearGroup.gibbonYearGroupID, gibbonReportingCycle.gibbonYearGroupIDList)
                AND gibbonStudentEnrolment.gibbonStudentEnrolmentID=:gibbonStudentEnrolmentID
                AND FIND_IN_SET(gibbonStudentEnrolment.gibbonYearGroupID, gibbonReportingCycle.gibbonYearGroupIDList)
                AND FIND_IN_SET(gibbonStudentEnrolment.gibbonYearGroupID, gibbonReport.gibbonYearGroupIDList)
                AND gibbonAttendanceLogPerson.date>=gibbonSchoolYear.firstDay
                AND gibbonAttendanceLogPerson.date<=CURDATE()
                AND (gibbonReport.accessDate IS NULL OR gibbonReportingCycle.dateStart<=gibbonReport.accessDate)
                GROUP BY gibbonAttendanceLogPerson.gibbonAttendanceLogPersonID
                ORDER BY gibbonAttendanceLogPerson.date, gibbonAttendanceLogPerson.timestampTaken";

        $result = $this->db()->select($sql, $data);

        $values = ($result->rowCount() > 0)? $result->fetchAll(\PDO::FETCH_GROUP) : array();

        $termAttendance = [];

        foreach ($values as $reportNum => $attendanceLogs) {
            // Group by date
            $attendance = array_reduce($attendanceLogs, function ($carry, $log) {
                $carry[$log['date']][] = $log;
                return $carry;
            }, array());

            $attendance = array_map(function ($logs) {
                $nonClassLogs = array_filter($logs, function ($log) {
                    return $log['context'] != 'Class' || $this->countClassAsSchool == 'Y';
                });
                $endOfDay = end($nonClassLogs);

                // Group by class
                $endOfClasses = array_reduce($logs, function ($carry, $log) {
                    if ($log['context'] == 'Class') {
                        $carry[$log['gibbonCourseClassID']][] = $log;
                    }
                    return $carry;
                }, array());

                // Filter to end of class only
                $endOfClasses = array_map(function ($logs) { 
                    return end($logs); 
                }, $endOfClasses);

                // Grab the the absent and late count (school-wide)
                $present = !empty($endOfDay) && ($endOfDay['direction'] == 'In')? 1 : 0;
                $absent = !empty($endOfDay) && ($endOfDay['direction'] == 'Out' && $endOfDay['scope'] == 'Offsite')? 1 : 0;
                $late = !empty($endOfDay) && ($endOfDay['scope'] == 'Onsite - Late' || $endOfDay['scope'] == 'Offsite - Late')? 1 : 0;

                // Optionally grab the class absent and late counts too
                $presentClass = $absentClass = $lateClass = 0;
                if ($this->countClassAsSchool == 'Y') {
                    foreach ($endOfClasses as $log) {
                        $presentClass += !empty($log) && ($log['direction'] == 'In')? 1 : 0;
                        $absentClass += !empty($log) && ($log['direction'] == 'Out' && $log['scope'] == 'Offsite')? 1 : 0;
                        $lateClass += !empty($log) && ($log['scope'] == 'Onsite - Late' || $log['scope'] == 'Offsite - Late')? 1 : 0;
                    }
                }

                return ['present' => $present, 'absent' => $absent, 'late' => $late, 'presentClass' => $presentClass, 'absentClass' => $absentClass, 'lateClass' => $lateClass];
            }, $attendance);

            // Sum up the absences for the term
            $termAttendance[$reportNum] = array_reduce($attendance, function($carry, $item) {
                $carry['present'] += $item['present'] ?? 0;
                $carry['absent'] += $item['absent'] ?? 0;
                $carry['late'] += $item['late'] ?? 0;
                $carry['presentClass'] += $item['presentClass'] ?? 0;
                $carry['absentClass'] += $item['absentClass'] ?? 0;
                $carry['lateClass'] += $item['lateClass'] ?? 0;
                return $carry;
            }, ['present' => 0, 'absent' => 0, 'late' => 0, 'presentClass' => 0, 'absentClass' => 0, 'lateClass' => 0]);
        }
        
        return $termAttendance;
    }
}
