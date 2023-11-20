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

use Gibbon\Contracts\Database\Result;
use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * Module Gateway
 *
 * @version v25
 * @since   v16
 */
class ModuleGateway extends QueryableGateway
{
    use TableAware;

    /**
     * Table name used by TableAware trait.
     *
     * @var string
     */
    private static $tableName = 'gibbonModule';

    /**
     * Table primary key used by TableAware trait.
     *
     * @var string
     */
    private static $primaryKey = 'gibbonModuleID';

    /**
     * Searchable columns used by TableAware trait.
     *
     * @var string
     */
    private static $searchableColumns = ['name'];

    /**
     * Queries the list for the Manage Modules page.
     *
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryModules(QueryCriteria $criteria)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonModuleID', 'name', 'description', 'type', 'author', 'url', 'active', 'version'
            ]);

        $criteria->addFilterRules([
            'type' => function ($query, $type) {
                return $query
                    ->where('gibbonModule.type = :type')
                    ->bindValue('type', ucfirst($type));
            },

            'active' => function ($query, $active) {
                return $query
                    ->where('gibbonModule.active = :active')
                    ->bindValue('active', ucfirst($active));
            },
        ]);

        return $this->runQuery($query, $criteria);
    }

    /**
     * Gets an unfiltered list of all modules.
     *
     * @version v16
     * @since   v16
     *
     * @return string[]
     */
    public function getAllModuleNames()
    {
        $sql = "SELECT name FROM gibbonModule";

        return $this->db()->select($sql)->fetchAll(\PDO::FETCH_COLUMN);
    }

   /**
     * The modules by role.
     *
     * @version v16
     * @since   v16
     *
     * @param string $gibbonRoleID
     *
     * @return Result
     */
    public function selectModulesByRole($gibbonRoleID)
    {
        $mainMenuCategoryOrder = $this->db()->selectOne("SELECT value FROM gibbonSetting WHERE scope='System' AND name='mainMenuCategoryOrder'");

        $data = array('gibbonRoleID' => $gibbonRoleID, 'menuOrder' => $mainMenuCategoryOrder);
        $sql = "SELECT gibbonModule.category, gibbonModule.name, gibbonModule.type, gibbonModule.entryURL, gibbonAction.entryURL as alternateEntryURL, (CASE WHEN gibbonModule.type <> 'Core' THEN gibbonModule.name ELSE NULL END) as textDomain
                FROM gibbonModule
                JOIN gibbonAction ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID)
                JOIN gibbonPermission ON (gibbonPermission.gibbonActionID=gibbonAction.gibbonActionID)
                WHERE gibbonModule.active='Y'
                AND gibbonAction.menuShow='Y'
                AND gibbonPermission.gibbonRoleID=:gibbonRoleID
                GROUP BY gibbonModule.name
                ORDER BY FIND_IN_SET(gibbonModule.category, :menuOrder), gibbonModule.category, gibbonModule.name, gibbonAction.name";

        return $this->db()->select($sql, $data);
    }

    /**
     * The module actions by role.
     *
     * @version v16
     * @since   v16
     *
     * @param string $gibbonRoleID
     * @param string $gibbonModuleID
     *
     * @return Result
     */
    public function selectModuleActionsByRole($gibbonRoleID, $gibbonModuleID)
    {
        $data = array('gibbonModuleID' => $gibbonRoleID, 'gibbonRoleID' => $gibbonModuleID);
        $sql = "SELECT gibbonAction.category, gibbonModule.entryURL AS moduleEntry, gibbonModule.name AS moduleName, gibbonAction.name as actionName, gibbonModule.type, gibbonAction.precedence, gibbonAction.entryURL, URLList, SUBSTRING_INDEX(gibbonAction.name, '_', 1) as name, (CASE WHEN gibbonModule.type <> 'Core' THEN gibbonModule.name ELSE NULL END) AS textDomain
                FROM gibbonModule
                JOIN gibbonAction ON (gibbonModule.gibbonModuleID=gibbonAction.gibbonModuleID)
                JOIN gibbonPermission ON (gibbonAction.gibbonActionID=gibbonPermission.gibbonActionID)
                WHERE (gibbonModule.gibbonModuleID=:gibbonModuleID)
                AND (gibbonPermission.gibbonRoleID=:gibbonRoleID)
                AND NOT gibbonAction.entryURL=''
                AND gibbonAction.menuShow='Y'
                GROUP BY name
                ORDER BY gibbonModule.name, gibbonAction.category, gibbonAction.name, precedence DESC";

        return $this->db()->select($sql, $data);
    }

    /**
     * A list of additional (non-core) modules.
     *
     * @version v25
     * @since   v25
     *
     * @param string $gibbonRoleID
     * @param string $gibbonModuleID
     * @return array
     */
    public function getActiveAdditional(): array
    {
        $select = $this->newSelect()
            ->from($this->getTableName())
            ->cols($this->getSearchableColumns())
            ->where('active="Y"')
            ->where('type="Additional"');
        return $this->runSelect($select)->fetchAll();
    }
}
