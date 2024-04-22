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

    public function selectClassesByCourseID($gibbonCourseID)
    {
        $data = array('gibbonCourseID' => $gibbonCourseID, 'today' => date('Y-m-d'));
        $sql = "SELECT gibbonCourseClass.*, COUNT(CASE WHEN gibbonPerson.status='Full' AND gibbonCourseClassPerson.role='Student' AND (gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart<:today) AND (gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd>=:today) THEN gibbonPerson.status END) as studentsActive, COUNT(CASE WHEN (gibbonPerson.status='Expected' OR gibbonPerson.dateStart>=:today) AND gibbonCourseClassPerson.role='Student' THEN gibbonPerson.status END) as studentsExpected, COUNT(DISTINCT CASE WHEN (gibbonPerson.status='Full' OR gibbonPerson.status='Expected') AND gibbonCourseClassPerson.role='Student' AND (gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd>=:today) THEN gibbonPerson.gibbonPersonID END) as studentsTotal, COUNT(DISTINCT CASE WHEN gibbonCourseClassPerson.role='Teacher' AND (gibbonPerson.status='Full' OR gibbonPerson.status='Expected') THEN gibbonPerson.gibbonPersonID END) as teachersTotal
            FROM gibbonCourseClass
            LEFT JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID AND (gibbonCourseClassPerson.role='Student' OR gibbonCourseClassPerson.role='Teacher'))
            LEFT JOIN gibbonPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID AND (gibbonPerson.status='Full' OR gibbonPerson.status='Expected'))
            WHERE gibbonCourseClass.gibbonCourseID=:gibbonCourseID
            GROUP BY gibbonCourseClass.gibbonCourseClassID
            ORDER BY gibbonCourseClass.nameShort";

        return $this->db()->select($sql, $data);
    }
    
    public function getCourseClassByID($gibbonCourseClassID)
    {
        $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
        $sql = "SELECT gibbonCourseClass.gibbonCourseClassID, gibbonCourseClass.name, gibbonCourseClass.nameShort, gibbonCourse.gibbonCourseID, gibbonCourse.name AS courseName, gibbonCourse.nameShort as courseNameShort, gibbonCourse.description AS courseDescription, gibbonCourse.gibbonDepartmentID, gibbonCourse.gibbonSchoolYearID, gibbonSchoolYear.name as yearName, gibbonYearGroupIDList, enrolmentMin, enrolmentMax, COUNT(DISTINCT gibbonCourseClassPerson.gibbonPersonID) as studentsTotal
                FROM gibbonCourseClass
                JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID)
                JOIN gibbonSchoolYear ON (gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID)
                LEFT JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID AND gibbonCourseClassPerson.role='Student')
                WHERE gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID
                GROUP BY gibbonCourseClass.gibbonCourseClassID";

        return $this->db()->selectOne($sql, $data);
    }
}
