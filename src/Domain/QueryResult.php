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

/**
 * Object representing the paginated results of a Gateway query.
 */
class QueryResult implements \Countable, \IteratorAggregate
{
    protected $data;
    protected $criteria;
    
    protected $resultCount; 
    protected $totalCount; 

    public function __construct(array $data, QueryCriteria $criteria, $resultCount = 0, $totalCount = 0)
    {
        $this->data = $data;
        $this->criteria = $criteria;
        $this->resultCount = $resultCount;
        $this->totalCount = $totalCount;
    }

    /**
     * Implements \Countable, allowing the data set to be counted.
     *
     * @return int
     */
    public function count()
    {
        return count($this->data);
    }

    /**
     * Implements IteratorAggregate, allowing this object to be looped over in a foreach.
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->data);
    }

    /**
     * Get the criteria used to query for this result set.
     *
     * @return \QueryCriteria
     */
    public function getCriteria()
    {
        return $this->criteria;
    }

    /**
     * This result is a subset of the whole table if searches or filters have been applied.
     * Ignores paginated row counts and looks at the total results vs the total table size.
     *
     * @return bool
     */
    public function isSubset()
    {
        return $this->totalCount > 0 && ($this->totalCount != $this->resultCount);
    }

    /**
     * The total number of rows in the table being queried, regardless of criteria.
     *
     * @return int
     */
    public function getTotalCount()
    {
        return $this->totalCount;
    }

    /**
     * The total un-paginated number of rows for this query. 
     * Will be less than the $totalCount if the results have criteria applied.
     *
     * @return int
     */
    public function getResultCount()
    {
        return $this->resultCount;
    }

    /**
     * The current page number, counting from 1.
     *
     * @return int
     */
    public function getPage()
    {
        return $this->criteria->page;
    }

    /**
     * The number of rows per page in this result set.
     *
     * @return int
     */
    public function getPageSize()
    {
        return $this->criteria->pageSize;
    }

    /**
     * The total number of pages in this result set.
     *
     * @return int
     */
    public function getPageCount()
    {
        return ceil($this->resultCount / $this->criteria->pageSize);
    }

    /**
     * The row number for the lower bounds of the current page.
     *
     * @return int
     */
    public function getPageFrom()
    {
        return (($this->criteria->page-1) * $this->criteria->pageSize + 1);
    }

    /**
     * The row number for the upper bounds of the current page.
     *
     * @return int
     */
    public function getPageTo()
    {
        return max(1, min( ($this->criteria->page * $this->criteria->pageSize), $this->resultCount));
    }

    /**
     * Extract a single column from the result set as an array.
     *
     * @param string $columnName
     * @return void
     */
    public function getColumn($columnName)
    {
        return array_column($this->data, $columnName);
    }

    /**
     * Joins a column of data to the result set based on a common key in both data.
     *
     * @param string $keyName
     * @param string $columnName
     * @param array $columnData
     * @return void
     */
    public function joinColumn($keyName, $columnName, &$columnData)
    {
        array_walk($this->data, function(&$item) use ($keyName, $columnName, &$columnData){
            $key = $item[$keyName];
            $item[$columnName] = isset($columnData[$key])? $columnData[$key] : array();
            return $item;
        });
    }
}