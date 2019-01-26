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
 * Class CourseClassPersonGateway
 * @package Gibbon\Domain\Departments
 */
class CourseClassPersonGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonCourseClassPerson';

    private static $searchableColumns = [];

    public function queryFullByDate(int $courseClassID, string $startDate, string $endDate = '')
    {
        if (empty($endDate))
            $endDate = $startDate;
        $query = $this
            ->newQuery()
            ->from('gibbonCourseClassPerson')
            ->cols(['role', 'surname', 'preferredName', 'email', 'studentID'])
            ->innerJoin('gibbonPerson', 'gibbonCourseClassPerson.gibbonPersonID = gibbonPerson.gibbonPersonID')
            ->where('gibbonCourseClassPerson.gibbonCourseClassID=:gibbonCourseClassID')
            ->bindValue('gibbonCourseClassID', $courseClassID)
            ->where('(dateStart IS NULL OR dateStart <= :dateStart)')
            ->bindValue('dateStart', $startDate)
            ->where('(dateEnd IS NULL OR dateStart >= :dateEnd)')
            ->bindValue('dateEnd', $endDate)
            ->where('status = :status')
            ->bindValue('status', 'Full')
            ->orderBy(['role DESC', 'surname', 'preferredName'])
        ;

        return $this->runQuery($query, $this->newQueryCriteria());
    }
}
