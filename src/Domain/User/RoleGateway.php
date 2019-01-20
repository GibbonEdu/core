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

namespace Gibbon\Domain\User;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * @version v16
 * @since   v16
 */
class RoleGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonRole';

    private static $searchableColumns = ['name', 'nameShort'];
    
    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryRoles(QueryCriteria $criteria)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonRoleID', 'name', 'nameShort', 'category', 'description', 'type', 'canLoginRole', 'futureYearsLogin', 'pastYearsLogin'
            ]);

        return $this->runQuery($query, $criteria);
    }

    public function getRoleByID($gibbonRoleID)
    {
        $data = array('gibbonRoleID' => $gibbonRoleID);
        $sql = "SELECT * FROM gibbonRole WHERE gibbonRoleID=:gibbonRoleID";

        return $this->db()->selectOne($sql, $data);
    }

    public function selectAllRolesByPerson($gibbonPersonID)
    {
        $data = array('gibbonPersonID' => $gibbonPersonID);
        $sql = "SELECT gibbonRoleID AS groupBy, gibbonRole.* 
                FROM gibbonPerson 
                JOIN gibbonRole ON (FIND_IN_SET(gibbonRole.gibbonRoleID, gibbonPerson.gibbonRoleIDAll))
                WHERE gibbonPersonID=:gibbonPersonID";

        return $this->db()->select($sql, $data);
    }
}
