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

namespace Gibbon\Domain\Traits;

/**
 * Provides methods for Gateway classes that are tied to a specific database table.
 * For QueryableGateways, this trait implements the required countAll() method.
 *
 * The classes using this trait must implement a static $tableName and $primaryKey
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
     * @param string $primaryKeyValue
     * @return array
     */
    public function getByID($primaryKeyValue) : array
    {
        if (empty($primaryKeyValue)) {
            throw new \InvalidArgumentException("Gateway getByID method for {$this->getTableName()} must provide a primary key value.");
        }

        $query = $this
            ->newSelect()
            ->cols(['*'])
            ->from($this->getTableName())
            ->where($this->getPrimaryKey().' = :primaryKey')
            ->bindValue('primaryKey', $primaryKeyValue);

        return $this->runSelect($query)->fetch();
    }

    /**
     * Selects a number of rows matching a simple key => value select.
     *
     * @param string $primaryKeyValue
     * @return array
     */
    public function selectBy(array $keysAndValues)
    {
        if (empty($keysAndValues)) {
            throw new \InvalidArgumentException("Gateway selectBy method for {$this->getTableName()} must provide an array of keys and values.");
        }

        $query = $this
            ->newSelect()
            ->cols(['*'])
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
     * @param array $data
     * @return void
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
     * Updates a row in the table based on primary key and returns true on success.
     *
     * @param string $primaryKeyValue
     * @param array $data
     * @return bool
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
     * Deletes a row in the table based on primary key and returns true on success.
     *
     * @param string $primaryKeyValue
     * @return bool
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
     * Returns true if no rows match the provided key => value pair of data.
     * Can optionally omit a row by primary key, when checking other rows only.
     *
     * @param array $data
     * @param array $uniqueKeys
     * @param string $primaryKeyValue
     * @return bool
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
     * @param string $primaryKeyValue
     * @return bool
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
}
