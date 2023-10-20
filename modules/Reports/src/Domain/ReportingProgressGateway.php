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

namespace Gibbon\Module\Reports\Domain;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

class ReportingProgressGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonReportingProgress';
    private static $primaryKey = 'gibbonReportingProgressID';
    private static $searchableColumns = [''];

    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryReportingProgressByCycle(QueryCriteria $criteria, $gibbonReportingCycleID)
    {
        // COURSES
        $query = $this
            ->newQuery()
            ->cols(['gibbonReportingScope.gibbonReportingScopeID AS gibbonReportingScopeID', 'gibbonReportingScope.sequenceNumber AS sequenceNumber', 'gibbonReportingScope.name', "COUNT(DISTINCT gibbonCourseClassPerson.gibbonCourseClassPersonID) as totalCount", "COUNT(DISTINCT CASE WHEN gibbonReportingProgress.status='Complete' THEN gibbonReportingProgress.gibbonReportingProgressID END) as progressCount"])
            ->from('gibbonReportingCycle')
            ->innerJoin('gibbonReportingScope', 'gibbonReportingScope.gibbonReportingCycleID=gibbonReportingCycle.gibbonReportingCycleID')
            ->innerJoin('gibbonReportingAccess', 'FIND_IN_SET(gibbonReportingScope.gibbonReportingScopeID, gibbonReportingAccess.gibbonReportingScopeIDList)')
            ->innerJoin('gibbonReportingCriteria', 'gibbonReportingCriteria.gibbonReportingScopeID=gibbonReportingScope.gibbonReportingScopeID')
            ->innerJoin('gibbonCourseClass', 'gibbonCourseClass.gibbonCourseID=gibbonReportingCriteria.gibbonCourseID')
            ->innerJoin('gibbonCourseClassPerson', "gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID")
            ->innerJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID')
            ->leftJoin('gibbonReportingProgress', 'gibbonReportingProgress.gibbonReportingScopeID=gibbonReportingScope.gibbonReportingScopeID AND gibbonReportingProgress.gibbonPersonIDStudent=gibbonCourseClassPerson.gibbonPersonID AND gibbonReportingProgress.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID')
            ->where('gibbonReportingCycle.gibbonReportingCycleID=:gibbonReportingCycleID')
            ->bindValue('gibbonReportingCycleID', $gibbonReportingCycleID)
            ->where("gibbonReportingScope.scopeType='Course'")
            ->where("gibbonReportingCriteria.target='Per Student'")
            ->where("gibbonCourseClass.reportable='Y'")
            ->where("gibbonCourseClassPerson.reportable='Y'")
            ->where("gibbonCourseClassPerson.role='Student'")
            ->where("gibbonPerson.status='Full'")
            ->where("(gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart<=:today)")
            ->where("(gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd>=:today)")
            ->bindValue('today', date('Y-m-d'))
            ->groupBy(['gibbonReportingScopeID']);

        // FORM GROUPS
        $query->unionAll()
            ->cols(['gibbonReportingScope.gibbonReportingScopeID AS gibbonReportingScopeID', 'gibbonReportingScope.sequenceNumber AS sequenceNumber', 'gibbonReportingScope.name', "COUNT(DISTINCT gibbonStudentEnrolment.gibbonStudentEnrolmentID) as totalCount", "COUNT(DISTINCT CASE WHEN gibbonReportingProgress.status='Complete' THEN gibbonReportingProgress.gibbonReportingProgressID END) as progressCount"])
            ->from('gibbonReportingCycle')
            ->innerJoin('gibbonReportingScope', 'gibbonReportingScope.gibbonReportingCycleID=gibbonReportingCycle.gibbonReportingCycleID')
            ->innerJoin('gibbonReportingAccess', 'FIND_IN_SET(gibbonReportingScope.gibbonReportingScopeID, gibbonReportingAccess.gibbonReportingScopeIDList)')
            ->innerJoin('gibbonReportingCriteria', 'gibbonReportingCriteria.gibbonReportingScopeID=gibbonReportingScope.gibbonReportingScopeID')
            ->innerJoin('gibbonFormGroup', 'gibbonFormGroup.gibbonFormGroupID=gibbonReportingCriteria.gibbonFormGroupID')
            ->innerJoin('gibbonStudentEnrolment', 'gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID AND gibbonStudentEnrolment.gibbonSchoolYearID=gibbonReportingCycle.gibbonSchoolYearID')
            ->innerJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID')
            ->leftJoin('gibbonReportingProgress', 'gibbonReportingProgress.gibbonReportingScopeID=gibbonReportingScope.gibbonReportingScopeID AND gibbonReportingProgress.gibbonPersonIDStudent=gibbonStudentEnrolment.gibbonPersonID AND gibbonReportingProgress.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID')
            ->where('gibbonReportingCycle.gibbonReportingCycleID=:gibbonReportingCycleID')
            ->bindValue('gibbonReportingCycleID', $gibbonReportingCycleID)
            ->where("gibbonReportingScope.scopeType='Form Group'")
            ->where("gibbonReportingCriteria.target='Per Student'")
            ->where("gibbonPerson.status='Full'")
            ->where("(gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart<=:today)")
            ->where("(gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd>=:today)")
            ->bindValue('today', date('Y-m-d'))
            ->groupBy(['gibbonReportingScopeID']);


        // YEAR GROUPS
        $query->unionAll()
            ->cols(['gibbonReportingScope.gibbonReportingScopeID AS gibbonReportingScopeID', 'gibbonReportingScope.sequenceNumber AS sequenceNumber', 'gibbonReportingScope.name', "COUNT(DISTINCT gibbonStudentEnrolment.gibbonStudentEnrolmentID) as totalCount", "COUNT(DISTINCT CASE WHEN gibbonReportingProgress.status='Complete' THEN gibbonReportingProgress.gibbonReportingProgressID END) as progressCount"])
            ->from('gibbonReportingCycle')
            ->innerJoin('gibbonReportingScope', 'gibbonReportingScope.gibbonReportingCycleID=gibbonReportingCycle.gibbonReportingCycleID')
            ->innerJoin('gibbonReportingAccess', 'FIND_IN_SET(gibbonReportingScope.gibbonReportingScopeID, gibbonReportingAccess.gibbonReportingScopeIDList)')
            ->innerJoin('gibbonReportingCriteria', 'gibbonReportingCriteria.gibbonReportingScopeID=gibbonReportingScope.gibbonReportingScopeID')
            ->innerJoin('gibbonYearGroup', 'gibbonYearGroup.gibbonYearGroupID=gibbonReportingCriteria.gibbonYearGroupID')
            ->innerJoin('gibbonStudentEnrolment', 'gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID AND gibbonStudentEnrolment.gibbonSchoolYearID=gibbonReportingCycle.gibbonSchoolYearID')
            ->innerJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID')
            ->leftJoin('gibbonReportingProgress', 'gibbonReportingProgress.gibbonReportingScopeID=gibbonReportingScope.gibbonReportingScopeID AND gibbonReportingProgress.gibbonPersonIDStudent=gibbonStudentEnrolment.gibbonPersonID AND gibbonReportingProgress.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID')
            ->where('gibbonReportingCycle.gibbonReportingCycleID=:gibbonReportingCycleID')
            ->bindValue('gibbonReportingCycleID', $gibbonReportingCycleID)
            ->where("gibbonReportingScope.scopeType='Year Group'")
            ->where("gibbonReportingCriteria.target='Per Student'")
            ->where("gibbonPerson.status='Full'")
            ->where("(gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart<=:today)")
            ->where("(gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd>=:today)")
            ->bindValue('today', date('Y-m-d'))
            ->groupBy(['gibbonReportingScopeID']);

        return $this->runQuery($query, $criteria);
    }

    public function queryReportingProgressByPerson(QueryCriteria $criteria, $gibbonSchoolYearID, $gibbonReportingCycleID = null, $gibbonReportingScopeID = null)
    {
        // COURSES
        $query = $this
            ->newQuery()
            ->cols(['teacher.gibbonPersonID AS gibbonPersonID', 'teacher.surname', 'teacher.preferredName', "COUNT(DISTINCT studentClass.gibbonCourseClassPersonID) as totalCount", "COUNT(DISTINCT CASE WHEN gibbonReportingProgress.status='Complete' THEN gibbonReportingProgress.gibbonReportingProgressID END) as progressCount"])
            ->from('gibbonReportingCycle')
            ->innerJoin('gibbonReportingScope', 'gibbonReportingScope.gibbonReportingCycleID=gibbonReportingCycle.gibbonReportingCycleID')
            ->innerJoin('gibbonReportingAccess', 'FIND_IN_SET(gibbonReportingScope.gibbonReportingScopeID, gibbonReportingAccess.gibbonReportingScopeIDList)')
            ->innerJoin('gibbonReportingCriteria', 'gibbonReportingCriteria.gibbonReportingScopeID=gibbonReportingScope.gibbonReportingScopeID')
            ->innerJoin('gibbonCourseClass', 'gibbonCourseClass.gibbonCourseID=gibbonReportingCriteria.gibbonCourseID')
            ->innerJoin('gibbonCourseClassPerson as studentClass', "studentClass.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID AND studentClass.role='Student'")
            ->innerJoin('gibbonPerson as student', 'student.gibbonPersonID=studentClass.gibbonPersonID')
            ->innerJoin('gibbonCourseClassPerson as teacherClass', "teacherClass.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID AND teacherClass.role='Teacher'")
            ->innerJoin('gibbonPerson as teacher', 'teacher.gibbonPersonID=teacherClass.gibbonPersonID')
            ->leftJoin('gibbonReportingProgress', 'gibbonReportingProgress.gibbonReportingScopeID=gibbonReportingScope.gibbonReportingScopeID AND gibbonReportingProgress.gibbonPersonIDStudent=studentClass.gibbonPersonID AND gibbonReportingProgress.gibbonCourseClassID=studentClass.gibbonCourseClassID')
            ->where('gibbonReportingCycle.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->where("gibbonReportingScope.scopeType='Course'")
            ->where("gibbonReportingCriteria.target='Per Student'")
            ->where("gibbonCourseClass.reportable='Y'")
            ->where("studentClass.reportable='Y'")
            ->where("teacherClass.reportable='Y'")
            ->where("student.status='Full'")
            ->where("(student.dateStart IS NULL OR student.dateStart<=:today)")
            ->where("(student.dateEnd IS NULL OR student.dateEnd>=:today)")
            ->bindValue('today', date('Y-m-d'))
            // ->where('FIND_IN_SET(teacher.gibbonRoleIDPrimary, gibbonReportingAccess.gibbonRoleIDList)')
            ->groupBy(['gibbonPersonID']);

        if ($gibbonReportingCycleID) {
            $query->where('gibbonReportingCycle.gibbonReportingCycleID=:gibbonReportingCycleID', ['gibbonReportingCycleID' => $gibbonReportingCycleID]);
        }
        if ($gibbonReportingScopeID) {
            $query->where('gibbonReportingScope.gibbonReportingScopeID=:gibbonReportingScopeID', ['gibbonReportingScopeID' => $gibbonReportingScopeID]);
        }
        if (empty($gibbonReportingCycleID) && empty($gibbonReportingScopeID)) {
            $query->where(':today BETWEEN gibbonReportingCycle.dateStart AND gibbonReportingCycle.dateEnd', ['today' => date('Y-m-d')]);
        }

        // FORM GROUPS
        $query->unionAll()
            ->cols(['teacher.gibbonPersonID AS gibbonPersonID', 'teacher.surname', 'teacher.preferredName', "COUNT(DISTINCT student.gibbonPersonID) as totalCount", "COUNT(DISTINCT CASE WHEN gibbonReportingProgress.status='Complete' THEN gibbonReportingProgress.gibbonReportingProgressID END) as progressCount"])
            ->from('gibbonReportingCycle')
            ->innerJoin('gibbonReportingScope', 'gibbonReportingScope.gibbonReportingCycleID=gibbonReportingCycle.gibbonReportingCycleID')
            ->innerJoin('gibbonReportingAccess', 'FIND_IN_SET(gibbonReportingScope.gibbonReportingScopeID, gibbonReportingAccess.gibbonReportingScopeIDList)')
            ->innerJoin('gibbonReportingCriteria', 'gibbonReportingCriteria.gibbonReportingScopeID=gibbonReportingScope.gibbonReportingScopeID')
            ->innerJoin('gibbonFormGroup', 'gibbonFormGroup.gibbonFormGroupID=gibbonReportingCriteria.gibbonFormGroupID')
            ->innerJoin('gibbonStudentEnrolment', 'gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID')
            ->innerJoin('gibbonPerson as student', 'student.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID')
            ->innerJoin('gibbonPerson as teacher', '(teacher.gibbonPersonID=gibbonFormGroup.gibbonPersonIDTutor OR teacher.gibbonPersonID=gibbonFormGroup.gibbonPersonIDTutor2 OR teacher.gibbonPersonID=gibbonFormGroup.gibbonPersonIDTutor3)')
            ->leftJoin('gibbonReportingProgress', 'gibbonReportingProgress.gibbonReportingScopeID=gibbonReportingScope.gibbonReportingScopeID AND gibbonReportingProgress.gibbonPersonIDStudent=gibbonStudentEnrolment.gibbonPersonID AND gibbonReportingProgress.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID')
            ->where('gibbonReportingCycle.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->where("gibbonReportingScope.scopeType='Form Group'")
            ->where("gibbonReportingCriteria.target='Per Student'")
            ->where("student.status='Full'")
            ->where("(student.dateStart IS NULL OR student.dateStart<=:today)")
            ->where("(student.dateEnd IS NULL OR student.dateEnd>=:today)")
            ->bindValue('today', date('Y-m-d'))
            // ->where('FIND_IN_SET(teacher.gibbonRoleIDPrimary, gibbonReportingAccess.gibbonRoleIDList)')
            ->groupBy(['gibbonPersonID']);

        if ($gibbonReportingCycleID) {
            $query->where('gibbonReportingCycle.gibbonReportingCycleID=:gibbonReportingCycleID', ['gibbonReportingCycleID' => $gibbonReportingCycleID]);
        }
        if ($gibbonReportingScopeID) {
            $query->where('gibbonReportingScope.gibbonReportingScopeID=:gibbonReportingScopeID', ['gibbonReportingScopeID' => $gibbonReportingScopeID]);
        }
        if (empty($gibbonReportingCycleID) && empty($gibbonReportingScopeID)) {
            $query->where(':today BETWEEN gibbonReportingCycle.dateStart AND gibbonReportingCycle.dateEnd', ['today' => date('Y-m-d')]);
        }

        // YEAR GROUPS
        $query->unionAll()
            ->cols(['teacher.gibbonPersonID AS gibbonPersonID', 'teacher.surname', 'teacher.preferredName', "COUNT(DISTINCT student.gibbonPersonID) as totalCount", "COUNT(DISTINCT CASE WHEN gibbonReportingProgress.status='Complete' THEN gibbonReportingProgress.gibbonReportingProgressID END) as progressCount"])
            ->from('gibbonReportingCycle')
            ->innerJoin('gibbonReportingScope', 'gibbonReportingScope.gibbonReportingCycleID=gibbonReportingCycle.gibbonReportingCycleID')
            ->innerJoin('gibbonReportingAccess', 'FIND_IN_SET(gibbonReportingScope.gibbonReportingScopeID, gibbonReportingAccess.gibbonReportingScopeIDList)')
            ->innerJoin('gibbonReportingCriteria', 'gibbonReportingCriteria.gibbonReportingScopeID=gibbonReportingScope.gibbonReportingScopeID')
            ->innerJoin('gibbonYearGroup', 'gibbonYearGroup.gibbonYearGroupID=gibbonReportingCriteria.gibbonYearGroupID')
            ->innerJoin('gibbonStudentEnrolment', 'gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID')
            ->innerJoin('gibbonPerson as student', 'student.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID')
            ->innerJoin('gibbonPerson as teacher', 'teacher.gibbonPersonID=gibbonYearGroup.gibbonPersonIDHOY')
            ->leftJoin('gibbonReportingProgress', 'gibbonReportingProgress.gibbonReportingScopeID=gibbonReportingScope.gibbonReportingScopeID AND gibbonReportingProgress.gibbonPersonIDStudent=gibbonStudentEnrolment.gibbonPersonID AND gibbonReportingProgress.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID')
            ->where('gibbonReportingCycle.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->where("gibbonReportingScope.scopeType='Year Group'")
            ->where("gibbonReportingCriteria.target='Per Student'")
            ->where("student.status='Full'")
            ->where("(student.dateStart IS NULL OR student.dateStart<=:today)")
            ->where("(student.dateEnd IS NULL OR student.dateEnd>=:today)")
            ->bindValue('today', date('Y-m-d'))
            // ->where('FIND_IN_SET(teacher.gibbonRoleIDPrimary, gibbonReportingAccess.gibbonRoleIDList)')
            ->groupBy(['gibbonPersonID']);

        if ($gibbonReportingCycleID) {
            $query->where('gibbonReportingCycle.gibbonReportingCycleID=:gibbonReportingCycleID', ['gibbonReportingCycleID' => $gibbonReportingCycleID]);
        }
        if ($gibbonReportingScopeID) {
            $query->where('gibbonReportingScope.gibbonReportingScopeID=:gibbonReportingScopeID', ['gibbonReportingScopeID' => $gibbonReportingScopeID]);
        }
        if (empty($gibbonReportingCycleID) && empty($gibbonReportingScopeID)) {
            $query->where(':today BETWEEN gibbonReportingCycle.dateStart AND gibbonReportingCycle.dateEnd', ['today' => date('Y-m-d')]);
        }

        return $this->runQuery($query, $criteria);
    }

    public function queryProofReadingProgressByScope(QueryCriteria $criteria, $gibbonReportingScopeID, $scopeType = 'Year Group')
    {
        // COURSES
        if ($scopeType == 'Course') {
            $query = $this
                ->newQuery()
                ->cols(['gibbonCourseClass.gibbonCourseClassID', 'gibbonReportingCriteria.sequenceNumber', "CONCAT(gibbonCourse.nameShort, '.', gibbonCourseClass.nameShort) as name", "COUNT(DISTINCT gibbonReportingValue.gibbonReportingValueID) as totalCount", "COUNT(DISTINCT CASE WHEN gibbonReportingProof.status='Done' OR gibbonReportingProof.status='Accepted' THEN gibbonReportingProof.gibbonReportingProofID END) as progressCount", "COUNT(DISTINCT CASE WHEN gibbonReportingProof.status='Edited' THEN gibbonReportingProof.gibbonReportingProofID END) as partialCount"])
                ->from('gibbonReportingScope')
                ->innerJoin('gibbonReportingCriteria', 'gibbonReportingCriteria.gibbonReportingScopeID=gibbonReportingScope.gibbonReportingScopeID')
                ->innerJoin('gibbonReportingCriteriaType', 'gibbonReportingCriteriaType.gibbonReportingCriteriaTypeID=gibbonReportingCriteria.gibbonReportingCriteriaTypeID')
                ->innerJoin('gibbonCourseClass', 'gibbonCourseClass.gibbonCourseID=gibbonReportingCriteria.gibbonCourseID')
                ->innerJoin('gibbonCourse', 'gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID')
                ->innerJoin('gibbonReportingValue', 'gibbonReportingValue.gibbonReportingCriteriaID=gibbonReportingCriteria.gibbonReportingCriteriaID AND gibbonReportingValue.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID')
                ->innerJoin('gibbonReportingProgress', 'gibbonReportingProgress.gibbonReportingScopeID=gibbonReportingCriteria.gibbonReportingScopeID AND gibbonReportingProgress.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID AND gibbonReportingProgress.gibbonPersonIDStudent=gibbonReportingValue.gibbonPersonIDStudent')
                ->leftJoin('gibbonReportingProof', 'gibbonReportingProof.gibbonReportingValueID=gibbonReportingValue.gibbonReportingValueID')
                ->where('gibbonReportingScope.gibbonReportingScopeID=:gibbonReportingScopeID')
                ->bindValue('gibbonReportingScopeID', $gibbonReportingScopeID)
                ->where("gibbonReportingScope.scopeType='Course'")
                ->where("gibbonReportingCriteria.target='Per Student'")
                ->where("gibbonReportingCriteriaType.valueType='Comment'")
                ->where("gibbonReportingProgress.status='Complete'")
                ->where("gibbonCourseClass.reportable='Y'")
                ->groupBy(['gibbonCourseClass.gibbonCourseClassID']);

        } else if ($scopeType == 'Form Group') {
            $query = $this
                ->newQuery()
                ->cols(['gibbonFormGroup.gibbonFormGroupID', 'gibbonReportingCriteria.sequenceNumber', "gibbonFormGroup.name", "COUNT(DISTINCT gibbonReportingValue.gibbonReportingValueID) as totalCount", "COUNT(DISTINCT CASE WHEN gibbonReportingProof.status='Done' OR gibbonReportingProof.status='Accepted' THEN gibbonReportingProof.gibbonReportingProofID END) as progressCount", "COUNT(DISTINCT CASE WHEN gibbonReportingProof.status='Edited' THEN gibbonReportingProof.gibbonReportingProofID END) as partialCount"])
                ->from('gibbonReportingScope')
                ->innerJoin('gibbonReportingCriteria', 'gibbonReportingCriteria.gibbonReportingScopeID=gibbonReportingScope.gibbonReportingScopeID')
                ->innerJoin('gibbonReportingCriteriaType', 'gibbonReportingCriteriaType.gibbonReportingCriteriaTypeID=gibbonReportingCriteria.gibbonReportingCriteriaTypeID')
                ->innerJoin('gibbonFormGroup', 'gibbonFormGroup.gibbonFormGroupID=gibbonReportingCriteria.gibbonFormGroupID')
                ->innerJoin('gibbonReportingValue', 'gibbonReportingValue.gibbonReportingCriteriaID=gibbonReportingCriteria.gibbonReportingCriteriaID')
                ->innerJoin('gibbonReportingProgress', 'gibbonReportingProgress.gibbonReportingScopeID=gibbonReportingCriteria.gibbonReportingScopeID AND gibbonReportingProgress.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID AND gibbonReportingProgress.gibbonPersonIDStudent=gibbonReportingValue.gibbonPersonIDStudent')
                ->leftJoin('gibbonReportingProof', 'gibbonReportingProof.gibbonReportingValueID=gibbonReportingValue.gibbonReportingValueID')
                ->where('gibbonReportingScope.gibbonReportingScopeID=:gibbonReportingScopeID')
                ->bindValue('gibbonReportingScopeID', $gibbonReportingScopeID)
                ->where("gibbonReportingScope.scopeType='Form Group'")
                ->where("gibbonReportingCriteria.target='Per Student'")
                ->where("gibbonReportingCriteriaType.valueType='Comment'")
                ->where("gibbonReportingProgress.status='Complete'")
                ->groupBy(['gibbonFormGroup.gibbonFormGroupID']);

        } else if ($scopeType == 'Year Group') {
            $query = $this
                ->newQuery()
                ->cols(['gibbonYearGroup.gibbonYearGroupID', 'gibbonReportingCriteria.sequenceNumber', "gibbonYearGroup.name", "COUNT(DISTINCT gibbonReportingValue.gibbonReportingValueID) as totalCount", "COUNT(DISTINCT CASE WHEN gibbonReportingProof.status='Done' OR gibbonReportingProof.status='Accepted' THEN gibbonReportingProof.gibbonReportingProofID END) as progressCount", "COUNT(DISTINCT CASE WHEN gibbonReportingProof.status='Edited' THEN gibbonReportingProof.gibbonReportingProofID END) as partialCount"])
                ->from('gibbonReportingScope')
                ->innerJoin('gibbonReportingCriteria', 'gibbonReportingCriteria.gibbonReportingScopeID=gibbonReportingScope.gibbonReportingScopeID')
                ->innerJoin('gibbonReportingCriteriaType', 'gibbonReportingCriteriaType.gibbonReportingCriteriaTypeID=gibbonReportingCriteria.gibbonReportingCriteriaTypeID')
                ->innerJoin('gibbonYearGroup', 'gibbonYearGroup.gibbonYearGroupID=gibbonReportingCriteria.gibbonYearGroupID')
                ->innerJoin('gibbonReportingValue', 'gibbonReportingValue.gibbonReportingCriteriaID=gibbonReportingCriteria.gibbonReportingCriteriaID')
                ->innerJoin('gibbonReportingProgress', 'gibbonReportingProgress.gibbonReportingScopeID=gibbonReportingCriteria.gibbonReportingScopeID AND gibbonReportingProgress.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID AND gibbonReportingProgress.gibbonPersonIDStudent=gibbonReportingValue.gibbonPersonIDStudent')
                ->leftJoin('gibbonReportingProof', 'gibbonReportingProof.gibbonReportingValueID=gibbonReportingValue.gibbonReportingValueID')
                ->where('gibbonReportingScope.gibbonReportingScopeID=:gibbonReportingScopeID')
                ->bindValue('gibbonReportingScopeID', $gibbonReportingScopeID)
                ->where("gibbonReportingScope.scopeType='Year Group'")
                ->where("gibbonReportingCriteria.target='Per Student'")
                ->where("gibbonReportingCriteriaType.valueType='Comment'")
                ->where("gibbonReportingProgress.status='Complete'")
                ->groupBy(['gibbonYearGroup.gibbonYearGroupID']);
        }

        $criteria->addFilterRules([
            'reportingCycle' => function ($query, $gibbonReportingCycleID) {
                return $query
                    ->where('gibbonReportingScope.gibbonReportingCycleID = :gibbonReportingCycleID')
                    ->bindValue('gibbonReportingCycleID', $gibbonReportingCycleID);
            },
        ]);

        return $this->runQuery($query, $criteria);
    }

}
