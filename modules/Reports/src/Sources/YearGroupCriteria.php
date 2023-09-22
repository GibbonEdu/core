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

class YearGroupCriteria extends DataSource
{
    public function getSchema()
    {
        $gender = rand(0, 99) > 50 ? 'female' : 'male';

        return [
            'perGroup' => [
                0 => [
                    'scopeName'           => 'Year Group',
                    'criteriaName'        => 'Year Group Comment',
                    'criteriaDescription' => ['sentence'],
                    'value'               => ['randomDigit'],
                    'comment'             => ['paragraph', 6],
                    'valueType'           => 'Comment',
                    'title'               => ['title', $gender],
                    'surname'             => ['lastName'],
                    'firstName'           => ['firstName', $gender],
                    'preferredName'       => ['sameAs', 'firstName'],
                    'officialName'        => ['sameAs', 'firstName surname'],
                ],
            ],

            'perStudent' => [
                0 => [
                    'scopeName'           => 'Year Group',
                    'criteriaName'        => 'Student Comment',
                    'criteriaDescription' => ['sentence'],
                    'value'               => ['randomDigit'],
                    'comment'             => ['paragraph', 6],
                    'valueType'           => 'Comment',
                    'title'               => ['title', $gender],
                    'surname'             => ['lastName'],
                    'firstName'           => ['firstName', $gender],
                    'preferredName'       => ['sameAs', 'firstName'],
                    'officialName'        => ['sameAs', 'firstName surname'],
                ],
            ],
        ];
    }

    public function getData($ids = [])
    {
        $data = ['gibbonStudentEnrolmentID' => $ids['gibbonStudentEnrolmentID'], 'gibbonReportingCycleID' => $ids['gibbonReportingCycleID']];
        $sql = "SELECT DISTINCT (CASE WHEN gibbonReportingCriteria.target = 'Per Group' THEN 'perGroup' ELSE 'perStudent' END) AS groupBy, 
                    gibbonReportingScope.name as scopeName,
                    gibbonReportingCriteria.name as criteriaName,
                    gibbonReportingCriteria.description as criteriaDescription, 
                    gibbonReportingValue.value, 
                    gibbonReportingValue.comment, 
                    gibbonScaleGrade.descriptor,
                    gibbonReportingCriteriaType.valueType, 
                    gibbonYearGroup.name as yearGroupName, 
                    gibbonYearGroup.nameShort as yearGroupNameShort,
                    createdBy.title,
                    createdBy.surname,
                    createdBy.firstName,
                    createdBy.preferredName,
                    createdBy.officialName,
                    gibbonStaff.jobTitle
                FROM gibbonStudentEnrolment 
                JOIN gibbonReportingCriteria ON (gibbonReportingCriteria.gibbonYearGroupID=gibbonStudentEnrolment.gibbonYearGroupID)
                JOIN gibbonReportingValue ON (gibbonReportingCriteria.gibbonReportingCriteriaID=gibbonReportingValue.gibbonReportingCriteriaID AND (gibbonReportingValue.gibbonPersonIDStudent=gibbonStudentEnrolment.gibbonPersonID OR gibbonReportingValue.gibbonPersonIDStudent=0))
                JOIN gibbonReportingCriteriaType ON (gibbonReportingCriteriaType.gibbonReportingCriteriaTypeID=gibbonReportingCriteria.gibbonReportingCriteriaTypeID)
                JOIN gibbonReportingScope ON (gibbonReportingScope.gibbonReportingScopeID=gibbonReportingCriteria.gibbonReportingScopeID)
                JOIN gibbonYearGroup ON (gibbonYearGroup.gibbonYearGroupID=gibbonReportingCriteria.gibbonYearGroupID)
                LEFT JOIN gibbonReportingProgress ON (gibbonReportingProgress.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID AND gibbonReportingProgress.gibbonPersonIDStudent=gibbonStudentEnrolment.gibbonPersonID)
                LEFT JOIN gibbonScaleGrade ON (gibbonScaleGrade.gibbonScaleID=gibbonReportingCriteriaType.gibbonScaleID AND gibbonScaleGrade.gibbonScaleGradeID=gibbonReportingValue.gibbonScaleGradeID)
                LEFT JOIN gibbonPerson as createdBy ON (createdBy.gibbonPersonID=gibbonReportingValue.gibbonPersonIDCreated)
                LEFT JOIN gibbonStaff ON (gibbonStaff.gibbonPersonID=createdBy.gibbonPersonID)
                WHERE gibbonStudentEnrolment.gibbonStudentEnrolmentID=:gibbonStudentEnrolmentID
                AND gibbonReportingCriteria.gibbonReportingCycleID=:gibbonReportingCycleID
                AND gibbonReportingScope.scopeType='Year Group'
                AND ((gibbonReportingProgress.status='Complete' AND gibbonReportingCriteria.target = 'Per Student') 
                    OR gibbonReportingCriteria.target = 'Per Group') 
                ORDER BY gibbonReportingScope.sequenceNumber, gibbonReportingCriteria.sequenceNumber, gibbonYearGroup.sequenceNumber";

        return $this->db()->select($sql, $data)->fetchGrouped();
    }
}
