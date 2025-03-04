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

// Create the form
$form = Form::create('interventionPhase3', $_SESSION[$guid]['absoluteURL'].'/modules/Interventions/intervention_process_phase3Process.php');
$form->setFactory(DatabaseFormFactory::create($pdo));
$form->setClass('w-full');

$form->addHiddenValue('address', $_SESSION[$guid]['address']);
$form->addHiddenValue('gibbonINInterventionID', $gibbonINInterventionID);

// PHASE 3: SUPPORT PLAN
$form->addRow()->addHeading(__('Phase 3: Support Plan'))->append('<p class="emphasis small">'.__('Define goals, strategies, and resources for the intervention').'</p>');

// Add a message if the intervention is not yet at this phase
if ($intervention['status'] != 'Intervention Required' && $intervention['status'] != 'Support Plan Active' && !$isAdmin) {
    $row = $form->addRow();
    $row->addContent('<div class="error">'.__('This intervention is not yet ready for a support plan.').'</div>');
    
    // Display the form without submit button
    echo $form->getOutput();
    return;
}

// Add a message if the support plan is already active
if ($intervention['status'] == 'Support Plan Active') {
    $row = $form->addRow();
    $row->addContent('<div class="message">'.__('This support plan is currently active.').'</div>');
}

// Support Plan Details
$row = $form->addRow();
    $row->addLabel('goals', __('Goals'))->description(__('What are the specific goals for this intervention?'));
    $row->addTextArea('goals')->setRows(3)->required()->setValue($intervention['goals'] ?? '');

$row = $form->addRow();
    $row->addLabel('strategies', __('Strategies'))->description(__('What strategies will be used to achieve these goals?'));
    $row->addTextArea('strategies')->setRows(3)->required()->setValue($intervention['strategies'] ?? '');

$row = $form->addRow();
    $row->addLabel('resources', __('Resources'))->description(__('What resources will be needed for this intervention?'));
    $row->addTextArea('resources')->setRows(3)->setValue($intervention['resources'] ?? '');

// Target Date
$row = $form->addRow();
    $row->addLabel('targetDate', __('Target Completion Date'))->description(__('When should this intervention be completed by?'));
    $row->addDate('targetDate')->setValue($intervention['targetDate'] ?? '')->required();

// Staff Responsible
$row = $form->addRow();
    $row->addLabel('gibbonPersonIDStaff', __('Staff Responsible'))->description(__('Who will be implementing this intervention?'));
    $row->addSelectStaff('gibbonPersonIDStaff')->required()->selected($intervention['gibbonPersonIDStaff'] ?? '');

// Add explanatory text about the workflow
$row = $form->addRow();
$row->addContent('<div class="message emphasis">');
$row->addContent('<p><strong>'.__('Workflow Information').':</strong></p>');
$row->addContent('<p>'.__('Completing this form will activate the support plan and move the intervention to Phase 4: Implementation.').'</p>');
$row->addContent('</div>');

// Add the submit button
$row = $form->addRow();
$row->addSubmit(__('Activate Support Plan & Continue'));

// Display the form
echo $form->getOutput();
