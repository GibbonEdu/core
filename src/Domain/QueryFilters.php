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
 * Immutable object describing the filters applied to a Gateway query.
 */
class QueryFilters
{
    protected $filters = array(
        'pageIndex'  => 0,
        'pageNumber' => 1,
        'pageSize'   => 25,
        'filterBy'   => array(),
        'orderBy'    => array(),
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
        return new QueryFilters($filters);
    }

    public static function createFromJson($json)
    {
        return new QueryFilters(json_decode($json));
    }

    public function toArray()
    {
        return $this->filters;
    }

    public function toJson()
    {
        return json_encode($this->filters);
    }

    public function applyFilters($sql)
    {
        if (!empty($this->orderBy)) {
            $sql .= ' ORDER BY ';

            $order = array();
            foreach ($this->orderBy as $column => $direction) {
                $order[] =  $column.' '.$direction;
            }

            $sql .= implode(', ', $order);
        }

        if (!empty($this->pageNumber)) {
            $page = $this->pageNumber - 1;
            $offset = max(0, $page * $this->pageSize);
            
            $sql .= ' LIMIT '.$this->pageSize;
            $sql .= ' OFFSET '.$offset;
        }

        return $sql;
    }

    protected function sanitizeFilters($filters)
    {
        return array(
            'pageIndex'  => intval($filters['pageIndex']),
            'pageNumber' => $filters['pageIndex'] + 1,
            'pageSize'   => intval($filters['pageSize']),
            'filterBy'   => is_array($filters['filterBy'])? $filters['filterBy'] : array(),
            'orderBy'    => isset($filters['sort'], $filters['direction'])? array($filters['sort'] => $filters['direction']) : array(),
        );
    }
    
}