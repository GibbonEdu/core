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

// Include Gibbon core
require_once '../../gibbon.php';

// Check if user is logged in
if (!$session->has('gibbonPersonID') || !$session->has('username')) {
    die(__('Your request failed because you do not have access to this action.'));
}

// Get parameters
$gibbonINEligibilityAssessmentTypeID = $_GET['gibbonINEligibilityAssessmentTypeID'] ?? '';
$gibbonINInterventionEligibilityContributorID = $_GET['gibbonINInterventionEligibilityContributorID'] ?? '';

if (empty($gibbonINEligibilityAssessmentTypeID)) {
    die(__('No assessment type specified.'));
}

// Debug information
error_log('AJAX request received for assessment type ID: ' . $gibbonINEligibilityAssessmentTypeID);
error_log('Contributor ID: ' . $gibbonINInterventionEligibilityContributorID);

// Get subfields for this assessment type
$sql = "SELECT * FROM gibbonINEligibilityAssessmentSubfield 
        WHERE gibbonINEligibilityAssessmentTypeID=:gibbonINEligibilityAssessmentTypeID 
        AND active='Y' 
        ORDER BY sequenceNumber";
$result = $pdo->select($sql, ['gibbonINEligibilityAssessmentTypeID' => $gibbonINEligibilityAssessmentTypeID]);

error_log('Subfields query: ' . $sql);
error_log('Subfields count: ' . ($result ? $result->rowCount() : 'No result'));

// Get existing ratings if contributor ID is provided
$ratings = [];
if (!empty($gibbonINInterventionEligibilityContributorID)) {
    $sql = "SELECT * FROM gibbonINInterventionEligibilityContributorRating 
            WHERE gibbonINInterventionEligibilityContributorID=:gibbonINInterventionEligibilityContributorID";
    $ratingResults = $pdo->select($sql, ['gibbonINInterventionEligibilityContributorID' => $gibbonINInterventionEligibilityContributorID]);
    
    if ($ratingResults && $ratingResults->rowCount() > 0) {
        while ($rating = $ratingResults->fetch()) {
            $ratings[$rating['gibbonINEligibilityAssessmentSubfieldID']] = $rating['rating'];
        }
    }
}

// Start output
$output = '';

if ($result && $result->rowCount() > 0) {
    $output .= '<h3>'.__('Assessment Ratings').'</h3>';
    
    // Add rating scale legend
    $output .= '<div class="mt-2 mb-4">
        <p><strong>'.__('Rating Scale Legend').':</strong></p>
        <div class="flex flex-col sm:flex-row">
            <div class="flex-1 bg-gray-100 border rounded p-2 mr-2 mb-2">
                <strong>0:</strong> '.__('Not Evaluated').'
            </div>
            <div class="flex-1 bg-gray-100 border rounded p-2 mr-2 mb-2">
                <strong>1:</strong> '.__('No Concern').'
            </div>
            <div class="flex-1 bg-gray-100 border rounded p-2 mr-2 mb-2">
                <strong>2:</strong> '.__('Mild Concern').'
            </div>
            <div class="flex-1 bg-gray-100 border rounded p-2 mr-2 mb-2">
                <strong>3:</strong> '.__('Moderate Concern').'
            </div>
            <div class="flex-1 bg-gray-100 border rounded p-2 mr-2 mb-2">
                <strong>4:</strong> '.__('Significant Concern').'
            </div>
            <div class="flex-1 bg-gray-100 border rounded p-2 mb-2">
                <strong>5:</strong> '.__('High Concern').'
            </div>
        </div>
    </div>';
    
    // Create a table for the subfields
    $output .= '<table class="smallIntBorder fullWidth colorOddEven" cellspacing="0">';
    
    // Add rating fields for each subfield
    while ($subfield = $result->fetch()) {
        $ratingOptions = [
            '0' => '0 - '.__('Not Evaluated'),
            '1' => '1 - '.__('No Concern'),
            '2' => '2 - '.__('Mild Concern'),
            '3' => '3 - '.__('Moderate Concern'),
            '4' => '4 - '.__('Significant Concern'),
            '5' => '5 - '.__('High Concern')
        ];
        
        $selectedRating = $ratings[$subfield['gibbonINEligibilityAssessmentSubfieldID']] ?? '0';
        
        $output .= '<tr>';
        $output .= '<td style="width: 33%">';
        $output .= '<b>'.$subfield['name'].'</b><br/>';
        $output .= '<span class="emphasis small">'.$subfield['description'].'</span>';
        $output .= '</td>';
        $output .= '<td>';
        $output .= '<select name="rating'.$subfield['gibbonINEligibilityAssessmentSubfieldID'].'" id="rating'.$subfield['gibbonINEligibilityAssessmentSubfieldID'].'" class="standardWidth">';
        
        foreach ($ratingOptions as $value => $label) {
            $selected = ($value == $selectedRating) ? 'selected' : '';
            $output .= '<option value="'.$value.'" '.$selected.'>'.$label.'</option>';
        }
        
        $output .= '</select>';
        $output .= '</td>';
        $output .= '</tr>';
    }
    
    $output .= '</table>';
} else {
    $output .= '<div class="warning">'.__('There are no subfields defined for this assessment type. Please contact an administrator.').'</div>';
}

echo $output;
