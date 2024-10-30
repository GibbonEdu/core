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

namespace Gibbon\Domain\Planner;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * OutcomeGateway
 *
 * @version v24
 * @since   v24
 */
class OutcomeGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonOutcome';
    private static $primaryKey = 'gibbonOutcomeID';
    private static $searchableColumns = ['gibbonOutcome.name'];

    public function queryOutcomes($criteria, $gibbonDepartmentID = null)
    {
        $query = $this
            ->newQuery()
            ->cols([
                'gibbonOutcome.*',
                "GROUP_CONCAT(gibbonYearGroup.nameShort ORDER BY gibbonYearGroup.sequenceNumber SEPARATOR ', ') AS yearGroupList",
                "COUNT(gibbonYearGroup.gibbonYearGroupID) AS yearGroups",
                "(SELECT COUNT(*) FROM gibbonYearGroup) AS totalYearGroups",
                "gibbonDepartment.name AS department"
                ])
            ->from($this->getTableName())
            ->leftJoin('gibbonDepartment', 'gibbonDepartment.gibbonDepartmentID=gibbonOutcome.gibbonDepartmentID')
            ->leftJoin('gibbonYearGroup', 'FIND_IN_SET(gibbonYearGroup.gibbonYearGroupID, gibbonOutcome.gibbonYearGroupIDList)')
            ->groupBy(['gibbonOutcome.gibbonOutcomeID']);

        if (!empty($gibbonDepartmentID)) {
            $query
                ->where('gibbonOutcome.gibbonDepartmentID=:gibbonDepartmentID')
                ->bindValue('gibbonDepartmentID', $gibbonDepartmentID);
        }

        return $this->runQuery($query, $criteria);
    }
}
