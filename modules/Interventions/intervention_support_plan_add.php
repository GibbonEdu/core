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

// Module includes
require_once __DIR__ . '/moduleFunctions.php';

$page->breadcrumbs
    ->add(__('Manage Interventions'), 'interventions.php')
    ->add(__('Add Support Plan'));

if (isActionAccessible($guid, $connection2, '/modules/Interventions/intervention_support_plan_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Get action with highest precedence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        $page->addError(__('The highest grouped action cannot be determined.'));
        return;
    }

    // Get intervention ID
    $gibbonINInterventionID = $_GET['gibbonINInterventionID'] ?? '';
    $returnProcess = isset($_GET['returnProcess']) && $_GET['returnProcess'] == 'true';

    if (empty($gibbonINInterventionID)) {
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

    // Check access to this intervention
    $gibbonPersonID = $_SESSION[$guid]['gibbonPersonID'];
    $isAdmin = isActionAccessible($guid, $connection2, '/modules/Interventions/interventions_manage.php', 'Manage Interventions_all');
    $isCoordinator = isActionAccessible($guid, $connection2, '/modules/Interventions/interventions_manage.php', 'Manage Interventions_my');
    
    if (!$isAdmin && !$isCoordinator) {
        $page->addError(__('You do not have access to this action.'));
        return;
    }

    // Check if intervention is ready for a support plan
    if ($intervention['status'] != 'Intervention Required' && $intervention['status'] != 'Support Plan Active' && !$isAdmin) {
        $page->addError(__('This intervention is not yet ready for a support plan.'));
        return;
    }

    // Create the form
    $form = Form::create('supportPlanAdd', $_SESSION[$guid]['absoluteURL'].'/modules/Interventions/intervention_support_plan_addProcess.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->setClass('w-full');

    $form->addHiddenValue('address', $_SESSION[$guid]['address']);
    $form->addHiddenValue('gibbonINInterventionID', $gibbonINInterventionID);
    
    if ($returnProcess) {
        $form->addHiddenValue('returnProcess', 'true');
    }

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

    // Status
    $statuses = [
        'Draft' => __('Draft'),
        'Active' => __('Active')
    ];
    
    $row = $form->addRow();
        $row->addLabel('status', __('Status'))->description(__('Set as Active to implement this support plan immediately'));
        $row->addSelect('status')->fromArray($statuses)->required()->selected('Draft');

    // Add the submit button
    $row = $form->addRow();
    $row->addSubmit(__('Create Support Plan'));

    // Display the form
    echo $form->getOutput();
}
