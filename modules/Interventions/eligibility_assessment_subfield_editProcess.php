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

use Gibbon\Module\Interventions\Domain\INEligibilityAssessmentTypeGateway;

require_once '../../gibbon.php';

$gibbonINEligibilityAssessmentTypeID = $_POST['gibbonINEligibilityAssessmentTypeID'] ?? '';
$gibbonINEligibilityAssessmentSubfieldID = $_POST['gibbonINEligibilityAssessmentSubfieldID'] ?? '';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Interventions/eligibility_assessment_subfield_edit.php&gibbonINEligibilityAssessmentTypeID='.$gibbonINEligibilityAssessmentTypeID.'&gibbonINEligibilityAssessmentSubfieldID='.$gibbonINEligibilityAssessmentSubfieldID;

if (isActionAccessible($guid, $connection2, '/modules/Interventions/eligibility_assessment_subfield_edit.php') == false) {
    // Access denied
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    
    // Validate the required values are present
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $sequenceNumber = $_POST['sequenceNumber'] ?? '';
    $active = $_POST['active'] ?? '';
    
    if (empty($gibbonINEligibilityAssessmentTypeID) || empty($gibbonINEligibilityAssessmentSubfieldID) || empty($name) || empty($sequenceNumber) || empty($active)) {
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
    
    $subfield = $result->fetch();
    
    // Check if a different subfield with this name already exists for this assessment type
    $data = [
        'gibbonINEligibilityAssessmentTypeID' => $gibbonINEligibilityAssessmentTypeID,
        'name' => $name,
        'gibbonINEligibilityAssessmentSubfieldID' => $gibbonINEligibilityAssessmentSubfieldID
    ];
    
    $sql = "SELECT COUNT(*) FROM gibbonINEligibilityAssessmentSubfield 
            WHERE gibbonINEligibilityAssessmentTypeID=:gibbonINEligibilityAssessmentTypeID 
            AND name=:name 
            AND gibbonINEligibilityAssessmentSubfieldID<>:gibbonINEligibilityAssessmentSubfieldID";
    
    $result = $pdo->select($sql, $data);
    $count = $result->fetchColumn(0);
    
    if ($count > 0) {
        $URL .= '&return=error7';
        header("Location: {$URL}");
        exit;
    }
    
    // Update the subfield
    try {
        $data = [
            'name' => $name,
            'description' => $description,
            'sequenceNumber' => $sequenceNumber,
            'active' => $active,
            'gibbonINEligibilityAssessmentSubfieldID' => $gibbonINEligibilityAssessmentSubfieldID
        ];
        
        $sql = "UPDATE gibbonINEligibilityAssessmentSubfield 
                SET name=:name, description=:description, sequenceNumber=:sequenceNumber, active=:active 
                WHERE gibbonINEligibilityAssessmentSubfieldID=:gibbonINEligibilityAssessmentSubfieldID";
                
        $result = $pdo->update($sql, $data);
        
        $URL = $session->get('absoluteURL').'/index.php?q=/modules/Interventions/eligibility_assessment_types_edit.php&gibbonINEligibilityAssessmentTypeID='.$gibbonINEligibilityAssessmentTypeID;
        $URL .= '&return=success0';
        header("Location: {$URL}");
        exit;
    } catch (Exception $e) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }
}
