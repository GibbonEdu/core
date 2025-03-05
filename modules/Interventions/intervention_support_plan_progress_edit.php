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

// Module includes
require_once __DIR__ . '/moduleFunctions.php';

$page->breadcrumbs
    ->add(__('Manage Interventions'), 'interventions.php')
    ->add(__('Edit Progress Report'));

if (isActionAccessible($guid, $connection2, '/modules/Interventions/intervention_support_plan_progress_edit.php') == false) {
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
        'gibbonPersonIDContributor' => $gibbonPersonID
    ];
    $sql = "SELECT * FROM gibbonINSupportPlanContributor 
            WHERE gibbonINSupportPlanID=:gibbonINSupportPlanID 
            AND gibbonPersonIDContributor=:gibbonPersonIDContributor 
            AND canEdit='Y'";
    $resultContributor = $pdo->executeQuery($data, $sql);
    $isContributor = ($resultContributor->rowCount() > 0);
    
    if (!$isAdmin && !$isCoordinator && !$isContributor) {
        $page->addError(__('You do not have access to this action.'));
        return;
    }
    
    // Get progress report details
    $data = [
        'gibbonINSupportPlanProgressID' => $gibbonINSupportPlanProgressID,
        'gibbonINSupportPlanID' => $gibbonINSupportPlanID
    ];
    $sql = "SELECT * FROM gibbonINSupportPlanProgress 
            WHERE gibbonINSupportPlanProgressID=:gibbonINSupportPlanProgressID 
            AND gibbonINSupportPlanID=:gibbonINSupportPlanID";
    $resultProgress = $pdo->executeQuery($data, $sql);
    
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

    // Create the form
    $form = Form::create('supportPlanProgressEdit', $_SESSION[$guid]['absoluteURL'].'/modules/Interventions/intervention_support_plan_progress_editProcess.php');
    $form->setClass('w-full');

    $form->addHiddenValue('address', $_SESSION[$guid]['address']);
    $form->addHiddenValue('gibbonINInterventionID', $gibbonINInterventionID);
    $form->addHiddenValue('gibbonINSupportPlanID', $gibbonINSupportPlanID);
    $form->addHiddenValue('gibbonINSupportPlanProgressID', $gibbonINSupportPlanProgressID);

    // Display support plan info
    $row = $form->addRow();
    $row->addContent('<h4>'.__('Support Plan').': '.$supportPlan['name'].'</h4>');
    
    // Get school year and term information for reporting cycles
    $data = ['gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']];
    $sql = "SELECT gibbonSchoolYear.name as yearName, 
                  CONCAT(gibbonSchoolYearTerm.name, ' (', 
                         DATE_FORMAT(gibbonSchoolYearTerm.firstDay, '%b %e'), ' - ', 
                         DATE_FORMAT(gibbonSchoolYearTerm.lastDay, '%b %e'), ')') as termName,
                  CONCAT(gibbonSchoolYear.name, ' - ', gibbonSchoolYearTerm.name) as reportingCycle
           FROM gibbonSchoolYear 
           JOIN gibbonSchoolYearTerm ON (gibbonSchoolYearTerm.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) 
           WHERE gibbonSchoolYear.gibbonSchoolYearID=:gibbonSchoolYearID 
           ORDER BY gibbonSchoolYearTerm.sequenceNumber";
    $resultTerms = $pdo->executeQuery($data, $sql);
    
    $reportingCycles = ($resultTerms->rowCount() > 0)? $resultTerms->fetchAll(\PDO::FETCH_KEY_PAIR) : [];

    // Progress report details
    $row = $form->addRow();
        $row->addLabel('reportingCycle', __('Reporting Cycle'));
        $row->addSelect('reportingCycle')
            ->fromArray($reportingCycles)
            ->required()
            ->placeholder()
            ->selected($progress['reportingCycle']);

    $row = $form->addRow();
        $row->addLabel('progressSummary', __('Progress Summary'))->description(__('Overall summary of progress during this reporting period'));
        $row->addTextArea('progressSummary')->setRows(5)->required()->setValue($progress['progressSummary']);

    $row = $form->addRow();
        $row->addLabel('goalProgress', __('Goal Progress'))->description(__('Specific progress towards the goals in the support plan'));
        $row->addTextArea('goalProgress')->setRows(5)->required()->setValue($progress['goalProgress']);

    $row = $form->addRow();
        $row->addLabel('nextSteps', __('Next Steps'))->description(__('Recommended next steps for the upcoming reporting period'));
        $row->addTextArea('nextSteps')->setRows(5)->required()->setValue($progress['nextSteps']);

    $row = $form->addRow();
        $row->addLabel('date', __('Date'))->description(__('Date of this progress report'));
        $row->addDate('date')->setValue($progress['date'])->required();

    // Add the submit button
    $row = $form->addRow();
    $row->addSubmit(__('Update Progress Report'));

    // Display the form
    echo $form->getOutput();
}
