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

use Gibbon\Forms\Prefab\DeleteForm;
use Gibbon\Module\Interventions\Domain\INSupportPlanGateway;
use Gibbon\Module\Interventions\Domain\INSupportPlanNoteGateway;

// Module includes
require_once __DIR__ . '/moduleFunctions.php';

$page->breadcrumbs
    ->add(__('Manage Interventions'), 'interventions.php')
    ->add(__('Delete Support Plan Note'));

if (isActionAccessible($guid, $connection2, '/modules/Interventions/intervention_support_plan_note_delete.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Get action with highest precedence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        $page->addError(__('The highest grouped action cannot be determined.'));
        return;
    }

    // Get parameters
    $gibbonINInterventionID = $_GET['gibbonINInterventionID'] ?? '';
    $gibbonINSupportPlanID = $_GET['gibbonINSupportPlanID'] ?? '';
    $gibbonINSupportPlanNoteID = $_GET['gibbonINSupportPlanNoteID'] ?? '';
    
    if (empty($gibbonINInterventionID) || empty($gibbonINSupportPlanID) || empty($gibbonINSupportPlanNoteID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    // Get support plan details
    $supportPlanGateway = $container->get(INSupportPlanGateway::class);
    $supportPlan = $supportPlanGateway->getByID($gibbonINSupportPlanID);

    if (empty($supportPlan) || $supportPlan['gibbonINInterventionID'] != $gibbonINInterventionID) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }
    
    // Get note details
    $supportPlanNoteGateway = $container->get(INSupportPlanNoteGateway::class);
    $note = $supportPlanNoteGateway->getByID($gibbonINSupportPlanNoteID);
    
    if (empty($note) || $note['gibbonINSupportPlanID'] != $gibbonINSupportPlanID) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    // Check access to this note
    $gibbonPersonID = $_SESSION[$guid]['gibbonPersonID'];
    $isAdmin = isActionAccessible($guid, $connection2, '/modules/Interventions/interventions_manage.php', 'Manage Interventions_all');
    
    if (!$isAdmin && $note['gibbonPersonID'] != $gibbonPersonID) {
        $page->addError(__('You do not have access to this action.'));
        return;
    }

    // Show confirmation form
    $form = DeleteForm::createForm($_SESSION[$guid]['absoluteURL'].'/modules/Interventions/intervention_support_plan_note_deleteProcess.php', true, false);
    $form->addHiddenValue('address', $_SESSION[$guid]['address']);
    $form->addHiddenValue('gibbonINInterventionID', $gibbonINInterventionID);
    $form->addHiddenValue('gibbonINSupportPlanID', $gibbonINSupportPlanID);
    $form->addHiddenValue('gibbonINSupportPlanNoteID', $gibbonINSupportPlanNoteID);
    
    echo $form->getOutput();
}
