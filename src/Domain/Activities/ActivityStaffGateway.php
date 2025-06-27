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

namespace Gibbon\Domain\Activities;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * Activity Staff Gateway
 *
 * @version v22
 * @since   v22
 */
class ActivityStaffGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonActivityStaff';
    private static $primaryKey = 'gibbonActivityStaffID';

    private static $searchableColumns = ['surname', 'preferredName'];

    public function selectActivityStaff($gibbonActivityID) {
        $select = $this
            ->newSelect()
            ->cols(['preferredName, surname, gibbonActivityStaff.*'])
            ->from($this->getTableName())
            ->leftJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=gibbonActivityStaff.gibbonPersonID')
            ->where('gibbonActivityStaff.gibbonActivityID = :gibbonActivityID')
            ->bindValue('gibbonActivityID', $gibbonActivityID)
            ->where('gibbonPerson.status="Full"')
            ->orderBy(['surname', 'preferredName']);

        return $this->runSelect($select);
    }
    
    public function queryUnassignedStaffByCategory($criteria, $gibbonActivityCategoryID)
    {
        $query = $this
            ->newQuery()
            ->from('gibbonPerson')
            ->cols([
                '0 as gibbonActivityID',
                'gibbonActivityCategory.gibbonActivityCategoryID',
                'gibbonActivityCategory.name as eventName',
                'gibbonActivityCategory.nameShort as eventNameShort',
                'gibbonPerson.gibbonPersonID',
                'gibbonPerson.surname',
                'gibbonPerson.preferredName',
                'gibbonPerson.title',
                'gibbonPerson.email',
                'gibbonPerson.image_240',
                'gibbonStaff.type',
                'gibbonStaff.jobTitle',
                'gibbonStaff.initials',
            ])
            ->innerJoin('gibbonStaff', 'gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID')
            ->leftJoin('gibbonActivityCategory', 'gibbonActivityCategory.gibbonActivityCategoryID=:gibbonActivityCategoryID')
            ->leftJoin('gibbonActivity', 'gibbonActivity.gibbonActivityCategoryID=gibbonActivityCategory.gibbonActivityCategoryID')
            ->leftJoin('gibbonActivityStaff', 'gibbonActivityStaff.gibbonActivityID=gibbonActivity.gibbonActivityID AND gibbonActivityStaff.gibbonPersonID=gibbonPerson.gibbonPersonID')
            ->bindValue('gibbonActivityCategoryID', $gibbonActivityCategoryID)
            ->where("gibbonPerson.status = 'Full'")
            ->where('(gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart <= :today)')
            ->where('(gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd >= :today)')
            ->bindValue('today', date('Y-m-d'))
            ->groupBy(['gibbonPerson.gibbonPersonID'])
            ->having('COUNT(DISTINCT gibbonActivityStaff.gibbonActivityStaffID) = 0');

        $criteria->addFilterRules([
            'type' => function ($query, $gibbonActivityCategoryID) {
                return $query
                    ->where('gibbonActivityCategory.gibbonActivityCategoryID = :gibbonActivityCategoryID')
                    ->bindValue('gibbonActivityCategoryID', $gibbonActivityCategoryID);
            },
        ]);

        return $this->runQuery($query, $criteria);
    }

    public function selectStaffByCategory($gibbonActivityCategoryID)
    {
        $data = ['gibbonActivityCategoryID' => $gibbonActivityCategoryID];
        $sql = "SELECT gibbonPerson.gibbonPersonID as groupBy,
                    gibbonActivity.gibbonActivityID,
                    gibbonActivityStaff.gibbonActivityStaffID,
                    gibbonActivityStaff.role,
                    gibbonPerson.gibbonPersonID,
                    gibbonPerson.surname,
                    gibbonPerson.preferredName,
                    gibbonPerson.image_240,
                    gibbonStaff.type
                FROM gibbonActivityStaff
                JOIN gibbonActivity ON (gibbonActivity.gibbonActivityID=gibbonActivityStaff.gibbonActivityID)
                JOIN gibbonPerson ON (gibbonPerson.gibbonPersonID=gibbonActivityStaff.gibbonPersonID) 
                LEFT JOIN gibbonStaff ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID)
                WHERE gibbonActivity.gibbonActivityCategoryID=:gibbonActivityCategoryID
                AND gibbonPerson.status='Full'
                ORDER BY gibbonActivityStaff.role DESC, gibbonPerson.surname, gibbonPerson.preferredName";

        return $this->db()->select($sql, $data);
    }

    public function selectStaffByActivity($gibbonActivityID) {
        $gibbonActivityID = is_array($gibbonActivityID) ? $gibbonActivityID : [$gibbonActivityID];
        $data = ['gibbonActivityID' => $gibbonActivityID];
        $sql = "SELECT gibbonActivity.*, gibbonActivityStaff.gibbonPersonID, gibbonActivityStaff.role 
            FROM gibbonActivity 
            JOIN gibbonActivityStaff ON (gibbonActivity.gibbonActivityID=gibbonActivityStaff.gibbonActivityID) 
            WHERE gibbonActivity.gibbonActivityID=:gibbonActivityID 
            AND gibbonActivityStaff.role='Organiser' AND active='Y' 
            ORDER BY name";

        return $this->db()->select($sql, $data);
    }

    public function selectActivityOrganiserByPerson($gibbonActivityID, $gibbonPersonID) {
        $data = ['gibbonPersonID' => $gibbonPersonID, 'gibbonActivityID' => $gibbonActivityID];
        $sql = "SELECT gibbonActivity.*, NULL as status, gibbonActivityStaff.role FROM gibbonActivity JOIN gibbonActivityStaff ON (gibbonActivity.gibbonActivityID=gibbonActivityStaff.gibbonActivityID) WHERE gibbonActivity.gibbonActivityID=:gibbonActivityID AND gibbonActivityStaff.gibbonPersonID=:gibbonPersonID AND gibbonActivityStaff.role='Organiser' AND active='Y' ORDER BY name";

        return $this->db()->select($sql, $data);
    }

    public function getActivityAccessByStaff($gibbonActivityID, $gibbonPersonID) {
        $data = ['gibbonPersonID' => $gibbonPersonID, 'gibbonActivityID' => $gibbonActivityID];
        $sql = "SELECT gibbonActivity.*, NULL as status, gibbonActivityStaff.role FROM gibbonActivity JOIN gibbonActivityStaff ON (gibbonActivity.gibbonActivityID=gibbonActivityStaff.gibbonActivityID) WHERE gibbonActivity.gibbonActivityID=:gibbonActivityID AND gibbonActivityStaff.gibbonPersonID=:gibbonPersonID AND active='Y' ORDER BY name";

        return $this->db()->selectOne($sql, $data);
    }

    public function selectStaffByCategoryAndPerson($gibbonActivityCategoryID, $gibbonPersonID)
    {
        $data = ['gibbonActivityCategoryID' => $gibbonActivityCategoryID, 'gibbonPersonID' => $gibbonPersonID];
        $sql = "SELECT gibbonActivity.gibbonActivityID,
                    gibbonActivity.gibbonActivityCategoryID,
                    gibbonActivity.name,
                    gibbonActivityStaff.gibbonActivityStaffID,
                    gibbonActivityStaff.gibbonPersonID,
                    gibbonActivityStaff.role
                FROM gibbonActivityStaff
                JOIN gibbonActivity ON (gibbonActivity.gibbonActivityID=gibbonActivityStaff.gibbonActivityID)
                JOIN gibbonPerson ON (gibbonPerson.gibbonPersonID=gibbonActivityStaff.gibbonPersonID) 
                WHERE gibbonActivity.gibbonActivityCategoryID=:gibbonActivityCategoryID
                AND gibbonActivityStaff.gibbonPersonID=:gibbonPersonID";

        return $this->db()->select($sql, $data);
    }

    public function deleteStaffNotInList($gibbonActivityID, $personIDList)
    {
        $personIDList = is_array($personIDList) ? implode(',', $personIDList) : $personIDList;

        $data = ['gibbonActivityID' => $gibbonActivityID, 'personIDList' => $personIDList];
        $sql = "DELETE FROM gibbonActivityStaff WHERE gibbonActivityID=:gibbonActivityID AND NOT FIND_IN_SET(gibbonPersonID, :personIDList)";

        return $this->db()->delete($sql, $data);
    }

    public function selectActivityStaffByID($gibbonActivityID, $gibbonPersonID) {
        return $this->selectBy([
            'gibbonPersonID' 	=> $gibbonPersonID,
            'gibbonActivityID' 	=> $gibbonActivityID
        ]);
    }

    public function insertActivityStaff($gibbonActivityID, $gibbonPersonID, $role) {
        return $this->insert([
            'gibbonPersonID' 	=> $gibbonPersonID,
            'gibbonActivityID' 	=> $gibbonActivityID,
            'role'				=> $role
        ]);
    }
}
