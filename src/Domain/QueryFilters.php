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
class QueryFilters
{
    protected $filters = array(
        'pageIndex' => 0,
        'pageSize' => 25,
        'searchBy' => array(),
        'filterBy' => array(),
        'orderBy' => array(),
    );

    protected $definitions = array();

    public function __construct($filters = array())
    {
        $this->filters = $this->sanitizeFilters(array_replace($this->filters, $filters));
    }

    public function __get($name)
    {
        return isset($this->filters[$name]) ? $this->filters[$name] : null;
    }

    public function __isset($name)
    {
        return isset($this->filters[$name]);
    }

    public static function createFromPost()
    {
        return new self($_POST);
    }

    public static function createEmpty()
    {
        return new self();
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

    public function getFilters()
    {
        return $this->filters;
    }

    public function getDefinitionLabels()
    {
        // return array_combine(array_keys($this->definitions), array_column($this->definitions, 'label'));
        return array();
    }

    public function addSearch($column, $search)
    {
        if (trim($search) == '') return $this;

        $columns = is_array($column) ? $column : array($column);
        foreach ($columns as $column) {
            $this->filters['searchBy'][$column] = trim($search);
        }

        return $this;
    }

    public function addFilter($name)
    {
        if (empty($name)) return $this;

        if (!in_array($name, $this->filters['filterBy'])) {
            $this->filters['filterBy'][] = trim($name);
        }

        return $this;
    }

    public function defaultSort($column, $direction = 'ASC')
    {
        if (empty($column) || !empty($this->filters['orderBy'])) return $this;

        $this->filters['orderBy'][$column] = ($direction == 'DESC') ? 'DESC' : 'ASC';

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
}