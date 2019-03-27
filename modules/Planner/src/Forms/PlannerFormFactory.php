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

namespace Gibbon\Module\Planner\Forms;

use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Contracts\Database\Connection;

/**
 * PlannerFormFactory
 *
 * @version v16
 * @since   v16
 */
class PlannerFormFactory extends DatabaseFormFactory
{
    /**
     * Create and return an instance of DatabaseFormFactory.
     * @return  object DatabaseFormFactory
     */
    public static function create(Connection $pdo = null)
    {
        return new PlannerFormFactory($pdo);
    }

    public function createSelectOutcome($name, $gibbonYearGroupIDList, $gibbonDepartmentID)
    {
        // Get School Outcomes
        $data = ['gibbonYearGroupIDList' => $gibbonYearGroupIDList];
        $sql = "SELECT category AS groupBy, CONCAT('all ', category) as chainedTo, gibbonOutcomeID AS value, gibbonOutcome.name AS name 
                FROM gibbonOutcome 
                JOIN gibbonYearGroup ON (FIND_IN_SET(gibbonYearGroup.gibbonYearGroupID, gibbonOutcome.gibbonYearGroupIDList))
                WHERE active='Y' AND scope='School' 
                AND FIND_IN_SET(gibbonYearGroup.gibbonYearGroupID, :gibbonYearGroupIDList) 
                GROUP BY gibbonOutcome.gibbonOutcomeID
                ORDER BY groupBy, name";

        // Get Departmental Outcomes
        $data2 = ['gibbonYearGroupIDList' => $gibbonYearGroupIDList, 'gibbonDepartmentID' => $gibbonDepartmentID];
        $sql2 = "SELECT CONCAT(gibbonDepartment.name, ': ', category) AS groupBy, CONCAT('all ', category) as chainedTo, gibbonOutcomeID AS value, gibbonOutcome.name AS name 
                FROM gibbonOutcome 
                JOIN gibbonDepartment ON (gibbonOutcome.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) 
                JOIN gibbonYearGroup ON (FIND_IN_SET(gibbonYearGroup.gibbonYearGroupID, gibbonOutcome.gibbonYearGroupIDList))
                WHERE active='Y' AND scope='Learning Area'
                AND gibbonDepartment.gibbonDepartmentID=:gibbonDepartmentID 
                AND FIND_IN_SET(gibbonYearGroup.gibbonYearGroupID, :gibbonYearGroupIDList) 
                GROUP BY gibbonOutcome.gibbonOutcomeID
                ORDER BY groupBy, gibbonOutcome.name";

        $col = $this->createColumn($name.'Col')->setClass('');

        $col->addSelect($name)
            ->setClass('addBlock floatNone standardWidth')
            ->fromArray(['' => __('Choose an outcome to add it to this lesson')])
            ->fromArray([__('SCHOOL OUTCOMES') => []])
            // ->fromQuery($this->pdo, $sql, $data, 'groupBy')
            ->fromQueryChained($this->pdo, $sql, $data, $name.'Filter', 'groupBy')

            ->fromArray([__('LEARNING AREAS') => []])
            // ->fromQuery($this->pdo, $sql2, $data2, 'groupBy');
            ->fromQueryChained($this->pdo, $sql2, $data2, $name.'Filter', 'groupBy');

        $data3 = ['gibbonYearGroupIDList' => $gibbonYearGroupIDList];
        $sql3 = "SELECT category as value, category as name
                FROM gibbonOutcome
                JOIN gibbonYearGroup ON (FIND_IN_SET(gibbonYearGroup.gibbonYearGroupID, gibbonOutcome.gibbonYearGroupIDList))
                WHERE active='Y' AND FIND_IN_SET(gibbonYearGroup.gibbonYearGroupID, :gibbonYearGroupIDList) 
                GROUP BY gibbonOutcome.category";

        $col->addSelect($name.'Filter')
            ->setClass('floatNone standardWidth mt-px')
            ->fromArray(['all' => __('View All')])
            ->fromQuery($this->pdo, $sql3, $data3);

        return $col;
    }
}
