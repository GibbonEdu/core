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
use Gibbon\Tables\Action;
use Gibbon\Services\Format;
use Gibbon\Domain\Timetable\TimetableDayDateGateway;
use Gibbon\UI\Timetable\TimetableContext;

/**
 * Timetable UI: ClassesLayer
 *
 * @version  v29
 * @since    v29
 */
class ClassesLayer extends AbstractTimetableLayer
{
    protected $timetableDayDateGateway;

    public function __construct(TimetableDayDateGateway $timetableDayDateGateway)
    {
        $this->timetableDayDateGateway = $timetableDayDateGateway;

        $this->name = 'Classes';
        $this->color = 'blue';
        $this->order = 1;
    }
    
    public function loadItems(\DatePeriod $dateRange, TimetableContext $context) 
    {
        if (!$context->has('gibbonTTID') || !$context->has('gibbonPersonID')) return;

        $classes = $this->timetableDayDateGateway->getTimetabledPeriodsByPersonAndDateRange($context->get('gibbonTTID'), $context->get('gibbonPersonID'), $dateRange->getStartDate()->format('Y-m-d'), $dateRange->getEndDate()->format('Y-m-d'))->fetchAll();

        foreach ($classes as $class) {
            $this->createItem($class['date'])->loadData([
                'type'      => $class['period'],
                'title'     => Format::courseClassName($class['course'], $class['class']),
                'subtitle'  => $class['roomName'],
                'timeStart' => $class['timeStart'],
                'timeEnd'   => $class['timeEnd'],
            ])->set('primaryAction', [
                'name'  => 'add',
                'label' => __('Add lesson plan'),
                'url'   => Url::fromModuleRoute('Planner', 'planner_add'),
                'icon'  => 'add',
            ]);
        }
    }
}
