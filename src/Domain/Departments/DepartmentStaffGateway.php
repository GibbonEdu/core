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

namespace Gibbon\Domain\Departments;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * @version v27
 * @since   v27
 */
class DepartmentStaffGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonDepartmentStaff';
    private static $primaryKey = 'gibbonDepartmentStaffID';

    private static $searchableColumns = ['role'];
    
    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */


     public function seletStaffListByDepartment($gibbonDepartmentID)
     {
        $data = ['gibbonDepartmentID' => $gibbonDepartmentID];
        $sql = "SELECT gibbonPerson.gibbonPersonID, gibbonDepartmentStaff.role, title, surname, preferredName, image_240, gibbonStaff.jobTitle, FIND_IN_SET(role, 'Manager,Assistant Coordinator,Coordinator,Director') as roleOrder
            FROM gibbonDepartmentStaff 
            JOIN gibbonPerson ON (gibbonDepartmentStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) 
            JOIN gibbonStaff ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) 
            WHERE status='Full' AND gibbonDepartmentID=:gibbonDepartmentID 
            ORDER BY roleOrder DESC, surname, preferredName";
            
        return $this->db()->select($sql, $data);
     }

}