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

namespace Gibbon\Domain\School;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * School Year Special Day Gateway
 *
 * @version v25
 * @since   v25
 */
class SchoolYearSpecialDayGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonSchoolYearSpecialDay';
    private static $primaryKey = 'gibbonSchoolYearSpecialDayID';

    public function getSpecialDayByDate($date)
    {
        $data = ['date' => $date];
        $sql = "SELECT * FROM gibbonSchoolYearSpecialDay WHERE date=:date";

        return $this->db()->selectOne($sql, $data);
    }

    public function selectSpecialDaysByDateRange($dateStart, $dateEnd)
    {
        $data = ['dateStart' => $dateStart, 'dateEnd' => $dateEnd];
        $sql = "SELECT date as groupBy, gibbonSchoolYearSpecialDay.* FROM gibbonSchoolYearSpecialDay WHERE date BETWEEN :dateStart AND :dateEnd";

        return $this->db()->select($sql, $data);
    }

    public function getIsFormGroupOffTimetableByDate($gibbonSchoolYearID, $gibbonFormGroupID, $date)
    {
        $data = ['gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonFormGroupID' => $gibbonFormGroupID, 'date' => $date];
        $sql = "SELECT (CASE WHEN count(*) = 0 THEN 1 ELSE 0 END) as offTimetable 
            FROM gibbonPerson AS student
            JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=student.gibbonPersonID ) 
            LEFT JOIN gibbonSchoolYearSpecialDay ON (gibbonSchoolYearSpecialDay.date=:date AND gibbonSchoolYearSpecialDay.type='Off Timetable')
            WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID
            AND gibbonStudentEnrolment.gibbonFormGroupID=:gibbonFormGroupID 
            AND student.status='Full' 
            AND (student.dateStart IS NULL OR student.dateStart<=:date) 
            AND (student.dateEnd IS NULL OR student.dateEnd>=:date) 
            AND (gibbonSchoolYearSpecialDayID IS NULL OR NOT FIND_IN_SET(gibbonStudentEnrolment.gibbonYearGroupID, gibbonSchoolYearSpecialDay.gibbonYearGroupIDList) )
            AND (gibbonSchoolYearSpecialDayID IS NULL OR NOT FIND_IN_SET(gibbonStudentEnrolment.gibbonFormGroupID, gibbonSchoolYearSpecialDay.gibbonFormGroupIDList))";

        return $this->db()->selectOne($sql, $data);
    }

    public function getIsClassOffTimetableByDate($gibbonSchoolYearID, $gibbonCourseClassID, $date)
    {
        $data = ['gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonCourseClassID' => $gibbonCourseClassID, 'date' => $date];
        $sql = "SELECT COUNT(*) as studentTotal, COUNT(CASE WHEN (gibbonSchoolYearSpecialDayID IS NULL OR NOT FIND_IN_SET(gibbonStudentEnrolment.gibbonYearGroupID, gibbonSchoolYearSpecialDay.gibbonYearGroupIDList) ) AND (gibbonSchoolYearSpecialDayID IS NULL OR NOT FIND_IN_SET(gibbonStudentEnrolment.gibbonFormGroupID, gibbonSchoolYearSpecialDay.gibbonFormGroupIDList)) THEN student.gibbonPersonID ELSE NULL END) as studentCount
            FROM gibbonCourseClassPerson 
            JOIN gibbonPerson AS student ON (gibbonCourseClassPerson.gibbonPersonID=student.gibbonPersonID) 
            JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=student.gibbonPersonID) 
            LEFT JOIN gibbonSchoolYearSpecialDay ON (gibbonSchoolYearSpecialDay.date=:date AND gibbonSchoolYearSpecialDay.type='Off Timetable')
            WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID
            AND gibbonCourseClassPerson.role='Student' 
            AND student.status='Full' 
            AND gibbonCourseClassPerson.gibbonCourseClassID=:gibbonCourseClassID 
            AND (student.dateStart IS NULL OR student.dateStart<=:date) 
            AND (student.dateEnd IS NULL OR student.dateEnd>=:date)";

        $result = $this->db()->selectOne($sql, $data);

        return !empty($result) && ($result['studentTotal'] > 0 && $result['studentCount'] <= 0);
    }
}
