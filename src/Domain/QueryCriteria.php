<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

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
    protected $identifier = '';

    protected $criteria = array(
        'page' => 1,
        'pageSize' => 0,
        'searchBy' => array('columns' => [], 'text' => ''),
        'filterBy' => array(),
        'sortBy' => array(),
    );

    protected $rules = array();

    /**
     * Loads a set of criteria from POST data, using an identifier (if available) to separate unique table instances.
     *
     * @param string $key
     * @return self
     */
    public function fromPOST($identifier = '')
    {
        $this->setIdentifier($identifier);

        return !empty($identifier) && isset($_POST[$identifier]) 
            ? $this->fromArray($_POST[$identifier]) 
            : $this->fromArray($_POST);
    }

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

        if (isset($criteria['searchBy']) && is_array($criteria['searchBy'])) {
            $columns = isset($criteria['searchBy']['columns'])? $criteria['searchBy']['columns'] : '';
            $text = isset($criteria['searchBy']['text'])? $criteria['searchBy']['text'] : '';
            $this->searchBy($columns, $text);
        }

        if (isset($criteria['filterBy']) && is_array($criteria['filterBy'])) {
            $this->criteria['filterBy'] = [];
            foreach ($criteria['filterBy'] as $name => $value) {
                $this->filterBy($name, $value);
            }
        }

        if (isset($criteria['sortBy']) && is_array($criteria['sortBy'])) {
            $this->criteria['sortBy'] = [];
            foreach ($criteria['sortBy'] as $column => $direction) {
                $this->sortBy($column, $direction);
            }
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
     * Sets a unique identifier for this criteria, used for multiple table instances.
     *
     * @param string $identifier
     * @return self
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;

        return $this;
    }

    /**
     * Gets the unique identifier for this criteria.
     *
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * Sets the page number for paginated queries, applied to the sql offset.
     * 
     * @param int $page
     * @return self
     */
    public function page($page)
    {
        $this->criteria['page'] = max(1, intval($page));

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
        $this->criteria['pageSize'] = max(0, intval($pageSize));

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

    /**
     * Add a search string to the criteria for the specified columns.
     * Accepts $column as a string or an array of columns to search.
     * Omitting the $text value will modify the columns for the current search.
     * 
     * @param string|array $column
     * @param string $search
     * @return self
     */
    public function searchBy($column, $text = null)
    {
        $columns = is_array($column) ? $column : array($column);
        $columns = array_filter($columns);

        if (!empty($columns)) {
            $columns = array_map(function($item){ 
                return preg_replace('/[^a-zA-Z0-9\.\_]/', '', $item); 
            }, $columns);
            $this->criteria['searchBy']['columns'] =  $columns;
        }

        if (!is_null($text)) {
            $this->criteria['searchBy']['text'] = $this->applyAdvancedSearchFilters($text);
        }

        return $this;
    }

    /**
     * Allows filters to be added to the search string as foo:bar or foo:"bar baz"
     * Removes each filter from the string and adds it to the criteria.
     *
     * @param string $text
     * @return string
     */
    private function applyAdvancedSearchFilters($text)
    {
        $text = preg_replace_callback('/(\w*\:[\w\-]*|(?:"[^"]*"))+/', function ($matches) {
            $this->filterBy($matches[0]);
            return '';
        }, $text);

        return trim($text);
    }

    /**
     * Does the criteria have any search values set, by column or in total?
     *
     * @return bool
     */
    public function hasSearchColumn($column = null)
    {
        return !is_null($column) ? in_array($column, $this->criteria['searchBy']['columns']) : !empty(array_filter($this->criteria['searchBy']['columns']));
    }

    /**
     * Does the criteria have any search values set, by column or in total?
     *
     * @return bool
     */
    public function hasSearchText()
    {
        return !empty($this->criteria['searchBy']['text']);
    }

    /**
     * Get all the search values, if any.
     *
     * @param string $column
     * @return string|array
     */
    public function getSearchBy()
    {
        return isset($this->criteria['searchBy']) ? $this->criteria['searchBy'] : array();
    }

    /**
     * Gets the current search text, optionally including any current filters.
     *
     * @param bool $includeFilters
     * @return string
     */
    public function getSearchText($includeFilters = false)
    {
        $searchText = $this->criteria['searchBy']['text'];

        if ($includeFilters && $this->hasFilter()) {
            $searchText .= ' '.$this->getFilterString();
        }

        return trim($searchText);
    }

    /**
     * Get the current searched columns.
     *
     * @return array
     */
    public function getSearchColumns()
    {
        return isset($this->criteria['searchBy']['columns'])? $this->criteria['searchBy']['columns'] : array();
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

        if (stripos($name, ':') !== false) {
            list($name, $value) = array_pad(explode(':', $name, 2), 2, '');
            $value = str_replace('"', '', $value);
        }

        if (!empty($value)) {
            $name = preg_replace('/[^a-zA-Z0-9\.\-\_]/', '', $name);
            $this->criteria['filterBy'][$name] = $value;
        }

        return $this;
    }

    /**
     * Does the criteria have a filter set, by name or in total?
     *
     * @return bool
     */
    public function hasFilter($name = null, $value = null)
    {
        if (!is_null($value)) {
            return isset($this->criteria['filterBy'][$name]) && $this->criteria['filterBy'][$name] == $value;
        } else {
            return !is_null($name)? isset($this->criteria['filterBy'][$name]) : !empty($this->criteria['filterBy']);
        }
    }

    /**
     * Get all the criteria filters, if any.
     *
     * @return array
     */
    public function getFilterBy()
    {
        return isset($this->criteria['filterBy'])? $this->criteria['filterBy'] : array();
    }

    /**
     * Get a filter value by name, if it exists.
     *
     * @return array
     */
    public function getFilterValue($name)
    {
        return isset($this->criteria['filterBy'][$name])? $this->criteria['filterBy'][$name] : '';
    }

    /**
     * Returns the current filter array as a string of name:value filters.
     *
     * @return string
     */
    public function getFilterString()
    {
        return implode(' ', array_map(function($value, $name) {
            return stripos($value, ' ') !== false ? $name.':"'.$value .'"' : $name.':'.$value;
        }, $this->criteria['filterBy'], array_keys($this->criteria['filterBy'])));
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
            $column = preg_replace('/[^a-zA-Z0-9\.\_]/', '', $column);
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
    public function getSortBy($column = null)
    {
        return isset($this->criteria['sortBy'][$column]) ? $this->criteria['sortBy'][$column] : $this->criteria['sortBy'];
    }

    /**
     * Add a closure which defines the behaviour for a given filter by name.
     *
     * @param string $name
     * @param Closure $callback
     * @return self
     */
    public function addFilterRule($name, Closure $callback)
    {
        $this->rules[$name] = $callback;

        return $this;
    }

    /**
     * Add multiple filter rules as an array.
     *
     * @param array $rules
     * @return self
     */
    public function addFilterRules(array $rules)
    {
        foreach ($rules as $name => $callback) {
            $this->addFilterRule($name, $callback);
        }

        return $this;
    }

    /**
     * Does the criteria have a filter rule for the given name?
     *
     * @param string $name
     * @return bool
     */
    public function hasFilterRule($name)
    {
        return isset($this->rules[$name]);
    }

    /**
     * Get the filter rule for a given name.
     *
     * @param string $name
     * @return Closure|null
     */
    public function getFilterRule($name)
    {
        return $this->hasFilterRule($name) ? $this->rules[$name] : null;
    }
}
