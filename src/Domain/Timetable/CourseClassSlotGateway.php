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
 * Activity Slot Gateway
 *
 * @version v22
 * @since   v22
 */
class CourseClassSlotGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibboncourseclassslot';
    private static $primaryKey = 'gibbonCourseClassSlotID';

    private static $searchableColumns = [];

    public function deleteActivitySlotsNotInList($gibbonCourseClassID, $gibbonCourseClassSlotIDList)
    {
        $gibbonCourseClassSlotIDList = is_array($gibbonCourseClassSlotIDList) ? implode(',', $gibbonCourseClassSlotIDList) : $gibbonCourseClassSlotIDList;

        $data = ['gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonCourseClassSlotIDList' => $gibbonCourseClassSlotIDList];
        $sql = "DELETE FROM gibbonCourseClassSlot WHERE gibbonCourseClassID=:gibbonCourseClassID AND NOT FIND_IN_SET(gibbonCourseClassSlotID, :gibbonCourseClassSlotIDList)";

        return $this->db()->delete($sql, $data);
    }
}
