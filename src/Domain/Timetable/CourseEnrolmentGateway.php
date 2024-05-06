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
class CourseEnrolmentGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonCourseClassPerson';
    private static $primaryKey = 'gibbonCourseClassPersonID';

    private static $searchableColumns = ['gibbonCourse.name', 'gibbonCourse.nameShort'];

    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryCourseEnrolmentByClass(QueryCriteria $criteria, $gibbonSchoolYearID, $gibbonCourseClassID, $left = false, $includeExpected = false)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonCourseClassPerson.gibbonCourseClassPersonID', 'gibbonCourseClass.gibbonCourseClassID', 'gibbonCourseClass.gibbonCourseID', 'gibbonPerson.gibbonPersonID', 'gibbonPerson.title', 'gibbonPerson.surname', 'gibbonPerson.preferredName', 'gibbonPerson.status', 'gibbonPerson.dateStart', 'gibbonPerson.dateEnd', 'gibbonPerson.email', 'gibbonPerson.privacy', 'gibbonPerson.image_240', 'gibbonPerson.dob', 'gibbonCourseClassPerson.reportable', 'gibbonCourseClassPerson.role', "(CASE WHEN gibbonCourseClassPerson.role LIKE 'Teacher%' THEN 0 WHEN gibbonCourseClassPerson.role LIKE 'Assistant%' THEN 1 WHEN gibbonCourseClassPerson.role LIKE 'Technician%' THEN 2 WHEN gibbonCourseClassPerson.role LIKE 'Parent%' THEN 3 WHEN gibbonCourseClassPerson.role LIKE 'Student%' THEN 4 ELSE 5 END) as roleSortOrder", "'Student' as roleCategory", 'gibbonCourse.gibbonYearGroupIDList as yearGroup'
            ])
            ->innerJoin('gibbonCourseClass', 'gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID')
            ->innerJoin('gibbonCourse', 'gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID')
            ->innerJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID')
            ->where('gibbonCourse.gibbonSchoolYearID = :gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->where('gibbonCourseClassPerson.gibbonCourseClassID = :gibbonCourseClassID')
            ->bindValue('gibbonCourseClassID', $gibbonCourseClassID);

        if ($left) {
            $query->where("gibbonCourseClassPerson.role LIKE '%Left'");
        } else {
            $query->where("gibbonCourseClassPerson.role NOT LIKE '%Left'");
        }

        if ($includeExpected) {
            $query->where("(gibbonPerson.status = 'Full' OR gibbonPerson.status = 'Expected')")
                  ->where('(gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd >= :today)')
                  ->bindValue('today', date('Y-m-d'));
        } else {
            $query->where("gibbonPerson.status = 'Full'")
                  ->where('(gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart <= :today)')
                  ->where('(gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd >= :today)')
                  ->bindValue('today', date('Y-m-d'));
        }

        $criteria->addFilterRules([
            'nonStudents' => function ($query, $role) {
                return $query->where("gibbonCourseClassPerson.role NOT LIKE 'Student%'");
            },
        ]);

        return $this->runQuery($query, $criteria);
    }

    public function queryCourseEnrolmentByPerson(QueryCriteria $criteria, $gibbonSchoolYearID, $gibbonPersonID, $left = false)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonCourseClass.gibbonCourseClassID', 'gibbonCourse.name AS courseName', 'gibbonCourse.nameShort AS course', 'gibbonCourseClass.nameShort AS class', 'gibbonCourseClassPerson.reportable', 'gibbonCourseClassPerson.role', "(CASE WHEN gibbonCourseClassPerson.role NOT LIKE 'Student%' THEN 0 ELSE 1 END) as roleSortOrder"
            ])
            ->innerJoin('gibbonCourseClass', 'gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID')
            ->innerJoin('gibbonCourse', 'gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID')
            ->innerJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID')
            ->where('gibbonCourse.gibbonSchoolYearID = :gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->where('gibbonCourseClassPerson.gibbonPersonID = :gibbonPersonID')
            ->bindValue('gibbonPersonID', $gibbonPersonID);

        if ($left) {
            $query->where("gibbonCourseClassPerson.role LIKE '%Left'");
        } else {
            $query->where("gibbonCourseClassPerson.role NOT LIKE '%Left'");
        }

        return $this->runQuery($query, $criteria);
    }

    public function selectEnrolableClassesByYearGroup($gibbonSchoolYearID, $gibbonYearGroupID)
    {
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonYearGroupID' => $gibbonYearGroupID, 'today' => date('Y-m-d'));
        $sql = "SELECT gibbonCourseClass.gibbonCourseClassID, gibbonCourse.name as courseName, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, enrolmentMin, enrolmentMax,
                    teacher.surname, teacher.preferredName,
                    (SELECT count(*) FROM gibbonCourseClassPerson JOIN gibbonPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID)
                    WHERE gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID AND (status='Full' OR status='Expected') AND role='Student' AND (dateEnd IS NULL OR dateEnd>=:today))
                    AS studentCount
                FROM gibbonCourse
                JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID)
                LEFT JOIN
                    (SELECT gibbonCourseClassID, title, surname, preferredName FROM gibbonCourseClassPerson
                    JOIN gibbonPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID)
                    WHERE gibbonPerson.status='Full' AND gibbonCourseClassPerson.role = 'Teacher')
                    AS teacher ON (teacher.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
                WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID
                AND FIND_IN_SET(:gibbonYearGroupID, gibbonCourse.gibbonYearGroupIDList)
                GROUP BY gibbonCourseClass.gibbonCourseClassID
                ORDER BY course, class";

        return $this->db()->select($sql, $data);
    }

    public function selectEnrolableStudentsByYearGroup($gibbonSchoolYearID, $gibbonYearGroupID)
    {
        $gibbonYearGroupIDList = is_array($gibbonYearGroupID)? implode(',', $gibbonYearGroupID) : $gibbonYearGroupID;
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonYearGroupIDList' => $gibbonYearGroupIDList);
        $sql = "SELECT gibbonPerson.gibbonPersonID, preferredName, surname, username, gibbonFormGroup.name AS formGroupName
                FROM gibbonPerson
                JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID)
                JOIN gibbonFormGroup ON (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID)
                WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID
                AND (gibbonPerson.status='Full' OR gibbonPerson.status='Expected')
                AND FIND_IN_SET(gibbonStudentEnrolment.gibbonYearGroupID, :gibbonYearGroupIDList)
                ORDER BY formGroupName, surname, preferredName";

        return $this->db()->select($sql, $data);
    }

    public function selectCourseEnrolmentByFormGroup($gibbonFormGroupID)
    {
        $data = array('gibbonFormGroupID' => $gibbonFormGroupID);
        $sql = "SELECT DISTINCT gibbonPerson.gibbonPersonID, gibbonPerson.surname, gibbonPerson.preferredName, gibbonFormGroup.name as formGroup,
                    (SELECT COUNT(*) FROM gibbonCourseClassPerson
                    JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID)
                    JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID)
                    WHERE gibbonCourseClassPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID
                    AND gibbonCourse.gibbonSchoolYearID=gibbonFormGroup.gibbonSchoolYearID
                    AND gibbonCourseClassPerson.role = 'Student') AS classCount
                FROM gibbonPerson
                JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID)
                JOIN gibbonFormGroup ON (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID)
                WHERE gibbonFormGroup.gibbonFormGroupID=:gibbonFormGroupID
                AND (gibbonPerson.status='Full' OR gibbonPerson.status='Expected')
                ORDER BY gibbonPerson.surname, gibbonPerson.preferredName";

        return $this->db()->select($sql, $data);
    }

    public function selectClassTeachersByStudent($gibbonSchoolYearID, $gibbonPersonIDStudent, $gibbonCourseClassID = null)
    {
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonPersonIDStudent' => $gibbonPersonIDStudent);
        $sql = "SELECT DISTINCT teacher.gibbonPersonID, teacher.surname, teacher.preferredName, teacher.email
                FROM gibbonCourseClassPerson AS studentClass
                JOIN gibbonCourseClassPerson AS teacherClass ON (studentClass.gibbonCourseClassID=teacherClass.gibbonCourseClassID)
                JOIN gibbonPerson AS teacher ON (teacherClass.gibbonPersonID=teacher.gibbonPersonID)
                JOIN gibbonCourseClass ON (studentClass.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
                JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID)
                WHERE teacher.status='Full'
                AND teacherClass.role='Teacher'
                AND studentClass.role='Student'
                AND studentClass.gibbonPersonID=:gibbonPersonIDStudent
                AND gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID ";

        if (!empty($gibbonCourseClassID)) {
            $data['gibbonCourseClassID'] = $gibbonCourseClassID;
            $sql .= " AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID ";
        }

        $sql .= " ORDER BY teacher.preferredName, teacher.surname, teacher.email";

        return $this->db()->select($sql, $data);
    }

    public function selectClassParticipantsByDate($gibbonCourseClassID, $date, $timeStart, $timeEnd)
    {
        $data =['gibbonCourseClassID' => $gibbonCourseClassID, 'date' => $date, 'timeStart' => $timeStart, 'timeEnd' => $timeEnd, 'today' => date('Y-m-d')];
        $sql = "SELECT gibbonCourseClassPerson.*, gibbonPerson.*
            FROM gibbonCourseClassPerson
            INNER JOIN gibbonPerson ON gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID
            LEFT JOIN (
                SELECT gibbonTTDayRowClass.gibbonCourseClassID, gibbonTTDayRowClass.gibbonTTDayRowClassID
                FROM gibbonTTDayDate
                JOIN gibbonTTDayRowClass ON (gibbonTTDayDate.gibbonTTDayID=gibbonTTDayRowClass.gibbonTTDayID)
                JOIN gibbonTTColumnRow ON (gibbonTTColumnRow.gibbonTTColumnRowID=gibbonTTDayRowClass.gibbonTTColumnRowID)
                WHERE gibbonTTDayDate.date=:date AND gibbonTTColumnRow.timeStart>=:timeStart AND gibbonTTColumnRow.timeEnd<=:timeEnd) AS gibbonTTDayRowClassSubset ON (gibbonTTDayRowClassSubset.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID)
            LEFT JOIN gibbonTTDayRowClassException ON (gibbonTTDayRowClassException.gibbonTTDayRowClassID=gibbonTTDayRowClassSubset.gibbonTTDayRowClassID AND gibbonTTDayRowClassException.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID)
            WHERE gibbonCourseClassPerson.gibbonCourseClassID=:gibbonCourseClassID
            AND status='Full'
            AND (dateStart IS NULL OR dateStart<=:today)
            AND (dateEnd IS NULL OR dateEnd>=:today)
            AND (NOT role='Student - Left') AND (NOT role='Teacher - Left') AND NOT (role='Teacher' AND reportable='N')
            GROUP BY gibbonCourseClassPerson.gibbonCourseClassPersonID, gibbonPerson.gibbonPersonID
            HAVING COUNT(gibbonTTDayRowClassExceptionID) = 0
            ORDER BY FIELD(role, 'Teacher', 'Assistant', 'Technician', 'Student', 'Parent'), surname, preferredName";

        return $this->db()->select($sql, $data);
    }

    public function selectClassesByPersonAndDate($gibbonSchoolYearID, $gibbonPersonID, $date)
    {
        $data = ['gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonPersonID' => $gibbonPersonID, 'date' => $date];
        $sql = "SELECT DISTINCT gibbonTT.gibbonTTID, gibbonTT.name, gibbonTTDayRowClass.gibbonTTDayRowClassID, gibbonCourseClass.gibbonCourseClassID, gibbonCourseClass.nameShort as classNameShort, gibbonCourseClass.attendance, gibbonTTColumnRow.name as columnName, gibbonTTColumnRow.timeStart, gibbonTTColumnRow.timeEnd, gibbonCourse.name as courseName, gibbonCourse.nameShort as courseNameShort
            FROM gibbonTT
            JOIN gibbonTTDay ON (gibbonTT.gibbonTTID=gibbonTTDay.gibbonTTID)
            JOIN gibbonTTDayRowClass ON (gibbonTTDayRowClass.gibbonTTDayID=gibbonTTDay.gibbonTTDayID)
            JOIN gibbonTTDayDate ON (gibbonTTDay.gibbonTTDayID=gibbonTTDayDate.gibbonTTDayID)
            JOIN gibbonCourseClass ON (gibbonTTDayRowClass.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
            JOIN gibbonTTColumnRow ON (gibbonTTColumnRow.gibbonTTColumnRowID=gibbonTTDayRowClass.gibbonTTColumnRowID)
            JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
            JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID)
            WHERE gibbonPersonID=:gibbonPersonID
            AND gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID
            AND gibbonTT.active='Y'
            AND gibbonTTDayDate.date=:date
            AND gibbonCourseClassPerson.role='Student'
            ORDER BY gibbonTTColumnRow.timeStart ASC";

        return $this->db()->select($sql, $data);
    }

    public function getClassStudentCount($gibbonCourseClassID, $honourStartDate = true)
    {
        $data =['gibbonCourseClassID' => $gibbonCourseClassID, 'today' => date('Y-m-d')];
        $sql = "SELECT COUNT(gibbonCourseClassPerson.gibbonCourseClassPersonID)
            FROM gibbonCourseClassPerson
            INNER JOIN gibbonPerson ON gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID
            WHERE gibbonCourseClassPerson.gibbonCourseClassID=:gibbonCourseClassID
            AND (gibbonPerson.status='Full' OR gibbonPerson.status='Expected')
            AND (gibbonPerson.dateEnd IS NULL  OR gibbonPerson.dateEnd>=:today)
            AND gibbonCourseClassPerson.role='Student'";

            if ($honourStartDate) {
                $sql .= " AND (gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart<=:today)";
            }

        return $this->db()->selectOne($sql, $data);
    }

    public function getEnrolmentDateBySchoolYear($gibbonSchoolYearID) {
        $data = ['gibbonSchoolYearIDEntry' => $gibbonSchoolYearID];
        $sql = "SELECT GREATEST((SELECT firstDay FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearIDEntry), CURRENT_DATE)";

        return $this->db()->selectOne($sql, $data);
    }

    public function unenrolAutomaticCourseEnrolments($gibbonFormGroupID, $gibbonStudentEnrolmentID, $date = null)
    {
        $data = array('gibbonFormGroupIDOriginal' => $gibbonFormGroupID, 'gibbonStudentEnrolmentID' => $gibbonStudentEnrolmentID, 'dateUnenrolled' => $date ?? date('Y-m-d'));
        $sql = "UPDATE gibbonCourseClassPerson
                JOIN gibbonStudentEnrolment ON (gibbonCourseClassPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID)
                JOIN gibbonCourseClassMap ON (gibbonCourseClassMap.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID)
                SET role='Student - Left', dateUnenrolled=:dateUnenrolled
                WHERE gibbonStudentEnrolment.gibbonStudentEnrolmentID=:gibbonStudentEnrolmentID
                AND gibbonCourseClassMap.gibbonFormGroupID=:gibbonFormGroupIDOriginal";

        return $this->db()->update($sql, $data);
    }

    public function deleteAutomaticCourseEnrolments($gibbonFormGroupID, $gibbonStudentEnrolmentID)
    {
        $data = array('gibbonFormGroupIDOriginal' => $gibbonFormGroupID, 'gibbonStudentEnrolmentID' => $gibbonStudentEnrolmentID);
        $sql = "DELETE gibbonCourseClassPerson
                FROM gibbonCourseClassPerson
                JOIN gibbonStudentEnrolment ON (gibbonCourseClassPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID)
                JOIN gibbonCourseClassMap ON (gibbonCourseClassMap.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID)
                WHERE gibbonStudentEnrolment.gibbonStudentEnrolmentID=:gibbonStudentEnrolmentID
                AND gibbonCourseClassMap.gibbonFormGroupID=:gibbonFormGroupIDOriginal";

        return $this->db()->update($sql, $data);
    }

    public function updateAutomaticCourseEnrolments($gibbonFormGroupID, $gibbonStudentEnrolmentID, $date = null)
    {
        $data = array('gibbonFormGroupID' => $gibbonFormGroupID, 'gibbonStudentEnrolmentID' => $gibbonStudentEnrolmentID, 'dateEnrolled' => $date ?? date('Y-m-d'));
        $sql = "UPDATE gibbonCourseClassPerson
                JOIN gibbonStudentEnrolment ON (gibbonCourseClassPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID)
                JOIN gibbonCourseClassMap ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClassMap.gibbonCourseClassID
                    AND gibbonCourseClassMap.gibbonFormGroupID=gibbonStudentEnrolment.gibbonFormGroupID)
                SET gibbonCourseClassPerson.role='Student', gibbonCourseClassPerson.dateEnrolled=:dateEnrolled, gibbonCourseClassPerson.dateUnenrolled=NULL, reportable='Y'
                WHERE gibbonStudentEnrolment.gibbonStudentEnrolmentID=:gibbonStudentEnrolmentID
                AND gibbonStudentEnrolment.gibbonFormGroupID=:gibbonFormGroupID
                AND gibbonCourseClassPerson.gibbonCourseClassPersonID IS NOT NULL";

        return $this->db()->update($sql, $data);
    }

    public function insertAutomaticCourseEnrolments($gibbonFormGroupID, $gibbonPersonID, $date = null)
    {
        $data = array('gibbonFormGroupID' => $gibbonFormGroupID, 'gibbonPersonID' => $gibbonPersonID, 'dateEnrolled' => $date ?? date('Y-m-d'));
        $sql = "INSERT INTO gibbonCourseClassPerson (`gibbonCourseClassID`, `gibbonPersonID`, `role`, `dateEnrolled`, `reportable`)
                SELECT gibbonCourseClassMap.gibbonCourseClassID, :gibbonPersonID, 'Student', :dateEnrolled, 'Y'
                FROM gibbonCourseClassMap
                LEFT JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClassMap.gibbonCourseClassID AND gibbonCourseClassPerson.role='Student')
                WHERE gibbonCourseClassMap.gibbonFormGroupID=:gibbonFormGroupID
                AND gibbonCourseClassPerson.gibbonCourseClassPersonID IS NULL";

        return $this->db()->insert($sql, $data);
    }
}
