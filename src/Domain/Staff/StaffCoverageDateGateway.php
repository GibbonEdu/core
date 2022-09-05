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
 * Staff Coverage Date Gateway
 *
 * @version v18
 * @since   v18
 */
class StaffCoverageDateGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonStaffCoverageDate';
    private static $primaryKey = 'gibbonStaffCoverageDateID';

    private static $searchableColumns = [''];

    public function selectDatesByCoverage($gibbonStaffCoverageID)
    {
        $gibbonStaffCoverageIDList = is_array($gibbonStaffCoverageID)? $gibbonStaffCoverageID : [$gibbonStaffCoverageID];
        $data = ['gibbonStaffCoverageIDList' => implode(',', $gibbonStaffCoverageIDList) ];
        $sql = "SELECT gibbonStaffCoverageDate.gibbonStaffCoverageID as groupBy, gibbonStaffCoverageDate.*, gibbonStaffCoverage.status as coverage, coverage.title as titleCoverage, coverage.preferredName as preferredNameCoverage, coverage.surname as surnameCoverage, coverage.gibbonPersonID as gibbonPersonIDCoverage
                FROM gibbonStaffCoverageDate
                LEFT JOIN gibbonStaffCoverage ON (gibbonStaffCoverage.gibbonStaffCoverageID=gibbonStaffCoverageDate.gibbonStaffCoverageID)
                LEFT JOIN gibbonPerson AS coverage ON (gibbonStaffCoverage.gibbonPersonIDCoverage=coverage.gibbonPersonID)
                WHERE FIND_IN_SET(gibbonStaffCoverageDate.gibbonStaffCoverageID, :gibbonStaffCoverageIDList)
                ORDER BY gibbonStaffCoverageDate.date";

        return $this->db()->select($sql, $data);
    }

    public function deleteCoverageDatesByAbsenceID($gibbonStaffAbsenceID)
    {
        $data = ['gibbonStaffAbsenceID' => $gibbonStaffAbsenceID];
        $sql = "DELETE gibbonStaffCoverageDate FROM gibbonStaffCoverageDate
                JOIN gibbonStaffAbsenceDate ON (gibbonStaffAbsenceDate.gibbonStaffAbsenceDateID=gibbonStaffCoverageDate.gibbonStaffAbsenceDateID)
                WHERE gibbonStaffAbsenceDate.gibbonStaffAbsenceID = :gibbonStaffAbsenceID";

        return $this->db()->delete($sql, $data);
    }
}
