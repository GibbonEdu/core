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

namespace Gibbon\Domain\Staff;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * Staff Duty Gateway
 *
 * @version v25
 * @since   v25
 */
class StaffDutyGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonStaffDuty';
    private static $primaryKey = 'gibbonStaffDutyID';

    private static $searchableColumns = ['gibbonStaffDutyID', 'name'];

    /**
     * Queries the duty schedule.
     *
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryDuty(QueryCriteria $criteria) {
        $query = $this
            ->newQuery()
            ->cols([
                'gibbonStaffDuty.gibbonStaffDutyID', 'gibbonStaffDuty.name', 'gibbonStaffDuty.nameShort', 'gibbonStaffDuty.timeStart', 'gibbonStaffDuty.timeEnd', 'gibbonStaffDuty.sequenceNumber', 'gibbonStaffDuty.gibbonDaysOfWeekIDList'
            ])
            ->from($this->getTableName());

        return $this->runQuery($query, $criteria);
    }

    /**
     * Gers the duty roster.
     *
     * @return Result
     */
    public function selectDutyTimeSlots() {
        $query = $this
            ->newSelect()
            ->cols([
                'gibbonDaysOfWeek.name as groupBy', 'gibbonStaffDuty.gibbonStaffDutyID', 'gibbonDaysOfWeek.gibbonDaysOfWeekID', 'gibbonDaysOfWeek.name as weekdayName', 'gibbonStaffDuty.name', 'gibbonStaffDuty.timeStart', 'gibbonStaffDuty.timeEnd'
            ])
            ->from($this->getTableName())
            ->innerJoin('gibbonDaysOfWeek', 'FIND_IN_SET(gibbonDaysOfWeek.gibbonDaysOfWeekID, gibbonStaffDuty.gibbonDaysOfWeekIDList)')
            ->groupBy(['gibbonStaffDuty.gibbonStaffDutyID', 'gibbonDaysOfWeek.gibbonDaysOfWeekID'])
            ->orderBy(['gibbonDaysOfWeek.sequenceNumber', 'gibbonStaffDuty.sequenceNumber']);

        return $this->runSelect($query);
    }

    public function selectDutyTimeSlotsByWeekday($gibbonDaysOfWeekID)
    {
        $data = ['gibbonDaysOfWeekID' => $gibbonDaysOfWeekID];
        $sql = "SELECT gibbonStaffDutyID as value, name FROM gibbonStaffDuty WHERE FIND_IN_SET(:gibbonDaysOfWeekID, gibbonDaysOfWeekIDList) ORDER BY gibbonStaffDuty.sequenceNumber";

        return $this->db()->select($sql, $data);
    }

    public function deleteDutyNotInList($gibbonStaffDutyIDList)
    {
        $gibbonStaffDutyIDList = is_array($gibbonStaffDutyIDList) ? implode(',', $gibbonStaffDutyIDList) : $gibbonStaffDutyIDList;

        $data = ['gibbonStaffDutyIDList' => $gibbonStaffDutyIDList];
        $sql = "DELETE FROM gibbonStaffDuty WHERE NOT FIND_IN_SET(gibbonStaffDutyID, :gibbonStaffDutyIDList)";

        return $this->db()->delete($sql, $data);
    }
}
