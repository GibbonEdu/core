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

use Gibbon\Domain\Interventions\INInterventionEligibilityAssessmentGateway;

require_once '../../gibbon.php';

$gibbonINInterventionEligibilityAssessmentID = $_POST['gibbonINInterventionEligibilityAssessmentID'] ?? '';
$gibbonPersonID = $_POST['gibbonPersonID'] ?? '';
$gibbonFormGroupID = $_POST['gibbonFormGroupID'] ?? '';
$gibbonYearGroupID = $_POST['gibbonYearGroupID'] ?? '';
$status = $_POST['status'] ?? '';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Interventions/intervention_eligibility_manage.php&gibbonPersonID='.$gibbonPersonID.'&gibbonFormGroupID='.$gibbonFormGroupID.'&gibbonYearGroupID='.$gibbonYearGroupID.'&status='.$status;

if (isActionAccessible($guid, $connection2, '/modules/Interventions/intervention_eligibility_delete.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Get action with highest precedence
    $highestAction = getHighestGroupedAction($guid, '/modules/Interventions/intervention_eligibility_delete.php', $connection2);
    if (empty($highestAction) || $highestAction != 'Manage Eligibility Assessments') {
        $URL .= '&return=error0';
        header("Location: {$URL}");
        exit;
    } else {
        // Proceed!
        if (empty($gibbonINInterventionEligibilityAssessmentID)) {
            $URL .= '&return=error1';
            header("Location: {$URL}");
            exit;
        }
        
        $assessmentGateway = $container->get(INInterventionEligibilityAssessmentGateway::class);
        $assessment = $assessmentGateway->getByID($gibbonINInterventionEligibilityAssessmentID);
        
        if (empty($assessment)) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
            exit;
        }
        
        // First, delete all contributors
        $sql = "DELETE FROM gibbonINInterventionEligibilityContributor 
                WHERE gibbonINInterventionEligibilityAssessmentID=:gibbonINInterventionEligibilityAssessmentID";
        $pdo->execute($sql, ['gibbonINInterventionEligibilityAssessmentID' => $gibbonINInterventionEligibilityAssessmentID]);
        
        // Then delete the assessment
        $deleted = $assessmentGateway->delete($gibbonINInterventionEligibilityAssessmentID);
        
        if ($deleted) {
            $URL .= '&return=success0';
        } else {
            $URL .= '&return=error2';
        }
        
        header("Location: {$URL}");
        exit;
    }
}
