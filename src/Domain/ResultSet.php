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
 * Object representing the results of a filtered Gateway query.
 */
class ResultSet
{
    protected $filters;
    protected $data;

    protected $totalCount; 
    protected $resultCount; 
    protected $pageCount; 

    protected $rowsFrom; 
    protected $rowsTo; 

    public function __construct(QueryFilters $filters, array $data, $resultCount, $totalCount)
    {
        $this->filters = $filters;
        $this->data = $data;

        $this->totalCount = $totalCount;
        $this->resultCount = $resultCount;
        $this->pageCount = ceil($resultCount / $filters->pageSize);

        // echo $resultCount.' / '.$filters->pageSize.' = '.$this->pageCount;

        $this->rowsFrom = ($filters->pageIndex * $filters->pageSize + 1);
        $this->rowsTo = max(1, min( (($filters->pageIndex + 1) * $filters->pageSize), $resultCount));
    }

    public function __get($name)
    {
        return isset($this->$name)? $this->$name : '';
    }

    public static function createFromArray(QueryFilters $filters, array $data, $resultCount, $totalCount)
    {
        return new self($filters, $data, $resultCount, $totalCount);
    }

    public static function createEmpty(QueryFilters $filters)
    {
        return new self($filters, array(), 0, 0);
    }

    public function getFilters()
    {
        return $this->filters;
    }

    public function getData()
    {
        return $this->data;
    }

    public function hasResults()
    {
        return !empty($this->data);
    }

    public function isFiltered()
    {
        return count($this->filters->filterBy) > 0;
    }

    public function isSubset()
    {
        return $this->totalCount > 0 && ($this->totalCount != $this->resultCount);
    }

    public function updateResults(callable $callable)
    {
        array_walk($this->data, $callable);
    }
}