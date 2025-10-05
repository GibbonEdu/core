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
class CalendarEventGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonCalendarEvent';
    private static $primaryKey = 'gibbonCalendarEventID';
    private static $searchableColumns = ['gibbonCalendarEvent.name', 'gibbonCalendarEvent.description', 'gibbonPerson.surname', 'gibbonPerson.preferredName', 'gibbonCalendar.name', 'gibbonCalendarEventType.type',];


     public function queryEvents(QueryCriteria $criteria, $gibbonPersonID = null)
    {
        $query = $this
            ->newQuery()
            ->distinct()
            ->cols([
                'gibbonCalendarEvent.gibbonCalendarEventID',
                'gibbonCalendarEvent.gibbonCalendarID',
                'gibbonCalendarEvent.gibbonCalendarEventTypeID',
                'gibbonCalendarEvent.name as eventName',
                'gibbonCalendarEvent.status',
                'gibbonCalendarEvent.description',
                'gibbonCalendarEvent.dateStart',
                'gibbonCalendarEvent.dateEnd',
                'gibbonCalendarEvent.locationType',
                'gibbonCalendarEvent.gibbonPersonIDOrganiser',
                'gibbonCalendar.name as calendarName',
                'gibbonCalendarEventType.type',
                'gibbonPerson.preferredName', 
                'gibbonPerson.surname',
            ])
            ->from($this->getTableName())
            ->leftJoin('gibbonCalendar', 'gibbonCalendar.gibbonCalendarID=gibbonCalendarEvent.gibbonCalendarID')
            ->leftJoin('gibbonCalendarEventType', 'gibbonCalendarEventType.gibbonCalendarEventTypeID=gibbonCalendarEvent.gibbonCalendarEventTypeID')
            ->leftJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=gibbonCalendarEvent.gibbonPersonIDOrganiser')
            ->groupBy(['gibbonCalendarEvent.gibbonCalendarEventID']);

        if (!empty($gibbonPersonID)) {
            $query->leftJoin('gibbonCalendarEventPerson as participant', 'participant.gibbonCalendarEventID=gibbonCalendarEvent.gibbonCalendarEventID')
                ->where('(participant.gibbonPersonID=:gibbonPersonID OR gibbonCalendarEvent.gibbonPersonIDOrganiser=:gibbonPersonID OR gibbonCalendarEvent.gibbonPersonIDCreated=:gibbonPersonID)')
                ->bindValue('gibbonPersonID', $gibbonPersonID);
        }

        $criteria->addFilterRules([
            'status' => function ($query, $status) {
                return $query
                    ->where('gibbonCalendarEvent.status = :status')
                    ->bindValue('status', ucfirst($status));
            },
        ]);

        return $this->runQuery($query, $criteria);
    }

    public function selectCalendarEvents()
    {

        $sql = "SELECT gibbonCalendarEventID as id, name as title,
                    (CASE WHEN allDay='N' THEN CONCAT(dateStart, 'T', timeStart) ELSE dateStart END) as start,
                    (CASE WHEN allDay='N' THEN CONCAT(dateEnd, 'T', timeEnd) ELSE dateEnd END) as end
                FROM gibbonCalendarEvent
                ORDER BY gibbonCalendarEvent.dateStart, gibbonCalendarEvent.dateEnd";

        return $this->db()->select($sql);
    }
}
