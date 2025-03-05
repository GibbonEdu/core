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

// Get the database connection
$pdo = $container->get('db')->getConnection();

// Get eligibility assessment
$eligibilityAssessmentGateway = $container->get(INInterventionEligibilityAssessmentGateway::class);
$stmt = $pdo->prepare("SELECT * FROM gibbonINInterventionEligibilityAssessment WHERE gibbonINInterventionID=:gibbonINInterventionID");
$stmt->execute(['gibbonINInterventionID' => $gibbonINInterventionID]);
$assessment = $stmt->fetch();

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



// Display Assessment Summary
echo '<h3>'.__('Assessment Summary').'</h3>';

// Get all contributors with complete status
$sql = "SELECT c.*, t.name as assessmentTypeName
        FROM gibbonINInterventionEligibilityContributor AS c 
        LEFT JOIN gibbonINEligibilityAssessmentType AS t ON (c.gibbonINEligibilityAssessmentTypeID=t.gibbonINEligibilityAssessmentTypeID)
        WHERE c.gibbonINInterventionEligibilityAssessmentID=:gibbonINInterventionEligibilityAssessmentID 
        AND c.status='Complete'";
$stmt = $pdo->prepare($sql);
$stmt->execute(['gibbonINInterventionEligibilityAssessmentID' => $assessment['gibbonINInterventionEligibilityAssessmentID']]);
$contributors = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($contributors) == 0) {
    echo "<div class='message warning'>".__('There are no completed assessments to display.')."</div>";
} else {
    // Get all assessment types used by contributors
    $assessmentTypes = [];
    foreach ($contributors as $contributor) {
        if (!empty($contributor['gibbonINEligibilityAssessmentTypeID']) && !isset($assessmentTypes[$contributor['gibbonINEligibilityAssessmentTypeID']])) {
            $assessmentTypes[$contributor['gibbonINEligibilityAssessmentTypeID']] = [
                'id' => $contributor['gibbonINEligibilityAssessmentTypeID'],
                'name' => $contributor['assessmentTypeName'],
                'subfields' => []
            ];
        }
    }
    
    // Get all subfields for these assessment types
    foreach ($assessmentTypes as $typeID => $type) {
        $sql = "SELECT * FROM gibbonINEligibilityAssessmentSubfield 
                WHERE gibbonINEligibilityAssessmentTypeID=:gibbonINEligibilityAssessmentTypeID 
                AND active='Y' 
                ORDER BY sequenceNumber";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['gibbonINEligibilityAssessmentTypeID' => $typeID]);
        $subfields = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($subfields as $subfield) {
            $assessmentTypes[$typeID]['subfields'][$subfield['gibbonINEligibilityAssessmentSubfieldID']] = [
                'id' => $subfield['gibbonINEligibilityAssessmentSubfieldID'],
                'name' => $subfield['name'],
                'description' => $subfield['description'],
                'ratings' => [],
                'averageRating' => 0
            ];
        }
    }
    
    // Get all ratings for all contributors
    foreach ($contributors as $contributor) {
        if (empty($contributor['gibbonINEligibilityAssessmentTypeID'])) {
            continue;
        }
        
        $sql = "SELECT r.*, s.name as subfieldName 
                FROM gibbonINInterventionEligibilityContributorRating AS r 
                JOIN gibbonINEligibilityAssessmentSubfield AS s ON (r.gibbonINEligibilityAssessmentSubfieldID=s.gibbonINEligibilityAssessmentSubfieldID) 
                WHERE r.gibbonINInterventionEligibilityContributorID=:gibbonINInterventionEligibilityContributorID";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['gibbonINInterventionEligibilityContributorID' => $contributor['gibbonINInterventionEligibilityContributorID']]);
        $ratings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($ratings as $rating) {
            if (isset($assessmentTypes[$contributor['gibbonINEligibilityAssessmentTypeID']]['subfields'][$rating['gibbonINEligibilityAssessmentSubfieldID']])) {
                $assessmentTypes[$contributor['gibbonINEligibilityAssessmentTypeID']]['subfields'][$rating['gibbonINEligibilityAssessmentSubfieldID']]['ratings'][] = $rating['rating'];
            }
        }
    }
    
    // Calculate average ratings
    foreach ($assessmentTypes as $typeID => $type) {
        foreach ($type['subfields'] as $subfieldID => $subfield) {
            $totalRating = 0;
            $ratingCount = 0;
            
            foreach ($subfield['ratings'] as $rating) {
                if ($rating > 0) {
                    $totalRating += $rating;
                    $ratingCount++;
                }
            }
            
            $assessmentTypes[$typeID]['subfields'][$subfieldID]['averageRating'] = $ratingCount > 0 ? round($totalRating / $ratingCount, 1) : 0;
        }
    }
    
    // Display summary tables for each assessment type
    foreach ($assessmentTypes as $type) {
        echo '<h4>'.$type['name'].'</h4>';
        
        echo '<table class="smallIntBorder" cellspacing="0" style="width:100%">';
        echo '<tr>';
        echo '<th style="width:40%">'.__('Subfield').'</th>';
        echo '<th style="width:20%">'.__('Average Rating').'</th>';
        echo '<th>'.__('Interpretation').'</th>';
        echo '</tr>';
        
        foreach ($type['subfields'] as $subfield) {
            echo '<tr>';
            echo '<td>'.$subfield['name'].'<br/><span class="small emphasis">'.$subfield['description'].'</span></td>';
            
            // Display average rating with color coding
            $ratingClass = '';
            if ($subfield['averageRating'] >= 4) {
                $ratingClass = 'error';
            } elseif ($subfield['averageRating'] >= 2.5) {
                $ratingClass = 'warning';
            } elseif ($subfield['averageRating'] > 0) {
                $ratingClass = 'success';
            }
            
            echo '<td class="'.$ratingClass.'">'.$subfield['averageRating'].'</td>';
            
            // Display interpretation
            $interpretation = '';
            if ($subfield['averageRating'] == 0) {
                $interpretation = __('Not Evaluated');
            } elseif ($subfield['averageRating'] < 2) {
                $interpretation = __('No/Minimal Concern');
            } elseif ($subfield['averageRating'] < 3.5) {
                $interpretation = __('Moderate Concern');
            } else {
                $interpretation = __('Significant/High Concern');
            }
            
            echo '<td>'.$interpretation.'</td>';
            echo '</tr>';
        }
        
        echo '</table>';
    }

    // Display Rating Scale Legend
echo '<div class="message">';
echo '<h4>'.__('Rating Scale Legend').'</h4>';
echo '<table class="smallIntBorder" cellspacing="0" style="width: 300px">';
echo '<tr><td>0</td><td>'.__('Not Evaluated').'</td></tr>';
echo '<tr><td>1</td><td>'.__('No Concern').'</td></tr>';
echo '<tr><td>2</td><td>'.__('Mild Concern').'</td></tr>';
echo '<tr><td>3</td><td>'.__('Moderate Concern').'</td></tr>';
echo '<tr><td>4</td><td>'.__('Significant Concern').'</td></tr>';
echo '<tr><td>5</td><td>'.__('High Concern').'</td></tr>';
echo '</table>';
echo '</div>';
}

// Get contributors
$assessmentID = $assessment['gibbonINInterventionEligibilityAssessmentID'];

 

// Direct SQL query to get contributors for this specific assessment
$sql = "SELECT 
            gibbonINInterventionEligibilityContributor.gibbonINInterventionEligibilityContributorID,
            gibbonINInterventionEligibilityContributor.gibbonINInterventionEligibilityAssessmentID,
            gibbonINInterventionEligibilityContributor.gibbonPersonIDContributor,
            gibbonINInterventionEligibilityContributor.gibbonINEligibilityAssessmentTypeID,
            gibbonINInterventionEligibilityContributor.notes,
            gibbonINInterventionEligibilityContributor.status,
            gibbonINInterventionEligibilityContributor.contribution,
            gibbonINInterventionEligibilityContributor.recommendation,
            gibbonINInterventionEligibilityContributor.timestampCreated,
            gibbonINInterventionEligibilityContributor.timestampModified,
            gibbonPerson.title,
            gibbonPerson.preferredName,
            gibbonPerson.surname,
            gibbonINEligibilityAssessmentType.name as assessmentType
        FROM gibbonINInterventionEligibilityContributor
        LEFT JOIN gibbonPerson ON gibbonPerson.gibbonPersonID=gibbonINInterventionEligibilityContributor.gibbonPersonIDContributor
        LEFT JOIN gibbonINEligibilityAssessmentType ON gibbonINEligibilityAssessmentType.gibbonINEligibilityAssessmentTypeID=gibbonINInterventionEligibilityContributor.gibbonINEligibilityAssessmentTypeID
        WHERE gibbonINInterventionEligibilityContributor.gibbonINInterventionEligibilityAssessmentID = :assessmentID
        ORDER BY gibbonPerson.surname, gibbonPerson.preferredName";

$stmt = $pdo->prepare($sql);
$stmt->execute(['assessmentID' => $assessmentID]);
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
$contributors = ($result) ? $result : [];

 

// Display contributors
$table = DataTable::create('contributors');
$table->withData($contributors);
$table->setTitle(__('Contributors'));

// Add header action
$table->addHeaderAction('add', __('Add'))
    ->setURL('/modules/Interventions/intervention_eligibility_contributor_add.php')
    ->addParam('gibbonINInterventionEligibilityAssessmentID', $assessment['gibbonINInterventionEligibilityAssessmentID'])
    ->addParam('gibbonINInterventionID', $gibbonINInterventionID)
    ->addParam('returnProcess', 'true')
    ->displayLabel();

// Add columns
$table->addColumn('name', __('Name'))
    ->format(Format::using('name', ['title', 'preferredName', 'surname', 'Staff', false, true]));

$table->addColumn('assessmentType', __('Assessment Type'))
    ->format(function ($values) {
        return !empty($values['assessmentType']) ? $values['assessmentType'] : '<span class="emphasis small">'.__('Not Selected').'</span>';
    });

$table->addColumn('status', __('Status'))
    ->format(function ($values) {
        if ($values['status'] == 'Pending') {
            return '<span class="tag dull">'.__('Pending').'</span>';
        } else {
            return '<span class="tag success">'.__('Complete').'</span>';
        }
    });

$table->addColumn('recommendation', __('Recommendation'));

// Add actions column
$table->addActionColumn()
    ->addParam('gibbonINInterventionEligibilityContributorID')
    ->addParam('gibbonINInterventionID', $gibbonINInterventionID)
    ->addParam('gibbonINInterventionEligibilityAssessmentID', $assessment['gibbonINInterventionEligibilityAssessmentID'])
    ->addParam('gibbonPersonIDStudent', '')
    ->addParam('gibbonFormGroupID', '')
    ->addParam('gibbonYearGroupID', '')
    ->addParam('status', '')
    ->addParam('returnProcess', 'true')
    ->format(function ($contributor, $actions) use ($guid, $isAdmin, $assessment, $gibbonPersonID) {
        // View action
        $actions->addAction('view', __('View'))
            ->setURL('/modules/Interventions/intervention_eligibility_contributor_edit.php')
            ->setIcon('page_white_text');

        // Edit action - only for the contributor or admin
        if ($isAdmin || $contributor['gibbonPersonIDContributor'] == $gibbonPersonID) {
            $actions->addAction('edit', __('Edit'))
                ->setURL('/modules/Interventions/intervention_eligibility_contributor_edit.php')
                ->setIcon('config');
        }
        
        // Delete action - available for all users if assessment is not complete
        if ($assessment['status'] != 'Complete') {
            $actions->addAction('delete', __('Delete'))
                ->setURL('/modules/Interventions/intervention_eligibility_contributor_delete.php')
                ->setIcon('garbage')
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
