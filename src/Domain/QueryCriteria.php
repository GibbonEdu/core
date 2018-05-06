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
        'page' => 1,
        'pageSize' => 25,
        'searchBy' => array(),
        'filterBy' => array(),
        'sortBy' => array(),
    );

    protected $definitions = array();

    public function __construct(array $criteria = array())
    {
        $this->criteria = $this->sanitizeArray(array_replace($this->criteria, $criteria));
    }

    public function __get($name)
    {
        return isset($this->criteria[$name]) ? $this->criteria[$name] : null;
    }

    public function fromArray(array $criteria)
    {
        $this->criteria = $this->sanitizeArray(array_replace($this->criteria, $criteria));

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

    public function getFilter($name)
    {
        return isset($this->definitions[$name]) ? $this->definitions[$name] : null;
    }

    /**
     * Add a search string to the criteria. 
     * Accepts $column as a string or an array of columns to search.
     * 
     * @param string|array $column
     * @param string $search
     * @return self
     */
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

    /**
     * Add a filter to the criteria. 
     * Accepts parameters as filter:value strings, or separate $name, $value params.
     * Values with spaces or other characters can also be quoted, as filter:"some value"
     * 
     * @param string $filter
     * @param string $value
     * @return self
     */
    public function filterBy($name, $value = null)
    {
        if (empty($name)) return $this;

        if (empty($value)) {
            list($name, $value) = array_pad(explode(':', $name, 2), 2, '');
        }

        $this->criteria['filterBy'][$name] = trim($value, '" ');

        return $this;
    }

    /**
     * Add a sort column to the criteria.
     * Accepts $column as a string or an array of columns to search.
     * 
     * @param string $column
     * @param string $direction
     * @return self
     */
    public function sortBy($column, $direction = 'ASC')
    {
        if (empty($column)) return $this;

        $columns = is_array($column) ? $column : array($column);
        $columns = array_map([$this, 'escapeIdentifier'], $columns);

        foreach ($columns as $column) {
            $this->criteria['sortBy'][$column] = (strtoupper($direction) == 'DESC') ? 'DESC' : 'ASC';
        }

        return $this;
    }

    /**
     * Sets the page number for paginated queries, applied to the sql offset.
     * 
     * @param int $page
     * @return self
     */
    public function page($page)
    {
        $this->criteria['page'] = intval($page);

        return $this;
    }

    /**
     * Sets the page size for paginated queries, applied to the sql limit.
     * @param int $pageSize
     * @return self
     */
    public function pageSize($pageSize)
    {
        $this->criteria['pageSize'] = intval($pageSize);

        return $this;
    }

    protected function sanitizeArray($criteria)
    {
        return array(
            'page' => intval($criteria['page']),
            'pageSize' => intval($criteria['pageSize']),
            'searchBy' => is_array($criteria['searchBy']) ? $criteria['searchBy'] : array(),
            'filterBy' => is_array($criteria['filterBy']) ? $criteria['filterBy'] : array(),
            'sortBy' => is_array($criteria['sortBy']) ? $criteria['sortBy'] : array(),
        );
    }

    protected function escapeIdentifier($value)
    {
        return implode('.', array_map(function ($piece) {
            return '`' . str_replace('`', '``', $piece) . '`';
        }, explode('.', $value, 2)));
    }
}