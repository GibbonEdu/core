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

use Gibbon\Contracts\Database\Connection as ConnectionInterface;
use PDO;
use Psr\Log\LoggerInterface;

/**
 * Database Connection.
 *
 * @version	v16
 * @since	v16
 */
class Connection implements ConnectionInterface
{
    /**
     * The active PDO connection.
     *
     * @var \PDO
     */
    protected $pdo;

    /**
     * @var bool
     */
    protected $querySuccess = false;

    /**
     * @var \PDOStatement
     */
    protected $result = null;

    /**
     * @var int
     */
    protected $transactions = 0;

    /**
     * @var LoggerInterface|null
     */
    private $logger;

    /**
     * @var bool
     */
    protected $errorMessage = null;

    /**
     * Create the connection wrapper around a \PDO instance.
     * @param \PDO $pdo
     * @param array $config
     */
    public function __construct(PDO $pdo, array $config = [])
    {
        $this->pdo = $pdo;
    }

    /**
     * Get the current PDO connection.
     *
     * @return \PDO
     */
    public function getConnection()
    {
        return $this->pdo;
    }

    /**
     * Run a select statement and return a single result.
     *
     * @param  string  $query
     * @param  array   $bindings
     * @return mixed
     */
    public function selectOne($query, $bindings = [])
    {
        $result = $this->run($query, $bindings);
        return $result->columnCount() == 1
            ? $result->fetchColumn()
            : $result->fetch();
    }

    /**
     * Run a select statement against the database.
     *
     * @param  string  $query
     * @param  array   $bindings
     * @return array
     */
    public function select($query, $bindings = [])
    {
        return $this->run($query, $bindings);
    }

    /**
     * Run an insert statement and return the last insert ID.
     *
     * @param  string  $query
     * @param  array   $bindings
     * @return int
     */
    public function insert($query, $bindings = [])
    {
        $querySuccess = $this->statement($query, $bindings);
        return $querySuccess
            ? $this->pdo->lastInsertID()
            : false;
    }

    /**
     * Run an update statement against the database.
     *
     * @param  string  $query
     * @param  array   $bindings
     * @return int
     */
    public function update($query, $bindings = [])
    {
        return $this->statement($query, $bindings);
    }

    /**
     * Run a delete statement against the database.
     *
     * @param  string  $query
     * @param  array   $bindings
     * @return int
     */
    public function delete($query, $bindings = [])
    {
        return $this->affectingStatement($query, $bindings);
    }

    /**
     * Execute an SQL statement and return the boolean result.
     *
     * @param  string  $query
     * @param  array   $bindings
     * @return bool
     */
    public function statement($query, $bindings = [])
    {
        $this->run($query, $bindings);
        return $this->querySuccess;
    }

    /**
     * Run an SQL statement and get the number of rows affected.
     *
     * @param  string  $query
     * @param  array   $bindings
     * @return int
     */
    public function affectingStatement($query, $bindings = [])
    {
        return $this->run($query, $bindings)->rowCount();
    }

    /**
     * Run a SQL statement, return the PDOStatement and handle exceptions.
     *
     * @param  string  $query
     * @param  array   $bindings
     * @return mixed
     *
     * @throws \PDOException
     */
    protected function run($query, $bindings = [])
    {
        try {
            $this->result = $this->pdo->prepare($query);
            $this->querySuccess = $this->result->execute($bindings);
        } catch (\PDOException $e) {
            $this->result = $this->handleQueryException($e);
            $this->querySuccess = false;
        }

        return $this->result;
    }

    /**
     * Currently downgrades fatal exceptions to user errors and returns a null statement.
     *
     * @param \PDOException $e
     * @return \PDOStatement
     */
    protected function handleQueryException($e)
    {
        trigger_error($e->getMessage(), E_USER_WARNING);

        $this->errorMessage = $e->getMessage();

        return new \PDOStatement();
    }

    /**
     * @deprecated v16
     * Backwards compatability for the old Gibbon\sqlConnection class.
     * Replaced with more expressive method names. Also because the
     * parameters are backwards. Hoping to phase this one out in v17.
     *
     * @param	array	Data Information
     * @param	string	SQL Query
     * @param	string	Error
     *
     * @return	\PDOStatement
     */
    public function executeQuery($data, $query, $error = null)
    {
        return $this->run($query, $data);
    }

    /**
     * @deprecated v16
     * Get the boolean success of the most recent query.
     *
     * @return bool
     */
    public function getQuerySuccess()
    {
        return $this->querySuccess;
    }

    /**
     * @deprecated v16
     * Get the result of the most recent query.
     *
     * @return \PDOStatement
     */
    public function getResult()
    {
        return $this->result;
    }
    
    /**
     * Start a new database transaction.
     *
     * @return void
     */
    public function beginTransaction()
    {
        if ($this->transactions == 0) {
            try {
                $this->pdo->beginTransaction();
            } catch (\Exception $e) {
                $this->handleQueryException($e);
                return;
            }

            $this->transactions++;
        }
    }

    /**
     * Commit the active database transaction.
     *
     * @return void
     */
    public function commit()
    {
        if ($this->transactions == 1) {
            $this->pdo->commit();
        }

        $this->transactions = 0;
    }

    /**
     * Rollback the active database transaction.
     *
     * @return void
     */
    public function rollBack()
    {
        if ($this->transactions == 1) {
            $this->pdo->rollBack();
        }

        $this->transactions = 0;
    }

    /**
     * @param LoggerInterface|null $logger
     * @return Connection
     */
    public function setLogger(LoggerInterface $logger): Connection
    {
        $this->logger = $logger;
        return $this;
    }

    /**
     * hasLogger
     * @return bool
     */
    private function hasLogger(): bool
    {
        return $this->logger instanceof LoggerInterface;
    }

    /**
     * Get the current PDO connection.
     *
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }
}
