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

class ReportingProofGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonReportingProof';
    private static $primaryKey = 'gibbonReportingProofID';
    private static $searchableColumns = [''];

    public function selectProofReadingScopes($gibbonSchoolYearID)
    {
        $query = $this
            ->newSelect()
            ->cols(['gibbonReportingScope.gibbonReportingScopeID AS gibbonReportingScopeID', 'gibbonReportingScope.name as scopeName', 'gibbonReportingScope.scopeType', 'gibbonReportingCycle.name as cycleName', 'gibbonReportingCycle.nameShort as cycleNameShort'])
            ->from('gibbonReportingCycle')
            ->innerJoin('gibbonReportingScope', 'gibbonReportingScope.gibbonReportingCycleID=gibbonReportingCycle.gibbonReportingCycleID')
            ->innerJoin('gibbonReportingCriteria', 'gibbonReportingCriteria.gibbonReportingScopeID=gibbonReportingScope.gibbonReportingScopeID')
            ->innerJoin('gibbonReportingCriteriaType', 'gibbonReportingCriteriaType.gibbonReportingCriteriaTypeID=gibbonReportingCriteria.gibbonReportingCriteriaTypeID')
            ->where('gibbonReportingCycle.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->where(':today BETWEEN gibbonReportingCycle.dateStart AND gibbonReportingCycle.dateEnd')
            ->bindValue('today', date('Y-m-d'))
            ->where("gibbonReportingCriteriaType.valueType='Comment'")
            ->where("gibbonReportingCriteria.target='Per Student'")
            ->groupBy(['gibbonReportingScopeID'])
            ->orderBy(['gibbonReportingCycle.sequenceNumber', 'gibbonReportingScope.sequenceNumber']);

        return $this->runSelect($query);
    }

    public function queryProofReadingByFormGroup($criteria, $gibbonSchoolYearID, $gibbonFormGroupID)
    {
        $criteria->addFilterRules($this->getSharedFilterRules());

        // COURSES
        $query = $this
            ->newQuery()
            ->from('gibbonReportingCycle')
            ->cols(['gibbonReportingValue.gibbonPersonIDStudent', 'gibbonReportingValue.gibbonReportingValueID', 'gibbonReportingCriteria.target as criteriaTarget', 'gibbonReportingCriteria.name as criteriaName', 'gibbonReportingCriteriaType.characterLimit', 'gibbonCourse.name', "CONCAT(gibbonCourse.nameShort, '.', gibbonCourseClass.nameShort) as nameShort", 'gibbonReportingValue.comment', 'student.surname', 'student.preferredName', 'student.gender', 'writtenBy.surname as surnameWrittenBy',  'writtenBy.preferredName as preferredNameWrittenBy', 'gibbonReportingScope.name AS "scopeName"' ])
            ->innerJoin('gibbonStudentEnrolment', 'gibbonReportingCycle.gibbonSchoolYearID=gibbonStudentEnrolment.gibbonSchoolYearID')
            ->innerJoin('gibbonPerson as student', 'student.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID')
            ->innerJoin('gibbonReportingValue', 'student.gibbonPersonID=gibbonReportingValue.gibbonPersonIDStudent AND gibbonReportingValue.gibbonReportingCycleID=gibbonReportingCycle.gibbonReportingCycleID')
            ->innerJoin('gibbonReportingCriteria', 'gibbonReportingValue.gibbonReportingCriteriaID=gibbonReportingCriteria.gibbonReportingCriteriaID')
            ->innerJoin('gibbonReportingCriteriaType', 'gibbonReportingCriteriaType.gibbonReportingCriteriaTypeID=gibbonReportingCriteria.gibbonReportingCriteriaTypeID')
            ->innerJoin('gibbonReportingScope', 'gibbonReportingScope.gibbonReportingScopeID=gibbonReportingCriteria.gibbonReportingScopeID')
            ->innerJoin('gibbonReportingProgress', 'gibbonReportingProgress.gibbonReportingScopeID=gibbonReportingCriteria.gibbonReportingScopeID AND gibbonReportingProgress.gibbonCourseClassID=gibbonReportingValue.gibbonCourseClassID AND gibbonReportingProgress.gibbonPersonIDStudent=gibbonReportingValue.gibbonPersonIDStudent')
            ->innerJoin('gibbonCourseClass', 'gibbonCourseClass.gibbonCourseClassID=gibbonReportingValue.gibbonCourseClassID')
            ->innerJoin('gibbonCourse', 'gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID')
            ->innerJoin('gibbonPerson as writtenBy', 'writtenBy.gibbonPersonID=gibbonReportingValue.gibbonPersonIDCreated')
            ->where('gibbonReportingCycle.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->where('gibbonStudentEnrolment.gibbonFormGroupID=:gibbonFormGroupID')
            ->bindValue('gibbonFormGroupID', $gibbonFormGroupID)
            ->where("gibbonReportingProgress.status='Complete'")
            ->where("gibbonReportingCriteriaType.valueType='Comment'")
            ->where("gibbonReportingValue.gibbonCourseClassID <> 0")
            ->where("(gibbonReportingValue.comment <> '' AND gibbonReportingValue.comment IS NOT NULL)")
            ->where('(:today BETWEEN gibbonReportingCycle.dateStart AND gibbonReportingCycle.dateEnd)')
            ->bindValue('today', date('Y-m-d'));

        // FORM GROUP
        $this->unionAllWithCriteria($query, $criteria)
            ->from('gibbonReportingCycle')
            ->cols(['gibbonReportingValue.gibbonPersonIDStudent', 'gibbonReportingValue.gibbonReportingValueID', 'gibbonReportingCriteria.target as criteriaTarget', 'gibbonReportingCriteria.name as criteriaName', 'gibbonReportingCriteriaType.characterLimit', 'gibbonFormGroup.name', 'gibbonFormGroup.nameShort', 'gibbonReportingValue.comment', 'student.surname', 'student.preferredName', 'student.gender', 'writtenBy.surname as surnameWrittenBy',  'writtenBy.preferredName as preferredNameWrittenBy', 'gibbonReportingScope.name AS "scopeName"'])
            ->innerJoin('gibbonStudentEnrolment', 'gibbonReportingCycle.gibbonSchoolYearID=gibbonStudentEnrolment.gibbonSchoolYearID')
            ->innerJoin('gibbonPerson as student', 'student.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID')
            ->innerJoin('gibbonReportingValue', 'student.gibbonPersonID=gibbonReportingValue.gibbonPersonIDStudent AND gibbonReportingValue.gibbonReportingCycleID=gibbonReportingCycle.gibbonReportingCycleID')
            ->innerJoin('gibbonReportingCriteria', 'gibbonReportingValue.gibbonReportingCriteriaID=gibbonReportingCriteria.gibbonReportingCriteriaID')
            ->innerJoin('gibbonReportingCriteriaType', 'gibbonReportingCriteriaType.gibbonReportingCriteriaTypeID=gibbonReportingCriteria.gibbonReportingCriteriaTypeID')
            ->innerJoin('gibbonReportingScope', 'gibbonReportingScope.gibbonReportingScopeID=gibbonReportingCriteria.gibbonReportingScopeID')
            ->innerJoin('gibbonFormGroup', 'gibbonFormGroup.gibbonFormGroupID=gibbonReportingCriteria.gibbonFormGroupID')
            ->innerJoin('gibbonReportingProgress', 'gibbonReportingProgress.gibbonReportingScopeID=gibbonReportingCriteria.gibbonReportingScopeID AND gibbonReportingProgress.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID AND gibbonReportingProgress.gibbonPersonIDStudent=gibbonReportingValue.gibbonPersonIDStudent')
            ->innerJoin('gibbonPerson as writtenBy', 'writtenBy.gibbonPersonID=gibbonReportingValue.gibbonPersonIDCreated')
            ->where('gibbonReportingCycle.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->where('gibbonStudentEnrolment.gibbonFormGroupID=:gibbonFormGroupID')
            ->bindValue('gibbonFormGroupID', $gibbonFormGroupID)
            ->where("gibbonReportingProgress.status='Complete'")
            ->where("gibbonReportingCriteriaType.valueType='Comment'")
            ->where("gibbonReportingCriteria.gibbonFormGroupID IS NOT NULL")
            ->where("(gibbonReportingValue.comment <> '' AND gibbonReportingValue.comment IS NOT NULL)")
            ->where('(:today BETWEEN gibbonReportingCycle.dateStart AND gibbonReportingCycle.dateEnd)')
            ->bindValue('today', date('Y-m-d'));

        // YEAR GROUP
        $this->unionAllWithCriteria($query, $criteria)
            ->from('gibbonReportingCycle')
            ->cols(['gibbonReportingValue.gibbonPersonIDStudent', 'gibbonReportingValue.gibbonReportingValueID', 'gibbonReportingCriteria.target as criteriaTarget', 'gibbonReportingCriteria.name as criteriaName', 'gibbonReportingCriteriaType.characterLimit', 'gibbonYearGroup.name', 'gibbonYearGroup.nameShort', 'gibbonReportingValue.comment', 'student.surname', 'student.preferredName', 'student.gender', 'writtenBy.surname as surnameWrittenBy', 'writtenBy.preferredName as preferredNameWrittenBy', 'gibbonReportingScope.name AS "scopeName"'])
            ->innerJoin('gibbonStudentEnrolment', 'gibbonReportingCycle.gibbonSchoolYearID=gibbonStudentEnrolment.gibbonSchoolYearID')
            ->innerJoin('gibbonPerson as student', 'student.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID')
            ->innerJoin('gibbonReportingValue', 'student.gibbonPersonID=gibbonReportingValue.gibbonPersonIDStudent AND gibbonReportingValue.gibbonReportingCycleID=gibbonReportingCycle.gibbonReportingCycleID')
            ->innerJoin('gibbonReportingCriteria', 'gibbonReportingValue.gibbonReportingCriteriaID=gibbonReportingCriteria.gibbonReportingCriteriaID')
            ->innerJoin('gibbonReportingCriteriaType', 'gibbonReportingCriteriaType.gibbonReportingCriteriaTypeID=gibbonReportingCriteria.gibbonReportingCriteriaTypeID')
            ->innerJoin('gibbonReportingScope', 'gibbonReportingScope.gibbonReportingScopeID=gibbonReportingCriteria.gibbonReportingScopeID')
            ->innerJoin('gibbonYearGroup', 'gibbonYearGroup.gibbonYearGroupID=gibbonReportingCriteria.gibbonYearGroupID')
            ->innerJoin('gibbonReportingProgress', 'gibbonReportingProgress.gibbonReportingScopeID=gibbonReportingCriteria.gibbonReportingScopeID AND gibbonReportingProgress.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID AND gibbonReportingProgress.gibbonPersonIDStudent=gibbonReportingValue.gibbonPersonIDStudent')
            ->innerJoin('gibbonPerson as writtenBy', 'writtenBy.gibbonPersonID=gibbonReportingValue.gibbonPersonIDCreated')
            ->where('gibbonReportingCycle.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->where('gibbonStudentEnrolment.gibbonFormGroupID=:gibbonFormGroupID')
            ->bindValue('gibbonFormGroupID', $gibbonFormGroupID)
            ->where("gibbonReportingProgress.status='Complete'")
            ->where("gibbonReportingCriteriaType.valueType='Comment'")
            ->where("gibbonReportingCriteria.gibbonYearGroupID IS NOT NULL")
            ->where("(gibbonReportingValue.comment <> '' AND gibbonReportingValue.comment IS NOT NULL)")
            ->where('(:today BETWEEN gibbonReportingCycle.dateStart AND gibbonReportingCycle.dateEnd)')
            ->bindValue('today', date('Y-m-d'));

        $query->orderBy(['criteriaTarget', 'surname', 'preferredName', 'nameShort']);

        return $this->runQuery($query, $criteria);

    }

    public function queryProofReadingByPerson($criteria, $gibbonSchoolYearID, $gibbonPersonID, $reportingScopeIDs = null)
    {
        $reportingScopeIDs = is_array($reportingScopeIDs)? implode(',', $reportingScopeIDs) : $reportingScopeIDs;

        $criteria->addFilterRules($this->getSharedFilterRules());

        // COURSES
        $query = $this
            ->newQuery()
            ->from('gibbonPerson')
            ->cols(['gibbonReportingValue.gibbonPersonIDStudent', 'gibbonReportingValue.gibbonReportingValueID', 'gibbonReportingCriteria.target as criteriaTarget', 'gibbonReportingCriteria.name as criteriaName', 'gibbonReportingCriteriaType.characterLimit', 'gibbonCourse.name', "CONCAT(gibbonCourse.nameShort, '.', gibbonCourseClass.nameShort) as nameShort", 'gibbonReportingValue.comment', 'student.surname', 'student.preferredName', 'student.gender', 'gibbonReportingScope.name AS "scopeName"'])
            ->innerJoin('gibbonCourseClassPerson', 'gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID')
            ->innerJoin('gibbonCourseClass', 'gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID')
            ->innerJoin('gibbonCourse', 'gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID')
            ->innerJoin('gibbonReportingCriteria', 'gibbonReportingCriteria.gibbonCourseID=gibbonCourse.gibbonCourseID')
            ->innerJoin('gibbonReportingScope', 'gibbonReportingScope.gibbonReportingScopeID=gibbonReportingCriteria.gibbonReportingScopeID')
            ->innerJoin('gibbonReportingCycle', 'gibbonReportingCycle.gibbonReportingCycleID=gibbonReportingCriteria.gibbonReportingCycleID')
            ->innerJoin('gibbonReportingCriteriaType', 'gibbonReportingCriteriaType.gibbonReportingCriteriaTypeID=gibbonReportingCriteria.gibbonReportingCriteriaTypeID')
            ->innerJoin('gibbonReportingValue', 'gibbonReportingValue.gibbonReportingCriteriaID=gibbonReportingCriteria.gibbonReportingCriteriaID')
            ->innerJoin('gibbonReportingProgress', 'gibbonReportingProgress.gibbonReportingScopeID=gibbonReportingCriteria.gibbonReportingScopeID AND gibbonReportingProgress.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID AND gibbonReportingProgress.gibbonPersonIDStudent=gibbonReportingValue.gibbonPersonIDStudent')
            ->leftJoin('gibbonPerson as student', 'student.gibbonPersonID=gibbonReportingValue.gibbonPersonIDStudent')
            ->where("gibbonReportingProgress.status='Complete'")
            ->where("gibbonReportingCriteriaType.valueType='Comment'")
            ->where("(gibbonReportingValue.comment <> '' AND gibbonReportingValue.comment IS NOT NULL)")
            ->where("gibbonCourseClassPerson.role='Teacher'")
            ->where("gibbonCourseClassPerson.reportable='Y'")
            ->where("gibbonCourseClass.reportable='Y'")
            ->where('gibbonPerson.gibbonPersonID=:gibbonPersonID')
            ->bindValue('gibbonPersonID', $gibbonPersonID)
            ->where('gibbonReportingCycle.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->where('(:today BETWEEN gibbonReportingCycle.dateStart AND gibbonReportingCycle.dateEnd)')
            ->bindValue('today', date('Y-m-d'));

        if (!empty($reportingScopeIDs)) {
            $query->where('FIND_IN_SET(gibbonReportingCriteria.gibbonReportingScopeID, :reportingScopeIDs)', ['reportingScopeIDs' => $reportingScopeIDs]);
        }

        // FORM GROUP
        $this->unionAllWithCriteria($query, $criteria)
            ->from('gibbonPerson')
            ->cols(['gibbonReportingValue.gibbonPersonIDStudent', 'gibbonReportingValue.gibbonReportingValueID', 'gibbonReportingCriteria.target as criteriaTarget', 'gibbonReportingCriteria.name as criteriaName', 'gibbonReportingCriteriaType.characterLimit', 'gibbonFormGroup.name', 'gibbonFormGroup.nameShort', 'gibbonReportingValue.comment', 'student.surname', 'student.preferredName', 'student.gender', 'gibbonReportingScope.name AS "scopeName"'])
            ->innerJoin('gibbonFormGroup', '(gibbonFormGroup.gibbonPersonIDTutor=gibbonPerson.gibbonPersonID OR gibbonFormGroup.gibbonPersonIDTutor2=gibbonPerson.gibbonPersonID OR gibbonFormGroup.gibbonPersonIDTutor3=gibbonPerson.gibbonPersonID)')
            ->innerJoin('gibbonReportingCriteria', 'gibbonReportingCriteria.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID')
            ->innerJoin('gibbonReportingScope', 'gibbonReportingScope.gibbonReportingScopeID=gibbonReportingCriteria.gibbonReportingScopeID')
            ->innerJoin('gibbonReportingCycle', 'gibbonReportingCycle.gibbonReportingCycleID=gibbonReportingCriteria.gibbonReportingCycleID')
            ->innerJoin('gibbonReportingCriteriaType', 'gibbonReportingCriteriaType.gibbonReportingCriteriaTypeID=gibbonReportingCriteria.gibbonReportingCriteriaTypeID')
            ->innerJoin('gibbonReportingValue', 'gibbonReportingValue.gibbonReportingCriteriaID=gibbonReportingCriteria.gibbonReportingCriteriaID')
            ->innerJoin('gibbonReportingProgress', 'gibbonReportingProgress.gibbonReportingScopeID=gibbonReportingCriteria.gibbonReportingScopeID AND gibbonReportingProgress.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID AND gibbonReportingProgress.gibbonPersonIDStudent=gibbonReportingValue.gibbonPersonIDStudent')
            ->leftJoin('gibbonPerson as student', 'student.gibbonPersonID=gibbonReportingValue.gibbonPersonIDStudent')
            ->where("gibbonReportingProgress.status='Complete'")
            ->where("gibbonReportingCriteriaType.valueType='Comment'")
            ->where("(gibbonReportingValue.comment <> '' AND gibbonReportingValue.comment IS NOT NULL)")
            ->where('gibbonPerson.gibbonPersonID=:gibbonPersonID')
            ->bindValue('gibbonPersonID', $gibbonPersonID)
            ->where('gibbonReportingCycle.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->where('(:today BETWEEN gibbonReportingCycle.dateStart AND gibbonReportingCycle.dateEnd)')
            ->bindValue('today', date('Y-m-d'));

        if (!empty($reportingScopeIDs)) {
            $query->where('FIND_IN_SET(gibbonReportingCriteria.gibbonReportingScopeID, :reportingScopeIDs)', ['reportingScopeIDs' => $reportingScopeIDs]);
        }

        // YEAR GROUP
        $this->unionAllWithCriteria($query, $criteria)
            ->from('gibbonPerson')
            ->cols(['gibbonReportingValue.gibbonPersonIDStudent', 'gibbonReportingValue.gibbonReportingValueID', 'gibbonReportingCriteria.target as criteriaTarget', 'gibbonReportingCriteria.name as criteriaName', 'gibbonReportingCriteriaType.characterLimit', 'gibbonYearGroup.name', 'gibbonYearGroup.nameShort', 'gibbonReportingValue.comment', 'student.surname', 'student.preferredName', 'student.gender', 'gibbonReportingScope.name AS "scopeName"'])
            ->innerJoin('gibbonYearGroup', 'gibbonYearGroup.gibbonPersonIDHOY=gibbonPerson.gibbonPersonID')
            ->innerJoin('gibbonReportingCriteria', 'gibbonReportingCriteria.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID')
            ->innerJoin('gibbonReportingScope', 'gibbonReportingScope.gibbonReportingScopeID=gibbonReportingCriteria.gibbonReportingScopeID')
            ->innerJoin('gibbonReportingCycle', 'gibbonReportingCycle.gibbonReportingCycleID=gibbonReportingCriteria.gibbonReportingCycleID')
            ->innerJoin('gibbonReportingCriteriaType', 'gibbonReportingCriteriaType.gibbonReportingCriteriaTypeID=gibbonReportingCriteria.gibbonReportingCriteriaTypeID')
            ->innerJoin('gibbonReportingValue', 'gibbonReportingValue.gibbonReportingCriteriaID=gibbonReportingCriteria.gibbonReportingCriteriaID')
            ->innerJoin('gibbonReportingProgress', 'gibbonReportingProgress.gibbonReportingScopeID=gibbonReportingCriteria.gibbonReportingScopeID AND gibbonReportingProgress.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID AND gibbonReportingProgress.gibbonPersonIDStudent=gibbonReportingValue.gibbonPersonIDStudent')
            ->leftJoin('gibbonPerson as student', 'student.gibbonPersonID=gibbonReportingValue.gibbonPersonIDStudent')
            ->where("gibbonReportingProgress.status='Complete'")
            ->where("gibbonReportingCriteriaType.valueType='Comment'")
            ->where("(gibbonReportingValue.comment <> '' AND gibbonReportingValue.comment IS NOT NULL)")
            ->where('gibbonPerson.gibbonPersonID=:gibbonPersonID')
            ->bindValue('gibbonPersonID', $gibbonPersonID)
            ->where('gibbonReportingCycle.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->where('(:today BETWEEN gibbonReportingCycle.dateStart AND gibbonReportingCycle.dateEnd)')
            ->bindValue('today', date('Y-m-d'));

        if (!empty($reportingScopeIDs)) {
            $query->where('FIND_IN_SET(gibbonReportingCriteria.gibbonReportingScopeID, :reportingScopeIDs)', ['reportingScopeIDs' => $reportingScopeIDs]);
        }

        $query->orderBy(['criteriaTarget', 'nameShort', 'surname', 'preferredName']);

        return $this->runQuery($query, $criteria);
    }

    public function selectPendingProofReadingEdits($gibbonReportingCycleIDList)
    {
        $gibbonReportingCycleIDList = is_array($gibbonReportingCycleIDList)? $gibbonReportingCycleIDList : [$gibbonReportingCycleIDList];

        // COURSES
        $query = $this
            ->newSelect()
            ->from('gibbonReportingProof')
            ->cols(['gibbonPerson.gibbonPersonID AS groupBy', 'gibbonPerson.title', 'gibbonPerson.surname', 'gibbonPerson.preferredName', 'gibbonReportingCycle.name', 'gibbonReportingProof.comment', 'gibbonReportingScope.scopeType'])
            ->innerJoin('gibbonReportingValue', 'gibbonReportingValue.gibbonReportingValueID=gibbonReportingProof.gibbonReportingValueID')
            ->innerJoin('gibbonReportingCycle', 'gibbonReportingCycle.gibbonReportingCycleID=gibbonReportingValue.gibbonReportingCycleID')
            ->innerJoin('gibbonReportingCriteria', 'gibbonReportingCriteria.gibbonReportingCriteriaID=gibbonReportingValue.gibbonReportingCriteriaID')
            ->innerJoin('gibbonReportingScope', 'gibbonReportingScope.gibbonReportingScopeID=gibbonReportingCriteria.gibbonReportingScopeID')
            ->innerJoin('gibbonCourseClassPerson', 'gibbonCourseClassPerson.gibbonCourseClassID=gibbonReportingValue.gibbonCourseClassID')
            ->innerJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID')
            ->where('FIND_IN_SET(gibbonReportingCycle.gibbonReportingCycleID, :gibbonReportingCycleIDList)')
            ->bindValue('gibbonReportingCycleIDList', implode(',', $gibbonReportingCycleIDList))
            ->where("gibbonReportingProof.status='Edited'")
            ->where("gibbonReportingScope.scopeType='Course'")
            ->where("gibbonCourseClassPerson.role='Teacher'")
            ->where("gibbonCourseClassPerson.reportable='Y'");

        // FORM GROUPS
        $query->unionAll()
            ->from('gibbonReportingProof')
            ->cols(['gibbonPerson.gibbonPersonID AS groupBy', 'gibbonPerson.title', 'gibbonPerson.surname', 'gibbonPerson.preferredName', 'gibbonReportingCycle.name', 'gibbonReportingProof.comment', 'gibbonReportingScope.scopeType'])
            ->innerJoin('gibbonReportingValue', 'gibbonReportingValue.gibbonReportingValueID=gibbonReportingProof.gibbonReportingValueID')
            ->innerJoin('gibbonReportingCycle', 'gibbonReportingCycle.gibbonReportingCycleID=gibbonReportingValue.gibbonReportingCycleID')
            ->innerJoin('gibbonReportingCriteria', 'gibbonReportingCriteria.gibbonReportingCriteriaID=gibbonReportingValue.gibbonReportingCriteriaID')
            ->innerJoin('gibbonReportingScope', 'gibbonReportingScope.gibbonReportingScopeID=gibbonReportingCriteria.gibbonReportingScopeID')
            ->innerJoin('gibbonFormGroup', 'gibbonFormGroup.gibbonFormGroupID=gibbonReportingCriteria.gibbonFormGroupID')
            ->innerJoin('gibbonPerson', '(gibbonFormGroup.gibbonPersonIDTutor=gibbonPerson.gibbonPersonID OR gibbonFormGroup.gibbonPersonIDTutor2=gibbonPerson.gibbonPersonID OR gibbonFormGroup.gibbonPersonIDTutor3=gibbonPerson.gibbonPersonID)')
            ->where('FIND_IN_SET(gibbonReportingCycle.gibbonReportingCycleID, :gibbonReportingCycleIDList)')
            ->bindValue('gibbonReportingCycleIDList', implode(',', $gibbonReportingCycleIDList))
            ->where("gibbonReportingProof.status='Edited'")
            ->where("gibbonReportingScope.scopeType='Form Group'");

        // YEAR GROUPS
        $query->unionAll()
            ->from('gibbonReportingProof')
            ->cols(['gibbonPerson.gibbonPersonID AS groupBy', 'gibbonPerson.title', 'gibbonPerson.surname', 'gibbonPerson.preferredName', 'gibbonReportingCycle.name', 'gibbonReportingProof.comment', 'gibbonReportingScope.scopeType'])
            ->innerJoin('gibbonReportingValue', 'gibbonReportingValue.gibbonReportingValueID=gibbonReportingProof.gibbonReportingValueID')
            ->innerJoin('gibbonReportingCycle', 'gibbonReportingCycle.gibbonReportingCycleID=gibbonReportingValue.gibbonReportingCycleID')
            ->innerJoin('gibbonReportingCriteria', 'gibbonReportingCriteria.gibbonReportingCriteriaID=gibbonReportingValue.gibbonReportingCriteriaID')
            ->innerJoin('gibbonReportingScope', 'gibbonReportingScope.gibbonReportingScopeID=gibbonReportingCriteria.gibbonReportingScopeID')
            ->innerJoin('gibbonYearGroup', 'gibbonYearGroup.gibbonYearGroupID=gibbonReportingCriteria.gibbonYearGroupID')
            ->innerJoin('gibbonPerson', 'gibbonYearGroup.gibbonPersonIDHOY=gibbonPerson.gibbonPersonID')
            ->where('FIND_IN_SET(gibbonReportingCycle.gibbonReportingCycleID, :gibbonReportingCycleIDList)')
            ->bindValue('gibbonReportingCycleIDList', implode(',', $gibbonReportingCycleIDList))
            ->where("gibbonReportingProof.status='Edited'")
            ->where("gibbonReportingScope.scopeType='Year Group'");

        $query->orderBy(['surname', 'preferredName']);

        return $this->runSelect($query);
    }

    public function selectProofsByValueID($gibbonReportingValueID)
    {
        $gibbonReportingValueIDList = is_array($gibbonReportingValueID)? $gibbonReportingValueID : [$gibbonReportingValueID];
        $gibbonReportingValueIDList = array_map(function ($item) {
            return str_pad($item, 12, 0, STR_PAD_LEFT);
        }, $gibbonReportingValueIDList);

        $data = ['gibbonReportingValueIDList' => implode(',', $gibbonReportingValueIDList)];
        $sql = "SELECT CAST(gibbonReportingProof.gibbonReportingValueID as UNSIGNED) as groupBy, gibbonReportingProof.*, gibbonPerson.preferredName, gibbonPerson.surname, gibbonPerson.image_240
                FROM gibbonReportingProof 
                JOIN gibbonPerson ON (gibbonPerson.gibbonPersonID=gibbonReportingProof.gibbonPersonIDProofed)
                WHERE FIND_IN_SET(gibbonReportingProof.gibbonReportingValueID, :gibbonReportingValueIDList)
                AND gibbonReportingProof.status <> 'Declined'";

        return $this->db()->select($sql, $data);
    }

    protected function getSharedFilterRules()
    {
        return [
            // 'status' => function ($query, $status) {
            //     return $query->where('gibbonStaffCoverage.status = :status')
            //                  ->bindValue('status', $status);
            // },
            'scopeType' => function ($query, $scopeType) {
                return $query->where('gibbonReportingScope.scopeType = :scopeType')
                             ->bindValue('scopeType', $scopeType);
            },
            'target' => function ($query, $target) {
                return $query->where('gibbonReportingCriteria.target = :target')
                             ->bindValue('target', $target);
            },
            'class' => function ($query, $class) {
                return $query->where('gibbonCourseClass.nameShort = :class')
                             ->bindValue('class', $class);
            },
            'scope' => function ($query, $scope) {
                return $query->where('gibbonReportingScope.name = :scope')
                             ->bindValue('scope', $scope);
            },
        ];
    }
}
