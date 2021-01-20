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

namespace Gibbon\Domain\Students;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\Traits\SharedUserLogic;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * @version v16
 * @since   v16
 */
class StudentGateway extends QueryableGateway
{
    use TableAware;
    use SharedUserLogic;

    private static $tableName = 'gibbonStudentEnrolment';
    private static $primaryKey = 'gibbonStudentEnrolmentID';

    private static $searchableColumns = ['gibbonPerson.preferredName', 'gibbonPerson.surname', 'gibbonPerson.username', 'gibbonPerson.email', 'gibbonPerson.emailAlternate', 'gibbonPerson.studentID', 'gibbonPerson.phone1', 'gibbonPerson.vehicleRegistration'];

    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryStudentsBySchoolYear(QueryCriteria $criteria, $gibbonSchoolYearID, $searchFamilyDetails = false)
    {
        $query = $this
            ->newQuery()
            ->distinct()
            ->from('gibbonPerson')
            ->cols([
                'gibbonPerson.gibbonPersonID', 'gibbonStudentEnrolmentID', 'gibbonPerson.title', 'gibbonPerson.preferredName', 'gibbonPerson.surname', 'gibbonPerson.image_240',  'gibbonYearGroup.gibbonYearGroupID', 'gibbonYearGroup.nameShort AS yearGroup', 'gibbonRollGroup.gibbonRollGroupID', 'gibbonRollGroup.nameShort AS rollGroup', 'gibbonStudentEnrolment.rollOrder', 'gibbonPerson.dateStart', 'gibbonPerson.dateEnd', 'gibbonPerson.status', "'Student' as roleCategory"
            ])
            ->leftJoin('gibbonStudentEnrolment', 'gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID AND gibbonStudentEnrolment.gibbonSchoolYearID = :gibbonSchoolYearID')
            ->leftJoin('gibbonYearGroup', 'gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID')
            ->leftJoin('gibbonRollGroup', 'gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID);

        if ($criteria->hasFilter('all')) {
            $query->innerJoin('gibbonRole', 'FIND_IN_SET(gibbonRole.gibbonRoleID, gibbonPerson.gibbonRoleIDAll)')
                  ->where("gibbonRole.category='Student'");
        } else {
            $query->where("gibbonStudentEnrolment.gibbonStudentEnrolmentID IS NOT NULL")
                  ->where("gibbonPerson.status = 'Full'")
                  ->where('(gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart <= :today)')
                  ->where('(gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd >= :today)')
                  ->bindValue('today', date('Y-m-d'));
        }

        if ($searchFamilyDetails && $criteria->hasSearchText()) {
            self::$searchableColumns = array_merge(self::$searchableColumns, ['parent1.email', 'parent1.emailAlternate', 'parent2.email', 'parent2.emailAlternate']);

            $query
                ->leftJoin('gibbonFamilyChild as child', "child.gibbonPersonID=gibbonPerson.gibbonPersonID")
                ->leftJoin('gibbonFamilyAdult as adult1', "(adult1.gibbonFamilyID=child.gibbonFamilyID AND adult1.contactPriority=1)")
                ->leftJoin('gibbonPerson as parent1', "(parent1.gibbonPersonID=adult1.gibbonPersonID AND parent1.status='Full')")
                ->leftJoin('gibbonFamilyAdult as adult2', "(adult2.gibbonFamilyID=child.gibbonFamilyID AND adult2.contactPriority=2)")
                ->leftJoin('gibbonPerson as parent2', "(parent2.gibbonPersonID=adult2.gibbonPersonID AND parent2.status='Full')");
        }

        $criteria->addFilterRules($this->getSharedUserFilterRules());

        return $this->runQuery($query, $criteria);
    }

    public function queryStudentEnrolmentBySchoolYear(QueryCriteria $criteria, $gibbonSchoolYearID)
    {
        $query = $this
            ->newQuery()
            ->from('gibbonPerson')
            ->cols([
                'gibbonPerson.gibbonPersonID', 'gibbonStudentEnrolmentID', 'gibbonPerson.title', 'gibbonPerson.preferredName', 'gibbonPerson.surname', 'gibbonPerson.image_240', 'gibbonYearGroup.nameShort AS yearGroup', 'gibbonRollGroup.nameShort AS rollGroup', 'gibbonStudentEnrolment.rollOrder', 'gibbonPerson.dateStart', 'gibbonPerson.dateEnd', 'gibbonPerson.status', "'Student' as roleCategory"
            ])
            ->innerJoin('gibbonStudentEnrolment', 'gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID')
            ->innerJoin('gibbonYearGroup', 'gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID')
            ->innerJoin('gibbonRollGroup', 'gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID')
            ->where('gibbonStudentEnrolment.gibbonSchoolYearID = :gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID);

        $criteria->addFilterRules($this->getSharedUserFilterRules());

        return $this->runQuery($query, $criteria);
    }

    public function queryStudentEnrolmentByRollGroup(QueryCriteria $criteria, $gibbonRollGroupID = null)
    {
        $query = $this
            ->newQuery()
            ->from('gibbonPerson')
            ->cols([
                'gibbonPerson.gibbonPersonID', 'gibbonStudentEnrolmentID', 'gibbonStudentEnrolment.gibbonSchoolYearID', 'gibbonPerson.title', 'gibbonPerson.preferredName', 'gibbonPerson.surname', 'gibbonPerson.image_240', 'gibbonYearGroup.nameShort AS yearGroup', 'gibbonRollGroup.nameShort AS rollGroup', 'gibbonStudentEnrolment.rollOrder', 'gibbonPerson.dateStart', 'gibbonPerson.dateEnd', 'gibbonPerson.status', "'Student' as roleCategory", 'gender', 'dob', 'citizenship1', 'citizenship2', 'transport', 'lockerNumber', 'privacy'
            ])
            ->innerJoin('gibbonStudentEnrolment', 'gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID')
            ->innerJoin('gibbonYearGroup', 'gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID')
            ->innerJoin('gibbonRollGroup', 'gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID')
            ->where("gibbonPerson.status = 'Full'")
            ->where('(gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart <= :today)')
            ->where('(gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd >= :today)')
            ->bindValue('today', date('Y-m-d'));

        if (!empty($gibbonRollGroupID)) {
            $query
                ->where('gibbonStudentEnrolment.gibbonRollGroupID = :gibbonRollGroupID')
                ->bindValue('gibbonRollGroupID', $gibbonRollGroupID);
        } else {
            $query->where("gibbonStudentEnrolment.gibbonSchoolYearID=(SELECT gibbonSchoolYearID FROM gibbonSchoolYear WHERE status='Current' LIMIT 1)");
        }

        $criteria->addFilterRules($this->getSharedUserFilterRules());

        $criteria->addFilterRules([
            'view' => function ($query, $view) {
                if ($view == 'extended') {
                    $query->cols(['gibbonHouse.name as house', 'gibbonPersonMedical.*', 'COUNT(gibbonPersonMedicalConditionID) as conditionCount'])
                        ->leftJoin('gibbonHouse', 'gibbonHouse.gibbonHouseID=gibbonPerson.gibbonHouseID')
                        ->leftJoin('gibbonPersonMedical', 'gibbonPersonMedical.gibbonPersonID=gibbonPerson.gibbonPersonID')
                        ->leftJoin('gibbonPersonMedicalCondition', 'gibbonPersonMedicalCondition.gibbonPersonMedicalID=gibbonPersonMedical.gibbonPersonMedicalID')
                        ->groupBy(['gibbonPerson.gibbonPersonID']);
                }
                return $query;
            },
        ]);

        return $this->runQuery($query, $criteria);
    }

    public function queryStudentsAndTeachersBySchoolYear(QueryCriteria $criteria, $gibbonSchoolYearID, $gibbonRoleIDCurrentCategory = null)
    {
        $query = $this
            ->newQuery()
            ->distinct()
            ->from('gibbonPerson')
            ->cols([
                'gibbonPerson.gibbonPersonID', 'gibbonStudentEnrolmentID', 'gibbonPerson.title', 'gibbonPerson.preferredName', 'gibbonPerson.surname', 'gibbonPerson.image_240', 'gibbonYearGroup.nameShort AS yearGroup', 'gibbonRollGroup.nameShort AS rollGroup', 'gibbonStudentEnrolment.rollOrder', 'gibbonPerson.dateStart', 'gibbonPerson.dateEnd', 'gibbonPerson.status', 'gibbonRole.category as roleCategory', 'gibbonStaff.type as staffType'
            ])
            ->innerJoin('gibbonRole', 'FIND_IN_SET(gibbonRole.gibbonRoleID, gibbonPerson.gibbonRoleIDAll)')
            ->leftJoin('gibbonStudentEnrolment', 'gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID AND gibbonStudentEnrolment.gibbonSchoolYearID = :gibbonSchoolYearID')
            ->leftJoin('gibbonYearGroup', 'gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID')
            ->leftJoin('gibbonRollGroup', 'gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID')
            ->leftJoin('gibbonStaff', "gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID")
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)

            ->groupBy(['gibbonPerson.gibbonPersonID']);

        if (!$criteria->hasFilter('all') || $gibbonRoleIDCurrentCategory != 'Staff') {
            $query->where("(gibbonStudentEnrolment.gibbonStudentEnrolmentID IS NOT NULL OR (gibbonStaff.gibbonStaffID IS NOT NULL AND gibbonRole.category='Staff') )")
                  ->where("gibbonPerson.status = 'Full'")
                  ->where('(gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart <= :today)')
                  ->where('(gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd >= :today)')
                  ->bindValue('today', date('Y-m-d'));
        }

        $criteria->addFilterRules($this->getSharedUserFilterRules());

        return $this->runQuery($query, $criteria);
    }

    public function selectAnyStudentsByFamilyAdult($gibbonSchoolYearID, $gibbonPersonID)
    {
        $data = array('gibbonPersonID' => $gibbonPersonID);
        $sql = "SELECT gibbonPerson.gibbonPersonID, title, surname, preferredName, image_240, 'Student' as roleCategory
                FROM gibbonFamilyAdult
                JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamilyAdult.gibbonFamilyID)
                JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID)
                WHERE gibbonFamilyAdult.gibbonPersonID=:gibbonPersonID
                AND gibbonFamilyAdult.childDataAccess='Y'
                GROUP BY gibbonPerson.gibbonPersonID
                ORDER BY surname, preferredName";

        return $this->db()->select($sql, $data);
    }

    public function selectActiveStudentsByFamilyAdult($gibbonSchoolYearID, $gibbonPersonID)
    {
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonPersonID' => $gibbonPersonID, 'today' => date('Y-m-d'));
        $sql = "SELECT gibbonPerson.gibbonPersonID, title, surname, preferredName, image_240, gibbonYearGroup.nameShort AS yearGroup, gibbonRollGroup.nameShort AS rollGroup, 'Student' as roleCategory
                FROM gibbonFamilyAdult
                JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamilyAdult.gibbonFamilyID)
                JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID)
                JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID)
                JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID)
                JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID)
                WHERE gibbonFamilyAdult.gibbonPersonID=:gibbonPersonID
                AND gibbonFamilyAdult.childDataAccess='Y'
                AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID
                AND gibbonPerson.status='Full'
                AND (dateStart IS NULL OR dateStart<=:today)
                AND (dateEnd IS NULL  OR dateEnd>=:today)
                GROUP BY gibbonPerson.gibbonPersonID
                ORDER BY surname, preferredName";

        return $this->db()->select($sql, $data);
    }

    public function selectActiveStudentByPerson($gibbonSchoolYearID, $gibbonPersonID)
    {
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonPersonID' => $gibbonPersonID, 'today' => date('Y-m-d'));
        $sql = "SELECT gibbonPerson.gibbonPersonID, title, surname, preferredName, image_240, gender, gibbonStudentEnrolment.gibbonStudentEnrolmentID, gibbonStudentEnrolment.gibbonSchoolYearID, gibbonYearGroup.gibbonYearGroupID, gibbonYearGroup.nameShort AS yearGroup, gibbonRollGroup.gibbonRollGroupID, gibbonRollGroup.nameShort AS rollGroup, 'Student' as roleCategory, gibbonPerson.privacy
                FROM gibbonPerson
                JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID)
                JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID)
                JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID)
                WHERE gibbonPerson.gibbonPersonID=:gibbonPersonID
                AND gibbonPerson.status='Full'
                AND (dateStart IS NULL OR dateStart<=:today)
                AND (dateEnd IS NULL  OR dateEnd>=:today)
                AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID";

        return $this->db()->select($sql, $data);
    }

    public function getStudentByUsername($gibbonSchoolYearID, $username)
    {
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'username' => $username);
        $sql = "SELECT gibbonPerson.gibbonPersonID, title, surname, preferredName, image_240, gender, gibbonStudentEnrolment.gibbonStudentEnrolmentID, gibbonStudentEnrolment.gibbonSchoolYearID, gibbonYearGroup.gibbonYearGroupID, gibbonYearGroup.nameShort AS yearGroup, gibbonRollGroup.gibbonRollGroupID, gibbonRollGroup.nameShort AS rollGroup, 'Student' as roleCategory, gibbonPerson.privacy, gibbonPerson.username
                FROM gibbonPerson
                JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID)
                JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID)
                JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID)
                WHERE gibbonPerson.username=:username
                AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID";

        return $this->db()->selectOne($sql, $data);
    }

    public function selectAllStudentEnrolmentsByPerson($gibbonPersonID)
    {
        $data = array('gibbonPersonID' => $gibbonPersonID);
        $sql = "SELECT *
                FROM gibbonStudentEnrolment
                JOIN gibbonSchoolYear ON (gibbonStudentEnrolment.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID)
                WHERE gibbonPersonID=:gibbonPersonID
                AND (gibbonSchoolYear.status='Current' OR gibbonSchoolYear.status='Past')
                ORDER BY sequenceNumber DESC";

        return $this->db()->select($sql, $data);
    }

    public function getStudentEnrolmentCount($gibbonSchoolYearID)
    {
        $data = ['gibbonSchoolYearID' => $gibbonSchoolYearID, 'today' => date('Y-m-d')];
        $sql = "SELECT COUNT(gibbonPerson.gibbonPersonID)
                FROM gibbonPerson
                JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID)
                JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID)
                WHERE gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID
                AND status='FULL' AND (dateStart IS NULL OR dateStart<=:today) AND (dateEnd IS NULL  OR dateEnd>=:today)";

        return $this->db()->selectOne($sql, $data);
    }

    public function selectAllRelatedUsersByStudent($gibbonSchoolYearID, $gibbonYearGroupID, $gibbonRollGroupID, $gibbonPersonID)
    {
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonPersonID' => $gibbonPersonID, 'gibbonYearGroupID' => $gibbonYearGroupID, 'gibbonRollGroupID' => $gibbonRollGroupID);
        $sql = "
            (
                SELECT DISTINCT gibbonCourseClass.gibbonCourseClassID as classID, teacher.gibbonPersonID, teacher.surname, teacher.preferredName, teacher.email, teacher.image_240, gibbonCourse.name as type, 4 as listOrder
                FROM gibbonPerson AS teacher
                JOIN gibbonCourseClassPerson AS teacherClass ON (teacherClass.gibbonPersonID=teacher.gibbonPersonID)
                JOIN gibbonCourseClassPerson AS studentClass ON (studentClass.gibbonCourseClassID=teacherClass.gibbonCourseClassID)
                JOIN gibbonPerson AS student ON (studentClass.gibbonPersonID=student.gibbonPersonID)
                JOIN gibbonCourseClass ON (studentClass.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
                JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID)
                WHERE teacher.status='Full' AND teacherClass.role='Teacher' AND studentClass.role='Student' AND student.gibbonPersonID=:gibbonPersonID AND gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID
                ORDER BY teacher.preferredName, teacher.surname, teacher.email
            )
            UNION
            (
                SELECT DISTINCT '' as classID, gibbonPerson.gibbonPersonID, surname, preferredName, email, image_240, 'Head of Year' as type, 1 as listOrder
                FROM gibbonPerson
                JOIN gibbonYearGroup ON (gibbonYearGroup.gibbonPersonIDHOY=gibbonPersonID)
                WHERE status='Full' AND gibbonYearGroupID=:gibbonYearGroupID
            )
            UNION
            (
                SELECT DISTINCT '' as classID, gibbonPerson.gibbonPersonID, surname, preferredName, email, image_240, 'IN Assistant' as type, 2 as listOrder
                FROM gibbonPerson
                    JOIN gibbonINAssistant ON (gibbonINAssistant.gibbonPersonIDAssistant=gibbonPerson.gibbonPersonID)
                WHERE status='Full'
                    AND gibbonPersonIDStudent=:gibbonPersonID
            )
            UNION
            (
                SELECT DISTINCT '' as classID, gibbonPerson.gibbonPersonID, surname, preferredName, email, image_240,  'Educational Assistant' as type, 2 as listOrder
                FROM gibbonPerson
                JOIN gibbonRollGroup ON (gibbonRollGroup.gibbonPersonIDEA=gibbonPerson.gibbonPersonID OR gibbonRollGroup.gibbonPersonIDEA2=gibbonPerson.gibbonPersonID OR gibbonRollGroup.gibbonPersonIDEA3=gibbonPerson.gibbonPersonID)
                JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID)
                JOIN gibbonSchoolYear ON (gibbonStudentEnrolment.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID)
                WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID
                AND gibbonStudentEnrolment.gibbonPersonID=:gibbonPersonID
                AND gibbonPerson.status='Full'
            )
            UNION
            (
                SELECT DISTINCT '' as classID, gibbonPerson.gibbonPersonID, surname, preferredName, email, image_240, 'Form Tutor' as type, 0 as listOrder
                FROM gibbonRollGroup
                JOIN gibbonPerson ON (gibbonRollGroup.gibbonPersonIDTutor=gibbonPerson.gibbonPersonID OR gibbonRollGroup.gibbonPersonIDTutor2=gibbonPerson.gibbonPersonID OR gibbonRollGroup.gibbonPersonIDTutor3=gibbonPerson.gibbonPersonID)
                WHERE gibbonRollGroupID=:gibbonRollGroupID AND gibbonPerson.status='Full'
            )
            ORDER BY listOrder, preferredName, surname, email";

        return $this->db()->select($sql, $data);
    }

    public function queryStudentHistoryByPerson(QueryCriteria $criteria, $gibbonPersonID)
    {
        //Students from timetable classes
        $query = $this
            ->newQuery()
            ->distinct()
            ->from('gibbonPerson')
            ->cols([
                'gibbonPerson.gibbonPersonID', 'gibbonPerson.preferredName', 'gibbonPerson.surname', 'gibbonPerson.image_240',  'gibbonPerson.dob'
            ])
            ->innerJoin('gibbonCourseClassPerson AS student', 'student.gibbonPersonID=gibbonPerson.gibbonPersonID AND student.role LIKE \'Student%\'')
            ->innerJoin('gibbonCourseClassPerson AS teacher', 'teacher.gibbonCourseClassID=student.gibbonCourseClassID AND teacher.role LIKE \'Teacher%\'')
            ->innerJoin('gibbonCourseClass', 'student.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID')
            ->innerJoin('gibbonCourse', 'gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID')
            ->where("(gibbonPerson.image_240 <> '' AND gibbonPerson.image_240 IS NOT NULL)")
            ->where('teacher.gibbonPersonID=:gibbonPersonID')
            ->bindValue('gibbonPersonID', $gibbonPersonID);

        //Students from form/roll groups
        $this->unionWithCriteria($query, $criteria)
            ->distinct()
            ->from('gibbonPerson')
            ->cols([
                'gibbonPerson.gibbonPersonID', 'gibbonPerson.preferredName', 'gibbonPerson.surname', 'gibbonPerson.image_240',  'gibbonPerson.dob'
            ])
            ->innerJoin('gibbonStudentEnrolment AS student', 'student.gibbonPersonID=gibbonPerson.gibbonPersonID')
            ->innerJoin('gibbonRollGroup', 'student.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID')
            ->where("(gibbonPerson.image_240 <> '' AND gibbonPerson.image_240 IS NOT NULL)")
            ->where('(gibbonPersonIDTutor=:gibbonPersonID OR gibbonPersonIDTutor2=:gibbonPersonID OR gibbonPersonIDTutor3=:gibbonPersonID)')
            ->bindValue('gibbonPersonID', $gibbonPersonID);

        return $this->runQuery($query, $criteria);
    }
}
