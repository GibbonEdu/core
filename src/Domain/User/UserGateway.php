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
use Gibbon\Domain\Traits\SharedUserLogic;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * User Gateway
 *
 * @version v16
 * @since   v16
 */
class UserGateway extends QueryableGateway
{
    use TableAware;
    use SharedUserLogic;

    private static $tableName = 'gibbonPerson';

    private static $searchableColumns = ['preferredName', 'surname', 'username', 'studentID', 'email', 'emailAlternate', 'phone1', 'phone2', 'phone3', 'phone4', 'vehicleRegistration', 'gibbonRole.name'];
    
    /**
     * Queries the list of users for the Manage Users page.
     *
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryAllUsers(QueryCriteria $criteria)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonPerson.gibbonPersonID', 'gibbonPerson.surname', 'gibbonPerson.preferredName', 'gibbonPerson.username', 
                'gibbonPerson.image_240', 'gibbonPerson.status', 'gibbonRole.name as primaryRole'
            ])
            ->leftJoin('gibbonRole', 'gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID');

        $criteria->addFilterRules($this->getSharedUserFilterRules());

        return $this->runQuery($query, $criteria);
    }

    /**
     * Selects the family info for a subset of users. Primarily used to join family data to the queryAllUsers results.
     *
     * @param string|array $gibbonPersonIDList
     * @return Result
     */
    public function selectFamilyDetailsByPersonID($gibbonPersonIDList)
    {
        $idList = is_array($gibbonPersonIDList) ? implode(',', $gibbonPersonIDList) : $gibbonPersonIDList;
        $data = array('idList' => $idList);
        $sql = "(
            SELECT LPAD(gibbonFamilyAdult.gibbonPersonID, 10, '0'), gibbonFamilyAdult.gibbonFamilyID, 'adult' AS role, gibbonFamily.name, (SELECT gibbonFamilyChild.gibbonPersonID FROM gibbonFamilyChild JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID ORDER BY gibbonPerson.dob DESC LIMIT 1) as gibbonPersonIDStudent
            FROM gibbonFamily 
            JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) 
            WHERE FIND_IN_SET(gibbonFamilyAdult.gibbonPersonID, :idList)
        ) UNION (
            SELECT LPAD(gibbonFamilyChild.gibbonPersonID, 10, '0'), gibbonFamilyChild.gibbonFamilyID, 'child' AS role, gibbonFamily.name, gibbonFamilyChild.gibbonPersonID as gibbonPersonIDStudent
            FROM gibbonFamily 
            JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) 
            WHERE FIND_IN_SET(gibbonFamilyChild.gibbonPersonID, :idList)
        ) ORDER BY gibbonFamilyID";

        return $this->db()->select($sql, $data);
    }

    public function selectUserNamesByStatus($status = 'Full')
    {
        $data = array('statusList' => is_array($status) ? implode(',', $status) : $status );
        $sql = "SELECT gibbonPersonID, surname, preferredName, status, username, gibbonRole.category as roleCategory
                FROM gibbonPerson 
                JOIN gibbonRole ON (gibbonRole.gibbonRoleID=gibbonPerson.gibbonRoleIDPrimary)
                WHERE FIND_IN_SET(gibbonPerson.status, :statusList) 
                ORDER BY surname, preferredName";

        return $this->db()->select($sql, $data);
    }
}
