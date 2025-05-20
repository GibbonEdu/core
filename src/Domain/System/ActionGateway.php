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

namespace Gibbon\Domain\System;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;
use Gibbon\Contracts\Database\Result;

/**
 * @version v16
 * @since   v16
 */
class ActionGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonAction';
    private static $primaryKey = 'gibbonActionID';

    private static $searchableColumns = ['name'];
    
    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryActions(QueryCriteria $criteria)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                '*'
            ]);

        return $this->runQuery($query, $criteria);
    }

    public function getFastFinderActions($gibbonRoleIDCurrent)
    {
        $data = ['gibbonRoleID' => $gibbonRoleIDCurrent];
        $sql = "SELECT DISTINCT concat(gibbonModule.name, '/', gibbonAction.entryURL) AS id, SUBSTRING_INDEX(gibbonAction.name, '_', 1) AS name, gibbonModule.type, gibbonModule.name AS module
                FROM gibbonModule
                JOIN gibbonAction ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID)
                JOIN gibbonPermission ON (gibbonPermission.gibbonActionID=gibbonAction.gibbonActionID)
                WHERE active='Y'
                AND menuShow='Y'
                AND gibbonPermission.gibbonRoleID=:gibbonRoleID
                ORDER BY name";

        $actions = $this->db()->select($sql, $data)->fetchAll();

        foreach ($actions as $index => $action) {
            $actions[$index]['name'] = __($action['name']);
        }

        $actions[] = ['name' => ''];

        return $actions;
    }

    /**
     * Check the specified role has access rights to the specified action.
     * Get the names of all actions available for the given role to the
     * specified module and route path.
     *
     * @param int          $gibbonRoleID      The role ID.
     * @param string       $moduleName        The module name.
     * @param string       $routePath         Route path of the entry point in the module.
     * @param string|null  $actionName        Specific action name string, or null if unspecified.
     *                                        Default: null.
     * @param bool         $activeModuleOnly  Only select the active modules or not.
     *                                        Default: true.
     *
     * @return Result
     */
    public function selectModuleActionsByRole(
        $gibbonRoleID,
        string $moduleName,
        string $routePath,
        ?string $actionName = null,
        bool $activeModuleOnly = true
    ): Result {
        $data = [
            'gibbonRoleID' => $gibbonRoleID,
            'moduleName' => $moduleName,
            'routePath' => '%'.$routePath.'.php%',
        ];
        $sql = 'SELECT gibbonAction.name as actionName
        FROM gibbonAction
        JOIN gibbonModule ON (gibbonModule.gibbonModuleID=gibbonAction.gibbonModuleID)
        JOIN gibbonPermission ON (gibbonAction.gibbonActionID=gibbonPermission.gibbonActionID)
        JOIN gibbonRole ON (gibbonPermission.gibbonRoleID=gibbonRole.gibbonRoleID)
        WHERE
            gibbonAction.URLList LIKE :routePath
            AND gibbonPermission.gibbonRoleID=:gibbonRoleID
            AND gibbonModule.name=:moduleName';

        if (!empty($actionName)) {
            $data['actionName'] = $actionName;
            $sql .= ' AND gibbonAction.name=:actionName';
        }

        if ($activeModuleOnly) {
            $data['moduleIsActive'] = 'Y';
            $sql .= ' AND gibbonModule.active=:moduleIsActive';
        }

        return $this->db()->select($sql, $data);
    }
    
    public function insertPermissionByAction($gibbonActionID, $gibbonRoleID)
    {
        $data = ['gibbonActionID' => $gibbonActionID, 'gibbonRoleID' => $gibbonRoleID];
        $sql = "INSERT INTO gibbonPermission SET gibbonActionID=:gibbonActionID, gibbonRoleID=:gibbonRoleID";
        
        return $this->db()->insert($sql, $data);
    }
    
    public function deletePermissionByAction($gibbonActionID)
    {
        $data = array('gibbonActionID' => $gibbonActionID);
        $sql = "DELETE FROM gibbonPermission WHERE gibbonActionID=:gibbonActionID";
        
        return $this->db()->delete($sql, $data);
    }
    
}
