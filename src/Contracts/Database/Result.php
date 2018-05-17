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

namespace Gibbon\Contracts\Database;

interface Result
{
    /**
     * Returns the number of rows affected by the last SQL statement. 
     * PDOStatement method. 
     *
     * @return integer
     */
    public function rowCount();

    /**
     * Does the result contain no database rows?
     *
     * @return bool
     */
    public function isEmpty();

    /**
     * Does the result contain any database rows?
     *
     * @return bool
     */
    public function isNotEmpty();

    /**
     * Fetches the next row from a result set. 
     * PDOStatement method. 
     *
     * @param integer|null $fetch_style
     * @param integer|null $cursor_orientation
     * @param integer|null $cursor_offset
     * @return mixed
     */
    public function fetch($fetch_style = null, $cursor_orientation = null, $cursor_offset = null);

    /**
     * Returns an array containing all of the result set rows. 
     * PDOStatement method. 
     *
     * @param integer|null        $fetch_style
     * @param integer|string|null $fetch_argument
     * @param array|null          $ctor_args
     * @return array
     */
    public function fetchAll($fetch_style = null, $fetch_argument = null, $ctor_args = null);

    /**
     * Returns a single column from the next row of a result set. 
     * PDOStatement method. 
     *
     * @param integer $column_number
     * @return string
     */
    public function fetchColumn($column_number = 0);
    
    /**
     * Fetches all as an array, grouped by key using the first column in the result set.
     *
     * @return array
     */
    public function fetchGrouped();

    /**
     * Fetches all as an array, grouped by key where the contents 
     *
     * @return array
     */
    public function fetchGroupedUnique();

    /**
     * Fetches all as an associative array of key => value pairs. The query may only have two columns.
     *
     * @return array
     */
    public function fetchKeyPair();

    /**
     * Returns the number of rows affected by the last SQL statement. 
     * PDOStatement method. 
     *
     * @param  integer    $mode
     * @param  mixed|null $params
     * @return boolean
     */
    public function setFetchMode($mode, $params = null);

    /**
     * Fetches all results and returns it as a DataSet object.
     *
     * @return array
     */
    public function toDataSet();
}
