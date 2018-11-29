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
 *
 * User: craig
 * Date: 27/11/2018
 * Time: 22:27
 */
namespace Gibbon\Services;

use Gibbon\Database\Connection;

class SettingManager
{
    /**
     * @var Connection|null
     */
    private $connection;

    /**
     * @return Connection|null
     */
    public function getConnection(): Connection
    {
        return $this->connection;
    }

    /**
     * @param Connection $connection
     * @return SettingManager
     */
    public function setConnection(Connection $connection): SettingManager
    {
        $this->connection = $connection;
        return $this;
    }

    /**
     * SecurityManager constructor.
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->setConnection($connection);
    }

    /**
     * @param $connection2
     * @param $scope
     * @param $name
     * @param bool $returnRow
     * @return bool
     */
    function getSettingByScope($scope, $name, $returnRow = false )
    {
        try {
            $data = array('scope' => $scope, 'name' => $name);
            $sql = 'SELECT * FROM gibbonSetting WHERE scope=:scope AND name=:name';
            $result = $this->getConnection()->selectOne($sql, $data);
        } catch (\PDOException $e) {
        }

        if ($result && count($result) == 1) {

            if ($returnRow) {
                return $result;
            } else {
                return $result['value'];
            }
        }

        return false;
    }

    /**
     * @param $connection2
     * @param $scope
     * @param $name
     * @param bool $returnRow
     * @return bool
     */
    function getSettingByScopeAsArray($scope, $name): array
    {
        if ($result = $this->getSettingByScope($scope, $name) === false)
            return [];
        return explode(',', $result);
    }
}