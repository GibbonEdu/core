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

namespace Gibbon\Domain\Rubrics;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * @version v17
 * @since   v17
 */
class RubricGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonRubric';
    private static $searchableColumns = ['gibbonRubric.name', 'gibbonRubric.category'];
    
    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryRubrics(QueryCriteria $criteria, $active = null, $gibbonYearGroupID = null)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonRubricID', 'gibbonRubric.scope', 'gibbonRubric.category', 'gibbonRubric.name', 'gibbonRubric.description', 'gibbonRubric.active', 'gibbonRubric.gibbonDepartmentID', 'gibbonDepartment.name AS learningArea', 
                "GROUP_CONCAT(DISTINCT gibbonYearGroup.nameShort ORDER BY gibbonYearGroup.sequenceNumber SEPARATOR ', ') as yearGroups",
                "COUNT(DISTINCT gibbonYearGroup.gibbonYearGroupID) as yearGroupCount",
            ])
            ->leftJoin('gibbonDepartment', "gibbonRubric.scope = 'Learning Area' AND gibbonDepartment.gibbonDepartmentID=gibbonRubric.gibbonDepartmentID")
            ->leftJoin('gibbonYearGroup', 'FIND_IN_SET(gibbonYearGroup.gibbonYearGroupID, gibbonRubric.gibbonYearGroupIDList)')
            ->groupBy(['gibbonRubric.gibbonRubricID']);
            
        if (!empty($active)) {
            $query->where('gibbonRubric.active = :active')
                ->bindValue('active', $active);
        }

        if (!empty($gibbonYearGroupID)) {
            $query->where('FIND_IN_SET(:gibbonYearGroupID, gibbonRubric.gibbonYearGroupIDList)')
                ->bindValue('gibbonYearGroupID', $gibbonYearGroupID);
        }

        $criteria->addFilterRules([
            'department' => function ($query, $gibbonDepartmentID) {
                return $query
                    ->where('gibbonRubric.gibbonDepartmentID = :gibbonDepartmentID')
                    ->bindValue('gibbonDepartmentID', $gibbonDepartmentID);
            },
        ]);
        
        return $this->runQuery($query, $criteria);
    }
}
