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

namespace Gibbon\Domain;

use Gibbon\sqlConnection;
use Gibbon\Domain\Model;
use Gibbon\Domain\Result;
use Gibbon\Domain\ResultSet;
use Gibbon\Domain\QueryFilters;

/**
 * Gateway
 *
 * @version v16
 * @since   v16
 */
abstract class Gateway
{
    protected $pdo;

    protected static $tableName = 'gibbonPerson';
    protected static $columns = array();

    public function __construct(sqlConnection $pdo)
    {
        if (empty(static::$tableName)) {
            throw new \Exception(get_called_class().' must define a $tableName');
        }

        $this->pdo = $pdo;

        $result = $this->doSelect("SELECT DISTINCT(column_name) FROM information_schema.columns WHERE table_name='".static::$tableName."'");
        static::$columns = ($result->rowCount() > 0)? $result->fetchAll(\PDO::FETCH_COLUMN, 0) : array();

        echo '<pre>';
        print_r(static::$columns);
        echo '</pre>';            
    }

    public function countAll()
    {
        return $this->doCount("SELECT COUNT(*) FROM `".static::$tableName."`");
    }

    // DATA MANIPULATION
    protected function doInsert($sql, $data = array())
    {
        $result = $this->pdo->executeQuery($data, $sql);

        return $this->pdo->getConnection()->lastInsertID();
    }

    protected function doUpdate($sql, $data = array())
    {
        $result = $this->pdo->executeQuery($data, $sql);

        return $result->rowCount();
    }

    protected function doDelete($sql, $data = array())
    {
        $result = $this->pdo->executeQuery($data, $sql);
        
        return $result->rowCount();
    }

    protected function doCopy($sql, $data = array())
    {
        $result = $this->pdo->executeQuery($data, $sql);
        
        return $result->rowCount();
    }

    // DATA QUERYING
    protected function doFetch($sql, $data = array())
    {
        return $this->pdo->executeQuery($data, $sql)->fetch();
    }

    protected function doCount($sql, $data = array())
    {
        return $this->pdo->executeQuery($data, $sql)->fetchColumn(0);
    }

    protected function doSelect($sql, $data = array())
    {
        return $this->pdo->executeQuery($data, $sql);
    }

    protected function doFilteredSelect($filters, $sql, $data = array())
    {
        $sql = $filters->applyFilters($sql);

        $result = $this->pdo->executeQuery($data, $sql);

        return ResultSet::createFromResults($filters, $result, $this->countAll());
    }

    // DATA OBJECT CREATION
    protected function doGet($sql, $data = array(), $model = Model::class, $args = array())
    {
        return $this->pdo->executeQuery($data, $sql)->fetchObject($model, $args);
    }
}