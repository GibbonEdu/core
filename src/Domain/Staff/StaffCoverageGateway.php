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

    private static $searchableColumns = ['absence.username', 'absence.preferredName', 'absence.surname', 'coverage.username', 'coverage.preferredName', 'coverage.surname', 'status.preferredName', 'status.surname', 'gibbonStaffCoverage.status'];

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
                'gibbonStaffCoverage.gibbonStaffCoverageID', 'gibbonStaffCoverage.status',  'gibbonStaffAbsenceType.name as type', 'gibbonStaffAbsence.reason', 'gibbonStaffCoverageDate.date', 'COUNT(*) as days', 'MIN(date) as dateStart', 'MAX(date) as dateEnd', 'gibbonStaffCoverageDate.allDay', 'gibbonStaffCoverageDate.timeStart', 'gibbonStaffCoverageDate.timeEnd', 'gibbonStaffCoverage.timestampStatus', 'gibbonStaffCoverage.timestampCoverage', 'gibbonStaffCoverage.gibbonStaffAbsenceID',
                'gibbonStaffCoverage.gibbonPersonID', 'absence.title AS titleAbsence', 'absence.preferredName AS preferredNameAbsence', 'absence.surname AS surnameAbsence', 
                'gibbonStaffCoverage.gibbonPersonIDCoverage', 'coverage.title as titleCoverage', 'coverage.preferredName as preferredNameCoverage', 'coverage.surname as surnameCoverage',
                'gibbonStaffCoverage.gibbonPersonIDStatus', 'status.title as titleStatus', 'status.preferredName as preferredNameStatus', 'status.surname as surnameStatus',
                'gibbonStaffCoverage.notesStatus', 'absenceStaff.jobTitle as jobTitleAbsence', 'gibbonStaffCoverageDate.foreignTableID',
                '(CASE WHEN gibbonStaffCoverage.status = "Pending" THEN 0 ELSE gibbonStaffCoverage.status END) as statusSort',

                '(CASE WHEN foreignTable="gibbonTTDayRowClass" THEN gibbonTTColumnRow.name WHEN foreignTable="gibbonStaffDutyPerson" THEN "Staff Duty" WHEN foreignTable="gibbonActivitySlot" THEN "Activity" END ) as period',
                '(CASE WHEN foreignTable="gibbonTTDayRowClass" THEN CONCAT(gibbonCourse.nameShort, ".", gibbonCourseClass.nameShort) WHEN foreignTable="gibbonStaffDutyPerson" THEN gibbonStaffDuty.name WHEN foreignTable="gibbonActivitySlot" THEN gibbonActivity.name END) as contextName', 'gibbonStaffCoverageDate.reason as coverageReason'
            ])
            ->innerJoin('gibbonStaffCoverageDate', 'gibbonStaffCoverageDate.gibbonStaffCoverageID=gibbonStaffCoverage.gibbonStaffCoverageID')
            ->innerJoin('gibbonSchoolYear', 'gibbonStaffCoverageDate.date BETWEEN firstDay AND lastDay')
            ->leftJoin('gibbonStaffAbsence', 'gibbonStaffCoverage.gibbonStaffAbsenceID=gibbonStaffAbsence.gibbonStaffAbsenceID')
            ->leftJoin('gibbonStaffAbsenceType', 'gibbonStaffAbsence.gibbonStaffAbsenceTypeID=gibbonStaffAbsenceType.gibbonStaffAbsenceTypeID')

            ->leftJoin('gibbonTTDayRowClass', 'gibbonTTDayRowClass.gibbonTTDayRowClassID=gibbonStaffCoverageDate.foreignTableID AND gibbonStaffCoverageDate.foreignTable="gibbonTTDayRowClass"')
            ->leftJoin('gibbonTTColumnRow', 'gibbonTTColumnRow.gibbonTTColumnRowID=gibbonTTDayRowClass.gibbonTTColumnRowID')
            ->leftJoin('gibbonCourseClass', 'gibbonCourseClass.gibbonCourseClassID=gibbonTTDayRowClass.gibbonCourseClassID')
            ->leftJoin('gibbonCourse', 'gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID')

            ->leftJoin('gibbonStaffDutyPerson', 'gibbonStaffDutyPerson.gibbonStaffDutyPersonID=gibbonStaffCoverageDate.foreignTableID AND gibbonStaffCoverageDate.foreignTable="gibbonStaffDutyPerson"')
            ->leftJoin('gibbonStaffDuty', 'gibbonStaffDutyPerson.gibbonStaffDutyID=gibbonStaffDuty.gibbonStaffDutyID')

            ->leftJoin('gibbonActivitySlot', 'gibbonActivitySlot.gibbonActivitySlotID=gibbonStaffCoverageDate.foreignTableID AND gibbonStaffCoverageDate.foreignTable="gibbonActivitySlot"')
            ->leftJoin('gibbonActivity', 'gibbonActivitySlot.gibbonActivityID=gibbonActivity.gibbonActivityID')

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

    public function queryCoverageByPersonCovering(QueryCriteria $criteria, $gibbonSchoolYearID, $gibbonPersonID, $grouped = true)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonStaffCoverage.gibbonStaffCoverageID', 'gibbonStaffCoverage.status', 'gibbonStaffCoverage.requestType', 'gibbonStaffAbsenceType.name as type', 'gibbonStaffAbsence.reason', 'gibbonStaffCoverageDate.date', 'COUNT(*) as days', 'MIN(date) as dateStart', 'MAX(date) as dateEnd', 'gibbonStaffCoverageDate.allDay', 'gibbonStaffCoverageDate.timeStart', 'gibbonStaffCoverageDate.timeEnd', 'timestampStatus', 'timestampCoverage', 'gibbonStaffCoverage.gibbonPersonIDCoverage', 
                'gibbonStaffCoverage.gibbonPersonID', 'absence.title AS titleAbsence', 'absence.preferredName AS preferredNameAbsence', 'absence.surname AS surnameAbsence',
                'gibbonStaffCoverage.gibbonPersonIDStatus', 'status.title as titleStatus', 'status.preferredName as preferredNameStatus', 'status.surname as surnameStatus',
                'gibbonStaffCoverage.notesStatus', 'absenceStaff.jobTitle as jobTitleAbsence', 'SUM(gibbonStaffCoverageDate.value) as value',
                'gibbonStaffCoverageDate.foreignTableID AS gibbonTTDayRowClassID', 'gibbonTTDayRowClass.gibbonTTDayID', 'gibbonSpace.name as roomName', 'gibbonSpace.phoneInternal', 'gibbonCourse.gibbonCourseID', 'gibbonCourse.nameShort as course',  'gibbonCourse.gibbonYearGroupIDList', 'gibbonCourseClass.gibbonCourseClassID', 'gibbonCourseClass.nameShort as class', 'gibbonTTColumnRow.gibbonTTColumnRowID', 'gibbonTTColumnRow.name', 'gibbonTTColumnRow.nameShort',

                '(CASE WHEN foreignTable="gibbonTTDayRowClass" THEN CONCAT(gibbonCourse.nameShort, ".", gibbonCourseClass.nameShort) WHEN foreignTable="gibbonStaffDutyPerson" THEN "Staff Duty" WHEN foreignTable="gibbonActivitySlot" THEN "Activity" ELSE gibbonStaffCoverageDate.reason END) as contextName'
            ])
            ->leftJoin('gibbonStaffCoverageDate', 'gibbonStaffCoverageDate.gibbonStaffCoverageID=gibbonStaffCoverage.gibbonStaffCoverageID')
            ->leftJoin('gibbonStaffAbsence', 'gibbonStaffCoverage.gibbonStaffAbsenceID=gibbonStaffAbsence.gibbonStaffAbsenceID')
            ->leftJoin('gibbonStaffAbsenceType', 'gibbonStaffAbsence.gibbonStaffAbsenceTypeID=gibbonStaffAbsenceType.gibbonStaffAbsenceTypeID')

            ->leftJoin('gibbonTTDayRowClass', 'gibbonStaffCoverageDate.foreignTable="gibbonTTDayRowClass" AND gibbonTTDayRowClass.gibbonTTDayRowClassID=gibbonStaffCoverageDate.foreignTableID')
            ->leftJoin('gibbonTTColumnRow', 'gibbonTTDayRowClass.gibbonTTColumnRowID=gibbonTTColumnRow.gibbonTTColumnRowID')
            ->leftJoin('gibbonCourseClass', 'gibbonCourseClass.gibbonCourseClassID=gibbonTTDayRowClass.gibbonCourseClassID')
            ->leftJoin('gibbonCourse', 'gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID')
            ->leftJoin('gibbonSpace', 'gibbonSpace.gibbonSpaceID=gibbonTTDayRowClass.gibbonSpaceID')

            ->leftJoin('gibbonPerson AS status', 'gibbonStaffCoverage.gibbonPersonIDStatus=status.gibbonPersonID')
            ->leftJoin('gibbonPerson AS absence', 'gibbonStaffCoverage.gibbonPersonID=absence.gibbonPersonID')
            ->leftJoin('gibbonStaff AS absenceStaff', 'absence.gibbonPersonID=absenceStaff.gibbonPersonID')

            ->where('gibbonStaffCoverage.gibbonSchoolYearID = :gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->where('gibbonStaffCoverage.gibbonPersonIDCoverage = :gibbonPersonID')
            ->bindValue('gibbonPersonID', $gibbonPersonID)
            ->groupBy($grouped ? ['gibbonStaffCoverage.gibbonStaffCoverageID'] : ['gibbonStaffCoverageDate.gibbonStaffCoverageDateID'])
            ->orderBy(["gibbonStaffCoverage.status = 'Requested' DESC"]);

        $criteria->addFilterRules($this->getSharedFilterRules());

        return $this->runQuery($query, $criteria);
    }

    /**
     * Get coverage for a single date grouped by timetable column.
     *
     * @param string $gibbonSchoolYearID
     * @param string $date
     * @return Result
     */
    public function selectCoverageByTimetableDate($gibbonSchoolYearID, $date)
    {
        $cols = [
             'gibbonStaffCoverageDate.foreignTableID', 'gibbonStaffCoverageDate.foreignTable', 'gibbonStaffCoverage.gibbonStaffCoverageID', 'gibbonStaffCoverageDate.gibbonStaffCoverageDateID', 'gibbonStaffCoverage.status', 'gibbonStaffCoverageDate.date', 'gibbonStaffCoverageDate.allDay', 'gibbonStaffCoverageDate.timeStart', 'gibbonStaffCoverageDate.timeEnd', 'gibbonStaffCoverage.timestampStatus', 'gibbonStaffCoverage.timestampCoverage', 'gibbonStaffCoverage.notesStatus', 'gibbonStaffCoverage.gibbonStaffAbsenceID', 'gibbonStaffAbsence.status as absenceStatus', 'gibbonStaffAbsenceType.name AS type', 'gibbonStaffAbsence.reason',
            'gibbonStaffAbsence.gibbonPersonID', 'absence.title AS titleAbsence', 'absence.preferredName AS preferredNameAbsence', 'absence.surname AS surnameAbsence', 'absenceStaff.initials as initialsAbsence',  
            'gibbonStaffCoverage.gibbonPersonIDCoverage', 'coverage.title as titleCoverage', 'coverage.preferredName as preferredNameCoverage', 'coverage.surname as surnameCoverage',
            'gibbonStaffCoverage.gibbonPersonIDStatus', 'status.title as titleStatus', 'status.preferredName as preferredNameStatus', 'status.surname as surnameStatus',
        ];

        $query = $this
            ->newSelect()
            ->from('gibbonStaffAbsence')
            ->cols(array_merge(['CONCAT("tt-", gibbonTTColumnRow.timeStart, "-", gibbonTTColumnRow.timeEnd) as groupBy', '"Class" as context', 'CONCAT(gibbonCourse.nameShort, ".", gibbonCourseClass.nameShort) as contextName','gibbonTTColumnRow.name as period', 'gibbonCourse.gibbonCourseID', 'gibbonCourse.gibbonDepartmentID', 'gibbonCourseClass.gibbonCourseClassID', 'gibbonTTDay.gibbonTTDayID', '"" as gibbonStaffDutyID', '(CASE WHEN gibbonSpaceChanged.gibbonSpaceID IS NOT NULL THEN gibbonSpaceChanged.name ELSE gibbonSpace.name END) AS space'], $cols))

            ->innerJoin('gibbonStaffAbsenceDate', 'gibbonStaffAbsenceDate.gibbonStaffAbsenceID=gibbonStaffAbsence.gibbonStaffAbsenceID')
            ->innerJoin('gibbonStaffAbsenceType', 'gibbonStaffAbsence.gibbonStaffAbsenceTypeID=gibbonStaffAbsenceType.gibbonStaffAbsenceTypeID')
            ->innerJoin('gibbonStaffCoverage', 'gibbonStaffCoverage.gibbonStaffAbsenceID=gibbonStaffAbsence.gibbonStaffAbsenceID AND gibbonStaffCoverage.status <> "Cancelled" AND gibbonStaffCoverage.status <> "Declined"')
            ->innerJoin('gibbonStaffCoverageDate', 'gibbonStaffCoverageDate.gibbonStaffCoverageID=gibbonStaffCoverage.gibbonStaffCoverageID AND gibbonStaffCoverageDate.date=gibbonStaffAbsenceDate.date')

            
            ->leftJoin('gibbonTTDayRowClass', 'gibbonTTDayRowClass.gibbonTTDayRowClassID=gibbonStaffCoverageDate.foreignTableID')
            ->leftJoin('gibbonTTColumnRow', 'gibbonTTColumnRow.gibbonTTColumnRowID=gibbonTTDayRowClass.gibbonTTColumnRowID')
            ->leftJoin('gibbonTTDay', 'gibbonTTDay.gibbonTTDayID=gibbonTTDayRowClass.gibbonTTDayID')
            ->leftJoin('gibbonTTDayDate', 'gibbonTTDayDate.gibbonTTDayID=gibbonTTDay.gibbonTTDayID AND gibbonTTDayDate.date=:date')

            ->leftJoin('gibbonCourseClass', 'gibbonCourseClass.gibbonCourseClassID=gibbonTTDayRowClass.gibbonCourseClassID')
            ->leftJoin('gibbonCourse', 'gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID')
            ->leftJoin('gibbonSpace', 'gibbonSpace.gibbonSpaceID=gibbonTTDayRowClass.gibbonSpaceID')
            ->leftJoin('gibbonTTSpaceChange', 'gibbonTTSpaceChange.gibbonTTDayRowClassID=gibbonTTDayRowClass.gibbonTTDayRowClassID AND gibbonTTSpaceChange.date=:date')
            ->leftJoin('gibbonSpace AS gibbonSpaceChanged', 'gibbonSpaceChanged.gibbonSpaceID=gibbonTTSpaceChange.gibbonSpaceID')
            
            ->leftJoin('gibbonPerson AS coverage', 'gibbonStaffCoverage.gibbonPersonIDCoverage=coverage.gibbonPersonID')
            ->leftJoin('gibbonPerson AS status', 'gibbonStaffCoverage.gibbonPersonIDStatus=status.gibbonPersonID')
            ->leftJoin('gibbonPerson AS absence', 'gibbonStaffCoverage.gibbonPersonID=absence.gibbonPersonID')
            ->leftJoin('gibbonStaff AS absenceStaff', 'absence.gibbonPersonID=absenceStaff.gibbonPersonID')
            
            ->where('gibbonStaffCoverageDate.foreignTable="gibbonTTDayRowClass"')
            ->where('gibbonStaffCoverage.gibbonSchoolYearID = :gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->where('gibbonStaffAbsenceDate.date = :date')
            ->bindValue('date', $date)
            ->where('gibbonStaffAbsence.coverageRequired <> "N"')
            ->groupBy(['gibbonStaffCoverageDate.gibbonStaffCoverageDateID']);


        $query->unionAll()
            ->from('gibbonStaffAbsence')
            ->cols(array_merge(['CONCAT("duty-", gibbonStaffDuty.timeStart, "-", gibbonStaffDuty.timeEnd) as groupBy', 'gibbonStaffDuty.name as context', '"Staff Duty" contextName', '"Staff Duty" as period', '"" AS gibbonCourseID', '"" AS gibbonDepartmentID', '"" AS gibbonCourseClassID', '"" AS gibbonTTDayID', 'gibbonStaffDuty.gibbonStaffDutyID as gibbonStaffDutyID', 'gibbonStaffDuty.nameShort AS space'], $cols))

            ->innerJoin('gibbonStaffAbsenceDate', 'gibbonStaffAbsenceDate.gibbonStaffAbsenceID=gibbonStaffAbsence.gibbonStaffAbsenceID')
            ->innerJoin('gibbonStaffAbsenceType', 'gibbonStaffAbsence.gibbonStaffAbsenceTypeID=gibbonStaffAbsenceType.gibbonStaffAbsenceTypeID')
            ->innerJoin('gibbonStaffCoverage', 'gibbonStaffCoverage.gibbonStaffAbsenceID=gibbonStaffAbsence.gibbonStaffAbsenceID')
            ->innerJoin('gibbonStaffCoverageDate', 'gibbonStaffCoverageDate.gibbonStaffCoverageID=gibbonStaffCoverage.gibbonStaffCoverageID AND gibbonStaffCoverageDate.date=gibbonStaffAbsenceDate.date')

            ->innerJoin('gibbonStaffDutyPerson', 'gibbonStaffDutyPerson.gibbonStaffDutyPersonID=gibbonStaffCoverageDate.foreignTableID')
            ->innerJoin('gibbonStaffDuty', 'gibbonStaffDuty.gibbonStaffDutyID=gibbonStaffDutyPerson.gibbonStaffDutyID')
            
            ->leftJoin('gibbonPerson AS coverage', 'gibbonStaffCoverage.gibbonPersonIDCoverage=coverage.gibbonPersonID')
            ->leftJoin('gibbonPerson AS status', 'gibbonStaffCoverage.gibbonPersonIDStatus=status.gibbonPersonID')
            ->leftJoin('gibbonPerson AS absence', 'gibbonStaffCoverage.gibbonPersonID=absence.gibbonPersonID')
            ->leftJoin('gibbonStaff AS absenceStaff', 'absence.gibbonPersonID=absenceStaff.gibbonPersonID')
            
            ->where('gibbonStaffCoverageDate.foreignTable="gibbonStaffDutyPerson"')
            ->where('gibbonStaffCoverage.gibbonSchoolYearID = :gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->where('gibbonStaffAbsenceDate.date = :date')
            ->bindValue('date', $date)
            ->where('gibbonStaffAbsence.coverageRequired <> "N"')
            ->where('gibbonStaffCoverage.status <> "Cancelled" AND gibbonStaffCoverage.status <> "Declined"')
            ->groupBy(['gibbonStaffCoverageDate.gibbonStaffCoverageDateID']);

        $query->unionAll()
            ->from('gibbonStaffAbsence')
            ->cols(array_merge(['"activity" as groupBy', '"Activity" as context', 'gibbonActivity.name contextName', '"Activity" as period', '"" AS gibbonCourseID', '"" AS gibbonDepartmentID', '"" AS gibbonCourseClassID', '"" AS gibbonTTDayID', '"" as gibbonStaffDutyID', '"" AS space'], $cols))

            ->innerJoin('gibbonStaffAbsenceDate', 'gibbonStaffAbsenceDate.gibbonStaffAbsenceID=gibbonStaffAbsence.gibbonStaffAbsenceID')
            ->innerJoin('gibbonStaffAbsenceType', 'gibbonStaffAbsence.gibbonStaffAbsenceTypeID=gibbonStaffAbsenceType.gibbonStaffAbsenceTypeID')
            ->innerJoin('gibbonStaffCoverage', 'gibbonStaffCoverage.gibbonStaffAbsenceID=gibbonStaffAbsence.gibbonStaffAbsenceID')
            ->innerJoin('gibbonStaffCoverageDate', 'gibbonStaffCoverageDate.gibbonStaffCoverageID=gibbonStaffCoverage.gibbonStaffCoverageID AND gibbonStaffCoverageDate.date=gibbonStaffAbsenceDate.date')

            ->innerJoin('gibbonActivitySlot', 'gibbonActivitySlot.gibbonActivitySlotID=gibbonStaffCoverageDate.foreignTableID')
            ->innerJoin('gibbonActivity', 'gibbonActivitySlot.gibbonActivityID=gibbonActivity.gibbonActivityID')
            ->innerJoin('gibbonActivityStaff', 'gibbonActivitySlot.gibbonActivityID=gibbonActivityStaff.gibbonActivityID && gibbonActivityStaff.gibbonPersonID=gibbonStaffCoverage.gibbonPersonID')
            
            ->leftJoin('gibbonPerson AS coverage', 'gibbonStaffCoverage.gibbonPersonIDCoverage=coverage.gibbonPersonID')
            ->leftJoin('gibbonPerson AS status', 'gibbonStaffCoverage.gibbonPersonIDStatus=status.gibbonPersonID')
            ->leftJoin('gibbonPerson AS absence', 'gibbonStaffCoverage.gibbonPersonID=absence.gibbonPersonID')
            ->leftJoin('gibbonStaff AS absenceStaff', 'absence.gibbonPersonID=absenceStaff.gibbonPersonID')
            
            ->where('gibbonStaffCoverageDate.foreignTable="gibbonActivitySlot"')
            ->where('gibbonStaffCoverage.gibbonSchoolYearID = :gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->where('gibbonStaffAbsenceDate.date = :date')
            ->bindValue('date', $date)
            ->where('gibbonStaffAbsence.coverageRequired <> "N"')
            ->where('gibbonStaffCoverage.status <> "Cancelled" AND gibbonStaffCoverage.status <> "Declined"')
            ->groupBy(['gibbonStaffCoverageDate.gibbonStaffCoverageDateID']);

        $query->orderBy(['timeStart', 'timeEnd']);

        return $this->runSelect($query);
    }

    /**
     * Get ad hoc coverage for a single date.
     *
     * @param string $gibbonSchoolYearID
     * @param string $date
     * @return Result
     */
    public function selectAdHocCoverageByDate($gibbonSchoolYearID, $date)
    {
        $query = $this
            ->newSelect()
            ->from('gibbonStaffCoverage')
            ->cols(['"Ad Hoc" as context', 'gibbonStaffCoverageDate.reason as contextName','"Ad Hoc" as period', 'gibbonStaffCoverageDate.foreignTableID', 'gibbonStaffCoverageDate.foreignTable', 'gibbonStaffCoverage.gibbonStaffCoverageID', 'gibbonStaffCoverageDate.gibbonStaffCoverageDateID', 'gibbonStaffCoverage.status', 'gibbonStaffCoverageDate.date', 'gibbonStaffCoverageDate.allDay', 'gibbonStaffCoverageDate.timeStart', 'gibbonStaffCoverageDate.timeEnd', 'gibbonStaffCoverage.timestampStatus', 'gibbonStaffCoverage.timestampCoverage', 'gibbonStaffCoverage.notesStatus', 
            '"" as absenceStatus', '"" as reason', '"" as type',
            'gibbonStaffCoverage.gibbonPersonIDCoverage', 'coverage.title as titleCoverage', 'coverage.preferredName as preferredNameCoverage', 'coverage.surname as surnameCoverage',
            'gibbonStaffCoverage.gibbonPersonIDStatus', 'status.title as titleStatus', 'status.preferredName as preferredNameStatus', 'status.surname as surnameStatus'])

            ->innerJoin('gibbonStaffCoverageDate', 'gibbonStaffCoverageDate.gibbonStaffCoverageID=gibbonStaffCoverage.gibbonStaffCoverageID')

            ->leftJoin('gibbonPerson AS coverage', 'gibbonStaffCoverage.gibbonPersonIDCoverage=coverage.gibbonPersonID')
            ->leftJoin('gibbonPerson AS status', 'gibbonStaffCoverage.gibbonPersonIDCoverage=status.gibbonPersonID')
            
            ->where('gibbonStaffCoverageDate.foreignTable IS NULL')
            ->where('gibbonStaffCoverage.status <> "Cancelled" AND gibbonStaffCoverage.status <> "Declined"')
            ->where('gibbonStaffCoverage.gibbonSchoolYearID = :gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->where('gibbonStaffCoverageDate.date = :date')
            ->bindValue('date', $date)
            ->groupBy(['gibbonStaffCoverageDate.gibbonStaffCoverageDateID']);

        $query->orderBy(['timeStart', 'timeEnd']);

        return $this->runSelect($query);

    }

    public function selectCoverageByDateRange($dateStart, $dateEnd = null)
    {
        if (empty($dateEnd)) $dateEnd = $dateStart;

        $query = $this
            ->newSelect()
            ->from('gibbonStaffCoverage')
            ->cols(['gibbonStaffCoverage.gibbonPersonIDCoverage', 'gibbonStaffCoverageDate.date', 'gibbonStaffCoverageDate.value'])
            ->innerJoin('gibbonStaffCoverageDate', 'gibbonStaffCoverage.gibbonStaffCoverageID=gibbonStaffCoverageDate.gibbonStaffCoverageID')
            ->where('gibbonStaffCoverageDate.date BETWEEN :dateStart AND :dateEnd')
            ->where("gibbonStaffCoverage.status = 'Accepted'")
            ->bindValue('dateStart', $dateStart)
            ->bindValue('dateEnd', $dateEnd);

        if (!empty($gibbonPersonID)) {
            $query->where('gibbonStaffCoverage.gibbonPersonIDCoverage=:gibbonPersonID')->bindValue('gibbonPersonID', $gibbonPersonID);
        }

        return $this->runSelect($query);
    }

    public function queryCoverageByPersonAbsent(QueryCriteria $criteria, $gibbonSchoolYearID, $gibbonPersonID, $grouped = true)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonStaffCoverage.gibbonStaffCoverageID', 'gibbonStaffCoverage.status', 'gibbonStaffCoverage.requestType',  'gibbonStaffAbsence.gibbonStaffAbsenceID', 'gibbonStaffAbsenceType.name as type', 'gibbonStaffAbsence.reason', 'gibbonStaffAbsence.coverageRequired', 'gibbonStaffAbsence.status as absenceStatus', 'gibbonStaffCoverageDate.date',  'gibbonStaffCoverageDate.allDay', 'gibbonStaffCoverageDate.timeStart', 'gibbonStaffCoverageDate.timeEnd', 'timestampStatus', 'timestampCoverage', 'gibbonStaffCoverage.gibbonPersonIDCoverage', 'gibbonStaffCoverage.gibbonPersonID', 
                'coverage.title as titleCoverage', 'coverage.preferredName as preferredNameCoverage', 'coverage.surname as surnameCoverage', 'gibbonStaffCoverage.notesStatus', 'gibbonStaffCoverage.notesCoverage', 'gibbonTTDayRowClass.gibbonTTDayRowClassID',  'gibbonStaffCoverageDate.foreignTable', 'gibbonStaffCoverageDate.foreignTableID',
                'gibbonCourse.gibbonCourseID', 'gibbonCourseClass.gibbonCourseClassID', 'gibbonCourse.gibbonDepartmentID',

                '(CASE WHEN foreignTable="gibbonTTDayRowClass" THEN gibbonTTColumnRow.name WHEN foreignTable="gibbonStaffDutyPerson" THEN "Staff Duty" WHEN foreignTable="gibbonActivitySlot" THEN "Activity" END ) as period',
                '(CASE WHEN foreignTable="gibbonTTDayRowClass" THEN CONCAT(gibbonCourse.nameShort, ".", gibbonCourseClass.nameShort) WHEN foreignTable="gibbonStaffDutyPerson" THEN gibbonStaffDuty.name WHEN foreignTable="gibbonActivitySlot" THEN gibbonActivity.name END ) as contextName'
            ])
            ->leftJoin('gibbonStaffCoverageDate', 'gibbonStaffCoverageDate.gibbonStaffCoverageID=gibbonStaffCoverage.gibbonStaffCoverageID')
            ->leftJoin('gibbonStaffAbsence', 'gibbonStaffCoverage.gibbonStaffAbsenceID=gibbonStaffAbsence.gibbonStaffAbsenceID')
            ->leftJoin('gibbonStaffAbsenceType', 'gibbonStaffAbsence.gibbonStaffAbsenceTypeID=gibbonStaffAbsenceType.gibbonStaffAbsenceTypeID')
            ->leftJoin('gibbonPerson AS coverage', 'gibbonStaffCoverage.gibbonPersonIDCoverage=coverage.gibbonPersonID')

            ->leftJoin('gibbonTTDayRowClass', 'gibbonTTDayRowClass.gibbonTTDayRowClassID=gibbonStaffCoverageDate.foreignTableID AND gibbonStaffCoverageDate.foreignTable="gibbonTTDayRowClass"')
            ->leftJoin('gibbonTTColumnRow', 'gibbonTTColumnRow.gibbonTTColumnRowID=gibbonTTDayRowClass.gibbonTTColumnRowID')
            ->leftJoin('gibbonCourseClass', 'gibbonCourseClass.gibbonCourseClassID=gibbonTTDayRowClass.gibbonCourseClassID')
            ->leftJoin('gibbonCourse', 'gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID')

            ->leftJoin('gibbonStaffDutyPerson', 'gibbonStaffDutyPerson.gibbonStaffDutyPersonID=gibbonStaffCoverageDate.foreignTableID AND gibbonStaffCoverageDate.foreignTable="gibbonStaffDutyPerson"')
            ->leftJoin('gibbonStaffDuty', 'gibbonStaffDutyPerson.gibbonStaffDutyID=gibbonStaffDuty.gibbonStaffDutyID')

            ->leftJoin('gibbonActivitySlot', 'gibbonActivitySlot.gibbonActivitySlotID=gibbonStaffCoverageDate.foreignTableID AND gibbonStaffCoverageDate.foreignTable="gibbonActivitySlot"')
            ->leftJoin('gibbonActivity', 'gibbonActivitySlot.gibbonActivityID=gibbonActivity.gibbonActivityID')

            ->where('gibbonStaffCoverage.gibbonSchoolYearID = :gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->where('gibbonStaffCoverage.gibbonPersonID = :gibbonPersonID')
            ->bindValue('gibbonPersonID', $gibbonPersonID)
            ->groupBy(['gibbonStaffCoverage.gibbonStaffCoverageID']);

        if ($grouped) {
            $query->cols(['COUNT(*) as days', 'MIN(gibbonStaffCoverageDate.date) as dateStart', 'MAX(gibbonStaffCoverageDate.date) as dateEnd'])
                  ->groupBy(['gibbonStaffCoverage.gibbonStaffCoverageID']);
        } else {
            $query->cols(['gibbonStaffCoverageDate.value as days', 'gibbonStaffCoverageDate.date as dateStart', 'gibbonStaffCoverageDate.date as dateEnd'])
                ->groupBy(['gibbonStaffCoverageDate.gibbonStaffCoverageDateID']);
        }

        $criteria->addFilterRules($this->getSharedFilterRules());

        return $this->runQuery($query, $criteria);
    }

    public function queryCoverageWithNoPersonAssigned(QueryCriteria $criteria, $substituteType = '')
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

        if (!empty($substituteType)) {
            $query->where("(gibbonStaffCoverage.substituteTypes = '' OR gibbonStaffCoverage.substituteTypes IS NULL OR FIND_IN_SET(:substituteType, gibbonStaffCoverage.substituteTypes))")
                  ->bindValue('substituteType', $substituteType);
        }

        $criteria->addFilterRules($this->getSharedFilterRules());

        return $this->runQuery($query, $criteria);
    }

    public function getCoverageDetailsByID($gibbonStaffCoverageID)
    {
        $data = ['gibbonStaffCoverageID' => $gibbonStaffCoverageID];
        $sql = "SELECT gibbonStaffCoverage.gibbonStaffCoverageID, gibbonStaffCoverage.status, gibbonStaffAbsence.gibbonStaffAbsenceID, gibbonStaffAbsenceType.name as type, gibbonStaffAbsence.reason, substituteTypes,
                MIN(date) as date, COUNT(*) as days, MIN(date) as dateStart, MAX(date) as dateEnd, MAX(allDay) as allDay, MIN(timeStart) as timeStart, MAX(timeEnd) as timeEnd, timestampStatus, timestampCoverage, gibbonStaffCoverage.requestType,
                gibbonStaffCoverage.notesCoverage, gibbonStaffCoverage.notesStatus, 0 as urgent, gibbonStaffAbsence.comment, gibbonStaffAbsence.notificationSent, gibbonStaffAbsence.gibbonGroupID, gibbonStaffAbsence.gibbonPersonIDApproval, gibbonStaffCoverage.notificationList as notificationListCoverage, gibbonStaffAbsence.notificationList as notificationListAbsence, 
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
                JOIN gibbonTTDayRowClass ON (gibbonStaffCoverageDate.foreignTable='gibbonTTDayRowClass' AND gibbonTTDayRowClass.gibbonTTDayRowClassID=gibbonStaffCoverageDate.foreignTableID)
                JOIN gibbonTTDayDate ON (gibbonTTDayDate.gibbonTTDayID=gibbonTTDayRowClass.gibbonTTDayID AND gibbonTTDayDate.date=gibbonStaffCoverageDate.date)
                JOIN gibbonTTColumnRow ON (gibbonTTColumnRow.gibbonTTColumnRowID=gibbonTTDayRowClass.gibbonTTColumnRowID)
                JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonStaffCoverage.gibbonPersonID AND gibbonCourseClassPerson.gibbonCourseClassID=gibbonTTDayRowClass.gibbonCourseClassID)
                JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID)
                JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID)

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

    public function selectCoverageCountsByPerson($gibbonPersonID, $date = null)
    {
        $gibbonPersonIDCoverage = is_array($gibbonPersonID)? implode(',', $gibbonPersonID) : $gibbonPersonID;

        $data = ['gibbonPersonIDCoverage' => $gibbonPersonIDCoverage, 'today' => $date ?? date('Y-m-d')];
        $sql = "SELECT gibbonStaffCoverage.gibbonPersonIDCoverage, COUNT(DISTINCT gibbonStaffCoverageDate.gibbonStaffCoverageDateID) as totalCoverage, SUM(CASE WHEN gibbonStaffCoverageDate.date BETWEEN DATE_ADD(:today, INTERVAL(1-DAYOFWEEK(:today)) DAY) AND DATE_ADD(:today, INTERVAL(7-DAYOFWEEK(:today)) DAY) THEN 1 ELSE 0 END) as weekCoverage
                FROM gibbonStaffCoverage
                JOIN gibbonStaffCoverageDate ON (gibbonStaffCoverageDate.gibbonStaffCoverageID=gibbonStaffCoverage.gibbonStaffCoverageID)
                JOIN gibbonSchoolYear ON (gibbonStaffCoverage.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID)
                WHERE FIND_IN_SET(gibbonStaffCoverage.gibbonPersonIDCoverage, :gibbonPersonIDCoverage)
                AND gibbonStaffCoverage.status='Accepted'
                GROUP BY gibbonStaffCoverage.gibbonPersonIDCoverage";

        return $this->db()->select($sql, $data);
    }

    public function selectCoverageByAbsenceID($gibbonStaffAbsenceID, $grouped = false)
    {
        $data = ['gibbonStaffAbsenceID' => $gibbonStaffAbsenceID];
        $sql = "SELECT gibbonStaffCoverageID
                FROM gibbonStaffCoverage
                WHERE gibbonStaffCoverage.gibbonStaffAbsenceID = :gibbonStaffAbsenceID ";
        if ($grouped) {
            $sql .= " GROUP BY gibbonStaffCoverage.gibbonStaffAbsenceID ";
        }
        $sql .= " ORDER BY gibbonStaffCoverage.timestampStatus ASC";

        return $this->db()->select($sql, $data);
    }

    public function deleteCoverageByAbsenceID($gibbonStaffAbsenceID)
    {
        $data = ['gibbonStaffAbsenceID' => $gibbonStaffAbsenceID];
        $sql = "DELETE FROM gibbonStaffCoverage
                WHERE gibbonStaffCoverage.gibbonStaffAbsenceID = :gibbonStaffAbsenceID";

        return $this->db()->delete($sql, $data);
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
                    case 'Upcoming': return $query->where("gibbonStaffCoverageDate.date >= CURRENT_DATE()")->where("gibbonStaffCoverage.status <> 'Declined' AND gibbonStaffCoverage.status <> 'Cancelled'")->orderBy(['gibbonStaffCoverageDate.date']);
                    case 'Today'   : return $query->where("gibbonStaffCoverageDate.date = CURRENT_DATE()");
                    case 'Past'    : return $query->where("gibbonStaffCoverageDate.date < CURRENT_DATE()");
                    default: return $query->bindValue('dateFilter', $date)->where("gibbonStaffCoverageDate.date = :dateFilter");
                }
            },
        ];
    }
}
