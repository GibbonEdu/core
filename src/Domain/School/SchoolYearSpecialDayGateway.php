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
}
