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

use Gibbon\Contracts\Database\Connection;
use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryResult;
use Aura\SqlQuery\Common\SelectInterface;
use Aura\SqlQuery\QueryFactory;

abstract class QueryableGateway extends Gateway
{
    use TableAware;

    private static $queryFactory;

    public function newQueryCriteria()
    {
        return new QueryCriteria();
    }

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
    protected function runQuery(SelectInterface $query, QueryCriteria $filters)
    {
        $query = $this->applyFilters($query, $filters);

        $result = $this->db->select($query->getStatement(), $query->getBindValues());

        $foundRows = $this->db->selectOne("SELECT FOUND_ROWS()");

        echo '<pre>';
        echo $query->getStatement();
        echo '</pre>';
        
        return QueryResult::createFromResult($result, $foundRows, $this->countAll(), $filters->pageIndex, $filters->pageSize);
    }

    private function applyFilters(SelectInterface $query, QueryCriteria $filters)
    {
        $query->calcFoundRows();

        // Filter By
        foreach ($filters->filterBy as $name => $value) {
            if ($callback = $filters->getDefinition($name)) {
                $query = $callback($query, $value);
            }
        }

        // Search By
        $count = 0;
        $query->where('(');
        foreach ($filters->searchBy as $column => $text) {
            $query->orWhere("{$column} LIKE :search{$count}");
            $query->bindValue(":search{$count}", $text);
            $count++;
        }
        $query->where(')');

        // Order By
        foreach ($filters->orderBy as $column => $direction) {
            $query->orderBy(["{$column} {$direction}"]);
        }

        // Limit & Offset
        
        $query->setPaging($filters->pageSize);
        $query->page($filters->pageIndex+1);

        // $query->limit($filters->pageSize);
        // $query->offset(max(0, $filters->pageIndex * $filters->pageSize));

        return $query;
    }

    private function getQueryFactory()
    {
        if (!isset(self::$queryFactory)) {
            self::$queryFactory = new QueryFactory('mysql');
        }

        return self::$queryFactory;
    }
}