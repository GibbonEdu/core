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
use Gibbon\Services\Format;
use Gibbon\Support\Facades\Access;
use Gibbon\UI\Timetable\TimetableContext;
use Gibbon\Domain\Timetable\TimetableDayGateway;
use Gibbon\Domain\Timetable\TimetableDayDateGateway;
use Gibbon\Domain\School\SchoolYearSpecialDayGateway;
use Gibbon\Domain\Planner\PlannerEntryGateway;
use Gibbon\Domain\User\UserGateway;
use Gibbon\Contracts\Services\Session;

/**
 * Timetable UI: ClassesLayer
 *
 * @version  v29
 * @since    v29
 */
class ClassesLayer extends AbstractTimetableLayer
{
    protected $session;

    protected $plannerEntryGateway;
    protected $timetableDayGateway;
    protected $timetableDayDateGateway;
    protected $specialDayGateway;
    protected $userGateway;
    protected $actionGateway;

    public function __construct(Session $session, PlannerEntryGateway $plannerEntryGateway, TimetableDayGateway $timetableDayGateway, TimetableDayDateGateway $timetableDayDateGateway, SchoolYearSpecialDayGateway $specialDayGateway, UserGateway $userGateway)
    {
        $this->session = $session;

        $this->plannerEntryGateway = $plannerEntryGateway;
        $this->timetableDayGateway = $timetableDayGateway;
        $this->timetableDayDateGateway = $timetableDayDateGateway;
        $this->specialDayGateway = $specialDayGateway;
        $this->userGateway = $userGateway;

        $this->name = 'Classes';
        $this->color = 'blue';
        $this->order = 20;
    }

    public function checkAccess(TimetableContext $context) : bool
    {
        return true;
    }
    
    public function loadItems(\DatePeriod $dateRange, TimetableContext $context) 
    {
        if ($context->has('gibbonPersonID')) {
            $this->loadItemsByPerson($dateRange, $context);
        } else if ($context->has('gibbonSpaceID')) {
            $this->loadItemsByFacility($dateRange, $context);
        }
    }

    protected function loadItemsByPerson(\DatePeriod $dateRange, TimetableContext $context) 
    {
        $specialDays = $this->specialDayGateway->selectSpecialDaysByDateRange($dateRange->getStartDate()->format('Y-m-d'), $dateRange->getEndDate()->format('Y-m-d'))->fetchGroupedUnique();

        $offTimetable = array_reduce($specialDays, function ($group, $item) use ($context) {
            $group[$item['date']] = $this->specialDayGateway->getIsStudentOffTimetableByDate($context->get('gibbonSchoolYearID'), $context->get('gibbonPersonID'), $item['date']) ? $item['name'] : '';

            return $group;
        }, []);

        $lessons = $this->plannerEntryGateway->selectPlannerEntriesByPersonAndDateRange($context->get('gibbonPersonID'), $dateRange->getStartDate()->format('Y-m-d'), $dateRange->getEndDate()->format('Y-m-d'))->fetchGroupedUnique();

        $classes = $this->timetableDayDateGateway->selectTimetabledPeriodsByPersonAndDateRange($context->get('gibbonPersonID'), $dateRange->getStartDate()->format('Y-m-d'), $dateRange->getEndDate()->format('Y-m-d'))->fetchAll();

        $ttRowClassIDs = array_column($classes, 'gibbonTTDayRowClassID');
        $classTeachers = $this->timetableDayGateway->selectTTDayRowClassTeachersByID($ttRowClassIDs)->fetchGrouped();

        $canViewLessons = Access::allows('Planner', 'planner_view_full');
        $canAddLessons = Access::allows('Planner', 'planner_add') && $this->session->get('gibbonPersonID') == $context->get('gibbonPersonID');
        $canViewClasses = Access::allows('Departments', 'department_course_class');
        $canViewCoverage = Access::allows('Staff', 'coverage_my');
        $canEditCoverage = Access::allows('Staff', 'coverage_view_edit') && $this->session->get('gibbonPersonID') == $context->get('gibbonPersonID');

        foreach ($classes as $class) {
            $teachers = $classTeachers[$class['gibbonTTDayRowClassID']] ?? [];
            $specialDay = $specialDays[$class['date']] ?? [];

            $item = $this->createItem($class['date'])->loadData([
                'type'          => __('Class'),
                'period'        => $class['period'],
                'title'         => Format::courseClassName($class['courseNameShort'], $class['classNameShort']),
                'label'         => $class['courseName'],
                'description'   => !empty($teachers) 
                    ? __n('Teacher', 'Teachers', count($teachers)).': '. Format::nameList($teachers, 'Staff', false, true, ', ') : '',
                'subtitle'      => $class['roomName'] ?? '',
                'location'      => $class['roomName'] ?? '',
                'phone'         => $class['phone'] ?? '',
                'timeStart'     => $class['timeStart'],
                'timeEnd'       => $class['timeEnd'],
                'link'          => $canViewClasses ? Url::fromModuleRoute('Departments', 'department_course_class')->withQueryParams(['gibbonCourseClassID' => $class['gibbonCourseClassID'], 'currentDate' => $class['date']]) : '',
            ]);

            // Handle off timetable days
            if (!empty($specialDay) && $specialDay['type'] == 'Off Timetable') {
                if (!empty($offTimetable[$class['date']]) || $this->specialDayGateway->getIsClassOffTimetableByDate($class['gibbonSchoolYearID'], $class['gibbonCourseClassID'], $class['date'])) {
                    $item->addStatus('offTimetable')
                        ->set('type', __('Off Timetable'))
                        ->set('subtitle', $specialDay['name'])
                        ->set('style', 'stripe')
                        ->set('color', 'gray');
                }
            }

            // Handle room changes
            if (!empty($class['spaceChanged']) && !$item->hasStatus('offTimetable')) {
                $item->addStatus('spaceChanged')
                    ->set('location', $class['roomNameChange'] ?? __('No Facility'))
                    ->set('subtitle', $class['roomNameChange'] ?? __('No Facility'))
                    ->set('phone', $class['phoneChange']);
            }

            // Handle covered class
            if ($canViewCoverage && !empty($class['coverageID'])) {
                $person = $this->userGateway->getByID($class['coveragePerson'], ['title', 'surname', 'preferredName']);
                $description = !empty($person)
                    ? __('Covered by {name}', ['name' => Format::name($person['title'], $person['preferredName'], $person['surname'], 'Staff', false, true)])
                    : __('Coverage').': '.$class['coverageStatus'];

                $item->addStatus('covered')
                    ->set('description', $description);

                $item->set('secondaryAction', [
                    'name'      => 'cover',
                    'label'     => $description,
                    'url'       => $canEditCoverage ? Url::fromModuleRoute('Staff', 'coverage_view_edit')->withQueryParams(['viewBy' => 'class', 'gibbonStaffCoverageID' => $class['coverageID']]) : Url::fromModuleRoute('Staff', 'report_absences_weekly'),
                    'icon'      => 'user',
                    'iconClass' => !empty($person) ? 'text-pink-500 hover:text-pink-800' : 'text-gray-600 hover:text-gray-800',
                ]);
            }

            $planner = $lessons[$class['lessonID']] ?? [];
            if (!empty($planner)) {
                unset($lessons[$class['lessonID']]);
            }
            
            if ($context->get('edit')) {
                // Add timetable-editing buttons for Edit mode
                $canEditTimetable = Access::allows('Timetable Admin', 'courseEnrolment_manage_byPerson_edit');
                if ($canEditTimetable && $class['date'] >= date('Y-m-d')) {
                    $item->set('primaryAction', [
                        'name'      => 'change',
                        'label'     => __('Add Facility Change'),
                        'url'       => Url::fromModuleRoute('Timetable', 'spaceChange_manage_add')->withQueryParams(['step' => '2', 'gibbonTTDayRowClassID' => $class['gibbonTTDayRowClassID'].'-'.$class['date'], 'gibbonCourseClassID' => $class['gibbonCourseClassID'], 'source' => $context->get('gibbonSpaceID')]),
                        'icon'      => 'next',
                        'iconClass' => 'text-gray-600 hover:text-gray-800',
                    ]);
                }

                if ($canEditTimetable) {
                    $item->set('secondaryAction', [
                        'name'      => 'edit',
                        'label'     => __('Add Exception'),
                        'url'       => Url::fromModuleRoute('Timetable Admin', 'tt_edit_day_edit_class_exception_addProcess')->withQueryParams(['gibbonSchoolYearID' => $context->get('gibbonSchoolYearID'), 'gibbonTTID' => $class['gibbonTTID'], 'gibbonTTDayID' => $class['gibbonTTDayID'], 'gibbonTTDayRowClassID' => $class['gibbonTTDayRowClassID'], 'gibbonTTColumnRowID' => $class['gibbonTTColumnRowID'], 'gibbonCourseClassID' => $class['gibbonCourseClassID'], 'gibbonPersonID' => $context->get('gibbonPersonID')])->directLink(),
                        'icon'      => 'user-minus',
                        'iconClass' => 'text-gray-600 hover:text-gray-800',
                    ]);
                }
            } else {
                // Add buttons to access or create lesson plans
                if ($canViewLessons && !empty($planner)) {
                    $item->set('primaryAction', [
                        'name'      => 'view',
                        'label'     => __('Lesson planned: {name}',['name' => htmlPrep($planner['name'])]),
                        'url'       => Url::fromModuleRoute('Planner', 'planner_view_full')->withQueryParams(['viewBy' => 'class', 'gibbonCourseClassID' => $planner['gibbonCourseClassID'], 'gibbonPlannerEntryID' => $planner['gibbonPlannerEntryID'], 'search' => $context->get('gibbonPersonID')]),
                        'icon'      => 'check',
                        'iconClass' => 'text-blue-500 hover:text-blue-800',
                    ]);
                }
                if ($canAddLessons && empty($planner)) {
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

        foreach ($lessons as $lesson) {
            $specialDay = $specialDays[$class['date']] ?? [];

            $item = $this->createItem($lesson['date'])->loadData([
                'type'          => __('Lesson'),
                'title'         => $lesson['name'],
                'period'        => $lesson['period'],
                'label'         => $lesson['name'],
                'description'   => __('Course').': ' .$lesson['courseName'].'<br>'.__('Class').': '.Format::courseClassName($lesson['courseNameShort'], $lesson['classNameShort']),
                'subtitle'      => $lesson['unitName'] ?? '',
                'timeStart'     => $lesson['timeStart'],
                'timeEnd'       => $lesson['timeEnd'],
                'link'          => Url::fromModuleRoute('Planner', 'planner_view_full')->withQueryParams(['viewBy' => 'class', 'gibbonCourseClassID' => $lesson['gibbonCourseClassID'], 'gibbonPlannerEntryID' => $lesson['gibbonPlannerEntryID']]),
            ]);

            // Add a button for the lesson plan
            $item->set('primaryAction', [
                'name'      => 'view',
                'label'     => __('Lesson planned: {name}',['name' => htmlPrep($lesson['name'])]),
                'url'       => Url::fromModuleRoute('Planner', 'planner_view_full')->withQueryParams(['viewBy' => 'class', 'gibbonCourseClassID' => $lesson['gibbonCourseClassID'], 'gibbonPlannerEntryID' => $lesson['gibbonPlannerEntryID']]),
                'icon'      => 'check',
                'iconClass' => 'text-blue-500 hover:text-blue-800',
            ]);

            // Handle off timetable days
            if (!empty($specialDay) && $specialDay['type'] == 'Off Timetable') {
                if ($this->specialDayGateway->getIsClassOffTimetableByDate($lesson['gibbonSchoolYearID'], $lesson['gibbonCourseClassID'], $lesson['date'])) {
                    $item->addStatus('offTimetable')
                        ->set('type', __('Off Timetable'))
                        ->set('subtitle', $specialDay['name'])
                        ->set('style', 'stripe')
                        ->set('color', 'gray');
                }
            }

        }

        // Add off timetable days as all day events for students
        foreach ($offTimetable as $date => $specialDayName) {
            if (!empty($specialDayName)) {
                $this->createItem($date, true)->loadData([
                    'type'      => __('Off Timetable'),
                    'title'     => $specialDayName,
                    'allDay'    => true,
                    'timeStart' => null,
                    'timeEnd'   => null,
                    'color'     => 'gray',
                    'style'     => 'stripe',
                ]);
            }
        }
    }

    public function loadItemsByFacility(\DatePeriod $dateRange, TimetableContext $context) 
    {
        $specialDays = $this->specialDayGateway->selectSpecialDaysByDateRange($dateRange->getStartDate()->format('Y-m-d'), $dateRange->getEndDate()->format('Y-m-d'))->fetchGroupedUnique();

        $classes = $this->timetableDayDateGateway->selectTimetabledPeriodsByFacilityAndDateRange($context->get('gibbonSpaceID'), $dateRange->getStartDate()->format('Y-m-d'), $dateRange->getEndDate()->format('Y-m-d'))->fetchAll();

        $ttRowClassIDs = array_column($classes, 'gibbonTTDayRowClassID');
        $classTeachers = $this->timetableDayGateway->selectTTDayRowClassTeachersByID($ttRowClassIDs)->fetchGrouped();

        $canViewClasses = Access::allows('Departments', 'department_course_class');
        $canAddChanges = Access::allows('Timetable', 'spaceChange_manage_add');
        $canEditTTDays = Access::allows('Timetable Admin', 'tt_edit_day_edit_class_edit');

        foreach ($classes as $class) {
            $specialDay = $specialDays[$class['date']] ?? [];

            $teachers = $classTeachers[$class['gibbonTTDayRowClassID']] ?? [];

            $item = $this->createItem($class['date'])->loadData([
                'type'          => __('Class'),
                'period'        => $class['period'],
                'title'         => Format::courseClassName($class['courseNameShort'], $class['classNameShort']),
                'label'         => $class['courseName'],
                'description'   => !empty($teachers) 
                    ? __n('Teacher', 'Teachers', count($teachers)).': '. Format::nameList($teachers, 'Staff', false, true, ', ') : '',
                'subtitle'      => $class['roomName'] ?? '',
                'location'      => $class['roomName'] ?? '',
                'phone'         => $class['phone'] ?? '',
                'timeStart'     => $class['timeStart'],
                'timeEnd'       => $class['timeEnd'],
                'link'          => $canViewClasses ? Url::fromModuleRoute('Departments', 'department_course_class')->withQueryParams(['gibbonCourseClassID' => $class['gibbonCourseClassID'], 'currentDate' => $class['date']]) : '',
            ]);

            // Handle off timetable days
            if (!empty($specialDay) && $specialDay['type'] == 'Off Timetable') {
                if ($this->specialDayGateway->getIsClassOffTimetableByDate($class['gibbonSchoolYearID'], $class['gibbonCourseClassID'], $class['date'])) {
                    $item->addStatus('offTimetable')
                        ->set('type', __('Off Timetable'))
                        ->set('subtitle', $specialDay['name'])
                        ->set('style', 'stripe')
                        ->set('color', 'gray');
                }
            }

            // Handle room changes
            if (!empty($class['spaceChanged']) && !$item->hasStatus('offTimetable')) {
                $item->addStatus('spaceChanged');
            }

            $gibbonTTDayRowClassID = str_pad($class['gibbonTTDayRowClassID'], 12, '0', STR_PAD_LEFT);

            if ($canAddChanges && $class['date'] >= date('Y-m-d')) {
                $item->set('primaryAction', [
                    'name'      => 'change',
                    'label'     => __('Add Facility Change'),
                    'url'       => Url::fromModuleRoute('Timetable', 'spaceChange_manage_add')->withQueryParams(['step' => '2', 'gibbonTTDayRowClassID' => $gibbonTTDayRowClassID.'-'.$class['date'], 'gibbonCourseClassID' => $class['gibbonCourseClassID'], 'source' => $context->get('gibbonSpaceID')]),
                    'icon'      => 'next',
                    'iconClass' => 'text-gray-600 hover:text-gray-800',
                ]);
            }

            if ($canEditTTDays) {
                $item->set('secondaryAction', [
                    'name'      => 'edit',
                    'label'     => __('Edit Class in Period'),
                    'url'       => Url::fromModuleRoute('Timetable Admin', 'tt_edit_day_edit_class_edit')->withQueryParams(['gibbonSchoolYearID' => $context->get('gibbonSchoolYearID'), 'gibbonTTID' => $class['gibbonTTID'], 'gibbonTTDayID' => $class['gibbonTTDayID'], 'gibbonTTDayRowClassID' => $gibbonTTDayRowClassID, 'gibbonTTColumnRowID' => $class['gibbonTTColumnRowID'], 'gibbonCourseClassID' => $class['gibbonCourseClassID']]),
                    'icon'      => 'edit',
                    'iconClass' => 'text-gray-600 hover:text-gray-800',
                ]);
            }
        }
    }
}
