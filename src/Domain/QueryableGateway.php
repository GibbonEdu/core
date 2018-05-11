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

/**
 * Queryable Gateway
 *
 * @version v16
 * @since   v16
 */
abstract class QueryableGateway extends Gateway
{
    use TableAware;

    /**
     * Internal QueryFactory.
     *
     * @var QueryFactory
     */
    private static $queryFactory;

    /**
     * Creates a new QueryCriteria instance.
     *
     * @param array $values
     * @return QueryCriteria
     */
    public function newQueryCriteria($values = [])
    {
        return new QueryCriteria($values);
    }

    /**
     * Creates a new instance of the Select class using the current database table.
     *
     * @return SelectInterface
     */
    protected function newQuery()
    {
        return $this->getQueryFactory()->newSelect()->from($this->getTableName());
    }

    /**
     * Runs a query with a defined set of criteria and returns the result as an object with pagination data.
     *
     * @param SelectInterface $query
     * @param QueryCriteria $criteria
     * @return QueryResult
     */
    protected function runQuery(SelectInterface $query, QueryCriteria $criteria)
    {
        $query = $this->applyCriteria($query, $criteria);

        $result = $this->db()->select($query->getStatement(), $query->getBindValues());

        $foundRows = $this->db()->selectOne("SELECT FOUND_ROWS()");
        $totalRows = $this->countAll();

        return QueryResult::createFromResult($result, $foundRows, $totalRows)->setPagination($criteria->page, $criteria->pageSize);
    }

    /**
     * Applies a set of criteria to an existing query and returns the resulting query.
     *
     * @param SelectInterface $query
     * @param QueryCriteria $criteria
     * @return SelectInterface
     */
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

        // Pagination
        $query->setPaging($criteria->pageSize);
        $query->page($criteria->page);

        return $query;
    }

    /**
     * Gets the internal QueryFactory. Lazy-loaded and static to maintain a single instance.
     *
     * @return QueryFactory
     */
    private function getQueryFactory()
    {
        if (!isset(self::$queryFactory)) {
            self::$queryFactory = new QueryFactory('mysql');
        }

        return self::$queryFactory;
    }
}