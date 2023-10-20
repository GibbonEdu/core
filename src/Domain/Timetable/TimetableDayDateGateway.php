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
}
