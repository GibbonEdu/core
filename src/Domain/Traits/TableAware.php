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

trait TableAware
{
    /**
     * The database table name. Inheriting classes must set this.
     *
     * @var string
     */
    protected static $tableName;

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
     * Gets the schema information for the columns in this database table.
     *
     * @return array
     */
    protected function getColumns()
    {
        $result = $this->db()->select("SELECT * FROM information_schema.columns WHERE table_name='{$this->getTableName()}'");
        return $result->rowCount() > 0 ? $result->fetchAll() : array();
    }
}