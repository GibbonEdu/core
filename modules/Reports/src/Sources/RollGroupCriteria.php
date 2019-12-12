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

namespace Gibbon\Module\Reports\Sources;

use Gibbon\Module\Reports\DataSource;

class RollGroupCriteria extends DataSource
{
    public function getSchema()
    {
        return [
            0 => [
                'criteriaName'        => ['words', 3, true],
                'criteriaDescription' => ['sentence'],
                'value'               => ['randomDigit'],
                'comment'             => ['paragraph', 6],
                'valueType'           => 'Comment',
            ],
        ];
    }

    public function getData($ids = [])
    {
        $data = ['gibbonStudentEnrolmentID' => $ids['gibbonStudentEnrolmentID'], 'gibbonReportingCycleID' => $ids['gibbonReportingCycleID']];
        $sql = "SELECT gibbonReportingCriteria.name as criteriaName, gibbonReportingCriteria.description as criteriaDescription, gibbonReportingValue.value, gibbonReportingValue.comment, gibbonReportingCriteriaType.valueType
                FROM gibbonStudentEnrolment 
                JOIN gibbonReportingProgress ON (gibbonReportingProgress.gibbonPersonIDStudent=gibbonStudentEnrolment.gibbonPersonID)
                JOIN gibbonReportingValue ON (gibbonReportingValue.gibbonPersonIDStudent=gibbonReportingProgress.gibbonPersonIDStudent)
                JOIN gibbonReportingCriteria ON (gibbonReportingCriteria.gibbonReportingCriteriaID=gibbonReportingValue.gibbonReportingCriteriaID)
                JOIN gibbonReportingCriteriaType ON (gibbonReportingCriteriaType.gibbonReportingCriteriaTypeID=gibbonReportingCriteria.gibbonReportingCriteriaTypeID)
                JOIN gibbonReportingScope ON (gibbonReportingScope.gibbonReportingScopeID=gibbonReportingCriteria.gibbonReportingScopeID)
                WHERE gibbonStudentEnrolment.gibbonStudentEnrolmentID=:gibbonStudentEnrolmentID
                AND gibbonReportingValue.gibbonReportingCycleID=:gibbonReportingCycleID
                AND gibbonReportingProgress.gibbonCourseClassID=0
                AND gibbonReportingProgress.status='Complete'
                AND gibbonReportingScope.scopeType='Roll Group'
                AND gibbonReportingCriteria.target='Per Student'
                ORDER BY gibbonReportingScope.sequenceNumber, gibbonReportingCriteria.sequenceNumber";

        return $this->db()->select($sql, $data)->fetchAll();
    }
}
