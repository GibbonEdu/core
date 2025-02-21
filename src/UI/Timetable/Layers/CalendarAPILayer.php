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

use Gibbon\Contracts\Services\Session;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\UI\Timetable\TimetableContext;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Microsoft\Graph\Graph;
use Microsoft\Graph\Model\Event;

/**
 * Timetable UI: CalendarAPILayer
 *
 * @version  v29
 * @since    v29
 */
class CalendarAPILayer extends AbstractTimetableLayer implements ContainerAwareInterface
{
    use ContainerAwareTrait;
    
    protected $settingGateway;
    protected $session;

    public function __construct(SettingGateway $settingGateway, Session $session)
    {
        $this->settingGateway = $settingGateway;
        $this->session = $session;

        $this->name = 'Calendar';
        $this->color = 'green';
        $this->order = 10;
    }
    
    public function loadItems(\DatePeriod $dateRange, TimetableContext $context) 
    {
        if ($context->get('gibbonPersonID') != $this->session->get('gibbonPersonID')) return;

        if ($schoolCalendarFeed = $this->session->get('calendarFeed', null)) {
            $this->loadEventsByFeed($schoolCalendarFeed, $dateRange, 'green');
        }

        if ($personalCalendarFeed = $this->session->get('calendarFeedPersonal', null)) {
            $this->loadEventsByFeed($personalCalendarFeed, $dateRange, 'cyan');
        }

        $this->sortOverlappingEvents($dateRange);
    }

    protected function loadEventsByFeed($calendarFeed, \DatePeriod $dateRange, $color = null)
    {
        $events = $this->getCalendarEvents($calendarFeed, $dateRange->getStartDate()->getTimestamp(), $dateRange->getEndDate()->getTimestamp());

        foreach ($events as $event) {
            if (empty($event['date'])) continue;

            $this->createItem($event['date'], $event['allDay'])->loadData([
                'type'      => __('Event'),
                'title'     => $event['title'] ?? '',
                'subtitle'  => $event['subtitle'] ?? '',
                'allDay'    => $event['allDay'] ?? false,
                'timeStart' => $event['timeStart'] ?? null,
                'timeEnd'   => $event['timeEnd'] ?? null,
                'link'      => $event['link'] ?? '',
                'color'     => $color,
            ]);
        }
    }

    protected function sortOverlappingEvents(\DatePeriod $dateRange)
    {
        foreach ($dateRange as $date) {
            $overlapIndex = 0;
            $overlap = false;

            $items = $this->getItemsByDate($date->format('Y-m-d'));
            usort($items, function ($a, $b) {
                return $a->timeStart <=> $b->timeStart;
            });

            $this->items[$date->format('Y-m-d').'-N'] = $items;

            foreach ($items as $itemA) {
                $overlap = false;

                foreach ($items as $itemB) {
                    if ($itemA === $itemB) continue;
                    if ($itemB->timeStart < $itemA->timeStart) {
                        continue;
                    }
                    if ($overlap) break;
                    
                    $overlap = (($itemA->timeStart >= $itemB->timeStart && $itemA->timeStart < $itemB->timeEnd) || ($itemB->timeStart >= $itemA->timeStart && $itemB->timeStart < $itemA->timeEnd));
                }

                $itemA->set('index', $overlapIndex);

                if ($overlap) {
                    $overlapIndex++;
                } else {
                    $overlapIndex = 0;
                }
            }
        }
    }

    protected function getCalendarEvents(string $calendarFeed, $startDayStamp, $endDayStamp)
    {
        $calendarEventsCache = 'calendarAPICache-'.date('W', $startDayStamp).'-'.substr($calendarFeed, 0, 24);
        $calendarRefresh = $_REQUEST['ttCalendarRefresh'] ?? false;
    
        if ($this->session->has($calendarEventsCache) && (empty($calendarRefresh) || $calendarRefresh == 'false')) {
            return $this->session->get($calendarEventsCache);
        }
    
        $ssoMicrosoft = $this->settingGateway->getSettingByScope('System Admin', 'ssoMicrosoft');
        $ssoMicrosoft = json_decode($ssoMicrosoft, true);
    
        if (!empty($ssoMicrosoft) && $ssoMicrosoft['enabled'] == 'Y' && $this->session->has('microsoftAPIAccessToken')) {
            $eventsSchool = [];
    
            // Create a Graph client
            $oauthProvider = $this->getContainer()->get('Microsoft_Auth');
            if (empty($oauthProvider)) return;
    
            $graph = new Graph();
            $graph->setAccessToken($this->session->get('microsoftAPIAccessToken'));
    
            $startOfWeek = new \DateTimeImmutable(date('Y-m-d H:i:s', $startDayStamp));
            $endOfWeek = new \DateTimeImmutable(date('Y-m-d H:i:s', $endDayStamp+ 86399));
    
            $queryParams = array(
                'startDateTime' => $startOfWeek->format(\DateTimeInterface::ISO8601),
                'endDateTime' => $endOfWeek->format(\DateTimeInterface::ISO8601),
                // Only request the properties used by the app
                '$select' => 'subject,start,end,location,webLink',
                // Sort them by start time
                '$orderby' => 'start/dateTime',
                // Limit results to 25
                '$top' => 25
              );
    
            $getEventsUrl = '/me/calendarView?'.http_build_query($queryParams);
    
            $events = $graph->createRequest('GET', $getEventsUrl)
                // Add the user's timezone to the Prefer header
                ->addHeaders(array(
                'Prefer' => 'outlook.timezone="'."China Standard Time".'"'
                ))
                ->setReturnType(Event::class)
                ->execute();
    
            foreach ($events as $event) {
                $properties = $event->getProperties();
    
                $timeStart = substr($properties['start']['dateTime'], 11, 8);
                $timeEnd = substr($properties['end']['dateTime'], 11, 8);
                $allDay = $timeStart == '00:00:00' && $timeEnd == '00:00:00';
    
                $eventsSchool[] = [
                    'title' => $event->getSubject(),
                    'subtitle' => $properties['location']['displayName'],
                    'allDay' => $allDay,
                    'timeStart' => $timeStart,
                    'timeEnd' => $timeEnd,
                    'link' => $event->getWebLink(),
                ];
            }
    
            return $eventsSchool;
        }
    
        $ssoGoogle = $this->settingGateway->getSettingByScope('System Admin', 'ssoGoogle');
        $ssoGoogle = json_decode($ssoGoogle, true);
    
        
    
        if (!empty($ssoGoogle) && $ssoGoogle['enabled'] == 'Y' && $this->session->has('googleAPIAccessToken') && $this->session->has('googleAPICalendarEnabled')) {
    
            $eventsSchool = [];
            $start = date("Y-m-d\TH:i:s", strtotime(date('Y-m-d', $startDayStamp)));
            $end = date("Y-m-d\TH:i:s", (strtotime(date('Y-m-d', $endDayStamp)) + 86399));
    
            $service = $this->getContainer()->get('Google_Service_Calendar');
            $getFail = empty($service);
    
            $calendarListEntry = array();
    
            try {
                $optParams = array('timeMin' => $start.'+00:00', 'timeMax' => $end.'+00:00', 'singleEvents' => true);
                $calendarListEntry = $service->events->listEvents($calendarFeed, $optParams);
            } catch (\Exception $e) {
                $getFail = true;
            }
    
            if ($getFail) {
                $eventsSchool = [];
            } else {
                $count = 0;
                foreach ($calendarListEntry as $entry) {
                    $hideEvent = false;
    
                    // Prevent displaying events that this user has declined
                    $email = $this->session->get('email');
                    $attendees = $entry['attendees'] ?? [];
                    foreach ($attendees as $attendee) {
                        if (!empty($attendee['email']) && $attendee['email'] != $email) continue;
                        if (!empty($attendee['responseStatus']) && strtolower($attendee['responseStatus'])  == 'declined') {
                            $hideEvent = true;
                        }
                    }
    
                    if ($hideEvent) continue;
                    
                    $multiDay = false;
                    
                    if (empty($entry['start']['dateTime'])) {
                        if ((strtotime($entry['end']['date']) - strtotime($entry['start']['date'])) / (60 * 60 * 24) > 1) {
                            $multiDay = true;
                        }
                    } elseif (substr($entry['start']['dateTime'], 0, 10) != substr($entry['end']['dateTime'], 0, 10)) {
                        $multiDay = true;
                    }

                    $eventsSchool[$count]['date'] = substr($entry['start']['dateTime'], 0, 10);
    
                    if ($multiDay) { //This event spans multiple days
                        if ($entry['start']['date'] != $entry['start']['end']) {
                            $days = (strtotime($entry['end']['date']) - strtotime($entry['start']['date'])) / (60 * 60 * 24);
                        } elseif (substr($entry['start']['dateTime'], 0, 10) != substr($entry['end']['dateTime'], 0, 10)) {
                            $days = (strtotime(substr($entry['end']['dateTime'], 0, 10)) - strtotime(substr($entry['start']['dateTime'], 0, 10))) / (60 * 60 * 24);
                            ++$days; //A hack for events that span multiple days with times set
                        }
                        for ($i = 0; $i < $days; ++$i) {
                            //WHAT
                            $eventsSchool[$count]['title'] = $entry['summary'];
                            $eventsSchool[$count]['date'] = date('Y-m-d', strtotime($entry['start']['date']) + ($i * 60 * 60 * 24));
    
                            //WHEN - treat events that span multiple days, but have times set, the same as those without time set
                            $eventsSchool[$count]['allDay'] = true;
    
                            //WHERE
                            $eventsSchool[$count]['subtitle'] = $entry['location'];
    
                            //LINK
                            $eventsSchool[$count]['link'] = $entry['htmlLink'];
    
                            ++$count;
                        }
                    } else {  //This event falls on a single day
                        //WHAT
                        $eventsSchool[$count]['title'] = $entry['summary'];
                        $eventsSchool[$count]['date'] = $entry['start']['date'] ?? substr($entry['start']['dateTime'], 0, 10);
    
                        //WHEN
                        if ($entry['start']['dateTime'] != '') { //Part of day
                            $eventsSchool[$count]['allDay'] = false;
                            $eventsSchool[$count]['timeStart'] = substr($entry['start']['dateTime'], 11, 8);
                            $eventsSchool[$count]['timeEnd'] = substr($entry['end']['dateTime'], 11, 8);
                        } else { //All day
                            $eventsSchool[$count]['allDay'] = true;
                        }
                        //WHERE
                        $eventsSchool[$count]['subtitle'] = $entry['location'];
    
                        //LINK
                        $eventsSchool[$count]['link'] = $entry['htmlLink'];
    
                        ++$count;
                    }
                }
            }
        } else {
            $eventsSchool = [];
        }
    
        $this->session->set($calendarEventsCache, $eventsSchool);
    
        return $eventsSchool;
    }
}
