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
}
