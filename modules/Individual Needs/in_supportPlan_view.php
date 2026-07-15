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

use Gibbon\Domain\Students\StudentGateway;
use Gibbon\Domain\IndividualNeeds\StudentSupportPlanGateway;

if (isActionAccessible($guid, $connection2, '/modules/Individual Needs/in_supportPlan_view.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $gibbonStudentSupportPlanID = $_GET['gibbonStudentSupportPlanID'] ?? '';
    $gibbonPersonID = $_GET['gibbonPersonID'] ?? '';

    if (empty($gibbonStudentSupportPlanID) || empty($gibbonPersonID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    $planGateway = $container->get(StudentSupportPlanGateway::class);
    $plan = $planGateway->getByID($gibbonStudentSupportPlanID);

    if (empty($plan)) {
        $page->addError(__('The selected record does not exist, or you do not have access to it.'));
        return;
    }

    // IDOR guard
    if ($plan['gibbonPersonID'] != $gibbonPersonID) {
        $page->addError(__('You do not have access to this action.'));
        return;
    }

    // Role-based access
    if ($highestAction == 'Student Support Plans_manage') {
        // No further restriction
    } elseif ($highestAction == 'Student Support Plans_view') {
        if ($plan['viewableStaff'] != 'Y' || $plan['active'] != 'Y') {
            $page->addError(__('You do not have access to this action.'));
            return;
        }
    } elseif ($highestAction == 'View Individual Education Plans_myChildren') {
        $gibbonSchoolYearID = $session->get('gibbonSchoolYearID');
        $children = $container->get(StudentGateway::class)
            ->selectAnyStudentsByFamilyAdult($gibbonSchoolYearID, $session->get('gibbonPersonID'))
            ->fetchGroupedUnique();
        if (empty($children[$gibbonPersonID]) || $plan['viewableParents'] != 'Y') {
            $page->addError(__('You do not have access to this action.'));
            return;
        }
    } else {
        $page->addError(__('You do not have access to this action.'));
        return;
    }

    $downloadURL = $session->get('absoluteURL').'/modules/Individual%20Needs/in_supportPlan_download.php'.'?gibbonStudentSupportPlanID='.urlencode($gibbonStudentSupportPlanID).'&gibbonPersonID='.urlencode($gibbonPersonID).'&action=view';

    echo '<h2>'.htmlspecialchars($plan['name']).'</h2>';

    if (!empty($plan['description'])) {
        echo '<p>'.htmlspecialchars($plan['description']).'</p>';
    }

    echo '<embed src="'.htmlspecialchars($downloadURL).'" type="application/pdf" class="w-full" style="min-height:620px;border:none;">';
}
