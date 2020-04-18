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
        // $data = array('gibbonPersonID1' => $gibbonPersonID, 'gibbonPersonID2' => $gibbonPersonID, 'gibbonPersonID3' => $gibbonPersonID, 'gibbonPersonID4' => $gibbonPersonID, 'gibbonPersonID5' => $gibbonPersonID, 'gibbonPersonID6' => $gibbonPersonID, 'gibbonSchoolYearID1' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonSchoolYearID2' => $_SESSION[$guid]['gibbonSchoolYearID']);
        //             $sql = '(SELECT gibbonSpace.*, gibbonSpacePersonID, usageType, NULL AS \'exception\' FROM gibbonSpacePerson JOIN gibbonSpace ON (gibbonSpacePerson.gibbonSpaceID=gibbonSpace.gibbonSpaceID) WHERE gibbonPersonID=:gibbonPersonID1)
        //             UNION
        //             (SELECT DISTINCT gibbonSpace.*, NULL AS gibbonSpacePersonID, \'Roll Group\' AS usageType, NULL AS \'exception\' FROM gibbonRollGroup JOIN gibbonSpace ON (gibbonRollGroup.gibbonSpaceID=gibbonSpace.gibbonSpaceID) WHERE (gibbonPersonIDTutor=:gibbonPersonID2 OR gibbonPersonIDTutor2=:gibbonPersonID3 OR gibbonPersonIDTutor3=:gibbonPersonID4) AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID1)
        //             UNION
        //             (SELECT DISTINCT gibbonSpace.*, NULL AS gibbonSpacePersonID, \'Timetable\' AS usageType, gibbonTTDayRowClassException.gibbonPersonID AS \'exception\' FROM gibbonSpace JOIN gibbonTTDayRowClass ON (gibbonTTDayRowClass.gibbonSpaceID=gibbonSpace.gibbonSpaceID) JOIN gibbonCourseClass ON (gibbonTTDayRowClass.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) LEFT JOIN gibbonTTDayRowClassException ON (gibbonTTDayRowClassException.gibbonTTDayRowClassID=gibbonTTDayRowClass.gibbonTTDayRowClassID AND (gibbonTTDayRowClassException.gibbonPersonID=:gibbonPersonID6 OR gibbonTTDayRowClassException.gibbonPersonID IS NULL)) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID2 AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID5)
        //             ORDER BY name';

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
                'gibbonSpace.*', 'NULL AS gibbonSpacePersonID', "'Roll Group' as usageType", "NULL AS exception"
            ])
            ->from('gibbonRollGroup')
            ->innerJoin('gibbonSpace', 'gibbonRollGroup.gibbonSpaceID=gibbonSpace.gibbonSpaceID')
            ->where('(gibbonPersonIDTutor=:gibbonPersonID OR gibbonPersonIDTutor2=:gibbonPersonID OR gibbonPersonIDTutor3=:gibbonPersonID)')
            ->bindValue('gibbonPersonID', $gibbonPersonID)
            ->where('gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID')
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
