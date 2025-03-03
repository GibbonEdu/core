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
use Gibbon\Domain\Interventions\INInterventionEligibilityAssessmentGateway;

require_once '../../gibbon.php';

$gibbonINInterventionID = $_POST['gibbonINInterventionID'] ?? '';
$gibbonINInterventionEligibilityAssessmentID = $_POST['gibbonINInterventionEligibilityAssessmentID'] ?? '';
$gibbonINInterventionEligibilityContributorID = $_POST['gibbonINInterventionEligibilityContributorID'] ?? '';
$gibbonPersonID = $_POST['gibbonPersonID'] ?? '';
$gibbonFormGroupID = $_POST['gibbonFormGroupID'] ?? '';
$gibbonYearGroupID = $_POST['gibbonYearGroupID'] ?? '';
$status = $_POST['status'] ?? '';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Interventions/intervention_eligibility_contributor_edit.php&gibbonINInterventionEligibilityContributorID='.$gibbonINInterventionEligibilityContributorID.'&gibbonINInterventionEligibilityAssessmentID='.$gibbonINInterventionEligibilityAssessmentID.'&gibbonINInterventionID='.$gibbonINInterventionID.'&gibbonPersonID='.$gibbonPersonID.'&gibbonFormGroupID='.$gibbonFormGroupID.'&gibbonYearGroupID='.$gibbonYearGroupID.'&status='.$status;

if (isActionAccessible($guid, $connection2, '/modules/Interventions/intervention_eligibility_contributor_edit.php') == false) {
    // Access denied
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Get action with highest precedence
    $highestAction = getHighestGroupedAction($guid, '/modules/Interventions/intervention_eligibility_contributor_edit.php', $connection2);
    if (empty($highestAction)) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
        exit;
    }

    // Proceed!
    if (empty($gibbonINInterventionEligibilityAssessmentID) || empty($gibbonINInterventionID) || empty($gibbonINInterventionEligibilityContributorID)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Check if the contributor exists
    $sql = "SELECT c.*, a.gibbonPersonIDStudent, a.gibbonPersonIDCreator 
            FROM gibbonINInterventionEligibilityContributor AS c
            JOIN gibbonINInterventionEligibilityAssessment AS a ON (c.gibbonINInterventionEligibilityAssessmentID=a.gibbonINInterventionEligibilityAssessmentID)
            WHERE c.gibbonINInterventionEligibilityContributorID=:gibbonINInterventionEligibilityContributorID";
            
    $result = $pdo->select($sql, ['gibbonINInterventionEligibilityContributorID' => $gibbonINInterventionEligibilityContributorID]);
    
    if ($result->rowCount() != 1) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }
    
    $contributor = $result->fetch();
    
    // Get intervention details to check access
    $sql = "SELECT * FROM gibbonINIntervention WHERE gibbonINInterventionID=:gibbonINInterventionID";
    $intervention = $pdo->selectOne($sql, ['gibbonINInterventionID' => $gibbonINInterventionID]);
    
    if (empty($intervention)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }
    
    // Check access based on the highest action level
    if ($highestAction == 'Manage Interventions_my' && $intervention['gibbonPersonIDCreator'] != $session->get('gibbonPersonID')) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
        exit;
    }

    // Validate the required values are present
    $contributorStatus = $_POST['status'] ?? '';
    $recommendation = $_POST['recommendation'] ?? '';
    $notes = $_POST['notes'] ?? '';
    $gibbonINEligibilityAssessmentTypeID = $_POST['gibbonINEligibilityAssessmentTypeID'] ?? '';

    if (empty($contributorStatus) || empty($recommendation) || empty($gibbonINEligibilityAssessmentTypeID)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Update the contributor record
    try {
        $data = [
            'status' => $contributorStatus,
            'recommendation' => $recommendation,
            'notes' => $notes,
            'gibbonINEligibilityAssessmentTypeID' => $gibbonINEligibilityAssessmentTypeID,
            'timestampModified' => date('Y-m-d H:i:s')
        ];
        
        $sql = "UPDATE gibbonINInterventionEligibilityContributor 
                SET status=:status, recommendation=:recommendation, notes=:notes, gibbonINEligibilityAssessmentTypeID=:gibbonINEligibilityAssessmentTypeID, timestampModified=:timestampModified 
                WHERE gibbonINInterventionEligibilityContributorID=:gibbonINInterventionEligibilityContributorID";
                
        $result = $pdo->update($sql, array_merge($data, ['gibbonINInterventionEligibilityContributorID' => $gibbonINInterventionEligibilityContributorID]));
        
        if (!$result) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
            exit;
        }
        
        // Process subfield ratings if assessment type is selected
        if (!empty($gibbonINEligibilityAssessmentTypeID)) {
            // Get all active subfields for this assessment type
            $sql = "SELECT * FROM gibbonINEligibilityAssessmentSubfield 
                    WHERE gibbonINEligibilityAssessmentTypeID=:gibbonINEligibilityAssessmentTypeID 
                    AND active='Y'";
            $subfields = $pdo->select($sql, ['gibbonINEligibilityAssessmentTypeID' => $gibbonINEligibilityAssessmentTypeID])->fetchAll();
            
            if (!empty($subfields)) {
                // Delete existing ratings for this contributor
                $sql = "DELETE FROM gibbonINInterventionEligibilityContributorRating 
                        WHERE gibbonINInterventionEligibilityContributorID=:gibbonINInterventionEligibilityContributorID";
                $pdo->delete($sql, ['gibbonINInterventionEligibilityContributorID' => $gibbonINInterventionEligibilityContributorID]);
                
                // Insert new ratings
                foreach ($subfields as $subfield) {
                    $ratingKey = 'rating'.$subfield['gibbonINEligibilityAssessmentSubfieldID'];
                    $ratingValue = $_POST[$ratingKey] ?? '0';
                    
                    // Validate rating value (0-5)
                    if ($ratingValue < 0 || $ratingValue > 5) {
                        $ratingValue = 0;
                    }
                    
                    $data = [
                        'gibbonINInterventionEligibilityContributorID' => $gibbonINInterventionEligibilityContributorID,
                        'gibbonINEligibilityAssessmentSubfieldID' => $subfield['gibbonINEligibilityAssessmentSubfieldID'],
                        'rating' => $ratingValue,
                        'timestamp' => date('Y-m-d H:i:s')
                    ];
                    
                    $sql = "INSERT INTO gibbonINInterventionEligibilityContributorRating 
                            (gibbonINInterventionEligibilityContributorID, gibbonINEligibilityAssessmentSubfieldID, rating, timestamp) 
                            VALUES 
                            (:gibbonINInterventionEligibilityContributorID, :gibbonINEligibilityAssessmentSubfieldID, :rating, :timestamp)";
                            
                    $pdo->insert($sql, $data);
                }
            }
        }
        
        // If status is now Complete, send a notification to the assessment creator
        if ($contributorStatus == 'Complete' && $contributor['status'] != 'Complete') {
            // Get contributor name
            $sql = "SELECT title, preferredName, surname FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID";
            $contributorPerson = $pdo->selectOne($sql, ['gibbonPersonID' => $contributor['gibbonPersonIDContributor']]);
            
            if (!empty($contributorPerson)) {
                $contributorName = Format::name($contributorPerson['title'], $contributorPerson['preferredName'], $contributorPerson['surname'], 'Staff', false, true);
                
                // Get student name
                $sql = "SELECT title, preferredName, surname FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID";
                $student = $pdo->selectOne($sql, ['gibbonPersonID' => $contributor['gibbonPersonIDStudent']]);
                
                if (!empty($student)) {
                    $studentName = Format::name('', $student['preferredName'], $student['surname'], 'Student', true);
                    
                    // Send notification
                    $notificationGateway = $container->get(NotificationGateway::class);
                    $notificationText = sprintf(__('%1$s has completed their contribution to the eligibility assessment for %2$s.'), $contributorName, $studentName);
                    
                    $notificationSender = $container->get(NotificationGateway::class);
                    $notificationSender->addNotification($contributor['gibbonPersonIDCreator'], $notificationText, 'Interventions', '/index.php?q=/modules/Interventions/intervention_eligibility_edit.php&gibbonINInterventionEligibilityAssessmentID='.$gibbonINInterventionEligibilityAssessmentID.'&gibbonINInterventionID='.$gibbonINInterventionID);
                }
            }
        }
        
        // Success
        $URL = $session->get('absoluteURL').'/index.php?q=/modules/Interventions/intervention_eligibility_edit.php&gibbonINInterventionEligibilityAssessmentID='.$gibbonINInterventionEligibilityAssessmentID.'&gibbonINInterventionID='.$gibbonINInterventionID.'&gibbonPersonID='.$gibbonPersonID.'&gibbonFormGroupID='.$gibbonFormGroupID.'&gibbonYearGroupID='.$gibbonYearGroupID.'&status='.$status;
        $URL .= '&return=success0';
        header("Location: {$URL}");
        exit;
    } catch (Exception $e) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }
}
