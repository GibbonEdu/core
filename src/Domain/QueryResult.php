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

/**
 * Object representing the paginated results of a Gateway query.
 */
class QueryResult implements \Countable, \IteratorAggregate
{
    protected $data;
    protected $criteria;
    
    protected $resultCount; 
    protected $totalCount; 

    public function __construct(array $data, array $criteria, $resultCount = 0, $totalCount = 0)
    {
        $this->data = $data;
        $this->criteria = $criteria;
        $this->resultCount = $resultCount;
        $this->totalCount = $totalCount;
    }

    public function count()
    {
        return count($this->data);
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->data);
    }

    public function arrayColumn($column)
    {
        return array_column($this->data, $column);
    }

    public function getCriteria()
    {
        return $this->criteria;
    }

    public function hasResults()
    {
        return !empty($this->data);
    }

    public function isSubset()
    {
        return $this->totalCount > 0 && ($this->totalCount != $this->resultCount);
    }

    public function getTotalCount()
    {
        return $this->totalCount;
    }

    public function getResultCount()
    {
        return $this->resultCount;
    }

    public function getPage()
    {
        return $this->criteria['page'];
    }

    public function getPageSize()
    {
        return $this->criteria['pageSize'];
    }

    public function getPageCount()
    {
        return ceil($this->resultCount / $this->criteria['pageSize']);
    }

    public function getPageLowerBounds()
    {
        return (($this->criteria['page']-1) * $this->criteria['pageSize'] + 1);
    }

    public function getPageUpperBounds()
    {
        return max(1, min( ($this->criteria['page'] * $this->criteria['pageSize']), $this->resultCount));
    }

    public function joinResults($keyField, $joinField, &$joinData)
    {
        array_walk($this->data, function(&$item) use ($keyField, $joinField, &$joinData){
            $key = $item[$keyField];
            $item[$joinField] = isset($joinData[$key])? $joinData[$key] : array();
            return $item;
        });
    }
}