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
 * The classes using this trait must implement a static $tableName;
 */
trait TableAware
{
    /**
     * Internal array of column name => data type.
     *
     * @var array
     */
    protected static $columns;

    /**
     * Gets the database table name.
     *
     * @return string
     */
    protected function getTableName()
    {
        if (empty(static::$tableName)) {
            throw new \BadMethodCallException(get_called_class().' must define a $tableName');
        }

        return static::$tableName;
    }

    /**
     * Gets the total number of rows in this database table.
     *
     * @return int
     */
    protected function countAll()
    {
        return $this->db()->selectOne("SELECT COUNT(*) FROM `{$this->getTableName()}`");
    }

    /**
     * Checks to see if the named column exists in the table schema.
     *
     * @param string $columnName
     * @return bool
     */
    protected function hasTableColumn($columnName) 
    {
        return isset($this->getTableColumns()[$columnName]);
    }

    /**
     * Gets the schema information for the columns in this database table.
     *
     * @return array
     */
    protected function getTableColumns()
    {
        if (empty(static::$columns)) {
            $result = $this->db()->select("SELECT DISTINCT `COLUMN_NAME`, `DATA_TYPE` FROM information_schema.columns WHERE table_name='{$this->getTableName()}'");
            static::$columns = $result->fetchKeyPair();
        }

        return static::$columns;
    }
}
