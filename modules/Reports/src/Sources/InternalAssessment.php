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

class InternalAssessment extends DataSource
{
    public function getSchema()
    {
        return [
            0 => [
                'name'                 => 'Example Assessment',
                'description'          => ['sentence'],
                'type'                 => 'Type',
                'attainmentActive'     => 'Y',
                'attainmentValue'      => ['randomDigit'],
                'attainmentDescriptor' => ['sameAs', 'attainmentValue'],
                'effortActive'         => 'Y',
                'effortValue'          => ['randomElement', ['Excellent', 'Very Good', 'Good', 'Satisfactory', 'Needs Improvement']],
                'effortDescriptor'     => ['sameAs', 'effortDescriptor'],
                'commentActive'        => 'Y',
                'comment'              => ['paragraph', 3],
                'courseName'           => 'Example Course',
                'courseNameShort'      => 'COURSE',
                'className'            => 'Example Class',
                'classNameShort'       => 'CLASS',
                'completeDate'         => ['date', 'Y-m-d'],
            ],
        ];
    }

    public function getData($ids = [])
    {
        $data = array('gibbonStudentEnrolmentID' => $ids['gibbonStudentEnrolmentID'], 'today' => date('Y-m-d'));
        $sql = "SELECT gibbonInternalAssessmentColumn.name, 
                    gibbonInternalAssessmentColumn.description, 
                    gibbonInternalAssessmentColumn.type, 
                    gibbonInternalAssessmentColumn.attainment as attainmentActive, 
                    gibbonInternalAssessmentEntry.attainmentValue, 
                    gibbonInternalAssessmentEntry.attainmentDescriptor, 
                    gibbonInternalAssessmentColumn.effort as effortActive, 
                    gibbonInternalAssessmentEntry.effortValue, 
                    gibbonInternalAssessmentEntry.effortDescriptor, 
                    gibbonInternalAssessmentColumn.comment as commentActive, 
                    gibbonInternalAssessmentEntry.comment, 
                    gibbonCourse.name as courseName,
                    gibbonCourse.nameShort AS courseNameShort, 
                    gibbonCourseClass.name AS className, 
                    gibbonCourseClass.nameShort AS classNameShort,
                    gibbonInternalAssessmentColumn.completeDate
                FROM gibbonStudentEnrolment
                JOIN gibbonInternalAssessmentEntry ON (gibbonInternalAssessmentEntry.gibbonPersonIDStudent=gibbonStudentEnrolment.gibbonPersonID)
                JOIN gibbonInternalAssessmentColumn ON (gibbonInternalAssessmentEntry.gibbonInternalAssessmentColumnID=gibbonInternalAssessmentColumn.gibbonInternalAssessmentColumnID) 
                JOIN gibbonCourseClassPerson ON (gibbonInternalAssessmentColumn.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) 
                JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) 
                JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) 
                WHERE gibbonStudentEnrolment.gibbonStudentEnrolmentID=:gibbonStudentEnrolmentID
                AND gibbonCourse.gibbonSchoolYearID=gibbonStudentEnrolment.gibbonSchoolYearID
                AND gibbonCourseClassPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID
                AND gibbonInternalAssessmentColumn.complete='Y'
                AND gibbonInternalAssessmentColumn.completeDate<=:today 
                ORDER BY gibbonInternalAssessmentColumn.completeDate DESC, gibbonCourse.nameShort, gibbonCourseClass.nameShort";

        return $this->db()->select($sql, $data)->fetchAll();
    }
}
