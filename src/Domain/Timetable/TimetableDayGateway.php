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

namespace Gibbon\Domain\Timetable;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * @version v21
 * @since   v16
 */
class TimetableDayGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonTTDay';
    private static $primaryKey = 'gibbonTTDayID';

    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryTTDays(QueryCriteria $criteria, $gibbonTTID)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonTTDay.*','gibbonTTColumn.name AS columnName'
            ])
            ->innerJoin('gibbonTTColumn', 'gibbonTTDay.gibbonTTColumnID=gibbonTTColumn.gibbonTTColumnID')
            ->where('gibbonTTID = :gibbonTTID')
            ->bindValue('gibbonTTID', $gibbonTTID);

        return $this->runQuery($query, $criteria);
    }

    public function selectTTDaysByID($gibbonTTID)
    {
        $data = array('gibbonTTID' => $gibbonTTID);
        $sql = "SELECT gibbonTTDay.*, gibbonTTColumn.name AS columnName
                FROM gibbonTTDay
                JOIN gibbonTTColumn ON (gibbonTTDay.gibbonTTColumnID=gibbonTTColumn.gibbonTTColumnID)
                WHERE gibbonTTDay.gibbonTTID=:gibbonTTID";

        return $this->db()->select($sql, $data);
    }

    public function selectTTDayRowsByID($gibbonTTDayID)
    {
        $data = array('gibbonTTDayID' => $gibbonTTDayID);
        $sql = "SELECT gibbonTTColumnRow.*, COUNT(DISTINCT gibbonTTDayRowClassID) AS classCount
                FROM gibbonTTDay
                JOIN gibbonTTColumnRow ON (gibbonTTColumnRow.gibbonTTColumnID=gibbonTTDay.gibbonTTColumnID)
                LEFT JOIN gibbonTTDayRowClass ON (gibbonTTDayRowClass.gibbonTTColumnRowID=gibbonTTColumnRow.gibbonTTColumnRowID AND gibbonTTDayRowClass.gibbonTTDayID=gibbonTTDay.gibbonTTDayID)
                WHERE gibbonTTDay.gibbonTTDayID=:gibbonTTDayID
                GROUP BY gibbonTTColumnRow.gibbonTTColumnRowID
                ORDER BY gibbonTTColumnRow.timeStart, gibbonTTColumnRow.name";

        return $this->db()->select($sql, $data);
    }

    public function selectTTDayRowClassesByID($gibbonTTDayID, $gibbonTTColumnRowID = null) {
        $data = array('gibbonTTDayID' => $gibbonTTDayID);
        $sql = "SELECT gibbonTTDayRowClassID, gibbonCourseClass.gibbonCourseClassID, gibbonCourse.nameShort AS courseName, gibbonCourseClass.nameShort AS className, gibbonSpace.gibbonSpaceID, gibbonSpace.name as location, gibbonTTColumnRowID
                FROM gibbonTTDayRowClass
                JOIN gibbonCourseClass ON (gibbonTTDayRowClass.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
                JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID)
                LEFT JOIN gibbonSpace ON (gibbonTTDayRowClass.gibbonSpaceID=gibbonSpace.gibbonSpaceID)
                WHERE gibbonTTDayID=:gibbonTTDayID";
                if (!empty($gibbonTTColumnRowID)) {
                    $data['gibbonTTColumnRowID'] = $gibbonTTColumnRowID;
                    $sql .= " AND gibbonTTColumnRowID=:gibbonTTColumnRowID";
                }
                $sql .= " ORDER BY courseName, className";

        return $this->db()->select($sql, $data);
    }

    public function selectTTDayRowClassTeachersByID($gibbonTTDayRowClassID) {
        $data = array('gibbonTTDayRowClassID' => $gibbonTTDayRowClassID);
        $sql = "SELECT DISTINCT title, surname, preferredName, gibbonTTDayRowClassException.gibbonPersonID AS exception
                FROM gibbonPerson
                JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID)
                JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
                JOIN gibbonTTDayRowClass ON (gibbonTTDayRowClass.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
                LEFT JOIN gibbonTTDayRowClassException ON (gibbonTTDayRowClassException.gibbonTTDayRowClassID=gibbonTTDayRowClass.gibbonTTDayRowClassID
                    AND gibbonTTDayRowClassException.gibbonPersonID=gibbonPerson.gibbonPersonID)
                WHERE gibbonCourseClassPerson.role='Teacher'
                AND gibbonTTDayRowClass.gibbonTTDayRowClassID=:gibbonTTDayRowClassID
                AND gibbonTTDayRowClassExceptionID IS NULL
                ORDER BY preferredName, surname";

        return $this->db()->select($sql, $data);
    }

    public function selectTTDayRowClassExceptionsByID($gibbonTTDayRowClassID) {
        $data = array('gibbonTTDayRowClassID' => $gibbonTTDayRowClassID);
        $sql = "SELECT gibbonTTDayRowClassExceptionID, gibbonPerson.gibbonPersonID, surname, preferredName
                FROM gibbonTTDayRowClassException
                JOIN gibbonPerson ON (gibbonTTDayRowClassException.gibbonPersonID=gibbonPerson.gibbonPersonID)
                WHERE gibbonTTDayRowClassID=:gibbonTTDayRowClassID
                ORDER BY preferredName, surname";

        return $this->db()->select($sql, $data);
    }

    public function getTTDayByID($gibbonTTDayID)
    {
        $data = array('gibbonTTDayID' => $gibbonTTDayID);
        $sql = "SELECT gibbonTT.gibbonTTID, gibbonSchoolYear.name AS schoolYear, gibbonTT.name AS ttName, gibbonTTDay.name, gibbonTTDay.nameShort, gibbonTTDay.color, gibbonTTDay.fontColor, gibbonTTColumn.gibbonTTColumnID, gibbonTTColumn.name AS columnName
            FROM gibbonTTDay
            JOIN gibbonTT ON (gibbonTTDay.gibbonTTID=gibbonTT.gibbonTTID)
            JOIN gibbonSchoolYear ON (gibbonTT.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID)
            JOIN gibbonTTColumn ON (gibbonTTDay.gibbonTTColumnID=gibbonTTColumn.gibbonTTColumnID)
            WHERE gibbonTTDay.gibbonTTDayID=:gibbonTTDayID";

        return $this->db()->selectOne($sql, $data);
    }

    public function getTTDayRowByID($gibbonTTDayID, $gibbonTTColumnRowID)
    {
        $data = array('gibbonTTDayID' => $gibbonTTDayID, 'gibbonTTColumnRowID' => $gibbonTTColumnRowID);
        $sql = "SELECT gibbonTT.name AS ttName, gibbonTTDay.name AS dayName, gibbonTTColumnRow.name AS rowName
                FROM gibbonTTDay
                JOIN gibbonTT ON (gibbonTT.gibbonTTID=gibbonTTDay.gibbonTTID)
                JOIN gibbonTTColumn ON (gibbonTTDay.gibbonTTColumnID=gibbonTTColumn.gibbonTTColumnID)
                JOIN gibbonTTColumnRow ON (gibbonTTColumn.gibbonTTColumnID=gibbonTTColumnRow.gibbonTTColumnID)
                WHERE gibbonTTDay.gibbonTTDayID=:gibbonTTDayID
                AND gibbonTTColumnRowID=:gibbonTTColumnRowID";

        return $this->db()->selectOne($sql, $data);
    }

    public function getTTDayRowClassByID($gibbonTTDayID, $gibbonTTColumnRowID, $gibbonCourseClassID)
    {
        $data = array('gibbonTTDayID' => $gibbonTTDayID, 'gibbonTTColumnRowID' => $gibbonTTColumnRowID, 'gibbonCourseClassID' => $gibbonCourseClassID);
        $sql = "SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonTTDayRowClass.gibbonTTDayRowClassID
                FROM gibbonTTDayRowClass
                JOIN gibbonTTDay ON (gibbonTTDay.gibbonTTDayID=gibbonTTDayRowClass.gibbonTTDayID)
                JOIN gibbonTTColumn ON (gibbonTTDay.gibbonTTColumnID=gibbonTTColumn.gibbonTTColumnID)
                JOIN gibbonCourseClass ON (gibbonTTDayRowClass.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
                JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID)
                WHERE gibbonTTDayRowClass.gibbonTTDayID=:gibbonTTDayID
                AND gibbonTTDayRowClass.gibbonTTColumnRowID=:gibbonTTColumnRowID
                AND gibbonTTDayRowClass.gibbonCourseClassID=:gibbonCourseClassID";

        return $this->db()->selectOne($sql, $data);
    }

    public function getTTDayRowClassExceptionByID($gibbonTTDayRowClassExceptionID)
    {
        $data = array('gibbonTTDayRowClassExceptionID' => $gibbonTTDayRowClassExceptionID);
        $sql = "SELECT * FROM gibbonTTDayRowClassException WHERE gibbonTTDayRowClassExceptionID=:gibbonTTDayRowClassExceptionID";

        return $this->db()->selectOne($sql, $data);
    }

    public function selectDaysByDate($date, $gibbonTTID = null) {
        $data = array('date' => $date);
        $sql = "SELECT gibbonTTDayDate.gibbonTTDayID
                FROM gibbonTTDayDate
                JOIN gibbonTTDay ON (gibbonTTDayDate.gibbonTTDayID=gibbonTTDay.gibbonTTDayID)
                WHERE date=:date";

        if (!is_null($gibbonTTID)) {
            $data["gibbonTTID"] = $gibbonTTID;
            $sql .= " AND gibbonTTID=:gibbonTTID";
        }

        return $this->db()->select($sql, $data);
    }

    public function insertDayRowClass(array $data)
    {
        $sql = "INSERT INTO gibbonTTDayRowClass SET gibbonTTDayID=:gibbonTTDayID, gibbonTTColumnRowID=:gibbonTTColumnRowID, gibbonCourseClassID=:gibbonCourseClassID, gibbonSpaceID=:gibbonSpaceID ON DUPLICATE KEY UPDATE gibbonTTDayID=:gibbonTTDayID";

        return $this->db()->insert($sql, $data);
    }

    public function insertDayRowClassException(array $data)
    {
        $sql = "INSERT INTO gibbonTTDayRowClassException SET gibbonTTDayRowClassID=:gibbonTTDayRowClassID, gibbonPersonID=:gibbonPersonID ON DUPLICATE KEY UPDATE gibbonTTDayRowClassID=:gibbonTTDayRowClassID";

        return $this->db()->insert($sql, $data);
    }
}
