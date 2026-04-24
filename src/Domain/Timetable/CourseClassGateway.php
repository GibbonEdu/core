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

    public function getCourseClass($gibbonCourseClassID)
    {
        $data = ['gibbonCourseClassID' => $gibbonCourseClassID];
        $sql = 'SELECT gibbonCourse.nameShort AS course, gibbonCourse.name AS courseName, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID, gibbonCourse.gibbonDepartmentID, gibbonYearGroupIDList FROM gibbonCourse, gibbonCourseClass WHERE gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID ORDER BY course, class';

        return $this->db()->selectOne($sql, $data);
    }

    public function getCourseClassByPerson($gibbonCourseClassID, $gibbonPersonID)
    {
        $data = ['gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonPersonID' => $gibbonPersonID];
        $sql = "SELECT gibbonCourse.nameShort AS course, gibbonCourse.name AS courseName, gibbonCourseClass.nameShort AS class, gibbonYearGroupIDList FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID AND gibbonPersonID=:gibbonPersonID AND role='Teacher'";

        return $this->db()->selectOne($sql, $data);
    }

    public function selectClassesByYear($gibbonSchoolYearID)
    {
        $data = ['gibbonSchoolYearID' => $gibbonSchoolYearID];
        $sql = "SELECT gibbonYearGroup.name as groupBy, gibbonCourseClassID as value, CONCAT(gibbonCourse.nameShort, '.', gibbonCourseClass.nameShort) AS name FROM gibbonCourseClass JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonYearGroup ON (gibbonCourse.gibbonYearGroupIDList LIKE concat( '%', gibbonYearGroup.gibbonYearGroupID, '%' )) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClass.reportable='Y' ORDER BY gibbonYearGroup.sequenceNumber, name";

        return $this->db()->select($sql, $data);
    }

    public function selectClassesByPerson($gibbonPersonID)
    {
        $data = ['gibbonPersonID' => $gibbonPersonID];
        $sql = "SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID FROM gibbonCourse, gibbonCourseClass, gibbonCourseClassPerson WHERE gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND gibbonCourse.gibbonSchoolYearID=(SELECT gibbonSchoolYearID FROM gibbonSchoolYear WHERE status='Current') ORDER BY course, class";

        return $this->db()->select($sql, $data);
    }

    public function selectClassesByYearAndPerson($gibbonSchoolYearID, $gibbonPersonID)
    {
        $data = ['gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonPersonID' => $gibbonPersonID];
        $sql = 'SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID FROM gibbonCourse, gibbonCourseClass, gibbonCourseClassPerson WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID ORDER BY course, class';

        return $this->db()->select($sql, $data);
    }

    public function selectTeacherListByClass($gibbonCourseClassID)
    {
        $data = ['gibbonCourseClassID' => $gibbonCourseClassID];
        $sql = "SELECT gibbonPerson.gibbonPersonID, title, surname, preferredName, gibbonCourseClassPerson.reportable FROM gibbonCourseClassPerson JOIN gibbonPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE role='Teacher' AND gibbonCourseClassID=:gibbonCourseClassID ORDER BY surname, preferredName";

        return $this->db()->select($sql, $data);
    }

    public function selectStudentListByClass($gibbonCourseClassID)
    {
        $data = ['gibbonCourseClassID' => $gibbonCourseClassID, 'today' => date('Y-m-d')];
        $sql = "SELECT title, surname, preferredName, gibbonPerson.gibbonPersonID, dateStart FROM gibbonCourseClassPerson JOIN gibbonPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE role='Student' AND gibbonCourseClassID=:gibbonCourseClassID AND status='Full' AND (dateStart IS NULL OR dateStart<=:today) AND (dateEnd IS NULL  OR dateEnd>=:today) AND gibbonCourseClassPerson.reportable='Y' ORDER BY surname, preferredName";

        return $this->db()->select($sql, $data);
    }

    public function selectAttendanceClassesByStudent($gibbonSchoolYearID, $gibbonPersonID)
    {
        $data = ['gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonPersonID' => $gibbonPersonID];
        $sql = "SELECT gibbonCourseClass.gibbonCourseClassID, gibbonSchoolYear.firstDay, gibbonSchoolYear.lastDay FROM gibbonCourse JOIN gibbonSchoolYear ON (gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonPersonID=:gibbonPersonID AND gibbonCourseClass.attendance='Y' AND gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID";

        return $this->db()->select($sql, $data);
    }
    
    public function selectStudentsByClassAndPeriod($gibbonCourseClassID, $date, $gibbonTTDayRowClassID)
    {
        $data = ['gibbonCourseClassID' => $gibbonCourseClassID, 'date' => $date, 'gibbonTTDayRowClassID' => $gibbonTTDayRowClassID];
        $sql = "SELECT gibbonPerson.surname, gibbonPerson.preferredName, gibbonPerson.gibbonPersonID, gibbonPerson.image_240, gibbonPerson.dob FROM gibbonCourseClassPerson INNER JOIN gibbonPerson ON gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID LEFT JOIN (SELECT gibbonTTDayRowClass.gibbonCourseClassID, gibbonTTDayRowClass.gibbonTTDayRowClassID FROM gibbonTTDayDate JOIN gibbonTTDayRowClass ON (gibbonTTDayDate.gibbonTTDayID=gibbonTTDayRowClass.gibbonTTDayID) WHERE gibbonTTDayDate.date=:date) AS gibbonTTDayRowClassSubset ON (gibbonTTDayRowClassSubset.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID AND gibbonTTDayRowClassSubset.gibbonTTDayRowClassID=:gibbonTTDayRowClassID) LEFT JOIN gibbonTTDayRowClassException ON (gibbonTTDayRowClassException.gibbonTTDayRowClassID=gibbonTTDayRowClassSubset.gibbonTTDayRowClassID AND gibbonTTDayRowClassException.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID) WHERE gibbonCourseClassPerson.gibbonCourseClassID=:gibbonCourseClassID AND status='Full' AND role='Student' AND (dateStart IS NULL OR dateStart<=:date) AND (dateEnd IS NULL OR dateEnd>=:date) GROUP BY gibbonCourseClassPerson.gibbonPersonID HAVING COUNT(gibbonTTDayRowClassExceptionID) = 0 ORDER BY surname, preferredName";

        return $this->db()->select($sql, $data);
    }
  
    public function selectClassesByCourseID($gibbonCourseID)
    {
        $data = array('gibbonCourseID' => $gibbonCourseID, 'today' => date('Y-m-d'));
        $sql = "SELECT gibbonCourseClass.*, gibbonCourseClass.nameShort as class, gibbonCourse.nameShort as course, COUNT(CASE WHEN gibbonPerson.status='Full' AND gibbonCourseClassPerson.role='Student' AND (gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart<:today) AND (gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd>=:today) THEN gibbonPerson.status END) as studentsActive, COUNT(CASE WHEN (gibbonPerson.status='Expected' OR gibbonPerson.dateStart>=:today) AND gibbonCourseClassPerson.role='Student' THEN gibbonPerson.status END) as studentsExpected, COUNT(DISTINCT CASE WHEN (gibbonPerson.status='Full' OR gibbonPerson.status='Expected') AND gibbonCourseClassPerson.role='Student' AND (gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd>=:today) THEN gibbonPerson.gibbonPersonID END) as studentsTotal, COUNT(DISTINCT CASE WHEN gibbonCourseClassPerson.role='Teacher' AND (gibbonPerson.status='Full' OR gibbonPerson.status='Expected') THEN gibbonPerson.gibbonPersonID END) as teachersTotal
            FROM gibbonCourseClass
            JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID)
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
