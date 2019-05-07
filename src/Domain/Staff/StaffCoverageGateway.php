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

use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;
use Gibbon\Domain\Traits\TableAware;

/**
 * Staff Coverage Gateway
 *
 * @version v18
 * @since   v18
 */
class StaffCoverageGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonStaffCoverage';
    private static $primaryKey = 'gibbonStaffCoverageID';

    private static $searchableColumns = ['absence.preferredName', 'absence.surname', 'coverage.preferredName', 'coverage.surname', 'status.preferredName', 'status.surname', 'gibbonStaffCoverage.status'];

    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryCoverageBySchoolYear(QueryCriteria $criteria, $gibbonSchoolYearID)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonStaffCoverage.gibbonStaffCoverageID', 'gibbonStaffCoverage.status',  'gibbonStaffAbsenceType.name as type', 'gibbonStaffAbsence.reason', 'date', 'COUNT(*) as days', 'MIN(date) as dateStart', 'MAX(date) as dateEnd', 'allDay', 'timeStart', 'timeEnd', 'timestampStatus', 'timestampCoverage', 'gibbonStaffCoverage.gibbonStaffAbsenceID',
                'gibbonStaffCoverage.gibbonPersonID', 'absence.title AS titleAbsence', 'absence.preferredName AS preferredNameAbsence', 'absence.surname AS surnameAbsence', 
                'gibbonStaffCoverage.gibbonPersonIDCoverage', 'coverage.title as titleCoverage', 'coverage.preferredName as preferredNameCoverage', 'coverage.surname as surnameCoverage',
                'gibbonStaffCoverage.gibbonPersonIDStatus', 'status.title as titleStatus', 'status.preferredName as preferredNameStatus', 'status.surname as surnameStatus',
                'gibbonStaffCoverage.notesStatus', 'absenceStaff.jobTitle as jobTitleAbsence'
            ])
            ->innerJoin('gibbonStaffCoverageDate', 'gibbonStaffCoverageDate.gibbonStaffCoverageID=gibbonStaffCoverage.gibbonStaffCoverageID')
            ->innerJoin('gibbonSchoolYear', 'gibbonStaffCoverageDate.date BETWEEN firstDay AND lastDay')
            ->leftJoin('gibbonStaffAbsence', 'gibbonStaffCoverage.gibbonStaffAbsenceID=gibbonStaffAbsence.gibbonStaffAbsenceID')
            ->leftJoin('gibbonStaffAbsenceType', 'gibbonStaffAbsence.gibbonStaffAbsenceTypeID=gibbonStaffAbsenceType.gibbonStaffAbsenceTypeID')
            ->leftJoin('gibbonPerson AS coverage', 'gibbonStaffCoverage.gibbonPersonIDCoverage=coverage.gibbonPersonID')
            ->leftJoin('gibbonPerson AS status', 'gibbonStaffCoverage.gibbonPersonIDStatus=status.gibbonPersonID')
            ->leftJoin('gibbonPerson AS absence', 'gibbonStaffCoverage.gibbonPersonID=absence.gibbonPersonID')
            ->leftJoin('gibbonStaff AS absenceStaff', 'absence.gibbonPersonID=absenceStaff.gibbonPersonID')
            ->where('gibbonSchoolYear.gibbonSchoolYearID = :gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->groupBy(['gibbonStaffCoverage.gibbonStaffCoverageID']);

        $criteria->addFilterRules($this->getSharedFilterRules());

        return $this->runQuery($query, $criteria);
    }

    public function queryCoverageByPersonCovering(QueryCriteria $criteria, $gibbonPersonID, $grouped = true)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonStaffCoverage.gibbonStaffCoverageID', 'gibbonStaffCoverage.status',  'gibbonStaffAbsenceType.name as type', 'gibbonStaffAbsence.reason', 'date', 'COUNT(*) as days', 'MIN(date) as dateStart', 'MAX(date) as dateEnd', 'allDay', 'timeStart', 'timeEnd', 'timestampStatus', 'timestampCoverage', 'gibbonStaffCoverage.gibbonPersonIDCoverage', 
                'gibbonStaffCoverage.gibbonPersonID', 'absence.title AS titleAbsence', 'absence.preferredName AS preferredNameAbsence', 'absence.surname AS surnameAbsence',
                'gibbonStaffCoverage.gibbonPersonIDStatus', 'status.title as titleStatus', 'status.preferredName as preferredNameStatus', 'status.surname as surnameStatus',
                'gibbonStaffCoverage.notesStatus', 'absenceStaff.jobTitle as jobTitleAbsence'
            ])
            ->leftJoin('gibbonStaffCoverageDate', 'gibbonStaffCoverageDate.gibbonStaffCoverageID=gibbonStaffCoverage.gibbonStaffCoverageID')
            ->leftJoin('gibbonStaffAbsence', 'gibbonStaffCoverage.gibbonStaffAbsenceID=gibbonStaffAbsence.gibbonStaffAbsenceID')
            ->leftJoin('gibbonStaffAbsenceType', 'gibbonStaffAbsence.gibbonStaffAbsenceTypeID=gibbonStaffAbsenceType.gibbonStaffAbsenceTypeID')
            ->leftJoin('gibbonPerson AS status', 'gibbonStaffCoverage.gibbonPersonIDStatus=status.gibbonPersonID')
            ->leftJoin('gibbonPerson AS absence', 'gibbonStaffCoverage.gibbonPersonID=absence.gibbonPersonID')
            ->leftJoin('gibbonStaff AS absenceStaff', 'absence.gibbonPersonID=absenceStaff.gibbonPersonID')
            ->where('gibbonStaffCoverage.gibbonPersonIDCoverage = :gibbonPersonID')
            ->bindValue('gibbonPersonID', $gibbonPersonID)
            ->groupBy($grouped ? ['gibbonStaffCoverage.gibbonStaffCoverageID'] : ['gibbonStaffCoverageDate.gibbonStaffCoverageDateID'])
            ->orderBy(["gibbonStaffCoverage.status = 'Requested' DESC"]);

        $criteria->addFilterRules($this->getSharedFilterRules());

        return $this->runQuery($query, $criteria);
    }

    public function queryCoverageByPersonAbsent(QueryCriteria $criteria, $gibbonPersonID)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonStaffCoverage.gibbonStaffCoverageID', 'gibbonStaffCoverage.status',  'gibbonStaffAbsenceType.name as type', 'gibbonStaffAbsence.reason', 'date', 'COUNT(*) as days', 'MIN(date) as dateStart', 'MAX(date) as dateEnd', 'allDay', 'timeStart', 'timeEnd', 'timestampStatus', 'timestampCoverage', 'gibbonStaffCoverage.gibbonPersonIDCoverage', 'gibbonStaffCoverage.gibbonPersonID', 
                'coverage.title as titleCoverage', 'coverage.preferredName as preferredNameCoverage', 'coverage.surname as surnameCoverage', 'gibbonStaffCoverage.notesCoverage'
            ])
            ->leftJoin('gibbonStaffCoverageDate', 'gibbonStaffCoverageDate.gibbonStaffCoverageID=gibbonStaffCoverage.gibbonStaffCoverageID')
            ->leftJoin('gibbonStaffAbsence', 'gibbonStaffCoverage.gibbonStaffAbsenceID=gibbonStaffAbsence.gibbonStaffAbsenceID')
            ->leftJoin('gibbonStaffAbsenceType', 'gibbonStaffAbsence.gibbonStaffAbsenceTypeID=gibbonStaffAbsenceType.gibbonStaffAbsenceTypeID')
            ->leftJoin('gibbonPerson AS coverage', 'gibbonStaffCoverage.gibbonPersonIDCoverage=coverage.gibbonPersonID')
            ->where('gibbonStaffCoverage.gibbonPersonID = :gibbonPersonID')
            ->bindValue('gibbonPersonID', $gibbonPersonID)
            ->groupBy(['gibbonStaffCoverage.gibbonStaffCoverageID'])
            ->orderBy(["gibbonStaffCoverage.status = 'Requested' DESC"]);

        $criteria->addFilterRules($this->getSharedFilterRules());

        return $this->runQuery($query, $criteria);
    }

    public function queryCoverageWithNoPersonAssigned(QueryCriteria $criteria)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonStaffCoverage.gibbonStaffCoverageID', 'gibbonStaffCoverage.status',  'gibbonStaffAbsenceType.name as type', 'gibbonStaffAbsence.reason', 'date', 'COUNT(*) as days', 'MIN(date) as dateStart', 'MAX(date) as dateEnd', 'allDay', 'timeStart', 'timeEnd', 'timestampStatus', 'timestampCoverage', 'gibbonStaffCoverage.gibbonPersonIDCoverage', 'gibbonStaffCoverage.gibbonPersonID', 
                'absence.title AS titleAbsence', 'absence.preferredName AS preferredNameAbsence', 'absence.surname AS surnameAbsence', 'absenceStaff.jobTitle as jobTitleAbsence'
            ])
            ->innerJoin('gibbonStaffAbsence', 'gibbonStaffCoverage.gibbonStaffAbsenceID=gibbonStaffAbsence.gibbonStaffAbsenceID')
            ->innerJoin('gibbonStaffAbsenceType', 'gibbonStaffAbsence.gibbonStaffAbsenceTypeID=gibbonStaffAbsenceType.gibbonStaffAbsenceTypeID')
            ->leftJoin('gibbonStaffCoverageDate', 'gibbonStaffCoverageDate.gibbonStaffCoverageID=gibbonStaffCoverage.gibbonStaffCoverageID')
            ->leftJoin('gibbonPerson AS absence', 'gibbonStaffCoverage.gibbonPersonID=absence.gibbonPersonID')
            ->leftJoin('gibbonStaff AS absenceStaff', 'absence.gibbonPersonID=absenceStaff.gibbonPersonID')
            ->where('gibbonStaffCoverage.gibbonPersonIDCoverage IS NULL')
            ->groupBy(['gibbonStaffCoverage.gibbonStaffCoverageID']);

        $criteria->addFilterRules($this->getSharedFilterRules());

        return $this->runQuery($query, $criteria);
    }

    public function getCoverageDetailsByID($gibbonStaffCoverageID)
    {
        $data = ['gibbonStaffCoverageID' => $gibbonStaffCoverageID];
        $sql = "SELECT gibbonStaffCoverage.gibbonStaffCoverageID, gibbonStaffCoverage.status, gibbonStaffAbsence.gibbonStaffAbsenceID, gibbonStaffAbsenceType.name as type, gibbonStaffAbsence.reason, substituteTypes,
                MIN(date) as date, COUNT(*) as days, MIN(date) as dateStart, MAX(date) as dateEnd, MAX(allDay) as allDay, MIN(timeStart) as timeStart, MAX(timeEnd) as timeEnd, timestampStatus, timestampCoverage, gibbonStaffCoverage.requestType,
                gibbonStaffCoverage.notesCoverage, gibbonStaffCoverage.notesStatus, 0 as urgent, gibbonStaffAbsence.notificationSent, gibbonStaffAbsence.gibbonGroupID, gibbonStaffCoverage.notificationList as notificationListCoverage, gibbonStaffAbsence.notificationList as notificationListAbsence, 
                gibbonStaffCoverage.gibbonPersonID, absence.title AS titleAbsence, absence.preferredName AS preferredNameAbsence, absence.surname AS surnameAbsence, 
                gibbonStaffCoverage.gibbonPersonIDStatus, status.title AS titleStatus, status.preferredName AS preferredNameStatus, status.surname AS surnameStatus, 
                gibbonStaffCoverage.gibbonPersonIDCoverage, coverage.title as titleCoverage, coverage.preferredName as preferredNameCoverage, coverage.surname as surnameCoverage
            FROM gibbonStaffCoverage
            LEFT JOIN gibbonStaffCoverageDate ON (gibbonStaffCoverageDate.gibbonStaffCoverageID=gibbonStaffCoverage.gibbonStaffCoverageID)
            LEFT JOIN gibbonStaffAbsence ON (gibbonStaffAbsence.gibbonStaffAbsenceID=gibbonStaffCoverage.gibbonStaffAbsenceID)
            LEFT JOIN gibbonStaffAbsenceType ON (gibbonStaffAbsence.gibbonStaffAbsenceTypeID=gibbonStaffAbsenceType.gibbonStaffAbsenceTypeID)
            LEFT JOIN gibbonPerson AS coverage ON (gibbonStaffCoverage.gibbonPersonIDCoverage=coverage.gibbonPersonID)
            LEFT JOIN gibbonPerson AS status ON (gibbonStaffCoverage.gibbonPersonIDStatus=status.gibbonPersonID)
            LEFT JOIN gibbonPerson AS absence ON (gibbonStaffCoverage.gibbonPersonID=absence.gibbonPersonID)
            WHERE gibbonStaffCoverage.gibbonStaffCoverageID=:gibbonStaffCoverageID
            GROUP BY gibbonStaffCoverage.gibbonStaffCoverageID
            ";

        return $this->db()->selectOne($sql, $data);
    }

    public function selectTimetableRowsByCoverageDate($gibbonStaffCoverageID, $date)
    {
        $data = ['gibbonStaffCoverageID' => $gibbonStaffCoverageID, 'date' => $date];
        $sql = "SELECT gibbonCourseClass.gibbonCourseClassID, gibbonTTColumnRow.name as period, gibbonTTColumnRow.timeStart, gibbonTTColumnRow.timeEnd, gibbonCourse.name as courseName, gibbonCourse.nameShort as courseNameShort, gibbonCourseClass.nameShort as className, gibbonCourseClass.attendance, gibbonSpace.name as spaceName
                FROM gibbonStaffCoverage
                JOIN gibbonStaffCoverageDate ON (gibbonStaffCoverage.gibbonStaffCoverageID=gibbonStaffCoverageDate.gibbonStaffCoverageID)
                JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonStaffCoverage.gibbonPersonID)
                JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID)
                JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID)
                JOIN gibbonTTDayRowClass ON (gibbonTTDayRowClass.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
                JOIN gibbonTTDayDate ON (gibbonTTDayDate.gibbonTTDayID=gibbonTTDayRowClass.gibbonTTDayID AND gibbonTTDayDate.date=gibbonStaffCoverageDate.date)
                JOIN gibbonTTColumnRow ON (gibbonTTColumnRow.gibbonTTColumnRowID=gibbonTTDayRowClass.gibbonTTColumnRowID)
                LEFT JOIN gibbonSpace ON (gibbonSpace.gibbonSpaceID=gibbonTTDayRowClass.gibbonSpaceID)
                WHERE gibbonStaffCoverage.gibbonStaffCoverageID=:gibbonStaffCoverageID 
                AND (gibbonCourseClassPerson.role = 'Teacher' OR gibbonCourseClassPerson.role = 'Assistant')
                AND gibbonStaffCoverageDate.date=:date
                AND gibbonCourse.gibbonSchoolYearID=gibbonStaffCoverage.gibbonSchoolYearID
                AND (gibbonStaffCoverageDate.allDay='Y' 
                    OR (gibbonStaffCoverageDate.allDay='N' AND gibbonTTColumnRow.timeStart <= gibbonStaffCoverageDate.timeEnd AND gibbonTTColumnRow.timeEnd >= gibbonStaffCoverageDate.timeStart)
                )
                GROUP BY gibbonTTColumnRow.gibbonTTColumnRowID, gibbonCourse.gibbonCourseID, gibbonCourseClass.gibbonCourseClassID, gibbonSpace.gibbonSpaceID
                ORDER BY gibbonTTColumnRow.timeStart
        ";

        return $this->db()->select($sql, $data);
    }

    public function selectCoverageByAbsenceID($gibbonStaffAbsenceID)
    {
        $data = ['gibbonStaffAbsenceID' => $gibbonStaffAbsenceID];
        $sql = "SELECT gibbonStaffCoverageID
                FROM gibbonStaffCoverage
                WHERE gibbonStaffCoverage.gibbonStaffAbsenceID = :gibbonStaffAbsenceID
                ORDER BY gibbonStaffCoverage.timestampStatus ASC";

        return $this->db()->select($sql, $data);
    }

    protected function getSharedFilterRules()
    {
        return [
            'requested' => function ($query, $requested) {
                return $requested == 'Y'
                    ? $query->where("gibbonStaffCoverage.status = 'Requested'")
                    : $query->where("gibbonStaffCoverage.status <> 'Requested'");
            },
            'status' => function ($query, $status) {
                return $query->where('gibbonStaffCoverage.status = :status')
                             ->bindValue('status', $status);
            },
            'dateStart' => function ($query, $dateStart) {
                return $query->where("gibbonStaffCoverageDate.date >= :dateStart")
                             ->bindValue('dateStart', $dateStart);
            },
            'dateEnd' => function ($query, $dateEnd) {
                return $query->where("gibbonStaffCoverageDate.date <= :dateEnd")
                             ->bindValue('dateEnd', $dateEnd);
            },
            'date' => function ($query, $date) {
                switch (ucfirst($date)) {
                    case 'Upcoming': return $query->where("date >= CURRENT_DATE()")->where("gibbonStaffCoverage.status <> 'Declined' AND gibbonStaffCoverage.status <> 'Cancelled'");
                    case 'Today'   : return $query->where("gibbonStaffCoverageDate.date = CURRENT_DATE()");
                    case 'Past'    : return $query->where("gibbonStaffCoverageDate.date < CURRENT_DATE()");
                }
            },
        ];
    }
}
