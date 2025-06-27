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
use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;
use Gibbon\Domain\Activities\ActivityGateway;
use Gibbon\Domain\Students\StudentGateway;

if (isActionAccessible($guid, $connection2, '/modules/Activities/activities_view_myChildren.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Get action with highest precedence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if (empty($highestAction)) {
        $page->addError(__('The highest grouped action cannot be determined.'));
        return;
    }
    
    $page->breadcrumbs->add(__('View Activities'));

    $gibbonSchoolYearID = $session->get('gibbonSchoolYearID');
    $gibbonPersonID = $session->get('gibbonPersonID');

    $activityGateway = $container->get(ActivityGateway::class);
    $studentGateway = $container->get(StudentGateway::class);
    $children = $studentGateway->selectActiveStudentsByFamilyAdult($gibbonSchoolYearID, $gibbonPersonID)->fetchAll();

    if (empty($children)) {
        echo Format::alert(__('There are no records to display.'), 'message');
        return;
    }

    $canExplore = isActionAccessible($guid, $connection2, '/modules/Activities/explore.php');

    foreach ($children as $child) {
        
        $criteria = $activityGateway->newQueryCriteria()
            ->sortBy(['sequenceNumber', 'accessOpenDate'])
            ->fromPOST();

        $activities = $activityGateway->queryActivitiesByParticipant($criteria, $gibbonSchoolYearID, $child['gibbonPersonID']);

        $table = DataTable::create('activities');
        $table->setTitle(Format::name('', $child['preferredName'], $child['surname'], 'Student', false, true));

        $table->addColumn('category', __('Category'))
            ->sortable(['category'])
            ->context('primary')
            ->width('20%')
            ->format(function ($activity) use ($canExplore) {
                $url = Url::fromModuleRoute('Activities', 'explore_category.php')->withQueryParams(['gibbonActivityCategoryID' => $activity['gibbonActivityCategoryID'], 'sidebar' => 'false']);
                return $canExplore 
                    ? Format::link($url, $activity['category'])
                    : $activity['category'];
            });

            $table->addColumn('choices', __('Activity'))
                ->context('primary')
                ->width('40%')
                ->format(function ($activity) {
                    if (empty($activity['choices'])) {
                        return $activity['name'].'<br/>'.Format::small($activity['type']);
                    }
                    
                    $choices = explode(',', $activity['choices']);
                    return Format::small(__('Activity Choices')).':<br/>'.Format::list($choices, 'ol', 'ml-2 my-0 text-xs');
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
            ->addParam('gibbonActivityCategoryID')
            ->addParam('gibbonActivityID')
            ->format(function ($activity, $actions) use ($canExplore) {
                if ($canExplore && !empty($activity['gibbonActivityID'])) {
                    $actions->addAction('view', __('View Details'))
                        // ->isModal(1000, 650)
                        ->addParam('sidebar', 'false')
                        ->setURL('/modules/Activities/explore_activity.php');
                }
            });

        echo $table->render($activities);
    }
}
