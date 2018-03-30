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
use Gibbon\Domain\ResultSet;
use Gibbon\Domain\QueryFilters;

abstract class QueryableGateway extends Gateway
{
    protected static $tableName;

    protected static $columns;

    public function __construct(sqlConnection $pdo)
    {
        if (empty(static::$tableName)) {
            throw new \Exception(get_called_class().' must define a $tableName');
        }

        parent::__construct($pdo);
    }

    // BUILT-IN QUERIES
    public function countAll()
    {
        return $this->doCount("SELECT COUNT(*) FROM `".static::$tableName."`");
    }

    public function foundRows()
    {
        return $this->doCount("SELECT FOUND_ROWS()");
    }

    // QUERY-RELATED
    protected function doFilteredQuery(QueryFilters $filters, $sql, $data = array())
    {
        $sql = $filters->applyFilters($sql, $data);

        $result = $this->pdo->executeQuery($data, $sql);

        if ($this->pdo->getQuerySuccess()) {
            return ResultSet::createFromArray($result->fetchAll(), $this->foundRows(), $this->countAll(), $filters->pageIndex, $filters->pageSize);
        } else {
            return ResultSet::createEmpty();
        }
    }

    // SCHEMA-RELATED
    protected function getColumns()
    {
        if (!isset(static::$columns)) {
            $result = $this->doSelect("SELECT DISTINCT(column_name), data_type FROM information_schema.columns WHERE table_name='".static::$tableName."'");
            static::$columns = ($result->rowCount() > 0)? $result->fetchAll(\PDO::FETCH_KEY_PAIR) : array();       
        }

        return static::$columns;
    }
}