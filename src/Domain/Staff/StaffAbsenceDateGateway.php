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
 * Staff Absence Date Gateway
 *
 * @version v18
 * @since   v18
 */
class StaffAbsenceDateGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonStaffAbsenceDate';
    private static $primaryKey = 'gibbonStaffAbsenceDateID';

    private static $searchableColumns = [];

    public function selectDatesByAbsence($gibbonStaffAbsenceID)
    {
        $gibbonStaffAbsenceIDList = is_array($gibbonStaffAbsenceID)? $gibbonStaffAbsenceID : [$gibbonStaffAbsenceID];
        $data = ['gibbonStaffAbsenceIDList' => implode(',', $gibbonStaffAbsenceIDList) ];
        $sql = "SELECT gibbonStaffAbsenceDate.gibbonStaffAbsenceID as groupBy, 
                gibbonStaffAbsenceDate.*, 
                gibbonStaffAbsenceDate.allDay, 
                gibbonStaffAbsenceDate.timeStart,
                gibbonStaffAbsenceDate.timeEnd, '' as coverage, '' as titleCoverage, '' as preferredNameCoverage, '' as surnameCoverage, '' as gibbonPersonIDCoverage, '' as gibbonStaffCoverageID, '' as notes, '' as gibbonTTDayRowClassID
            FROM gibbonStaffAbsenceDate
            WHERE FIND_IN_SET(gibbonStaffAbsenceDate.gibbonStaffAbsenceID, :gibbonStaffAbsenceIDList)
            ORDER BY gibbonStaffAbsenceDate.date, gibbonStaffAbsenceDate.timeStart";

        return $this->db()->select($sql, $data);
    }

    public function selectDatesByAbsenceWithCoverage($gibbonStaffAbsenceID, $coverageOnly = false)
    {
        $gibbonStaffAbsenceIDList = is_array($gibbonStaffAbsenceID)? $gibbonStaffAbsenceID : [$gibbonStaffAbsenceID];
        $data = ['gibbonStaffAbsenceIDList' => implode(',', $gibbonStaffAbsenceIDList) ];
        $sql = "SELECT gibbonStaffAbsenceDate.gibbonStaffAbsenceID as groupBy, gibbonStaffAbsenceDate.*, 
        (CASE WHEN gibbonStaffCoverageDateID IS NOT NULL THEN gibbonStaffCoverageDate.allDay ELSE gibbonStaffAbsenceDate.allDay END) as allDay, 
        (CASE WHEN gibbonStaffCoverageDateID IS NOT NULL THEN gibbonStaffCoverageDate.timeStart ELSE gibbonStaffAbsenceDate.timeStart END) as timeStart,
        (CASE WHEN gibbonStaffCoverageDateID IS NOT NULL THEN gibbonStaffCoverageDate.timeEnd ELSE gibbonStaffAbsenceDate.timeEnd END) as timeEnd, gibbonStaffCoverage.requestType, gibbonStaffAbsence.status as absenceStatus,
        gibbonStaffCoverage.status as coverage, coverage.title as titleCoverage, coverage.preferredName as preferredNameCoverage, coverage.surname as surnameCoverage, coverage.gibbonPersonID as gibbonPersonIDCoverage, gibbonStaffCoverage.gibbonStaffCoverageID, gibbonStaffCoverageDate.reason as notes, gibbonStaffCoverageDate.gibbonStaffCoverageDateID, gibbonStaffCoverageDate.foreignTable, gibbonStaffCoverageDate.foreignTableID
                FROM gibbonStaffAbsenceDate
                LEFT JOIN gibbonStaffAbsence ON (gibbonStaffAbsence.gibbonStaffAbsenceID=gibbonStaffAbsenceDate.gibbonStaffAbsenceID)
                LEFT JOIN gibbonStaffCoverageDate ON (gibbonStaffCoverageDate.gibbonStaffAbsenceDateID=gibbonStaffAbsenceDate.gibbonStaffAbsenceDateID)
                LEFT JOIN gibbonStaffCoverage ON (gibbonStaffCoverage.gibbonStaffCoverageID=gibbonStaffCoverageDate.gibbonStaffCoverageID AND gibbonStaffCoverage.status <> 'Cancelled' AND gibbonStaffCoverage.status <> 'Declined')
                LEFT JOIN gibbonPerson AS coverage ON (gibbonStaffCoverage.gibbonPersonIDCoverage=coverage.gibbonPersonID)
                WHERE FIND_IN_SET(gibbonStaffAbsenceDate.gibbonStaffAbsenceID, :gibbonStaffAbsenceIDList) ";
               
        if ($coverageOnly) {
            $sql .= " AND gibbonStaffCoverage.gibbonStaffCoverageID IS NOT NULL";
        }
               
        $sql .= " ORDER BY gibbonStaffAbsenceDate.date, gibbonStaffAbsenceDate.timeStart, gibbonStaffCoverageDate.timeStart";

        return $this->db()->select($sql, $data);
    }

    public function selectApprovedAbsenceDatesByPerson($gibbonSchoolYearID, $gibbonPersonID)
    {
        $data = ['gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonPersonID' => $gibbonPersonID];
        $sql = "SELECT gibbonStaffAbsenceDate.date as groupBy, gibbonStaffAbsence.*, gibbonStaffAbsenceDate.*, gibbonStaffAbsenceType.name as type, gibbonStaffAbsenceType.sequenceNumber
                FROM gibbonStaffAbsence 
                JOIN gibbonStaffAbsenceDate ON (gibbonStaffAbsenceDate.gibbonStaffAbsenceID=gibbonStaffAbsence.gibbonStaffAbsenceID) 
                JOIN gibbonStaffAbsenceType ON (gibbonStaffAbsenceType.gibbonStaffAbsenceTypeID=gibbonStaffAbsence.gibbonStaffAbsenceTypeID)
                WHERE gibbonStaffAbsence.gibbonSchoolYearID=:gibbonSchoolYearID
                AND gibbonStaffAbsence.gibbonPersonID=:gibbonPersonID
                AND gibbonStaffAbsence.status='Approved'
                ORDER BY gibbonStaffAbsenceDate.date";

        return $this->db()->select($sql, $data);
    }

    public function getByAbsenceAndDate($gibbonStaffAbsenceID, $date)
    {
        $data = ['gibbonStaffAbsenceID' => $gibbonStaffAbsenceID, 'date' => $date ];
        $sql = "SELECT gibbonStaffAbsenceDate.gibbonStaffAbsenceID as groupBy, gibbonStaffAbsenceDate.*, gibbonStaffCoverage.status as coverage, coverage.title as titleCoverage, coverage.preferredName as preferredNameCoverage, coverage.surname as surnameCoverage, coverage.gibbonPersonID as gibbonPersonIDCoverage, gibbonStaffCoverage.gibbonStaffCoverageID
                FROM gibbonStaffAbsenceDate
                LEFT JOIN gibbonStaffCoverageDate ON (gibbonStaffCoverageDate.gibbonStaffAbsenceDateID=gibbonStaffAbsenceDate.gibbonStaffAbsenceDateID)
                LEFT JOIN gibbonStaffCoverage ON (gibbonStaffCoverage.gibbonStaffCoverageID=gibbonStaffCoverageDate.gibbonStaffCoverageID)
                LEFT JOIN gibbonPerson AS coverage ON (gibbonStaffCoverage.gibbonPersonIDCoverage=coverage.gibbonPersonID)
                WHERE gibbonStaffAbsenceDate.gibbonStaffAbsenceID=:gibbonStaffAbsenceID
                AND gibbonStaffAbsenceDate.date=:date";

        return $this->db()->selectOne($sql, $data);
    }
}
