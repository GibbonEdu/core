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
        //Get school outcomes
        $countClause = 0;
        $years = explode(',', $gibbonYearGroupIDList);
        $data = array();
        $sql = '';
        foreach ($years as $year) {
            $data['clause'.$countClause] = '%'.$year.'%';
            $sql .= "(SELECT category AS groupBy, gibbonOutcomeID AS value, name AS name FROM gibbonOutcome WHERE active='Y' AND scope='School' AND gibbonYearGroupIDList LIKE :clause".$countClause.') UNION ';
            ++$countClause;
        }
        $sql = substr($sql, 0, -6).' ORDER BY groupBy, name';

        //Get departmental Outcomes
        $data2 = array('gibbonDepartmentID' => $gibbonDepartmentID);
        $sql2 = '';
        foreach ($years as $year) {
            $data2['clause'.$countClause] = '%'.$year.'%';
            $sql2 .= "(SELECT gibbonDepartment.name AS groupBy, gibbonOutcomeID AS value, gibbonOutcome.name AS name FROM gibbonOutcome JOIN gibbonDepartment ON (gibbonOutcome.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE active='Y' AND scope='Learning Area' AND gibbonDepartment.gibbonDepartmentID=:gibbonDepartmentID AND gibbonYearGroupIDList LIKE :clause".$countClause.') UNION ';
            ++$countClause;
        }
        $sql2 = substr($sql2, 0, -6).' ORDER BY groupBy, name';

        return $this->createSelect($name)
            ->fromArray(array('' => __('Choose an outcome to add it to this lesson')))
            ->fromQuery($this->pdo, $sql, $data, 'groupBy')
            ->fromQuery($this->pdo, $sql2, $data2, 'groupBy');
    }
}
