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

namespace Gibbon\Domain\Traits;

use Aura\SqlQuery\Common\DeleteInterface;
use Aura\SqlQuery\Common\InsertInterface;
use Aura\SqlQuery\Common\SelectInterface;
use Aura\SqlQuery\Common\UpdateInterface;
use Aura\SqlQuery\Mysql\Insert as MysqlInsert;

/**
 * Provides methods for Gateway classes that are tied to a specific database table.
 * For QueryableGateways, this trait implements the required countAll() method.
 *
 * The classes using this trait must implement a static $tableName and $primaryKey.
 * They can also implement an optional static $searchableColumns array.
 */
trait TableAware
{
    /**
     * Gets the database table name.
     *
     * @return string
     */
    public function getTableName()
    {
        if (empty(static::$tableName)) {
            throw new \BadMethodCallException(get_called_class().' must define a $tableName');
        }

        return static::$tableName;
    }

    /**
     * Gets the primary key column name for the table.
     *
     * @return string
     */
    public function getPrimaryKey()
    {
        if (empty(static::$primaryKey)) {
            throw new \BadMethodCallException(get_called_class().' must define a $primaryKey');
        }

        return static::$primaryKey;
    }

    /**
     * Get an internal pre-defined array of column names that are searchable.
     *
     * @return array
     */
    public function getSearchableColumns()
    {
        return isset(self::$searchableColumns)? self::$searchableColumns : [];
    }

    /**
     * Gets the total number of rows in this database table.
     *
     * @return int
     */
    public function countAll()
    {
        return $this->db()->selectOne("SELECT COUNT(*) FROM `{$this->getTableName()}`");
    }

    /**
     * Gets the values of a table row by it's primary key.
     *
     * @param string $primaryKeyValue  The primary key value to select data with.
     *
     * @return \Gibbon\Database\Result|array  The select result, or empty array if empty.
     */
    public function getByID($primaryKeyValue, $cols = []) : array
    {
        if (empty($primaryKeyValue)) {
            return [];
        }

        $query = $this
            ->newSelect()
            ->cols(!empty($cols) ? $cols : ['*'])
            ->from($this->getTableName())
            ->where($this->getPrimaryKey().' = :primaryKey')
            ->bindValue('primaryKey', $primaryKeyValue);

        $result = $this->runSelect($query);
        return $result->isNotEmpty()
            ? $result->fetch()
            : [];
    }

    /**
     * Selects a number of rows matching a simple key => value select.
     *
     * @param string $keysAndValues  The key-value pairs to select data with.
     *
     * @return \Gibbon\Database\Result  The select query result.
     */
    public function selectBy(array $keysAndValues, $cols = [])
    {
        $query = $this
            ->newSelect()
            ->cols(!empty($cols) ? $cols : ['*'])
            ->from($this->getTableName());

        $count = 0;
        foreach ($keysAndValues as $key => $value) {
            $query->where($key." = :key{$count}")
                  ->bindValue("key{$count}", $value);
            $count++;
        }

        return $this->runSelect($query);
    }

    /**
     * Inserts a row into the table and returns the primary key.
     *
     * @param array $data  A row of data to insert into database.
     *
     * @return mixed The primary key for the just inserted data.
     *
     *               Will call `$this->runInsert` internally
     *               for actual operation. Expects `runInsert`
     *               implementation to fulfill the contract and
     *               return the primary key.
     *
     *               @see \Gibbon\Domain\QueryableGateway::runInsert
     */
    public function insert(array $data)
    {
        unset($data[$this->getPrimaryKey()]);

        $query = $this
            ->newInsert()
            ->into($this->getTableName())
            ->cols($data);

        return $this->runInsert($query);
    }

    /**
     * Upsert implementation. Inserts a row into the table and
     * returns the primary key, or update existing row.
     *
     * @param array $data  A row of data to insert into database.
     * @param array $updateCols
     *
     * @return mixed The primary key for the just inserted data.
     *
     *               Will call `$this->runInsert` internally
     *               for actual operation. Expects `runInsert`
     *               implementation to fulfill the contract and
     *               return the primary key.
     *
     *               @see \Gibbon\Domain\QueryableGateway::runInsert
     */
    public function insertAndUpdate(array $data, array $updateCols)
    {
        unset($data[$this->getPrimaryKey()]);

        $query = $this
            ->newInsert()
            ->into($this->getTableName())
            ->cols($data)
            ->onDuplicateKeyUpdateCols($updateCols);

        return $this->runInsert($query);
    }

    /**
     * Updates a row in the table based on primary key and returns true on success.
     *
     * @param string $primaryKeyValue  The primary key for the row to update with.
     * @param array  $data             A row of data to insert into database.
     *
     * @return bool  Boolean indicator for the success database operation of the
     *               update.
     *
     *               Will call `$this->runUpdate` internally
     *               for actual operation. Expects `runInsert`
     *               implementation to fulfill the contract and
     *               return the boolean result.
     *
     *               @see \Gibbon\Domain\QueryableGateway::runUpdate
     */
    public function update($primaryKeyValue, array $data) : bool
    {
        if (empty($primaryKeyValue)) {
            throw new \InvalidArgumentException("Gateway update method for {$this->getTableName()} must provide a primary key value.");
        }

        unset($data[$this->getPrimaryKey()]);

        $query = $this
            ->newUpdate()
            ->table($this->getTableName())
            ->cols($data)
            ->where($this->getPrimaryKey().' = :primaryKey')
            ->bindValue('primaryKey', $primaryKeyValue);

        return $this->runUpdate($query);
    }

    /**
     * Updates one or more rows in the table based key=>value pairs, and returns true on success.
     *
     * @param array $keysAndValues
     * @param array $data
     *
     * @return bool  Boolean indicator for the success database operation of the
     *               update.
     *
     *               Will call `$this->runUpdate` internally
     *               for actual operation. Expects `runInsert`
     *               implementation to fulfill the contract and
     *               return the boolean result.
     *
     *               @see \Gibbon\Domain\QueryableGateway::runUpdate
     */
    public function updateWhere(array $keysAndValues, array $data) : bool
    {
        if (empty($keysAndValues)) {
            throw new \InvalidArgumentException("Gateway update method for {$this->getTableName()} must provide an array of keys and values.");
        }

        unset($data[$this->getPrimaryKey()]);

        $query = $this
            ->newUpdate()
            ->table($this->getTableName())
            ->cols($data);

        $count = 0;
        foreach ($keysAndValues as $key => $value) {
            $query->where($key." = :key{$count}")
                  ->bindValue("key{$count}", $value);
            $count++;
        }

        return $this->runUpdate($query);
    }

    /**
     * Deletes a row in the table based on primary key and returns true on success.
     *
     * @param string $primaryKeyValue
     *
     * @return bool  Boolean indicator for the success database operation of the
     *               delete.
     *
     *               Will call `$this->runDelete` internally
     *               for actual operation. Expects `runInsert`
     *               implementation to fulfill the contract and
     *               return the boolean result.
     *
     *               @see \Gibbon\Domain\QueryableGateway::runDelete
     */
    public function delete($primaryKeyValue) : bool
    {
        if (empty($primaryKeyValue)) {
            throw new \InvalidArgumentException("Gateway delete method for {$this->getTableName()} must provide a primary key value.");
        }

        $query = $this
            ->newDelete()
            ->from($this->getTableName())
            ->where($this->getPrimaryKey().' = :primaryKey')
            ->bindValue('primaryKey', $primaryKeyValue);

        return $this->runDelete($query);
    }

    /**
     * Deletes one or more rows in the table based key=>value pairs, and returns true on success.
     *
     * @param array $keysAndValues
     * @return bool
     */
    public function deleteWhere(array $keysAndValues) : bool
    {
        if (empty($keysAndValues)) {
            throw new \InvalidArgumentException("Gateway update method for {$this->getTableName()} must provide an array of keys and values.");
        }

        $query = $this
            ->newDelete()
            ->from($this->getTableName());

        $count = 0;
        foreach ($keysAndValues as $key => $value) {
            $query->where($key." = :key{$count}")
                  ->bindValue("key{$count}", $value);
            $count++;
        }

        return $this->runDelete($query);
    }

    /**
     * Returns true if no rows match the provided key => value pair of data.
     * Can optionally omit a row by primary key, when checking other rows only.
     *
     * @param array $data              An assoc array of key-value pair(s).
     * @param array $uniqueKeys        An assoc array of unique key key-value pair(s).
     * @param string $primaryKeyValue  Optional primary key value to check with.
     *
     * @return bool  True if no rows match the provided key => value pair of data, or false.
     */
    public function unique(array $data, array $uniqueKeys, $primaryKeyValue = '') : bool
    {
        $query = $this
            ->newSelect()
            ->cols([$this->getPrimaryKey()])
            ->from($this->getTableName());

        $query->where(function ($query) use ($uniqueKeys, $data) {
            foreach ($uniqueKeys as $i => $key) {
                if (empty($data[$key])) return false;

                $query->where("{$key} = :key{$i}")
                    ->bindValue("key{$i}", $data[$key]);
            }
        });

        if (!empty($primaryKeyValue)) {
            $query->where($this->getPrimaryKey().' <> :primaryKey')
                  ->bindValue('primaryKey', $primaryKeyValue);
        }

        return $this->runSelect($query)->rowCount() == 0;
    }

    /**
     * Returns true if the primary key value exists in the table.
     *
     * @param string $primaryKeyValue  The primary key to check with.
     *
     * @return bool   If the primary key value exists in the table.
     */
    public function exists($primaryKeyValue) : bool
    {
        $query = $this
            ->newSelect()
            ->cols([$this->getPrimaryKey()])
            ->from($this->getTableName())
            ->where($this->getPrimaryKey().' = :primaryKey')
            ->bindValue('primaryKey', $primaryKeyValue);

        return $this->runSelect($query)->rowCount() > 0;
    }

    /**
     * Get database connection.
     *
     * @return \Gibbon\Contracts\Database\Connection
     * @see    \Gibbon\Domain\Gateway::db()
     */
    abstract protected function db();

    /**
     * Creates a new instance of the Insert class.
     *
     * @return InsertInterface
     * @see    \Gibbon\Domain\QueryableGateway::newInsert()
     */
    abstract protected function newInsert();

    /**
     * Run an insert query.
     *
     * @param InsertInterface $query
     * @return int
     * @see    \Gibbon\Domain\QueryableGateway::runInsert()
     */
    abstract protected function runInsert(InsertInterface $query);

    /**
     * Creates a new instance of the Select class.
     *
     * @return SelectInterface
     * @see    \Gibbon\Domain\QueryableGateway::newSelect()
     */
    abstract protected function newSelect();

    /**
     * Run a select query.
     *
     * @param SelectInterface $query
     * @return \Gibbon\Contracts\Database\Result
     * @see    \Gibbon\Domain\QueryableGateway::runSelect()
     */
    abstract protected function runSelect(SelectInterface $query);

    /**
     * Creates a new instance of the Update class.
     *
     * @return UpdateInterface
     * @see    \Gibbon\Domain\QueryableGateway::newUpdate()
     */
    abstract protected function newUpdate();

    /**
     * Run an update query.
     *
     * @param  UpdateInterface $query
     * @return bool
     * @see    \Gibbon\Domain\QueryableGateway::runUpdate()
     */
    abstract protected function runUpdate(UpdateInterface $query);

    /**
     * Creates a new instance of the Update class.
     *
     * @return DeleteInterface
     * @see    \Gibbon\Domain\QueryableGateway::newDelete()
     */
    abstract protected function newDelete();

    /**
     * Run an delete query.
     *
     * @param  DeleteInterface $query
     * @return int
     * @see    \Gibbon\Domain\QueryableGateway::runDelete()
     */
    abstract protected function runDelete(DeleteInterface $query);
}
