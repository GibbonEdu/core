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
use Gibbon\Domain\Planner\PlannerEntryGateway;

/**
 * Timetable UI: ClassesLayer
 *
 * @version  v29
 * @since    v29
 */
class ClassesLayer extends AbstractTimetableLayer
{
    protected $timetableDayDateGateway;
    protected $plannerEntryGateway;

    public function __construct(TimetableDayDateGateway $timetableDayDateGateway, PlannerEntryGateway $plannerEntryGateway)
    {
        $this->timetableDayDateGateway = $timetableDayDateGateway;
        $this->plannerEntryGateway = $plannerEntryGateway;

        $this->name = 'Classes';
        $this->color = 'blue';
        $this->order = 1;
    }
    
    public function loadItems(\DatePeriod $dateRange, TimetableContext $context) 
    {
        if (!$context->has('gibbonPersonID')) return;

        $classes = $this->timetableDayDateGateway->selectTimetabledPeriodsByPersonAndDateRange($context->get('gibbonPersonID'), $dateRange->getStartDate()->format('Y-m-d'), $dateRange->getEndDate()->format('Y-m-d'))->fetchAll();

        $ids = array_column($classes, 'gibbonTTDayRowClassID');

        // Todo: Handle off timetable classes

        foreach ($classes as $class) {
        
            $item = $this->createItem($class['date'])->loadData([
                'type'          => $class['period'],
                'title'         => Format::courseClassName($class['course'], $class['class']),
                'subtitle'      => $class['roomNameChange'] ?? $class['roomName'] ?? '',
                'specialStatus' => !empty($class['roomNameChange']) ? 'roomchange' : '',
                'timeStart'     => $class['timeStart'],
                'timeEnd'       => $class['timeEnd'],
            ]);
            
            $planner = $this->plannerEntryGateway->getPlannerEntryByClassTimes($class['gibbonCourseClassID'], $class['date'], $class['timeStart'], $class['timeEnd']);

            if (!empty($planner)) {
                $item->set('primaryAction', [
                    'name'      => 'add',
                    'label'     => __('Lesson planned: {name}',['name' => htmlPrep($planner['name'])]),
                    'url'       => Url::fromModuleRoute('Planner', 'planner_view_full')->withQueryParams(['viewBy' => 'class', 'gibbonCourseClassID' => $planner['gibbonCourseClassID'], 'gibbonPlannerEntryID' => $planner['gibbonPlannerEntryID']]),
                    'icon'      => 'check',
                    'iconClass' => 'text-blue-500 hover:text-blue-800',
                ]);
            } else {
                $item->set('primaryAction', [
                    'name'      => 'add',
                    'label'     => __('Add lesson plan'),
                    'url'       => Url::fromModuleRoute('Planner', 'planner_add')->withQueryParams(['viewBy' => 'class', 'gibbonCourseClassID' => $class['gibbonCourseClassID'], 'date' => $class['date'], 'timeStart' => $class['timeStart'], 'timeEnd' => $class['timeEnd']]),
                    'icon'      => 'add',
                    'iconClass' => 'text-gray-600 hover:text-gray-800',
                ]);
            }
            
        }
    }
}
