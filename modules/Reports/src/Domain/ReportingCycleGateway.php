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

namespace Gibbon\Module\Reports\Domain;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

class ReportingCycleGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonReportingCycle';
    private static $primaryKey = 'gibbonReportingCycleID';
    private static $searchableColumns = ['gibbonReportingCycle.name'];
    
    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryReportingCyclesBySchoolYear(QueryCriteria $criteria, $gibbonSchoolYearID, $currentOnly = false)
    {
        $query = $this
            ->newQuery()
            ->distinct()
            ->from($this->getTableName())
            ->cols(['gibbonReportingCycle.gibbonReportingCycleID', 'name', 'dateStart', 'dateEnd', 'cycleNumber', "(SELECT GROUP_CONCAT(gibbonYearGroup.nameShort ORDER BY gibbonYearGroup.sequenceNumber SEPARATOR ', ') FROM gibbonYearGroup WHERE FIND_IN_SET(gibbonYearGroup.gibbonYearGroupID, gibbonReportingCycle.gibbonYearGroupIDList)) as yearGroups"])
            ->where('gibbonReportingCycle.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID);

        if ($currentOnly) {
            $query->where(':today BETWEEN gibbonReportingCycle.dateStart AND gibbonReportingCycle.dateEnd')
                  ->bindValue('today', date('Y-m-d'));
        }

        return $this->runQuery($query, $criteria);
    }

    public function selectReportingCyclesBySchoolYear($gibbonSchoolYearID)
    {
        $data = ['gibbonSchoolYearID' => $gibbonSchoolYearID];
        $sql = "SELECT gibbonReportingCycleID as value, name 
                FROM gibbonReportingCycle 
                WHERE gibbonSchoolYearID=:gibbonSchoolYearID
                ORDER BY sequenceNumber, name";

        return $this->db()->select($sql, $data);
    }
}
