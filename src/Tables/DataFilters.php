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

namespace Gibbon\Tables;

/**
 * Immutable? Data Filters for SQL Queries
 */
class DataFilters
{
    protected $filters = array(
        'page'      => 0,
        'limit'     => 25,
        'sort'      => 'name',
        'direction' => 'ASC',
        'totalRows' => 0,
    );

    public function __construct($filters = array())
    {
        $this->filters = $this->sanitizeFilters(array_replace($this->filters, $filters));
    }

    public function __get($name)
    {
        return isset($this->filters[$name])? $this->filters[$name] : null;
    }

    public function __isset($name)
    {
        return isset($this->filters[$name]);
    }

    public static function createFromArray($filters)
    {
        return new DataFilters($filters);
    }

    public static function createFromJson($json)
    {
        return new DataFilters(json_decode($json));
    }

    public function toArray()
    {
        return $this->filters;
    }

    public function toJson()
    {
        return json_encode($this->filters);
    }

    protected function sanitizeFilters($filters)
    {
        $filters['limit'] = max(1, intval($filters['limit']));

        $filters['page'] = intval($filters['page']);
        $filters['pageMax'] = ceil($filters['totalRows'] / $filters['limit']) - 1;

        $filters['direction'] = $filters['direction'] == 'DESC'? 'DESC' : 'ASC';

        return $filters;
    }

}