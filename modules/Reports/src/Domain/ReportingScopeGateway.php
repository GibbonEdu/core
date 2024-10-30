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

class ReportingScopeGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonReportingScope';
    private static $primaryKey = 'gibbonReportingScopeID';
    private static $searchableColumns = ['gibbonReportingScope.name'];
    
    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryReportingScopesByCycle(QueryCriteria $criteria, $gibbonReportingCycleID)
    {
        $query = $this
            ->newQuery()
            ->distinct()
            ->from($this->getTableName())
            ->cols(['gibbonReportingScope.gibbonReportingScopeID', 'gibbonReportingScope.name', 'gibbonReportingScope.scopeType', "GROUP_CONCAT(DISTINCT gibbonRole.name ORDER BY gibbonRole.name SEPARATOR ', ') as accessRoles"])
            ->leftJoin('gibbonReportingAccess', 'FIND_IN_SET(gibbonReportingScope.gibbonReportingScopeID, gibbonReportingAccess.gibbonReportingScopeIDList)')
            ->leftJoin('gibbonRole', 'FIND_IN_SET(gibbonRole.gibbonRoleID, gibbonReportingAccess.gibbonRoleIDList)')
            ->where('gibbonReportingScope.gibbonReportingCycleID=:gibbonReportingCycleID')
            ->bindValue('gibbonReportingCycleID', $gibbonReportingCycleID)
            ->groupBy(['gibbonReportingScope.gibbonReportingScopeID']);

        return $this->runQuery($query, $criteria);
    }

    public function selectReportingScopesBySchoolYear($gibbonSchoolYearID)
    {
        $data = ['gibbonSchoolYearID' => $gibbonSchoolYearID];
        $sql = "SELECT gibbonReportingScope.gibbonReportingCycleID as chained, gibbonReportingScope.gibbonReportingScopeID as value, gibbonReportingScope.name 
                FROM gibbonReportingScope 
                JOIN gibbonReportingCycle ON (gibbonReportingCycle.gibbonReportingCycleID=gibbonReportingScope.gibbonReportingCycleID)
                WHERE gibbonReportingCycle.gibbonSchoolYearID=:gibbonSchoolYearID
                ORDER BY gibbonReportingCycle.sequenceNumber, gibbonReportingScope.sequenceNumber";

        return $this->db()->select($sql, $data);
    }

    public function selectRelatedReportingScopesByID($gibbonReportingScopeID, $scopeType, $scopeTypeID)
    {
        $query = $this
            ->newQuery()
            ->from('gibbonReportingScope as currentScope')
            ->cols(['gibbonReportingCycle.gibbonReportingCycleID', 'gibbonReportingScope.gibbonReportingScopeID', 'gibbonReportingCycle.name', 'gibbonReportingCycle.nameShort'])
            ->innerJoin('gibbonReportingCycle as currentCycle', 'currentScope.gibbonReportingCycleID=currentCycle.gibbonReportingCycleID')
            ->innerJoin('gibbonYearGroup', 'FIND_IN_SET(gibbonYearGroup.gibbonYearGroupID, currentCycle.gibbonYearGroupIDList)')
            ->innerJoin('gibbonReportingCycle', 'FIND_IN_SET(gibbonYearGroup.gibbonYearGroupID, gibbonReportingCycle.gibbonYearGroupIDList)')
            ->innerJoin('gibbonReportingScope', 'gibbonReportingScope.gibbonReportingCycleID=gibbonReportingCycle.gibbonReportingCycleID')
            ->innerJoin('gibbonReportingCriteria', 'gibbonReportingCriteria.gibbonReportingScopeID=gibbonReportingScope.gibbonReportingScopeID')
            ->where('currentScope.gibbonReportingScopeID=:gibbonReportingScopeID')
            ->where('currentScope.scopeType=gibbonReportingScope.scopeType')
            ->bindValue('gibbonReportingScopeID', $gibbonReportingScopeID)
            ->groupBy(['gibbonReportingCycle.gibbonReportingCycleID'])
            ->orderBy(['gibbonReportingCycle.sequenceNumber']);

        if ($scopeType == 'Year Group') {
            $query->where('gibbonReportingCriteria.gibbonYearGroupID=:scopeTypeID')
                  ->bindValue('scopeTypeID', $scopeTypeID);
        } elseif ($scopeType == 'Form Group') {
            $query->where('gibbonReportingCriteria.gibbonFormGroupID=:scopeTypeID')
                  ->bindValue('scopeTypeID', $scopeTypeID);
        } elseif ($scopeType == 'Course') {
            $query->innerJoin('gibbonCourseClass', 'gibbonCourseClass.gibbonCourseID=gibbonReportingCriteria.gibbonCourseID')
                  ->where('gibbonCourseClass.gibbonCourseClassID=:scopeTypeID')
                  ->bindValue('scopeTypeID', $scopeTypeID);
        }

        return $this->runSelect($query);
    }
}
