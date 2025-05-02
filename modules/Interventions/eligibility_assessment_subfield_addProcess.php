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

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Interventions/eligibility_assessment_types_edit.php&gibbonINEligibilityAssessmentTypeID='.$gibbonINEligibilityAssessmentTypeID;

if (isActionAccessible($guid, $connection2, '/modules/Interventions/eligibility_assessment_subfield_add.php') == false) {
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
    
    if (empty($gibbonINEligibilityAssessmentTypeID) || empty($name) || empty($sequenceNumber) || empty($active)) {
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
    
    // Check if a subfield with this name already exists for this assessment type
    $data = [
        'gibbonINEligibilityAssessmentTypeID' => $gibbonINEligibilityAssessmentTypeID,
        'name' => $name
    ];
    
    $sql = "SELECT COUNT(*) FROM gibbonINEligibilityAssessmentSubfield 
            WHERE gibbonINEligibilityAssessmentTypeID=:gibbonINEligibilityAssessmentTypeID 
            AND name=:name";
    
    $result = $pdo->select($sql, $data);
    $count = $result->fetchColumn(0);
    
    if ($count > 0) {
        $URL .= '&return=error7';
        header("Location: {$URL}");
        exit;
    }
    
    // Insert the new subfield
    try {
        $data = [
            'gibbonINEligibilityAssessmentTypeID' => $gibbonINEligibilityAssessmentTypeID,
            'name' => $name,
            'description' => $description,
            'sequenceNumber' => $sequenceNumber,
            'active' => $active
        ];
        
        $sql = "INSERT INTO gibbonINEligibilityAssessmentSubfield 
                (gibbonINEligibilityAssessmentTypeID, name, description, sequenceNumber, active) 
                VALUES 
                (:gibbonINEligibilityAssessmentTypeID, :name, :description, :sequenceNumber, :active)";
                
        $pdo->insert($sql, $data);
        
        $URL .= '&return=success0';
        header("Location: {$URL}");
        exit;
    } catch (Exception $e) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }
}
