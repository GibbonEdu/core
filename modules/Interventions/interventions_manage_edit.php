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
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;
use Gibbon\Domain\IndividualNeeds\INInterventionGateway;
use Gibbon\Domain\IndividualNeeds\INInterventionContributorGateway;
use Gibbon\Domain\IndividualNeeds\INInterventionStrategyGateway;
use Gibbon\Domain\IndividualNeeds\INInterventionOutcomeGateway;
use Gibbon\Domain\Staff\StaffGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Interventions/interventions_manage_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Get action with highest precedence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if (empty($highestAction)) {
        $page->addError(__('The highest grouped action cannot be determined.'));
    } else {
        // Proceed!
        $gibbonPersonID = $_GET['gibbonPersonID'] ?? '';
        $gibbonFormGroupID = $_GET['gibbonFormGroupID'] ?? '';
        $gibbonYearGroupID = $_GET['gibbonYearGroupID'] ?? '';
        $status = $_GET['status'] ?? '';

        $page->breadcrumbs
            ->add(__('Manage Interventions'), 'interventions_manage.php', [
                'gibbonPersonID' => $gibbonPersonID,
                'gibbonFormGroupID' => $gibbonFormGroupID,
                'gibbonYearGroupID' => $gibbonYearGroupID,
                'status' => $status,
            ])
            ->add(__('Edit Intervention'));

        $gibbonINInterventionID = $_GET['gibbonINInterventionID'] ?? '';
        if (empty($gibbonINInterventionID)) {
            $page->addError(__('You have not specified one or more required parameters.'));
            return;
        }

        $interventionGateway = $container->get(INInterventionGateway::class);
        $intervention = $interventionGateway->getInterventionByID($gibbonINInterventionID);

        if (empty($intervention)) {
            $page->addError(__('The specified record cannot be found.'));
            return;
        }

        // Check access based on the highest action level
        if ($highestAction == 'Manage Interventions_my' && $intervention['gibbonPersonIDCreator'] != $session->get('gibbonPersonID')) {
            $page->addError(__('You do not have access to this action.'));
            return;
        }

        $form = Form::create('intervention', $session->get('absoluteURL').'/modules/Interventions/interventions_manage_editProcess.php');
        $form->setFactory(DatabaseFormFactory::create($pdo));

        $form->addHiddenValue('address', $session->get('address'));
        $form->addHiddenValue('gibbonINInterventionID', $gibbonINInterventionID);
        $form->addHiddenValue('gibbonPersonID', $gibbonPersonID);
        $form->addHiddenValue('gibbonFormGroupID', $gibbonFormGroupID);
        $form->addHiddenValue('gibbonYearGroupID', $gibbonYearGroupID);
        $form->addHiddenValue('status', $status);

        // Get student details
        $studentName = Format::name('', $intervention['preferredName'], $intervention['surname'], 'Student', true);
        $form->addRow()->addHeading(__('Student Details'));
        $row = $form->addRow();
            $row->addLabel('studentName', __('Student'));
            $row->addTextField('studentName')->setValue($studentName)->readonly();

        $row = $form->addRow();
            $row->addLabel('formGroup', __('Form Group'));
            $row->addTextField('formGroup')->setValue($intervention['formGroup'])->readonly();

        // Intervention Details
        $form->addRow()->addHeading(__('Intervention Details'));
        $row = $form->addRow();
            $row->addLabel('name', __('Name'))->description(__('Brief name for this intervention'));
            $row->addTextField('name')->maxLength(100)->required();

        $row = $form->addRow();
            $row->addLabel('description', __('Description'))->description(__('Details about the intervention'));
            $row->addTextArea('description')->setRows(5)->required();

        // Form Tutor Review Section (Only visible to form tutors or admins)
        $isFormTutor = ($intervention['gibbonPersonIDFormTutor'] == $session->get('gibbonPersonID'));
        $isAdmin = ($highestAction == 'Manage Interventions');
        
        if ($isFormTutor || $isAdmin) {
            $form->addRow()->addHeading(__('Form Tutor Review'));
            
            $formTutorDecisions = [
                'Pending' => __('Pending'),
                'Resolvable' => __('Resolvable'),
                'Try Interventions' => __('Try Interventions'),
                'Referral' => __('Referral')
            ];
            
            $row = $form->addRow();
                $row->addLabel('formTutorDecision', __('Decision'));
                $row->addSelect('formTutorDecision')->fromArray($formTutorDecisions)->required();
            
            $row = $form->addRow();
                $row->addLabel('formTutorNotes', __('Notes'))->description(__('Explanation of decision'));
                $row->addTextArea('formTutorNotes')->setRows(5);
        }
        
        // Status Section
        $statusOptions = [
            'Referral' => __('Referral'),
            'Form Tutor Review' => __('Form Tutor Review'),
            'Intervention' => __('Intervention'),
            'Referral' => __('Referral'),
            'Resolved' => __('Resolved'),
            'Completed' => __('Completed')
        ];
        
        $row = $form->addRow();
            $row->addLabel('status', __('Status'));
            $row->addSelect('status')->fromArray($statusOptions)->required();
        
        // Outcome Section (Only visible if status is Intervention or later)
        if (in_array($intervention['status'], ['Intervention', 'Resolved', 'Completed']) || $isAdmin) {
            $form->addRow()->addHeading(__('Outcome'));
            
            $outcomeDecisions = [
                'Pending' => __('Pending'),
                'Success' => __('Success'),
                'Needs IEP' => __('Needs IEP')
            ];
            
            $row = $form->addRow();
                $row->addLabel('outcomeDecision', __('Decision'));
                $row->addSelect('outcomeDecision')->fromArray($outcomeDecisions)->required();
            
            $row = $form->addRow();
                $row->addLabel('outcomeNotes', __('Notes'))->description(__('Summary of intervention outcome'));
                $row->addTextArea('outcomeNotes')->setRows(5);
        }

        $row = $form->addRow();
            $row->addFooter();
            $row->addSubmit();

        $form->loadAllValuesFrom($intervention);

        echo $form->getOutput();

        // CONTRIBUTORS
        $contributorGateway = $container->get(INInterventionContributorGateway::class);
        
        $criteria = $contributorGateway->newQueryCriteria()
            ->sortBy(['timestampCreated'])
            ->fromPOST();

        $contributors = $contributorGateway->queryContributorsByIntervention($criteria, $gibbonINInterventionID);

        $table = DataTable::createPaginated('contributors', $criteria);
        $table->setTitle(__('Contributors'));

        $table->addHeaderAction('add', __('Add'))
            ->setURL('/modules/Interventions/interventions_manage_contributor_add.php')
            ->addParam('gibbonINInterventionID', $gibbonINInterventionID)
            ->addParam('gibbonPersonID', $gibbonPersonID)
            ->addParam('gibbonFormGroupID', $gibbonFormGroupID)
            ->addParam('gibbonYearGroupID', $gibbonYearGroupID)
            ->addParam('status', $status)
            ->displayLabel();

        $table->addColumn('name', __('Name'))
            ->format(function ($person) {
                return Format::name($person['title'], $person['preferredName'], $person['surname'], 'Staff', false, true);
            });

        $table->addColumn('type', __('Type'));
        $table->addColumn('timestampCreated', __('Date'))
            ->format(Format::using('dateTime', ['timestampCreated']));

        $table->addActionColumn()
            ->addParam('gibbonINInterventionContributorID')
            ->addParam('gibbonINInterventionID')
            ->addParam('gibbonPersonID', $gibbonPersonID)
            ->addParam('gibbonFormGroupID', $gibbonFormGroupID)
            ->addParam('gibbonYearGroupID', $gibbonYearGroupID)
            ->addParam('status', $status)
            ->format(function ($row, $actions) {
                $actions->addAction('delete', __('Delete'))
                    ->setURL('/modules/Interventions/interventions_manage_contributor_delete.php');
            });

        echo $table->render($contributors);
        
        // STRATEGIES
        if ($intervention['formTutorDecision'] == 'Try Interventions' || $intervention['status'] == 'Intervention') {
            $strategyGateway = $container->get(INInterventionStrategyGateway::class);
            
            $criteria = $strategyGateway->newQueryCriteria()
                ->sortBy(['targetDate'])
                ->fromPOST();
    
            $strategies = $strategyGateway->queryStrategies($criteria);
            $strategies->addFilterRules([
                'gibbonINInterventionID' => $gibbonINInterventionID
            ]);
    
            $table = DataTable::createPaginated('strategies', $criteria);
            $table->setTitle(__('Strategies'));
    
            $table->addHeaderAction('add', __('Add'))
                ->setURL('/modules/Interventions/interventions_manage_strategy_add.php')
                ->addParam('gibbonINInterventionID', $gibbonINInterventionID)
                ->addParam('gibbonPersonID', $gibbonPersonID)
                ->addParam('gibbonFormGroupID', $gibbonFormGroupID)
                ->addParam('gibbonYearGroupID', $gibbonYearGroupID)
                ->addParam('status', $status)
                ->displayLabel();
    
            $table->addColumn('name', __('Name'));
            $table->addColumn('status', __('Status'));
            $table->addColumn('targetDate', __('Target Date'))
                ->format(Format::using('date', ['targetDate']));
            $table->addColumn('creator', __('Created By'))
                ->format(function($row) {
                    return Format::name($row['title'], $row['creatorPreferredName'], $row['creatorSurname'], 'Staff', false, true);
                });
    
            $table->addActionColumn()
                ->addParam('gibbonINInterventionStrategyID')
                ->addParam('gibbonINInterventionID')
                ->addParam('gibbonPersonID', $gibbonPersonID)
                ->addParam('gibbonFormGroupID', $gibbonFormGroupID)
                ->addParam('gibbonYearGroupID', $gibbonYearGroupID)
                ->addParam('status', $status)
                ->format(function ($row, $actions) {
                    $actions->addAction('edit', __('Edit'))
                        ->setURL('/modules/Interventions/interventions_manage_strategy_edit.php');
                    $actions->addAction('outcome', __('Add Outcome'))
                        ->setURL('/modules/Interventions/interventions_manage_outcome_add.php')
                        ->setIcon('attendance');
                });
    
            echo $table->render($strategies);
        }
    }
}
