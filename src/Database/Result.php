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

namespace Gibbon\Database;

use PDO;
use PDOStatement;
use Gibbon\Domain\DataSet;

/**
 * Helper methods to improve the intent and readability of database code.
 */
class Result extends PDOStatement
{
    public function isEmpty()
    {
        return $this->rowCount() == 0;
    }

    public function isNotEmpty()
    {
        return $this->rowCount() > 0;
    }

    public function fetchAny()
    {
        return $this->isNotEmpty()? $this->fetchAll() : array();
    }

    public function fetchGrouped()
    {
        return $this->isNotEmpty()? $this->fetchAll(PDO::FETCH_GROUP) : array();
    }

    public function fetchGroupedByUnique()
    {
        return $this->isNotEmpty()? $this->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_UNIQUE) : array();
    }

    public function fetchKeyPair()
    {
        return $this->isNotEmpty()? $this->fetchAll(PDO::FETCH_KEY_PAIR) : array();
    }

    public function toDataSet($foundRows, $totalRows)
    {
        return new DataSet($this->fetchAll(), $foundRows, $totalRows);
    }
}