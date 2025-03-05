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
use Gibbon\Module\Interventions\Domain\INSupportPlanGateway;
use Gibbon\Module\Interventions\Domain\INSupportPlanNoteGateway;

// Module includes
require_once __DIR__ . '/moduleFunctions.php';

$page->breadcrumbs
    ->add(__('Manage Interventions'), 'interventions.php')
    ->add(__('Edit Support Plan Note'));

if (isActionAccessible($guid, $connection2, '/modules/Interventions/intervention_support_plan_note_edit.php') == false) {
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

    // Create the form
    $form = Form::create('supportPlanNoteEdit', $_SESSION[$guid]['absoluteURL'].'/modules/Interventions/intervention_support_plan_note_editProcess.php');
    $form->setClass('w-full');

    $form->addHiddenValue('address', $_SESSION[$guid]['address']);
    $form->addHiddenValue('gibbonINInterventionID', $gibbonINInterventionID);
    $form->addHiddenValue('gibbonINSupportPlanID', $gibbonINSupportPlanID);
    $form->addHiddenValue('gibbonINSupportPlanNoteID', $gibbonINSupportPlanNoteID);

    // Display support plan info
    $row = $form->addRow();
    $row->addContent('<h4>'.__('Support Plan').': '.$supportPlan['name'].'</h4>');

    // Note details
    $row = $form->addRow();
        $row->addLabel('title', __('Title'))->description(__('Brief title for this note'));
        $row->addTextField('title')->required()->maxLength(100)->setValue($note['title']);

    $row = $form->addRow();
        $row->addLabel('note', __('Note'))->description(__('Details of progress, observations, or concerns'));
        $row->addTextArea('note')->setRows(5)->required()->setValue($note['note']);

    $row = $form->addRow();
        $row->addLabel('date', __('Date'))->description(__('Date of this note'));
        $row->addDate('date')->required()->setValue($note['date']);

    // Add the submit button
    $row = $form->addRow();
    $row->addSubmit(__('Update Note'));

    // Display the form
    echo $form->getOutput();
}
