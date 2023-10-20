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
    private static $primaryKey = 'gibbonRoleID';

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

        $criteria->addFilterRules([
            'category' => function ($query, $category) {
                return $query
                    ->where('gibbonRole.category = :category')
                    ->bindValue('category', $category);
            },
        ]);

        return $this->runQuery($query, $criteria);
    }

    public function queryUsersByRole(QueryCriteria $criteria, $gibbonRoleID)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonPerson.gibbonPersonID', "(CASE WHEN gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID THEN 'Y' ELSE 'N' END) AS primaryRole",
                'gibbonPerson.surname', 'gibbonPerson.preferredName', 'gibbonPerson.username', 'gibbonPerson.image_240', 'gibbonPerson.status', 'gibbonPerson.canLogin',
                "GROUP_CONCAT(allRoles.name ORDER BY allRoles.name SEPARATOR ',') as allRoles"
            ])
            ->innerJoin('gibbonPerson', 'gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID OR FIND_IN_SET(gibbonRole.gibbonRoleID, gibbonPerson.gibbonRoleIDAll)')
            ->leftJoin('gibbonRole as allRoles', 'FIND_IN_SET(allRoles.gibbonRoleID, gibbonPerson.gibbonRoleIDAll)')
            ->where('gibbonRole.gibbonRoleID=:gibbonRoleID')
            ->where("gibbonPerson.status='Full'")
            ->bindValue('gibbonRoleID', $gibbonRoleID)
            ->groupBy(['gibbonPerson.gibbonPersonID']);

        $criteria->addFilterRules([
            'status' => function ($query, $status) {
                return $query
                    ->where('gibbonPerson.status = :status')
                    ->bindValue('status', ucfirst($status));
            },
            'primaryRole' => function ($query, $primaryRole) {
                if (strtoupper($primaryRole) == 'Y') {
                    $query->where('gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID');
                } elseif (strtoupper($primaryRole) == 'N') {
                    $query->where('gibbonPerson.gibbonRoleIDPrimary<>gibbonRole.gibbonRoleID');
                }
                return $query;
            },
        ]);

        return $this->runQuery($query, $criteria);
    }

    public function selectUsersByAction($name)
    {
        $data = array('name' => $name);
        $sql = "SELECT DISTINCT gibbonPersonID, surname, preferredName
                FROM gibbonAction
                JOIN gibbonPermission ON (gibbonPermission.gibbonActionID=gibbonAction.gibbonActionID)
                JOIN gibbonRole ON (gibbonPermission.gibbonRoleID=gibbonRole.gibbonRoleID)
                JOIN gibbonPerson ON (FIND_IN_SET(gibbonRole.gibbonRoleID, gibbonPerson.gibbonRoleIDAll))
                WHERE
                    (gibbonAction.name=:name OR (gibbonAction.name LIKE CONCAT(:name,'\_%')))
                    AND gibbonPerson.status='Full'
                ORDER BY surname, preferredName";

        return $this->db()->select($sql, $data);
    }

    public function selectActionsByRole($gibbonRoleID)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonModule.name as moduleName', 'gibbonRole.gibbonRoleID', 'gibbonAction.name', 'gibbonAction.description',
            ])
            ->innerJoin('gibbonPermission', 'gibbonPermission.gibbonRoleID=gibbonRole.gibbonRoleID')
            ->innerJoin('gibbonAction', 'gibbonAction.gibbonActionID=gibbonPermission.gibbonActionID')
            ->innerJoin('gibbonModule', 'gibbonModule.gibbonModuleID=gibbonAction.gibbonModuleID')
            ->where('gibbonRole.gibbonRoleID=:gibbonRoleID')
            ->bindValue('gibbonRoleID', $gibbonRoleID)
            ->orderBy(['gibbonModule.name', 'gibbonAction.name']);

        return $this->runSelect($query);
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

    public function selectRoleListByIDs($gibbonRoleIDAll)
    {
        $data = ['gibbonRoleIDAll' => $gibbonRoleIDAll];
        $sql = "SELECT gibbonRoleID as `0`, name as `1`
                FROM gibbonRole
                WHERE FIND_IN_SET(gibbonRoleID, :gibbonRoleIDAll)";

        return $this->db()->select($sql, $data);
    }

    /**
     * Returns the category of the specified role
     *
     * @param int $gibbonRoleID
     *
     * @return string|false
     */
    public function getRoleCategory($gibbonRoleID)
    {
        $sql = 'SELECT category FROM gibbonRole WHERE gibbonRoleID=:gibbonRoleID';

        return $this->db()->selectOne($sql, ['gibbonRoleID' => $gibbonRoleID]);
    }

    public function getAvailableUserRoleByID($gibbonPersonID, $gibbonRoleID)
    {
        $data = ['gibbonPersonID' => $gibbonPersonID, 'gibbonRoleID' => $gibbonRoleID];
        $sql = "SELECT gibbonRole.*
                FROM gibbonPerson 
                JOIN gibbonRole ON (FIND_IN_SET(gibbonRole.gibbonRoleID, gibbonPerson.gibbonRoleIDAll))
                WHERE (gibbonPerson.gibbonPersonID=:gibbonPersonID) 
                AND gibbonRole.gibbonRoleID=:gibbonRoleID";

        return $this->db()->selectOne($sql, $data);
    }
}
