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

use Gibbon\Domain\Interventions\INInterventionGateway;
use Gibbon\Domain\Interventions\INInterventionContributorGateway;
use Gibbon\Domain\Interventions\INInterventionStrategyGateway;
use Gibbon\Domain\Interventions\INInterventionUpdateGateway;
use Gibbon\Domain\Interventions\INEligibilityAssessmentGateway;
use Gibbon\Module\Interventions\Tables\InterventionHistory;

// Module includes
require_once __DIR__ . '/moduleFunctions.php';

// If this is the hook page, display the full intervention history
if (isset($_GET['hook']) && $_GET['hook'] == 'Interventions') {
    
    // Check access to the Interventions module
    if (isActionAccessible($guid, $connection2, '/modules/Interventions/interventions_manage.php', 'Student Profile Hook')) {
        // Include the InterventionHistory class
        include_once $session->get('absolutePath').'/modules/Interventions/src/Tables/InterventionHistory.php';
        
        // Get the student ID from the URL
        $gibbonPersonID = $_GET['gibbonPersonID'] ?? '';
        
        if (!empty($gibbonPersonID)) {
            // Create an instance of the InterventionHistory class manually
            $interventionGateway = $container->get(INInterventionGateway::class);
            $contributorGateway = $container->get(INInterventionContributorGateway::class);
            $strategyGateway = $container->get(INInterventionStrategyGateway::class);
            $updateGateway = $container->get(INInterventionUpdateGateway::class);
            $assessmentGateway = $container->get(INEligibilityAssessmentGateway::class);
            
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
        } else {
            echo __('No student selected.');
        }
    } else {
        echo __('You do not have access to this action.');
    }
} else {
    // This is the sidebar button display
    $output = '';

    // Check access to the Interventions module - using the action referenced in the hook options
    $interventionsAccess = isActionAccessible($guid, $connection2, '/modules/Interventions/interventions_manage.php', 'Student Profile Hook');
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
