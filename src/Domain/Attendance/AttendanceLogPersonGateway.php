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
                'gibbonAttendanceLogPersonID', 'gibbonAttendanceLogPerson.direction', 'gibbonAttendanceLogPerson.type', 'gibbonAttendanceLogPerson.reason', 'gibbonAttendanceLogPerson.context', 'gibbonAttendanceLogPerson.comment', 'gibbonAttendanceLogPerson.timestampTaken', 'gibbonAttendanceLogPerson.gibbonCourseClassID', 'takenBy.title', 'takenBy.preferredName', 'takenBy.surname', 'gibbonCourseClass.nameShort as className', 'gibbonCourse.nameShort as courseName', 'gibbonAttendanceCode.scope'
            ])
            ->innerJoin('gibbonPerson as takenBy', 'gibbonAttendanceLogPerson.gibbonPersonIDTaker=takenBy.gibbonPersonID')
            ->leftJoin('gibbonAttendanceCode', 'gibbonAttendanceCode.gibbonAttendanceCodeID=gibbonAttendanceLogPerson.gibbonAttendanceCodeID')
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
                'timetable.period', '(CASE WHEN timetable.timeStart IS NOT NULL THEN timetable.timeStart ELSE gibbonAttendanceLogPerson.timestampTaken END) as timeStart', '(CASE WHEN timetable.timeEnd IS NOT NULL THEN timetable.timeEnd ELSE gibbonAttendanceLogPerson.timestampTaken END) as timeEnd', 'gibbonAttendanceCode.scope'
            ])
            ->innerJoin('gibbonCourseClass', 'gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID')
            ->innerJoin('gibbonCourse', 'gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID')
            ->leftJoin('gibbonAttendanceLogPerson', "gibbonAttendanceLogPerson.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID
                AND gibbonAttendanceLogPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID
                AND gibbonAttendanceLogPerson.date=:date
                AND gibbonAttendanceLogPerson.context = 'Class'")
            ->leftJoin('gibbonAttendanceCode', 'gibbonAttendanceCode.gibbonAttendanceCodeID=gibbonAttendanceLogPerson.gibbonAttendanceCodeID')
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

    public function selectAllAttendanceLogsByPerson($gibbonSchoolYearID, $gibbonPersonID, $countClassAsSchool)
    {
        $query = $this
            ->newSelect()
            ->from('gibbonSchoolYear')
            ->cols([
                'gibbonAttendanceLogPerson.date as groupBy','gibbonAttendanceLogPerson.date', 'gibbonAttendanceLogPerson.type', 'gibbonAttendanceLogPerson.reason', 'gibbonAttendanceLogPerson.timestampTaken', 'gibbonAttendanceCode.nameShort as code', 'gibbonAttendanceCode.direction', 'gibbonAttendanceCode.scope', 'gibbonAttendanceLogPerson.context', "(CASE WHEN gibbonCourse.gibbonCourseID IS NOT NULL THEN CONCAT(gibbonCourse.nameShort, '.', gibbonCourseClass.nameShort) END) as contextName",
            ])
            ->innerJoin('gibbonAttendanceLogPerson', 'gibbonAttendanceLogPerson.date >= firstDay AND gibbonAttendanceLogPerson.date <= lastDay')
            ->innerJoin('gibbonAttendanceCode', 'gibbonAttendanceLogPerson.type=gibbonAttendanceCode.name')
            ->leftJoin('gibbonCourseClass', "gibbonCourseClass.gibbonCourseClassID=gibbonAttendanceLogPerson.gibbonCourseClassID AND gibbonAttendanceLogPerson.context='Class'")
            ->leftJoin('gibbonCourse', 'gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID')
            ->where('gibbonSchoolYear.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->where('gibbonAttendanceLogPerson.gibbonPersonID=:gibbonPersonID')
            ->bindValue('gibbonPersonID', $gibbonPersonID)
            ->orderBy(['timestampTaken ASC']);

        if ($countClassAsSchool == 'N') {
            $query->where("NOT gibbonAttendanceLogPerson.context='Class'");
        }

        return $this->runSelect($query);
    }

    public function queryAttendanceCountsByType($criteria, $gibbonSchoolYearID, $rollGroups, $dateStart, $dateEnd, $countClassAsSchool)
    {
        $subSelect = $this
            ->newSelect()
            ->from('gibbonAttendanceLogPerson')
            ->cols(['gibbonPersonID', 'date', 'MAX(timestampTaken) as maxTimestamp', 'context'])
            ->where("date>=:dateStart AND date<=:dateEnd")
            ->groupBy(['gibbonPersonID', 'date']);

        if ($countClassAsSchool == 'N') {
            $subSelect->where("context <> 'Class'");
        }

        $query = $this
            ->newQuery()
            ->from('gibbonAttendanceLogPerson')
            ->cols([
                'gibbonAttendanceCode.name', 'gibbonAttendanceLogPerson.reason', 'count(DISTINCT gibbonAttendanceLogPerson.gibbonPersonID) as count', 'gibbonAttendanceLogPerson.date'
            ])
            ->innerJoin('gibbonAttendanceCode', 'gibbonAttendanceLogPerson.type=gibbonAttendanceCode.name')
            ->joinSubSelect(
                'INNER',
                $subSelect,
                'log',
                'gibbonAttendanceLogPerson.gibbonPersonID=log.gibbonPersonID AND gibbonAttendanceLogPerson.date=log.date'
            )
            ->where('gibbonAttendanceLogPerson.timestampTaken=log.maxTimestamp')
            ->where('gibbonAttendanceLogPerson.date>=:dateStart')
            ->bindValue('dateStart', $dateStart)
            ->where('gibbonAttendanceLogPerson.date<=:dateEnd')
            ->bindValue('dateEnd', $dateEnd)
            ->groupBy(['gibbonAttendanceLogPerson.date', 'gibbonAttendanceCode.name', 'gibbonAttendanceLogPerson.reason'])
            ->orderBy(['gibbonAttendanceLogPerson.date', 'gibbonAttendanceCode.direction DESC', 'gibbonAttendanceCode.name']);

        if ($countClassAsSchool == 'N') {
            $query->where("gibbonAttendanceLogPerson.context <> 'Class'");
        }

        if ($rollGroups != array('all')) {
            $query
                ->innerJoin('gibbonStudentEnrolment', 'gibbonAttendanceLogPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID')
                ->where('FIND_IN_SET(gibbonStudentEnrolment.gibbonRollGroupID, :rollGroups)')
                ->bindValue('rollGroups', implode(',', $rollGroups))
                ->where('gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID')
                ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID);
        }

        return $this->runQuery($query, $criteria);
    }
}
