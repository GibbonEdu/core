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
use Gibbon\Domain\Staff\StaffCoverageGateway;
use Gibbon\Domain\Planner\PlannerEntryGateway;

/**
 * Timetable UI: StaffCoverLayer
 *
 * @version  v29
 * @since    v29
 */
class StaffCoverLayer extends AbstractTimetableLayer
{
    protected $staffCoverageGateway;
    protected $plannerEntryGateway;

    public function __construct(StaffCoverageGateway $staffCoverageGateway, PlannerEntryGateway $plannerEntryGateway)
    {
        $this->staffCoverageGateway = $staffCoverageGateway;
        $this->plannerEntryGateway = $plannerEntryGateway;

        $this->name = 'Staff Cover';
        $this->color = 'pink';
        $this->order = 50;
    }

    public function checkAccess(TimetableContext $context) : bool
    {
        return Access::allows('Staff', 'coverage_my')  && $context->has('gibbonPersonID') && $context->has('gibbonSchoolYearID');
    }
    
    public function loadItems(\DatePeriod $dateRange, TimetableContext $context) 
    {
        $criteria = $this->staffCoverageGateway->newQueryCriteria()
            ->filterBy('dateStart', $dateRange->getStartDate()->format('Y-m-d'))
            ->filterBy('dateEnd', $dateRange->getEndDate()->format('Y-m-d'))
            ->filterBy('status', 'Accepted');
                    
        $staffCoverage = $this->staffCoverageGateway->queryCoverageByPersonCovering($criteria, $context->get('gibbonSchoolYearID'), $context->get('gibbonPersonID'), false);

        $canViewPlanner = Access::allows('Planner', 'planner_view_full');

        foreach ($staffCoverage as $coverage) {
            $fullName = !empty($coverage['surnameAbsence']) 
                ? Format::name($coverage['titleAbsence'], $coverage['preferredNameAbsence'], $coverage['surnameAbsence'], 'Staff', false, true)
                : Format::name($coverage['titleStatus'], $coverage['preferredNameStatus'], $coverage['surnameStatus'], 'Staff', false, true);

            $item = $this->createItem($coverage['date'])->loadData([
                'type'        => __('Covering'),
                'title'       => $coverage['contextName'],
                'label'       => $coverage['courseName'],
                'subtitle'    => $coverage['roomName'] ?? '',
                'description' => __('Covering for {name}', ['name' => $fullName]),
                'location'    => $coverage['roomName'] ?? '',
                'phone'       => $coverage['phoneInternal'] ?? '',
                'allDay'      => $coverage['allDay'] == 'Y',
                'link'        => !empty($coverage['gibbonCourseClassID'])
                    ? Url::fromModuleRoute('Departments', 'department_course_class')->withQueryParams(['gibbonCourseClassID' => $coverage['gibbonCourseClassID'], 'currentDate' => $coverage['date']])
                    : Url::fromModuleRoute('Staff', 'coverage_my'),
                'timeStart'   => $coverage['timeStart'],
                'timeEnd'     => $coverage['timeEnd'],
            ]);

            // Handle room changes
            if (!empty($coverage['spaceChanged'])) {
                $item->addStatus('spaceChanged')
                    ->set('location', $coverage['roomNameChange'] ?? __('No Facility'))
                    ->set('subtitle', $coverage['roomNameChange'] ?? __('No Facility'))
                    ->set('phone', $coverage['phoneChange']);
            }

            $planner = !empty($coverage['gibbonCourseClassID']) 
                ? $this->plannerEntryGateway->getPlannerEntryByClassTimes($coverage['gibbonCourseClassID'], $coverage['date'], $coverage['timeStart'], $coverage['timeEnd'])
                : [];

            if (!empty($planner)) {
                $item->set('primaryAction', [
                    'name'      => 'view',
                    'label'     => __('Lesson planned: {name}',['name' => htmlPrep($planner['name'])]),
                    'url'       => $canViewPlanner ? Url::fromModuleRoute('Planner', 'planner_view_full')->withQueryParams(['viewBy' => 'class', 'gibbonCourseClassID' => $planner['gibbonCourseClassID'], 'gibbonPlannerEntryID' => $planner['gibbonPlannerEntryID']]) : '',
                    'icon'      => 'check',
                    'iconClass' => 'text-blue-500 hover:text-blue-800',
                ]);
            }

            $item->set('secondaryAction', [
                'name'      => 'cover',
                'label'     => __('Covering for {name}', ['name' => $fullName]),
                'url'       => Url::fromModuleRoute('Staff', 'coverage_my.php'),
                'icon'      => 'user',
                'iconClass' => !empty($fullName) ? 'text-pink-500 hover:text-pink-800' : 'text-gray-600 hover:text-gray-800',
            ]);
        }
    }
}
