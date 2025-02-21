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

use Gibbon\UI\Timetable\TimetableContext;
use Gibbon\Domain\Timetable\FacilityBookingGateway;

/**
 * Timetable UI: BookingsLayer
 *
 * @version  v29
 * @since    v29
 */
class BookingsLayer extends AbstractTimetableLayer
{
    protected $facilityBookingGateway;

    public function __construct(FacilityBookingGateway $facilityBookingGateway)
    {
        $this->facilityBookingGateway = $facilityBookingGateway;

        $this->name = 'Bookings';
        $this->color = 'orange';
        $this->order = 3;
    }
    
    public function loadItems(\DatePeriod $dateRange, TimetableContext $context) 
    {
        $bookings = $this->facilityBookingGateway->selectFacilityBookingsByDateRange($dateRange->getStartDate()->format('Y-m-d'), $dateRange->getEndDate()->format('Y-m-d'), $context->get('gibbonPersonID'))->fetchAll();

        foreach ($bookings as $booking) {
            $this->createItem($booking['date'])->loadData([
                'type'    => __('Booking'),
                'title'     => $booking['reason'],
                'subtitle'  => $booking['name'],
                'timeStart' => $booking['timeStart'],
                'timeEnd'   => $booking['timeEnd'],
            ]);
        }
    }
}
