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
class CourseGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonCourse';
    private static $primaryKey = 'gibbonCourseID';

    private static $searchableColumns = ['gibbonCourse.name', 'gibbonCourse.nameShort'];

    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryCoursesBySchoolYear(QueryCriteria $criteria, $gibbonSchoolYearID)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonCourse.gibbonCourseID', 'gibbonCourse.name', 'gibbonCourse.nameShort', 'gibbonDepartment.name as department', 'COUNT(DISTINCT gibbonCourseClassID) as classCount'
            ])
            ->leftJoin('gibbonDepartment', 'gibbonDepartment.gibbonDepartmentID=gibbonCourse.gibbonDepartmentID')
            ->leftJoin('gibbonCourseClass', 'gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID')
            ->where('gibbonSchoolYearID = :gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->groupBy(['gibbonCourse.gibbonCourseID']);

        $criteria->addFilterRules([
            'yearGroup' => function ($query, $gibbonYearGroupID) {
                return $query
                    ->where('FIND_IN_SET(:gibbonYearGroupID, gibbonCourse.gibbonYearGroupIDList)')
                    ->bindValue('gibbonYearGroupID', $gibbonYearGroupID);
            },
        ]);

        return $this->runQuery($query, $criteria);
    }

    public function queryCoursesByDepartmentStaff(QueryCriteria $criteria, $gibbonSchoolYearID, $gibbonPersonID)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonCourse.gibbonCourseID', 'gibbonCourse.name', 'gibbonCourse.nameShort', 'gibbonDepartment.name as department', 'COUNT(DISTINCT gibbonCourseClassID) as classCount'
            ])
            ->innerJoin('gibbonDepartment', 'gibbonDepartment.gibbonDepartmentID=gibbonCourse.gibbonDepartmentID')
            ->innerJoin('gibbonDepartmentStaff', 'gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID')
            ->innerJoin('gibbonCourseClass', 'gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID')
            ->where("(gibbonDepartmentStaff.role='Coordinator' OR gibbonDepartmentStaff.role='Assistant Coordinator')")
            ->where('gibbonCourse.gibbonSchoolYearID = :gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->where('gibbonDepartmentStaff.gibbonPersonID = :gibbonPersonID')
            ->bindValue('gibbonPersonID', $gibbonPersonID)
            ->groupBy(['gibbonCourse.gibbonCourseID']);

        $criteria->addFilterRules([
            'yearGroup' => function ($query, $gibbonYearGroupID) {
                return $query
                    ->where('FIND_IN_SET(:gibbonYearGroupID, gibbonCourse.gibbonYearGroupIDList)')
                    ->bindValue('gibbonYearGroupID', $gibbonYearGroupID);
            },
        ]);

        return $this->runQuery($query, $criteria);
    }

    public function selectClassesBySchoolYear($gibbonSchoolYearID)
    {
        $data= array('gibbonSchoolYearID' => $gibbonSchoolYearID);
        $sql = "SELECT gibbonCourseClass.gibbonCourseClassID, gibbonCourse.name as courseName, gibbonCourse.nameShort as course, gibbonCourseClass.nameShort as class
                FROM gibbonCourse
                JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID)
                WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID
                ORDER BY gibbonCourse.nameShort, gibbonCourseClass.nameShort";

        return $this->db()->select($sql, $data);
    }

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

    public function selectCoursesBySchoolYear($gibbonSchoolYearID)
    {
        $data = ['gibbonSchoolYearID' => $gibbonSchoolYearID];
        $sql = "SELECT gibbonCourseID, CONCAT(gibbonCourse.nameShort, ' - ', gibbonCourse.name) AS course
                FROM gibbonCourse
                JOIN gibbonSchoolYear ON (gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID)
                WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID
                ORDER BY nameShort";

        return $this->db()->select($sql, $data);
    }

    public function selectActiveAndUpcomingCourses($gibbonSchoolYearID)
    {
        $data = ['gibbonSchoolYearID' => $gibbonSchoolYearID];
        $sql = "SELECT gibbonSchoolYear.name as groupBy, gibbonCourseID as value, CONCAT(gibbonCourse.nameShort, ' - ', gibbonCourse.name) AS name
                FROM gibbonCourse
                JOIN gibbonSchoolYear ON (gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID)
                WHERE gibbonCourse.gibbonSchoolYearID>=:gibbonSchoolYearID
                ORDER BY gibbonSchoolYear.sequenceNumber, gibbonCourse.nameShort";

        return $this->db()->select($sql, $data);
    }

    public function selectCoursesByPerson($gibbonSchoolYearID, $gibbonPersonID)
    {
        $data = ['gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonPersonID' => $gibbonPersonID];
        $sql = "SELECT gibbonCourseID, CONCAT(gibbonCourse.nameShort, ' - ', gibbonCourse.name) AS course
                FROM gibbonCourse
                JOIN gibbonSchoolYear ON (gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID)
                JOIN gibbonDepartment ON (gibbonCourse.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID)
                JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID)
                WHERE gibbonDepartmentStaff.gibbonPersonID=:gibbonPersonID
                AND gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID
                AND (role='Coordinator' OR role='Assistant Coordinator' OR role='Teacher (Curriculum)')
                ORDER BY gibbonCourse.nameShort";

        return $this->db()->select($sql, $data);
    }

    public function selectCourseDetailsByCourse($gibbonCourseID)
    {
        $data = ['gibbonCourseID' => $gibbonCourseID];
        $sql = 'SELECT *, gibbonSchoolYear.name AS schoolYear, gibbonCourse.nameShort AS course
                FROM gibbonCourse
                JOIN gibbonSchoolYear ON (gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID)
                WHERE gibbonCourse.gibbonCourseID=:gibbonCourseID';

        return $this->db()->select($sql, $data);
    }

    public function selectCourseDetailsByCourseAndPerson($gibbonCourseID, $gibbonPersonID)
    {
        $data = ['gibbonCourseID' => $gibbonCourseID, 'gibbonPersonID' => $gibbonPersonID];
        $sql = "SELECT gibbonCourseID, gibbonCourse.name, gibbonCourse.nameShort, gibbonCourse.gibbonYearGroupIDList, gibbonCourse.gibbonDepartmentID, gibbonSchoolYear.name AS schoolYear
            FROM gibbonCourse
            JOIN gibbonSchoolYear ON (gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID)
            JOIN gibbonDepartment ON (gibbonCourse.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID)
            JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID)
            WHERE gibbonDepartmentStaff.gibbonPersonID=:gibbonPersonID
            AND (role='Coordinator' OR role='Assistant Coordinator' OR role='Teacher (Curriculum)')
            AND gibbonCourseID=:gibbonCourseID
            ORDER BY gibbonCourse.nameShort";

        return $this->db()->select($sql, $data);
    }

    public function selectCourseDetailsByClass($gibbonCourseClassID)
    {
        $data = ['gibbonCourseClassID' => $gibbonCourseClassID];
        $sql = 'SELECT *, gibbonSchoolYear.name AS schoolYear, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class
                FROM gibbonCourse
                JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID)
                JOIN gibbonSchoolYear ON (gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID)
                WHERE gibbonCourseClassID=:gibbonCourseClassID';

        return $this->db()->select($sql, $data);
    }

    public function selectCourseDetailsByClassAndPerson($gibbonCourseClassID, $gibbonPersonID)
    {
        $data = ['gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonPersonID' => $gibbonPersonID];
        $sql = "SELECT gibbonCourse.gibbonCourseID, gibbonCourse.name, gibbonCourse.nameShort, gibbonSchoolYear.name AS schoolYear, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class
                FROM gibbonCourse
                JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID)
                JOIN gibbonSchoolYear ON (gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID)
                JOIN gibbonDepartment ON (gibbonCourse.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID)
                JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID)
                WHERE gibbonDepartmentStaff.gibbonPersonID=:gibbonPersonID
                AND (role='Coordinator' OR role='Assistant Coordinator' OR role='Teacher (Curriculum)')
                AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID
                ORDER BY gibbonCourse.nameShort";

        return $this->db()->select($sql, $data);
    }

    public function selectCourseListBySchoolYear($gibbonSchoolYearID)
    {
        $data = ['gibbonSchoolYearID' => $gibbonSchoolYearID];
        $sql = "SELECT gibbonCourseID as value, nameShort as name FROM gibbonCourse WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name";

        return $this->db()->select($sql, $data);
    }

    public function selectCourseListBySchoolYearAndPerson($gibbonSchoolYearID, $gibbonPersonID )
    {
        $data = ['gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonPersonID' => $gibbonPersonID];
        $sql = "SELECT gibbonCourse.gibbonCourseID as value, gibbonCourse.nameShort as name FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID AND NOT role LIKE '%- Left' GROUP BY gibbonCourse.gibbonCourseID ORDER BY name";

        return $this->db()->select($sql, $data);
    }

    public function selectClassListBySchoolYear($gibbonSchoolYearID)
    {
        $data = ['gibbonSchoolYearID' => $gibbonSchoolYearID];
        $sql = "SELECT gibbonCourseClassID as value, CONCAT(gibbonCourse.nameShort, '.', gibbonCourseClass.nameShort) as name FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name";
        
        return $this->db()->select($sql, $data);
    }

    public function selectClassListBySchoolYearAndPerson($gibbonSchoolYearID, $gibbonPersonID)
    {
        $data = ['gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonPersonID' => $gibbonPersonID];
        $sql = "SELECT gibbonCourseClass.gibbonCourseClassID as value, CONCAT(gibbonCourse.nameShort, '.', gibbonCourseClass.nameShort) as name FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID AND NOT role LIKE '%- Left' ORDER BY gibbonCourseClass.name";

        return $this->db()->select($sql, $data);
    }
}
