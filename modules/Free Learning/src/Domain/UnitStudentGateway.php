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

use Gibbon\Services\Format;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;
use Gibbon\Domain\Traits\TableAware;

class UnitStudentGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'freeLearningUnitStudent';
    private static $primaryKey = 'freeLearningUnitStudentID';
    private static $searchableColumns = [];

    public function queryCurrentStudentsByUnit($criteria, $gibbonSchoolYearID, $freeLearningUnitID, $gibbonPersonID, $manageAll)
    {
        if ($manageAll) {
            $query = $this
                ->newQuery()
                ->distinct()
                ->from($this->getTableName())
                ->cols(['gibbonPerson.gibbonPersonID', 'gibbonPerson.email', 'gibbonPerson.surname', 'gibbonPerson.preferredName', 'freeLearningUnitStudent.*', 'gibbonCourse.nameShort AS course', 'gibbonCourseClass.nameShort AS class', 'mentor.surname AS mentorsurname', 'mentor.preferredName AS mentorpreferredName', 'gibbonPerson.fields', 'freeLearningUnitStudent.freeLearningUnitStudentID', "FIELD(freeLearningUnitStudent.status,'Complete - Pending','Evidence Not Yet Approved','Current','Complete - Approved','Exempt') as statusSort", 'count(DISTINCT gibbonDiscussionID) AS submissions'])
                ->innerJoin('gibbonPerson', 'freeLearningUnitStudent.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID')
                ->leftJoin('gibbonCourseClass', 'freeLearningUnitStudent.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID')
                ->leftJoin('gibbonCourse', 'gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID')
                ->leftJoin('gibbonPerson AS mentor', 'freeLearningUnitStudent.gibbonPersonIDSchoolMentor=mentor.gibbonPersonID')
                ->leftJoin('gibbonDiscussion', "gibbonDiscussion.foreignTableID=freeLearningUnitStudent.freeLearningUnitStudentID AND gibbonDiscussion.foreignTable='freeLearningUnitStudent' AND gibbonDiscussion.type='Complete - Pending'")
                ->where('freeLearningUnitStudent.freeLearningUnitID=:freeLearningUnitID')
                ->bindValue('freeLearningUnitID', $freeLearningUnitID)
                ->where('freeLearningUnitStudent.gibbonSchoolYearID=:gibbonSchoolYearID')
                ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
                ->where("gibbonPerson.status='Full'")
                ->where('(gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart<=:today)')
                ->where('(gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd>=:today)')
                ->bindValue('today', date('Y-m-d'))
                ->groupBy(['freeLearningUnitStudent.freeLearningUnitStudentID', 'gibbonDiscussion.foreignTableID']);
            }
            else {
                $query = $this
                    ->newQuery()
                    ->distinct()
                    ->from($this->getTableName())
                    ->cols(['gibbonPerson.gibbonPersonID', 'gibbonPerson.email', 'gibbonPerson.surname', 'gibbonPerson.preferredName', 'freeLearningUnitStudent.*', 'null AS course', 'null AS class', 'mentor.surname AS mentorsurname', 'mentor.preferredName AS mentorpreferredName', 'gibbonPerson.fields', 'freeLearningUnitStudent.freeLearningUnitStudentID', "FIELD(freeLearningUnitStudent.status,'Complete - Pending','Evidence Not Yet Approved','Current','Complete - Approved','Exempt') as statusSort", 'count(DISTINCT gibbonDiscussionID) AS submissions'])
                    ->innerJoin('gibbonPerson', 'freeLearningUnitStudent.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID')
                    ->innerJoin('gibbonPerson AS mentor', 'freeLearningUnitStudent.gibbonPersonIDSchoolMentor=mentor.gibbonPersonID')
                    ->leftJoin('gibbonDiscussion', "gibbonDiscussion.foreignTableID=freeLearningUnitStudent.freeLearningUnitStudentID AND gibbonDiscussion.foreignTable='freeLearningUnitStudent' AND gibbonDiscussion.type='Complete - Pending'")
                    ->where('freeLearningUnitStudent.freeLearningUnitID=:freeLearningUnitID')
                    ->bindValue('freeLearningUnitID', $freeLearningUnitID)
                    ->where('freeLearningUnitStudent.gibbonSchoolYearID=:gibbonSchoolYearID')
                    ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
                    ->where("gibbonPerson.status='Full'")
                    ->where('(gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart<=:today)')
                    ->where('(gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd>=:today)')
                    ->bindValue('today', date('Y-m-d'))
                    ->where('mentor.gibbonPersonID=:gibbonPersonID')
                    ->bindValue('gibbonPersonID', $gibbonPersonID)
                    ->groupBy(['freeLearningUnitStudent.freeLearningUnitStudentID', 'gibbonDiscussion.foreignTableID']);
                $this->unionAllWithCriteria($query, $criteria)
                    ->distinct()
                    ->from($this->getTableName())
                    ->cols(['gibbonPerson.gibbonPersonID', 'gibbonPerson.email', 'gibbonPerson.surname', 'gibbonPerson.preferredName', 'freeLearningUnitStudent.*', 'gibbonCourse.nameShort AS course', 'gibbonCourseClass.nameShort AS class', 'null AS mentorsurname', 'null AS mentorpreferredName', 'gibbonPerson.fields', 'freeLearningUnitStudent.freeLearningUnitStudentID', "FIELD(freeLearningUnitStudent.status,'Complete - Pending','Evidence Not Yet Approved','Current','Complete - Approved','Exempt') as statusSort", 'count(DISTINCT gibbonDiscussionID) AS submissions'])
                    ->innerJoin('gibbonPerson', 'freeLearningUnitStudent.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID')
                    ->innerJoin('gibbonCourseClass', 'freeLearningUnitStudent.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID')
                    ->innerJoin('gibbonCourseClassPerson', 'gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID')
                    ->innerJoin('gibbonCourse', 'gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID')
                    ->leftJoin('gibbonDiscussion', "gibbonDiscussion.foreignTableID=freeLearningUnitStudent.freeLearningUnitStudentID AND gibbonDiscussion.foreignTable='freeLearningUnitStudent' AND gibbonDiscussion.type='Complete - Pending'")
                    ->where('freeLearningUnitStudent.freeLearningUnitID=:freeLearningUnitID')
                    ->bindValue('freeLearningUnitID', $freeLearningUnitID)
                    ->where('freeLearningUnitStudent.gibbonSchoolYearID=:gibbonSchoolYearID')
                    ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
                    ->where("gibbonPerson.status='Full'")
                    ->where('(gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart<=:today)')
                    ->where('(gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd>=:today)')
                    ->bindValue('today', date('Y-m-d'))
                    ->where('gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND (gibbonCourseClassPerson.role=\'Teacher\' OR gibbonCourseClassPerson.role=\'Assistant\')')
                    ->bindValue('gibbonPersonID', $gibbonPersonID)
                    ->groupBy(['freeLearningUnitStudent.freeLearningUnitStudentID', 'gibbonDiscussion.foreignTableID']);
            }

        return $this->runQuery($query, $criteria);
    }

    public function queryUnitsByStudent(QueryCriteria $criteria, $gibbonPersonID, $gibbonSchoolYearID = null, $dateStart = null, $dateEnd = null, $includeInactive = true)
    {
        $query = $this
            ->newQuery()
            ->cols([
                'freeLearningUnit.freeLearningUnitID',
                'freeLearningUnitStudentID',
                'enrolmentMethod',
                'freeLearningUnit.name AS unit',
                "GROUP_CONCAT(DISTINCT gibbonDepartment.name SEPARATOR '<br/>') as learningArea",
                'freeLearningUnit.course AS flCourse',
                'freeLearningUnitStudent.status',
                'gibbonSchoolYear.name AS schoolYear',
                'evidenceLocation',
                'evidenceType',
                'commentStudent',
                'commentApproval',
                'gibbonCourse.nameShort AS course',
                'gibbonCourseClass.nameShort AS class',
                'timestampCompletePending',
                'timestampCompleteApproved',
                'timestampJoined',
            ])
            ->from('freeLearningUnit')
            ->innerJoin('freeLearningUnitStudent', 'freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID')
            ->innerJoin('gibbonPerson', 'freeLearningUnitStudent.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID')
            ->leftJoin('gibbonSchoolYear', 'freeLearningUnitStudent.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID')
            ->leftJoin('gibbonCourseClass', 'freeLearningUnitStudent.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID')
            ->leftJoin('gibbonCourse', 'gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID')
            ->leftJoin('gibbonDepartment', "freeLearningUnit.gibbonDepartmentIDList LIKE CONCAT('%', gibbonDepartment.gibbonDepartmentID, '%')")
            ->where('gibbonPerson.gibbonPersonID=:gibbonPersonID')
            ->bindValue('gibbonPersonID', $gibbonPersonID)
            ->where("gibbonPerson.status='Full'")
            ->where('(gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart<=:today)')
            ->where('(gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd>=:today)')
            ->bindValue('today', date('Y-m-d'))
            ->groupBy(['freeLearningUnitStudent.freeLearningUnitStudentID']);

        if (!empty($gibbonSchoolYearID)) {
            $query->where('freeLearningUnitStudent.gibbonSchoolYearID=:gibbonSchoolYearID')
                ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID);
        }

        if (!empty($dateStart)) {
            $query->where("(CASE WHEN (timestampCompleteApproved IS NOT NULL AND timestampCompleteApproved > timestampCompletePending) THEN timestampCompleteApproved WHEN timestampCompletePending IS NOT NULL THEN timestampCompletePending ELSE timestampJoined END)>=:dateStart")
                ->bindValue('dateStart', Format::dateConvert($dateStart)." 00:00:00");
        }

        if (!empty($dateEnd)) {
            $query->where("(CASE WHEN (timestampCompleteApproved IS NOT NULL AND timestampCompleteApproved > timestampCompletePending) THEN timestampCompleteApproved WHEN timestampCompletePending IS NOT NULL THEN timestampCompletePending ELSE timestampJoined END)<=:dateEnd")
                ->bindValue('dateEnd', Format::dateConvert($dateEnd)." 23:59:59");
        }

        if (!$includeInactive) {
            $query->where("active='Y'");
        }
        

        $criteria->addFilterRules([
            'department' => function ($query, $department) {
                return $query
                    ->where('gibbonDepartment.name = :department')
                    ->bindValue('department', ucwords($department));
            },

            'status' => function ($query, $status) {
                return $query
                    ->where('freeLearningUnitStudent.status = :status')
                    ->bindValue('status', ucwords($status));
            },
        ]);
        return $this->runQuery($query, $criteria);
    }

    public function queryEvidencePending(QueryCriteria $criteria, $gibbonSchoolYearID, $gibbonPersonID = null, $gibbonCourseClassID = null)
    {
        $query = $this
            ->newQuery()
            ->cols(['enrolmentMethod', 'freeLearningUnit.name AS unit', 'freeLearningUnit.freeLearningUnitID', "GROUP_CONCAT(DISTINCT gibbonDepartment.name SEPARATOR '<br/>') as learningArea", 'freeLearningUnit.course AS flCourse', 'gibbonPerson.gibbonPersonID', 'gibbonPerson.surname AS studentsurname', 'gibbonPerson.preferredName AS studentpreferredName', 'freeLearningUnitStudent.*', 'gibbonCourse.nameShort AS course', 'gibbonCourseClass.nameShort AS class', 'gibbonRole.category', "GROUP_CONCAT(DISTINCT CONCAT(teacherPerson.preferredName, ' ', teacherPerson.surname) ORDER BY teacherPerson.preferredName, teacherPerson.surname SEPARATOR '<br/>') AS teacherNames", 'NULL AS mentorsurname', 'NULL AS mentorpreferredName', 'gibbonPerson.fields', 'count(DISTINCT gibbonDiscussionID) AS submissions'])
            ->from('freeLearningUnit')
            ->innerJoin('freeLearningUnitStudent', 'freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID')
            ->innerJoin('gibbonPerson', 'freeLearningUnitStudent.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID')
            ->innerJoin('gibbonRole', 'gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID')
            ->leftJoin('gibbonCourseClass', 'freeLearningUnitStudent.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID')
            ->leftJoin('gibbonCourse', 'gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID')
            ->leftJoin('gibbonCourseClassPerson', 'gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID')
            ->leftJoin('gibbonCourseClassPerson AS teacher', 'teacher.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID AND teacher.role=\'Teacher\'')
            ->leftJoin('gibbonPerson AS teacherPerson', 'teacher.gibbonPersonID=teacherPerson.gibbonPersonID AND teacherPerson.status=\'Full\'')
            ->leftJoin('gibbonDepartment', "freeLearningUnit.gibbonDepartmentIDList LIKE CONCAT('%', gibbonDepartment.gibbonDepartmentID, '%')")
            ->leftJoin('gibbonDiscussion', "gibbonDiscussion.foreignTableID=freeLearningUnitStudent.freeLearningUnitStudentID AND gibbonDiscussion.foreignTable='freeLearningUnitStudent' AND gibbonDiscussion.type='Complete - Pending'")
            ->where('(gibbonCourseClassPerson.role=\'Teacher\' OR gibbonCourseClassPerson.role=\'Assistant\') AND gibbonPerson.status=\'Full\' AND freeLearningUnitStudent.status=\'Complete - Pending\' AND (gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart<=:date) AND (gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd>=:date) AND freeLearningUnitStudent.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->bindValue('date', date("Y-m-d"))
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->groupBy(['freeLearningUnitStudent.freeLearningUnitStudentID', 'gibbonDiscussion.foreignTableID']);

        if (!empty($gibbonCourseClassID)) {
            $query->where('freeLearningUnitStudent.gibbonCourseClassID=:gibbonCourseClassID')
                ->bindValue('gibbonCourseClassID', $gibbonCourseClassID);
        }

        if (!is_null($gibbonPersonID)) {
            $query->where('gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID')
                ->bindValue('gibbonPersonID', $gibbonPersonID);
        }

        $this->unionWithCriteria($query, $criteria)
            ->cols(['enrolmentMethod', 'freeLearningUnit.name AS unit', 'freeLearningUnit.freeLearningUnitID', "GROUP_CONCAT(DISTINCT gibbonDepartment.name SEPARATOR '<br/>') as learningArea", 'freeLearningUnit.course AS flCourse', 'gibbonPerson.gibbonPersonID', 'gibbonPerson.surname AS studentsurname', 'gibbonPerson.preferredName AS studentpreferredName', 'freeLearningUnitStudent.*', 'null AS course', 'null AS class', 'gibbonRole.category', 'NULL AS teacherNames', 'mentor.surname AS mentorsurname', 'mentor.preferredName AS mentorpreferredName', 'gibbonPerson.fields', 'count(DISTINCT gibbonDiscussionID) AS submissions'])
            ->from('freeLearningUnit')
            ->innerJoin('freeLearningUnitStudent', 'freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID')
            ->innerJoin('gibbonPerson', 'freeLearningUnitStudent.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID')
            ->innerJoin('gibbonRole', 'gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID')
            ->innerJoin('gibbonPerson AS mentor', 'freeLearningUnitStudent.gibbonPersonIDSchoolMentor=mentor.gibbonPersonID')
            ->leftJoin('gibbonDepartment', "freeLearningUnit.gibbonDepartmentIDList LIKE CONCAT('%', gibbonDepartment.gibbonDepartmentID, '%')")
            ->leftJoin('gibbonDiscussion', "gibbonDiscussion.foreignTableID=freeLearningUnitStudent.freeLearningUnitStudentID AND gibbonDiscussion.foreignTable='freeLearningUnitStudent' AND gibbonDiscussion.type='Complete - Pending'")
            ->where('gibbonPerson.status=\'Full\' AND freeLearningUnitStudent.status=\'Complete - Pending\'  AND (gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart<=:date) AND (gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd>=:date) AND freeLearningUnitStudent.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->bindValue('date', date("Y-m-d"))
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->groupBy(['freeLearningUnitStudent.freeLearningUnitStudentID', 'gibbonDiscussion.foreignTableID']);

        if (!is_null($gibbonPersonID)) {
            $query->where('freeLearningUnitStudent.gibbonPersonIDSchoolMentor=:gibbonPersonID')
                ->bindValue('gibbonPersonID', $gibbonPersonID);
        }

        return $this->runQuery($query, $criteria);
    }

    public function queryMentorship(QueryCriteria $criteria, $gibbonSchoolYearID, $gibbonPersonID = null, $allStudents = false, $dateStart = null, $dateEnd = null)
    {
        $query = $this
            ->newQuery()
            ->cols(['enrolmentMethod', 'freeLearningUnit.name AS unit', 'freeLearningUnit.freeLearningUnitID', "GROUP_CONCAT(DISTINCT gibbonDepartment.name SEPARATOR '<br/>') as learningArea", 'freeLearningUnit.course AS flCourse', 'gibbonPerson.gibbonPersonID', 'gibbonPerson.surname AS studentsurname', 'gibbonPerson.preferredName AS studentpreferredName', 'freeLearningUnitStudent.*', 'gibbonRole.category', 'mentor.surname AS mentorsurname', 'mentor.preferredName AS mentorpreferredName', 'teacher.surname AS teachersurname', 'teacher.preferredName AS teacherpreferredName', 'teacher.gibbonPersonID AS teachergibbonPersonID', 'gibbonPerson.fields', "(CASE WHEN freeLearningUnitStudent.status='Current - Pending' THEN 1 ELSE 0 END) as statusSort", "(CASE WHEN (timestampCompleteApproved IS NOT NULL AND timestampCompleteApproved > timestampCompletePending) THEN timestampCompleteApproved WHEN timestampCompletePending IS NOT NULL THEN timestampCompletePending ELSE timestampJoined END) as timestamp", "ROUND(AVG(((UNIX_TIMESTAMP(timestampCompleteApproved)-UNIX_TIMESTAMP(timestampCompletePending))/(60*60*24))), 1) AS waitInDays", 'count(DISTINCT gibbonDiscussionID) AS submissions'])
            ->from('freeLearningUnit')
            ->innerJoin('freeLearningUnitStudent', 'freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID')
            ->innerJoin('gibbonPerson', 'freeLearningUnitStudent.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID')
            ->innerJoin('gibbonRole', 'gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID')
            ->leftJoin('gibbonPerson AS mentor', 'freeLearningUnitStudent.gibbonPersonIDSchoolMentor=mentor.gibbonPersonID')
            ->leftJoin('gibbonCourseClass', 'freeLearningUnitStudent.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID')
            ->leftJoin('gibbonCourseClassPerson', 'role=\'teacher\' AND gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID')
            ->leftJoin('gibbonPerson AS teacher', 'gibbonCourseClassPerson.gibbonPersonID=teacher.gibbonPersonID')
            ->leftJoin('gibbonDepartment', "freeLearningUnit.gibbonDepartmentIDList LIKE CONCAT('%', gibbonDepartment.gibbonDepartmentID, '%')")
            ->leftJoin('gibbonDiscussion', "gibbonDiscussion.foreignTableID=freeLearningUnitStudent.freeLearningUnitStudentID AND gibbonDiscussion.foreignTable='freeLearningUnitStudent' AND gibbonDiscussion.type='Complete - Pending'")
            ->where('freeLearningUnitStudent.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->where("(gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart<=:date) AND (gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd>=:date)")
            ->bindValue('date', date("Y-m-d"))
            ->groupBy(['freeLearningUnitStudent.freeLearningUnitStudentID']);

        if (!is_null($gibbonPersonID)) {
            $query->where("((enrolmentMethod='schoolMentor' AND mentor.gibbonPersonID=:gibbonPersonID AND freeLearningUnitStudent.gibbonSchoolYearID=:gibbonSchoolYearID) OR (enrolmentMethod='class' AND teacher.gibbonPersonID=:gibbonPersonID AND freeLearningUnitStudent.gibbonSchoolYearID=:gibbonSchoolYearID))")
                ->bindValue('gibbonPersonID', $gibbonPersonID);
        }

        if ($allStudents != "on") {
            $query->where("gibbonPerson.status='Full'");
        }

        if (!empty($dateStart)) {
            $query->where("(CASE WHEN (timestampCompleteApproved IS NOT NULL AND timestampCompleteApproved > timestampCompletePending) THEN timestampCompleteApproved WHEN timestampCompletePending IS NOT NULL THEN timestampCompletePending ELSE timestampJoined END)>=:dateStart")
                ->bindValue('dateStart', Format::dateConvert($dateStart)." 00:00:00");
        }

        if (!empty($dateEnd)) {
            $query->where("(CASE WHEN (timestampCompleteApproved IS NOT NULL AND timestampCompleteApproved > timestampCompletePending) THEN timestampCompleteApproved WHEN timestampCompletePending IS NOT NULL THEN timestampCompletePending ELSE timestampJoined END)<=:dateEnd")
                ->bindValue('dateEnd', Format::dateConvert($dateEnd)." 23:59:59");
        }

        $criteria->addFilterRules([
            'status' => function ($query, $status) {
                return $query
                    ->where('freeLearningUnitStudent.status = :status')
                    ->bindValue('status', ucwords($status));
            },
        ]);

        return $this->runQuery($query, $criteria);
    }

    public function queryStudentProgressByStudent(QueryCriteria $criteria, $gibbonCourseClassID, $gibbonSchoolYearID, $gibbonDepartmentID)
    {
        $query = $this
            ->newSelect();
            if (empty($gibbonDepartmentID)) {
                $query->cols(['gibbonPerson.gibbonPersonID',
                    'surname',
                    'preferredName',
                    'SUM(CASE WHEN freeLearningUnitStudent.status = \'Complete - Approved\' THEN 1 ELSE 0 END) AS completeApprovedCount',
                    'SUM(CASE WHEN freeLearningUnitStudent.status = \'Complete - Pending\' THEN 1 ELSE 0 END) AS completePendingCount',
                    'SUM(CASE WHEN freeLearningUnitStudent.status = \'Current\' THEN 1 ELSE 0 END) AS currentCount',
                    'SUM(CASE WHEN freeLearningUnitStudent.status = \'Current - Pending\' THEN 1 ELSE 0 END) AS currentPendingCount',
                    'SUM(CASE WHEN freeLearningUnitStudent.status = \'Evidence Not Yet Approved\' THEN 1 ELSE 0 END) AS evidenceNotYetApprovedCount',
                    'SUM(CASE WHEN freeLearningUnitStudent.status = \'Exempt\' THEN 1 ELSE 0 END) AS exemptCount',
                    'SUM(CASE WHEN freeLearningUnitStudent.status = \'Complete - Approved\' THEN 1 ELSE 0 END)+SUM(CASE WHEN freeLearningUnitStudent.status = \'Complete - Pending\' THEN 1 ELSE 0 END)+SUM(CASE WHEN freeLearningUnitStudent.status = \'Current\' THEN 1 ELSE 0 END)+SUM(CASE WHEN freeLearningUnitStudent.status = \'Current - Pending\' THEN 1 ELSE 0 END)+SUM(CASE WHEN freeLearningUnitStudent.status = \'Evidence Not Yet Approved\' THEN 1 ELSE 0 END)+SUM(CASE WHEN freeLearningUnitStudent.status = \'Exempt\' THEN 1 ELSE 0 END) as totalCount',
                ]);
            } else {
                $query->cols(['gibbonPerson.gibbonPersonID',
                    'surname',
                    'preferredName',
                    'SUM(CASE WHEN FIND_IN_SET(:gibbonDepartmentID, freeLearningUnit.gibbonDepartmentIDList) AND freeLearningUnitStudent.status = \'Complete - Approved\' THEN 1 ELSE 0 END) AS completeApprovedCount',
                    'SUM(CASE WHEN FIND_IN_SET(:gibbonDepartmentID, freeLearningUnit.gibbonDepartmentIDList) AND freeLearningUnitStudent.status = \'Complete - Pending\' THEN 1 ELSE 0 END) AS completePendingCount',
                    'SUM(CASE WHEN FIND_IN_SET(:gibbonDepartmentID, freeLearningUnit.gibbonDepartmentIDList) AND freeLearningUnitStudent.status = \'Current\' THEN 1 ELSE 0 END) AS currentCount',
                    'SUM(CASE WHEN FIND_IN_SET(:gibbonDepartmentID, freeLearningUnit.gibbonDepartmentIDList) AND freeLearningUnitStudent.status = \'Current - Pending\' THEN 1 ELSE 0 END) AS currentPendingCount',
                    'SUM(CASE WHEN FIND_IN_SET(:gibbonDepartmentID, freeLearningUnit.gibbonDepartmentIDList) AND freeLearningUnitStudent.status = \'Evidence Not Yet Approved\' THEN 1 ELSE 0 END) AS evidenceNotYetApprovedCount',
                    'SUM(CASE WHEN FIND_IN_SET(:gibbonDepartmentID, freeLearningUnit.gibbonDepartmentIDList) AND freeLearningUnitStudent.status = \'Exempt\' THEN 1 ELSE 0 END) AS exemptCount',
                    'SUM(CASE WHEN FIND_IN_SET(:gibbonDepartmentID, freeLearningUnit.gibbonDepartmentIDList) AND freeLearningUnitStudent.status = \'Complete - Approved\' THEN 1 ELSE 0 END)+SUM(CASE WHEN FIND_IN_SET(:gibbonDepartmentID, freeLearningUnit.gibbonDepartmentIDList) AND freeLearningUnitStudent.status = \'Complete - Pending\' THEN 1 ELSE 0 END)+SUM(CASE WHEN FIND_IN_SET(:gibbonDepartmentID, freeLearningUnit.gibbonDepartmentIDList) AND freeLearningUnitStudent.status = \'Current\' THEN 1 ELSE 0 END)+SUM(CASE WHEN FIND_IN_SET(:gibbonDepartmentID, freeLearningUnit.gibbonDepartmentIDList) AND freeLearningUnitStudent.status = \'Current - Pending\' THEN 1 ELSE 0 END)+SUM(CASE WHEN FIND_IN_SET(:gibbonDepartmentID, freeLearningUnit.gibbonDepartmentIDList) AND freeLearningUnitStudent.status = \'Evidence Not Yet Approved\' THEN 1 ELSE 0 END)+SUM(CASE WHEN FIND_IN_SET(:gibbonDepartmentID, freeLearningUnit.gibbonDepartmentIDList) AND freeLearningUnitStudent.status = \'Exempt\' THEN 1 ELSE 0 END) as totalCount'
                ])
                    ->bindValue('gibbonDepartmentID', $gibbonDepartmentID);
            }
        $query->from('gibbonPerson')
            ->innerJoin('gibbonCourseClassPerson', 'gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID AND role=\'Student\'')
            ->leftJoin('freeLearningUnitStudent', 'freeLearningUnitStudent.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID AND (freeLearningUnitStudent.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID OR enrolmentMethod=\'schoolMentor\' OR enrolmentMethod=\'externalMentor\') AND freeLearningUnitStudent.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->leftJoin('freeLearningUnit', 'freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID')
            ->where('gibbonCourseClassPerson.gibbonCourseClassID=:gibbonCourseClassID')
            ->where("gibbonPerson.status='Full'")
            ->where('(gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart<=:today) AND (gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd>=:today)')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->bindValue('gibbonCourseClassID', $gibbonCourseClassID)
            ->bindValue('today', date('Y-m-d'))
            ->groupBy(['gibbonPerson.gibbonPersonID']);

        return $this->runQuery($query, $criteria);
    }

    public function selectEnrolmentPending($gibbonSchoolYearID, $gibbonPersonID = null, $mentorshipAcceptancePrompt = 31)
    {
        $query = $this
            ->newSelect()
            ->cols(['freeLearningUnitStudent.gibbonPersonIDSchoolMentor', 'COUNT(DISTINCT freeLearningUnitStudentID) AS count'])
            ->from('freeLearningUnit')
            ->innerJoin('freeLearningUnitStudent', 'freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID')
            ->innerJoin('gibbonPerson', 'freeLearningUnitStudent.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID')
            ->where("freeLearningUnitStudent.gibbonSchoolYearID=:gibbonSchoolYearID")
            ->where("freeLearningUnitStudent.enrolmentMethod='schoolMentor'")
            ->where("freeLearningUnitStudent.status='Current - Pending'")
            ->where("gibbonPerson.status='Full'")
            ->where('(gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart<=:today) AND (gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd>=:today)')
            ->where('freeLearningUnitStudent.timestampJoined < DATE_SUB(:today, INTERVAL :mentorshipAcceptancePrompt DAY)')
            ->bindValue('today', date('Y-m-d'))
            ->bindvalue('mentorshipAcceptancePrompt', $mentorshipAcceptancePrompt)
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->groupBy(['freeLearningUnitStudent.gibbonPersonIDSchoolMentor']);

        if (!is_null($gibbonPersonID)) {
            $query->where('freeLearningUnitStudent.gibbonPersonIDSchoolMentor=:gibbonPersonID')
                ->bindValue('gibbonPersonID', $gibbonPersonID);
        }

        return $this->runSelect($query);
    }

    public function selectEvidencePending($gibbonSchoolYearID, $gibbonPersonID = null, $evidenceOutstandingPrompt = 31)
    {
        $query = $this
            ->newSelect()
            ->cols(['freeLearningUnitStudent.gibbonPersonIDSchoolMentor', 'COUNT(DISTINCT freeLearningUnitStudentID) AS count'])
            ->from('freeLearningUnit')
            ->innerJoin('freeLearningUnitStudent', 'freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID')
            ->innerJoin('gibbonPerson', 'freeLearningUnitStudent.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID')
            ->where("freeLearningUnitStudent.gibbonSchoolYearID=:gibbonSchoolYearID")
            ->where("freeLearningUnitStudent.enrolmentMethod='schoolMentor'")
            ->where("freeLearningUnitStudent.status='Complete - Pending'")
            ->where("gibbonPerson.status='Full'")
            ->where('(gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart<=:today) AND (gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd>=:today)')
            ->where('freeLearningUnitStudent.timestampCompletePending < DATE_SUB(:today, INTERVAL :evidenceOutstandingPrompt DAY)')
            ->bindValue('today', date('Y-m-d'))
            ->bindvalue('evidenceOutstandingPrompt', $evidenceOutstandingPrompt)
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->groupBy(['freeLearningUnitStudent.gibbonPersonIDSchoolMentor']);

        if (!is_null($gibbonPersonID)) {
            $query->where('freeLearningUnitStudent.gibbonPersonIDSchoolMentor=:gibbonPersonID')
                ->bindValue('gibbonPersonID', $gibbonPersonID);
        }

        return $this->runSelect($query);
    }

    public function selectEvidenceNotSubmitted($gibbonSchoolYearID, $gibbonPersonID = null, $studentEvidencePrompt = 31)
    {
        $query = $this
            ->newSelect()
            ->cols(['freeLearningUnitStudent.gibbonPersonIDStudent', "freeLearningUnit.name"])
            ->from('freeLearningUnit')
            ->innerJoin('freeLearningUnitStudent', 'freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID')
            ->innerJoin('gibbonPerson', 'freeLearningUnitStudent.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID')
            ->where("freeLearningUnitStudent.gibbonSchoolYearID=:gibbonSchoolYearID")
            ->where("gibbonPerson.status='Full'")
            ->where('(gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart<=:today) AND (gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd>=:today)')
            ->where("(
                (freeLearningUnitStudent.status='Current' AND freeLearningUnitStudent.timestampJoined < DATE_SUB(:today, INTERVAL :studentEvidencePrompt DAY))
                OR (freeLearningUnitStudent.status='Evidence Not Yet Approved' AND freeLearningUnitStudent.timestampCompletePending < DATE_SUB(:today, INTERVAL :studentEvidencePrompt DAY))
                )
            ")
            ->bindValue('today', date('Y-m-d'))
            ->bindValue('studentEvidencePrompt', $studentEvidencePrompt)
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID);

        if (!is_null($gibbonPersonID)) {
            $query->where('freeLearningUnitStudent.gibbonPersonIDStudent=:gibbonPersonID')
                ->bindValue('gibbonPersonID', $gibbonPersonID);
        }

        return $this->runSelect($query);
    }

    public function getUnitStudentDetailsByID($freeLearningUnitID, $gibbonPersonID = null, $freeLearningUnitStudentID = null)
    {
        $query = $this
            ->newSelect()
            ->cols(['freeLearningUnit.*','freeLearningUnitStudent.*', 'gibbonPerson.gibbonPersonID', 'gibbonPerson.title', 'gibbonPerson.surname', 'gibbonPerson.preferredName', 'gibbonPerson.gender', 'gibbonPerson.dateStart', 'gibbonPerson.image_240', '(SELECT count(*) FROM gibbonINPersonDescriptor WHERE gibbonINPersonDescriptor.gibbonPersonID=freeLearningUnitStudent.gibbonPersonIDStudent GROUP BY gibbonINPersonDescriptor.gibbonPersonID) AS inCount'])
            ->from('freeLearningUnitStudent')
            ->innerJoin('freeLearningUnit', 'freeLearningUnit.freeLearningUnitID=freeLearningUnitStudent.freeLearningUnitID')
            ->innerJoin('gibbonPerson', 'freeLearningUnitStudent.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID')
            ->where('freeLearningUnitStudent.freeLearningUnitID = :freeLearningUnitID')
            ->bindValue('freeLearningUnitID', $freeLearningUnitID);

        if (!empty($gibbonPersonID)) {
            $query->where('freeLearningUnitStudent.gibbonPersonIDStudent = :gibbonPersonID')
                ->bindValue('gibbonPersonID', $gibbonPersonID);
        }

        if (!empty($freeLearningUnitStudentID)) {
            $query->where('freeLearningUnitStudent.freeLearningUnitStudentID = :freeLearningUnitStudentID')
                ->bindValue('freeLearningUnitStudentID', $freeLearningUnitStudentID);
        }

        return $this->runSelect($query)->fetch();
    }

    public function selectUnitStudentDiscussion($freeLearningUnitStudentID)
    {
        $query = $this
            ->newSelect()
            ->cols(['gibbonDiscussionID', 'gibbonDiscussion.comment', 'gibbonDiscussion.type', 'gibbonDiscussion.tag', 'gibbonDiscussion.attachmentType', 'gibbonDiscussion.attachmentLocation', 'gibbonPerson.gibbonPersonID', 'gibbonPerson.title', 'gibbonPerson.surname', 'gibbonPerson.preferredName', 'gibbonPerson.image_240', 'gibbonPerson.username', 'gibbonPerson.email', 'gibbonRole.category', 'gibbonDiscussion.timestamp'])
            ->from('gibbonDiscussion')
            ->innerJoin('gibbonPerson', 'gibbonDiscussion.gibbonPersonID=gibbonPerson.gibbonPersonID')
            ->innerJoin('gibbonRole', 'gibbonRole.gibbonRoleID=gibbonPerson.gibbonRoleIDPrimary')
            ->where('gibbonDiscussion.foreignTable = :foreignTable')
            ->bindValue('foreignTable', 'freeLearningUnitStudent')
            ->where('gibbonDiscussion.foreignTableID = :foreignTableID')
            ->bindValue('foreignTableID', $freeLearningUnitStudentID);

        $query->union()
            ->cols(['null AS gibbonDiscussionID', 'freeLearningUnitStudent.commentApproval as comment', 'freeLearningUnitStudent.status as type', "(CASE WHEN freeLearningUnitStudent.status = 'Complete - Pending' THEN 'pending' WHEN freeLearningUnitStudent.status = 'Evidence Not Yet Approved' THEN 'warning' WHEN freeLearningUnitStudent.status = 'Complete - Approved' THEN 'success' ELSE 'dull' END) as tag", 'freeLearningUnitStudent.evidenceType as attachmentType', 'freeLearningUnitStudent.evidenceLocation as attachmentLocation', 'freeLearningUnitStudent.gibbonPersonIDStudent as gibbonPersonID', "'' as title", 'nameExternalMentor as surname', "'' as preferredName", '"" as image_240', '"" as email', "'Staff' as category", '"" as username', 'timestampCompleteApproved as timestamp'])
            ->from('freeLearningUnitStudent')
            ->where('freeLearningUnitStudent.freeLearningUnitStudentID = :freeLearningUnitStudentID')
            ->bindValue('freeLearningUnitStudentID', $freeLearningUnitStudentID)
            ->where("freeLearningUnitStudent.enrolmentMethod = 'externalMentor'")
            ->where('gibbonPersonIDApproval IS NULL')
            ->where('commentApproval IS NOT NULL')

        ->orderBy(['timestamp']);

        $result = $this->runSelect($query);

        if ($result->rowCount() == 0) {
            $query = $this
                ->newSelect()
                ->cols(['freeLearningUnitStudent.commentStudent as comment', "'Complete - Pending' as type", "'pending' as tag", 'gibbonPerson.gibbonPersonID', 'gibbonPerson.title', 'gibbonPerson.surname', 'gibbonPerson.preferredName', 'gibbonPerson.image_240',  "'Student' as category",  'timestampCompletePending as timestamp'])
                ->from('freeLearningUnitStudent')
                ->innerJoin('gibbonPerson', 'freeLearningUnitStudent.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID')
                ->where('freeLearningUnitStudent.freeLearningUnitStudentID = :freeLearningUnitStudentID')
                ->bindValue('freeLearningUnitStudentID', $freeLearningUnitStudentID)
                ->where('commentStudent IS NOT NULL');

            $query->union()
                ->cols(['freeLearningUnitStudent.commentApproval as comment', 'freeLearningUnitStudent.status as type', "(CASE WHEN freeLearningUnitStudent.status = 'Complete - Pending' THEN 'pending' WHEN freeLearningUnitStudent.status = 'Evidence Not Yet Approved' THEN 'warning' WHEN freeLearningUnitStudent.status = 'Complete - Approved' THEN 'success' ELSE 'dull' END) as tag", 'gibbonPerson.gibbonPersonID', 'gibbonPerson.title', 'gibbonPerson.surname', 'gibbonPerson.preferredName', 'gibbonPerson.image_240',  "'Staff' as category",  'timestampCompleteApproved as timestamp'])
                ->from('freeLearningUnitStudent')
                ->innerJoin('gibbonPerson', 'freeLearningUnitStudent.gibbonPersonIDApproval=gibbonPerson.gibbonPersonID')
                ->where('freeLearningUnitStudent.freeLearningUnitStudentID = :freeLearningUnitStudentID')
                ->bindValue('freeLearningUnitStudentID', $freeLearningUnitStudentID)
                ->where('commentApproval IS NOT NULL');

            $result = $this->runSelect($query);
        }

        return $result;
    }

    public function selectLearningAreasByStudent($gibbonPersonID)
    {
        $data = ['gibbonPersonID' => $gibbonPersonID];
        $sql = "SELECT DISTINCT gibbonDepartment.gibbonDepartmentID as value, gibbonDepartment.name
                FROM freeLearningUnit
                JOIN freeLearningUnitStudent ON (freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID)
                JOIN gibbonDepartment ON (FIND_IN_SET(gibbonDepartment.gibbonDepartmentID, freeLearningUnit.gibbonDepartmentIDList))
                WHERE freeLearningUnitStudent.gibbonPersonIDStudent = :gibbonPersonID
                AND gibbonDepartment.type='Learning Area'
                GROUP BY gibbonDepartment.gibbonDepartmentID
                ORDER BY gibbonDepartment.name";

        return $this->db()->select($sql, $data);
    }

    public function selectCoursesByStudent($gibbonPersonIDStudent, $gibbonSchoolYearID)
    {
        $query = $this
            ->newSelect()
            ->distinct()
            ->cols(['course as value', 'course as name', "'".__m('Enrolled Course')."' as groupBy", "(SELECT COUNT(*) FROM freeLearningUnit as unit WHERE unit.course=freeLearningUnit.course AND unit.active='Y') as total"])
            ->from('freeLearningUnit')
            ->innerJoin('freeLearningUnitStudent', 'freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID')
            ->where("freeLearningUnit.active='Y'")
            ->where('NOT course IS NULL')
            ->where('NOT course=\'\'')
            ->where('freeLearningUnitStudent.gibbonPersonIDStudent=:gibbonPersonIDStudent')
            ->bindValue('gibbonPersonIDStudent', $gibbonPersonIDStudent)
            ->orderBy(['freeLearningUnit.course']);

        if (!empty($gibbonSchoolYearID)) {
            $query->where('freeLearningUnitStudent.gibbonSchoolYearID=:gibbonSchoolYearID')
                ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID);
        }

        return $this->runSelect($query);
    }

    public function selectCourseEnrolmentByStudent($gibbonPersonID, $gibbonSchoolYearID = null)
    {
        $query = $this
            ->newSelect()
            ->cols(['gibbonCourse.gibbonCourseID', 'gibbonCourse.nameShort', 'gibbonCourse.name'])
            ->from('gibbonCourse')
            ->innerJoin('gibbonCourseClass', 'gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID')
            ->innerJoin('gibbonCourseClassPerson', 'gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID')
            ->innerJoin('gibbonSchoolYear', 'gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID')
            ->where('gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID')
            ->where('gibbonCourseClassPerson.role NOT LIKE "%LEFT"')
            ->bindValue('gibbonPersonID', $gibbonPersonID)
            ->orderBy(['gibbonSchoolYear.sequenceNumber', 'gibbonCourse.nameShort']);

            if (!empty($gibbonSchoolYearID)) {
                $query->where('gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID')
                    ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID);
            }

        return $this->runSelect($query);
    }

    public function selectUnitCollaboratorsByKey($collaborationKey)
    {
        $query = $this
            ->newSelect()
            ->cols(['freeLearningUnitStudent.*', 'gibbonPerson.gibbonPersonID', 'gibbonPerson.title', 'gibbonPerson.surname', 'gibbonPerson.preferredName', 'gibbonPerson.gender', 'gibbonPerson.dateStart', 'gibbonPerson.image_240', '(SELECT count(*) FROM gibbonINPersonDescriptor WHERE gibbonINPersonDescriptor.gibbonPersonID=freeLearningUnitStudent.gibbonPersonIDStudent GROUP BY gibbonINPersonDescriptor.gibbonPersonID) AS inCount'])
            ->from('freeLearningUnitStudent')
            ->innerJoin('gibbonPerson', 'freeLearningUnitStudent.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID')
            ->where('freeLearningUnitStudent.collaborationKey = :collaborationKey')
            ->where("gibbonPerson.status = 'Full'")
            ->bindValue('collaborationKey', $collaborationKey);

        return $this->runSelect($query);
    }

    public function selectUnitMentors($freeLearningUnitID, $gibbonPersonID, $params = [])
    {
        $sql = [];
        $data = [];

        if (!empty($params['disableLearningAreaMentors']) && $params['disableLearningAreaMentors'] == 'N') {
            $data = ['freeLearningUnitID' => $freeLearningUnitID, 'gibbonPersonID' => $gibbonPersonID];
            $sql[] = "(SELECT DISTINCT gibbonPerson.gibbonPersonID, gibbonPerson.title, gibbonPerson.preferredName, gibbonPerson.surname
                FROM gibbonPerson
                    JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonPersonID=gibbonPerson.gibbonPersonID)
                    JOIN freeLearningUnit ON (freeLearningUnit.gibbonDepartmentIDList LIKE concat('%',gibbonDepartmentStaff.gibbonDepartmentID,'%'))
                WHERE gibbonPerson.status='Full'
                    AND freeLearningUnitID=:freeLearningUnitID
                    AND NOT gibbonPerson.gibbonPersonID=:gibbonPersonID
                )";
        }
        if (!empty($params['schoolMentorCompletors']) && $params['schoolMentorCompletors'] == 'Y') {
            $sql[] = "(SELECT gibbonPerson.gibbonPersonID, gibbonPerson.title, gibbonPerson.preferredName, gibbonPerson.surname
                    FROM gibbonPerson
                    LEFT JOIN freeLearningUnitAuthor ON (freeLearningUnitAuthor.gibbonPersonID=gibbonPerson.gibbonPersonID AND freeLearningUnitAuthor.freeLearningUnitID=:freeLearningUnitID)
                    LEFT JOIN freeLearningUnitStudent ON (freeLearningUnitStudent.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID AND freeLearningUnitStudent.freeLearningUnitID=:freeLearningUnitID)
                    WHERE gibbonPerson.status='Full'
                        AND NOT gibbonPerson.gibbonPersonID=:gibbonPersonID
                        AND (freeLearningUnitStudent.status='Complete - Approved' OR freeLearningUnitAuthor.freeLearningUnitAuthorID IS NOT NULL)
                    GROUP BY gibbonPersonID)";
        }
        if (!empty($params['schoolMentorCustom'])) {
            $data['schoolMentorCustom'] = $params['schoolMentorCustom'];
            $sql[] = "(SELECT gibbonPerson.gibbonPersonID, gibbonPerson.title, gibbonPerson.preferredName, gibbonPerson.surname
                FROM gibbonPerson
                WHERE FIND_IN_SET(gibbonPersonID, :schoolMentorCustom)
                    AND status='Full')";
        }
        if (!empty($params['schoolMentorCustomRole'])) {
            $data['gibbonRoleID'] = $params['schoolMentorCustomRole'];
            $sql[] = "(SELECT gibbonPerson.gibbonPersonID, gibbonPerson.title, gibbonPerson.preferredName, gibbonPerson.surname
                FROM gibbonPerson
                    JOIN gibbonRole ON (FIND_IN_SET(gibbonRole.gibbonRoleID, gibbonPerson.gibbonRoleIDAll))
                WHERE gibbonRoleID=:gibbonRoleID
                    AND status='Full')";
        }

        if (count($sql) == 1) {
            return $this->db()->select($sql[0]." ORDER BY surname, preferredName", $data);
        }
        else if (count($sql) > 1) {
            return $this->db()->select(implode(" UNION DISTINCT ", $sql)." ORDER BY surname, preferredName", $data);
        }
        else {
            return false;
        }
    }

    public function selectPotentialCollaborators($gibbonSchoolYearID, $gibbonPersonID, $roleCategory, $prerequisiteCount, $params = [])
    {
        if ($roleCategory == 'Student') {
            $data = ['gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonPersonID' => $gibbonPersonID, 'gibbonYearGroupIDMinimum' => $params['gibbonYearGroupIDMinimum'], 'prerequisiteList' => $params['freeLearningUnitIDPrerequisiteList'], 'prerequisiteCount' => $prerequisiteCount, 'freeLearningUnitID' => $params['freeLearningUnitID']];
            $sql = "SELECT gibbonPerson.gibbonPersonID, preferredName, surname, gibbonFormGroup.name AS FormGroup, prerequisites.count, currentUnit.completed
            FROM gibbonPerson
            JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID)
            JOIN gibbonFormGroup ON (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID)
            LEFT JOIN (
                SELECT COUNT(*) as count, freeLearningUnitStudent.gibbonPersonIDStudent
                FROM freeLearningUnitStudent
                JOIN freeLearningUnit ON (freeLearningUnit.freeLearningUnitID=freeLearningUnitStudent.freeLearningUnitID)
                WHERE freeLearningUnit.active='Y'
                AND (:prerequisiteList = '' OR FIND_IN_SET(freeLearningUnit.freeLearningUnitID, :prerequisiteList))
                AND (freeLearningUnitStudent.status='Complete - Approved' OR freeLearningUnitStudent.status='Exempt')
                GROUP BY freeLearningUnitStudent.gibbonPersonIDStudent
            ) AS prerequisites ON (prerequisites.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID)
            LEFT JOIN (
                SELECT freeLearningUnitStudentID as completed, freeLearningUnitStudent.gibbonPersonIDStudent
                FROM freeLearningUnitStudent
                WHERE freeLearningUnitStudent.freeLearningUnitID=:freeLearningUnitID
                AND (freeLearningUnitStudent.status='Complete - Approved' OR freeLearningUnitStudent.status='Exempt')
            ) as currentUnit ON (currentUnit.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID)
            WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID
            AND status='Full' AND NOT gibbonPerson.gibbonPersonID=:gibbonPersonID
            AND (:gibbonYearGroupIDMinimum IS NULL OR gibbonStudentEnrolment.gibbonYearGroupID >= :gibbonYearGroupIDMinimum)
            HAVING (:prerequisiteCount = 0 OR prerequisites.count >= :prerequisiteCount) AND (currentUnit.completed IS NULL)
            ORDER BY surname, preferredName";
        } else if ($roleCategory == 'Staff') {
            $data = ['gibbonPersonID' => $gibbonPersonID];
            $sql = "SELECT DISTINCT gibbonPerson.gibbonPersonID, preferredName, surname
                FROM gibbonPerson
                JOIN gibbonStaff ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID)
                WHERE status='Full'
                    AND NOT gibbonPerson.gibbonPersonID=:gibbonPersonID
                ORDER BY surname, preferredName";
        } else if ($roleCategory == 'Parent') {
            $data = ['gibbonPersonID' => $gibbonPersonID];
            $sql = "SELECT DISTINCT gibbonPerson.gibbonPersonID, preferredName, surname
                FROM gibbonPerson
                JOIN gibbonRole ON (gibbonRole.gibbonRoleID LIKE concat( '%', gibbonPerson.gibbonRoleIDAll, '%' ) AND category='Parent')
                WHERE status='Full'
                    AND NOT gibbonPerson.gibbonPersonID=:gibbonPersonID
                ORDER BY surname, preferredName";
        }

        return $this->db()->select($sql, $data);
    }

    public function selectShowcase($freeLearningUnitID = null)
    {
        $query = $this
            ->newSelect()
            ->cols(['freeLearningUnit.name', 'freeLearningUnit.logo', 'freeLearningUnitStudent.*', "gibbonPerson.preferredName as students"])
            ->from('freeLearningUnitStudent')
            ->innerJoin('freeLearningUnit', 'freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID')
            ->innerJoin('gibbonPerson', 'freeLearningUnitStudent.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID')
            ->where('freeLearningUnit.active=\'Y\'')
            ->where('freeLearningUnitStudent.exemplarWork=\'Y\'')
            ->where('freeLearningUnitStudent.grouping=\'Individual\'');

        $query->union()
            ->cols(['freeLearningUnit.name', 'freeLearningUnit.logo', 'freeLearningUnitStudent.*', "GROUP_CONCAT(DISTINCT gibbonPerson.preferredName SEPARATOR ', ') as students"])
            ->from('freeLearningUnitStudent')
            ->innerJoin('freeLearningUnit', 'freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID')
            ->innerJoin('gibbonPerson', 'freeLearningUnitStudent.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID')
            ->where('freeLearningUnit.active=\'Y\'')
            ->where('freeLearningUnitStudent.exemplarWork=\'Y\'')
            ->where('NOT freeLearningUnitStudent.grouping=\'Individual\'')
            ->groupBy(['freeLearningUnit.freeLearningUnitID', 'freeLearningUnitStudent.collaborationKey'])

        ->orderBy(['timestampCompleteApproved DESC']);

        if (!empty($freeLearningUnitID)) {
            $query = $this
                ->newSelect()
                ->cols(['freeLearningUnit.name', 'freeLearningUnit.logo', 'freeLearningUnitStudent.*', "gibbonPerson.preferredName as students"])
                ->from('freeLearningUnitStudent')
                ->innerJoin('freeLearningUnit', 'freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID')
                ->innerJoin('gibbonPerson', 'freeLearningUnitStudent.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID')
                ->where('freeLearningUnit.active=\'Y\'')
                ->where('freeLearningUnitStudent.exemplarWork=\'Y\'')
                ->where('freeLearningUnitStudent.grouping=\'Individual\'')
                ->where('freeLearningUnit.freeLearningUnitID = :freeLearningUnitID')
                ->bindValue('freeLearningUnitID', $freeLearningUnitID);

            $query->union()
                ->cols(['freeLearningUnit.name', 'freeLearningUnit.logo', 'freeLearningUnitStudent.*', "GROUP_CONCAT(DISTINCT gibbonPerson.preferredName SEPARATOR ', ') as students"])
                ->from('freeLearningUnitStudent')
                ->innerJoin('freeLearningUnit', 'freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID')
                ->innerJoin('gibbonPerson', 'freeLearningUnitStudent.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID')
                ->where('freeLearningUnit.active=\'Y\'')
                ->where('freeLearningUnitStudent.exemplarWork=\'Y\'')
                ->where('NOT freeLearningUnitStudent.grouping=\'Individual\'')
                ->groupBy(['freeLearningUnit.freeLearningUnitID', 'freeLearningUnitStudent.collaborationKey'])
                ->where('freeLearningUnit.freeLearningUnitID = :freeLearningUnitID')
                ->bindValue('freeLearningUnitID', $freeLearningUnitID)

            ->orderBy(['timestampCompleteApproved DESC']);
        }

        return $this->runSelect($query)->fetchAll();
    }

    public function selectLastCompleteUnitByLearner($gibbonPersonID)
    {
        $query = $this
            ->newSelect()
            ->cols(['freeLearningUnitStudent.timestampCompleteApproved', 'freeLearningUnit.name', 'freeLearningUnit.freeLearningUnitID'])
            ->from('freeLearningUnitStudent')
            ->innerJoin('freeLearningUnit', 'freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID')
            ->where('freeLearningUnitStudent.gibbonPersonIDStudent = :gibbonPersonID')
            ->bindValue('gibbonPersonID', $gibbonPersonID)
            ->where("freeLearningUnitStudent.status='Complete - Approved'")
            ->orderBy(['timestampCompleteApproved DESC'])
            ->setPaging(1)
            ->page(1);

        return $this->runSelect($query)->fetchAll();
    }

    public function selectCurrentUnitByLearner($gibbonPersonID)
    {
        $query = $this
            ->newSelect()
            ->cols(['freeLearningUnitStudent.timestampJoined', 'freeLearningUnit.name', 'freeLearningUnit.freeLearningUnitID'])
            ->from('freeLearningUnitStudent')
            ->innerJoin('freeLearningUnit', 'freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID')
            ->where('freeLearningUnitStudent.gibbonPersonIDStudent = :gibbonPersonID')
            ->bindValue('gibbonPersonID', $gibbonPersonID)
            ->where("freeLearningUnitStudent.status IN ('Current', 'Complete - Pending')")
            ->orderBy(['timestampJoined DESC'])
            ->setPaging(1)
            ->page(1);

        return $this->runSelect($query)->fetchAll();
    }

    public function selectAllUnitsByLearner($gibbonPersonID, $date = null)
    {
        $query = $this
            ->newSelect()
            ->cols(['freeLearningUnitStudent.timestampJoined', 'freeLearningUnit.name'])
            ->from('freeLearningUnitStudent')
            ->innerJoin('freeLearningUnit', 'freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID')
            ->where('freeLearningUnitStudent.gibbonPersonIDStudent=:gibbonPersonID')
            ->bindValue('gibbonPersonID', $gibbonPersonID)
            ->orderBy(['timestampJoined']);

        if (!empty($date)) {
            $query->where('freeLearningUnitStudent.timestampJoined>=:date')
                ->bindValue('date', $date." 00:00:00");
        }

        return $this->runSelect($query)->fetchAll();
    }

    public function selectMentorshipByMentor($gibbonPersonID, $status = null, $current = true)
    {
        $query = $this
            ->newSelect()
            ->cols(['freeLearningUnitStudent.timestampJoined', 'freeLearningUnit.name'])
            ->from('freeLearningUnitStudent')
            ->innerJoin('freeLearningUnit', 'freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID')
            ->innerJoin('gibbonPerson', 'freeLearningUnitStudent.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID')
            ->where("freeLearningUnitStudent.enrolmentMethod='schoolMentor'")
            ->where('freeLearningUnitStudent.gibbonPersonIDSchoolMentor=:gibbonPersonID')
            ->where("freeLearningUnit.active='Y'")
            ->bindValue('gibbonPersonID', $gibbonPersonID)
            ->orderBy(['timestampJoined']);

        if (!empty($status)) {
            $query->where('freeLearningUnitStudent.status=:status')
                ->bindValue('status', $status);
        }

        if ($current) {
            $query->where("gibbonPerson.status='Full'");
        }

        return $this->runSelect($query)->fetchAll();
    }
}
