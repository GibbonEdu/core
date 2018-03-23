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
 * Helper methods to improve the intent and readability of database code.
 */
class Result extends \PDOStatement
{
    public function isEmpty()
    {
        return $this->rowCount() == 0;
    }

    public function hasRows()
    {
        return $this->rowCount() > 0;
    }

    public function rowsAffected()
    {
        return $this->rowCount();
    }

    public function fetchGrouped()
    {
        return $this->fetchAll(\PDO::FETCH_GROUP);
    }

    public function fetchGroupedByID()
    {
        return $this->fetchAll(\PDO::FETCH_GROUP|\PDO::FETCH_UNIQUE);
    }
}