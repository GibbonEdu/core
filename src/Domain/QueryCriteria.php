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

use Closure;

/**
 * Object describing the filters applied to a Gateway query.
 */
class QueryCriteria
{
    protected $criteria = array(
        'pageIndex' => 0,
        'pageSize' => 25,
        'searchBy' => array(),
        'filterBy' => array(),
        'orderBy' => array(),
    );

    protected $definitions = array();

    public function __construct(array $criteria = array())
    {
        $this->criteria = $this->sanitizeFilters(array_replace($this->criteria, $criteria));
    }

    public function __get($name)
    {
        return isset($this->criteria[$name]) ? $this->criteria[$name] : null;
    }

    public function __isset($name)
    {
        return isset($this->criteria[$name]);
    }

    public function fromArray(array $criteria)
    {
        $this->criteria = $this->sanitizeFilters(array_replace($this->criteria, $criteria));

        return $this;
    }

    public function fromJson($jsonString)
    {
        return $this->fromArray(json_decode($jsonString, true));
    }

    public function toArray()
    {
        return $this->criteria;
    }

    public function toJson()
    {
        return json_encode($this->criteria);
    }

    public function defineFilter($name, Closure $callback)
    {
        $this->definitions[$name] = $callback;

        return $this;
    }

    public function getDefinition($name)
    {
        return isset($this->definitions[$name]) ? $this->definitions[$name] : null;
    }

    public function searchBy($column, $search)
    {
        if (trim($search) == '') return $this;

        $columns = is_array($column) ? $column : array($column);
        $columns = array_map([$this, 'escapeIdentifier'], $columns);

        foreach ($columns as $column) {
            $this->criteria['searchBy'][$column] = trim($search);
        }

        return $this;
    }

    public function filterBy($filter)
    {
        if (empty($filter)) return $this;

        list($name, $value) = array_pad(explode(':', $filter, 2), 2, '');

        $this->criteria['filterBy'][$name] = trim($value, '"');

        return $this;
    }

    public function sortBy($column, $direction = 'ASC')
    {
        if (empty($column)) return $this;

        $this->criteria['orderBy'][$column] = (strtoupper($direction) == 'DESC') ? 'DESC' : 'ASC';

        return $this;
    }

    protected function sanitizeFilters($filters)
    {
        return array(
            'pageIndex' => intval($filters['pageIndex']),
            'pageSize' => intval($filters['pageSize']),
            'searchBy' => is_array($filters['searchBy']) ? $filters['searchBy'] : array(),
            'filterBy' => is_array($filters['filterBy']) ? $filters['filterBy'] : array(),
            'orderBy' => is_array($filters['orderBy']) ? $filters['orderBy'] : array(),
        );
    }

    protected function escapeIdentifier($value)
    {
        return implode('.', array_map(function ($piece) {
            return '`' . str_replace('`', '``', $piece) . '`';
        }, explode('.', $value, 2)));
    }
}