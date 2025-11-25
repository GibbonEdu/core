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
                'gibbonCalendarEvent.timeStart',
                'gibbonCalendarEvent.timeEnd',
                'gibbonCalendarEvent.allDay',
                'gibbonCalendarEvent.locationType',
                'gibbonCalendarEvent.locationDetail',
                'gibbonCalendarEvent.locationURL',
                'gibbonCalendarEvent.gibbonPersonIDOrganiser',
                'gibbonCalendar.name as calendarName',
                'gibbonCalendarEventType.type',
                'gibbonPerson.preferredName', 
                'gibbonPerson.surname',
                'gibbonCalendar.color',
                'gibbonSpace.name as space',
                'COUNT(DISTINCT gibbonCalendarEventPerson.gibbonPersonID) as participants'
            ])
            ->from($this->getTableName())
            ->leftJoin('gibbonCalendar', 'gibbonCalendar.gibbonCalendarID=gibbonCalendarEvent.gibbonCalendarID')
            ->leftJoin('gibbonCalendarEventType', 'gibbonCalendarEventType.gibbonCalendarEventTypeID=gibbonCalendarEvent.gibbonCalendarEventTypeID')
            ->leftJoin('gibbonCalendarEventPerson', 'gibbonCalendarEventPerson.gibbonCalendarEventID=gibbonCalendarEvent.gibbonCalendarEventID')
            ->leftJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=gibbonCalendarEvent.gibbonPersonIDOrganiser')
            ->leftJoin('gibbonSpace', 'gibbonSpace.gibbonSpaceID=gibbonCalendarEvent.gibbonSpaceID')
            ->groupBy(['gibbonCalendarEvent.gibbonCalendarEventID']);

        if (!empty($gibbonPersonID)) {
            $query
                ->cols(['editor.gibbonCalendarEditorID', 'editor.editAllEvents', '(CASE WHEN editor.editAllEvents="Y" OR (gibbonCalendarEvent.gibbonPersonIDOrganiser=:gibbonPersonID OR gibbonCalendarEvent.gibbonPersonIDCreated=:gibbonPersonID) THEN "Y" ELSE "N" END) as editor'])
                ->leftJoin('gibbonCalendarEventPerson as participant', 'participant.gibbonCalendarEventID=gibbonCalendarEvent.gibbonCalendarEventID')
                ->leftJoin('gibbonCalendarEditor as editor', 'editor.gibbonCalendarID=gibbonCalendarEvent.gibbonCalendarID AND editor.gibbonPersonID=:gibbonPersonID')
                ->where('((participant.gibbonPersonID=:gibbonPersonID OR gibbonCalendarEvent.gibbonPersonIDOrganiser=:gibbonPersonID OR gibbonCalendarEvent.gibbonPersonIDCreated=:gibbonPersonID) 
                OR (editor.gibbonCalendarEditorID IS NOT NULL AND (editor.editAllEvents="Y" OR (gibbonCalendarEvent.gibbonPersonIDOrganiser=:gibbonPersonID OR gibbonCalendarEvent.gibbonPersonIDCreated=:gibbonPersonID))))')
                ->bindValue('gibbonPersonID', $gibbonPersonID);
        } else {
            $query->cols(['"N" as editor']);
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

    public function selectVisibleEventsByPerson($gibbonPersonID, $roleCategory, $dateStart, $dateEnd)
    {
        $query = $this
            ->newSelect()
            ->cols([
                'gibbonCalendarEvent.gibbonCalendarEventID as id', 'gibbonCalendarEvent.name as title', 'gibbonCalendarEvent.description', 
                "(CASE WHEN allDay='N' THEN CONCAT(gibbonCalendarEvent.dateStart, 'T', timeStart) ELSE gibbonCalendarEvent.dateStart END) as start", 
                "(CASE WHEN allDay='N' THEN CONCAT(gibbonCalendarEvent.dateEnd, 'T', timeEnd) ELSE DATE_ADD(gibbonCalendarEvent.dateEnd, INTERVAL 1 DAY) END) as end",
                'gibbonCalendar.color', 'gibbonCalendarEventType.type', 'gibbonCalendarEvent.allDay', 'gibbonCalendarEvent.timeStart', 'gibbonCalendarEvent.timeEnd',
                'gibbonCalendar.name as calendar', 'gibbonCalendarEvent.locationType', 'gibbonSpace.phoneInternal AS phone',
                '(CASE WHEN gibbonCalendarEvent.locationType="Internal" THEN gibbonSpace.name ELSE gibbonCalendarEvent.locationDetail END) AS location'
            ])
            ->from($this->getTableName())
            ->innerJoin('gibbonCalendar', 'gibbonCalendar.gibbonCalendarID=gibbonCalendarEvent.gibbonCalendarID')
            ->leftJoin('gibbonCalendarEventType', 'gibbonCalendarEventType.gibbonCalendarEventTypeID=gibbonCalendarEvent.gibbonCalendarEventTypeID')
            ->leftJoin('gibbonCalendarEventPerson', 'gibbonCalendarEvent.gibbonCalendarEventID=gibbonCalendarEventPerson.gibbonCalendarEventID AND gibbonCalendarEventPerson.gibbonPersonID=:gibbonPersonID')
            ->leftJoin('gibbonSpace', 'gibbonSpace.gibbonSpaceID=gibbonCalendarEvent.gibbonSpaceID')
            ->where('(gibbonCalendarEvent.dateStart BETWEEN :dateStart AND :dateEnd OR gibbonCalendarEvent.dateEnd BETWEEN :dateStart AND :dateEnd)')
            ->bindValue('dateStart', $dateStart)
            ->bindValue('dateEnd', $dateEnd)
            ->bindValue('gibbonPersonID', $gibbonPersonID)
            ->orderBy(['gibbonCalendarEvent.dateStart', 'gibbonCalendarEvent.dateEnd']);

        $viewableParticipants = "(gibbonCalendar.viewableParticipants='Y' AND gibbonCalendarEventPerson.gibbonCalendarEventPersonID IS NOT NULL)";
        if ($roleCategory == 'Staff') {
            $query->where("(gibbonCalendar.viewableStaff='Y' OR gibbonCalendar.public='Y' OR {$viewableParticipants})");
        } elseif ($roleCategory == 'Student') {
            $query->where("(gibbonCalendar.viewableStudents='Y' OR gibbonCalendar.public='Y' OR {$viewableParticipants})");
        } elseif ($roleCategory == 'Parent') {
            $query->where("(gibbonCalendar.viewableParents='Y' OR gibbonCalendar.public='Y' OR {$viewableParticipants})");
        } elseif ($roleCategory == 'Other') {
            $query->where("(gibbonCalendar.viewableOther='Y' OR gibbonCalendar.public='Y' OR {$viewableParticipants})");
        } else {
            $query->where("(gibbonCalendar.public='Y' OR {$viewableParticipants})");
        }

        return $this->runSelect($query);
    }

    public function selectEventsByCalendar($gibbonCalendarID, $gibbonPersonID, $dateStart, $dateEnd)
    {
        $query = $this
            ->newSelect()
            ->from($this->getTableName())
            ->cols([
                'gibbonCalendarEvent.gibbonCalendarEventID',
                'gibbonCalendarEvent.gibbonCalendarID',
                'gibbonCalendarEvent.gibbonCalendarEventTypeID',
                'gibbonCalendarEvent.name',
                'gibbonCalendarEvent.status',
                'gibbonCalendarEvent.description',
                'gibbonCalendarEvent.dateStart',
                'gibbonCalendarEvent.dateEnd',
                'gibbonCalendarEvent.timeStart',
                'gibbonCalendarEvent.timeEnd',
                'gibbonCalendarEvent.allDay',
                'gibbonCalendarEvent.locationType',
                'gibbonCalendarEvent.locationDetail',
                'gibbonCalendarEvent.locationURL',
                'gibbonCalendarEvent.gibbonSpaceID',
                'gibbonCalendarEvent.gibbonPersonIDOrganiser',
                'gibbonCalendarEventType.type',
                'organiser.preferredName as organiserPreferredName', 
                'organiser.surname as organiserSurname',
                '(CASE WHEN gibbonCalendarEvent.gibbonSpaceID IS NOT NULL THEN gibbonSpace.name ELSE NULL END) AS space',
                '(CASE WHEN gibbonCalendarEventPersonID IS NOT NULL THEN gibbonCalendarEventPerson.role ELSE NULL END) as role',
                '(CASE WHEN gibbonCalendarEventPersonID IS NOT NULL THEN "Y" ELSE "N" END) as participant',
            ])
            ->leftJoin('gibbonCalendarEventType', 'gibbonCalendarEventType.gibbonCalendarEventTypeID=gibbonCalendarEvent.gibbonCalendarEventTypeID')
            ->leftJoin('gibbonCalendar', 'gibbonCalendar.gibbonCalendarID=gibbonCalendarEvent.gibbonCalendarID')
            ->leftJoin('gibbonCalendarEventPerson', 'gibbonCalendarEvent.gibbonCalendarEventID=gibbonCalendarEventPerson.gibbonCalendarEventID AND gibbonCalendarEventPerson.gibbonPersonID=:gibbonPersonID')
            ->leftJoin('gibbonPerson as organiser', "gibbonCalendarEvent.gibbonPersonIDOrganiser=organiser.gibbonPersonID AND organiser.status = 'Full'")
            ->leftJoin('gibbonSpace', 'gibbonSpace.gibbonSpaceID=gibbonCalendarEvent.gibbonSpaceID')
            ->where('gibbonCalendar.gibbonCalendarID = :gibbonCalendarID')
            ->bindValue('gibbonCalendarID', $gibbonCalendarID)
            ->bindValue('gibbonPersonID', $gibbonPersonID)
            ->where("gibbonCalendarEvent.status = 'Confirmed'")
            ->where('(gibbonCalendarEvent.dateStart <= :rangeEnd AND gibbonCalendarEvent.dateEnd >= :rangeStart)')
            ->bindValue('rangeStart', $dateStart)
            ->bindValue('rangeEnd', $dateEnd);

        return $this->runSelect($query);
    }
    
    public function selectEventsByFacility($gibbonCalendarID, $gibbonSpaceID, $rangeStart = null, $rangeEnd = null)
    {
        $query = $this
            ->newSelect()
            ->from($this->getTableName())
            ->cols([
                'gibbonCalendarEvent.gibbonCalendarEventID',
                'gibbonCalendarEvent.gibbonCalendarID',
                'gibbonCalendarEvent.gibbonCalendarEventTypeID',
                'gibbonCalendarEvent.name',
                'gibbonCalendarEvent.status',
                'gibbonCalendarEvent.description',
                'gibbonCalendarEvent.dateStart',
                'gibbonCalendarEvent.dateEnd',
                'gibbonCalendarEvent.timeStart',
                'gibbonCalendarEvent.timeEnd',
                'gibbonCalendarEvent.allDay',
                'gibbonCalendarEvent.locationType',
                'gibbonCalendarEvent.locationDetail',
                'gibbonCalendarEvent.locationURL',
                'gibbonCalendarEvent.gibbonSpaceID',
                'gibbonCalendarEvent.gibbonPersonIDOrganiser',
                'gibbonCalendarEventType.type',
                'organiser.preferredName as organiserPreferredName', 
                'organiser.surname as organiserSurname',
                'CASE WHEN gibbonCalendarEvent.gibbonSpaceID IS NOT NULL THEN gibbonSpace.name ELSE NULL END AS space',
                '"N" as participant',
            ])
            ->leftJoin('gibbonCalendar', 'gibbonCalendar.gibbonCalendarID=gibbonCalendarEvent.gibbonCalendarID')
            ->leftJoin('gibbonCalendarEventType', 'gibbonCalendarEventType.gibbonCalendarEventTypeID=gibbonCalendarEvent.gibbonCalendarEventTypeID')
            ->leftJoin('gibbonPerson as organiser', "gibbonCalendarEvent.gibbonPersonIDOrganiser=organiser.gibbonPersonID AND organiser.status = 'Full'")
            ->leftJoin('gibbonSpace', 'gibbonSpace.gibbonSpaceID=gibbonCalendarEvent.gibbonSpaceID')
            ->where('gibbonCalendar.gibbonCalendarID = :gibbonCalendarID')
            ->bindValue('gibbonCalendarID', $gibbonCalendarID)
            ->where("gibbonCalendarEvent.status = 'Confirmed'")
            ->where('(gibbonCalendarEvent.dateStart <= :rangeEnd AND gibbonCalendarEvent.dateEnd >= :rangeStart)')
            ->bindValue('rangeStart', $rangeStart)
            ->bindValue('rangeEnd', $rangeEnd)
            ->where('gibbonSpace.gibbonSpaceID=:gibbonSpaceID')
            ->bindValue('gibbonSpaceID', $gibbonSpaceID);

        return $this->runSelect($query);
    }

    public function getEventDetailsByID($gibbonCalendarEventID, $gibbonPersonID)
    {
        $query = $this
            ->newSelect()
            ->from($this->getTableName())
            ->cols([
                'gibbonCalendar.name as calendarName',
                'gibbonCalendarEvent.gibbonCalendarEventID',
                'gibbonCalendarEvent.gibbonCalendarID',
                'gibbonCalendarEvent.gibbonCalendarEventTypeID',
                'gibbonCalendarEvent.name',
                'gibbonCalendarEvent.status',
                'gibbonCalendarEvent.description',
                'gibbonCalendarEvent.dateStart',
                'gibbonCalendarEvent.dateEnd',
                'gibbonCalendarEvent.timeStart',
                'gibbonCalendarEvent.timeEnd',
                'gibbonCalendarEvent.allDay',
                'gibbonCalendarEvent.locationType',
                'gibbonCalendarEvent.locationDetail',
                'gibbonCalendarEvent.locationURL',
                'gibbonCalendarEvent.gibbonSpaceID',
                'gibbonCalendarEvent.gibbonPersonIDCreated',
                'gibbonCalendarEvent.gibbonPersonIDOrganiser',
                'gibbonCalendarEventType.type as eventType',
                'organiser.preferredName as organiserPreferredName', 
                'organiser.surname as organiserSurname',
                'gibbonSpace.name AS space',
                '(CASE WHEN editor.editAllEvents="Y" OR (gibbonCalendarEvent.gibbonPersonIDOrganiser=:gibbonPersonID OR gibbonCalendarEvent.gibbonPersonIDCreated=:gibbonPersonID) THEN "Y" ELSE "N" END) as editor',
                '(CASE WHEN participant.gibbonCalendarEventPersonID IS NOT NULL THEN "Y" ELSE "N" END) as participant',
            ])
            ->leftJoin('gibbonCalendar', 'gibbonCalendar.gibbonCalendarID=gibbonCalendarEvent.gibbonCalendarID')
            ->leftJoin('gibbonCalendarEventType', 'gibbonCalendarEventType.gibbonCalendarEventTypeID=gibbonCalendarEvent.gibbonCalendarEventTypeID')
            ->leftJoin('gibbonCalendarEditor as editor', 'editor.gibbonCalendarID=gibbonCalendarEvent.gibbonCalendarID AND editor.gibbonPersonID=:gibbonPersonID')
            ->leftJoin('gibbonCalendarEventPerson as participant', 'participant.gibbonCalendarEventID=gibbonCalendarEvent.gibbonCalendarEventID AND participant.gibbonPersonID=:gibbonPersonID')
            ->leftJoin('gibbonPerson as organiser', "gibbonCalendarEvent.gibbonPersonIDOrganiser=organiser.gibbonPersonID AND organiser.status = 'Full'")
            ->leftJoin('gibbonSpace', 'gibbonSpace.gibbonSpaceID=gibbonCalendarEvent.gibbonSpaceID')
            ->where('gibbonCalendarEvent.gibbonCalendarEventID = :gibbonCalendarEventID')
            ->bindValue('gibbonCalendarEventID', $gibbonCalendarEventID)
            ->bindValue('gibbonPersonID', $gibbonPersonID)
            ->groupBy(['gibbonCalendarEvent.gibbonCalendarEventID']);

        return $this->runSelect($query)->fetch();
    }
}
