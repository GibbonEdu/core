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
use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Module\Interventions\Domain\INInterventionEligibilityAssessmentGateway;
use Gibbon\Module\Interventions\Domain\INInterventionEligibilityContributorGateway;

// Get eligibility assessment
$eligibilityAssessmentGateway = $container->get(INInterventionEligibilityAssessmentGateway::class);
$assessment = $eligibilityAssessmentGateway->selectBy(['gibbonINInterventionID' => $gibbonINInterventionID])->fetch();

// Check if we have an assessment
if (empty($assessment)) {
    echo '<div class="error">';
    echo __('No eligibility assessment has been created for this intervention.');
    echo '</div>';
    
    // Add a button to create an assessment if user is admin
    if ($isAdmin) {
        echo '<div class="mt-4">';
        echo '<a href="' . $_SESSION[$guid]['absoluteURL'] . '/modules/Interventions/intervention_eligibility_add.php?gibbonINInterventionID=' . $gibbonINInterventionID . '" class="button">';
        echo __('Create Eligibility Assessment');
        echo '</a>';
        echo '</div>';
    }
    
    return;
}

// Display assessment information
echo '<div class="message">';
echo '<h4>' . __('Eligibility Assessment Information') . '</h4>';
echo '<strong>' . __('Date Created') . ':</strong> ' . Format::dateTime($assessment['timestampCreated'] ?? date('Y-m-d H:i:s')) . '<br/>';
echo '<strong>' . __('Status') . ':</strong> ' . $assessment['status'] . '<br/>';
echo '<strong>' . __('Decision') . ':</strong> ' . ($assessment['eligibilityDecision'] == 'Pending' ? __('Pending') : $assessment['eligibilityDecision']) . '<br/>';
echo '</div>';

// Get contributors
$contributorGateway = $container->get(INInterventionEligibilityContributorGateway::class);

// Create a QueryCriteria object
$criteria = new QueryCriteria();
$criteria->addFilterRule('gibbonINInterventionEligibilityAssessmentID', function ($query, $gibbonINInterventionEligibilityAssessmentID) use ($assessment) {
    return $query
        ->where('gibbonINInterventionEligibilityContributor.gibbonINInterventionEligibilityAssessmentID = :gibbonINInterventionEligibilityAssessmentID')
        ->bindValue('gibbonINInterventionEligibilityAssessmentID', $assessment['gibbonINInterventionEligibilityAssessmentID']);
});

// Query contributors
$contributors = $contributorGateway->queryContributors($criteria);

// Display contributors
$table = DataTable::createPaginated('contributors', $criteria);
$table->withData($contributors);
$table->setTitle(__('Contributors'));

$table->addHeaderAction('add', __('Add'))
    ->setURL('/modules/Interventions/intervention_eligibility_contributor_add.php')
    ->addParam('gibbonINInterventionEligibilityAssessmentID', $assessment['gibbonINInterventionEligibilityAssessmentID'])
    ->addParam('gibbonINInterventionID', $gibbonINInterventionID)
    ->addParam('returnProcess', 'true')
    ->displayLabel();

$table->addColumn('name', __('Name'))
    ->sortable(['surname', 'preferredName'])
    ->format(Format::using('name', ['title', 'preferredName', 'surname', 'Staff', false, true]));

$table->addColumn('assessmentType', __('Assessment Type'));

$table->addColumn('status', __('Status'))
    ->format(function ($values) {
        global $guid;
        return $values['status'] == 'Complete' 
            ? '<img src="./themes/'.$_SESSION[$guid]['gibbonThemeName'].'/img/iconTick.png" alt="Complete" />' 
            : '<img src="./themes/'.$_SESSION[$guid]['gibbonThemeName'].'/img/iconCross.png" alt="Incomplete" />';
    });

$table->addColumn('recommendation', __('Recommendation'));

// Add actions column
$table->addActionColumn()
    ->addParam('gibbonINInterventionEligibilityAssessmentID', $assessment['gibbonINInterventionEligibilityAssessmentID'])
    ->addParam('gibbonINInterventionEligibilityContributorID')
    ->addParam('gibbonINInterventionID', $gibbonINInterventionID)
    ->addParam('returnProcess', 'true')
    ->format(function ($contributor, $actions) use ($guid, $isAdmin, $assessment, $gibbonPersonID) {
        // View action
        $actions->addAction('view', __('View'))
            ->setURL('/modules/Interventions/intervention_eligibility_contributor_view.php');

        // Edit action - only for the contributor or admin
        if ($isAdmin || $contributor['gibbonPersonID'] == $gibbonPersonID) {
            $actions->addAction('edit', __('Edit'))
                ->setURL('/modules/Interventions/intervention_eligibility_contributor_edit.php');
        }
        
        // Delete action - only for admin
        if ($isAdmin && $assessment['status'] != 'Complete') {
            $actions->addAction('delete', __('Delete'))
                ->setURL('/modules/Interventions/intervention_eligibility_contributor_delete.php')
                ->modalWindow(650, 400);
        }
    });

echo $table->render($contributors);

// If assessment is not complete, show the decision form for admin or assessment creator
if (($isAdmin || $assessment['gibbonPersonIDCreator'] == $gibbonPersonID) && $assessment['status'] != 'Complete') {
    // Create the form
    $form = Form::create('assessmentDecision', $_SESSION[$guid]['absoluteURL'].'/modules/Interventions/intervention_process_phase2Process.php');
    $form->setClass('w-full');

    $form->addHiddenValue('address', $_SESSION[$guid]['address']);
    $form->addHiddenValue('gibbonINInterventionID', $gibbonINInterventionID);
    $form->addHiddenValue('gibbonINInterventionEligibilityAssessmentID', $assessment['gibbonINInterventionEligibilityAssessmentID']);

    $form->addRow()->addHeading(__('Assessment Decision'))->append('<p class="emphasis small">'.__('Review the assessment contributions and make a final decision').'</p>');
    
    $decisions = [
        'Intervention Required' => __('Intervention Required'),
        'No Intervention Required' => __('No Intervention Required'),
        'Refer for IEP' => __('Refer for IEP')
    ];
    
    $row = $form->addRow();
        $row->addLabel('eligibilityDecision', __('Decision'))->description(__('Select the appropriate decision based on the assessment'));
        $row->addSelect('eligibilityDecision')->fromArray($decisions)->required()->placeholder(__('Please select...'));
    
    $row = $form->addRow();
        $row->addLabel('notes', __('Decision Notes'))->description(__('Explanation of decision'));
        $row->addTextArea('notes')->setRows(5);
    
    // Add explanatory text about the workflow
    $row = $form->addRow();
    $row->addContent('<div class="message emphasis">');
    $row->addContent('<p><strong>'.__('Workflow Information').':</strong></p>');
    $row->addContent('<ul>');
    $row->addContent('<li>'.__('Intervention Required: Move to Phase 3 to create a support plan').'</li>');
    $row->addContent('<li>'.__('No Intervention Required: Mark the intervention as resolved').'</li>');
    $row->addContent('<li>'.__('Refer for IEP: Mark for referral to the IEP process').'</li>');
    $row->addContent('</ul>');
    $row->addContent('</div>');
    
    // Add the submit button
    $row = $form->addRow();
    $row->addSubmit(__('Complete Assessment & Continue'));

    // Display the form
    echo $form->getOutput();
} elseif ($assessment['status'] == 'Complete') {
    // Display the decision information
    echo '<div class="message">';
    echo '<h4>' . __('Assessment Decision') . '</h4>';
    echo '<strong>' . __('Decision') . ':</strong> ' . $assessment['eligibilityDecision'] . '<br/>';
    
    if (!empty($assessment['notes'])) {
        echo '<strong>' . __('Decision Notes') . ':</strong> ' . $assessment['notes'] . '<br/>';
    }
    
    echo '<strong>' . __('Date Completed') . ':</strong> ' . Format::dateTime($assessment['timestampCreated']) . '<br/>';
    echo '</div>';
    
    // Add a button to continue to the next phase if decision was Intervention Required
    if ($assessment['eligibilityDecision'] == 'Intervention Required') {
        echo '<div class="mt-4">';
        echo '<a href="' . $_SESSION[$guid]['absoluteURL'] . '/index.php?q=/modules/Interventions/intervention_process.php&gibbonINInterventionID=' . $gibbonINInterventionID . '&step=3" class="button">';
        echo __('Continue to Support Plan');
        echo '</a>';
        echo '</div>';
    }
}
