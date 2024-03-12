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
                'absence.status as absence', 'coverage.status as coverage', 'timetable.status as timetable', 'timetable.ID as courseClassID', 'duty.ID as duty', 'activity.ID as activity', 'unavailable.reason as unavailable',
            ])
            ->leftJoin('gibbonSubstitute', 'gibbonSubstitute.gibbonPersonID=gibbonPerson.gibbonPersonID');
                
        if ($criteria->hasFilter('allStaff', 'Y')) {
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
            "SELECT gibbonTTDayRowClass.gibbonCourseClassID as ID, CONCAT(gibbonCourse.nameShort, '.', gibbonCourseClass.nameShort) as status, gibbonCourseClassPerson.gibbonPersonID, gibbonTTDayDate.date, timeStart, timeEnd
                FROM gibbonCourseClassPerson 
                JOIN gibbonTTDayRowClass ON (gibbonTTDayRowClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID)
                JOIN gibbonTTDayDate ON (gibbonTTDayDate.gibbonTTDayID=gibbonTTDayRowClass.gibbonTTDayID)
                JOIN gibbonTTDay ON (gibbonTTDay.gibbonTTDayID=gibbonTTDayDate.gibbonTTDayID)
                JOIN gibbonTTColumnRow ON (gibbonTTColumnRow.gibbonTTColumnRowID=gibbonTTDayRowClass.gibbonTTColumnRowID 
                    AND gibbonTTDay.gibbonTTColumnID=gibbonTTColumnRow.gibbonTTColumnID)
                JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseClassID=gibbonTTDayRowClass.gibbonCourseClassID)
                JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID)
                LEFT JOIN gibbonTTDayRowClassException ON (gibbonTTDayRowClassException.gibbonTTDayRowClassID=gibbonTTDayRowClass.gibbonTTDayRowClassID AND gibbonTTDayRowClassException.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID)
                WHERE (gibbonCourseClassPerson.role = 'Teacher' OR gibbonCourseClassPerson.role = 'Assistant')
                AND gibbonTTDayRowClassExceptionID IS NULL",
            'timetable',
            "timetable.gibbonPersonID=gibbonPerson.gibbonPersonID AND timetable.date = :date 
                AND timetable.timeStart < :timeEnd AND timetable.timeEnd > :timeStart"
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
        $activityDateType = $this->db()->selectOne("SELECT value FROM gibbonSetting WHERE scope='Activities' AND name='dateType'");
        if ($activityDateType == 'Term') {
            $sql = "SELECT gibbonActivityStaff.gibbonActivityStaffID as ID, gibbonActivity.name as status, gibbonActivityStaff.gibbonPersonID, :date as date, gibbonActivitySlot.timeStart, gibbonActivitySlot.timeEnd, gibbonDaysOfWeek.gibbonDaysOfWeekID, activityTerm.firstDay as dateStart, activityTerm.lastDay as dateEnd
            FROM gibbonActivityStaff 
            JOIN gibbonActivity ON (gibbonActivityStaff.gibbonActivityID=gibbonActivity.gibbonActivityID)
            JOIN gibbonActivitySlot ON (gibbonActivitySlot.gibbonActivityID=gibbonActivity.gibbonActivityID)
            JOIN gibbonDaysOfWeek ON (gibbonActivitySlot.gibbonDaysOfWeekID=gibbonDaysOfWeek.gibbonDaysOfWeekID)
            JOIN gibbonSchoolYearTerm as activityTerm ON FIND_IN_SET(activityTerm.gibbonSchoolYearTermID, gibbonActivity.gibbonSchoolYearTermIDList)";
        } else {
            $sql = "SELECT gibbonActivityStaff.gibbonActivityStaffID as ID, gibbonActivity.name as status, gibbonActivityStaff.gibbonPersonID, :date as date, gibbonActivitySlot.timeStart, gibbonActivitySlot.timeEnd, gibbonDaysOfWeek.gibbonDaysOfWeekID, gibbonActivity.programStart as dateStart, gibbonActivity.programEnd as dateEnd
            FROM gibbonActivityStaff 
            JOIN gibbonActivity ON (gibbonActivityStaff.gibbonActivityID=gibbonActivity.gibbonActivityID)
            JOIN gibbonActivitySlot ON (gibbonActivitySlot.gibbonActivityID=gibbonActivity.gibbonActivityID)
            JOIN gibbonDaysOfWeek ON (gibbonActivitySlot.gibbonDaysOfWeekID=gibbonDaysOfWeek.gibbonDaysOfWeekID)";
        }
        $query->joinSubSelect(
            'LEFT',
            $sql,
            'activity',
            "activity.gibbonPersonID=gibbonPerson.gibbonPersonID AND (activity.gibbonDaysOfWeekID-1) = WEEKDAY(:date) 
                AND activity.timeStart < :timeEnd AND activity.timeEnd > :timeStart AND :date BETWEEN activity.dateStart AND activity.dateEnd"
        );
        

        $query->where("gibbonPerson.status='Full'")
              ->where('(gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart<=:date)')
              ->where('(gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd>=:date)')
              ->bindValue('date', $date);

        if ($criteria->hasFilter('allStaff', 'Y')) {
            $query->where("gibbonRole.category='Staff' AND gibbonStaff.type='Teaching'");
        } else {
            $query->where("gibbonSubstitute.active='Y'");
        }

        $showUnavailable = $criteria->hasFilter('showUnavailable', true);

        if ($showUnavailable) {
            $query->groupBy(['gibbonPerson.gibbonPersonID']);
            $query->orderBy(['available DESC', 'priority DESC']);
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
        
        $dataSet = $this->runQuery($query, $criteria);

        // Get any Off Timetable special days for this date
        $sql = "SELECT * FROM gibbonSchoolYearSpecialDay WHERE date =:date AND type='Off Timetable'";
        $specialDay = $this->db()->select($sql, ['date' => $date])->fetch();

        // Update the results to release teachers from off-timetable classes
        $dataSet->transform(function (&$item) use (&$specialDay) {
            $item['available'] = empty($item['absence']) && empty($item['coverage']) && empty($item['timetable']) && empty($item['duty']) && empty($item['activity']) && empty($item['unavailable']);

            if (!empty($specialDay) && !empty($item['courseClassID'])) {
                if ($this->getIsClassOffTimetableByDate($item['courseClassID'], $specialDay['date'])) {
                    $item['available'] = empty($item['absence']) && empty($item['coverage']) && empty($item['duty']) && empty($item['activity']) && empty($item['unavailable']);
                    $item['courseClassID'] = '';
                    $item['timetable'] = '';
                }
            }
        });

        // Filter the results based on updated availability
        $dataSet->filter(function ($item) use (&$showUnavailable) {
            return $item['available'] || $showUnavailable;
        });

        return $dataSet;
    }

    public function selectUnavailableDatesBySub($gibbonPersonID, $dateStart, $dateEnd, $gibbonStaffCoverageIDExclude = '')
    {
        $data = ['gibbonPersonID' => $gibbonPersonID, 'dateStart' => $dateStart, 'dateEnd' => $dateEnd, 'gibbonStaffCoverageIDExclude' => $gibbonStaffCoverageIDExclude];
        $sql = "(
                SELECT date as groupBy, date, 'Not Available' as status, allDay, timeStart, timeEnd, gibbonStaffCoverageDate.gibbonStaffCoverageDateID as contextID
                FROM gibbonStaffCoverageDate 
                WHERE gibbonStaffCoverageDate.gibbonPersonIDUnavailable=:gibbonPersonID 
                AND gibbonStaffCoverageDate.date BETWEEN :dateStart AND :dateEnd
                ORDER BY DATE
            ) UNION ALL (
                SELECT date as groupBy, date, 'Covering' as status, allDay, timeStart, timeEnd, gibbonStaffCoverage.gibbonStaffCoverageID as contextID
                FROM gibbonStaffCoverage
                JOIN gibbonStaffCoverageDate ON (gibbonStaffCoverageDate.gibbonStaffCoverageID=gibbonStaffCoverage.gibbonStaffCoverageID)
                WHERE gibbonStaffCoverage.gibbonPersonIDCoverage=:gibbonPersonID 
                AND (gibbonStaffCoverage.status='Accepted')
                AND gibbonStaffCoverage.gibbonStaffCoverageID <> :gibbonStaffCoverageIDExclude
                AND gibbonStaffCoverageDate.date BETWEEN :dateStart AND :dateEnd
            ) UNION ALL (
                SELECT date as groupBy, date, 'Absent' as status, allDay, timeStart, timeEnd, gibbonStaffAbsence.gibbonStaffAbsenceID as contextID
                FROM gibbonStaffAbsence
                JOIN gibbonStaffAbsenceDate ON (gibbonStaffAbsenceDate.gibbonStaffAbsenceID=gibbonStaffAbsence.gibbonStaffAbsenceID)
                WHERE gibbonStaffAbsence.gibbonPersonID=:gibbonPersonID 
                AND gibbonStaffAbsence.status <> 'Declined'
                AND gibbonStaffAbsenceDate.date BETWEEN :dateStart AND :dateEnd
            ) UNION ALL (
                SELECT date as groupBy, date, 'Staff Duty' as status, 'N' as allDay, timeStart, timeEnd, gibbonStaffDuty.gibbonStaffDutyID as contextID
                FROM gibbonStaffDutyPerson
                JOIN gibbonStaffDuty ON (gibbonStaffDutyPerson.gibbonStaffDutyID=gibbonStaffDuty.gibbonStaffDutyID)
                JOIN gibbonDaysOfWeek ON (gibbonStaffDutyPerson.gibbonDaysOfWeekID=gibbonDaysOfWeek.gibbonDaysOfWeekID)
                JOIN gibbonTTDayDate ON ( (gibbonDaysOfWeek.gibbonDaysOfWeekID-1) = WEEKDAY(gibbonTTDayDate.date) )
                WHERE gibbonStaffDutyPerson.gibbonPersonID=:gibbonPersonID 
                AND gibbonTTDayDate.date BETWEEN :dateStart AND :dateEnd
                GROUP BY gibbonTTDayDate.date
            ) UNION ALL (
                SELECT date as groupBy, date, 'Teaching' as status, 'N', timeStart, timeEnd, gibbonCourseClassPerson.gibbonCourseClassID as contextID
                FROM gibbonCourseClassPerson 
                JOIN gibbonTTDayRowClass ON (gibbonTTDayRowClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID)
                JOIN gibbonTTDayDate ON (gibbonTTDayDate.gibbonTTDayID=gibbonTTDayRowClass.gibbonTTDayID)
                JOIN gibbonTTDay ON (gibbonTTDay.gibbonTTDayID=gibbonTTDayDate.gibbonTTDayID)
                JOIN gibbonTTColumnRow ON (gibbonTTColumnRow.gibbonTTColumnRowID=gibbonTTDayRowClass.gibbonTTColumnRowID 
                    AND gibbonTTDay.gibbonTTColumnID=gibbonTTColumnRow.gibbonTTColumnID)
                LEFT JOIN gibbonTTDayRowClassException ON (gibbonTTDayRowClassException.gibbonTTDayRowClassID=gibbonTTDayRowClass.gibbonTTDayRowClassID AND gibbonTTDayRowClassException.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID)
                WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID 
                AND (gibbonCourseClassPerson.role = 'Teacher' OR gibbonCourseClassPerson.role = 'Assistant')
                AND gibbonTTDayRowClassExceptionID IS NULL
                AND gibbonTTDayDate.date BETWEEN :dateStart AND :dateEnd
            )";

        return $this->db()->select($sql, $data);
    }

    public function selectUnavailableDatesByDateRange($dateStart, $dateEnd)
    {
        $data = ['dateStart' => $dateStart, 'dateEnd' => $dateEnd];
        $sql = "(
                SELECT gibbonStaffCoverageDate.gibbonPersonIDUnavailable as gibbonPersonID, gibbonStaffCoverageDate.date, 'Not Available' as status, allDay, timeStart, timeEnd, gibbonSubstitute.type, gibbonSubstitute.priority, gibbonStaffCoverageDate.gibbonStaffCoverageDateID as contextID, gibbonStaffCoverageDate.reason
                FROM gibbonStaffCoverageDate 
                LEFT JOIN gibbonSubstitute ON (gibbonSubstitute.gibbonPersonID=gibbonStaffCoverageDate.gibbonPersonIDUnavailable AND gibbonSubstitute.active='Y')
                WHERE gibbonStaffCoverageDate.date BETWEEN :dateStart AND :dateEnd
            ) UNION ALL (
                SELECT gibbonStaffCoverage.gibbonPersonIDCoverage as gibbonPersonID, gibbonStaffCoverageDate.date, 'Covering' as status, allDay, timeStart, timeEnd, gibbonSubstitute.type, gibbonSubstitute.priority, gibbonStaffCoverage.gibbonStaffCoverageID as contextID, gibbonStaffCoverageDate.reason
                FROM gibbonStaffCoverage
                JOIN gibbonStaffCoverageDate ON (gibbonStaffCoverageDate.gibbonStaffCoverageID=gibbonStaffCoverage.gibbonStaffCoverageID)
                LEFT JOIN gibbonSubstitute ON (gibbonSubstitute.gibbonPersonID=gibbonStaffCoverage.gibbonPersonIDCoverage AND gibbonSubstitute.active='Y')
                WHERE gibbonStaffCoverageDate.date BETWEEN :dateStart AND :dateEnd
                AND (gibbonStaffCoverage.status='Accepted')
            ) UNION ALL (
                SELECT gibbonStaffAbsence.gibbonPersonID as gibbonPersonID, gibbonStaffAbsenceDate.date, 'Absent' as status, allDay, timeStart, timeEnd, gibbonSubstitute.type, gibbonSubstitute.priority, gibbonStaffAbsence.gibbonStaffAbsenceID as contextID, gibbonStaffAbsenceType.name as reason
                FROM gibbonStaffAbsence
                JOIN gibbonStaffAbsenceType ON (gibbonStaffAbsence.gibbonStaffAbsenceTypeID=gibbonStaffAbsenceType.gibbonStaffAbsenceTypeID)
                JOIN gibbonStaffAbsenceDate ON (gibbonStaffAbsenceDate.gibbonStaffAbsenceID=gibbonStaffAbsence.gibbonStaffAbsenceID)
                LEFT JOIN gibbonSubstitute ON (gibbonSubstitute.gibbonPersonID=gibbonStaffAbsence.gibbonPersonID AND gibbonSubstitute.active='Y')
                WHERE gibbonStaffAbsenceDate.date BETWEEN :dateStart AND :dateEnd
                AND gibbonStaffAbsence.status <> 'Declined'
            ) UNION ALL (
                SELECT gibbonStaffDutyPerson.gibbonPersonID, gibbonTTDayDate.date, 'Staff Duty' as status, 'N' as allDay, timeStart, timeEnd, gibbonSubstitute.type, gibbonSubstitute.priority, gibbonStaffDuty.gibbonStaffDutyID as contextID, gibbonStaffDuty.name as reason
                FROM gibbonStaffDutyPerson
                JOIN gibbonStaffDuty ON (gibbonStaffDutyPerson.gibbonStaffDutyID=gibbonStaffDuty.gibbonStaffDutyID)
                JOIN gibbonDaysOfWeek ON (gibbonStaffDutyPerson.gibbonDaysOfWeekID=gibbonDaysOfWeek.gibbonDaysOfWeekID)
                JOIN gibbonTTDayDate ON ( (gibbonDaysOfWeek.gibbonDaysOfWeekID-1) = WEEKDAY(gibbonTTDayDate.date) )
                LEFT JOIN gibbonSubstitute ON (gibbonSubstitute.gibbonPersonID=gibbonStaffDutyPerson.gibbonPersonID AND gibbonSubstitute.active='Y')
                WHERE gibbonTTDayDate.date BETWEEN :dateStart AND :dateEnd
                GROUP BY gibbonTTDayDate.date
            ) UNION ALL (
                SELECT DISTINCT gibbonCourseClassPerson.gibbonPersonID as gibbonPersonID, gibbonTTDayDate.date, 'Teaching' as status, 'N', timeStart, timeEnd, gibbonSubstitute.type, gibbonSubstitute.priority, gibbonCourseClassPerson.gibbonCourseClassID as contextID, CONCAT(gibbonCourse.nameShort, '.', gibbonCourseClass.nameShort) as reason
                FROM gibbonCourseClassPerson
                JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID)
                JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID)
                LEFT JOIN gibbonSubstitute ON (gibbonSubstitute.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID AND gibbonSubstitute.active='Y')
                JOIN gibbonTTDayRowClass ON (gibbonTTDayRowClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID)
                JOIN gibbonTTDayDate ON (gibbonTTDayDate.gibbonTTDayID=gibbonTTDayRowClass.gibbonTTDayID)
                JOIN gibbonTTDay ON (gibbonTTDay.gibbonTTDayID=gibbonTTDayDate.gibbonTTDayID)
                JOIN gibbonTTColumnRow ON (gibbonTTColumnRow.gibbonTTColumnRowID=gibbonTTDayRowClass.gibbonTTColumnRowID 
                    AND gibbonTTDay.gibbonTTColumnID=gibbonTTColumnRow.gibbonTTColumnID)
                LEFT JOIN gibbonTTDayRowClassException ON (gibbonTTDayRowClassException.gibbonTTDayRowClassID=gibbonTTDayRowClass.gibbonTTDayRowClassID AND gibbonTTDayRowClassException.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID)
                WHERE gibbonTTDayDate.date BETWEEN :dateStart AND :dateEnd 
                AND (gibbonCourseClassPerson.role = 'Teacher' OR gibbonCourseClassPerson.role = 'Assistant')
                AND gibbonTTDayRowClassExceptionID IS NULL
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

    protected function getIsClassOffTimetableByDate($gibbonCourseClassID, $date)
    {
        $data = ['gibbonCourseClassID' => $gibbonCourseClassID, 'date' => $date];
        $sql = "SELECT COUNT(*) as studentTotal, COUNT(CASE WHEN (gibbonSchoolYearSpecialDayID IS NULL OR NOT FIND_IN_SET(gibbonStudentEnrolment.gibbonYearGroupID, gibbonSchoolYearSpecialDay.gibbonYearGroupIDList) ) AND (gibbonSchoolYearSpecialDayID IS NULL OR NOT FIND_IN_SET(gibbonStudentEnrolment.gibbonFormGroupID, gibbonSchoolYearSpecialDay.gibbonFormGroupIDList)) THEN student.gibbonPersonID ELSE NULL END) as studentCount 
            FROM gibbonCourseClassPerson 
            JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID)
            JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID)
            JOIN gibbonPerson AS student ON (gibbonCourseClassPerson.gibbonPersonID=student.gibbonPersonID) 
            JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=student.gibbonPersonID) 
            LEFT JOIN gibbonSchoolYearSpecialDay ON (gibbonSchoolYearSpecialDay.date=:date AND gibbonSchoolYearSpecialDay.type='Off Timetable')
            WHERE gibbonStudentEnrolment.gibbonSchoolYearID=gibbonCourse.gibbonSchoolYearID
            AND gibbonCourseClassPerson.role='Student' 
            AND student.status='Full' 
            AND gibbonCourseClassPerson.gibbonCourseClassID=:gibbonCourseClassID 
            AND (student.dateStart IS NULL OR student.dateStart<=:date) 
            AND (student.dateEnd IS NULL OR student.dateEnd>=:date) 
            AND (
                (gibbonSchoolYearSpecialDayID IS NULL OR NOT FIND_IN_SET(gibbonStudentEnrolment.gibbonYearGroupID, gibbonSchoolYearSpecialDay.gibbonYearGroupIDList) )
                OR (gibbonSchoolYearSpecialDayID IS NULL OR NOT FIND_IN_SET(gibbonStudentEnrolment.gibbonFormGroupID, gibbonSchoolYearSpecialDay.gibbonFormGroupIDList))
            )";

        $result = $this->db()->selectOne($sql, $data);

        return !empty($result) && ($result['studentTotal'] > 0 && $result['studentCount'] <= 0);

    }
}
