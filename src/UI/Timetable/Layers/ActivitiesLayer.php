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
use Gibbon\UI\Timetable\TimetableContext;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\Activities\ActivityGateway;

/**
 * Timetable UI: ActivitiesLayer
 *
 * @version  v29
 * @since    v29
 */
class ActivitiesLayer extends AbstractTimetableLayer
{
    protected $activityGateway;
    protected $settingGateway;

    public function __construct(SettingGateway $settingGateway, ActivityGateway $activityGateway)
    {
        $this->activityGateway = $activityGateway;
        $this->settingGateway = $settingGateway;

        $this->name = 'Activities';
        $this->color = 'purple';
        $this->order = 10;
    }

    public function checkAccess(TimetableContext $context) : bool
    {
        return Access::allows('Activities', 'explore') || Access::allows('Activities', 'activities_view');
    }
    
    public function loadItems(\DatePeriod $dateRange, TimetableContext $context) 
    {
        if (!$context->has('gibbonSchoolYearID')) return;
    
        $dateType = $this->settingGateway->getSettingByScope('Activities', 'dateType');

        if ($context->has('gibbonPersonID')) {
            $activityList = $this->activityGateway->selectActiveEnrolledActivities($context->get('gibbonSchoolYearID'), $context->get('gibbonPersonID'), $dateType, $dateRange->getStartDate()->format('Y-m-d'))->fetchAll();
        } elseif ($context->has('gibbonSpaceID')) {
            $activityList = $this->activityGateway->selectActivitiesByFacility($context->get('gibbonSchoolYearID'), $context->get('gibbonSpaceID'), $dateType)->fetchAll();
        }

        $canViewActivities = Access::allows('Activities', 'activities_my');

        foreach ($dateRange as $dateObject) {
            $date = $dateObject->format('Y-m-d');
            $weekday = $dateObject->format('l');
            foreach ($activityList as $activity) {
                // Add activities that match the weekday and the school is open
                if (empty($activity['dayOfWeek']) || $activity['dayOfWeek'] != $weekday) continue;
                if ($date < $activity['dateStart'] || $date > $activity['dateEnd'] ) continue;

                $this->createItem($date)->loadData([
                    'type'    => __('Activity'),
                    'title'     => $activity['name'],
                    'subtitle'  => !empty($activity['space'])? $activity['space'] : $activity['locationExternal'] ?? '',
                    'link'      => $canViewActivities ? Url::fromModuleRoute('Activities', 'activities_my') : '',
                    'timeStart' => $activity['timeStart'],
                    'timeEnd'   => $activity['timeEnd'],
                ]);

            }
        }
    }
}
