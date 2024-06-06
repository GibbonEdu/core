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
            ->joinSubSelect('LEFT', $subSelect, 'timetable', '(timetable.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID AND timetable.date=:date AND (gibbonAttendanceLogPerson.gibbonTTDayRowClassID IS NULL OR gibbonAttendanceLogPerson.gibbonTTDayRowClassID=timetable.gibbonTTDayRowClassID))')
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

    public function selectAllAttendanceLogsByPerson($gibbonSchoolYearID, $gibbonPersonID)
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

        return $this->runSelect($query);
    }

    public function queryAttendanceCountsByType($criteria, $gibbonSchoolYearID, $formGroups, $dateStart, $dateEnd, $countClassAsSchool)
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

        if ($formGroups != array('all')) {
            $query
                ->innerJoin('gibbonStudentEnrolment', 'gibbonAttendanceLogPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID')
                ->where('FIND_IN_SET(gibbonStudentEnrolment.gibbonFormGroupID, :formGroups)')
                ->bindValue('formGroups', implode(',', $formGroups))
                ->where('gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID')
                ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID);
        }

        return $this->runQuery($query, $criteria);
    }

    public function queryStudentsNotPresent(QueryCriteria $criteria, $gibbonSchoolYearID, $date, $allStudents = null, $countClassAsSchool = null)
    {
        $subSelect = $this
            ->newSelect()
            ->from('gibbonAttendanceLogPerson')
            ->cols(['gibbonPersonID', 'date', 'MAX(timestampTaken) as maxTimestamp', 'context', 'MAX(gibbonAttendanceLogPersonID) as gibbonAttendanceLogPersonID'])
            ->where("date=:date")
            ->groupBy(['gibbonPersonID', 'date']);

        if ($countClassAsSchool == 'N') {
            $subSelect->where("context<>'Class'");
        }

        $query = $this
            ->newQuery()
            ->cols([
                'gibbonPerson.gibbonPersonID',
                'gibbonPerson.title',
                'gibbonPerson.preferredName',
                'gibbonPerson.surname',
                'gibbonFormGroup.name as formGroupName',
                'gibbonFormGroup.nameShort as formGroup',
                'gibbonAttendanceLogPerson.type',
                'gibbonAttendanceLogPerson.reason',
                'gibbonAttendanceLogPerson.comment',
            ])
            ->from('gibbonPerson')
            ->innerJoin('gibbonStudentEnrolment', 'gibbonPerson.gibbonPersonID = gibbonStudentEnrolment.gibbonPersonID')
            ->innerJoin('gibbonFormGroup', 'gibbonStudentEnrolment.gibbonFormGroupID = gibbonFormGroup.gibbonFormGroupID')
            ->leftJoin('gibbonAttendanceLogPerson', 'gibbonAttendanceLogPerson.gibbonPersonID = gibbonPerson.gibbonPersonID AND gibbonAttendanceLogPerson.date = :date' .( $countClassAsSchool == 'N' ? " AND gibbonAttendanceLogPerson.context<>'Class'" : ""))
            ->joinSubSelect(
                'LEFT',
                $subSelect,
                'log',
                'gibbonAttendanceLogPerson.gibbonPersonID=log.gibbonPersonID AND gibbonAttendanceLogPerson.date=log.date'
            )
            ->where("gibbonPerson.status = 'Full'")
            ->where('(gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart <= CURRENT_TIMESTAMP)')
            ->where('(gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd >= CURRENT_TIMESTAMP)')
            ->where('gibbonStudentEnrolment.gibbonSchoolYearID = :gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->bindValue('date', $date);

        if ($allStudents == 'Y') {
            $query->where("(gibbonAttendanceLogPerson.gibbonAttendanceLogPersonID IS NULL OR (gibbonAttendanceLogPerson.direction = 'Out' AND gibbonAttendanceLogPerson.timestampTaken=log.maxTimestamp AND gibbonAttendanceLogPerson.gibbonAttendanceLogPersonID>=log.gibbonAttendanceLogPersonID))");
        } else {
            $query->where("(gibbonAttendanceLogPerson.direction = 'Out' AND gibbonAttendanceLogPerson.timestampTaken=log.maxTimestamp AND gibbonAttendanceLogPerson.gibbonAttendanceLogPersonID>=log.gibbonAttendanceLogPersonID)");
        }

        $criteria->addFilterRules([
            'yearGroup' => function ($query, $gibbonYearGroupIDList) {
                if (empty($gibbonYearGroupIDList)) return $query;
                return $query
                    ->where('FIND_IN_SET(gibbonStudentEnrolment.gibbonYearGroupID, :gibbonYearGroupIDList)')
                    ->bindValue('gibbonYearGroupIDList', $gibbonYearGroupIDList);
            },
        ]);

        return $this->runQuery($query, $criteria);
    }

    public function queryStudentsNotOnsite(QueryCriteria $criteria, $gibbonSchoolYearID, $date, $allStudents = null, $countClassAsSchool = null)
    {
        $subSelect = $this
            ->newSelect()
            ->from('gibbonAttendanceLogPerson')
            ->cols(['gibbonPersonID', 'date', 'MAX(timestampTaken) as maxTimestamp', 'context', 'MAX(gibbonAttendanceLogPersonID) as gibbonAttendanceLogPersonID'])
            ->where("date=:date")
            ->groupBy(['gibbonPersonID', 'date']);

        if ($countClassAsSchool == 'N') {
            $subSelect->where("context<>'Class'");
        }

        $query = $this
            ->newQuery()
            ->cols([
                'gibbonPerson.gibbonPersonID',
                'gibbonPerson.title',
                'gibbonPerson.preferredName',
                'gibbonPerson.surname',
                'gibbonFormGroup.name as formGroupName',
                'gibbonFormGroup.nameShort as formGroup',
                'gibbonAttendanceLogPerson.type',
                'gibbonAttendanceLogPerson.reason',
                'gibbonAttendanceLogPerson.comment',
            ])
            ->from('gibbonPerson')
            ->innerJoin('gibbonStudentEnrolment', 'gibbonPerson.gibbonPersonID = gibbonStudentEnrolment.gibbonPersonID')
            ->innerJoin('gibbonFormGroup', 'gibbonStudentEnrolment.gibbonFormGroupID = gibbonFormGroup.gibbonFormGroupID')
            ->leftJoin('gibbonAttendanceLogPerson', 'gibbonAttendanceLogPerson.gibbonPersonID = gibbonPerson.gibbonPersonID AND gibbonAttendanceLogPerson.date = :date '.( $countClassAsSchool == 'N' ? " AND gibbonAttendanceLogPerson.context<>'Class'" : "") )
            ->leftJoin('gibbonAttendanceCode', 'gibbonAttendanceCode.gibbonAttendanceCodeID=gibbonAttendanceLogPerson.gibbonAttendanceCodeID')
            ->joinSubSelect(
                'LEFT',
                $subSelect,
                'log',
                'gibbonAttendanceLogPerson.gibbonPersonID=log.gibbonPersonID AND gibbonAttendanceLogPerson.date=log.date'
            )
            ->where("gibbonPerson.status = 'Full'")
            ->where('(gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart <= CURRENT_TIMESTAMP)')
            ->where('(gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd >= CURRENT_TIMESTAMP)')
            ->where('gibbonStudentEnrolment.gibbonSchoolYearID = :gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->bindValue('date', $date);

        if ($allStudents == 'Y') {
            $query->where("(gibbonAttendanceLogPerson.gibbonAttendanceLogPersonID IS NULL OR (gibbonAttendanceCode.scope LIKE 'Offsite%' AND gibbonAttendanceLogPerson.timestampTaken=log.maxTimestamp AND gibbonAttendanceLogPerson.gibbonAttendanceLogPersonID>=log.gibbonAttendanceLogPersonID))");
        } else {
            $query->where("(gibbonAttendanceCode.scope LIKE 'Offsite%' AND gibbonAttendanceLogPerson.timestampTaken=log.maxTimestamp AND gibbonAttendanceLogPerson.gibbonAttendanceLogPersonID>=log.gibbonAttendanceLogPersonID)");
        }

        $criteria->addFilterRules([
            'yearGroup' => function ($query, $gibbonYearGroupIDList) {
                if (empty($gibbonYearGroupIDList)) return $query;
                return $query
                    ->where('FIND_IN_SET(gibbonStudentEnrolment.gibbonYearGroupID, :gibbonYearGroupIDList)')
                    ->bindValue('gibbonYearGroupIDList', $gibbonYearGroupIDList);
            },
        ]);

        return $this->runQuery($query, $criteria);
    }

    public function queryStudentsNotInClass($criteria, $gibbonSchoolYearID, $date, $allStudents = null)
    {
        $query = $this
            ->newQuery()
            ->from('gibbonAttendanceLogPerson')
            ->cols([
                'gibbonAttendanceLogPersonID', 'gibbonAttendanceLogPerson.type', 'gibbonAttendanceLogPerson.reason', 'gibbonAttendanceLogPerson.comment', 'gibbonAttendanceLogPerson.date','gibbonAttendanceLogPerson.gibbonPersonID', 'gibbonPerson.surname', 'gibbonPerson.preferredName', 'gibbonPerson.username', 'gibbonPerson.status', 'gibbonFormGroup.nameShort as formGroup', 'gibbonYearGroup.nameShort as yearGroup', 'gibbonCourse.nameShort as courseName', 'gibbonCourseClass.nameShort as className'
            ])
            ->innerJoin('gibbonPerson', 'gibbonAttendanceLogPerson.gibbonPersonID=gibbonPerson.gibbonPersonID')
            ->innerJoin('gibbonStudentEnrolment', 'gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID')
            ->innerJoin('gibbonFormGroup', 'gibbonFormGroup.gibbonFormGroupID=gibbonStudentEnrolment.gibbonFormGroupID')
            ->innerJoin('gibbonYearGroup', 'gibbonYearGroup.gibbonYearGroupID=gibbonStudentEnrolment.gibbonYearGroupID')
            ->leftJoin('gibbonCourseClass', 'gibbonCourseClass.gibbonCourseClassID=gibbonAttendanceLogPerson.gibbonCourseClassID')
            ->leftJoin('gibbonCourse', 'gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID')
            ->where("gibbonAttendanceLogPerson.context='Class'")
            ->where("gibbonAttendanceLogPerson.type<>'Present'")
            ->where('gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->where('gibbonAttendanceLogPerson.date=:date')
            ->bindValue('date', $date)
            ->where("gibbonPerson.status='Full'")
            ->where('(gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart<=:today)')
            ->where('(gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd>=:today)')
            ->bindValue('today', date('Y-m-d'));

        if ($allStudents != 'Y') {
            $query->cols(["(SELECT type FROM gibbonAttendanceLogPerson as schoolAttendance WHERE schoolAttendance.gibbonPersonID=gibbonPerson.gibbonPersonID AND schoolAttendance.date=gibbonAttendanceLogPerson.date AND schoolAttendance.context<>'Class' ORDER BY schoolAttendance.timestampTaken DESC LIMIT 1) as schoolAttendanceType"])
                  ->having("schoolAttendanceType NOT LIKE '%Absent%'");
        }

        $criteria->addFilterRules([
            'yearGroup' => function ($query, $gibbonYearGroupIDList) {
                return $query->where('FIND_IN_SET(gibbonYearGroup.gibbonYearGroupID, :gibbonYearGroupIDList)')
                             ->bindValue('gibbonYearGroupIDList', $gibbonYearGroupIDList);
            },
            'types' => function ($query, $types) {
                return $query->where('FIND_IN_SET(gibbonAttendanceLogPerson.gibbonAttendanceCodeID, :types)')
                             ->bindValue('types', $types);
            },
        ]);

        return $this->runQuery($query, $criteria);
    }

    function selectClassAttendanceLogsByPersonAndDate($gibbonCourseClassID, $gibbonPersonID, $date)
    {
        $data = ['gibbonPersonID' => $gibbonPersonID, 'date' => $date, 'gibbonCourseClassID' => $gibbonCourseClassID];
        $sql = "SELECT gibbonAttendanceLogPerson.type, reason, comment, direction, context, timestampTaken, gibbonAttendanceLogPerson.gibbonTTDayRowClassID FROM gibbonAttendanceLogPerson
                JOIN gibbonPerson ON (gibbonAttendanceLogPerson.gibbonPersonID=gibbonPerson.gibbonPersonID)
                WHERE gibbonAttendanceLogPerson.gibbonPersonID=:gibbonPersonID
                AND date=:date
                AND context='Class' AND gibbonCourseClassID=:gibbonCourseClassID
                ORDER BY timestampTaken DESC";

        return $this->db()->select($sql, $data);
    }

    function selectNonClassAttendanceLogsByPersonAndDate($gibbonPersonID, $date)
    {
        $data = ['gibbonPersonID' => $gibbonPersonID, 'date' => $date];
        $sql = "SELECT gibbonAttendanceLogPerson.type, reason, comment, direction, context, timestampTaken FROM gibbonAttendanceLogPerson
                WHERE gibbonAttendanceLogPerson.gibbonPersonID=:gibbonPersonID
                AND date=:date
                AND context <> 'Class'
                ORDER BY timestampTaken DESC";

        return $this->db()->select($sql, $data);
    }

    public function selectFutureAttendanceLogsByPersonAndDate($gibbonPersonID, $date)
    {
        $data = array('gibbonPersonID' => $gibbonPersonID, 'date' => $date);
        $sql = "SELECT gibbonAttendanceLogPersonID, date, direction, type, context, reason, comment, timestampTaken, gibbonAttendanceLogPerson.gibbonCourseClassID, preferredName, surname, gibbonCourseClass.nameShort as className, gibbonCourse.nameShort as courseName, gibbonAttendanceLogPerson.gibbonTTDayRowClassID
            FROM gibbonAttendanceLogPerson 
            JOIN gibbonPerson ON (gibbonAttendanceLogPerson.gibbonPersonIDTaker=gibbonPerson.gibbonPersonID) 
            LEFT JOIN gibbonCourseClass ON (gibbonAttendanceLogPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) 
            LEFT JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) 
            WHERE gibbonAttendanceLogPerson.gibbonPersonIDTaker=gibbonPerson.gibbonPersonID 
            AND gibbonAttendanceLogPerson.gibbonPersonID=:gibbonPersonID AND date>=:date 
            ORDER BY date";

        return $this->db()->select($sql, $data);
    }

    function selectAttendanceLogsByPersonAndDate($gibbonPersonID, $date, $crossFillClasses)
    {
        $data = ['gibbonPersonID' => $gibbonPersonID, 'date' => $date];
        $sql = "SELECT gibbonAttendanceLogPerson.type, reason, comment, gibbonAttendanceLogPerson.direction, context, timestampTaken, gibbonAttendanceCode.prefill, gibbonAttendanceCode.scope, gibbonAttendanceLogPerson.gibbonTTDayRowClassID
                FROM gibbonAttendanceLogPerson
                JOIN gibbonPerson ON (gibbonAttendanceLogPerson.gibbonPersonID=gibbonPerson.gibbonPersonID)
                JOIN gibbonAttendanceCode ON (gibbonAttendanceCode.gibbonAttendanceCodeID=gibbonAttendanceLogPerson.gibbonAttendanceCodeID)
                WHERE gibbonAttendanceLogPerson.gibbonPersonID=:gibbonPersonID
                AND date=:date";
        if ($crossFillClasses == "N") {
            $sql .= " AND NOT context='Class'";
        }
        $sql .= " ORDER BY timestampTaken DESC";

        return $this->db()->select($sql, $data);
    }

    public function selectAdHocAttendanceStudents($gibbonSchoolYearID, $target, $targetID, $currentDate)
    {
        switch ($target) {
            case 'Activity':
                $data = ['gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonActivityID' => $targetID, 'date' => $currentDate];
                $sql = "SELECT gibbonPerson.image_240, gibbonPerson.dob, gibbonPerson.preferredName, gibbonPerson.surname, gibbonPerson.gibbonPersonID, gibbonFormGroup.nameShort AS formGroup
                        FROM gibbonStudentEnrolment 
                        JOIN gibbonPerson ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID)
                        JOIN gibbonFormGroup ON (gibbonFormGroup.gibbonFormGroupID=gibbonStudentEnrolment.gibbonFormGroupID)
                        JOIN gibbonActivityStudent ON (gibbonActivityStudent.gibbonPersonID=gibbonPerson.gibbonPersonID)
                        WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID
                        AND gibbonActivityStudent.gibbonActivityID=:gibbonActivityID
                        AND gibbonActivityStudent.status='Accepted'
                        AND gibbonPerson.status='Full' 
                        AND (gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart<=:date) 
                        AND (gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd>=:date) 
                        ORDER BY gibbonStudentEnrolment.rollOrder, gibbonPerson.surname, gibbonPerson.preferredName";
                    break;
            case 'Messenger':
                $data = ['gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonGroupID' => $targetID, 'date' => $currentDate];
                $sql = "SELECT gibbonPerson.image_240, gibbonPerson.dob, gibbonPerson.preferredName, gibbonPerson.surname, gibbonPerson.gibbonPersonID, gibbonFormGroup.nameShort AS formGroup 
                        FROM gibbonStudentEnrolment 
                        JOIN gibbonPerson ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID)
                        JOIN gibbonFormGroup ON (gibbonFormGroup.gibbonFormGroupID=gibbonStudentEnrolment.gibbonFormGroupID)
                        JOIN gibbonGroupPerson ON (gibbonGroupPerson.gibbonPersonID=gibbonPerson.gibbonPersonID)
                        WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID
                        AND gibbonGroupPerson.gibbonGroupID=:gibbonGroupID
                        AND gibbonPerson.status='Full' 
                        AND (gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart<=:date) 
                        AND (gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd>=:date) 
                        ORDER BY gibbonStudentEnrolment.rollOrder, gibbonPerson.surname, gibbonPerson.preferredName";
                    break;
            case 'Select':
                $data = ['gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonPersonIDList' => implode(',', $targetID), 'date' => $currentDate];
                $sql = "SELECT gibbonPerson.image_240, gibbonPerson.dob, gibbonPerson.preferredName, gibbonPerson.surname, gibbonPerson.gibbonPersonID, gibbonFormGroup.nameShort AS formGroup 
                        FROM gibbonStudentEnrolment 
                        JOIN gibbonPerson ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) 
                        JOIN gibbonFormGroup ON (gibbonFormGroup.gibbonFormGroupID=gibbonStudentEnrolment.gibbonFormGroupID)
                        WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID
                        AND FIND_IN_SET(gibbonPerson.gibbonPersonID, :gibbonPersonIDList)
                        AND gibbonPerson.status='Full' 
                        AND (gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart<=:date) 
                        AND (gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd>=:date) 
                        ORDER BY gibbonStudentEnrolment.rollOrder, gibbonPerson.surname, gibbonPerson.preferredName";
                break;
        }

        return $this->db()->select($sql, $data);
    }
}
