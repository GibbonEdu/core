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

namespace Gibbon\Domain\Calendar;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * @version v29
 * @since   v29
 */
class CalendarGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonCalendar';
    private static $primaryKey = 'gibbonCalendarID';

    private static $searchableColumns = ['name'];
    
    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryCalendars(QueryCriteria $criteria, $gibbonSchoolYearID)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonCalendarID', 'name', 'description', 'color'
            ])
            ->where('gibbonSchoolYearID=:gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID);

        return $this->runQuery($query, $criteria);
    }

     public function selectCalendarsBySchoolYear($gibbonSchoolYearID)
    {
        $data = ['gibbonSchoolYearID' => $gibbonSchoolYearID];
        $sql = "SELECT gibbonCalendar.gibbonCalendarID as value, gibbonCalendar.name 
                FROM gibbonCalendar
                WHERE gibbonCalendar.gibbonSchoolYearID=:gibbonSchoolYearID
                ORDER BY gibbonCalendar.sequenceNumber, gibbonCalendar.name";

        return $this->db()->select($sql, $data);
    }
}
