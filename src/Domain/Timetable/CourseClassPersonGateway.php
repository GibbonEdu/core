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


    public function selectStudentsByClass($gibbonCourseClassID, $date = null)
    {
        $today = $date ?? date('Y-m-d');
        $data = ['gibbonCourseClassID' => $gibbonCourseClassID, 'today' => $today];
        $sql = "SELECT gibbonCourseClassPerson.role, gibbonPerson.gibbonPersonID, gibbonPerson.surname, gibbonPerson.preferredName, gibbonPerson.image_240, gibbonPerson.dob, gibbonPerson.email, gibbonPerson.studentID, gibbonFormGroup.nameShort as formGroup FROM gibbonCourseClassPerson JOIN gibbonPerson ON gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonFormGroup ON (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID)";

        if (!empty($date)) {
            $sql .= " LEFT JOIN (SELECT gibbonTTDayRowClass.gibbonCourseClassID, gibbonTTDayRowClass.gibbonTTDayRowClassID FROM gibbonTTDayDate JOIN gibbonTTDayRowClass ON (gibbonTTDayDate.gibbonTTDayID=gibbonTTDayRowClass.gibbonTTDayID) WHERE gibbonTTDayDate.date=:today) AS gibbonTTDayRowClassSubset ON (gibbonTTDayRowClassSubset.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) LEFT JOIN gibbonTTDayRowClassException ON (gibbonTTDayRowClassException.gibbonTTDayRowClassID=gibbonTTDayRowClassSubset.gibbonTTDayRowClassID AND gibbonTTDayRowClassException.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID)";
        }

        $sql .= " WHERE gibbonCourseClassPerson.gibbonCourseClassID=:gibbonCourseClassID AND status='Full' AND gibbonCourseClassPerson.role='Student' AND (dateStart IS NULL OR dateStart<=:today) AND (dateEnd IS NULL OR dateEnd>=:today)";

        if (!empty($date)) {
            $sql .= " GROUP BY gibbonCourseClassPerson.gibbonPersonID HAVING COUNT(gibbonTTDayRowClassExceptionID) = 0";
        }

        $sql .= " ORDER BY surname, preferredName, role DESC";

        return $this->db()->select($sql, $data);
    }

    public function selectTeachersByClass($gibbonCourseClassID)
    {
        $data = ['gibbonCourseClassID' => $gibbonCourseClassID, 'today' => date('Y-m-d')];
        $sql = "SELECT gibbonPerson.gibbonPersonID as groupBy, gibbonCourseClassPerson.role, gibbonPerson.gibbonPersonID, gibbonPerson.title, gibbonPerson.surname, gibbonPerson.preferredName, gibbonPerson.image_240, gibbonPerson.email
            FROM gibbonCourseClassPerson 
            JOIN gibbonPerson ON gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID
            WHERE gibbonCourseClassPerson.gibbonCourseClassID=:gibbonCourseClassID 
            AND gibbonPerson.status='Full'
            AND gibbonCourseClassPerson.role='Teacher'
            AND (gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart<=:today) AND (gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd>=:today)
            ORDER BY surname, preferredName, role DESC";

        return $this->db()->select($sql, $data);
    }
}
