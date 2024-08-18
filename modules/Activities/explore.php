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

use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\Activities\ActivityCategoryGateway;

if (isActionAccessible($guid, $connection2, '/modules/Activities/explore.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__m('Explore Activities'));

    $canViewInactive = isActionAccessible($guid, $connection2, '/modules/Activities/activities_manage.php');

    // Query events
    $categoryGateway = $container->get(ActivityCategoryGateway::class);

    $criteria = $categoryGateway->newQueryCriteria()
        ->filterBy('active', 'Y')
        ->sortBy(['gibbonActivityCategory.sequenceNumber'])
        ->fromPOST();

    $categories = $categoryGateway->queryCategories($criteria, $session->get('gibbonSchoolYearID'));

    $page->writeFromTemplate('categories.twig.html', [
        // 'welcomeText'     => $container->get(SettingGateway::class)->getSettingByScope('Activities', 'welcomeText'),
        'categories'      => $categories->toArray(),
        'canViewInactive' => $canViewInactive,
    ]);
}
