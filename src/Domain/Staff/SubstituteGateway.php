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

namespace Gibbon\Domain\Staff;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * Substitute Gateway
 *
 * @version v18
 * @since   v18
 */
class SubstituteGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonSubstitute';
    private static $primaryKey = 'gibbonSubstituteID';

    private static $searchableColumns = ['preferredName', 'surname', 'username'];
    
    /**
     * Queries the list of users for the Manage Substitutes page.
     *
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryAllSubstitutes(QueryCriteria $criteria)
    {
        $query = $this
            ->newQuery()
            ->from('gibbonPerson')
            ->cols([
                'gibbonSubstitute.gibbonSubstituteID', 'gibbonSubstitute.type', 'gibbonSubstitute.details', 'gibbonSubstitute.priority', 'gibbonSubstitute.active',
                'gibbonPerson.gibbonPersonID', 'gibbonPerson.title', 'gibbonPerson.surname', 'gibbonPerson.preferredName', 'gibbonPerson.status', 'gibbonPerson.image_240', 'gibbonPerson.username',
                'gibbonStaff.gibbonStaffID', 'gibbonStaff.type as staffType', 'gibbonStaff.jobTitle'
                
            ])
            ->innerJoin('gibbonRole', 'gibbonRole.gibbonRoleID=gibbonPerson.gibbonRoleIDPrimary')
            ->leftJoin('gibbonStaff', 'gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID');

        if ($criteria->hasFilter('allStaff', 'Y')) {
            $query->leftJoin('gibbonSubstitute', 'gibbonPerson.gibbonPersonID=gibbonSubstitute.gibbonPersonID')
                ->where("gibbonRole.category='Staff' AND gibbonStaff.type='Teaching'");
                
            $criteria->addFilterRules([
                'active' => function ($query, $active) {
                    if ($active != 'Y') return $query;
                    return $query->where("gibbonPerson.status='Full'");
                },
            ]);
            
        } else {
            $query->innerJoin('gibbonSubstitute', 'gibbonPerson.gibbonPersonID=gibbonSubstitute.gibbonPersonID');

            $criteria->addFilterRules([
                'active' => function ($query, $active) {
                    return $query
                        ->where('gibbonSubstitute.active = :active')
                        ->bindValue('active', $active);
                },
            ]);
        }

        $criteria->addFilterRules([
            'status' => function ($query, $status) {
                return $query
                    ->where('gibbonPerson.status = :status')
                    ->bindValue('status', ucfirst($status));
            },
        ]);

        return $this->runQuery($query, $criteria);
    }

    public function queryUnavailableDatesBySub(QueryCriteria $criteria, $gibbonSchoolYearID, $gibbonPersonIDCoverage)
    {
        $query = $this
            ->newQuery()
            ->cols([
                'date as groupBy', 'gibbonStaffCoverageDate.*', 'gibbonStaffCoverageDate.gibbonPersonIDUnavailable as gibbonPersonID'
            ])
            ->from('gibbonStaffCoverageDate')
            ->innerJoin('gibbonSchoolYear', 'date BETWEEN gibbonSchoolYear.firstDay AND gibbonSchoolYear.lastDay')
            ->where('gibbonStaffCoverageDate.gibbonPersonIDUnavailable = :gibbonPersonIDCoverage')
            ->bindValue('gibbonPersonIDCoverage', $gibbonPersonIDCoverage)
            ->where('gibbonSchoolYear.gibbonSchoolYearID = :gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID);

        return $this->runQuery($query, $criteria);
    }

    public function queryAvailableSubsByDate($criteria, $date, $timeStart = null, $timeEnd = null)
    {
        $query = $this
            ->newQuery()
            ->from('gibbonPerson')
            ->cols([
                'gibbonPerson.gibbonPersonID as groupBy', ':date as date', 'gibbonPerson.gibbonPersonID', 'gibbonSubstitute.details', '(CASE WHEN gibbonSubstitute.gibbonSubstituteID IS NOT NULL THEN gibbonSubstitute.type ELSE "Internal Substitute" END) as type', 'gibbonSubstitute.priority', 'gibbonPerson.title', 'gibbonPerson.preferredName', 'gibbonPerson.surname', 'gibbonPerson.status', 'gibbonPerson.image_240', 'gibbonPerson.email', 'gibbonPerson.phone1', 'gibbonPerson.phone1Type', 'gibbonPerson.phone1CountryCode', 'gibbonStaff.gibbonStaffID', 'gibbonPerson.username', 'gibbonStaff.jobTitle',
                '(absence.ID IS NULL AND coverage.ID IS NULL AND timetable.ID IS NULL AND duty.ID IS NULL AND activity.ID IS NULL AND unavailable.gibbonStaffCoverageDateID IS NULL) as available',
                'absence.status as absence', 'coverage.status as coverage', 'timetable.status as timetable', 'unavailable.reason as unavailable',
            ])
            ->leftJoin('gibbonSubstitute', 'gibbonSubstitute.gibbonPersonID=gibbonPerson.gibbonPersonID');
                
        if ($criteria->hasFilter('allStaff')) {
            $query->innerJoin('gibbonStaff', 'gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID')
                  ->innerJoin('gibbonRole', 'gibbonRole.gibbonRoleID=gibbonPerson.gibbonRoleIDPrimary');
        } else {
            $query->leftJoin('gibbonStaff', 'gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID');
        }

        if (empty($timeStart) || empty($timeEnd)) {
            $times = $this->db()->selectOne("SELECT schoolStart, schoolEnd FROM gibbonDaysOfWeek WHERE name=:dayOfWeek", ['dayOfWeek' => date('l', strtotime($date))]);

            $timeStart = $times['schoolStart'];
            $timeEnd = $times['schoolEnd'];
        }

        $query->bindValue('timeStart', $timeStart)
                ->bindValue('timeEnd', $timeEnd);
        $query->cols([':timeStart as timeStart', ':timeEnd as timeEnd']);

        // Not available?
        $query->leftJoin('gibbonStaffCoverageDate as unavailable', "unavailable.gibbonPersonIDUnavailable=gibbonPerson.gibbonPersonID AND unavailable.date = :date 
                AND (unavailable.allDay='Y' OR (unavailable.allDay='N' AND unavailable.timeStart < :timeEnd AND unavailable.timeEnd > :timeStart))");

        // Already covering?
        $query->joinSubSelect(
            'LEFT',
            "SELECT gibbonStaffCoverageDateID as ID, (CASE WHEN absence.gibbonPersonID IS NOT NULL THEN CONCAT(absence.preferredName, ' ', absence.surname) ELSE CONCAT(status.preferredName, ' ', status.surname) END) as status, gibbonStaffCoverage.gibbonPersonIDCoverage, gibbonStaffCoverageDate.date, allDay, timeStart, timeEnd
                FROM gibbonStaffCoverage 
                JOIN gibbonStaffCoverageDate ON (gibbonStaffCoverageDate.gibbonStaffCoverageID=gibbonStaffCoverage.gibbonStaffCoverageID)
                LEFT JOIN gibbonStaffAbsence ON (gibbonStaffAbsence.gibbonStaffAbsenceID=gibbonStaffCoverage.gibbonStaffAbsenceID)
                LEFT JOIN gibbonPerson as absence ON (absence.gibbonPersonID=gibbonStaffAbsence.gibbonPersonID)
                LEFT JOIN gibbonPerson as status ON (status.gibbonPersonID=gibbonStaffCoverage.gibbonPersonID)
                WHERE gibbonStaffCoverage.status = 'Accepted'",
            'coverage',
            "coverage.gibbonPersonIDCoverage=gibbonPerson.gibbonPersonID AND coverage.date = :date 
                AND (coverage.allDay='Y' OR (coverage.allDay='N' AND coverage.timeStart < :timeEnd AND coverage.timeEnd > :timeStart))"
        );

        // Already absent?
        $query->joinSubSelect(
            'LEFT',
            "SELECT gibbonStaffAbsenceDateID as ID, gibbonStaffAbsenceType.name as status, gibbonStaffAbsence.gibbonPersonID, gibbonStaffAbsenceDate.date, allDay, timeStart, timeEnd
                FROM gibbonStaffAbsence 
                JOIN gibbonStaffAbsenceDate ON (gibbonStaffAbsenceDate.gibbonStaffAbsenceID=gibbonStaffAbsence.gibbonStaffAbsenceID)
                JOIN gibbonStaffAbsenceType ON (gibbonStaffAbsenceType.gibbonStaffAbsenceTypeID=gibbonStaffAbsence.gibbonStaffAbsenceTypeID) 
                WHERE gibbonStaffAbsence.status <> 'Declined'",
            'absence',
            "absence.gibbonPersonID=gibbonPerson.gibbonPersonID AND absence.date = :date 
                AND (absence.allDay='Y' OR (absence.allDay='N' AND absence.timeStart < :timeEnd AND absence.timeEnd > :timeStart))"
        );

        // Already teaching?
        $query->joinSubSelect(
            'LEFT',
            "SELECT gibbonTTColumnRow.gibbonTTColumnRowID as ID, CONCAT(gibbonCourse.nameShort, '.', gibbonCourseClass.nameShort) as status, gibbonCourseClassPerson.gibbonPersonID, gibbonTTDayDate.date, timeStart, timeEnd, specialDay.name as specialDay
                FROM gibbonCourseClassPerson 
                JOIN gibbonTTDayRowClass ON (gibbonTTDayRowClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID)
                JOIN gibbonTTDayDate ON (gibbonTTDayDate.gibbonTTDayID=gibbonTTDayRowClass.gibbonTTDayID)
                JOIN gibbonTTDay ON (gibbonTTDay.gibbonTTDayID=gibbonTTDayDate.gibbonTTDayID)
                JOIN gibbonTTColumnRow ON (gibbonTTColumnRow.gibbonTTColumnRowID=gibbonTTDayRowClass.gibbonTTColumnRowID 
                    AND gibbonTTDay.gibbonTTColumnID=gibbonTTColumnRow.gibbonTTColumnID)
                JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseClassID=gibbonTTDayRowClass.gibbonCourseClassID)
                JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID)
                JOIN gibbonCourseClassPerson as studentClass ON (studentClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID AND studentClass.role='Student')
                LEFT JOIN gibbonPerson as student ON (student.gibbonPersonID=studentClass.gibbonPersonID AND student.status='Full')
                LEFT JOIN gibbonStudentEnrolment as enrolment ON (enrolment.gibbonPersonID=student.gibbonPersonID AND enrolment.gibbonSchoolYearID=gibbonCourse.gibbonSchoolYearID)
                LEFT JOIN gibbonSchoolYearSpecialDay as specialDay ON (specialDay.date=gibbonTTDayDate.date AND specialDay.type='Off Timetable' AND (FIND_IN_SET(enrolment.gibbonYearGroupID, specialDay.gibbonYearGroupIDList) OR FIND_IN_SET(enrolment.gibbonFormGroupID, specialDay.gibbonFormGroupIDList)))
                WHERE gibbonCourseClassPerson.role = 'Teacher'",
            'timetable',
            "timetable.gibbonPersonID=gibbonPerson.gibbonPersonID AND timetable.date = :date 
                AND timetable.timeStart < :timeEnd AND timetable.timeEnd > :timeStart AND timetable.specialDay IS NULL"
        );

        // Already doing staff duty?
        $query->joinSubSelect(
            'LEFT',
            "SELECT gibbonStaffDutyPerson.gibbonStaffDutyPersonID as ID, gibbonStaffDuty.name as status, gibbonStaffDutyPerson.gibbonPersonID, :date as date, gibbonStaffDuty.timeStart, gibbonStaffDuty.timeEnd, gibbonDaysOfWeek.gibbonDaysOfWeekID
                FROM gibbonStaffDutyPerson 
                JOIN gibbonStaffDuty ON (gibbonStaffDutyPerson.gibbonStaffDutyID=gibbonStaffDuty.gibbonStaffDutyID)
                JOIN gibbonDaysOfWeek ON (gibbonStaffDutyPerson.gibbonDaysOfWeekID=gibbonDaysOfWeek.gibbonDaysOfWeekID)",
            'duty',
            "duty.gibbonPersonID=gibbonPerson.gibbonPersonID AND (duty.gibbonDaysOfWeekID-1) = WEEKDAY(:date) 
                AND duty.timeStart < :timeEnd AND duty.timeEnd > :timeStart"
        );

        // Already doing activity?
        $query->joinSubSelect(
            'LEFT',
            "SELECT gibbonActivityStaff.gibbonActivityStaffID as ID, gibbonActivity.name as status, gibbonActivityStaff.gibbonPersonID, :date as date, gibbonActivitySlot.timeStart, gibbonActivitySlot.timeEnd, gibbonDaysOfWeek.gibbonDaysOfWeekID
                FROM gibbonActivityStaff 
                JOIN gibbonActivity ON (gibbonActivityStaff.gibbonActivityID=gibbonActivity.gibbonActivityID)
                JOIN gibbonActivitySlot ON (gibbonActivitySlot.gibbonActivityID=gibbonActivity.gibbonActivityID)
                JOIN gibbonDaysOfWeek ON (gibbonActivitySlot.gibbonDaysOfWeekID=gibbonDaysOfWeek.gibbonDaysOfWeekID)",
            'activity',
            "activity.gibbonPersonID=gibbonPerson.gibbonPersonID AND (activity.gibbonDaysOfWeekID-1) = WEEKDAY(:date) 
                AND activity.timeStart < :timeEnd AND activity.timeEnd > :timeStart"
        );
        

        $query->where("gibbonPerson.status='Full'")
              ->where('(gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart<=:date)')
              ->where('(gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd>=:date)')
              ->bindValue('date', $date);

        if ($criteria->hasFilter('allStaff')) {
            $query->where("gibbonRole.category='Staff' AND gibbonStaff.type='Teaching'");
            // $query->where("(SELECT COUNT(*) FROM gibbonCourseClassPerson 
            //     INNER JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID)
            //     INNER JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID)
            //     INNER JOIN gibbonSchoolYear ON (gibbonSchoolYear.gibbonSchoolYearID=gibbonCourse.gibbonSchoolYearID)
            //     WHERE gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonSchoolYear.status='Current') > 0");  
        } else {
            $query->where("gibbonSubstitute.active='Y'");
        }

        if ($criteria->hasFilter('showUnavailable', true)) {
            $query->groupBy(['gibbonPerson.gibbonPersonID']);
            $query->orderBy(['available DESC', 'priority DESC']);
        } else {
            $query->where('absence.ID IS NULL')
                  ->where('coverage.ID IS NULL')
                  ->where('timetable.ID IS NULL')
                  ->where('duty.ID IS NULL')
                  ->where('activity.ID IS NULL')
                  ->where('unavailable.gibbonStaffCoverageDateID IS NULL');
        }

        $criteria->addFilterRules([
            'substituteTypes' => function ($query, $substituteTypes) {
                if (!empty($substituteTypes)) {
                    $query->where('FIND_IN_SET(gibbonSubstitute.type, :substituteTypes)')
                          ->bindValue('substituteTypes', $substituteTypes);
                }

                return $query;
            },
        ]);
        
        return $this->runQuery($query, $criteria);
    }

    public function selectUnavailableDatesBySub($gibbonPersonID, $gibbonStaffCoverageIDExclude = '')
    {
        $data = ['gibbonPersonID' => $gibbonPersonID, 'gibbonStaffCoverageIDExclude' => $gibbonStaffCoverageIDExclude];
        $sql = "(
                SELECT date as groupBy, date, 'Not Available' as status, allDay, timeStart, timeEnd, gibbonStaffCoverageDate.gibbonStaffCoverageDateID as contextID
                FROM gibbonStaffCoverageDate 
                WHERE gibbonStaffCoverageDate.gibbonPersonIDUnavailable=:gibbonPersonID 
                ORDER BY DATE
            ) UNION ALL (
                SELECT date as groupBy, date, 'Covering' as status, allDay, timeStart, timeEnd, gibbonStaffCoverage.gibbonStaffCoverageID as contextID
                FROM gibbonStaffCoverage
                JOIN gibbonStaffCoverageDate ON (gibbonStaffCoverageDate.gibbonStaffCoverageID=gibbonStaffCoverage.gibbonStaffCoverageID)
                WHERE gibbonStaffCoverage.gibbonPersonIDCoverage=:gibbonPersonID 
                AND (gibbonStaffCoverage.status='Accepted')
                AND gibbonStaffCoverage.gibbonStaffCoverageID <> :gibbonStaffCoverageIDExclude
            ) UNION ALL (
                SELECT date as groupBy, date, 'Absent' as status, allDay, timeStart, timeEnd, gibbonStaffAbsence.gibbonStaffAbsenceID as contextID
                FROM gibbonStaffAbsence
                JOIN gibbonStaffAbsenceDate ON (gibbonStaffAbsenceDate.gibbonStaffAbsenceID=gibbonStaffAbsence.gibbonStaffAbsenceID)
                WHERE gibbonStaffAbsence.gibbonPersonID=:gibbonPersonID 
                AND gibbonStaffAbsence.status <> 'Declined'
            ) UNION ALL (
                SELECT date as groupBy, date, 'Teaching' as status, 'N', timeStart, timeEnd, gibbonCourseClassPerson.gibbonCourseClassID as contextID
                FROM gibbonCourseClassPerson 
                JOIN gibbonTTDayRowClass ON (gibbonTTDayRowClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID)
                JOIN gibbonTTDayDate ON (gibbonTTDayDate.gibbonTTDayID=gibbonTTDayRowClass.gibbonTTDayID)
                JOIN gibbonTTDay ON (gibbonTTDay.gibbonTTDayID=gibbonTTDayDate.gibbonTTDayID)
                JOIN gibbonTTColumnRow ON (gibbonTTColumnRow.gibbonTTColumnRowID=gibbonTTDayRowClass.gibbonTTColumnRowID 
                    AND gibbonTTDay.gibbonTTColumnID=gibbonTTColumnRow.gibbonTTColumnID)
                WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID 
                AND (gibbonCourseClassPerson.role = 'Teacher' OR gibbonCourseClassPerson.role = 'Assistant')
            )";

        return $this->db()->select($sql, $data);
    }

    public function selectUnavailableDatesByDateRange($dateStart, $dateEnd)
    {
        $data = ['dateStart' => $dateStart, 'dateEnd' => $dateEnd];
        $sql = "(
                SELECT gibbonStaffCoverageDate.gibbonPersonIDUnavailable as gibbonPersonID, gibbonStaffCoverageDate.date, 'Not Available' as status, allDay, timeStart, timeEnd, gibbonSubstitute.type, gibbonSubstitute.priority, gibbonStaffCoverageDate.gibbonStaffCoverageDateID as contextID
                FROM gibbonStaffCoverageDate 
                LEFT JOIN gibbonSubstitute ON (gibbonSubstitute.gibbonPersonID=gibbonStaffCoverageDate.gibbonPersonIDUnavailable AND gibbonSubstitute.active='Y')
                WHERE gibbonStaffCoverageDate.date BETWEEN :dateStart AND :dateEnd
            ) UNION ALL (
                SELECT gibbonStaffCoverage.gibbonPersonIDCoverage as gibbonPersonID, gibbonStaffCoverageDate.date, 'Covering' as status, allDay, timeStart, timeEnd, gibbonSubstitute.type, gibbonSubstitute.priority, gibbonStaffCoverage.gibbonStaffCoverageID as contextID
                FROM gibbonStaffCoverage
                JOIN gibbonStaffCoverageDate ON (gibbonStaffCoverageDate.gibbonStaffCoverageID=gibbonStaffCoverage.gibbonStaffCoverageID)
                LEFT JOIN gibbonSubstitute ON (gibbonSubstitute.gibbonPersonID=gibbonStaffCoverage.gibbonPersonIDCoverage AND gibbonSubstitute.active='Y')
                WHERE gibbonStaffCoverageDate.date BETWEEN :dateStart AND :dateEnd
                AND (gibbonStaffCoverage.status='Accepted')
            ) UNION ALL (
                SELECT gibbonStaffAbsence.gibbonPersonID as gibbonPersonID, gibbonStaffAbsenceDate.date, 'Absent' as status, allDay, timeStart, timeEnd, gibbonSubstitute.type, gibbonSubstitute.priority, gibbonStaffAbsence.gibbonStaffAbsenceID as contextID
                FROM gibbonStaffAbsence
                JOIN gibbonStaffAbsenceDate ON (gibbonStaffAbsenceDate.gibbonStaffAbsenceID=gibbonStaffAbsence.gibbonStaffAbsenceID)
                LEFT JOIN gibbonSubstitute ON (gibbonSubstitute.gibbonPersonID=gibbonStaffAbsence.gibbonPersonID AND gibbonSubstitute.active='Y')
                WHERE gibbonStaffAbsenceDate.date BETWEEN :dateStart AND :dateEnd
                AND gibbonStaffAbsence.status <> 'Declined'
            ) UNION ALL (
                SELECT DISTINCT gibbonCourseClassPerson.gibbonPersonID as gibbonPersonID, gibbonTTDayDate.date, 'Teaching' as status, 'N', timeStart, timeEnd, gibbonSubstitute.type, gibbonSubstitute.priority, gibbonCourseClassPerson.gibbonCourseClassID as contextID
                FROM gibbonCourseClassPerson 
                LEFT JOIN gibbonSubstitute ON (gibbonSubstitute.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID AND gibbonSubstitute.active='Y')
                JOIN gibbonTTDayRowClass ON (gibbonTTDayRowClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID)
                JOIN gibbonTTDayDate ON (gibbonTTDayDate.gibbonTTDayID=gibbonTTDayRowClass.gibbonTTDayID)
                JOIN gibbonTTDay ON (gibbonTTDay.gibbonTTDayID=gibbonTTDayDate.gibbonTTDayID)
                JOIN gibbonTTColumnRow ON (gibbonTTColumnRow.gibbonTTColumnRowID=gibbonTTDayRowClass.gibbonTTColumnRowID 
                    AND gibbonTTDay.gibbonTTColumnID=gibbonTTColumnRow.gibbonTTColumnID)
                WHERE gibbonTTDayDate.date BETWEEN :dateStart AND :dateEnd AND (gibbonCourseClassPerson.role = 'Teacher' OR gibbonCourseClassPerson.role = 'Assistant')
            ) ORDER BY priority DESC, type DESC, date, timeStart, timeEnd";

        return $this->db()->select($sql, $data);
    }

    public function getSubstituteByPerson($gibbonPersonID, $internalCoverage = 'N')
    {
        $data = ['gibbonPersonID' => $gibbonPersonID];
        if ($internalCoverage == 'Y') {
            $sql = "SELECT gibbonSubstitute.*, gibbonPerson.gibbonPersonID FROM gibbonPerson LEFT JOIN gibbonSubstitute ON (gibbonSubstitute.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.gibbonPersonID=:gibbonPersonID";
        } else {
            $sql = "SELECT * FROM gibbonSubstitute WHERE gibbonSubstitute.gibbonPersonID=:gibbonPersonID";
        }

        return $this->db()->selectOne($sql, $data);
    }
}
