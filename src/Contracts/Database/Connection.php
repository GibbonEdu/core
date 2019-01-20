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

/**
 * Database Connection Interface 
 * Borrowed in part from Illuminate\Database\ConnectionInterface
 *
 * @version	v16
 * @since	v16
 */
interface Connection
{
    /**
     * Get the current PDO connection.
     *
     * @return \PDO
     */
    public function getConnection();

    /**
     * Run a select statement and return a single result.
     *
     * @param  string  $query
     * @param  array   $bindings
     * @return mixed
     */
    public function selectOne($query, $bindings = []);

    /**
     * Run a select statement against the database.
     *
     * @param  string  $query
     * @param  array   $bindings
     * @return array
     */
    public function select($query, $bindings = []);

    /**
     * Run an insert statement and return the last insert ID.
     *
     * @param  string  $query
     * @param  array   $bindings
     * @return int
     */
    public function insert($query, $bindings = []);

    /**
     * Run an update statement against the database.
     *
     * @param  string  $query
     * @param  array   $bindings
     * @return int
     */
    public function update($query, $bindings = []);

    /**
     * Run a delete statement against the database.
     *
     * @param  string  $query
     * @param  array   $bindings
     * @return int
     */
    public function delete($query, $bindings = []);

    /**
     * Execute an SQL statement and return the boolean result.
     *
     * @param  string  $query
     * @param  array   $bindings
     * @return bool
     */
    public function statement($query, $bindings = []);

    /**
     * Run an SQL statement and get the number of rows affected.
     *
     * @param  string  $query
     * @param  array   $bindings
     * @return int
     */
    public function affectingStatement($query, $bindings = []);

    // /**
    //  * Start a new database transaction.
    //  *
    //  * @return void
    //  */
    // public function beginTransaction();

    // /**
    //  * Commit the active database transaction.
    //  *
    //  * @return void
    //  */
    // public function commit();

    // /**
    //  * Rollback the active database transaction.
    //  *
    //  * @return void
    //  */
    // public function rollBack();

    // /**
    //  * Get the number of active transactions.
    //  *
    //  * @return int
    //  */
    // public function transactionLevel();

    /**
     * @deprecated
     * Backwards compatability for the old Gibbon\sqlConnection class. 
     * Replaced with more expressive method names. Also because the 
     * parameters are backwards. Hoping to phase this one out in v17.
     *
     * @param  string  $query
     * @param  array   $bindings
     * @return bool
     */
    public function executeQuery($bindings = [], $query);
}