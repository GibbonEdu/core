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
 * Immutable object representing the results of a filtered Gateway query.
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

    public function __construct(ResultFilters $filters, array $data, $totalCount)
    {
        $this->filters = $filters;
        $this->data = $data;

        $this->totalCount = $totalCount;
        $this->resultCount = count($this->data);
        $this->pageCount = ceil($totalCount / $filters->pageSize) - 1;

        $this->rowsFrom = ($filters->pageIndex * $filters->pageSize + 1);
        $this->rowsTo = max(1, min( (($filters->pageIndex + 1) * $filters->pageSize), $totalCount));
        
    }

    public function __get($name)
    {
        return isset($this->$name)? $this->$name : '';
    }

    public static function createFromArray(ResultFilters $filters, array $data, $totalCount)
    {
        return new ResultSet($filters, $data, $totalCount);
    }

    public static function createFromResults(ResultFilters $filters, \PDOStatement $results, $totalCount)
    {
        return new ResultSet($filters, $results->fetchAll(), $totalCount);
    }

    public static function createEmpty(ResultFilters $filters)
    {
        return new ResultSet($filters, array(), 0);
    }

    public function getFilters()
    {
        return $this->filters;
    }

    public function getData()
    {
        return $this->data;
    }
}