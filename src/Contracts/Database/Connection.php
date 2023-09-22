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
     *
     * @return mixed|array|false
     *     Depends on the SQL statement. It returns either:
     *     (a) a single column from the next row of a result
     *         set if the query only has 1 column; or
     *     (b) a normal result row of the next row of a result
     *         set; or
     *     (c) boolean false if there are no more rows.
     */
    public function selectOne($query, $bindings = []);

    /**
     * Run a select statement against the database.
     *
     * @param  string  $query
     * @param  array   $bindings
     *
     * @return \Gibbon\Database\Result Result of the database query.
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
     *
     * @return bool  If the update statment is execute successfully.
     */
    public function update($query, $bindings = []);

    /**
     * Run a delete statement against the database.
     *
     * @param  string  $query
     * @param  array   $bindings
     *
     * @return int  Affected row count.
     */
    public function delete($query, $bindings = []);

    /**
     * Execute an SQL statement and return the boolean result.
     *
     * @param  string  $query
     * @param  array   $bindings
     *
     * @return bool  If the update statment is execute successfully.
     */
    public function statement($query, $bindings = []);

    /**
     * Run an SQL statement and get the number of rows affected.
     *
     * @param  string  $query
     * @param  array   $bindings
     *
     * @return int  Affected row count.
     */
    public function affectingStatement($query, $bindings = []);

    /**
     * Start a new database transaction.
     *
     * @return void
     */
    public function beginTransaction();

    /**
     * Commit the active database transaction.
     *
     * @return void
     */
    public function commit();

    /**
     * Rollback the active database transaction.
     *
     * @return void
     */
    public function rollBack();

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
    public function executeQuery($bindings = [], $query = "");
}
