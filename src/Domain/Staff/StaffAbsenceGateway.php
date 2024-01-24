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
use Gibbon\Domain\ScrubbableGateway;
use Gibbon\Domain\Traits\Scrubbable;
use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\Traits\ScrubByPerson;

/**
 * Staff Absence Gateway
 *
 * @version v18
 * @since   v18
 */
class StaffAbsenceGateway extends QueryableGateway implements ScrubbableGateway
{
    use TableAware;
    use Scrubbable;
    use ScrubByPerson;
    
    private static $tableName = 'gibbonStaffAbsence';
    private static $primaryKey = 'gibbonStaffAbsenceID';

    private static $searchableColumns = ['gibbonStaffAbsence.reason', 'gibbonStaffAbsence.comment', 'gibbonStaffAbsence.status', 'gibbonStaffAbsenceType.name', 'gibbonPerson.preferredName', 'gibbonPerson.surname'];

    private static $scrubbableKey = 'gibbonPersonID';
    private static $scrubbableColumns = ['commentConfidential' => ''];

    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryAbsencesBySchoolYear($criteria, $gibbonSchoolYearID, $grouped = true)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonStaffAbsence.*', 'gibbonStaffAbsenceDate.*', 'gibbonStaffAbsenceType.name as type', 'gibbonPerson.gibbonPersonID', 'gibbonPerson.title', 'gibbonPerson.preferredName', 'gibbonPerson.surname', 'creator.preferredName AS preferredNameCreator', 'creator.surname AS surnameCreator'
            ])
            ->innerJoin('gibbonStaffAbsenceType', 'gibbonStaffAbsence.gibbonStaffAbsenceTypeID=gibbonStaffAbsenceType.gibbonStaffAbsenceTypeID')
            ->innerJoin('gibbonStaffAbsenceDate', 'gibbonStaffAbsenceDate.gibbonStaffAbsenceID=gibbonStaffAbsence.gibbonStaffAbsenceID')
            ->innerJoin('gibbonSchoolYear', '((gibbonStaffAbsenceDate.date BETWEEN firstDay AND lastDay) OR (gibbonStaffAbsence.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID))')
            // ->leftJoin('gibbonStaffCoverageDate', 'gibbonStaffCoverageDate.gibbonStaffAbsenceDateID=gibbonStaffAbsenceDate.gibbonStaffAbsenceDateID')
            // ->leftJoin('gibbonStaffCoverage', 'gibbonStaffCoverage.gibbonStaffCoverageID=gibbonStaffCoverageDate.gibbonStaffCoverageID')
            ->leftJoin('gibbonPerson', 'gibbonStaffAbsence.gibbonPersonID=gibbonPerson.gibbonPersonID')
            ->leftJoin('gibbonPerson AS creator', 'gibbonStaffAbsence.gibbonPersonIDCreator=creator.gibbonPersonID')
            ->where('gibbonSchoolYear.gibbonSchoolYearID = :gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID);

        if ($grouped) {
            $query->cols(['COUNT(DISTINCT gibbonStaffAbsenceDate.date) as days', 'MIN(gibbonStaffAbsenceDate.date) as dateStart', 'MAX(gibbonStaffAbsenceDate.date) as dateEnd', 'SUM(gibbonStaffAbsenceDate.value) as value'])
                ->groupBy(['gibbonStaffAbsence.gibbonStaffAbsenceID']);
        } else {
            $query->cols(['1 as days', 'gibbonStaffAbsenceDate.date as dateStart', 'gibbonStaffAbsenceDate.date as dateEnd', 'gibbonStaffAbsenceDate.value as value'])
                ->groupBy(['gibbonStaffAbsenceDate.gibbonStaffAbsenceDateID']);
        }

        $criteria->addFilterRules($this->getSharedFilterRules());

        return $this->runQuery($query, $criteria);
    }

    public function queryAbsencesByPerson(QueryCriteria $criteria, $gibbonPersonID, $grouped = true)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonStaffAbsence.gibbonStaffAbsenceID', 'gibbonStaffAbsence.gibbonPersonID', 'gibbonStaffAbsenceType.name as type', 'gibbonStaffAbsence.reason', 'comment', 'gibbonStaffAbsenceDate.date', 'gibbonStaffAbsenceDate.allDay', 'gibbonStaffAbsenceDate.timeStart', 'gibbonStaffAbsenceDate.timeEnd', 'timestampCreator', 'gibbonStaffAbsence.coverageRequired', 'gibbonStaffCoverage.status as coverage', 'gibbonStaffAbsence.status',
                'creator.title as titleCreator', 'creator.preferredName AS preferredNameCreator', 'creator.surname AS surnameCreator', 'gibbonStaffAbsence.gibbonPersonIDCreator',
                'coverage.title as titleCoverage', 'coverage.preferredName as preferredNameCoverage', 'coverage.surname as surnameCoverage', 'gibbonStaffCoverage.gibbonPersonIDCoverage', 'gibbonStaffCoverage.gibbonStaffCoverageID', 'gibbonStaffCoverageDate.foreignTableID AS gibbonTTDayRowClassID'
            ])
            ->innerJoin('gibbonStaffAbsenceType', 'gibbonStaffAbsence.gibbonStaffAbsenceTypeID=gibbonStaffAbsenceType.gibbonStaffAbsenceTypeID')
            ->innerJoin('gibbonStaffAbsenceDate', 'gibbonStaffAbsenceDate.gibbonStaffAbsenceID=gibbonStaffAbsence.gibbonStaffAbsenceID')
            ->leftJoin('gibbonStaffCoverageDate', 'gibbonStaffCoverageDate.gibbonStaffAbsenceDateID=gibbonStaffAbsenceDate.gibbonStaffAbsenceDateID')
            ->leftJoin('gibbonStaffCoverage', 'gibbonStaffCoverage.gibbonStaffCoverageID=gibbonStaffCoverageDate.gibbonStaffCoverageID')
            ->leftJoin('gibbonPerson AS creator', 'gibbonStaffAbsence.gibbonPersonIDCreator=creator.gibbonPersonID')
            ->leftJoin('gibbonPerson AS coverage', 'gibbonStaffCoverage.gibbonPersonIDCoverage=coverage.gibbonPersonID')
            ->where('gibbonStaffAbsence.gibbonPersonID = :gibbonPersonID')
            ->bindValue('gibbonPersonID', $gibbonPersonID);

        if ($grouped === true) {
            $query->cols(['COUNT(DISTINCT gibbonStaffAbsenceDate.date) as days', 'MIN(gibbonStaffAbsenceDate.date) as dateStart', 'MAX(gibbonStaffAbsenceDate.date) as dateEnd', 'SUM(gibbonStaffAbsenceDate.value) as value'])
                ->groupBy(['gibbonStaffAbsence.gibbonStaffAbsenceID']);
        } elseif ($grouped === 'coverage') {
            $query->cols(['COUNT(DISTINCT gibbonStaffAbsenceDate.date) as days', 'MIN(gibbonStaffAbsenceDate.date) as dateStart', 'MAX(gibbonStaffAbsenceDate.date) as dateEnd', 'SUM(gibbonStaffAbsenceDate.value) as value'])
                ->groupBy(['gibbonStaffAbsence.gibbonStaffAbsenceID', 'gibbonStaffCoverageDate.gibbonStaffCoverageDateID']);
        } else {
            $query->cols(['1 as days', 'gibbonStaffAbsenceDate.date as dateStart', 'gibbonStaffAbsenceDate.date as dateEnd', 'gibbonStaffAbsenceDate.value as value'])
                ->groupBy(['gibbonStaffAbsenceDate.gibbonStaffAbsenceDateID']);
        }

        $criteria->addFilterRules($this->getSharedFilterRules());

        $criteria->addFilterRules([
            'schoolYear' => function ($query, $gibbonSchoolYearID) {
                return $query
                    ->where('gibbonStaffAbsence.gibbonSchoolYearID = :gibbonSchoolYearID')
                    ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID);
            }
        ]);

        return $this->runQuery($query, $criteria);
    }

    public function queryAbsencesByApprover(QueryCriteria $criteria, $gibbonPersonIDApproval)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonStaffAbsence.gibbonStaffAbsenceID', 'gibbonStaffAbsenceType.name as type', 'gibbonStaffAbsence.reason', 'comment', 'gibbonStaffAbsenceDate.date', 'COUNT(DISTINCT gibbonStaffAbsenceDate.date) as days', 'MIN(gibbonStaffAbsenceDate.date) as dateStart', 'MAX(gibbonStaffAbsenceDate.date) as dateEnd', 'gibbonStaffAbsenceDate.allDay', 'gibbonStaffAbsenceDate.timeStart', 'gibbonStaffAbsenceDate.timeEnd', 'SUM(gibbonStaffAbsenceDate.value) as value', 'timestampCreator', 'gibbonPerson.gibbonPersonID', 'gibbonPerson.title', 'gibbonPerson.preferredName', 'gibbonPerson.surname', 'gibbonStaffAbsence.gibbonPersonIDCreator', 'creator.preferredName AS preferredNameCreator', 'creator.surname AS surnameCreator', 'gibbonStaffCoverage.status as coverage', 'gibbonStaffAbsence.status', 'gibbonStaffAbsence.coverageRequired', 'gibbonStaffCoverageDate.foreignTable', 'gibbonStaffCoverageDate.foreignTableID'
            ])
            ->innerJoin('gibbonStaffAbsenceType', 'gibbonStaffAbsence.gibbonStaffAbsenceTypeID=gibbonStaffAbsenceType.gibbonStaffAbsenceTypeID')
            ->innerJoin('gibbonStaffAbsenceDate', 'gibbonStaffAbsenceDate.gibbonStaffAbsenceID=gibbonStaffAbsence.gibbonStaffAbsenceID')
            ->leftJoin('gibbonStaffCoverageDate', 'gibbonStaffCoverageDate.gibbonStaffAbsenceDateID=gibbonStaffAbsenceDate.gibbonStaffAbsenceDateID')
            ->leftJoin('gibbonStaffCoverage', 'gibbonStaffCoverage.gibbonStaffCoverageID=gibbonStaffCoverageDate.gibbonStaffCoverageID')
            ->leftJoin('gibbonPerson', 'gibbonStaffAbsence.gibbonPersonID=gibbonPerson.gibbonPersonID')
            ->leftJoin('gibbonPerson AS creator', 'gibbonStaffAbsence.gibbonPersonIDCreator=creator.gibbonPersonID')
            ->where('gibbonStaffAbsence.gibbonPersonIDApproval = :gibbonPersonIDApproval')
            ->bindValue('gibbonPersonIDApproval', $gibbonPersonIDApproval)
            ->groupBy(['gibbonStaffAbsence.gibbonStaffAbsenceID']);

        $criteria->addFilterRules($this->getSharedFilterRules());

        return $this->runQuery($query, $criteria);
    }

    public function queryApprovedAbsencesByDateRange(QueryCriteria $criteria, $dateStart, $dateEnd = null, $grouped = true)
    {
        if (empty($dateEnd)) $dateEnd = $dateStart;
        
        $query = $this
            ->newQuery()
            ->from('gibbonStaffAbsenceDate')
            ->cols([
                'gibbonStaffAbsence.gibbonStaffAbsenceID', 'gibbonStaffAbsence.gibbonPersonID', 'gibbonStaffAbsenceType.name as type', 'gibbonStaffAbsence.reason', 'comment', 'gibbonStaffAbsenceDate.date',  'gibbonStaffAbsenceDate.allDay', 'gibbonStaffAbsenceDate.timeStart', 'gibbonStaffAbsenceDate.timeEnd', 'gibbonStaffAbsenceDate.value', 'timestampCreator',  'MIN(gibbonStaffCoverage.status) as coverage',
                'gibbonStaffAbsence.status',
                'gibbonPerson.title', 'gibbonPerson.preferredName', 'gibbonPerson.surname', 
                'creator.title AS titleCreator', 'creator.preferredName AS preferredNameCreator', 'creator.surname AS surnameCreator', 'gibbonStaffAbsence.gibbonPersonIDCreator',
                'coverage.title as titleCoverage', 'coverage.preferredName as preferredNameCoverage', 'coverage.surname as surnameCoverage', 'gibbonStaffCoverage.gibbonPersonIDCoverage',
            ])
            ->innerJoin('gibbonStaffAbsence', 'gibbonStaffAbsence.gibbonStaffAbsenceID=gibbonStaffAbsenceDate.gibbonStaffAbsenceID')
            ->innerJoin('gibbonStaffAbsenceType', 'gibbonStaffAbsence.gibbonStaffAbsenceTypeID=gibbonStaffAbsenceType.gibbonStaffAbsenceTypeID')
            ->leftJoin('gibbonStaffAbsenceDate AS dates', 'dates.gibbonStaffAbsenceID=gibbonStaffAbsence.gibbonStaffAbsenceID')
            ->leftJoin('gibbonStaffCoverageDate', 'gibbonStaffCoverageDate.gibbonStaffAbsenceDateID=gibbonStaffAbsenceDate.gibbonStaffAbsenceDateID')
            ->leftJoin('gibbonStaffCoverage', 'gibbonStaffCoverage.gibbonStaffCoverageID=gibbonStaffCoverageDate.gibbonStaffCoverageID')
            ->leftJoin('gibbonPerson', 'gibbonStaffAbsence.gibbonPersonID=gibbonPerson.gibbonPersonID')
            ->leftJoin('gibbonPerson AS creator', 'gibbonStaffAbsence.gibbonPersonIDCreator=creator.gibbonPersonID')
            ->leftJoin('gibbonPerson AS coverage', 'gibbonStaffCoverage.gibbonPersonIDCoverage=coverage.gibbonPersonID')
            ->where('gibbonStaffAbsenceDate.date BETWEEN :dateStart AND :dateEnd')
            ->where("gibbonStaffAbsence.status = 'Approved'")
            ->bindValue('dateStart', $dateStart)
            ->bindValue('dateEnd', $dateEnd)
            ->groupBy(['gibbonStaffAbsence.gibbonStaffAbsenceID', 'gibbonStaffCoverageDate.gibbonStaffCoverageDateID']);

        if ($grouped) {
            $query->cols(['COUNT(DISTINCT dates.gibbonStaffAbsenceDateID) as days', 'MIN(dates.date) as dateStart', 'MAX(dates.date) as dateEnd', 'SUM(value) as value'])
                ->groupBy(['gibbonStaffAbsence.gibbonStaffAbsenceID']);
        } else {
            $query->cols(['1 as days', 'gibbonStaffAbsenceDate.date as dateStart', 'gibbonStaffAbsenceDate.date as dateEnd', 'gibbonStaffAbsenceDate.value as value'])
                ->groupBy(['gibbonStaffAbsenceDate.gibbonStaffAbsenceDateID']);
        }

        if (!$criteria->hasFilter('all')) {
            $query->where('gibbonPerson.status = "Full"');
        }

        $criteria->addFilterRules($this->getSharedFilterRules());

        return $this->runQuery($query, $criteria);
    }

    public function getAbsenceDetailsByID($gibbonStaffAbsenceID)
    {
        $data = ['gibbonStaffAbsenceID' => $gibbonStaffAbsenceID];
        $sql = "SELECT gibbonStaffAbsence.gibbonStaffAbsenceID, gibbonStaffAbsence.gibbonStaffAbsenceID, gibbonStaffAbsenceType.name as type, gibbonStaffAbsenceType.sequenceNumber, gibbonStaffAbsence.gibbonStaffAbsenceTypeID, gibbonStaffAbsence.reason, gibbonStaffAbsence.comment, gibbonStaffAbsence.commentConfidential, gibbonStaffAbsence.coverageRequired,
                MIN(gibbonStaffAbsenceDate.date) as date, COUNT(DISTINCT gibbonStaffAbsenceDateID) as days, MIN(gibbonStaffAbsenceDate.date) as dateStart, MAX(gibbonStaffAbsenceDate.date) as dateEnd, MAX(gibbonStaffAbsenceDate.allDay) as allDay, MIN(gibbonStaffAbsenceDate.timeStart) as timeStart, MAX(gibbonStaffAbsenceDate.timeEnd) as timeEnd, 0 as urgent, gibbonStaffAbsenceDate.value as value,
                gibbonStaffAbsence.status, gibbonStaffAbsence.timestampApproval, gibbonStaffAbsence.notesApproval,
                gibbonPersonIDCreator, timestampCreator, timestampStatus, timestampCoverage, gibbonStaffAbsence.notificationList, gibbonStaffAbsence.notificationSent, gibbonStaffAbsence.gibbonGroupID, gibbonStaffAbsence.googleCalendarEventID,
                gibbonStaffCoverage.status as coverage, gibbonStaffCoverage.notesCoverage, gibbonStaffCoverage.notesStatus, 
                gibbonStaffAbsence.gibbonPersonID, absence.title AS titleAbsence, absence.preferredName AS preferredNameAbsence, absence.surname AS surnameAbsence, 
                gibbonStaffAbsence.gibbonPersonIDApproval, approval.title as titleApproval, approval.preferredName as preferredNameApproval, approval.surname as surnameApproval,
                gibbonStaffCoverage.gibbonPersonIDCoverage, coverage.title as titleCoverage, coverage.preferredName as preferredNameCoverage, coverage.surname as surnameCoverage
            FROM gibbonStaffAbsence 
            JOIN gibbonStaffAbsenceType ON (gibbonStaffAbsence.gibbonStaffAbsenceTypeID=gibbonStaffAbsenceType.gibbonStaffAbsenceTypeID)
            LEFT JOIN gibbonStaffAbsenceDate ON (gibbonStaffAbsenceDate.gibbonStaffAbsenceID=gibbonStaffAbsence.gibbonStaffAbsenceID)
            LEFT JOIN gibbonStaffCoverage ON (gibbonStaffCoverage.gibbonStaffAbsenceID=gibbonStaffAbsence.gibbonStaffAbsenceID)
            LEFT JOIN gibbonPerson AS absence ON (gibbonStaffAbsence.gibbonPersonID=absence.gibbonPersonID)
            LEFT JOIN gibbonPerson AS coverage ON (gibbonStaffCoverage.gibbonPersonIDCoverage=coverage.gibbonPersonID)
            LEFT JOIN gibbonPerson AS approval ON (gibbonStaffAbsence.gibbonPersonIDApproval=approval.gibbonPersonID)
            WHERE gibbonStaffAbsence.gibbonStaffAbsenceID=:gibbonStaffAbsenceID
            GROUP BY gibbonStaffAbsence.gibbonStaffAbsenceID
            ";

        return $this->db()->selectOne($sql, $data);
    }

    public function getMostRecentAbsenceByPerson($gibbonPersonID)
    {
        $data = ['gibbonPersonID' => $gibbonPersonID];
        $sql = "SELECT * 
                FROM gibbonStaffAbsence 
                WHERE gibbonStaffAbsence.gibbonPersonID=:gibbonPersonID
                ORDER BY timestampCreator DESC
                LIMIT 1";

        return $this->db()->selectOne($sql, $data);
    }

    public function getMostRecentApproverByPerson($gibbonPersonID)
    {
        $data = ['gibbonPersonID' => $gibbonPersonID];
        $sql = "SELECT gibbonPersonIDApproval 
                FROM gibbonStaffAbsence 
                WHERE gibbonStaffAbsence.gibbonPersonID=:gibbonPersonID
                AND gibbonPersonIDApproval IS NOT NULL
                ORDER BY timestampCreator DESC
                LIMIT 1";

        return $this->db()->selectOne($sql, $data);
    }

    protected function getSharedFilterRules()
    {
        return [
            'type' => function ($query, $type) {
                return $query
                    ->where('gibbonStaffAbsence.gibbonStaffAbsenceTypeID = :type')
                    ->bindValue('type', $type);
            },
            'status' => function ($query, $status) {
                return $query->where('gibbonStaffAbsence.status = :status')
                             ->bindValue('status', ucwords($status));
            },
            'coverage' => function ($query, $coverage) {
                return $query->where('gibbonStaffCoverage.status = :coverage')
                             ->bindValue('coverage', $coverage);
            },
            'dateStart' => function ($query, $dateStart) {
                return $query->where("gibbonStaffAbsenceDate.date >= :dateStart")
                             ->bindValue('dateStart', $dateStart);
            },
            'dateEnd' => function ($query, $dateEnd) {
                return $query->where("gibbonStaffAbsenceDate.date <= :dateEnd")
                             ->bindValue('dateEnd', $dateEnd);
            },
            'date' => function ($query, $date) {
                switch (ucfirst($date)) {
                    case 'Upcoming': return $query->where("gibbonStaffAbsenceDate.date >= CURRENT_DATE()");
                    case 'Today'   : return $query->where("gibbonStaffAbsenceDate.date = CURRENT_DATE()");
                    case 'Past'    : return $query->where("gibbonStaffAbsenceDate.date < CURRENT_DATE()");
                }
            },
        ];
    }
}
