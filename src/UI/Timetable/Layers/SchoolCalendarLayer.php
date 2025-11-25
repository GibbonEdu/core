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
use Gibbon\UI\Timetable\TimetableContext;

/**
 * Timetable UI: SchoolCalendarLayer
 *
 * @version  v29
 * @since    v29
 */
class SchoolCalendarLayer extends AbstractCalendarLayer
{
    protected $session;

    public function __construct(Session $session)
    {
        $this->session = $session;

        $this->name = 'School Calendar';
        $this->color = 'green';
        $this->type = 'calendar';
        $this->order = 0;
    }

    public function checkAccess(TimetableContext $context) : bool
    {
        return $context->get('gibbonPersonID') == $this->session->get('gibbonPersonID') && $this->session->has('calendarFeed') && ($this->session->has('googleAPICalendarEnabled') || $this->session->has('microsoftAPIAccessToken'));
    }
    
    public function loadItems(\DatePeriod $dateRange, TimetableContext $context) 
    {
        if ($context->get('gibbonPersonID') != $this->session->get('gibbonPersonID')) return;
        
        if ($schoolCalendarFeed = $this->session->get('calendarFeed', null)) {
            $this->loadEventsByCalendarFeed($schoolCalendarFeed, $dateRange);
        }

        $this->sortOverlappingEvents($dateRange);
    }
}
