<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright 2010, Gibbon Foundation
Gibbon, Gibbon Education Ltd. (Hong Kong)

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
use Gibbon\Services\Format;

// Module includes
require_once __DIR__ . '/moduleFunctions.php';

$page->breadcrumbs
    ->add(__('Manage Interventions'), 'interventions.php')
    ->add(__('Delete Progress Report'));

if (isActionAccessible($guid, $connection2, '/modules/Interventions/intervention_support_plan_progress_delete.php') == false) {
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
    $gibbonINSupportPlanProgressID = $_GET['gibbonINSupportPlanProgressID'] ?? '';
    
    if (empty($gibbonINInterventionID) || empty($gibbonINSupportPlanID) || empty($gibbonINSupportPlanProgressID)) {
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

    // Check access to this intervention
    $gibbonPersonID = $_SESSION[$guid]['gibbonPersonID'];
    $isAdmin = isActionAccessible($guid, $connection2, '/modules/Interventions/interventions_manage.php', 'Manage Interventions_all');
    $isCoordinator = isActionAccessible($guid, $connection2, '/modules/Interventions/interventions_manage.php', 'Manage Interventions_my');
    
    // Check if user is a contributor with edit rights
    $data = [
        'gibbonINSupportPlanID' => $gibbonINSupportPlanID,
        'gibbonPersonID' => $gibbonPersonID
    ];
    $sql = "SELECT * FROM gibbonINSupportPlanContributor 
            WHERE gibbonINSupportPlanID=:gibbonINSupportPlanID 
            AND gibbonPersonID=:gibbonPersonID 
            AND canEdit='Y'";
    $stmt = $connection2->prepare($sql);
    $stmt->execute($data);
    $resultContributor = $stmt->fetch();
    
    $isContributor = ($resultContributor !== false);
    
    if (!$isAdmin && !$isCoordinator && !$isContributor) {
        $page->addError(__('You do not have access to this action.'));
        return;
    }
    
    // Get progress report details
    $data = [
        'gibbonINSupportPlanProgressID' => $gibbonINSupportPlanProgressID,
        'gibbonINSupportPlanID' => $gibbonINSupportPlanID
    ];
    $sql = "SELECT gibbonINSupportPlanProgress.*, gibbonPerson.title, gibbonPerson.surname, gibbonPerson.preferredName 
            FROM gibbonINSupportPlanProgress 
            JOIN gibbonPerson ON (gibbonPerson.gibbonPersonID=gibbonINSupportPlanProgress.gibbonPersonID) 
            WHERE gibbonINSupportPlanProgressID=:gibbonINSupportPlanProgressID 
            AND gibbonINSupportPlanID=:gibbonINSupportPlanID";
    $resultProgress = $connection2->executeQuery($data, $sql);
    
    if ($resultProgress->rowCount() != 1) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }
    
    $progress = $resultProgress->fetch();
    
    // Check if user is the creator of the progress report or has admin/coordinator access
    if (!$isAdmin && !$isCoordinator && $progress['gibbonPersonID'] != $gibbonPersonID) {
        $page->addError(__('You do not have access to this action.'));
        return;
    }
    
    // Show confirmation form
    $form = DeleteForm::createForm($_SESSION[$guid]['absoluteURL'].'/modules/Interventions/intervention_support_plan_progress_deleteProcess.php', true, false);
    $form->addHiddenValue('address', $_SESSION[$guid]['address']);
    $form->addHiddenValue('gibbonINInterventionID', $gibbonINInterventionID);
    $form->addHiddenValue('gibbonINSupportPlanID', $gibbonINSupportPlanID);
    $form->addHiddenValue('gibbonINSupportPlanProgressID', $gibbonINSupportPlanProgressID);
    
    echo $form->getOutput();
    
    // Display progress report info
    echo '<div class="message">';
    echo sprintf(__('Are you sure you want to delete the progress report for %1$s from %2$s?'), $progress['reportingCycle'], Format::date($progress['date']));
    echo '<br/>';
    echo '<span class="emphasis small">';
    echo __('This operation cannot be undone.');
    echo '</span>';
    echo '</div>';
}
