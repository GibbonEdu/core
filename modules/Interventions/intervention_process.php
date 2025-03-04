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
use Gibbon\Tables\DataTable;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Module\Interventions\Domain\INInterventionGateway;
use Gibbon\Module\Interventions\Domain\INInterventionEligibilityAssessmentGateway;

// Module includes
require_once __DIR__ . '/moduleFunctions.php';

// Common variables
$gibbonPersonID = $_SESSION[$guid]['gibbonPersonID'];
$highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
$gibbonINInterventionID = $_GET['gibbonINInterventionID'] ?? '';
$step = $_GET['step'] ?? 1;

// Check access
if (empty($highestAction)) {
    // User does not have access to this page
    $page->addError(__('You do not have access to this action.'));
    return;
}

// Check if intervention ID is specified
if (empty($gibbonINInterventionID)) {
    $page->addError(__('You have not specified one or more required parameters.'));
    return;
}

// Get intervention data
$interventionGateway = $container->get(INInterventionGateway::class);
$intervention = $interventionGateway->getByID($gibbonINInterventionID);

if (empty($intervention)) {
    $page->addError(__('The specified record cannot be found.'));
    return;
}

// Determine user roles
$isFormTutor = ($intervention['gibbonPersonIDFormTutor'] == $gibbonPersonID);
$isCreator = ($intervention['gibbonPersonIDCreator'] == $gibbonPersonID);
$isAdmin = ($highestAction == 'Manage Interventions');

// Check permissions for this intervention
if (!$isFormTutor && !$isCreator && !$isAdmin) {
    $page->addError(__('You do not have access to this action.'));
    return;
}

// Get eligibility assessment if it exists
$eligibilityAssessmentGateway = $container->get(INInterventionEligibilityAssessmentGateway::class);
$existingAssessment = $eligibilityAssessmentGateway->selectBy(['gibbonINInterventionID' => $gibbonINInterventionID])->fetch();

// Determine the maximum step the user can access based on intervention status
$maxStep = 1; // Default to step 1

switch($intervention['status']) {
    case 'Referral':
    case 'Form Tutor Review':
        $maxStep = 1;
        break;
    case 'Eligibility Assessment':
        $maxStep = 2;
        break;
    case 'Intervention Required':
        $maxStep = 3;
        break;
    case 'Support Plan Active':
        $maxStep = 4;
        break;
    case 'Resolved':
        $maxStep = 5;
        break;
    default:
        $maxStep = 1;
}

// Admin can access any step
if ($isAdmin) {
    $maxStep = 5;
}

// Enforce step restrictions
if ($step > $maxStep) {
    $page->addError(__('You cannot access this step until previous steps are completed.'));
    $step = $maxStep;
}

// Set page title based on step
$stepTitles = [
    1 => __('Phase 1: Initial Referral'),
    2 => __('Phase 2: Assessment'),
    3 => __('Phase 3: Support Plan'),
    4 => __('Phase 4: Implementation'),
    5 => __('Phase 5: Evaluation')
];

$page->breadcrumbs
    ->add(__('Manage Interventions'), 'interventions_manage.php')
    ->add(__('Intervention Process'), 'intervention_process.php', ['gibbonINInterventionID' => $gibbonINInterventionID])
    ->add($stepTitles[$step] ?? '');

// Add visual progress indicator
echo '<div class="message">';
echo '<div class="progress-indicator">';

$phases = [
    1 => __('Referral'),
    2 => __('Assessment'),
    3 => __('Planning'),
    4 => __('Implementation'),
    5 => __('Evaluation')
];

foreach ($phases as $phaseNum => $phaseLabel) {
    $isActive = $step == $phaseNum;
    $isPast = $phaseNum < $step;
    $isFuture = $phaseNum > $step && $phaseNum <= $maxStep;
    $isDisabled = $phaseNum > $maxStep;
    
    $class = $isActive ? 'active' : ($isPast ? 'past' : ($isFuture ? 'future' : 'disabled'));
    
    echo '<div class="progress-phase ' . $class . '">';
    
    if ($isDisabled) {
        echo '<div class="progress-circle"></div>';
    } else {
        echo '<a href="index.php?q=/modules/Interventions/intervention_process.php&gibbonINInterventionID=' . $gibbonINInterventionID . '&step=' . $phaseNum . '" class="progress-circle-link">';
        echo '<div class="progress-circle"></div>';
        echo '</a>';
    }
    
    echo '<div class="progress-label">' . $phaseLabel . '</div>';
    echo '</div>';
    
    // Add connector line between phases (except for the last one)
    if ($phaseNum < 5) {
        $lineClass = $isPast ? 'past' : ($isActive ? 'active' : 'future');
        echo '<div class="progress-line ' . $lineClass . '"></div>';
    }
}

echo '</div>';
echo '</div>';

// Add CSS for progress indicator
echo '<style>
    .progress-indicator {
        display: flex;
        align-items: center;
        margin: 20px 0;
    }
    .progress-phase {
        display: flex;
        flex-direction: column;
        align-items: center;
        position: relative;
    }
    .progress-circle {
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background-color: #ddd;
        margin-bottom: 5px;
    }
    .progress-label {
        font-size: 0.8em;
        text-align: center;
        max-width: 80px;
    }
    .progress-line {
        flex-grow: 1;
        height: 2px;
        background-color: #ddd;
        margin: 0 5px;
        position: relative;
        top: -12px;
    }
    .progress-phase.past .progress-circle {
        background-color: #A0CFEC;
    }
    .progress-phase.active .progress-circle {
        background-color: #3B9AD9;
        border: 2px solid #1177AA;
    }
    .progress-phase.future .progress-circle {
        background-color: #ddd;
        cursor: pointer;
    }
    .progress-phase.disabled .progress-circle {
        background-color: #eee;
        cursor: not-allowed;
    }
    .progress-line.past, .progress-line.active {
        background-color: #A0CFEC;
    }
    .progress-circle-link {
        text-decoration: none;
    }
</style>';

// Display student information
echo '<div class="message">';
echo '<h4>' . __('Student Information') . '</h4>';

// Get student details
$studentName = '';
$formGroup = '';
$yearGroup = '';

try {
    $dataStudent = array('gibbonPersonID' => $intervention['gibbonPersonIDStudent']);
    $sqlStudent = "SELECT gibbonPerson.surname, gibbonPerson.preferredName, 
                    gibbonFormGroup.name as formGroup, 
                    gibbonYearGroup.name as yearGroup 
                    FROM gibbonPerson 
                    LEFT JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID)
                    LEFT JOIN gibbonFormGroup ON (gibbonFormGroup.gibbonFormGroupID=gibbonStudentEnrolment.gibbonFormGroupID)
                    LEFT JOIN gibbonYearGroup ON (gibbonYearGroup.gibbonYearGroupID=gibbonStudentEnrolment.gibbonYearGroupID)
                    WHERE gibbonPerson.gibbonPersonID=:gibbonPersonID
                    AND gibbonStudentEnrolment.gibbonSchoolYearID=(SELECT gibbonSchoolYearID FROM gibbonSchoolYear WHERE status='Current' LIMIT 1)";
    $resultStudent = $connection2->prepare($sqlStudent);
    $resultStudent->execute($dataStudent);
    
    if ($resultStudent->rowCount() == 1) {
        $rowStudent = $resultStudent->fetch();
        $studentName = Format::name('', $rowStudent['preferredName'], $rowStudent['surname'], 'Student');
        $formGroup = $rowStudent['formGroup'] ?? __('Unknown');
        $yearGroup = $rowStudent['yearGroup'] ?? __('Unknown');
    } else {
        $studentName = __('Unknown');
        $formGroup = __('Unknown');
        $yearGroup = __('Unknown');
    }
} catch (PDOException $e) {
    $studentName = __('Unknown');
    $formGroup = __('Unknown');
    $yearGroup = __('Unknown');
}

echo '<strong>' . __('Student') . ':</strong> ' . $studentName . '<br/>';
echo '<strong>' . __('Form Group') . ':</strong> ' . $formGroup . '<br/>';
echo '<strong>' . __('Year Group') . ':</strong> ' . $yearGroup . '<br/>';
echo '<strong>' . __('Intervention Name') . ':</strong> ' . $intervention['name'] . '<br/>';
echo '<strong>' . __('Current Status') . ':</strong> ' . $intervention['status'] . '<br/>';
echo '</div>';

// Process based on current step
switch($step) {
    case 1:
        // PHASE 1: REFERRAL INFORMATION
        include __DIR__ . '/intervention_process_phase1.php';
        break;
        
    case 2:
        // PHASE 2: ASSESSMENT
        include __DIR__ . '/intervention_process_phase2.php';
        break;
        
    case 3:
        // PHASE 3: SUPPORT PLAN
        include __DIR__ . '/intervention_process_phase3.php';
        break;
        
    case 4:
        // PHASE 4: IMPLEMENTATION
        include __DIR__ . '/intervention_process_phase4.php';
        break;
        
    case 5:
        // PHASE 5: EVALUATION
        include __DIR__ . '/intervention_process_phase5.php';
        break;
        
    default:
        $page->addError(__('Invalid step.'));
}
