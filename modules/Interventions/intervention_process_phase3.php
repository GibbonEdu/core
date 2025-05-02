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

use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Tables\DataTable;
use Gibbon\Module\Interventions\Domain\INSupportPlanGateway;

// Add the support plan gateway
$supportPlanGateway = $container->get(INSupportPlanGateway::class);

// Get current user ID
$gibbonPersonID = $_SESSION[$guid]['gibbonPersonID'];

// PHASE 3: SUPPORT PLAN
echo '<h2>'.__('Phase 3: Support Plan').'</h2>';
echo '<p class="emphasis small">'.__('Define goals, strategies, and resources for the intervention').'</p>';

// Add a message if the intervention is not yet at this phase
if ($intervention['status'] != 'Intervention Required' && $intervention['status'] != 'Support Plan Active' && !$isAdmin) {
    echo '<div class="error">';
    echo __('This intervention is not yet ready for a support plan.');
    echo '</div>';
    return;
}

// Get existing support plans for this intervention
$supportPlans = $supportPlanGateway->getSupportPlansByIntervention($gibbonINInterventionID);
$activeSupportPlan = $supportPlanGateway->getActiveSupportPlan($gibbonINInterventionID);

// Display existing support plans
if ($supportPlans && $supportPlans->rowCount() > 0) {
    $table = DataTable::create('supportPlans');
    $table->setTitle(__('Support Plans'));

    // Add action to create a new support plan
    $table->addHeaderAction('add', __('Add New Support Plan'))
        ->setURL('/modules/Interventions/intervention_support_plan_add.php')
        ->addParam('gibbonINInterventionID', $gibbonINInterventionID)
        ->addParam('returnProcess', true)
        ->displayLabel();

    $table->addColumn('name', __('Name'));
    $table->addColumn('status', __('Status'))
        ->format(function($values) {
            $statuses = [
                'Draft' => "<span class='tag dull'>".__('Draft')."</span>",
                'Active' => "<span class='tag success'>".__('Active')."</span>",
                'Completed' => "<span class='tag attention'>".__('Completed')."</span>",
                'Cancelled' => "<span class='tag error'>".__('Cancelled')."</span>"
            ];
            return $statuses[$values['status']] ?? $values['status'];
        });
    
    $table->addColumn('dateStart', __('Start Date'))
        ->format(Format::using('date', ['dateStart']));
    
    $table->addColumn('targetDate', __('Target Date'))
        ->format(Format::using('date', ['targetDate']));
    
    $table->addColumn('staffName', __('Staff Responsible'))
        ->format(function($values) {
            return Format::name($values['staffTitle'], $values['staffPreferredName'], $values['staffSurname'], 'Staff');
        });

    // Add actions column
    $table->addActionColumn()
        ->addParam('gibbonINInterventionID', $gibbonINInterventionID)
        ->addParam('gibbonINSupportPlanID')
        ->format(function ($supportPlan, $actions) use ($guid, $isAdmin, $gibbonPersonID) {
            // View action
            $actions->addAction('view', __('View'))
                ->setURL('/modules/Interventions/intervention_support_plan_view.php');

            // Edit action - only for admin or creator
            if ($isAdmin || $supportPlan['gibbonPersonIDCreator'] == $gibbonPersonID) {
                $actions->addAction('edit', __('Edit'))
                    ->setURL('/modules/Interventions/intervention_support_plan_edit.php');
            }
            
            // Activate/Deactivate action - only for admin or creator
            if ($isAdmin || $supportPlan['gibbonPersonIDCreator'] == $gibbonPersonID) {
                if ($supportPlan['status'] == 'Active') {
                    $actions->addAction('deactivate', __('Deactivate'))
                        ->setURL('/modules/Interventions/intervention_support_plan_status.php')
                        ->addParam('status', 'Draft')
                        ->setIcon('contract');
                } elseif ($supportPlan['status'] == 'Draft') {
                    $actions->addAction('activate', __('Activate'))
                        ->setURL('/modules/Interventions/intervention_support_plan_status.php')
                        ->addParam('status', 'Active')
                        ->setIcon('expand');
                }
            }
            
            // Delete action - only for admin
            if ($isAdmin && $supportPlan['status'] != 'Active') {
                $actions->addAction('delete', __('Delete'))
                    ->setURL('/modules/Interventions/intervention_support_plan_delete.php')
                    ->modalWindow(650, 400);
            }
        });

    // Convert the Result object to an array for the DataTable
    $supportPlansArray = $supportPlans->fetchAll();
    echo $table->render($supportPlansArray);

    // If there's an active support plan, show its details
    if ($activeSupportPlan) {
        echo '<h3>'.__('Active Support Plan Details').'</h3>';
        echo '<div class="message">';
        echo '<p><strong>'.__('Name').':</strong> '.$activeSupportPlan['name'].'</p>';
        if (!empty($activeSupportPlan['description'])) {
            echo '<p><strong>'.__('Description').':</strong> '.$activeSupportPlan['description'].'</p>';
        }
        echo '<p><strong>'.__('Goals').':</strong> '.$activeSupportPlan['goals'].'</p>';
        echo '<p><strong>'.__('Strategies').':</strong> '.$activeSupportPlan['strategies'].'</p>';
        if (!empty($activeSupportPlan['resources'])) {
            echo '<p><strong>'.__('Resources').':</strong> '.$activeSupportPlan['resources'].'</p>';
        }
        echo '<p><strong>'.__('Target Date').':</strong> '.Format::date($activeSupportPlan['targetDate']).'</p>';
        echo '<p><strong>'.__('Staff Responsible').':</strong> '.Format::name($activeSupportPlan['staffTitle'], $activeSupportPlan['staffPreferredName'], $activeSupportPlan['staffSurname'], 'Staff').'</p>';
        echo '<p><strong>'.__('Date Started').':</strong> '.Format::date($activeSupportPlan['dateStart']).'</p>';
        echo '</div>';
    }

    // Add explanatory text about the workflow
    echo '<div class="message emphasis">';
    echo '<p><strong>'.__('Workflow Information').':</strong></p>';
    echo '<p>'.__('You can create multiple support plans for this intervention. Only one plan can be active at a time.').'</p>';
    echo '<p>'.__('When a support plan is activated, the intervention will move to Phase 4: Implementation.').'</p>';
    echo '</div>';
} else {
    // No support plans exist yet, show form to create the first one
    echo '<div class="message">';
    echo '<p>'.__('No support plans have been created for this intervention yet. Please create your first support plan below.').'</p>';
    echo '</div>';

    // Create the form for the first support plan
    $form = Form::create('supportPlanAdd', $_SESSION[$guid]['absoluteURL'].'/modules/Interventions/intervention_support_plan_addProcess.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->setClass('w-full');

    $form->addHiddenValue('address', $_SESSION[$guid]['address']);
    $form->addHiddenValue('gibbonINInterventionID', $gibbonINInterventionID);

    $row = $form->addRow();
        $row->addLabel('name', __('Support Plan Name'))->description(__('Give this support plan a descriptive name'));
        $row->addTextField('name')->required()->maxLength(100);

    $row = $form->addRow();
        $row->addLabel('description', __('Description'))->description(__('Brief description of this support plan'));
        $row->addTextArea('description')->setRows(2);

    $row = $form->addRow();
        $row->addLabel('goals', __('Goals'))->description(__('What are the specific goals for this intervention?'));
        $row->addTextArea('goals')->setRows(3)->required();

    $row = $form->addRow();
        $row->addLabel('strategies', __('Strategies'))->description(__('What strategies will be used to achieve these goals?'));
        $row->addTextArea('strategies')->setRows(3)->required();

    $row = $form->addRow();
        $row->addLabel('resources', __('Resources'))->description(__('What resources will be needed for this intervention?'));
        $row->addTextArea('resources')->setRows(3);

    // Target Date
    $row = $form->addRow();
        $row->addLabel('targetDate', __('Target Completion Date'))->description(__('When should this intervention be completed by?'));
        $row->addDate('targetDate')->required();

    // Staff Responsible
    $row = $form->addRow();
        $row->addLabel('gibbonPersonIDStaff', __('Staff Responsible'))->description(__('Who will be implementing this intervention?'));
        $row->addSelectStaff('gibbonPersonIDStaff')->required();

    // Add explanatory text about the workflow
    $row = $form->addRow();
    $row->addContent('<div class="message emphasis">');
    $row->addContent('<p><strong>'.__('Workflow Information').':</strong></p>');
    $row->addContent('<p>'.__('Creating this support plan will allow you to activate it and move the intervention to Phase 4: Implementation.').'</p>');
    $row->addContent('</div>');

    // Add the submit button
    $row = $form->addRow();
    $row->addSubmit(__('Create Support Plan'));

    // Display the form
    echo $form->getOutput();
}
