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

        $lessons = $this->plannerEntryGateway->getPlannerEntriesByPersonAndDateRange($context->get('gibbonPersonID'), $dateRange->getStartDate()->format('Y-m-d'), $dateRange->getEndDate()->format('Y-m-d'))->fetchGroupedUnique();

        $classIDs = array_unique(array_merge(array_column($classes, 'gibbonCourseClassID'), array_column($lessons, 'gibbonCourseClassID')));

        // TODO: Handle off timetable classes
        // TODO: Teacher name, phone number, etc.
        // TODO: Handle coverage (covered by not covering)

        foreach ($classes as $class) {
        
            $item = $this->createItem($class['date'])->loadData([
                'type'          => __('Class'),
                'period'        => $class['period'],
                'title'         => Format::courseClassName($class['courseNameShort'], $class['classNameShort']),
                'label'         => $class['courseName'],
                'description'   => __('Teacher').': '.'Teacher Name',
                'subtitle'      => $class['roomNameChange'] ?? $class['roomName'] ?? '',
                'location'      => $class['roomNameChange'] ?? $class['roomName'] ?? '',
                'phone'         => $class['phoneChange'] ?? $class['phone'] ?? '',
                'specialStatus' => !empty($class['roomNameChange']) ? 'roomchange' : '',
                'timeStart'     => $class['timeStart'],
                'timeEnd'       => $class['timeEnd'],
                'link'          => Url::fromModuleRoute('Departments', 'department_course_class')->withQueryParams(['gibbonCourseClassID' => $class['gibbonCourseClassID'], 'currentDate' => $class['date']]),
            ]);
            
            $planner = $lessons[$class['lessonID']] ?? [];

            if (!empty($planner)) {
                $item->set('primaryAction', [
                    'name'      => 'view',
                    'label'     => __('Lesson planned: {name}',['name' => htmlPrep($planner['name'])]),
                    'url'       => Url::fromModuleRoute('Planner', 'planner_view_full')->withQueryParams(['viewBy' => 'class', 'gibbonCourseClassID' => $planner['gibbonCourseClassID'], 'gibbonPlannerEntryID' => $planner['gibbonPlannerEntryID']]),
                    'icon'      => 'check',
                    'iconClass' => 'text-blue-500 hover:text-blue-800',
                ]);
                
                unset($lessons[$class['lessonID']]);
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

        foreach ($lessons as $lesson) {

            $item = $this->createItem($lesson['date'])->loadData([
                'type'          => __('Lesson'),
                'title'         => $lesson['name'],
                'label'         => $lesson['name'],
                'description'   => __('Course').': ' .$lesson['courseName'].'<br>'.__('Class').': '.Format::courseClassName($lesson['courseNameShort'], $lesson['classNameShort']),
                'subtitle'      => $lesson['unitName'] ?? '',
                'timeStart'     => $lesson['timeStart'],
                'timeEnd'       => $lesson['timeEnd'],
                'link'          => Url::fromModuleRoute('Planner', 'planner_view_full')->withQueryParams(['viewBy' => 'class', 'gibbonCourseClassID' => $lesson['gibbonCourseClassID'], 'gibbonPlannerEntryID' => $lesson['gibbonPlannerEntryID']]),
            ]);

            $item->set('primaryAction', [
                'name'      => 'view',
                'label'     => __('Lesson planned: {name}',['name' => htmlPrep($lesson['name'])]),
                'url'       => Url::fromModuleRoute('Planner', 'planner_view_full')->withQueryParams(['viewBy' => 'class', 'gibbonCourseClassID' => $lesson['gibbonCourseClassID'], 'gibbonPlannerEntryID' => $lesson['gibbonPlannerEntryID']]),
                'icon'      => 'check',
                'iconClass' => 'text-blue-500 hover:text-blue-800',
            ]);

        }
    }
}
