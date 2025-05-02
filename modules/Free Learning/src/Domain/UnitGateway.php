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

namespace Gibbon\Module\FreeLearning\Domain;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

class UnitGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'freeLearningUnit';
    private static $primaryKey = 'freeLearningUnitID';
    private static $searchableColumns = ['freeLearningUnit.name','freeLearningUnit.course'];

    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryAllUnits(QueryCriteria $criteria, $gibbonPersonID, $publicUnits = null, $countOnly = false)
    {
        $query = $this
            ->newQuery()
            ->distinct()
            ->from($this->getTableName())
            ->cols($countOnly ? ['COUNT(DISTINCT freeLearningUnit.freeLearningUnitID) as count'] :
            ['freeLearningUnit.*', "GROUP_CONCAT(DISTINCT freeLearningUnitPrerequisite.freeLearningUnitIDPrerequisite SEPARATOR ',') as freeLearningUnitIDPrerequisiteList", "GROUP_CONCAT(gibbonDepartment.name SEPARATOR '<br/>') as learningArea", 'freeLearningUnitStudent.status',
                "(SELECT SUM(freeLearningUnitBlock.length) FROM freeLearningUnitBlock WHERE freeLearningUnitBlock.freeLearningUnitID=freeLearningUnit.freeLearningUnitID) as length",
                "FIND_IN_SET(freeLearningUnit.difficulty, :difficultyOptions) as difficultyOrder"])
            ->leftJoin('gibbonDepartment', "freeLearningUnit.gibbonDepartmentIDList LIKE CONCAT('%', gibbonDepartment.gibbonDepartmentID, '%')")
            ->leftJoin('freeLearningUnitStudent', "freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID AND gibbonPersonIDStudent=:gibbonPersonID")
            ->leftJoin('freeLearningUnitPrerequisite', "freeLearningUnitPrerequisite.freeLearningUnitID=freeLearningUnit.freeLearningUnitID")
            ->bindValue('gibbonPersonID', $gibbonPersonID);

        if (!$countOnly) {
            $query->groupBy(['freeLearningUnit.freeLearningUnitID']);

            $difficultyOptions = $this->db()->selectOne("SELECT value FROM gibbonSetting WHERE scope='Free Learning' AND name='difficultyOptions'");
            $query->bindValue('difficultyOptions', $difficultyOptions);
        }

        if ($publicUnits == 'Y' && empty($gibbonPersonID)) {
            $query->where("freeLearningUnit.sharedPublic='Y'")
                  ->where("freeLearningUnit.gibbonYearGroupIDMinimum IS NULL")
                  ->where("freeLearningUnit.active='Y'");
        }

        if ($criteria->getFilterValue('showInactive') == 'N') {
            $query->where("freeLearningUnit.active='Y'");
        }

        if ($criteria->getFilterValue('gibbonYearGroupIDMinimum') != '') {
            $query->bindValue('gibbonYearGroupIDMinimum', $criteria->getFilterValue('gibbonYearGroupIDMinimum'));
            $query->where("freeLearningUnit.gibbonYearGroupIDMinimum=:gibbonYearGroupIDMinimum");
        }

        $criteria->addFilterRules($this->getSharedFilterRules());

        return $this->runQuery($query, $criteria);
    }

    public function queryUnitsByPrerequisites(QueryCriteria $criteria, $gibbonSchoolYearID, $gibbonPersonID, $roleCategory = null)
    {
        $query = $this
            ->newQuery()
            ->distinct()
            ->from($this->getTableName())
            ->cols(['freeLearningUnit.*', "GROUP_CONCAT(DISTINCT freeLearningUnitPrerequisite.freeLearningUnitIDPrerequisite SEPARATOR ',') as freeLearningUnitIDPrerequisiteList", "GROUP_CONCAT(gibbonDepartment.name SEPARATOR '<br/>') as learningArea", 'freeLearningUnitStudent.status',
                "(SELECT SUM(freeLearningUnitBlock.length) FROM freeLearningUnitBlock WHERE freeLearningUnitBlock.freeLearningUnitID=freeLearningUnit.freeLearningUnitID) as length",
                "FIND_IN_SET(freeLearningUnit.difficulty, :difficultyOptions) as difficultyOrder"])
            ->leftJoin('gibbonDepartment', "freeLearningUnit.gibbonDepartmentIDList LIKE CONCAT('%', gibbonDepartment.gibbonDepartmentID, '%')")
            ->leftJoin('freeLearningUnitStudent', "(freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID AND gibbonPersonIDStudent=:gibbonPersonID)")
            ->leftJoin('freeLearningUnitPrerequisite', "freeLearningUnitPrerequisite.freeLearningUnitID=freeLearningUnit.freeLearningUnitID")
            ->bindValue('gibbonPersonID', $gibbonPersonID)
            ->groupBy(['freeLearningUnit.freeLearningUnitID']);

        $difficultyOptions = $this->db()->selectOne("SELECT value FROM gibbonSetting WHERE scope='Free Learning' AND name='difficultyOptions'");
        $query->bindValue('difficultyOptions', $difficultyOptions);

        switch ($roleCategory) {
            case 'Student':
                $query->leftJoin('gibbonStudentEnrolment', 'gibbonPersonID=:gibbonPersonID AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID')
                      ->leftJoin('gibbonYearGroup as studentYearGroup', 'gibbonStudentEnrolment.gibbonYearGroupID=studentYearGroup.gibbonYearGroupID')
                      ->leftJoin('gibbonYearGroup as minimumYearGroup', 'freeLearningUnit.gibbonYearGroupIDMinimum=minimumYearGroup.gibbonYearGroupID')
                      ->where('(minimumYearGroup.sequenceNumber IS NULL OR minimumYearGroup.sequenceNumber<=studentYearGroup.sequenceNumber)')
                      ->where("freeLearningUnit.active='Y'")
                      ->where("freeLearningUnit.availableStudents='Y'")
                      ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID);
                break;

            case 'Parent':
                $query->where("freeLearningUnit.active='Y'")
                      ->where("freeLearningUnit.availableParents='Y'");
                break;

            case 'Staff':
                $query->where("freeLearningUnit.active='Y'")
                      ->where("freeLearningUnit.availableStaff='Y'");
                break;

            case 'Other':
                $query->where("freeLearningUnit.active='Y'")
                      ->where("freeLearningUnit.availableOther='Y'");
                break;
        }

        if ($criteria->getFilterValue('showInactive') == 'N') {
            $query->where("freeLearningUnit.active='Y'");
        }

        $criteria->addFilterRules($this->getSharedFilterRules());

        return $this->runQuery($query, $criteria);
    }

    public function queryUnitsByLearningAreaStaff(QueryCriteria $criteria, $gibbonPersonID)
    {
        $query = $this
            ->newQuery()
            ->distinct()
            ->from($this->getTableName())
            ->cols(['freeLearningUnit.*', "GROUP_CONCAT(gibbonDepartment.name SEPARATOR '<br/>') as learningArea"])
            ->innerJoin('gibbonDepartment', "freeLearningUnit.gibbonDepartmentIDList LIKE CONCAT('%', gibbonDepartment.gibbonDepartmentID, '%')")
            ->innerJoin('gibbonDepartment as departmentStaff', "freeLearningUnit.gibbonDepartmentIDList LIKE CONCAT('%', departmentStaff.gibbonDepartmentID, '%')")
            ->innerJoin('gibbonDepartmentStaff', 'gibbonDepartmentStaff.gibbonDepartmentID=departmentStaff.gibbonDepartmentID')
            ->where("(role='Coordinator' OR role='Assistant Coordinator' OR role='Teacher (Curriculum)')")
            ->where('gibbonDepartmentStaff.gibbonPersonID=:gibbonPersonID')
            ->bindValue('gibbonPersonID', $gibbonPersonID)
            ->groupBy(['freeLearningUnit.freeLearningUnitID']);
            
        if ($criteria->getFilterValue('gibbonYearGroupIDMinimum') != '') {
            $query->bindValue('gibbonYearGroupIDMinimum', $criteria->getFilterValue('gibbonYearGroupIDMinimum'));
            $query->where("freeLearningUnit.gibbonYearGroupIDMinimum=:gibbonYearGroupIDMinimum");
        }

        $criteria->addFilterRules($this->getSharedFilterRules());

        $this->unionWithCriteria($query, $criteria)
            ->distinct()
            ->cols(['freeLearningUnit.*', "GROUP_CONCAT(gibbonDepartment.name SEPARATOR '<br/>') as learningArea"])
            ->from($this->getTableName())
            ->leftJoin('gibbonDepartment', "freeLearningUnit.gibbonDepartmentIDList LIKE CONCAT('%', gibbonDepartment.gibbonDepartmentID, '%')")
            ->innerJoin('freeLearningUnitAuthor', 'freeLearningUnitAuthor.freeLearningUnitID=freeLearningUnit.freeLearningUnitID AND freeLearningUnitAuthor.gibbonPersonID=:gibbonPersonID')
            ->innerJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=freeLearningUnitAuthor.gibbonPersonID')
            ->bindValue('gibbonPersonID', $gibbonPersonID)
            ->groupBy(['freeLearningUnit.freeLearningUnitID']);
            return $this->runQuery($query, $criteria);
    }
    
    public function selectPrerequisiteNamesByUnitID($freeLearningUnitID)
    {
        $data = ['freeLearningUnitID' => $freeLearningUnitID];
        $sql = "SELECT
                name
            FROM
                freeLearningUnitPrerequisite
                JOIN freeLearningUnit ON (freeLearningUnitPrerequisite.freeLearningUnitIDPrerequisite=freeLearningUnit.freeLearningUnitID)
            WHERE
                freeLearningUnitPrerequisite.freeLearningUnitID=:freeLearningUnitID";

        return $this->db()->select($sql, $data);
    }

    public function selectPrerequisiteIDsByNames($freeLearningUnitIDPrerequisiteList)
    {
        $prerequisites = is_array($freeLearningUnitIDPrerequisiteList)? $freeLearningUnitIDPrerequisiteList : [$freeLearningUnitIDPrerequisiteList];

        $data = [];
        $where = [];
        $count = 1;
        foreach ($prerequisites as $prerequisite) {
            $data["prereq$count"] = $prerequisite;
            $where[] = "name=:prereq$count";
            $count++;
        }

        $sql = "SELECT
                    freeLearningUnit.freeLearningUnitID
                FROM
                    freeLearningUnitPrerequisite
                    JOIN freeLearningUnit ON (freeLearningUnitPrerequisite.freeLearningUnitIDPrerequisite=freeLearningUnit.freeLearningUnitID)
                WHERE
                    ".implode(' OR ', $where)." ORDER BY freeLearningUnitID";

        return $this->db()->select($sql, $data);
    }

    public function selectUnitPrerequisitesByPerson($gibbonPersonID)
    {
        $data = ['gibbonPersonID' => $gibbonPersonID];
        $sql = "SELECT
                	freeLearningUnit.freeLearningUnitID as groupBy,
                	prerequisite.name, freeLearningUnitStudent.status,
                	(CASE WHEN status='Complete - Approved' OR status='Complete - Pending' OR status='Exempt' THEN 'Y' ELSE 'N' END) as complete
                FROM
                	freeLearningUnit
                	LEFT JOIN freeLearningUnitPrerequisite ON (freeLearningUnitPrerequisite.freeLearningUnitID=freeLearningUnit.freeLearningUnitID)
                	LEFT JOIN freeLearningUnit AS prerequisite ON (freeLearningUnitPrerequisite.freeLearningUnitIDPrerequisite=prerequisite.freeLearningUnitID)
                	LEFT JOIN freeLearningUnitStudent ON (freeLearningUnitStudent.freeLearningUnitID=prerequisite.freeLearningUnitID AND gibbonPersonIDStudent=:gibbonPersonID)
                WHERE prerequisite.active='Y'
                ORDER BY prerequisite.name";

        return $this->db()->select($sql, $data);
    }

    public function selectUnitAuthors()
    {
        $sql = "SELECT freeLearningUnitID as groupBy, freeLearningUnitID,
                (CASE WHEN gibbonPerson.surname IS NOT NULL THEN gibbonPerson.surname ELSE freeLearningUnitAuthor.surname END) as surname,
                (CASE WHEN gibbonPerson.preferredName IS NOT NULL THEN gibbonPerson.preferredName ELSE freeLearningUnitAuthor.preferredName END) as preferredName,
                (CASE WHEN gibbonPerson.gibbonPersonID IS NULL THEN freeLearningUnitAuthor.website END) as website
                FROM freeLearningUnitAuthor
                LEFT JOIN gibbonPerson ON (gibbonPerson.gibbonPersonID=freeLearningUnitAuthor.gibbonPersonID)
                ORDER BY surname, preferredName";
        return $this->db()->select($sql);
    }

    public function selectUnitAuthorsByID($freeLearningUnitID)
    {
        $data = ['freeLearningUnitID' => $freeLearningUnitID];
        $sql = "SELECT
                gibbonPerson.title as title,
                (CASE WHEN gibbonPerson.surname IS NOT NULL THEN gibbonPerson.surname ELSE freeLearningUnitAuthor.surname END) as surname,
                (CASE WHEN gibbonPerson.preferredName IS NOT NULL THEN gibbonPerson.preferredName ELSE freeLearningUnitAuthor.preferredName END) as preferredName,
                (CASE WHEN gibbonPerson.gibbonPersonID IS NULL THEN freeLearningUnitAuthor.website END) as website
                FROM freeLearningUnitAuthor
                LEFT JOIN gibbonPerson ON (gibbonPerson.gibbonPersonID=freeLearningUnitAuthor.gibbonPersonID)
                WHERE freeLearningUnitID=:freeLearningUnitID
                ORDER BY surname, preferredName";
        return $this->db()->select($sql, $data);
    }

    public function selectUnitDepartmentsByID($freeLearningUnitID)
    {
        $data = ['freeLearningUnitID' => $freeLearningUnitID];
        $sql = "SELECT gibbonDepartment.name FROM freeLearningUnit
                JOIN gibbonDepartment ON (FIND_IN_SET(gibbonDepartment.gibbonDepartmentID, freeLearningUnit.gibbonDepartmentIDList))
                WHERE freeLearningUnitID=:freeLearningUnitID";

        return $this->db()->select($sql, $data);
    }

    public function selectRelevantClassesByTeacher($gibbonSchoolYearID, $gibbonPersonID)
    {
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonPersonID' => $gibbonPersonID);
        $sql = "SELECT DISTINCT gibbonCourseClassPerson.gibbonCourseClassID
                FROM gibbonCourse
                JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID)
                JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
                WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID
                AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID
                AND (gibbonCourseClassPerson.role='Teacher' OR gibbonCourseClassPerson.role='Assistant')
                GROUP BY gibbonCourseClassPerson.gibbonCourseClassPersonID
                ORDER BY gibbonCourseClassPerson.gibbonCourseClassID";
        return $this->db()->select($sql, $data);
    }

    public function selectLearningAreasAndCourses($gibbonPersonID = null, $disableLearningAreas = 'N', $roleCategory = null, $gibbonSchoolYearID = null, $highestAction = 'Browse Units_prerequisites', $mode = 'Browse')
    {
        $data = [];
        $sql = '';

        // Prep course query
        $data['course'] = __m('Course');
        if ($roleCategory == "Student") {
            $data['gibbonSchoolYearID'] = $gibbonSchoolYearID;
            $data['gibbonPersonID2'] = $gibbonPersonID;
            $course = "(SELECT DISTINCT course as value, course as name, :course as groupBy
                FROM freeLearningUnit
                LEFT JOIN gibbonStudentEnrolment ON (gibbonPersonID=:gibbonPersonID2 AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID)
                LEFT JOIN gibbonYearGroup as studentYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=studentYearGroup.gibbonYearGroupID)
                LEFT JOIN gibbonYearGroup as minimumYearGroup ON (freeLearningUnit.gibbonYearGroupIDMinimum=minimumYearGroup.gibbonYearGroupID)
                WHERE active='Y'
                AND NOT course IS NULL
                AND NOT course=''
                AND (minimumYearGroup.sequenceNumber IS NULL OR minimumYearGroup.sequenceNumber<=studentYearGroup.sequenceNumber)
                AND freeLearningUnit.active='Y'
                AND freeLearningUnit.availableStudents='Y')";
        } else if ($roleCategory == "Staff") {
            $course = "(SELECT DISTINCT course as value, course as name, :course as groupBy
                FROM freeLearningUnit
                WHERE active='Y'
                AND NOT course IS NULL
                AND NOT course=''";
                if ($highestAction = 'Browse Units_prerequisites') {
                    $course .= "AND freeLearningUnit.availableStaff='Y'";
                }
                $course .= ")";
        } else if ($roleCategory == "Parent") {
            $course = "(SELECT DISTINCT course as value, course as name, :course as groupBy
                FROM freeLearningUnit
                WHERE active='Y'
                AND NOT course IS NULL
                AND NOT course=''
                AND freeLearningUnit.availableParents='Y')";
        } else if ($roleCategory == "Other") {
            $course = "(SELECT DISTINCT course as value, course as name, :course as groupBy
                FROM freeLearningUnit
                WHERE active='Y'
                AND NOT course IS NULL
                AND NOT course=''
                AND freeLearningUnit.availableOther='Y')";
        } else {
            $course = "(SELECT DISTINCT course as value, course as name, :course as groupBy
                FROM freeLearningUnit
                WHERE active='Y'
                AND NOT course IS NULL
                AND NOT course=''
                AND freeLearningUnit.sharedPublic='Y'
                AND freeLearningUnit.gibbonYearGroupIDMinimum IS NULL)";
        }

        // Prep main query
        if ($disableLearningAreas != 'Y') {
            if (!empty($gibbonPersonID) && $mode == 'Manage') {
                $data['gibbonPersonID'] = $gibbonPersonID;
                $data['learningArea'] = __m('Learning Area');
                $sql .= "(SELECT gibbonDepartment.gibbonDepartmentID as value, gibbonDepartment.name, :learningArea as groupBy
                        FROM freeLearningUnit
                        JOIN gibbonDepartment ON (FIND_IN_SET(gibbonDepartment.gibbonDepartmentID, freeLearningUnit.gibbonDepartmentIDList))
                        JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID)
                        WHERE gibbonDepartment.type='Learning Area' AND gibbonDepartmentStaff.gibbonPersonID=:gibbonPersonID
                        AND (role='Coordinator' OR role='Assistant Coordinator' OR role='Teacher (Curriculum)')
                        GROUP BY gibbonDepartment.gibbonDepartmentID
                        ORDER BY gibbonDepartment.name
                        ) UNION ALL ";
            } else {
                $data['learningArea'] = __m('Learning Area');
                $sql .= "(SELECT gibbonDepartment.gibbonDepartmentID as value, gibbonDepartment.name, :learningArea as groupBy
                        FROM freeLearningUnit
                        JOIN gibbonDepartment ON (FIND_IN_SET(gibbonDepartment.gibbonDepartmentID, freeLearningUnit.gibbonDepartmentIDList))
                        WHERE type='Learning Area'
                        GROUP BY gibbonDepartment.gibbonDepartmentID
                        ORDER BY gibbonDepartment.name
                        ) UNION ALL ";
            }
            $sql .= $course. " ORDER BY FIELD(groupBy, :learningArea, :course), name";

        } else {
            $sql = $course . " ORDER BY course";
        }

        return $this->db()->select($sql, $data);
    }

    public function selectCoursesByStudent($gibbonPersonID)
    {
        $data = ['gibbonPersonID' => $gibbonPersonID];
        $sql = "SELECT freeLearningUnit.course FROM freeLearningUnitStudent
                JOIN freeLearningUnit ON (freeLearningUnit.freeLearningUnitID=freeLearningUnitStudent.freeLearningUnitID)
                WHERE freeLearningUnitStudent.gibbonPersonIDStudent=:gibbonPersonID
                GROUP BY freeLearningUnit.course";

        return $this->db()->select($sql, $data);
    }

    public function selectAllCourses()
    {
        $sql = "SELECT freeLearningUnit.course as value, freeLearningUnit.course as name
                FROM freeLearningUnit
                WHERE freeLearningUnit.active='Y'
                GROUP BY freeLearningUnit.course";

        return $this->db()->select($sql);
    }

    protected function getSharedFilterRules()
    {
        return [
            'active' => function ($query, $active) {
                return $query
                    ->where('freeLearningUnit.active = :active')
                    ->bindValue('active', $active);
            },
            'department' => function ($query, $department) {
                $department = is_numeric($department) ? str_pad($department, 4, '0', STR_PAD_LEFT): $department;
                return $query
                    ->where('(FIND_IN_SET(:department, freeLearningUnit.gibbonDepartmentIDList) OR course = :department)')
                    ->bindValue('department', $department);
            },
            'course' => function ($query, $course) {
                $course = is_array($course) ? implode(',', $course) : $course;
                return $query
                    ->where("FIND_IN_SET(freeLearningUnit.course, :course) AND freeLearningUnit.course <> ''")
                    ->bindValue('course', $course);
            },
            'difficulty' => function ($query, $difficulty) {
                return $query
                    ->where('freeLearningUnit.difficulty = :difficulty')
                    ->bindValue('difficulty', $difficulty);
            },
            'access' => function ($query, $access) {
                switch ($access) {
                    case 'students': return $query->where("freeLearningUnit.availableStudents='Y'");
                    case 'staff': return $query->where("freeLearningUnit.availableStaff='Y'");
                    case 'parents': return $query->where("freeLearningUnit.availableParents='Y'");
                    case 'other': return $query->where("freeLearningUnit.availableOther='Y'");
                }
                return $query;
            },
        ];
    }
}
