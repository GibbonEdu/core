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
                'gibbonCourseClassMap.gibbonRollGroupID',
                'gibbonCourseClassMap.gibbonYearGroupID',
                'gibbonYearGroup.gibbonYearGroupID',
                'gibbonRollGroup.name as rollGroupName',
                'gibbonYearGroup.name as yearGroupName',
                'COUNT(DISTINCT gibbonCourseClassMap.gibbonCourseClassID) as classCount',
                "GROUP_CONCAT(DISTINCT gibbonRollGroup.nameShort ORDER BY gibbonRollGroup.nameShort SEPARATOR ', ') as rollGroupList",
                "GROUP_CONCAT(DISTINCT gibbonRollGroup.gibbonRollGroupID ORDER BY gibbonRollGroup.gibbonRollGroupID SEPARATOR ',') as gibbonRollGroupIDList",
            ])
            ->innerJoin('gibbonRollGroup', 'gibbonCourseClassMap.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID')
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
