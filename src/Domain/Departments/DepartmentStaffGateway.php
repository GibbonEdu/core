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

use Gibbon\Domain\DataSet;
use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryableGateway;

/**
 * Class CourseGateway
 * @package Gibbon\Domain\Departments
 */
class DapartmentStaffGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonDepartmentStaff';

    private static $searchableColumns = [];

    /**
     * queryByCourseClass
     * @param int $courseClassID
     * @return DataSet
     */
    public function getRole(int $departmentID, int $personID)
    {
        $query = $this
            ->newQuery()
            ->from('gibbonDepartmentStaff')
            ->cols(['gibbonDepartmentStaff.role'])
            ->where('gibbonDepartmentID = :gibbonDepartmentID')
            ->where('gibbonPersonID = :gibbonPersonID')
            ->bindValue('gibbonDepartmentID', $departmentID)
            ->bindValue('gibbonPersonID', $personID);

        $result = $this->runQuery($query,$this->newQueryCriteria());

        return $result->getResultCount() === 1 ? $result->toArray()[0]['role']: false;
    }
}
