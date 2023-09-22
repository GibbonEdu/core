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

namespace Gibbon\Domain\Timetable;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * @version v17
 * @since   v17
 */
class CourseSyncGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonCourseClassMap';
    private static $primaryKey = 'gibbonCourseClassMapID';
    private static $searchableColumns = [];
    
    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryCourseClassMaps(QueryCriteria $criteria, $gibbonSchoolYearID)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonCourseClassMap.gibbonCourseClassID',
                'gibbonCourseClassMap.gibbonFormGroupID',
                'gibbonCourseClassMap.gibbonYearGroupID',
                'gibbonYearGroup.gibbonYearGroupID',
                'gibbonFormGroup.name as formGroupName',
                'gibbonYearGroup.name as yearGroupName',
                'COUNT(DISTINCT gibbonCourseClassMap.gibbonCourseClassID) as classCount',
                "GROUP_CONCAT(DISTINCT gibbonFormGroup.nameShort ORDER BY gibbonFormGroup.nameShort SEPARATOR ', ') as formGroupList",
                "GROUP_CONCAT(DISTINCT gibbonFormGroup.gibbonFormGroupID ORDER BY gibbonFormGroup.gibbonFormGroupID SEPARATOR ',') as gibbonFormGroupIDList",
            ])
            ->innerJoin('gibbonFormGroup', 'gibbonCourseClassMap.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID')
            ->innerJoin('gibbonYearGroup', 'gibbonYearGroup.gibbonYearGroupID=gibbonCourseClassMap.gibbonYearGroupID')
            ->innerJoin('gibbonCourseClass', 'gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassMap.gibbonCourseClassID')
            ->innerJoin('gibbonCourse', 'gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID')
            ->where('FIND_IN_SET(gibbonYearGroup.gibbonYearGroupID, gibbonCourse.gibbonYearGroupIDList)')
            ->where('gibbonCourse.gibbonSchoolYearID = :gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->groupBy(['gibbonYearGroup.gibbonYearGroupID']);

        return $this->runQuery($query, $criteria);
    }
}
