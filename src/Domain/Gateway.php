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

use Gibbon\sqlConnection;
use Gibbon\Tables\DataFilters;

/**
 * Gateway
 *
 * @version v16
 * @since   v16
 */
abstract class Gateway
{
    protected $pdo;

    public function __construct(sqlConnection $pdo)
    {
        $this->pdo = $pdo;
    }

    public function applyDataFilters($sql, DataFilters $filters)
    {
        if (isset($filters->sort)) {
            $sql .= ' ORDER BY '.$filters->sort.' '.$filters->direction;
        }

        if (isset($filters->page)) {
            $offset = min(max(0, $filters->page * $filters->limit), $filters->pageMax * $filters->limit);
            $sql .= ' LIMIT '.$filters->limit.' OFFSET '.$offset;
        }

        return $sql;
    }
}