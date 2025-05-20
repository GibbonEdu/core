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
use Gibbon\Domain\Staff\StaffDutyPersonGateway;
use Gibbon\UI\Timetable\TimetableContext;

/**
 * Timetable UI: StaffDutyLayer
 *
 * @version  v29
 * @since    v29
 */
class StaffDutyLayer extends AbstractTimetableLayer
{
    protected $staffDutyPersonGateway;

    public function __construct(StaffDutyPersonGateway $staffDutyPersonGateway)
    {
        $this->staffDutyPersonGateway = $staffDutyPersonGateway;

        $this->name = 'Staff Duty';
        $this->color = 'yellow';
        $this->order = 30;
    }
    
    public function checkAccess(TimetableContext $context) : bool
    {
        return Access::allows('Staff', 'staff_duty') && $context->has('gibbonPersonID');
    }

    public function loadItems(\DatePeriod $dateRange, TimetableContext $context) 
    {
        $staffDutyList = $this->staffDutyPersonGateway->selectDutyByPerson($context->get('gibbonPersonID'))->fetchAll();

        foreach ($dateRange as $dateObject) {
            $date = $dateObject->format('Y-m-d');
            $weekday = $dateObject->format('l');
            foreach ($staffDutyList as $duty) {
                // Add duty that matched the weekday and the school is open
                if (empty($duty['dayOfWeek']) || $duty['dayOfWeek'] != $weekday) continue;

                $this->createItem($date)->loadData([
                    'type'    => __('Staff Duty'),
                    'label'     => $duty['name'],
                    'title'     => $duty['nameShort'],
                    'link'      => Url::fromModuleRoute('Staff', 'staff_duty'),
                    'timeStart' => $duty['timeStart'],
                    'timeEnd'   => $duty['timeEnd'],
                ]);
                
            }
        }
    }
}
