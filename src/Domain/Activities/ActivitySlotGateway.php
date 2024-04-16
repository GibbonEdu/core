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

namespace Gibbon\Domain\Activities;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * Activity Slot Gateway
 *
 * @version v22
 * @since   v22
 */
class ActivitySlotGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonActivitySlot';
    private static $primaryKey = 'gibbonActivitySlotID';

    private static $searchableColumns = [];

    public function deleteActivitySlotsNotInList($gibbonActivityID, $gibbonActivitySlotIDList)
    {
        $gibbonActivitySlotIDList = is_array($gibbonActivitySlotIDList) ? implode(',', $gibbonActivitySlotIDList) : $gibbonActivitySlotIDList;

        $data = ['gibbonActivityID' => $gibbonActivityID, 'gibbonActivitySlotIDList' => $gibbonActivitySlotIDList];
        $sql = "DELETE FROM gibbonActivitySlot WHERE gibbonActivityID=:gibbonActivityID AND NOT FIND_IN_SET(gibbonActivitySlotID, :gibbonActivitySlotIDList)";

        return $this->db()->delete($sql, $data);
    }

    public function selectActivitySlots($gibbonActivityID)
    {
        $dataSlots = ['gibbonActivityID' => $gibbonActivityID];
        $sqlSlots = 'SELECT gibbonActivitySlot.*, gibbonDaysOfWeek.name AS day, gibbonSpace.name AS space FROM gibbonActivitySlot JOIN gibbonDaysOfWeek ON (gibbonActivitySlot.gibbonDaysOfWeekID=gibbonDaysOfWeek.gibbonDaysOfWeekID) LEFT JOIN gibbonSpace ON (gibbonActivitySlot.gibbonSpaceID=gibbonSpace.gibbonSpaceID) WHERE gibbonActivityID=:gibbonActivityID ORDER BY sequenceNumber';

        return $this->db()->select($sqlSlots, $dataSlots);
    }

    public function selectWeekdayNamesByActivity($gibbonActivityID)
    {
        $data = array('gibbonActivityID' => $gibbonActivityID);
        $sql = "SELECT DISTINCT nameShort FROM gibbonActivitySlot JOIN gibbonDaysOfWeek ON (gibbonActivitySlot.gibbonDaysOfWeekID=gibbonDaysOfWeek.gibbonDaysOfWeekID) WHERE gibbonActivityID=:gibbonActivityID ORDER BY sequenceNumber";

        return $this->db()->select($sql, $data);
    }

    public function selectActivityTimeSlots($gibbonActivityID)
    {
        $data = ['gibbonActivityID' => $gibbonActivityID];
        $sql = 'SELECT nameShort, timeStart, timeEnd FROM gibbonActivitySlot JOIN gibbonDaysOfWeek ON (gibbonActivitySlot.gibbonDaysOfWeekID=gibbonDaysOfWeek.gibbonDaysOfWeekID) WHERE gibbonActivityID=:gibbonActivityID ORDER BY gibbonDaysOfWeek.gibbonDaysOfWeekID';

        return $this->db()->select($sql, $data);       
    }
}
