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
trait ScrubByFamily
{
    public function getScrubbableRecords(string $cutoffDate, array $context = []) : array
    {
        $tableName = $this->getTableName();
        $scrubbableKey = $this->getScrubbableKey();

        $query = $this
            ->newSelect()
            ->cols([$this->getTableName().'.'.$this->getPrimaryKey(), 'gibbonPerson.gibbonPersonID'])
            ->from($this->getTableName())
            ->where("gibbonPerson.status='Left'");

        // Join the correct family relation based on the role category
        if (in_array('Student', $context)) {
            $query->innerJoin('gibbonFamilyChild', "gibbonFamilyChild.gibbonFamilyID={$tableName}.{$scrubbableKey}")
                ->innerJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=gibbonFamilyChild.gibbonPersonID');
        } else {
            $query->innerJoin('gibbonFamilyAdult', "gibbonFamilyAdult.gibbonFamilyID={$tableName}.{$scrubbableKey}")
                ->innerJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=gibbonFamilyAdult.gibbonPersonID');
        }

        // Apply the context based on user role
        if (!empty($context)) {
            $query->innerJoin('gibbonRole', 'gibbonRole.gibbonRoleID=gibbonPerson.gibbonRoleIDPrimary')
                ->where('FIND_IN_SET(gibbonRole.category, :roleCategories)')
                ->bindValue('roleCategories', implode(',', $context));
        }

        // Only get users whose dateEnd is before the cutoff, falling back to using the lastTimestamp
        $query->where('((gibbonPerson.dateEnd IS NOT NULL AND gibbonPerson.dateEnd < :cutoffDate) 
            OR (gibbonPerson.dateEnd IS NULL AND gibbonPerson.lastTimestamp IS NOT NULL AND gibbonPerson.lastTimestamp < :cutoffDate) 
            OR (gibbonPerson.dateEnd IS NULL AND gibbonPerson.lastTimestamp IS NULL))')
            ->bindValue('cutoffDate', $cutoffDate);

        // Check that all members of this family are no longer active
        $query->cols([
            "(SELECT COUNT(p.gibbonPersonID) FROM gibbonFamilyAdult AS fa JOIN gibbonPerson AS p ON (fa.gibbonPersonID=p.gibbonPersonID) WHERE p.status <> 'Left' AND fa.gibbonFamilyID=$tableName.$scrubbableKey) as activeAdults", 
            "(SELECT COUNT(p.gibbonPersonID) FROM gibbonFamilyChild AS fc JOIN gibbonPerson AS p ON (fc.gibbonPersonID=p.gibbonPersonID) WHERE p.status <> 'Left' AND fc.gibbonFamilyID=$tableName.$scrubbableKey) as activeChildren"])
            ->having("(activeAdults + activeChildren) = 0");

        return $this->runSelect($query)->fetchGroupedUnique();
    }
}
