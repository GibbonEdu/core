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

class ReportingValueGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonReportingValue';
    private static $primaryKey = 'gibbonReportingValueID';
    private static $searchableColumns = [''];


    public function getGradeScaleValueByID($gibbonScaleGradeID)
    {
        $data = ['gibbonScaleGradeID' => $gibbonScaleGradeID];
        $sql = "SELECT value FROM gibbonScaleGrade WHERE gibbonScaleGradeID=:gibbonScaleGradeID";

        return $this->db()->selectOne($sql, $data);
    }

    public function getGradeScaleIDByValue($gibbonScaleID, $value)
    {
        $data = ['gibbonScaleID' => $gibbonScaleID, 'value' => $value];
        $sql = "SELECT gibbonScaleGradeID FROM gibbonScaleGrade WHERE gibbonScaleID=:gibbonScaleID AND value=:value";

        return $this->db()->selectOne($sql, $data);
    }

    public function selectReportingCommentsByCycle($gibbonReportingCycleID)
    {
        $data = ['gibbonReportingCycleID' => $gibbonReportingCycleID];
        $sql = "SELECT gibbonReportingValue.gibbonReportingValueID, gibbonReportingValue.comment, gibbonPerson.gibbonPersonID, gibbonPerson.preferredName, gibbonPerson.surname, gibbonReportingScope.scopeType, (CASE WHEN scopeType='Course' THEN gibbonReportingValue.gibbonCourseClassID WHEN scopeType='Form Group' THEN gibbonReportingCriteria.gibbonFormGroupID WHEN scopeType='Year Group' THEN gibbonReportingCriteria.gibbonYearGroupID END) as scopeTypeID, gibbonReportingScope.gibbonReportingScopeID, gibbonReportingCriteria.name as criteriaName
                FROM gibbonReportingCycle
                JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonSchoolYearID=gibbonReportingCycle.gibbonSchoolYearID)
                JOIN gibbonPerson ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID)
                JOIN gibbonReportingValue ON (gibbonReportingValue.gibbonReportingCycleID=gibbonReportingCycle.gibbonReportingCycleID AND gibbonReportingValue.gibbonPersonIDStudent=gibbonStudentEnrolment.gibbonPersonID)
                JOIN gibbonReportingCriteria ON (gibbonReportingCriteria.gibbonReportingCriteriaID=gibbonReportingValue.gibbonReportingCriteriaID)
                JOIN gibbonReportingCriteriaType ON (gibbonReportingCriteriaType.gibbonReportingCriteriaTypeID=gibbonReportingCriteria.gibbonReportingCriteriaTypeID)
                JOIN gibbonReportingScope ON (gibbonReportingScope.gibbonReportingScopeID=gibbonReportingCriteria.gibbonReportingScopeID)
                WHERE gibbonReportingCriteria.gibbonReportingCycleID=:gibbonReportingCycleID
                AND gibbonReportingCriteriaType.valueType='Comment'
                AND gibbonReportingCriteria.target = 'Per Student'
                AND (gibbonReportingValue.comment IS NOT NULL AND gibbonReportingValue.comment <> '')
                ORDER BY gibbonPerson.surname, gibbonPerson.preferredName, gibbonReportingScope.sequenceNumber, gibbonReportingCriteria.sequenceNumber";

        return $this->db()->select($sql, $data);
    }
}
