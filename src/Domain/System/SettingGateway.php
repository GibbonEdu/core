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

    /**
     * Name for database table to use.
     * Referenced by the TableAware trait.
     *
     * @var string
     */
    private static $tableName = 'gibbonSetting';

    /**
     * Primary key for the database table.
     * Referenced by the TableAware trait.
     *
     * @var string
     */
    private static $primaryKey = 'gibbonSettingID';

    /**
     * Searchable columns in the database table.
     * Referenced by the TableAware trait.
     *
     * @var string[]
     */
    private static $searchableColumns = ['scope', 'name'];

    /**
     * Get settings by the scope and the setting name.
     *
     * @param string  $scope       The scope of setting.
     * @param string  $name        The key of the specific setting.
     * @param boolean $returnRow   Should this operation return entire
     *                             Result or just a single value.
     *                             Default: false.
     *
     * @return string|array|false
     *     Result of the setting query. Either returns:
     *     (a) A single string value of the given setting.
     *     (b) A result row, if $returnRow is set to true.
     *     (c) Boolean false ff the setting is not found.
     */
    public function getSettingByScope($scope, $name, $returnRow = false)
    {
        $data = ['scope' => $scope, 'name' => $name];
        $sql = $returnRow
            ? "SELECT * FROM gibbonSetting WHERE scope=:scope AND name=:name"
            : "SELECT value FROM gibbonSetting WHERE scope=:scope AND name=:name";

        return $this->db()->selectOne($sql, $data);
    }

    /**
     * Getting all the settings of the specific scope.
     *
     * @param string $scope
     *
     * @return array
     */
    public function getAllSettingsByScope($scope)
    {
        $data = ['scope' => $scope];
        $sql = "SELECT * FROM gibbonSetting WHERE scope=:scope ORDER BY name";

        return $this->db()->select($sql, $data)->fetchAll();
    }

    /**
     * Update a setting by the scope and setting name.
     *
     * @param string $scope  The scope of the setting.
     * @param string $name   The key of the specific setting.
     * @param string $value  The setting value.
     *
     * @return bool  True, if the database operation is successful, or false.
     */
    public function updateSettingByScope($scope, $name, $value)
    {
        $data = ['scope' => $scope, 'name' => $name, 'value' => $value];
        $sql = "UPDATE gibbonSetting SET value=:value WHERE scope=:scope AND name=:name";

        return $this->db()->update($sql, $data);
    }
}
