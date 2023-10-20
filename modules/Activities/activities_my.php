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

use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Domain\Activities\ActivityGateway;
use Gibbon\Domain\System\SettingGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Activities/activities_my.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $page->breadcrumbs->add(__('My Activities')); 

    $highestAction = getHighestGroupedAction($guid, '/modules/Activities/activities_attendance.php', $connection2);
    $canAccessEnrolment = isActionAccessible($guid, $connection2, '/modules/Activities/activities_manage_enrolment.php');

    $activityGateway = $container->get(ActivityGateway::class);
    
    // CRITERIA
    $criteria = $activityGateway->newQueryCriteria()
        ->sortBy('name')
        ->fromArray($_POST);

    $activities = $activityGateway->queryActivitiesByParticipant($criteria, $session->get('gibbonSchoolYearID'), $session->get('gibbonPersonID'));

    // DATA TABLE
    $table = DataTable::createPaginated('myActivities', $criteria);

    $table->addColumn('name', __('Activity'))
        ->format(function ($activity) {
            return $activity['name'].'<br/><span class="small emphasis">'.$activity['type'].'</span>';
        });
    $table->addColumn('role', __('Role'))
        ->format(function ($activity) {
            return !empty($activity['role']) ? $activity['role'] : __('Student');
        });

    $table->addColumn('status', __('Status'))
        ->format(function ($activity) {
            return !empty($activity['status']) ? $activity['status'] : '<i>'.__('N/A').'</i>';
        });

    $table->addActionColumn()
        ->addParam('gibbonActivityID')
        ->format(function ($activity, $actions) use ($highestAction, $canAccessEnrolment) {
            if ($activity['role'] == 'Organiser' &&  $canAccessEnrolment) {
                $actions->addAction('enrolment', __('Enrolment'))
                    ->addParam('gibbonSchoolYearTermID', '')
                    ->addParam('search', '')
                    ->setIcon('config')
                    ->setURL('/modules/Activities/activities_manage_enrolment.php');
            }

            $actions->addAction('view', __('View Details'))
                ->isModal(1000, 550)
                ->setURL('/modules/Activities/activities_my_full.php');

            if ($highestAction == "Enter Activity Attendance" || 
               ($highestAction == "Enter Activity Attendance_leader" && ($activity['role'] == 'Organiser' || $activity['role'] == 'Assistant' || $activity['role'] == 'Coach'))) {
                $actions->addAction('attendance', __('Attendance'))
                    ->setIcon('attendance')
                    ->setURL('/modules/Activities/activities_attendance.php');
            }
        });

    echo $table->render($activities);
}
