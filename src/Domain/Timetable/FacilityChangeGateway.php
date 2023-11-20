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
 * @version v16
 * @since   v16
 */
class FacilityChangeGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonTTSpaceChange';
    private static $primaryKey = 'gibbonTTSpaceChangeID';

    private static $searchableColumns = ['spaceOld.name', 'spaceNew.name'];
    
    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryFacilityChanges(QueryCriteria $criteria, $gibbonPersonID = null)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonTTSpaceChangeID', 'date', 'gibbonCourseClass.gibbonCourseClassID', 'gibbonCourse.nameShort as courseName', 'gibbonCourseClass.nameShort as className', 'spaceOld.name as spaceOld', 'spaceNew.name as spaceNew', 'gibbonPerson.preferredName', 'gibbonPerson.surname'
            ])
            ->innerJoin('gibbonTTDayRowClass', 'gibbonTTSpaceChange.gibbonTTDayRowClassID=gibbonTTDayRowClass.gibbonTTDayRowClassID')
            ->innerJoin('gibbonCourseClass', 'gibbonTTDayRowClass.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID')
            ->innerJoin('gibbonCourse', 'gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID')
            ->leftJoin('gibbonSpace AS spaceOld', 'gibbonTTDayRowClass.gibbonSpaceID=spaceOld.gibbonSpaceID')
            ->leftJoin('gibbonSpace AS spaceNew', 'gibbonTTSpaceChange.gibbonSpaceID=spaceNew.gibbonSpaceID')
            ->leftJoin('gibbonPerson', 'gibbonTTSpaceChange.gibbonPersonID=gibbonPerson.gibbonPersonID')
            ->where('date >= :today')
            ->bindValue('today', date('Y-m-d'));

        if (!empty($gibbonPersonID)) {
            $query->leftJoin('gibbonCourseClassPerson', 
                             'gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID')
                  ->where('gibbonCourseClassPerson.gibbonPersonID = :gibbonPersonID')
                  ->bindValue('gibbonPersonID', $gibbonPersonID);
        }

        return $this->runQuery($query, $criteria);
    }

    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryFacilityChangesByDepartment(QueryCriteria $criteria, $gibbonPersonID)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonTTSpaceChangeID', 'date', 'gibbonCourseClass.gibbonCourseClassID', 'gibbonCourse.nameShort as courseName', 'gibbonCourseClass.nameShort as className', 'spaceOld.name as spaceOld', 'spaceNew.name as spaceNew', 'gibbonPerson.preferredName', 'gibbonPerson.surname'
            ])
            ->innerJoin('gibbonTTDayRowClass', 'gibbonTTSpaceChange.gibbonTTDayRowClassID=gibbonTTDayRowClass.gibbonTTDayRowClassID')
            ->innerJoin('gibbonCourseClass', 'gibbonTTDayRowClass.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID')
            ->innerJoin('gibbonCourse', 'gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID')
            ->innerJoin('gibbonCourseClassPerson', 'gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID')
            ->leftJoin('gibbonSpace AS spaceOld', 'gibbonTTDayRowClass.gibbonSpaceID=spaceOld.gibbonSpaceID')
            ->leftJoin('gibbonSpace AS spaceNew', 'gibbonTTSpaceChange.gibbonSpaceID=spaceNew.gibbonSpaceID')
            ->leftJoin('gibbonPerson', 'gibbonTTSpaceChange.gibbonPersonID=gibbonPerson.gibbonPersonID')
            ->where('gibbonCourseClassPerson.gibbonPersonID = :gibbonPersonID')
            ->bindValue('gibbonPersonID', $gibbonPersonID)
            ->where('date >= :today')
            ->bindValue('today', date('Y-m-d'));

        $query->union()
            ->from($this->getTableName())
            ->cols([
                'gibbonTTSpaceChangeID', 'date', 'gibbonCourseClass.gibbonCourseClassID', 'gibbonCourse.nameShort as courseName', 'gibbonCourseClass.nameShort as className', 'spaceOld.name as spaceOld', 'spaceNew.name as spaceNew', 'gibbonPerson.preferredName', 'gibbonPerson.surname'
            ])
            ->innerJoin('gibbonTTDayRowClass', 'gibbonTTSpaceChange.gibbonTTDayRowClassID=gibbonTTDayRowClass.gibbonTTDayRowClassID')
            ->innerJoin('gibbonCourseClass', 'gibbonTTDayRowClass.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID')
            ->innerJoin('gibbonCourse', 'gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID')
            ->innerJoin('gibbonDepartment', 'gibbonCourse.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID')
            ->innerJoin('gibbonDepartmentStaff', 'gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID')
            ->leftJoin('gibbonSpace AS spaceOld', 'gibbonTTDayRowClass.gibbonSpaceID=spaceOld.gibbonSpaceID')
            ->leftJoin('gibbonSpace AS spaceNew', 'gibbonTTSpaceChange.gibbonSpaceID=spaceNew.gibbonSpaceID')
            ->leftJoin('gibbonPerson', 'gibbonTTSpaceChange.gibbonPersonID=gibbonPerson.gibbonPersonID')
            ->where("gibbonDepartmentStaff.role = 'Coordinator'")
            ->where("gibbonDepartmentStaff.gibbonPersonID = :gibbonPersonID")
            ->bindValue('gibbonPersonID', $gibbonPersonID)
            ->where('date >= :today')
            ->bindValue('today', date('Y-m-d'));

        return $this->runQuery($query, $criteria);
    }
}
