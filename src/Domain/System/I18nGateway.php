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
 * @version v17
 * @since   v17
 */
class I18nGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibboni18n';

    private static $searchableColumns = ['name'];
    
    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryI18n(QueryCriteria $criteria, $installed = 'Y')
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibboni18nID', 'name', 'code', 'active', 'version', 'systemDefault'
            ])
            ->where('installed = :installed')
            ->bindValue('installed', $installed);

        return $this->runQuery($query, $criteria);
    }

    public function selectActiveI18n()
    {
        $sql = "SELECT * FROM gibboni18n WHERE active='Y'";

        return $this->db()->select($sql);
    }

    public function getI18nByID($gibboni18nID)
    {
        $data = array('gibboni18nID' => $gibboni18nID);
        $sql = "SELECT * FROM gibboni18n WHERE gibboni18nID=:gibboni18nID";

        return $this->db()->selectOne($sql, $data);
    }

    public function updateI18nVersion($gibboni18nID, $installed, $version)
    {
        $data = array('gibboni18nID' => $gibboni18nID, 'installed' => $installed, 'version' => $version);
        $sql = "UPDATE gibboni18n SET installed=:installed, version=:version WHERE gibboni18nID=:gibboni18nID";

        return $this->db()->update($sql, $data);
    }
}
