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
 * @version v25
 * @since   v25
 */
class CourseClassGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonCourseClass';
    private static $primaryKey = 'gibbonCourseClassID';

    private static $searchableColumns = ['gibbonCourseClass.name', 'gibbonCourseClass.nameShort'];

    public function selectActiveEnrolledActivities($gibbonSchoolYearID, $gibbonPersonID, $dateType, $date)
    {
        $query = $this
            ->newSelect()
            ->from($this->getTableName())
            ->cols([
                'gibbonCourseClass.gibbonCourseClassID',
                'gibbonCourse.gibbonCourseID',
                'gibbonCourse.nameShort as course',
                'gibbonCourseClass.nameShort AS class',
                'gibbonCourse.gibbonYearGroupIDList',
                'gibbonSpace.phoneInternal',
                'gibbonPerson.gibbonPersonID',
                'gibbonCourseClassSlot.gibbonCourseClassSlotID',
                'gibbonCourseClassSlot.timeStart',
                'gibbonCourseClassSlot.timeEnd',
                'gibbonSpace.name AS roomName',
                'gibbonDaysOfWeek.name as dayOfWeek',
                // '(CASE WHEN gibbonStaffCoverage.gibbonPersonID=:gibbonPersonID THEN 1 ELSE 0 END) as coverageStatus'
            ])
            ->leftJoin('gibbonCourse', 'gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID')
            ->leftJoin('gibbonCourseClassSlot', 'gibbonCourseClassSlot.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID')
            ->innerJoin('gibbonDaysOfWeek', 'gibbonCourseClassSlot.gibbonDaysOfWeekID=gibbonDaysOfWeek.gibbonDaysOfWeekID')
            ->innerJoin('gibbonCourseClassPerson', "gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID")
            ->innerJoin('gibbonPerson', "gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID")
            ->leftJoin('gibbonSpace', 'gibbonSpace.gibbonSpaceID=gibbonCourseClassSlot.gibbonSpaceID')
            ->where('gibbonCourse.gibbonSchoolYearID = :gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->where('gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID')
            ->bindValue('gibbonPersonID', $gibbonPersonID)
            ->where('gibbonDaysOfWeek.nameShort=:today')
            ->bindValue('today', $date)
            ->where("gibbonDaysOfWeek.schoolDay='Y'");
        // ->bindValue('dateType', $dateType);

        return $this->runSelect($query);
    }

    public function selectCourseClassExceptionsByID($gibbonCourseClassID)
    {
        $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
        $sql = "SELECT gibbonCourseClassSlotExceptionID, gibbonPerson.gibbonPersonID, surname, preferredName, gibbonDaysOfWeek.nameShort, gibbonDaysOfWeek.name , CONCAT(TIME_FORMAT(gibbonCourseClassSlot.timeStart, '%H:%i'), ' - ' ,TIME_FORMAT(gibbonCourseClassSlot.timeEnd, '%H:%i')) as slot
                FROM gibbonCourseClassSlotException
                JOIN gibbonPerson ON (gibbonCourseClassSlotException.gibbonPersonID=gibbonPerson.gibbonPersonID)
                JOIN gibbonCourseClassSlot ON (gibbonCourseClassSlot.gibbonCourseClassSlotID=gibbonCourseClassSlotException.gibbonCourseClassSlotID)
                JOIN gibbonDaysOfWeek ON gibbonDaysOfWeek.gibbonDaysOfWeekID = gibbonCourseClassSlot.gibbonDaysOfWeekID 
                WHERE gibbonCourseClassSlot.gibbonCourseClassID=:gibbonCourseClassID
                ORDER BY surname, preferredName";

        return $this->db()->select($sql, $data);
    }
}
