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

use Gibbon\Domain\QueryCriteria;
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
    public function newQueryCriteria()
    {
        return new QueryCriteria();
    }

    /**
     * Creates a new instance of the Select class.
     *
     * @return SelectInterface
     */
    protected function newQuery()
    {
        return $this->getQueryFactory()->newSelect()->calcFoundRows();
    }

    /**
     * Runs a query with a defined set of criteria and returns the result as a data set with pagination info.
     *
     * @param SelectInterface $query
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    protected function runQuery(SelectInterface $query, QueryCriteria $criteria)
    {
        $query = $this->applyCriteria($query, $criteria);

        $result = $this->db()->select($query->getStatement(), $query->getBindValues());

        $foundRows = $this->db()->selectOne("SELECT FOUND_ROWS()");
        $totalRows = $this->countAll();

        return $result->toDataSet()->setResultCount($foundRows, $totalRows)->setPagination($criteria->getPage(), $criteria->getPageSize());
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
        $criteria->addFilterRules($this->getDefaultFilterRules($criteria));

        // Filter By
        if ($criteria->hasFilter()) {
            foreach ($criteria->getFilterBy() as $name => $value) {
                if ($callback = $criteria->getFilterRule($name)) {
                    $callback($query, $value);
                }
            }
        }

        // Search By
        if ($criteria->hasSearchColumn() && $criteria->hasSearchText()) {
            $searchable = $this->getSearchableColumns();

            $query->where(function($query) use ($criteria, $searchable) {
                $searchText = $criteria->getSearchText();
                foreach ($criteria->getSearchColumns() as $count => $column) {
                    if (!in_array($column, $searchable)) continue;

                    $column = $this->escapeIdentifier($column);
                    $query->orWhere("{$column} LIKE :search{$count}");
                    $query->bindValue(":search{$count}", "%{$searchText}%");
                }
            });
        }
        
        // Sort By
        if ($criteria->hasSort()) {
            foreach ($criteria->getSortBy() as $column => $direction) {
                $column = $this->escapeIdentifier($column);
                $query->orderBy(["{$column} {$direction}"]);
            }
        }

        // Pagination
        $query->setPaging($criteria->getPageSize());
        $query->page($criteria->getPage());

        return $query;
    }

    /**
     * Returns a set of built-in rules available to all queryable gateways.
     *
     * @return array
     */
    protected function getDefaultFilterRules(QueryCriteria $criteria)
    {
        return [
            'in' => function ($query, $columnName) use (&$criteria) {
                if (in_array($columnName, $this->getSearchableColumns())) {
                    $criteria->searchBy($columnName);
                } else {
                    $criteria->fromArray(['filterBy' => []]);
                }
                return $query;
            },
        ];
    }

    /**
     * The total count of all queryable rows. Commonly provided through the TableAware trait.
     *
     * @return int
     */
    protected abstract function countAll();

    /**
     * The column names that are valid when searching. Commonly provided through the TableAware trait.
     *
     * @return array
     */
    protected abstract function getSearchableColumns();

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

    /**
     * Wraps all SQL identifiers in ` backticks, escaping existing backticks; handles tableName.columnName
     *
     * @param string $value
     * @return string
     */
    private function escapeIdentifier($value)
    {
        return implode('.', array_map(function ($piece) {
            return '`' . str_replace('`', '``', $piece) . '`';
        }, explode('.', $value, 2)));
    }
}
