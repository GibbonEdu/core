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

class CourseCriteriaByCycle extends DataSource
{
    public function getSchema()
    {
        return [
            0 => [
                'courseName' => 'Example Course',
                'courseNameShort' => 'EXAMPLE',
                'className' => 'Class 1',
                'classNameShort' => '1',
                'teachers'  => $this->getFactory()->get('ClassTeachers')->getSchema(),
                'perGroup' => [
                    0 => [
                        'scopeName'           => 'Course',
                        'criteriaName'        => 'Course Comment',
                        'criteriaDescription' => ['sentence'],
                        'comment'             => ['paragraph', 6],
                        'valueType'           => 'Comment',
                        'values' => [
                            'Cycle 1' => '',
                            'Cycle 2' => '',
                            'Cycle 3' => '',
                        ]
                    ],
                ],
                'perStudent' => [

                    0 => [
                        'scopeName'           => 'Course',
                        'criteriaName'        => 'Student Comment',
                        'criteriaDescription' => ['sentence'],
                        'comment'             => ['paragraph', 6],
                        'valueType'           => 'Comment',
                        'values' => [
                            'Cycle 1' => '',
                            'Cycle 2' => '',
                            'Cycle 3' => '',
                        ]
                    ],
                    1 => [
                        'scopeName'           => 'Course',
                        'criteriaName'        => 'Student Grade',
                        'criteriaDescription' => ['sentence'],
                        'comment'             => '',
                        'valueType'           => 'Grade Scale',
                        'values' => [
                            'Cycle 1' => [
                                'value' => ['randomElement', ['A+', 'A', 'A-', 'B+', 'B', 'B-', 'C+', 'C', 'C-', 'D']],
                                'descriptor' => ['sameAs', 'value']
                            ],
                            'Cycle 2' => [
                                'value' => ['randomElement', ['A+', 'A', 'A-', 'B+', 'B', 'B-', 'C+', 'C', 'C-', 'D']],
                                'descriptor' => ['sameAs', 'value']
                            ],
                            'Cycle 3' => [
                                'value' => ['randomElement', ['A+', 'A', 'A-', 'B+', 'B', 'B-', 'C+', 'C', 'C-', 'D']],
                                'descriptor' => ['sameAs', 'value']
                            ],
                        ]
                    ],
                ],
            ],
        ];
    }

    public function getData($ids = [])
    {
        $data = ['gibbonStudentEnrolmentID' => $ids['gibbonStudentEnrolmentID'], 'gibbonReportID' => $ids['gibbonReportID']];
        $sql = "SELECT DISTINCT gibbonCourse.gibbonCourseID, gibbonCourseClass.gibbonCourseClassID,
                    (CASE WHEN gibbonReportingCriteria.target = 'Per Group' THEN 'perGroup' ELSE 'perStudent' END) AS groupBy, 
                    gibbonReportingCycle.nameShort as cycleName,
                    gibbonReportingScope.name as scopeName,
                    gibbonReportingCriteria.name as criteriaName,
                    gibbonReportingCriteria.description as criteriaDescription, 
                    gibbonReportingCriteria.gibbonReportingCriteriaID as criteriaID,
                    gibbonReportingValue.value, 
                    gibbonReportingValue.comment, 
                    gibbonScaleGrade.descriptor,
                    gibbonReportingCriteriaType.valueType, 
                    gibbonCourse.name as courseName, 
                    gibbonCourse.nameShort as courseNameShort,
                    gibbonCourseClass.name as className, 
                    gibbonCourseClass.nameShort as classNameShort
                FROM gibbonReport
                JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonSchoolYearID=gibbonReport.gibbonSchoolYearID)
                JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID)
                JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID)
                JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID)
                LEFT JOIN gibbonReportingCriteria ON (gibbonReportingCriteria.gibbonCourseID=gibbonCourse.gibbonCourseID)
                LEFT JOIN gibbonReportingCriteriaType ON (gibbonReportingCriteriaType.gibbonReportingCriteriaTypeID=gibbonReportingCriteria.gibbonReportingCriteriaTypeID)
                LEFT JOIN gibbonReportingScope ON (gibbonReportingScope.gibbonReportingScopeID=gibbonReportingCriteria.gibbonReportingScopeID)
                LEFT JOIN gibbonReportingCycle ON (gibbonReportingCycle.gibbonReportingCycleID=gibbonReportingScope.gibbonReportingCycleID)
                LEFT JOIN gibbonReportingValue ON (
                    gibbonReportingValue.gibbonReportingCriteriaID=gibbonReportingCriteria.gibbonReportingCriteriaID
                    AND
                    (
                        (gibbonReportingCriteria.target='Per Student' AND gibbonReportingValue.gibbonPersonIDStudent=gibbonStudentEnrolment.gibbonPersonID) 
                        OR (gibbonReportingCriteria.target='Per Group' AND gibbonReportingValue.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
                    ))
                LEFT JOIN gibbonReportingProgress ON (gibbonReportingProgress.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID AND (gibbonReportingProgress.gibbonPersonIDStudent=gibbonStudentEnrolment.gibbonPersonID OR gibbonReportingProgress.gibbonPersonIDStudent=0))
                LEFT JOIN gibbonScaleGrade ON (gibbonScaleGrade.gibbonScaleID=gibbonReportingCriteriaType.gibbonScaleID AND gibbonScaleGrade.gibbonScaleGradeID=gibbonReportingValue.gibbonScaleGradeID)
                WHERE gibbonReport.gibbonReportID=:gibbonReportID
                AND gibbonStudentEnrolment.gibbonStudentEnrolmentID=:gibbonStudentEnrolmentID
                AND gibbonCourse.gibbonSchoolYearID=gibbonStudentEnrolment.gibbonSchoolYearID
                AND FIND_IN_SET(gibbonStudentEnrolment.gibbonYearGroupID, gibbonReport.gibbonYearGroupIDList)
                AND FIND_IN_SET(gibbonStudentEnrolment.gibbonYearGroupID, gibbonReportingCycle.gibbonYearGroupIDList)
                AND gibbonCourseClass.reportable='Y'
                AND gibbonCourseClassPerson.role='Student'
                AND gibbonCourseClassPerson.reportable='Y'
                AND gibbonReportingScope.scopeType='Course'
                AND ((gibbonReportingProgress.status='Complete' AND gibbonReportingCriteria.target = 'Per Student') 
                    OR gibbonReportingCriteria.target = 'Per Group') 
                AND (valueType IS NULL OR valueType <> 'Comment' OR (valueType = 'Comment' AND gibbonReportingCriteria.gibbonReportingCycleID=gibbonReport.gibbonReportingCycleID))
                ORDER BY gibbonReportingScope.sequenceNumber, gibbonReportingCriteria.sequenceNumber, gibbonCourse.orderBy, gibbonCourse.nameShort, gibbonReportingCriteria.sequenceNumber";

        $courses = $this->db()->select($sql, $data)->fetchAll();

        $courses = array_reduce($courses, function ($group, $item) {
            $cycle = $item['cycleName'];
            $courseID = $item['gibbonCourseID'];

            $group[$courseID]['gibbonCourseID'] = $item['gibbonCourseID'];
            $group[$courseID]['gibbonCourseClassID'] = $item['gibbonCourseClassID'];
            $group[$courseID]['courseName'] = $item['courseName'];
            $group[$courseID]['courseNameShort'] = $item['courseNameShort'];
            $group[$courseID]['className'] = $item['className'];
            $group[$courseID]['classNameShort'] = $item['classNameShort'];

            $group[$courseID][$item['groupBy']][$item['criteriaName']]['scopeName'] = $item['scopeName'];
            $group[$courseID][$item['groupBy']][$item['criteriaName']]['criteriaName'] = $item['criteriaName'];
            $group[$courseID][$item['groupBy']][$item['criteriaName']]['criteriaDescription'] = $item['criteriaDescription'];
            $group[$courseID][$item['groupBy']][$item['criteriaName']]['comment'] = $item['comment'];
            $group[$courseID][$item['groupBy']][$item['criteriaName']]['valueType'] = $item['valueType'];

            $values = $group[$courseID][$item['groupBy']][$item['criteriaName']]['values'] ?? [];
            $values[$cycle] = [
                'value'      => $item['value'],
                'descriptor' => $item['descriptor'],
            ];
            $group[$courseID][$item['groupBy']][$item['criteriaName']]['values'] = $values;

            if ($item['valueType'] == 'Comment') {
                $group[$courseID]['hasComments'] = true;
            }
            
            return $group;
        }, []);

        $courses = array_map(function ($course) use (&$ids) {
            $ids['gibbonCourseID'] = $course['gibbonCourseID'];
            $ids['gibbonCourseClassID'] = $course['gibbonCourseClassID'];

            $course['teachers'] = $this->getFactory()->get('ClassTeachers')->getData($ids);

            return $course;
        }, $courses);

        return $courses;
    }
}
