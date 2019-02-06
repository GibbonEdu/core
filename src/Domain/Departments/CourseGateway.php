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
class CourseGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonCourse';

    private static $searchableColumns = [];

    /**
     * queryByCourseClass
     * @param int $courseClassID
     * @return DataSet
     */
    public function queryByCourseClass(int $courseClassID, bool $withDepartment = false): DataSet
    {
        $query = $this
            ->newQuery()
            ->from('gibbonCourse')
            ->cols([
                'gibbonCourse.gibbonSchoolYearID',
                'gibbonCourse.name AS courseLong',
                'gibbonCourse.nameShort AS course',
                'gibbonCourseClass.nameShort AS class',
                'gibbonCourse.gibbonCourseID',
                'gibbonSchoolYear.name AS year',
                'gibbonCourseClass.attendance'
            ])
            ->join('','gibbonCourseClass', 'gibbonCourse.gibbonCourseID = gibbonCourseClass.gibbonCourseID')
            ->join('','gibbonSchoolYear', 'gibbonCourse.gibbonSchoolYearID = gibbonSchoolYear.gibbonSchoolYearID')
            ->where('gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID')
            ->bindValue('gibbonCourseClassID', $courseClassID);
        if ($withDepartment)
            $query->cols(['gibbonDepartment.name AS department'])
                ->join('','gibbonDepartment', 'gibbonDepartment.gibbonDepartmentID = gibbonCourse.gibbonDepartmentID');

        return $this->runQuery($query, $this->newQueryCriteria());
    }
}
