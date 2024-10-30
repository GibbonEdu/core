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

/**
 * Implements the ScrubbableGateway interface.
 */
trait Scrubbable
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

    /**
     * Cycles through all scrubbable records in this gateway to overwrite certain fields.
     * Handles randomizing string data as well as deleting files.
     * The getScrubbableRecords method is implemented by separate traits.
     *
     * @param string $cutoffDate
     * @param array $context
     * @return array returns and array of scrubbed tables per user
     */
    public function scrub(string $cutoffDate, array $context = []) : array
    {
        // Select scrubbable records
        $scrubbable = $this->getScrubbableRecords($cutoffDate, $context);
        $scrubbed = [];

        // Get scrubbable columns, randomize values as needed
        $columns = $this->getScrubbableColumns();
        $columns = array_map(function ($item) {
            return $item == 'randomString'
                ? $this->randomString(20)
                : $item;
        }, $columns);

        // Handle files that need deleted along with the scrub
        $absolutePath = $this->db()->selectOne("SELECT value FROM gibbonSetting WHERE scope='System' AND name='absolutePath'");
        foreach ($columns as $name => $property) {
            if ($property != 'deleteFile') continue;

            foreach ($scrubbable as $primaryKey => $values) {
                $values = $this->getByID($primaryKey, [$name]);
                $columns[$name] = 'Deleted File';

                if (is_file($absolutePath.'/'.$values[$name])) {
                    unlink($absolutePath.'/'.$values[$name]);
                }
            }
        }

        // Update scrubbable records
        foreach ($scrubbable as $primaryKey => $values) {
            $query = $this
                ->newUpdate()
                ->cols($columns)
                ->table($this->getTableName())
                ->where($this->getPrimaryKey().' = :primaryKey')
                ->bindValue('primaryKey', $primaryKey);

            $success = $this->runUpdate($query);

            $scrubbed[$values['gibbonPersonID']][$this->getTableName()] = $success;
        }

        return $scrubbed;
    }

    protected function randomString($length)
    {
        $charList = 'abcdefghijkmnopqrstuvwxyz0123456789';
        $output = '';

        //Generate the password
        for ($i = 0; $i < $length; ++$i) {
            $output .= substr($charList, rand(1, strlen($charList)), 1);
        }

        return $output;
    }
}
