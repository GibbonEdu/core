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

namespace Gibbon\Module\Reports\Sources;

use Gibbon\Module\Reports\DataSource;

class FormGroupCriteria extends DataSource
{
    public function getSchema()
    {
        return [
            'perGroup' => [
                0 => [
                    'scopeName'           => 'Form Group',
                    'criteriaName'        => 'Form Group Comment',
                    'criteriaDescription' => ['sentence'],
                    'value'               => ['randomDigit'],
                    'comment'             => ['paragraph', 6],
                    'valueType'           => 'Comment',
                ],
            ],
            'perStudent' => [
                0 => [
                    'scopeName'           => 'Form Group',
                    'criteriaName'        => 'Student Comment',
                    'criteriaDescription' => ['sentence'],
                    'value'               => ['randomDigit'],
                    'comment'             => ['paragraph', 6],
                    'valueType'           => 'Comment',
                ],
                1 => [
                    'scopeName'           => 'Form Group',
                    'criteriaName'        => 'Effort',
                    'criteriaDescription' => ['sentence'],
                    'value'               => ['randomElement', ['Excellent', 'Very Good', 'Good', 'Satisfactory', 'Needs Improvement']],
                    'comment'             => '',
                    'valueType'           => 'Grade Scale',
                ],
            ],
        ];
    }

    public function getData($ids = [])
    {
        $data = ['gibbonStudentEnrolmentID' => $ids['gibbonStudentEnrolmentID'], 'gibbonReportingCycleID' => $ids['gibbonReportingCycleID']];
        $sql = "SELECT (CASE WHEN gibbonReportingCriteria.target = 'Per Group' THEN 'perGroup' ELSE 'perStudent' END) AS groupBy, 
                    gibbonReportingScope.name as scopeName,
                    gibbonReportingCriteria.name as criteriaName,
                    gibbonReportingCriteria.description as criteriaDescription, 
                    gibbonReportingValue.value, 
                    gibbonReportingValue.comment, 
                    gibbonScaleGrade.descriptor,
                    gibbonReportingCriteriaType.valueType, 
                    gibbonFormGroup.name as formGroupName, 
                    gibbonFormGroup.nameShort as formGroupNameShort
                FROM gibbonStudentEnrolment 
                JOIN gibbonReportingCriteria ON (gibbonReportingCriteria.gibbonFormGroupID=gibbonStudentEnrolment.gibbonFormGroupID)
                JOIN gibbonReportingValue ON (gibbonReportingCriteria.gibbonReportingCriteriaID=gibbonReportingValue.gibbonReportingCriteriaID AND (gibbonReportingValue.gibbonPersonIDStudent=gibbonStudentEnrolment.gibbonPersonID OR gibbonReportingValue.gibbonPersonIDStudent=0))
                JOIN gibbonReportingCriteriaType ON (gibbonReportingCriteriaType.gibbonReportingCriteriaTypeID=gibbonReportingCriteria.gibbonReportingCriteriaTypeID)
                JOIN gibbonReportingScope ON (gibbonReportingScope.gibbonReportingScopeID=gibbonReportingCriteria.gibbonReportingScopeID)
                JOIN gibbonFormGroup ON (gibbonFormGroup.gibbonFormGroupID=gibbonReportingCriteria.gibbonFormGroupID)
                LEFT JOIN gibbonReportingProgress ON (gibbonReportingProgress.gibbonReportingScopeID=gibbonReportingScope.gibbonReportingScopeID AND gibbonReportingProgress.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID AND gibbonReportingProgress.gibbonPersonIDStudent=gibbonStudentEnrolment.gibbonPersonID)
                LEFT JOIN gibbonScaleGrade ON (gibbonScaleGrade.gibbonScaleID=gibbonReportingCriteriaType.gibbonScaleID AND gibbonScaleGrade.gibbonScaleGradeID=gibbonReportingValue.gibbonScaleGradeID)
                WHERE gibbonStudentEnrolment.gibbonStudentEnrolmentID=:gibbonStudentEnrolmentID
                AND gibbonReportingCriteria.gibbonReportingCycleID=:gibbonReportingCycleID
                AND gibbonReportingScope.scopeType='Form Group'
                AND ((gibbonReportingProgress.status='Complete' AND gibbonReportingCriteria.target = 'Per Student') 
                    OR gibbonReportingCriteria.target = 'Per Group') 
                ORDER BY gibbonReportingScope.sequenceNumber, gibbonReportingCriteria.sequenceNumber, gibbonFormGroup.nameShort";

        return $this->db()->select($sql, $data)->fetchGrouped();
    }
}
