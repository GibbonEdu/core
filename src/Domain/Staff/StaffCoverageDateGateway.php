<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

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

namespace Gibbon\Domain\Staff;

use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;
use Gibbon\Domain\Traits\TableAware;

/**
 * Staff Coverage Date Gateway
 *
 * @version v18
 * @since   v18
 */
class StaffCoverageDateGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonStaffCoverageDate';
    private static $primaryKey = 'gibbonStaffCoverageDateID';

    private static $searchableColumns = [''];

    public function selectDatesByCoverage($gibbonStaffCoverageID)
    {
        $gibbonStaffCoverageIDList = is_array($gibbonStaffCoverageID)? $gibbonStaffCoverageID : [$gibbonStaffCoverageID];
        $data = ['gibbonStaffCoverageIDList' => implode(',', $gibbonStaffCoverageIDList) ];
        $sql = "SELECT gibbonStaffCoverageDate.gibbonStaffCoverageID as groupBy, gibbonStaffCoverageDate.*, gibbonStaffCoverage.status as coverage, coverage.title as titleCoverage, coverage.preferredName as preferredNameCoverage, coverage.surname as surnameCoverage, coverage.gibbonPersonID as gibbonPersonIDCoverage, gibbonStaffCoverageDate.reason as notes
                FROM gibbonStaffCoverageDate
                LEFT JOIN gibbonStaffCoverage ON (gibbonStaffCoverage.gibbonStaffCoverageID=gibbonStaffCoverageDate.gibbonStaffCoverageID)
                LEFT JOIN gibbonPerson AS coverage ON (gibbonStaffCoverage.gibbonPersonIDCoverage=coverage.gibbonPersonID)
                WHERE FIND_IN_SET(gibbonStaffCoverageDate.gibbonStaffCoverageID, :gibbonStaffCoverageIDList)
                ORDER BY gibbonStaffCoverageDate.date, gibbonStaffCoverageDate.timeStart";

        return $this->db()->select($sql, $data);
    }

    public function getCoverageDateDetailsByID($gibbonStaffCoverageDateID)
    {
        $data = ['gibbonStaffCoverageDateID' => $gibbonStaffCoverageDateID];
        $sql = "SELECT gibbonStaffCoverage.gibbonStaffCoverageID, gibbonStaffCoverage.status, gibbonStaffAbsence.gibbonStaffAbsenceID, gibbonStaffAbsenceType.name as type, gibbonStaffAbsence.reason, gibbonStaffCoverage.substituteTypes,
                gibbonStaffCoverageDate.date, gibbonStaffCoverageDate.allDay, gibbonStaffCoverageDate.timeStart, gibbonStaffCoverageDate.timeEnd, gibbonStaffCoverage.timestampStatus, gibbonStaffCoverage.timestampCoverage, gibbonStaffCoverage.requestType,
                gibbonStaffCoverage.notesCoverage, gibbonStaffCoverage.notesStatus, 0 as urgent, gibbonStaffAbsence.notificationSent, gibbonStaffAbsence.gibbonGroupID, gibbonStaffCoverage.notificationList as notificationListCoverage, gibbonStaffAbsence.notificationList as notificationListAbsence, 
                gibbonStaffCoverage.gibbonPersonID, absence.title AS titleAbsence, absence.preferredName AS preferredNameAbsence, absence.surname AS surnameAbsence, 
                gibbonStaffCoverage.gibbonPersonIDStatus, status.title AS titleStatus, status.preferredName AS preferredNameStatus, status.surname AS surnameStatus, 
                gibbonStaffCoverage.gibbonPersonIDCoverage, coverage.title as titleCoverage, coverage.preferredName as preferredNameCoverage, coverage.surname as surnameCoverage, gibbonStaffCoverageDate.gibbonTTDayRowClassID
            FROM gibbonStaffCoverageDate 
            JOIN gibbonStaffCoverage ON (gibbonStaffCoverageDate.gibbonStaffCoverageID=gibbonStaffCoverage.gibbonStaffCoverageID)
            LEFT JOIN gibbonStaffAbsence ON (gibbonStaffAbsence.gibbonStaffAbsenceID=gibbonStaffCoverage.gibbonStaffAbsenceID)
            LEFT JOIN gibbonStaffAbsenceType ON (gibbonStaffAbsence.gibbonStaffAbsenceTypeID=gibbonStaffAbsenceType.gibbonStaffAbsenceTypeID)
            LEFT JOIN gibbonPerson AS coverage ON (gibbonStaffCoverage.gibbonPersonIDCoverage=coverage.gibbonPersonID)
            LEFT JOIN gibbonPerson AS status ON (gibbonStaffCoverage.gibbonPersonIDStatus=status.gibbonPersonID)
            LEFT JOIN gibbonPerson AS absence ON (gibbonStaffCoverage.gibbonPersonID=absence.gibbonPersonID)
            WHERE gibbonStaffCoverageDate.gibbonStaffCoverageDateID=:gibbonStaffCoverageDateID
            ";

        return $this->db()->selectOne($sql, $data);
    }

    public function selectTimetabledClassCoverageByPersonAndDate($gibbonSchoolYearID, $gibbonPersonID, $dateStart, $dateEnd)
    {
        $data = ['gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonPersonID' => $gibbonPersonID, 'dateStart' => $dateStart, 'dateEnd' => $dateEnd];
        $sql = "SELECT DISTINCT gibbonTTDayDate.date, gibbonTT.gibbonTTID, gibbonTT.name, gibbonTTDayRowClass.gibbonTTDayRowClassID, gibbonCourseClass.gibbonCourseClassID, gibbonCourseClass.nameShort as classNameShort, gibbonTTColumnRow.name as columnName, gibbonTTColumnRow.timeStart, gibbonTTColumnRow.timeEnd, gibbonCourse.name as courseName, gibbonCourse.nameShort as courseNameShort, gibbonStaffCoverage.gibbonStaffCoverageID, gibbonStaffCoverage.status, CONCAT(gibbonTTDayDate.date, ':', gibbonTTDayRowClass.gibbonTTDayRowClassID) as timetableClassPeriod, coverage.surname as surnameCoverage, coverage.preferredName as preferredNameCoverage
        FROM gibbonTT 
        JOIN gibbonTTDay ON (gibbonTT.gibbonTTID=gibbonTTDay.gibbonTTID) 
        JOIN gibbonTTDayRowClass ON (gibbonTTDayRowClass.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) 
        JOIN gibbonTTDayDate ON (gibbonTTDay.gibbonTTDayID=gibbonTTDayDate.gibbonTTDayID) 
        JOIN gibbonCourseClass ON (gibbonTTDayRowClass.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
        JOIN gibbonTTColumnRow ON (gibbonTTColumnRow.gibbonTTColumnRowID=gibbonTTDayRowClass.gibbonTTColumnRowID)
        JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
        JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID)
        LEFT JOIN gibbonStaffCoverageDate ON (gibbonStaffCoverageDate.gibbonTTDayRowClassID=gibbonTTDayRowClass.gibbonTTDayRowClassID AND gibbonStaffCoverageDate.date=gibbonTTDayDate.date)
        LEFT JOIN gibbonStaffCoverage ON (gibbonStaffCoverage.gibbonStaffCoverageID=gibbonStaffCoverageDate.gibbonStaffCoverageID AND gibbonStaffCoverage.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID)
        LEFT JOIN gibbonPerson as coverage ON (coverage.gibbonPersonID=gibbonStaffCoverage.gibbonPersonIDCoverage)
        WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID 
        AND gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID 
        AND gibbonTT.active='Y' 
        AND gibbonTTDayDate.date BETWEEN :dateStart AND :dateEnd
        AND gibbonCourseClassPerson.role NOT LIKE '%Left'
        ORDER BY gibbonTTDayDate.date, gibbonTTColumnRow.timeStart ASC";

        return $this->db()->select($sql, $data);
    }

    public function getCoverageTimesByTimetableClass($gibbonTTDayRowClassID)
    {
        $data = ['gibbonTTDayRowClassID' => $gibbonTTDayRowClassID];
        $sql = "SELECT gibbonTTColumnRow.name as period, gibbonTTColumnRow.timeStart, gibbonTTColumnRow.timeEnd, 'N' as allDay, gibbonCourse.nameShort as courseName, gibbonCourseClass.nameShort as className, gibbonSpace.name as spaceName, gibbonSpace.gibbonSpaceID
            FROM gibbonTTDayRowClass
            JOIN gibbonTTColumnRow ON (gibbonTTColumnRow.gibbonTTColumnRowID=gibbonTTDayRowClass.gibbonTTColumnRowID)
            JOIN gibbonCourseClass ON (gibbonTTDayRowClass.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
            JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID)
            LEFT JOIN gibbonSpace ON (gibbonTTDayRowClass.gibbonSpaceID=gibbonSpace.gibbonSpaceID)
            WHERE gibbonTTDayRowClassID=:gibbonTTDayRowClassID";
        
        return $this->db()->selectOne($sql, $data);
    }

    public function deleteCoverageDatesByAbsenceID($gibbonStaffAbsenceID)
    {
        $data = ['gibbonStaffAbsenceID' => $gibbonStaffAbsenceID];
        $sql = "DELETE gibbonStaffCoverageDate FROM gibbonStaffCoverageDate
                JOIN gibbonStaffAbsenceDate ON (gibbonStaffAbsenceDate.gibbonStaffAbsenceDateID=gibbonStaffCoverageDate.gibbonStaffAbsenceDateID)
                WHERE gibbonStaffAbsenceDate.gibbonStaffAbsenceID = :gibbonStaffAbsenceID";

        return $this->db()->delete($sql, $data);
    }
}
