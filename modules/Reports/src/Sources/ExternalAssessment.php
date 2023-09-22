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

class ExternalAssessment extends DataSource
{
    public function getSchema()
    {
        return [
            0 => [
                'name'          => 'Example Assessment',
                'nameShort'     => 'EXMPL',
                'description'   => ['sentence'],
                'date'          => ['date', 'Y-m-d'],
                'fields'        => [
                    0 => [
                        'fieldName'     => ['words', 3, true],
                        'fieldCategory' => 'Category',
                        'value'         => ['randomDigit'],
                        'descriptor'    => ['sameAs', 'value'],
                    ],
                    1 => [
                        'fieldName'     => ['words', 3, true],
                        'fieldCategory' => 'Category',
                        'value'         => ['randomDigit'],
                        'descriptor'    => ['sameAs', 'value'],
                    ],
                    2 => [
                        'fieldName'     => ['words', 3, true],
                        'fieldCategory' => 'Category',
                        'value'         => ['randomDigit'],
                        'descriptor'    => ['sameAs', 'value'],
                    ],
                ],
            ],
            1 => [
                'name'          => 'Example Assessment',
                'nameShort'     => 'EXMPL',
                'description'   => ['sentence'],
                'date'          => ['date', 'Y-m-d'],
                'fields'        => [
                    0 => [
                        'fieldName'     => ['words', 3, true],
                        'fieldCategory' => 'Category',
                        'value'         => ['randomDigit'],
                        'descriptor'    => ['sameAs', 'value'],
                    ],
                    1 => [
                        'fieldName'     => ['words', 3, true],
                        'fieldCategory' => 'Category',
                        'value'         => ['randomDigit'],
                        'descriptor'    => ['sameAs', 'value'],
                    ],
                    2 => [
                        'fieldName'     => ['words', 3, true],
                        'fieldCategory' => 'Category',
                        'value'         => ['randomDigit'],
                        'descriptor'    => ['sameAs', 'value'],
                    ],
                ],
            ],
        ];
    }

    public function getData($ids = [])
    {
        $data = array('gibbonStudentEnrolmentID' => $ids['gibbonStudentEnrolmentID']);
        $sql = "SELECT gibbonExternalAssessment.gibbonExternalAssessmentID,
                gibbonExternalAssessment.name,
                gibbonExternalAssessment.nameShort,
                gibbonExternalAssessment.description,
                gibbonExternalAssessmentStudent.date,
                gibbonExternalAssessmentField.name as fieldName,
                gibbonExternalAssessmentField.category as fieldCategory,
                gibbonScaleGrade.value,
                gibbonScaleGrade.descriptor
                FROM gibbonStudentEnrolment
                JOIN gibbonExternalAssessmentStudent ON (gibbonExternalAssessmentStudent.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) 
                JOIN gibbonExternalAssessmentStudentEntry ON (gibbonExternalAssessmentStudent.gibbonExternalAssessmentStudentID=gibbonExternalAssessmentStudentEntry.gibbonExternalAssessmentStudentID)
                JOIN gibbonExternalAssessment ON (gibbonExternalAssessmentStudent.gibbonExternalAssessmentID=gibbonExternalAssessment.gibbonExternalAssessmentID)
                JOIN gibbonExternalAssessmentField ON (gibbonExternalAssessmentField.gibbonExternalAssessmentID=gibbonExternalAssessment.gibbonExternalAssessmentID)
                JOIN gibbonScaleGrade ON (gibbonExternalAssessmentStudentEntry.gibbonScaleGradeID=gibbonScaleGrade.gibbonScaleGradeID AND gibbonExternalAssessmentField.gibbonScaleID=gibbonScaleGrade.gibbonScaleID)
                WHERE gibbonStudentEnrolment.gibbonStudentEnrolmentID=:gibbonStudentEnrolmentID
                ORDER BY gibbonExternalAssessmentStudent.date";

        $values = $this->db()->select($sql, $data)->fetchAll();

        $values = array_reduce($values, function ($group, $item) {
            $group[$item['name']]['name'] = $item['name'];
            $group[$item['name']]['nameShort'] = $item['nameShort'];
            $group[$item['name']]['description'] = $item['description'];
            $group[$item['name']]['date'] = $item['date'];

            $group[$item['name']]['fields'][] = [
                'fieldName' => $item['fieldName'],
                'fieldCategory' => $item['fieldCategory'],
                'value' => $item['value'],
                'descriptor' => $item['descriptor'],
            ];
            return $group;
        }, []);

        return $values;
    }
}
