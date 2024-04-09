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

    private static $searchableColumns = ['gibbonPerson.preferredName', 'gibbonPerson.firstName', 'gibbonPerson.surname', 'gibbonPerson.username', 'gibbonPerson.email', 'gibbonPerson.emailAlternate', 'gibbonPerson.studentID', 'gibbonPerson.phone1', 'gibbonPerson.vehicleRegistration'];

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
                'gibbonPerson.gibbonPersonID', 'gibbonStudentEnrolmentID', 'gibbonPerson.title', 'gibbonPerson.preferredName', 'gibbonPerson.surname', 'gibbonPerson.image_240',  'gibbonYearGroup.gibbonYearGroupID', 'gibbonYearGroup.nameShort AS yearGroup', 'gibbonFormGroup.gibbonFormGroupID', 'gibbonFormGroup.nameShort AS formGroup', 'gibbonStudentEnrolment.rollOrder', 'gibbonPerson.dateStart', 'gibbonPerson.dateEnd', 'gibbonPerson.status', "'Student' as roleCategory"
            ])
            ->leftJoin('gibbonStudentEnrolment', 'gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID AND gibbonStudentEnrolment.gibbonSchoolYearID = :gibbonSchoolYearID')
            ->leftJoin('gibbonYearGroup', 'gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID')
            ->leftJoin('gibbonFormGroup', 'gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID')
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
                'gibbonPerson.gibbonPersonID', 'gibbonStudentEnrolmentID', 'gibbonPerson.title', 'gibbonPerson.preferredName', 'gibbonPerson.surname', 'gibbonPerson.image_240', 'gibbonYearGroup.nameShort AS yearGroup', 'gibbonFormGroup.nameShort AS formGroup', 'gibbonStudentEnrolment.rollOrder', 'gibbonPerson.dateStart', 'gibbonPerson.dateEnd', 'gibbonPerson.status', "'Student' as roleCategory"
            ])
            ->innerJoin('gibbonStudentEnrolment', 'gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID')
            ->innerJoin('gibbonYearGroup', 'gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID')
            ->innerJoin('gibbonFormGroup', 'gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID')
            ->where('gibbonStudentEnrolment.gibbonSchoolYearID = :gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID);

        $criteria->addFilterRules($this->getSharedUserFilterRules());

        return $this->runQuery($query, $criteria);
    }

    public function queryStudentEnrolmentByFormGroup(QueryCriteria $criteria, $gibbonFormGroupID = null)
    {
        $query = $this
            ->newQuery()
            ->from('gibbonPerson')
            ->cols([
                'gibbonPerson.gibbonPersonID', 'gibbonStudentEnrolmentID', 'gibbonStudentEnrolment.gibbonSchoolYearID', 'gibbonPerson.title', 'gibbonPerson.preferredName', 'gibbonPerson.surname', 'gibbonPerson.image_240', 'gibbonYearGroup.nameShort AS yearGroup', 'gibbonFormGroup.nameShort AS formGroup', 'gibbonStudentEnrolment.rollOrder', 'gibbonPerson.dateStart', 'gibbonPerson.dateEnd', 'gibbonPerson.status', "'Student' as roleCategory", 'gender', 'dob', 'transport', 'lockerNumber', 'privacy',
                "GROUP_CONCAT(DISTINCT (CASE WHEN gibbonPersonalDocumentType.name IS NOT NULL THEN gibbonPersonalDocument.country END) SEPARATOR '<br/>') as citizenship"
            ])
            ->innerJoin('gibbonStudentEnrolment', 'gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID')
            ->innerJoin('gibbonYearGroup', 'gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID')
            ->innerJoin('gibbonFormGroup', 'gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID')
            ->leftJoin('gibbonPersonalDocument', "gibbonPersonalDocument.foreignTable='gibbonPerson' AND gibbonPersonalDocument.foreignTableID=gibbonPerson.gibbonPersonID AND gibbonPersonalDocument.country IS NOT NULL")
            ->leftJoin('gibbonPersonalDocumentType', "gibbonPersonalDocumentType.gibbonPersonalDocumentTypeID=gibbonPersonalDocument.gibbonPersonalDocumentTypeID AND gibbonPersonalDocumentType.document='Passport'")
            ->where("gibbonPerson.status = 'Full'")
            ->where('(gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart <= :today)')
            ->where('(gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd >= :today)')
            ->bindValue('today', date('Y-m-d'))
            ->groupBy(['gibbonPerson.gibbonPersonID']);

        if (!empty($gibbonFormGroupID)) {
            $query
                ->where('gibbonStudentEnrolment.gibbonFormGroupID = :gibbonFormGroupID')
                ->bindValue('gibbonFormGroupID', $gibbonFormGroupID);
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
                'gibbonPerson.gibbonPersonID', 'gibbonStudentEnrolmentID', 'gibbonPerson.title', 'gibbonPerson.preferredName', 'gibbonPerson.surname', 'gibbonPerson.image_240', 'gibbonYearGroup.nameShort AS yearGroup', 'gibbonFormGroup.nameShort AS formGroup', 'gibbonStudentEnrolment.rollOrder', 'gibbonPerson.dateStart', 'gibbonPerson.dateEnd', 'gibbonPerson.status', 'gibbonRole.category as roleCategory', 'gibbonStaff.type as staffType'
            ])
            ->innerJoin('gibbonRole', 'FIND_IN_SET(gibbonRole.gibbonRoleID, gibbonPerson.gibbonRoleIDAll)')
            ->leftJoin('gibbonStudentEnrolment', 'gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID AND gibbonStudentEnrolment.gibbonSchoolYearID = :gibbonSchoolYearID')
            ->leftJoin('gibbonYearGroup', 'gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID')
            ->leftJoin('gibbonFormGroup', 'gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID')
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

    public function selectUnenrolledStudentsBySchoolYear($gibbonSchoolYearID)
    {
        $data = ['gibbonSchoolYearID' => $gibbonSchoolYearID];
        $sql = "SELECT gibbonPerson.gibbonPersonID AS value, CONCAT(surname, \", \", preferredName, \" (\", username, \")\") AS name
                FROM gibbonPerson
                    JOIN gibbonRole ON (FIND_IN_SET(gibbonRole.gibbonRoleID, gibbonPerson.gibbonRoleIDAll))
                    LEFT JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID)
                    LEFT JOIN gibbonFormGroup ON (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID)
                WHERE
                    (status='Full' OR status='Expected')
                    AND gibbonFormGroup.name IS NULL
                    AND gibbonRole.category='Student'
                ORDER BY name, surname, preferredName";

        return $this->db()->select($sql, $data);
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
        $sql = "SELECT gibbonPerson.gibbonPersonID, title, surname, preferredName, image_240, gibbonYearGroup.nameShort AS yearGroup, gibbonFormGroup.nameShort AS formGroup, 'Student' as roleCategory
                FROM gibbonFamilyAdult
                JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamilyAdult.gibbonFamilyID)
                JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID)
                JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID)
                JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID)
                JOIN gibbonFormGroup ON (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID)
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

    public function selectActiveStudentByPerson($gibbonSchoolYearID, $gibbonPersonID, $onlyFull = true)
    {
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonPersonID' => $gibbonPersonID);
        $sql = "SELECT gibbonPerson.gibbonPersonID, title, surname, preferredName, email, image_240, gender, dateStart, dateEnd, gibbonStudentEnrolment.gibbonStudentEnrolmentID, gibbonStudentEnrolment.gibbonSchoolYearID, gibbonYearGroup.gibbonYearGroupID, gibbonYearGroup.nameShort AS yearGroup, gibbonYearGroup.name AS yearGroupName, gibbonFormGroup.gibbonFormGroupID, gibbonFormGroup.nameShort AS formGroup, gibbonFormGroup.name AS formGroupName, 'Student' as roleCategory, gibbonPerson.privacy, gibbonStudentEnrolment.fields
                FROM gibbonPerson
                JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID)
                JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID)
                JOIN gibbonFormGroup ON (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID)
                WHERE gibbonPerson.gibbonPersonID=:gibbonPersonID
                AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID ";

        if ($onlyFull) {
            $data['today'] = date('Y-m-d');
            $sql .= " AND gibbonPerson.status='Full'
                AND (dateStart IS NULL OR dateStart<=:today)
                AND (dateEnd IS NULL  OR dateEnd>=:today) ";
        }

        return $this->db()->select($sql, $data);
    }

    public function getStudentByUsername($gibbonSchoolYearID, $username)
    {
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'username' => $username);
        $sql = "SELECT gibbonPerson.gibbonPersonID, title, surname, preferredName, image_240, gender, gibbonStudentEnrolment.gibbonStudentEnrolmentID, gibbonStudentEnrolment.gibbonSchoolYearID, gibbonYearGroup.gibbonYearGroupID, gibbonYearGroup.nameShort AS yearGroup, gibbonFormGroup.gibbonFormGroupID, gibbonFormGroup.nameShort AS formGroup, 'Student' as roleCategory, gibbonPerson.privacy, gibbonPerson.username
                FROM gibbonPerson
                JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID)
                JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID)
                JOIN gibbonFormGroup ON (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID)
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

    public function getStudentEnrolmentCount($gibbonSchoolYearID, $date = null)
    {
        $data = ['gibbonSchoolYearID' => $gibbonSchoolYearID, 'date' => $date ?? date('Y-m-d')];
        $sql = "SELECT COUNT(gibbonPerson.gibbonPersonID)
                FROM gibbonPerson
                JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID)
                JOIN gibbonFormGroup ON (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID)
                WHERE gibbonFormGroup.gibbonSchoolYearID=:gibbonSchoolYearID ";

        if (!empty($date)) {
            $sql .= " AND ((status='Full' AND (dateStart IS NULL OR dateStart<=:date) AND (dateEnd IS NULL OR dateEnd>=:date))
                OR (status='Left' AND (dateStart IS NULL OR dateStart<=:date) AND dateEnd>=:date))";
        } else {
            $sql .= " AND status='Full'
                AND (dateStart IS NULL OR dateStart<=:date) AND (dateEnd IS NULL OR dateEnd>=:date)";
        }
        return $this->db()->selectOne($sql, $data);
    }

    public function selectAllRelatedUsersByStudent($gibbonSchoolYearID, $gibbonYearGroupID, $gibbonFormGroupID, $gibbonPersonID, $includeClassTeachers = true)
    {
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonPersonID' => $gibbonPersonID, 'gibbonYearGroupID' => $gibbonYearGroupID, 'gibbonFormGroupID' => $gibbonFormGroupID);
        $sql = "
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
                JOIN gibbonFormGroup ON (gibbonFormGroup.gibbonPersonIDEA=gibbonPerson.gibbonPersonID OR gibbonFormGroup.gibbonPersonIDEA2=gibbonPerson.gibbonPersonID OR gibbonFormGroup.gibbonPersonIDEA3=gibbonPerson.gibbonPersonID)
                JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID)
                JOIN gibbonSchoolYear ON (gibbonStudentEnrolment.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID)
                WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID
                AND gibbonStudentEnrolment.gibbonPersonID=:gibbonPersonID
                AND gibbonPerson.status='Full'
            )
            UNION
            (
                SELECT DISTINCT '' as classID, gibbonPerson.gibbonPersonID, surname, preferredName, email, image_240, 'Form Tutor' as type, 0 as listOrder
                FROM gibbonFormGroup
                JOIN gibbonPerson ON (gibbonFormGroup.gibbonPersonIDTutor=gibbonPerson.gibbonPersonID OR gibbonFormGroup.gibbonPersonIDTutor2=gibbonPerson.gibbonPersonID OR gibbonFormGroup.gibbonPersonIDTutor3=gibbonPerson.gibbonPersonID)
                WHERE gibbonFormGroupID=:gibbonFormGroupID AND gibbonPerson.status='Full'
            )";

            if ($includeClassTeachers) {
                $sql .= "UNION (
                    SELECT DISTINCT gibbonCourseClass.gibbonCourseClassID as classID, teacher.gibbonPersonID, teacher.surname, teacher.preferredName, teacher.email, teacher.image_240, gibbonCourse.name as type, 4 as listOrder
                    FROM gibbonPerson AS teacher
                    JOIN gibbonCourseClassPerson AS teacherClass ON (teacherClass.gibbonPersonID=teacher.gibbonPersonID)
                    JOIN gibbonCourseClassPerson AS studentClass ON (studentClass.gibbonCourseClassID=teacherClass.gibbonCourseClassID)
                    JOIN gibbonPerson AS student ON (studentClass.gibbonPersonID=student.gibbonPersonID)
                    JOIN gibbonCourseClass ON (studentClass.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
                    JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID)
                    WHERE teacher.status='Full' AND teacherClass.role='Teacher' AND studentClass.role='Student' AND student.gibbonPersonID=:gibbonPersonID AND gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID
                    ORDER BY teacher.preferredName, teacher.surname, teacher.email
                ) ";
            }

        $sql .= " ORDER BY listOrder, preferredName, surname, email";

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

        //Students from form groups
        $this->unionWithCriteria($query, $criteria)
            ->distinct()
            ->from('gibbonPerson')
            ->cols([
                'gibbonPerson.gibbonPersonID', 'gibbonPerson.preferredName', 'gibbonPerson.surname', 'gibbonPerson.image_240',  'gibbonPerson.dob'
            ])
            ->innerJoin('gibbonStudentEnrolment AS student', 'student.gibbonPersonID=gibbonPerson.gibbonPersonID')
            ->innerJoin('gibbonFormGroup', 'student.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID')
            ->where("(gibbonPerson.image_240 <> '' AND gibbonPerson.image_240 IS NOT NULL)")
            ->where('(gibbonPersonIDTutor=:gibbonPersonID OR gibbonPersonIDTutor2=:gibbonPersonID OR gibbonPersonIDTutor3=:gibbonPersonID)')
            ->bindValue('gibbonPersonID', $gibbonPersonID);

        return $this->runQuery($query, $criteria);
    }

    public function selectActiveStudentNames($gibbonSchoolYearID)
    {
        $data = ['gibbonSchoolYearID' => $gibbonSchoolYearID];
        $sql  = "SELECT preferredName
                FROM gibbonPerson
                JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID)
                WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID
                AND gibbonPerson.status='Full'";

        return $this->db()->select($sql, $data);
    }

    public function selectStudentEnrolmentHistory($gibbonPersonID)
    {
        $data = ['gibbonPersonID' => $gibbonPersonID];
        $sql = "SELECT gibbonFormGroup.name AS formGroup, gibbonSchoolYear.name AS schoolYear, gibbonYearGroup.nameShort as studyYear
            FROM gibbonStudentEnrolment
            JOIN gibbonFormGroup ON (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID)
            JOIN gibbonSchoolYear ON (gibbonStudentEnrolment.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID)
            JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID)
            WHERE gibbonPersonID=:gibbonPersonID
            AND (gibbonSchoolYear.status = 'Current' OR gibbonSchoolYear.status='Past')
            ORDER BY gibbonStudentEnrolment.gibbonSchoolYearID";
          
          return $this->db()->select($sql, $data);
    }
}
