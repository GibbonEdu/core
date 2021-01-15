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

namespace Gibbon\Module\Reports\Domain;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

class ReportingAccessGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonReportingAccess';
    private static $primaryKey = 'gibbonReportingAccessID';
    private static $searchableColumns = [''];

    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryReportingAccessBySchoolYear(QueryCriteria $criteria, $gibbonSchoolYearID)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols(['gibbonReportingAccess.gibbonReportingAccessID', 'gibbonReportingCycle.name as reportingCycle', "GROUP_CONCAT(DISTINCT gibbonRole.name ORDER BY gibbonRole.type, gibbonRole.name SEPARATOR '<br/>') as roleName", "GROUP_CONCAT(DISTINCT gibbonReportingScope.name ORDER BY gibbonReportingScope.sequenceNumber SEPARATOR '<br/>') as scopeName", 'gibbonReportingAccess.dateStart', 'gibbonReportingAccess.dateEnd', 'gibbonReportingCycle.dateStart as cycleDateStart', 'gibbonReportingCycle.dateEnd as cycleDateEnd'])
            ->innerJoin('gibbonReportingCycle', 'gibbonReportingCycle.gibbonReportingCycleID=gibbonReportingAccess.gibbonReportingCycleID')
            ->leftJoin('gibbonReportingScope', 'FIND_IN_SET(gibbonReportingScope.gibbonReportingScopeID, gibbonReportingAccess.gibbonReportingScopeIDList)')
            ->leftJoin('gibbonRole', 'FIND_IN_SET(gibbonRole.gibbonRoleID, gibbonReportingAccess.gibbonRoleIDList)')
            ->where('gibbonReportingCycle.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->groupBy(['gibbonReportingAccess.gibbonReportingAccessID']);

        $criteria->addFilterRules([
            'reportingCycle' => function ($query, $gibbonReportingCycleID) {
                return $query
                    ->where('gibbonReportingCycle.gibbonReportingCycleID = :gibbonReportingCycleID')
                    ->bindValue('gibbonReportingCycleID', $gibbonReportingCycleID);
            },
        ]);

        return $this->runQuery($query, $criteria);
    }

    public function queryActiveReportingCyclesByPerson(QueryCriteria $criteria, $gibbonSchoolYearID, $gibbonPersonID)
    {
        $query = $this
            ->newQuery()
            ->from('gibbonPerson')
            ->cols(['gibbonReportingCycle.gibbonReportingCycleID', 'gibbonReportingCycle.name', 'gibbonReportingCycle.dateStart', 'gibbonReportingCycle.dateEnd', 'gibbonReportingCycle.milestones', 'gibbonReportingAccess.canWrite', 'gibbonReportingAccess.canProofRead'])
            ->innerJoin('gibbonReportingAccess', "(
                (gibbonReportingAccess.accessType='Person' AND FIND_IN_SET(gibbonPerson.gibbonPersonID, gibbonReportingAccess.gibbonPersonIDList)) OR (gibbonReportingAccess.accessType='Role' AND FIND_IN_SET(gibbonPerson.gibbonRoleIDPrimary, gibbonReportingAccess.gibbonRoleIDList))
            )")
            ->innerJoin('gibbonReportingCycle', 'gibbonReportingCycle.gibbonReportingCycleID=gibbonReportingAccess.gibbonReportingCycleID')
            ->where('gibbonPerson.gibbonPersonID=:gibbonPersonID')
            ->bindValue('gibbonPersonID', $gibbonPersonID)
            ->where('gibbonReportingCycle.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->where('(:today BETWEEN gibbonReportingCycle.dateStart AND gibbonReportingCycle.dateEnd)')
            ->where('(:today BETWEEN gibbonReportingAccess.dateStart AND gibbonReportingAccess.dateEnd)')
            ->bindValue('today', date('Y-m-d'))
            ->groupBy(['gibbonReportingCycle.gibbonReportingCycleID']);

        return $this->runQuery($query, $criteria);
    }

    public function queryActiveReportingScopesByPerson(QueryCriteria $criteria, $gibbonReportingCycleID, $gibbonPersonID)
    {
        $gibbonReportingCycleIDList = is_array($gibbonReportingCycleID)? $gibbonReportingCycleID : [$gibbonReportingCycleID];
        $query = $this
            ->newQuery()
            ->from('gibbonPerson')
            ->cols(['gibbonReportingScope.gibbonReportingScopeID', 'gibbonReportingScope.name', 'MIN(gibbonReportingAccess.dateStart) as dateStart', 'MAX(gibbonReportingAccess.dateEnd) as dateEnd', 'gibbonReportingAccess.canWrite', 'gibbonReportingAccess.canProofRead'])
            ->innerJoin('gibbonReportingAccess', "(
                (gibbonReportingAccess.accessType='Person' AND FIND_IN_SET(gibbonPerson.gibbonPersonID, gibbonReportingAccess.gibbonPersonIDList)) OR (gibbonReportingAccess.accessType='Role' AND FIND_IN_SET(gibbonPerson.gibbonRoleIDPrimary, gibbonReportingAccess.gibbonRoleIDList))
            )")
            ->innerJoin('gibbonReportingCycle', 'gibbonReportingCycle.gibbonReportingCycleID=gibbonReportingAccess.gibbonReportingCycleID')
            ->innerJoin('gibbonReportingScope', 'gibbonReportingScope.gibbonReportingCycleID=gibbonReportingCycle.gibbonReportingCycleID AND FIND_IN_SET(gibbonReportingScope.gibbonReportingScopeID, gibbonReportingAccess.gibbonReportingScopeIDList)')
            ->where('gibbonPerson.gibbonPersonID=:gibbonPersonID')
            ->bindValue('gibbonPersonID', $gibbonPersonID)
            ->where('FIND_IN_SET(gibbonReportingCycle.gibbonReportingCycleID, :gibbonReportingCycleIDList)')
            ->bindValue('gibbonReportingCycleIDList', implode(',', $gibbonReportingCycleIDList))
            ->where('(:today BETWEEN gibbonReportingCycle.dateStart AND gibbonReportingCycle.dateEnd)')
            ->where('(:today BETWEEN gibbonReportingAccess.dateStart AND gibbonReportingAccess.dateEnd)')
            ->bindValue('today', date('Y-m-d'))
            ->groupBy(['gibbonReportingScope.gibbonReportingScopeID']);

        return $this->runQuery($query, $criteria);
    }

    public function queryActiveCriteriaGroupsByPerson(QueryCriteria $criteria, $gibbonReportingScopeID, $gibbonPersonID, $allStudents = false)
    {
        $onlyFullStudents = !$allStudents
            ? "AND student.status='Full' AND (student.dateStart IS NULL OR student.dateStart<=:today) AND (student.dateEnd IS NULL OR student.dateEnd>=:today)"
            : "";

        $query = $this
            ->newQuery()
            ->from('gibbonPerson')
            ->cols(["LPAD(gibbonCourseClass.gibbonCourseClassID, 8, '0')  as scopeTypeID", 'gibbonCourse.name as criteriaName', "CONCAT(gibbonCourse.nameShort, '.', gibbonCourseClass.nameShort) as criteriaNameShort",
            "(SELECT COUNT(*) FROM gibbonReportingCriteria as criteria WHERE criteria.gibbonReportingScopeID=:gibbonReportingScopeID AND criteria.target='Per Student') as targetCount",
            "(SELECT COUNT(*) FROM gibbonCourseClassPerson as students JOIN gibbonPerson as student ON (student.gibbonPersonID=students.gibbonPersonID) WHERE students.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID AND students.role='Student' AND students.reportable='Y' $onlyFullStudents) as totalCount",
            "(SELECT COUNT(*) FROM gibbonReportingProgress as progress WHERE progress.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID AND progress.gibbonReportingScopeID=:gibbonReportingScopeID AND progress.status='Complete') as progressCount"])
            ->innerJoin('gibbonCourseClassPerson', 'gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID')
            ->innerJoin('gibbonCourseClass', 'gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID')
            ->innerJoin('gibbonCourse', 'gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID')
            ->innerJoin('gibbonReportingCriteria', 'gibbonReportingCriteria.gibbonCourseID=gibbonCourse.gibbonCourseID')
            ->where("gibbonCourseClassPerson.role='Teacher'")
            ->where("gibbonCourseClassPerson.reportable='Y'")
            ->where("gibbonCourseClass.reportable='Y'")
            ->where('gibbonPerson.gibbonPersonID=:gibbonPersonID')
            ->bindValue('gibbonPersonID', $gibbonPersonID)
            ->where('gibbonReportingCriteria.gibbonReportingScopeID=:gibbonReportingScopeID')
            ->bindValue('gibbonReportingScopeID', $gibbonReportingScopeID)
            ->having('totalCount > 0')
            ->groupBy(['gibbonCourse.gibbonCourseID', 'gibbonCourseClass.gibbonCourseClassID']);

        if (!$allStudents) $query->bindValue('today', date('Y-m-d'));

        $query->unionAll()
            ->from('gibbonPerson')
            ->cols(["LPAD(gibbonYearGroup.gibbonYearGroupID, 3, '0') as scopeTypeID", 'gibbonYearGroup.name as criteriaName', 'gibbonYearGroup.nameShort as criteriaNameShort',
            "(SELECT COUNT(*) FROM gibbonReportingCriteria as criteria WHERE criteria.gibbonReportingScopeID=:gibbonReportingScopeID AND criteria.target='Per Student') as targetCount",
            "(SELECT COUNT(*) FROM gibbonStudentEnrolment as enrolment JOIN gibbonPerson as student ON (student.gibbonPersonID=enrolment.gibbonPersonID) WHERE enrolment.gibbonSchoolYearID=gibbonReportingCycle.gibbonSchoolYearID AND enrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID $onlyFullStudents) as totalCount",
            "(SELECT COUNT(*) FROM gibbonReportingProgress as progress WHERE progress.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID AND progress.gibbonReportingScopeID=:gibbonReportingScopeID AND progress.status='Complete') as progressCount"])
            ->innerJoin('gibbonYearGroup', 'gibbonYearGroup.gibbonPersonIDHOY=gibbonPerson.gibbonPersonID')
            ->innerJoin('gibbonReportingCriteria', 'gibbonReportingCriteria.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID')
            ->innerJoin('gibbonReportingCycle', 'gibbonReportingCycle.gibbonReportingCycleID=gibbonReportingCriteria.gibbonReportingCycleID')
            ->where('gibbonPerson.gibbonPersonID=:gibbonPersonID')
            ->bindValue('gibbonPersonID', $gibbonPersonID)
            ->where('gibbonReportingCriteria.gibbonReportingScopeID=:gibbonReportingScopeID')
            ->bindValue('gibbonReportingScopeID', $gibbonReportingScopeID)
            ->having('totalCount > 0')
            ->groupBy(['gibbonYearGroup.gibbonYearGroupID']);

        if (!$allStudents) $query->bindValue('today', date('Y-m-d'));

        $query->unionAll()
            ->from('gibbonPerson')
            ->cols(["LPAD(gibbonRollGroup.gibbonRollGroupID, 5, '0') as scopeTypeID", 'gibbonRollGroup.name as criteriaName', 'gibbonRollGroup.nameShort as criteriaNameShort',
            "(SELECT COUNT(*) FROM gibbonReportingCriteria as criteria WHERE criteria.gibbonReportingScopeID=:gibbonReportingScopeID AND criteria.target='Per Student') as targetCount",
            "(SELECT COUNT(*) FROM gibbonStudentEnrolment as enrolment JOIN gibbonPerson as student ON (student.gibbonPersonID=enrolment.gibbonPersonID) WHERE enrolment.gibbonSchoolYearID=gibbonReportingCycle.gibbonSchoolYearID AND enrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID $onlyFullStudents) as totalCount",
            "(SELECT COUNT(*) FROM gibbonReportingProgress as progress WHERE progress.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID AND progress.gibbonReportingScopeID=:gibbonReportingScopeID AND progress.status='Complete') as progressCount"])
            ->innerJoin('gibbonRollGroup', '(gibbonRollGroup.gibbonPersonIDTutor=gibbonPerson.gibbonPersonID OR gibbonRollGroup.gibbonPersonIDTutor2=gibbonPerson.gibbonPersonID OR gibbonRollGroup.gibbonPersonIDTutor3=gibbonPerson.gibbonPersonID)')
            ->innerJoin('gibbonReportingCriteria', 'gibbonReportingCriteria.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID')
            ->innerJoin('gibbonReportingCycle', 'gibbonReportingCycle.gibbonReportingCycleID=gibbonReportingCriteria.gibbonReportingCycleID')
            ->where('gibbonPerson.gibbonPersonID=:gibbonPersonID')
            ->bindValue('gibbonPersonID', $gibbonPersonID)
            ->where('gibbonReportingCriteria.gibbonReportingScopeID=:gibbonReportingScopeID')
            ->bindValue('gibbonReportingScopeID', $gibbonReportingScopeID)
            ->having('totalCount > 0')
            ->groupBy(['gibbonRollGroup.gibbonRollGroupID']);

        if (!$allStudents) $query->bindValue('today', date('Y-m-d'));

        return $this->runQuery($query, $criteria);
    }


    public function selectAccessibleRollGroupsByReportingScope($gibbonReportingScopeID)
    {
        $query = $this
            ->newSelect()
            ->from('gibbonReportingScope')
            ->cols(['gibbonRollGroup.gibbonRollGroupID', 'gibbonRollGroup.name'])
            ->innerJoin('gibbonReportingCycle', 'gibbonReportingCycle.gibbonReportingCycleID=gibbonReportingScope.gibbonReportingCycleID')
            ->innerJoin('gibbonStudentEnrolment', 'FIND_IN_SET(gibbonStudentEnrolment.gibbonYearGroupID, gibbonReportingCycle.gibbonYearGroupIDList) AND gibbonStudentEnrolment.gibbonSchoolYearID=gibbonReportingCycle.gibbonSchoolYearID')
            ->innerJoin('gibbonRollGroup', 'gibbonRollGroup.gibbonRollGroupID=gibbonStudentEnrolment.gibbonRollGroupID')
            ->where('gibbonReportingScope.gibbonReportingScopeID=:gibbonReportingScopeID')
            ->bindValue('gibbonReportingScopeID', $gibbonReportingScopeID)
            ->groupBy(['gibbonRollGroup.gibbonRollGroupID'])
            ->orderBy(['LENGTH(gibbonRollGroup.name)', 'gibbonRollGroup.name']);

        return $this->runSelect($query);
    }

    public function selectAccessibleStaffByReportingScope($gibbonReportingScopeID)
    {
        // COURSE
        $query = $this
            ->newSelect()
            ->distinct()
            ->from('gibbonReportingScope')
            ->cols(['gibbonPerson.gibbonPersonID', 'gibbonPerson.surname', 'gibbonPerson.preferredName'])
            ->innerJoin('gibbonReportingCriteria', 'gibbonReportingCriteria.gibbonReportingScopeID=gibbonReportingScope.gibbonReportingScopeID')
            ->innerJoin('gibbonCourse', 'gibbonCourse.gibbonCourseID=gibbonReportingCriteria.gibbonCourseID')
            ->innerJoin('gibbonCourseClass', 'gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID')
            ->innerJoin('gibbonCourseClassPerson', 'gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID')
            ->innerJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID')
            ->where('gibbonReportingScope.gibbonReportingScopeID=:gibbonReportingScopeID')
            ->where("gibbonCourseClassPerson.role='Teacher'")
            ->where("gibbonCourseClassPerson.reportable='Y'")
            ->where("gibbonCourseClass.reportable='Y'")
            ->where("gibbonPerson.status='Full'")
            ->bindValue('gibbonReportingScopeID', $gibbonReportingScopeID);

        // ROLL GROUP
        $query->unionAll()
            ->distinct()
            ->from('gibbonReportingScope')
            ->cols(['gibbonPerson.gibbonPersonID', 'gibbonPerson.surname', 'gibbonPerson.preferredName'])
            ->innerJoin('gibbonReportingCriteria', 'gibbonReportingCriteria.gibbonReportingScopeID=gibbonReportingScope.gibbonReportingScopeID')
            ->innerJoin('gibbonRollGroup', 'gibbonRollGroup.gibbonRollGroupID=gibbonReportingCriteria.gibbonRollGroupID')
            ->innerJoin('gibbonPerson', '(gibbonRollGroup.gibbonPersonIDTutor=gibbonPerson.gibbonPersonID OR gibbonRollGroup.gibbonPersonIDTutor2=gibbonPerson.gibbonPersonID OR gibbonRollGroup.gibbonPersonIDTutor3=gibbonPerson.gibbonPersonID)')
            ->where('gibbonReportingScope.gibbonReportingScopeID=:gibbonReportingScopeID')
            ->where("gibbonPerson.status='Full'")
            ->bindValue('gibbonReportingScopeID', $gibbonReportingScopeID);

        // YEAR GROUP
        $query->unionAll()
            ->distinct()
            ->from('gibbonReportingScope')
            ->cols(['gibbonPerson.gibbonPersonID', 'gibbonPerson.surname', 'gibbonPerson.preferredName'])
            ->innerJoin('gibbonReportingCriteria', 'gibbonReportingCriteria.gibbonReportingScopeID=gibbonReportingScope.gibbonReportingScopeID')
            ->innerJoin('gibbonYearGroup', 'gibbonYearGroup.gibbonYearGroupID=gibbonReportingCriteria.gibbonYearGroupID')
            ->innerJoin('gibbonPerson', 'gibbonYearGroup.gibbonPersonIDHOY=gibbonPerson.gibbonPersonID')
            ->where('gibbonReportingScope.gibbonReportingScopeID=:gibbonReportingScopeID')
            ->where("gibbonPerson.status='Full'")
            ->bindValue('gibbonReportingScopeID', $gibbonReportingScopeID);

        $query->orderBy(['surname', 'preferredName']);

        return $this->runSelect($query);
    }

    public function selectReportingDetailsByScope($gibbonReportingScopeID, $scopeType, $scopeTypeID)
    {
        $query = $this
            ->newSelect()
            ->from('gibbonReportingScope')
            ->cols(['gibbonReportingScope.name as scopeName'])
            ->innerJoin('gibbonReportingCriteria', 'gibbonReportingCriteria.gibbonReportingScopeID=gibbonReportingScope.gibbonReportingScopeID')
            ->where('gibbonReportingScope.gibbonReportingScopeID=:gibbonReportingScopeID')
            ->bindValue('gibbonReportingScopeID', $gibbonReportingScopeID)
            ->bindValue('scopeTypeID', $scopeTypeID)
            ->groupBy(['gibbonReportingScope.gibbonReportingScopeID']);

        if ($scopeType == 'Year Group') {
            $query->cols(['gibbonYearGroup.name as name', 'gibbonYearGroup.nameShort as nameShort'])
                ->innerJoin('gibbonYearGroup', 'gibbonYearGroup.gibbonYearGroupID=gibbonReportingCriteria.gibbonYearGroupID')
                ->where('gibbonYearGroup.gibbonYearGroupID=:scopeTypeID');
        } elseif ($scopeType == 'Roll Group') {
            $query->cols(['gibbonRollGroup.name as name', 'gibbonRollGroup.nameShort as nameShort'])
                ->innerJoin('gibbonRollGroup', 'gibbonRollGroup.gibbonRollGroupID=gibbonReportingCriteria.gibbonRollGroupID')
                ->where('gibbonRollGroup.gibbonRollGroupID=:scopeTypeID');
        } elseif ($scopeType == 'Course') {
            $query->cols(['gibbonCourse.name', "CONCAT(gibbonCourse.nameShort, '.', gibbonCourseClass.nameShort) as nameShort"])
                ->innerJoin('gibbonCourseClass', 'gibbonCourseClass.gibbonCourseID=gibbonReportingCriteria.gibbonCourseID')
                ->innerJoin('gibbonCourse', 'gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID')
                ->where('gibbonCourseClass.gibbonCourseClassID=:scopeTypeID');
        }

        return $this->runSelect($query);
    }

    public function selectReportingProgressByScope($gibbonReportingScopeID, $scopeType, $scopeTypeID, $allStudents = false)
    {
        $query = $this
            ->newSelect()
            ->from('gibbonReportingCriteria')
            ->cols(['gibbonPerson.gibbonPersonID', 'gibbonReportingProgress.gibbonReportingProgressID', 'gibbonReportingProgress.status as progress', 'gibbonPerson.gibbonPersonID', 'gibbonPerson.title', 'gibbonPerson.surname', 'gibbonPerson.preferredName', 'gibbonPerson.image_240', 'gibbonPerson.status'])
            ->innerJoin('gibbonReportingCycle', 'gibbonReportingCycle.gibbonReportingCycleID=gibbonReportingCriteria.gibbonReportingCycleID')
            ->bindValue('scopeTypeID', $scopeTypeID)
            ->where('gibbonReportingCriteria.gibbonReportingScopeID=:gibbonReportingScopeID')
            ->where("gibbonReportingCriteria.target='Per Student'")
            ->bindValue('gibbonReportingScopeID', $gibbonReportingScopeID)
            ->orderBy(['gibbonPerson.surname', 'gibbonPerson.preferredName']);

        if (!$allStudents) {
            $query->where("gibbonPerson.status='Full'")
                ->where('(gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart<=:today)')
                ->where('(gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd>=:today)')
                ->bindValue('today', date('Y-m-d'));
        }

        if ($scopeType == 'Year Group') {
            $query->innerJoin('gibbonStudentEnrolment', 'gibbonStudentEnrolment.gibbonSchoolYearID=gibbonReportingCycle.gibbonSchoolYearID')
                ->innerJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID')
                ->leftJoin('gibbonReportingProgress', 'gibbonReportingProgress.gibbonReportingScopeID=gibbonReportingCriteria.gibbonReportingScopeID AND gibbonReportingProgress.gibbonYearGroupID=gibbonStudentEnrolment.gibbonYearGroupID AND gibbonReportingProgress.gibbonPersonIDStudent=gibbonStudentEnrolment.gibbonPersonID')
                ->where('gibbonStudentEnrolment.gibbonYearGroupID=:scopeTypeID')
                ->groupBy(['gibbonStudentEnrolment.gibbonPersonID']);
        } elseif ($scopeType == 'Roll Group') {
            $query->innerJoin('gibbonStudentEnrolment', 'gibbonStudentEnrolment.gibbonSchoolYearID=gibbonReportingCycle.gibbonSchoolYearID')
                ->innerJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID')
                ->leftJoin('gibbonReportingProgress', 'gibbonReportingProgress.gibbonReportingScopeID=gibbonReportingCriteria.gibbonReportingScopeID AND gibbonReportingProgress.gibbonRollGroupID=gibbonStudentEnrolment.gibbonRollGroupID AND gibbonReportingProgress.gibbonPersonIDStudent=gibbonStudentEnrolment.gibbonPersonID')
                ->where('gibbonStudentEnrolment.gibbonRollGroupID=:scopeTypeID')
                ->groupBy(['gibbonStudentEnrolment.gibbonPersonID']);
        } elseif ($scopeType == 'Course') {
            $query->innerJoin('gibbonCourseClass', 'gibbonCourseClass.gibbonCourseID=gibbonReportingCriteria.gibbonCourseID')
                ->innerJoin('gibbonCourseClassPerson', "gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID AND gibbonCourseClassPerson.role='Student'")
                ->innerJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID')
                ->leftJoin('gibbonReportingProgress', 'gibbonReportingProgress.gibbonReportingScopeID=gibbonReportingCriteria.gibbonReportingScopeID AND gibbonReportingProgress.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID AND gibbonReportingProgress.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID')
                ->where('gibbonCourseClass.gibbonCourseClassID=:scopeTypeID')
                ->where("gibbonCourseClassPerson.reportable='Y'")
                ->where("gibbonCourseClass.reportable='Y'")
                ->groupBy(['gibbonCourseClassPerson.gibbonCourseClassPersonID']);
        }

        return $this->runSelect($query);
    }

    public function selectReportingCriteriaByStudent($gibbonReportingCycleID, $gibbonPersonIDStudent)
    {
        // YEAR GROUP
        $query = $this
            ->newSelect()
            ->from('gibbonReportingCycle')
            ->cols(['gibbonReportingScope.gibbonReportingScopeID  as groupBy', 'gibbonReportingScope.name as scopeName', '0 as orderBy',
            'gibbonReportingCriteria.gibbonReportingCriteriaID', 'gibbonReportingCriteria.name', 'gibbonReportingCriteria.description', 'gibbonReportingCriteria.category', 'gibbonReportingCriteriaType.name as criteriaName', 'gibbonReportingCriteriaType.valueType', 'gibbonReportingCriteriaType.characterLimit', 'gibbonReportingCriteriaType.gibbonScaleID', 'gibbonReportingValue.gibbonScaleGradeID', "(CASE WHEN gibbonReportingCriteriaType.valueType='Grade Scale' THEN gibbonScaleGrade.descriptor ELSE gibbonReportingValue.value END) as value", 'gibbonReportingValue.comment', 'gibbonReportingProgress.status as progress',
            'created.title', 'created.preferredName', 'created.surname', 'gibbonReportingScope.sequenceNumber as scopeSequence', 'gibbonReportingCriteria.sequenceNumber as criteriaSequence', 'gibbonReportingCriteria.target as criteriaTarget'])
            ->innerJoin('gibbonStudentEnrolment', 'gibbonStudentEnrolment.gibbonSchoolYearID=gibbonReportingCycle.gibbonSchoolYearID')
            ->innerJoin('gibbonReportingCriteria', 'gibbonReportingCriteria.gibbonReportingCycleID=gibbonReportingCycle.gibbonReportingCycleID
                AND gibbonReportingCriteria.gibbonYearGroupID=gibbonStudentEnrolment.gibbonYearGroupID')
            ->innerJoin('gibbonReportingCriteriaType', 'gibbonReportingCriteriaType.gibbonReportingCriteriaTypeID=gibbonReportingCriteria.gibbonReportingCriteriaTypeID')
            ->innerJoin('gibbonReportingScope', 'gibbonReportingScope.gibbonReportingScopeID=gibbonReportingCriteria.gibbonReportingScopeID')
            ->leftJoin('gibbonReportingValue', "gibbonReportingValue.gibbonReportingCriteriaID=gibbonReportingCriteria.gibbonReportingCriteriaID
                AND (gibbonReportingCriteria.target='Per Group' OR (gibbonReportingValue.gibbonPersonIDStudent=gibbonStudentEnrolment.gibbonPersonID) AND gibbonReportingCriteria.target='Per Student')
                ")
            ->leftJoin('gibbonScaleGrade', 'gibbonScaleGrade.gibbonScaleID=gibbonReportingCriteriaType.gibbonScaleID AND gibbonReportingValue.gibbonScaleGradeID=gibbonScaleGrade.gibbonScaleGradeID')
            ->leftJoin('gibbonReportingProgress', 'gibbonReportingProgress.gibbonReportingScopeID=gibbonReportingCriteria.gibbonReportingScopeID
                AND gibbonReportingProgress.gibbonYearGroupID=gibbonReportingCriteria.gibbonYearGroupID
                AND gibbonReportingProgress.gibbonPersonIDStudent=gibbonReportingValue.gibbonPersonIDStudent')
            ->leftJoin('gibbonPerson as created', 'gibbonReportingValue.gibbonPersonIDCreated=created.gibbonPersonID')
            ->where('gibbonStudentEnrolment.gibbonPersonID=:gibbonPersonIDStudent')
            ->bindValue('gibbonPersonIDStudent', $gibbonPersonIDStudent)
            ->where('gibbonReportingCriteria.gibbonReportingCycleID=:gibbonReportingCycleID')
            ->bindValue('gibbonReportingCycleID', $gibbonReportingCycleID)
            ->where("gibbonReportingScope.scopeType = 'Year Group'")
            ->where("gibbonReportingCriteriaType.valueType <> 'Remark'");

        // ROLL GROUP
        $query->unionAll()
            ->from('gibbonReportingCycle')
            ->cols(['gibbonReportingScope.gibbonReportingScopeID  as groupBy', 'gibbonReportingScope.name as scopeName', '0 as orderBy',
            'gibbonReportingCriteria.gibbonReportingCriteriaID', 'gibbonReportingCriteria.name', 'gibbonReportingCriteria.description', 'gibbonReportingCriteria.category', 'gibbonReportingCriteriaType.name as criteriaName', 'gibbonReportingCriteriaType.valueType', 'gibbonReportingCriteriaType.characterLimit', 'gibbonReportingCriteriaType.gibbonScaleID', 'gibbonReportingValue.gibbonScaleGradeID', "(CASE WHEN gibbonReportingCriteriaType.valueType='Grade Scale' THEN gibbonScaleGrade.descriptor ELSE gibbonReportingValue.value END) as value", 'gibbonReportingValue.comment', 'gibbonReportingProgress.status as progress',
            'created.title', 'created.preferredName', 'created.surname', 'gibbonReportingScope.sequenceNumber as scopeSequence', 'gibbonReportingCriteria.sequenceNumber as criteriaSequence', 'gibbonReportingCriteria.target as criteriaTarget'])
            ->innerJoin('gibbonStudentEnrolment', 'gibbonStudentEnrolment.gibbonSchoolYearID=gibbonReportingCycle.gibbonSchoolYearID')
            ->innerJoin('gibbonReportingCriteria', 'gibbonReportingCriteria.gibbonReportingCycleID=gibbonReportingCycle.gibbonReportingCycleID
                AND gibbonReportingCriteria.gibbonRollGroupID=gibbonStudentEnrolment.gibbonRollGroupID')
            ->innerJoin('gibbonReportingCriteriaType', 'gibbonReportingCriteriaType.gibbonReportingCriteriaTypeID=gibbonReportingCriteria.gibbonReportingCriteriaTypeID')
            ->innerJoin('gibbonReportingScope', 'gibbonReportingScope.gibbonReportingScopeID=gibbonReportingCriteria.gibbonReportingScopeID')
            ->leftJoin('gibbonReportingValue', "gibbonReportingValue.gibbonReportingCriteriaID=gibbonReportingCriteria.gibbonReportingCriteriaID
                AND (gibbonReportingCriteria.target='Per Group' OR (gibbonReportingValue.gibbonPersonIDStudent=gibbonStudentEnrolment.gibbonPersonID) AND gibbonReportingCriteria.target='Per Student')
                ")
            ->leftJoin('gibbonScaleGrade', 'gibbonScaleGrade.gibbonScaleID=gibbonReportingCriteriaType.gibbonScaleID AND gibbonReportingValue.gibbonScaleGradeID=gibbonScaleGrade.gibbonScaleGradeID')
            ->leftJoin('gibbonReportingProgress', 'gibbonReportingProgress.gibbonReportingScopeID=gibbonReportingCriteria.gibbonReportingScopeID
                AND gibbonReportingProgress.gibbonRollGroupID=gibbonReportingCriteria.gibbonRollGroupID
                AND gibbonReportingProgress.gibbonPersonIDStudent=gibbonReportingValue.gibbonPersonIDStudent')
            ->leftJoin('gibbonPerson as created', 'gibbonReportingValue.gibbonPersonIDCreated=created.gibbonPersonID')
            ->where('gibbonStudentEnrolment.gibbonPersonID=:gibbonPersonIDStudent')
            ->bindValue('gibbonPersonIDStudent', $gibbonPersonIDStudent)
            ->where('gibbonReportingCriteria.gibbonReportingCycleID=:gibbonReportingCycleID')
            ->bindValue('gibbonReportingCycleID', $gibbonReportingCycleID)
            ->where("gibbonReportingScope.scopeType = 'Roll Group'")
            ->where("gibbonReportingCriteriaType.valueType <> 'Remark'");

        $query->unionAll()
            ->from('gibbonReportingCycle')
            ->cols(['gibbonCourse.gibbonCourseID as groupBy', 'gibbonCourse.name as scopeName', 'gibbonCourse.orderBy as orderBy',
            'gibbonReportingCriteria.gibbonReportingCriteriaID', 'gibbonReportingCriteria.name', 'gibbonReportingCriteria.description', 'gibbonReportingCriteria.category', 'gibbonReportingCriteriaType.name as criteriaName', 'gibbonReportingCriteriaType.valueType', 'gibbonReportingCriteriaType.characterLimit', 'gibbonReportingCriteriaType.gibbonScaleID', 'gibbonReportingValue.gibbonScaleGradeID', "(CASE WHEN gibbonReportingCriteriaType.valueType='Grade Scale' THEN gibbonScaleGrade.descriptor ELSE gibbonReportingValue.value END) as value", 'gibbonReportingValue.comment', 'gibbonReportingProgress.status as progress', 'created.title', 'created.preferredName', 'created.surname', 'gibbonReportingScope.sequenceNumber as scopeSequence', 'gibbonReportingCriteria.sequenceNumber as criteriaSequence', 'gibbonReportingCriteria.target as criteriaTarget'])
            ->innerJoin('gibbonStudentEnrolment', 'gibbonStudentEnrolment.gibbonSchoolYearID=gibbonReportingCycle.gibbonSchoolYearID')
            ->innerJoin('gibbonReportingCriteria', 'gibbonReportingCriteria.gibbonReportingCycleID=gibbonReportingCycle.gibbonReportingCycleID
                AND gibbonReportingCriteria.gibbonCourseID IS NOT NULL')
            ->innerJoin('gibbonReportingCriteriaType', 'gibbonReportingCriteriaType.gibbonReportingCriteriaTypeID=gibbonReportingCriteria.gibbonReportingCriteriaTypeID')
            ->innerJoin('gibbonReportingScope', 'gibbonReportingScope.gibbonReportingScopeID=gibbonReportingCriteria.gibbonReportingScopeID')
            ->innerJoin('gibbonCourse', 'gibbonCourse.gibbonCourseID=gibbonReportingCriteria.gibbonCourseID')
            ->innerJoin('gibbonCourseClass', "gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID")
            ->innerJoin('gibbonCourseClassPerson', 'gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID')
            ->leftJoin('gibbonReportingValue', "gibbonReportingValue.gibbonReportingCriteriaID=gibbonReportingCriteria.gibbonReportingCriteriaID
                AND gibbonCourseClass.gibbonCourseClassID=gibbonReportingValue.gibbonCourseClassID
                AND (gibbonReportingCriteria.target='Per Group' OR (gibbonReportingValue.gibbonPersonIDStudent=gibbonStudentEnrolment.gibbonPersonID) AND gibbonReportingCriteria.target='Per Student')
                ")
            ->leftJoin('gibbonScaleGrade', 'gibbonScaleGrade.gibbonScaleID=gibbonReportingCriteriaType.gibbonScaleID AND gibbonReportingValue.gibbonScaleGradeID=gibbonScaleGrade.gibbonScaleGradeID')
            ->leftJoin('gibbonReportingProgress', 'gibbonReportingProgress.gibbonReportingScopeID=gibbonReportingCriteria.gibbonReportingScopeID
                AND gibbonReportingProgress.gibbonCourseClassID=gibbonReportingValue.gibbonCourseClassID
                AND gibbonReportingProgress.gibbonPersonIDStudent=gibbonReportingValue.gibbonPersonIDStudent')
            ->leftJoin('gibbonPerson as created', 'gibbonReportingValue.gibbonPersonIDCreated=created.gibbonPersonID')
            ->where('gibbonCourseClassPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID')
            ->where("gibbonCourseClassPerson.role='Student'")
            ->where("gibbonCourseClassPerson.reportable='Y'")
            ->where('gibbonStudentEnrolment.gibbonPersonID=:gibbonPersonIDStudent')
            ->bindValue('gibbonPersonIDStudent', $gibbonPersonIDStudent)
            ->where('gibbonReportingCriteria.gibbonReportingCycleID=:gibbonReportingCycleID')
            ->bindValue('gibbonReportingCycleID', $gibbonReportingCycleID)
            ->where("gibbonReportingCriteriaType.valueType <> 'Remark'")
            ->where("gibbonReportingScope.scopeType = 'Course'");

        $query->orderBy([
            'scopeSequence',
            'orderBy',
            'criteriaTarget DESC',
            'criteriaSequence',
            'gibbonReportingCriteriaID']);

        return $this->runSelect($query);
    }

    public function selectReportingCriteriaByStudentAndScope($gibbonReportingScopeID, $scopeType, $scopeTypeID, $gibbonPersonIDStudent)
    {
        $query = $this
            ->newSelect()
            ->from('gibbonReportingCriteria')
            ->cols(['gibbonReportingCriteria.gibbonReportingCriteriaID', 'gibbonReportingCriteria.name', 'gibbonReportingCriteria.description', 'gibbonReportingCriteria.category', 'gibbonReportingCriteriaType.name as criteriaName', 'gibbonReportingCriteriaType.valueType', 'gibbonReportingCriteriaType.characterLimit', 'gibbonReportingCriteriaType.gibbonScaleID', 'gibbonReportingValue.gibbonReportingValueID', 'gibbonReportingValue.gibbonScaleGradeID', 'gibbonReportingValue.value', 'gibbonReportingValue.comment'])
            ->innerJoin('gibbonReportingCriteriaType', 'gibbonReportingCriteriaType.gibbonReportingCriteriaTypeID=gibbonReportingCriteria.gibbonReportingCriteriaTypeID')
            ->leftJoin('gibbonReportingValue', 'gibbonReportingValue.gibbonReportingCriteriaID=gibbonReportingCriteria.gibbonReportingCriteriaID AND gibbonReportingValue.gibbonPersonIDStudent=:gibbonPersonIDStudent')
            ->where('gibbonReportingCriteria.gibbonReportingScopeID=:gibbonReportingScopeID')
            ->where("gibbonReportingCriteria.target='Per Student'")
            ->bindValue('gibbonReportingScopeID', $gibbonReportingScopeID)
            ->bindValue('gibbonPersonIDStudent', $gibbonPersonIDStudent)
            ->bindValue('scopeTypeID', $scopeTypeID)
            ->orderBy(['gibbonReportingCriteria.sequenceNumber', 'gibbonReportingCriteria.gibbonReportingCriteriaID']);

        if ($scopeType == 'Year Group') {
            $query->where('gibbonReportingCriteria.gibbonYearGroupID=:scopeTypeID');
        } elseif ($scopeType == 'Roll Group') {
            $query->where('gibbonReportingCriteria.gibbonRollGroupID=:scopeTypeID');
        } elseif ($scopeType == 'Course') {
            $query->leftJoin('gibbonCourseClass', 'gibbonCourseClass.gibbonCourseID=gibbonReportingCriteria.gibbonCourseID')
                ->where('gibbonCourseClass.gibbonCourseClassID=:scopeTypeID');
        }

        return $this->runSelect($query);
    }

    public function selectAllRemarksByStudent($gibbonReportingCycleID, $gibbonPersonIDStudent)
    {
        $query = $this
            ->newSelect()
            ->from('gibbonReportingCycle')
            ->cols(['gibbonReportingCriteria.gibbonReportingCriteriaID', 'gibbonReportingCriteria.name', 'gibbonReportingCriteria.description', 'gibbonReportingCriteria.category', 'gibbonReportingCriteriaType.name as criteriaName', 'gibbonReportingValue.comment', 'gibbonReportingValue.timestampModified', 'created.title', 'created.preferredName', 'created.surname', 'created.image_240', 'gibbonReportingProgress.status as progress', 'gibbonReportingScope.gibbonReportingScopeID', 'gibbonReportingScope.scopeType', "(CASE WHEN gibbonReportingCriteria.gibbonYearGroupID IS NOT NULL THEN gibbonReportingCriteria.gibbonYearGroupID WHEN gibbonReportingCriteria.gibbonRollGroupID IS NOT NULL THEN gibbonReportingCriteria.gibbonRollGroupID ELSE gibbonReportingValue.gibbonCourseClassID END) AS scopeTypeID"])
            ->innerJoin('gibbonStudentEnrolment', 'gibbonStudentEnrolment.gibbonSchoolYearID=gibbonReportingCycle.gibbonSchoolYearID')
            ->innerJoin('gibbonReportingCriteria', 'gibbonReportingCriteria.gibbonReportingCycleID=gibbonReportingCycle.gibbonReportingCycleID
                AND (gibbonReportingCriteria.gibbonYearGroupID=gibbonStudentEnrolment.gibbonYearGroupID
                OR gibbonReportingCriteria.gibbonRollGroupID=gibbonStudentEnrolment.gibbonRollGroupID
                OR gibbonReportingCriteria.gibbonCourseID IS NOT NULL)')
            ->innerJoin('gibbonReportingCriteriaType', 'gibbonReportingCriteriaType.gibbonReportingCriteriaTypeID=gibbonReportingCriteria.gibbonReportingCriteriaTypeID')
            ->innerJoin('gibbonReportingScope', 'gibbonReportingScope.gibbonReportingScopeID=gibbonReportingCriteria.gibbonReportingScopeID')
            ->leftJoin('gibbonReportingValue', 'gibbonReportingValue.gibbonReportingCriteriaID=gibbonReportingCriteria.gibbonReportingCriteriaID AND gibbonReportingValue.gibbonPersonIDStudent=gibbonStudentEnrolment.gibbonPersonID')
            ->leftJoin('gibbonReportingProgress', 'gibbonReportingProgress.gibbonReportingScopeID=gibbonReportingCriteria.gibbonReportingScopeID
                AND (gibbonReportingProgress.gibbonYearGroupID=gibbonReportingCriteria.gibbonYearGroupID
                    OR gibbonReportingProgress.gibbonRollGroupID=gibbonReportingCriteria.gibbonRollGroupID
                    OR gibbonReportingProgress.gibbonCourseClassID=gibbonReportingValue.gibbonCourseClassID)
                AND gibbonReportingProgress.gibbonPersonIDStudent=gibbonReportingValue.gibbonPersonIDStudent')
            ->leftJoin('gibbonPerson as created', 'gibbonReportingValue.gibbonPersonIDCreated=created.gibbonPersonID')
            ->where('gibbonReportingCriteria.gibbonReportingCycleID=:gibbonReportingCycleID')
            ->bindValue('gibbonReportingCycleID', $gibbonReportingCycleID)
            ->where("gibbonStudentEnrolment.gibbonPersonID=:gibbonPersonIDStudent")
            ->bindValue('gibbonPersonIDStudent', $gibbonPersonIDStudent)
            ->where("gibbonReportingCriteria.target='Per Student'")
            ->where("gibbonReportingCriteriaType.valueType='Remark'")
            ->where("gibbonReportingProgress.status='Complete'")
            ->orderBy(['gibbonReportingCriteria.sequenceNumber', 'gibbonReportingCriteria.gibbonReportingCriteriaID']);

        return $this->runSelect($query);
    }

    public function selectReportingCriteriaByGroup($gibbonReportingScopeID, $scopeType, $scopeTypeID)
    {
        $query = $this
            ->newSelect()
            ->distinct()
            ->from('gibbonReportingCriteria')
            ->cols(['gibbonReportingCriteria.gibbonReportingCriteriaID', 'gibbonReportingCriteria.name', 'gibbonReportingCriteria.description', 'gibbonReportingCriteria.category', 'gibbonReportingCriteriaType.name as criteriaName', 'gibbonReportingCriteriaType.valueType', 'gibbonReportingCriteriaType.characterLimit', 'gibbonReportingCriteriaType.gibbonScaleID', 'gibbonReportingValue.gibbonScaleGradeID', 'gibbonReportingValue.value', 'gibbonReportingValue.comment'])
            ->innerJoin('gibbonReportingCriteriaType', 'gibbonReportingCriteriaType.gibbonReportingCriteriaTypeID=gibbonReportingCriteria.gibbonReportingCriteriaTypeID')
            ->where('gibbonReportingCriteria.gibbonReportingScopeID=:gibbonReportingScopeID')
            ->where("gibbonReportingCriteria.target='Per Group'")
            ->bindValue('gibbonReportingScopeID', $gibbonReportingScopeID)
            ->bindValue('scopeTypeID', $scopeTypeID)
            ->groupBy(['gibbonReportingCriteria.gibbonReportingCriteriaID'])
            ->orderBy(['gibbonReportingCriteria.sequenceNumber', 'gibbonReportingCriteria.gibbonReportingCriteriaID']);

        if ($scopeType == 'Year Group') {
            $query->leftJoin('gibbonReportingValue', 'gibbonReportingValue.gibbonReportingCriteriaID=gibbonReportingCriteria.gibbonReportingCriteriaID')
                ->where('gibbonReportingCriteria.gibbonYearGroupID=:scopeTypeID');
        } elseif ($scopeType == 'Roll Group') {
            $query->leftJoin('gibbonReportingValue', 'gibbonReportingValue.gibbonReportingCriteriaID=gibbonReportingCriteria.gibbonReportingCriteriaID')
                ->where('gibbonReportingCriteria.gibbonRollGroupID=:scopeTypeID');
        } elseif ($scopeType == 'Course') {
            $query
                ->innerJoin('gibbonCourseClass', 'gibbonCourseClass.gibbonCourseID=gibbonReportingCriteria.gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=:scopeTypeID')
                ->leftJoin('gibbonReportingValue', 'gibbonReportingValue.gibbonReportingCriteriaID=gibbonReportingCriteria.gibbonReportingCriteriaID AND gibbonReportingValue.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID')
                ->where('(gibbonReportingValue.gibbonReportingValueID IS NULL OR (gibbonReportingValue.gibbonReportingValueID IS NOT NULL AND gibbonReportingValue.gibbonCourseClassID=:scopeTypeID))');
        }

        return $this->runSelect($query);
    }

    public function getAccessToScopeByPerson($gibbonReportingScopeID, $gibbonPersonID)
    {
        $query = $this
            ->newSelect()
            ->from('gibbonPerson')
            ->cols(['gibbonReportingScope.gibbonReportingScopeID', 'gibbonReportingScope.name', 'MIN(gibbonReportingAccess.dateStart) as dateStart', 'MAX(gibbonReportingAccess.dateEnd) as dateEnd', "(CASE WHEN :today BETWEEN gibbonReportingCycle.dateStart AND gibbonReportingCycle.dateEnd AND :today BETWEEN MIN(gibbonReportingAccess.dateStart) AND MAX(gibbonReportingAccess.dateEnd) THEN 'Y' ELSE 'N' END) as reportingOpen", "(CASE WHEN gibbonReportingAccess.gibbonReportingAccessID IS NOT NULL THEN 'Y' ELSE 'N' END) AS canAccess", 'gibbonReportingAccess.canWrite', 'gibbonReportingAccess.canProofRead'])
            ->innerJoin('gibbonReportingAccess', "(
                (gibbonReportingAccess.accessType='Person' AND FIND_IN_SET(gibbonPerson.gibbonPersonID, gibbonReportingAccess.gibbonPersonIDList)) OR (gibbonReportingAccess.accessType='Role' AND FIND_IN_SET(gibbonPerson.gibbonRoleIDPrimary, gibbonReportingAccess.gibbonRoleIDList))
            )")
            ->innerJoin('gibbonReportingCycle', 'gibbonReportingCycle.gibbonReportingCycleID=gibbonReportingAccess.gibbonReportingCycleID')
            ->innerJoin('gibbonReportingScope', 'gibbonReportingScope.gibbonReportingCycleID=gibbonReportingCycle.gibbonReportingCycleID AND FIND_IN_SET(gibbonReportingScope.gibbonReportingScopeID, gibbonReportingAccess.gibbonReportingScopeIDList)')
            ->bindValue('today', date('Y-m-d'))
            ->where('gibbonPerson.gibbonPersonID=:gibbonPersonID')
            ->bindValue('gibbonPersonID', $gibbonPersonID)
            ->where('gibbonReportingScope.gibbonReportingScopeID=:gibbonReportingScopeID')
            ->bindValue('gibbonReportingScopeID', $gibbonReportingScopeID)
            ->having('gibbonReportingScope.gibbonReportingScopeID IS NOT NULL');

        return $this->runSelect($query)->fetch();
    }

    public function getAccessToScopeAndCriteriaGroupByPerson($gibbonReportingScopeID, $scopeType, $scopeTypeID, $gibbonPersonID)
    {
        $query = $this
            ->newSelect()
            ->from('gibbonPerson')
            ->cols(['gibbonReportingScope.gibbonReportingScopeID', 'gibbonReportingScope.name', 'MIN(gibbonReportingAccess.dateStart) as dateStart', 'MAX(gibbonReportingAccess.dateEnd) as dateEnd', "(CASE WHEN :today BETWEEN gibbonReportingCycle.dateStart AND gibbonReportingCycle.dateEnd AND :today BETWEEN MIN(gibbonReportingAccess.dateStart) AND MAX(gibbonReportingAccess.dateEnd) THEN 'Y' ELSE 'N' END) as reportingOpen", "(CASE WHEN MAX(gibbonReportingAccess.gibbonReportingAccessID) IS NOT NULL THEN 'Y' ELSE 'N' END) AS canAccess", 'MAX(gibbonReportingAccess.canWrite) as canWrite', 'MAX(gibbonReportingAccess.canProofRead) as canProofRead'])
            ->innerJoin('gibbonReportingAccess', "(
                (gibbonReportingAccess.accessType='Person' AND FIND_IN_SET(gibbonPerson.gibbonPersonID, gibbonReportingAccess.gibbonPersonIDList)) OR (gibbonReportingAccess.accessType='Role' AND FIND_IN_SET(gibbonPerson.gibbonRoleIDPrimary, gibbonReportingAccess.gibbonRoleIDList))
            )")
            ->innerJoin('gibbonReportingCycle', 'gibbonReportingCycle.gibbonReportingCycleID=gibbonReportingAccess.gibbonReportingCycleID')
            ->innerJoin('gibbonReportingScope', 'gibbonReportingScope.gibbonReportingCycleID=gibbonReportingCycle.gibbonReportingCycleID AND FIND_IN_SET(gibbonReportingScope.gibbonReportingScopeID, gibbonReportingAccess.gibbonReportingScopeIDList)')
            ->innerJoin('gibbonReportingCriteria', 'gibbonReportingCriteria.gibbonReportingScopeID=gibbonReportingScope.gibbonReportingScopeID')
            ->bindValue('today', date('Y-m-d'))
            ->where('gibbonPerson.gibbonPersonID=:gibbonPersonID')
            ->bindValue('gibbonPersonID', $gibbonPersonID)
            ->where('gibbonReportingScope.gibbonReportingScopeID=:gibbonReportingScopeID')
            ->bindValue('gibbonReportingScopeID', $gibbonReportingScopeID)
            ->bindValue('scopeTypeID', $scopeTypeID)
            ->having('identifier IS NOT NULL')
            ->groupBy(['gibbonReportingScope.gibbonReportingScopeID']);

        if ($scopeType == 'Year Group') {
            $query->cols(['gibbonYearGroup.gibbonYearGroupID as identifier'])
                ->innerJoin('gibbonYearGroup', 'gibbonYearGroup.gibbonYearGroupID=gibbonReportingCriteria.gibbonYearGroupID')
                ->where('gibbonYearGroup.gibbonYearGroupID=:scopeTypeID')
                ->where('gibbonYearGroup.gibbonPersonIDHOY=gibbonPerson.gibbonPersonID');
        } elseif ($scopeType == 'Roll Group') {
            $query->cols(['gibbonRollGroup.gibbonRollGroupID as identifier'])
                ->innerJoin('gibbonRollGroup', 'gibbonRollGroup.gibbonRollGroupID=gibbonReportingCriteria.gibbonRollGroupID')
                ->where('gibbonRollGroup.gibbonRollGroupID=:scopeTypeID')
                ->where('(gibbonRollGroup.gibbonPersonIDTutor=gibbonPerson.gibbonPersonID OR gibbonRollGroup.gibbonPersonIDTutor2=gibbonPerson.gibbonPersonID OR gibbonRollGroup.gibbonPersonIDTutor3=gibbonPerson.gibbonPersonID)');
        } elseif ($scopeType == 'Course') {
            $query->cols(['gibbonCourseClassPerson.gibbonCourseClassID as identifier'])
                ->innerJoin('gibbonCourse', 'gibbonCourse.gibbonCourseID=gibbonReportingCriteria.gibbonCourseID')
                ->innerJoin('gibbonCourseClass', 'gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID')
                ->innerJoin('gibbonCourseClassPerson', 'gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID')
                ->where('gibbonCourseClassPerson.gibbonCourseClassID=:scopeTypeID')
                ->where('gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID')
                ->where("(gibbonCourseClassPerson.role='Teacher' OR gibbonCourseClassPerson.role='Assistant')")
                ->where("gibbonCourseClassPerson.reportable='Y'");
        }

        return $this->runSelect($query)->fetch();
    }
}
