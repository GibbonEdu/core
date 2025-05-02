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
use Gibbon\Module\Interventions\Domain\INSupportPlanGateway;

// Module includes
require_once __DIR__ . '/moduleFunctions.php';

$page->breadcrumbs
    ->add(__('Manage Interventions'), 'interventions.php')
    ->add(__('Add Progress Report'));

if (isActionAccessible($guid, $connection2, '/modules/Interventions/intervention_support_plan_progress_add.php') == false) {
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
    
    if (empty($gibbonINInterventionID) || empty($gibbonINSupportPlanID)) {
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

    // Get the database connection
    $pdo = $container->get('db')->getConnection();

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
    $stmt = $pdo->prepare($sql);
    $stmt->execute($data);
    $resultContributor = $stmt->fetch();
    $isContributor = ($resultContributor !== false);
    
    if (!$isAdmin && !$isCoordinator && !$isContributor) {
        $page->addError(__('You do not have access to this action.'));
        return;
    }

    // Create the form
    $form = Form::create('supportPlanProgressAdd', $_SESSION[$guid]['absoluteURL'].'/modules/Interventions/intervention_support_plan_progress_addProcess.php');
    $form->setClass('w-full');

    $form->addHiddenValue('address', $_SESSION[$guid]['address']);
    $form->addHiddenValue('gibbonINInterventionID', $gibbonINInterventionID);
    $form->addHiddenValue('gibbonINSupportPlanID', $gibbonINSupportPlanID);

    // Display support plan info
    $row = $form->addRow();
    $row->addContent('<h4>'.__('Support Plan').': '.$supportPlan['name'].'</h4>');
    
    // Progress report details
    $row = $form->addRow();
        $row->addLabel('progressSummary', __('Progress Summary'))->description(__('Overall summary of progress during this reporting period'));
        $row->addTextArea('progressSummary')->setRows(5)->required();

    $row = $form->addRow();
        $row->addLabel('status', __('Status'));
        $row->addSelect('status')
            ->fromArray([
                'On Track' => __('On Track'),
                'Concerns' => __('Concerns'),
                'Achieved' => __('Achieved')
            ])
            ->required()
            ->selected('On Track');

    $row = $form->addRow();
        $row->addLabel('nextSteps', __('Next Steps'))->description(__('Recommended next steps for the upcoming reporting period'));
        $row->addTextArea('nextSteps')->setRows(5);

    $row = $form->addRow();
        $row->addLabel('date', __('Date'))->description(__('Date of this progress report'));
        $row->addDate('date')->setValue(date('Y-m-d'))->required();

    // Add the submit button
    $row = $form->addRow();
    $row->addSubmit(__('Add Progress Report'));

    // Display the form
    echo $form->getOutput();
}
