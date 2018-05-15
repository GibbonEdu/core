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

    /**
     * Loads and sanitizes a set of criteria from array.
     *
     * @param array $criteria
     * @return self
     */
    public function fromArray(array $criteria)
    {
        if (isset($criteria['page'])) {
            $this->page($criteria['page']);
        }

        if (isset($criteria['pageSize'])) {
            $this->pageSize($criteria['pageSize']);
        }

        return $this;
    }

    /**
     * Return an array representing the criteria settings.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->criteria;
    }

    /**
     * Loads and sanitizes a set of criteria from a JSON string.
     *
     * @param string $jsonString
     * @return self
     */
    public function fromJson($jsonString)
    {
        return $this->fromArray(json_decode($jsonString, true));
    }

    /**
     * Returns the criteria settings as a JSON string.
     *
     * @return string
     */
    public function toJson()
    {
        return json_encode($this->criteria);
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

        foreach ($columns as $column) {
            $this->criteria['searchBy'][$column] = trim($search);
        }

        return $this;
    }

    /**
     * Does the criteria have any search values set, by column or in total?
     *
     * @return bool
     */
    public function hasSearch($column = null)
    {
        return !is_null($column) ? isset($this->criteria['searchBy'][$column]) : !empty($this->criteria['searchBy']);
    }

    /**
     * Get the search value by column name, or return all search columns if none is specified.
     *
     * @param string $column
     * @return string|array
     */
    public function getSearch($column = null)
    {
        return isset($this->criteria['searchBy'][$column]) ? $this->criteria['searchBy'][$column] : $this->criteria['searchBy'];
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

        if (!empty($value)) {
            $name = $name.':'.strtolower($value);
        }

        $this->criteria['filterBy'][] = $name;

        return $this;
    }

    /**
     * Does the criteria have any filters set?
     *
     * @return bool
     */
    public function hasFilters()
    {
        return !empty($this->criteria['filterBy']);
    }

    /**
     * Get the criteria filters, if any.
     *
     * @return array
     */
    public function getFilters()
    {
        return $this->hasFilters()? $this->criteria['filterBy'] : array();
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

        foreach ($columns as $column) {
            $this->criteria['sortBy'][$column] = (strtoupper($direction) == 'DESC') ? 'DESC' : 'ASC';
        }

        return $this;
    }

    /**
     * Does the criteria have any sort values set, by column or in total?
     *
     * @return bool
     */
    public function hasSort($column = null)
    {
        return !is_null($column) ? isset($this->criteria['sortBy'][$column]) : !empty($this->criteria['sortBy']);
    }

    /**
     * Get the sort value by column name, or return all search columns if none is specified.
     *
     * @param string $column
     * @return string|array
     */
    public function getSort($column = null)
    {
        return isset($this->criteria['sortBy'][$column]) ? $this->criteria['sortBy'][$column] : $this->criteria['sortBy'];
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
     * Gets the page number.
     *
     * @return int
     */
    public function getPage()
    {
        return $this->criteria['page'];
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

    /**
     * Gets the page size.
     *
     * @return int
     */
    public function getPageSize()
    {
        return $this->criteria['pageSize'];
    }

    public function defineFilter($name, Closure $callback)
    {
        $this->definitions[$name] = $callback;

        return $this;
    }

    public function defineFilters(array $filters)
    {
        foreach ($filters as $name => $callback) {
            $this->defineFilter($name, $callback);
        }

        return $this;
    }

    public function getFilter($name)
    {
        return isset($this->definitions[$name]) ? $this->definitions[$name] : null;
    }
}