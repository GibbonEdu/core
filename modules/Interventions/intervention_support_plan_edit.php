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
use Gibbon\Services\Format;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Module\Interventions\Domain\INInterventionGateway;
use Gibbon\Module\Interventions\Domain\INSupportPlanGateway;

// Module includes
require_once __DIR__ . '/moduleFunctions.php';

$page->breadcrumbs
    ->add(__('Manage Interventions'), 'interventions.php')
    ->add(__('Edit Support Plan'));

if (isActionAccessible($guid, $connection2, '/modules/Interventions/intervention_support_plan_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Get action with highest precedence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        $page->addError(__('The highest grouped action cannot be determined.'));
        return;
    }

    // Get intervention ID and support plan ID
    $gibbonINInterventionID = $_GET['gibbonINInterventionID'] ?? '';
    $gibbonINSupportPlanID = $_GET['gibbonINSupportPlanID'] ?? '';

    if (empty($gibbonINInterventionID) || empty($gibbonINSupportPlanID)) {
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

    // Create the form
    $form = Form::create('supportPlanEdit', $_SESSION[$guid]['absoluteURL'].'/modules/Interventions/intervention_support_plan_editProcess.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->setClass('w-full');

    $form->addHiddenValue('address', $_SESSION[$guid]['address']);
    $form->addHiddenValue('gibbonINInterventionID', $gibbonINInterventionID);
    $form->addHiddenValue('gibbonINSupportPlanID', $gibbonINSupportPlanID);

    $row = $form->addRow();
        $row->addLabel('name', __('Support Plan Name'))->description(__('Give this support plan a descriptive name'));
        $row->addTextField('name')->required()->maxLength(100)->setValue($supportPlan['name']);

    $row = $form->addRow();
        $row->addLabel('description', __('Description'))->description(__('Brief description of this support plan'));
        $row->addTextArea('description')->setRows(2)->setValue($supportPlan['description']);

    $row = $form->addRow();
        $row->addLabel('goals', __('Goals'))->description(__('What are the specific goals for this intervention?'));
        $row->addTextArea('goals')->setRows(3)->required()->setValue($supportPlan['goals']);

    $row = $form->addRow();
        $row->addLabel('strategies', __('Strategies'))->description(__('What strategies will be used to achieve these goals?'));
        $row->addTextArea('strategies')->setRows(3)->required()->setValue($supportPlan['strategies']);

    $row = $form->addRow();
        $row->addLabel('resources', __('Resources'))->description(__('What resources will be needed for this intervention?'));
        $row->addTextArea('resources')->setRows(3)->setValue($supportPlan['resources']);

    // Target Date
    $row = $form->addRow();
        $row->addLabel('targetDate', __('Target Completion Date'))->description(__('When should this intervention be completed by?'));
        $row->addDate('targetDate')->required()->setValue($supportPlan['targetDate']);

    // Staff Responsible
    $row = $form->addRow();
        $row->addLabel('gibbonPersonIDStaff', __('Staff Responsible'))->description(__('Who will be implementing this intervention?'));
        $row->addSelectStaff('gibbonPersonIDStaff')->required()->selected($supportPlan['gibbonPersonIDStaff']);

    // Status - only if the plan is not already active
    if ($supportPlan['status'] != 'Active') {
        $statuses = [
            'Draft' => __('Draft'),
            'Active' => __('Active')
        ];
        
        $row = $form->addRow();
            $row->addLabel('status', __('Status'))->description(__('Set as Active to implement this support plan immediately'));
            $row->addSelect('status')->fromArray($statuses)->required()->selected($supportPlan['status']);
    } else {
        $form->addHiddenValue('status', $supportPlan['status']);
    }

    // If the plan is completed, allow outcome to be set
    if ($supportPlan['status'] == 'Completed') {
        $outcomes = [
            'Successful' => __('Successful'),
            'Partially Successful' => __('Partially Successful'),
            'Not Successful' => __('Not Successful')
        ];
        
        $row = $form->addRow();
            $row->addLabel('outcome', __('Outcome'))->description(__('Was this support plan successful?'));
            $row->addSelect('outcome')->fromArray($outcomes)->placeholder()->selected($supportPlan['outcome']);
            
        $row = $form->addRow();
            $row->addLabel('outcomeNotes', __('Outcome Notes'))->description(__('Notes about the outcome of this support plan'));
            $row->addTextArea('outcomeNotes')->setRows(3)->setValue($supportPlan['outcomeNotes']);
    }

    // Add the submit button
    $row = $form->addRow();
    $row->addSubmit(__('Update Support Plan'));

    // Display the form
    echo $form->getOutput();
}
