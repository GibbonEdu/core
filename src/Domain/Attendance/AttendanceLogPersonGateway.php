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

namespace Gibbon\Domain\Attendance;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * @version v18
 * @since   v18
 */
class AttendanceLogPersonGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonAttendanceLogPerson';
    private static $primaryKey = 'gibbonAttendanceLogPersonID';

    private static $searchableColumns = [''];
    
    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryByPersonAndDate(QueryCriteria $criteria, $gibbonPersonID, $date)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonAttendanceLogPersonID', 'gibbonAttendanceLogPerson.direction', 'gibbonAttendanceLogPerson.type', 'gibbonAttendanceLogPerson.reason', 'gibbonAttendanceLogPerson.context', 'gibbonAttendanceLogPerson.comment', 'gibbonAttendanceLogPerson.timestampTaken', 'gibbonAttendanceLogPerson.gibbonCourseClassID', 'takenBy.title', 'takenBy.preferredName', 'takenBy.surname', 'gibbonCourseClass.nameShort as className', 'gibbonCourse.nameShort as courseName',
            ])
            ->innerJoin('gibbonPerson as takenBy', 'gibbonAttendanceLogPerson.gibbonPersonIDTaker=takenBy.gibbonPersonID')
            ->leftJoin('gibbonCourseClass', 'gibbonAttendanceLogPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID')
            ->leftJoin('gibbonCourse', 'gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID')
            ->where('gibbonAttendanceLogPerson.gibbonPersonID=:gibbonPersonID')
            ->bindValue('gibbonPersonID', $gibbonPersonID)
            ->where('gibbonAttendanceLogPerson.date=:date')
            ->bindValue('date', $date);

        $criteria->addFilterRules([
            'notClass' => function ($query, $context) {
                return $query->where('NOT gibbonAttendanceLogPerson.context="Class"');
            },
        ]);

        return $this->runQuery($query, $criteria);
    }

    public function queryClassAttendanceByPersonAndDate(QueryCriteria $criteria, $gibbonSchoolYearID, $gibbonPersonID, $date)
    {
        $subSelect = $this
            ->newSelect()
            ->from('gibbonTTDayRowClass')
            ->cols(['gibbonTTColumnRow.name as period', 'gibbonTTColumnRow.timeStart', 'gibbonTTColumnRow.timeEnd', 'gibbonTTDayDate.date', 'gibbonTTDayRowClass.gibbonCourseClassID', 'gibbonTTDayRowClass.gibbonTTDayRowClassID'])
            ->innerJoin('gibbonTTColumnRow', 'gibbonTTColumnRow.gibbonTTColumnRowID=gibbonTTDayRowClass.gibbonTTColumnRowID')
            ->innerJoin('gibbonTTDay', 'gibbonTTDay.gibbonTTDayID=gibbonTTDayRowClass.gibbonTTDayID AND gibbonTTDay.gibbonTTColumnID=gibbonTTColumnRow.gibbonTTColumnID')
            ->innerJoin('gibbonTTDayDate', 'gibbonTTDayDate.gibbonTTDayID=gibbonTTDay.gibbonTTDayID')
            ->where('gibbonTTDayDate.date=:date')
            ->bindValue('date', $date);

        $query = $this
            ->newQuery()
            ->from('gibbonCourseClassPerson')
            ->cols([
                'gibbonAttendanceLogPersonID', 'gibbonAttendanceLogPerson.direction', 'gibbonAttendanceLogPerson.type', 'gibbonAttendanceLogPerson.reason',  "'Class' as context", 'gibbonAttendanceLogPerson.comment', 'gibbonAttendanceLogPerson.timestampTaken', 'takenBy.title', 'takenBy.preferredName', 'takenBy.surname',
                'gibbonCourseClass.gibbonCourseClassID', 'gibbonCourseClass.nameShort as className', 'gibbonCourse.nameShort as courseName',
                'timetable.period', '(CASE WHEN timetable.timeStart IS NOT NULL THEN timetable.timeStart ELSE gibbonAttendanceLogPerson.timestampTaken END) as timeStart', '(CASE WHEN timetable.timeEnd IS NOT NULL THEN timetable.timeEnd ELSE gibbonAttendanceLogPerson.timestampTaken END) as timeEnd',
            ])
            ->innerJoin('gibbonCourseClass', 'gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID')
            ->innerJoin('gibbonCourse', 'gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID')
            ->leftJoin('gibbonAttendanceLogPerson', "gibbonAttendanceLogPerson.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID 
                AND gibbonAttendanceLogPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID 
                AND gibbonAttendanceLogPerson.date=:date
                AND gibbonAttendanceLogPerson.context = 'Class'")
            ->leftJoin('gibbonPerson as takenBy', 'gibbonAttendanceLogPerson.gibbonPersonIDTaker=takenBy.gibbonPersonID')
            ->joinSubSelect('LEFT', $subSelect, 'timetable', '(timetable.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID AND timetable.date=:date)')
            ->where("gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID")
            ->bindValue('gibbonPersonID', $gibbonPersonID)
            ->where("gibbonCourseClassPerson.role = 'Student'")
            ->where("gibbonCourseClass.attendance='Y'")
            ->where('gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->where('NOT (gibbonAttendanceLogPerson.gibbonAttendanceLogPersonID IS NULL AND timetable.gibbonCourseClassID IS NULL)')
            ->bindValue('date', $date)
            ->groupBy(['gibbonAttendanceLogPerson.gibbonAttendanceLogPersonID', 'timetable.gibbonTTDayRowClassID']);
        
        return $this->runQuery($query, $criteria);
    }
}
