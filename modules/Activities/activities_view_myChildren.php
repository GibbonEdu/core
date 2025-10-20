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
use Gibbon\Domain\Students\StudentGateway;
use Gibbon\Module\Activities\Tables\ActivitiesViewParent;

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

    $children = $container->get(StudentGateway::class)->selectActiveStudentsByFamilyAdult($gibbonSchoolYearID, $gibbonPersonID)->fetchAll();

    if (empty($children)) {
        echo Format::alert(__('There are no records to display.'), 'message');
        return;
    }
    
    foreach ($children as $child) {
        echo $container->get(ActivitiesViewParent::class)
            ->createTable($gibbonSchoolYearID, $child['gibbonPersonID'], $child)
            ->getOutput();
    }
}
