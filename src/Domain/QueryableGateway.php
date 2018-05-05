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

use Gibbon\Database\Result;
use Gibbon\Domain\QueryResult;
use Gibbon\Domain\QueryFilters;
use Gibbon\Domain\Traits\TableAware;
use Gibbon\Contracts\Database\Connection;

use Aura\SqlQuery\Mysql\Select;
use Aura\SqlQuery\QueryFactory;

abstract class QueryableGateway extends Gateway
{
    use TableAware;

    private static $queryFactory;

    // BUILT-IN QUERIES
    protected function countAll()
    {
        return $this->db->selectOne("SELECT COUNT(*) FROM `{$this->getTableName()}`");
    }

    protected function newQuery()
    {
        return $this->getQueryFactory()->newSelect()->from($this->getTableName());
    }

    // QUERY-RELATED
    protected function runQuery(Select $query, QueryFilters $filters)
    {
        $query = $this->applyFilters($query, $filters);

        $result = $this->db->select($query->getStatement(), $query->getBindValues());

        $foundRows = $this->db->selectOne("SELECT FOUND_ROWS()");

        echo $query->getStatement();

        return QueryResult::createFromResult($result, $foundRows, $this->countAll(), $filters->pageIndex, $filters->pageSize);
    }

    private function applyFilters(Select $query, QueryFilters $filters)
    {
        $query->calcFoundRows();

        // Filter By
        foreach ($filters->filterBy as $filter) {
            list($name, $value) = explode(':', $filter, 2);
            if ($callback = $filters->getDefinition($name)) {
                $query = $callback($query, $value);
            }
        }

        // Search By
        $count = 0;
        foreach ($filters->searchBy as $column => $text) {
            $column = $this->escapeIdentifier($column);
            $query->orWhere("{$column} LIKE :search{$count}");
            $query->bindValue(":search{$count}", $text);
            $count++;
        }

        // Order By
        foreach ($filters->orderBy as $column => $direction) {
            // $column = $this->escapeIdentifier($column);
            $direction = (strtoupper($direction) == 'DESC') ? 'DESC' : 'ASC';
            $query->orderBy(["{$column} {$direction}"]);
        }

        // Limit & Offset
        $query->limit($filters->pageSize);
        $query->offset(max(0, $filters->pageIndex * $filters->pageSize));

        return $query;
    }

    private function escapeIdentifier($value)
    {
        return implode('.', array_map(function ($piece) {
            return '`' . str_replace('`', '``', $piece) . '`';
        }, explode('.', $value, 2)));
    }

    private function getQueryFactory()
    {
        if (!isset(self::$queryFactory)) {
            self::$queryFactory = new QueryFactory('mysql');
        }

        return self::$queryFactory;
    }
}