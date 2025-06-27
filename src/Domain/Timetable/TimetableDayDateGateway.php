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
 * @version v22
 * @since   v22
 */
class TimetableDayDateGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonTTDayDate';
    private static $primaryKey = 'gibbonTTDayDateID';

    public function deleteTTDatesInRange($firstDayOld, $firstDayNew)
    {
        $data = array('firstDayOld' => $firstDayOld, 'firstDayNew' => $firstDayNew);
        $sql = "DELETE FROM gibbonTTDayDate WHERE date >= :firstDayOld AND date < :firstDayNew";

        return $this->db()->delete($sql, $data);
    }
    
    public function getTimetablePeriodByDayRowClass($gibbonTTDayRowClassID)
    {
        $data = ['gibbonTTDayRowClassID' => $gibbonTTDayRowClassID];
        $sql = "SELECT gibbonTTColumnRow.name, gibbonTTColumnRow.timeStart, gibbonTTColumnRow.timeEnd, gibbonTTDayRowClass.gibbonCourseClassID
                FROM gibbonTTDayRowClass
                JOIN gibbonTTColumnRow ON (gibbonTTColumnRow.gibbonTTColumnRowID=gibbonTTDayRowClass.gibbonTTColumnRowID)
                WHERE gibbonTTDayRowClass.gibbonTTDayRowClassID=:gibbonTTDayRowClassID";

        return $this->db()->selectOne($sql, $data);
    }

    public function selectTimetabledPeriodsByClass($gibbonCourseClassID, $date)
    {
        $data = ['gibbonCourseClassID' => $gibbonCourseClassID, 'date' => $date];
        $sql = "SELECT gibbonTTDayRowClass.gibbonTTDayRowClassID, gibbonTTColumnRow.name as period,  gibbonTTColumnRow.timeStart, gibbonTTColumnRow.timeEnd, gibbonTTDayRowClass.gibbonCourseClassID
                FROM gibbonTTDayRowClass
                JOIN gibbonTTColumnRow ON (gibbonTTColumnRow.gibbonTTColumnRowID=gibbonTTDayRowClass.gibbonTTColumnRowID)
                JOIN gibbonTTDayDate ON (gibbonTTDayDate.gibbonTTDayID=gibbonTTDayRowClass.gibbonTTDayID)
                WHERE gibbonTTDayRowClass.gibbonCourseClassID=:gibbonCourseClassID
                AND gibbonTTDayDate.date=:date";

        return $this->db()->select($sql, $data);
    }

    public function getTimetabledPeriodByClassAndTime($gibbonCourseClassID, $date, $timeStart, $timeEnd)
    {
        $data = ['gibbonCourseClassID' => $gibbonCourseClassID, 'date' => $date, 'timeStart' => $timeStart, 'timeEnd' => $timeEnd];
        $sql = "SELECT gibbonTTDayRowClass.gibbonTTDayRowClassID, gibbonTTColumnRow.name as period,  gibbonTTColumnRow.timeStart, gibbonTTColumnRow.timeEnd, gibbonTTDayRowClass.gibbonCourseClassID
                FROM gibbonTTDayRowClass
                JOIN gibbonTTColumnRow ON (gibbonTTColumnRow.gibbonTTColumnRowID=gibbonTTDayRowClass.gibbonTTColumnRowID)
                JOIN gibbonTTDayDate ON (gibbonTTDayDate.gibbonTTDayID=gibbonTTDayRowClass.gibbonTTDayID)
                WHERE gibbonTTDayRowClass.gibbonCourseClassID=:gibbonCourseClassID
                AND gibbonTTDayDate.date=:date
                AND gibbonTTColumnRow.timeStart=:timeStart 
                AND gibbonTTColumnRow.timeEnd=:timeEnd";

        return $this->db()->selectOne($sql, $data);
    }

    public function selectTimetabledPeriodsByPersonAndDateRange($gibbonPersonID, $dateStart, $dateEnd)
    {
        $data = ['gibbonPersonID' => $gibbonPersonID, 'dateStart' => $dateStart, 'dateEnd' => $dateEnd];

        $sql = "SELECT gibbonTTDayRowClass.gibbonTTDayID, gibbonTTDayRowClass.gibbonTTDayRowClassID, gibbonTTColumnRow.gibbonTTColumnRowID, gibbonCourseClass.gibbonCourseClassID, gibbonTTDay.gibbonTTID, gibbonTTDayDate.date, gibbonTTColumnRow.name as period, gibbonTTColumnRow.nameShort, gibbonCourse.gibbonSchoolYearID, gibbonCourse.gibbonCourseID, gibbonCourse.name as courseName, gibbonCourse.nameShort AS courseNameShort, gibbonCourseClass.nameShort AS classNameShort, gibbonCourse.gibbonYearGroupIDList, gibbonTTColumnRow.timeStart, gibbonTTColumnRow.timeEnd, gibbonSpace.phoneInternal as phone, gibbonSpace.name AS roomName, gibbonTTSpaceChange.gibbonTTSpaceChangeID as spaceChanged, spaceChange.name as roomNameChange, spaceChange.phoneInternal as phoneChange, gibbonStaffCoverage.status as coverageStatus, gibbonStaffCoverage.gibbonStaffCoverageID as coverageID, gibbonStaffCoverage.gibbonPersonIDCoverage as coveragePerson, CONCAT(gibbonCourseClass.gibbonCourseClassID, gibbonTTDayDate.date, gibbonTTColumnRow.timeStart, gibbonTTColumnRow.timeEnd) as lessonID
        FROM gibbonCourse 
        JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) 
        JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) 
        JOIN gibbonTTDayRowClass ON (gibbonCourseClass.gibbonCourseClassID=gibbonTTDayRowClass.gibbonCourseClassID) 
        JOIN gibbonTTColumnRow ON (gibbonTTDayRowClass.gibbonTTColumnRowID=gibbonTTColumnRow.gibbonTTColumnRowID) 
        JOIN gibbonTTDay ON (gibbonTTDay.gibbonTTDayID=gibbonTTDayRowClass.gibbonTTDayID) 
        JOIN gibbonTTDayDate ON (gibbonTTDayDate.gibbonTTDayID=gibbonTTDay.gibbonTTDayID)
        LEFT JOIN gibbonTTDayRowClassException ON (gibbonTTDayRowClassException.gibbonTTDayRowClassID=gibbonTTDayRowClass.gibbonTTDayRowClassID AND gibbonTTDayRowClassException.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID)
        LEFT JOIN gibbonSpace ON (gibbonTTDayRowClass.gibbonSpaceID=gibbonSpace.gibbonSpaceID) 
        LEFT JOIN gibbonTTSpaceChange ON (gibbonTTSpaceChange.gibbonTTDayRowClassID=gibbonTTDayRowClass.gibbonTTDayRowClassID AND gibbonTTSpaceChange.date=gibbonTTDayDate.date) 
        LEFT JOIN gibbonSpace as spaceChange ON (spaceChange.gibbonSpaceID=gibbonTTSpaceChange.gibbonSpaceID) 
        LEFT JOIN gibbonStaffCoverageDate ON (gibbonStaffCoverageDate.foreignTableID=gibbonTTDayRowClass.gibbonTTDayRowClassID AND gibbonStaffCoverageDate.foreignTable='gibbonTTDayRowClass' AND gibbonStaffCoverageDate.date=gibbonTTDayDate.date)
        LEFT JOIN gibbonStaffCoverage ON (gibbonStaffCoverageDate.gibbonStaffCoverageID=gibbonStaffCoverage.gibbonStaffCoverageID)
        WHERE gibbonTTDayDate.date BETWEEN :dateStart AND :dateEnd
            AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID 
            AND NOT role LIKE '% - Left' 
            AND gibbonTTDayRowClassException.gibbonTTDayRowClassExceptionID IS NULL
        GROUP BY gibbonTTDayRowClass.gibbonTTDayRowClassID 
        ORDER BY timeStart, timeEnd, FIND_IN_SET(gibbonCourseClassPerson.role, 'Teacher,Assistant,Student') DESC
        ";

        return $this->db()->select($sql, $data);
    }

    public function selectTimetabledPeriodsByFacilityAndDateRange($gibbonSpaceID, $dateStart, $dateEnd)
    {
        $data = ['gibbonSpaceID' => $gibbonSpaceID, 'dateStart' => $dateStart, 'dateEnd' => $dateEnd];

        $sql = "(
            SELECT gibbonTTDayRowClass.gibbonTTDayID, gibbonTTDayRowClass.gibbonTTDayRowClassID, gibbonTTDay.gibbonTTID, gibbonTTColumnRow.gibbonTTColumnRowID, gibbonCourseClass.gibbonCourseClassID, gibbonTTDayDate.date, gibbonTTColumnRow.name as period, gibbonTTColumnRow.nameShort, gibbonCourse.gibbonSchoolYearID, gibbonCourse.gibbonCourseID, gibbonCourse.name as courseName, gibbonCourse.nameShort AS courseNameShort, gibbonCourseClass.nameShort AS classNameShort, gibbonCourse.gibbonYearGroupIDList, gibbonTTColumnRow.timeStart, gibbonTTColumnRow.timeEnd, gibbonSpace.phoneInternal as phone, gibbonSpace.name AS roomName, null as spaceChanged
            FROM gibbonSpace
            JOIN gibbonTTDayRowClass ON (gibbonTTDayRowClass.gibbonSpaceID=gibbonSpace.gibbonSpaceID)
            JOIN gibbonTTColumnRow ON (gibbonTTDayRowClass.gibbonTTColumnRowID=gibbonTTColumnRow.gibbonTTColumnRowID) 
            JOIN gibbonTTDay ON (gibbonTTDay.gibbonTTDayID=gibbonTTDayRowClass.gibbonTTDayID) 
            JOIN gibbonTTDayDate ON (gibbonTTDayDate.gibbonTTDayID=gibbonTTDay.gibbonTTDayID)
            JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseClassID=gibbonTTDayRowClass.gibbonCourseClassID) 
            JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) 
            LEFT JOIN gibbonTTSpaceChange ON (gibbonTTSpaceChange.gibbonTTDayRowClassID=gibbonTTDayRowClass.gibbonTTDayRowClassID AND gibbonTTSpaceChange.date=gibbonTTDayDate.date) 
            WHERE gibbonTTDayDate.date BETWEEN :dateStart AND :dateEnd
                AND gibbonSpace.gibbonSpaceID=:gibbonSpaceID 
                AND gibbonTTSpaceChange.gibbonTTSpaceChangeID IS NULL
            GROUP BY gibbonTTDayRowClass.gibbonTTDayRowClassID 
            ORDER BY timeStart, timeEnd DESC
        ) UNION ALL (
            SELECT gibbonTTDayRowClass.gibbonTTDayID, gibbonTTDayRowClass.gibbonTTDayRowClassID, gibbonTTDay.gibbonTTID, gibbonTTColumnRow.gibbonTTColumnRowID, gibbonCourseClass.gibbonCourseClassID, gibbonTTDayDate.date, gibbonTTColumnRow.name as period, gibbonTTColumnRow.nameShort, gibbonCourse.gibbonSchoolYearID, gibbonCourse.gibbonCourseID, gibbonCourse.name as courseName, gibbonCourse.nameShort AS courseNameShort, gibbonCourseClass.nameShort AS classNameShort, gibbonCourse.gibbonYearGroupIDList, gibbonTTColumnRow.timeStart, gibbonTTColumnRow.timeEnd, gibbonSpace.phoneInternal as phone, gibbonSpace.name AS roomName, gibbonTTSpaceChange.gibbonTTSpaceChangeID as spaceChanged
            FROM gibbonTTSpaceChange
            JOIN gibbonSpace ON (gibbonSpace.gibbonSpaceID=gibbonTTSpaceChange.gibbonSpaceID)
            JOIN gibbonTTDayRowClass ON (gibbonTTDayRowClass.gibbonTTDayRowClassID=gibbonTTSpaceChange.gibbonTTDayRowClassID)
            JOIN gibbonTTColumnRow ON (gibbonTTDayRowClass.gibbonTTColumnRowID=gibbonTTColumnRow.gibbonTTColumnRowID) 
            JOIN gibbonTTDay ON (gibbonTTDay.gibbonTTDayID=gibbonTTDayRowClass.gibbonTTDayID) 
            JOIN gibbonTTDayDate ON (gibbonTTDayDate.gibbonTTDayID=gibbonTTDay.gibbonTTDayID)
            JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseClassID=gibbonTTDayRowClass.gibbonCourseClassID) 
            JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) 
            WHERE gibbonTTDayDate.date BETWEEN :dateStart AND :dateEnd
                AND gibbonTTSpaceChange.gibbonSpaceID=:gibbonSpaceID 
                AND gibbonTTSpaceChange.date=gibbonTTDayDate.date

            GROUP BY gibbonTTDayRowClass.gibbonTTDayRowClassID 
            ORDER BY timeStart, timeEnd DESC
        )
        ";

        return $this->db()->select($sql, $data);
    }
}
