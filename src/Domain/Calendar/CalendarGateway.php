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
                'gibbonCalendarID', 'name', 'description', 'color', 'public', 'viewableStaff', 'viewableStudents', 'viewableParents', 'viewableOther', 'viewableParticipants', 'editableStaff',
                '(SELECT COUNT(*) FROM gibbonCalendarEditor WHERE gibbonCalendarID=gibbonCalendar.gibbonCalendarID) as editors'
            ])
            ->where('gibbonSchoolYearID=:gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID);

        return $this->runQuery($query, $criteria);
    }

    public function selectActiveCalendarsBySchoolYear($gibbonSchoolYearID)
    {
        $select = $this
            ->newSelect()
            ->from($this->getTableName())
            ->cols([
                'gibbonCalendar.gibbonCalendarID', 'gibbonCalendar.name', 'gibbonCalendar.description', 'gibbonCalendar.color', 'gibbonCalendar.sequenceNumber', 'gibbonCalendar.public', 'gibbonCalendar.viewableStaff', 'gibbonCalendar.viewableStudents', 'gibbonCalendar.viewableParents', 'gibbonCalendar.viewableOther', 'gibbonCalendar.viewableParticipants', 'gibbonCalendar.editableStaff'
            ])
            ->where('gibbonSchoolYearID=:gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->orderBy(['gibbonCalendar.sequenceNumber', 'gibbonCalendar.name']);

        return $this->runSelect($select);
    }

    public function selectEditableCalendarsByPerson($gibbonSchoolYearID, $gibbonPersonID)
    {
        $select = $this
            ->newSelect()
            ->cols([
                'gibbonCalendar.gibbonCalendarID as value', 'gibbonCalendar.name'
            ])
            ->from($this->getTableName())
            
            ->where('gibbonCalendar.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->orderBy(['gibbonCalendar.sequenceNumber', 'gibbonCalendar.name']);

        if (!empty($gibbonPersonID)) {
            $select
                ->leftJoin('gibbonCalendarEditor', 'gibbonCalendarEditor.gibbonCalendarID=gibbonCalendar.gibbonCalendarID AND gibbonCalendarEditor.gibbonPersonID=:gibbonPersonID')
                ->leftJoin('gibbonStaff', 'gibbonStaff.gibbonPersonID=:gibbonPersonID')
                ->where('( (gibbonCalendar.editableStaff="Y" AND gibbonStaff.gibbonStaffID IS NOT NULL) OR (gibbonCalendarEditor.gibbonCalendarEditorID IS NOT NULL) )')
                ->bindValue('gibbonPersonID', $gibbonPersonID);
        }

        return $this->runSelect($select);
    }
}
