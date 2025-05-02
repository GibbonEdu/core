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

use Gibbon\Domain\System\NotificationGateway;
use Gibbon\Services\Format;
use Gibbon\Module\Interventions\Domain\INInterventionEligibilityAssessmentGateway;
use Gibbon\Module\Interventions\Domain\INInterventionEligibilityContributorGateway;

require_once '../../gibbon.php';
require_once __DIR__ . '/moduleFunctions.php';

$gibbonINInterventionEligibilityAssessmentID = $_POST['gibbonINInterventionEligibilityAssessmentID'] ?? '';
$gibbonINInterventionID = $_POST['gibbonINInterventionID'] ?? '';
$gibbonPersonIDContributor = $_POST['gibbonPersonIDContributor'] ?? '';
$gibbonINEligibilityAssessmentTypeID = $_POST['gibbonINEligibilityAssessmentTypeID'] ?? '';
$notes = $_POST['notes'] ?? '';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Interventions/interventions_contributor_dashboard.php';

if (isActionAccessible($guid, $connection2, '/modules/Interventions/intervention_eligibility_contributor_add_type.php') == false) {
    // Access denied
    $URL = $URL.'&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    
    // Check required fields
    if (empty($gibbonINInterventionEligibilityAssessmentID) || empty($gibbonINInterventionID) || empty($gibbonPersonIDContributor) || empty($gibbonINEligibilityAssessmentTypeID)) {
        $URL = $URL.'&return=error1';
        header("Location: {$URL}");
        exit;
    }
    
    // Check that the current user is the contributor
    if ($gibbonPersonIDContributor != $session->get('gibbonPersonID')) {
        $URL = $URL.'&return=error0';
        header("Location: {$URL}");
        exit;
    }
    
    // Check if the contributor already has an assessment of this type
    $sql = "SELECT COUNT(*) FROM gibbonINInterventionEligibilityContributor 
            WHERE gibbonINInterventionEligibilityAssessmentID=:gibbonINInterventionEligibilityAssessmentID 
            AND gibbonPersonIDContributor=:gibbonPersonIDContributor 
            AND gibbonINEligibilityAssessmentTypeID=:gibbonINEligibilityAssessmentTypeID";
    $result = $pdo->select($sql, [
        'gibbonINInterventionEligibilityAssessmentID' => $gibbonINInterventionEligibilityAssessmentID,
        'gibbonPersonIDContributor' => $gibbonPersonIDContributor,
        'gibbonINEligibilityAssessmentTypeID' => $gibbonINEligibilityAssessmentTypeID
    ]);
    
    if ($result->rowCount() > 0 && $result->fetchColumn() > 0) {
        $URL = $URL.'&return=error7';
        header("Location: {$URL}");
        exit;
    }
    
    try {
        // Insert the new contributor record
        $data = [
            'gibbonINInterventionEligibilityAssessmentID' => $gibbonINInterventionEligibilityAssessmentID,
            'gibbonPersonIDContributor' => $gibbonPersonIDContributor,
            'gibbonINEligibilityAssessmentTypeID' => $gibbonINEligibilityAssessmentTypeID,
            'notes' => $notes,
            'status' => 'Pending',
            'recommendation' => 'Pending',
            'timestampCreated' => date('Y-m-d H:i:s')
        ];
        
        $sql = "INSERT INTO gibbonINInterventionEligibilityContributor 
                (gibbonINInterventionEligibilityAssessmentID, gibbonPersonIDContributor, gibbonINEligibilityAssessmentTypeID, 
                notes, status, recommendation, timestampCreated) 
                VALUES 
                (:gibbonINInterventionEligibilityAssessmentID, :gibbonPersonIDContributor, :gibbonINEligibilityAssessmentTypeID, 
                :notes, :status, :recommendation, :timestampCreated)";
        
        $pdo->insert($sql, $data);
        $gibbonINInterventionEligibilityContributorID = $pdo->getConnection()->lastInsertID();
        
        // Get intervention details to update status if needed
        $sql = "SELECT i.status FROM gibbonINIntervention i 
                JOIN gibbonINInterventionEligibilityAssessment a ON a.gibbonINInterventionID = i.gibbonINInterventionID 
                WHERE a.gibbonINInterventionEligibilityAssessmentID = :assessmentID";
        $intervention = $pdo->selectOne($sql, ['assessmentID' => $gibbonINInterventionEligibilityAssessmentID]);
        
        // Update intervention status if it's still in Referral status
        if ($intervention && $intervention['status'] == 'Referral') {
            $sql = "UPDATE gibbonINIntervention SET status = 'Eligibility Assessment' 
                    WHERE gibbonINInterventionID = :gibbonINInterventionID";
            $pdo->update($sql, ['gibbonINInterventionID' => $gibbonINInterventionID]);
        }
        
        // Redirect to the edit page for the new contributor record
        $URL = $session->get('absoluteURL').'/index.php?q=/modules/Interventions/intervention_eligibility_contributor_edit.php';
        $URL .= '&gibbonINInterventionEligibilityContributorID='.$gibbonINInterventionEligibilityContributorID;
        $URL .= '&gibbonINInterventionEligibilityAssessmentID='.$gibbonINInterventionEligibilityAssessmentID;
        $URL .= '&gibbonINInterventionID='.$gibbonINInterventionID;
        $URL .= '&return=success0';
        
        header("Location: {$URL}");
        exit;
    } catch (Exception $e) {
        $URL = $URL.'&return=error2';
        header("Location: {$URL}");
        exit;
    }
}
