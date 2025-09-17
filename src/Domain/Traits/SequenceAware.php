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
 * Provides methods for Gateway classes that implement a sequence number.
 */
trait SequenceAware
{
  
    /**
     * Get an internal pre-defined field name for the sequenceNumber column.
     *
     * @return array
     */
    public function getSequenceField()
    {
        return isset(self::$sequenceField)? self::$sequenceField : 'sequenceNumber';
    }

    /**
     * Gets the highest number in sequence.
     *
     * @return int
     */
    public function getSequenceNumber()
    {
        return $this->db()->selectOne("SELECT MAX({$this->getSequenceField()}) FROM `{$this->getTableName()}`");
    }

    /**
     * Gets the next number in sequence.
     *
     * @return int
     */
    public function getNextSequenceNumber()
    {
        return $this->getSequenceNumber()+1;
    }

    /**
     * Update all sequence numbers for a given set of IDs.
     *
     * @param array $order
     * @return bool
     */
    public function updateSequenceNumbers(array $order) : bool
    {
        if (empty($order)) return false; 
        
        $count = 1;
        $updated = false;

        foreach ($order as $itemID) {
            $updated &= $this->update($itemID, [$this->getSequenceField() => $count]);
            $count++;
        }

        return $updated;
    }

}
