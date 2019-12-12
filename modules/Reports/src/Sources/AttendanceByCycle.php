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

namespace Gibbon\Module\Reports\Sources;

use Gibbon\Module\Reports\DataSource;

class AttendanceByCycle extends DataSource
{
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
        $data = [
            'gibbonStudentEnrolmentID' => $ids['gibbonStudentEnrolmentID'],
            'gibbonReportID'       => $ids['gibbonReportID']
        ];
        $sql = "SELECT gibbonReportingCycle.cycleNumber , gibbonReport.gibbonReportID, gibbonAttendanceLogPerson.gibbonCourseClassID, gibbonAttendanceLogPerson.date, gibbonAttendanceLogPerson.timestampTaken, gibbonAttendanceLogPerson.type, gibbonAttendanceLogPerson.context
                FROM gibbonReport
                JOIN gibbonReportingCycle ON (gibbonReportingCycle.gibbonReportingCycleID=gibbonReport.gibbonReportingCycleID)
                JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonSchoolYearID=gibbonReport.gibbonSchoolYearID)
                JOIN gibbonSchoolYear ON (gibbonSchoolYear.gibbonSchoolYearID=gibbonStudentEnrolment.gibbonSchoolYearID)
                JOIN gibbonAttendanceLogPerson ON (gibbonAttendanceLogPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID AND gibbonAttendanceLogPerson.date BETWEEN gibbonReportingCycle.dateStart AND gibbonReportingCycle.dateEnd)
                WHERE gibbonReport.gibbonReportID=:gibbonReportID
                AND gibbonStudentEnrolment.gibbonStudentEnrolmentID=:gibbonStudentEnrolmentID
                AND FIND_IN_SET(gibbonStudentEnrolment.gibbonYearGroupID, gibbonReport.gibbonYearGroupIDList)
                AND gibbonAttendanceLogPerson.date>=gibbonSchoolYear.firstDay
                AND gibbonAttendanceLogPerson.date<=CURDATE()
                GROUP BY gibbonAttendanceLogPerson.gibbonAttendanceLogPersonID
                ORDER BY gibbonAttendanceLogPerson.date, gibbonAttendanceLogPerson.timestampTaken";

        $result = $this->db()->executeQuery($data, $sql);

        $values = ($result->rowCount() > 0)? $result->fetchAll(\PDO::FETCH_GROUP) : array();

        $termAttendance = array();

        foreach ($values as $reportNum => $attendanceLogs) {
            // Group by date
            $attendance = array_reduce($attendanceLogs, function($carry, $log) {
                $carry[$log['date']][] = $log;
                return $carry;
            }, array());

            $attendance = array_map(function($logs) {
                $endOfDay = end($logs);

                // Group by class
                $endOfClasses = array_reduce($logs, function($carry, $log) {
                    if ($log['context'] == 'Class') {
                        $carry[$log['gibbonCourseClassID']][] = $log;
                    }
                    return $carry;
                }, array());

                // Filter to end of class only
                $endOfClasses = array_map(function($logs) { 
                    return end($logs); 
                }, $endOfClasses);

                // Grab the the absent (by date) and late count (by classes)
                $absent = ($endOfDay['type'] == 'Absent - Excused' || $endOfDay['type'] == 'Absent - Unexcused')? 1 : 0;
                
                if (!empty($endOfClasses)) {
                    $lates = count(array_filter($endOfClasses, function($log) {
                        return ($log['type'] == 'Present - Late');
                    }));
                } else {
                    $lates = ($endOfDay['type'] == 'Present - Late')? 1 : 0;
                }

                return array('absent' => $absent, 'late' => $lates);
            }, $attendance);

            // Sum up the absences for the term
            $termAttendance[$reportNum] = array_reduce($attendance, function($carry, $item) {
                $carry['absent'] += $item['absent'];
                $carry['late'] += $item['late'];
                return $carry;
            }, array('absent' => 0, 'late' => 0));
        }
        
        return $termAttendance;
    }
}
