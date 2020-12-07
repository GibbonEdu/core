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
 * Implements the ScrubbableGateway interface.
 */
trait ScrubByPerson
{
    /**
     * Gets the table key that identified what can be scrubbed.
     *
     * @return string|array
     */
    public function getScrubbableKey()
    {
        if (!isset(static::$scrubbableKey)) {
            throw new \BadMethodCallException(get_called_class().' must define $scrubbableKey');
        }

        return static::$scrubbableKey;
    }

    /**
     * Gets the table columns that can be scrubbed.
     *
     * @return array
     */
    public function getScrubbableColumns() : array
    {
        if (!isset(static::$scrubbableColumns)) {
            throw new \BadMethodCallException(get_called_class().' must define $scrubbableColumns');
        }

        return static::$scrubbableColumns;
    }

    public function scrub(string $cutoffDate, array $context = []) : bool
    {
        // Select scrubbable records

        $scrubbable = $this->getScrubbableRecords($cutoffDate, $context);

        echo '<pre>';
        print_r($scrubbable);
        echo '</pre>';

        $columns = $this->getScrubbableColumns();
        $columns = array_map(function ($item) {
            return $item == 'randomString'
                ? randomPassword(20)
                : $item;
        }, $columns);

        $scrubbedCount = 0;

        foreach ($scrubbable as $primaryKey) {

            $query = $this
                ->newUpdate()
                ->cols($columns)
                ->table($this->getTableName())
                ->where($this->getPrimaryKey().' = :primaryKey')
                ->bindValue('primaryKey', $primaryKey);

            $scrubbedCount += $this->runUpdate($query);
        }

        echo get_called_class().' was scrubbed: '.$scrubbedCount;

        return $scrubbedCount == count($scrubbable);
    }

    public function getScrubbableRecords(string $cutoffDate, array $context = []) : array
    {
        $query = $this
            ->newSelect()
            ->cols([$this->getTableName().'.'.$this->getPrimaryKey()])
            ->from($this->getTableName());

        // Handle tables that need to be joined with another table to get the scrubbable record
        $scrubbableKey = $this->getScrubbableKey();
        if (is_array($scrubbableKey)) {
            list($keyID, $tableJoin, $tableJoinID) = $scrubbableKey;
            $query->innerJoin($tableJoin, $tableJoin.'.'.$tableJoinID.'='.$this->getTableName().'.'.$tableJoinID)
                  ->innerJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID='.$tableJoin.'.'.$keyID);
        } else if (is_string($scrubbableKey)) {
            $query->innerJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID='.$this->getTableName().'.'.$this->getScrubbableKey());
        }

        // Apply the context based on user role
        if (!empty($context)) {
            $query->innerJoin('gibbonRole', 'gibbonRole.gibbonRoleID=gibbonPerson.gibbonRoleIDPrimary')
                ->where('FIND_IN_SET(gibbonRole.category, :roleCategories)')
                ->bindValue('roleCategories', implode(',', $context));
        }

        // Only get users whose dateEnd is before the cutoff, falling back to using the lastTimestamp
        $query->where('((gibbonPerson.dateEnd IS NOT NULL AND gibbonPerson.dateEnd < :cutoffDate) OR (gibbonPerson.dateEnd IS NULL AND gibbonPerson.lastTimestamp IS NOT NULL AND gibbonPerson.lastTimestamp < :cutoffDate))')
            ->bindValue('cutoffDate', $cutoffDate);

        return $this->runSelect($query)->fetchAll(\PDO::FETCH_COLUMN, 0);
    }
}
