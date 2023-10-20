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

namespace Gibbon\Database;

use PDO;
use PDOStatement;
use Gibbon\Domain\DataSet;
use Gibbon\Contracts\Database\Result as ResultContract;

/**
 * Methods to improve the intent and readability of database code.
 */
class Result extends PDOStatement implements ResultContract
{
    /**
     * Does the result contain no database rows?
     *
     * @return bool
     */
    public function isEmpty()
    {
        return $this->rowCount() == 0;
    }

    /**
     * Does the result contain any database rows?
     *
     * @return bool
     */
    public function isNotEmpty()
    {
        return $this->rowCount() > 0;
    }

    /**
     * Fetches all as an array, grouped by key using the first column in the result set.
     *
     * @return array
     */
    public function fetchGrouped()
    {
        return $this->isNotEmpty()? $this->fetchAll(PDO::FETCH_GROUP) : array();
    }

    /**
     * Fetches all as an array, grouped by key where the contents 
     *
     * @return array
     */
    public function fetchGroupedUnique()
    {
        return $this->isNotEmpty()? $this->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_UNIQUE) : array();
    }

    /**
     * Fetches all as an associative array of key => value pairs. The query may only have two columns.
     *
     * @return array
     */
    public function fetchKeyPair()
    {
        return $this->isNotEmpty()? $this->fetchAll(PDO::FETCH_KEY_PAIR) : array();
    }

    /**
     * {@inheritDoc}
     */
    public function toDataSet()
    {
        return new DataSet($this->isNotEmpty()? $this->fetchAll() : []);
    }
}
