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
        $data= ['gibbonSchoolYearID' => $gibbonSchoolYearID];
        $sql = "SELECT gibbonCourseClass.gibbonCourseClassID, gibbonCourse.name as courseName, gibbonCourse.nameShort as course, gibbonCourseClass.nameShort as class
                FROM gibbonCourse
                JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID)
                WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID
                ORDER BY gibbonCourse.nameShort, gibbonCourseClass.nameShort";

        return $this->db()->select($sql, $data);
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

    public function getCourseClassInfoAndDepartment($gibbonCourseClassID)
    {
        $data = ['gibbonCourseClassID' => $gibbonCourseClassID];
        $sql = "SELECT gibbonCourse.gibbonSchoolYearID,gibbonDepartment.name AS department, gibbonCourse.name AS courseLong, gibbonCourse.nameShort AS course, gibbonCourseClass.name AS classLong, gibbonCourseClass.nameShort AS class, gibbonCourse.gibbonCourseID, gibbonSchoolYear.name AS year, gibbonCourseClass.attendance, gibbonCourseClass.fields
                FROM gibbonCourse
                JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID)
                JOIN gibbonSchoolYear ON (gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID)
                JOIN gibbonDepartment ON (gibbonDepartment.gibbonDepartmentID=gibbonCourse.gibbonDepartmentID)
                WHERE gibbonCourseClassID=:gibbonCourseClassID";
        
        return $this->db()->selectOne($sql, $data);
    }

    public function getCourseClassInfoByID($gibbonCourseClassID)
    {
        $data = ['gibbonCourseClassID' => $gibbonCourseClassID];
        $sql = "SELECT gibbonCourse.gibbonSchoolYearID, gibbonCourse.name AS courseLong, gibbonCourse.nameShort AS course, gibbonCourseClass.name AS classLong, gibbonCourseClass.nameShort AS class, gibbonCourse.gibbonCourseID, gibbonSchoolYear.name AS year, gibbonCourseClass.attendance, gibbonCourseClass.fields
                    FROM gibbonCourse
                    JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID)
                    JOIN gibbonSchoolYear ON (gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID)
                    WHERE gibbonCourseClassID=:gibbonCourseClassID";
        
        return $this->db()->selectOne($sql, $data);
    }

    public function selectClassesByCourseID($gibbonCourseID, $gibbonSchoolYearID)
    {
        $data = ['gibbonCourseID' => $gibbonCourseID, 'gibbonSchoolYearID' => $gibbonSchoolYearID];
        $sql = 'SELECT gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourse.gibbonCourseID=:gibbonCourseID AND gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY class';

        return $this->db()->select($sql, $data);
    }

    public function selectCoursesAndClassesBySchoolYear($gibbonSchoolYearID)
    {
        $data = ['gibbonSchoolYearID' => $gibbonSchoolYearID];
        $sql = "SELECT gibbonCourseClassID as value, CONCAT(gibbonCourse.nameShort, '.', gibbonCourseClass.nameShort) as name
                FROM gibbonCourse
                JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID)
                WHERE gibbonSchoolYearID=:gibbonSchoolYearID
                ORDER BY gibbonCourse.nameShort, gibbonCourseClass.nameShort";

        return $this->db()->select($sql, $data);
    }

    public function selectCourseAndClassNameByCourseClassID($gibbonCourseClassID)
    {
        $data = ['gibbonCourseClassID' => $gibbonCourseClassID];
        $sql = 'SELECT gibbonCourseClassID, gibbonCourse.nameShort AS courseName, gibbonCourseClass.nameShort AS className FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourseClassID=:gibbonCourseClassID ORDER BY gibbonCourse.name, gibbonCourseClass.name';

        return $this->db()->select($sql, $data);
    }

    public function getCourseInfoByCourseID($gibbonCourseID)
    {
        $data = ['gibbonCourseID' => $gibbonCourseID];
        $sql = 'SELECT gibbonSchoolYear.name AS year, gibbonDepartment.name AS department, gibbonCourse.name AS course, description, gibbonCourse.gibbonSchoolYearID FROM gibbonCourse JOIN gibbonDepartment ON (gibbonDepartment.gibbonDepartmentID=gibbonCourse.gibbonDepartmentID) JOIN gibbonSchoolYear ON (gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE gibbonCourseID=:gibbonCourseID';

        return $this->db()->selectOne($sql, $data);
    }

    public function selectClassesByCourse($gibbonCourseID)
    {
        $data = ['gibbonCourseID' => $gibbonCourseID];
        $sql = 'SELECT gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourse.gibbonCourseID=:gibbonCourseID ORDER BY class';

        return $this->db()->select($sql, $data);
    }

    public function selectCurrentCoursesByDepartment($gibbonDepartmentID)
    {
        $data = ['gibbonDepartmentID' => $gibbonDepartmentID];
        $sql = "SELECT gibbonCourse.* FROM gibbonCourse
            JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID)
            WHERE gibbonDepartmentID=:gibbonDepartmentID
            AND gibbonYearGroupIDList <> ''
            AND gibbonSchoolYearID=(SELECT gibbonSchoolYearID FROM gibbonSchoolYear WHERE status='Current')
            GROUP BY gibbonCourse.gibbonCourseID
            ORDER BY nameShort, name";

        return $this->db()->select($sql, $data);
    }

    public function selectCourseListByOtherDepartment($gibbonDepartmentID, $gibbonSchoolYearID)
    {
        $data = ['gibbonDepartmentID' => $gibbonDepartmentID, 'gibbonSchoolYearID' => $gibbonSchoolYearID];
        $sql = "SELECT gibbonSchoolYear.name AS year, gibbonCourse.gibbonCourseID as value, gibbonCourse.name AS name
                        FROM gibbonCourse
                        JOIN gibbonSchoolYear ON (gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID)
                        WHERE gibbonDepartmentID=:gibbonDepartmentID
                        AND NOT gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID
                        ORDER BY sequenceNumber, gibbonCourse.nameShort, name";
        
        return $this->db()->select($sql, $data);
    }

    public function selectClassesByStaff($gibbonSchoolYearID, $gibbonPersonID)
    {
        $data = ['gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonPersonID' => $gibbonPersonID];
        $sql = 'SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID FROM gibbonCourse, gibbonCourseClass, gibbonCourseClassPerson WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role LIKE \'% - Left%\' ORDER BY course, class';

        return $this->db()->select($sql, $data);
    }
}
