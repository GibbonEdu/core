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
    ->add(__('Delete Contributor'));

if (isActionAccessible($guid, $connection2, '/modules/Interventions/intervention_support_plan_contributor_delete.php') == false) {
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
    $gibbonINSupportPlanContributorID = $_GET['gibbonINSupportPlanContributorID'] ?? '';
    
    if (empty($gibbonINInterventionID) || empty($gibbonINSupportPlanID) || empty($gibbonINSupportPlanContributorID)) {
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
    
    if (!$isAdmin && !$isCoordinator) {
        $page->addError(__('You do not have access to this action.'));
        return;
    }
    
    // Get contributor details
    $data = [
        'gibbonINSupportPlanContributorID' => $gibbonINSupportPlanContributorID,
        'gibbonINSupportPlanID' => $gibbonINSupportPlanID
    ];
    $sql = "SELECT gibbonINSupportPlanContributor.*, gibbonPerson.title, gibbonPerson.surname, gibbonPerson.preferredName 
            FROM gibbonINSupportPlanContributor 
            JOIN gibbonPerson ON (gibbonPerson.gibbonPersonID=gibbonINSupportPlanContributor.gibbonPersonID) 
            WHERE gibbonINSupportPlanContributorID=:gibbonINSupportPlanContributorID 
            AND gibbonINSupportPlanID=:gibbonINSupportPlanID";
    $resultContributor = $pdo->executeQuery($data, $sql);
    
    if ($resultContributor->rowCount() != 1) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }
    
    $contributor = $resultContributor->fetch();
    
    // Show confirmation form
    $form = DeleteForm::createForm($_SESSION[$guid]['absoluteURL'].'/modules/Interventions/intervention_support_plan_contributor_deleteProcess.php', true, false);
    $form->addHiddenValue('address', $_SESSION[$guid]['address']);
    $form->addHiddenValue('gibbonINInterventionID', $gibbonINInterventionID);
    $form->addHiddenValue('gibbonINSupportPlanID', $gibbonINSupportPlanID);
    $form->addHiddenValue('gibbonINSupportPlanContributorID', $gibbonINSupportPlanContributorID);
    
    echo $form->getOutput();
    
    // Display contributor info
    echo '<div class="message">';
    echo sprintf(__('Are you sure you want to delete %1$s as a contributor to this support plan?'), Format::name($contributor['title'], $contributor['preferredName'], $contributor['surname'], 'Staff'));
    echo '<br/>';
    echo '<span class="emphasis small">';
    echo __('This operation cannot be undone.');
    echo '</span>';
    echo '</div>';
}
