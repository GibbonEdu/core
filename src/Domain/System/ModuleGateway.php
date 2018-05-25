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

namespace Gibbon\Domain\System;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * Module Gateway
 *
 * @version v16
 * @since   v16
 */
class ModuleGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonModule';

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
     * @return array
     */
    public function getAllModuleNames()
    {
        $sql = "SELECT name FROM gibbonModule";

        return $this->db()->select($sql)->fetchAll(\PDO::FETCH_COLUMN);
    }
}
