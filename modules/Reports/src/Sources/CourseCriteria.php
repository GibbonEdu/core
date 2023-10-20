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

class CourseCriteria extends DataSource
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
                        'value'               => ['randomDigit'],
                        'comment'             => ['paragraph', 6],
                        'valueType'           => 'Comment',
                    ],
                ],
                'perStudent' => [
                    0 => [
                        'scopeName'           => 'Course',
                        'criteriaName'        => 'Student Comment',
                        'criteriaDescription' => ['sentence'],
                        'value'               => ['randomDigit'],
                        'comment'             => ['paragraph', 6],
                        'valueType'           => 'Comment',
                    ],
                    1 => [
                        'scopeName'           => 'Course',
                        'criteriaName'        => 'Student Grade',
                        'criteriaDescription' => ['sentence'],
                        'value'               => ['randomElement', ['A+', 'A', 'A-', 'B+', 'B', 'B-', 'C+', 'C', 'C-', 'D']],
                        'comment'             => '',
                        'valueType'           => 'Grade Scale',
                    ],
                ],
            ],
        ];
    }

    public function getData($ids = [])
    {
        $data = ['gibbonStudentEnrolmentID' => $ids['gibbonStudentEnrolmentID'], 'gibbonReportingCycleID' => $ids['gibbonReportingCycleID']];
        $sql = "SELECT DISTINCT gibbonCourse.gibbonCourseID, gibbonCourseClass.gibbonCourseClassID,
                    (CASE WHEN gibbonReportingCriteria.target = 'Per Group' THEN 'perGroup' ELSE 'perStudent' END) AS groupBy, 
                    gibbonReportingScope.name as scopeName,
                    gibbonReportingCriteria.name as criteriaName,
                    gibbonReportingCriteria.description as criteriaDescription, 
                    gibbonReportingValue.value, 
                    gibbonReportingValue.comment, 
                    gibbonScaleGrade.descriptor,
                    gibbonReportingCriteriaType.valueType, 
                    gibbonCourse.name as courseName, 
                    gibbonCourse.nameShort as courseNameShort,
                    gibbonCourseClass.name as className, 
                    gibbonCourseClass.nameShort as classNameShort,
                    author.title as authorTitle,
                    author.preferredName as authorPreferredName,
                    author.surname as authorSurname
                FROM gibbonStudentEnrolment 
                JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID)
                JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID)
                JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID)
                JOIN gibbonReportingCriteria ON (gibbonReportingCriteria.gibbonCourseID=gibbonCourse.gibbonCourseID)
                JOIN gibbonReportingCriteriaType ON (gibbonReportingCriteriaType.gibbonReportingCriteriaTypeID=gibbonReportingCriteria.gibbonReportingCriteriaTypeID)
                JOIN gibbonReportingScope ON (gibbonReportingScope.gibbonReportingScopeID=gibbonReportingCriteria.gibbonReportingScopeID)
                LEFT JOIN gibbonReportingValue ON (gibbonReportingCriteria.gibbonReportingCriteriaID=gibbonReportingValue.gibbonReportingCriteriaID AND gibbonReportingValue.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID AND (gibbonReportingValue.gibbonPersonIDStudent=gibbonStudentEnrolment.gibbonPersonID OR gibbonReportingValue.gibbonPersonIDStudent=0))
                LEFT JOIN gibbonReportingProgress ON (gibbonReportingProgress.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID AND (gibbonReportingProgress.gibbonPersonIDStudent=gibbonStudentEnrolment.gibbonPersonID OR gibbonReportingProgress.gibbonPersonIDStudent=0))
                LEFT JOIN gibbonScaleGrade ON (gibbonScaleGrade.gibbonScaleID=gibbonReportingCriteriaType.gibbonScaleID AND gibbonScaleGrade.gibbonScaleGradeID=gibbonReportingValue.gibbonScaleGradeID)
                LEFT JOIN gibbonPerson as author ON (gibbonReportingValue.gibbonPersonIDCreated=author.gibbonPersonID)
                WHERE gibbonStudentEnrolment.gibbonStudentEnrolmentID=:gibbonStudentEnrolmentID
                AND gibbonReportingCriteria.gibbonReportingCycleID=:gibbonReportingCycleID
                AND gibbonCourse.gibbonSchoolYearID=gibbonStudentEnrolment.gibbonSchoolYearID
                AND (gibbonCourseClassPerson.role='Student' OR gibbonCourseClassPerson.role='Student - Left')
                AND gibbonReportingScope.scopeType='Course'
                AND ((gibbonReportingProgress.status='Complete' AND gibbonReportingCriteria.target = 'Per Student') 
                    OR gibbonReportingCriteria.target = 'Per Group') 
                ORDER BY gibbonReportingScope.sequenceNumber, gibbonReportingCriteria.sequenceNumber, gibbonCourse.orderBy, gibbonCourse.name";

        $courses = $this->db()->select($sql, $data)->fetchAll();

        $courses = array_reduce($courses, function ($group, $item) {
            $courseID = $item['gibbonCourseID'];
            $group[$courseID][$item['groupBy']][] = $item;
            $group[$courseID]['gibbonCourseID'] = $item['gibbonCourseID'];
            $group[$courseID]['gibbonCourseClassID'] = $item['gibbonCourseClassID'];
            $group[$courseID]['courseName'] = $item['courseName'];
            $group[$courseID]['courseNameShort'] = $item['courseNameShort'];
            $group[$courseID]['className'] = $item['className'];
            $group[$courseID]['classNameShort'] = $item['classNameShort'];
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
