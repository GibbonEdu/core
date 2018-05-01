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

class QueryBuilder
{
    protected $cols = [];
    protected $from = [];
    protected $where = [];
    protected $groupBy = [];
    protected $orderBy = [];
    protected $limit = '';
    protected $offset = '';

    public function cols($value = [])
    {
        return $this->addTo($this->cols, $value);
    }

    public function from($value = [])
    {
        return $this->addTo($this->from, $value);
    }

    // public function where($value = [])
    // {
    //     return $this->addTo($this->where, $value);
    // }

    // public function groupBy($value = [])
    // {
    //     return $this->addTo($this->groupBy, $value);
    // }

    // public function orderBy($value = [])
    // {
    //     return $this->addTo($this->orderBy, $value);
    // }

    public function limit($value = '')
    {
        $this->limit = $value;
        return $this;
    }

    public function offset($value = '')
    {
        $this->offset = $value;
        return $this;
    }

    public function build()
    {
        $sql = '';
        
        $sql .= $this->apply('SELECT', $this->cols);
        $sql .= $this->apply('FROM', $this->from);
        $sql .= $this->apply('WHERE', $this->where, ' AND ');
        $sql .= $this->apply('GROUP BY', $this->groupBy);
        $sql .= $this->apply('ORDER BY', $this->orderBy);
        $sql .= $this->apply('LIMIT', $this->limit);
        $sql .= $this->apply('OFFSET', $this->offset);

        return trim($sql);
    }

    protected function apply($keyword, $pieces = null, $glue = ', ')
    {
        if (empty($pieces)) return '';

        return is_array($pieces)
            ? $keyword.' '.implode($glue, $pieces).' '
            : $keyword.' '.$pieces.' ';
    }

    protected function addTo(&$collection, $pieces)
    {
        $pieces = !is_array($pieces)? array($pieces) : $pieces;
        $collection = array_replace($collection, $pieces);

        return $this;
    }

    protected function escapeIdentifier($value)
    {
        return implode('.', array_map(function($piece) {
            return '`'.str_replace('`','``',$piece).'`';
        }, explode('.', $value)));
    }
}