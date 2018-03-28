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
 * Object describing the filters applied to a Gateway query.
 */
class QueryFilters
{
    protected $filters = array(
        'pageIndex'  => 0,
        'pageSize'   => 25,
        'searchBy'   => array(),
        'filterBy'   => array(),
        'orderBy'    => array(),
    );

    protected $definitions = array();

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

    public static function createFromPost()
    {
        return new QueryFilters($_POST);
    }

    public function toJson()
    {
        return json_encode($this->filters);
    }

    public function defineFilter($name, $label, $query, $data = array())
    {
        $this->definitions[$name] = array(
            'label' => $label,
            'query' => $query,
            'data' => $data,
        );

        return $this;
    }

    public function getDefinitions()
    {
        return $this->definitions;
    }

    public function addSearch($search, $column)
    {
        if (trim($search) == '') return $this;

        $columns = is_array($column)? $column : array($column);
        foreach ($columns as $column) {
            $this->filters['searchBy'][$column] = $search;
        }

        return $this;
    }

    public function addFilter($name)
    {
        if (empty($name)) return $this;

        if (!in_array($name, $this->filters['filterBy'])) {
            $this->filters['filterBy'][] = $name;
        }

        return $this;
    }

    public function defaultSort($column, $direction = 'ASC') 
    {
        if (empty($column) || !empty($this->filters['orderBy'])) return $this;

        $this->filters['orderBy'][$column] = ($direction == 'DESC')? 'DESC' : 'ASC';

        return $this;
    }

    public function applyFilters($sql, &$data = array())
    {
        if (mb_stripos($sql, 'SQL_CALC_FOUND_ROWS') === false) {
            $sql = str_ireplace('SELECT', 'SELECT SQL_CALC_FOUND_ROWS', $sql);
        }

        if (!empty($this->searchBy)) {
            $sql .= (mb_stripos($sql, 'WHERE') !== false)? ' AND ' : ' WHERE ';

            $where = array();
            $count = 0;
            foreach ($this->searchBy as $column => $search) {
                $data['search'.$count] = "%{$search}%";
                $where[] = $this->escapeIdentifier($column)." LIKE :search{$count}";
                $count++;
            }

            $sql .= '('.implode(' OR ', $where).')';
        }

        if (!empty($this->filterBy)) {
            $sql .= (mb_stripos($sql, 'WHERE') !== false)? ' AND ' : ' WHERE ';

            $where = array();
            $filters = array_intersect_key($this->definitions, array_flip($this->filterBy));

            foreach ($filters as $filterName => $filter) {
                $where[] = $filter['query'];
                if (!empty($filter['data'])) {
                    $data = array_replace($data, $filter['data']);
                }
            }

            $sql .= '('.implode(' AND ', $where).')';
        }

        if (!empty($this->orderBy)) {
            $sql .= ' ORDER BY ';

            $order = array();
            foreach ($this->orderBy as $column => $direction) {
                $direction = ($direction == 'DESC')? 'DESC' : 'ASC';
                $order[] =  $this->escapeIdentifier($column).' '.$direction;
            }

            $sql .= implode(', ', $order);
        }

        if (!empty($this->pageSize)) {
            $offset = max(0, $this->pageIndex * $this->pageSize);
            
            $sql .= ' LIMIT '.intval($this->pageSize);
            $sql .= ' OFFSET '.intval($offset);
        }

        // echo $sql;

        return $sql;
    }

    protected function escapeIdentifier($value)
    {
        return implode('.', array_map(function($piece) {
            return '`'.str_replace('`','``',$piece).'`';
        }, explode('.', $value)));
    }

    protected function sanitizeFilters($filters)
    {
        return array(
            'pageIndex'  => intval($filters['pageIndex']),
            'pageSize'   => intval($filters['pageSize']),
            'searchBy'   => is_array($filters['searchBy'])? $filters['searchBy'] : array(),
            'filterBy'   => is_array($filters['filterBy'])? $filters['filterBy'] : array(),
            'orderBy'   => is_array($filters['orderBy'])? $filters['orderBy'] : array(),
            // 'orderBy'    => isset($filters['sort'], $filters['direction'])? array($filters['sort'] => $filters['direction']) : array(),
        );
    }
}