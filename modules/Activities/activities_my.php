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

use Gibbon\Http\Url;
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
    $canSignUp = isActionAccessible($guid, $connection2, '/modules/Activities/explore_activity_signUp.php', 'Explore Activities_studentRegister');
    $canExplore = isActionAccessible($guid, $connection2, '/modules/Activities/explore.php');

    $page->return->addReturns([
        'success1' => __('Your activity choices have been successfully recorded. You can view your activities below.'),
        'error4'   => __('Sign up is currently not available for this activity.'),
        'error5'   => __('There was an error verifying your activity choices. Please try again.'),
    ]);

    $activityGateway = $container->get(ActivityGateway::class);
    
    // CRITERIA
    $criteria = $activityGateway->newQueryCriteria()
        ->sortBy('sequenceNumber')
        ->fromArray($_POST);

    $activities = $activityGateway->queryActivitiesByParticipant($criteria, $session->get('gibbonSchoolYearID'), $session->get('gibbonPersonID'));

    // DATA TABLE
    $table = DataTable::createPaginated('myActivities', $criteria);

    $table->addColumn('category', __('Category'))
        ->width('15%')
        ->format(function ($activity) use ($canExplore) {
            $url = Url::fromModuleRoute('Activities', 'explore_category.php')->withQueryParams(['gibbonActivityCategoryID' => $activity['gibbonActivityCategoryID'], 'sidebar' => 'false']);
                return $canExplore 
                    ? Format::link($url, $activity['category'])
                    : $activity['category'];
        });

    $table->addColumn('name', __('Activity'))
        ->format(function ($activity) use ($canExplore) {
            if (empty($activity['choices'])) {
                return $activity['name'].'<br/>'.Format::small($activity['type']);
            }
            
            $choices = explode(',', $activity['choices']);
            return Format::small(__('Activity Choices')).':<br/>'.Format::list($choices, 'ol', 'ml-2 my-0 text-xs');
            
        });
    $table->addColumn('role', __('Role'))
        ->format(function ($activity) {
            return !empty($activity['role']) ? $activity['role'] : __('Student');
        });

    $table->addColumn('status', __('Status'))
        ->width('12%')
        ->format(function ($activity) {
            if (empty($activity['status'])) return Format::small(__('N/A'));

            return $activity['status'] == 'Pending' 
                        ? (!empty($activity['choices']) ? Format::tag($activity['status'], 'message')  : '')
                        : $activity['status'];
        });

    $table->addActionColumn()
        ->addParam('gibbonActivityID')
        ->format(function ($activity, $actions) use ($highestAction, $canAccessEnrolment, $canSignUp) {
            if (empty($activity['gibbonActivityID'])) {
                // Check that sign up is open based on the date
                $signUpIsOpen = false;

                if (!empty($activity['accessOpenDate']) && !empty($activity['accessCloseDate'])) {
                    $accessOpenDate = DateTime::createFromFormat('Y-m-d H:i:s', $activity['accessOpenDate'])->format('U');
                    $accessCloseDate = DateTime::createFromFormat('Y-m-d H:i:s', $activity['accessCloseDate'])->format('U');
                    $now = (new DateTime('now'))->format('U');

                    $signUpIsOpen = $accessOpenDate <= $now && $accessCloseDate >= $now;
                }

                if ($signUpIsOpen && $canSignUp) {
                    $actions->addAction('add', __('Sign Up'))
                            ->setURL('/modules/Activities/explore_activity_signUp.php')
                            ->addParam('gibbonActivityCategoryID', $activity['gibbonActivityCategoryID'])
                            ->setIcon('attendance')
                            ->modalWindow(750, 440);
                }

                return;
            }

            if ($activity['role'] == 'Organiser' &&  $canAccessEnrolment) {
                $actions->addAction('enrolment', __('Enrolment'))
                    ->addParam('gibbonSchoolYearTermID', '')
                    ->addParam('search', '')
                    ->setIcon('config')
                    ->setURL('/modules/Activities/activities_manage_enrolment.php');
            }

            $actions->addAction('view', __('View Details'))
                // ->isModal(1000, 650)
                ->addParam('sidebar', 'false')
                ->setURL('/modules/Activities/explore_activity.php');

            if ($highestAction == "Enter Activity Attendance" || 
               ($highestAction == "Enter Activity Attendance_leader" && ($activity['role'] == 'Organiser' || $activity['role'] == 'Assistant' || $activity['role'] == 'Coach'))) {
                $actions->addAction('attendance', __('Attendance'))
                    ->setIcon('attendance')
                    ->setURL('/modules/Activities/activities_attendance.php');
            }
        });

    echo $table->render($activities);
}
