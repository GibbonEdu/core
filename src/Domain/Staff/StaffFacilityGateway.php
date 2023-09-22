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

namespace Gibbon\Domain\Staff;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * StaffFacilityGateway Gateway
 *
 * @version v20
 * @since   v20
 */
class StaffFacilityGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonSpacePerson';
    private static $primaryKey = 'gibbonSpacePersonID';

    private static $searchableColumns = [];
    
    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryFacilitiesByPerson(QueryCriteria $criteria, $gibbonSchoolYearID, $gibbonPersonID)
    {
        $query = $this
            ->newQuery()
            ->distinct()
            ->cols([
                'gibbonSpace.*', 'gibbonSpacePerson.gibbonSpacePersonID', 'usageType', "NULL AS exception"
            ])
            ->from('gibbonSpacePerson')
            ->innerJoin('gibbonSpace', 'gibbonSpacePerson.gibbonSpaceID=gibbonSpace.gibbonSpaceID')
            ->where('gibbonPersonID=:gibbonPersonID')
            ->bindValue('gibbonPersonID', $gibbonPersonID);

        $this->unionWithCriteria($query, $criteria)
            ->distinct()
            ->cols([
                'gibbonSpace.*', 'NULL AS gibbonSpacePersonID', "'Form Group' as usageType", "NULL AS exception"
            ])
            ->from('gibbonFormGroup')
            ->innerJoin('gibbonSpace', 'gibbonFormGroup.gibbonSpaceID=gibbonSpace.gibbonSpaceID')
            ->where('(gibbonPersonIDTutor=:gibbonPersonID OR gibbonPersonIDTutor2=:gibbonPersonID OR gibbonPersonIDTutor3=:gibbonPersonID)')
            ->bindValue('gibbonPersonID', $gibbonPersonID)
            ->where('gibbonFormGroup.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID);

        $this->unionWithCriteria($query, $criteria)
            ->distinct()
            ->cols([
                'gibbonSpace.*', 'NULL AS gibbonSpacePersonID', "'Timetable' as usageType", 'gibbonTTDayRowClassException.gibbonPersonID AS exception'
            ])
            ->from('gibbonSpace')
            ->innerJoin('gibbonTTDayRowClass', 'gibbonTTDayRowClass.gibbonSpaceID=gibbonSpace.gibbonSpaceID')
            ->innerJoin('gibbonCourseClass', 'gibbonTTDayRowClass.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID')
            ->innerJoin('gibbonCourse', 'gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID')
            ->innerJoin('gibbonCourseClassPerson', 'gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID')
            ->leftJoin('gibbonTTDayRowClassException', 'gibbonTTDayRowClassException.gibbonTTDayRowClassID=gibbonTTDayRowClass.gibbonTTDayRowClassID AND (gibbonTTDayRowClassException.gibbonPersonID=:gibbonPersonID OR gibbonTTDayRowClassException.gibbonPersonID IS NULL)')
            ->where('gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID')
            ->bindValue('gibbonPersonID', $gibbonPersonID)
            ->where('gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID);

        return $this->runQuery($query, $criteria);
    }
}
