<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

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

use Gibbon\Domain\Activities\ActivityGateway;
use Gibbon\Domain\Activities\ActivityCategoryGateway;

if (isActionAccessible($guid, $connection2, '/modules/Activities/explore_category.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__('Explore Activities'), 'explore.php')
        ->add(__('Category'));

    $gibbonActivityCategoryID = $_REQUEST['gibbonActivityCategoryID'] ?? '';

    $canViewInactive = isActionAccessible($guid, $connection2, '/modules/Activities/activities_manage.php');

    $categoryGateway = $container->get(ActivityCategoryGateway::class);
    $activityGateway = $container->get(ActivityGateway::class);

    if (empty($gibbonActivityCategoryID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $category = $categoryGateway->getCategoryDetailsByID($gibbonActivityCategoryID);

    if (empty($category)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    if ($category['active'] != 'Y' && !$canViewInactive) {
        $page->addError(__('You do not have access to this action.'));
        return;
    }

    if ($category['viewable'] != 'Y' && !$canViewInactive) {
        $page->addMessage(__m('This activity is not viewable at this time. Please return to the categories page to explore a different activity.'));
        return;
    }

    // Query experiences
    $criteria = $activityGateway->newQueryCriteria()
        ->filterBy('active', 'Y')
        ->filterBy('category', $gibbonActivityCategoryID)
        ->sortBy(['name'])
        ->fromPOST();

    $activities = $activityGateway->queryActivitiesBySchoolYear($criteria, $session->get('gibbonSchoolYearID'));
    // $photos = $categoryGateway->selectPhotosByActivityCategory($gibbonActivityCategoryID)->fetchGroupedUnique();

    $page->writeFromTemplate('activities.twig.html', [
        'category'        => $category,
        'activities'      => $activities->toArray(),
        // 'photos'          => $photos,
        'canViewInactive' => $canViewInactive,
    ]);
}
