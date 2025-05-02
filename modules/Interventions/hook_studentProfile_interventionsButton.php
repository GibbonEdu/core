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

use Gibbon\Module\Interventions\Domain\INInterventionGateway;
use Gibbon\Module\Interventions\Domain\INInterventionContributorGateway;
use Gibbon\Module\Interventions\Domain\INInterventionStrategyGateway;
use Gibbon\Module\Interventions\Domain\INInterventionUpdateGateway;
use Gibbon\Module\Interventions\Domain\INInterventionEligibilityAssessmentGateway;
use Gibbon\Module\Interventions\Domain\INEligibilityAssessmentTypeGateway;
use Gibbon\Module\Interventions\Tables\InterventionHistory;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;

global $gibbon, $container;

// Include required gateway files
include_once $session->get('absolutePath').'/modules/Interventions/src/Domain/INInterventionUpdateGateway.php';
include_once $session->get('absolutePath').'/modules/Interventions/src/Domain/INInterventionGateway.php';
include_once $session->get('absolutePath').'/modules/Interventions/src/Domain/INInterventionContributorGateway.php';
include_once $session->get('absolutePath').'/modules/Interventions/src/Domain/INInterventionStrategyGateway.php';
include_once $session->get('absolutePath').'/modules/Interventions/src/Domain/INInterventionEligibilityAssessmentGateway.php';
include_once $session->get('absolutePath').'/modules/Interventions/src/Domain/INEligibilityAssessmentTypeGateway.php';
include_once $session->get('absolutePath').'/modules/Interventions/src/Tables/InterventionHistory.php';

// Check access
if (isActionAccessible($guid, $connection2, '/modules/Interventions/hook_studentProfile_interventionsButton.php', 'Student Profile Hook') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // If this is the hook page, display the full intervention history
    if (isset($_GET['hook']) && $_GET['hook'] == 'Interventions') {
        
        // Get the student ID from the URL
        $gibbonPersonID = $_GET['gibbonPersonID'] ?? '';
        
        if (!empty($gibbonPersonID)) {
            // Create an instance of the InterventionHistory class manually
            $interventionGateway = $container->get(INInterventionGateway::class);
            $contributorGateway = $container->get(INInterventionContributorGateway::class);
            $strategyGateway = $container->get(INInterventionStrategyGateway::class);
            $updateGateway = $container->get(INInterventionUpdateGateway::class);
            $assessmentGateway = $container->get(INInterventionEligibilityAssessmentGateway::class);
            $assessmentTypeGateway = $container->get(INEligibilityAssessmentTypeGateway::class);
            
            $interventionHistory = new InterventionHistory(
                $interventionGateway,
                $contributorGateway,
                $strategyGateway,
                $updateGateway,
                $assessmentGateway,
                $session
            );
            
            // Display the intervention history
            echo $interventionHistory->create($gibbonPersonID);
            
            // Add a section for detailed assessment ratings
            echo '<h2>' . __('Assessment Ratings') . '</h2>';
            
            // Get all eligibility assessments for this student
            $eligibilityAssessments = $assessmentGateway->getByStudentID($gibbonPersonID);
            
            if (!empty($eligibilityAssessments)) {
                foreach ($eligibilityAssessments as $assessment) {
                    echo '<h3>' . $assessment['interventionName'] . ' - ' . __('Assessment') . '</h3>';
                    
                    // Get all contributors for this assessment
                    $sql = "SELECT c.*, p.title, p.preferredName, p.surname, t.name as assessmentType 
                            FROM gibbonINInterventionEligibilityContributor as c
                            JOIN gibbonPerson as p ON p.gibbonPersonID=c.gibbonPersonIDContributor
                            LEFT JOIN gibbonINEligibilityAssessmentType as t ON t.gibbonINEligibilityAssessmentTypeID=c.gibbonINEligibilityAssessmentTypeID
                            WHERE c.gibbonINInterventionEligibilityAssessmentID=:assessmentID
                            ORDER BY t.name, p.surname, p.preferredName";
                    
                    $result = $pdo->select($sql, ['assessmentID' => $assessment['gibbonINInterventionEligibilityAssessmentID']]);
                    
                    if ($result->rowCount() > 0) {
                        $contributors = $result->fetchAll();
                        
                        // Group contributors by assessment type
                        $contributorsByType = [];
                        foreach ($contributors as $contributor) {
                            $type = $contributor['assessmentType'] ?? __('General');
                            if (!isset($contributorsByType[$type])) {
                                $contributorsByType[$type] = [];
                            }
                            $contributorsByType[$type][] = $contributor;
                        }
                        
                        // Display contributors and their ratings by assessment type
                        foreach ($contributorsByType as $type => $typeContributors) {
                            echo '<h4>' . $type . '</h4>';
                            
                            foreach ($typeContributors as $contributor) {
                                echo '<div class="column-no-break">';
                                echo '<h5>' . Format::name($contributor['title'], $contributor['preferredName'], $contributor['surname'], 'Staff') . '</h5>';
                                
                                // Get all ratings for this contributor
                                $sql = "SELECT r.*, s.name as subfieldName, s.description as subfieldDescription
                                        FROM gibbonINInterventionEligibilityContributorRating as r
                                        JOIN gibbonINEligibilityAssessmentSubfield as s ON s.gibbonINEligibilityAssessmentSubfieldID=r.gibbonINEligibilityAssessmentSubfieldID
                                        WHERE r.gibbonINInterventionEligibilityContributorID=:contributorID
                                        ORDER BY s.sequenceNumber";
                                
                                $ratingResult = $pdo->select($sql, ['contributorID' => $contributor['gibbonINInterventionEligibilityContributorID']]);
                                
                                if ($ratingResult->rowCount() > 0) {
                                    $ratings = $ratingResult->fetchAll();
                                    
                                    // Create a table to display the ratings
                                    echo '<table class="smallIntBorder fullWidth colorOddEven" cellspacing="0">';
                                    echo '<tr class="head">';
                                    echo '<th style="width: 40%;">' . __('Subfield') . '</th>';
                                    echo '<th style="width: 30%;">' . __('Rating') . '</th>';
                                    echo '<th style="width: 30%;">' . __('Comment') . '</th>';
                                    echo '</tr>';
                                    
                                    foreach ($ratings as $rating) {
                                        echo '<tr>';
                                        echo '<td>' . $rating['subfieldName'] . '<br/><span class="small emphasis">' . $rating['subfieldDescription'] . '</span></td>';
                                        
                                        // Format the rating with color coding
                                        $ratingText = '';
                                        $ratingColor = '';
                                        
                                        switch ($rating['rating']) {
                                            case 0:
                                                $ratingText = __('Not Evaluated');
                                                $ratingColor = 'dull';
                                                break;
                                            case 1:
                                                $ratingText = __('No Concern');
                                                $ratingColor = 'success';
                                                break;
                                            case 2:
                                                $ratingText = __('Mild Concern');
                                                $ratingColor = 'message';
                                                break;
                                            case 3:
                                                $ratingText = __('Moderate Concern');
                                                $ratingColor = 'warning';
                                                break;
                                            case 4:
                                                $ratingText = __('Significant Concern');
                                                $ratingColor = 'error';
                                                break;
                                            case 5:
                                                $ratingText = __('High Concern');
                                                $ratingColor = 'error';
                                                break;
                                        }
                                        
                                        echo '<td>' . Format::tag($ratingText, $ratingColor) . '</td>';
                                        echo '<td>' . $rating['comment'] . '</td>';
                                        echo '</tr>';
                                    }
                                    
                                    echo '</table>';
                                } else {
                                    echo '<div class="warning">' . __('No ratings have been recorded.') . '</div>';
                                }
                                
                                // Display the contributor's recommendation
                                echo '<div class="mt-2">';
                                echo '<strong>' . __('Recommendation') . ':</strong> ';
                                
                                $recommendationColor = 'dull';
                                if ($contributor['recommendation'] == 'Eligible for IEP') {
                                    $recommendationColor = 'success';
                                } elseif ($contributor['recommendation'] == 'Needs Intervention') {
                                    $recommendationColor = 'warning';
                                }
                                
                                echo Format::tag(__($contributor['recommendation']), $recommendationColor);
                                echo '</div>';
                                
                                // Display the contributor's notes
                                if (!empty($contributor['contribution'])) {
                                    echo '<div class="mt-2">';
                                    echo '<strong>' . __('Notes') . ':</strong> ';
                                    echo nl2br($contributor['contribution']);
                                    echo '</div>';
                                }
                                
                                echo '</div>';
                                echo '<hr/>';
                            }
                        }
                        
                        // Display the final decision
                        echo '<div class="success">';
                        echo '<h4>' . __('Final Decision') . '</h4>';
                        
                        $decisionColor = 'dull';
                        if ($assessment['eligibilityDecision'] == 'Eligible for IEP') {
                            $decisionColor = 'success';
                        } elseif ($assessment['eligibilityDecision'] == 'Needs Intervention') {
                            $decisionColor = 'warning';
                        }
                        
                        echo '<p><strong>' . __('Decision') . ':</strong> ' . Format::tag(__($assessment['eligibilityDecision']), $decisionColor) . '</p>';
                        
                        if (!empty($assessment['notes'])) {
                            echo '<p><strong>' . __('Notes') . ':</strong> ' . nl2br($assessment['notes']) . '</p>';
                        }
                        
                        echo '</div>';
                        
                    } else {
                        echo '<div class="warning">' . __('No contributors have been assigned to this assessment.') . '</div>';
                    }
                }
            } else {
                echo '<div class="warning">' . __('No eligibility assessments found for this student.') . '</div>';
            }
            
            // Add a legend for the rating scale
            echo '<div class="message">';
            echo '<h3>' . __('Rating Scale Legend') . '</h3>';
            echo '<p>' . __('The following scale is used for rating assessment subfields:') . '</p>';
            echo '<ul>';
            echo '<li><strong>0 - ' . __('Not Evaluated') . ':</strong> ' . __('This area has not been evaluated.') . '</li>';
            echo '<li><strong>1 - ' . __('No Concern') . ':</strong> ' . __('No concerns in this area.') . '</li>';
            echo '<li><strong>2 - ' . __('Mild Concern') . ':</strong> ' . __('Minor concerns that may need monitoring.') . '</li>';
            echo '<li><strong>3 - ' . __('Moderate Concern') . ':</strong> ' . __('Moderate concerns that require attention.') . '</li>';
            echo '<li><strong>4 - ' . __('Significant Concern') . ':</strong> ' . __('Significant concerns requiring intervention.') . '</li>';
            echo '<li><strong>5 - ' . __('High Concern') . ':</strong> ' . __('Severe concerns requiring immediate intervention.') . '</li>';
            echo '</ul>';
            echo '</div>';
            
        } else {
            echo __('No student selected.');
        }
    } else {
        // This is the sidebar button display
        $output = '';

        // Check access to the Interventions module - using the action referenced in the hook options
        $interventionsAccess = isActionAccessible($guid, $connection2, '/modules/Interventions/hook_studentProfile_interventionsButton.php', 'Student Profile Hook');
        $submitAccess = isActionAccessible($guid, $connection2, '/modules/Interventions/interventions_submit.php');

        // Only show the button if the user has access to the Interventions module
        if ($interventionsAccess || $submitAccess) {
            // Get the student ID from the URL
            $gibbonPersonID = $_GET['gibbonPersonID'] ?? '';
            
            if (!empty($gibbonPersonID)) {
                // Check if there are any existing interventions for this student
                $interventionGateway = $container->get(INInterventionGateway::class);
                $criteria = $interventionGateway->newQueryCriteria()
                    ->filterBy('gibbonPersonIDStudent', $gibbonPersonID);
                
                $interventions = $interventionGateway->queryInterventions($criteria, $session->get('gibbonSchoolYearID'));
                $interventionCount = $interventions->getResultCount();
                
                // Create the button with a badge showing the number of interventions
                $output .= '<div class="column-no-break">';
                $output .= '<h4>' . __('Interventions') . '</h4>';
                
                if ($interventionsAccess) {
                    $output .= '<a class="button" href="' . $session->get('absoluteURL') . '/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=' . $gibbonPersonID . '&hook=Interventions&module=Interventions&action=Student%20Profile%20Hook">';
                    $output .= __('View Interventions');
                    if ($interventionCount > 0) {
                        $output .= ' <span class="badge-notify">' . $interventionCount . '</span>';
                    }
                    $output .= '</a>';
                }
                
                if ($submitAccess) {
                    $output .= ' <a class="button" href="' . $session->get('absoluteURL') . '/index.php?q=/modules/Interventions/interventions_submit.php&gibbonPersonID=' . $gibbonPersonID . '">';
                    $output .= __('Submit Referral');
                    $output .= '</a>';
                }
                
                $output .= '</div>';
            }
        }

        return $output;
    }
}
