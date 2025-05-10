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
 * Timetable UI: PersonalCalendarLayer
 *
 * @version  v29
 * @since    v29
 */
class PersonalCalendarLayer extends AbstractCalendarLayer
{
    protected $session;

    public function __construct(Session $session)
    {
        $this->session = $session;

        $this->name = 'Personal Calendar';
        $this->color = 'cyan';
        $this->type = 'optional';
        $this->order = 12;
    }
    
    public function loadItems(\DatePeriod $dateRange, TimetableContext $context) 
    {
        if ($context->get('gibbonPersonID') != $this->session->get('gibbonPersonID')) return;

        if ($personalCalendarFeed = $this->session->get('calendarFeedPersonal', null)) {
            $this->loadEventsByCalendarFeed($personalCalendarFeed, $dateRange, 'cyan');
        }

        $this->sortOverlappingEvents($dateRange);
    }
}
