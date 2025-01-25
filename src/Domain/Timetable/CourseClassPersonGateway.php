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

namespace Gibbon\Domain\Timetable;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * @version v27
 * @since   v27
 */
class CourseClassPersonGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonCourseClassPerson';
    private static $primaryKey = 'gibbonCourseClassPersonID';

    private static $searchableColumns = ['gibbonCourseClassPerson.gibbonPersonID', 'gibbonCourseClassPerson.gibbonCourseClassID'];

    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */


     public function selectStudentsInEachClass($gibbonCourseClassID, $gibbonSchoolYearID)
     {
        $data = ['gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonSchoolYearID' => $gibbonSchoolYearID, 'today' => date('Y-m-d')];
        $sql = "SELECT role, surname, preferredName, email, studentID, gibbonFormGroup.nameShort as formGroup
                FROM gibbonCourseClassPerson
                JOIN gibbonPerson ON gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID
                JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID)
                JOIN gibbonFormGroup ON (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID)
                WHERE gibbonCourseClassID=:gibbonCourseClassID AND status='Full'
                AND (dateStart IS NULL OR dateStart<=:today)
                AND (dateEnd IS NULL  OR dateEnd>=:today)
                AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID
                AND gibbonCourseClassPerson.role='Student'
                ORDER BY role DESC, surname, preferredName";
                
                return $this->db()->select($sql, $data);
     }
}