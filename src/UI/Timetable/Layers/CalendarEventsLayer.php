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

namespace Gibbon\UI\Timetable\Layers;

use Gibbon\Http\Url;
use Gibbon\Support\Facades\Access;
use Gibbon\UI\Timetable\TimetableContext;
use Gibbon\Domain\Calendar\CalendarEventGateway;

/**
 * Timetable UI: CalendarEventsLayer
 *
 * @version  v30
 * @since    v30
 */
class CalendarEventsLayer extends AbstractTimetableLayer
{
    protected $calendarEventGateway;

    public function __construct(CalendarEventGateway $calendarEventGateway)
    {
        $this->calendarEventGateway = $calendarEventGateway;

        $this->name = 'Events';
        $this->color = 'green';
        $this->order = 10;
    }

    public function checkAccess(TimetableContext $context) : bool
    {
        return Access::allows('Calendar', 'calendar_event_manage') || Access::allows('Calendar', 'calendar_event_view');
    }
    
    public function loadItems(\DatePeriod $dateRange, TimetableContext $context)
    {
        if (!$context->has('gibbonSchoolYearID')) return;

        if ($context->has('gibbonPersonID')) {
            $eventList = $this->calendarEventGateway->selectActiveEnrolledEvents($context->get('gibbonSchoolYearID'), $context->get('gibbonPersonID'), $dateRange->getStartDate()->format('Y-m-d'), $dateRange->getEndDate()->format('Y-m-d'))->fetchAll();
        } elseif ($context->has('gibbonSpaceID')) {
            $eventList = $this->calendarEventGateway->selectEventsByFacility($context->get('gibbonSchoolYearID'), $context->get('gibbonSpaceID'), $dateRange->getStartDate()->format('Y-m-d'), $dateRange->getEndDate()->format('Y-m-d'))->fetchAll();
        }

        $canViewActivities = Access::allows('Calendar', 'calendar_event_view');

        foreach ($dateRange as $dateObject) {
            $date = $dateObject->format('Y-m-d');
            foreach ($eventList as $event) {
                if ($date < $event['dateStart'] || $date > $event['dateEnd'] ) continue;

                $this->createItem($date, $event['allDay'])->loadData([
                    'id'        => $event['gibbonCalendarEventID'],
                    'type'      => __('Event'),
                    'title'     => $event['name'],
                    'subtitle'  => $event['locationType']. ': ' .(!empty($event['space']) ? $event['space'] : $event['locationDetail'] ?? ''),
                    'link'      => $canViewActivities ? Url::fromModuleRoute('Calendar', 'calendar_event_view')->withQueryParam('gibbonCalendarEventID', $event['gibbonCalendarEventID']) : '',
                    'allDay'      => $event['allDay'] ?? false,
                    'timeStart'   => $event['timeStart'] ?? null,
                    'timeEnd'     => $event['timeEnd'] ?? null,
                ]);
            }
        }
    }
}