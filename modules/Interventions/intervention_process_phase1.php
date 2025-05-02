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

// Create the form
$form = Form::create('interventionPhase1', $_SESSION[$guid]['absoluteURL'].'/modules/Interventions/intervention_process_phase1Process.php');
$form->setClass('w-full');

$form->addHiddenValue('address', $_SESSION[$guid]['address']);
$form->addHiddenValue('gibbonINInterventionID', $gibbonINInterventionID);

// Add CSS for better formatting of read-only fields
echo '<style>
.readonly-field {
    background-color: #f8f9fa;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 8px 12px;
    min-height: 40px;
    width: 100%;
    display: block;
    white-space: pre-wrap;
    font-family: inherit;
    font-size: inherit;
    line-height: 1.5;
    color: #495057;
}
</style>';

// PHASE 1: REFERRAL INFORMATION
$form->addRow()->addHeading(__('Phase 1: Referral Information'))->append('<p class="emphasis small">'.__('Initial information about the student and reason for referral').'</p>');

// Display referral information as read-only
$row = $form->addRow();
    $row->addLabel('descriptionLabel', __('Description'))->description(__('Reason for referral, including strategies already tried'));
    $column = $row->addColumn();
    $column->addContent('<div class="readonly-field">' . nl2br(htmlspecialchars($intervention['description'])) . '</div>');
    $form->addHiddenValue('description', $intervention['description']);
    
$row = $form->addRow();
    $row->addLabel('parentConsentLabel', __('Parent Consent'));
    $column = $row->addColumn();
    $column->addContent('<div class="readonly-field">' . ($intervention['parentConsent'] == 'Y' ? __('Yes') : __('No')) . '</div>');
    $form->addHiddenValue('parentConsent', $intervention['parentConsent'] ?? 'N');
    
$row = $form->addRow();
    $row->addLabel('parentConsultNotesLabel', __('Parent Consent Notes'))->description(__('If no parent consent, explain why'));
    $column = $row->addColumn();
    $column->addContent('<div class="readonly-field">' . nl2br(htmlspecialchars($intervention['parentConsultNotes'] ?? '')) . '</div>');
    $form->addHiddenValue('parentConsultNotes', $intervention['parentConsultNotes'] ?? '');
    
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
        $row->addSelect('formTutorDecision')->fromArray($formTutorDecisions)->required()->placeholder(__('Please select...'))->selected($intervention['formTutorDecision'] ?? 'Pending');
    
    $row = $form->addRow();
        $row->addLabel('formTutorNotes', __('Notes'))->description(__('Explanation of decision'));
        $row->addTextArea('formTutorNotes')->setRows(5)->setValue($intervention['formTutorNotes'] ?? '');
        
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
}

// Add the submit button
$row = $form->addRow();
$row->addSubmit(__('Save & Continue'));

// Display the form
echo $form->getOutput();
