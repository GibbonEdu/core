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

use Gibbon\Domain\Planner\PlannerEntryGateway;
use Gibbon\Domain\Planner\PlannerEntryHomeworkGateway;

//Gibbon system-wide includes
include '../../gibbon.php';

//Module includes
include './moduleFunctions.php';

$gibbonPlannerEntryID = $_GET['gibbonPlannerEntryID'] ?? '';
$gibbonPlannerEntryHomeworkID = $_GET['gibbonPlannerEntryHomeworkID'] ?? '';
$date = $_GET['date'] ?? '';
$gibbonCourseClassID = $_GET['gibbonCourseClassID'] ?? '';
$viewBy = $_GET['viewBy'] ?? '';
$subView = $_GET['subView'] ?? '';
$search = $_POST['search'] ?? '';
$URL = $session->get('absoluteURL')."/index.php?q=/modules/Planner/planner_view_full.php&date=$date&viewBy=$viewBy&subView=$subView&gibbonCourseClassID=$gibbonCourseClassID&gibbonPlannerEntryID=$gibbonPlannerEntryID&search=$search";

if (isActionAccessible($guid, $connection2, '/modules/Planner/planner_view_full.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    $highestAction = getHighestGroupedAction($guid, '/modules/Planner/planner_view_full.php', $connection2); 

    //Check if planner specified
    if ($gibbonPlannerEntryID == '' || $gibbonPlannerEntryHomeworkID == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    $plannerEntryGateway = $container->get(PlannerEntryGateway::class);
    $plannerEntryHomeworkGateway = $container->get(PlannerEntryHomeworkGateway::class);

    $values = $plannerEntryGateway->getByID($gibbonPlannerEntryID);
    if (empty($values)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    if ($highestAction == 'Lesson Planner_viewAllEditMyClasses' || $highestAction == "Lesson Planner_viewEditAllClasses") {
        $plannerEntryHomeworkGateway->delete($gibbonPlannerEntryHomeworkID);
    } else {
        $plannerEntryHomeworkGateway->deleteWhere(['gibbonPlannerEntryHomeworkID' => $gibbonPlannerEntryHomeworkID, 'gibbonPersonID' => $session->get('gibbonPersonID')]);
    }

    $URL .= '&return=success0';
    header("Location: {$URL}");    
}
