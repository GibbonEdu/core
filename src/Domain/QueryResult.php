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

/**
 * Object representing the paginated results of a Gateway query.
 */
class QueryResult implements \Countable, \IteratorAggregate
{
    protected $data;

    protected $totalCount; 
    protected $resultCount; 
    protected $pageIndex; 
    protected $pageSize; 

    public function __construct(array $data = [], $resultCount = 0, $totalCount = 0, $pageIndex = 0, $pageSize = -1)
    {
        $this->data = $data;
        $this->resultCount = $resultCount;
        $this->totalCount = $totalCount;
        $this->pageIndex = $pageIndex;
        $this->pageSize = $pageSize;
    }

    public static function createFromResult(Result $result, $resultCount, $totalCount, $pageIndex = 0, $pageSize = -1)
    {
        return new self($result->fetchAll(), $resultCount, $totalCount, $pageIndex, $pageSize);
    }

    public function count()
    {
        return count($this->data);
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->data);
    }

    public function getColumn($column)
    {
        return array_column($this->data, $column);
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

    public function getPageIndex()
    {
        return $this->pageIndex;
    }

    public function getPageSize()
    {
        return $this->pageSize;
    }

    public function getPageCount()
    {
        return ceil($this->resultCount / $this->pageSize);
    }

    public function getPageLowerBounds()
    {
        return ($this->pageIndex * $this->pageSize + 1);
    }

    public function getPageUpperBounds()
    {
        return max(1, min( (($this->pageIndex + 1) * $this->pageSize), $this->resultCount));
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