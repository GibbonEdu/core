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

namespace Gibbon\Domain\Planner;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * ResourceGateway
 *
 * @version v21
 * @since   v21
 */
class ResourceGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonResource';
    private static $primaryKey = 'gibbonResourceID';
    private static $searchableColumns = ['gibbonResource.name'];
    
    public function queryResources($criteria, $gibbonPersonID = null)
    {
        $query = $this
            ->newQuery()
            ->cols([
                'gibbonResource.*',
                'gibbonPerson.surname',
                'gibbonPerson.preferredName',
                'gibbonPerson.title',
                "GROUP_CONCAT(gibbonYearGroup.nameShort ORDER BY sequenceNumber SEPARATOR ', ') as yearGroupList",
                "COUNT(gibbonYearGroup.gibbonYearGroupID) as yearGroups",
                "(SELECT COUNT(*) FROM gibbonYearGroup) as totalYearGroups",
                ])
            ->from($this->getTableName())
            ->innerJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=gibbonResource.gibbonPersonID')
            ->leftJoin('gibbonYearGroup', 'FIND_IN_SET(gibbonYearGroup.gibbonYearGroupID, gibbonResource.gibbonYearGroupIDList)')
            ->groupBy(['gibbonResource.gibbonResourceID']);
          
        if (!empty($gibbonPersonID)) {
            $query
                ->where('gibbonResource.gibbonPersonID=:gibbonPersonID')
                ->bindValue('gibbonPersonID', $gibbonPersonID);
        }

        return $this->runQuery($query, $criteria);
    }
}
