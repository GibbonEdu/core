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
 * Setting Gateway
 *
 * @version v17
 * @since   v17
 */
class SettingGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonSetting';

    private static $searchableColumns = ['scope', 'name'];
    
    public function getSettingByScope($scope, $name, $returnRow = false)
    {
        $data = ['scope' => $scope, 'name' => $name];
        $sql = $returnRow
            ? "SELECT * FROM gibbonSetting WHERE scope=:scope AND name=:name"
            : "SELECT value FROM gibbonSetting WHERE scope=:scope AND name=:name";

        return $this->db()->selectOne($sql, $data);
    }

    public function getAllSettingsByScope($scope)
    {
        $data = ['scope' => $scope];
        $sql = "SELECT * FROM gibbonSetting WHERE scope=:scope ORDER BY name";

        return $this->db()->select($sql, $data)->fetchAll();
    }

    public function updateSettingByScope($scope, $name, $value)
    {
        $data = ['scope' => $scope, 'name' => $name, 'value' => $value];
        $sql = "UPDATE gibbonSetting SET value=:value WHERE scope=:scope AND name=:name";

        return $this->db()->update($sql, $data);
    }
}
