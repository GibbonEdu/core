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

namespace Gibbon\Domain\Departments;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * @version v17
 * @since   v17
 */
class DepartmentGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonDepartment';
    private static $primaryKey = 'gibbonDepartmentID';

    private static $searchableColumns = ['name'];
    
    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryDepartments(QueryCriteria $criteria, $type = null)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonDepartmentID', 'name', 'nameShort', 'type', 'subjectListing', 'blurb', 'logo'
            ]);

        if (!empty($type)) {
            $query->where('gibbonDepartment.type = :type')
                  ->bindValue('type', $type);
        }

        return $this->runQuery($query, $criteria);
    }

    public function selectStaffByDepartment($gibbonDepartmentID)
    {
        $data = array('gibbonDepartmentID' => $gibbonDepartmentID);
        $sql = "SELECT preferredName, surname, title
                FROM gibbonDepartmentStaff 
                JOIN gibbonPerson ON (gibbonDepartmentStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) 
                WHERE gibbonPerson.status='Full' AND gibbonDepartmentID=:gibbonDepartmentID 
                ORDER BY surname, preferredName";

        return $this->db()->select($sql, $data);
    }

    public function selectMemberOfDepartmentByRole($gibbonDepartmentID, $gibbonPersonID, array $roles = ['Teacher'])
    {
        $data = array('gibbonDepartmentID' => $gibbonDepartmentID, 'gibbonPersonID' => $gibbonPersonID, 'roles' => implode(',', $roles));
        $sql = "SELECT gibbonDepartmentStaff.* 
                FROM gibbonDepartment 
                JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) 
                WHERE gibbonDepartment.gibbonDepartmentID=:gibbonDepartmentID 
                AND gibbonDepartmentStaff.gibbonPersonID=:gibbonPersonID 
                AND FIND_IN_SET(gibbonDepartmentStaff.role, :roles)";

        return $this->db()->select($sql, $data);
    }
}
