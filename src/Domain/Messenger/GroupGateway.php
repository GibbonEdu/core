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

namespace Gibbon\Domain\Messenger;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * Group Gateway
 *
 * @version v16
 * @since   v16
 */
class GroupGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonGroup';

    private static $searchableColumns = ['name'];
    
    /**
     * Queries the list of groups for the messenger Manage Groups page.
     *
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryGroups(QueryCriteria $criteria)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonGroup.gibbonGroupID', 'gibbonGroup.name', 'gibbonPerson.surname', 'gibbonPerson.preferredName', 'COUNT(DISTINCT gibbonGroupPersonID) as count'
            ])
            ->leftJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=gibbonGroup.gibbonPersonIDOwner')
            ->leftJoin('gibbonGroupPerson', 'gibbonGroupPerson.gibbonGroupID=gibbonGroup.gibbonGroupID')
            ->groupBy(['gibbonGroup.gibbonGroupID']);

        return $this->runQuery($query, $criteria);
    }

    public function insertGroup(array $data)
    {
        $sql = "INSERT INTO gibbonGroup SET gibbonPersonIDOwner=:gibbonPersonIDOwner, name=:name, timestampCreated=NOW()";

        return $this->db()->insert($sql, $data);
    }

    public function insertGroupPerson(array $data)
    {
        $sql = "INSERT INTO gibbonGroupPerson SET gibbonGroupID=:gibbonGroupID, gibbonPersonID=:gibbonPersonID ON DUPLICATE KEY UPDATE gibbonPersonID=:gibbonPersonID";

        return $this->db()->insert($sql, $data);
    }

    public function updateGroup(array $data)
    {
        $sql = "UPDATE gibbonGroup SET gibbonPersonIDOwner=:gibbonPersonIDOwner, name=:name WHERE gibbonGroupID=:gibbonGroupID";

        return $this->db()->update($sql, $data);
    }

    public function deleteGroup($gibbonGroupID)
    {
        $this->deletePeopleByGroupID($gibbonGroupID);

        $data = array('gibbonGroupID' => $gibbonGroupID);
        $sql = "DELETE FROM gibbonGroup WHERE gibbonGroupID=:gibbonGroupID";

        return $this->db()->delete($sql, $data);
    }

    public function deleteGroupPerson($gibbonGroupPersonID)
    {
        $data = array('gibbonGroupPersonID' => $gibbonGroupPersonID);
        $sql = "DELETE FROM gibbonGroupPerson WHERE gibbonGroupPersonID=:gibbonGroupPersonID";

        return $this->db()->delete($sql, $data);
    }

    public function deletePeopleByGroupID($gibbonGroupID)
    {
        $data = array('gibbonGroupID' => $gibbonGroupID);
        $sql = "DELETE FROM gibbonGroupPerson WHERE gibbonGroupID=:gibbonGroupID";

        return $this->db()->delete($sql, $data);
    }
}
