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
class CalendarEditorGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonCalendarEditor';
    private static $primaryKey = 'gibbonCalendarEditorID';

    private static $searchableColumns = [];

    public function selectEditorsByCalendar($gibbonCalendarID)
    {
        $data = ['gibbonCalendarID' => $gibbonCalendarID];
        $sql = "SELECT gibbonPerson.gibbonPersonID as groupBy,
                    gibbonCalendarEditor.gibbonCalendarEditorID,
                    gibbonCalendarEditor.editAllEvents,
                    gibbonPerson.gibbonPersonID,
                    gibbonPerson.surname,
                    gibbonPerson.preferredName,
                    gibbonPerson.image_240
                FROM gibbonCalendarEditor
                JOIN gibbonPerson ON (gibbonPerson.gibbonPersonID=gibbonCalendarEditor.gibbonPersonID) 
                WHERE gibbonCalendarEditor.gibbonCalendarID=:gibbonCalendarID
                ORDER BY gibbonCalendarEditor.gibbonCalendarEditorID, gibbonPerson.surname, gibbonPerson.preferredName";

        return $this->db()->select($sql, $data);
    }

    public function deleteEditorsNotInList($gibbonCalendarID, $editorIDList)
    {
        $editorIDList = is_array($editorIDList) ? implode(',', $editorIDList) : $editorIDList;

        $data = ['gibbonCalendarID' => $gibbonCalendarID, 'editorIDList' => $editorIDList];
        $sql = "DELETE FROM gibbonCalendarEditor WHERE gibbonCalendarID=:gibbonCalendarID AND NOT FIND_IN_SET(gibbonCalendarEditorID, :editorIDList)";

        return $this->db()->delete($sql, $data);
    }
}
