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
use Aura\SqlQuery\QueryFactory;
use Aura\SqlQuery\Common\SelectInterface;

abstract class QueryableGateway extends Gateway
{
    use TableAware;

    private static $queryFactory;

    public function newQueryCriteria($values = [])
    {
        return new QueryCriteria($values);
    }

    protected function newQuery()
    {
        return $this->getQueryFactory()->newSelect()->from($this->getTableName());
    }

    protected function runQuery(SelectInterface $query, QueryCriteria $criteria)
    {
        $query = $this->applyCriteria($query, $criteria);

        $result = $this->db->select($query->getStatement(), $query->getBindValues());

        $foundRows = $this->db->selectOne("SELECT FOUND_ROWS()");
        $totalRows = $this->db->selectOne("SELECT COUNT(*) FROM `{$this->getTableName()}`");
        
        return new QueryResult($result->fetchAll(), $criteria->toArray(), $foundRows, $totalRows);
    }

    private function applyCriteria(SelectInterface $query, QueryCriteria $criteria)
    {
        $query->calcFoundRows();

        // Filter By
        foreach ($criteria->filterBy as $name) {
            list($name, $value) = array_pad(explode(':', $name, 2), 2, '');
            if ($callback = $criteria->getFilter($name)) {
                $query = $callback($query, $value);
            }
        }

        // Search By
        $query->where(function($query) use ($criteria) {
            $count = 0;
            foreach ($criteria->searchBy as $column => $text) {
                $query->orWhere("{$column} LIKE :search{$count}");
                $query->bindValue(":search{$count}", "%$text%");
                $count++;
            }
        });
        
        // Sort By
        foreach ($criteria->sortBy as $column => $direction) {
            $query->orderBy(["{$column} {$direction}"]);
        }

        // Limit & Offset
        $query->setPaging($criteria->pageSize);
        $query->page($criteria->page);

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