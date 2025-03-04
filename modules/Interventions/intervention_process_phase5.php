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

// Add a message if the intervention is not yet at this phase
if ($intervention['status'] != 'Ready for Evaluation' && $intervention['status'] != 'Resolved' && !$isAdmin) {
    echo '<div class="error">';
    echo __('This intervention is not yet ready for evaluation.');
    echo '</div>';
    return;
}

// Display support plan details
echo '<div class="message">';
echo '<h4>' . __('Support Plan Details') . '</h4>';
echo '<strong>' . __('Goals') . ':</strong> ' . $intervention['goals'] . '<br/>';
echo '<strong>' . __('Strategies') . ':</strong> ' . $intervention['strategies'] . '<br/>';

if (!empty($intervention['resources'])) {
    echo '<strong>' . __('Resources') . ':</strong> ' . $intervention['resources'] . '<br/>';
}

echo '<strong>' . __('Target Date') . ':</strong> ' . Format::date($intervention['targetDate']) . '<br/>';
echo '<strong>' . __('Date Started') . ':</strong> ' . Format::date($intervention['dateStart']) . '<br/>';

if (!empty($intervention['dateEnd'])) {
    echo '<strong>' . __('Date Ended') . ':</strong> ' . Format::date($intervention['dateEnd']) . '<br/>';
}
echo '</div>';

// If the intervention is already resolved, show the outcome
if ($intervention['status'] == 'Resolved') {
    echo '<div class="message">';
    echo '<h4>' . __('Evaluation Outcome') . '</h4>';
    
    // Display outcome details
    echo '<strong>' . __('Outcome') . ':</strong> ' . $intervention['outcome'] . '<br/>';
    
    if (!empty($intervention['outcomeNotes'])) {
        echo '<strong>' . __('Outcome Notes') . ':</strong> ' . $intervention['outcomeNotes'] . '<br/>';
    }
    
    echo '<strong>' . __('Date Resolved') . ':</strong> ' . Format::date($intervention['dateResolved']) . '<br/>';
    echo '</div>';
    
    // Add a message that the intervention is complete
    echo '<div class="success">';
    echo __('This intervention has been completed and resolved.');
    echo '</div>';
    
    return;
}

// Create the evaluation form
$form = Form::create('interventionPhase5', $_SESSION[$guid]['absoluteURL'].'/modules/Interventions/intervention_process_phase5Process.php');
$form->setClass('w-full');

$form->addHiddenValue('address', $_SESSION[$guid]['address']);
$form->addHiddenValue('gibbonINInterventionID', $gibbonINInterventionID);

$form->addRow()->addHeading(__('Phase 5: Evaluation'))->append('<p class="emphasis small">'.__('Evaluate the effectiveness of the intervention and determine next steps').'</p>');

// Outcome options
$outcomes = [
    'Goals Achieved' => __('Goals Achieved'),
    'Partial Progress' => __('Partial Progress'),
    'No Progress' => __('No Progress'),
    'Refer for IEP' => __('Refer for IEP')
];

$row = $form->addRow();
    $row->addLabel('outcome', __('Outcome'))->description(__('Select the outcome that best describes the results of this intervention'));
    $row->addSelect('outcome')->fromArray($outcomes)->required()->placeholder(__('Please select...'));

$row = $form->addRow();
    $row->addLabel('outcomeNotes', __('Outcome Notes'))->description(__('Provide details about the outcome and any recommendations for future support'));
    $row->addTextArea('outcomeNotes')->setRows(5)->required();

// Add explanatory text about the workflow
$row = $form->addRow();
$row->addContent('<div class="message emphasis">');
$row->addContent('<p><strong>'.__('Workflow Information').':</strong></p>');
$row->addContent('<p>'.__('Completing this form will mark the intervention as resolved and complete the intervention process.').'</p>');
$row->addContent('</div>');

// Add the submit button
$row = $form->addRow();
$row->addSubmit(__('Complete Evaluation & Resolve Intervention'));

// Display the form
echo $form->getOutput();
