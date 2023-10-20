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
trait ScrubByTimestamp
{
    public function getScrubbableRecords(string $cutoffDate, array $context = []) : array
    {
        // Only get records whose timestamp field is before the cutoff date
        $query = $this
            ->newSelect()
            ->cols([$this->getTableName().'.'.$this->getPrimaryKey(), 'NULL as gibbonPersonID'])
            ->from($this->getTableName());

        // Handle tables that need to be joined with another table to get the scrubbable record
        $scrubbableKey = $this->getScrubbableKey();
        if (is_array($scrubbableKey)) {
            list($keyID, $tableJoin, $tableJoinID) = $scrubbableKey;

            $query->innerJoin($tableJoin, $tableJoin.'.'.$tableJoinID.'='.$this->getTableName().'.'.$tableJoinID)
                ->where($tableJoin.'.'.$keyID.' < :cutoffDate')
                ->bindValue('cutoffDate', $cutoffDate);
        } else if (is_string($scrubbableKey)) {
            $query->where($this->getTableName().'.'.$scrubbableKey.' < :cutoffDate')
                ->bindValue('cutoffDate', $cutoffDate);
        }

        return $this->runSelect($query)->fetchGroupedUnique();
    }
}
