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
use Gibbon\Domain\Interventions\INInterventionGateway;
use Gibbon\Domain\Interventions\INInterventionContributorGateway;
use Gibbon\Domain\Interventions\INInterventionStrategyGateway;
use Gibbon\Domain\Interventions\INInterventionOutcomeGateway;
use Gibbon\Domain\Interventions\INInterventionEligibilityAssessmentGateway;
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
        if ($highestAction == 'Manage Interventions_my' && $intervention['gibbonPersonIDCreator'] != $session->get('gibbonPersonID') && $intervention['gibbonPersonIDFormTutor'] != $session->get('gibbonPersonID')) {
            $page->addError(__('You do not have access to this action.'));
            return;
        }

        // Determine user roles
        $isCreator = ($intervention['gibbonPersonIDCreator'] == $session->get('gibbonPersonID'));
        $isFormTutor = ($intervention['gibbonPersonIDFormTutor'] == $session->get('gibbonPersonID'));
        $isAdmin = ($highestAction == 'Manage Interventions');

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
            $row->addTextField('formGroup')->setValue($intervention['formGroup'] ?? '')->readonly();

        // Intervention Details
        $form->addRow()->addHeading(__('Intervention Details'));
        $row = $form->addRow();
            $row->addLabel('name', __('Name'))->description(__('Brief name for this intervention'));
            if ($isCreator || $isAdmin) {
                $row->addTextField('name')->maxLength(100)->required();
            } else {
                $row->addTextField('name')->maxLength(100)->required()->readonly();
            }

        $row = $form->addRow();
            $row->addLabel('description', __('Description'))->description(__('Details about the intervention'));
            if ($isCreator || $isAdmin) {
                $row->addTextArea('description')->setRows(5)->required();
            } else {
                $row->addTextArea('description')->setRows(5)->required()->readonly();
            }

        // Form Tutor Review Section (Only visible to form tutors or admins)
        if ($isFormTutor || $isAdmin) {
            $form->addRow()->addHeading(__('Form Tutor Review'));
            
            $formTutorDecisions = [
                'Pending' => __('Pending'),
                'Resolved' => __('Resolve'),
                'Eligibility Assessment' => __('Conduct Eligibility Assessment')
            ];
            
            $row = $form->addRow();
                $row->addLabel('formTutorDecision', __('Decision'))->description(__('Select an action to take on this referral'));
                $row->addSelect('formTutorDecision')->fromArray($formTutorDecisions)->required()->placeholder(__('Please select...'));
            
            $row = $form->addRow();
                $row->addLabel('formTutorNotes', __('Notes'))->description(__('Explanation of decision'));
                $row->addTextArea('formTutorNotes')->setRows(5);
                
            // Add explanatory text about the workflow
            $row = $form->addRow();
            $row->addContent('<div class="message emphasis">');
            $row->addContent('<p><strong>'.__('Workflow Information').':</strong></p>');
            $row->addContent('<ul>');
            $row->addContent('<li>'.__('Pending: Keep the referral under review').'</li>');
            $row->addContent('<li>'.__('Resolve: Mark the referral as resolved/dismissed').'</li>');
            $row->addContent('<li>'.__('Conduct Eligibility Assessment: Refer for further assessment').'</li>');
            $row->addContent('</ul>');
            $row->addContent('</div>');
            
            // Add JavaScript to show/hide sections based on form tutor decision
            echo "<script type='text/javascript'>
                $(document).ready(function(){
                    // Initial state
                    updateVisibility();
                    
                    // On change
                    $('#formTutorDecision').change(function(){
                        updateVisibility();
                    });
                    
                    function updateVisibility() {
                        var decision = $('#formTutorDecision').val();
                        
                        // Hide all conditional sections first
                        $('.eligibilitySection').hide();
                        $('.contributorsSection').hide();
                        $('.outcomeSection').hide();
                        $('.strategiesSection').hide();
                        
                        // Show sections based on decision
                        if (decision == 'Eligibility Assessment') {
                            $('.eligibilitySection').show();
                            $('.contributorsSection').show();
                        } else if (decision == 'Resolved') {
                            $('.outcomeSection').show();
                        }
                    }
                });
            </script>";
        }
        
        // Eligibility Assessment Section (Always present but controlled by JavaScript)
        $eligibilitySection = $form->addRow()->addHeading(__('Eligibility Assessment'))->addClass('eligibilitySection');
        $eligibilitySection->append('<div class="message emphasis eligibilitySection">');
        $eligibilitySection->append('<p>'.__('The student has been referred for an eligibility assessment. After completing the assessment, you will need to make a decision about whether the student is eligible for an IEP or should receive interventions.').'</p>');
        
        // Check if an eligibility assessment already exists for this intervention
        $eligibilityAssessmentGateway = $container->get(INInterventionEligibilityAssessmentGateway::class);
        $existingAssessment = $eligibilityAssessmentGateway->getByInterventionID($gibbonINInterventionID);
        
        if (empty($existingAssessment)) {
            // No assessment exists yet, show create button
            $params = [
                'gibbonINInterventionID' => $gibbonINInterventionID,
                'gibbonPersonIDStudent' => $intervention['gibbonPersonID'] ?? '',
                'action' => 'create'
            ];
            
            $url = './index.php?q=/modules/Interventions/intervention_eligibility_edit.php&'.http_build_query($params);
            $eligibilitySection->append('<a href="'.$url.'" class="button">'.__('Create Eligibility Assessment').'</a>');
        } else {
            // Assessment exists, show manage button
            $params = [
                'gibbonINInterventionID' => $gibbonINInterventionID,
                'gibbonINInterventionEligibilityAssessmentID' => $existingAssessment['gibbonINInterventionEligibilityAssessmentID'] ?? '',
                'gibbonPersonIDStudent' => $intervention['gibbonPersonID'] ?? ''
            ];
            
            $url = './index.php?q=/modules/Interventions/intervention_eligibility_edit.php&'.http_build_query($params);
            $eligibilitySection->append('<a href="'.$url.'" class="button">'.__('Manage Eligibility Assessment').'</a>');
        }
        
        $eligibilitySection->append('</div>');
        
        // Status Section - Only editable by admin or based on form tutor decision
        $statusOptions = [
            'Referral' => __('Referral'),
            'Form Tutor Review' => __('Form Tutor Review'),
            'Resolved' => __('Resolved'),
            'Eligibility Assessment' => __('Eligibility Assessment')
        ];
        
        // Only show status field to admins, hide it for form tutors
        if ($isAdmin) {
            $row = $form->addRow();
                $row->addLabel('status', __('Status'));
                $row->addSelect('status')->fromArray($statusOptions)->required();
        } else {
            // For non-admins, status is hidden and set based on form tutor decision
            $form->addHiddenValue('status', $intervention['status']);
        }
        
        // Outcome Section (Only visible if status is Intervention or later)
        if (in_array($intervention['status'], ['Intervention', 'Resolved']) || $isAdmin) {
            $form->addRow()->addHeading(__('Outcome'))->addClass('outcomeSection');
            
            $outcomeDecisions = [
                'Pending' => __('Pending'),
                'Success' => __('Success'),
                'Needs IEP' => __('Needs IEP')
            ];
            
            // For resolved status, only show if admin or if form tutor decision was Resolved
            if ($intervention['status'] != 'Resolved' || $isAdmin || $intervention['formTutorDecision'] == 'Resolved') {
                $row = $form->addRow()->addClass('outcomeSection');
                    $row->addLabel('outcomeDecision', __('Decision'));
                    $row->addSelect('outcomeDecision')->fromArray($outcomeDecisions)->required();
                
                $row = $form->addRow()->addClass('outcomeSection');
                    $row->addLabel('outcomeNotes', __('Notes'))->description(__('Summary of intervention outcome'));
                    $row->addTextArea('outcomeNotes')->setRows(5);
            } else {
                // If status is Resolved but form tutor decision doesn't match, show warning
                $row = $form->addRow()->addClass('outcomeSection');
                $row->addContent('<div class="error">');
                $row->addContent('<p>'.__('The status and form tutor decision are not synchronized. Please contact an administrator.').'</p>');
                $row->addContent('</div>');
            }
        }

        // Add a hidden field to store the form tutor decision
        $form->addHiddenValue('formTutorDecisionSubmitted', 'Y');
        
        $row = $form->addRow();
            $row->addFooter();
            $row->addSubmit();

        $form->loadAllValuesFrom($intervention);

        echo $form->getOutput();

        // CONTRIBUTORS
        echo '<div class="contributorsSection" style="display: none;">';
        
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
        echo '</div>';
        
        // STRATEGIES
        if ($intervention['formTutorDecision'] == 'Try Interventions' || $intervention['status'] == 'Intervention') {
            echo '<div class="strategiesSection" style="display: none;">';
            
            $strategyGateway = $container->get(INInterventionStrategyGateway::class);
            
            $criteria = $strategyGateway->newQueryCriteria()
                ->sortBy(['timestampCreated'])
                ->fromPOST();

            $strategies = $strategyGateway->queryStrategiesByIntervention($criteria, $gibbonINInterventionID);

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
            echo '</div>';
        }
    }
}
