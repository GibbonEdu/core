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

use Gibbon\Forms\Form;
use Gibbon\Module\Interventions\Domain\INInterventionGateway;
use Gibbon\Module\Interventions\Domain\INSupportPlanGateway;

// Module includes
require_once __DIR__ . '/moduleFunctions.php';

$page->breadcrumbs
    ->add(__('Manage Interventions'), 'interventions.php')
    ->add(__('Change Support Plan Status'));

if (isActionAccessible($guid, $connection2, '/modules/Interventions/intervention_support_plan_status.php') == false) {
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
    $status = $_GET['status'] ?? '';
    
    if (empty($gibbonINInterventionID) || empty($gibbonINSupportPlanID) || empty($status)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    // Get intervention details
    $interventionGateway = $container->get(INInterventionGateway::class);
    $intervention = $interventionGateway->getByID($gibbonINInterventionID);

    if (empty($intervention)) {
        $page->addError(__('The specified record cannot be found.'));
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
    
    if (!$isAdmin && $supportPlan['gibbonPersonIDCreator'] != $gibbonPersonID) {
        $page->addError(__('You do not have access to this action.'));
        return;
    }

    // Validate status
    $validStatuses = ['Draft', 'Active', 'Completed', 'Cancelled'];
    if (!in_array($status, $validStatuses)) {
        $page->addError(__('Invalid status specified.'));
        return;
    }

    // Create the form
    $form = Form::create('supportPlanStatus', $_SESSION[$guid]['absoluteURL'].'/modules/Interventions/intervention_support_plan_statusProcess.php');
    $form->setClass('w-full');

    $form->addHiddenValue('address', $_SESSION[$guid]['address']);
    $form->addHiddenValue('gibbonINInterventionID', $gibbonINInterventionID);
    $form->addHiddenValue('gibbonINSupportPlanID', $gibbonINSupportPlanID);
    $form->addHiddenValue('status', $status);

    // Add confirmation message
    $statusLabels = [
        'Draft' => __('Draft'),
        'Active' => __('Active'),
        'Completed' => __('Completed'),
        'Cancelled' => __('Cancelled')
    ];
    
    $row = $form->addRow();
    $row->addContent(sprintf(__('Are you sure you want to change the status of this support plan to "%1$s"?'), $statusLabels[$status]));
    
    // If changing to Completed or Cancelled, add outcome fields
    if ($status == 'Completed' || $status == 'Cancelled') {
        $outcomes = [
            'Successful' => __('Successful'),
            'Partially Successful' => __('Partially Successful'),
            'Not Successful' => __('Not Successful')
        ];
        
        $row = $form->addRow();
            $row->addLabel('outcome', __('Outcome'))->description(__('Was this support plan successful?'));
            $row->addSelect('outcome')->fromArray($outcomes)->placeholder()->required();
            
        $row = $form->addRow();
            $row->addLabel('outcomeNotes', __('Outcome Notes'))->description(__('Notes about the outcome of this support plan'));
            $row->addTextArea('outcomeNotes')->setRows(3);
    }

    // Add the submit button
    $row = $form->addRow();
    $row->addSubmit(__('Confirm'));

    // Display the form
    echo $form->getOutput();
}
