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
    private static $searchableColumns = [];
    
    /**
     * Queries the list of groups for the messenger Manage Groups page.
     *
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryGroups(QueryCriteria $criteria, $gibbonPersonIDOwner = null)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonGroup.gibbonGroupID', 'gibbonGroup.name', 'gibbonPerson.surname', 'gibbonPerson.preferredName', 'COUNT(DISTINCT gibbonGroupPersonID) as count', 'gibbonSchoolYear.name as schoolYear'
            ])
            ->innerJoin('gibbonSchoolYear', 'gibbonSchoolYear.gibbonSchoolYearID=gibbonGroup.gibbonSchoolYearID')
            ->leftJoin('gibbonGroupPerson', 'gibbonGroupPerson.gibbonGroupID=gibbonGroup.gibbonGroupID')
            ->leftJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=gibbonGroup.gibbonPersonIDOwner')
            ->groupBy(['gibbonGroup.gibbonGroupID']);

        if (!empty($gibbonPersonIDOwner)) {
            $query->where('gibbonGroup.gibbonPersonIDOwner = :gibbonPersonIDOwner')
                  ->bindValue('gibbonPersonIDOwner', $gibbonPersonIDOwner);
        }
        
        return $this->runQuery($query, $criteria);
    }

    /**
     * Queries the group members based on group ID.
     * @param QueryCriteria $criteria
     * @param string $gibbonGroupID
     * @return DataSet
     */
    public function queryGroupMembers(QueryCriteria $criteria, $gibbonGroupID)
    {
        $query = $this
            ->newQuery()
            ->from('gibbonGroupPerson')
            ->cols(['gibbonGroupPerson.gibbonGroupID', 'gibbonGroupPerson.gibbonPersonID', 'gibbonPerson.surname', 'gibbonPerson.preferredName', 'gibbonPerson.email'])
            ->innerJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=gibbonGroupPerson.gibbonPersonID')
            ->where('gibbonGroupPerson.gibbonGroupID = :gibbonGroupID')
            ->bindValue('gibbonGroupID', $gibbonGroupID);

        return $this->runQuery($query, $criteria);
    }

    public function selectGroupByID($gibbonGroupID)
    {
        $data = array('gibbonGroupID' => $gibbonGroupID);
        $sql = "SELECT * FROM gibbonGroup WHERE gibbonGroupID=:gibbonGroupID";

        return $this->db()->select($sql, $data);
    }

    public function selectGroupByIDAndOwner($gibbonGroupID, $gibbonPersonIDOwner)
    {
        $data = array('gibbonGroupID' => $gibbonGroupID, 'gibbonPersonIDOwner' => $gibbonPersonIDOwner);
        $sql = "SELECT * FROM gibbonGroup WHERE gibbonGroupID=:gibbonGroupID AND gibbonPersonIDOwner=:gibbonPersonIDOwner";

        return $this->db()->select($sql, $data);
    }

    public function selectGroupPersonByID($gibbonGroupID, $gibbonPersonID)
    {
        $data = array('gibbonGroupID' => $gibbonGroupID, 'gibbonPersonID' => $gibbonPersonID);
        $sql = "SELECT * FROM gibbonGroupPerson WHERE gibbonGroupID=:gibbonGroupID AND gibbonPersonID=:gibbonPersonID";

        return $this->db()->select($sql, $data);
    }

    public function insertGroup(array $data)
    {
        $sql = "INSERT INTO gibbonGroup SET gibbonPersonIDOwner=:gibbonPersonIDOwner, gibbonSchoolYearID=:gibbonSchoolYearID, name=:name, timestampCreated=NOW()";

        return $this->db()->insert($sql, $data);
    }

    public function insertGroupPerson(array $data)
    {
        $sql = "INSERT INTO gibbonGroupPerson SET gibbonGroupID=:gibbonGroupID, gibbonPersonID=:gibbonPersonID ON DUPLICATE KEY UPDATE gibbonPersonID=:gibbonPersonID";

        return $this->db()->insert($sql, $data);
    }

    public function updateGroup(array $data)
    {
        $sql = "UPDATE gibbonGroup SET name=:name WHERE gibbonGroupID=:gibbonGroupID";

        return $this->db()->update($sql, $data);
    }

    public function deleteGroup($gibbonGroupID)
    {
        $data = array('gibbonGroupID' => $gibbonGroupID);
        $sql = "DELETE FROM gibbonGroup WHERE gibbonGroupID=:gibbonGroupID";

        return $this->db()->delete($sql, $data);
    }

    public function deleteGroupPerson($gibbonGroupID, $gibbonPersonID)
    {
        $data = array('gibbonGroupID' => $gibbonGroupID, 'gibbonPersonID' => $gibbonPersonID);
        $sql = "DELETE FROM gibbonGroupPerson WHERE gibbonGroupID=:gibbonGroupID AND gibbonPersonID=:gibbonPersonID";

        return $this->db()->delete($sql, $data);
    }

    public function deletePeopleByGroupID($gibbonGroupID)
    {
        $data = array('gibbonGroupID' => $gibbonGroupID);
        $sql = "DELETE FROM gibbonGroupPerson WHERE gibbonGroupID=:gibbonGroupID";

        return $this->db()->delete($sql, $data);
    }
}
