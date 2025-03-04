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

$gibbonINInterventionID = $_POST['gibbonINInterventionID'] ?? '';
$gibbonINInterventionEligibilityAssessmentID = $_POST['gibbonINInterventionEligibilityAssessmentID'] ?? '';
$gibbonINInterventionEligibilityContributorID = $_POST['gibbonINInterventionEligibilityContributorID'] ?? '';
$gibbonPersonID = $_POST['gibbonPersonID'] ?? '';
$gibbonFormGroupID = $_POST['gibbonFormGroupID'] ?? '';
$gibbonYearGroupID = $_POST['gibbonYearGroupID'] ?? '';
$status = $_POST['status'] ?? '';
$returnProcess = $_POST['returnProcess'] ?? false;

// Get the redirect URL for error cases
$errorURL = $session->get('absoluteURL').'/index.php?q=/modules/Interventions/intervention_eligibility_contributor_edit.php&gibbonINInterventionEligibilityContributorID='.$gibbonINInterventionEligibilityContributorID.'&gibbonINInterventionEligibilityAssessmentID='.$gibbonINInterventionEligibilityAssessmentID.'&gibbonINInterventionID='.$gibbonINInterventionID.'&gibbonPersonID='.$gibbonPersonID.'&gibbonFormGroupID='.$gibbonFormGroupID.'&gibbonYearGroupID='.$gibbonYearGroupID.'&status='.$status;

if (isActionAccessible($guid, $connection2, '/modules/Interventions/intervention_eligibility_contributor_edit.php') == false) {
    // Access denied
    $errorURL .= '&return=error0';
    header("Location: {$errorURL}");
    exit;
} else {
    // Get action with highest precedence
    $highestAction = getHighestGroupedAction($guid, '/modules/Interventions/intervention_eligibility_contributor_edit.php', $connection2);
    if (empty($highestAction)) {
        $errorURL .= '&return=error0';
        header("Location: {$errorURL}");
        exit;
    }

    // Proceed!
    if (empty($gibbonINInterventionEligibilityAssessmentID) || empty($gibbonINInterventionID) || empty($gibbonINInterventionEligibilityContributorID)) {
        $errorURL .= '&return=error1';
        header("Location: {$errorURL}");
        exit;
    }

    // Check if the contributor exists
    $sql = "SELECT c.*, a.gibbonPersonIDStudent, a.gibbonPersonIDCreator 
            FROM gibbonINInterventionEligibilityContributor AS c
            JOIN gibbonINInterventionEligibilityAssessment AS a ON (c.gibbonINInterventionEligibilityAssessmentID=a.gibbonINInterventionEligibilityAssessmentID)
            WHERE c.gibbonINInterventionEligibilityContributorID=:gibbonINInterventionEligibilityContributorID";
            
    $result = $pdo->select($sql, ['gibbonINInterventionEligibilityContributorID' => $gibbonINInterventionEligibilityContributorID]);
    
    if ($result->rowCount() != 1) {
        $errorURL .= '&return=error2';
        header("Location: {$errorURL}");
        exit;
    }
    
    $contributor = $result->fetch();
    
    // Get intervention details to check access
    $sql = "SELECT * FROM gibbonINIntervention WHERE gibbonINInterventionID=:gibbonINInterventionID";
    $intervention = $pdo->selectOne($sql, ['gibbonINInterventionID' => $gibbonINInterventionID]);
    
    if (empty($intervention)) {
        $errorURL .= '&return=error2';
        header("Location: {$errorURL}");
        exit;
    }
    
    // Check access based on the highest action level
    if ($highestAction == 'Manage Interventions_my' && $intervention['gibbonPersonIDCreator'] != $session->get('gibbonPersonID')) {
        $errorURL .= '&return=error0';
        header("Location: {$errorURL}");
        exit;
    }

    // Validate the required values are present
    $contributorStatus = $_POST['status'] ?? '';
    $recommendation = $_POST['recommendation'] ?? '';
    $notes = $_POST['notes'] ?? '';
    $gibbonINEligibilityAssessmentTypeID = $_POST['gibbonINEligibilityAssessmentTypeID'] ?? '';

    error_log('Contributor edit process - POST data: ' . print_r($_POST, true));
    error_log('Assessment Type ID: ' . $gibbonINEligibilityAssessmentTypeID);

    if (empty($contributorStatus) || empty($gibbonINEligibilityAssessmentTypeID)) {
        error_log('Missing required fields: Status=' . $contributorStatus . ', AssessmentTypeID=' . $gibbonINEligibilityAssessmentTypeID);
        $errorURL .= '&return=error1';
        header("Location: {$errorURL}");
        exit;
    }

    // Update the contributor record
    try {
        $data = [
            'status' => $contributorStatus,
            'notes' => $notes,
            'gibbonINEligibilityAssessmentTypeID' => $gibbonINEligibilityAssessmentTypeID,
            'timestampModified' => date('Y-m-d H:i:s')
        ];
        
        // Only include recommendation if it's provided
        if (!empty($recommendation)) {
            $data['recommendation'] = $recommendation;
        }
        
        error_log('Updating contributor with data: ' . print_r($data, true));
        
        $contributorGateway = $container->get(INInterventionEligibilityContributorGateway::class);
        $updated = $contributorGateway->update($gibbonINInterventionEligibilityContributorID, $data);
        
        if (!$updated) {
            error_log('Failed to update contributor record');
            throw new Exception('Failed to update contributor');
        }
        
        // Process ratings for each subfield
        if (!empty($gibbonINEligibilityAssessmentTypeID)) {
            // Get all subfields for this assessment type
            $sql = "SELECT * FROM gibbonINEligibilityAssessmentSubfield 
                    WHERE gibbonINEligibilityAssessmentTypeID=:gibbonINEligibilityAssessmentTypeID 
                    AND active='Y'";
            $subfields = $pdo->select($sql, ['gibbonINEligibilityAssessmentTypeID' => $gibbonINEligibilityAssessmentTypeID]);
            
            error_log('Found ' . ($subfields ? $subfields->rowCount() : 0) . ' subfields for assessment type ' . $gibbonINEligibilityAssessmentTypeID);
            
            if ($subfields && $subfields->rowCount() > 0) {
                // First, delete any existing ratings
                $sql = "DELETE FROM gibbonINInterventionEligibilityContributorRating 
                        WHERE gibbonINInterventionEligibilityContributorID=:gibbonINInterventionEligibilityContributorID";
                $pdo->delete($sql, ['gibbonINInterventionEligibilityContributorID' => $gibbonINInterventionEligibilityContributorID]);
                
                error_log('Deleted existing ratings for contributor ' . $gibbonINInterventionEligibilityContributorID);
                
                // Then insert new ratings
                while ($subfield = $subfields->fetch()) {
                    $ratingKey = 'rating'.$subfield['gibbonINEligibilityAssessmentSubfieldID'];
                    $rating = $_POST[$ratingKey] ?? '0';
                    
                    error_log('Processing rating for subfield ' . $subfield['gibbonINEligibilityAssessmentSubfieldID'] . ': ' . $rating);
                    
                    try {
                        $sql = "INSERT INTO gibbonINInterventionEligibilityContributorRating 
                                (gibbonINInterventionEligibilityContributorID, gibbonINEligibilityAssessmentSubfieldID, rating) 
                                VALUES (:gibbonINInterventionEligibilityContributorID, :gibbonINEligibilityAssessmentSubfieldID, :rating)";
                        $pdo->insert($sql, [
                            'gibbonINInterventionEligibilityContributorID' => $gibbonINInterventionEligibilityContributorID,
                            'gibbonINEligibilityAssessmentSubfieldID' => $subfield['gibbonINEligibilityAssessmentSubfieldID'],
                            'rating' => $rating
                        ]);
                    } catch (Exception $e) {
                        error_log('Error inserting rating: ' . $e->getMessage());
                        throw $e;
                    }
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
                    
                    $notificationSender = new \Gibbon\Comms\NotificationSender($notificationGateway, $session);
                    $notificationSender->addNotification($contributor['gibbonPersonIDCreator'], $notificationText, 'Interventions', '/index.php?q=/modules/Interventions/intervention_eligibility_edit.php&gibbonINInterventionEligibilityAssessmentID='.$gibbonINInterventionEligibilityAssessmentID.'&gibbonINInterventionID='.$gibbonINInterventionID);
                    $notificationSender->sendNotifications();
                }
            }
        }
        
        // Success - Get the redirect URL
        $URL = getInterventionRedirectURL($session, $gibbonINInterventionID, $gibbonINInterventionEligibilityAssessmentID, $gibbonPersonID, $gibbonFormGroupID, $gibbonYearGroupID, $status, $returnProcess);
        $URL .= '&return=success0';
        header("Location: {$URL}");
        exit;
    } catch (Exception $e) {
        error_log('Error updating contributor: ' . $e->getMessage());
        $errorURL .= '&return=error2';
        header("Location: {$errorURL}");
        exit;
    }
}
