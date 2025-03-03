<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

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

use Gibbon\Domain\Interventions\INEligibilityAssessmentTypeGateway;

require_once '../../gibbon.php';

$gibbonINEligibilityAssessmentTypeID = $_POST['gibbonINEligibilityAssessmentTypeID'] ?? '';
$gibbonINEligibilityAssessmentSubfieldID = $_POST['gibbonINEligibilityAssessmentSubfieldID'] ?? '';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Interventions/eligibility_assessment_types_edit.php&gibbonINEligibilityAssessmentTypeID='.$gibbonINEligibilityAssessmentTypeID;

if (isActionAccessible($guid, $connection2, '/modules/Interventions/eligibility_assessment_subfield_delete.php') == false) {
    // Access denied
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    
    // Validate the required values are present
    if (empty($gibbonINEligibilityAssessmentTypeID) || empty($gibbonINEligibilityAssessmentSubfieldID)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }
    
    // Check if assessment type exists
    $assessmentTypeGateway = $container->get(INEligibilityAssessmentTypeGateway::class);
    $assessmentType = $assessmentTypeGateway->getByID($gibbonINEligibilityAssessmentTypeID);
    
    if (empty($assessmentType)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }
    
    // Check if the subfield exists
    $sql = "SELECT * FROM gibbonINEligibilityAssessmentSubfield 
            WHERE gibbonINEligibilityAssessmentSubfieldID=:gibbonINEligibilityAssessmentSubfieldID 
            AND gibbonINEligibilityAssessmentTypeID=:gibbonINEligibilityAssessmentTypeID";
            
    $result = $pdo->select($sql, [
        'gibbonINEligibilityAssessmentSubfieldID' => $gibbonINEligibilityAssessmentSubfieldID,
        'gibbonINEligibilityAssessmentTypeID' => $gibbonINEligibilityAssessmentTypeID
    ]);
    
    if ($result->rowCount() != 1) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }
    
    // Check if this subfield is in use
    $sql = "SELECT COUNT(*) FROM gibbonINInterventionEligibilityContributorRating 
            WHERE gibbonINEligibilityAssessmentSubfieldID=:gibbonINEligibilityAssessmentSubfieldID";
            
    $result = $pdo->select($sql, ['gibbonINEligibilityAssessmentSubfieldID' => $gibbonINEligibilityAssessmentSubfieldID]);
    $count = $result->fetchColumn(0);
    
    if ($count > 0) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }
    
    // Delete the subfield
    try {
        $sql = "DELETE FROM gibbonINEligibilityAssessmentSubfield 
                WHERE gibbonINEligibilityAssessmentSubfieldID=:gibbonINEligibilityAssessmentSubfieldID";
                
        $pdo->delete($sql, ['gibbonINEligibilityAssessmentSubfieldID' => $gibbonINEligibilityAssessmentSubfieldID]);
        
        $URL .= '&return=success0';
        header("Location: {$URL}");
        exit;
    } catch (Exception $e) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }
}
