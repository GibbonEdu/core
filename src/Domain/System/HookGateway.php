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

/**
 * Hook Gateway
 *
 * @version v20
 * @since   v20
 */
class HookGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonHook';
    private static $primaryKey = 'gibbonHookID';

    public function selectHooksByType($type)
    {
        $data = ['type' => $type];
        $sql = "SELECT gibbonHook.name as groupBy, gibbonHook.* 
                FROM gibbonHook 
                JOIN gibbonModule ON (gibbonModule.gibbonModuleID=gibbonHook.gibbonModuleID)
                WHERE gibbonHook.type=:type 
                AND gibbonModule.active='Y'
                ORDER BY gibbonHook.name";

        return $this->db()->select($sql, $data);
    }

    public function getHookPermission($gibbonHookID, $gibbonRoleIDCurrent, $moduleName, $actionName)
    {
        $data = ['gibbonHookID' => $gibbonHookID, 'gibbonRoleIDCurrent' => $gibbonRoleIDCurrent, 'sourceModuleName' => $moduleName, 'sourceModuleAction' => $actionName];
        $sql = "SELECT gibbonHook.name, gibbonModule.name AS module, gibbonAction.name AS action
            FROM gibbonHook
            JOIN gibbonModule ON (gibbonHook.gibbonModuleID=gibbonModule.gibbonModuleID)
            JOIN gibbonAction ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID)
            JOIN gibbonPermission ON (gibbonPermission.gibbonActionID=gibbonAction.gibbonActionID)
            WHERE gibbonModule.name=:sourceModuleName
            AND FIND_IN_SET(gibbonAction.name, :sourceModuleAction)
            AND gibbonPermission.gibbonRoleID=:gibbonRoleIDCurrent
            AND gibbonAction.gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE name=:sourceModuleName)
            AND gibbonHook.gibbonHookID=:gibbonHookID 
            ORDER BY name";

        return $this->db()->selectOne($sql, $data);
    }

    public function getAccessibleHooksByType(string $type, string $gibbonRoleIDCurrent)
    {
        $hooksAvailable = $this->selectHooksByType($type)->fetchAll();
        $hooks = [];

        foreach ($hooksAvailable as $hook) {
            $options = unserialize($hook['options']);

            //Check for permission to hook
            $hookPermission = $this->getHookPermission($hook['gibbonHookID'], $gibbonRoleIDCurrent, $options['sourceModuleName'] ?? '', $options['sourceModuleAction'] ?? '');

            if (!empty($options) && !empty($hookPermission)) {
                $hooks[] = [
                    'name'                => $hook['name'],
                    'sourceModuleName'    => $hookPermission['module'],
                    'sourceModuleInclude' => $options['sourceModuleInclude'],
                ];
            }
        }

        return $hooks;
    }
}
