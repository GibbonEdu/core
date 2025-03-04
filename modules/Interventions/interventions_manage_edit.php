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
use Gibbon\Module\Interventions\Domain\INInterventionGateway;
use Gibbon\Module\Interventions\Domain\INInterventionContributorGateway;
use Gibbon\Module\Interventions\Domain\INInterventionStrategyGateway;
use Gibbon\Module\Interventions\Domain\INInterventionOutcomeGateway;
use Gibbon\Module\Interventions\Domain\INInterventionEligibilityAssessmentGateway;
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
        $gibbonPersonIDStudent = $_GET['gibbonPersonIDStudent'] ?? '';
        $gibbonFormGroupID = $_GET['gibbonFormGroupID'] ?? '';
        $gibbonYearGroupID = $_GET['gibbonYearGroupID'] ?? '';
        $status = $_GET['status'] ?? '';

        $page->breadcrumbs
            ->add(__('Manage Interventions'), 'interventions_manage.php', [
                'gibbonPersonIDStudent' => $gibbonPersonIDStudent,
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
        
        // PHASE 1: REFERRAL INFORMATION
        $form->addRow()->addHeading(__('Phase 1: Referral Information'))->append('<p class="emphasis small">'.__('Initial information about the student and reason for referral').'</p>');
        
        $row = $form->addRow();
            $row->addLabel('student', __('Student'));
            $row->addTextField('student')->setValue(Format::name('', $intervention['preferredName'], $intervention['surname'], 'Student'))->readonly();
            
        $row = $form->addRow();
            $row->addLabel('name', __('Intervention Name'));
            $row->addTextField('name')->maxLength(100)->required();
            
        $row = $form->addRow();
            $row->addLabel('description', __('Description'))->description(__('Reason for referral, including strategies already tried'));
            $row->addTextArea('description')->setRows(5)->required();
            
        $row = $form->addRow();
            $row->addLabel('parentConsent', __('Parent Consent'));
            $row->addYesNo('parentConsent')->required()->selected($intervention['parentConsent'] ?? 'N');
            
        $row = $form->addRow();
            $row->addLabel('parentConsultNotes', __('Parent Consent Notes'))->description(__('If no parent consent, explain why'));
            $row->addTextArea('parentConsultNotes')->setRows(3);
            
        // Form Tutor Decision Section (Only visible to form tutors or admins)
        if ($isFormTutor || $isAdmin) {
            $form->addRow()->addHeading(__('Form Tutor Decision'))->append('<p class="emphasis small">'.__('As the form tutor, you need to decide how to proceed with this referral').'</p>');
            
            // Add a message if a decision has already been made
            if (!empty($intervention['formTutorDecision']) && $intervention['formTutorDecision'] != 'Pending') {
                $decisionText = '';
                if ($intervention['formTutorDecision'] == 'Resolved') {
                    $decisionText = __('This intervention has been marked as Resolved.');
                } else if ($intervention['formTutorDecision'] == 'Eligibility Assessment') {
                    $decisionText = __('This intervention has been referred for Eligibility Assessment.');
                }
                
                if (!empty($decisionText)) {
                    $row = $form->addRow();
                    $row->addContent('<div class="message emphasis">');
                    $row->addContent('<p><strong>'.__('Decision Status').':</strong> ' . $decisionText . '</p>');
                    $row->addContent('</div>');
                }
            }
            
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
            
            // Add a phase-specific submit button for Phase 1
            $row = $form->addRow();
            $row->addContent('<input type="hidden" name="phase" value="phase1">');
            $row->addSubmit(__('Submit Phase 1: Referral Decision'));
            
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
                        var currentStatus = '" . $intervention['status'] . "';
                        var hasExistingAssessment = " . (!empty($existingAssessment) ? 'true' : 'false') . ";
                        
                        // Hide all conditional sections first
                        $('.eligibilitySection').hide();
                        $('.outcomeSection').hide();
                        
                        // Show sections based on decision
                        if (decision == 'Eligibility Assessment') {
                            $('.eligibilitySection').show();
                        } else if (decision == 'Resolved') {
                            $('.outcomeSection').show();
                        }
                        
                        // Also show eligibility section if status is already Eligibility Assessment
                        // or if an assessment already exists
                        if (currentStatus == 'Eligibility Assessment' || hasExistingAssessment) {
                            $('.eligibilitySection').show();
                        }
                        
                        // Also show outcome section if status is already Resolved
                        if (currentStatus == 'Resolved') {
                            $('.outcomeSection').show();
                        }
                    }
                });
            </script>";
            
            // End the form here for Phase 1 if the user is only a form tutor (not an admin)
            if ($isFormTutor && !$isAdmin) {
                $form->addRow()->addContent('<hr>');
                return;
            }
        }
            
        // PHASE 2: ASSESSMENT INFORMATION
        $form->addRow()->addHeading(__('Phase 2: Assessment Information'))->append('<p class="emphasis small">'.__('Information about the eligibility assessment process').'</p>');
        
        // Only show assessment information if status is Eligibility Assessment or later
        $assessmentClass = (in_array($intervention['status'], ['Eligibility Assessment', 'Intervention Required', 'Support Plan Active', 'Resolved']) || $isAdmin) ? '' : 'hidden';
        
        $row = $form->addRow()->addClass($assessmentClass)->addClass('eligibilitySection');
        $row->addContent('<div class="message emphasis">');
        $row->addContent('<p>'.__('Eligibility assessments are managed on a separate page.').'</p>');
        if (!empty($existingAssessment)) {
            $row->addContent('<a href="'.$session->get('absoluteURL').'/index.php?q=/modules/Interventions/intervention_eligibility_edit.php&gibbonINInterventionEligibilityAssessmentID='.$existingAssessment['gibbonINInterventionEligibilityAssessmentID'].'" class="button">'.__('Go to Eligibility Assessment').'</a>');
        }
        $row->addContent('</div>');
        
        // PHASE 3: SUPPORT PLAN
        $form->addRow()->addHeading(__('Phase 3: Support Plan'))->append('<p class="emphasis small">'.__('Information about the support plan and goals').'</p>');
        
        $row = $form->addRow();
            $row->addLabel('targetDate', __('Target Date'))->description(__('When the intervention should be completed by'));
            $row->addDate('targetDate');
            
        $row = $form->addRow();
            $row->addLabel('strategies', __('Support Strategies'))->description(__('Specific strategies to be implemented'));
            $row->addTextArea('strategies')->setRows(5);
            
        $row = $form->addRow();
            $row->addLabel('goals', __('Goals'))->description(__('Specific, measurable goals for this intervention'));
            $row->addTextArea('goals')->setRows(5);
            
        // Only show activate support plan option if status is Intervention Required
        $activateSupportPlanClass = ($intervention['status'] == 'Intervention Required' || $isAdmin) ? '' : 'hidden';
        $row = $form->addRow()->addClass($activateSupportPlanClass);
            $row->addLabel('activateSupportPlan', __('Activate Support Plan'))->description(__('Change status to Support Plan Active'));
            $row->addCheckbox('activateSupportPlan')->setValue('1')->description(__('Yes'));
            
        // Add a phase-specific submit button for Phase 3
        $row = $form->addRow();
        $row->addContent('<input type="hidden" name="phase" value="phase3">');
        $row->addSubmit(__('Submit Phase 3: Support Plan'));
        
        // PHASE 4 & 5: IMPLEMENTATION & EVALUATION
        $form->addRow()->addHeading(__('Phase 4 & 5: Implementation & Evaluation'))->append('<p class="emphasis small">'.__('Track progress and evaluate outcomes').'</p>');
        
        // Only show outcome section if status is Support Plan Active or Resolved
        $outcomeClass = (in_array($intervention['status'], ['Support Plan Active', 'Resolved']) || $isAdmin) ? '' : 'hidden';
        
        $outcomeOptions = [
            'Pending' => __('Pending'),
            'Partially Achieved' => __('Partially Achieved'),
            'Fully Achieved' => __('Fully Achieved'),
            'Not Achieved' => __('Not Achieved'),
            'Resolved' => __('Resolved')
        ];
        
        $row = $form->addRow()->addClass($outcomeClass)->addClass('outcomeSection');
            $row->addLabel('outcomeDecision', __('Outcome'));
            $row->addSelect('outcomeDecision')->fromArray($outcomeOptions)->placeholder(__('Please select...'));
            
        $row = $form->addRow()->addClass($outcomeClass)->addClass('outcomeSection');
            $row->addLabel('outcomeNotes', __('Outcome Notes'))->description(__('Details about the outcome and next steps'));
            $row->addTextArea('outcomeNotes')->setRows(5);
            
        // Add a phase-specific submit button for Phase 5
        $row = $form->addRow()->addClass($outcomeClass);
        $row->addContent('<input type="hidden" name="phase" value="phase5">');
        $row->addSubmit(__('Submit Phase 5: Outcome Evaluation'));
        
        // Add a general submit button for all phases (only for admins)
        if ($isAdmin) {
            $row = $form->addRow();
            $row->addContent('<hr>');
            $row->addSubmit(__('Save All Changes'));
        }
        
        // Only show status field to admins, hide it for form tutors
        if ($isAdmin) {
            $row = $form->addRow();
                $row->addLabel('status', __('Status'));
                $row->addSelect('status')->fromArray($statusOptions)->required();
        } else {
            // For non-admins, status is hidden and set based on form tutor decision
            // Set a default status if it's empty
            $currentStatus = !empty($intervention['status']) ? $intervention['status'] : 'Referral';
            $form->addHiddenValue('status', $currentStatus);
        }
        
        // Add a hidden field to store the form tutor decision submission flag
        // Always set to Y when the form is submitted to ensure the decision is processed
        $form->addHiddenValue('formTutorDecisionSubmitted', 'Y');
        
        $row = $form->addRow();
            $row->addFooter();
            $row->addSubmit();

        $form->loadAllValuesFrom($intervention);

        echo $form->getOutput();

        
      
    }
}
