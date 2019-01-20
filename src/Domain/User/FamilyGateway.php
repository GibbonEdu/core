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

namespace Gibbon\Domain\User;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * @version v16
 * @since   v16
 */
class FamilyGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonFamily';

    private static $searchableColumns = ['name'];
    
    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryFamilies(QueryCriteria $criteria)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonFamilyID', 'name', 'status'
            ]);

        return $this->runQuery($query, $criteria);
    }

    public function selectAdultsByFamily($gibbonFamilyIDList)
    {
        $gibbonFamilyIDList = is_array($gibbonFamilyIDList) ? implode(',', $gibbonFamilyIDList) : $gibbonFamilyIDList;
        $data = array('gibbonFamilyIDList' => $gibbonFamilyIDList);
        $sql = "SELECT gibbonFamilyAdult.gibbonFamilyID, gibbonPerson.title, gibbonPerson.preferredName, gibbonPerson.surname, gibbonPerson.status, gibbonPerson.email
            FROM gibbonFamilyAdult
            JOIN gibbonPerson ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID)
            WHERE FIND_IN_SET(gibbonFamilyAdult.gibbonFamilyID, :gibbonFamilyIDList) 
            ORDER BY gibbonPerson.surname, gibbonPerson.preferredName";

        return $this->db()->select($sql, $data);
    }

    public function selectChildrenByFamily($gibbonFamilyIDList)
    {
        $gibbonFamilyIDList = is_array($gibbonFamilyIDList) ? implode(',', $gibbonFamilyIDList) : $gibbonFamilyIDList;
        $data = array('gibbonFamilyIDList' => $gibbonFamilyIDList);
        $sql = "SELECT gibbonFamilyChild.gibbonFamilyID, '' as title, gibbonPerson.preferredName, gibbonPerson.surname, gibbonPerson.status, gibbonPerson.email
            FROM gibbonFamilyChild
            JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID)
            WHERE FIND_IN_SET(gibbonFamilyChild.gibbonFamilyID, :gibbonFamilyIDList) 
            ORDER BY gibbonPerson.surname, gibbonPerson.preferredName";

        return $this->db()->select($sql, $data);
    }

    public function selectFamilyAdultsByStudent($gibbonPersonID, $allUsers = false)
    {
        $gibbonPersonIDList = is_array($gibbonPersonID) ? implode(',', $gibbonPersonID) : $gibbonPersonID;
        $data = array('gibbonPersonIDList' => $gibbonPersonIDList);
        $sql = "SELECT gibbonFamilyChild.gibbonPersonID, gibbonFamilyAdult.gibbonFamilyID, gibbonPerson.*, gibbonFamilyAdult.childDataAccess, gibbonFamilyAdult.contactEmail, gibbonFamilyAdult.contactCall
            FROM gibbonFamilyChild
            JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamilyChild.gibbonFamilyID)
            JOIN gibbonPerson ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID)
            WHERE FIND_IN_SET(gibbonFamilyChild.gibbonPersonID, :gibbonPersonIDList)";

        if (!$allUsers) $sql .= " AND gibbonPerson.status='Full'";

        $sql .= " ORDER BY gibbonFamilyAdult.contactPriority, gibbonPerson.surname, gibbonPerson.preferredName";

        return $this->db()->select($sql, $data);
    }

    public function selectFamiliesByStudent($gibbonPersonID)
    {
        $gibbonPersonIDList = is_array($gibbonPersonID) ? implode(',', $gibbonPersonID) : $gibbonPersonID;
        $data = array('gibbonPersonIDList' => $gibbonPersonIDList);
        $sql = "SELECT gibbonFamilyChild.gibbonPersonID, gibbonFamily.*
            FROM gibbonFamilyChild
            JOIN gibbonFamily ON (gibbonFamily.gibbonFamilyID=gibbonFamilyChild.gibbonFamilyID)
            WHERE FIND_IN_SET(gibbonFamilyChild.gibbonPersonID, :gibbonPersonIDList)
            ORDER BY gibbonFamily.name";

        return $this->db()->select($sql, $data);
    }
}
